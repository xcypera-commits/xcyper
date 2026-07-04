<?php
// api/get_threats.php
// API لجلب بيانات التهديدات

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

try {
    $filter = $_GET['filter'] ?? 'all';
    
    // إحصائيات التهديدات
    $statistics = getThreatsStatistics($db);
    
    // جميع التهديدات
    $threats = getAllThreats($db);
    
    // تصفية حسب الحالة
    if ($filter !== 'all') {
        $threats = array_filter($threats, function($threat) use ($filter) {
            return $threat['status'] === $filter;
        });
    }
    
    // تنسيق التهديدات
    $formatted_threats = array_map(function($threat) {
        return [
            'id' => $threat['id'],
            'name' => $threat['name'],
            'type' => $threat['type'],
            'type_text' => getThreatTypeText($threat['type']),
            'type_color' => getThreatTypeColor($threat['type']),
            'source_ip' => $threat['source_ip'],
            'target' => $threat['target_server_name'] ?? $threat['target_url'],
            'severity' => $threat['severity'],
            'severity_text' => getSeverityText($threat['severity']),
            'severity_color' => getSeverityColor($threat['severity']),
            'status' => $threat['status'],
            'last_seen' => formatTimeAgo($threat['last_seen'])
        ];
    }, $threats);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'statistics' => $statistics,
            'threats' => $formatted_threats
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>