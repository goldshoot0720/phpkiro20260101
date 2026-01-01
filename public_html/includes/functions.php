<?php
/**
 * 系統共用函數
 */

/**
 * 安全輸出 HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 重導向
 */
function redirect($url) {
    // 確保 URL 是相對路徑
    if (!str_starts_with($url, 'http') && !str_starts_with($url, '/')) {
        $url = '/' . $url;
    }
    header("Location: $url");
    exit;
}

/**
 * 檢查使用者是否已登入
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 檢查使用者是否為管理員
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * 格式化檔案大小
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 格式化日期
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * 計算日期差異
 */
function daysDifference($date1, $date2 = null) {
    if ($date2 === null) $date2 = date('Y-m-d');
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days * ($interval->invert ? -1 : 1);
}

/**
 * 生成隨機檔名
 */
function generateRandomFilename($extension) {
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * 驗證圖片檔案
 */
function validateImageFile($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 50 * 1024 * 1024; // 50MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return '不支援的圖片格式';
    }
    
    if ($file['size'] > $maxSize) {
        return '檔案大小超過限制';
    }
    
    return true;
}

/**
 * 驗證影片檔案
 */
function validateVideoFile($file) {
    $allowedTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo'];
    $maxSize = 500 * 1024 * 1024; // 500MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return '不支援的影片格式';
    }
    
    if ($file['size'] > $maxSize) {
        return '檔案大小超過限制';
    }
    
    return true;
}

/**
 * 取得食品狀態
 */
function getFoodStatus($expiryDate) {
    if (empty($expiryDate)) return 'normal';
    
    $days = daysDifference($expiryDate);
    
    if ($days < 0) {
        return 'expired';
    } elseif ($days <= 7) {
        return 'expiring';
    }
    
    return 'normal';
}

/**
 * 取得狀態顏色類別
 */
function getStatusClass($status) {
    switch ($status) {
        case 'expired':
            return 'text-red-500';
        case 'expiring':
            return 'text-yellow-500';
        case 'active':
            return 'text-green-500';
        default:
            return 'text-gray-500';
    }
}

/**
 * 分頁計算
 */
function calculatePagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * JSON 回應
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 錯誤回應
 */
function errorResponse($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

/**
 * 成功回應
 */
function successResponse($data = [], $message = '操作成功') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}
?>