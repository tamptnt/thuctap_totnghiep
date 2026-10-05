<?php
require_once 'config.php';
require_once 'includes/gemini.php';
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors', '0');
$__t0 = microtime(true);
@set_time_limit(40);
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['status' => 'error', 'message' => 'Chatbot gặp lỗi máy chủ, vui lòng thử lại sau.'], JSON_UNESCAPED_UNICODE);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$now = time();
$rate = array_values(array_filter($_SESSION['gemini_chat_rate'] ?? [], function ($timestamp) use ($now) { return $timestamp > $now - 60; }));
if (count($rate) >= 12) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn gửi câu hỏi quá nhanh. Vui lòng thử lại sau ít phút.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$rate[] = $now;
$_SESSION['gemini_chat_rate'] = $rate;

if (gemini_get_setting($conn, 'gemini_chat_enabled', '1') !== '1') {
    echo json_encode(['status' => 'error', 'message' => 'Chatbot AI đang tạm tắt.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Nhận dữ liệu dạng form (tránh bị WAF của host chặn JSON), vẫn hỗ trợ JSON cũ
if (isset($_POST['message'])) {
    $hist = json_decode((string)($_POST['history'] ?? '[]'), true);
    $input = ['message' => $_POST['message'], 'history' => is_array($hist) ? $hist : []];
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
}
$message = trim((string)($input['message'] ?? ''));
$model = gemini_get_setting($conn, 'gemini_chat_model', 'gemini-3.5-flash');
$history = is_array($input['history'] ?? null) ? $input['history'] : [];

if ($message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa nhập câu hỏi.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (mb_strlen($message) > 1500) {
    echo json_encode(['status' => 'error', 'message' => 'Câu hỏi quá dài, vui lòng rút gọn dưới 1.500 ký tự.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$catalog = [];
$q = $conn->query("SELECT p.id,p.name,p.price,p.sale_price,p.stock_quantity,p.warranty_text,c.name category_name,b.name brand_name FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN brands b ON p.brand_id=b.id ORDER BY p.is_hot DESC,p.sales_count DESC,p.id DESC LIMIT 40");
while ($row = $q->fetch_assoc()) {
    $price = (float)$row['sale_price'] > 0 ? (float)$row['sale_price'] : (float)$row['price'];
    $catalog[] = $row['id'] . ' | ' . $row['name'] . ' | ' . $row['brand_name'] . ' | ' . $row['category_name'] . ' | ' . number_format($price, 0, ',', '.') . ' đ | tồn ' . (int)$row['stock_quantity'] . ' | ' . $row['warranty_text'];
}

$system = "Bạn là trợ lý bán hàng của Showroom. Trả lời bằng tiếng Việt, rõ ràng, dễ đọc, thân thiện và tập trung vào sản phẩm công nghệ, cấu hình, hiệu năng, màn hình, kết nối, thời lượng pin, sản phẩm, mua hàng, giao hàng và bảo hành. Chỉ dùng giá, tồn kho và thông tin sản phẩm trong dữ liệu cửa hàng bên dưới. Không tự bịa sản phẩm, giá hay tồn kho. Nếu thiếu dữ liệu, nói rõ và hướng khách liên hệ tư vấn. Khi đề xuất sản phẩm công nghệ, ưu tiên mục đích sử dụng, ngân sách, hệ điều hành, hiệu năng, màn hình, kết nối, thời lượng pin và khả năng tương thích với thiết bị hiện có. Không dùng Markdown, không dùng dấu **, không dùng ký hiệu tiêu đề #. Có thể đánh số 1., 2., 3. và xuống dòng để nội dung dễ đọc. Dữ liệu cửa hàng:\n" . implode("\n", $catalog);

$contents = [];
$history = array_slice($history, -8);
foreach ($history as $turn) {
    $role = ($turn['role'] ?? '') === 'model' ? 'model' : 'user';
    $text = trim((string)($turn['text'] ?? ''));
    if ($text !== '') {
        $contents[] = ['role' => $role, 'parts' => [['text' => mb_substr($text, 0, 1200)]]];
    }
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

$result = gemini_request($conn, $contents, $model, $system, false, 1800);
if (!$result['ok']) {
    echo json_encode(['status' => 'error', 'message' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$text = gemini_clean_plain_text($result['text']);
echo json_encode(['status' => 'success', 'message' => $text, 'model' => $result['model'], 'ms' => (int)((microtime(true) - $__t0) * 1000)], JSON_UNESCAPED_UNICODE);
