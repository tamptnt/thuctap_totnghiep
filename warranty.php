<?php
ob_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
require_once 'header.php';

$warranty_items = get_user_warranty_items($conn, $user_id);
$active_count = count(array_filter($warranty_items, fn($i) => $i['is_active']));
?>
<style>
.warranty-page{padding:15px 0 60px}
.warranty-head{padding:17px 19px;border:1px solid var(--line);border-radius:14px;background:#fbfaf8;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.warranty-head h1{font-size:20px;color:var(--primary-dark);margin-bottom:4px}
.warranty-head p{color:var(--muted);font-size:13px}
.warranty-stat{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:12px;background:#fff;border:1px solid var(--line)}
.warranty-stat strong{font-size:22px;color:var(--primary-dark)}
.warranty-stat span{display:block;color:var(--muted);font-size:12px}
.wr-card{border:1px solid var(--line);border-radius:12px;background:#fff;margin-bottom:10px;padding:13px 14px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.wr-card img{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid var(--line);flex-shrink:0}
.wr-info{flex:1;min-width:200px}
.wr-name{font-weight:bold;color:var(--primary-dark);margin-bottom:3px}
.wr-meta{color:var(--muted);font-size:13px}
.wr-side{display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.wr-badge{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:bold;white-space:nowrap}
.wr-badge.active{background:#e7f7ee;color:#16a34a}
.wr-badge.expired{background:#fdeaea;color:#b65757}
.wr-end{color:var(--muted);font-size:12px}
.empty-box{padding:40px 20px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:14px}
.empty-box i{font-size:30px;display:block;margin-bottom:10px;color:#c7cdd6}
</style>

<div class="container warranty-page">
    <div class="breadcrumb"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i> Trang chủ</a><i class="fas fa-chevron-right"></i><span>Bảo hành</span></div>

    <div class="warranty-head">
        <div>
            <h1><i class="fas fa-shield-halved"></i> Tra cứu bảo hành</h1>
            <p>Danh sách sản phẩm bạn đã mua và tình trạng bảo hành hiện tại.</p>
        </div>
        <div class="warranty-stat"><i class="fas fa-shield-halved" style="color:#16a34a;font-size:22px"></i><div><strong><?php echo $active_count; ?></strong><span>sản phẩm còn bảo hành</span></div></div>
    </div>

    <?php if (!$warranty_items): ?>
        <div class="empty-box"><i class="fas fa-box-open"></i>Bạn chưa có sản phẩm nào đủ điều kiện tra cứu bảo hành (chỉ tính đơn hàng đã hoàn thành).</div>
    <?php else: foreach ($warranty_items as $item): ?>
        <div class="wr-card">
            <img src="<?php echo htmlspecialchars(media_url($item['product_image'] ?? '', $base_url)); ?>" alt="">
            <div class="wr-info">
                <div class="wr-name"><?php echo htmlspecialchars($item['product_name']); ?><?php echo $item['variant_name'] ? ' (' . htmlspecialchars($item['variant_name']) . ')' : ''; ?></div>
                <div class="wr-meta">Đơn hàng #<?php echo (int)$item['order_id']; ?> · Mua ngày <?php echo date('d/m/Y', strtotime($item['purchased_at'])); ?> · Bảo hành <?php echo (int)$item['warranty_months']; ?> tháng</div>
            </div>
            <div class="wr-side">
                <?php if ($item['is_active']): ?>
                    <span class="wr-badge active"><i class="fas fa-circle-check"></i> Còn <?php echo (int)$item['days_left']; ?> ngày</span>
                <?php else: ?>
                    <span class="wr-badge expired"><i class="fas fa-circle-xmark"></i> Đã hết hạn</span>
                <?php endif; ?>
                <span class="wr-end">Hạn: <?php echo date('d/m/Y', strtotime($item['warranty_end'])); ?></span>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php require_once 'footer.php'; ?>
