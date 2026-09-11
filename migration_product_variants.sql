-- =====================================================================
-- MIGRATION: Thêm quản lý biến thể sản phẩm (product_variants)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- Chạy 1 lần duy nhất. Đã kiểm tra IF NOT EXISTS ở phần có thể lặp lại.
-- =====================================================================

USE `tech_store`;

-- 1) Bảng biến thể sản phẩm
CREATE TABLE IF NOT EXISTS `product_variants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `variant_name` varchar(150) NOT NULL,
    `sku` varchar(64) DEFAULT NULL,
    `price_adjust` decimal(10,2) NOT NULL DEFAULT 0.00,
    `sale_price_adjust` decimal(10,2) DEFAULT NULL,
    `stock_quantity` int(11) NOT NULL DEFAULT 0,
    `is_default` tinyint(1) NOT NULL DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    UNIQUE KEY `sku` (`sku`),
    CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Thêm cột lưu biến thể vào order_items (bỏ qua nếu đã có, chạy tay nếu MySQL < 8.0 báo lỗi cột trùng thì bỏ dòng đã chạy)
ALTER TABLE `order_items`
    ADD COLUMN `variant_id` int(11) DEFAULT NULL AFTER `product_id`,
    ADD COLUMN `variant_name` varchar(150) DEFAULT NULL AFTER `variant_id`;

ALTER TABLE `order_items`
    ADD KEY `variant_id` (`variant_id`),
    ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

-- 3) (Tuỳ chọn) Dữ liệu mẫu minh hoạ cho 2 sản phẩm sẵn có trong DB demo.
--    Bỏ qua/sửa lại nếu id sản phẩm của bạn khác.
INSERT INTO `product_variants` (`product_id`,`variant_name`,`sku`,`price_adjust`,`sale_price_adjust`,`stock_quantity`,`is_default`,`status`) VALUES
(1,'8GB / 256GB','MBA-M2-8-256',0.00,0.00,10,1,1),
(1,'16GB / 512GB','MBA-M2-16-512',6000000.00,6000000.00,8,0,1),
(4,'128GB - Đen','IP15-128-BLACK',0.00,0.00,5,1,1),
(4,'128GB - Xanh','IP15-128-BLUE',0.00,0.00,3,0,1),
(4,'256GB - Đen','IP15-256-BLACK',2500000.00,2500000.00,0,0,1);
