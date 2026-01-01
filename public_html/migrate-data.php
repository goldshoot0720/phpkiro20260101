<?php
/**
 * 資料遷移工具 - 從 goldshoot0720 遷移到 hsin_php
 */

try {
    // 連接到 MySQL (不指定資料庫)
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<h2>開始資料遷移...</h2>\n";
    
    // 1. 確保 hsin_php 資料庫存在
    $pdo->exec("CREATE DATABASE IF NOT EXISTS hsin_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ 確保 hsin_php 資料庫存在</p>\n";
    
    // 2. 使用 hsin_php 資料庫
    $pdo->exec("USE hsin_php");
    
    // 3. 建立 users 表格 (系統需要)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(50) NOT NULL,
            password varchar(255) NOT NULL,
            email varchar(100) DEFAULT NULL,
            role enum('admin','user') DEFAULT 'user',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ 建立 users 表格</p>\n";
    
    // 4. 建立 foods 表格 (對應原本的 food)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS foods (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            brand varchar(100) DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            price decimal(10,2) DEFAULT NULL,
            purchase_date date DEFAULT NULL,
            expiry_date date DEFAULT NULL,
            quantity int(11) DEFAULT 1,
            unit varchar(20) DEFAULT '個',
            storage_location varchar(100) DEFAULT NULL,
            notes text,
            image_path varchar(500) DEFAULT NULL,
            image_url text DEFAULT NULL,
            status enum('normal','expiring','expired') DEFAULT 'normal',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_expiry_date (expiry_date),
            KEY idx_category (category),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ 建立 foods 表格</p>\n";
    
    // 5. 建立 subscriptions 表格 (對應原本的 subscription)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id int(11) NOT NULL AUTO_INCREMENT,
            service_name varchar(200) NOT NULL,
            website_url varchar(500) DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            billing_cycle enum('monthly','yearly','weekly','daily') DEFAULT 'monthly',
            start_date date NOT NULL,
            next_billing_date date NOT NULL,
            status enum('active','paused','cancelled') DEFAULT 'active',
            category varchar(100) DEFAULT NULL,
            notes text,
            account_info text,
            reminder_days int(11) DEFAULT 3,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_next_billing_date (next_billing_date),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ 建立 subscriptions 表格</p>\n";
    
    // 6. 建立其他系統需要的表格
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS images (
            id int(11) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            width int(11) DEFAULT NULL,
            height int(11) DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            tags text,
            description text,
            upload_date timestamp DEFAULT CURRENT_TIMESTAMP,
            user_id int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_category (category),
            KEY idx_upload_date (upload_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ 建立 images 表格</p>\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS videos (
            id int(11) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            duration int(11) DEFAULT NULL,
            thumbnail_path varchar(500) DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            tags text,
            description text,
            upload_date timestamp DEFAULT CURRENT_TIMESTAMP,
            user_id int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_category (category),
            KEY idx_upload_date (upload_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ 建立 videos 表格</p>\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text,
            description varchar(255) DEFAULT NULL,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ 建立 system_settings 表格</p>\n";
    
    // 7. 檢查 goldshoot0720 資料庫是否存在
    $stmt = $pdo->query("SHOW DATABASES LIKE 'goldshoot0720'");
    if ($stmt->fetch()) {
        echo "<h3>開始遷移現有資料...</h3>\n";
        
        // 遷移食品資料
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM goldshoot0720.food");
        $foodCount = $stmt->fetch()['count'];
        
        if ($foodCount > 0) {
            $pdo->exec("
                INSERT INTO foods (name, expiry_date, quantity, price, image_url, notes)
                SELECT 
                    name,
                    todate as expiry_date,
                    COALESCE(amount, 1) as quantity,
                    price,
                    photo as image_url,
                    CONCAT_WS(' | ', shop, photohash) as notes
                FROM goldshoot0720.food
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    expiry_date = VALUES(expiry_date),
                    quantity = VALUES(quantity),
                    price = VALUES(price),
                    image_url = VALUES(image_url),
                    notes = VALUES(notes)
            ");
            echo "<p>✓ 遷移 $foodCount 筆食品資料</p>\n";
        }
        
        // 遷移訂閱資料
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM goldshoot0720.subscription");
        $subCount = $stmt->fetch()['count'];
        
        if ($subCount > 0) {
            $pdo->exec("
                INSERT INTO subscriptions (service_name, next_billing_date, price, website_url, notes, account_info, start_date, status)
                SELECT 
                    name as service_name,
                    nextdate as next_billing_date,
                    price,
                    site as website_url,
                    note as notes,
                    account as account_info,
                    COALESCE(nextdate - INTERVAL 1 MONTH, CURDATE()) as start_date,
                    CASE 
                        WHEN name LIKE '%取消%' OR name LIKE '%已經取消%' THEN 'cancelled'
                        ELSE 'active'
                    END as status
                FROM goldshoot0720.subscription
                ON DUPLICATE KEY UPDATE
                    service_name = VALUES(service_name),
                    next_billing_date = VALUES(next_billing_date),
                    price = VALUES(price),
                    website_url = VALUES(website_url),
                    notes = VALUES(notes),
                    account_info = VALUES(account_info),
                    status = VALUES(status)
            ");
            echo "<p>✓ 遷移 $subCount 筆訂閱資料</p>\n";
        }
        
    } else {
        echo "<p>⚠ goldshoot0720 資料庫不存在，跳過資料遷移</p>\n";
    }
    
    // 8. 插入預設系統設定
    $pdo->exec("
        INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
        ('site_name', '鋒兒AI資訊系統', '網站名稱'),
        ('site_description', '智能管理您的影片和圖片收藏，支援智能分類和快速搜尋', '網站描述'),
        ('max_upload_size', '50', '最大上傳檔案大小(MB)'),
        ('allowed_image_types', 'jpg,jpeg,png,gif,webp', '允許的圖片格式'),
        ('allowed_video_types', 'mp4,avi,mov,wmv,flv', '允許的影片格式'),
        ('expiry_warning_days', '7', '食品到期提醒天數'),
        ('subscription_reminder_days', '3', '訂閱提醒天數')
    ");
    echo "<p>✓ 插入系統設定</p>\n";
    
    // 9. 建立預設管理員帳號
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', $hashedPassword, 'admin@example.com', 'admin']);
    echo "<p>✓ 建立預設管理員帳號</p>\n";
    
    echo "<h3>✅ 資料遷移完成！</h3>\n";
    echo "<p><strong>系統已準備就緒，可以開始使用。</strong></p>\n";
    echo "<p><a href='index.php'>前往系統首頁</a></p>\n";
    
} catch (PDOException $e) {
    echo "<h3>❌ 遷移失敗！</h3>\n";
    echo "<p>錯誤訊息：" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><a href='debug-db.php'>檢查資料庫設定</a></p>\n";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料遷移 - 鋒兒AI資訊系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        h2, h3 {
            color: #fff;
        }
        p {
            margin: 10px 0;
            line-height: 1.6;
        }
        a {
            color: #90cdf4;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
    </div>
</body>
</html>