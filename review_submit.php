<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base_url . 'san-pham');
    exit();
}

csrf_guard();

$product_id = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));
$user_id = (int)$_SESSION['user_id'];

$redirect = $base_url . 'san-pham/' . $product_id . '#reviews';
// Nếu có slug thật của sản phẩm thì dùng link đẹp hơn để quay lại đúng trang.
$stmtName = $conn->prepare('SELECT name FROM products WHERE id=?');
$stmtName->bind_param('i', $product_id);
$stmtName->execute();
$productRow = $stmtName->get_result()->fetch_assoc();
$stmtName->close();
if ($productRow) {
    $redirect = $base_url . 'san-pham/' . createSlugForReview($productRow['name']) . '-' . $product_id . '#reviews';
}

function createSlugForReview($str)
{
    $u = ['a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ', 'd' => 'đ', 'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ', 'i' => 'í|ì|ỉ|ĩ|ị', 'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ', 'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự', 'y' => 'ý|ỳ|ỷ|ỹ|ỵ'];
    foreach ($u as $n => $v) $str = preg_replace("/($v)/i", $n, $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    return trim(preg_replace('/-+/', '-', $str), '-');
}

function flashAndRedirect($status, $message, $redirect)
{
    $_SESSION['review_flash'] = ['status' => $status, 'message' => $message];
    header('Location: ' . $redirect);
    exit();
}

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    flashAndRedirect('error', 'Dữ liệu đánh giá không hợp lệ.', $redirect);
}

if (mb_strlen($comment) > 1000) {
    flashAndRedirect('error', 'Nội dung đánh giá quá dài (tối đa 1000 ký tự).', $redirect);
}

$order_id = find_completed_order_for_review($conn, $user_id, $product_id);
if (!$order_id) {
    flashAndRedirect('error', 'Bạn cần mua và nhận sản phẩm này (đơn hàng đã hoàn thành) mới có thể đánh giá.', $redirect);
}

if (has_reviewed($conn, $user_id, $product_id)) {
    flashAndRedirect('error', 'Bạn đã đánh giá sản phẩm này rồi.', $redirect);
}

$stmt = $conn->prepare('INSERT INTO reviews (product_id,user_id,order_id,rating,comment,status) VALUES (?,?,?,?,?,1)');
$stmt->bind_param('iiiis', $product_id, $user_id, $order_id, $rating, $comment);

if ($stmt->execute()) {
    flashAndRedirect('success', 'Cảm ơn bạn đã đánh giá sản phẩm!', $redirect);
} else {
    // Lỗi 1062 = trùng UNIQUE KEY (user_id, product_id) do gửi trùng lúc chạy song song.
    flashAndRedirect('error', 'Không thể lưu đánh giá. Có thể bạn đã đánh giá sản phẩm này rồi.', $redirect);
}
