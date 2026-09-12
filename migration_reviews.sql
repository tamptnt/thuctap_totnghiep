-- =====================================================================
-- MIGRATION: Thêm đánh giá/review sản phẩm (reviews)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- Chỉ khách hàng có đơn hàng ở trạng thái 'completed' chứa sản phẩm mới
-- được đánh giá sản phẩm đó, mỗi người chỉ đánh giá 1 lần / sản phẩm.
-- =====================================================================

USE `tech_store`;

CREATE TABLE IF NOT EXISTS `reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `order_id` int(11) NOT NULL,
    `rating` tinyint(1) NOT NULL,
    `comment` text DEFAULT NULL,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `order_id` (`order_id`),
    UNIQUE KEY `user_product_unique` (`user_id`,`product_id`),
    CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
