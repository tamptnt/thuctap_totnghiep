<?php
include 'header.php';

$product_id = (int)($_GET['product_id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM products WHERE id=?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không tìm thấy sản phẩm.'];
    echo "<script>window.location.href='products.php';</script>";
    exit();
}

$msg_type = '';
$msg_text = '';
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

function redirect_back($product_id)
{
    echo "<script>window.location.href='product_variants.php?product_id=$product_id';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $variant_name = trim((string)($_POST['variant_name'] ?? ''));
        $sku = trim((string)($_POST['sku'] ?? ''));
        $sku = $sku === '' ? null : $sku;
        $price_adjust = (float)str_replace(',', '', (string)($_POST['price_adjust'] ?? '0'));
        $sale_raw = trim((string)($_POST['sale_price_adjust'] ?? ''));
        $sale_price_adjust = $sale_raw === '' ? null : (float)str_replace(',', '', $sale_raw);
        $stock_quantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($variant_name === '') {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Tên biến thể không được để trống.'];
            redirect_back($product_id);
        }

        if ($is_default) {
            $reset = $conn->prepare('UPDATE product_variants SET is_default=0 WHERE product_id=?');
            $reset->bind_param('i', $product_id);
            $reset->execute();
            $reset->close();
        }

        if ($action === 'add') {
            $stmtIns = $conn->prepare('INSERT INTO product_variants (product_id,variant_name,sku,price_adjust,sale_price_adjust,stock_quantity,is_default,status) VALUES (?,?,?,?,?,?,?,?)');
            $stmtIns->bind_param('issddiii', $product_id, $variant_name, $sku, $price_adjust, $sale_price_adjust, $stock_quantity, $is_default, $status);
            if ($stmtIns->execute()) {
                sync_product_stock_from_variants($conn, $product_id);
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã thêm biến thể mới.'];
            } else {
                $duplicate = $conn->errno === 1062;
                $_SESSION['msg'] = ['type' => 'error', 'text' => $duplicate ? 'Mã SKU này đã tồn tại.' : 'Có lỗi xảy ra khi thêm biến thể.'];
            }
            $stmtIns->close();
        } else {
            $variant_id = (int)($_POST['variant_id'] ?? 0);
            $stmtUpd = $conn->prepare('UPDATE product_variants SET variant_name=?,sku=?,price_adjust=?,sale_price_adjust=?,stock_quantity=?,is_default=?,status=? WHERE id=? AND product_id=?');
            $stmtUpd->bind_param('ssddiiiii', $variant_name, $sku, $price_adjust, $sale_price_adjust, $stock_quantity, $is_default, $status, $variant_id, $product_id);
            if ($stmtUpd->execute()) {
                sync_product_stock_from_variants($conn, $product_id);
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã cập nhật biến thể.'];
            } else {
                $duplicate = $conn->errno === 1062;
                $_SESSION['msg'] = ['type' => 'error', 'text' => $duplicate ? 'Mã SKU này đã tồn tại.' : 'Có lỗi xảy ra khi cập nhật biến thể.'];
            }
            $stmtUpd->close();
        }
        redirect_back($product_id);
    }

    if ($action === 'delete') {
        $variant_id = (int)($_POST['variant_id'] ?? 0);
        $used = $conn->prepare('SELECT id FROM order_items WHERE variant_id=? LIMIT 1');
        $used->bind_param('i', $variant_id);
        $used->execute();
        $hasOrders = $used->get_result()->num_rows > 0;
        $used->close();

        if ($hasOrders) {
            $stmtDel = $conn->prepare('UPDATE product_variants SET status=0 WHERE id=? AND product_id=?');
            $stmtDel->bind_param('ii', $variant_id, $product_id);
            $stmtDel->execute();
            $stmtDel->close();
            sync_product_stock_from_variants($conn, $product_id);
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Biến thể đã phát sinh đơn hàng nên chỉ được ẩn (không xoá hẳn).'];
        } else {
            $stmtDel = $conn->prepare('DELETE FROM product_variants WHERE id=? AND product_id=?');
            $stmtDel->bind_param('ii', $variant_id, $product_id);
            $stmtDel->execute();
            $stmtDel->close();
            sync_product_stock_from_variants($conn, $product_id);
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xoá biến thể.'];
        }
        redirect_back($product_id);
    }
}

