<?php
ob_start();
require_once 'config.php';
require_once 'includes/auth_services.php';
require_once 'includes/email_verification.php';

$base_url = '/tech_store/';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "dang-nhap");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg_type = '';
$msg_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard();
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $date_of_birth = trim((string)($_POST['date_of_birth'] ?? ''));
        $date_of_birth = $date_of_birth === '' ? null : $date_of_birth;
        $gender = $_POST['gender'] ?? '';
        $gender = in_array($gender, ['male', 'female', 'other'], true) ? $gender : null;

        $check_email = $conn->prepare("SELECT id, email FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $msg_type = 'error'; 
            $msg_text = 'Địa chỉ Email này đã được sử dụng bởi tài khoản khác!';
        } else {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $avatarError = false;
            $newAvatar = save_uploaded_media($_FILES['avatar'] ?? null, $upload_dir, 'avatar');
            if ($newAvatar === false) $avatarError = true;

            if ($avatarError) {
                $msg_type = 'error';
                $msg_text = 'Ảnh đại diện không hợp lệ. Chỉ hỗ trợ JPG, PNG, GIF, WEBP hoặc AVIF, tối đa 8MB.';
            } else {
                $stmt_old = $conn->prepare('SELECT email, avatar FROM users WHERE id=?');
                $stmt_old->bind_param('i', $user_id);
                $stmt_old->execute();
                $old = $stmt_old->get_result()->fetch_assoc();
                $stmt_old->close();

                $emailChanged = strcasecmp($old['email'], $email) !== 0;

                if ($newAvatar !== null) {
                    delete_local_media($old['avatar'] ?? '', $upload_dir);
                }
                $avatarToSave = $newAvatar !== null ? $newAvatar : $old['avatar'];

                if ($emailChanged) {
                    $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, email=?, address=?, date_of_birth=?, gender=?, avatar=?, email_verified_at=NULL WHERE id=?");
                    $stmt->bind_param("sssssssi", $fullname, $phone, $email, $address, $date_of_birth, $gender, $avatarToSave, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, email=?, address=?, date_of_birth=?, gender=?, avatar=? WHERE id=?");
                    $stmt->bind_param("sssssssi", $fullname, $phone, $email, $address, $date_of_birth, $gender, $avatarToSave, $user_id);
                }

                if ($stmt->execute()) {
                    $_SESSION['fullname'] = $fullname;
                    $msg_type = 'success'; 
                    $msg_text = 'Cập nhật thông tin cá nhân thành công!';
                    if ($emailChanged) {
                        $freshUser = ['id' => $user_id, 'fullname' => $fullname, 'email' => $email];
                        send_verification_email($conn, $freshUser, $base_url);
                        $msg_text .= ' Email của bạn đã thay đổi nên cần xác thực lại — vui lòng kiểm tra hộp thư.';
                    }
                } else {
                    $msg_type = 'error'; 
                    $msg_text = 'Có lỗi xảy ra trong quá trình cập nhật, vui lòng thử lại!';
                }
                $stmt->close();
            }
        }
        $check_email->close();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'resend_verification') {
        $stmt = $conn->prepare('SELECT id, fullname, email, email_verified_at FROM users WHERE id=?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $freshUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($freshUser['email_verified_at'] !== null) {
            $msg_type = 'success';
            $msg_text = 'Email của bạn đã được xác thực rồi.';
        } elseif (send_verification_email($conn, $freshUser, $base_url)) {
            $msg_type = 'success';
            $msg_text = 'Đã gửi lại email xác thực. Vui lòng kiểm tra hộp thư.';
        } else {
            $msg_type = 'error';
            $msg_text = 'Không gửi được email. Hệ thống có thể chưa cấu hình SMTP.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $old_password = (string)($_POST['old_password'] ?? '');
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_pass = $stmt->get_result()->fetch_assoc()['password'];
        $stmt->close();

        if (!password_verify_upgradeable($conn, $user_id, $old_password, $user_pass)) {
            $msg_type = 'error'; 
            $msg_text = 'Mật khẩu hiện tại không chính xác!';
        } elseif ($new_password !== $confirm_password) {
            $msg_type = 'error'; 
            $msg_text = 'Mật khẩu xác nhận không trùng khớp!';
        } elseif (strlen($new_password) < 6) {
            $msg_type = 'error'; 
            $msg_text = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        } else {
            $new_pass_hash = password_hash_secure($new_password);
            $update_pass = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update_pass->bind_param("si", $new_pass_hash, $user_id);
            if ($update_pass->execute()) {
                $msg_type = 'success'; 
                $msg_text = 'Thay đổi mật khẩu thành công!';
            } else {
                $msg_type = 'error'; 
                $msg_text = 'Có lỗi xảy ra, không thể đổi mật khẩu!';
            }
            $update_pass->close();
        }
    }
}

