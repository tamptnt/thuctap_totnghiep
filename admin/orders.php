<?php
require_once '../config.php';

if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_details') {
    header('Content-Type: application/json');
    $order_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT o.*, u.fullname, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        echo json_encode(['status' => 'error']);
        exit;
    }
    
    $items = [];
    $stmt_items = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();
    while ($row = $res_items->fetch_assoc()) {
        $row['image_url'] = media_url($row['image'] ?? '', $base_url);
        $items[] = $row;
    }
    $stmt_items->close();
    
    $history = get_order_status_history($conn, $order_id);

    echo json_encode([
        'status' => 'success',
        'order' => $order,
        'items' => $items,
        'history' => $history
    ]);
    exit;
}

require_once 'header.php';

$msg_type = '';
$msg_text = '';

if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_guard();
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $id = (int)$_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            log_order_status($conn, $id, $status, null, order_history_admin_actor());
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Cập nhật trạng thái đơn hàng thành công!'];
        } else {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra khi cập nhật!'];
        }
        $stmt->close();
        echo "<script>window.location.href='orders.php';</script>";
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_payment_status') {
        $id = (int)($_POST['order_id'] ?? 0);
        $paymentStatus = $_POST['payment_status'] ?? 'unpaid';
        $allowedPaymentStatuses = ['unpaid','pending','paid','failed'];
        if (in_array($paymentStatus, $allowedPaymentStatuses, true)) {
            if ($paymentStatus === 'paid') {
                $stmt = $conn->prepare("UPDATE orders SET payment_status='paid',payment_paid_at=COALESCE(payment_paid_at,NOW()),payment_transaction_id=COALESCE(NULLIF(payment_transaction_id,''),'MANUAL') WHERE id=?");
                $stmt->bind_param('i', $id);
            } else {
                $stmt = $conn->prepare('UPDATE orders SET payment_status=? WHERE id=?');
                $stmt->bind_param('si', $paymentStatus, $id);
            }
            if ($stmt->execute()) {
                $currentStatusRow = $conn->query('SELECT status FROM orders WHERE id=' . (int)$id)->fetch_assoc();
                $currentStatus = $currentStatusRow ? $currentStatusRow['status'] : 'pending';
                log_order_status($conn, $id, $currentStatus, 'Cập nhật trạng thái thanh toán: ' . $paymentStatus, order_history_admin_actor());
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Cập nhật trạng thái thanh toán thành công!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không thể cập nhật trạng thái thanh toán!'];
            }
            $stmt->close();
        }
        echo "<script>window.location.href='orders.php';</script>";
        exit();
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND (o.id = '$search_esc' OR u.fullname LIKE '%$search_esc%' OR o.phone LIKE '%$search_esc%')";
}
if ($status_filter !== '') {
    $status_esc = $conn->real_escape_string($status_filter);
    $where .= " AND o.status = '$status_esc'";
}

