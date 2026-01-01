<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 移除登入檢查，直接允許訪問

$db = Database::getInstance()->getConnection();

// 檢查資料表是否存在
try {
    $stmt = $db->query("SHOW TABLES LIKE 'subscriptions'");
    if (!$stmt->fetch()) {
        // 資料表不存在，重導向到安裝頁面
        header("Location: setup-guide.html");
        exit;
    }
} catch (Exception $e) {
    // 資料庫連線或查詢失敗，重導向到除錯頁面
    header("Location: debug-db.php");
    exit;
}

// 取得統計資料
$stats = [];

// 訂閱統計
try {
    $stmt = $db->query("SELECT 
        COUNT(*) as total_subscriptions,
        SUM(CASE WHEN DATEDIFF(next_billing_date, CURDATE()) <= 3 AND status = 'active' THEN 1 ELSE 0 END) as expiring_3days,
        SUM(CASE WHEN DATEDIFF(next_billing_date, CURDATE()) <= 7 AND status = 'active' THEN 1 ELSE 0 END) as expiring_7days,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM subscriptions");
    $subscriptionStats = $stmt->fetch();
} catch (Exception $e) {
    $subscriptionStats = ['total_subscriptions' => 0, 'expiring_3days' => 0, 'expiring_7days' => 0, 'cancelled' => 0];
}

// 食品統計
try {
    $stmt = $db->query("SELECT 
        COUNT(*) as total_foods,
        SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) <= 3 AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring_3days,
        SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) <= 7 AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring_7days,
        SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) <= 30 AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring_30days,
        SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired
        FROM foods");
    $foodStats = $stmt->fetch();
} catch (Exception $e) {
    $foodStats = ['total_foods' => 0, 'expiring_3days' => 0, 'expiring_7days' => 0, 'expiring_30days' => 0, 'expired' => 0];
}

