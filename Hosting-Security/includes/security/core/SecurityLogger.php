<?php
/**
 * نظام تسجيل الأحداث الأمنية
 * Security Logger
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityLogger {
    
    private static $logPath;
    private static $initialized = false;
    
    /**
     * تهيئة السجل
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        self::$logPath = __DIR__ . '/../../../logs/security/';
        
        // إنشاء المجلد إذا لم يكن موجوداً
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
        
        self::$initialized = true;
    }
    
    /**
     * تسجيل حدث
     */
    public static function log($type, $message, $data = []) {
        self::init();
        
        $logFile = self::$logPath . $type . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_id = $_SESSION['user_id'] ?? 0;
        $session_id = session_id() ?? 'no-session';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message,
            'user_id' => $user_id,
            'ip' => $ip,
            'session_id' => $session_id,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'data' => $data,
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // إذا كان حدث خطير، سجل في ملف منفصل
        if ($type === 'threat' || $type === 'incident') {
            file_put_contents(self::$logPath . 'critical.log', $logLine, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * تسجيل حدث أمني
     */
    public static function logSecurity($message, $level = 'info', $data = []) {
        self::log('security', "[$level] $message", $data);
    }
    
    /**
     * تسجيل تهديد
     */
    public static function logThreat($message, $threatType, $data = []) {
        $data['threat_type'] = $threatType;
        self::log('threat', $message, $data);
    }
    
    /**
     * تسجيل خطأ
     */
    public static function logError($message, $error, $data = []) {
        $data['error'] = $error;
        self::log('error', $message, $data);
    }
    
    /**
     * تسجيل وصول
     */
    public static function logAccess($action, $status = 'success', $data = []) {
        self::log('access', "$action - $status", $data);
    }
    
    /**
     * تسجيل رفع ملفات
     */
    public static function logUpload($filename, $status, $data = []) {
        $data['filename'] = $filename;
        self::log('uploads', "File upload: $status", $data);
    }
    
    /**
     * قراءة السجلات
     */
    public static function readLogs($type, $lines = 100) {
        self::init();
        
        $logFile = self::$logPath . $type . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen($logFile, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $logs[] = json_decode($line, true);
                if (count($logs) > $lines) {
                    array_shift($logs);
                }
            }
            fclose($handle);
        }
        
        return array_reverse($logs);
    }
    
    /**
     * تنظيف السجلات القديمة
     */
    public static function cleanOldLogs($days = 30) {
        self::init();
        
        $files = glob(self::$logPath . '*.log');
        $cutoff = time() - ($days * 86400);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
?>