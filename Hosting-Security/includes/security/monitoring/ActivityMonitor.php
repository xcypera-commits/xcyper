<?php
/**
 * مراقبة النشاطات
 * Activity Monitor
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class ActivityMonitor {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
    }
    
    /**
     * تسجيل دخول الصفحة
     */
    public static function logPageView() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        $activity = [
            'user_id' => $_SESSION['user_id'],
            'page' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // حفظ في قاعدة البيانات أو ملف
        $logFile = LOGS_PATH . 'access.log';
        file_put_contents($logFile, json_encode($activity) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * مراقبة محاولات تسجيل الدخول الفاشلة
     */
    public function trackFailedLogin($username) {
        $key = 'failed_login_' . $_SERVER['REMOTE_ADDR'];
        $attempts = $_SESSION[$key] ?? 0;
        $_SESSION[$key] = $attempts + 1;
        
        $this->logger->log('auth', "Failed login attempt for user: $username", [
            'attempts' => $attempts + 1,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        // قفل IP بعد محاولات كثيرة
        if ($_SESSION[$key] >= 5) {
            $this->blockIP($_SERVER['REMOTE_ADDR'], 'Too many failed login attempts');
        }
    }
    
    /**
     * مراقبة رفع الملفات
     */
    public function trackFileUpload($userId, $filename, $size) {
        $this->logger->log('upload', "User $userId uploaded file: $filename", [
            'size' => $size,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    /**
     * مراقبة التغييرات الحساسة
     */
    public function trackSensitiveChange($userId, $action, $details) {
        $this->logger->log('audit', "User $userId performed: $action", $details);
    }
    
    /**
     * حظر IP
     */
    private function blockIP($ip, $reason) {
        $blocked = [];
        $blockFile = CONFIG_PATH . '/blocked_ips.json';
        
        if (file_exists($blockFile)) {
            $blocked = json_decode(file_get_contents($blockFile), true);
        }
        
        $blocked[$ip] = [
            'reason' => $reason,
            'blocked_at' => date('Y-m-d H:i:s'),
            'expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];
        
        file_put_contents($blockFile, json_encode($blocked, JSON_PRETTY_PRINT));
        
        $this->logger->logThreat("IP $ip blocked", 'brute_force', ['reason' => $reason]);
    }
    
    /**
     * التحقق من حظر IP
     */
    public static function isIPBlocked($ip) {
        $blockFile = LOGS_PATH . '/blocked_ips.json';
        
        if (!file_exists($blockFile)) {
            return false;
        }
        
        $blocked = json_decode(file_get_contents($blockFile), true);
        
        if (!isset($blocked[$ip])) {
            return false;
        }
        
        // التحقق من انتهاء الحظر
        if (strtotime($blocked[$ip]['expires']) < time()) {
            unset($blocked[$ip]);
            file_put_contents($blockFile, json_encode($blocked, JSON_PRETTY_PRINT));
            return false;
        }
        
        return true;
    }
    
    /**
     * الحصول على إحصائيات النشاط
     */
    public function getActivityStats($userId = null, $days = 7) {
        $logFile = LOGS_PATH . 'access.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $stats = [
            'total_views' => 0,
            'unique_pages' => [],
            'daily_activity' => []
        ];
        
        $handle = fopen($logFile, 'r');
        $cutoff = strtotime("-$days days");
        
        while (($line = fgets($handle)) !== false) {
            $activity = json_decode($line, true);
            
            if (!$activity) continue;
            
            if ($userId && $activity['user_id'] != $userId) continue;
            
            if (strtotime($activity['timestamp']) < $cutoff) continue;
            
            $stats['total_views']++;
            
            $date = substr($activity['timestamp'], 0, 10);
            $stats['daily_activity'][$date] = ($stats['daily_activity'][$date] ?? 0) + 1;
            
            $page = $activity['page'];
            $stats['unique_pages'][$page] = ($stats['unique_pages'][$page] ?? 0) + 1;
        }
        
        fclose($handle);
        
        return $stats;
    }
}
?>