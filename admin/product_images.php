<?php
include 'header.php';

$product_id = (int)($_GET['product_id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM products WHERE id=?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không tìm thấy sản phẩm.'];
    echo "<script>window.location.href='products.php';</script>";
    exit();
}

$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$msg_type = '';
$msg_text = '';
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

function redirect_back_images($product_id)
{
    echo "<script>window.location.href='product_images.php?product_id=$product_id';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $image = '';
        $image_url = trim((string)($_POST['image_url'] ?? ''));

        if ($image_url !== '') {
            if (!valid_remote_media_url($image_url)) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Link ảnh không hợp lệ. Hãy dùng URL bắt đầu bằng http:// hoặc https://'];
                redirect_back_images($product_id);
            }
            $downloaded = download_remote_media($image_url, $upload_dir, 'product_gallery');
            if ($downloaded === false) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không tải được ảnh từ link đã dán (chỉ hỗ trợ JPG, PNG, GIF, WEBP, AVIF, tối đa 8MB).'];
                redirect_back_images($product_id);
            }
            $image = $downloaded;
        }

        $uploaded = save_uploaded_media($_FILES['image'] ?? null, $upload_dir, 'product_gallery');
        if ($uploaded === false) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Ảnh tải lên không hợp lệ. Chỉ hỗ trợ JPG, JPEG, PNG, GIF, WEBP hoặc AVIF, tối đa 8MB.'];
            redirect_back_images($product_id);
        }
        if ($uploaded !== null) $image = $uploaded;

        if ($image === '') {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Vui lòng chọn ảnh để tải lên hoặc dán link ảnh.'];
            redirect_back_images($product_id);
        }

        $maxOrder = $conn->query('SELECT COALESCE(MAX(sort_order),-1) m FROM product_images WHERE product_id=' . (int)$product_id)->fetch_assoc()['m'];
        $sort_order = (int)$maxOrder + 1;

        $stmtIns = $conn->prepare('INSERT INTO product_images (product_id,image,sort_order) VALUES (?,?,?)');
        $stmtIns->bind_param('isi', $product_id, $image, $sort_order);
        $stmtIns->execute();
        $stmtIns->close();

        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã thêm ảnh vào thư viện sản phẩm.'];
        redirect_back_images($product_id);
    }

    if ($action === 'delete') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $stmtSel = $conn->prepare('SELECT image FROM product_images WHERE id=? AND product_id=?');
        $stmtSel->bind_param('ii', $image_id, $product_id);
        $stmtSel->execute();
        $row = $stmtSel->get_result()->fetch_assoc();
        $stmtSel->close();

        if ($row) {
            $stmtDel = $conn->prepare('DELETE FROM product_images WHERE id=? AND product_id=?');
            $stmtDel->bind_param('ii', $image_id, $product_id);
            $stmtDel->execute();
            $stmtDel->close();
            delete_local_media($row['image'], $upload_dir);
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xoá ảnh khỏi thư viện.'];
        }
        redirect_back_images($product_id);
    }

    if ($action === 'move') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        $images = get_product_images($conn, $product_id);
        $ids = array_column($images, 'id');
        $pos = array_search($image_id, $ids, true);

        if ($pos !== false) {
            $swapWith = $direction === 'up' ? $pos - 1 : $pos + 1;
            if (isset($ids[$swapWith])) {
                $orderA = $images[$pos]['sort_order'];
                $orderB = $images[$swapWith]['sort_order'];
                $u1 = $conn->prepare('UPDATE product_images SET sort_order=? WHERE id=?');
                $u1->bind_param('ii', $orderB, $ids[$pos]);
                $u1->execute();
                $u1->close();
                $u2 = $conn->prepare('UPDATE product_images SET sort_order=? WHERE id=?');
                $u2->bind_param('ii', $orderA, $ids[$swapWith]);
                $u2->execute();
                $u2->close();
            }
        }
        redirect_back_images($product_id);
    }
}

