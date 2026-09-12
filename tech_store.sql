SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `tech_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tech_store`;
CREATE TABLE `banners` (`id` int(11) NOT NULL,`image_url` varchar(255) NOT NULL,`link` varchar(255) DEFAULT NULL,`position` varchar(50) DEFAULT 'home_top',`banner_type` varchar(50) DEFAULT 'main',`status` tinyint(1) DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `banners` VALUES
(1,'tech_banner_01.png','san-pham','home_top','main',1),
(2,'tech_banner_02.png','san-pham?categories[]=2','home_top','main',1),
(3,'tech_banner_03.png','san-pham?categories[]=4','home_top','main',1);
CREATE TABLE `brands` (`id` int(11) NOT NULL,`name` varchar(100) NOT NULL,`status` tinyint(1) DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `brands` VALUES (1,'Apple',1),(2,'Samsung',1),(3,'ASUS',1),(4,'Lenovo',1),(5,'Dell',1),(6,'Logitech',1),(7,'Sony',1),(8,'TP-Link',1),(9,'Kingston',1),(10,'Xiaomi',1);
CREATE TABLE `categories` (`id` int(11) NOT NULL,`name` varchar(100) NOT NULL,`status` tinyint(1) DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `categories` VALUES (1,'Laptop',1),(2,'Điện thoại',1),(3,'Máy tính bảng',1),(4,'Màn hình',1),(5,'Phụ kiện',1),(6,'Thiết bị mạng',1),(7,'Âm thanh',1),(8,'Linh kiện máy tính',1);
CREATE TABLE `news` (`id` int(11) NOT NULL,`title` varchar(255) NOT NULL,`thumbnail` varchar(255) DEFAULT NULL,`content` longtext DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `news` VALUES
(1,'Cách chọn laptop học tập và làm việc năm 2026','tech_news_01.png','<p>Khi chọn laptop nên ưu tiên bộ xử lý, dung lượng RAM, loại ổ SSD, chất lượng màn hình và thời lượng pin theo nhu cầu thực tế. Sinh viên và nhân viên văn phòng thường phù hợp cấu hình 8-16GB RAM, SSD từ 256GB và màn hình Full HD trở lên.</p><p>Nếu thường xuyên xử lý đồ họa hoặc lập trình nặng, nên cân nhắc CPU hiệu năng cao hơn, RAM 16GB trở lên và khả năng nâng cấp.</p>','2026-07-17 10:00:00'),
(2,'Chọn điện thoại theo camera, pin hay hiệu năng','tech_news_02.png','<p>Mỗi nhóm người dùng có ưu tiên khác nhau. Người chụp ảnh nên quan tâm cảm biến, chống rung và khả năng xử lý ảnh; người chơi game cần hiệu năng ổn định, màn hình tần số quét cao và tản nhiệt tốt; người dùng cơ bản nên ưu tiên pin và thời gian cập nhật phần mềm.</p>','2026-07-18 10:00:00'),
(3,'Màn hình 2K và 4K khác nhau thế nào khi làm việc','tech_news_03.png','<p>Màn hình 2K cân bằng giữa độ nét và yêu cầu phần cứng, trong khi 4K phù hợp xử lý nội dung chi tiết, ảnh và video. Ngoài độ phân giải, cần chú ý kích thước, tấm nền, độ phủ màu, cổng kết nối và khả năng điều chỉnh chân đế.</p>','2026-07-19 10:00:00'),
(4,'Wi-Fi 6 có đáng nâng cấp cho gia đình','tech_news_04.png','<p>Wi-Fi 6 cải thiện hiệu quả khi nhiều thiết bị truy cập cùng lúc và phù hợp nhà có điện thoại, laptop, TV, camera hoặc thiết bị IoT. Hiệu quả thực tế còn phụ thuộc gói Internet, vị trí đặt router và thiết bị đầu cuối có hỗ trợ chuẩn mới hay không.</p>','2026-07-20 10:00:00'),
(5,'SSD NVMe và SATA: nên chọn loại nào','tech_news_05.png','<p>SSD NVMe dùng giao tiếp PCIe nên có tốc độ cao hơn đáng kể so với SATA. Với máy hỗ trợ khe M.2 NVMe, đây là lựa chọn phù hợp cho hệ điều hành, phần mềm và game; SATA vẫn hữu ích khi cần nâng cấp máy cũ với chi phí hợp lý.</p>','2026-07-21 10:00:00'),
(6,'5 phụ kiện giúp góc làm việc gọn và hiệu quả hơn','tech_news_01.png','<p>Chuột công thái học, bàn phím phù hợp, hub USB-C, tai nghe chống ồn và giá đỡ laptop là những phụ kiện có thể cải thiện trải nghiệm làm việc. Nên chọn theo thiết bị đang dùng và số cổng kết nối cần thiết.</p>','2026-07-22 10:00:00'),
(7,'Những thông số cần kiểm tra trước khi mua tai nghe','tech_news_02.png','<p>Ngoài kiểu đeo, hãy kiểm tra chuẩn Bluetooth, codec, thời lượng pin, khả năng chống ồn, micro và hỗ trợ kết nối nhiều thiết bị. Với nhu cầu họp trực tuyến, chất lượng micro và độ ổn định kết nối quan trọng không kém chất âm.</p>','2026-07-23 10:00:00'),
(8,'Kiểm tra thiết bị công nghệ khi nhận hàng','tech_news_03.png','<p>Khi nhận hàng nên đối chiếu đúng model, dung lượng, màu sắc, phụ kiện, số serial và tình trạng bên ngoài. Với đơn thanh toán trực tuyến cần kiểm tra mã đơn và trạng thái thanh toán trước khi xác nhận nhận hàng.</p>','2026-07-24 10:00:00');
CREATE TABLE `users` (`id` int(11) NOT NULL,`fullname` varchar(100) NOT NULL,`username` varchar(50) NOT NULL,`email` varchar(100) NOT NULL,`email_verified_at` datetime DEFAULT NULL,`password` varchar(255) NOT NULL,`google_id` varchar(190) DEFAULT NULL,`avatar` varchar(255) DEFAULT NULL,`phone` varchar(20) NOT NULL DEFAULT '',`date_of_birth` date DEFAULT NULL,`gender` enum('male','female','other') DEFAULT NULL,`address` text DEFAULT NULL,`role` enum('admin','user') NOT NULL DEFAULT 'user',`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `users` (`id`,`fullname`,`username`,`email`,`email_verified_at`,`password`,`google_id`,`phone`,`address`,`role`,`created_at`) VALUES (1,'Nguyễn Văn A','huyen','nguyenvana@gmail.com','2026-04-06 04:35:00','e10adc3949ba59abbe56e057f20f883e',NULL,'0901234567',NULL,'user','2026-04-06 04:30:38'),(2,'Trần Thị B','tranthib','tranthib@gmail.com',NULL,'e10adc3949ba59abbe56e057f20f883e',NULL,'0987654321',NULL,'user','2026-04-06 04:30:38'),(3,'Lê Văn C','levanc','levanc@gmail.com',NULL,'e10adc3949ba59abbe56e057f20f883e',NULL,'0911222333',NULL,'user','2026-04-06 04:30:38'),(4,'Quản Trị Viên','admin','admin@novatech.vn','2026-04-06 04:52:16','e10adc3949ba59abbe56e057f20f883e',NULL,'0888888888',NULL,'admin','2026-04-06 04:52:16');
CREATE TABLE `password_resets` (`id` int(11) NOT NULL,`user_id` int(11) NOT NULL,`token_hash` varchar(255) NOT NULL,`expires_at` datetime NOT NULL,`attempts` int(11) NOT NULL DEFAULT 0,`used_at` datetime DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `login_attempts` (`id` int(11) NOT NULL,`identity` varchar(150) NOT NULL,`ip_address` varchar(45) NOT NULL,`success` tinyint(1) NOT NULL DEFAULT 0,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `email_verifications` (`id` int(11) NOT NULL,`user_id` int(11) NOT NULL,`token_hash` char(64) NOT NULL,`expires_at` datetime NOT NULL,`used_at` datetime DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `products` (`id` int(11) NOT NULL,`category_id` int(11) NOT NULL,`brand_id` int(11) NOT NULL,`name` varchar(255) NOT NULL,`image` varchar(255) DEFAULT NULL,`price` decimal(10,2) NOT NULL,`sale_price` decimal(10,2) DEFAULT NULL,`sales_count` int(11) DEFAULT 0,`stock_quantity` int(11) NOT NULL DEFAULT 10,`is_hot` tinyint(1) DEFAULT 0,`warranty_text` varchar(255) DEFAULT NULL,`description` text DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `products` VALUES
(1,1,1,'MacBook Air M2 13 inch 8GB 256GB','tech_product_01.png',22990000,21490000,178,18,1,'Apple M2 · 8GB · SSD 256GB · BH 12 tháng','<p>MacBook Air M2 có thiết kế mỏng nhẹ, màn hình Liquid Retina 13.6 inch và thời lượng pin tốt. Cấu hình 8GB RAM, SSD 256GB phù hợp học tập, văn phòng, lập trình và chỉnh sửa nội dung cơ bản.</p>','2026-07-17 10:00:00'),
(2,1,3,'ASUS Vivobook 15 OLED A1505','tech_product_02.png',18990000,16990000,164,14,1,'Core i5 · 16GB · SSD 512GB · OLED · BH 24 tháng','<p>Vivobook 15 OLED ưu tiên màn hình màu sắc tốt, cấu hình cân bằng và bàn phím đầy đủ. Phù hợp sinh viên, văn phòng và nhu cầu xử lý đa nhiệm.</p>','2026-07-17 10:00:00'),
(3,1,4,'Lenovo LOQ 15 Gaming RTX 4060','tech_product_03.png',28990000,26990000,132,6,1,'Core i7 · 16GB · RTX 4060 · SSD 512GB · BH 24 tháng','<p>Lenovo LOQ 15 hướng đến gaming và đồ họa với GPU RTX 4060, hệ thống tản nhiệt chủ động và màn hình tần số quét cao. Máy phù hợp game, dựng hình và các tác vụ cần GPU rời.</p>','2026-07-17 10:00:00'),
(4,2,1,'iPhone 15 128GB','tech_product_04.png',19990000,18490000,198,8,1,'A16 Bionic · 128GB · USB-C · BH 12 tháng','<p>iPhone 15 sử dụng chip A16 Bionic, camera chính 48MP, màn hình OLED và cổng USB-C. Phiên bản 128GB phù hợp nhu cầu sử dụng hằng ngày, chụp ảnh và hệ sinh thái Apple.</p>','2026-07-17 10:00:00'),
(5,2,2,'Samsung Galaxy S24 256GB','tech_product_05.png',21990000,18990000,205,17,1,'8GB · 256GB · 5G · Galaxy AI · BH 12 tháng','<p>Galaxy S24 có màn hình AMOLED tần số quét cao, hiệu năng mạnh và các tính năng Galaxy AI hỗ trợ tìm kiếm, biên tập và dịch. Bộ nhớ 256GB phù hợp người dùng lưu nhiều ứng dụng và nội dung.</p>','2026-07-17 10:00:00'),
(6,2,10,'Xiaomi Redmi Note 13 Pro 5G','tech_product_06.png',9490000,8490000,230,25,1,'12GB · 256GB · 5G · Camera 200MP · BH 18 tháng','<p>Redmi Note 13 Pro 5G tập trung vào màn hình AMOLED, camera độ phân giải cao và dung lượng RAM lớn trong phân khúc tầm trung.</p>','2026-07-17 10:00:00'),
(7,3,1,'iPad Air M2 11 inch Wi-Fi 128GB','tech_product_07.png',17990000,16990000,151,13,1,'Apple M2 · 128GB · Wi-Fi 6E · BH 12 tháng','<p>iPad Air M2 11 inch phù hợp ghi chú, học tập, sáng tạo nội dung và làm việc di động. Thiết bị hỗ trợ Apple Pencil và các phụ kiện bàn phím tương thích.</p>','2026-07-17 10:00:00'),
(8,3,2,'Samsung Galaxy Tab S9 FE 128GB','tech_product_08.png',10990000,9990000,146,16,1,'6GB · 128GB · S Pen · Wi-Fi · BH 12 tháng','<p>Galaxy Tab S9 FE đi kèm S Pen, màn hình lớn và giao diện hỗ trợ đa nhiệm. Phù hợp ghi chú, học online, đọc tài liệu và giải trí.</p>','2026-07-17 10:00:00'),
(9,4,5,'Dell S2722QC 27 inch 4K USB-C','tech_product_09.png',10990000,9490000,171,12,1,'27 inch · 4K IPS · USB-C 65W · BH 36 tháng','<p>Dell S2722QC có độ phân giải 4K, tấm nền IPS và USB-C hỗ trợ truyền hình ảnh kèm cấp nguồn. Phù hợp văn phòng, thiết kế và kết nối laptop gọn dây.</p>','2026-07-17 10:00:00'),
(10,4,3,'ASUS TUF Gaming VG249Q3A 180Hz','tech_product_10.png',4990000,4490000,219,22,1,'23.8 inch · IPS · 180Hz · 1ms · BH 36 tháng','<p>Màn hình TUF Gaming 180Hz ưu tiên chuyển động mượt, thời gian phản hồi thấp và kích thước vừa gọn cho bàn gaming hoặc góc làm việc.</p>','2026-07-17 10:00:00'),
(11,5,6,'Logitech MX Master 3S','tech_product_11.png',2290000,1990000,260,31,1,'Bluetooth · Logi Bolt · USB-C · BH 12 tháng','<p>MX Master 3S là chuột không dây cho công việc với cuộn điện từ MagSpeed, nút tùy chỉnh và khả năng chuyển đổi giữa nhiều thiết bị.</p>','2026-07-17 10:00:00'),
(12,7,7,'Sony WH-1000XM5','tech_product_12.png',8490000,7490000,187,9,1,'Bluetooth 5.2 · ANC · Pin 30 giờ · BH 12 tháng','<p>Sony WH-1000XM5 tập trung vào chống ồn chủ động, micro cho hội thoại và thời lượng pin dài. Phù hợp di chuyển, làm việc và nghe nhạc.</p>','2026-07-17 10:00:00'),
(13,6,8,'TP-Link Archer AX55 Wi-Fi 6','tech_product_13.png',2090000,1890000,193,27,1,'Wi-Fi 6 AX3000 · Gigabit · OneMesh · BH 24 tháng','<p>Archer AX55 hỗ trợ Wi-Fi 6 và nhiều thiết bị truy cập đồng thời. Phù hợp căn hộ hoặc gia đình có nhu cầu streaming, học tập và thiết bị IoT.</p>','2026-07-17 10:00:00'),
(14,8,9,'Kingston NV2 NVMe PCIe 4.0 1TB','tech_product_14.png',1790000,1590000,245,34,1,'M.2 2280 · PCIe 4.0 NVMe · 1TB · BH 36 tháng','<p>Kingston NV2 1TB là SSD NVMe M.2 phù hợp nâng cấp hệ điều hành, phần mềm và game. Dung lượng 1TB mang lại không gian lưu trữ rộng với tốc độ cao hơn SSD SATA.</p>','2026-07-17 10:00:00'),
(15,5,6,'Logitech G Pro X TKL Lightspeed','tech_product_15.png',5490000,4990000,176,20,1,'TKL · LIGHTSPEED · RGB · USB-C · BH 24 tháng','<p>Bàn phím cơ TKL không dây hướng đến game thủ và người cần không gian bàn gọn. Kết nối LIGHTSPEED ổn định và hỗ trợ tùy chỉnh đèn RGB.</p>','2026-07-17 10:00:00');
CREATE TABLE `product_variants` (`id` int(11) NOT NULL,`product_id` int(11) NOT NULL,`variant_name` varchar(150) NOT NULL,`sku` varchar(64) DEFAULT NULL,`price_adjust` decimal(10,2) NOT NULL DEFAULT 0.00,`sale_price_adjust` decimal(10,2) DEFAULT NULL,`stock_quantity` int(11) NOT NULL DEFAULT 0,`is_default` tinyint(1) NOT NULL DEFAULT 0,`status` tinyint(1) NOT NULL DEFAULT 1,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `product_variants` (`id`,`product_id`,`variant_name`,`sku`,`price_adjust`,`sale_price_adjust`,`stock_quantity`,`is_default`,`status`) VALUES
(1,1,'8GB / 256GB','MBA-M2-8-256',0.00,0.00,10,1,1),
(2,1,'16GB / 512GB','MBA-M2-16-512',6000000.00,6000000.00,8,0,1),
(3,4,'128GB - Đen','IP15-128-BLACK',0.00,0.00,5,1,1),
(4,4,'128GB - Xanh','IP15-128-BLUE',0.00,0.00,3,0,1),
(5,4,'256GB - Đen','IP15-256-BLACK',2500000.00,2500000.00,0,0,1);
CREATE TABLE `product_images` (`id` int(11) NOT NULL,`product_id` int(11) NOT NULL,`image` varchar(255) NOT NULL,`sort_order` int(11) NOT NULL DEFAULT 0,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `product_images` (`id`,`product_id`,`image`,`sort_order`) VALUES
(1,1,'tech_product_01.png',0),
(2,4,'tech_product_04.png',0);
CREATE TABLE `reviews` (`id` int(11) NOT NULL,`product_id` int(11) NOT NULL,`user_id` int(11) NOT NULL,`order_id` int(11) NOT NULL,`rating` tinyint(1) NOT NULL,`comment` text DEFAULT NULL,`status` tinyint(1) NOT NULL DEFAULT 1,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `reviews` (`id`,`product_id`,`user_id`,`order_id`,`rating`,`comment`,`status`,`created_at`) VALUES
(1,1,1,1,5,'Máy đẹp, chạy mượt, đóng gói cẩn thận. Rất hài lòng!',1,'2026-04-10 09:12:00'),
(2,12,1,4,4,'Sản phẩm tốt so với giá tiền, giao hàng hơi chậm.',1,'2026-06-12 14:20:00');
CREATE TABLE `coupons` (`id` int(11) NOT NULL,`code` varchar(50) NOT NULL,`description` varchar(255) DEFAULT NULL,`discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',`discount_value` decimal(10,2) NOT NULL,`min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,`max_discount_amount` decimal(10,2) DEFAULT NULL,`usage_limit` int(11) DEFAULT NULL,`used_count` int(11) NOT NULL DEFAULT 0,`starts_at` datetime DEFAULT NULL,`expires_at` datetime DEFAULT NULL,`status` tinyint(1) NOT NULL DEFAULT 1,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `coupons` (`id`,`code`,`description`,`discount_type`,`discount_value`,`min_order_amount`,`max_discount_amount`,`usage_limit`,`used_count`,`starts_at`,`expires_at`,`status`) VALUES
(1,'CHAOMOI10','Giảm 10% cho đơn hàng đầu tiên, tối đa 500.000đ','percent',10.00,500000.00,500000.00,NULL,1,NULL,NULL,1),
(2,'GIAM200K','Giảm ngay 200.000đ cho đơn từ 5.000.000đ','fixed',200000.00,5000000.00,NULL,100,0,NULL,'2026-12-31 23:59:59',1),
(3,'HETHAN2025','Mã đã hết hạn dùng để demo','percent',15.00,0.00,NULL,NULL,0,NULL,'2025-12-31 23:59:59',1);
CREATE TABLE `orders` (`id` int(11) NOT NULL,`user_id` int(11) NOT NULL,`total_money` decimal(10,2) NOT NULL,`status` varchar(50) DEFAULT 'pending',`shipping_address` text NOT NULL,`phone` varchar(20) NOT NULL,`payment_method` varchar(30) NOT NULL DEFAULT 'cod',`payment_status` varchar(30) NOT NULL DEFAULT 'unpaid',`payment_code` varchar(100) DEFAULT NULL,`payment_transaction_id` varchar(100) DEFAULT NULL,`payment_paid_at` datetime DEFAULT NULL,`coupon_code` varchar(50) DEFAULT NULL,`discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `orders` (`id`,`user_id`,`total_money`,`status`,`shipping_address`,`phone`,`payment_method`,`payment_status`,`coupon_code`,`discount_amount`,`created_at`) VALUES
(1,1,20990000,'completed','123 Đường Lê Lợi, Quận 1, TP.HCM','0901234567','vnpay','paid','CHAOMOI10',500000,'2026-04-06 04:30:38'),
(2,2,1990000,'pending','456 Đường Trần Hưng Đạo, Hà Nội','0987654321','cod','unpaid',NULL,0,'2026-04-20 04:30:38'),
(3,1,18990000,'processing','123 Đường Lê Lợi, Quận 1, TP.HCM','0901234567','vnpay','paid',NULL,0,'2026-05-06 04:30:38'),
(4,1,7490000,'completed','123 Đường Lê Lợi, Quận 1, TP.HCM','0901234567','cod','unpaid',NULL,0,'2026-06-06 04:30:38'),
(5,2,1590000,'canceled','456 Đường Trần Hưng Đạo, Hà Nội','0987654321','cod','unpaid',NULL,0,'2026-07-06 04:30:38');
CREATE TABLE `order_items` (`id` int(11) NOT NULL,`order_id` int(11) NOT NULL,`product_id` int(11) NOT NULL,`variant_id` int(11) DEFAULT NULL,`variant_name` varchar(150) DEFAULT NULL,`quantity` int(11) NOT NULL,`price` decimal(10,2) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `order_items` (`id`,`order_id`,`product_id`,`variant_id`,`variant_name`,`quantity`,`price`) VALUES (1,1,1,1,'8GB / 256GB',1,21490000),(2,2,11,NULL,NULL,1,1990000),(3,3,5,NULL,NULL,1,18990000),(4,4,12,NULL,NULL,1,7490000),(5,5,14,NULL,NULL,1,1590000);
CREATE TABLE `order_status_history` (`id` int(11) NOT NULL,`order_id` int(11) NOT NULL,`status` varchar(50) NOT NULL,`note` varchar(255) DEFAULT NULL,`changed_by` varchar(100) DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `order_status_history` (`id`,`order_id`,`status`,`note`,`changed_by`,`created_at`) VALUES
(1,1,'pending','Đơn hàng được tạo','Nguyễn Văn A (khách hàng)','2026-04-06 04:30:38'),
(2,1,'pending','Thanh toán trực tuyến thành công.','Cổng thanh toán','2026-04-06 04:32:00'),
(3,1,'processing',NULL,'Admin: admin','2026-04-07 09:00:00'),
(4,1,'completed',NULL,'Admin: admin','2026-04-09 15:00:00'),
(5,2,'pending','Đơn hàng được tạo','Trần Thị B (khách hàng)','2026-04-20 04:30:38'),
(6,3,'pending','Đơn hàng được tạo','Nguyễn Văn A (khách hàng)','2026-05-06 04:30:38'),
(7,3,'pending','Thanh toán trực tuyến thành công.','Cổng thanh toán','2026-05-06 04:33:00'),
(8,3,'processing',NULL,'Admin: admin','2026-05-07 10:00:00'),
(9,4,'pending','Đơn hàng được tạo','Nguyễn Văn A (khách hàng)','2026-06-06 04:30:38'),
(10,4,'processing',NULL,'Admin: admin','2026-06-07 09:00:00'),
(11,4,'completed',NULL,'Admin: admin','2026-06-10 14:00:00'),
(12,5,'pending','Đơn hàng được tạo','Trần Thị B (khách hàng)','2026-07-06 04:30:38'),
(13,5,'canceled','Khách hàng tự hủy đơn','Trần Thị B (khách hàng)','2026-07-06 06:00:00');
CREATE TABLE `purchase_logs` (`id` int(11) NOT NULL,`order_id` int(11) DEFAULT NULL,`log_message` text NOT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `purchase_logs` VALUES (1,1,'Khách hàng đã đặt MacBook Air M2 13 inch 8GB 256GB','2026-07-17 11:00:00'),(2,2,'Khách hàng đã đặt Logitech MX Master 3S','2026-07-17 11:00:00'),(3,3,'Khách hàng đã đặt Samsung Galaxy S24 256GB','2026-07-17 11:00:00');
CREATE TABLE `settings` (`id` int(11) NOT NULL,`setting_key` varchar(100) NOT NULL,`setting_value` text NOT NULL,`icon` varchar(50) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `settings` VALUES (1,'site_name','NovaTech','fas fa-microchip'),(2,'hotline','0908 246 810','fas fa-phone'),(3,'email','contact@novatech.vn','fas fa-envelope'),(4,'address','168 Thái Hà, Đống Đa, Hà Nội','fas fa-location-dot'),(5,'facebook_link','https://facebook.com/novatech','fab fa-facebook'),(6,'gemini_api_key','','fas fa-key'),(7,'gemini_chat_enabled','1','fas fa-comments'),(8,'gemini_chat_model','gemini-3.5-flash','fas fa-robot'),(9,'gemini_dashboard_model','gemini-3.5-flash','fas fa-chart-line'),(10,'gemini_writer_model','gemini-3.5-flash','fas fa-pen-nib'),(11,'payment_cod_enabled','1','fas fa-money-bill-wave'),(12,'payment_vnpay_enabled','1','fas fa-credit-card'),(13,'vnpay_tmn_code','','fas fa-key'),(14,'vnpay_hash_secret','','fas fa-lock'),(15,'vnpay_url','https://sandbox.vnpayment.vn/paymentv2/vpcpay.html','fas fa-link'),(16,'mail_host','smtp.gmail.com','fas fa-server'),(17,'mail_port','587','fas fa-hashtag'),(18,'mail_username','','fas fa-envelope'),(19,'mail_password','','fas fa-lock'),(20,'mail_encryption','tls','fas fa-shield-halved'),(21,'mail_from_address','','fas fa-at'),(22,'mail_from_name','NovaTech','fas fa-signature'),(23,'google_client_id','','fab fa-google'),(24,'google_client_secret','','fas fa-lock'),(25,'google_redirect_uri','','fas fa-link');
ALTER TABLE `banners` ADD PRIMARY KEY (`id`);
ALTER TABLE `brands` ADD PRIMARY KEY (`id`);
ALTER TABLE `categories` ADD PRIMARY KEY (`id`);
ALTER TABLE `news` ADD PRIMARY KEY (`id`);
ALTER TABLE `orders` ADD PRIMARY KEY (`id`),ADD KEY `user_id` (`user_id`),ADD KEY `payment_code` (`payment_code`);
ALTER TABLE `coupons` ADD PRIMARY KEY (`id`),ADD UNIQUE KEY `code` (`code`);
ALTER TABLE `order_items` ADD PRIMARY KEY (`id`),ADD KEY `order_id` (`order_id`),ADD KEY `product_id` (`product_id`),ADD KEY `variant_id` (`variant_id`);
ALTER TABLE `order_status_history` ADD PRIMARY KEY (`id`),ADD KEY `order_id` (`order_id`);
ALTER TABLE `products` ADD PRIMARY KEY (`id`),ADD KEY `category_id` (`category_id`),ADD KEY `brand_id` (`brand_id`);
ALTER TABLE `product_variants` ADD PRIMARY KEY (`id`),ADD KEY `product_id` (`product_id`),ADD UNIQUE KEY `sku` (`sku`);
ALTER TABLE `product_images` ADD PRIMARY KEY (`id`),ADD KEY `product_id` (`product_id`);
ALTER TABLE `reviews` ADD PRIMARY KEY (`id`),ADD KEY `product_id` (`product_id`),ADD KEY `order_id` (`order_id`),ADD UNIQUE KEY `user_product_unique` (`user_id`,`product_id`);
ALTER TABLE `purchase_logs` ADD PRIMARY KEY (`id`),ADD KEY `order_id` (`order_id`);
ALTER TABLE `settings` ADD PRIMARY KEY (`id`),ADD UNIQUE KEY `setting_key` (`setting_key`);
ALTER TABLE `users` ADD PRIMARY KEY (`id`),ADD UNIQUE KEY `email` (`email`),ADD UNIQUE KEY `username` (`username`),ADD UNIQUE KEY `google_id` (`google_id`);
ALTER TABLE `password_resets` ADD PRIMARY KEY (`id`),ADD KEY `user_id` (`user_id`);
ALTER TABLE `login_attempts` ADD PRIMARY KEY (`id`),ADD KEY `identity` (`identity`),ADD KEY `ip_address` (`ip_address`),ADD KEY `created_at` (`created_at`);
ALTER TABLE `email_verifications` ADD PRIMARY KEY (`id`),ADD KEY `user_id` (`user_id`),ADD KEY `token_hash` (`token_hash`);
ALTER TABLE `banners` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
ALTER TABLE `brands` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
ALTER TABLE `news` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
ALTER TABLE `orders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
ALTER TABLE `coupons` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
ALTER TABLE `order_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
ALTER TABLE `order_status_history` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
ALTER TABLE `products` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
ALTER TABLE `product_variants` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
ALTER TABLE `product_images` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
ALTER TABLE `reviews` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
ALTER TABLE `purchase_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
ALTER TABLE `password_resets` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `login_attempts` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `email_verifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `orders` ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
ALTER TABLE `order_status_history` ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
ALTER TABLE `products` ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`);
ALTER TABLE `product_variants` ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
ALTER TABLE `product_images` ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
ALTER TABLE `reviews` ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;
ALTER TABLE `purchase_logs` ADD CONSTRAINT `purchase_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;
ALTER TABLE `password_resets` ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `email_verifications` ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
