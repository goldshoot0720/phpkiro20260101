<?php
/**
 * 示範資料產生器
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>正在建立示範資料...</h2>\n";
    
    // 清空現有資料 (除了使用者表)
    $tables = ['images', 'videos', 'foods', 'subscriptions'];
    foreach ($tables as $table) {
        $db->exec("DELETE FROM $table");
        echo "<p>✓ 清空 $table 表格</p>\n";
    }
    
    // 新增示範食品資料
    $foods = [
        ['張君雅小妹妹五香海苔休閒丸子', '張君雅', '零食', 35, '2025-12-20', '2026-01-06', 3, '包', '櫥櫃', '15天後到期'],
        ['張君雅小妹妹日式燒烤休閒丸子', '張君雅', '零食', 35, '2025-12-20', '2026-01-07', 6, '包', '櫥櫃', '16天後到期'],
        ['統一麵包', '統一', '麵包', 25, '2025-12-30', '2026-01-03', 2, '個', '冰箱', '2天後到期'],
        ['鮮奶', '光泉', '飲品', 65, '2025-12-28', '2026-01-05', 1, '瓶', '冰箱', '4天後到期'],
        ['雞蛋', '大成', '蛋類', 120, '2025-12-25', '2026-01-15', 12, '顆', '冰箱', '14天後到期']
    ];
    
    $stmt = $db->prepare("INSERT INTO foods (name, brand, category, price, purchase_date, expiry_date, quantity, unit, storage_location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($foods as $food) {
        $stmt->execute($food);
    }
    echo "<p>✓ 新增 " . count($foods) . " 筆食品資料</p>\n";
    
    // 新增示範訂閱資料
    $subscriptions = [
        ['天成/黃信訊/心臟內科', 'https://www.tcmg.com.tw/index.php/main/schedule_time?id=18', 530, 'monthly', '2025-12-26', '2026-01-26', 'active', '醫療', '每月回診', 3],
        ['kiro pro', 'https://app.kiro.dev/account/', 640, 'monthly', '2026-01-01', '2026-02-01', 'active', '開發工具', 'AI 程式開發助手', 10]
    ];
    
    $stmt = $db->prepare("INSERT INTO subscriptions (service_name, website_url, price, billing_cycle, start_date, next_billing_date, status, category, notes, reminder_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($subscriptions as $sub) {
        $stmt->execute($sub);
    }
    echo "<p>✓ 新增 " . count($subscriptions) . " 筆訂閱資料</p>\n";
    
    echo "<h3>示範資料建立完成！</h3>\n";
    echo "<p><a href='index.php'>返回系統首頁</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3>建立示範資料失敗！</h3>\n";
    echo "<p>錯誤訊息：" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>示範資料 - 鋒兒AI資訊系統</title>
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
        p {
            margin: 10px 0;
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