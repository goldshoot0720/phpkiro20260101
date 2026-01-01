<?php
echo "<h2>資料庫連線測試</h2>";

// 環境檢測
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

echo "<p>環境: " . ($isLocal ? '本地' : '遠端') . "</p>";
echo "<p>主機: " . htmlspecialchars($host) . "</p>";

if ($isLocal) {
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'hsin_php';
} else {
    $dbHost = 'localhost';
    $dbUser = 'hsin_php';
    $dbPass = 'ym0Tagood129';
    $dbName = 'hsin_php';
}

try {
    // 先測試不指定資料庫的連線
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<p>✅ MySQL 連線成功</p>";
    
    // 檢查資料庫是否存在
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->fetch()) {
        echo "<p>✅ 資料庫 '$dbName' 存在</p>";
        
        // 連接到指定資料庫
        $pdo->exec("USE $dbName");
        
        // 檢查資料表
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>✅ 找到 " . count($tables) . " 個資料表</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>⚠️ 資料庫存在但沒有資料表</p>";
            echo "<p><a href='migrate-data.php'>執行資料遷移</a></p>";
        }
        
    } else {
        echo "<p>❌ 資料庫 '$dbName' 不存在</p>";
        echo "<p><a href='migrate-data.php'>執行資料遷移建立資料庫</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ 資料庫連線失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href='index.php'>返回首頁</a>";
?>