$images = get_product_images($conn, $product_id);
?>
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap:wrap; gap:10px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #2d6f9d; padding-bottom: 5px; display: inline-block; }
    .product-recap { display:flex; align-items:center; gap:12px; background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(0,0,0,.03); margin-bottom:18px; }
    .product-recap img { width:56px; height:56px; object-fit:cover; border-radius:8px; border:1px solid #eee; }
    .product-recap strong { display:block; color:#2c3e50; font-size:15px; }
    .product-recap span { display:block; color:#888; font-size:13px; margin-top:3px; }
    .btn { padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: #fff; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration:none; }
    .btn-primary { background-color: #2d6f9d; }
    .btn-secondary { background-color: #7b8a97; }
    .btn-danger { background-color: #e74c3c; }
    .btn-sm { padding: 6px 10px; font-size:14px; border-radius: 6px; }
    .upload-card { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.03); padding:18px; margin-bottom:18px; }
    .upload-row { display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; }
    .form-group { flex:1 1 220px; }
    .form-label { display:block; margin-bottom:8px; font-weight:bold; color:#333; font-size:14px; }
    .form-control { width:100%; padding:11px 14px; border:1px solid #e1e5eb; border-radius:8px; font-size:14px; outline:none; }
    .form-hint { display:block; margin-top:6px; color:#8a97a3; font-size:12px; }
    .or-sep { color:#aaa; font-size:13px; padding-bottom:12px; }
    .gallery-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:14px; }
    .gallery-card { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.03); overflow:hidden; }
    .gallery-card img { width:100%; height:130px; object-fit:cover; display:block; background:#f8f9fa; }
    .gallery-card .primary-tag { display:block; text-align:center; background:#f2b705; color:#fff; font-size:11px; font-weight:bold; padding:3px 0; }
    .gallery-actions { display:flex; gap:6px; padding:8px; justify-content:center; }
    .empty-box { text-align:center; padding:40px; color:#888; background:#fff; border-radius:12px; }
</style>

<div class="page-header">
    <h2 class="page-title">Thư viện ảnh sản phẩm</h2>
    <a href="products.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Quay lại danh sách sản phẩm</a>
</div>

<div class="product-recap">
    <img src="<?php echo htmlspecialchars(media_url($product['image'] ?? '', $base_url)); ?>" alt="">
    <div>
        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
        <span>Ảnh này là ảnh đại diện chính, hiển thị trong danh sách sản phẩm và thẻ sản phẩm. Thêm ảnh phụ bên dưới để hiển thị dạng thư viện (gallery) ở trang chi tiết.</span>
    </div>
</div>

<div class="upload-card">
    <form method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add">
        <div class="upload-row">
            <div class="form-group">
                <label class="form-label">Tải ảnh từ máy</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="or-sep">hoặc</div>
            <div class="form-group">
                <label class="form-label">Dán link ảnh</label>
                <input type="text" name="image_url" class="form-control" placeholder="https://...">
                <small class="form-hint">Chỉ hỗ trợ JPG, PNG, GIF, WEBP, AVIF, tối đa 8MB.</small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm vào thư viện</button>
        </div>
    </form>
</div>

<?php if (empty($images)): ?>
    <div class="empty-box">Sản phẩm này chưa có ảnh phụ nào. Trang chi tiết sẽ chỉ hiển thị ảnh đại diện chính.</div>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $i => $img): ?>
            <div class="gallery-card">
                <img src="<?php echo htmlspecialchars(media_url($img['image'], $base_url)); ?>" alt="">
                <div class="gallery-actions">
                    <form method="POST" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="move">
                        <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" class="btn btn-secondary btn-sm" <?php echo $i === 0 ? 'disabled' : ''; ?>><i class="fas fa-arrow-left"></i></button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="move">
                        <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" class="btn btn-secondary btn-sm" <?php echo $i === count($images) - 1 ? 'disabled' : ''; ?>><i class="fas fa-arrow-right"></i></button>
                    </form>
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteImage(<?php echo $img['id']; ?>)"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="js-delete-image-form" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="image_id" class="js-delete-image-id">
</form>

<script>
    function confirmDeleteImage(id) {
        Swal.fire({
            title: 'Xoá ảnh này?',
            text: 'Ảnh sẽ bị xoá khỏi thư viện sản phẩm.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Huỷ'
        }).then(function (result) {
            if (result.isConfirmed) {
                document.querySelector('.js-delete-image-id').value = id;
                document.querySelector('.js-delete-image-form').submit();
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
            timer: 2500
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>
