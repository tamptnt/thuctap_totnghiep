<?php
include 'header.php';

$msg_type = '';
$msg_text = '';
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

function redirect_coupons()
{
    echo "<script>window.location.href='coupons.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $description = trim((string)($_POST['description'] ?? ''));
        $discount_type = ($_POST['discount_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $discount_value = (float)str_replace(',', '', (string)($_POST['discount_value'] ?? '0'));
        $min_order_amount = (float)str_replace(',', '', (string)($_POST['min_order_amount'] ?? '0'));
        $max_raw = trim((string)($_POST['max_discount_amount'] ?? ''));
        $max_discount_amount = $max_raw === '' ? null : (float)str_replace(',', '', $max_raw);
        $limit_raw = trim((string)($_POST['usage_limit'] ?? ''));
        $usage_limit = $limit_raw === '' ? null : max(0, (int)$limit_raw);
        $starts_raw = trim((string)($_POST['starts_at'] ?? ''));
        $starts_at = $starts_raw === '' ? null : str_replace('T', ' ', $starts_raw) . ':00';
        $expires_raw = trim((string)($_POST['expires_at'] ?? ''));
        $expires_at = $expires_raw === '' ? null : str_replace('T', ' ', $expires_raw) . ':00';
        $status = isset($_POST['status']) ? 1 : 0;

        if ($code === '' || $discount_value <= 0) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Vui lòng nhập mã và giá trị giảm hợp lệ.'];
            redirect_coupons();
        }
        if ($discount_type === 'percent' && $discount_value > 100) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Phần trăm giảm giá không được vượt quá 100%.'];
            redirect_coupons();
        }

        if ($action === 'add') {
            $stmt = $conn->prepare('INSERT INTO coupons (code,description,discount_type,discount_value,min_order_amount,max_discount_amount,usage_limit,starts_at,expires_at,status) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('sssdddissi', $code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $usage_limit, $starts_at, $expires_at, $status);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã tạo mã giảm giá mới.'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => $conn->errno === 1062 ? 'Mã này đã tồn tại.' : 'Có lỗi xảy ra.'];
            }
            $stmt->close();
        } else {
            $coupon_id = (int)($_POST['coupon_id'] ?? 0);
            $stmt = $conn->prepare('UPDATE coupons SET code=?,description=?,discount_type=?,discount_value=?,min_order_amount=?,max_discount_amount=?,usage_limit=?,starts_at=?,expires_at=?,status=? WHERE id=?');
            $stmt->bind_param('sssdddissii', $code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $usage_limit, $starts_at, $expires_at, $status, $coupon_id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã cập nhật mã giảm giá.'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => $conn->errno === 1062 ? 'Mã này đã tồn tại.' : 'Có lỗi xảy ra.'];
            }
            $stmt->close();
        }
        redirect_coupons();
    }

    if ($action === 'delete') {
        $coupon_id = (int)($_POST['coupon_id'] ?? 0);
        $used = $conn->query('SELECT id FROM orders WHERE coupon_code=(SELECT code FROM coupons WHERE id=' . $coupon_id . ') LIMIT 1');
        if ($used && $used->num_rows > 0) {
            $conn->query('UPDATE coupons SET status=0 WHERE id=' . $coupon_id);
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Mã đã từng được sử dụng nên chỉ được tắt (không xoá hẳn).'];
        } else {
            $stmt = $conn->prepare('DELETE FROM coupons WHERE id=?');
            $stmt->bind_param('i', $coupon_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xoá mã giảm giá.'];
        }
        redirect_coupons();
    }
}

