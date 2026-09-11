-- =====================================================================
-- MIGRATION: Thêm ảnh phụ sản phẩm (product_images / gallery nhiều ảnh)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- =====================================================================

USE `tech_store`;

CREATE TABLE IF NOT EXISTS `product_images` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `image` varchar(255) NOT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
