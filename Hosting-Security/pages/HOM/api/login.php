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

require_once '../../../security-init.php';
require_once '../config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('طريقة الطلب غير صحيحة', 405);
}
// قراءة بيانات الطلب
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'الرجاء إدخال اسم المستخدم وكلمة المرور'
    ]);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

try {
    // البحث عن المستخدم في قاعدة البيانات
    $stmt = $pdo->prepare("SELECT * FROM users_login WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // التحقق من كلمة المرور
        if (password_verify($password, $user['password'])) {
            // تسجيل دخول ناجح
            echo json_encode([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'visitor' => [
                        'id' => $user['id'],
                        'full_name' => $user['full_name'] ?? $user['username'],
                        'email' => $user['email'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ],
                    'is_new' => false
                ]
            ]);
        } else {
            // كلمة مرور خاطئة
            echo json_encode([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ]);
        }
    } else {
        // مستخدم غير موجود
        echo json_encode([
            'success' => false,
            'message' => 'اسم المستخدم أو البريد الإلكتروني غير موجود'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في قاعدة البيانات'
    ]);
}
?>