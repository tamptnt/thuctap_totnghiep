<?php
/**
 * Các hàm dùng chung cho tính năng đổi/trả hàng.
 *
 * Quy tắc nghiệp vụ:
 * - Chỉ đơn hàng ở trạng thái 'completed' mới được gửi yêu cầu đổi trả.
 * - Chỉ trong vòng RETURN_WINDOW_DAYS ngày kể từ ngày đơn hàng hoàn thành.
 * - Mỗi dòng sản phẩm (order_items) chỉ được yêu cầu tối đa bằng số lượng đã mua,
 *   trừ đi số lượng đã yêu cầu trước đó (không tính yêu cầu đã bị từ chối/huỷ).
 *
 * Dữ liệu lưu ở bảng `order_returns` (xem migration_returns.sql).
 */

/** Số ngày cho phép gửi yêu cầu đổi trả, tính từ lúc đơn hàng hoàn thành. */
if (!defined('RETURN_WINDOW_DAYS')) {
    define('RETURN_WINDOW_DAYS', 7);
}

/** Các trạng thái yêu cầu vẫn còn hiệu lực (được tính vào số lượng đã yêu cầu). */
if (!defined('RETURN_ACTIVE_STATUSES_SQL')) {
    define('RETURN_ACTIVE_STATUSES_SQL', "'pending','approved','completed'");
}

/**
 * Biểu thức SQL lấy thời điểm đơn hàng được đánh dấu hoàn thành.
 * Ưu tiên mốc 'completed' mới nhất trong order_status_history,
 * nếu chưa có thì tạm lấy ngày tạo đơn.
 */
function return_completed_at_expr($orderAlias = 'o')
{
    return "COALESCE(
                (SELECT MAX(h.created_at) FROM order_status_history h
                 WHERE h.order_id = {$orderAlias}.id AND h.status = 'completed'),
                {$orderAlias}.created_at
            )";
}

/**
 * Thời điểm đơn hàng được đánh dấu hoàn thành (chuỗi datetime) hoặc null.
 */
function get_order_completed_at($conn, $order_id)
{
    $expr = return_completed_at_expr('o');
    $stmt = $conn->prepare("SELECT $expr AS completed_at FROM orders o WHERE o.id = ? LIMIT 1");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? $row['completed_at'] : null;
}

/**
 * Số ngày còn lại để gửi yêu cầu đổi trả. Trả về 0 nếu đã hết hạn.
 */
function return_days_left($completed_at)
{
    if (!$completed_at) return 0;
    $deadline = strtotime($completed_at) + RETURN_WINDOW_DAYS * 86400;
    return max(0, (int)ceil(($deadline - time()) / 86400));
}

/**
 * Số lượng đã gửi yêu cầu đổi trả cho 1 dòng sản phẩm trong đơn.
 */
function get_requested_return_quantity($conn, $order_item_id)
{
    $statuses = RETURN_ACTIVE_STATUSES_SQL;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM order_returns
                            WHERE order_item_id = ? AND status IN ($statuses)");
    $stmt->bind_param('i', $order_item_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['total'];
}

/**
 * Các dòng sản phẩm trong 1 đơn còn có thể yêu cầu đổi trả (remaining_quantity > 0).
 */
function get_returnable_order_items($conn, $order_id)
{
    $statuses = RETURN_ACTIVE_STATUSES_SQL;
    $sql = "SELECT oi.id, oi.order_id, oi.product_id, oi.variant_id, oi.variant_name,
                   oi.quantity, oi.price,
                   p.name AS product_name, p.image AS product_image,
                   COALESCE((SELECT SUM(r.quantity) FROM order_returns r
                             WHERE r.order_item_id = oi.id AND r.status IN ($statuses)), 0) AS requested_quantity
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $items = [];
    foreach ($rows as $row) {
        $remaining = (int)$row['quantity'] - (int)$row['requested_quantity'];
        if ($remaining <= 0) continue;
        $row['remaining_quantity'] = $remaining;
        $items[] = $row;
    }
    return $items;
}

/**
 * Danh sách đơn hàng của khách còn đủ điều kiện gửi yêu cầu đổi trả.
 * Mỗi phần tử: ['id', 'completed_at', 'days_left', 'items' => [...]].
 * Đơn không còn sản phẩm nào có thể yêu cầu sẽ bị loại khỏi kết quả.
 */
function get_returnable_orders($conn, $user_id)
{
    $window = RETURN_WINDOW_DAYS;
    $expr = return_completed_at_expr('o');
    $sql = "SELECT t.id, t.completed_at FROM (
                SELECT o.id, $expr AS completed_at
                FROM orders o
                WHERE o.user_id = ? AND o.status = 'completed'
            ) t
            WHERE t.completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY t.completed_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $window);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $result = [];
    foreach ($orders as $order) {
        $items = get_returnable_order_items($conn, (int)$order['id']);
        if (!$items) continue;
        $order['days_left'] = return_days_left($order['completed_at']);
        $order['items'] = $items;
        $result[] = $order;
    }
    return $result;
}

