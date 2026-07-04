<?php
/**
 * اتصال قاعدة البيانات
 */

// منع الوصول المباشر
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'security_monitoring_db');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // =============================================
    // إنشاء مستخدم افتراضي مؤقت إذا لم يكن موجوداً
    // =============================================
    
    // التحقق من وجود المستخدم admin
    $checkStmt = $db->prepare("SELECT id FROM users_all WHERE username = 'admin'");
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        // إنشاء مستخدم افتراضي
        $insertStmt = $db->prepare("
            INSERT INTO users_all (
                uuid, username, email, password, full_name, 
                user_source, source_id, user_type, role_id, status, 
                email_verified_at, created_at
            ) VALUES (
                UUID(), 'admin', 'admin@example.com', ?, 'مدير النظام',
                'system', 0, 'admin', 'admin', 'active',
                NOW(), NOW()
            )
        ");
        
        // كلمة المرور: admin123
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertStmt->execute([$hashedPassword]);
        
        echo "<!-- تم إنشاء المستخدم الافتراضي: admin / admin123 -->";
    }
    
    // التحقق من وجود مستخدم manager
    $checkStmt = $db->prepare("SELECT id FROM users_all WHERE username = 'manager'");
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        // إنشاء مستخدم مدير
        $insertStmt = $db->prepare("
            INSERT INTO users_all (
                uuid, username, email, password, full_name, 
                user_source, source_id, user_type, role_id, status, 
                email_verified_at, created_at
            ) VALUES (
                UUID(), 'manager', 'manager@example.com', ?, 'مدير الاستضافة',
                'system', 0, 'manager', 'manager', 'active',
                NOW(), NOW()
            )
        ");
        
        // كلمة المرور: manager123
        $hashedPassword = password_hash('manager123', PASSWORD_DEFAULT);
        $insertStmt->execute([$hashedPassword]);
    }
    
    // التحقق من وجود مستخدم client
    $checkStmt = $db->prepare("SELECT id FROM users_all WHERE username = 'client'");
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        // إنشاء مستخدم عميل
        $insertStmt = $db->prepare("
            INSERT INTO users_all (
                uuid, username, email, password, full_name, 
                user_source, source_id, user_type, role_id, status, 
                email_verified_at, created_at
            ) VALUES (
                UUID(), 'client', 'client@example.com', ?, 'عميل تجريبي',
                'system', 0, 'client', 'client', 'active',
                NOW(), NOW()
            )
        ");
        
        // كلمة المرور: client123
        $hashedPassword = password_hash('client123', PASSWORD_DEFAULT);
        $insertStmt->execute([$hashedPassword]);
    }
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("عذراً، حدث خطأ في الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً.");
}
?>