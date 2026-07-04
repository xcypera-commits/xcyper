<?php
// api/get_alerts.php
// API لجلب بيانات التنبيهات

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

try {
    $type = $_GET['type'] ?? 'all';
    
    $response = [];
    
    // إحصائيات التنبيهات
    $response['statistics'] = getAlertsStatistics($db);
    
    // قواعد التنبيهات
    $response['rules'] = getAlertRules($db);
    
    // جميع التنبيهات
    $alerts = getAllAlerts($db);
    
    // تصفية حسب النوع إذا لزم الأمر
    if ($type !== 'all') {
        $alerts = array_filter($alerts, function($alert) use ($type) {
            return $alert['type'] === $type;
        });
    }
    
    // تنسيق التنبيهات
    $response['alerts'] = array_map(function($alert) {
        return [
            'id' => $alert['id'],
            'title' => $alert['title'],
            'description' => $alert['description'],
            'type' => $alert['type'],
            'severity' => $alert['severity'],
            'source' => $alert['source'],
            'server' => $alert['server_name'] ?? 'الكل',
            'time' => formatTimeAgo($alert['created_at']),
            'status' => $alert['status'],
            'status_text' => getAlertStatusText($alert['status']),
            'status_color' => getAlertStatusColor($alert['status']),
            'severity_text' => getSeverityText($alert['severity']),
            'severity_color' => getSeverityColor($alert['severity'])
        ];
    }, $alerts);
    
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