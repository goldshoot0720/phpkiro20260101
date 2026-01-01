<?php
/**
 * 使用者認證相關函數
 */

/**
 * 使用者登入
 */
function loginUser($username, $password) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id, username, password, email, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        // 更新最後登入時間
        $updateStmt = $db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        return true;
    }
    
    return false;
}

/**
 * 使用者登出
 */
function logoutUser() {
    session_destroy();
    redirect('login.php');
}

/**
 * 註冊新使用者
 */
function registerUser($username, $password, $email, $role = 'user') {
    $db = Database::getInstance()->getConnection();
    
    // 檢查使用者名稱是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return '使用者名稱已存在';
    }
    
    // 檢查 email 是否已存在
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return 'Email 已被使用';
        }
    }
    
    // 建立新使用者
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$username, $hashedPassword, $email, $role])) {
        return true;
    }
    
    return '註冊失敗';
}

/**
 * 檢查登入狀態中介軟體
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * 檢查管理員權限中介軟體
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php?error=權限不足');
    }
}

/**
 * 變更密碼
 */
function changePassword($userId, $oldPassword, $newPassword) {
    $db = Database::getInstance()->getConnection();
    
    // 驗證舊密碼
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($oldPassword, $user['password'])) {
        return '舊密碼錯誤';
    }
    
    // 更新密碼
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt->execute([$hashedPassword, $userId])) {
        return true;
    }
    
    return '密碼更新失敗';
}

/**
 * 取得使用者資訊
 */
function getUserInfo($userId) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    return $stmt->fetch();
}

/**
 * 更新使用者資訊
 */
function updateUserInfo($userId, $email) {
    $db = Database::getInstance()->getConnection();
    
    // 檢查 email 是否已被其他使用者使用
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return 'Email 已被其他使用者使用';
        }
    }
    
    $stmt = $db->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt->execute([$email, $userId])) {
        $_SESSION['user_email'] = $email;
        return true;
    }
    
    return '更新失敗';
}
?>