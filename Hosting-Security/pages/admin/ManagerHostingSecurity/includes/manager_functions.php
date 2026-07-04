<?php
// includes/manager_functions.php - دوال خاصة بمدير النظام

function getManagerDashboardStats($db) {
    $stats = [];
    
    // المشاريع النشطة
    $stats['active_projects'] = $db->query("SELECT COUNT(*) FROM projects WHERE status != 'completed'")->fetchColumn();
    
    // الحوادث المفتوحة
    $stats['open_incidents'] = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open', 'in-progress')")->fetchColumn();
    
    // الحوادث الحرجة
    $stats['critical_incidents'] = $db->query("SELECT COUNT(*) FROM incidents WHERE severity = 'critical' AND status IN ('open', 'in-progress')")->fetchColumn();
    
    // توافر النظام
    $stats['system_availability'] = $db->query("SELECT ROUND(AVG(system_uptime), 2) FROM security_statistics WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn() ?: 99.95;
    
    // إنتاجية الفرق
    $stats['team_productivity'] = $db->query("SELECT ROUND(AVG(productivity), 1) FROM performance_metrics WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn() ?: 88;
    
    return $stats;
}

function getUnitStatus($db) {
    return $db->query("
        SELECT u.*, 
               COUNT(p.id) as active_projects,
               (SELECT COUNT(*) FROM incidents WHERE unit_id = u.id AND status IN ('open', 'in-progress')) as unit_incidents
        FROM units u
        LEFT JOIN projects p ON u.id = p.unit_id AND p.status != 'completed'
        GROUP BY u.id
    ")->fetchAll();
}

function getCriticalAlerts($db, $limit = 3) {
    return $db->query("
        SELECT a.*, s.name as server_name, u.name as unit_name
        FROM alerts a
        LEFT JOIN servers s ON a.server_id = s.id
        LEFT JOIN units u ON s.unit_id = u.id
        WHERE a.type = 'critical' AND a.status != 'resolved'
        ORDER BY a.created_at DESC
        LIMIT $limit
    ")->fetchAll();
}

function getRecentActivities($db, $limit = 5) {
    return $db->query("
        SELECT al.*, u.full_name as user_name, un.name as unit_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN units un ON al.unit_id = un.id
        ORDER BY al.created_at DESC
        LIMIT $limit
    ")->fetchAll();
}

function getPendingApprovals($db, $limit = 2) {
    return $db->query("
        SELECT pa.*, u.full_name as requester_name, un.name as unit_name
        FROM pending_approvals pa
        LEFT JOIN users u ON pa.requester_id = u.id
        LEFT JOIN units un ON pa.unit_id = un.id
        WHERE pa.status = 'pending'
        ORDER BY 
            CASE pa.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            pa.request_date ASC
        LIMIT $limit
    ")->fetchAll();
}

function getResourceRequests($db, $limit = 2) {
    return $db->query("
        SELECT rr.*, u.full_name as requester_name, un.name as unit_name
        FROM resource_requests rr
        LEFT JOIN users u ON rr.requester_id = u.id
        LEFT JOIN units un ON rr.unit_id = un.id
        WHERE rr.status = 'pending'
        ORDER BY 
            CASE rr.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            rr.request_date ASC
        LIMIT $limit
    ")->fetchAll();
}

function getUnitPerformance($db) {
    return $db->query("
        SELECT u.*, 
               pm.productivity, pm.quality, pm.speed,
               pm.employee_count, pm.active_projects
        FROM units u
        LEFT JOIN performance_metrics pm ON u.id = pm.unit_id AND pm.metric_date = CURDATE()
        ORDER BY u.id
    ")->fetchAll();
}

function getComplianceStandards($db) {
    return $db->query("
        SELECT cs.*, u.name as responsible_unit_name
        FROM compliance_standards cs
        LEFT JOIN units u ON cs.responsible_unit = u.id
        ORDER BY cs.status, cs.compliance_rate DESC
    ")->fetchAll();
}

function getViolations($db, $severity = null) {
    $sql = "SELECT v.*, cs.name as standard_name, u.full_name as assigned_to_name
            FROM violations v
            LEFT JOIN compliance_standards cs ON v.standard_id = cs.id
            LEFT JOIN users u ON v.assigned_to = u.id
            WHERE 1=1";
    if ($severity) {
        $sql .= " AND v.severity = '$severity'";
    }
    $sql .= " ORDER BY 
                CASE v.severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                v.detected_date DESC";
    
    return $db->query($sql)->fetchAll();
}

function getArchivedReports($db, $limit = 10) {
    return $db->query("
        SELECT ar.*, u.name as unit_name, usr.full_name as generated_by_name
        FROM archived_reports ar
        LEFT JOIN units u ON ar.unit_id = u.id
        LEFT JOIN users usr ON ar.generated_by = usr.id
        ORDER BY ar.archive_date DESC
        LIMIT $limit
    ")->fetchAll();
}

function getSystemStatus($db) {
    return $db->query("SELECT * FROM system_status ORDER BY component")->fetchAll();
}

function getLiveThreats($db) {
    return $db->query("SELECT * FROM live_threats WHERE is_active = true ORDER BY severity DESC")->fetchAll();
}

function getProjectStats($db) {
    return $db->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM projects
        WHERE status != 'completed'
        GROUP BY status
    ")->fetchAll();
}
?>