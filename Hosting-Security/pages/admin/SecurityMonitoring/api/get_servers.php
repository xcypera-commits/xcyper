<?php
// api/get_servers.php
// API لجلب بيانات الخوادم

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

try {
    // استعلام مفصل للخوادم
    $query = "SELECT * FROM servers ORDER BY 
              CASE status
                  WHEN 'warning' THEN 1
                  WHEN 'online' THEN 2
                  WHEN 'offline' THEN 3
                  ELSE 4
              END,
              cpu_usage DESC";
    
    $stmt = $db->query($query);
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إحصائيات الخوادم
    $stats_query = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online,
                      SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning,
                      SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline,
                      ROUND(AVG(cpu_usage), 1) as avg_cpu,
                      ROUND(AVG(memory_usage), 1) as avg_memory,
                      ROUND(AVG(storage_usage), 1) as avg_storage
                    FROM servers";
    
    $stmt_stats = $db->query($stats_query);
    $statistics = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'servers' => $servers,
            'statistics' => $statistics
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>