<?php
require_once 'config.php';
require_once 'includes/auth_services.php';
require_once 'includes/email_verification.php';

$uid = (int)($_GET['uid'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));

$success = false;
$message = 'Liên kết xác thực không hợp lệ.';

if ($uid > 0 && $token !== '') {
    $stmt = $conn->prepare('SELECT id, fullname, email_verified_at FROM users WHERE id=?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $message = 'Không tìm thấy tài khoản tương ứng với liên kết này.';
    } elseif ($user['email_verified_at'] !== null) {
        $success = true;
        $message = 'Email của bạn đã được xác thực trước đó rồi.';
    } elseif (verify_email_token($conn, $uid, $token)) {
        $success = true;
        $message = 'Xác thực email thành công! Cảm ơn bạn đã xác nhận tài khoản.';
    } else {
        $message = 'Liên kết xác thực không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu gửi lại email xác thực trong trang tài khoản.';
    }
}

include 'header.php';
?>
<style>
.verify-wrap{display:flex;justify-content:center;align-items:center;padding:60px 15px;min-height:55vh}
.verify-card{width:100%;max-width:480px;background:#fff;border:1px solid #f1f1f1;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:38px 30px;text-align:center}
.verify-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:30px;color:#fff}
.verify-icon.ok{background:linear-gradient(135deg,#2d9d5f,#4dbd7e)}
.verify-icon.fail{background:linear-gradient(135deg,#e74c3c,#f2716a)}
.verify-card h1{font-size:22px;color:#2c3e50;margin-bottom:10px}
.verify-card p{color:#666;line-height:1.6;margin-bottom:22px}
.verify-btn{display:inline-block;padding:12px 26px;border-radius:9px;background:#465f79;color:#fff;font-weight:bold;text-decoration:none}
</style>
<div class="verify-wrap">
    <div class="verify-card">
        <div class="verify-icon <?php echo $success ? 'ok' : 'fail'; ?>"><i class="fas <?php echo $success ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i></div>
        <h1><?php echo $success ? 'Xác thực thành công' : 'Xác thực thất bại'; ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a class="verify-btn" href="<?php echo $base_url; ?><?php echo isset($_SESSION['user_id']) ? 'tai-khoan' : 'dang-nhap'; ?>">
            <?php echo isset($_SESSION['user_id']) ? 'Về trang tài khoản' : 'Đăng nhập ngay'; ?>
        </a>
    </div>
</div>
<?php include 'footer.php'; ?>
