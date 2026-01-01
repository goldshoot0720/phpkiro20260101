<?php
/**
 * 安裝狀態檢查
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // 檢查所有必要的資料表
    $requiredTables = ['users', 'images', 'videos', 'foods', 'subscriptions', 'system_settings'];
    $existingTables = [];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->fetch()) {
            $existingTables[] = $table;
        } else {
            $missingTables[] = $table;
        }
    }
    
    $isInstalled = empty($missingTables);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $isInstalled = false;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安裝檢查 - 鋒兒AI資訊系統</title>
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
        h1, h2 {
            color: #fff;
            text-align: center;
        }
        .status {
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            font-size: 1.2em;
        }
        .status.success {
            background: rgba(72, 187, 120, 0.3);
            border: 2px solid #48bb78;
        }
        .status.error {
            background: rgba(245, 101, 101, 0.3);
            border: 2px solid #f56565;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
        }
        .btn-warning {
            background: #ed8936;
        }
        .btn-warning:hover {
            background: #dd6b20;
        }
        ul {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 鋒兒AI資訊系統 - 安裝檢查</h1>
        
        <?php if (isset($error)): ?>
            <div class="status error">
                <h2>❌ 資料庫連線失敗</h2>
                <p>錯誤訊息：<?= htmlspecialchars($error) ?></p>
                <p><a href="debug-db.php" class="btn btn-warning">🔍 資料庫除錯</a></p>
            </div>
        <?php elseif ($isInstalled): ?>
            <div class="status success">
                <h2>✅ 系統已安裝完成</h2>
                <p>所有必要的資料表都已建立，系統可以正常使用！</p>
                <p>
                    <a href="index.php" class="btn btn-success">🏠 進入系統</a>
                    <a href="demo-data.php" class="btn">🎯 建立示範資料</a>
                </p>
            </div>
            
            <h3>✅ 已建立的資料表：</h3>
            <ul>
                <?php foreach ($existingTables as $table): ?>
                <li>✓ <?= $table ?></li>
                <?php endforeach; ?>
            </ul>
            
        <?php else: ?>
            <div class="status error">
                <h2>⚠️ 系統尚未安裝</h2>
                <p>部分資料表尚未建立，請執行安裝程序。</p>
                <p><a href="install.php" class="btn btn-success">⚡ 立即安裝</a></p>
            </div>
            
            <?php if (!empty($existingTables)): ?>
            <h3>✅ 已建立的資料表：</h3>
            <ul>
                <?php foreach ($existingTables as $table): ?>
                <li>✓ <?= $table ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            
            <?php if (!empty($missingTables)): ?>
            <h3>❌ 缺少的資料表：</h3>
            <ul>
                <?php foreach ($missingTables as $table): ?>
                <li>✗ <?= $table ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="server-info.php" class="btn">📊 伺服器資訊</a>
            <a href="setup-guide.html" class="btn">📖 安裝指南</a>
        </div>
        
        <div style="text-align: center; margin-top: 40px; opacity: 0.8;">
            <p>鋒兒達習公開資訊 © 版權所有 2025 - 2125</p>
        </div>
    </div>
</body>
</html>