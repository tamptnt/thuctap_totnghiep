<?php
require_once 'config.php';
require_once 'includes/auth_services.php';
require_once 'includes/email_verification.php';

$base_url = '/tech_store/';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: " . $base_url . "admin/index.php");
    } else {
        header("Location: " . $base_url);
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_guard();
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $rawPassword = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif (strlen($rawPassword) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        $password = password_hash_secure($rawPassword);

        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc Email này đã được sử dụng!';
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
            $insert_stmt->bind_param("ssssss", $fullname, $username, $email, $password, $phone, $address);

            if ($insert_stmt->execute()) {
                $new_user_id = $insert_stmt->insert_id;
                $insert_stmt->close();

                $newUser = ['id' => $new_user_id, 'fullname' => $fullname, 'email' => $email];
                if (send_verification_email($conn, $newUser, $base_url)) {
                    $success = 'Đăng ký tài khoản thành công! Vui lòng kiểm tra email để xác thực tài khoản.';
                } else {
                    $success = 'Đăng ký tài khoản thành công! (Không gửi được email xác thực — bạn có thể yêu cầu gửi lại sau khi đăng nhập.)';
                }
            } else {
                $error = 'Đã xảy ra lỗi, vui lòng thử lại!';
                $insert_stmt->close();
            }
        }
        $check_stmt->close();
    }
}

include 'header.php';
?>
<style>
    .auth-wrapper { display: flex; justify-content: center; align-items: center; padding: 40px 15px; min-height: 60vh; }
    .auth-container { width: 100%; max-width: 420px; background: #fff; padding: 35px 25px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #f1f1f1; }
    .auth-title { text-align: center; color: #465f79; margin-bottom: 30px; font-size: 26px; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; }
    .form-group { margin-bottom: 20px; position: relative; }
    .form-control { width: 100%; padding: 14px 18px; border: 2px solid #eaeaea; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; background-color: #fcfcfc; }
    .form-control:focus { border-color: #465f79; outline: none; background-color: #fff; box-shadow: 0 0 0 4px rgba(118,81,58,.15); }
    .auth-btn { width: 100%; background: linear-gradient(135deg, #243449, #a56f45); color: #fff; padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(118,81,58,.25); }
    .auth-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(18, 107, 181, 0.4); }
    .auth-links { text-align: center; margin-top: 20px; font-size: 15px; color: #666; }
    .auth-links a { color: #465f79; font-weight: bold; text-decoration: none; transition: 0.2s; }
    .auth-links a:hover { color: #243449; }
</style>

<div class="auth-wrapper">
    <div class="auth-container">
        <h2 class="auth-title">Đăng ký tài khoản</h2>
        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="fullname" class="form-control" placeholder="Họ và tên của bạn" required>
            </div>
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Địa chỉ Email" required>
            </div>
            <div class="form-group">
                <input type="tel" name="phone" class="form-control" placeholder="Số điện thoại" required>
            </div>
            <div class="form-group">
                <input type="text" name="address" class="form-control" placeholder="Địa chỉ giao hàng" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
            </div>
            <button type="submit" class="auth-btn">Tạo tài khoản mới</button>
        </form>
        <div class="auth-links">
            Đã có tài khoản? <a href="<?php echo $base_url; ?>dang-nhap">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<script>
    <?php if($error != ''): ?>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({ icon: 'error', title: 'Đăng ký thất bại', text: <?php echo json_encode($error, JSON_UNESCAPED_UNICODE); ?>, confirmButtonColor: '#243449', shape: 'pill' });
        });
    <?php endif; ?>
    
    <?php if($success != ''): ?>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({ icon: 'success', title: 'Tuyệt vời!', text: <?php echo json_encode($success, JSON_UNESCAPED_UNICODE); ?> }).then(() => { 
                window.location.href = '<?php echo $base_url; ?>dang-nhap'; 
            });
        });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>