// 即將到期的訂閱
try {
    $stmt = $db->prepare("SELECT service_name, next_billing_date, price, DATEDIFF(next_billing_date, CURDATE()) as days_left 
        FROM subscriptions 
        WHERE status = 'active' AND DATEDIFF(next_billing_date, CURDATE()) <= 7 
        ORDER BY next_billing_date ASC LIMIT 5");
    $stmt->execute();
    $upcomingSubscriptions = $stmt->fetchAll();
} catch (Exception $e) {
    $upcomingSubscriptions = [];
}

// 即將到期的食品
try {
    $stmt = $db->prepare("SELECT name, expiry_date, DATEDIFF(expiry_date, CURDATE()) as days_left 
        FROM foods 
        WHERE expiry_date >= CURDATE() AND DATEDIFF(expiry_date, CURDATE()) <= 7 
        ORDER BY expiry_date ASC LIMIT 5");
    $stmt->execute();
    $expiringFoods = $stmt->fetchAll();
} catch (Exception $e) {
    $expiringFoods = [];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鋒兒AI資訊系統 - 系統儀表板</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="text-white">
    <!-- 側邊欄 -->
    <div class="fixed left-0 top-0 h-full w-64 glass-card p-6">
        <div class="flex items-center mb-8">
            <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-robot text-white"></i>
            </div>
            <span class="text-xl font-bold">鋒兒AI系統</span>
        </div>
        
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 text-white">
                <i class="fas fa-home mr-3"></i>
                內頁
            </a>
            <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
                <i class="fas fa-chart-bar mr-3"></i>
                儀表板
            </a>
            <a href="images.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
                <i class="fas fa-images mr-3"></i>
                圖片庫
            </a>
            <a href="videos.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
                <i class="fas fa-video mr-3"></i>
                影片庫
            </a>
            <a href="subscriptions.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
                <i class="fas fa-calendar-alt mr-3"></i>
                訂閱管理
            </a>
            <a href="foods.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
                <i class="fas fa-apple-alt mr-3"></i>
                食品管理
            </a>
        </nav>
    </div>

    <!-- 主要內容 -->
    <div class="ml-64 p-8">
        <!-- 頂部標題 -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold mb-2">系統儀表板</h1>
                <p class="text-white text-opacity-80">即時監控您的訂閱和食品到期狀況 - 無需登入直接使用</p>
            </div>
            <div class="flex items-center space-x-4">
                <button class="glass-card px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <i class="fas fa-sync-alt mr-2"></i>
                    刷新數據
                </button>
            </div>
        </div>

        <!-- 統計卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- 訂閱管理統計 -->
            <div class="glass-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">訂閱管理</h3>
                    <i class="fas fa-calendar-alt text-2xl text-yellow-400"></i>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm">總訂閱數</span>
                        <span class="font-bold text-2xl"><?= $subscriptionStats['total_subscriptions'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">3天內到期</span>
                        <span class="text-red-400 font-bold"><?= $subscriptionStats['expiring_3days'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">7天內到期</span>
                        <span class="text-yellow-400 font-bold"><?= $subscriptionStats['expiring_7days'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">已過期</span>
                        <span class="text-gray-400"><?= $subscriptionStats['cancelled'] ?></span>
                    </div>
                </div>
            </div>

            <!-- 食品管理統計 -->
            <div class="glass-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">食品管理</h3>
                    <i class="fas fa-apple-alt text-2xl text-green-400"></i>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm">總食品數</span>
                        <span class="font-bold text-2xl"><?= $foodStats['total_foods'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">3天內到期</span>
                        <span class="text-red-400 font-bold"><?= $foodStats['expiring_3days'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">7天內到期</span>
                        <span class="text-yellow-400 font-bold"><?= $foodStats['expiring_7days'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">30天內到期</span>
                        <span class="text-orange-400"><?= $foodStats['expiring_30days'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">已過期</span>
                        <span class="text-gray-400"><?= $foodStats['expired'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 提醒區域 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 訂閱到期提醒 -->
            <?php if (!empty($upcomingSubscriptions)): ?>
            <div class="glass-card p-6 rounded-xl">
                <div class="flex items-center mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-3"></i>
                    <h3 class="text-lg font-semibold">訂閱到期提醒</h3>
                </div>
                <div class="space-y-3">
                    <?php foreach ($upcomingSubscriptions as $sub): ?>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <div>
                            <div class="font-medium"><?= h($sub['service_name']) ?></div>
                            <div class="text-sm text-white text-opacity-70">NT$ <?= number_format($sub['price']) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm"><?= formatDate($sub['next_billing_date']) ?></div>
                            <div class="text-xs <?= $sub['days_left'] <= 3 ? 'text-red-400' : 'text-yellow-400' ?>">
                                <?= $sub['days_left'] ?> 天後
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 食品到期提醒 -->
            <?php if (!empty($expiringFoods)): ?>
            <div class="glass-card p-6 rounded-xl">
                <div class="flex items-center mb-4">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                    <h3 class="text-lg font-semibold">食品到期提醒</h3>
                </div>
                <div class="space-y-3">
                    <?php foreach ($expiringFoods as $food): ?>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <div class="font-medium"><?= h($food['name']) ?></div>
                        <div class="text-right">
                            <div class="text-sm"><?= formatDate($food['expiry_date']) ?></div>
                            <div class="text-xs <?= $food['days_left'] <= 3 ? 'text-red-400' : 'text-yellow-400' ?>">
                                <?= $food['days_left'] ?> 天後到期
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 系統資訊 -->
        <div class="mt-8 text-center">
            <div class="glass-card p-6 rounded-xl inline-block">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-robot text-2xl text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">鋒兒AI資訊系統</h2>
                        <p class="text-white text-opacity-80">智能管理您的影片和圖片收藏，支援智能分類和快速搜尋</p>
                        <p class="text-sm text-green-300 mt-2">✨ 無需登入，直接使用所有功能</p>
                    </div>
                </div>
                
                <div class="text-sm text-white text-opacity-60 mb-4">
                    鋒兒達習公開資訊 © 版權所有 2025 - 2125
                </div>
                
                <div class="grid grid-cols-2 gap-8 text-sm">
                    <div>
                        <h4 class="text-orange-400 font-semibold mb-2">前端技術</h4>
                        <ul class="space-y-1 text-white text-opacity-80">
                            <li>• SolidJS (SolidStart)</li>
                            <li>• 網頁互動版 Netlify</li>
                            <li>• 響應式設計 + Tailwind CSS</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-pink-400 font-semibold mb-2">後端技術</h4>
                        <ul class="space-y-1 text-white text-opacity-80">
                            <li>• Strapi CMS</li>
                            <li>• 多平台發佈 Strapi</li>
                            <li>• RESTful API 支援</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>