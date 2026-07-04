<?php
// config.php - ملف تكوين الاتصال بقاعدة البيانات

// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // اتركه فارغاً إذا لم تضع كلمة مرور
define('DB_NAME', 'security_monitoring_db');

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

?>