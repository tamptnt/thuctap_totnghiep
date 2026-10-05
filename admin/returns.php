<?php
include 'header.php';

$msg_type = '';
$msg_text = '';
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard();
    $action = $_POST['action'] ?? '';
    $return_id = (int)($_POST['return_id'] ?? 0);
    $admin_note = trim((string)($_POST['admin_note'] ?? ''));
    $admin_note = $admin_note !== '' ? $admin_note : null;

    if ($return_id > 0 && in_array($action, ['approve', 'reject', 'complete'], true)) {
        $fromStatuses = $action === 'complete' ? ['approved'] : ['pending'];
        $toStatus = ['approve' => 'approved', 'reject' => 'rejected', 'complete' => 'completed'][$action];

        $placeholders = implode(',', array_fill(0, count($fromStatuses), '?'));
        $stmt = $conn->prepare("UPDATE order_returns SET status = ?, admin_note = ?, processed_at = NOW()
                                WHERE id = ? AND status IN ($placeholders)");
        $types = 'ssi' . str_repeat('s', count($fromStatuses));
        $params = array_merge([$toStatus, $admin_note, $return_id], $fromStatuses);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        if ($ok) {
            $labels = ['approve' => 'duyệt', 'reject' => 'từ chối', 'complete' => 'hoàn tất'];
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã ' . $labels[$action] . ' yêu cầu #' . $return_id . '.'];
        } else {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không thể cập nhật (yêu cầu đã được xử lý hoặc không tồn tại).'];
        }
    }
    echo "<script>window.location.href='returns.php" . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '') . "';</script>";
    exit();
}

$filter = $_GET['filter'] ?? 'pending';
$validFilters = ['all', 'pending', 'approved', 'rejected', 'completed', 'canceled'];
if (!in_array($filter, $validFilters, true)) $filter = 'pending';
$where = $filter === 'all' ? '' : $conn->real_escape_string($filter);

$sql = "SELECT r.*, u.fullname, u.phone AS user_phone, p.name AS product_name, p.image AS product_image
        FROM order_returns r
        JOIN users u ON u.id = r.user_id
        JOIN products p ON p.id = r.product_id"
     . ($filter !== 'all' ? " WHERE r.status = '$where'" : '')
     . " ORDER BY r.created_at DESC";
$requests = $conn->query($sql);

$counts = [];
$countRes = $conn->query("SELECT status, COUNT(*) c FROM order_returns GROUP BY status");
while ($row = $countRes->fetch_assoc()) $counts[$row['status']] = (int)$row['c'];
$totalAll = array_sum($counts);

