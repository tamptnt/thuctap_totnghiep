<?php
/**
 * Các hàm dùng chung cho quản lý biến thể sản phẩm (product_variants).
 * Một sản phẩm có thể có 0 biến thể (giữ nguyên hành vi cũ) hoặc nhiều
 * biến thể (RAM/dung lượng/màu...), mỗi biến thể có tồn kho và
 * giá lệch (price_adjust) riêng so với giá gốc của sản phẩm.
 */

/**
 * Lấy danh sách biến thể đang bật (status=1) của 1 sản phẩm, sắp theo mặc định trước.
 */
function get_active_variants($conn, $product_id)
{
    $stmt = $conn->prepare('SELECT * FROM product_variants WHERE product_id=? AND status=1 ORDER BY is_default DESC, id ASC');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Lấy toàn bộ biến thể (kể cả đang ẩn) của 1 sản phẩm — dùng cho trang quản trị.
 */
function get_all_variants($conn, $product_id)
{
    $stmt = $conn->prepare('SELECT * FROM product_variants WHERE product_id=? ORDER BY is_default DESC, id ASC');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Lấy 1 biến thể theo id, kèm thông tin sản phẩm cha (giá gốc, tồn kho legacy...).
 * Trả về null nếu không tồn tại hoặc không thuộc đúng product_id (nếu truyền vào).
 */
function get_variant_with_product($conn, $variant_id, $expected_product_id = null)
{
    $stmt = $conn->prepare('SELECT v.*, p.price AS product_price, p.sale_price AS product_sale_price, p.name AS product_name FROM product_variants v JOIN products p ON v.product_id=p.id WHERE v.id=? AND v.status=1');
    $stmt->bind_param('i', $variant_id);
    $stmt->execute();
    $variant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$variant) return null;
    if ($expected_product_id !== null && (int)$variant['product_id'] !== (int)$expected_product_id) return null;

    return $variant;
}

/**
 * Tính giá bán hiệu lực (giá gốc sản phẩm + chênh lệch biến thể) và giá khuyến mãi (nếu có).
 * Trả về ['price' => float, 'sale_price' => float|null].
 */
function variant_effective_price($product_price, $product_sale_price, $variant)
{
    $price = (float)$product_price + (float)($variant['price_adjust'] ?? 0);

    $sale_price = null;
    $hasProductSale = (float)$product_sale_price > 0;
    $hasVariantSaleAdjust = $variant['sale_price_adjust'] !== null;
    if ($hasProductSale || $hasVariantSaleAdjust) {
        $baseSale = $hasProductSale ? (float)$product_sale_price : (float)$product_price;
        $sale_price = $baseSale + (float)($variant['sale_price_adjust'] ?? $variant['price_adjust'] ?? 0);
    }

    return ['price' => max(0, $price), 'sale_price' => $sale_price !== null ? max(0, $sale_price) : null];
}

/**
 * Tổng tồn kho của sản phẩm tính theo biến thể (nếu có).
 * Dùng để hiển thị/đồng bộ cho các sản phẩm đã chuyển sang quản lý theo biến thể.
 */
function sum_variant_stock($conn, $product_id)
{
    $stmt = $conn->prepare('SELECT COALESCE(SUM(stock_quantity),0) AS total FROM product_variants WHERE product_id=? AND status=1');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

/**
 * Lấy danh sách ảnh phụ (gallery) của 1 sản phẩm, theo thứ tự sắp xếp.
 */
function get_product_images($conn, $product_id)
{
    $stmt = $conn->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC, id ASC');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Đồng bộ products.stock_quantity = tổng tồn kho các biến thể đang bán, để các trang
 * chưa "biết" về biến thể (danh sách sản phẩm, dữ liệu cho AI Chat, thống kê admin cũ...)
 * vẫn hiển thị đúng số lượng còn hàng mà không cần sửa lại từng nơi.
 * Nếu sản phẩm chưa có biến thể nào thì giữ nguyên stock_quantity hiện tại (admin quản lý trực tiếp).
 */
function sync_product_stock_from_variants($conn, $product_id)
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS has_variant, COALESCE(SUM(CASE WHEN status=1 THEN stock_quantity ELSE 0 END),0) AS total FROM product_variants WHERE product_id=?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$row['has_variant'] > 0) {
        $total = (int)$row['total'];
        $upd = $conn->prepare('UPDATE products SET stock_quantity=? WHERE id=?');
        $upd->bind_param('ii', $total, $product_id);
        $upd->execute();
        $upd->close();
    }
}
