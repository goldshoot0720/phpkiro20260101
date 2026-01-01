<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 移除登入檢查，直接允許訪問

$db = Database::getInstance()->getConnection();

// 處理上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $uploadDir = 'uploads/videos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['video'];
    $validation = validateVideoFile($file);
    
    if ($validation === true) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = generateRandomFilename($extension);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // 儲存到資料庫
            $stmt = $db->prepare("INSERT INTO videos (filename, original_name, file_path, file_size, mime_type, category, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $filename,
                $file['name'],
                $filepath,
                $file['size'],
                $file['type'],
                $_POST['category'] ?? '',
                $_POST['description'] ?? '',
                1 // 預設使用者 ID
            ]);
            
            $success = '影片上傳成功！';
        } else {
            $error = '檔案上傳失敗';
        }
    } else {
        $error = $validation;
    }
}

// 取得影片列表
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

$stmt = $db->prepare("SELECT * FROM videos $whereClause ORDER BY upload_date DESC");
$stmt->execute($params);
$videos = $stmt->fetchAll();

// 分類列表
$categoryStmt = $db->query("SELECT DISTINCT category FROM videos WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>影片庫 - 鋒兒AI資訊系統</title>
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
            <a href="videos.php" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 text-white">
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
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-video text-xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">鋒兒影片庫</h1>
                        <p class="text-white text-opacity-80">智能的影片管理和收藏系統</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">
                    <i class="fas fa-plus mr-2"></i>
                    新增影片
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
                        placeholder="搜尋影片名稱..."
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

        <!-- 影片列表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($videos as $video): ?>
            <div class="glass-card rounded-xl overflow-hidden hover:scale-105 transition-transform">
                <div class="aspect-video bg-gray-800 flex items-center justify-center relative">
                    <video class="w-full h-full object-cover" controls>
                        <source src="<?= h($video['file_path']) ?>" type="<?= h($video['mime_type']) ?>">
                        您的瀏覽器不支援影片播放
                    </video>
                    <div class="absolute top-2 right-2 bg-black bg-opacity-50 px-2 py-1 rounded text-xs">
                        MP4
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold mb-2 truncate"><?= h($video['original_name']) ?></h3>
                    <?php if ($video['description']): ?>
                    <p class="text-sm text-white text-opacity-70 mb-2 line-clamp-2"><?= h($video['description']) ?></p>
                    <?php endif; ?>
                    <div class="flex justify-between items-center text-xs text-white text-opacity-60">
                        <span><?= formatFileSize($video['file_size']) ?></span>
                        <span><?= formatDate($video['upload_date']) ?></span>
                    </div>
                    <?php if ($video['category']): ?>
                    <div class="mt-2">
                        <span class="inline-block px-2 py-1 bg-purple-500 bg-opacity-30 rounded text-xs">
                            <?= h($video['category']) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($videos)): ?>
        <div class="text-center py-12">
            <i class="fas fa-video text-6xl text-white text-opacity-30 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">尚無影片</h3>
            <p class="text-white text-opacity-70">開始上傳您的第一個影片吧！</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 上傳模態框 -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="glass-card p-6 rounded-xl w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">上傳影片</h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">選擇影片</label>
                    <input type="file" name="video" accept="video/*" required class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white">
                    <div class="text-xs text-white text-opacity-60 mt-1">
                        支援格式：MP4, AVI, MOV, WMV (最大 500MB)
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">分類</label>
                    <input type="text" name="category" placeholder="例如：教學、娛樂、紀錄" class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">描述</label>
                    <textarea name="description" rows="3" placeholder="影片描述..." class="w-full px-3 py-2 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white placeholder-white placeholder-opacity-60"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 rounded-lg">
                    <i class="fas fa-upload mr-2"></i>
                    上傳影片
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