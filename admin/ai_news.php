<?php
require_once '../config.php';
require_once '../includes/gemini.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Không có quyền truy cập.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$topic = trim((string)($input['topic'] ?? ''));
$title = trim((string)($input['title'] ?? ''));
$model = gemini_get_setting($conn, 'gemini_writer_model', 'gemini-3.5-flash');
$length = trim((string)($input['length'] ?? 'medium'));
$style = trim((string)($input['style'] ?? 'technical'));

if ($topic === '' && $title === '') {
    echo json_encode(['status' => 'error', 'message' => 'Hãy nhập chủ đề hoặc tiêu đề bài viết.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$lengthMap = ['short' => '500-700 từ', 'medium' => '800-1100 từ', 'long' => '1200-1600 từ'];
$styleMap = ['technical' => 'chuyên nghiệp, dễ hiểu, có tính kỹ thuật', 'guide' => 'hướng dẫn từng bước thực tế', 'seo' => 'tự nhiên, dễ đọc, hỗ trợ SEO nhưng không nhồi từ khóa'];
$targetLength = $lengthMap[$length] ?? $lengthMap['medium'];
$targetStyle = $styleMap[$style] ?? $styleMap['technical'];

$prompt = "Viết một bài tin tức hoàn chỉnh cho website bán đồ công nghệ. Chủ đề: {$topic}. Tiêu đề hiện có: {$title}. Văn phong: {$targetStyle}. Độ dài: {$targetLength}. Nội dung phải chính xác, hữu ích, có mở bài, các mục nội dung rõ ràng và kết luận. Không tự khẳng định giá hoặc thông số sản phẩm cụ thể nếu đề bài không cung cấp. Trả về duy nhất một JSON object có đúng ba khóa title, excerpt, content. title là tiêu đề tự nhiên. excerpt gồm 1-2 câu. content là HTML sạch chỉ dùng các thẻ p, h2, h3, ul, ol, li, strong, table, thead, tbody, tr, th, td khi cần. Không dùng markdown, không bọc JSON trong code fence, không dùng thẻ html, body, script hoặc style.";
$system = 'Bạn là biên tập viên nội dung công nghệ chuyên viết về sản phẩm công nghệ, thiết bị số, phần cứng, kết nối, hiệu năng, bảo hành và xu hướng công nghệ. Luôn tuân thủ đúng định dạng JSON được yêu cầu.';

$result = gemini_text($conn, $prompt, $model, $system, true, 16000);
if (!$result['ok'] && $model !== 'gemini-2.5-flash') {
    $fallback = gemini_text($conn, $prompt, 'gemini-2.5-flash', $system, true, 16000);
    if ($fallback['ok']) {
        $result = $fallback;
    }
}

if (!$result['ok']) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = gemini_decode_json($result['text']);
if (!is_array($data)) {
    $retryPrompt = "Viết bài cho website bán đồ công nghệ về chủ đề: {$topic}. Tiêu đề gợi ý: {$title}. Văn phong: {$targetStyle}. Độ dài: {$targetLength}. Không dùng markdown. Trả về đúng ba phần theo mẫu sau và không thêm nội dung ngoài mẫu:
[TITLE]
Tiêu đề
[EXCERPT]
Mô tả ngắn 1-2 câu
[CONTENT]
Nội dung HTML chỉ dùng p, h2, h3, ul, ol, li, strong, table, thead, tbody, tr, th, td.";
    $retry = gemini_text($conn, $retryPrompt, $result['model'], 'Bạn là biên tập viên nội dung công nghệ chuyên viết về sản phẩm công nghệ, thiết bị số, phần cứng, kết nối, hiệu năng, bảo hành và xu hướng công nghệ.', false, 16000);
    if ($retry['ok'] && preg_match('/\[TITLE\]\s*(.*?)\s*\[EXCERPT\]\s*(.*?)\s*\[CONTENT\]\s*(.*)$/is', $retry['text'], $match)) {
        $data = ['title' => trim($match[1]), 'excerpt' => trim($match[2]), 'content' => trim($match[3])];
        $result = $retry;
    }
}
if (!is_array($data)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'AI đã phản hồi nhưng nội dung chưa đúng định dạng. Vui lòng bấm viết lại.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$generatedTitle = trim((string)($data['title'] ?? $title));
$excerpt = trim((string)($data['excerpt'] ?? ''));
$content = trim((string)($data['content'] ?? ''));
$content = str_replace(['**', '```html', '```'], '', $content);
$content = preg_replace('#<(script|style|iframe)\b[^>]*>.*?</\1>#is', '', $content);
$content = strip_tags($content, '<p><h2><h3><ul><ol><li><strong><table><thead><tbody><tr><th><td>');

if ($generatedTitle === '' || $content === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'AI chưa tạo đủ tiêu đề và nội dung. Hãy thử lại.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => 'success',
    'title' => $generatedTitle,
    'excerpt' => $excerpt,
    'content' => $content,
    'model' => $result['model']
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
