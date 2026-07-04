<?php
// api/register.php - API إنشاء حساب جديد

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

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'البيانات غير صالحة'
    ]);
    exit;
}

$full_name = trim($input['full_name'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';

// التحقق من البيانات
if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'جميع الحقول المطلوبة يجب أن تمتلئ'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'البريد الإلكتروني غير صالح'
    ]);
    exit;
}

try {
    // التحقق من وجود اسم المستخدم
    $stmt = $pdo->prepare("SELECT id FROM users_login WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'اسم المستخدم موجود بالفعل'
        ]);
        exit;
    }
    
    // التحقق من وجود البريد الإلكتروني
    $stmt = $pdo->prepare("SELECT id FROM users_login WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني مسجل بالفعل'
        ]);
        exit;
    }
    
    // تشفير كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // إدراج المستخدم الجديد
    $stmt = $pdo->prepare("INSERT INTO users_login (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'editor')");
    $stmt->execute([$username, $hashed_password, $email, $full_name]);
    
    $user_id = $pdo->lastInsertId();
    
    // إضافة رقم الهاتف إذا وجد (اختياري - يمكنك إضافة عمود phone للجدول إذا أردت)
    // إذا أردت إضافة عمود الهاتف، استخدم هذا الكود المعدل:
    $stmt = $pdo->prepare("UPDATE users_login SET phone = ? WHERE id = ?");
    $stmt->execute([$phone, $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء الحساب بنجاح',
        'data' => [
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}
?>