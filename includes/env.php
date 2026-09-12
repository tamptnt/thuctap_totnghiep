<?php
/**
 * Bộ nạp cấu hình môi trường tối giản (không cần composer/vlucas phpdotenv).
 * Đọc file .env dạng KEY=VALUE, bỏ qua dòng trống và dòng bắt đầu bằng #.
 * Biến môi trường thực của hệ điều hành (nếu có) luôn được ưu tiên hơn file .env,
 * để dễ ghi đè khi triển khai trên server thật (Docker, hosting, CI/CD...).
 */
function load_env($path)
{
    if (!is_file($path) || !is_readable($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Bỏ dấu ngoặc kép/đơn bao quanh giá trị nếu có, ví dụ DB_PASS="abc 123".
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($key) === false && !isset($_ENV[$key])) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Lấy giá trị biến môi trường: ưu tiên $_ENV, sau đó getenv(), cuối cùng là $default.
 */
function env($key, $default = null)
{
    if (array_key_exists($key, $_ENV)) return $_ENV[$key];
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