$total_sql = "SELECT COUNT(o.id) as total FROM orders o JOIN users u ON o.user_id = u.id $where";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT o.*, u.fullname FROM orders o JOIN users u ON o.user_id = u.id $where ORDER BY o.id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #b5657d; padding-bottom: 5px; display: inline-block; }
    
    .action-bar { background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; }
    .search-form { display: flex; flex-wrap: wrap; gap: 10px; width: 100%; align-items: center; }
    .search-wrap { flex: 1; min-width: 200px; position: relative; }
    .search-input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; }
    .search-input:focus { border-color: #b5657d; box-shadow: 0 0 0 3px rgba(181,101,125,.1); }
    .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
    
    .filter-group { display: flex; gap: 10px; flex: 1; min-width: 250px; }
    .filter-select { flex: 1; padding: 10px 15px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; background: #fff; }
    
    .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: #fff; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
    .btn-primary { background-color: #b5657d; }
    .btn-primary:hover { background-color: #72aa25; }
    .btn-info { background-color: #8a7563; }
    .btn-info:hover { background-color: #2980b9; }
    .btn-warning { background-color: #68404e; }
    .btn-warning:hover { background-color: #e67600; }
    .btn-secondary { background-color: #95a5a6; }
    .btn-secondary:hover { background-color: #7f8c8d; }
    
    .btn-action-icon { width: 36px; height: 36px; border: none; border-radius: 8px; color: #fff; display: inline-flex; justify-content: center; align-items: center; font-size: 15px; cursor: pointer; transition: 0.3s; padding: 0; }
    .btn-action-icon.view { background-color: #8a7563; }
    .btn-action-icon.view:hover { background-color: #2980b9; transform: translateY(-2px); }
    .btn-action-icon.status { background-color: #68404e; }
    .btn-action-icon.status:hover { background-color: #e67600; transform: translateY(-2px); }
    .btn-action-icon.print { background-color: #95a5a6; }
    .btn-action-icon.print:hover { background-color: #7f8c8d; transform: translateY(-2px); }

    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size:14px; }
    .data-table tr:hover { background-color: #fcfcfc; }
    
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size:14px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; }
    .status-pending { background-color: #fef9e7; color: #e67e22; border: 1px solid #fdebd0; }
    .status-processing { background-color: #e8f4f8; color: #8a7563; border: 1px solid #d4e6f1; }
    .status-completed { background-color: #e9f7ef; color: #27ae60; border: 1px solid #d5f5e3; }
    .status-canceled { background-color: #fdedec; color: #e74c3c; border: 1px solid #fadbd8; }
    
    .action-cell { display: flex; gap: 8px; flex-wrap: wrap; }
    .customer-info { display: flex; flex-direction: column; gap: 4px; }
    .customer-name { font-weight: bold; color: #2c3e50; font-size: 14px; }
    .customer-phone { font-size:14px; color: #7f8c8d; }

    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; }
    .page-link { padding: 8px 14px; background: #fff; border: 1px solid #e1e5eb; border-radius: 6px; color: #333; transition: 0.3s; }
    .page-link:hover, .page-link.active { background: #b5657d; border-color: #b5657d; color: #fff; }

    .modal-box { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 15px; }
    .modal-box.show { display: flex; opacity: 1; }
    .modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 800px; transform: translateY(-30px); transition: 0.3s; position: relative; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.2); }
    .modal-box.show .modal-content { transform: translateY(0); }
    .modal-header { padding: 20px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
    .modal-title { font-size: 16px; color: #2c3e50; font-weight: 900; text-transform: uppercase; margin: 0; }
    .close-modal { cursor: pointer; font-size: 20px; color: #aaa; transition: 0.2s; }
    .close-modal:hover { color: #e74c3c; }
    .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
    
    .detail-grid { display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px; }
    .detail-group { background: #fcfcfc; padding: 15px; border-radius: 12px; border: 1px solid #f1f1f1; }
    .detail-label { font-size:14px; color: #7f8c8d; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
    .detail-value { font-size: 14px; color: #2c3e50; font-weight: 600; line-height: 1.6; }
    
    .items-container { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
    .item-card { display: flex; gap: 15px; padding: 15px; border: 1px solid #eee; border-radius: 12px; background: #fff; align-items: center; }
    .item-card-img { width: 60px; height: 60px; object-fit: contain; border-radius: 8px; border: 1px solid #f1f1f1; padding: 2px; flex-shrink: 0; }
    .item-card-info { flex: 1; display: flex; flex-direction: column; gap: 5px; }
    .item-card-name { font-weight: bold; color: #333; font-size:14px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .item-card-meta { display: flex; justify-content: space-between; align-items: center; font-size:14px; }
    .item-card-price { color: #68404e; font-weight: bold; }
    .item-card-total { color: #e74c3c; font-weight: 900; font-size: 14px; }
    .history-item { position: relative; padding: 0 0 16px 22px; border-left: 2px solid #eef1f4; }
    .history-item:last-child { border-color: transparent; padding-bottom: 0; }
    .history-item:before { content: ''; position: absolute; left: -6px; top: 2px; width: 10px; height: 10px; border-radius: 50%; background: #2d6f9d; }
    .history-item .h-status { font-weight: bold; color: #2c3e50; font-size: 14px; }
    .history-item .h-note { color: #667681; font-size: 13px; margin-top: 2px; }
    .history-item .h-meta { color: #a3adb5; font-size: 12px; margin-top: 2px; }
    
    .modal-footer { padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }

    @media screen {
        #printArea { display: none; }
    }

    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; display: block; padding: 20mm; font-family: "Times New Roman", Times, serif; color: #000; }
        
        .inv-header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .inv-logo { font-size: 32px; font-weight: 900; color: #000; }
        .inv-title { font-size: 28px; font-weight: bold; text-transform: uppercase; text-align: right; }
        .inv-date { font-size: 14px; color: #555; text-align: right; margin-top: 5px; }
        
        .inv-info { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .inv-box { width: 45%; }
        .inv-box-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #555; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; }
        .inv-text { font-size: 14px; line-height: 1.6; }
        
        .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .inv-table th { background: #f0f0f0 !important; color: #000; padding: 12px; text-align: left; border: 1px solid #ccc; -webkit-print-color-adjust: exact; }
        .inv-table td { padding: 12px; border: 1px solid #ccc; vertical-align: middle; }
        
        .inv-item-flex { display: flex; align-items: center; gap: 15px; }
        .inv-item-img { width: 50px; height: 50px; object-fit: contain; }
        
        .inv-summary { width: 300px; margin-left: auto; border-top: 2px solid #000; padding-top: 15px; }
        .inv-sum-row { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 10px; }
        .inv-sum-row.total { font-size: 20px; font-weight: bold; }
        
        .inv-footer { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 14px; color: #555; font-style: italic; }
    }

    @media (min-width: 768px) {
        .detail-grid { grid-template-columns: 1fr 1fr; }
        .modal-title { font-size: 18px; }
        .items-container { display: grid; grid-template-columns: 1fr; }
        .item-card { flex-direction: row; }
    }

    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin: 15px 0; border: 1px solid #e1e5eb; border-radius: 12px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .data-table td { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f9f9f9; padding: 10px 0; text-align: right; }
        .data-table td:last-child { border-bottom: none; flex-direction: row; justify-content: flex-end; gap: 10px; }
        .data-table td::before { content: attr(data-label); font-weight: bold; color: #b5657d; text-align: left; text-transform: uppercase; font-size:14px; }
        .table-wrapper { background: transparent; box-shadow: none; }
        .action-cell { justify-content: flex-end; width: auto; }
        .customer-info { text-align: right; }
    }
</style>

<div class="page-header">
    <h2 class="page-title">Quản Lý Đơn Hàng</h2>
</div>

<div class="action-bar">
    <form class="search-form" method="GET">
        <div class="search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="search" class="search-input" placeholder="Mã ĐH, Tên, SĐT..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group">
            <select name="status" class="filter-select">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Đang giao hàng</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                <option value="canceled" <?php echo $status_filter == 'canceled' ? 'selected' : ''; ?>>Đã hủy</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
        </div>
    </form>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Khách Hàng</th>
                <th>Tổng Tiền</th>
                <th>Thanh Toán</th>
                <th>Ngày Đặt</th>
                <th>Trạng Thái</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="Mã ĐH" style="font-weight:bold; color:#68404e;"><?php echo $row['id']; ?></td>
                    <td data-label="Khách Hàng">
                        <div class="customer-info">
                            <span class="customer-name"><?php echo htmlspecialchars($row['fullname']); ?></span>
                            <span class="customer-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?></span>
                        </div>
                    </td>
                    <td data-label="Tổng Tiền" style="font-weight:bold; color:#e74c3c;">
                        <?php echo number_format($row['total_money'], 0, '.', '.'); ?> đ
                    </td>
                    <td data-label="Thanh Toán">
                        <?php
                            $payMethods = ['cod'=>'COD','vnpay'=>'VNPay'];
                            $payStatuses = ['unpaid'=>'Chưa thanh toán','pending'=>'Chờ thanh toán','paid'=>'Đã thanh toán','failed'=>'Chưa thành công'];
                            $payColor = $row['payment_status']==='paid' ? '#18785c' : ($row['payment_status']==='failed' ? '#b83d46' : '#8a6816');
                        ?>
                        <div style="font-size:14px;font-weight:bold;color:#2c3e50;"><?php echo htmlspecialchars($payMethods[$row['payment_method']] ?? $row['payment_method']); ?></div>
                        <div style="font-size:14px;margin-top:4px;color:<?php echo $payColor; ?>;"><?php echo htmlspecialchars($payStatuses[$row['payment_status']] ?? $row['payment_status']); ?></div>
                    </td>
                    <td data-label="Ngày Đặt"><?php echo date('H:i d/m/Y', strtotime($row['created_at'])); ?></td>
                    <td data-label="Trạng Thái">
                        <?php 
                            $st_class = ''; $st_text = ''; $st_icon = '';
                            switch($row['status']) {
                                case 'pending': $st_class = 'status-pending'; $st_text = 'Chờ xử lý'; $st_icon = 'fa-clock'; break;
                                case 'processing': $st_class = 'status-processing'; $st_text = 'Đang giao'; $st_icon = 'fa-truck'; break;
                                case 'completed': $st_class = 'status-completed'; $st_text = 'Hoàn thành'; $st_icon = 'fa-circle-check'; break;
                                case 'canceled': $st_class = 'status-canceled'; $st_text = 'Đã hủy'; $st_icon = 'fa-xmark-circle'; break;
                            }
                        ?>
                        <span class="status-badge <?php echo $st_class; ?>">
                            <i class="fas <?php echo $st_icon; ?>"></i> <?php echo $st_text; ?>
                        </span>
                    </td>
                    <td data-label="Thao Tác">
                        <div class="action-cell">
                            <button class="btn-action-icon view" onclick="viewOrder(<?php echo $row['id']; ?>)" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action-icon status" onclick="openStatusModal(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')" title="Cập nhật trạng thái">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            <button class="btn-action-icon view" onclick="openPaymentModal(<?php echo $row['id']; ?>, '<?php echo $row['payment_status']; ?>')" title="Cập nhật thanh toán">
                                <i class="fas fa-wallet"></i>
                            </button>
                            <button class="btn-action-icon print" onclick="printOrder(<?php echo $row['id']; ?>)" title="In hóa đơn">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 10px; color: #ddd;"></i><br>
                        Không tìm thấy đơn hàng nào!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if($total_pages > 1): ?>
<div class="pagination">
    <?php 
        $query_string = $_GET;
        unset($query_string['page']);
        $qs = http_build_query($query_string);
        $qs = $qs ? '&' . $qs : '';
        for($i = 1; $i <= $total_pages; $i++): 
    ?>
        <a href="?page=<?php echo $i . $qs; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<div class="modal-box js-view-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Chi Tiết Đơn Hàng <span id="v_order_id" style="color: #68404e;"></span></h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-view-modal')"></i>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-group">
                    <div class="detail-label">Thông tin khách hàng</div>
                    <div class="detail-value">
                        <i class="fas fa-user" style="width: 20px; color: #aaa;"></i> <span id="v_fullname"></span><br>
                        <i class="fas fa-phone" style="width: 20px; color: #aaa; margin-top: 8px;"></i> <span id="v_phone"></span><br>
                        <i class="fas fa-envelope" style="width: 20px; color: #aaa; margin-top: 8px;"></i> <span id="v_email"></span>
                    </div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Thông tin giao hàng</div>
                    <div class="detail-value">
                        <i class="fas fa-location-dot" style="width: 20px; color: #aaa;"></i> <span id="v_address"></span><br>
                        <i class="far fa-clock" style="width: 20px; color: #aaa; margin-top: 8px;"></i> <span id="v_date"></span><br>
                        <i class="fas fa-circle-info" style="width: 20px; color: #aaa; margin-top: 8px;"></i> Trạng thái: <span id="v_status" style="font-weight: 900; color: #b5657d;"></span><br>
                        <i class="fas fa-wallet" style="width: 20px; color: #aaa; margin-top: 8px;"></i> Thanh toán: <span id="v_payment" style="font-weight: 900; color: #27699a;"></span>
                    </div>
                </div>
            </div>
            
            <div class="detail-label" style="border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 15px;">Danh sách sản phẩm</div>
            <div class="items-container" id="v_items_container"></div>
            
            <div id="v_discount_row" style="display:none;text-align: right; font-size: 15px; color: #16a34a; margin-top: 10px;">
                Giảm giá: <span id="v_discount" style="font-weight: 700;"></span>
            </div>
            <div style="text-align: right; font-size: 16px; color: #2c3e50; margin-top: 10px; border-top: 2px dashed #eee; padding-top: 15px;">
                Tổng thanh toán: <span id="v_total" style="font-weight: 900; color: #e74c3c; font-size: 22px;"></span>
            </div>

            <div class="detail-label" style="border-bottom: 2px solid #eee; padding-bottom: 8px; margin: 20px 0 15px;">Lịch sử trạng thái</div>
            <div id="v_history"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('js-view-modal')">Đóng</button>
        </div>
    </div>
</div>

<div class="modal-box js-status-modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Cập Nhật Trạng Thái</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-status-modal')"></i>
        </div>
        <form method="POST"><?php echo csrf_field(); ?>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="s_order_id">
                
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="margin-bottom: 10px;">Chọn trạng thái mới</label>
                    <select name="status" id="s_status_select" class="filter-select" style="width: 100%; height: 45px;">
                        <option value="pending">Chờ xử lý</option>
                        <option value="processing">Đang giao hàng</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="canceled">Đã hủy</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('js-status-modal')">Hủy</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-floppy-disk"></i> Lưu</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-box js-payment-modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Cập Nhật Thanh Toán</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-payment-modal')"></i>
        </div>
        <form method="POST"><?php echo csrf_field(); ?>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_payment_status">
                <input type="hidden" name="order_id" id="pay_order_id">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="margin-bottom: 10px;">Trạng thái thanh toán</label>
                    <select name="payment_status" id="pay_status_select" class="filter-select" style="width:100%;height:45px;">
                        <option value="unpaid">Chưa thanh toán</option>
                        <option value="pending">Chờ thanh toán</option>
                                                <option value="paid">Đã thanh toán</option>
                        <option value="failed">Thanh toán chưa thành công</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('js-payment-modal')">Hủy</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-floppy-disk"></i> Lưu</button>
            </div>
        </form>
    </div>
</div>

<div id="printArea">
    <div class="inv-header">
        <div class="inv-logo">NOVATECH</div>
        <div>
            <div class="inv-title">Hóa đơn bán hàng</div>
            <div class="inv-date">Mã đơn: <strong id="p_order_id"></strong></div>
            <div class="inv-date">Ngày lập: <span id="p_date"></span></div>
        </div>
    </div>
    
    <div class="inv-info">
        <div class="inv-box">
            <div class="inv-box-title">Đơn vị bán hàng</div>
            <div class="inv-text">
                <strong>NovaTech</strong><br>
                Cửa hàng công nghệ NovaTech<br>
                Website: www.novatech.vn
            </div>
        </div>
        <div class="inv-box">
            <div class="inv-box-title">Thông tin khách hàng</div>
            <div class="inv-text">
                Họ tên: <strong id="p_fullname"></strong><br>
                Điện thoại: <span id="p_phone"></span><br>
                Địa chỉ: <span id="p_address"></span>
            </div>
        </div>
    </div>
    
    <table class="inv-table" id="p_items_table">
        <thead>
            <tr>
                <th style="width: 50%;">Sản phẩm</th>
                <th style="text-align: center; width: 15%;">Số lượng</th>
                <th style="text-align: right; width: 15%;">Đơn giá</th>
                <th style="text-align: right; width: 20%;">Thành tiền</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    
    <div class="inv-summary">
        <div class="inv-sum-row">
            <span>Tạm tính:</span>
            <span id="p_subtotal"></span>
        </div>
        <div class="inv-sum-row">
            <span>Phí vận chuyển:</span>
            <span>0 đ</span>
        </div>
        <div class="inv-sum-row" id="p_discount_row" style="display:none;">
            <span>Giảm giá:</span>
            <span id="p_discount"></span>
        </div>
        <div class="inv-sum-row total">
            <span>Tổng cộng:</span>
            <span id="p_total"></span>
        </div>
    </div>
    
    <div class="inv-footer">
        Cảm ơn quý khách đã tin tưởng và mua sắm tại NovaTech!<br>
        Mọi thắc mắc xin vui lòng liên hệ hotline hỗ trợ.
    </div>
</div>

<script>
    function openModal(className) {
        document.querySelector('.' + className).classList.add('show');
    }

    function closeModal(className) {
        document.querySelector('.' + className).classList.remove('show');
    }

    const adminOrderMediaBase = <?php echo json_encode($base_url . 'uploads/', JSON_UNESCAPED_SLASHES); ?>;

    function resolveAdminOrderMedia(value) {
        value = String(value || '').trim();
        if (!value) return 'https://placehold.co/100x100?text=No+Img';
        if (/^https?:\/\//i.test(value)) return value;
        value = value.replace(/\\/g, '/').replace(/^(?:\.\.\/|\.\/)+/, '').replace(/^\/+/, '').replace(/^uploads\//i, '');
        return adminOrderMediaBase + value.split('/').filter(Boolean).map(encodeURIComponent).join('/');
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    }

    function translateStatus(status) {
        switch(status) {
            case 'pending': return 'Chờ xử lý';
            case 'processing': return 'Đang giao hàng';
            case 'completed': return 'Hoàn thành';
            case 'canceled': return 'Đã hủy';
            default: return status;
        }
    }

    function formatDate(dateStr) {
        let d = new Date(dateStr);
        return d.getHours().toString().padStart(2, '0') + ':' + 
               d.getMinutes().toString().padStart(2, '0') + ' ' + 
               d.getDate().toString().padStart(2, '0') + '/' + 
               (d.getMonth() + 1).toString().padStart(2, '0') + '/' + 
               d.getFullYear();
    }

    async function fetchOrderData(id) {
        Swal.fire({ title: 'Đang tải dữ liệu...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
        try {
            let res = await fetch(`orders.php?ajax_action=get_details&id=${id}`);
            let data = await res.json();
            Swal.close();
            if (data.status === 'success') {
                return data;
            } else {
                Swal.fire('Lỗi!', 'Không thể lấy thông tin đơn hàng', 'error');
                return null;
            }
        } catch (e) {
            Swal.close();
            Swal.fire('Lỗi!', 'Mất kết nối máy chủ', 'error');
            return null;
        }
    }

    async function viewOrder(id) {
        let data = await fetchOrderData(id);
        if (!data) return;
        
        const o = data.order;
        document.getElementById('v_order_id').innerText = o.id;
        document.getElementById('v_fullname').innerText = o.fullname;
        document.getElementById('v_phone').innerText = o.phone;
        document.getElementById('v_email').innerText = o.email || 'Không cung cấp';
        document.getElementById('v_address').innerText = o.shipping_address;
        document.getElementById('v_date').innerText = formatDate(o.created_at);
        document.getElementById('v_status').innerText = translateStatus(o.status);
        const paymentMethods = {cod:'COD',vnpay:'VNPay'};
        const paymentStatuses = {unpaid:'Chưa thanh toán',pending:'Chờ thanh toán',paid:'Đã thanh toán',failed:'Chưa thành công'};
        document.getElementById('v_payment').innerText = (paymentMethods[o.payment_method] || o.payment_method) + ' - ' + (paymentStatuses[o.payment_status] || o.payment_status);
        document.getElementById('v_total').innerText = formatMoney(o.total_money);
        const discountRow = document.getElementById('v_discount_row');
        if (o.discount_amount && parseFloat(o.discount_amount) > 0) {
            discountRow.style.display = '';
            document.getElementById('v_discount').innerText = '-' + formatMoney(o.discount_amount) + (o.coupon_code ? ' (' + o.coupon_code + ')' : '');
        } else {
            discountRow.style.display = 'none';
        }
        
        const container = document.getElementById('v_items_container');
        container.innerHTML = '';
        
        data.items.forEach(item => {
            let img = item.image_url || resolveAdminOrderMedia(item.image);
            let total = formatMoney(item.price * item.quantity);
            
            container.innerHTML += `
                <div class="item-card">
                    <img src="${img}" class="item-card-img">
                    <div class="item-card-info">
                        <div class="item-card-name">${item.name}${item.variant_name ? ' <span style="color:#2d6f9d;font-weight:normal;">(' + item.variant_name + ')</span>' : ''}</div>
                        <div class="item-card-meta">
                            <span class="item-card-price">${formatMoney(item.price)} <span style="color:#888; font-weight:normal; font-size:14px;">x${item.quantity}</span></span>
                            <span class="item-card-total">${total}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        const statusLabels = {pending:'Chờ xác nhận',processing:'Đang xử lý',completed:'Hoàn thành',canceled:'Đã hủy'};
        const historyBox = document.getElementById('v_history');
        historyBox.innerHTML = (data.history || []).map(function (h) {
            const label = statusLabels[h.status] || h.status;
            const time = h.created_at ? formatDate(h.created_at) : '';
            return '<div class="history-item"><div class="h-status">' + label + '</div>' +
                (h.note ? '<div class="h-note">' + h.note + '</div>' : '') +
                '<div class="h-meta">' + (h.changed_by || '') + ' · ' + time + '</div></div>';
        }).join('') || '<p style="color:#999;font-size:13px;">Chưa có lịch sử.</p>';

        openModal('js-view-modal');
    }

    function openStatusModal(id, current_status) {
        document.getElementById('s_order_id').value = id;
        document.getElementById('s_status_select').value = current_status;
        openModal('js-status-modal');
    }

    function openPaymentModal(id, currentStatus) {
        document.getElementById('pay_order_id').value = id;
        document.getElementById('pay_status_select').value = currentStatus;
        openModal('js-payment-modal');
    }

    async function printOrder(id) {
        let data = await fetchOrderData(id);
        if (!data) return;
        
        const o = data.order;
        document.getElementById('p_order_id').innerText =o.id;
        document.getElementById('p_date').innerText = formatDate(o.created_at);
        document.getElementById('p_fullname').innerText = o.fullname;
        document.getElementById('p_phone').innerText = o.phone;
        document.getElementById('p_address').innerText = o.shipping_address;
        
        const tbody = document.querySelector('#p_items_table tbody');
        tbody.innerHTML = '';
        data.items.forEach(item => {
            let img = item.image_url || resolveAdminOrderMedia(item.image);
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="inv-item-flex">
                            <img src="${img}" class="inv-item-img">
                            <strong>${item.name}${item.variant_name ? ' (' + item.variant_name + ')' : ''}</strong>
                        </div>
                    </td>
                    <td style="text-align: center;">${item.quantity}</td>
                    <td style="text-align: right;">${formatMoney(item.price)}</td>
                    <td style="text-align: right; font-weight: bold;">${formatMoney(item.price * item.quantity)}</td>
                </tr>
            `;
        });
        
        const itemsSubtotal = data.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        document.getElementById('p_subtotal').innerText = formatMoney(itemsSubtotal);
        const discountRowP = document.getElementById('p_discount_row');
        if (o.discount_amount && parseFloat(o.discount_amount) > 0) {
            discountRowP.style.display = '';
            document.getElementById('p_discount').innerText = '-' + formatMoney(o.discount_amount) + (o.coupon_code ? ' (' + o.coupon_code + ')' : '');
        } else {
            discountRowP.style.display = 'none';
        }
        document.getElementById('p_total').innerText = formatMoney(o.total_money);
        
        setTimeout(() => {
            window.print();
        }, 500);
    }

    <?php if($msg_type != ''): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type == 'success' ? 'Thành Công!' : 'Lỗi!'; ?>',
            text: '<?php echo $msg_text; ?>',
            confirmButtonColor: '<?php echo $msg_type == 'success' ? '#b5657d' : '#e74c3c'; ?>',
            timer: 2500
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>