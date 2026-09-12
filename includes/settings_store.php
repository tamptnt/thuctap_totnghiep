<?php
/**
 * Lớp lưu trữ settings dùng chung cho bảng `settings`, tự động MÃ HOÁ các khoá nhạy cảm
 * (API key, mật khẩu SMTP, secret key cổng thanh toán...) trước khi lưu vào CSDL,
 * và tự GIẢI MÃ khi đọc ra — thay vì lưu thẳng plaintext như trước.
 *
 * Dùng AES-256-CBC với khoá lấy từ biến môi trường APP_KEY (xem file .env / .env.example).
 * Nếu APP_KEY chưa được cấu hình, hệ thống vẫn hoạt động bình thường (lưu plaintext như cũ)
 * để không làm sập ứng dụng, nhưng nên cấu hình APP_KEY trước khi triển khai thật.
 */

const SENSITIVE_SETTING_KEYS = [
    'gemini_api_key',
    'google_client_secret',
    'mail_password',
    'momo_secret_key',
    'vnpay_hash_secret',
];

/**
 * Lấy khoá mã hoá nhị phân 32 byte từ APP_KEY.
 * APP_KEY nên ở dạng "base64:xxxxx" (sinh bằng random_bytes(32) rồi base64_encode).
 * Nếu chỉ là chuỗi thường, sẽ băm SHA-256 để ra đủ 32 byte (kém an toàn hơn nhưng vẫn hoạt động).
 */
function settings_encryption_key()
{
    $key = env('APP_KEY', '');
    if ($key === '') return null;
    if (strpos($key, 'base64:') === 0) {
        $decoded = base64_decode(substr($key, 7), true);
        return $decoded !== false ? $decoded : null;
    }
    return hash('sha256', $key, true);
}

function settings_encrypt_value($value)
{
    $key = settings_encryption_key();
    if ($key === null || $value === '') return $value;
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) return $value;
    return 'ENC:' . base64_encode($iv . $cipher);
}

function settings_decrypt_value($stored)
{
    if (!is_string($stored) || strpos($stored, 'ENC:') !== 0) {
        return $stored; // giá trị chưa mã hoá từ trước (tương thích ngược), hoặc rỗng.
    }
    $key = settings_encryption_key();
    if ($key === null) return ''; // không có khoá -> không thể giải mã, coi như trống thay vì lộ ciphertext.

    $raw = base64_decode(substr($stored, 4), true);
    if ($raw === false || strlen($raw) < 17) return '';
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

/**
 * Đọc 1 setting theo key, tự động giải mã nếu là khoá nhạy cảm.
 */
function settings_get($conn, $key, $default = '')
{
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
    if (!$stmt) return $default;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return $default;

    $value = (string)$row['setting_value'];
    return in_array($key, SENSITIVE_SETTING_KEYS, true) ? settings_decrypt_value($value) : $value;
}

/**
 * Ghi 1 setting, tự động mã hoá nếu là khoá nhạy cảm.
 */
function settings_set($conn, $key, $value, $icon = 'fas fa-key')
{
    $toStore = in_array($key, SENSITIVE_SETTING_KEYS, true) ? settings_encrypt_value($value) : $value;
    $stmt = $conn->prepare('INSERT INTO settings (setting_key,setting_value,icon) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),icon=VALUES(icon)');
    if (!$stmt) return false;
    $stmt->bind_param('sss', $key, $toStore, $icon);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
