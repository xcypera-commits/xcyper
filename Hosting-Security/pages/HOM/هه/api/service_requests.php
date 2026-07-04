<?php
// =====================================================
// API إضافة طلب خدمة - مع تحديث رقم هاتف الزائر
// =====================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../cloud_storage/config/database.php';
require_once '../../cloud_storage/includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('طريقة الطلب غير صحيحة', 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendError('لا توجد بيانات مرسلة', 400);
}

// التحقق من الحقول المطلوبة
$required = ['service_type', 'service_name', 'full_name', 'email', 'phone', 'details', 'visitor_id'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        sendError("الحقل $field مطلوب", 400);
    }
}

try {
    $db->beginTransaction();
    
    // 1. تحديث رقم هاتف الزائر في جدول visitors
    $updateVisitor = $db->prepare("
        UPDATE visitors 
        SET phone = ?, 
            full_name = ? 
        WHERE id = ? AND (phone IS NULL OR phone = '')
    ");
    $updateVisitor->execute([
        $data['phone'],
        $data['full_name'],
        $data['visitor_id']
    ]);
    
    // 2. إضافة طلب الخدمة
    $stmt = $db->prepare("
        INSERT INTO service_requests (
            visitor_id, service_type, service_name, full_name, email, 
            phone, company_name, details, status, requested_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $data['visitor_id'],
        $data['service_type'],
        $data['service_name'],
        $data['full_name'],
        $data['email'],
        $data['phone'],
        $data['company_name'] ?? null,
        $data['details']
    ]);
    
    $requestId = $db->lastInsertId();
    
    $db->commit();
    
    sendSuccess([
        'request_id' => $requestId,
        'message' => 'تم إرسال الطلب وتحديث رقم الهاتف'
    ], 'تم إرسال الطلب بنجاح', 201);
    
} catch (PDOException $e) {
    $db->rollBack();
    sendError('خطأ في قاعدة البيانات: ' . $e->getMessage(), 500);
}
?>