<?php
require_once 'config.php';
require_once 'includes/payment.php';
$valid = payment_verify_vnpay($conn, $_GET);
$code = (string)($_GET['vnp_TxnRef'] ?? '');
$order = $code !== '' ? payment_get_order_by_code($conn, $code) : null;
if ($valid && $order) {
    $amount = (int)($_GET['vnp_Amount'] ?? -1);
    $expected = (int)round((float)$order['total_money']) * 100;
    if ($amount === $expected && ($_GET['vnp_ResponseCode'] ?? '') === '00' && ($_GET['vnp_TransactionStatus'] ?? '') === '00') {
        payment_mark_result($conn, $order['id'], 'paid', (string)($_GET['vnp_TransactionNo'] ?? ''));
        $_SESSION['payment_message'] = 'VNPay đã xác nhận giao dịch thành công.';
    } else {
        payment_mark_result($conn, $order['id'], 'failed');
        $_SESSION['payment_message'] = $amount !== $expected ? 'Số tiền VNPay trả về không khớp đơn hàng.' : 'Giao dịch VNPay chưa thành công.';
    }
} else {
    $_SESSION['payment_message'] = 'Không xác thực được kết quả trả về từ VNPay.';
}
if ($order) {
    header('Location: ' . $base_url . 'payment.php?order_id=' . (int)$order['id']);
} else {
    header('Location: ' . $base_url . 'don-hang');
}
exit();
?>
