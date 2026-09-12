<?php
include 'header.php';

$msg_type = '';
$msg_text = '';

if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

$icon_list = [
    'fas fa-store' => 'Cửa Hàng',
    'fas fa-phone' => 'Điện Thoại',
    'fas fa-envelope' => 'Email',
    'fas fa-location-dot' => 'Địa Chỉ',
    'fab fa-facebook' => 'Facebook',
    'fab fa-youtube' => 'YouTube',
    'fab fa-instagram' => 'Instagram',
    'fab fa-tiktok' => 'TikTok',
    'fas fa-clock' => 'Thời Gian',
    'fas fa-circle-info' => 'Thông Tin',
    'fas fa-shield-alt' => 'Bảo Hành',
    'fas fa-truck' => 'Giao Hàng',
    'fas fa-money-bill-wave' => 'Thanh toán',
    'fas fa-headset' => 'Hỗ Trợ',
    'fas fa-link' => 'Liên Kết'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_guard();
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $setting_key = trim($_POST['setting_key']);
            $setting_value = trim($_POST['setting_value']);
            $icon = trim($_POST['icon']);
            
            $check = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $check->bind_param("s", $setting_key);
            $check->execute();
            $check_res = $check->get_result();
            
            if ($check_res->num_rows > 0) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Từ khóa cài đặt này đã tồn tại!'];
            } else {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, icon) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $setting_key, $setting_value, $icon);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Thêm cài đặt thành công!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
                }
                $stmt->close();
            }
            $check->close();
            echo "<script>window.location.href='settings.php';</script>";
            exit();
        }
        
        if ($action === 'edit') {
            $id = (int)$_POST['id'];
            $setting_key = trim($_POST['setting_key']);
            $setting_value = trim($_POST['setting_value']);
            $icon = trim($_POST['icon']);
            
            $check = $conn->prepare("SELECT id FROM settings WHERE setting_key = ? AND id != ?");
            $check->bind_param("si", $setting_key, $id);
            $check->execute();
            $check_res = $check->get_result();
            
            if ($check_res->num_rows > 0) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Từ khóa cài đặt này đã bị trùng!'];
            } else {
                $stmt = $conn->prepare("UPDATE settings SET setting_key=?, setting_value=?, icon=? WHERE id=?");
                $stmt->bind_param("sssi", $setting_key, $setting_value, $icon, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Cập nhật cài đặt thành công!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
                }
                $stmt->close();
            }
            $check->close();
            echo "<script>window.location.href='settings.php';</script>";
            exit();
        }
        
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM settings WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xóa cài đặt!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
            }
            $stmt->close();
            echo "<script>window.location.href='settings.php';</script>";
            exit();
        }
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($search !== '') {
    $where .= " AND (setting_key LIKE '%" . $conn->real_escape_string($search) . "%' OR setting_value LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$total_sql = "SELECT COUNT(id) as total FROM settings $where";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM settings $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
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
    
    .action-cell { display: flex; gap: 8px; }
    .value-text { display: block; max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .icon-preview { display: inline-flex; justify-content: center; align-items: center; width: 35px; height: 35px; background: #fdfdfd; border: 1px solid #eee; border-radius: 6px; font-size: 16px; color: #8a7563; }

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
    textarea.form-control { resize: vertical; min-height: 120px; }
    .form-control:focus { border-color: #b5657d; box-shadow: 0 0 0 3px rgba(181,101,125,.1); }
    
    .icon-select-wrapper { position: relative; }
    .icon-select-preview { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #8a7563; }
    .icon-select-input { padding-left: 45px; }

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
        .value-text { max-width: 200px; }
    }
</style>

<div class="page-header">
    <h2 class="page-title">Quản Lý Cài Đặt Chung</h2>
</div>

<div class="action-bar">
    <form class="search-form" method="GET">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="search-input" placeholder="Tìm theo khóa hoặc giá trị..." value="<?php echo htmlspecialchars($search); ?>">
    </form>
    <button class="btn btn-primary" onclick="openModal('js-add-modal')">
        <i class="fas fa-plus"></i> <span class="btn-text">Thêm Mới</span>
    </button>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Từ Khóa (Key)</th>
                <th>Icon</th>
                <th>Giá Trị (Value)</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="Từ Khóa" style="font-weight:bold; color: #68404e;"><?php echo htmlspecialchars($row['setting_key']); ?></td>
                    <td data-label="Icon">
                        <div class="icon-preview" title="<?php echo htmlspecialchars($row['icon']); ?>">
                            <i class="<?php echo htmlspecialchars($row['icon'] ?: 'fas fa-gear'); ?>"></i>
                        </div>
                    </td>
                    <td data-label="Giá Trị">
                        <span class="value-text" title="<?php echo htmlspecialchars($row['setting_value']); ?>">
                            <?php echo htmlspecialchars($row['setting_value']); ?>
                        </span>
                    </td>
                    <td data-label="Thao Tác">
                        <div class="action-cell">
                            <?php 
                                $edit_data = json_encode([
                                    'id' => $row['id'],
                                    'setting_key' => htmlspecialchars($row['setting_key']),
                                    'setting_value' => htmlspecialchars($row['setting_value']),
                                    'icon' => htmlspecialchars($row['icon'])
                                ]);
                            ?>
                            <button class="btn btn-warning btn-sm" onclick='openEditModal(<?php echo htmlspecialchars($edit_data, ENT_QUOTES, 'UTF-8'); ?>)'>
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
                    <td colspan="4" style="text-align: center; padding: 30px;">Không tìm thấy dữ liệu nào!</td>
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
            <h3 class="modal-title">Thêm Cài Đặt Mới</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-add-modal')"></i>
        </div>
        <form method="POST"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label class="form-label">Từ Khóa (Key)</label>
                <input type="text" name="setting_key" class="form-control" placeholder="VD: email, hotline, facebook_link..." required>
            </div>
            <div class="form-group">
                <label class="form-label">Icon Hiển Thị</label>
                <div class="icon-select-wrapper">
                    <i class="fas fa-gear icon-select-preview" id="addIconPreview"></i>
                    <select name="icon" class="form-control icon-select-input" onchange="document.getElementById('addIconPreview').className = this.value + ' icon-select-preview'">
                        <?php foreach($icon_list as $class => $label): ?>
                            <option value="<?php echo $class; ?>"><?php echo $label; ?> (<?php echo $class; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Giá Trị (Value)</label>
                <textarea name="setting_value" class="form-control" placeholder="Nhập giá trị cài đặt..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Lưu Dữ Liệu</button>
        </form>
    </div>
</div>

<div class="modal-box js-edit-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #68404e;">Cập Nhật Cài Đặt</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-edit-modal')"></i>
        </div>
        <form method="POST"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" class="js-edit-id">
            
            <div class="form-group">
                <label class="form-label">Từ Khóa (Key)</label>
                <input type="text" name="setting_key" class="form-control js-edit-key" required>
            </div>
            <div class="form-group">
                <label class="form-label">Icon Hiển Thị</label>
                <div class="icon-select-wrapper">
                    <i class="fas fa-gear icon-select-preview" id="editIconPreview"></i>
                    <select name="icon" class="form-control icon-select-input js-edit-icon" onchange="document.getElementById('editIconPreview').className = this.value + ' icon-select-preview'">
                        <?php foreach($icon_list as $class => $label): ?>
                            <option value="<?php echo $class; ?>"><?php echo $label; ?> (<?php echo $class; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Giá Trị (Value)</label>
                <textarea name="setting_value" class="form-control js-edit-value" required></textarea>
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
        document.querySelector('.js-edit-key').value = data.setting_key;
        document.querySelector('.js-edit-value').value = data.setting_value;
        
        let iconSelect = document.querySelector('.js-edit-icon');
        iconSelect.value = data.icon;
        document.getElementById('editIconPreview').className = data.icon + ' icon-select-preview';
        
        openModal('js-edit-modal');
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Cài đặt này sẽ bị xóa khỏi hệ thống!",
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
        });
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