$tabs = [
    'pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'completed' => 'Hoàn tất',
    'rejected' => 'Từ chối', 'canceled' => 'Đã huỷ', 'all' => 'Tất cả',
];
?>
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap:wrap; gap:10px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #2d6f9d; padding-bottom: 5px; display: inline-block; }
    .filter-tabs { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    .filter-tabs a { padding:8px 14px; border-radius:8px; background:#fff; color:#2c3e50; text-decoration:none; font-size:13px; font-weight:bold; border:1px solid #e1e5eb; }
    .filter-tabs a.active { background:#2d6f9d; color:#fff; border-color:#2d6f9d; }
    .filter-tabs a .cnt { opacity:.7; margin-left:4px; }
    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 14px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 13px; }
    .product-cell { display:flex; align-items:center; gap:10px; }
    .product-cell img { width:40px; height:40px; object-fit:cover; border-radius:8px; border:1px solid #eee; }
    .cust-cell strong{display:block}
    .cust-cell span{color:#888;font-size:12px}
    .reason-cell{max-width:220px;color:#555;font-size:13px}
    .reason-cell small{display:block;color:#999;margin-top:3px}
    .type-pill{padding:3px 9px;border-radius:20px;font-size:12px;font-weight:bold;background:#eef2ff;color:#4338ca}
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size:12px; font-weight: bold; color: #fff; white-space:nowrap; }
    .status-pending{background:#f2b705}
    .status-approved{background:#2d6f9d}
    .status-completed{background:#16a34a}
    .status-rejected{background:#e74c3c}
    .status-canceled{background:#aaa}
    .admin-note{font-size:12px;color:#888;margin-top:4px}
    .btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: #fff; font-size:13px; }
    .btn-success { background-color: #16a34a; }
    .btn-danger { background-color: #e74c3c; }
    .btn-primary { background-color: #2d6f9d; }
    .action-cell { display:flex; gap:6px; flex-wrap:wrap; }
    .empty-row td { text-align:center; padding:30px; color:#888; }
    .note-modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:2000;align-items:center;justify-content:center}
    .note-modal.show{display:flex}
    .note-box{background:#fff;border-radius:12px;padding:20px;width:360px;max-width:92vw}
    .note-box h3{margin-bottom:10px;color:#2c3e50}
    .note-box textarea{width:100%;border:1px solid #e1e5eb;border-radius:8px;padding:10px;font-family:inherit;min-height:90px;resize:vertical}
    .note-box .row{display:flex;gap:8px;margin-top:14px;justify-content:flex-end}
    .note-box .row button{padding:9px 16px;border:none;border-radius:8px;font-weight:bold;cursor:pointer}
</style>

<div class="page-header">
    <h2 class="page-title">Quản lý yêu cầu đổi trả</h2>
</div>

<div class="filter-tabs">
    <?php foreach ($tabs as $key => $label): $c = $key === 'all' ? $totalAll : ($counts[$key] ?? 0); ?>
    <a href="returns.php?filter=<?php echo $key; ?>" class="<?php echo $filter === $key ? 'active' : ''; ?>"><?php echo $label; ?> <span class="cnt">(<?php echo $c; ?>)</span></a>
    <?php endforeach; ?>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Khách hàng</th>
                <th>Đơn hàng</th>
                <th>Loại</th>
                <th>Lý do</th>
                <th>SL</th>
                <th>Ngày gửi</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($requests->num_rows === 0): ?>
                <tr class="empty-row"><td colspan="9">Không có yêu cầu nào.</td></tr>
            <?php else: while ($rq = $requests->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?php echo htmlspecialchars(media_url($rq['product_image'] ?? '', $base_url)); ?>" alt="">
                            <span><?php echo htmlspecialchars($rq['product_name']); ?><?php echo $rq['variant_name'] ? ' (' . htmlspecialchars($rq['variant_name']) . ')' : ''; ?></span>
                        </div>
                    </td>
                    <td class="cust-cell"><strong><?php echo htmlspecialchars($rq['fullname']); ?></strong><span><?php echo htmlspecialchars($rq['user_phone']); ?></span></td>
                    <td>#<?php echo (int)$rq['order_id']; ?></td>
                    <td><span class="type-pill"><?php echo return_type_label($rq['type']); ?></span></td>
                    <td class="reason-cell">
                        <?php echo htmlspecialchars($rq['reason']); ?>
                        <?php if (!empty($rq['description'])): ?><small><?php echo htmlspecialchars(mb_strimwidth($rq['description'], 0, 80, '...')); ?></small><?php endif; ?>
                        <?php if (!empty($rq['admin_note'])): ?><div class="admin-note"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($rq['admin_note']); ?></div><?php endif; ?>
                    </td>
                    <td><?php echo (int)$rq['quantity']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($rq['created_at'])); ?></td>
                    <td><span class="status-badge status-<?php echo $rq['status']; ?>"><?php echo return_status_label($rq['status']); ?></span></td>
                    <td>
                        <div class="action-cell">
                            <?php if ($rq['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-success" onclick="openNoteModal(<?php echo (int)$rq['id']; ?>,'approve',false)"><i class="fas fa-check"></i> Duyệt</button>
                                <button type="button" class="btn btn-danger" onclick="openNoteModal(<?php echo (int)$rq['id']; ?>,'reject',true)"><i class="fas fa-xmark"></i> Từ chối</button>
                            <?php elseif ($rq['status'] === 'approved'): ?>
                                <button type="button" class="btn btn-primary" onclick="openNoteModal(<?php echo (int)$rq['id']; ?>,'complete',false)"><i class="fas fa-flag-checkered"></i> Hoàn tất</button>
                            <?php else: ?>
                                <span style="color:#bbb;font-size:12px">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div class="note-modal js-note-modal">
    <div class="note-box">
        <h3 class="js-note-title">Xác nhận</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" class="js-note-action">
            <input type="hidden" name="return_id" class="js-note-return-id">
            <textarea name="admin_note" class="js-note-text" placeholder="Ghi chú cho khách hàng (không bắt buộc)..."></textarea>
            <div class="row">
                <button type="button" onclick="closeNoteModal()" style="background:#eee;color:#333">Huỷ</button>
                <button type="submit" class="js-note-submit" style="background:#2d6f9d;color:#fff">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNoteModal(id, action, noteRequired) {
    document.querySelector('.js-note-return-id').value = id;
    document.querySelector('.js-note-action').value = action;
    const textEl = document.querySelector('.js-note-text');
    textEl.value = '';
    textEl.placeholder = noteRequired ? 'Lý do từ chối (bắt buộc)...' : 'Ghi chú cho khách hàng (không bắt buộc)...';
    textEl.required = noteRequired;
    const titles = {approve: 'Duyệt yêu cầu đổi trả', reject: 'Từ chối yêu cầu', complete: 'Đánh dấu hoàn tất'};
    document.querySelector('.js-note-title').textContent = titles[action] || 'Xác nhận';
    const submitBtn = document.querySelector('.js-note-submit');
    submitBtn.style.background = action === 'reject' ? '#e74c3c' : (action === 'complete' ? '#16a34a' : '#2d6f9d');
    document.querySelector('.js-note-modal').classList.add('show');
}
function closeNoteModal() {
    document.querySelector('.js-note-modal').classList.remove('show');
}
<?php if ($msg_type !== ''): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: '<?php echo $msg_type === 'success' ? 'success' : 'error'; ?>',
        title: '<?php echo $msg_type === 'success' ? 'Thành công!' : 'Lỗi!'; ?>',
        text: '<?php echo addslashes($msg_text); ?>',
        confirmButtonColor: '<?php echo $msg_type === 'success' ? '#2d6f9d' : '#e74c3c'; ?>',
        timer: 2200
    });
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
