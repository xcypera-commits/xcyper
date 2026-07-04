<?php
// api/change-password.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // استلام البيانات
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['old_password']) || !isset($data['new_password'])) {
        throw new Exception('جميع الحقول مطلوبة');
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $oldPassword = $data['old_password'];
    $newPassword = $data['new_password'];
    
    // التحقق من قوة كلمة المرور الجديدة
    if (strlen($newPassword) < 8) {
        throw new Exception('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
    }
    
    if (!preg_match('/[A-Z]/', $newPassword)) {
        throw new Exception('كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل');
    }
    
    if (!preg_match('/[a-z]/', $newPassword)) {
        throw new Exception('كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل');
    }
    
    if (!preg_match('/[0-9]/', $newPassword)) {
        throw new Exception('كلمة المرور يجب أن تحتوي على رقم واحد على الأقل');
    }
    
    if (!preg_match('/[!@#$%^&*]/', $newPassword)) {
        throw new Exception('كلمة المرور يجب أن تحتوي على رمز خاص واحد على الأقل (!@#$%^&*)');
    }
    
    // الاتصال بقاعدة البيانات
    $database = new Database();
    $db = $database->getConnection();
    
    // البحث عن المستخدم والتحقق من كلمة المرور القديمة
    $query = "SELECT id, password_hash FROM client_clients WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception('المستخدم غير موجود');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // التحقق من كلمة المرور القديمة
    if (!password_verify($oldPassword, $user['password_hash'])) {
        throw new Exception('كلمة المرور القديمة غير صحيحة');
    }
    
    // تشفير كلمة المرور الجديدة
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // تحديث كلمة المرور
    $updateQuery = "UPDATE client_clients SET password_hash = :password WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $newPasswordHash);
    $updateStmt->bindParam(':id', $user['id']);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    } else {
        throw new Exception('فشل في تحديث كلمة المرور');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>