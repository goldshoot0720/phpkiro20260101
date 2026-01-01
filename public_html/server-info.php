<?php
/**
 * 伺服器資訊頁面
 */
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>伺服器資訊 - 鋒兒AI資訊系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
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
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status.success {
            background: #48bb78;
            color: white;
        }
        .status.error {
            background: #f56565;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 鋒兒AI資訊系統 - 伺服器狀態</h1>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>📊 PHP 資訊</h3>
                <table>
                    <tr>
                        <th>PHP 版本</th>
                        <td><?= phpversion() ?> <span class="status success">✓ 正常</span></td>
                    </tr>
                    <tr>
                        <th>伺服器軟體</th>
                        <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server' ?></td>
                    </tr>
                    <tr>
                        <th>文件根目錄</th>
                        <td><?= $_SERVER['DOCUMENT_ROOT'] ?></td>
                    </tr>
                    <tr>
                        <th>伺服器時間</th>
                        <td><?= date('Y-m-d H:i:s') ?></td>
                    </tr>
                </table>
            </div>

            <div class="info-card">
                <h3>🔧 PHP 擴展檢查</h3>
                <table>
                    <tr>
                        <th>PDO</th>
                        <td>
                            <?php if (extension_loaded('pdo')): ?>
                                <span class="status success">✓ 已安裝</span>
                            <?php else: ?>
                                <span class="status error">✗ 未安裝</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>PDO MySQL</th>
                        <td>
                            <?php if (extension_loaded('pdo_mysql')): ?>
                                <span class="status success">✓ 已安裝</span>
                            <?php else: ?>
                                <span class="status error">✗ 未安裝</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>GD</th>
                        <td>
                            <?php if (extension_loaded('gd')): ?>
                                <span class="status success">✓ 已安裝</span>
                            <?php else: ?>
                                <span class="status error">✗ 未安裝</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Session</th>
                        <td>
                            <?php if (extension_loaded('session')): ?>
                                <span class="status success">✓ 已安裝</span>
                            <?php else: ?>
                                <span class="status error">✗ 未安裝</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="info-card">
                <h3>📁 目錄權限檢查</h3>
                <table>
                    <?php
                    $directories = [
                        'uploads' => 'uploads/',
                        'uploads/images' => 'uploads/images/',
                        'uploads/videos' => 'uploads/videos/'
                    ];
                    
                    foreach ($directories as $name => $path) {
                        $exists = is_dir($path);
                        $writable = $exists ? is_writable($path) : false;
                        echo "<tr>";
                        echo "<th>$name</th>";
                        echo "<td>";
                        if ($exists && $writable) {
                            echo '<span class="status success">✓ 可寫入</span>';
                        } elseif ($exists) {
                            echo '<span class="status error">✗ 無寫入權限</span>';
                        } else {
                            echo '<span class="status error">✗ 目錄不存在</span>';
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>

            <div class="info-card">
                <h3>🌐 連線資訊</h3>
                <table>
                    <tr>
                        <th>本地網址</th>
                        <td><a href="http://localhost:8888" target="_blank" style="color: #90cdf4;">http://localhost:8888</a></td>
                    </tr>
                    <tr>
                        <th>IP 位址</th>
                        <td><?= $_SERVER['SERVER_ADDR'] ?? '127.0.0.1' ?></td>
                    </tr>
                    <tr>
                        <th>端口</th>
                        <td><?= $_SERVER['SERVER_PORT'] ?? '8888' ?></td>
                    </tr>
                    <tr>
                        <th>協定</th>
                        <td><?= $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <h2>🔗 快速連結</h2>
        <div style="text-align: center; margin: 30px 0;">
            <a href="setup-guide.html" class="btn">📖 安裝指南</a>
            <a href="debug-db.php" class="btn">🔍 資料庫除錯</a>
            <a href="check-install.php" class="btn">📋 檢查安裝</a>
            <a href="migrate-data.php" class="btn btn-success">🔄 資料遷移</a>
            <a href="demo-data.php" class="btn">🎯 建立示範資料</a>
            <a href="index.php" class="btn">🏠 系統首頁</a>
        </div>

        <div style="text-align: center; margin-top: 40px; opacity: 0.8;">
            <p>鋒兒達習公開資訊 © 版權所有 2025 - 2125</p>
            <p>系統運行於 PHP <?= phpversion() ?></p>
        </div>
    </div>
</body>
</html>