<?php
// api/login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // استلام البيانات
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('البريد الإلكتروني وكلمة المرور مطلوبان');
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    // الاتصال بقاعدة البيانات
    $database = new Database();
    $db = $database->getConnection();
    
    // البحث عن المستخدم
    $query = "SELECT id, client_code, full_name, email, phone, company_name, 
                     password_hash, balance, credit_limit, status 
              FROM client_clients 
              WHERE email = :email AND status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // التحقق من كلمة المرور (نفترض أنها مشفرة بـ password_hash)
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
    }
    
    // تحديث آخر تسجيل دخول
    $updateQuery = "UPDATE client_clients SET last_login = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // إزالة كلمة المرور من البيانات المرسلة
    unset($user['password_hash']);
    
    // إنشاء توكن جلسة (يمكن تحسينه باستخدام JWT)
    $sessionToken = bin2hex(random_bytes(32));
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'data' => [
            'user' => $user,
            'token' => $sessionToken
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>