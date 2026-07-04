<?php
/**
 * الحماية من XSS
 * XSS Protection
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class XSSProtection {
    
    /**
     * تنظيف النص من هجمات XSS
     */
    public static function clean($input, $context = 'html') {
        if (is_array($input)) {
            return array_map([self::class, 'clean'], $input);
        }
        
        if ($input === null) {
            return '';
        }
        
        switch ($context) {
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'attribute':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            case 'javascript':
                return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            
            case 'css':
                return preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $input);
            
            case 'url':
                return urlencode($input);
            
            default:
                return strip_tags($input);
        }
    }
    
    /**
     * التحقق من وجود هجمات XSS
     */
    public static function detectXSS($input) {
        if (is_array($input)) {
            foreach ($input as $value) {
                if (self::detectXSS($value)) {
                    return true;
                }
            }
            return false;
        }
        
        $patterns = [
            '/<script.*>.*<\/script>/is',
            '/javascript:/i',
            '/onerror\s*=/i',
            '/onload\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/expression\s*\(/i',
            '/vbscript:/i',
            '/data:\s*text\/html/i',
            '/document\.cookie/i',
            '/document\.write/i',
            '/eval\s*\(/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * تنظيف كامل للإدخال
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        // إزالة أي تاغات HTML
        $input = strip_tags($input);
        
        // تحويل الأحرف الخاصة
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // إزالة أي محاولات هروب
        $input = stripslashes($input);
        
        return trim($input);
    }
    
    /**
     * تنظيف للإخراج في HTML
     */
    public static function escapeForHTML($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * تنظيف للإخراج في JSON
     */
    public static function escapeForJSON($input) {
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * تنظيف للإخراج في URL
     */
    public static function escapeForURL($input) {
        return urlencode($input);
    }
}
?>