<?php
require_once __DIR__ . '/../../security-init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'login':
            $response = handleLogin();
            break;
            
        case 'logout':
            $response = handleLogout();
            break;
            
        case 'verify_mfa':
            $response = handleMFAVerification();
            break;
            
        default:
            $response['message'] = 'إجراء غير معروف';
    }
} catch (Exception $e) {
    $response['message'] = 'حدث خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

function handleLogin() {
    require_once __DIR__ . '/../../authentication/AuthManager.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'جميع الحقول مطلوبة'];
    }
    
    // الاتصال بقاعدة البيانات
    $db = new PDO('mysql:host=localhost;dbname=hosting_security', 'root', '');
    $authManager = new AuthManager($db);
    
    $result = $authManager->login($username, $password, isset($_POST['remember']));
    
    if ($result['success']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['user_role'] = $result['user']['role'];
        $_SESSION['user_name'] = $result['user']['full_name'];
        
        // إذا كان يحتاج MFA
        if (isset($result['requires_mfa'])) {
            return [
                'success' => true,
                'requires_mfa' => true,
                'message' => 'يجب التحقق عبر MFA'
            ];
        }
        
        return [
            'success' => true,
            'redirect' => '/dashboard.php',
            'message' => 'تم تسجيل الدخول بنجاح'
        ];
    }
    
    return $result;
}

function handleLogout() {
    session_destroy();
    return ['success' => true, 'message' => 'تم تسجيل الخروج'];
}

function handleMFAVerification() {
    $mfa_code = $_POST['mfa_code'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if (empty($mfa_code)) {
        return ['success' => false, 'message' => 'يرجى إدخال رمز MFA'];
    }
    
    // التحقق من رمز MFA
    require_once __DIR__ . '/../..//authentication/MFAManager.php';
    
    $mfaManager = new MFAManager();
    if ($mfaManager->verifyCode($user_id, $mfa_code)) {
        $_SESSION['mfa_verified'] = true;
        return ['success' => true, 'redirect' => '/dashboard.php'];
    }
    
    return ['success' => false, 'message' => 'رمز MFA غير صحيح'];
}
?>