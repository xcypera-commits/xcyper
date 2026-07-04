<?php
/**
 * auth.php
 * نظام التحقق من العملاء والصلاحيات
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    exit('لا يمكن الوصول المباشر إلى هذا الملف');
}

/**
 * =============================================
 * Class ClientAuth
 * إدارة مصادقة العملاء
 * =============================================
 */
class ClientAuth {
    private $db;
    private $session_name = 'client_auth';
    private $max_attempts = 5;
    private $lockout_time = 900; // 15 دقيقة
    
    /**
     * Constructor
     */
    public function __construct($db) {
        $this->db = $db;
        $this->initSession();
    }
    
    /**
     * بدء الجلسة بشكل آمن
     */
    private function initSession() {
        if (session_status() == PHP_SESSION_NONE) {
            // إعدادات أمان الجلسة
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 1);
            
            session_name($this->session_name);
            session_start();
        }
    }
    
    /**
     * محاولة تسجيل الدخول
     * @param string $email البريد الإلكتروني
     * @param string $password كلمة المرور
     * @return array
     */
    public function login($email, $password) {
        $response = [
            'success' => false,
            'message' => '',
            'redirect' => ''
        ];
        
        // التحقق من محاولات الدخول الفاشلة
        if ($this->isLockedOut($_SERVER['REMOTE_ADDR'])) {
            $response['message'] = 'تم حظر محاولات الدخول مؤقتاً. الرجاء المحاولة بعد 15 دقيقة';
            return $response;
        }
        
        // البحث عن العميل
        $client = $this->getClientByEmail($email);
        
        if (!$client) {
            $this->logFailedAttempt($_SERVER['REMOTE_ADDR'], $email);
            $response['message'] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            return $response;
        }
        
        // التحقق من حالة الحساب
        if ($client['status'] != 'active') {
            $response['message'] = 'هذا الحساب غير نشط. الرجاء التواصل مع الدعم';
            return $response;
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $client['password_hash'])) {
            $this->logFailedAttempt($_SERVER['REMOTE_ADDR'], $email);
            $response['message'] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            return $response;
        }
        
        // تسجيل الدخول بنجاح
        $this->setClientSession($client);
        $this->clearFailedAttempts($_SERVER['REMOTE_ADDR']);
        $this->updateLastLogin($client['id']);
        $this->logActivity($client['id'], 'login', 'تسجيل دخول ناجح');
        
        $response['success'] = true;
        $response['message'] = 'تم تسجيل الدخول بنجاح';
        $response['redirect'] = '/client-unit/index.php';
        
        return $response;
    }
    
    /**
     * تسجيل الخروج
     * @return bool
     */
    public function logout() {
        $client_id = $_SESSION['client_id'] ?? null;
        
        if ($client_id) {
            $this->logActivity($client_id, 'logout', 'تسجيل خروج');
        }
        
        // مسح جميع متغيرات الجلسة
        $_SESSION = [];
        
        // حذف كوكي الجلسة
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // تدمير الجلسة
        session_destroy();
        
        return true;
    }
    
    /**
     * التحقق من تسجيل الدخول
     * @return bool
     */
    public function check() {
        return isset($_SESSION['client_id']) && !empty($_SESSION['client_id']);
    }
    
    /**
     * الحصول على بيانات العميل الحالي
     * @return array|null
     */
    public function client() {
        if (!$this->check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['client_id'],
            'full_name' => $_SESSION['client_name'],
            'email' => $_SESSION['client_email'],
            'company' => $_SESSION['client_company'] ?? '',
            'phone' => $_SESSION['client_phone'] ?? ''
        ];
    }
    
    /**
     * الحصول على العميل من قاعدة البيانات
     * @param string $email
     * @return array|null
     */
    private function getClientByEmail($email) {
        $sql = "SELECT * FROM client_clients WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * تخزين بيانات العميل في الجلسة
     * @param array $client
     */
    private function setClientSession($client) {
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['full_name'];
        $_SESSION['client_email'] = $client['email'];
        $_SESSION['client_company'] = $client['company_name'];
        $_SESSION['client_phone'] = $client['phone'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // تجديد معرف الجلسة لمنع fixation
        session_regenerate_id(true);
    }
    
    /**
     * تحديث آخر تسجيل دخول
     * @param int $client_id
     */
    private function updateLastLogin($client_id) {
        $sql = "UPDATE client_clients SET last_login = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$client_id]);
    }
    
    /**
     * التحقق من حظر IP
     * @param string $ip
     * @return bool
     */
    private function isLockedOut($ip) {
        $sql = "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                FROM login_attempts 
                WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip, $this->lockout_time]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['attempts'] >= $this->max_attempts;
    }
    
    /**
     * تسجيل محاولة فاشلة
     * @param string $ip
     * @param string $email
     */
    private function logFailedAttempt($ip, $email) {
        $sql = "INSERT INTO login_attempts (ip_address, username, attempt_time) 
                VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip, $email]);
    }
    
    /**
     * مسح محاولات الفاشلة
     * @param string $ip
     */
    private function clearFailedAttempts($ip) {
        $sql = "DELETE FROM login_attempts WHERE ip_address = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip]);
    }
    
    /**
     * تسجيل النشاط
     * @param int $client_id
     * @param string $action
     * @param string $description
     */
    private function logActivity($client_id, $action, $description) {
        $sql = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $client_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    /**
     * تغيير كلمة المرور
     * @param int $client_id
     * @param string $old_password
     * @param string $new_password
     * @return array
     */
    public function changePassword($client_id, $old_password, $new_password) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // الحصول على العميل
        $sql = "SELECT password_hash FROM client_clients WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            $response['message'] = 'العميل غير موجود';
            return $response;
        }
        
        // التحقق من كلمة المرور القديمة
        if (!password_verify($old_password, $client['password_hash'])) {
            $response['message'] = 'كلمة المرور القديمة غير صحيحة';
            return $response;
        }
        
        // التحقق من قوة كلمة المرور الجديدة
        if (!$this->validatePasswordStrength($new_password)) {
            $response['message'] = 'كلمة المرور الجديدة ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم ورمز خاص';
            return $response;
        }
        
        // تحديث كلمة المرور
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $sql = "UPDATE client_clients SET password_hash = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$new_hash, $client_id])) {
            $this->logActivity($client_id, 'change_password', 'تغيير كلمة المرور');
            $response['success'] = true;
            $response['message'] = 'تم تغيير كلمة المرور بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في تحديث كلمة المرور';
        }
        
        return $response;
    }
    
    /**
     * التحقق من قوة كلمة المرور
     * @param string $password
     * @return bool
     */
    private function validatePasswordStrength($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) return false;
        
        return true;
    }
}

