<?php
/**
 * Các hàm dùng chung cho tính năng đánh giá bảng tin (news_reviews).
 * Khác với review sản phẩm (yêu cầu đơn hàng completed), ở đây chỉ cần
 * đăng nhập là được đánh giá, mỗi người chỉ đánh giá 1 lần / bài viết
 * (đảm bảo bởi UNIQUE KEY (user_id, news_id) ở tầng CSDL).
 */

/**
 * Kiểm tra user đã đánh giá bài viết này chưa.
 */
function has_reviewed_news($conn, $user_id, $news_id)
{
    $stmt = $conn->prepare('SELECT id FROM news_reviews WHERE user_id=? AND news_id=? LIMIT 1');
    $stmt->bind_param('ii', $user_id, $news_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Tổng hợp điểm đánh giá trung bình và số lượt đánh giá đang hiển thị (status=1) của 1 bài viết.
 * Trả về ['avg' => float, 'count' => int].
 */
function get_news_rating_summary($conn, $news_id)
{
    $stmt = $conn->prepare('SELECT COALESCE(AVG(rating),0) avg_rating, COUNT(*) total FROM news_reviews WHERE news_id=? AND status=1');
    $stmt->bind_param('i', $news_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['avg' => round((float)$row['avg_rating'], 1), 'count' => (int)$row['total']];
}

/**
 * Lấy danh sách đánh giá đang hiển thị (status=1) của 1 bài viết, kèm tên người dùng, mới nhất trước.
 */
function get_news_reviews($conn, $news_id)
{
    $stmt = $conn->prepare("SELECT nr.*, u.fullname FROM news_reviews nr JOIN users u ON nr.user_id=u.id WHERE nr.news_id=? AND nr.status=1 ORDER BY nr.created_at DESC");
    $stmt->bind_param('i', $news_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
