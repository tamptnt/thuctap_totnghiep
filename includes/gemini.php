<?php
function gemini_model_options()
{
    return [
        'gemini-3.6-flash' => 'Gemini 3.6 Flash',
        'gemini-3.5-flash' => 'Gemini 3.5 Flash',
        'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash-Lite',
        'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash-Lite',
        'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro Preview',
        'gemini-2.5-pro' => 'Gemini 2.5 Pro',
        'gemini-2.5-flash' => 'Gemini 2.5 Flash',
        'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite'
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

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60
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
                'timeout' => 60,
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

    if ($response === false) {
        return ['ok' => false, 'error' => $transportError !== '' ? $transportError : 'Không thể kết nối Gemini API.'];
    }

    $data = json_decode($response, true);
    if ($status < 200 || $status >= 300) {
        $message = $data['error']['message'] ?? 'Gemini API trả về lỗi HTTP ' . $status . '.';
        return ['ok' => false, 'error' => $message];
    }
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Phản hồi từ Gemini không hợp lệ.'];
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

    return ['ok' => true, 'text' => $text, 'model' => $model, 'raw' => $data];
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
