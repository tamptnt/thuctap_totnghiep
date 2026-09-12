<?php
require_once 'config.php';
require_once 'includes/payment.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

$orderId = (int)($_GET['order_id'] ?? 0);
$order = payment_get_order($conn, $orderId, (int)$_SESSION['user_id']);
if (!$order) {
    header('Location: ' . $base_url . 'don-hang');
    exit();
}

$message = $_SESSION['payment_message'] ?? '';
unset($_SESSION['payment_message']);
$methodLabels = ['cod' => 'Thanh toán khi nhận hàng', 'vnpay' => 'VNPay'];
$statusLabels = ['unpaid' => 'Chưa thanh toán', 'pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thanh toán chưa thành công'];
$orderStatusLabels = ['pending' => 'Chờ xử lý', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn thành', 'canceled' => 'Đã hủy'];
require_once 'header.php';
?>
<style>
.payment-page{padding:34px 0 70px}.payment-shell{width:min(780px,100%);margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}.payment-head{padding:22px 24px;background:#f7fafc;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:15px;align-items:center}.payment-head h1{font-size:24px;color:var(--primary-dark)}.payment-head p{margin-top:4px;color:var(--muted);font-size:14px}.payment-badge{padding:7px 11px;border-radius:18px;background:#edf3f7;color:var(--primary-dark);font-size:14px;font-weight:bold}.payment-body{padding:24px}.payment-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.payment-summary div{padding:13px;border:1px solid var(--line);border-radius:10px;background:#fff}.payment-summary span{display:block;color:var(--muted);font-size:13px}.payment-summary strong{display:block;margin-top:5px;color:var(--primary-dark);font-size:15px}.notice{margin:16px 0;padding:12px 14px;border-radius:9px;background:#fff8e8;border:1px solid #efdfb3;color:#7a5c18;font-size:14px;line-height:1.5}.success-box{margin-top:18px;padding:28px;text-align:center;border:1px solid #cfe7da;border-radius:12px;background:#f3fbf7}.success-box i{font-size:42px;color:#3f8062}.success-box h2{margin-top:10px;color:#285e48}.payment-actions{margin-top:20px;display:flex;flex-wrap:wrap;gap:9px}.btn-pay{min-height:42px;padding:0 16px;border:0;border-radius:9px;background:var(--primary);color:#fff;font-weight:bold;display:inline-flex;align-items:center;gap:8px;cursor:pointer}.btn-light{background:#fff;color:var(--primary-dark);border:1px solid var(--line)}@media(max-width:700px){.payment-summary{grid-template-columns:1fr}.payment-head{align-items:flex-start;flex-direction:column}}
</style>
<div class="container payment-page">
    <div class="payment-shell">
        <div class="payment-head"><div><h1>Thanh toán đơn hàng <?php echo (int)$order['id']; ?></h1><p>Kiểm tra đúng số tiền trước khi thực hiện thanh toán.</p></div><span class="payment-badge"><?php echo htmlspecialchars($statusLabels[$order['payment_status']] ?? $order['payment_status']); ?></span></div>
        <div class="payment-body">
            <div class="payment-summary"><div><span>Phương thức</span><strong><?php echo htmlspecialchars($methodLabels[$order['payment_method']] ?? $order['payment_method']); ?></strong></div><div><span>Tổng tiền</span><strong><?php echo number_format($order['total_money'],0,',','.'); ?> đ</strong></div><div><span>Trạng thái đơn</span><strong><?php echo htmlspecialchars($orderStatusLabels[$order['status']] ?? $order['status']); ?></strong></div></div>
            <?php if ($message !== ''): ?><div class="notice"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($order['status'] === 'canceled'): ?>
                <div class="notice">Đơn hàng này đã bị hủy nên không thể tiếp tục thanh toán.</div>
                <div class="payment-actions"><a class="btn-pay btn-light" href="<?php echo $base_url; ?>don-hang">Về đơn hàng</a></div>
            <?php elseif ($order['payment_status'] === 'paid'): ?>
                <div class="success-box"><i class="fas fa-circle-check"></i><h2>Thanh toán thành công</h2><p>Giao dịch đã được xác nhận cho đơn hàng <?php echo (int)$order['id']; ?>.</p></div>
                <div class="payment-actions"><a class="btn-pay" href="<?php echo $base_url; ?>don-hang"><i class="fas fa-box"></i> Xem đơn hàng</a></div>
            <?php elseif ($order['payment_method'] === 'vnpay'): ?>
                <div class="notice">Nếu giao dịch chưa hoàn tất hoặc bạn đã đóng trang VNPay, có thể bấm thanh toán lại. Hệ thống sẽ tạo mã giao dịch mới cho cùng đơn hàng.</div>
                <div class="payment-actions"><a class="btn-pay" href="<?php echo $base_url; ?>payment_start.php?order_id=<?php echo (int)$order['id']; ?>"><i class="fas fa-arrow-up-right-from-square"></i> Thanh toán lại</a><a class="btn-pay btn-light" href="<?php echo $base_url; ?>don-hang">Về đơn hàng</a></div>
            <?php else: ?>
                <div class="payment-actions"><a class="btn-pay" href="<?php echo $base_url; ?>don-hang">Xem đơn hàng</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>
