<?php
/**
 * وسيط الأمان الرئيسي
 * Security Middleware
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityMiddleware {
    
    /**
     * تنفيذ جميع فحوصات الأمان
     */
    public static function run() {
        // التحقق من حظر IP
        if (ActivityMonitor::isIPBlocked($_SERVER['REMOTE_ADDR'])) {
            http_response_code(403);
            die('Access denied. Your IP has been blocked.');
        }
        
        // فحص التهديدات في الطلب
        $detector = new ThreatDetector();
        $threats = $detector->scanRequest();
        
        if ($threats['has_threats'] && $threats['score'] > 10) {
            http_response_code(403);
            die('Request blocked due to security threats.');
        }
        
        // التحقق من CSRF لطلبات POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFProtection::validatePostRequest()) {
                http_response_code(403);
                die('Invalid CSRF token.');
            }
        }
        
        // التحقق من الجلسة
        self::validateSession();
        
        // تطبيق رؤوس الأمان
        SecurityHeaders::apply();
    }
    
    /**
     * التحقق من صحة الجلسة
     */
    public static function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        // التحقق من انتهاء الجلسة
        $lifetime = 7200; // 2 ساعة
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $lifetime)) {
            session_unset();
            session_destroy();
            return;
        }
        
        // تحديث وقت النشاط
        $_SESSION['last_activity'] = time();
        
        // التحقق من تغيير IP
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            // تسجيل خرق أمني محتمل
            SecurityLogger::logThreat('Session IP mismatch', 'session_hijack', [
                'session_ip' => $_SESSION['user_ip'],
                'current_ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            session_unset();
            session_destroy();
        }
        
        // التحقق من تغيير User Agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            SecurityLogger::logThreat('Session User Agent mismatch', 'session_hijack');
            session_unset();
            session_destroy();
        }
    }
    
    /**
     * التحقق من الصلاحيات
     */
    public static function checkPermission($requiredRole) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // يمكن تنفيذها لاحقاً مع نظام الصلاحيات
        return true;
    }
}
?>