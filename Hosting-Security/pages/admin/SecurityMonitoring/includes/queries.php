<?php
// includes/queries.php
// استعلامات قاعدة البيانات

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * الحصول على بيانات لوحة التحكم
 */
function getDashboardData($db) {
    $data = [];

    // عدد الخوادم النشطة
    $query = "SELECT COUNT(*) as count FROM servers WHERE status = 'online'";
    $stmt = $db->query($query);
    $data['active_servers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // التهديدات النشطة
    $query = "SELECT COUNT(*) as count FROM threats WHERE status = 'active'";
    $stmt = $db->query($query);
    $data['active_threats'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // التنبيهات اليومية
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
              FROM alerts 
              WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['daily_alerts'] = $result['total'];
    $data['resolved_alerts'] = $result['resolved'];

    // متوسط وقت التشغيل
    $query = "SELECT ROUND(AVG(system_uptime), 2) as uptime 
              FROM security_statistics 
              WHERE stat_date = CURDATE()";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['uptime'] = $result['uptime'] ?? 99.98;

    return $data;
}

/**
 * الحصول على حالة الخوادم
 */
function getServersStatus($db, $limit = 4) {
    $query = "SELECT id, name, type, status, cpu_usage, memory_usage, storage_usage, 
                     ip_address, location, last_check
              FROM servers
              ORDER BY 
                CASE status
                    WHEN 'warning' THEN 1
                    WHEN 'online' THEN 2
                    WHEN 'offline' THEN 3
                    ELSE 4
                END,
                cpu_usage DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على آخر التنبيهات الحرجة
 */
function getCriticalAlerts($db, $limit = 5) {
    $query = "SELECT a.id, a.title, a.description, a.type, a.severity, a.status,
                     s.name as server_name, a.created_at
              FROM alerts a
              LEFT JOIN servers s ON a.server_id = s.id
              WHERE a.type = 'critical' AND a.status != 'resolved'
              ORDER BY a.created_at DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على آخر أحداث الأمان
 */
function getSecurityEvents($db, $limit = 5) {
    $query = "SELECT log_type, level, description, source, created_at
              FROM logs
              WHERE log_type = 'security'
              ORDER BY created_at DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع التنبيهات
 */
function getAllAlerts($db) {
    $query = "SELECT a.*, s.name as server_name,
                     u1.full_name as acknowledged_by_name,
                     u2.full_name as resolved_by_name
              FROM alerts a
              LEFT JOIN servers s ON a.server_id = s.id
              LEFT JOIN users u1 ON a.acknowledged_by = u1.id
              LEFT JOIN users u2 ON a.resolved_by = u2.id
              ORDER BY a.created_at DESC";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات التنبيهات
 */
function getAlertsStatistics($db) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN type = 'critical' AND status != 'resolved' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN type = 'warning' AND status != 'resolved' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN type = 'info' AND status != 'resolved' THEN 1 ELSE 0 END) as info,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
              FROM alerts
              WHERE DATE(created_at) = CURDATE()";
    
    $stmt = $db->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على قواعد التنبيهات
 */
function getAlertRules($db) {
    $query = "SELECT ar.*, u.full_name as created_by_name
              FROM alert_rules ar
              LEFT JOIN users u ON ar.created_by = u.id
              WHERE ar.is_active = 1
              ORDER BY ar.severity DESC";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع التهديدات
 */
function getAllThreats($db) {
    $query = "SELECT t.*, s.name as target_server_name
              FROM threats t
              LEFT JOIN servers s ON t.target_server_id = s.id
              ORDER BY t.last_seen DESC";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات التهديدات
 */
function getThreatsStatistics($db) {
    $query = "SELECT 
                SUM(CASE WHEN type = 'ddos' THEN 1 ELSE 0 END) as ddos,
                SUM(CASE WHEN type = 'brute_force' THEN 1 ELSE 0 END) as brute_force,
                SUM(CASE WHEN type = 'sql_injection' THEN 1 ELSE 0 END) as sql_injection,
                SUM(CASE WHEN type = 'xss' THEN 1 ELSE 0 END) as xss,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'mitigated' THEN 1 ELSE 0 END) as mitigated
              FROM threats
              WHERE DATE(first_seen) = CURDATE()";
    
    $stmt = $db->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع السجلات
 */
function getAllLogs($db) {
    $query = "SELECT l.*, u.username, s.name as server_name
              FROM logs l
              LEFT JOIN users u ON l.user_id = u.id
              LEFT JOIN servers s ON l.server_id = s.id
              ORDER BY l.created_at DESC
              LIMIT 100";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع الحوادث
 */
function getAllIncidents($db) {
    $query = "SELECT i.*, 
                     u1.full_name as assigned_to_name,
                     u2.full_name as created_by_name
              FROM incidents i
              LEFT JOIN users u1 ON i.assigned_to = u1.id
              LEFT JOIN users u2 ON i.created_by = u2.id
              ORDER BY i.detected_at DESC";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الحوادث
 */
function getIncidentsStatistics($db) {
    $query = "SELECT 
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' AND MONTH(resolved_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as resolved_this_month,
                ROUND(AVG(CASE WHEN status = 'resolved' 
                          THEN TIMESTAMPDIFF(HOUR, detected_at, resolved_at) 
                          ELSE NULL END), 1) as avg_resolution_hours
              FROM incidents
              WHERE YEAR(created_at) = YEAR(CURDATE())";
    
    $stmt = $db->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع السياسات الأمنية
 */
function getAllPolicies($db) {
    $query = "SELECT p.*, 
                     u1.full_name as created_by_name,
                     u2.full_name as approved_by_name
              FROM security_policies p
              LEFT JOIN users u1 ON p.created_by = u1.id
              LEFT JOIN users u2 ON p.approved_by = u2.id
              WHERE p.status = 'active'
              ORDER BY p.priority DESC";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات السياسات
 */
function getPoliciesStatistics($db) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN compliance_percentage >= 90 THEN 1 ELSE 0 END) as compliant,
                SUM(CASE WHEN compliance_percentage < 80 THEN 1 ELSE 0 END) as needs_review
              FROM security_policies";
    
    $stmt = $db->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع التقارير
 */
function getAllReports($db) {
    $query = "SELECT r.*, u.full_name as generated_by_name
              FROM reports r
              LEFT JOIN users u ON r.generated_by = u.id
              ORDER BY r.generated_at DESC
              LIMIT 20";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * تحديث حالة التنبيه
 */
function updateAlertStatus($db, $alert_id, $status, $user_id) {
    $query = "UPDATE alerts 
              SET status = :status,
                  resolved_at = CASE WHEN :status = 'resolved' THEN NOW() ELSE NULL END,
                  resolved_by = CASE WHEN :status = 'resolved' THEN :user_id ELSE resolved_by END,
                  acknowledged_by = CASE WHEN :status = 'acknowledged' THEN :user_id ELSE acknowledged_by END
              WHERE id = :alert_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':alert_id', $alert_id);
    
    return $stmt->execute();
}

/**
 * الحصول على بيانات المستخدمين
 */
function getUsers($db) {
    $query = "SELECT id, username, full_name, role, department, last_login, is_active
              FROM users
              WHERE is_active = 1
              ORDER BY full_name";
    
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>