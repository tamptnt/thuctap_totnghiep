<?php
// Trang chẩn đoán chatbot - chỉ dành cho admin. Xóa file này sau khi kiểm tra xong.
require_once '../config.php';
require_once '../includes/gemini.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Chỉ admin được xem trang này.');
}
header('Content-Type: text/plain; charset=utf-8');
@ini_set('display_errors', '0');

$key = trim(gemini_get_setting($conn, 'gemini_api_key', ''));
echo "PHP: " . PHP_VERSION . "\n";
echo "cURL: " . (function_exists('curl_init') ? 'co' : 'KHONG') . "\n";
echo "API key: " . ($key !== '' ? 'da cau hinh (' . strlen($key) . ' ky tu)' : 'CHUA cau hinh') . "\n";
echo "Model chat: " . gemini_get_setting($conn, 'gemini_chat_enabled', '1') . ' / ' . gemini_get_setting($conn, 'gemini_chat_model', 'gemini-3.5-flash') . "\n\n";

$t = microtime(true);
$r = gemini_text($conn, 'Trả lời đúng một từ: OK', '', '', false, 256);
$sec = round(microtime(true) - $t, 1);
echo "Thoi gian goi Gemini: {$sec}s\n";
if ($r['ok']) {
    echo "Ket qua: THANH CONG (model " . $r['model'] . ")\n";
    echo "Tra loi: " . $r['text'] . "\n";
} else {
    echo "Ket qua: LOI\n";
    echo "Chi tiet: " . $r['error'] . "\n";
    foreach (($r['attempts'] ?? []) as $a) {
        echo " - thu model {$a[0]}: HTTP {$a[1]} {$a[2]}\n";
    }
}
