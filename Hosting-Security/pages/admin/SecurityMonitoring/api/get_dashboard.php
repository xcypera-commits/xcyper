<?php
// api/get_dashboard.php
// API لجلب بيانات لوحة التحكم

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

try {
    $response = [];

    // بيانات لوحة التحكم الرئيسية
    $dashboard = getDashboardData($db);
    
    // حالة الخوادم
    $servers = getServersStatus($db, 4);
    
    // التنبيهات الحرجة
    $criticalAlerts = getCriticalAlerts($db, 5);
    
    // أحداث الأمان
    $securityEvents = getSecurityEvents($db, 5);
    
    // تنسيق البيانات
    $response['active_servers'] = $dashboard['active_servers'];
    $response['active_threats'] = $dashboard['active_threats'];
    $response['daily_alerts'] = $dashboard['daily_alerts'];
    $response['uptime'] = $dashboard['uptime'];
    $response['resolved_alerts'] = $dashboard['resolved_alerts'];
    
    // تنسيق الخوادم
    $response['servers'] = array_map(function($server) {
        return [
            'id' => $server['id'],
            'name' => $server['name'],
            'type' => $server['type'],
            'status' => $server['status'],
            'cpu' => $server['cpu_usage'],
            'ip' => $server['ip_address'],
            'location' => $server['location'],
            'last_check' => $server['last_check'],
            'status_color' => getServerStatusColor($server['status']),
            'indicator_color' => getServerIndicatorColor($server['status'])
        ];
    }, $servers);
    
    // تنسيق التنبيهات
    $response['critical_alerts'] = array_map(function($alert) {
        return [
            'id' => $alert['id'],
            'title' => $alert['title'],
            'description' => $alert['description'],
            'server' => $alert['server_name'] ?? 'غير محدد',
            'time' => formatTimeAgo($alert['created_at']),
            'severity_color' => getSeverityColor($alert['severity'])
        ];
    }, $criticalAlerts);
    
    // تنسيق أحداث الأمان
    $response['security_events'] = array_map(function($event) {
        return [
            'type' => getLogTypeText($event['log_type']),
            'description' => $event['description'],
            'source' => $event['source'],
            'time' => date('H:i', strtotime($event['created_at'])),
            'level_color' => getLogLevelColor($event['level'])
        ];
    }, $securityEvents);
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>