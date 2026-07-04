<?php
/**
 * سجل التدقيق المتقدم
 * Advanced Audit Trail
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class AuditTrail {
    
    private $logger;
    private $db;
    private $config;
    private $encryption;
    
    // مستويات الأحداث
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_SECURITY = 'security';
    
    // فئات الأحداث
    const CATEGORY_AUTH = 'authentication';
    const CATEGORY_AUTHORIZATION = 'authorization';
    const CATEGORY_DATA = 'data';
    const CATEGORY_FILE = 'file';
    const CATEGORY_USER = 'user';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_BACKUP = 'backup';
    const CATEGORY_ISOLATION = 'isolation';
    const CATEGORY_API = 'api';
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/security-config.php';
        $this->encryption = new EncryptionService();
        $this->initAuditSystem();
    }
    
    /**
     * تهيئة نظام التدقيق
     */
    private function initAuditSystem() {
        $auditDir = LOGS_PATH . 'audit/';
        if (!is_dir($auditDir)) {
            mkdir($auditDir, 0750, true);
        }
        
        // إنشاء ملفات التدقيق الشهرية
        $this->ensureMonthlyFiles();
    }
    
    /**
     * التأكد من وجود ملفات الشهر الحالي
     */
    private function ensureMonthlyFiles() {
        $month = date('Y-m');
        $files = [
            "audit_$month.json",
            "security_$month.json",
            "access_$month.json"
        ];
        
        foreach ($files as $file) {
            $path = LOGS_PATH . 'audit/' . $file;
            if (!file_exists($path)) {
                file_put_contents($path, json_encode([]));
                chmod($path, 0640);
            }
        }
    }
    
    /**
     * تسجيل حدث في سجل التدقيق
     */
    public function logEvent($category, $action, $details = [], $level = self::LEVEL_INFO) {
        $event = $this->buildEvent($category, $action, $details, $level);
        
        // حفظ في الملف الشهري
        $this->saveToFile($event);
        
        // حفظ في قاعدة البيانات إذا كانت متوفرة
        $this->saveToDatabase($event);
        
        // إذا كان حدث أمني، سجل في سجل منفصل
        if ($level === self::LEVEL_SECURITY || $level === self::LEVEL_CRITICAL) {
            $this->logSecurityEvent($event);
        }
        
        // تنبيه للأحداث الحرجة
        if ($level === self::LEVEL_CRITICAL) {
            $this->triggerCriticalAlert($event);
        }
        
        return $event['audit_id'];
    }
    
    /**
     * بناء كائن الحدث
     */
    private function buildEvent($category, $action, $details, $level) {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'system';
        $userRole = $_SESSION['user_role'] ?? 'system';
        
        // الحصول على معلومات إضافية
        $requestId = $this->getRequestId();
        $sessionId = session_id() ?: 'no-session';
        
        return [
            'audit_id' => $this->generateAuditId(),
            'timestamp' => date('Y-m-d H:i:s'),
            'microtime' => microtime(true),
            'category' => $category,
            'action' => $action,
            'level' => $level,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'role' => $userRole,
                'ip' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'session_id' => $sessionId
            ],
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'query' => $_GET,
                'request_id' => $requestId
            ],
            'details' => $this->sanitizeDetails($details),
            'server' => [
                'host' => gethostname(),
                'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown'
            ]
        ];
    }
    
    /**
     * توليد معرف فريد للحدث
     */
    private function generateAuditId() {
        return uniqid('audit_', true) . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * الحصول على معرف الطلب
     */
    private function getRequestId() {
        if (!isset($_SESSION['request_id'])) {
            $_SESSION['request_id'] = uniqid('req_', true);
        }
        return $_SESSION['request_id'];
    }
    
    /**
     * الحصول على IP العميل الحقيقي
     */
    private function getClientIP() {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * تنظيف التفاصيل من البيانات الحساسة
     */
    private function sanitizeDetails($details) {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth', 'credit_card'];
        
        array_walk_recursive($details, function(&$value, $key) use ($sensitiveFields) {
            foreach ($sensitiveFields as $field) {
                if (stripos($key, $field) !== false) {
                    $value = '[REDACTED]';
                    break;
                }
            }
        });
        
        return $details;
    }
    
    /**
     * حفظ في ملف
     */
    private function saveToFile($event) {
        $month = date('Y-m');
        $category = $event['category'];
        
        // تحديد الملف المناسب
        if ($event['level'] === self::LEVEL_SECURITY) {
            $file = LOGS_PATH . "audit/security_$month.json";
        } elseif ($category === self::CATEGORY_AUTH) {
            $file = LOGS_PATH . "audit/access_$month.json";
        } else {
            $file = LOGS_PATH . "audit/audit_$month.json";
        }
        
        // قراءة الملفات الموجودة
        $events = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $events = json_decode($content, true) ?: [];
        }
        
        // إضافة الحدث الجديد
        $events[] = $event;
        
        // الاحتفاظ بآخر 10000 حدث فقط
        if (count($events) > 10000) {
            $events = array_slice($events, -10000);
        }
        
        // حفظ مع تنسيق جميل
        file_put_contents($file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * حفظ في قاعدة البيانات
     */
    private function saveToDatabase($event) {
        // يمكن تنفيذها لاحقاً مع اتصال قاعدة البيانات
        // هذا مثال للهيكل
        /*
        $sql = "INSERT INTO audit_logs 
                (audit_id, timestamp, category, action, level, user_id, username, 
                 user_role, ip, user_agent, details, request_uri, request_method)
                VALUES 
                (:audit_id, :timestamp, :category, :action, :level, :user_id, :username,
                 :user_role, :ip, :user_agent, :details, :request_uri, :request_method)";
        */
    }
    
    /**
     * تسجيل حدث أمني
     */
    private function logSecurityEvent($event) {
        // تسجيل في سجل الأمان المنفصل
        $this->logger->log('audit_security', json_encode([
            'id' => $event['audit_id'],
            'action' => $event['action'],
            'user' => $event['user']['username'],
            'level' => $event['level']
        ]));
    }
    
    /**
     * إرسال تنبيه للأحداث الحرجة
     */
    private function triggerCriticalAlert($event) {
        $alertSystem = new AlertSystem();
        $alertSystem->sendAlert(
            'critical_audit',
            "حدث حرج: {$event['action']} - المستخدم: {$event['user']['username']}",
            'critical',
            $event
        );
    }
    
    /**
     * تسجيل دخول المستخدم
     */
    public function logLogin($userId, $username, $status, $reason = null) {
        $this->logEvent(
            self::CATEGORY_AUTH,
            'login',
            [
                'status' => $status,
                'reason' => $reason,
                'username' => $username,
                'user_id' => $userId
            ],
            $status === 'success' ? self::LEVEL_INFO : self::LEVEL_WARNING
        );
    }
    
    /**
     * تسجيل تسجيل خروج
     */
    public function logLogout($userId, $username) {
        $this->logEvent(
            self::CATEGORY_AUTH,
            'logout',
            [
                'user_id' => $userId,
                'username' => $username
            ],
            self::LEVEL_INFO
        );
    }
    
    /**
     * تسجيل تغيير كلمة المرور
     */
    public function logPasswordChange($userId, $username, $method) {
        $this->logEvent(
            self::CATEGORY_AUTH,
            'password_change',
            [
                'user_id' => $userId,
                'username' => $username,
                'method' => $method
            ],
            self::LEVEL_WARNING
        );
    }
    
    /**
     * تسجيل رفع ملف
     */
    public function logFileUpload($userId, $filename, $size, $status, $virusScan = null) {
        $this->logEvent(
            self::CATEGORY_FILE,
            'upload',
            [
                'filename' => $filename,
                'size' => $size,
                'status' => $status,
                'virus_scan' => $virusScan,
                'user_id' => $userId
            ],
            $status === 'success' ? self::LEVEL_INFO : self::LEVEL_WARNING
        );
    }
    
    /**
     * تسجيل حذف ملف
     */
    public function logFileDelete($userId, $filename, $reason = null) {
        $this->logEvent(
            self::CATEGORY_FILE,
            'delete',
            [
                'filename' => $filename,
                'reason' => $reason,
                'user_id' => $userId
            ],
            self::LEVEL_WARNING
        );
    }
    
    /**
     * تسجيل عزل حاوية
     */
    public function logContainerIsolation($clientId, $containerId, $reason) {
        $this->logEvent(
            self::CATEGORY_ISOLATION,
            'container_isolated',
            [
                'client_id' => $clientId,
                'container_id' => $containerId,
                'reason' => $reason
            ],
            self::LEVEL_CRITICAL
        );
    }
    
    /**
     * تسجيل كشف تهديد
     */
    public function logThreatDetection($threatType, $details, $severity) {
        $level = $severity === 'high' ? self::LEVEL_CRITICAL : self::LEVEL_SECURITY;
        
        $this->logEvent(
            self::CATEGORY_SECURITY,
            'threat_detected',
            [
                'threat_type' => $threatType,
                'details' => $details,
                'severity' => $severity
            ],
            $level
        );
    }
    
    /**
     * تسجيل نسخ احتياطي
     */
    public function logBackup($clientId, $backupId, $size, $status) {
        $this->logEvent(
            self::CATEGORY_BACKUP,
            'backup_' . $status,
            [
                'client_id' => $clientId,
                'backup_id' => $backupId,
                'size' => $size
            ],
            $status === 'success' ? self::LEVEL_INFO : self::LEVEL_ERROR
        );
    }
    
    /**
     * تسجيل استعادة نسخة
     */
    public function logRestore($clientId, $backupId, $status) {
        $this->logEvent(
            self::CATEGORY_BACKUP,
            'restore_' . $status,
            [
                'client_id' => $clientId,
                'backup_id' => $backupId
            ],
            $status === 'success' ? self::LEVEL_WARNING : self::LEVEL_ERROR
        );
    }
    
    /**
     * تسجيل تغيير صلاحيات
     */
    public function logPermissionChange($adminId, $targetUserId, $changes) {
        $this->logEvent(
            self::CATEGORY_AUTHORIZATION,
            'permission_change',
            [
                'admin_id' => $adminId,
                'target_user' => $targetUserId,
                'changes' => $changes
            ],
            self::LEVEL_WARNING
        );
    }
    
    /**
     * تسجيل خطأ نظام
     */
    public function logSystemError($error, $context = []) {
        $this->logEvent(
            self::CATEGORY_SYSTEM,
            'error',
            [
                'error' => $error,
                'context' => $context
            ],
            self::LEVEL_ERROR
        );
    }
    
    /**
     * تسجيل وصول API
     */
    public function logAPIRequest($endpoint, $method, $status, $responseTime) {
        $this->logEvent(
            self::CATEGORY_API,
            'request',
            [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $status,
                'response_time' => $responseTime
            ],
            $status >= 400 ? self::LEVEL_WARNING : self::LEVEL_INFO
        );
    }
}
?>