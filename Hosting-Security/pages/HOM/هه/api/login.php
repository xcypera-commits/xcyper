<?php
// =====================================================
// API تسجيل دخول الزوار - مع إنشاء تلقائي
// =====================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../cloud_storage/config/database.php';
require_once '../../cloud_storage/includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('طريقة الطلب غير صحيحة', 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['username']) || empty($data['password'])) {
    sendError('اسم المستخدم وكلمة المرور مطلوبان', 400);
}

try {
    // البحث عن المستخدم
    $stmt = $db->prepare("SELECT id, full_name, email, phone, password_hash FROM visitors WHERE email = ?");
    $stmt->execute([$data['username']]);
    $user = $stmt->fetch();
    
    $isNew = false;
    
    // إذا لم يكن المستخدم موجوداً، أنشئ حساب جديد
    if (!$user) {
        // التحقق من صحة البريد
        if (!filter_var($data['username'], FILTER_VALIDATE_EMAIL)) {
            sendError('البريد الإلكتروني غير صالح', 400);
        }
        
        // تشفير كلمة المرور
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // إنشاء مستخدم جديد
        $insert = $db->prepare("
            INSERT INTO visitors (full_name, email, password_hash, status, registered_at) 
            VALUES (?, ?, ?, 'active', NOW())
        ");
        
        $fullName = explode('@', $data['username'])[0];
        $insert->execute([$fullName, $data['username'], $password_hash]);
        
        $userId = $db->lastInsertId();
        
        // جلب المستخدم الجديد
        $stmt = $db->prepare("SELECT id, full_name, email, phone FROM visitors WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $isNew = true;
        
    } else {
        // التحقق من كلمة المرور
        if (!password_verify($data['password'], $user['password_hash'])) {
            sendError('كلمة المرور غير صحيحة', 401);
        }
    }
    
    // تحديث آخر دخول
    $update = $db->prepare("UPDATE visitors SET last_login = NOW() WHERE id = ?");
    $update->execute([$user['id']]);
    
    // إزالة كلمة المرور
    unset($user['password_hash']);
    
    sendSuccess([
        'visitor' => $user,
        'is_new' => $isNew
    ], $isNew ? 'تم إنشاء حساب جديد' : 'تم تسجيل الدخول بنجاح');
    
} catch (PDOException $e) {
    sendError('خطأ في قاعدة البيانات: ' . $e->getMessage(), 500);
}
?>