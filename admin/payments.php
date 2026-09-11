<?php
require_once '../config.php';
require_once '../includes/payment.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../dang-nhap');
    exit();
}

$fields = [
    'payment_cod_enabled',
    'payment_vnpay_enabled',
    'vnpay_tmn_code',
    'vnpay_hash_secret',
    'vnpay_url',
    'payment_momo_enabled',
    'momo_partner_code',
    'momo_access_key',
    'momo_secret_key',
    'momo_endpoint'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_guard();
    $checkboxes = ['payment_cod_enabled','payment_vnpay_enabled','payment_momo_enabled'];
    foreach ($fields as $key) {
        if (in_array($key, $checkboxes, true)) {
            $value = isset($_POST[$key]) ? '1' : '0';
        } else {
            $value = trim($_POST[$key] ?? '');
        }
        if (($key === 'vnpay_hash_secret' || $key === 'momo_secret_key') && $value === '') {
            continue;
        }
        settings_set($conn, $key, $value, 'fas fa-credit-card');
    }
    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã lưu cấu hình thanh toán.'];
    header('Location: payments.php');
    exit();
}

$values = [];
foreach ($fields as $key) {
    $values[$key] = payment_get_setting($conn, $key);
}
if ($values['vnpay_url'] === '') $values['vnpay_url'] = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
if ($values['momo_endpoint'] === '') $values['momo_endpoint'] = 'https://test-payment.momo.vn/v2/gateway/api/create';
$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);
require_once 'header.php';
?>
<style>
.pay-grid{display:grid;grid-template-columns:1fr;gap:15px}.pay-card{background:#fff;border:1px solid var(--line);border-radius:13px;overflow:hidden}.pay-card-head{padding:16px 18px;background:#f8fafb;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px}.pay-title{display:flex;align-items:center;gap:10px}.pay-title i{width:34px;height:34px;border-radius:8px;background:var(--soft);color:var(--primary);display:flex;align-items:center;justify-content:center}.pay-title h3{font-size:16px;color:var(--navy)}.pay-title p{margin-top:3px;font-size:14px;color:var(--muted)}.switch{position:relative;width:45px;height:24px}.switch input{opacity:0;width:0;height:0}.slider{position:absolute;inset:0;border-radius:20px;background:#cbd5dc;cursor:pointer;transition:.2s}.slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;border-radius:50%;background:#fff;transition:.2s}.switch input:checked+.slider{background:var(--primary)}.switch input:checked+.slider:before{transform:translateX(21px)}.pay-card-body{padding:18px}.field-grid{display:grid;grid-template-columns:1fr;gap:13px}.form-group label{display:block;margin-bottom:6px;color:var(--navy);font-size:14px;font-weight:bold}.form-control{width:100%;height:42px;padding:0 12px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--text);outline:none}.form-control:focus{border-color:var(--primary)}.hint{margin-top:6px;color:var(--muted);font-size:13px;line-height:1.4}.callback-box{margin-top:12px;padding:11px 12px;border:1px dashed var(--line);border-radius:8px;background:#fafcfd;font-size:14px;color:var(--muted);word-break:break-all}.callback-box strong{color:var(--navy)}.save-bar{position:sticky;bottom:10px;margin-top:16px;padding:12px;border:1px solid var(--line);border-radius:11px;background:rgba(255,255,255,.96);display:flex;justify-content:flex-end}.btn-save{height:42px;padding:0 18px;border:0;border-radius:8px;background:var(--primary);color:#fff;font-weight:bold;cursor:pointer}.alert{margin-bottom:14px;padding:11px 13px;border-radius:9px;background:#edf8f2;border:1px solid #cae6d5;color:#35694f;font-size:14px}@media(min-width:900px){.pay-grid{grid-template-columns:1fr 1fr}.pay-card.full{grid-column:1/-1}.field-grid.two{grid-template-columns:1fr 1fr}}
</style>
<?php if ($msg): ?><div class="alert"><?php echo htmlspecialchars($msg['text']); ?></div><?php endif; ?>
<form method="POST"><?php echo csrf_field(); ?>
<div class="pay-grid">
    <section class="pay-card">
        <div class="pay-card-head"><div class="pay-title"><i class="fas fa-money-bill-wave"></i><div><h3>Thanh toán khi nhận hàng</h3><p>Cho phép khách thanh toán trực tiếp khi nhận hàng.</p></div></div><label class="switch"><input type="checkbox" name="payment_cod_enabled" <?php echo $values['payment_cod_enabled'] === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
        <div class="pay-card-body"><div class="hint">Bật để khách hàng có thể đặt đơn và thanh toán khi nhận hàng.</div></div>
    </section>
    <section class="pay-card">
        <div class="pay-card-head"><div class="pay-title"><i class="fas fa-credit-card"></i><div><h3>VNPay</h3><p>Thanh toán trực tuyến qua cổng VNPay.</p></div></div><label class="switch"><input type="checkbox" name="payment_vnpay_enabled" <?php echo $values['payment_vnpay_enabled'] === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
        <div class="pay-card-body"><div class="field-grid"><div class="form-group"><label>vnp_TmnCode</label><input class="form-control" name="vnpay_tmn_code" value="<?php echo htmlspecialchars($values['vnpay_tmn_code']); ?>"></div><div class="form-group"><label>vnp_HashSecret</label><input type="password" class="form-control" name="vnpay_hash_secret" placeholder="Để trống nếu không đổi"></div><div class="form-group"><label>URL thanh toán</label><input class="form-control" name="vnpay_url" value="<?php echo htmlspecialchars($values['vnpay_url']); ?>"></div></div><div class="callback-box"><strong>Return URL:</strong> <?php echo htmlspecialchars(payment_absolute_url('vnpay_return.php')); ?><br><strong>IPN URL:</strong> <?php echo htmlspecialchars(payment_absolute_url('vnpay_ipn.php')); ?></div></div>
    </section>
    <section class="pay-card">
        <div class="pay-card-head"><div class="pay-title"><i class="fas fa-wallet"></i><div><h3>Ví MoMo</h3><p>Thanh toán trực tuyến qua ví điện tử MoMo.</p></div></div><label class="switch"><input type="checkbox" name="payment_momo_enabled" <?php echo $values['payment_momo_enabled'] === '1' ? 'checked' : ''; ?>><span class="slider"></span></label></div>
        <div class="pay-card-body"><div class="field-grid"><div class="form-group"><label>Partner Code</label><input class="form-control" name="momo_partner_code" value="<?php echo htmlspecialchars($values['momo_partner_code']); ?>"></div><div class="form-group"><label>Access Key</label><input class="form-control" name="momo_access_key" value="<?php echo htmlspecialchars($values['momo_access_key']); ?>"></div><div class="form-group"><label>Secret Key</label><input type="password" class="form-control" name="momo_secret_key" placeholder="Để trống nếu không đổi"></div><div class="form-group"><label>Endpoint API</label><input class="form-control" name="momo_endpoint" value="<?php echo htmlspecialchars($values['momo_endpoint']); ?>"></div></div><div class="callback-box"><strong>Redirect URL:</strong> <?php echo htmlspecialchars(payment_absolute_url('momo_return.php')); ?><br><strong>IPN URL:</strong> <?php echo htmlspecialchars(payment_absolute_url('momo_ipn.php')); ?></div></div>
    </section>
</div>
<div class="save-bar"><button type="submit" class="btn-save"><i class="fas fa-floppy-disk"></i> Lưu cấu hình thanh toán</button></div>
</form>
<?php require_once 'footer.php'; ?>
