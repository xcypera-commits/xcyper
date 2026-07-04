<?php
/**
 * الحماية من CSRF
 * CSRF Protection
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class CSRFProtection {
    
    /**
     * إنشاء توكن CSRF جديد
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600; // ساعة واحدة
        
        $_SESSION['csrf_tokens'][$token] = $expires;
        
        // تنظيف التوكن المنتهية
        self::cleanExpiredTokens();
        
        return $token;
    }
    
    /**
     * التحقق من صحة توكن CSRF
     */
    public static function validateToken($token) {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            SecurityLogger::logThreat('Invalid CSRF token', 'csrf', ['token' => $token]);
            return false;
        }
        
        $expires = $_SESSION['csrf_tokens'][$token];
        
        if (time() > $expires) {
            unset($_SESSION['csrf_tokens'][$token]);
            SecurityLogger::logThreat('Expired CSRF token', 'csrf', ['token' => $token]);
            return false;
        }
        
        // استخدام مرة واحدة
        unset($_SESSION['csrf_tokens'][$token]);
        
        return true;
    }
    
    /**
     * الحصول على حقل CSRF مخفي للنماذج
     */
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * تنظيف التوكن المنتهية
     */
    private static function cleanExpiredTokens() {
        $now = time();
        foreach ($_SESSION['csrf_tokens'] as $token => $expires) {
            if ($now > $expires) {
                unset($_SESSION['csrf_tokens'][$token]);
            }
        }
    }
    
    /**
     * التحقق من طلب POST
     */
    public static function validatePostRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token)) {
            SecurityLogger::logThreat('Missing CSRF token', 'csrf');
            return false;
        }
        
        return self::validateToken($token);
    }
}
?>