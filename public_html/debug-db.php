<?php
/**
 * 資料庫連線除錯工具
 */

echo "<h2>資料庫連線除錯資訊</h2>";

// 顯示伺服器環境變數
echo "<h3>伺服器環境變數：</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>變數名稱</th><th>值</th></tr>";

$serverVars = ['HTTP_HOST', 'SERVER_NAME', 'SERVER_PORT', 'SERVER_SOFTWARE', 'DOCUMENT_ROOT'];
foreach ($serverVars as $var) {
    $value = $_SERVER[$var] ?? '(未設定)';
    echo "<tr><td>$var</td><td>$value</td></tr>";
}
echo "</table>";

// 環境檢測邏輯
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

$isLocal = (
    strpos($host, 'localhost') !== false || 
    strpos($host, '127.0.0.1') !== false ||
    strpos($serverName, 'localhost') !== false ||
    strpos($serverName, '127.0.0.1') !== false ||
    $_SERVER['SERVER_PORT'] == '8888'
);

echo "<h3>環境檢測結果：</h3>";
echo "<p><strong>檢測為本地環境：</strong> " . ($isLocal ? '是' : '否') . "</p>";

if ($isLocal) {
    echo "<h4>使用本地資料庫設定：</h4>";
    echo "<ul>";
    echo "<li>主機: localhost</li>";
    echo "<li>使用者: root</li>";
    echo "<li>密碼: (空白)</li>";
    echo "<li>資料庫: hsin_php</li>";
    echo "</ul>";
} else {
    echo "<h4>使用遠端資料庫設定：</h4>";
    echo "<ul>";
    echo "<li>主機: localhost</li>";
    echo "<li>使用者: hsin_php</li>";
    echo "<li>密碼: ym0Tagood129</li>";
    echo "<li>資料庫: hsin_php</li>";
    echo "</ul>";
}

// 測試資料庫連線
echo "<h3>資料庫連線測試：</h3>";

try {
    if ($isLocal) {
        $dsn = "mysql:host=localhost;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "<p style='color: green;'>✓ 本地 MySQL 連線成功</p>";
        
        // 檢查資料庫是否存在
        $stmt = $pdo->query("SHOW DATABASES LIKE 'hsin_php'");
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ 資料庫 'hsin_php' 存在</p>";
        } else {
            echo "<p style='color: orange;'>⚠ 資料庫 'hsin_php' 不存在，需要建立</p>";
            
            // 嘗試建立資料庫
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS hsin_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<p style='color: green;'>✓ 已自動建立資料庫 'hsin_php'</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ 無法建立資料庫: " . $e->getMessage() . "</p>";
            }
        }
        
    } else {
        $dsn = "mysql:host=localhost;dbname=hsin_php;charset=utf8mb4";
        $pdo = new PDO($dsn, 'hsin_php', 'ym0Tagood129', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "<p style='color: green;'>✓ 遠端 MySQL 連線成功</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ 資料庫連線失敗: " . $e->getMessage() . "</p>";
    
    // 提供解決建議
    echo "<h4>解決建議：</h4>";
    echo "<ul>";
    if ($isLocal) {
        echo "<li>確認 MySQL 服務是否已啟動</li>";
        echo "<li>確認 root 使用者可以無密碼登入</li>";
        echo "<li>嘗試在命令列執行: <code>mysql -u root -p</code></li>";
    } else {
        echo "<li>確認遠端資料庫使用者 'hsin_php' 是否存在</li>";
        echo "<li>確認密碼是否正確</li>";
        echo "<li>確認使用者權限是否足夠</li>";
    }
    echo "</ul>";
}

echo "<p><a href='server-info.php'>返回伺服器資訊</a> | <a href='index.php'>返回首頁</a></p>";
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料庫除錯 - 鋒兒AI資訊系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2, h3, h4 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        a {
            color: #007cba;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
</body>
</html>