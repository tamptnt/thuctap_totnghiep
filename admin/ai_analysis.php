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
$model = trim((string)($input['model'] ?? gemini_get_setting($conn, 'gemini_dashboard_model', 'gemini-3.5-flash')));

$summary = [];
$summary['total_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$summary['completed_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status='completed'")->fetch_assoc()['c'];
$summary['pending_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$summary['processing_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status='processing'")->fetch_assoc()['c'];
$summary['canceled_orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status='canceled'")->fetch_assoc()['c'];
$summary['completed_revenue'] = (float)($conn->query("SELECT COALESCE(SUM(total_money),0) c FROM orders WHERE status='completed'")->fetch_assoc()['c']);
$summary['customers'] = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];
$summary['products'] = (int)$conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$summary['low_stock'] = (int)$conn->query("SELECT COUNT(*) c FROM products WHERE stock_quantity<=10")->fetch_assoc()['c'];
$summary['out_of_stock'] = (int)$conn->query("SELECT COUNT(*) c FROM products WHERE stock_quantity<=0")->fetch_assoc()['c'];

$months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $row = $conn->query("SELECT COUNT(*) orders_count,COALESCE(SUM(CASE WHEN status='completed' THEN total_money ELSE 0 END),0) revenue FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='" . $conn->real_escape_string($key) . "'")->fetch_assoc();
    $months[] = ['month' => $key, 'orders' => (int)$row['orders_count'], 'revenue' => (float)$row['revenue']];
}

$lowStock = [];
$q = $conn->query("SELECT id,name,stock_quantity,sales_count FROM products ORDER BY stock_quantity ASC,sales_count DESC LIMIT 8");
while ($row = $q->fetch_assoc()) {
    $lowStock[] = $row;
}

$topProducts = [];
$q = $conn->query("SELECT id,name,sales_count,stock_quantity FROM products ORDER BY sales_count DESC LIMIT 8");
while ($row = $q->fetch_assoc()) {
    $topProducts[] = $row;
}

$dataText = json_encode(['summary' => $summary, 'months' => $months, 'low_stock_products' => $lowStock, 'top_products' => $topProducts], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$prompt = "Phân tích dữ liệu vận hành của cửa hàng công nghệ dưới đây. Chỉ suy luận từ dữ liệu được cung cấp, không bịa số. Ưu tiên xu hướng doanh thu, tình trạng đơn hàng, tồn kho, sản phẩm bán chạy, rủi ro thiếu hàng và hành động nên làm. Trả về JSON đúng cấu trúc: {\"summary\":\"2-4 câu\",\"conclusions\":[\"...\"],\"warnings\":[{\"level\":\"high|medium|low\",\"title\":\"...\",\"detail\":\"...\"}],\"actions\":[\"...\"]}. Dữ liệu: " . $dataText;
$result = gemini_text($conn, $prompt, $model, 'Bạn là chuyên gia phân tích vận hành thương mại điện tử và quản trị tồn kho. Viết tiếng Việt ngắn gọn, cụ thể, có số liệu khi phù hợp.', true, 5000);
if (!$result['ok']) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}
$data = json_decode($result['text'], true);
if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'AI trả về dữ liệu chưa đúng định dạng. Hãy thử lại.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => 'success', 'analysis' => $data, 'model' => $result['model']], JSON_UNESCAPED_UNICODE);
