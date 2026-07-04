<?php
namespace HostingSystem\Database;

class AuditDatabase extends \PDO {
    private $auditEnabled = true;
    private $currentUser = null;
    
    public function __construct() {
        parent::__construct(
            'mysql:host=' . getenv('AUDIT_DB_HOST') . 
            ';dbname=' . getenv('AUDIT_DB_NAME') . 
            ';charset=utf8mb4',
            getenv('AUDIT_DB_USER'),
            getenv('AUDIT_DB_PASS'),
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_PERSISTENT => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
        
        // إنشاء جداول التدقيق إذا لم تكن موجودة
        $this->createAuditTables();
    }
    
    /**
     * إنشاء جداول التدقيق
     */
    private function createAuditTables(): void {
        $tables = [
            // جدول الأحداث الأمنية
            'audit_events' => "
                CREATE TABLE IF NOT EXISTS audit_events (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    event_category VARCHAR(50) NOT NULL,
                    user_id INT UNSIGNED NULL,
                    user_ip VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    resource_type VARCHAR(50) NULL,
                    resource_id VARCHAR(100) NULL,
                    action VARCHAR(100) NOT NULL,
                    old_value JSON NULL,
                    new_value JSON NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
                    status ENUM('success', 'failure', 'pending') DEFAULT 'success',
                    metadata JSON NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_event_type (event_type),
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at),
                    INDEX idx_severity (severity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // جدول محاولات الدخول
            'audit_logins' => "
                CREATE TABLE IF NOT EXISTS audit_logins (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL,
                    user_id INT UNSIGNED NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    country_code CHAR(2),
                    user_agent TEXT,
                    success TINYINT(1) DEFAULT 0,
                    failure_reason VARCHAR(100) NULL,
                    mfa_used TINYINT(1) DEFAULT 0,
                    session_id VARCHAR(100),
                    login_duration INT UNSIGNED NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_success (success),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // جدول التغييرات على البيانات
            'audit_data_changes' => "
                CREATE TABLE IF NOT EXISTS audit_data_changes (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    table_name VARCHAR(100) NOT NULL,
                    record_id VARCHAR(100) NOT NULL,
                    operation ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
                    changed_by INT UNSIGNED NULL,
                    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    old_data JSON NULL,
                    new_data JSON NULL,
                    diff JSON NULL,
                    change_reason VARCHAR(255) NULL,
                    INDEX idx_table_record (table_name, record_id),
                    INDEX idx_changed_by (changed_by),
                    INDEX idx_operation (operation),
                    INDEX idx_changed_at (changed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // جدول تنفيذ الصلاحيات
            'audit_permissions' => "
                CREATE TABLE IF NOT EXISTS audit_permissions (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    permission VARCHAR(100) NOT NULL,
                    resource_type VARCHAR(50) NULL,
                    resource_id VARCHAR(100) NULL,
                    granted_by INT UNSIGNED NULL,
                    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    revoked_by INT UNSIGNED NULL,
                    revoked_at TIMESTAMP NULL,
                    reason TEXT NULL,
                    INDEX idx_user_id (user_id),
                    INDEX idx_permission (permission),
                    INDEX idx_granted_at (granted_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // جدول الأمان النظامي
            'audit_system' => "
                CREATE TABLE IF NOT EXISTS audit_system (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    component VARCHAR(100) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    status ENUM('success', 'warning', 'error', 'critical') NOT NULL,
                    message TEXT,
                    details JSON NULL,
                    server_ip VARCHAR(45),
                    executed_by INT UNSIGNED NULL,
                    execution_time_ms INT UNSIGNED,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_component (component),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $table => $sql) {
            try {
                $this->exec($sql);
            } catch (\PDOException $e) {
                error_log("Failed to create audit table {$table}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * تسجيل حدث تدقيق
     */
    public function logEvent(array $event): bool {
        if (!$this->auditEnabled) {
            return false;
        }
        
        $sql = "
            INSERT INTO audit_events 
            (event_type, event_category, user_id, user_ip, user_agent, 
             resource_type, resource_id, action, old_value, new_value,
             severity, status, metadata)
            VALUES 
            (:event_type, :event_category, :user_id, :user_ip, :user_agent,
             :resource_type, :resource_id, :action, :old_value, :new_value,
             :severity, :status, :metadata)
        ";
        
        $stmt = $this->prepare($sql);
        
        return $stmt->execute([
            ':event_type' => $event['event_type'] ?? 'unknown',
            ':event_category' => $event['category'] ?? 'general',
            ':user_id' => $event['user_id'] ?? null,
            ':user_ip' => $this->getClientIP(),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':resource_type' => $event['resource_type'] ?? null,
            ':resource_id' => $event['resource_id'] ?? null,
            ':action' => $event['action'] ?? '',
            ':old_value' => isset($event['old_value']) ? json_encode($event['old_value']) : null,
            ':new_value' => isset($event['new_value']) ? json_encode($event['new_value']) : null,
            ':severity' => $event['severity'] ?? 'low',
            ':status' => $event['status'] ?? 'success',
            ':metadata' => isset($event['metadata']) ? json_encode($event['metadata']) : null
        ]);
    }
    
    /**
     * تسجيل محاولة دخول
     */
    public function logLoginAttempt(array $data): bool {
        $sql = "
            INSERT INTO audit_logins 
            (username, user_id, ip_address, country_code, user_agent,
             success, failure_reason, mfa_used, session_id, login_duration)
            VALUES 
            (:username, :user_id, :ip_address, :country_code, :user_agent,
             :success, :failure_reason, :mfa_used, :session_id, :login_duration)
        ";
        
        $stmt = $this->prepare($sql);
        
        return $stmt->execute([
            ':username' => $data['username'] ?? '',
            ':user_id' => $data['user_id'] ?? null,
            ':ip_address' => $this->getClientIP(),
            ':country_code' => $this->getCountryFromIP($this->getClientIP()),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':success' => $data['success'] ? 1 : 0,
            ':failure_reason' => $data['failure_reason'] ?? null,
            ':mfa_used' => $data['mfa_used'] ? 1 : 0,
            ':session_id' => session_id(),
            ':login_duration' => $data['duration'] ?? null
        ]);
    }
    
    /**
     * تسجيل تغيير بيانات
     */
    public function logDataChange(string $table, string $recordId, string $operation, $oldData, $newData, $userId = null): bool {
        // حساب الفروق
        $diff = $this->calculateDiff($oldData, $newData);
        
        $sql = "
            INSERT INTO audit_data_changes 
            (table_name, record_id, operation, changed_by, old_data, new_data, diff)
            VALUES 
            (:table_name, :record_id, :operation, :changed_by, :old_data, :new_data, :diff)
        ";
        
        $stmt = $this->prepare($sql);
        
        return $stmt->execute([
            ':table_name' => $table,
            ':record_id' => $recordId,
            ':operation' => $operation,
            ':changed_by' => $userId ?? $this->currentUser,
            ':old_data' => $oldData ? json_encode($oldData) : null,
            ':new_data' => $newData ? json_encode($newData) : null,
            ':diff' => $diff ? json_encode($diff) : null
        ]);
    }
    
    /**
     * استعلام الأحداث حسب الفترة
     */
    public function getEventsByPeriod(\DateTime $from, \DateTime $to, array $filters = []): array {
        $sql = "SELECT * FROM audit_events WHERE created_at BETWEEN :from AND :to";
        $params = [':from' => $from->format('Y-m-d H:i:s'), ':to' => $to->format('Y-m-d H:i:s')];
        
        // إضافة الفلاتر
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 1000";
        
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * توليد تقارير التدقيق
     */
    public function generateAuditReport(string $period = 'daily'): array {
        $report = [
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => [],
            'details' => []
        ];
        
        switch ($period) {
            case 'daily':
                $report['summary'] = $this->getDailySummary();
                $report['details'] = $this->getDailyDetails();
                break;
                
            case 'weekly':
                $report['summary'] = $this->getWeeklySummary();
                $report['details'] = $this->getWeeklyDetails();
                break;
                
            case 'monthly':
                $report['summary'] = $this->getMonthlySummary();
                $report['details'] = $this->getMonthlyDetails();
                break;
        }
        
        return $report;
    }
}