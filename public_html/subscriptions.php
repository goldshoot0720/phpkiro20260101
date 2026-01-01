<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 移除登入檢查，直接允許訪問

$db = Database::getInstance()->getConnection();

// 處理新增訂閱
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // 計算下次帳單日期
    $startDate = $_POST['start_date'];
    $billingCycle = $_POST['billing_cycle'];
    
    $nextBillingDate = $startDate;
    switch ($billingCycle) {
        case 'monthly':
            $nextBillingDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
            break;
        case 'yearly':
            $nextBillingDate = date('Y-m-d', strtotime($startDate . ' +1 year'));
            break;
        case 'weekly':
            $nextBillingDate = date('Y-m-d', strtotime($startDate . ' +1 week'));
            break;
    }
    
    $stmt = $db->prepare("INSERT INTO subscriptions (service_name, website_url, price, billing_cycle, start_date, next_billing_date, category, notes, reminder_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['service_name'],
        $_POST['website_url'],
        $_POST['price'],
        $_POST['billing_cycle'],
        $_POST['start_date'],
        $nextBillingDate,
        $_POST['category'],
        $_POST['notes'],
        $_POST['reminder_days'] ?: 3
    ]);
    
    $success = '訂閱新增成功！';
}

// 取得訂閱列表
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND service_name LIKE ?";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $whereClause .= " AND status = ?";
    $params[] = $status;
}

$stmt = $db->prepare("SELECT *, DATEDIFF(next_billing_date, CURDATE()) as days_left FROM subscriptions $whereClause ORDER BY next_billing_date ASC");
$stmt->execute($params);
$subscriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂閱管理 - 鋒兒AI資訊系統</title>
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
            <a href="index.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
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
            <a href="subscriptions.php" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 text-white">
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
        <!-- 頂部 -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-alt text-xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">訂閱管理系統</h1>
                        <p class="text-white text-opacity-80">管理您的各種訂閱服務和帳單資訊</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">
                    <i class="fas fa-plus mr-2"></i>
                    新增訂閱
                </button>
            </div>
        </div>

        <!-- 搜尋和篩選 -->
        <div class="glass-card p-6 rounded-xl mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= h($search) ?>"
                        placeholder="搜尋訂閱名稱..."
                        class="w-full px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60"
                    >
                </div>
                <div>
                    <select name="status" class="px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                        <option value="">所有狀態</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用中</option>
                        <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>暫停</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-search mr-2"></i>
                    搜尋
                </button>
            </form>
        </div>

        <!-- 訂閱列表 -->
        <div class="space-y-4">
            <?php foreach ($subscriptions as $sub): 
                $statusClass = '';
                $statusIcon = '';
                
                switch ($sub['status']) {
                    case 'active':
                        if ($sub['days_left'] <= 3) {
                            $statusClass = 'border-red-500 bg-red-500 bg-opacity-20';
                            $statusIcon = 'fas fa-exclamation-triangle text-red-400';
                        } elseif ($sub['days_left'] <= 7) {
                            $statusClass = 'border-yellow-500 bg-yellow-500 bg-opacity-20';
                            $statusIcon = 'fas fa-clock text-yellow-400';
                        } else {
                            $statusClass = 'border-green-500 bg-green-500 bg-opacity-20';
                            $statusIcon = 'fas fa-check-circle text-green-400';
                        }
                        break;
                    case 'paused':
                        $statusClass = 'border-gray-500 bg-gray-500 bg-opacity-20';
                        $statusIcon = 'fas fa-pause text-gray-400';
                        break;
                    case 'cancelled':
                        $statusClass = 'border-red-500 bg-red-500 bg-opacity-20';
                        $statusIcon = 'fas fa-times-circle text-red-400';
                        break;
                }
            ?>
            <div class="glass-card p-6 rounded-xl <?= $statusClass ?>">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <h3 class="text-xl font-semibold mr-3"><?= h($sub['service_name']) ?></h3>
                            <i class="<?= $statusIcon ?>"></i>
                            <?php if ($sub['status'] === 'active' && $sub['days_left'] <= 7): ?>
                            <span class="ml-2 text-sm px-2 py-1 bg-yellow-500 bg-opacity-30 rounded text-yellow-200">
                                即將到期
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($sub['website_url']): ?>
                        <div class="text-sm text-white text-opacity-70 mb-2">
                            網站: <a href="<?= h($sub['website_url']) ?>" target="_blank" class="text-blue-300 hover:text-blue-200"><?= h($sub['website_url']) ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div>
                                <span class="text-white text-opacity-70">價格:</span>
                                <span class="font-semibold">NT$ <?= number_format($sub['price']) ?></span>
                            </div>
                            <div>
                                <span class="text-white text-opacity-70">週期:</span>
                                <span><?= 
                                    $sub['billing_cycle'] === 'monthly' ? '每月' : 
                                    ($sub['billing_cycle'] === 'yearly' ? '每年' : 
                                    ($sub['billing_cycle'] === 'weekly' ? '每週' : $sub['billing_cycle'])) 
                                ?></span>
                            </div>
                            <div>
                                <span class="text-white text-opacity-70">下次帳單:</span>
                                <span><?= formatDate($sub['next_billing_date']) ?></span>
                            </div>
                            <?php if ($sub['status'] === 'active'): ?>
                            <div>
                                <span class="text-white text-opacity-70">剩餘天數:</span>
                                <span class="<?= $sub['days_left'] <= 3 ? 'text-red-400' : ($sub['days_left'] <= 7 ? 'text-yellow-400' : 'text-green-400') ?>">
                                    <?= $sub['days_left'] ?> 天
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-lg text-sm">
                            編輯
                        </button>
                        <button class="px-4 py-2 bg-red-500 hover:bg-red-600 rounded-lg text-sm">
                            刪除
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 新增訂閱模態框 -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="glass-card p-6 rounded-xl w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">新增訂閱</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">服務名稱 *</label>
                    <input type="text" name="service_name" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">網站網址</label>
                    <input type="url" name="website_url" placeholder="https://example.com" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">價格 *</label>
                    <input type="number" name="price" step="0.01" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">計費週期 *</label>
                    <select name="billing_cycle" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                        <option value="monthly">每月</option>
                        <option value="yearly">每年</option>
                        <option value="weekly">每週</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">開始日期 *</label>
                    <input type="date" name="start_date" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">提醒天數</label>
                    <input type="number" name="reminder_days" value="3" min="1" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">分類</label>
                    <input type="text" name="category" placeholder="例如：娛樂、工具、學習" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">備註</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>
                        新增訂閱
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <script>
        alert('<?= $success ?>');
        window.location.reload();
    </script>
    <?php endif; ?>
</body>
</html>