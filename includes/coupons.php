<?php
/**
 * Các hàm dùng chung cho tính năng mã giảm giá (coupon).
 * Luôn tính toán lại số tiền giảm ở phía server (không tin dữ liệu từ client)
 * để tránh khách hàng tự sửa số tiền giảm giá khi đặt hàng.
 */

/**
 * Kiểm tra và tính số tiền giảm giá cho 1 mã, dựa trên tổng tiền hàng (subtotal) hiện tại.
 * Trả về mảng:
 *   ['valid' => bool, 'message' => string, 'coupon' => array|null, 'discount' => float]
 */
function validate_coupon($conn, $code, $subtotal)
{
    $code = strtoupper(trim((string)$code));
    if ($code === '') {
        return ['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá.', 'coupon' => null, 'discount' => 0];
    }

    $stmt = $conn->prepare('SELECT * FROM coupons WHERE code=?');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại.', 'coupon' => null, 'discount' => 0];
    }
    if ((int)$coupon['status'] !== 1) {
        return ['valid' => false, 'message' => 'Mã giảm giá này hiện không khả dụng.', 'coupon' => null, 'discount' => 0];
    }

    $now = new DateTime();
    if (!empty($coupon['starts_at']) && $now < new DateTime($coupon['starts_at'])) {
        return ['valid' => false, 'message' => 'Mã giảm giá chưa đến thời gian áp dụng.', 'coupon' => null, 'discount' => 0];
    }
    if (!empty($coupon['expires_at']) && $now > new DateTime($coupon['expires_at'])) {
        return ['valid' => false, 'message' => 'Mã giảm giá đã hết hạn.', 'coupon' => null, 'discount' => 0];
    }
    if ($coupon['usage_limit'] !== null && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.', 'coupon' => null, 'discount' => 0];
    }
    if ((float)$subtotal < (float)$coupon['min_order_amount']) {
        return ['valid' => false, 'message' => 'Đơn hàng cần tối thiểu ' . number_format((float)$coupon['min_order_amount'], 0, ',', '.') . 'đ để áp dụng mã này.', 'coupon' => null, 'discount' => 0];
    }

    if ($coupon['discount_type'] === 'percent') {
        $discount = $subtotal * (float)$coupon['discount_value'] / 100;
        if ($coupon['max_discount_amount'] !== null) {
            $discount = min($discount, (float)$coupon['max_discount_amount']);
        }
    } else {
        $discount = (float)$coupon['discount_value'];
    }
    $discount = min($discount, $subtotal);
    $discount = max(0, round($discount));

    return ['valid' => true, 'message' => 'Áp dụng mã giảm giá thành công.', 'coupon' => $coupon, 'discount' => $discount];
}

/**
 * Tăng số lượt đã sử dụng của mã giảm giá lên 1, gọi khi đơn hàng được tạo thành công.
 */
function increment_coupon_usage($conn, $coupon_id)
{
    $stmt = $conn->prepare('UPDATE coupons SET used_count=used_count+1 WHERE id=?');
    $stmt->bind_param('i', $coupon_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Nhãn hiển thị mô tả ngắn gọn giá trị giảm của 1 mã, ví dụ "Giảm 10% (tối đa 500.000đ)" hoặc "Giảm 200.000đ".
 */
function coupon_value_label($coupon)
{
    if ($coupon['discount_type'] === 'percent') {
        $label = 'Giảm ' . rtrim(rtrim(number_format((float)$coupon['discount_value'], 1), '0'), '.') . '%';
        if ($coupon['max_discount_amount'] !== null) {
            $label .= ' (tối đa ' . number_format((float)$coupon['max_discount_amount'], 0, ',', '.') . 'đ)';
        }
        return $label;
    }
    return 'Giảm ' . number_format((float)$coupon['discount_value'], 0, ',', '.') . 'đ';
}
