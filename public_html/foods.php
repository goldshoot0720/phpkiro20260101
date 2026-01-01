<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 移除登入檢查，直接允許訪問

$db = Database::getInstance()->getConnection();

// 處理新增食品
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO foods (name, brand, category, price, purchase_date, expiry_date, quantity, unit, storage_location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['name'],
        $_POST['brand'],
        $_POST['category'],
        $_POST['price'] ?: null,
        $_POST['purchase_date'] ?: null,
        $_POST['expiry_date'] ?: null,
        $_POST['quantity'] ?: 1,
        $_POST['unit'] ?: '個',
        $_POST['storage_location'],
        $_POST['notes']
    ]);
    
    $success = '食品新增成功！';
}

// 取得食品列表
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND name LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $whereClause .= " AND category = ?";
    $params[] = $category;
}

if (!empty($status)) {
    switch ($status) {
        case 'expiring':
            $whereClause .= " AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'expired':
            $whereClause .= " AND expiry_date < CURDATE()";
            break;
    }
}

$stmt = $db->prepare("SELECT * FROM foods $whereClause ORDER BY expiry_date ASC");
$stmt->execute($params);
$foods = $stmt->fetchAll();

// 分類列表
$categoryStmt = $db->query("SELECT DISTINCT category FROM foods WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>食品管理 - 鋒兒AI資訊系統</title>
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
            <a href="subscriptions.php" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-10 text-white">
                <i class="fas fa-calendar-alt mr-3"></i>
                訂閱管理
            </a>
            <a href="foods.php" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 text-white">
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
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-apple-alt text-xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">食品管理系統</h1>
                        <p class="text-white text-opacity-80">管理您的食品存貨和到期狀況</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">
                    <i class="fas fa-plus mr-2"></i>
                    新增食品
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
                        placeholder="搜尋食品名稱..."
                        class="w-full px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60"
                    >
                </div>
                <div>
                    <select name="category" class="px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                        <option value="">所有分類</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select name="status" class="px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                        <option value="">所有狀態</option>
                        <option value="expiring" <?= $status === 'expiring' ? 'selected' : '' ?>>即將到期</option>
                        <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>已過期</option>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-search mr-2"></i>
                    搜尋
                </button>
            </form>
        </div>

        <!-- 食品列表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($foods as $food): 
                $daysLeft = $food['expiry_date'] ? daysDifference($food['expiry_date']) : null;
                $statusClass = '';
                $statusText = '正常';
                
                if ($daysLeft !== null) {
                    if ($daysLeft < 0) {
                        $statusClass = 'border-red-500 bg-red-500 bg-opacity-20';
                        $statusText = '已過期';
                    } elseif ($daysLeft <= 3) {
                        $statusClass = 'border-red-400 bg-red-400 bg-opacity-20';
                        $statusText = '即將到期';
                    } elseif ($daysLeft <= 7) {
                        $statusClass = 'border-yellow-400 bg-yellow-400 bg-opacity-20';
                        $statusText = '注意';
                    } else {
                        $statusClass = 'border-green-400 bg-green-400 bg-opacity-20';
                    }
                }
            ?>
            <div class="glass-card p-6 rounded-xl <?= $statusClass ?>">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold"><?= h($food['name']) ?></h3>
                        <?php if ($food['brand']): ?>
                        <p class="text-sm text-white text-opacity-70"><?= h($food['brand']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <div class="text-sm"><?= $food['quantity'] ?> <?= h($food['unit']) ?></div>
                        <?php if ($food['price']): ?>
                        <div class="text-xs text-white text-opacity-70">NT$ <?= number_format($food['price']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($food['expiry_date']): ?>
                <div class="mb-3">
                    <div class="text-sm">到期日期: <?= formatDate($food['expiry_date']) ?></div>
                    <?php if ($daysLeft !== null): ?>
                    <div class="text-xs <?= $daysLeft < 0 ? 'text-red-400' : ($daysLeft <= 7 ? 'text-yellow-400' : 'text-green-400') ?>">
                        <?php if ($daysLeft < 0): ?>
                            已過期 <?= abs($daysLeft) ?> 天
                        <?php elseif ($daysLeft === 0): ?>
                            今天到期
                        <?php else: ?>
                            還有 <?= $daysLeft ?> 天
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($food['storage_location']): ?>
                <div class="text-sm text-white text-opacity-70 mb-2">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    <?= h($food['storage_location']) ?>
                </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center">
                    <span class="text-xs px-2 py-1 bg-white bg-opacity-20 rounded">
                        <?= h($food['category'] ?: '未分類') ?>
                    </span>
                    <div class="flex space-x-2">
                        <button class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-400 hover:text-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 新增食品模態框 -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="glass-card p-6 rounded-xl w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">新增食品</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">食品名稱 *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">品牌</label>
                    <input type="text" name="brand" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">分類</label>
                    <input type="text" name="category" placeholder="例如：零食、飲料、冷凍食品" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">價格</label>
                    <input type="number" name="price" step="0.01" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">購買日期</label>
                    <input type="date" name="purchase_date" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">到期日期</label>
                    <input type="date" name="expiry_date" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">數量</label>
                    <input type="number" name="quantity" value="1" min="1" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">單位</label>
                    <input type="text" name="unit" value="個" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">存放位置</label>
                    <input type="text" name="storage_location" placeholder="例如：冰箱、櫥櫃、冷凍庫" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">備註</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>
                        新增食品
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