<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 移除登入檢查，直接允許訪問

$db = Database::getInstance()->getConnection();

// 處理上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = 'uploads/images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $validation = validateImageFile($file);
    
    if ($validation === true) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = generateRandomFilename($extension);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // 取得圖片尺寸
            $imageInfo = getimagesize($filepath);
            $width = $imageInfo[0] ?? null;
            $height = $imageInfo[1] ?? null;
            
            // 儲存到資料庫
            $stmt = $db->prepare("INSERT INTO images (filename, original_name, file_path, file_size, mime_type, width, height, category, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $filename,
                $file['name'],
                $filepath,
                $file['size'],
                $file['type'],
                $width,
                $height,
                $_POST['category'] ?? '',
                $_POST['description'] ?? '',
                1 // 預設使用者 ID
            ]);
            
            $success = '圖片上傳成功！';
        } else {
            $error = '檔案上傳失敗';
        }
    } else {
        $error = $validation;
    }
}

// 取得圖片列表
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (original_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $whereClause .= " AND category = ?";
    $params[] = $category;
}

// 總數
$countStmt = $db->prepare("SELECT COUNT(*) FROM images $whereClause");
$countStmt->execute($params);
$totalImages = $countStmt->fetchColumn();

// 圖片列表
$stmt = $db->prepare("SELECT * FROM images $whereClause ORDER BY upload_date DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$images = $stmt->fetchAll();

// 分類列表
$categoryStmt = $db->query("SELECT DISTINCT category FROM images WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

$pagination = calculatePagination($totalImages, $perPage, $page);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>圖片庫 - 鋒兒AI資訊系統</title>
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
            <a href="images.php" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 text-white">
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
        <!-- 頂部 -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-images text-xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">鋒兒圖片庫</h1>
                        <p class="text-white text-opacity-80">智能的圖片管理和收藏系統 (241 張圖片)</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">
                    <i class="fas fa-plus mr-2"></i>
                    新增圖片
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
                        placeholder="搜尋圖片名稱..."
                        class="w-full px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60"
                    >
                </div>
                <div>
                    <select name="category" class="px-4 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                        <option value="">所有類型</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-search mr-2"></i>
                    搜尋
                </button>
            </form>
        </div>

        <!-- 圖片網格 -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
            <?php foreach ($images as $image): ?>
            <div class="glass-card rounded-lg overflow-hidden hover:scale-105 transition-transform cursor-pointer">
                <div class="aspect-square bg-gray-800 flex items-center justify-center">
                    <img src="<?= h($image['file_path']) ?>" alt="<?= h($image['original_name']) ?>" class="w-full h-full object-cover">
                </div>
                <div class="p-3">
                    <div class="text-sm font-medium truncate"><?= h($image['original_name']) ?></div>
                    <div class="text-xs text-white text-opacity-60 mt-1">
                        <?= formatFileSize($image['file_size']) ?>
                    </div>
                    <div class="text-xs text-white text-opacity-60">
                        <?= $image['width'] ?>x<?= $image['height'] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 分頁 -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="flex justify-center space-x-2">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
               class="px-4 py-2 glass-card rounded-lg hover:bg-white hover:bg-opacity-10">
                上一頁
            </a>
            <?php endif; ?>
            
            <span class="px-4 py-2 glass-card rounded-lg">
                第 <?= $page ?> 頁，共 <?= $pagination['total_pages'] ?> 頁
            </span>
            
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
               class="px-4 py-2 glass-card rounded-lg hover:bg-white hover:bg-opacity-10">
                下一頁
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 上傳模態框 -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="glass-card p-6 rounded-xl w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">上傳圖片</h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">選擇圖片</label>
                    <input type="file" name="image" accept="image/*" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">分類</label>
                    <input type="text" name="category" placeholder="例如：風景、人物、動物" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">描述</label>
                    <textarea name="description" rows="3" placeholder="圖片描述..." class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg">
                    <i class="fas fa-upload mr-2"></i>
                    上傳圖片
                </button>
            </form>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <script>
        alert('<?= $success ?>');
        window.location.reload();
    </script>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <script>
        alert('<?= $error ?>');
    </script>
    <?php endif; ?>
</body>
</html>