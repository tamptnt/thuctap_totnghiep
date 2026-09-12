<?php
include 'header.php';

$msg_type = '';
$msg_text = '';

if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_guard();
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $link = $_POST['link'];
            $position = $_POST['position'];
            $banner_type = $_POST['banner_type'];
            $status = (int)$_POST['status'];
            
            $image_url = '';
            $external_url = trim((string)($_POST['external_url'] ?? ''));
            if ($external_url !== '') {
                if (!valid_remote_media_url($external_url)) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Link ảnh banner không hợp lệ. Hãy dùng URL bắt đầu bằng http:// hoặc https://'];
                    header('Location: banners.php');
                    exit();
                }
                $image_url = $external_url;
            }
            $uploaded_image = save_uploaded_media($_FILES['image'] ?? null, $upload_dir, 'banner');
            if ($uploaded_image === false) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Ảnh banner tải lên không hợp lệ. Chỉ hỗ trợ JPG, JPEG, PNG, GIF, WEBP hoặc AVIF.'];
                header('Location: banners.php');
                exit();
            }
            if ($uploaded_image !== null) $image_url = $uploaded_image;

            $stmt = $conn->prepare("INSERT INTO banners (image_url, link, position, banner_type, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $image_url, $link, $position, $banner_type, $status);
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Thêm banner thành công!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
            }
            $stmt->close();
            echo "<script>window.location.href='banners.php';</script>";
            exit();
        }
        
        if ($action === 'edit') {
            $id = (int)$_POST['id'];
            $link = $_POST['link'];
            $position = $_POST['position'];
            $banner_type = $_POST['banner_type'];
            $status = (int)$_POST['status'];
            
            $old_image = (string)($_POST['old_image'] ?? '');
            $image_url = $old_image;
            $external_url = trim((string)($_POST['external_url'] ?? ''));
            if ($external_url !== '') {
                if (!valid_remote_media_url($external_url)) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Link ảnh banner không hợp lệ. Hãy dùng URL bắt đầu bằng http:// hoặc https://'];
                    header('Location: banners.php');
                    exit();
                }
                $image_url = $external_url;
            }
            $uploaded_image = save_uploaded_media($_FILES['image'] ?? null, $upload_dir, 'banner');
            if ($uploaded_image === false) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Ảnh banner tải lên không hợp lệ. Chỉ hỗ trợ JPG, JPEG, PNG, GIF, WEBP hoặc AVIF.'];
                header('Location: banners.php');
                exit();
            }
            if ($uploaded_image !== null) $image_url = $uploaded_image;

            $stmt = $conn->prepare("UPDATE banners SET image_url=?, link=?, position=?, banner_type=?, status=? WHERE id=?");
            $stmt->bind_param("ssssii", $image_url, $link, $position, $banner_type, $status, $id);
            
            if ($stmt->execute()) {
                if ($image_url !== $old_image) delete_local_media($old_image, $upload_dir);
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Cập nhật banner thành công!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
            }
            $stmt->close();
            echo "<script>window.location.href='banners.php';</script>";
            exit();
        }
        
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $img_query = $conn->query("SELECT image_url FROM banners WHERE id = $id");
            if ($img_query->num_rows > 0) {
                $img_name = $img_query->fetch_assoc()['image_url'];
                delete_local_media($img_name, $upload_dir);
            }
            
            $stmt = $conn->prepare("DELETE FROM banners WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xóa banner!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
            }
            $stmt->close();
            echo "<script>window.location.href='banners.php';</script>";
            exit();
        }
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($search !== '') {
    $where .= " AND (position LIKE '%" . $conn->real_escape_string($search) . "%' OR link LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$total_sql = "SELECT COUNT(id) as total FROM banners $where";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM banners $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #b5657d; padding-bottom: 5px; display: inline-block; }
    
    .action-bar { display: flex; flex-wrap: nowrap; gap: 10px; margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
    .search-form { display: flex; flex-grow: 1; position: relative; }
    .search-input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; }
    .search-input:focus { border-color: #b5657d; box-shadow: 0 0 0 3px rgba(181,101,125,.1); }
    .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
    .btn { padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: #fff; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
    .btn-primary { background-color: #b5657d; }
    .btn-primary:hover { background-color: #72aa25; }
    .btn-warning { background-color: #68404e; }
    .btn-warning:hover { background-color: #e67600; }
    .btn-danger { background-color: #e74c3c; }
    .btn-danger:hover { background-color: #c0392b; }
    .btn-sm { padding: 6px 10px; font-size:14px; border-radius: 6px; }

    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 14px; }
    .data-table tr:hover { background-color: #fcfcfc; }
    
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size:14px; font-weight: bold; color: #fff; }
    .status-active { background-color: #b5657d; }
    .status-inactive { background-color: #aaa; }
    
    .type-badge { padding: 5px 10px; border-radius: 6px; font-size:14px; font-weight: bold; color: #fff; }
    .type-main { background-color: #8a7563; }
    .type-sub { background-color: #68404e; }

    .action-cell { display: flex; gap: 8px; }
    .banner-img { width: 120px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }

    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; }
    .page-link { padding: 8px 14px; background: #fff; border: 1px solid #e1e5eb; border-radius: 6px; color: #333; transition: 0.3s; }
    .page-link:hover { border-color: #b5657d; color: #b5657d; }
    .page-link.active { background: #b5657d; border-color: #b5657d; color: #fff; }

    .modal-box { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 15px; }
    .modal-box.show { display: flex; opacity: 1; }
    .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 500px; transform: translateY(-30px); transition: 0.3s; position: relative; max-height: 90vh; overflow-y: auto; }
    .modal-box.show .modal-content { transform: translateY(0); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: sticky; top: -25px; background: #fff; padding-top: 25px; z-index: 10; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .modal-title { font-size: 18px; color: #b5657d; font-weight: bold; text-transform: uppercase; }
    .close-modal { cursor: pointer; font-size: 20px; color: #aaa; transition: 0.2s; }
    .close-modal:hover { color: #e74c3c; }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 14px; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; background: #fff; }
    .form-control:focus { border-color: #b5657d; box-shadow: 0 0 0 3px rgba(181,101,125,.1); }
    
    @media (max-width: 768px) {
        .btn-text { display: none; }
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin: 15px 0; border: 1px solid #e1e5eb; border-radius: 12px; padding: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .data-table td { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f9f9f9; padding: 10px 5px; text-align: right; }
        .data-table td:last-child { border-bottom: none; }
        .data-table td::before { content: attr(data-label); font-weight: bold; color: #b5657d; text-align: left; text-transform: uppercase; font-size:14px; }
        .table-wrapper { background: transparent; box-shadow: none; }
        .action-cell { justify-content: flex-end; }
        .banner-img { width: 80px; height: 40px; }
    }
</style>

<div class="page-header">
    <h2 class="page-title">Quản Lý Banner</h2>
</div>

<div class="action-bar">
    <form class="search-form" method="GET">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="search-input" placeholder="Tìm theo liên kết, vị trí..." value="<?php echo htmlspecialchars($search); ?>">
    </form>
    <button class="btn btn-primary" onclick="openModal('js-add-modal')">
        <i class="fas fa-plus"></i> <span class="btn-text">Thêm Mới</span>
    </button>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Hình Ảnh</th>
                <th>Vị Trí</th>
                <th>Liên Kết</th>
                <th>Loại Banner</th>
                <th>Trạng Thái</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="ID"><?php echo $row['id']; ?></td>
                    <td data-label="Hình Ảnh">
                        <?php if($row['image_url']): ?>
                            <img src="<?php echo htmlspecialchars(media_url($row['image_url'], $base_url)); ?>" class="banner-img">
                        <?php else: ?>
                            <div class="banner-img" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#aaa;"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Vị Trí" style="font-weight:bold;"><?php echo htmlspecialchars($row['position']); ?></td>
                    <td data-label="Liên Kết" style="color:#b5657d; font-size:14px; word-break: break-all; max-width: 150px;">
                        <?php echo htmlspecialchars($row['link']); ?>
                    </td>
                    <td data-label="Loại Banner">
                        <span class="type-badge <?php echo $row['banner_type'] == 'main' ? 'type-main' : 'type-sub'; ?>">
                            <?php echo $row['banner_type'] == 'main' ? 'Banner Chính' : 'Banner Phụ'; ?>
                        </span>
                    </td>
                    <td data-label="Trạng Thái">
                        <span class="status-badge <?php echo $row['status'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $row['status'] == 1 ? 'Hiển Thị' : 'Đã Ẩn'; ?>
                        </span>
                    </td>
                    <td data-label="Thao Tác">
                        <div class="action-cell">
                            <?php 
                                $edit_data = json_encode([
                                    'id' => $row['id'],
                                    'link' => htmlspecialchars($row['link']),
                                    'position' => htmlspecialchars($row['position']),
                                    'banner_type' => $row['banner_type'],
                                    'status' => $row['status'],
                                    'old_image' => $row['image_url']
                                ]);
                            ?>
                            <button class="btn btn-warning btn-sm" onclick='openEditModal(<?php echo $edit_data; ?>)'>
                                <i class="fas fa-pen-to-square"></i> <span class="btn-text">Sửa</span>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash"></i> <span class="btn-text">Xóa</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">Không tìm thấy dữ liệu nào!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if($total_pages > 1): ?>
<div class="pagination">
    <?php for($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?><?php echo $search != '' ? '&search='.$search : ''; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<div class="modal-box js-add-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Thêm Banner Mới</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-add-modal')"></i>
        </div>
        <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label class="form-label">Ảnh banner</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <input type="url" name="external_url" class="form-control" placeholder="Hoặc dán link banner https://..." style="margin-top:7px">
                <small style="display:block;margin-top:6px;color:#758392">Chọn upload ảnh hoặc dán link ảnh. Nếu dùng cả hai, ảnh upload sẽ được ưu tiên.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Liên Kết (URL)</label>
                <input type="text" name="link" class="form-control" placeholder="Ví dụ: /khuyen-mai">
            </div>
            <div class="form-group">
                <label class="form-label">Vị Trí Cụ Thể</label>
                <input type="text" name="position" class="form-control" placeholder="Ví dụ: Trang chủ trên cùng">
            </div>
            <div class="form-group">
                <label class="form-label">Loại Banner</label>
                <select name="banner_type" class="form-control">
                    <option value="main">Banner Chính</option>
                    <option value="sub">Banner Phụ</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Trạng Thái</label>
                <select name="status" class="form-control">
                    <option value="1">Hiển Thị</option>
                    <option value="0">Ẩn</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Lưu Dữ Liệu</button>
        </form>
    </div>
</div>

<div class="modal-box js-edit-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #68404e;">Sửa Banner</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-edit-modal')"></i>
        </div>
        <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" class="js-edit-id">
            <input type="hidden" name="old_image" class="js-edit-old-image">
            
            <div class="form-group">
                <label class="form-label">Ảnh banner</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <input type="url" name="external_url" class="form-control js-edit-external-url" placeholder="Hoặc dán link banner https://..." style="margin-top:7px">
                <small style="display:block;margin-top:6px;color:#758392">Bỏ trống cả hai để giữ ảnh hiện tại.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Liên Kết (URL)</label>
                <input type="text" name="link" class="form-control js-edit-link">
            </div>
            <div class="form-group">
                <label class="form-label">Vị Trí Cụ Thể</label>
                <input type="text" name="position" class="form-control js-edit-position">
            </div>
            <div class="form-group">
                <label class="form-label">Loại Banner</label>
                <select name="banner_type" class="form-control js-edit-banner-type">
                    <option value="main">Banner Chính</option>
                    <option value="sub">Banner Phụ</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Trạng Thái</label>
                <select name="status" class="form-control js-edit-status">
                    <option value="1">Hiển Thị</option>
                    <option value="0">Ẩn</option>
                </select>
            </div>
            <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 10px;">Cập Nhật</button>
        </form>
    </div>
</div>

<form class="js-delete-form" method="POST" style="display: none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" class="js-delete-id">
</form>

<script>
    function openModal(className) {
        document.querySelector('.' + className).classList.add('show');
    }

    function closeModal(className) {
        document.querySelector('.' + className).classList.remove('show');
    }

    function openEditModal(data) {
        document.querySelector('.js-edit-id').value = data.id;
        document.querySelector('.js-edit-link').value = data.link;
        document.querySelector('.js-edit-position').value = data.position;
        document.querySelector('.js-edit-banner-type').value = data.banner_type;
        document.querySelector('.js-edit-status').value = data.status;
        document.querySelector('.js-edit-old-image').value = data.old_image;
        const externalUrl=document.querySelector('.js-edit-external-url'); if(externalUrl) externalUrl.value=/^https?:\/\//i.test(data.old_image||'')?data.old_image:'';
        openModal('js-edit-modal');
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Hình ảnh banner cũng sẽ bị xóa khỏi hệ thống!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector('.js-delete-id').value = id;
                document.querySelector('.js-delete-form').submit();
            }
        })
    }

    <?php if($msg_type != ''): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type == 'success' ? 'Thành Công!' : 'Lỗi!'; ?>',
            text: '<?php echo $msg_text; ?>',
            confirmButtonColor: '<?php echo $msg_type == 'success' ? '#b5657d' : '#e74c3c'; ?>',
            timer: 2500
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>