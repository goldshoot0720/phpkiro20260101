<?php
/**
 * 資料庫連線測試
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>資料庫連線測試</h2>";
    echo "<p>✓ 資料庫連線成功！</p>";
    
    // 測試查詢
    $stmt = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
    $result = $stmt->fetch();
    
    echo "<p>資料庫中共有 {$result['table_count']} 個表格</p>";
    
    // 檢查主要表格是否存在
    $tables = ['users', 'images', 'videos', 'foods', 'subscriptions', 'system_settings'];
    
    echo "<h3>表格檢查：</h3>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->fetch()) {
            echo "<li>✓ $table - 存在</li>";
        } else {
            echo "<li>❌ $table - 不存在</li>";
        }
    }
    
    echo "</ul>";
    
    // 檢查管理員帳號
    $stmt = $db->prepare("SELECT username FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>✓ 管理員帳號已建立：{$admin['username']}</p>";
    } else {
        echo "<p>❌ 尚未建立管理員帳號</p>";
    }
    
    echo "<p><a href='index.php'>前往系統首頁</a></p>";
    
} catch (Exception $e) {
    echo "<h2>資料庫連線失敗</h2>";
    echo "<p>錯誤訊息：" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>請檢查資料庫設定或執行 <a href='install.php'>install.php</a> 進行安裝</p>";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料庫測試 - 鋒兒AI資訊系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2, h3 {
            color: #333;
        }
        p, li {
            margin: 10px 0;
        }
        ul {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007cba;
        }
        a {
            color: #007cba;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
</body>
</html>