$variants = get_all_variants($conn, $product_id);
?>
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap:wrap; gap:10px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #2d6f9d; padding-bottom: 5px; display: inline-block; }
    .product-recap { display:flex; align-items:center; gap:12px; background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(0,0,0,.03); margin-bottom:18px; }
    .product-recap img { width:56px; height:56px; object-fit:cover; border-radius:8px; border:1px solid #eee; }
    .product-recap strong { display:block; color:#2c3e50; font-size:15px; }
    .product-recap span { display:block; color:#888; font-size:13px; margin-top:3px; }
    .btn { padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: #fff; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration:none; }
    .btn-primary { background-color: #2d6f9d; }
    .btn-secondary { background-color: #7b8a97; }
    .btn-warning { background-color: #26384d; }
    .btn-danger { background-color: #e74c3c; }
    .btn-sm { padding: 6px 10px; font-size:14px; border-radius: 6px; }
    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 14px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 13px; }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size:13px; font-weight: bold; color: #fff; }
    .status-active { background-color: #2d6f9d; }
    .status-inactive { background-color: #aaa; }
    .status-out { background-color: #e74c3c; }
    .default-badge { background:#f2b705; color:#fff; padding:3px 8px; border-radius:12px; font-size:12px; margin-left:6px; }
    .action-cell { display: flex; gap: 8px; }
    .modal-box { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 15px; }
    .modal-box.show { display: flex; opacity: 1; }
    .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 560px; position:relative; max-height: 90vh; overflow-y:auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-title { font-size: 18px; color: #2d6f9d; font-weight: bold; text-transform: uppercase; }
    .close-modal { cursor: pointer; font-size: 20px; color: #aaa; }
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 14px; }
    .form-control { width: 100%; padding: 11px 14px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; }
    .form-hint { display:block; margin-top:6px; color:#8a97a3; font-size:12px; }
    .checkbox-row { display:flex; align-items:center; gap:8px; }
    .empty-row td { text-align:center; padding:30px; color:#888; }
</style>

<div class="page-header">
    <h2 class="page-title">Biến thể sản phẩm</h2>
    <a href="products.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Quay lại danh sách sản phẩm</a>
</div>

<div class="product-recap">
    <img src="<?php echo htmlspecialchars(media_url($product['image'] ?? '', $base_url)); ?>" alt="">
    <div>
        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
        <span>Giá gốc: <?php echo number_format($product['price'], 0, ',', '.'); ?> đ<?php if ($product['sale_price'] > 0): ?> · Giá KM: <?php echo number_format($product['sale_price'], 0, ',', '.'); ?> đ<?php endif; ?> · Tồn kho gốc (không biến thể): <?php echo (int)$product['stock_quantity']; ?></span>
    </div>
</div>

<div class="page-header" style="margin-bottom:12px;">
    <p style="color:#666; font-size:14px; max-width:640px;">Nếu sản phẩm có nhiều lựa chọn (RAM/dung lượng/màu...), thêm từng biến thể bên dưới. Giá bán hiển thị cho khách = giá gốc sản phẩm + chênh lệch của biến thể. Tồn kho được quản lý riêng theo từng biến thể.</p>
    <button class="btn btn-primary" onclick="openModal('js-add-modal')"><i class="fas fa-plus"></i> Thêm biến thể</button>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Biến thể</th>
                <th>SKU</th>
                <th>Giá bán (đã tính chênh lệch)</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($variants)): ?>
                <tr class="empty-row"><td colspan="6">Sản phẩm này chưa có biến thể nào. Khách sẽ mua theo giá và tồn kho gốc của sản phẩm.</td></tr>
            <?php else: foreach ($variants as $v):
                $eff = variant_effective_price($product['price'], $product['sale_price'], $v);
            ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($v['variant_name']); ?>
                        <?php if ($v['is_default']): ?><span class="default-badge">Mặc định</span><?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($v['sku'] ?? '—'); ?></td>
                    <td>
                        <?php if ($eff['sale_price'] !== null && $eff['sale_price'] < $eff['price']): ?>
                            <strong><?php echo number_format($eff['sale_price'], 0, ',', '.'); ?> đ</strong>
                            <span style="text-decoration:line-through;color:#aaa;font-size:12px;margin-left:6px;"><?php echo number_format($eff['price'], 0, ',', '.'); ?> đ</span>
                        <?php else: ?>
                            <strong><?php echo number_format($eff['price'], 0, ',', '.'); ?> đ</strong>
                        <?php endif; ?>
                        <?php if ((float)$v['price_adjust'] != 0): ?>
                            <div style="font-size:12px;color:#888;">(<?php echo $v['price_adjust'] > 0 ? '+' : ''; ?><?php echo number_format($v['price_adjust'], 0, ',', '.'); ?> đ so với giá gốc)</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($v['stock_quantity'] > 0): ?>
                            <span style="font-weight:bold;color:#2d6f9d;"><?php echo (int)$v['stock_quantity']; ?></span>
                        <?php else: ?>
                            <span class="status-badge status-out">HẾT HÀNG</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $v['status'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $v['status'] ? 'Đang bán' : 'Đã ẩn'; ?></span>
                    </td>
                    <td>
                        <div class="action-cell">
                            <?php $edit_data = json_encode([
                                'id' => $v['id'],
                                'variant_name' => htmlspecialchars($v['variant_name']),
                                'sku' => htmlspecialchars($v['sku'] ?? ''),
                                'price_adjust' => rtrim(rtrim(number_format((float)$v['price_adjust'], 0, '.', ','), '0'), '.'),
                                'sale_price_adjust' => $v['sale_price_adjust'] !== null ? number_format((float)$v['sale_price_adjust'], 0, '.', ',') : '',
                                'stock_quantity' => $v['stock_quantity'],
                                'is_default' => (int)$v['is_default'],
                                'status' => (int)$v['status'],
                            ]); ?>
                            <button class="btn btn-warning btn-sm" onclick='openEditModal(<?php echo $edit_data; ?>)'><i class="fas fa-pen-to-square"></i></button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $v['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-box js-add-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Thêm biến thể</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-add-modal')"></i>
        </div>
        <form method="POST"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label class="form-label">Tên biến thể</label><input type="text" name="variant_name" class="form-control" placeholder="VD: 16GB / 512GB - Xám" required></div>
            <div class="form-group"><label class="form-label">Mã SKU (tuỳ chọn)</label><input type="text" name="sku" class="form-control"></div>
            <div class="form-group"><label class="form-label">Chênh lệch giá so với giá gốc (đ)</label><input type="text" name="price_adjust" class="form-control js-currency" value="0" oninput="formatSignedCurrency(this)"><small class="form-hint">Để 0 nếu giá bằng giá gốc sản phẩm. Có thể để số âm nếu biến thể rẻ hơn.</small></div>
            <div class="form-group"><label class="form-label">Chênh lệch giá khuyến mãi (đ, để trống nếu không áp dụng riêng)</label><input type="text" name="sale_price_adjust" class="form-control js-currency" oninput="formatSignedCurrency(this)"></div>
            <div class="form-group"><label class="form-label">Tồn kho biến thể</label><input type="number" name="stock_quantity" class="form-control" value="0" min="0" required></div>
            <div class="form-group checkbox-row"><input type="checkbox" name="is_default" id="add_default"><label for="add_default">Đặt làm biến thể mặc định</label></div>
            <div class="form-group checkbox-row"><input type="checkbox" name="status" id="add_status" checked><label for="add_status">Đang bán (hiển thị cho khách)</label></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Lưu biến thể</button>
        </form>
    </div>
</div>

<div class="modal-box js-edit-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Sửa biến thể</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-edit-modal')"></i>
        </div>
        <form method="POST"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="variant_id" class="js-edit-id">
            <div class="form-group"><label class="form-label">Tên biến thể</label><input type="text" name="variant_name" class="form-control js-edit-name" required></div>
            <div class="form-group"><label class="form-label">Mã SKU (tuỳ chọn)</label><input type="text" name="sku" class="form-control js-edit-sku"></div>
            <div class="form-group"><label class="form-label">Chênh lệch giá so với giá gốc (đ)</label><input type="text" name="price_adjust" class="form-control js-currency js-edit-price" oninput="formatSignedCurrency(this)"></div>
            <div class="form-group"><label class="form-label">Chênh lệch giá khuyến mãi (đ, để trống nếu không áp dụng riêng)</label><input type="text" name="sale_price_adjust" class="form-control js-currency js-edit-sale" oninput="formatSignedCurrency(this)"></div>
            <div class="form-group"><label class="form-label">Tồn kho biến thể</label><input type="number" name="stock_quantity" class="form-control js-edit-stock" min="0" required></div>
            <div class="form-group checkbox-row"><input type="checkbox" name="is_default" class="js-edit-default" id="edit_default"><label for="edit_default">Đặt làm biến thể mặc định</label></div>
            <div class="form-group checkbox-row"><input type="checkbox" name="status" class="js-edit-status" id="edit_status"><label for="edit_status">Đang bán (hiển thị cho khách)</label></div>
            <button type="submit" class="btn btn-warning" style="width:100%;">Cập nhật</button>
        </form>
    </div>
</div>

<form class="js-delete-form" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="variant_id" class="js-delete-id">
</form>

<script>
    function formatSignedCurrency(input) {
        let neg = input.value.trim().startsWith('-');
        let value = input.value.replace(/[^\d]/g, '');
        input.value = value === '' ? '' : (neg ? '-' : '') + parseInt(value, 10).toLocaleString('en-US');
    }
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('.js-currency').forEach(function (input) {
                input.value = input.value.replace(/,/g, '');
            });
        });
    });
    function openModal(cls) { document.querySelector('.' + cls).classList.add('show'); }
    function closeModal(cls) { document.querySelector('.' + cls).classList.remove('show'); }
    function openEditModal(data) {
        document.querySelector('.js-edit-id').value = data.id;
        document.querySelector('.js-edit-name').value = data.variant_name;
        document.querySelector('.js-edit-sku').value = data.sku;
        document.querySelector('.js-edit-price').value = data.price_adjust;
        document.querySelector('.js-edit-sale').value = data.sale_price_adjust;
        document.querySelector('.js-edit-stock').value = data.stock_quantity;
        document.querySelector('.js-edit-default').checked = data.is_default == 1;
        document.querySelector('.js-edit-status').checked = data.status == 1;
        openModal('js-edit-modal');
    }
    function confirmDelete(id) {
        Swal.fire({
            title: 'Xoá biến thể này?',
            text: 'Nếu biến thể đã phát sinh đơn hàng, hệ thống sẽ chỉ ẩn thay vì xoá hẳn.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Huỷ'
        }).then(function (result) {
            if (result.isConfirmed) {
                document.querySelector('.js-delete-id').value = id;
                document.querySelector('.js-delete-form').submit();
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
