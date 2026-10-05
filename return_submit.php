<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base_url . 'doi-tra-hang');
    exit();
}

csrf_guard();

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

function returns_flash_and_redirect($type, $text, $base_url)
{
    $_SESSION['msg'] = ['type' => $type, 'text' => $text];
    header('Location: ' . $base_url . 'doi-tra-hang');
    exit();
}

if ($action === 'cancel') {
    $return_id = (int)($_POST['return_id'] ?? 0);
    if ($return_id > 0 && cancel_return_request($conn, $return_id, $user_id)) {
        returns_flash_and_redirect('success', 'Đã huỷ yêu cầu đổi trả.', $base_url);
    }
    returns_flash_and_redirect('error', 'Không thể huỷ yêu cầu này (có thể đã được xử lý).', $base_url);
}

if ($action === 'create') {
    $order_item_id = (int)($_POST['order_item_id'] ?? 0);
    $type = ($_POST['type'] ?? 'return') === 'exchange' ? 'exchange' : 'return';
    $reason = trim((string)($_POST['reason'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($order_item_id <= 0 || $quantity < 1 || $reason === '') {
        returns_flash_and_redirect('error', 'Dữ liệu yêu cầu không hợp lệ.', $base_url);
    }
    if (mb_strlen($description) > 1000) {
        returns_flash_and_redirect('error', 'Mô tả quá dài (tối đa 1000 ký tự).', $base_url);
    }

    $item = get_eligible_order_item($conn, $order_item_id, $user_id);
    if (!$item) {
        returns_flash_and_redirect('error', 'Sản phẩm này không (còn) đủ điều kiện đổi trả.', $base_url);
    }

    $requested = get_requested_return_quantity($conn, $order_item_id);
    $remaining = (int)$item['quantity'] - $requested;
    if ($quantity > $remaining) {
        returns_flash_and_redirect('error', 'Số lượng yêu cầu vượt quá số lượng còn có thể đổi trả (' . $remaining . ').', $base_url);
    }

    $return_id = create_return_request($conn, [
        'order_id' => (int)$item['order_id'],
        'order_item_id' => $order_item_id,
        'user_id' => $user_id,
        'product_id' => (int)$item['product_id'],
        'variant_id' => $item['variant_id'] !== null ? (int)$item['variant_id'] : null,
        'variant_name' => $item['variant_name'],
        'quantity' => $quantity,
        'type' => $type,
        'reason' => $reason,
        'description' => $description !== '' ? $description : null,
    ]);

    if ($return_id) {
        $logMessage = 'Khách hàng gửi yêu cầu ' . ($type === 'exchange' ? 'đổi hàng' : 'trả hàng') . ' cho đơn hàng ' . $item['order_id'];
        $logStmt = $conn->prepare('INSERT INTO purchase_logs (order_id,log_message,created_at) VALUES (?,?,NOW())');
        $logStmt->bind_param('is', $item['order_id'], $logMessage);
        $logStmt->execute();
        $logStmt->close();

        returns_flash_and_redirect('success', 'Đã gửi yêu cầu đổi trả. Cửa hàng sẽ xử lý sớm nhất.', $base_url);
    }

    returns_flash_and_redirect('error', 'Không thể gửi yêu cầu. Vui lòng thử lại.', $base_url);
}

header('Location: ' . $base_url . 'doi-tra-hang');
exit();
