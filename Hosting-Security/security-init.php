<?php
/**
 * ملف تهيئة نظام الحماية - 🟢 نسخة مفتوحة مؤقتاً 🟢
 */

// منع الوصول المباشر
if (!defined('SECURITY_ACCESS')) {
    define('SECURITY_ACCESS', true);
}

// تحديد المسارات الأساسية
define('ROOT_PATH', __DIR__ . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('SECURITY_PATH', INCLUDES_PATH . 'security/');
define('LOGS_PATH', ROOT_PATH . 'logs/security/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('BACKUPS_PATH', ROOT_PATH . 'backups/');
define('HOSTING_PATH', ROOT_PATH . 'hosting/');
define('CONFIG_PATH', ROOT_PATH . 'config/');

// إنشاء المجلدات الضرورية
$directories = [
    LOGS_PATH,
    UPLOADS_PATH . 'temp/',
    UPLOADS_PATH . 'quarantine/',
    UPLOADS_PATH . 'clean/',
    BACKUPS_PATH,
    HOSTING_PATH . 'clients/',
    HOSTING_PATH . 'containers/',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
// تحميل الإعدادات
$config_file = SECURITY_PATH . 'config/security-config.php';
if (file_exists($config_file)) {
    $GLOBALS['security_config'] = require $config_file;
}

// دالة التحميل التلقائي للكلاسات
function autoloadSecurityClasses($className) {
    $paths = [
        SECURITY_PATH . 'core/',
        SECURITY_PATH . 'authentication/',
        SECURITY_PATH . 'authorization/',
        SECURITY_PATH . 'encryption/',
        SECURITY_PATH . 'isolation/',
        SECURITY_PATH . 'firewall/',
        SECURITY_PATH . 'monitoring/',
        SECURITY_PATH . 'detection/',
        SECURITY_PATH . 'validation/',
        SECURITY_PATH . 'audit/',
        SECURITY_PATH . 'backup/',
        SECURITY_PATH . 'middleware/',
        SECURITY_PATH . 'responses/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
}

spl_autoload_register('autoloadSecurityClasses');

// تهيئة السجل
SecurityLogger::init();

// تطبيق رؤوس الأمان
SecurityHeaders::apply();

// التحقق من الجلسة
SecurityMiddleware::validateSession();

// التحقق من حظر IP
if (ActivityMonitor::isIPBlocked($_SERVER['REMOTE_ADDR'] ?? '')) {
    http_response_code(403);
    die('⚠️ الوصول محظور. عنوان IP الخاص بك محظور.');
}

// تطبيق تحديد المعدل
if (in_array($_SERVER['REQUEST_URI'] ?? '', ['/pages/HOM/index.php', '/pages/admin/staff_login.php'])) {
    RateLimitMiddleware::apply(5, 300);
}

// فحص التهديدات
$detector = new ThreatDetector();
$threats = $detector->scanRequest();

if ($threats['has_threats'] && $threats['score'] > 20) {
    SecurityLogger::logThreat('طلب مشبوه', 'high_score', $threats);
    
    if ($threats['score'] > 30) {
        ActivityMonitor::blockIP($_SERVER['REMOTE_ADDR'] ?? '', 'تهديد عالي الخطورة');
        http_response_code(403);
        die('⚠️ تم حظر الطلب لأسباب أمنية.');
    }
}

// تفعيل الجدار الناري
try {
    $firewall = new FirewallManager();
    $firewall->inspectRequest();
    $firewall->applyWAFRules();
} catch (Exception $e) {
    SecurityLogger::log('error', 'خطأ في الجدار الناري: ' . $e->getMessage());
}

// التحقق من صحة الطلب
try {
    $validator = new RequestValidator();
    $validator->validate();
} catch (Exception $e) {
    SecurityLogger::log('error', 'خطأ في مدقق الطلبات: ' . $e->getMessage());
}

// تسجيل دخول الصفحة
ActivityMonitor::logPageView();
*/

// =============================================
// ✅ المؤقتاً شغال بس
// =============================================

// تضمين دوال المساعدة العامة
if (file_exists(INCLUDES_PATH . 'functions.php')) {
    require_once INCLUDES_PATH . 'functions.php';
}

if (file_exists(INCLUDES_PATH . 'auth.php')) {
    require_once INCLUDES_PATH . 'auth.php';
}

if (file_exists(INCLUDES_PATH . 'client_functions.php')) {
    require_once INCLUDES_PATH . 'client_functions.php';
}

if (file_exists(INCLUDES_PATH . 'admin_functions.php')) {
    require_once INCLUDES_PATH . 'admin_functions.php';
}

// الاتصال بقاعدة البيانات
if (file_exists(CONFIG_PATH . 'database.php')) {
    require_once CONFIG_PATH . 'database.php';
}

// تسجيل وقت التهيئة
define('SECURITY_INIT_TIME', microtime(true));
?>