<?php
require_once 'config.php';
require_once 'includes/payment.php';
header('Content-Type: application/json; charset=utf-8');
try {
    if (!payment_verify_vnpay($conn, $_GET)) {
        echo json_encode(['RspCode' => '97', 'Message' => 'Invalid signature']);
        exit();
    }
    $code = (string)($_GET['vnp_TxnRef'] ?? '');
    $order = payment_get_order_by_code($conn, $code);
    if (!$order) {
        echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
        exit();
    }
    $amount = (int)($_GET['vnp_Amount'] ?? -1);
    $expected = (int)round((float)$order['total_money']) * 100;
    if ($amount !== $expected) {
        echo json_encode(['RspCode' => '04', 'Message' => 'invalid amount']);
        exit();
    }
    if ($order['payment_status'] === 'paid') {
        echo json_encode(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        exit();
    }
    if (($_GET['vnp_ResponseCode'] ?? '') === '00' && ($_GET['vnp_TransactionStatus'] ?? '') === '00') {
        payment_mark_result($conn, $order['id'], 'paid', (string)($_GET['vnp_TransactionNo'] ?? ''));
    } else {
        payment_mark_result($conn, $order['id'], 'failed');
    }
    echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
} catch (Throwable $e) {
    echo json_encode(['RspCode' => '99', 'Message' => 'Unknown error']);
}
?>
