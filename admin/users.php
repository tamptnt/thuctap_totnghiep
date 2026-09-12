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
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            csrf_guard();
            $fullname = $_POST['fullname'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $role = $_POST['role'];
            $password = password_hash_secure($_POST['password']);
            
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $check->close();
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Tên đăng nhập hoặc Email đã tồn tại!'];
            } else {
                $check->close();
                $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $fullname, $username, $email, $password, $phone, $address, $role);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Thêm tài khoản thành công!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
                }
                $stmt->close();
            }
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }
        
        if ($action === 'edit') {
            csrf_guard();
            $id = (int)$_POST['id'];
            $fullname = $_POST['fullname'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $role = $_POST['role'];
            
            $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $check->bind_param("ssi", $username, $email, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $check->close();
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Tên đăng nhập hoặc Email đã bị trùng!'];
            } else {
                $check->close();
                if (!empty($_POST['password'])) {
                    $password = password_hash_secure($_POST['password']);
                    $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, password=?, phone=?, address=?, role=? WHERE id=?");
                    $stmt->bind_param("sssssssi", $fullname, $username, $email, $password, $phone, $address, $role, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, address=?, role=? WHERE id=?");
                    $stmt->bind_param("ssssssi", $fullname, $username, $email, $phone, $address, $role, $id);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Cập nhật tài khoản thành công!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
                }
                $stmt->close();
            }
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }
        
        if ($action === 'delete') {
            csrf_guard();
            $id = (int)$_POST['id'];
            
            if ($id == $_SESSION['user_id']) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Bạn không thể tự xóa tài khoản của chính mình!'];
            } else {
                $check = $conn->query("SELECT id FROM orders WHERE user_id = $id");
                if ($check->num_rows > 0) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Khách hàng này đã có đơn hàng, không thể xóa!'];
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xóa tài khoản!'];
                    } else {
                        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
                    }
                    $stmt->close();
                }
            }
            echo "<script>window.location.href='users.php';</script>";
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
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND (fullname LIKE '%$search_esc%' OR username LIKE '%$search_esc%' OR email LIKE '%$search_esc%' OR phone LIKE '%$search_esc%')";
}

$total_sql = "SELECT COUNT(id) as total FROM users $where";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM users $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
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
    .role-admin { background-color: #e74c3c; }
    .role-user { background-color: #8a7563; }
    
    .action-cell { display: flex; gap: 8px; }
    .contact-info { display: flex; flex-direction: column; gap: 3px; font-size:14px; }
    .contact-info i { color: #b5657d; width: 15px; }

    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; }
    .page-link { padding: 8px 14px; background: #fff; border: 1px solid #e1e5eb; border-radius: 6px; color: #333; transition: 0.3s; }
    .page-link:hover { border-color: #b5657d; color: #b5657d; }
    .page-link.active { background: #b5657d; border-color: #b5657d; color: #fff; }

    .modal-box { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 15px; }
    .modal-box.show { display: flex; opacity: 1; }
    .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 600px; transform: translateY(-30px); transition: 0.3s; position: relative; max-height: 90vh; overflow-y: auto; }
    .modal-box.show .modal-content { transform: translateY(0); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: sticky; top: -25px; background: #fff; padding-top: 25px; z-index: 10; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .modal-title { font-size: 18px; color: #b5657d; font-weight: bold; text-transform: uppercase; }
    .close-modal { cursor: pointer; font-size: 20px; color: #aaa; transition: 0.2s; }
    .close-modal:hover { color: #e74c3c; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-group { margin-bottom: 15px; }
    .form-group.full-width { grid-column: span 2; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 14px; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; background: #fff; }
    .form-control:focus { border-color: #b5657d; box-shadow: 0 0 0 3px rgba(181,101,125,.1); }
    .note-text { font-size:14px; color: #e74c3c; margin-top: 5px; display: block; }
    
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
        .form-grid { grid-template-columns: 1fr; }
        .form-group.full-width { grid-column: span 1; }
        .contact-info { align-items: flex-end; }
    }
</style>

<div class="page-header">
    <h2 class="page-title">Quản Lý Khách Hàng</h2>
</div>

<div class="action-bar">
    <form class="search-form" method="GET">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="search-input" placeholder="Tìm tên, email, sđt, username..." value="<?php echo htmlspecialchars($search); ?>">
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
                <th>Tài khoản</th>
                <th>Họ Tên</th>
                <th>Thông Tin Liên Hệ</th>
                <th>Phân Quyền</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="ID"><?php echo $row['id']; ?></td>
                    <td data-label="Tài khoản" style="font-weight:bold; color:#68404e;"><?php echo htmlspecialchars($row['username']); ?></td>
                    <td data-label="Họ Tên" style="font-weight:bold;"><?php echo htmlspecialchars($row['fullname']); ?></td>
                    <td data-label="Liên Hệ">
                        <div class="contact-info">
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?></span>
                        </div>
                    </td>
                    <td data-label="Phân Quyền">
                        <span class="status-badge <?php echo $row['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                            <?php echo strtoupper($row['role']); ?>
                        </span>
                    </td>
                    <td data-label="Thao Tác">
                        <div class="action-cell">
                            <?php 
                                $edit_data = json_encode([
                                    'id' => $row['id'],
                                    'fullname' => htmlspecialchars($row['fullname']),
                                    'username' => htmlspecialchars($row['username']),
                                    'email' => htmlspecialchars($row['email']),
                                    'phone' => htmlspecialchars($row['phone']),
                                    'address' => htmlspecialchars($row['address']),
                                    'role' => $row['role']
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
                    <td colspan="6" style="text-align: center; padding: 30px;">Không tìm thấy dữ liệu nào!</td>
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
            <h3 class="modal-title">Thêm tài khoản</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-add-modal')"></i>
        </div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Họ Tên</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mật Khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Số Điện Thoại</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phân Quyền</label>
                    <select name="role" class="form-control">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Địa Chỉ</label>
                    <input type="text" name="address" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Lưu Dữ Liệu</button>
        </form>
    </div>
</div>

<div class="modal-box js-edit-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #68404e;">Cập nhật tài khoản</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-edit-modal')"></i>
        </div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" class="js-edit-id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control js-edit-username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Họ Tên</label>
                    <input type="text" name="fullname" class="form-control js-edit-fullname" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control js-edit-email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mật Khẩu Mới</label>
                    <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                    <span class="note-text">* Không nhập nếu muốn giữ mật khẩu cũ</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Số Điện Thoại</label>
                    <input type="text" name="phone" class="form-control js-edit-phone" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phân Quyền</label>
                    <select name="role" class="form-control js-edit-role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Địa Chỉ</label>
                    <input type="text" name="address" class="form-control js-edit-address" required>
                </div>
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
        document.querySelector('.js-edit-username').value = data.username;
        document.querySelector('.js-edit-fullname').value = data.fullname;
        document.querySelector('.js-edit-email').value = data.email;
        document.querySelector('.js-edit-phone').value = data.phone;
        document.querySelector('.js-edit-role').value = data.role;
        document.querySelector('.js-edit-address').value = data.address;
        openModal('js-edit-modal');
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Hành động này không thể hoàn tác!",
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