/**
 * Lấy 1 dòng sản phẩm đủ điều kiện đổi trả của đúng khách hàng đang đăng nhập.
 * Trả về null nếu không thuộc về khách, đơn chưa hoàn thành, hoặc đã quá hạn.
 */
function get_eligible_order_item($conn, $order_item_id, $user_id)
{
    $window = RETURN_WINDOW_DAYS;
    $expr = return_completed_at_expr('o');
    $sql = "SELECT * FROM (
                SELECT oi.id, oi.order_id, oi.product_id, oi.variant_id, oi.variant_name,
                       oi.quantity, oi.price,
                       p.name AS product_name, p.image AS product_image,
                       $expr AS completed_at
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN products p ON p.id = oi.product_id
                WHERE oi.id = ? AND o.user_id = ? AND o.status = 'completed'
            ) t
            WHERE t.completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $order_item_id, $user_id, $window);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Tạo mới 1 yêu cầu đổi trả. Trả về id vừa tạo, hoặc 0 nếu thất bại.
 */
function create_return_request($conn, array $data)
{
    $sql = "INSERT INTO order_returns
            (order_id, order_item_id, user_id, product_id, variant_id, variant_name,
             quantity, type, reason, description, status, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,'pending',NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;

    $order_id      = (int)$data['order_id'];
    $order_item_id = (int)$data['order_item_id'];
    $user_id       = (int)$data['user_id'];
    $product_id    = (int)$data['product_id'];
    $variant_id    = isset($data['variant_id']) && $data['variant_id'] !== null ? (int)$data['variant_id'] : null;
    $variant_name  = $data['variant_name'] ?? null;
    $quantity      = (int)$data['quantity'];
    $type          = ($data['type'] ?? 'return') === 'exchange' ? 'exchange' : 'return';
    $reason        = (string)$data['reason'];
    $description   = $data['description'] ?? null;

    $stmt->bind_param(
        'iiiiisisss',
        $order_id, $order_item_id, $user_id, $product_id, $variant_id,
        $variant_name, $quantity, $type, $reason, $description
    );
    $ok = $stmt->execute();
    $id = $ok ? (int)$stmt->insert_id : 0;
    $stmt->close();
    return $id;
}

/**
 * Khách tự huỷ yêu cầu của mình, chỉ khi cửa hàng chưa xử lý (status = 'pending').
 */
function cancel_return_request($conn, $return_id, $user_id)
{
    $stmt = $conn->prepare("UPDATE order_returns SET status = 'canceled', processed_at = NOW()
                            WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param('ii', $return_id, $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0;
}

/**
 * Danh sách yêu cầu đổi trả của 1 khách hàng, mới nhất trước.
 */
function get_user_returns($conn, $user_id)
{
    $sql = "SELECT r.*, p.name AS product_name, p.image AS product_image
            FROM order_returns r
            JOIN products p ON p.id = r.product_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC, r.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Nhãn tiếng Việt cho loại yêu cầu. */
function return_type_label($type)
{
    return $type === 'exchange' ? 'Đổi hàng' : 'Trả hàng - hoàn tiền';
}

/** Nhãn tiếng Việt cho trạng thái yêu cầu. */
function return_status_label($status)
{
    $map = [
        'pending'   => 'Chờ xử lý',
        'approved'  => 'Đã duyệt',
        'rejected'  => 'Bị từ chối',
        'completed' => 'Hoàn tất',
        'canceled'  => 'Đã huỷ',
    ];
    return $map[$status] ?? ucfirst((string)$status);
}
