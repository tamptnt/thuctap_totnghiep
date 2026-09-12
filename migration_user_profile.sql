-- =====================================================================
-- MIGRATION: Hoàn thiện hồ sơ người dùng
-- (avatar, ngày sinh, giới tính, xác thực email)
-- Dùng cho DB đã tồn tại dữ liệu (không cần import lại tech_store.sql).
-- =====================================================================

USE `tech_store`;

ALTER TABLE `users`
    ADD COLUMN `email_verified_at` datetime DEFAULT NULL AFTER `email`,
    ADD COLUMN `avatar` varchar(255) DEFAULT NULL AFTER `google_id`,
    ADD COLUMN `date_of_birth` date DEFAULT NULL AFTER `phone`,
    ADD COLUMN `gender` enum('male','female','other') DEFAULT NULL AFTER `date_of_birth`;

CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `token_hash` char(64) NOT NULL,
    `expires_at` datetime NOT NULL,
    `used_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `token_hash` (`token_hash`),
    CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tuỳ chọn: coi các tài khoản đã tồn tại từ trước là đã xác thực (tránh chặn nhầm người dùng cũ).
-- Bỏ dòng dưới nếu bạn muốn bắt toàn bộ user cũ xác thực lại email.
UPDATE `users` SET `email_verified_at` = `created_at` WHERE `email_verified_at` IS NULL;
