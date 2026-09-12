<?php
function payment_get_setting($conn, $key, $default = '')
{
    return settings_get($conn, $key, $default);
}

function payment_absolute_url($path = '')
{
    global $base_url;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

function payment_new_code($orderId)
{
    return 'DH' . (int)$orderId . 'T' . date('YmdHis') . random_int(10, 99);
}

function payment_get_order_by_code($conn, $code)
{
    $stmt = $conn->prepare('SELECT * FROM orders WHERE payment_code=? LIMIT 1');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function payment_get_order($conn, $orderId, $userId = null)
{
    if ($userId === null) {
        $stmt = $conn->prepare('SELECT * FROM orders WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $orderId);
    } else {
        $stmt = $conn->prepare('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1');
        $stmt->bind_param('ii', $orderId, $userId);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function payment_update_code($conn, $orderId, $code)
{
    $stmt = $conn->prepare("UPDATE orders SET payment_code=?,payment_status='pending',payment_transaction_id=NULL WHERE id=?");
    $stmt->bind_param('si', $code, $orderId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function payment_mark_result($conn, $orderId, $status, $transactionId = '')
{
    if ($status === 'paid') {
        $stmt = $conn->prepare("UPDATE orders SET payment_status='paid',payment_transaction_id=?,payment_paid_at=COALESCE(payment_paid_at,NOW()) WHERE id=? AND payment_status<>'paid'");
        $stmt->bind_param('si', $transactionId, $orderId);
    } else {
        $stmt = $conn->prepare("UPDATE orders SET payment_status=? WHERE id=? AND payment_status<>'paid'");
        $stmt->bind_param('si', $status, $orderId);
    }
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($ok && $affected > 0 && function_exists('log_order_status')) {
        $orderRow = payment_get_order($conn, $orderId);
        $currentStatus = $orderRow ? $orderRow['status'] : 'pending';
        if ($status === 'paid') {
            log_order_status($conn, $orderId, $currentStatus, 'Thanh toán trực tuyến thành công.', 'Cổng thanh toán');
        } elseif ($status === 'failed') {
            log_order_status($conn, $orderId, $currentStatus, 'Thanh toán trực tuyến thất bại.', 'Cổng thanh toán');
        }
    }

    return $ok;
}

function payment_create_vnpay_url($conn, $order)
{
    $tmnCode = trim(payment_get_setting($conn, 'vnpay_tmn_code'));
    $hashSecret = trim(payment_get_setting($conn, 'vnpay_hash_secret'));
    $paymentUrl = trim(payment_get_setting($conn, 'vnpay_url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'));
    if ($tmnCode === '' || $hashSecret === '') {
        return ['ok' => false, 'message' => 'VNPay chưa được cấu hình TmnCode và HashSecret.'];
    }
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $code = payment_new_code($order['id']);
    payment_update_code($conn, $order['id'], $code);
    $inputData = [
        'vnp_Version' => '2.1.0',
        'vnp_TmnCode' => $tmnCode,
        'vnp_Amount' => (int)round((float)$order['total_money']) * 100,
        'vnp_Command' => 'pay',
        'vnp_CreateDate' => date('YmdHis'),
        'vnp_CurrCode' => 'VND',
        'vnp_IpAddr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'vnp_Locale' => 'vn',
        'vnp_OrderInfo' => 'Thanh toan don hang ' . (int)$order['id'],
        'vnp_OrderType' => 'other',
        'vnp_ReturnUrl' => payment_absolute_url('vnpay_return.php'),
        'vnp_TxnRef' => $code,
        'vnp_ExpireDate' => date('YmdHis', strtotime('+15 minutes'))
    ];
    ksort($inputData);
    $pairs = [];
    foreach ($inputData as $key => $value) {
        $pairs[] = urlencode($key) . '=' . urlencode($value);
    }
    $hashData = implode('&', $pairs);
    $secureHash = hash_hmac('sha512', $hashData, $hashSecret);
    return ['ok' => true, 'url' => rtrim($paymentUrl, '?') . '?' . $hashData . '&vnp_SecureHash=' . $secureHash];
}

function payment_verify_vnpay($conn, $data)
{
    $hashSecret = trim(payment_get_setting($conn, 'vnpay_hash_secret'));
    $receivedHash = $data['vnp_SecureHash'] ?? '';
    if ($hashSecret === '' || $receivedHash === '') {
        return false;
    }
    $input = [];
    foreach ($data as $key => $value) {
        if (strpos($key, 'vnp_') === 0 && $key !== 'vnp_SecureHash' && $key !== 'vnp_SecureHashType') {
            $input[$key] = $value;
        }
    }
    ksort($input);
    $pairs = [];
    foreach ($input as $key => $value) {
        $pairs[] = urlencode($key) . '=' . urlencode($value);
    }
    $secureHash = hash_hmac('sha512', implode('&', $pairs), $hashSecret);
    return hash_equals(strtolower($secureHash), strtolower($receivedHash));
}
function payment_create_momo_url($conn, $order)
{
    $partnerCode = trim(payment_get_setting($conn, 'momo_partner_code'));
    $accessKey = trim(payment_get_setting($conn, 'momo_access_key'));
    $secretKey = trim(payment_get_setting($conn, 'momo_secret_key'));
    $endpoint = trim(payment_get_setting($conn, 'momo_endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create'));
    if ($partnerCode === '' || $accessKey === '' || $secretKey === '') {
        return ['ok' => false, 'message' => 'MoMo chưa được cấu hình Partner Code, Access Key và Secret Key.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'Máy chủ chưa bật cURL để kết nối MoMo.'];
    }

    $code = payment_new_code($order['id']);
    payment_update_code($conn, $order['id'], $code);

    $orderId = $code;
    $requestId = $orderId . '-' . bin2hex(random_bytes(4));
    $amount = (string)(int)round((float)$order['total_money']);
    $orderInfo = 'Thanh toan don hang ' . (int)$order['id'];
    $redirectUrl = payment_absolute_url('momo_return.php');
    $ipnUrl = payment_absolute_url('momo_ipn.php');
    $extraData = '';
    $requestType = 'captureWallet';

    $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$ipnUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=$requestType";
    $signature = hash_hmac('sha256', $rawHash, $secretKey);

    $payload = [
        'partnerCode' => $partnerCode,
        'partnerName' => 'NovaTech',
        'storeId' => 'NovaTechStore',
        'requestId' => $requestId,
        'amount' => $amount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $redirectUrl,
        'ipnUrl' => $ipnUrl,
        'lang' => 'vi',
        'extraData' => $extraData,
        'requestType' => $requestType,
        'signature' => $signature,
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'message' => 'Không thể kết nối tới MoMo: ' . $curlError];
    }

    $data = json_decode((string)$response, true);
    if (!is_array($data) || (int)($data['resultCode'] ?? -1) !== 0 || empty($data['payUrl'])) {
        return ['ok' => false, 'message' => $data['message'] ?? 'MoMo từ chối khởi tạo giao dịch.'];
    }

    return ['ok' => true, 'url' => $data['payUrl']];
}

function payment_verify_momo($conn, $data)
{
    $accessKey = trim(payment_get_setting($conn, 'momo_access_key'));
    $secretKey = trim(payment_get_setting($conn, 'momo_secret_key'));
    $receivedSignature = (string)($data['signature'] ?? '');
    if ($accessKey === '' || $secretKey === '' || $receivedSignature === '') return false;

    $amount = (string)($data['amount'] ?? '');
    $extraData = (string)($data['extraData'] ?? '');
    $message = (string)($data['message'] ?? '');
    $orderId = (string)($data['orderId'] ?? '');
    $orderInfo = (string)($data['orderInfo'] ?? '');
    $orderType = (string)($data['orderType'] ?? '');
    $partnerCode = (string)($data['partnerCode'] ?? '');
    $payType = (string)($data['payType'] ?? '');
    $requestId = (string)($data['requestId'] ?? '');
    $responseTime = (string)($data['responseTime'] ?? '');
    $resultCode = (string)($data['resultCode'] ?? '');
    $transId = (string)($data['transId'] ?? '');

    $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&message=$message&orderId=$orderId&orderInfo=$orderInfo&orderType=$orderType&partnerCode=$partnerCode&payType=$payType&requestId=$requestId&responseTime=$responseTime&resultCode=$resultCode&transId=$transId";
    $expectedSignature = hash_hmac('sha256', $rawHash, $secretKey);

    return hash_equals(strtolower($expectedSignature), strtolower($receivedSignature));
}
?>
