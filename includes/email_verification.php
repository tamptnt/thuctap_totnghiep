<?php
/**
 * Các hàm dùng chung cho luồng xác thực email qua link (không phải OTP gõ tay,
 * vì click link tiện hơn cho việc xác thực email, khác với đổi mật khẩu cần OTP để xác nhận
 * đúng người đang cầm điện thoại/máy tính đó).
 */

/**
 * Tạo 1 token xác thực mới cho user, lưu bản băm SHA-256 vào DB (không lưu token gốc),
 * trả về token gốc (dạng hex) để gắn vào link gửi qua email.
 */
function create_email_verification_token($conn, $user_id, $ttlMinutes = 60 * 24)
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + $ttlMinutes * 60);

    // Vô hiệu các token cũ chưa dùng của user này để tránh tồn đọng nhiều link còn hiệu lực cùng lúc.
    $conn->query('UPDATE email_verifications SET used_at=NOW() WHERE user_id=' . (int)$user_id . ' AND used_at IS NULL');

    $stmt = $conn->prepare('INSERT INTO email_verifications (user_id,token_hash,expires_at) VALUES (?,?,?)');
    $stmt->bind_param('iss', $user_id, $tokenHash, $expiresAt);
    $stmt->execute();
    $stmt->close();

    return $token;
}

/**
 * Xác thực 1 token: đúng user, chưa dùng, chưa hết hạn.
 * Nếu hợp lệ: đánh dấu đã dùng + set users.email_verified_at, trả về true.
 */
function verify_email_token($conn, $user_id, $token)
{
    if ($token === '' || $user_id <= 0) return false;
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare('SELECT id FROM email_verifications WHERE user_id=? AND token_hash=? AND used_at IS NULL AND expires_at >= NOW() LIMIT 1');
    $stmt->bind_param('is', $user_id, $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return false;

    $conn->begin_transaction();
    try {
        $u1 = $conn->prepare('UPDATE email_verifications SET used_at=NOW() WHERE id=?');
        $u1->bind_param('i', $row['id']);
        $u1->execute();
        $u1->close();

        $u2 = $conn->prepare('UPDATE users SET email_verified_at=NOW() WHERE id=?');
        $u2->bind_param('i', $user_id);
        $u2->execute();
        $u2->close();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Gửi email xác thực tới user, kèm link chứa token. Trả về true nếu gửi thành công
 * (hoặc false nếu SMTP chưa cấu hình / gửi lỗi — không nên chặn đăng ký chỉ vì gửi mail thất bại).
 */
function send_verification_email($conn, $user, $base_url)
{
    $token = create_email_verification_token($conn, $user['id']);
    $link = auth_absolute_url('xac-thuc-email?uid=' . (int)$user['id'] . '&token=' . $token);
    $site = auth_setting($conn, 'site_name', 'NovaTech');

    $subject = 'Xác thực địa chỉ email - ' . $site;
    $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#403832">'
        . '<h2 style="color:#6f513c">' . htmlspecialchars($site) . '</h2>'
        . '<p>Xin chào ' . htmlspecialchars($user['fullname']) . ',</p>'
        . '<p>Vui lòng bấm nút bên dưới để xác thực địa chỉ email của bạn:</p>'
        . '<p style="text-align:center;margin:24px 0"><a href="' . htmlspecialchars($link) . '" style="display:inline-block;padding:14px 28px;background:#6f513c;color:#fff;text-decoration:none;border-radius:8px;font-weight:700">Xác thực email</a></p>'
        . '<p style="color:#7a7069;font-size:13px">Hoặc dán link sau vào trình duyệt: ' . htmlspecialchars($link) . '</p>'
        . '<p style="color:#7a7069;font-size:13px">Link có hiệu lực trong 24 giờ. Nếu bạn không tạo tài khoản này, hãy bỏ qua email.</p>'
        . '</div>';

    return auth_send_mail($conn, $user['email'], $subject, $html);
}
