<?php
/**
 * تحديد معدل الطلبات
 * Rate Limit Middleware
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class RateLimitMiddleware {
    
    private static $limits = [];
    private static $storage = [];
    
    /**
     * التحقق من معدل الطلبات
     */
    public static function check($key = null, $limit = 60, $window = 60) {
        if ($key === null) {
            $key = $_SERVER['REMOTE_ADDR'];
        }
        
        self::loadStorage();
        
        $now = time();
        $windowStart = $now - $window;
        
        // تنظيف الطلبات القديمة
        self::$storage[$key] = array_filter(self::$storage[$key] ?? [], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        $count = count(self::$storage[$key] ?? []);
        
        if ($count >= $limit) {
            self::saveStorage();
            
            // تسجيل محاولة تجاوز الحد
            SecurityLogger::logThreat('Rate limit exceeded', 'rate_limit', [
                'key' => $key,
                'count' => $count,
                'limit' => $limit,
                'window' => $window
            ]);
            
            return false;
        }
        
        // تسجيل الطلب الحالي
        self::$storage[$key][] = $now;
        self::saveStorage();
        
        return true;
    }
    
    /**
     * تطبيق تحديد المعدل
     */
    public static function apply($limit = 60, $window = 60) {
        if (!self::check(null, $limit, $window)) {
            http_response_code(429);
            header('Retry-After: ' . $window);
            die('Too many requests. Please try again later.');
        }
    }
    
    /**
     * تحميل التخزين
     */
    private static function loadStorage() {
        $file = LOGS_PATH . 'rate_limits.json';
        
        if (file_exists($file)) {
            self::$storage = json_decode(file_get_contents($file), true) ?: [];
        }
    }
    
    /**
     * حفظ التخزين
     */
    private static function saveStorage() {
        $file = LOGS_PATH . 'rate_limits.json';
        
        // تنظيف التخزين القديم (أكثر من ساعة)
        $cutoff = time() - 3600;
        foreach (self::$storage as $key => $timestamps) {
            self::$storage[$key] = array_filter($timestamps, function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            });
            
            if (empty(self::$storage[$key])) {
                unset(self::$storage[$key]);
            }
        }
        
        file_put_contents($file, json_encode(self::$storage, JSON_PRETTY_PRINT));
    }
    
    /**
     * الحصول على عدد الطلبات المتبقية
     */
    public static function getRemaining($key = null, $limit = 60, $window = 60) {
        if ($key === null) {
            $key = $_SERVER['REMOTE_ADDR'];
        }
        
        self::loadStorage();
        
        $now = time();
        $windowStart = $now - $window;
        
        $count = count(array_filter(self::$storage[$key] ?? [], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        }));
        
        return max(0, $limit - $count);
    }
}
?>