<?php
// api/update_alert.php
// API لتحديث حالة التنبيه

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/queries.php';

$db = getDB();

if (!$db) {
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'طريقة طلب غير صحيحة']);
    exit;
}

// الحصول على البيانات
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'بيانات غير صحيحة']);
    exit;
}

$alert_id = $input['alert_id'] ?? 0;
$status = $input['status'] ?? '';
$user_id = $input['user_id'] ?? 1; // مؤقتاً، سيأتي من الجلسة لاحقاً

if (!$alert_id || !$status) {
    echo json_encode(['error' => 'جميع الحقول مطلوبة']);
    exit;
}

try {
    $result = updateAlertStatus($db, $alert_id, $status, $user_id);
    
    if ($result) {
        // تسجيل النشاط
        $log_query = "INSERT INTO user_activity_log (user_id, action_type, target_type, target_id, description, ip_address) 
                      VALUES (:user_id, :action, 'alert', :target_id, :description, :ip)";
        
        $log_stmt = $db->prepare($log_query);
        $log_stmt->execute([
            ':user_id' => $user_id,
            ':action' => $status,
            ':target_id' => $alert_id,
            ':description' => "تم تحديث حالة التنبيه إلى $status",
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث حالة التنبيه بنجاح'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'فشل تحديث حالة التنبيه'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>