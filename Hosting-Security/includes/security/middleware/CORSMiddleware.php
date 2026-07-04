<?php
/**
 * وسيط CORS
 * CORS Middleware
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class CORSMiddleware {
    
    private static $allowedOrigins = [
        'http://localhost',
        'http://localhost:3000',
        'https://yourdomain.com',
        'https://www.yourdomain.com'
    ];
    
    private static $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS'
    ];
    
    private static $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-Token'
    ];
    
    /**
     * تطبيق CORS
     */
    public static function apply() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // التحقق من الأصل المسموح به
        if (in_array($origin, self::$allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        }
        
        // التعامل مع طلبات OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: ' . implode(', ', self::$allowedMethods));
            header('Access-Control-Allow-Headers: ' . implode(', ', self::$allowedHeaders));
            header('Access-Control-Max-Age: 86400'); // 24 hours
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * إضافة أصل مسموح به
     */
    public static function addAllowedOrigin($origin) {
        if (!in_array($origin, self::$allowedOrigins)) {
            self::$allowedOrigins[] = $origin;
        }
    }
    
    /**
     * إضافة طريقة مسموح بها
     */
    public static function addAllowedMethod($method) {
        if (!in_array($method, self::$allowedMethods)) {
            self::$allowedMethods[] = $method;
        }
    }
    
    /**
     * إضافة رأس مسموح به
     */
    public static function addAllowedHeader($header) {
        if (!in_array($header, self::$allowedHeaders)) {
            self::$allowedHeaders[] = $header;
        }
    }
}
?>