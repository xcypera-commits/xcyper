<?php
/**
 * تحديد معدل الطلبات
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class RateLimiter {
    private $storage = [];
    private $db;
    
    public function __construct($db = null) {
        $this->db = $db;
        $this->loadStorage();
    }
    
    /**
     * التحقق من معدل الطلبات
     */
    public function check($key, $limit = 60, $window = 60) {
        $now = time();
        $windowStart = $now - $window;
        
        // تنظيف الطلبات القديمة
        $this->storage[$key] = array_filter($this->storage[$key] ?? [], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        $count = count($this->storage[$key] ?? []);
        
        if ($count >= $limit) {
            $this->saveStorage();
            return false;
        }
        
        // تسجيل الطلب الحالي
        $this->storage[$key][] = $now;
        $this->saveStorage();
        
        return true;
    }
    
    /**
     * الحصول على عدد الطلبات المتبقية
     */
    public function getRemaining($key, $limit = 60, $window = 60) {
        $now = time();
        $windowStart = $now - $window;
        
        $count = count(array_filter($this->storage[$key] ?? [], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        }));
        
        return max(0, $limit - $count);
    }
    
    /**
     * الحصول على وقت إعادة التعيين
     */
    public function getResetTime($key, $window = 60) {
        $timestamps = $this->storage[$key] ?? [];
        if (empty($timestamps)) {
            return 0;
        }
        
        $oldest = min($timestamps);
        return $oldest + $window;
    }
    
    /**
     * تحميل التخزين
     */
    private function loadStorage() {
        $file = __DIR__ . '/../../../logs/security/rate_limits.json';
        
        if (file_exists($file)) {
            $this->storage = json_decode(file_get_contents($file), true) ?: [];
        }
    }
    
    /**
     * حفظ التخزين
     */
    private function saveStorage() {
        $file = __DIR__ . '/../../../logs/security/rate_limits.json';
        
        // تنظيف التخزين القديم (أكثر من ساعة)
        $cutoff = time() - 3600;
        foreach ($this->storage as $key => $timestamps) {
            $this->storage[$key] = array_filter($timestamps, function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            });
            
            if (empty($this->storage[$key])) {
                unset($this->storage[$key]);
            }
        }
        
        file_put_contents($file, json_encode($this->storage, JSON_PRETTY_PRINT));
    }
    
    /**
     * تنظيف التخزين القديم
     */
    public function clean() {
        $this->saveStorage();
    }
}
?>