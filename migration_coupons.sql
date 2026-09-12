-- =====================================================================
-- MIGRATION: Thêm mã giảm giá (coupons)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- =====================================================================

USE `tech_store`;

CREATE TABLE IF NOT EXISTS `coupons` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
    `discount_value` decimal(10,2) NOT NULL,
    `min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `max_discount_amount` decimal(10,2) DEFAULT NULL,
    `usage_limit` int(11) DEFAULT NULL,
    `used_count` int(11) NOT NULL DEFAULT 0,
    `starts_at` datetime DEFAULT NULL,
    `expires_at` datetime DEFAULT NULL,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `orders`
    ADD COLUMN `coupon_code` varchar(50) DEFAULT NULL AFTER `payment_paid_at`,
    ADD COLUMN `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`;

-- Dữ liệu mẫu (tuỳ chọn, có thể sửa/xoá):
INSERT INTO `coupons` (`code`,`description`,`discount_type`,`discount_value`,`min_order_amount`,`max_discount_amount`,`usage_limit`,`starts_at`,`expires_at`,`status`) VALUES
('CHAOMOI10','Giảm 10% cho đơn hàng đầu tiên, tối đa 500.000đ','percent',10.00,500000.00,500000.00,NULL,NULL,NULL,1),
('GIAM200K','Giảm ngay 200.000đ cho đơn từ 5.000.000đ','fixed',200000.00,5000000.00,NULL,100,NULL,'2026-12-31 23:59:59',1);
