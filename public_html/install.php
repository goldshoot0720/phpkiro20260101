<?php
/**
 * 系統安裝程式
 * 執行此檔案來建立資料庫表格和預設資料
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // 讀取 SQL 檔案
    $sqlFile = 'sql/create_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("找不到 SQL 檔案: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // 分割 SQL 語句
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h2>開始安裝鋒兒AI資訊系統...</h2>\n";
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $db->exec($statement);
            
            // 判斷執行的語句類型
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches);
                $tableName = $matches[1] ?? '未知表格';
                echo "<p>✓ 建立表格: $tableName</p>\n";
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO.*?`([^`]+)`/i', $statement, $matches);
                $tableName = $matches[1] ?? '未知表格';
                echo "<p>✓ 插入資料到: $tableName</p>\n";
            }
        } catch (PDOException $e) {
            echo "<p>❌ 執行失敗: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }
    
    echo "<h3>安裝完成！</h3>\n";
    echo "<p>預設管理員帳號：</p>\n";
    echo "<ul>\n";
    echo "<li>帳號：admin</li>\n";
    echo "<li>密碼：admin123</li>\n";
    echo "</ul>\n";
    echo "<p><a href='index.php'>前往系統首頁</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3>安裝失敗！</h3>\n";
    echo "<p>錯誤訊息：" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統安裝 - 鋒兒AI資訊系統</title>
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