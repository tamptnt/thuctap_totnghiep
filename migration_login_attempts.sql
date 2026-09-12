-- =====================================================================
-- MIGRATION: Chống brute-force đăng nhập theo IP/tài khoản (login_attempts)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- =====================================================================

USE `tech_store`;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `identity` varchar(150) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `success` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `identity` (`identity`),
    KEY `ip_address` (`ip_address`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gợi ý: bảng này có thể dọn định kỳ (ví dụ giữ 30 ngày gần nhất) bằng:
-- DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 30 DAY);
