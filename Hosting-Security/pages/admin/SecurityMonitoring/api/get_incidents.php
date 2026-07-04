<?php
// api/get_incidents.php
// API لجلب بيانات الحوادث

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

try {
    $filter = $_GET['filter'] ?? 'all';
    
    // إحصائيات الحوادث
    $statistics = getIncidentsStatistics($db);
    
    // جميع الحوادث
    $incidents = getAllIncidents($db);
    
    // تصفية حسب الحالة
    if ($filter !== 'all') {
        $incidents = array_filter($incidents, function($incident) use ($filter) {
            return $incident['status'] === $filter;
        });
    }
    
    // تنسيق الحوادث
    $formatted_incidents = array_map(function($incident) {
        return [
            'id' => $incident['id'],
            'name' => $incident['name'],
            'type' => $incident['type'],
            'severity' => $incident['severity'],
            'severity_text' => getSeverityText($incident['severity']),
            'severity_color' => getSeverityColor($incident['severity']),
            'status' => $incident['status'],
            'assigned_to' => $incident['assigned_to_name'] ?? 'غير معين',
            'impact' => $incident['impact'],
            'detected_at' => formatTimeAgo($incident['detected_at']),
            'last_update' => formatTimeAgo($incident['detected_at'])
        ];
    }, $incidents);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'statistics' => $statistics,
            'incidents' => $formatted_incidents
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>