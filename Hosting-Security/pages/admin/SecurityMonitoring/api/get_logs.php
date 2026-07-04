<?php
// api/get_logs.php
// API لجلب بيانات السجلات

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

try {
    $type = $_GET['type'] ?? '';
    $level = $_GET['level'] ?? '';
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    
    $query = "SELECT l.*, u.username, s.name as server_name
              FROM logs l
              LEFT JOIN users u ON l.user_id = u.id
              LEFT JOIN servers s ON l.server_id = s.id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($type)) {
        $query .= " AND l.log_type = :type";
        $params[':type'] = $type;
    }
    
    if (!empty($level)) {
        $query .= " AND l.level = :level";
        $params[':level'] = $level;
    }
    
    if (!empty($from)) {
        $query .= " AND DATE(l.created_at) >= :from";
        $params[':from'] = $from;
    }
    
    if (!empty($to)) {
        $query .= " AND DATE(l.created_at) <= :to";
        $params[':to'] = $to;
    }
    
    $query .= " ORDER BY l.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تنسيق السجلات
    $formatted_logs = array_map(function($log) {
        return [
            'id' => $log['id'],
            'type' => $log['log_type'],
            'type_text' => getLogTypeText($log['log_type']),
            'level' => $log['level'],
            'level_text' => getLogLevelText($log['level']),
            'level_color' => getLogLevelColor($log['level']),
            'source' => $log['source'],
            'user' => $log['username'] ?? 'system',
            'server' => $log['server_name'] ?? '-',
            'event_type' => $log['event_type'],
            'description' => $log['description'],
            'ip' => $log['ip_address'] ?? '-',
            'time' => date('Y-m-d H:i:s', strtotime($log['created_at']))
        ];
    }, $logs);
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_logs
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>