/**
 * =============================================
 * دوال مساعدة لل Auth
 * =============================================
 */

/**
 * إنشاء كائن ClientAuth
 * @param PDO $db
 * @return ClientAuth
 */
function clientAuth($db) {
    static $auth = null;
    
    if ($auth === null) {
        $auth = new ClientAuth($db);
    }
    
    return $auth;
}

/**
 * التحقق من تسجيل دخول العميل وإعادة التوجيه
 * @param PDO $db
 */
function requireClientLogin($db) {
    $auth = clientAuth($db);
    
    if (!$auth->check()) {
        header('Location: /client-unit/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * عرض رسالة ترحيب للعميل
 * @param array $client
 * @return string
 */
function renderClientWelcome($client) {
    $hour = date('H');
    
    if ($hour < 12) {
        $greeting = 'صباح الخير';
    } elseif ($hour < 17) {
        $greeting = 'مساء الخير';
    } else {
        $greeting = 'مساء النور';
    }
    
    return "
    <div class='flex items-center space-x-4 space-x-reverse'>
        <div class='text-right'>
            <p class='text-sm text-gray-400'>{$greeting}</p>
            <p class='font-semibold'>{$client['full_name']}</p>
            <p class='text-xs text-gray-400'>{$client['company']}</p>
        </div>
        <div class='w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center'>
            <span class='text-white font-bold text-lg'>
                " . mb_substr($client['full_name'], 0, 1) . "
            </span>
        </div>
    </div>";
}

/**
 * =============================================
 * نهاية الملف
 * =============================================
 */