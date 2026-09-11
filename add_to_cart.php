<?php
ob_start();
session_start();
require_once 'config.php';

$is_ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
    || isset($_POST['ajax']);

function cartResponse($status, $message, $extra = [])
{
    global $is_ajax;
    $payload = array_merge([
        'status' => $status,
        'message' => $message
    ], $extra);

    if ($is_ajax) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['cart_flash'] = [
        'status' => $status,
        'message' => $message
    ];

    $redirect = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartResponse('error', 'Yêu cầu không hợp lệ.');
}
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    cartResponse('error', 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.');
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$variant_id = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    cartResponse('error', 'Dữ liệu không hợp lệ.');
}

$stmt = $conn->prepare("SELECT name, image, price, sale_price, stock_quantity FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    cartResponse('error', 'Sản phẩm không tồn tại.');
}

$product = $result->fetch_assoc();
$stmt->close();

$variant = null;
if ($variant_id > 0) {
    $variant = get_variant_with_product($conn, $variant_id, $product_id);
    if (!$variant) {
        cartResponse('error', 'Phiên bản sản phẩm không hợp lệ hoặc đã ngừng bán.');
    }
}

if ($variant) {
    $eff = variant_effective_price($product['price'], $product['sale_price'], $variant);
    $price = ($eff['sale_price'] !== null && $eff['sale_price'] < $eff['price']) ? $eff['sale_price'] : $eff['price'];
    $stock = (int)$variant['stock_quantity'];
    $displayName = $product['name'] . ' (' . $variant['variant_name'] . ')';
} else {
    $price = (float)$product['sale_price'] > 0 ? (float)$product['sale_price'] : (float)$product['price'];
    $stock = (int)$product['stock_quantity'];
    $displayName = $product['name'];
}

if ($stock <= 0) {
    cartResponse('error', 'Sản phẩm đang tạm hết hàng.');
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Khóa giỏ hàng theo cặp sản phẩm-biến thể (biến thể khác nhau là dòng khác nhau trong giỏ).
$cart_key = $variant ? $product_id . '-' . $variant_id : (string)$product_id;

$current_qty = isset($_SESSION['cart'][$cart_key]) ? (int)$_SESSION['cart'][$cart_key]['quantity'] : 0;
$new_qty = $current_qty + $quantity;

if ($new_qty > $stock) {
    cartResponse(
        'error',
        'Số lượng vượt quá tồn kho. Trong giỏ bạn đã có ' . $current_qty . ' sản phẩm này.'
    );
}

$_SESSION['cart'][$cart_key] = [
    'product_id' => $product_id,
    'variant_id' => $variant ? $variant_id : null,
    'variant_name' => $variant ? $variant['variant_name'] : null,
    'name' => $displayName,
    'image' => $product['image'],
    'price' => $price,
    'quantity' => $new_qty,
    'max_stock' => $stock
];

cartResponse('success', 'Đã thêm sản phẩm vào giỏ hàng.', [
    'cart_count' => count($_SESSION['cart']),
    'product_name' => $displayName,
    'quantity' => $new_qty
]);
?>