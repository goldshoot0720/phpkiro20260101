<?php
// Simple test script to check if PHP is working
echo "PHP is working!<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP version: " . phpversion() . "<br>";

// Test if we can connect to database
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Database connection: SUCCESS<br>";
} catch (PDOException $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

echo "<br><a href='index.php'>Go to main page</a>";
?>