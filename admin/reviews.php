<?php
include 'header.php';

$msg_type = '';
$msg_text = '';
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard();
    $action = $_POST['action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);

    if ($action === 'toggle_status') {
        $conn->query('UPDATE reviews SET status = 1 - status WHERE id = ' . $review_id);
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã cập nhật trạng thái hiển thị đánh giá.'];
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare('DELETE FROM reviews WHERE id=?');
        $stmt->bind_param('i', $review_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xoá đánh giá.'];
    }
    echo "<script>window.location.href='reviews.php';</script>";
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'visible') $where = 'WHERE r.status=1';
elseif ($filter === 'hidden') $where = 'WHERE r.status=0';

$reviews = $conn->query("SELECT r.*, u.fullname, p.name AS product_name, p.image AS product_image FROM reviews r JOIN users u ON r.user_id=u.id JOIN products p ON r.product_id=p.id $where ORDER BY r.created_at DESC");
$total_reviews = (int)$conn->query('SELECT COUNT(*) c FROM reviews')->fetch_assoc()['c'];
$visible_reviews = (int)$conn->query('SELECT COUNT(*) c FROM reviews WHERE status=1')->fetch_assoc()['c'];
$avg_rating = (float)$conn->query('SELECT COALESCE(AVG(rating),0) a FROM reviews WHERE status=1')->fetch_assoc()['a'];
?>
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap:wrap; gap:10px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #2d6f9d; padding-bottom: 5px; display: inline-block; }
    .filter-tabs { display:flex; gap:8px; margin-bottom:16px; }
    .filter-tabs a { padding:8px 14px; border-radius:8px; background:#fff; color:#2c3e50; text-decoration:none; font-size:13px; font-weight:bold; border:1px solid #e1e5eb; }
    .filter-tabs a.active { background:#2d6f9d; color:#fff; border-color:#2d6f9d; }
    .stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
    .stat-box { background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 10px rgba(0,0,0,.03); }
    .stat-box .num { font-size:24px; font-weight:900; color:#2c3e50; }
    .stat-box .lbl { color:#888; font-size:13px; margin-top:4px; }
    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 14px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 13px; }
    .product-cell { display:flex; align-items:center; gap:10px; }
    .product-cell img { width:40px; height:40px; object-fit:cover; border-radius:8px; border:1px solid #eee; }
    .stars-cell { color:#f59e0b; }
    .comment-cell { max-width:280px; color:#555; font-size:13px; }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size:13px; font-weight: bold; color: #fff; }
    .status-active { background-color: #2d6f9d; }
    .status-inactive { background-color: #aaa; }
    .btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: #fff; font-size:13px; }
    .btn-warning { background-color: #f2b705; }
    .btn-danger { background-color: #e74c3c; }
    .action-cell { display:flex; gap:6px; }
    .empty-row td { text-align:center; padding:30px; color:#888; }
</style>

<div class="page-header">
    <h2 class="page-title">Quản lý đánh giá sản phẩm</h2>
</div>

<div class="stat-row">
    <div class="stat-box"><div class="num"><?php echo $total_reviews; ?></div><div class="lbl">Tổng số đánh giá</div></div>
    <div class="stat-box"><div class="num"><?php echo $visible_reviews; ?></div><div class="lbl">Đang hiển thị</div></div>
    <div class="stat-box"><div class="num"><?php echo number_format($avg_rating, 1); ?> / 5</div><div class="lbl">Điểm trung bình (đang hiển thị)</div></div>
</div>

<div class="filter-tabs">
    <a href="reviews.php?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">Tất cả</a>
    <a href="reviews.php?filter=visible" class="<?php echo $filter === 'visible' ? 'active' : ''; ?>">Đang hiển thị</a>
    <a href="reviews.php?filter=hidden" class="<?php echo $filter === 'hidden' ? 'active' : ''; ?>">Đã ẩn</a>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Khách hàng</th>
                <th>Đánh giá</th>
                <th>Nội dung</th>
                <th>Ngày</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($reviews->num_rows === 0): ?>
                <tr class="empty-row"><td colspan="7">Không có đánh giá nào.</td></tr>
            <?php else: while ($rv = $reviews->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?php echo htmlspecialchars(media_url($rv['product_image'] ?? '', $base_url)); ?>" alt="">
                            <span><?php echo htmlspecialchars($rv['product_name']); ?></span>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($rv['fullname']); ?></td>
                    <td class="stars-cell"><?php for ($s = 1; $s <= 5; $s++): ?><i class="fa-star <?php echo $s <= $rv['rating'] ? 'fas' : 'far'; ?>"></i><?php endfor; ?></td>
                    <td class="comment-cell"><?php echo htmlspecialchars($rv['comment'] ?? ''); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></td>
                    <td><span class="status-badge <?php echo $rv['status'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $rv['status'] ? 'Hiển thị' : 'Đã ẩn'; ?></span></td>
                    <td>
                        <div class="action-cell">
                            <form method="POST" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="review_id" value="<?php echo $rv['id']; ?>">
                                <button type="submit" class="btn btn-warning"><i class="fas <?php echo $rv['status'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i></button>
                            </form>
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteReview(<?php echo $rv['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<form class="js-delete-review-form" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="review_id" class="js-delete-review-id">
</form>

<script>
    function confirmDeleteReview(id) {
        Swal.fire({
            title: 'Xoá đánh giá này?',
            text: 'Hành động này không thể hoàn tác.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Huỷ'
        }).then(function (result) {
            if (result.isConfirmed) {
                document.querySelector('.js-delete-review-id').value = id;
                document.querySelector('.js-delete-review-form').submit();
            }
        });
    }
    <?php if ($msg_type !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type === 'success' ? 'Thành công!' : 'Lỗi!'; ?>',
            text: '<?php echo addslashes($msg_text); ?>',
            confirmButtonColor: '<?php echo $msg_type === 'success' ? '#2d6f9d' : '#e74c3c'; ?>',
            timer: 2200
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>
