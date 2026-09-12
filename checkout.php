<?php
ob_start();
require_once 'config.php';
require_once 'includes/payment.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

if (empty($_SESSION['cart'])) {
    header('Location: ' . $base_url . 'gio-hang');
    exit();
}

$paymentOptions = [
    'cod' => ['label' => 'Thanh toán khi nhận hàng', 'icon' => 'fas fa-money-bill-wave', 'desc' => 'Thanh toán trực tiếp khi nhận được hàng.'],
    'vnpay' => ['label' => 'VNPay', 'icon' => 'fas fa-credit-card', 'desc' => 'Thanh toán trực tuyến an toàn qua cổng VNPay.'],
    'momo' => ['label' => 'Ví MoMo', 'icon' => 'fas fa-wallet', 'desc' => 'Thanh toán nhanh qua ví điện tử MoMo.']
];

$enabledMap = [
    'cod' => payment_get_setting($conn, 'payment_cod_enabled', '1') === '1',
    'vnpay' => payment_get_setting($conn, 'payment_vnpay_enabled', '1') === '1',
    'momo' => payment_get_setting($conn, 'payment_momo_enabled', '0') === '1'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) $subtotal += (float)$item['price'] * (int)$item['quantity'];

    $result = validate_coupon($conn, $_POST['coupon_code'] ?? '', $subtotal);
    if ($result['valid']) {
        $_SESSION['coupon'] = ['code' => $result['coupon']['code'], 'id' => (int)$result['coupon']['id']];
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
            'discount' => $result['discount'],
            'code' => $result['coupon']['code'],
            'total' => max(0, $subtotal - $result['discount'])
        ], JSON_UNESCAPED_UNICODE);
    } else {
        unset($_SESSION['coupon']);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'total' => $subtotal], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    unset($_SESSION['coupon']);
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) $subtotal += (float)$item['price'] * (int)$item['quantity'];
    echo json_encode(['status' => 'success', 'total' => $subtotal], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_checkout'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'cod');

    if ($address === '' || $phone === '' || $fullname === '') {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin nhận hàng.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($paymentOptions[$paymentMethod]) || empty($enabledMap[$paymentMethod])) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Phương thức thanh toán không khả dụng.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($paymentMethod === 'vnpay') {
        if (trim(payment_get_setting($conn, 'vnpay_tmn_code')) === '' || trim(payment_get_setting($conn, 'vnpay_hash_secret')) === '') {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'VNPay chưa được cấu hình TmnCode và HashSecret trong trang quản trị.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($paymentMethod === 'momo') {
        if (trim(payment_get_setting($conn, 'momo_partner_code')) === '' || trim(payment_get_setting($conn, 'momo_access_key')) === '' || trim(payment_get_setting($conn, 'momo_secret_key')) === '') {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'MoMo chưa được cấu hình Partner Code, Access Key và Secret Key trong trang quản trị.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $subtotal_money = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal_money += (float)$item['price'] * (int)$item['quantity'];
    }

    $applied_coupon = null;
    $discount_amount = 0;
    if (!empty($_SESSION['coupon']['code'])) {
        $couponCheck = validate_coupon($conn, $_SESSION['coupon']['code'], $subtotal_money);
        if ($couponCheck['valid']) {
            $applied_coupon = $couponCheck['coupon'];
            $discount_amount = $couponCheck['discount'];
        } else {
            // Mã không còn hợp lệ tại thời điểm đặt hàng (hết lượt/hết hạn/giỏ hàng thay đổi) -> bỏ qua, không áp giảm giá.
            unset($_SESSION['coupon']);
        }
    }
    $total_money = max(0, $subtotal_money - $discount_amount);
    $coupon_code_to_save = $applied_coupon ? $applied_coupon['code'] : null;

    $paymentStatus = $paymentMethod === 'cod' ? 'unpaid' : 'pending';
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO orders (user_id,total_money,status,shipping_address,phone,payment_method,payment_status,coupon_code,discount_amount,created_at) VALUES (?,?,'pending',?,?,?,?,?,?,NOW())");
        $stmt->bind_param('idsssssd', $user_id, $total_money, $address, $phone, $paymentMethod, $paymentStatus, $coupon_code_to_save, $discount_amount);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        log_order_status($conn, $order_id, 'pending', 'Đơn hàng được tạo', order_history_customer_actor($fullname));

        foreach ($_SESSION['cart'] as $id => $item) {
            $productId = (int)($item['product_id'] ?? $id);
            $variantId = (int)($item['variant_id'] ?? 0);
            $variantName = $item['variant_name'] ?? null;
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $name = (string)$item['name'];

            if ($variantId > 0) {
                // Sản phẩm có biến thể: kiểm tra và trừ tồn kho theo biến thể.
                $stmt_check = $conn->prepare('SELECT stock_quantity FROM product_variants WHERE id=? AND product_id=? AND status=1 FOR UPDATE');
                $stmt_check->bind_param('ii', $variantId, $productId);
                $stmt_check->execute();
                $v_data = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if (!$v_data || (int)$v_data['stock_quantity'] < $qty) {
                    $available = $v_data ? (int)$v_data['stock_quantity'] : 0;
                    throw new Exception('Sản phẩm ' . $name . ' chỉ còn ' . $available . ' cái trong kho.');
                }

                $stmt_update = $conn->prepare('UPDATE product_variants SET stock_quantity=stock_quantity-? WHERE id=?');
                $stmt_update->bind_param('ii', $qty, $variantId);
                $stmt_update->execute();
                $stmt_update->close();
                sync_product_stock_from_variants($conn, $productId);

                $stmt_update2 = $conn->prepare('UPDATE products SET sales_count=sales_count+? WHERE id=?');
                $stmt_update2->bind_param('ii', $qty, $productId);
                $stmt_update2->execute();
                $stmt_update2->close();
            } else {
                $stmt_check = $conn->prepare('SELECT stock_quantity FROM products WHERE id=? FOR UPDATE');
                $stmt_check->bind_param('i', $productId);
                $stmt_check->execute();
                $p_data = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if (!$p_data || (int)$p_data['stock_quantity'] < $qty) {
                    $available = $p_data ? (int)$p_data['stock_quantity'] : 0;
                    throw new Exception('Sản phẩm ' . $name . ' chỉ còn ' . $available . ' cái trong kho.');
                }

                $stmt_update = $conn->prepare('UPDATE products SET stock_quantity=stock_quantity-?,sales_count=sales_count+? WHERE id=?');
                $stmt_update->bind_param('iii', $qty, $qty, $productId);
                $stmt_update->execute();
                $stmt_update->close();
            }

            $variantIdForInsert = $variantId > 0 ? $variantId : null;
            $stmt_item = $conn->prepare('INSERT INTO order_items (order_id,product_id,variant_id,variant_name,quantity,price) VALUES (?,?,?,?,?,?)');
            $stmt_item->bind_param('iiisid', $order_id, $productId, $variantIdForInsert, $variantName, $qty, $price);
            $stmt_item->execute();
            $stmt_item->close();

            $log_message = $fullname . ' đã đặt ' . $qty . ' ' . $name;
            $stmt_log = $conn->prepare('INSERT INTO purchase_logs (order_id,log_message,created_at) VALUES (?,?,NOW())');
            $stmt_log->bind_param('is', $order_id, $log_message);
            $stmt_log->execute();
            $stmt_log->close();
        }

        $conn->commit();
        if ($applied_coupon) {
            increment_coupon_usage($conn, (int)$applied_coupon['id']);
        }
        unset($_SESSION['cart']);
        unset($_SESSION['coupon']);
        $redirect = $paymentMethod === 'cod' ? $base_url . 'don-hang' : $base_url . 'payment_start.php?order_id=' . $order_id;
        ob_end_clean();
        echo json_encode([
            'status' => 'success',
            'message' => $paymentMethod === 'cod' ? 'Đơn hàng đã được tạo thành công!' : 'Đơn hàng đã được tạo. Đang chuyển đến bước thanh toán.',
            'redirect_url' => $redirect
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

require_once 'header.php';

$stmt_user = $conn->prepare('SELECT fullname,phone,address,email FROM users WHERE id=?');
$stmt_user->bind_param('i', $_SESSION['user_id']);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$cart_items = $_SESSION['cart'];
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += (float)$item['price'] * (int)$item['quantity'];
}

$applied_coupon_view = null;
$discount_preview = 0;
if (!empty($_SESSION['coupon']['code'])) {
    $couponPreview = validate_coupon($conn, $_SESSION['coupon']['code'], $total_price);
    if ($couponPreview['valid']) {
        $applied_coupon_view = $couponPreview['coupon'];
        $discount_preview = $couponPreview['discount'];
    } else {
        unset($_SESSION['coupon']);
    }
}
$final_price = max(0, $total_price - $discount_preview);
?>
<style>
.checkout-page{padding:40px 0 80px}.checkout-layout{display:grid;grid-template-columns:1fr;gap:30px}.checkout-box{background:#fff;border-radius:16px;box-shadow:0 5px 20px rgba(0,0,0,.03);padding:25px;border:1px solid #f1f1f1}.box-title{font-size:18px;font-weight:900;color:#2c3e50;margin-bottom:20px;border-bottom:2px dashed #eee;padding-bottom:15px;display:flex;align-items:center;gap:10px}.box-title i{color:#465f79}.form-group{margin-bottom:20px}.form-label{display:block;margin-bottom:8px;font-weight:bold;color:#444;font-size:14px}.form-control{width:100%;padding:14px 18px;border:2px solid #eaeaea;border-radius:10px;font-size:15px;transition:.3s;background:#fcfcfc}.form-control:focus{border-color:#465f79;outline:none;background:#fff;box-shadow:0 0 0 4px rgba(70,95,121,.12)}.form-control:read-only{background:#f5f5f5;color:#777}.order-items-wrapper{max-height:250px;overflow-y:auto;padding-right:10px;margin-bottom:20px}.checkout-item{display:flex;align-items:center;gap:15px;padding:15px 0;border-bottom:1px solid #f4f4f4}.ci-img{width:70px;height:70px;border-radius:8px;border:1px solid #eee;background:#fafafa;padding:5px;object-fit:contain;flex-shrink:0}.ci-info{flex:1}.ci-name{font-size:14px;font-weight:bold;color:#333;line-height:1.4;margin-bottom:5px}.ci-meta{display:flex;justify-content:space-between;align-items:center;font-size:14px}.ci-price{color:#243449;font-weight:bold}.ci-qty{color:#888}.summary-line{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;font-size:15px;color:#555}.summary-line.total{border-top:2px dashed #eee;padding-top:15px;margin-top:15px;font-size:18px;font-weight:900;color:#2c3e50}.price-final{color:#e74c3c;font-size:24px}.payment-methods{display:grid;gap:10px;margin-top:18px}.payment-option{position:relative}.payment-option input{position:absolute;opacity:0;pointer-events:none}.payment-card{min-height:72px;padding:12px 14px;border:1px solid #dfe6eb;border-radius:11px;background:#fff;display:grid;grid-template-columns:42px 1fr 20px;gap:11px;align-items:center;cursor:pointer;transition:.2s}.payment-card:hover{border-color:#9bb0c0}.payment-icon{width:42px;height:42px;border-radius:9px;background:#eef4f8;color:#365a76;display:flex;align-items:center;justify-content:center;font-size:18px}.payment-copy strong{display:block;color:#263b4d;font-size:14px}.payment-copy span{display:block;margin-top:4px;color:#75828b;font-size:14px;line-height:1.35}.payment-check{width:18px;height:18px;border:2px solid #b8c4cc;border-radius:50%;position:relative}.payment-option input:checked+.payment-card{border-color:#465f79;box-shadow:0 0 0 2px rgba(70,95,121,.08)}.payment-option input:checked+.payment-card .payment-check{border-color:#465f79}.payment-option input:checked+.payment-card .payment-check:after{content:'';position:absolute;inset:3px;border-radius:50%;background:#465f79}.btn-submit-order{display:flex;justify-content:center;align-items:center;gap:10px;width:100%;padding:16px;background:#465f79;color:#fff;border:none;border-radius:10px;font-weight:900;font-size:16px;cursor:pointer;transition:.3s;margin-top:20px}.btn-submit-order:hover{background:#30475e}.btn-submit-order:disabled{opacity:.65;cursor:not-allowed}.payment-empty{padding:13px;border:1px dashed #d5dde2;border-radius:10px;color:#75828b;font-size:14px;text-align:center}.payment-name-line{display:flex;align-items:center;gap:7px}.vnpay-info-btn{width:20px;height:20px;border:1px solid #9fb2c1;border-radius:50%;background:#fff;color:#365a76;font-size:14px;font-weight:bold;line-height:18px;text-align:center;cursor:pointer;padding:0;flex:0 0 20px}.vnpay-info-btn:hover{background:#eef4f8}.vnpay-info-modal{position:fixed;inset:0;z-index:99999;background:rgba(18,35,49,.48);display:none;align-items:center;justify-content:center;padding:18px}.vnpay-info-modal.show{display:flex}.vnpay-info-dialog{width:min(520px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #dbe5eb}.vnpay-info-head{padding:16px 18px;border-bottom:1px solid #e4ebef;display:flex;align-items:center;justify-content:space-between}.vnpay-info-head h3{margin:0;color:#263b4d;font-size:18px}.vnpay-info-close{width:32px;height:32px;border:0;border-radius:8px;background:#f2f5f7;color:#435665;cursor:pointer}.vnpay-info-body{padding:18px}.vnpay-info-note{padding:11px 12px;border-radius:9px;background:#f6f9fb;border:1px solid #dfe8ed;color:#536470;font-size:14px;line-height:1.5;margin-bottom:14px}.vnpay-data-list{display:grid;gap:9px}.vnpay-data-row{display:grid;grid-template-columns:125px 1fr 34px;align-items:center;gap:8px;padding:10px 11px;border:1px solid #e1e8ed;border-radius:9px}.vnpay-data-row span{font-size:14px;color:#6a7881}.vnpay-data-row strong{font-size:14px;color:#263b4d;word-break:break-all}.vnpay-copy-btn{width:34px;height:32px;border:1px solid #d5e0e7;border-radius:7px;background:#fff;color:#365a76;cursor:pointer}.vnpay-copy-all{margin-top:14px;width:100%;height:40px;border:0;border-radius:8px;background:#465f79;color:#fff;font-weight:bold;cursor:pointer}.coupon-box{margin-bottom:18px}.coupon-input-row{display:flex;gap:8px}.coupon-input-row .form-control{padding:11px 14px;font-size:14px}.btn-coupon{flex-shrink:0;padding:0 16px;border:0;border-radius:10px;background:#465f79;color:#fff;font-weight:bold;font-size:14px;cursor:pointer}.btn-coupon-remove{background:#e74c3c}.coupon-msg{margin-top:8px;font-size:13px;min-height:18px}.coupon-msg .ok{color:#16a34a}.coupon-msg .err{color:#e74c3c}@media(min-width:992px){.checkout-layout{grid-template-columns:1.2fr 1fr;gap:40px}.checkout-box.sticky{position:sticky;top:90px}}
</style>
<div class="container checkout-page">
    <div class="page-intro"><div><h1>Thanh toán đơn hàng</h1><p>Xác nhận thông tin nhận hàng và lựa chọn phương thức thanh toán.</p></div><div class="page-intro-icon"><i class="fas fa-credit-card"></i></div></div>
    <form id="checkoutForm" method="POST">
        <input type="hidden" name="ajax_checkout" value="1">
        <?php echo csrf_field(); ?>
        <div class="checkout-layout">
            <div class="checkout-box">
                <h3 class="box-title"><i class="fas fa-location-dot"></i> Thông tin nhận hàng</h3>
                <div class="form-group"><label class="form-label">Họ và tên</label><input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user_info['fullname'] ?? ''); ?>" required></div>
                <div class="form-group"><label class="form-label">Số điện thoại</label><input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" required></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" readonly></div>
                <div class="form-group"><label class="form-label">Địa chỉ giao hàng chi tiết</label><textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea></div>
            </div>
            <div class="checkout-box sticky">
                <h3 class="box-title"><i class="fas fa-bag-shopping"></i> Đơn hàng của bạn</h3>
                <div class="order-items-wrapper">
                    <?php foreach ($cart_items as $id => $item): ?>
                        <div class="checkout-item">
                            <img src="<?php echo htmlspecialchars(media_url($item['image'] ?? '', $base_url)); ?>" class="ci-img" alt="">
                            <div class="ci-info"><div class="ci-name"><?php echo htmlspecialchars($item['name']); ?><?php if(!empty($item['variant_name'])):?><br><small style="color:#465f79;font-weight:normal;">Phiên bản: <?php echo htmlspecialchars($item['variant_name']); ?></small><?php endif;?></div><div class="ci-meta"><span class="ci-price"><?php echo number_format($item['price'],0,',','.'); ?> đ</span><span class="ci-qty">x<?php echo (int)$item['quantity']; ?></span></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="coupon-box">
                    <div class="coupon-input-row">
                        <input type="text" id="couponInput" class="form-control" placeholder="Nhập mã giảm giá" value="<?php echo $applied_coupon_view ? htmlspecialchars($applied_coupon_view['code']) : ''; ?>" <?php echo $applied_coupon_view ? 'readonly' : ''; ?>>
                        <?php if ($applied_coupon_view): ?>
                            <button type="button" class="btn-coupon btn-coupon-remove" id="btnRemoveCoupon"><i class="fas fa-xmark"></i> Bỏ mã</button>
                        <?php else: ?>
                            <button type="button" class="btn-coupon" id="btnApplyCoupon">Áp dụng</button>
                        <?php endif; ?>
                    </div>
                    <div class="coupon-msg" id="couponMsg"><?php if ($applied_coupon_view): ?><span class="ok"><i class="fas fa-circle-check"></i> Đã áp dụng mã "<?php echo htmlspecialchars($applied_coupon_view['code']); ?>" — <?php echo coupon_value_label($applied_coupon_view); ?></span><?php endif; ?></div>
                </div>
                <div class="summary-line"><span>Tạm tính:</span><strong id="sumSubtotal"><?php echo number_format($total_price,0,',','.'); ?> đ</strong></div>
                <div class="summary-line" id="sumDiscountRow" style="<?php echo $discount_preview > 0 ? '' : 'display:none'; ?>"><span>Giảm giá:</span><strong id="sumDiscount" style="color:#16a34a">-<?php echo number_format($discount_preview,0,',','.'); ?> đ</strong></div>
                <div class="summary-line"><span>Phí vận chuyển:</span><strong>Miễn phí</strong></div>
                <div class="summary-line total"><span>Tổng thanh toán:</span><span class="price-final" id="sumTotal"><?php echo number_format($final_price,0,',','.'); ?> đ</span></div>
                <h3 class="box-title" style="margin-top:23px"><i class="fas fa-wallet"></i> Phương thức thanh toán</h3>
                <div class="payment-methods">
                    <?php $hasPayment = false; foreach ($paymentOptions as $key => $option): if (empty($enabledMap[$key])) continue; $hasPayment = true; ?>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="<?php echo $key; ?>" <?php echo $key === 'cod' ? 'checked' : ''; ?>>
                            <span class="payment-card"><span class="payment-icon"><i class="<?php echo $option['icon']; ?>"></i></span><span class="payment-copy"><span class="payment-name-line"><strong><?php echo htmlspecialchars($option['label']); ?></strong><?php if ($key === 'vnpay'): ?><button type="button" class="vnpay-info-btn" aria-label="Thông tin thanh toán VNPay" title="Thông tin thanh toán VNPay" onclick="openVnpayInfo(event)">i</button><?php endif; ?></span><span><?php echo htmlspecialchars($option['desc']); ?></span></span><span class="payment-check"></span></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!$hasPayment): ?><div class="payment-empty">Chưa có phương thức thanh toán nào được bật.</div><?php endif; ?>
                </div>
                <button type="submit" class="btn-submit-order" id="btnSubmitOrder" <?php echo !$hasPayment ? 'disabled' : ''; ?>><i class="fas fa-circle-check"></i> Xác nhận đặt hàng</button>
            </div>
        </div>
    </form>
</div>
<div class="vnpay-info-modal" id="vnpayInfoModal" aria-hidden="true">
    <div class="vnpay-info-dialog" role="dialog" aria-modal="true" aria-labelledby="vnpayInfoTitle">
        <div class="vnpay-info-head"><h3 id="vnpayInfoTitle">Thông tin thanh toán VNPay</h3><button type="button" class="vnpay-info-close" onclick="closeVnpayInfo()" aria-label="Đóng"><i class="fas fa-xmark"></i></button></div>
        <div class="vnpay-info-body">
            <div class="vnpay-info-note">Khi VNPay mở trang thanh toán, chọn ngân hàng NCB rồi nhập các thông tin dưới đây. Bấm biểu tượng sao chép để lấy nhanh từng giá trị.</div>
            <div class="vnpay-data-list">
                <div class="vnpay-data-row"><span>Ngân hàng</span><strong>NCB</strong><button type="button" class="vnpay-copy-btn" onclick="copyVnpayValue('NCB',this)" title="Sao chép"><i class="far fa-copy"></i></button></div>
                <div class="vnpay-data-row"><span>Số thẻ</span><strong>9704198526191432198</strong><button type="button" class="vnpay-copy-btn" onclick="copyVnpayValue('9704198526191432198',this)" title="Sao chép"><i class="far fa-copy"></i></button></div>
                <div class="vnpay-data-row"><span>Chủ thẻ</span><strong>NGUYEN VAN A</strong><button type="button" class="vnpay-copy-btn" onclick="copyVnpayValue('NGUYEN VAN A',this)" title="Sao chép"><i class="far fa-copy"></i></button></div>
                <div class="vnpay-data-row"><span>Ngày phát hành</span><strong>07/15</strong><button type="button" class="vnpay-copy-btn" onclick="copyVnpayValue('07/15',this)" title="Sao chép"><i class="far fa-copy"></i></button></div>
                <div class="vnpay-data-row"><span>OTP</span><strong>123456</strong><button type="button" class="vnpay-copy-btn" onclick="copyVnpayValue('123456',this)" title="Sao chép"><i class="far fa-copy"></i></button></div>
            </div>
            <button type="button" class="vnpay-copy-all" onclick="copyAllVnpayInfo(this)"><i class="far fa-copy"></i> Sao chép toàn bộ thông tin</button>
        </div>
    </div>
</div>
<script>
function openVnpayInfo(event){event.preventDefault();event.stopPropagation();const modal=document.getElementById('vnpayInfoModal');modal.classList.add('show');modal.setAttribute('aria-hidden','false')}
function closeVnpayInfo(){const modal=document.getElementById('vnpayInfoModal');modal.classList.remove('show');modal.setAttribute('aria-hidden','true')}
async function copyVnpayValue(value,button){try{await navigator.clipboard.writeText(value);const old=button.innerHTML;button.innerHTML='<i class="fas fa-check"></i>';setTimeout(function(){button.innerHTML=old},900)}catch(e){Swal.fire({icon:'info',title:'Thông tin cần nhập',text:value,confirmButtonColor:'#465f79'})}}
async function copyAllVnpayInfo(button){const value='Ngân hàng: NCB\nSố thẻ: 9704198526191432198\nChủ thẻ: NGUYEN VAN A\nNgày phát hành: 07/15\nOTP: 123456';try{await navigator.clipboard.writeText(value);const old=button.innerHTML;button.innerHTML='<i class="fas fa-check"></i> Đã sao chép';setTimeout(function(){button.innerHTML=old},1200)}catch(e){Swal.fire({icon:'info',title:'Thông tin thanh toán',html:'NCB<br>9704198526191432198<br>NGUYEN VAN A<br>07/15<br>123456',confirmButtonColor:'#465f79'})}}
document.getElementById('vnpayInfoModal')?.addEventListener('click',function(e){if(e.target===this)closeVnpayInfo()});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeVnpayInfo()});
const checkoutForm=document.getElementById('checkoutForm');
const btnSubmit=document.getElementById('btnSubmitOrder');
function formatVnd(n){return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')+' đ'}
const csrfTokenInput=checkoutForm?checkoutForm.querySelector('input[name="csrf_token"]'):null;
const btnApplyCoupon=document.getElementById('btnApplyCoupon');
const btnRemoveCoupon=document.getElementById('btnRemoveCoupon');
const couponInput=document.getElementById('couponInput');
const couponMsg=document.getElementById('couponMsg');
async function postCouponAction(payload){
    const formData=new FormData();
    formData.append('csrf_token',csrfTokenInput?csrfTokenInput.value:'');
    Object.keys(payload).forEach(function(key){formData.append(key,payload[key])});
    const response=await fetch('',{method:'POST',body:formData});
    return response.json();
}
if(btnApplyCoupon){
    btnApplyCoupon.addEventListener('click',async function(){
        const code=(couponInput.value||'').trim();
        if(!code){couponMsg.innerHTML='<span class="err">Vui lòng nhập mã giảm giá.</span>';return}
        btnApplyCoupon.disabled=true;btnApplyCoupon.textContent='Đang kiểm tra...';
        try{
            const data=await postCouponAction({apply_coupon:1,coupon_code:code});
            if(data.status==='success'){
                document.getElementById('sumDiscountRow').style.display='';
                document.getElementById('sumDiscount').textContent='-'+formatVnd(data.discount);
                document.getElementById('sumTotal').textContent=formatVnd(data.total);
                couponMsg.innerHTML='<span class="ok"><i class="fas fa-circle-check"></i> Đã áp dụng mã "'+data.code+'".</span>';
                couponInput.setAttribute('readonly','readonly');
                btnApplyCoupon.style.display='none';
                const removeBtn=document.createElement('button');
                removeBtn.type='button';removeBtn.className='btn-coupon btn-coupon-remove';removeBtn.id='btnRemoveCoupon';
                removeBtn.innerHTML='<i class="fas fa-xmark"></i> Bỏ mã';
                btnApplyCoupon.insertAdjacentElement('afterend',removeBtn);
                bindRemoveCoupon(removeBtn);
            }else{
                couponMsg.innerHTML='<span class="err">'+data.message+'</span>';
                document.getElementById('sumDiscountRow').style.display='none';
                document.getElementById('sumTotal').textContent=formatVnd(data.total);
            }
        }catch(e){
            couponMsg.innerHTML='<span class="err">Có lỗi xảy ra, vui lòng thử lại.</span>';
        }finally{
            btnApplyCoupon.disabled=false;btnApplyCoupon.textContent='Áp dụng';
        }
    });
}
function bindRemoveCoupon(el){
    el.addEventListener('click',async function(){
        el.disabled=true;
        try{
            const data=await postCouponAction({remove_coupon:1});
            document.getElementById('sumDiscountRow').style.display='none';
            document.getElementById('sumTotal').textContent=formatVnd(data.total);
            couponMsg.innerHTML='';
            couponInput.value='';couponInput.removeAttribute('readonly');
            el.remove();
            if(btnApplyCoupon) btnApplyCoupon.style.display='';
        }catch(e){el.disabled=false}
    });
}
if(btnRemoveCoupon) bindRemoveCoupon(btnRemoveCoupon);
if(checkoutForm){checkoutForm.addEventListener('submit',async function(e){e.preventDefault();btnSubmit.disabled=true;btnSubmit.innerHTML='<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';try{const response=await fetch('',{method:'POST',body:new FormData(this)});const data=await response.json();if(data.status==='success'){Swal.fire({icon:'success',title:'Đã tạo đơn hàng',text:data.message,showConfirmButton:false,timer:1200}).then(function(){window.location.href=data.redirect_url})}else{throw new Error(data.message||'Không thể tạo đơn hàng')}}catch(error){btnSubmit.disabled=false;btnSubmit.innerHTML='<i class="fas fa-circle-check"></i> Xác nhận đặt hàng';Swal.fire({icon:'error',title:'Không thể đặt hàng',text:error.message||'Có lỗi xảy ra.',confirmButtonColor:'#465f79'})}})}
</script>
<?php require_once 'footer.php'; ?>
