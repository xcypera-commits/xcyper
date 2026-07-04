<?php
// api/get_incident_details.php - جلب تفاصيل حادث محدد
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = getDB();
$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'معرف الحادث مطلوب']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT i.*, 
               u1.full_name as assigned_to_name,
               u2.full_name as created_by_name
        FROM incidents i
        LEFT JOIN users u1 ON i.assigned_to = u1.id
        LEFT JOIN users u2 ON i.created_by = u2.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($incident) {
        // تنسيق التواريخ
        $incident['detected_at'] = date('Y-m-d H:i:s', strtotime($incident['detected_at']));
        if ($incident['resolved_at']) {
            $incident['resolved_at'] = date('Y-m-d H:i:s', strtotime($incident['resolved_at']));
        }
        
        echo json_encode([
            'success' => true,
            'incident' => $incident
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'الحادث غير موجود']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>