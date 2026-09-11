<?php
include 'header.php';

$msg_type = '';
$msg_text = '';

if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_guard();
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $name = $_POST['name'];
            $category_id = (int)$_POST['category_id'];
            $brand_id = (int)$_POST['brand_id'];
            $price = str_replace(',', '', $_POST['price']);
            $sale_price = str_replace(',', '', $_POST['sale_price']) ?: null;
            $stock_quantity = (int)$_POST['stock_quantity'];
            $sales_count = (int)$_POST['sales_count'];
            $is_hot = (int)$_POST['is_hot'];
            $warranty_text = $_POST['warranty_text'];
            $description = $_POST['description'];
            
            $image = '';
            $image_url = trim((string)($_POST['image_url'] ?? ''));
            if ($image_url !== '') {
                if (!valid_remote_media_url($image_url)) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Link ảnh sản phẩm không hợp lệ. Hãy dùng URL bắt đầu bằng http:// hoặc https://'];
                    header('Location: products.php');
                    exit();
                }
                $downloaded_image = download_remote_media($image_url, $upload_dir, 'product');
                if ($downloaded_image === false) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không tải được ảnh từ link đã dán. Kiểm tra lại link (chỉ hỗ trợ JPG, PNG, GIF, WEBP, AVIF, tối đa 8MB).'];
                    header('Location: products.php');
                    exit();
                }
                $image = $downloaded_image;
            }
            $uploaded_image = save_uploaded_media($_FILES['image'] ?? null, $upload_dir, 'product');
            if ($uploaded_image === false) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Ảnh tải lên không hợp lệ. Chỉ hỗ trợ JPG, JPEG, PNG, GIF, WEBP hoặc AVIF, tối đa 8MB.'];
                header('Location: products.php');
                exit();
            }
            if ($uploaded_image !== null) $image = $uploaded_image;

            $stmt = $conn->prepare("INSERT INTO products (category_id, brand_id, name, image, price, sale_price, stock_quantity, sales_count, is_hot, warranty_text, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssdiiiss", $category_id, $brand_id, $name, $image, $price, $sale_price, $stock_quantity, $sales_count, $is_hot, $warranty_text, $description);
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Thêm sản phẩm thành công!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
            }
            $stmt->close();
            echo "<script>window.location.href='products.php';</script>";
            exit();
        }
        
        if ($action === 'edit') {
            $id = (int)$_POST['id'];
            $name = $_POST['name'];
            $category_id = (int)$_POST['category_id'];
            $brand_id = (int)$_POST['brand_id'];
            $price = str_replace(',', '', $_POST['price']);
            $sale_price = str_replace(',', '', $_POST['sale_price']) ?: null;
            $stock_quantity = (int)$_POST['stock_quantity'];
            $sales_count = (int)$_POST['sales_count'];
            $is_hot = (int)$_POST['is_hot'];
            $warranty_text = $_POST['warranty_text'];
            $description = $_POST['description'];
            
            $old_image = (string)($_POST['old_image'] ?? '');
            $image = $old_image;
            $image_url = trim((string)($_POST['image_url'] ?? ''));
            if ($image_url !== '') {
                if (!valid_remote_media_url($image_url)) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Link ảnh sản phẩm không hợp lệ. Hãy dùng URL bắt đầu bằng http:// hoặc https://'];
                    header('Location: products.php');
                    exit();
                }
                $downloaded_image = download_remote_media($image_url, $upload_dir, 'product');
                if ($downloaded_image === false) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Không tải được ảnh từ link đã dán. Kiểm tra lại link (chỉ hỗ trợ JPG, PNG, GIF, WEBP, AVIF, tối đa 8MB).'];
                    header('Location: products.php');
                    exit();
                }
                $image = $downloaded_image;
            }
            $uploaded_image = save_uploaded_media($_FILES['image'] ?? null, $upload_dir, 'product');
            if ($uploaded_image === false) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Ảnh tải lên không hợp lệ. Chỉ hỗ trợ JPG, JPEG, PNG, GIF, WEBP hoặc AVIF, tối đa 8MB.'];
                header('Location: products.php');
                exit();
            }
            if ($uploaded_image !== null) $image = $uploaded_image;

            $stmt = $conn->prepare("UPDATE products SET category_id=?, brand_id=?, name=?, image=?, price=?, sale_price=?, stock_quantity=?, sales_count=?, is_hot=?, warranty_text=?, description=? WHERE id=?");
            $stmt->bind_param("iisssdiiissi", $category_id, $brand_id, $name, $image, $price, $sale_price, $stock_quantity, $sales_count, $is_hot, $warranty_text, $description, $id);
            
            if ($stmt->execute()) {
                if ($image !== $old_image) delete_local_media($old_image, $upload_dir);
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Cập nhật sản phẩm thành công!'];
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
            }
            $stmt->close();
            echo "<script>window.location.href='products.php';</script>";
            exit();
        }
        
        if ($action === 'bulk_download_images') {
            $done = 0;
            $failed = 0;
            $rows = $conn->query("SELECT id, image FROM products WHERE image LIKE 'http://%' OR image LIKE 'https://%'");
            if ($rows) {
                while ($row = $rows->fetch_assoc()) {
                    $localName = download_remote_media($row['image'], $upload_dir, 'product');
                    if ($localName !== false) {
                        $upd = $conn->prepare("UPDATE products SET image=? WHERE id=?");
                        $upd->bind_param("si", $localName, $row['id']);
                        $upd->execute();
                        $upd->close();
                        $done++;
                    } else {
                        $failed++;
                    }
                }
            }
            $_SESSION['msg'] = [
                'type' => $failed === 0 ? 'success' : 'error',
                'text' => "Đã tải về máy chủ $done ảnh." . ($failed > 0 ? " Còn $failed ảnh tải lỗi (link hỏng hoặc không phải ảnh)." : '')
            ];
            echo "<script>window.location.href='products.php';</script>";
            exit();
        }

        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $check = $conn->query("SELECT id FROM order_items WHERE product_id = $id");
            if ($check->num_rows > 0) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Sản phẩm đã phát sinh đơn hàng, không thể xóa!'];
            } else {
                $image_row = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
                $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    if ($image_row) delete_local_media($image_row['image'] ?? '', $upload_dir);
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Đã xóa sản phẩm!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Có lỗi xảy ra!'];
                }
                $stmt->close();
            }
            echo "<script>window.location.href='products.php';</script>";
            exit();
        }
    }
}

