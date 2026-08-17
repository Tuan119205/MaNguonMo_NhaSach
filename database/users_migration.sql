-- UTF-8 cho tiếng Việt: chạy file này trong database obs_db.
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS `obs_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `obs_db`;

-- Thêm bảng users cho hệ thống đăng ký/đăng nhập của khách hàng
CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` varchar(100) NOT NULL UNIQUE,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(15),
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` boolean DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo một user mẫu (password: 123456)
INSERT INTO `users` (`email`, `username`, `password`, `fullname`, `phone`) VALUES
('customer@example.com', 'customer1', 'e807f1fcf82d132f9bb018ca6738a19f', 'John Doe', '0123456789');
