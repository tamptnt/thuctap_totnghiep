<?php
/**
 * Các hàm dùng chung cho tính năng tra cứu bảo hành.
 *
 * Không có bảng riêng cho thời hạn bảo hành: số tháng được suy ra từ
 * chuỗi `products.warranty_text`, vốn có dạng tự do do admin nhập, ví dụ
 * "Core i5 · 16GB · SSD 512GB · BH 24 tháng". Ta tìm cụm "BH <số> tháng"
 * bằng regex; nếu không tìm thấy, dùng mặc định WARRANTY_DEFAULT_MONTHS.
 */

if (!defined('WARRANTY_DEFAULT_MONTHS')) {
    define('WARRANTY_DEFAULT_MONTHS', 12);
}

/**
 * Trích số tháng bảo hành từ chuỗi warranty_text, vd "BH 24 tháng" -> 24.
 * Trả về WARRANTY_DEFAULT_MONTHS nếu không tìm thấy.
 */
function get_warranty_months($warranty_text)
{
    if (preg_match('/BH\s*(\d+)\s*th[aá]ng/iu', (string)$warranty_text, $m)) {
        return max(1, (int)$m[1]);
    }
    return WARRANTY_DEFAULT_MONTHS;
}

/**
 * Toàn bộ sản phẩm khách đã mua (từ các đơn 'completed'), kèm hạn bảo hành
 * tính từ ngày đơn hoàn thành. Mới hết hạn/còn hạn gần nhất hiển thị trước.
 *
 * Mỗi phần tử: order_id, order_item_id, product_id, product_name, product_image,
 * variant_name, quantity, warranty_text, warranty_months, purchased_at,
 * warranty_end (Y-m-d H:i:s), is_active (bool), days_left (int, 0 nếu đã hết hạn).
 */
function get_user_warranty_items($conn, $user_id)
{
    $expr = return_completed_at_expr('o');
    $sql = "SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.variant_name, oi.quantity,
                   p.name AS product_name, p.image AS product_image, p.warranty_text,
                   $expr AS purchased_at
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            WHERE o.user_id = ? AND o.status = 'completed'
            ORDER BY purchased_at DESC, oi.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $now = time();
    $items = [];
    foreach ($rows as $row) {
        $months = get_warranty_months($row['warranty_text']);
        $endTs = strtotime('+' . $months . ' months', strtotime($row['purchased_at']));
        $row['warranty_months'] = $months;
        $row['warranty_end'] = date('Y-m-d H:i:s', $endTs);
        $row['is_active'] = $endTs >= $now;
        $row['days_left'] = $row['is_active'] ? (int)ceil(($endTs - $now) / 86400) : 0;
        $items[] = $row;
    }

    // Còn hạn (sắp hết hạn trước) lên đầu, hết hạn (mới hết hạn nhất) xuống cuối.
    usort($items, function ($a, $b) {
        if ($a['is_active'] !== $b['is_active']) {
            return $a['is_active'] ? -1 : 1;
        }
        return $a['is_active']
            ? $a['days_left'] <=> $b['days_left']
            : strtotime($b['warranty_end']) <=> strtotime($a['warranty_end']);
    });

    return $items;
}