$categories = [];
$cat_query = $conn->query("SELECT id, name FROM categories WHERE status = 1");
while($row = $cat_query->fetch_assoc()) $categories[] = $row;

$brands = [];
$brand_query = $conn->query("SELECT id, name FROM brands WHERE status = 1");
while($row = $brand_query->fetch_assoc()) $brands[] = $row;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($search !== '') {
    $where .= " AND p.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$total_sql = "SELECT COUNT(p.id) as total FROM products p $where";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT p.*, c.name as cat_name, b.name as brand_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        $where ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-title { color: #2c3e50; font-size: 22px; text-transform: uppercase; border-bottom: 3px solid #2d6f9d; padding-bottom: 5px; display: inline-block; }
    
    .action-bar { display: flex; flex-wrap: nowrap; gap: 10px; margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
    .search-form { display: flex; flex-grow: 1; position: relative; }
    .search-input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; }
    .search-input:focus { border-color: #2d6f9d; box-shadow: 0 0 0 3px rgba(45,111,157,.1); }
    .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
    .btn { padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: #fff; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
    .btn-primary { background-color: #2d6f9d; }
    .btn-primary:hover { background-color: #72aa25; }
    .btn-warning { background-color: #26384d; }
    .btn-warning:hover { background-color: #e67600; }
    .btn-danger { background-color: #e74c3c; }
    .btn-danger:hover { background-color: #c0392b; }
    .btn-sm { padding: 6px 10px; font-size:14px; border-radius: 6px; }

    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 14px; }
    .data-table tr:hover { background-color: #fcfcfc; }
    
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size:14px; font-weight: bold; color: #fff; }
    .status-active { background-color: #2d6f9d; }
    .status-inactive { background-color: #aaa; }
    .status-out { background-color: #e74c3c; }
    .price-text { color: #26384d; font-weight: bold; }
    
    .action-cell { display: flex; gap: 8px; }
    .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }

    .pagination { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 5px; margin-top: 20px; }
    .page-link { padding: 8px 14px; background: #fff; border: 1px solid #e1e5eb; border-radius: 6px; color: #333; transition: 0.3s; }
    .page-link:hover { border-color: #2d6f9d; color: #2d6f9d; }
    .page-link.active { background: #2d6f9d; border-color: #2d6f9d; color: #fff; }
    .page-dots { padding: 8px 6px; color: #999; }

    .modal-box { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 15px; }
    .modal-box.show { display: flex; opacity: 1; }
    .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 800px; transform: translateY(-30px); transition: 0.3s; position: relative; max-height: 90vh; overflow-y: auto; }
    .modal-box.show .modal-content { transform: translateY(0); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: sticky; top: -25px; background: #fff; padding-top: 25px; z-index: 10; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .modal-title { font-size: 18px; color: #2d6f9d; font-weight: bold; text-transform: uppercase; }
    .close-modal { cursor: pointer; font-size: 20px; color: #aaa; transition: 0.2s; }
    .close-modal:hover { color: #e74c3c; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-group { margin-bottom: 15px; }
    .form-group.full-width { grid-column: span 2; }
    .form-label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 14px; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e1e5eb; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; background: #fff; }
    textarea.form-control { resize: vertical; min-height: 100px; }
    .form-control:focus { border-color: #2d6f9d; box-shadow: 0 0 0 3px rgba(45,111,157,.1); }
    
    @media (max-width: 768px) {
        .btn-text { display: none; }
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin: 15px 0; border: 1px solid #e1e5eb; border-radius: 12px; padding: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .data-table td { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f9f9f9; padding: 10px 5px; text-align: right; }
        .data-table td:last-child { border-bottom: none; }
        .data-table td::before { content: attr(data-label); font-weight: bold; color: #2d6f9d; text-align: left; text-transform: uppercase; font-size:14px; }
        .table-wrapper { background: transparent; box-shadow: none; }
        .action-cell { justify-content: flex-end; }
        .form-grid { grid-template-columns: 1fr; }
        .form-group.full-width { grid-column: span 1; }
    }
</style>

<div class="page-header">
    <h2 class="page-title">Quản lý sản phẩm</h2>
</div>

<div class="action-bar">
    <form class="search-form" method="GET">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="search-input" placeholder="Tên sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
    </form>
    <button class="btn btn-warning" type="button" onclick="confirmBulkDownload()">
        <i class="fas fa-cloud-arrow-down"></i> <span class="btn-text">Tải ảnh online về server</span>
    </button>
    <button class="btn btn-primary" onclick="openModal('js-add-modal')">
        <i class="fas fa-plus"></i> <span class="btn-text">Thêm</span>
    </button>
</div>

<form class="js-bulk-download-form" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="bulk_download_images">
</form>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Giá Bán</th>
                <th>Kho</th>
                <th>Phân Loại</th>
                <th>Nổi Bật</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="ID"><?php echo $row['id']; ?></td>
                    <td data-label="Ảnh">
                        <?php if($row['image']): ?>
                            <img src="<?php echo htmlspecialchars(media_url($row['image'], $base_url)); ?>" class="product-img">
                        <?php else: ?>
                            <div class="product-img" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#aaa; font-size:20px;"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Tên sản phẩm" style="font-weight:bold;"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td data-label="Giá Bán" class="price-text">
                        <?php echo number_format($row['price'], 0, '.', ','); ?> đ
                    </td>
                    <td data-label="Kho">
                        <?php if($row['stock_quantity'] > 0): ?>
                            <span style="font-weight:bold; color:#2d6f9d;"><?php echo $row['stock_quantity']; ?></span>
                        <?php else: ?>
                            <span class="status-badge status-out">HẾT HÀNG</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Phân Loại">
                        <span style="display:block; font-size:14px; color:#666;"><?php echo htmlspecialchars($row['cat_name']); ?></span>
                        <span style="display:block; font-size:14px; color:#aaa;"><?php echo htmlspecialchars($row['brand_name']); ?></span>
                    </td>
                    <td data-label="Nổi Bật">
                        <span class="status-badge <?php echo $row['is_hot'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $row['is_hot'] == 1 ? 'HOT' : 'Thường'; ?>
                        </span>
                    </td>
                    <td data-label="Thao Tác">
                        <div class="action-cell">
                            <?php 
                                $edit_data = json_encode([
                                    'id' => $row['id'],
                                    'name' => htmlspecialchars($row['name']),
                                    'category_id' => $row['category_id'],
                                    'brand_id' => $row['brand_id'],
                                    'price' => number_format($row['price'], 0, '.', ','),
                                    'sale_price' => $row['sale_price'] ? number_format($row['sale_price'], 0, '.', ',') : '',
                                    'stock_quantity' => $row['stock_quantity'],
                                    'sales_count' => $row['sales_count'],
                                    'is_hot' => $row['is_hot'],
                                    'warranty_text' => htmlspecialchars($row['warranty_text']),
                                    'description' => htmlspecialchars($row['description']),
                                    'old_image' => $row['image']
                                ]);
                            ?>
                            <button class="btn btn-warning btn-sm" onclick='openEditModal(<?php echo $edit_data; ?>)'>
                                <i class="fas fa-pen-to-square"></i> <span class="btn-text">Sửa</span>
                            </button>
                            <a class="btn btn-primary btn-sm" href="product_variants.php?product_id=<?php echo $row['id']; ?>" title="Quản lý biến thể (RAM/dung lượng/màu...)">
                                <i class="fas fa-layer-group"></i> <span class="btn-text">Biến thể</span>
                            </a>
                            <a class="btn btn-primary btn-sm" href="product_images.php?product_id=<?php echo $row['id']; ?>" title="Quản lý thư viện ảnh phụ" style="background-color:#7a5ea8;">
                                <i class="fas fa-images"></i> <span class="btn-text">Ảnh phụ</span>
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash"></i> <span class="btn-text">Xóa</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">Không tìm thấy dữ liệu nào!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if($total_pages > 1):
    $qs = $search !== '' ? '&search=' . urlencode($search) : '';
    // Chỉ hiển thị: trang đầu, trang cuối, trang hiện tại +-2, còn lại rút gọn bằng "..."
    $pages_to_show = [];
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)) {
            $pages_to_show[] = $i;
        }
    }
?>
<div class="pagination">
    <?php if($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo $qs; ?>" class="page-link">‹</a>
    <?php endif; ?>

    <?php $prev = 0; foreach ($pages_to_show as $i): ?>
        <?php if ($prev && $i - $prev > 1): ?>
            <span class="page-dots">...</span>
        <?php endif; ?>
        <a href="?page=<?php echo $i; ?><?php echo $qs; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php $prev = $i; ?>
    <?php endforeach; ?>

    <?php if($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $qs; ?>" class="page-link">›</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="modal-box js-add-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Thêm sản phẩm mới</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-add-modal')"></i>
        </div>
        <form method="POST" enctype="multipart/form-data" class="js-product-form"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Tên sản phẩm</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Thương hiệu</label>
                    <select name="brand_id" class="form-control" required>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Giá Bán</label>
                    <input type="text" name="price" class="form-control js-currency" required oninput="formatCurrency(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Giá Khuyến Mãi (Nếu có)</label>
                    <input type="text" name="sale_price" class="form-control js-currency" oninput="formatCurrency(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng kho</label>
                    <input type="number" name="stock_quantity" class="form-control" value="10" min="0" required>
                    <small style="display:block;margin-top:6px;color:#8a97a3;">Nếu sau này bạn thêm biến thể cho sản phẩm (RAM/dung lượng/màu...), số này sẽ tự động được thay bằng tổng tồn kho các biến thể.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Lượt Bán</label>
                    <input type="number" name="sales_count" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Sản phẩm nổi bật</label>
                    <select name="is_hot" class="form-control">
                        <option value="0">Bình Thường</option>
                        <option value="1">HOT</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Ảnh sản phẩm</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <input type="url" name="image_url" class="form-control" placeholder="Hoặc dán link ảnh https://..." style="margin-top:7px">
                    <small style="display:block;margin-top:6px;color:#758392">Chọn upload ảnh hoặc dán link ảnh. Nếu dùng cả hai, ảnh upload sẽ được ưu tiên.</small>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Thông số / bảo hành</label>
                    <select name="warranty_text" class="form-control">
                        <option value="Core i5 · 16GB · SSD 512GB · BH 24 tháng">Core i5 · 16GB · SSD 512GB · BH 24 tháng</option>
                        <option value="Apple M2 · 8GB · SSD 256GB · BH 12 tháng">Apple M2 · 8GB · SSD 256GB · BH 12 tháng</option>
                        <option value="8GB · 256GB · 5G · BH 12 tháng">8GB · 256GB · 5G · BH 12 tháng</option>
                        <option value="27 inch · 4K IPS · USB-C · BH 36 tháng">27 inch · 4K IPS · USB-C · BH 36 tháng</option>
                        <option value="Bluetooth · USB-C · BH 12 tháng">Bluetooth · USB-C · BH 12 tháng</option>
                        <option value="Wi-Fi 6 · Gigabit · BH 24 tháng">Wi-Fi 6 · Gigabit · BH 24 tháng</option>
                        <option value="M.2 NVMe · 1TB · BH 36 tháng">M.2 NVMe · 1TB · BH 36 tháng</option>
                        <option value="Tùy chỉnh thông số trong mô tả">Tùy chỉnh thông số trong mô tả</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control" placeholder="Nhập mô tả chi tiết sản phẩm..."></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Lưu Dữ Liệu</button>
        </form>
    </div>
</div>

<div class="modal-box js-edit-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #26384d;">Cập nhật sản phẩm</h3>
            <i class="fas fa-xmark close-modal" onclick="closeModal('js-edit-modal')"></i>
        </div>
        <form method="POST" enctype="multipart/form-data" class="js-product-form"><?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" class="js-edit-id">
            <input type="hidden" name="old_image" class="js-edit-old-image">
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Tên sản phẩm</label>
                    <input type="text" name="name" class="form-control js-edit-name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-control js-edit-category" required>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Thương hiệu</label>
                    <select name="brand_id" class="form-control js-edit-brand" required>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Giá Bán</label>
                    <input type="text" name="price" class="form-control js-currency js-edit-price" required oninput="formatCurrency(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Giá Khuyến Mãi</label>
                    <input type="text" name="sale_price" class="form-control js-currency js-edit-sale-price" oninput="formatCurrency(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng kho</label>
                    <input type="number" name="stock_quantity" class="form-control js-edit-stock" min="0" required>
                    <small style="display:block;margin-top:6px;color:#8a97a3;">Nếu sản phẩm đã có biến thể, hãy sửa tồn kho trong trang "Biến thể" — số ở đây sẽ tự bị ghi đè theo tổng tồn kho biến thể.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Lượt Bán</label>
                    <input type="number" name="sales_count" class="form-control js-edit-sales-count" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Sản phẩm nổi bật</label>
                    <select name="is_hot" class="form-control js-edit-hot">
                        <option value="0">Bình Thường</option>
                        <option value="1">HOT</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Ảnh sản phẩm</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <input type="url" name="image_url" class="form-control js-edit-image-url" placeholder="Hoặc dán link ảnh https://..." style="margin-top:7px">
                    <small style="display:block;margin-top:6px;color:#758392">Bỏ trống cả hai để giữ ảnh hiện tại.</small>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Thông số / bảo hành</label>
                    <select name="warranty_text" class="form-control js-edit-warranty">
                        <option value="Core i5 · 16GB · SSD 512GB · BH 24 tháng">Core i5 · 16GB · SSD 512GB · BH 24 tháng</option>
                        <option value="Apple M2 · 8GB · SSD 256GB · BH 12 tháng">Apple M2 · 8GB · SSD 256GB · BH 12 tháng</option>
                        <option value="8GB · 256GB · 5G · BH 12 tháng">8GB · 256GB · 5G · BH 12 tháng</option>
                        <option value="27 inch · 4K IPS · USB-C · BH 36 tháng">27 inch · 4K IPS · USB-C · BH 36 tháng</option>
                        <option value="Bluetooth · USB-C · BH 12 tháng">Bluetooth · USB-C · BH 12 tháng</option>
                        <option value="Wi-Fi 6 · Gigabit · BH 24 tháng">Wi-Fi 6 · Gigabit · BH 24 tháng</option>
                        <option value="M.2 NVMe · 1TB · BH 36 tháng">M.2 NVMe · 1TB · BH 36 tháng</option>
                        <option value="Tùy chỉnh thông số trong mô tả">Tùy chỉnh thông số trong mô tả</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control js-edit-description"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 10px;">Cập Nhật</button>
        </form>
    </div>
</div>

<form class="js-delete-form" method="POST" style="display: none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" class="js-delete-id">
</form>

<script>
    function formatCurrency(input) {
        let value = input.value.replace(/\D/g, "");
        if (value !== "") {
            input.value = parseInt(value, 10).toLocaleString('en-US');
        } else {
            input.value = "";
        }
    }

    function openModal(className) {
        document.querySelector('.' + className).classList.add('show');
    }

    function closeModal(className) {
        document.querySelector('.' + className).classList.remove('show');
    }

    function openEditModal(data) {
        document.querySelector('.js-edit-id').value = data.id;
        document.querySelector('.js-edit-name').value = data.name;
        document.querySelector('.js-edit-category').value = data.category_id;
        document.querySelector('.js-edit-brand').value = data.brand_id;
        document.querySelector('.js-edit-price').value = data.price;
        document.querySelector('.js-edit-sale-price').value = data.sale_price;
        document.querySelector('.js-edit-stock').value = data.stock_quantity;
        document.querySelector('.js-edit-sales-count').value = data.sales_count;
        document.querySelector('.js-edit-hot').value = data.is_hot;
        document.querySelector('.js-edit-warranty').value = data.warranty_text;
        document.querySelector('.js-edit-description').value = data.description;
        document.querySelector('.js-edit-old-image').value = data.old_image;
        const imageUrlInput=document.querySelector('.js-edit-image-url'); if(imageUrlInput) imageUrlInput.value=/^https?:\/\//i.test(data.old_image||'')?data.old_image:'';
        openModal('js-edit-modal');
    }

    function confirmBulkDownload() {
        Swal.fire({
            title: 'Tải toàn bộ ảnh online về server?',
            text: "Hệ thống sẽ quét tất cả sản phẩm đang dùng link ảnh ngoài (http/https), tải về lưu trong thư mục uploads và cập nhật lại đường dẫn local.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2d6f9d',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Tải về ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector('.js-bulk-download-form').submit();
            }
        })
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector('.js-delete-id').value = id;
                document.querySelector('.js-delete-form').submit();
            }
        })
    }

    document.querySelectorAll('.js-product-form').forEach(form => {
        form.addEventListener('submit', function() {
            let currencyInputs = this.querySelectorAll('.js-currency');
            currencyInputs.forEach(input => {
                input.value = input.value.replace(/,/g, '');
            });
        });
    });

    <?php if($msg_type != ''): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type == 'success' ? 'Thành Công!' : 'Lỗi!'; ?>',
            text: '<?php echo $msg_text; ?>',
            confirmButtonColor: '<?php echo $msg_type == 'success' ? '#2d6f9d' : '#e74c3c'; ?>',
            timer: 2500
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>