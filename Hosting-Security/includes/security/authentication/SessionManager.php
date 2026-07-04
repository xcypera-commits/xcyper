<?php
/**
 * مدير الجلسات
 * Session Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SessionManager {
    
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/security-config.php';
        
        $this->configureSession();
    }
    
    /**
     * تهيئة إعدادات الجلسة
     */
    private function configureSession() {
        // إعدادات أمان الجلسة
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', $this->config['general']['session_lifetime']);
        ini_set('session.cookie_lifetime', 0);
        
        // اسم جلسة مخصص
        session_name($this->config['general']['session_name'] ?? 'secure_session');
    }
    
    /**
     * بدء جلسة آمنة
     */
    public function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // تجديد معرف الجلسة لمنع fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['created_at'] = time();
            }
        }
        
        $this->validateSession();
    }
    
    /**
     * التحقق من صحة الجلسة
     */
    private function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        // التحقق من IP
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            $this->destroySession('IP mismatch');
            return;
        }
        
        // التحقق من User Agent
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->destroySession('User Agent mismatch');
            return;
        }
        
        // التحقق من انتهاء الجلسة
        $maxLifetime = $this->config['general']['session_lifetime'];
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxLifetime)) {
            $this->destroySession('Session expired');
            return;
        }
        
        // تحديث وقت النشاط
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * تدمير الجلسة
     */
    private function destroySession($reason) {
        $userId = $_SESSION['user_id'] ?? 'unknown';
        
        $this->logger->log('session', "Session destroyed for user $userId: $reason");
        
        $_SESSION = [];
        session_destroy();
    }
    
    /**
     * الحصول على بيانات الجلسة
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * تعيين قيمة في الجلسة
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * حذف قيمة من الجلسة
     */
    public function delete($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * التحقق من وجود قيمة
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * الحصول على معرف الجلسة
     */
    public function getSessionId() {
        return session_id();
    }
    
    /**
     * تجديد معرف الجلسة
     */
    public function regenerateId() {
        session_regenerate_id(true);
        $this->logger->log('session', 'Session ID regenerated');
    }
    
    /**
     * الحصول على جميع بيانات الجلسة
     */
    public function getAll() {
        return $_SESSION;
    }
    
    /**
     * تنظيف الجلسة
     */
    public function clear() {
        $_SESSION = [];
    }
}
?>