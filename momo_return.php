<?php
require_once 'config.php';
require_once 'includes/payment.php';

$valid = payment_verify_momo($conn, $_GET);
$orderId = (string)($_GET['orderId'] ?? '');
$order = $orderId !== '' ? payment_get_order_by_code($conn, $orderId) : null;

if ($valid && $order) {
    $amount = (int)($_GET['amount'] ?? -1);
    $expected = (int)round((float)$order['total_money']);
    if ($amount === $expected && (string)($_GET['resultCode'] ?? '') === '0') {
        payment_mark_result($conn, $order['id'], 'paid', (string)($_GET['transId'] ?? ''));
        $_SESSION['payment_message'] = 'MoMo đã xác nhận giao dịch thành công.';
    } else {
        payment_mark_result($conn, $order['id'], 'failed');
        $_SESSION['payment_message'] = $amount !== $expected ? 'Số tiền MoMo trả về không khớp đơn hàng.' : 'Giao dịch MoMo chưa thành công.';
    }
} else {
    $_SESSION['payment_message'] = 'Không xác thực được kết quả trả về từ MoMo.';
}

if ($order) {
    header('Location: ' . $base_url . 'payment.php?order_id=' . (int)$order['id']);
} else {
    header('Location: ' . $base_url . 'don-hang');
}
exit();
?>
