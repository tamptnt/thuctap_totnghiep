<?php
require_once 'config.php';
require_once 'includes/payment.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        echo json_encode(['resultCode' => 97, 'message' => 'Invalid payload']);
        exit();
    }
    if (!payment_verify_momo($conn, $data)) {
        echo json_encode(['resultCode' => 97, 'message' => 'Invalid signature']);
        exit();
    }
    $orderId = (string)($data['orderId'] ?? '');
    $order = payment_get_order_by_code($conn, $orderId);
    if (!$order) {
        echo json_encode(['resultCode' => 1, 'message' => 'Order not found']);
        exit();
    }
    $amount = (int)($data['amount'] ?? -1);
    $expected = (int)round((float)$order['total_money']);
    if ($amount !== $expected) {
        echo json_encode(['resultCode' => 4, 'message' => 'Invalid amount']);
        exit();
    }
    if ($order['payment_status'] === 'paid') {
        echo json_encode(['resultCode' => 2, 'message' => 'Order already confirmed']);
        exit();
    }
    if ((string)($data['resultCode'] ?? '') === '0') {
        payment_mark_result($conn, $order['id'], 'paid', (string)($data['transId'] ?? ''));
    } else {
        payment_mark_result($conn, $order['id'], 'failed');
    }
    echo json_encode(['resultCode' => 0, 'message' => 'Confirm Success']);
} catch (Throwable $e) {
    echo json_encode(['resultCode' => 99, 'message' => 'Unknown error']);
}
?>
