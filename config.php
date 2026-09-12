<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/includes/env.php';
load_env(__DIR__ . '/.env');
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/settings_store.php';
require_once __DIR__ . '/includes/variants.php';
require_once __DIR__ . '/includes/order_status.php';
require_once __DIR__ . '/includes/reviews.php';
require_once __DIR__ . '/includes/coupons.php';
security_send_headers();
$db_host = env('DB_HOST', 'localhost');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');
$db_name = env('DB_NAME', 'tech_store');
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die("Connection failed"); }
$conn->set_charset("utf8mb4");

function project_base_url()
{
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = realpath(__DIR__);
    if ($documentRoot && $projectRoot) {
        $documentRoot = str_replace('\\', '/', $documentRoot);
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $relativeRoot = trim(substr($projectRoot, strlen($documentRoot)), '/');
        return $relativeRoot === '' ? '/' : '/' . $relativeRoot . '/';
    }
    return '/tech_store/';
}

function media_url($value, $baseUrl = null)
{
    $value = trim((string)$value);
    if ($value === '') return 'https://placehold.co/700x700?text=No+Image';
    if (preg_match('~^https?://~i', $value)) return $value;

    $baseUrl = $baseUrl ?: project_base_url();
    $baseUrl = '/' . trim((string)$baseUrl, '/') . '/';
    if ($baseUrl === '//') $baseUrl = '/';

    $normalized = str_replace('\\', '/', $value);
    if (strpos($normalized, $baseUrl) === 0) return $normalized;

    $normalized = preg_replace('~^(?:\./|\.\./)+~', '', $normalized);
    $normalized = ltrim($normalized, '/');
    if (stripos($normalized, 'uploads/') === 0) {
        $normalized = substr($normalized, 8);
    }

    $segments = array_values(array_filter(explode('/', $normalized), 'strlen'));
    if (!$segments) return 'https://placehold.co/700x700?text=No+Image';

    return $baseUrl . 'uploads/' . implode('/', array_map('rawurlencode', $segments));
}

function normalize_content_media_urls($html, $baseUrl = null)
{
    $html = (string)$html;
    if ($html === '') return '';

    $baseUrl = $baseUrl ?: project_base_url();
    return preg_replace_callback(
        '~\bsrc\s*=\s*(["\'])([^"\']+)\1~i',
        function ($match) use ($baseUrl) {
            $src = trim($match[2]);
            if ($src === '' || preg_match('~^(?:https?:)?//|^(?:data|blob):|^#~i', $src)) {
                return $match[0];
            }
            if (preg_match('~^(?:\.\./|\./)*uploads/(.+)$~i', $src, $parts)) {
                return 'src=' . $match[1] . htmlspecialchars(media_url($parts[1], $baseUrl), ENT_QUOTES, 'UTF-8') . $match[1];
            }
            return $match[0];
        },
        $html
    );
}

function is_remote_media($value)
{
    return (bool)preg_match('~^https?://~i', trim((string)$value));
}

function valid_remote_media_url($value)
{
    $value = trim((string)$value);
    if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) return false;
    $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function save_uploaded_media($file, $uploadDir, $prefix = 'img', $maxBytes = 8388608)
{
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return false;
    if ((int)($file['size'] ?? 0) > $maxBytes) return false; // chặn upload quá lớn (mặc định tối đa 8MB), tránh làm đầy ổ đĩa.
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExt = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif'];
    if (!isset($allowedExt[$ext])) return false;

    // Kiểm tra nội dung file thực sự là ảnh, không chỉ dựa vào tên/đuôi file
    // (chống upload file .php.jpg hoặc file giả mạo đuôi ảnh).
    if (function_exists('finfo_open')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realType = (string)$finfo->file((string)($file['tmp_name'] ?? ''));
        $acceptable = [$allowedExt[$ext]];
        if ($ext === 'jpg' || $ext === 'jpeg') $acceptable = ['image/jpeg'];
        if (!in_array($realType, $acceptable, true)) return false;
    } elseif (function_exists('getimagesize')) {
        if (@getimagesize((string)($file['tmp_name'] ?? '')) === false) return false;
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) return false;
    $name = time() . '_' . $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $name) ? $name : false;
}

function download_remote_media($url, $uploadDir, $prefix = 'img', $maxBytes = 8388608)
{
    if (!valid_remote_media_url($url)) return false;
    if (!is_url_host_public($url)) return false; // chống SSRF tới mạng nội bộ/loopback
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) return false;

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    if (!function_exists('curl_init')) return false;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, // tắt redirect để tránh bị lách qua kiểm tra SSRF
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'NovaTech-ImageFetcher/1.0',
        CURLOPT_RANGE => '0-' . ($maxBytes - 1),
    ]);
    $data = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = strtolower(trim((string)explode(';', (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE))[0]));
    curl_close($ch);

    if ($data === false || $data === '' || $status >= 400) return false;
    if (strlen($data) > $maxBytes) return false;

    $ext = $allowedTypes[$contentType] ?? null;
    if ($ext === null && function_exists('finfo_open')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $sniffed = (string)$finfo->buffer($data);
        $ext = $allowedTypes[$sniffed] ?? null;
    }
    if ($ext === null) return false;

    $name = time() . '_' . $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $name;
    return file_put_contents($path, $data) !== false ? $name : false;
}

