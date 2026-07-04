<?php
class SecurityDatabase extends PDO {
    
    public function __construct() {
        parent::__construct(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
    }
    
    public function secureQuery($sql, $params = []) {
        $stmt = $this->prepare($sql);
        
        foreach ($params as $key => $value) {
            $type = $this->determineParamType($value);
            $stmt->bindValue($key, $value, $type);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    private function determineParamType($value) {
        if (is_int($value)) return PDO::PARAM_INT;
        if (is_bool($value)) return PDO::PARAM_BOOL;
        if (is_null($value)) return PDO::PARAM_NULL;
        return PDO::PARAM_STR;
    }
    
    public function getSecurityStats() {
        $stats = [];
        
        // إحصائيات المستخدمين
        $stats['users'] = $this->secureQuery(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN failed_login_attempts > 0 THEN 1 ELSE 0 END) as failed_logins
             FROM users"
        )->fetch();
        
        // إحصائيات الجلسات
        $stats['sessions'] = $this->secureQuery(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
             FROM user_sessions"
        )->fetch();
        
        // إحصائيات التهديدات
        $stats['threats'] = $this->secureQuery(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new
             FROM security_threats
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetch();
        
        return $stats;
    }
}
?>