$coupons = $conn->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
?>
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap:wrap; gap:10px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #2d6f9d; padding-bottom: 5px; display: inline-block; }
    .btn { padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: #fff; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration:none; }
    .btn-primary { background-color: #2d6f9d; }
    .btn-warning { background-color: #f2b705; }
    .btn-danger { background-color: #e74c3c; }
    .btn-sm { padding: 6px 10px; font-size:14px; border-radius: 6px; }
    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; overflow-x:auto; }
    .data-table { width: 100%; border-collapse: collapse; min-width:900px; }
    .data-table th, .data-table td { padding: 14px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 13px; }
    .code-cell { font-weight:900; color:#2d6f9d; letter-spacing:.5px; }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size:13px; font-weight: bold; color: #fff; }
    .status-active { background-color: #2d6f9d; }
    .status-inactive { background-color: #aaa; }
    .status-expired { background-color: #e74c3c; }
    .action-cell { display: flex; gap: 8px; }
    .modal-box { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 15px; }
    .modal-box.show { display: flex; opacity: 1; }
    .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 560px; position:relative; max-height: 90vh; overflow-y:auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-title { font-size: 18px; color: #2d6f9d; font-weight: bold; text-transform: uppercase; }
    .close-modal { cursor: pointer; font-size: 20px; color: #aaa; }
    .form-group { margin-bottom: 15px; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 14px; }
    .form-control { width: 100%; padding: 11px 14px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; }
    .form-hint { display:block; margin-top:6px; color:#8a97a3; font-size:12px; }
    .checkbox-row { display:flex; align-items:center; gap:8px; }
    .empty-row td { text-align:center; padding:30px; color:#888; }
</style>

<div class="page-header">
    <h2 class="page-title">Mã giảm giá</h2>
    <button class="btn btn-primary" onclick="openModal('js-add-modal')"><i class="fas fa-plus"></i> Tạo mã mới</button>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Giá trị</th>
                <th>Điều kiện</th>
                <th>Lượt dùng</th>
                <th>Hiệu lực</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($coupons)): ?>
                <tr class="empty-row"><td colspan="7">Chưa có mã giảm giá nào.</td></tr>
            <?php else: foreach ($coupons as $c):
                $now = date('Y-m-d H:i:s');
                $isExpired = !empty($c['expires_at']) && $c['expires_at'] < $now;
                $isNotStarted = !empty($c['starts_at']) && $c['starts_at'] > $now;
                $isMaxedOut = $c['usage_limit'] !== null && (int)$c['used_count'] >= (int)$c['usage_limit'];
            ?>
                <tr>
                    <td class="code-cell"><?php echo htmlspecialchars($c['code']); ?><?php if($c['description']):?><div style="font-weight:normal;color:#888;font-size:12px;margin-top:3px;"><?php echo htmlspecialchars($c['description']); ?></div><?php endif;?></td>
                    <td>
                        <?php if ($c['discount_type'] === 'percent'): ?>
                            <?php echo rtrim(rtrim(number_format((float)$c['discount_value'], 1), '0'), '.'); ?>%
                            <?php if ($c['max_discount_amount'] !== null): ?><div style="font-size:12px;color:#888;">tối đa <?php echo number_format($c['max_discount_amount'], 0, ',', '.'); ?>đ</div><?php endif; ?>
                        <?php else: ?>
                            <?php echo number_format($c['discount_value'], 0, ',', '.'); ?>đ
                        <?php endif; ?>
                    </td>
                    <td>Đơn tối thiểu <?php echo number_format($c['min_order_amount'], 0, ',', '.'); ?>đ</td>
                    <td><?php echo (int)$c['used_count']; ?><?php echo $c['usage_limit'] !== null ? ' / ' . (int)$c['usage_limit'] : ' / ∞'; ?></td>
                    <td style="font-size:12px;color:#666;">
                        <?php echo $c['starts_at'] ? date('d/m/Y', strtotime($c['starts_at'])) : '—'; ?> → <?php echo $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : 'Không giới hạn'; ?>
                    </td>
                    <td>
                        <?php if (!$c['status']): ?><span class="status-badge status-inactive">Đã tắt</span>
                        <?php elseif ($isExpired): ?><span class="status-badge status-expired">Hết hạn</span>
                        <?php elseif ($isMaxedOut): ?><span class="status-badge status-expired">Hết lượt</span>
                        <?php elseif ($isNotStarted): ?><span class="status-badge status-inactive">Chưa mở</span>
                        <?php else: ?><span class="status-badge status-active">Đang chạy</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-cell">
                            <?php $edit_data = json_encode([
                                'id' => $c['id'],
                                'code' => htmlspecialchars($c['code']),
                                'description' => htmlspecialchars($c['description'] ?? ''),
                                'discount_type' => $c['discount_type'],
                                'discount_value' => rtrim(rtrim(number_format((float)$c['discount_value'], 2, '.', ','), '0'), '.'),
                                'min_order_amount' => number_format((float)$c['min_order_amount'], 0, '.', ','),
                                'max_discount_amount' => $c['max_discount_amount'] !== null ? number_format((float)$c['max_discount_amount'], 0, '.', ',') : '',
                                'usage_limit' => $c['usage_limit'] ?? '',
                                'starts_at' => $c['starts_at'] ? str_replace(' ', 'T', substr($c['starts_at'], 0, 16)) : '',
                                'expires_at' => $c['expires_at'] ? str_replace(' ', 'T', substr($c['expires_at'], 0, 16)) : '',
                                'status' => (int)$c['status'],
                            ]); ?>
                            <button class="btn btn-warning btn-sm" onclick='openEditModal(<?php echo $edit_data; ?>)'><i class="fas fa-pen-to-square"></i></button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDeleteCoupon(<?php echo $c['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-box js-add-modal">
    <div class="modal-content">
        <div class="modal-header"><h3 class="modal-title">Tạo mã giảm giá</h3><i class="fas fa-xmark close-modal" onclick="closeModal('js-add-modal')"></i></div>
        <form method="POST"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label class="form-label">Mã giảm giá</label><input type="text" name="code" class="form-control" style="text-transform:uppercase;" placeholder="VD: SALE50" required></div>
            <div class="form-group"><label class="form-label">Mô tả (tuỳ chọn)</label><input type="text" name="description" class="form-control"></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Loại giảm giá</label><select name="discount_type" class="form-control"><option value="percent">Phần trăm (%)</option><option value="fixed">Số tiền cố định (đ)</option></select></div>
                <div class="form-group"><label class="form-label">Giá trị giảm</label><input type="number" step="0.01" name="discount_value" class="form-control" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Đơn tối thiểu (đ)</label><input type="number" name="min_order_amount" class="form-control" value="0"></div>
                <div class="form-group"><label class="form-label">Giảm tối đa (đ, chỉ áp dụng %)</label><input type="number" name="max_discount_amount" class="form-control"><small class="form-hint">Để trống nếu không giới hạn.</small></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Giới hạn lượt dùng</label><input type="number" name="usage_limit" class="form-control" min="0"><small class="form-hint">Để trống nếu không giới hạn.</small></div>
                <div class="form-group"><label class="form-label">Trạng thái</label><div class="checkbox-row" style="margin-top:10px;"><input type="checkbox" name="status" id="add_status" checked><label for="add_status">Kích hoạt ngay</label></div></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Bắt đầu (tuỳ chọn)</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                <div class="form-group"><label class="form-label">Hết hạn (tuỳ chọn)</label><input type="datetime-local" name="expires_at" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Tạo mã</button>
        </form>
    </div>
</div>

<div class="modal-box js-edit-modal">
    <div class="modal-content">
        <div class="modal-header"><h3 class="modal-title">Sửa mã giảm giá</h3><i class="fas fa-xmark close-modal" onclick="closeModal('js-edit-modal')"></i></div>
        <form method="POST"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="coupon_id" class="js-edit-id">
            <div class="form-group"><label class="form-label">Mã giảm giá</label><input type="text" name="code" class="form-control js-edit-code" style="text-transform:uppercase;" required></div>
            <div class="form-group"><label class="form-label">Mô tả (tuỳ chọn)</label><input type="text" name="description" class="form-control js-edit-description"></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Loại giảm giá</label><select name="discount_type" class="form-control js-edit-type"><option value="percent">Phần trăm (%)</option><option value="fixed">Số tiền cố định (đ)</option></select></div>
                <div class="form-group"><label class="form-label">Giá trị giảm</label><input type="number" step="0.01" name="discount_value" class="form-control js-edit-value" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Đơn tối thiểu (đ)</label><input type="number" name="min_order_amount" class="form-control js-edit-min"></div>
                <div class="form-group"><label class="form-label">Giảm tối đa (đ, chỉ áp dụng %)</label><input type="number" name="max_discount_amount" class="form-control js-edit-max"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Giới hạn lượt dùng</label><input type="number" name="usage_limit" class="form-control js-edit-limit" min="0"></div>
                <div class="form-group"><label class="form-label">Trạng thái</label><div class="checkbox-row" style="margin-top:10px;"><input type="checkbox" name="status" class="js-edit-status" id="edit_status"><label for="edit_status">Đang kích hoạt</label></div></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Bắt đầu (tuỳ chọn)</label><input type="datetime-local" name="starts_at" class="form-control js-edit-starts"></div>
                <div class="form-group"><label class="form-label">Hết hạn (tuỳ chọn)</label><input type="datetime-local" name="expires_at" class="form-control js-edit-expires"></div>
            </div>
            <button type="submit" class="btn btn-warning" style="width:100%;">Cập nhật</button>
        </form>
    </div>
</div>

<form class="js-delete-coupon-form" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="coupon_id" class="js-delete-coupon-id">
</form>

<script>
    function openModal(cls) { document.querySelector('.' + cls).classList.add('show'); }
    function closeModal(cls) { document.querySelector('.' + cls).classList.remove('show'); }
    function openEditModal(data) {
        document.querySelector('.js-edit-id').value = data.id;
        document.querySelector('.js-edit-code').value = data.code;
        document.querySelector('.js-edit-description').value = data.description;
        document.querySelector('.js-edit-type').value = data.discount_type;
        document.querySelector('.js-edit-value').value = data.discount_value;
        document.querySelector('.js-edit-min').value = data.min_order_amount;
        document.querySelector('.js-edit-max').value = data.max_discount_amount;
        document.querySelector('.js-edit-limit').value = data.usage_limit;
        document.querySelector('.js-edit-starts').value = data.starts_at;
        document.querySelector('.js-edit-expires').value = data.expires_at;
        document.querySelector('.js-edit-status').checked = data.status == 1;
        openModal('js-edit-modal');
    }
    function confirmDeleteCoupon(id) {
        Swal.fire({
            title: 'Xoá mã giảm giá này?',
            text: 'Nếu mã đã từng được sử dụng, hệ thống sẽ chỉ tắt thay vì xoá hẳn.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Huỷ'
        }).then(function (result) {
            if (result.isConfirmed) {
                document.querySelector('.js-delete-coupon-id').value = id;
                document.querySelector('.js-delete-coupon-form').submit();
            }
        });
    }
    <?php if ($msg_type !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type === 'success' ? 'Thành công!' : 'Lỗi!'; ?>',
            text: '<?php echo addslashes($msg_text); ?>',
            confirmButtonColor: '<?php echo $msg_type === 'success' ? '#2d6f9d' : '#e74c3c'; ?>',
            timer: 2500
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>
