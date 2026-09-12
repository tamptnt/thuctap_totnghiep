-- =====================================================================
-- MIGRATION: Thêm lịch sử trạng thái đơn hàng (order_status_history)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- =====================================================================

USE `tech_store`;

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `status` varchar(50) NOT NULL,
    `note` varchar(255) DEFAULT NULL,
    `changed_by` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Khởi tạo 1 dòng lịch sử "hiện trạng" cho các đơn đã có sẵn trước khi tính năng này ra đời,
-- để timeline không bị trống. Chạy 1 lần duy nhất sau khi tạo bảng.
INSERT INTO `order_status_history` (`order_id`, `status`, `note`, `changed_by`, `created_at`)
SELECT `id`, `status`, 'Trạng thái trước khi bật tính năng theo dõi lịch sử.', 'Hệ thống', `created_at`
FROM `orders`
WHERE `id` NOT IN (SELECT DISTINCT `order_id` FROM `order_status_history`);
