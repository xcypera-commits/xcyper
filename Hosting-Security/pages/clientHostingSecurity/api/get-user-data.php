<?php
// api/get-user-data.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // استلام التوكن (يمكن تحسينه باستخدام JWT)
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        throw new Exception('غير مصرح به');
    }
    
    // هنا يمكن التحقق من التوكن إذا كنت تستخدم JWT
    // للتبسيط، نستخدم البريد الإلكتروني من الطلب
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id'])) {
        throw new Exception('معرف المستخدم مطلوب');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, client_code, full_name, email, phone, company_name, 
                     tax_number, address, city, country, balance, credit_limit,
                     last_login, created_at
              FROM client_clients 
              WHERE id = :id AND status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception('المستخدم غير موجود');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>