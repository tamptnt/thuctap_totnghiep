<?php
function gemini_model_options()
{
    return [
        'gemini-3.8-flash' => 'Gemini 3.8 Flash',
        'gemini-3.7-flash' => 'Gemini 3.7 Flash',
        'gemini-3.6-flash' => 'Gemini 3.6 Flash',
        'gemini-3.5-flash' => 'Gemini 3.5 Flash',
        'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash-Lite',
        'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash-Lite',
        'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro Preview'
    ];
}

function gemini_legacy_models()
{
    return [
        'gemini-2.0-flash' => 'Gemini 2.0 Flash',
        'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash-Lite'
    ];
}

function gemini_get_setting($conn, $key, $default = '')
{
    return settings_get($conn, $key, $default);
}

function gemini_set_setting($conn, $key, $value, $icon = 'fas fa-robot')
{
    return settings_set($conn, $key, $value, $icon);
}

function gemini_http_post($url, $body, $apiKey, $timeout = 15)
{
    $status = 0;
    $response = false;
    $transportError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => max(3, (int)$timeout)
        ]);
        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            $transportError = curl_error($ch);
        }
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nx-goog-api-key: {$apiKey}\r\n",
                'content' => $body,
                'timeout' => max(3, (int)$timeout),
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int)$match[1];
        }
        if ($response === false) {
            $transportError = 'Không thể kết nối Gemini API từ máy chủ.';
        }
    }

    return [$response, $status, $transportError];
}

function gemini_request($conn, $contents, $model = '', $systemInstruction = '', $jsonMode = false, $maxOutputTokens = 4096)
{
    $apiKey = trim(gemini_get_setting($conn, 'gemini_api_key', ''));
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Chưa cấu hình Gemini API key trong Cài đặt hệ thống.'];
    }

    $activeModels = gemini_model_options();
    $legacyModels = gemini_legacy_models();
    $model = trim($model);
    if ($model === '') {
        $model = gemini_get_setting($conn, 'gemini_chat_model', 'gemini-3.5-flash');
    }
    if (isset($legacyModels[$model])) {
        return ['ok' => false, 'error' => 'Model Gemini 2.0 đã ngừng cung cấp trên Gemini API. Hãy chọn model 2.5, 3.1 hoặc mới hơn.'];
    }
    if (!isset($activeModels[$model])) {
        $model = 'gemini-3.5-flash';
    }

    $payload = ['contents' => $contents];
    if ($systemInstruction !== '') {
        $payload['system_instruction'] = ['parts' => [['text' => $systemInstruction]]];
    }
    $payload['generationConfig'] = ['maxOutputTokens' => max(256, min(65536, (int)$maxOutputTokens))];
    if ($jsonMode) {
        $payload['generationConfig']['responseMimeType'] = 'application/json';
    }
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Model chính, sau đó các model dự phòng (chỉ dùng model có trong danh sách hợp lệ).
    $candidates = [$model];
    foreach (['gemini-3.5-flash-lite', 'gemini-3.1-flash-lite', 'gemini-3.6-flash'] as $fallback) {
        if (isset($activeModels[$fallback]) && !in_array($fallback, $candidates, true)) {
            $candidates[] = $fallback;
        }
    }

    @set_time_limit(40);
    $deadline = microtime(true) + 22; // tổng thời gian tối đa cho mọi lần thử
    $retryable = [429, 500, 502, 503, 504];
    $data = null;
    $usedModel = $model;
    $lastError = 'Không thể kết nối Gemini API.';
    $lastStatus = 0;
    $attempts = [];

    foreach ($candidates as $candidate) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($candidate) . ':generateContent';
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $remaining = $deadline - microtime(true);
            if ($remaining < 3) {
                break 2; // hết ngân sách thời gian, dừng để trả lỗi rõ ràng thay vì bị hosting ngắt kết nối
            }
            list($response, $status, $transportError) = gemini_http_post($url, $body, $apiKey, min(15, (int)floor($remaining)));

            if ($response === false) {
                $attempts[] = [$candidate, 0, $transportError];
                $lastStatus = 0;
                $lastError = $transportError !== '' ? $transportError : 'Không thể kết nối Gemini API.';
                usleep(500000);
                continue;
            }

            $decoded = json_decode($response, true);
            if ($status >= 200 && $status < 300 && is_array($decoded)) {
                $data = $decoded;
                $usedModel = $candidate;
                break 2;
            }

            $lastStatus = $status;
            $lastError = $decoded['error']['message'] ?? ('Gemini API trả về lỗi HTTP ' . $status . '.');
            $attempts[] = [$candidate, $status, mb_substr((string)$lastError, 0, 160)];

            if ($status === 404 || $status === 429) {
                break; // model không tồn tại hoặc hết quota: đổi sang model kế tiếp
            }
            if (!in_array($status, $retryable, true)) {
                break 2; // lỗi cấu hình (sai key, sai định dạng...): dừng hẳn
            }
            usleep(500000); // 500/502/503/504: đợi rồi thử lại cùng model
        }
    }

    if ($data === null) {
        if (in_array($lastStatus, $retryable, true) || microtime(true) >= $deadline - 3) {
            $lastError = 'Hệ thống AI đang quá tải, bạn vui lòng thử lại sau ít phút nhé.';
        }
        return ['ok' => false, 'error' => $lastError, 'attempts' => $attempts];
    }

    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    $text = '';
    foreach ($parts as $part) {
        if (isset($part['text'])) {
            $text .= $part['text'];
        }
    }
    $text = trim($text);
    if ($text === '') {
        $reason = $data['candidates'][0]['finishReason'] ?? '';
        return ['ok' => false, 'error' => $reason !== '' ? 'Gemini không tạo được nội dung. Trạng thái: ' . $reason : 'Gemini không trả về nội dung.'];
    }

    return ['ok' => true, 'text' => $text, 'model' => $usedModel, 'raw' => $data];
}

function gemini_text($conn, $prompt, $model = '', $systemInstruction = '', $jsonMode = false, $maxOutputTokens = 4096)
{
    return gemini_request($conn, [['role' => 'user', 'parts' => [['text' => $prompt]]]], $model, $systemInstruction, $jsonMode, $maxOutputTokens);
}

function gemini_clean_plain_text($text)
{
    $text = (string)$text;
    $text = str_replace(['**', '__', '```json', '```html', '```'], '', $text);
    $text = preg_replace('/^#{1,6}\s*/m', '', $text);
    $text = preg_replace('/^[\t ]*[-*][\t ]+/m', '• ', $text);
    $text = preg_replace("/\r\n?|\n/", "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function gemini_decode_json($text)
{
    $text = trim((string)$text);
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/', '', $text);
    $data = json_decode($text, true);
    if (is_array($data)) {
        return $data;
    }
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $candidate = substr($text, $start, $end - $start + 1);
        $data = json_decode($candidate, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return null;
}
