<?php
/**
 * 資料庫連線設定
 */

// 環境檢測 - 改進版本
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

// 檢測是否為本地環境
$isLocal = (
    strpos($host, 'localhost') !== false || 
    strpos($host, '127.0.0.1') !== false ||
    strpos($serverName, 'localhost') !== false ||
    strpos($serverName, '127.0.0.1') !== false ||
    $_SERVER['SERVER_PORT'] == '8888' // PHP 內建伺服器
);

if ($isLocal) {
    // 本地測試環境
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'hsin_php');
} else {
    // 遠端上線環境
    define('DB_HOST', 'localhost');
    define('DB_USER', 'hsin_php');
    define('DB_PASS', 'ym0Tagood129');
    define('DB_NAME', 'hsin_php');
}

define('DB_CHARSET', 'utf8mb4');

/**
 * 資料庫連線類別
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("資料庫連線失敗: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>