$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

require_once 'header.php';
?>

<style>
    .profile-page { padding: 40px 0 80px; }
    .page-title { font-size: 24px; font-weight: 900; color: #2c3e50; text-transform: uppercase; margin-bottom: 25px; position: relative; padding-bottom: 10px; }
    .page-title::after { content: ''; position: absolute; left: 0; bottom: 0; width: 60px; height: 4px; background: linear-gradient(90deg, #465f79, #a56f45); border-radius: 4px; }
    
    .profile-layout { display: grid; grid-template-columns: 1fr; gap: 30px; }
    
    .profile-card { background: #fff; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); border: 1px solid #f1f1f1; overflow: hidden; height: fit-content; }
    .profile-header { background: linear-gradient(135deg, #f8f9fa, #eef2f5); padding: 30px 20px; text-align: center; border-bottom: 1px solid #eee; }
    .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: #465f79; color: #fff; display: inline-flex; justify-content: center; align-items: center; font-size: 40px; box-shadow: 0 5px 15px rgba(118,81,58,.25); margin-bottom: 15px; border: 4px solid #fff; overflow: hidden; }
    .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .verify-banner { margin-top: 12px; padding: 8px 12px; border-radius: 8px; background: #fff4e5; color: #9a6a1c; font-size: 12px; font-weight: bold; }
    .verify-banner.ok { background: #eaf7ee; color: #2d7a4d; }
    .verify-resend-btn { margin-top: 4px; border: 0; background: #9a6a1c; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; }
    .profile-name { font-size: 20px; font-weight: 900; color: #2c3e50; margin-bottom: 5px; }
    .profile-username { color: #7f8c8d; font-size: 14px; font-weight: bold; }
    .profile-body { padding: 20px; }
    .profile-stat { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #eee; font-size: 14px; }
    .profile-stat:last-child { border-bottom: none; }
    .profile-stat span:first-child { color: #7f8c8d; font-weight: bold; }
    .profile-stat span:last-child { color: #2c3e50; font-weight: bold; }

    .form-card { background: #fff; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); padding: 25px; border: 1px solid #f1f1f1; margin-bottom: 30px; }
    .form-title { font-size: 18px; font-weight: 900; color: #2c3e50; margin-bottom: 20px; text-transform: uppercase; border-bottom: 2px dashed #eee; padding-bottom: 15px; display: flex; align-items: center; gap: 10px; }
    .form-title i { color: #465f79; }
    .form-title.pwd-title i { color: #243449; }

    .form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
    .form-group { position: relative; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #444; font-size: 14px; }
    .form-control { width: 100%; padding: 14px 18px; border: 2px solid #eaeaea; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; background-color: #fcfcfc; }
    .form-control:focus { border-color: #465f79; outline: none; background-color: #fff; box-shadow: 0 0 0 4px rgba(118,81,58,.15); }
    
    .btn-submit { display: flex; justify-content: center; align-items: center; gap: 8px; width: 100%; padding: 14px; background: linear-gradient(135deg, #465f79, #a56f45); color: #fff; border: none; border-radius: 10px; font-weight: 900; font-size: 15px; text-transform: uppercase; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(118,81,58,.25); margin-top: 10px; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(11, 79, 138, 0.4); }
    .btn-pwd { background: linear-gradient(135deg, #243449, #a56f45); box-shadow: 0 4px 15px rgba(118,81,58,.25); }
    .btn-pwd:hover { box-shadow: 0 6px 20px rgba(18, 107, 181, 0.4); }

    @media (min-width: 768px) {
        .form-grid { grid-template-columns: 1fr 1fr; }
        .form-group.full-width { grid-column: span 2; }
    }

    @media (min-width: 992px) {
        .profile-layout { grid-template-columns: 300px 1fr; gap: 40px; }
        .profile-card { position: sticky; top: 90px; }
    }
</style>

<div class="container profile-page">
    <div class="page-intro"><div><h1>Thông tin tài khoản</h1><p>Cập nhật thông tin cá nhân và bảo mật tài khoản.</p></div><div class="page-intro-icon"><i class="fas fa-user-shield"></i></div></div>

    <div class="profile-layout">
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars(media_url($user['avatar'], $base_url)); ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                    <div class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                    <?php if (empty($user['email_verified_at'])): ?>
                        <div class="verify-banner">
                            <i class="fas fa-triangle-exclamation"></i> Email chưa xác thực
                            <form method="POST" style="margin-top:8px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="resend_verification">
                                <button type="submit" class="verify-resend-btn">Gửi lại email xác thực</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="verify-banner ok"><i class="fas fa-circle-check"></i> Email đã xác thực</div>
                    <?php endif; ?>
                </div>
                <div class="profile-body">
                    <div class="profile-stat">
                        <span>Ngày tham gia:</span>
                        <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="profile-stat">
                        <span>Loại tài khoản:</span>
                        <span style="color: #465f79;"><?php echo $user['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <form method="POST" class="form-card" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_profile">
                <h3 class="form-title"><i class="fas fa-user-pen"></i> Cập Nhật Thông Tin</h3>
                <div class="form-group">
                    <label class="form-label">Ảnh đại diện</label>
                    <input type="file" name="avatar" class="form-control" accept="image/*">
                    <small style="display:block;margin-top:6px;color:#999;">JPG, PNG, GIF, WEBP hoặc AVIF, tối đa 8MB. Để trống nếu không đổi ảnh.</small>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Họ và Tên</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số Điện Thoại</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giới tính</label>
                        <select name="gender" class="form-control">
                            <option value="">-- Không chọn --</option>
                            <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Nam</option>
                            <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Nữ</option>
                            <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Địa Chỉ Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <small style="display:block;margin-top:6px;color:#999;">Đổi email sẽ yêu cầu xác thực lại.</small>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Địa Chỉ Giao Hàng</label>
                        <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-floppy-disk"></i> Lưu Thay Đổi</button>
            </form>

            <form method="POST" class="form-card"><?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="change_password">
                <h3 class="form-title pwd-title"><i class="fas fa-key"></i> Đổi Mật Khẩu</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Mật Khẩu Hiện Tại</label>
                        <input type="password" name="old_password" class="form-control" placeholder="Nhập mật khẩu hiện tại" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mật Khẩu Mới</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Mật khẩu mới" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Xác Nhận Mật Khẩu</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-pwd"><i class="fas fa-lock"></i> Đổi Mật Khẩu</button>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if($msg_type != ''): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type == 'success' ? 'Thành Công!' : 'Lỗi!'; ?>',
            text: '<?php echo $msg_text; ?>',
            confirmButtonColor: '<?php echo $msg_type == 'success' ? '#465f79' : '#e74c3c'; ?>',
            timer: 3000,
            timerProgressBar: true,
            backdrop: `rgba(0,0,0,0.4)`
        });
    });
    <?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>