function delete_local_media($value, $uploadDir)
{
    $value = trim((string)$value);
    if ($value === '' || is_remote_media($value)) return;
    $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . basename($value);
    if (is_file($path)) unlink($path);
}

/**
 * Đồng bộ lại tên/ảnh/giá/tồn kho của giỏ hàng theo dữ liệu mới nhất trong DB.
 * Hỗ trợ cả dòng giỏ hàng có biến thể (product_id + variant_id) lẫn dòng cũ không có biến thể.
 */
function sync_cart_product_media($conn)
{
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return;

    $productIds = [];
    $variantIds = [];
    foreach ($_SESSION['cart'] as $key => $item) {
        $pid = (int)($item['product_id'] ?? $key);
        if ($pid > 0) $productIds[$pid] = true;
        $vid = (int)($item['variant_id'] ?? 0);
        if ($vid > 0) $variantIds[$vid] = true;
    }
    if (!$productIds) return;

    $products = [];
    $result = $conn->query('SELECT id,name,image,price,sale_price,stock_quantity FROM products WHERE id IN (' . implode(',', array_map('intval', array_keys($productIds))) . ')');
    if ($result) while ($row = $result->fetch_assoc()) $products[(int)$row['id']] = $row;

    $variants = [];
    if ($variantIds) {
        $result = $conn->query('SELECT * FROM product_variants WHERE id IN (' . implode(',', array_map('intval', array_keys($variantIds))) . ')');
        if ($result) while ($row = $result->fetch_assoc()) $variants[(int)$row['id']] = $row;
    }

    foreach ($_SESSION['cart'] as $key => $item) {
        $pid = (int)($item['product_id'] ?? $key);
        if (!isset($products[$pid])) {
            unset($_SESSION['cart'][$key]); // sản phẩm đã bị xoá khỏi cửa hàng
            continue;
        }
        $product = $products[$pid];
        $vid = (int)($item['variant_id'] ?? 0);

        if ($vid > 0) {
            if (!isset($variants[$vid]) || (int)$variants[$vid]['status'] !== 1) {
                unset($_SESSION['cart'][$key]); // biến thể đã bị xoá/ẩn
                continue;
            }
            $variant = $variants[$vid];
            $eff = variant_effective_price($product['price'], $product['sale_price'], $variant);
            $price = ($eff['sale_price'] !== null && $eff['sale_price'] < $eff['price']) ? $eff['sale_price'] : $eff['price'];
            $_SESSION['cart'][$key]['name'] = $product['name'] . ' (' . $variant['variant_name'] . ')';
            $_SESSION['cart'][$key]['variant_name'] = $variant['variant_name'];
            $_SESSION['cart'][$key]['price'] = $price;
            $_SESSION['cart'][$key]['max_stock'] = (int)$variant['stock_quantity'];
        } else {
            $_SESSION['cart'][$key]['name'] = $product['name'];
            $_SESSION['cart'][$key]['max_stock'] = (int)$product['stock_quantity'];
        }
        $_SESSION['cart'][$key]['product_id'] = $pid;
        $_SESSION['cart'][$key]['image'] = $product['image'];

        if ((int)$_SESSION['cart'][$key]['quantity'] > (int)$_SESSION['cart'][$key]['max_stock']) {
            $_SESSION['cart'][$key]['quantity'] = max(0, (int)$_SESSION['cart'][$key]['max_stock']);
            if ($_SESSION['cart'][$key]['quantity'] === 0) unset($_SESSION['cart'][$key]);
        }
    }
}

$base_url = project_base_url();
$conn->query("INSERT IGNORE INTO settings (setting_key,setting_value,icon) VALUES ('gemini_api_key','','fas fa-key'),('gemini_chat_enabled','1','fas fa-comments'),('gemini_chat_model','gemini-3.5-flash','fas fa-robot'),('gemini_dashboard_model','gemini-3.5-flash','fas fa-chart-line'),('gemini_writer_model','gemini-3.5-flash','fas fa-pen-nib')");
$conn->query("INSERT IGNORE INTO settings (setting_key,setting_value,icon) VALUES ('mail_host','smtp.gmail.com','fas fa-server'),('mail_port','587','fas fa-hashtag'),('mail_username','','fas fa-user'),('mail_password','','fas fa-key'),('mail_encryption','tls','fas fa-lock'),('mail_from_address','','fas fa-envelope'),('mail_from_name','NovaTech','fas fa-signature'),('google_client_id','','fab fa-google'),('google_client_secret','','fas fa-key'),('google_redirect_uri','','fas fa-link')");
?>
