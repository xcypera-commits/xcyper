<?php
/**
 * مدير المصادقة الرئيسي
 * Authentication Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class AuthManager {
    
    private $db;
    private $logger;
    private $config;
    
    public function __construct($db = null) {
        $this->db = $db ?? $GLOBALS['db'] ?? null;
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/security-config.php';
    }
    
    /**
     * تسجيل الدخول
     */
    public function login($username, $password, $remember = false) {
        // التحقق من وجود المستخدم
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            $this->logger->log('auth', "Login failed - user not found: $username");
            return [
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'
            ];
        }
        
        // التحقق من حالة الحساب
        if ($user['status'] !== 'active') {
            $this->logger->log('auth', "Login blocked - account $username is {$user['status']}");
            return [
                'success' => false,
                'message' => 'الحساب غير نشط. يرجى التواصل مع الدعم'
            ];
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password'])) {
            $this->handleFailedLogin($user['id'], $username);
            return [
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'
            ];
        }
        
        // التحقق من إعادة تعيين كلمة المرور
        if ($user['password_reset_required']) {
            return [
                'success' => false,
                'redirect' => 'reset-password.php',
                'user_id' => $user['id']
            ];
        }
        
        // التحقق من MFA إذا كان مفعلاً
        if ($user['mfa_enabled'] && $this->config['auth']['mfa_required']) {
            return $this->initiateMFA($user);
        }
        
        // تسجيل الدخول الناجح
        return $this->completeLogin($user, $remember);
    }
    
    /**
     * الحصول على مستخدم باسم المستخدم
     */
    private function getUserByUsername($username) {
        // محاكاة - يجب استبدالها باستعلام قاعدة بيانات حقيقي
        $users = [
            'admin' => [
                'id' => 1,
                'username' => 'admin',
                'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
                'email' => 'admin@example.com',
                'role' => 'admin',
                'status' => 'active',
                'mfa_enabled' => true,
                'mfa_secret' => null,
                'password_reset_required' => false,
                'failed_attempts' => 0,
                'last_login' => null
            ],
            'client1' => [
                'id' => 2,
                'username' => 'client1',
                'password' => password_hash('Client@123', PASSWORD_DEFAULT),
                'email' => 'client1@example.com',
                'role' => 'client',
                'status' => 'active',
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'password_reset_required' => false,
                'failed_attempts' => 0,
                'last_login' => null
            ]
        ];
        
        return $users[$username] ?? null;
    }
    
    /**
     * معالجة محاولة تسجيل دخول فاشلة
     */
    private function handleFailedLogin($userId, $username) {
        $attempts = $_SESSION['failed_attempts_' . $userId] ?? 0;
        $attempts++;
        $_SESSION['failed_attempts_' . $userId] = $attempts;
        
        $this->logger->log('auth', "Failed login attempt #$attempts for user: $username");
        
        if ($attempts >= $this->config['auth']['max_login_attempts']) {
            $this->lockAccount($userId, $username);
        }
    }
    
    /**
     * قفل الحساب
     */
    private function lockAccount($userId, $username) {
        // في النظام الحقيقي: تحديث قاعدة البيانات
        $_SESSION['locked_' . $userId] = time() + $this->config['auth']['lockout_duration'];
        
        $this->logger->log('auth', "Account locked: $username");
        
        // إرسال تنبيه
        $alert = new AlertSystem();
        $alert->sendAlert('account_locked', "Account $username locked due to failed attempts", 'warning');
    }
    
    /**
     * بدء عملية MFA
     */
    private function initiateMFA($user) {
        $code = $this->generateMFACode();
        
        $_SESSION['mfa_user_id'] = $user['id'];
        $_SESSION['mfa_code'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['mfa_expires'] = time() + 300; // 5 دقائق
        
        // إرسال الكود عبر البريد أو SMS
        $this->sendMFACode($user['email'], $code);
        
        return [
            'success' => true,
            'mfa_required' => true,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني'
        ];
    }
    
    /**
     * توليد رمز MFA
     */
    private function generateMFACode() {
        return sprintf("%06d", random_int(0, 999999));
    }
    
    /**
     * إرسال رمز MFA
     */
    private function sendMFACode($email, $code) {
        // محاكاة إرسال بريد
        $subject = "رمز التحقق الخاص بك";
        $message = "رمز التحقق الخاص بك هو: $code\n";
        $message .= "هذا الرمز صالح لمدة 5 دقائق.";
        
        mail($email, $subject, $message);
        
        $this->logger->log('mfa', "MFA code sent to $email");
    }
    
    /**
     * التحقق من رمز MFA
     */
    public function verifyMFA($code) {
        if (!isset($_SESSION['mfa_user_id']) || !isset($_SESSION['mfa_code'])) {
            return [
                'success' => false,
                'message' => 'جلسة MFA غير صالحة'
            ];
        }
        
        if (time() > $_SESSION['mfa_expires']) {
            return [
                'success' => false,
                'message' => 'انتهت صلاحية الرمز'
            ];
        }
        
        if (!password_verify($code, $_SESSION['mfa_code'])) {
            return [
                'success' => false,
                'message' => 'الرمز غير صحيح'
            ];
        }
        
        $userId = $_SESSION['mfa_user_id'];
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ];
        }
        
        // تنظيف جلسة MFA
        unset($_SESSION['mfa_user_id']);
        unset($_SESSION['mfa_code']);
        unset($_SESSION['mfa_expires']);
        
        return $this->completeLogin($user);
    }
    
    /**
     * الحصول على مستخدم بالمعرف
     */
    private function getUserById($userId) {
        // محاكاة
        $users = [
            1 => [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'name' => 'مدير النظام'
            ],
            2 => [
                'id' => 2,
                'username' => 'client1',
                'email' => 'client1@example.com',
                'role' => 'client',
                'name' => 'عميل 1'
            ]
        ];
        
        return $users[$userId] ?? null;
    }
    
    /**
     * إكمال تسجيل الدخول
     */
    private function completeLogin($user, $remember = false) {
        // تسجيل الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'] ?? $user['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // إذا كان "تذكرني" مفعلاً
        if ($remember) {
            $this->createRememberToken($user['id']);
        }
        
        // تسجيل النشاط
        $this->logger->log('auth', "User {$user['username']} logged in successfully");
        
        // تحديث آخر تسجيل دخول
        $this->updateLastLogin($user['id']);
        
        // إعادة تعيين محاولات الفاشلة
        unset($_SESSION['failed_attempts_' . $user['id']]);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'] ?? $user['username'],
                'role' => $user['role'],
                'email' => $user['email']
            ],
            'redirect' => $this->getRedirectUrl($user['role'])
        ];
    }
    
    /**
     * إنشاء تذكرني token
     */
    private function createRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (30 * 86400)); // 30 يوم
        
        // حفظ في قاعدة البيانات
        $_SESSION['remember_token'] = $token;
        $_SESSION['remember_expires'] = $expires;
        
        // تخزين في كوكي آمن
        setcookie('remember_token', $token, time() + (30 * 86400), '/', '', true, true);
        
        $this->logger->log('auth', "Remember token created for user $userId");
    }
    
    /**
     * تحديث آخر تسجيل دخول
     */
    private function updateLastLogin($userId) {
        // تحديث قاعدة البيانات
        $_SESSION['last_login_update'] = time();
    }
    
    /**
     * الحصول على رابط إعادة التوجيه حسب الدور
     */
    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return '/pages/admin/security-dashboard.php';
            case 'staff':
                return '/pages/admin/dashboard.php';
            case 'client':
                return '/pages/clientHostingSecurity/dashboard.php';
            default:
                return '/index.php';
        }
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;
        
        // حذف تذكرني
        setcookie('remember_token', '', time() - 3600, '/');
        
        // تنظيف الجلسة
        $_SESSION = [];
        session_destroy();
        
        if ($username) {
            $this->logger->log('auth', "User $username logged out");
        }
        
        return true;
    }
    
    /**
     * التحقق من جلسة تذكرني
     */
    public function checkRememberToken() {
        if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
            $token = $_COOKIE['remember_token'];
            
            // التحقق من التوكن في قاعدة البيانات
            // محاكاة:
            if ($token === $_SESSION['remember_token'] ?? null) {
                $userId = 1; // الحصول من قاعدة البيانات
                $user = $this->getUserById($userId);
                if ($user) {
                    return $this->completeLogin($user);
                }
            }
        }
        
        return false;
    }
}
?>