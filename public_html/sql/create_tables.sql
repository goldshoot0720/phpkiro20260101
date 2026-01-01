-- 鋒兒AI資訊系統 資料表結構

-- 使用者表
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 圖片庫表
CREATE TABLE IF NOT EXISTS `images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text,
  `description` text,
  `upload_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_upload_date` (`upload_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 影片庫表
CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT '影片長度(秒)',
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text,
  `description` text,
  `upload_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_upload_date` (`upload_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 食品管理表
CREATE TABLE IF NOT EXISTS `foods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit` varchar(20) DEFAULT '個',
  `storage_location` varchar(100) DEFAULT NULL,
  `notes` text,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('normal','expiring','expired') DEFAULT 'normal',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 訂閱管理表
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(200) NOT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','yearly','weekly','daily') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `next_billing_date` date NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `category` varchar(100) DEFAULT NULL,
  `notes` text,
  `reminder_days` int(11) DEFAULT 3 COMMENT '提前幾天提醒',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_next_billing_date` (`next_billing_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 系統設定表
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入預設系統設定
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', '鋒兒AI資訊系統', '網站名稱'),
('site_description', '智能管理您的影片和圖片收藏，支援智能分類和快速搜尋', '網站描述'),
('max_upload_size', '50', '最大上傳檔案大小(MB)'),
('allowed_image_types', 'jpg,jpeg,png,gif,webp', '允許的圖片格式'),
('allowed_video_types', 'mp4,avi,mov,wmv,flv', '允許的影片格式'),
('expiry_warning_days', '7', '食品到期提醒天數'),
('subscription_reminder_days', '3', '訂閱提醒天數');

-- 建立預設管理員帳號 (密碼: admin123)
INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');