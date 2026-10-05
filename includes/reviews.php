<?php
/**
 * Các hàm dùng chung cho tính năng đánh giá/review sản phẩm.
 * Nguyên tắc: chỉ khách hàng có đơn hàng ở trạng thái 'completed' chứa
 * đúng sản phẩm đó mới được đánh giá, và mỗi người chỉ đánh giá 1 lần / sản phẩm
 * (đảm bảo bởi UNIQUE KEY (user_id, product_id) ở tầng CSDL).
 */

/**
 * Tìm 1 đơn hàng đã hoàn thành (completed) của user chứa sản phẩm này, nếu có.
 * Trả về order_id hoặc null nếu chưa từng mua/chưa hoàn thành.
 */
function find_completed_order_for_review($conn, $user_id, $product_id)
{
    $stmt = $conn->prepare("SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.user_id=? AND oi.product_id=? AND o.status='completed' ORDER BY o.created_at DESC LIMIT 1");
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id'] : null;
}

/**
 * Kiểm tra user đã đánh giá sản phẩm này chưa.
 */
function has_reviewed($conn, $user_id, $product_id)
{
    $stmt = $conn->prepare('SELECT id FROM reviews WHERE user_id=? AND product_id=? LIMIT 1');
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Tổng hợp điểm đánh giá trung bình và số lượt đánh giá đang hiển thị (status=1) của 1 sản phẩm.
 * Trả về ['avg' => float, 'count' => int].
 */
function get_product_rating_summary($conn, $product_id)
{
    $stmt = $conn->prepare('SELECT COALESCE(AVG(rating),0) avg_rating, COUNT(*) total FROM reviews WHERE product_id=? AND status=1');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['avg' => round((float)$row['avg_rating'], 1), 'count' => (int)$row['total']];
}

/**
 * Lấy danh sách đánh giá đang hiển thị (status=1) của 1 sản phẩm, kèm tên khách hàng, mới nhất trước.
 */
function get_product_reviews($conn, $product_id)
{
    $stmt = $conn->prepare("SELECT r.*, u.fullname FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.product_id=? AND r.status=1 ORDER BY r.created_at DESC");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Che bớt tên khách hàng để bảo vệ quyền riêng tư khi hiển thị công khai, ví dụ "Nguyễn Văn A" -> "Nguyễn Văn A".
 * Chỉ giữ lại chữ cái đầu của từ cuối, các phần khác giữ nguyên (đủ để nhận diện nhưng không lộ toàn bộ).
 */
function mask_reviewer_name($fullname)
{
    $fullname = trim((string)$fullname);
    if ($fullname === '') return 'Khách hàng';
    $parts = preg_split('/\s+/u', $fullname);
    $last = array_pop($parts);
    $maskedLast = mb_substr($last, 0, 1) . str_repeat('*', max(1, mb_strlen($last) - 1));
    $parts[] = $maskedLast;
    return implode(' ', $parts);
}
