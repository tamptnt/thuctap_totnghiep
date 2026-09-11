<?php
/**
 * Các hàm dùng chung để ghi lại và hiển thị lịch sử trạng thái đơn hàng.
 * Mỗi lần trạng thái đơn hàng (status) thay đổi, hoặc có sự kiện đáng chú ý
 * (thanh toán online thành công/thất bại...), hệ thống ghi 1 dòng vào
 * order_status_history để khách hàng và admin theo dõi được toàn bộ vòng đời đơn hàng.
 */

/**
 * Ghi 1 mốc lịch sử cho đơn hàng.
 * @param string      $status     Trạng thái tại thời điểm ghi (pending/processing/completed/canceled...).
 * @param string|null $note       Ghi chú thêm, ví dụ "Thanh toán VNPAY thành công".
 * @param string|null $changed_by Ai/cái gì thực hiện thay đổi, ví dụ "Admin: admin", "Nguyễn Văn A (khách hàng)", "Cổng thanh toán".
 */
function log_order_status($conn, $order_id, $status, $note = null, $changed_by = null)
{
    $stmt = $conn->prepare('INSERT INTO order_status_history (order_id,status,note,changed_by,created_at) VALUES (?,?,?,?,NOW())');
    $stmt->bind_param('isss', $order_id, $status, $note, $changed_by);
    $stmt->execute();
    $stmt->close();
}

/**
 * Lấy toàn bộ lịch sử trạng thái của 1 đơn hàng, theo thứ tự thời gian tăng dần.
 */
function get_order_status_history($conn, $order_id)
{
    $stmt = $conn->prepare('SELECT * FROM order_status_history WHERE order_id=? ORDER BY created_at ASC, id ASC');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Nhãn tiếng Việt hiển thị cho từng trạng thái đơn hàng.
 */
function order_status_label($status)
{
    $map = [
        'pending' => 'Chờ xác nhận',
        'processing' => 'Đang xử lý',
        'completed' => 'Hoàn thành',
        'canceled' => 'Đã hủy',
    ];
    return $map[$status] ?? ucfirst((string)$status);
}

/**
 * Tên hiển thị của người thực hiện thay đổi, dùng khi ghi log từ khu vực quản trị.
 */
function order_history_admin_actor()
{
    $name = $_SESSION['fullname'] ?? 'Admin';
    return 'Admin: ' . $name;
}

/**
 * Tên hiển thị của khách hàng, dùng khi ghi log từ khu vực khách hàng.
 */
function order_history_customer_actor($fullname)
{
    return trim((string)$fullname) . ' (khách hàng)';
}
