<?php
/**
 * دوال مساعدة عامة
 */

// منع الوصول المباشر
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}

/**
 * تنظيف المدخلات
 */


/**
 * التحقق من طريقة الطلب
 */

/**
 * التحقق من طريقة الطلب GET
 */


/**
 * إعادة التوجيه
 */


/**
 * إنشاء رابط آمن
 */


/**
 * عرض رسالة خطأ
 */


/**
 * عرض رسالة نجاح
 */


/**
 * عرض رسالة تحذير
 */
function set_warning($message) {
    $_SESSION['warning'] = $message;
}

/**
 * عرض جميع الرسائل
 */


/**
 * تنسيق التاريخ
 */


/**
 * تنسيق الحجم
 */
function format_size($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * توليد رمز عشوائي
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * تسجيل نشاط
 */
function log_activity($user_id, $action, $details = []) {
    global $db;
    
    try {
        $stmt = $db->prepare("INSERT INTO user_events 
            (event_id, user_id, event_type, action, description, ip_address, created_at)
            VALUES (UUID(), ?, 'admin_action', ?, ?, ?, NOW())");
        
        $description = json_encode($details, JSON_UNESCAPED_UNICODE);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt->execute([$user_id, $action, $description, $ip]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}
?>