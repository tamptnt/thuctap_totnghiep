<?php
require_once 'config.php';
require_once 'includes/payment.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

$orderId = (int)($_GET['order_id'] ?? 0);
$order = payment_get_order($conn, $orderId, (int)$_SESSION['user_id']);
if (!$order) {
    header('Location: ' . $base_url . 'don-hang');
    exit();
}

if ($order['status'] === 'canceled' || $order['payment_status'] === 'paid') {
    header('Location: ' . $base_url . 'payment.php?order_id=' . $orderId);
    exit();
}

if (!in_array($order['payment_method'], ['vnpay', 'momo'], true)) {
    header('Location: ' . $base_url . 'don-hang');
    exit();
}

$result = $order['payment_method'] === 'momo'
    ? payment_create_momo_url($conn, $order)
    : payment_create_vnpay_url($conn, $order);
if (!empty($result['ok']) && !empty($result['url'])) {
    header('Location: ' . $result['url']);
    exit();
}

payment_mark_result($conn, $orderId, 'failed');
$_SESSION['payment_message'] = $result['message'] ?? 'Không thể khởi tạo giao dịch thanh toán.';
header('Location: ' . $base_url . 'payment.php?order_id=' . $orderId);
exit();
?>
