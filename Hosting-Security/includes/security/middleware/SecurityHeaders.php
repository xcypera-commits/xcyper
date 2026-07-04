<?php
/**
 * رؤوس الأمان
 * Security Headers
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityHeaders {
    
    /**
     * تطبيق جميع رؤوس الأمان
     */
    public static function apply() {
        self::preventXSS();
        self::preventSniffing();
        self::preventFraming();
        self::applyCSP();
        self::applyHSTS();
        self::removeServerInfo();
    }
    
    /**
     * منع هجمات XSS
     */
    private static function preventXSS() {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * منع تخمين نوع الملف
     */
    private static function preventSniffing() {
        header('X-Content-Type-Options: nosniff');
    }
    
    /**
     * منع التضمين في iframe
     */
    private static function preventFraming() {
        header('X-Frame-Options: SAMEORIGIN');
    }
    
    /**
     * تطبيق سياسة أمان المحتوى
     */
    private static function applyCSP() {
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net; ";
        $csp .= "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ";
        $csp .= "img-src 'self' data:; ";
        $csp .= "font-src 'self'; ";
        $csp .= "connect-src 'self'; ";
        $csp .= "frame-ancestors 'self'; ";
        $csp .= "form-action 'self'; ";
        $csp .= "base-uri 'self'; ";
        $csp .= "upgrade-insecure-requests;";
        
        header("Content-Security-Policy: " . $csp);
    }
    
    /**
     * تطبيق HSTS
     */
    private static function applyHSTS() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * إخفاء معلومات السيرفر
     */
    private static function removeServerInfo() {
        header_remove('X-Powered-By');
        header_remove('Server');
    }
    
    /**
     * تطبيق رؤوس إضافية للتخزين المؤقت
     */
    public static function applyCacheHeaders($seconds = 3600) {
        header("Cache-Control: public, max-age=$seconds");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    }
    
    /**
     * منع التخزين المؤقت (للصفحات الحساسة)
     */
    public static function preventCaching() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    }
}
?>