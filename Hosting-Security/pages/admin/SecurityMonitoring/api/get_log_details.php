<?php
// api/get_log_details.php - جلب تفاصيل سجل محدد
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = getDB();
$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'معرف السجل مطلوب']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT l.*, 
               u.username as user_name,
               s.name as server_name
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.id
        LEFT JOIN servers s ON l.server_id = s.id
        WHERE l.id = ?
    ");
    $stmt->execute([$id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($log) {
        // تنسيق الوقت
        $log['created_at'] = date('Y-m-d H:i:s', strtotime($log['created_at']));
        
        echo json_encode([
            'success' => true,
            'log' => $log
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'السجل غير موجود']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>