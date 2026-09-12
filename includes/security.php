<?php
/**
 * Các hàm bảo mật dùng chung: hash mật khẩu (bcrypt), CSRF token,
 * security headers, và cấu hình session an toàn hơn.
 */

function security_send_headers()
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Băm mật khẩu bằng bcrypt (thay cho MD5).
 */
function password_hash_secure($plain)
{
    return password_hash((string)$plain, PASSWORD_DEFAULT);
}

/**
 * Kiểm tra mật khẩu, hỗ trợ cả hash bcrypt mới và hash MD5 cũ còn sót lại
 * trong dữ liệu cũ. Nếu khớp bằng MD5, tự nâng cấp lên bcrypt ngay trong DB.
 * Trả về true/false. $conn và $userId truyền vào để có thể tự nâng cấp hash.
 */
function password_verify_upgradeable($conn, $userId, $plain, $storedHash)
{
    $storedHash = (string)$storedHash;
    $plain = (string)$plain;

    $info = password_get_info($storedHash);
    if (($info['algo'] ?? null) !== null) {
        return password_verify($plain, $storedHash);
    }

    // Hash cũ dạng MD5 (32 ký tự hex) — kiểm tra tương thích ngược rồi nâng cấp.
    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash) && hash_equals($storedHash, md5($plain))) {
        if ($conn && $userId) {
            $newHash = password_hash_secure($plain);
            $stmt = $conn->prepare('UPDATE users SET password=? WHERE id=?');
            $stmt->bind_param('si', $newHash, $userId);
            $stmt->execute();
            $stmt->close();
        }
        return true;
    }

    return false;
}

/* ================= CSRF ================= */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify($token)
{
    if (empty($_SESSION['csrf_token']) || !is_string($token) || $token === '') return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gọi ở đầu khối xử lý POST. Nếu token sai, dừng và báo lỗi thay vì thực thi.
 */
function csrf_guard()
{
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Yêu cầu không hợp lệ hoặc đã hết hạn (CSRF token sai). Vui lòng tải lại trang và thử lại.');
    }
}

/* ================= SSRF protection cho tải ảnh từ link ngoài ================= */

/**
 * Kiểm tra host của URL không trỏ vào mạng nội bộ/loopback/metadata,
 * để chống SSRF khi admin dán link ảnh và server tự tải về.
 */
function is_url_host_public($url)
{
    $host = parse_url((string)$url, PHP_URL_HOST);
    if (!$host) return false;

    // Chặn theo tên host thường dùng để trỏ nội bộ.
    $lowerHost = strtolower($host);
    if ($lowerHost === 'localhost' || substr($lowerHost, -10) === '.localhost') return false;

    $ips = [];
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips[] = $host;
    } else {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $r) {
                if (!empty($r['ip'])) $ips[] = $r['ip'];
                if (!empty($r['ipv6'])) $ips[] = $r['ipv6'];
            }
        }
        if (!$ips) {
            $resolved = gethostbyname($host);
            if ($resolved !== $host) $ips[] = $resolved;
        }
    }
    if (!$ips) return false;

    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
    }
    return true;
}

/**
 * Lấy địa chỉ IP thực của client. Chỉ dùng REMOTE_ADDR (không tin các header có thể giả mạo
 * như X-Forwarded-For) trừ khi ứng dụng được đặt sau 1 reverse proxy tin cậy đã cấu hình riêng.
 */
function client_ip()
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Ghi lại 1 lần thử đăng nhập (thành công hay thất bại) vào bảng login_attempts,
 * dùng để chống brute-force theo IP/tài khoản một cách bền vững hơn so với chỉ dựa vào session
 * (session có thể bị xoá/đổi trình duyệt để reset bộ đếm).
 */
function record_login_attempt($conn, $identity, $success)
{
    $ip = client_ip();
    $successInt = $success ? 1 : 0;
    $stmt = $conn->prepare('INSERT INTO login_attempts (identity,ip_address,success,created_at) VALUES (?,?,?,NOW())');
    if (!$stmt) return;
    $stmt->bind_param('ssi', $identity, $ip, $successInt);
    $stmt->execute();
    $stmt->close();
}

/**
 * Kiểm tra xem tài khoản/IP này có đang bị tạm khoá do nhập sai quá nhiều lần không.
 * Khoá nếu có >= $maxAttempts lần thất bại liên tiếp (không tính các lần thành công sau đó)
 * trong $windowSeconds giây gần nhất, tính theo IP HOẶC theo định danh (username/email) — đạt 1 trong 2 là khoá,
 * để chống cả kiểu tấn công "1 IP thử nhiều tài khoản" lẫn "nhiều IP cùng thử 1 tài khoản".
 */
function is_login_locked($conn, $identity, $maxAttempts = 8, $windowSeconds = 300)
{
    $ip = client_ip();

    $stmt = $conn->prepare("SELECT COUNT(*) c FROM login_attempts WHERE ip_address=? AND success=0 AND created_at > (NOW() - INTERVAL ? SECOND)");
    $stmt->bind_param('si', $ip, $windowSeconds);
    $stmt->execute();
    $byIp = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    if ($byIp >= $maxAttempts) return true;

    if (trim((string)$identity) !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM login_attempts WHERE identity=? AND success=0 AND created_at > (NOW() - INTERVAL ? SECOND)");
        $stmt->bind_param('si', $identity, $windowSeconds);
        $stmt->execute();
        $byIdentity = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        if ($byIdentity >= $maxAttempts) return true;
    }

    return false;
}
