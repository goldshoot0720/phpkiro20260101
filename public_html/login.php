<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 如果已登入，導向首頁
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '請輸入使用者名稱和密碼';
    } else {
        if (loginUser($username, $password)) {
            redirect('index.php');
        } else {
            $error = '使用者名稱或密碼錯誤';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - 鋒兒AI資訊系統</title>
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
<body class="flex items-center justify-center min-h-screen text-white">
    <div class="glass-card p-8 rounded-2xl w-full max-w-md">
        <!-- Logo 和標題 -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-robot text-2xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold mb-2">鋒兒AI資訊系統</h1>
            <p class="text-white text-opacity-80">歡迎使用智能資訊管理系統</p>
        </div>

        <!-- 錯誤訊息 -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= h($error) ?>
        </div>
        <?php endif; ?>

        <!-- 登入表單 -->
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium mb-2">
                    <i class="fas fa-user mr-2"></i>
                    使用者名稱
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?= h($_POST['username'] ?? '') ?>"
                    class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent text-white placeholder-white placeholder-opacity-60"
                    placeholder="請輸入使用者名稱"
                    required
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-2">
                    <i class="fas fa-lock mr-2"></i>
                    密碼
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent text-white placeholder-white placeholder-opacity-60"
                    placeholder="請輸入密碼"
                    required
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                登入系統
            </button>
        </form>

        <!-- 預設帳號提示 -->
        <div class="mt-8 p-4 bg-blue-500 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg">
            <h3 class="text-sm font-semibold mb-2 text-blue-200">
                <i class="fas fa-info-circle mr-2"></i>
                預設管理員帳號
            </h3>
            <div class="text-xs text-blue-100 space-y-1">
                <div>帳號：admin</div>
                <div>密碼：admin123</div>
            </div>
        </div>

        <!-- 系統資訊 -->
        <div class="mt-6 text-center text-xs text-white text-opacity-60">
            <p>鋒兒達習公開資訊 © 版權所有 2025 - 2125</p>
            <p class="mt-1">智能管理您的影片和圖片收藏</p>
        </div>
    </div>
</body>
</html>