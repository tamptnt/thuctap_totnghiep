<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base_url . 'tin-tuc');
    exit();
}

csrf_guard();

$news_id = (int)($_POST['news_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));
$user_id = (int)$_SESSION['user_id'];

function createSlugForNewsReview($str)
{
    $u = ['a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ', 'd' => 'đ', 'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ', 'i' => 'í|ì|ỉ|ĩ|ị', 'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ', 'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự', 'y' => 'ý|ỳ|ỷ|ỹ|ỵ'];
    foreach ($u as $n => $v) $str = preg_replace("/($v)/i", $n, $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    return trim(preg_replace('/-+/', '-', $str), '-');
}

$redirect = $base_url . 'tin-tuc/' . $news_id . '#news-reviews';
$stmtName = $conn->prepare('SELECT title FROM news WHERE id=?');
$stmtName->bind_param('i', $news_id);
$stmtName->execute();
$newsRow = $stmtName->get_result()->fetch_assoc();
$stmtName->close();
if ($newsRow) {
    $redirect = $base_url . 'tin-tuc/' . createSlugForNewsReview($newsRow['title']) . '-' . $news_id . '#news-reviews';
}

function flashAndRedirectNews($status, $message, $redirect)
{
    $_SESSION['news_review_flash'] = ['status' => $status, 'message' => $message];
    header('Location: ' . $redirect);
    exit();
}

if ($news_id <= 0 || $rating < 1 || $rating > 5) {
    flashAndRedirectNews('error', 'Dữ liệu đánh giá không hợp lệ.', $redirect);
}

if (mb_strlen($comment) > 1000) {
    flashAndRedirectNews('error', 'Nội dung đánh giá quá dài (tối đa 1000 ký tự).', $redirect);
}

if (!$newsRow) {
    flashAndRedirectNews('error', 'Bài viết không tồn tại.', $redirect);
}

if (has_reviewed_news($conn, $user_id, $news_id)) {
    flashAndRedirectNews('error', 'Bạn đã đánh giá bài viết này rồi.', $redirect);
}

$stmt = $conn->prepare('INSERT INTO news_reviews (news_id,user_id,rating,comment,status) VALUES (?,?,?,?,1)');
$stmt->bind_param('iiis', $news_id, $user_id, $rating, $comment);

if ($stmt->execute()) {
    flashAndRedirectNews('success', 'Cảm ơn bạn đã đánh giá bài viết!', $redirect);
} else {
    // Lỗi 1062 = trùng UNIQUE KEY (user_id, news_id) do gửi trùng lúc chạy song song.
    flashAndRedirectNews('error', 'Không thể lưu đánh giá. Có thể bạn đã đánh giá bài viết này rồi.', $redirect);
}
