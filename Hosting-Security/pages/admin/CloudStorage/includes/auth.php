<?php
/**
 * auth.php
 * نظام التحقق من المستخدمين والصلاحيات لوحدة الاستضافة
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    exit('لا يمكن الوصول المباشر إلى هذا الملف');
}

/**
 * =============================================
 * Class Auth
 * إدارة المصادقة والصلاحيات
 * =============================================
 */
class Auth {
    private $db;
    private $session_name = 'cloud_auth';
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
     * @param string $username اسم المستخدم
     * @param string $password كلمة المرور
     * @return array
     */
    public function login($username, $password) {
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
        
        // البحث عن المستخدم
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            $this->logFailedAttempt($_SERVER['REMOTE_ADDR'], $username);
            $response['message'] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            return $response;
        }
        
        // التحقق من حالة الحساب
        if (!$user['is_active']) {
            $response['message'] = 'هذا الحساب غير نشط. الرجاء التواصل مع الإدارة';
            return $response;
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($_SERVER['REMOTE_ADDR'], $username);
            $response['message'] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            return $response;
        }
        
        // تسجيل الدخول بنجاح
        $this->setUserSession($user);
        $this->clearFailedAttempts($_SERVER['REMOTE_ADDR']);
        $this->updateLastLogin($user['id']);
        $this->logActivity($user['id'], 'login', 'تسجيل دخول ناجح');
        
        $response['success'] = true;
        $response['message'] = 'تم تسجيل الدخول بنجاح';
        $response['redirect'] = $this->getUserDashboard($user['role']);
        
        return $response;
    }
    
    /**
     * تسجيل الخروج
     * @return bool
     */
    public function logout() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id) {
            $this->logActivity($user_id, 'logout', 'تسجيل خروج');
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
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * الحصول على بيانات المستخدم الحالي
     * @return array|null
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'department' => $_SESSION['department'] ?? '',
            'avatar' => $_SESSION['avatar'] ?? ''
        ];
    }
    
    /**
     * التحقق من الصلاحية
     * @param string $permission الصلاحية المطلوبة
     * @return bool
     */
    public function can($permission) {
        if (!$this->check()) {
            return false;
        }
        
        $role = $_SESSION['role'];
        $permissions = $this->getRolePermissions($role);
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    /**
     * التحقق من الدور
     * @param string $role الدور المطلوب
     * @return bool
     */
    public function is($role) {
        return $this->check() && $_SESSION['role'] === $role;
    }
    
    /**
     * التحقق من أحد الأدوار
     * @param array $roles قائمة الأدوار
     * @return bool
     */
    public function isAny($roles) {
        return $this->check() && in_array($_SESSION['role'], $roles);
    }
    
    /**
     * الحصول على المستخدم من قاعدة البيانات
     * @param string $username
     * @return array|null
     */
    private function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $username]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * تخزين بيانات المستخدم في الجلسة
     * @param array $user
     */
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['avatar'] = $user['avatar'] ?? '';
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // تجديد معرف الجلسة لمنع fixation
        session_regenerate_id(true);
    }
    
    /**
     * تحديث آخر تسجيل دخول
     * @param int $user_id
     */
    private function updateLastLogin($user_id) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
    }
    
    /**
     * الحصول على لوحة التحكم حسب الدور
     * @param string $role
     * @return string
     */
    private function getUserDashboard($role) {
        $dashboards = [
            'admin' => '/cloud-unit/pages/dashboard.php',
            'manager' => '/cloud-unit/pages/dashboard.php',
            'engineer' => '/cloud-unit/pages/dashboard.php',
            'viewer' => '/cloud-unit/pages/dashboard.php'
        ];
        
        return $dashboards[$role] ?? '/cloud-unit/index.php';
    }
    
    /**
     * الحصول على صلاحيات الدور
     * @param string $role
     * @return array
     */
    private function getRolePermissions($role) {
        $permissions = [
            'admin' => ['*'],
            'manager' => [
                'view_all_servers',
                'view_all_projects',
                'create_server',
                'edit_server',
                'delete_server',
                'create_project',
                'edit_project',
                'delete_project',
                'deploy',
                'backup',
                'restore',
                'view_reports',
                'manage_users'
            ],
            'engineer' => [
                'view_assigned_servers',
                'view_assigned_projects',
                'deploy',
                'backup',
                'restore',
                'restart_services',
                'view_logs',
                'monitor'
            ],
            'viewer' => [
                'view_assigned_servers',
                'view_assigned_projects',
                'view_reports'
            ]
        ];
        
        return $permissions[$role] ?? [];
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
     * @param string $username
     */
    private function logFailedAttempt($ip, $username) {
        $sql = "INSERT INTO login_attempts (ip_address, username, attempt_time) 
                VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip, $username]);
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
     * @param int $user_id
     * @param string $action
     * @param string $description
     */
    private function logActivity($user_id, $action, $description) {
        $sql = "INSERT INTO cloud_activity_log (user_id, activity_type, target_type, target_id, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, 'system', 0, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    /**
     * تغيير كلمة المرور
     * @param int $user_id
     * @param string $old_password
     * @param string $new_password
     * @return array
     */
    public function changePassword($user_id, $old_password, $new_password) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // الحصول على المستخدم
        $sql = "SELECT password_hash FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $response['message'] = 'المستخدم غير موجود';
            return $response;
        }
        
        // التحقق من كلمة المرور القديمة
        if (!password_verify($old_password, $user['password_hash'])) {
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
        $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$new_hash, $user_id])) {
            $this->logActivity($user_id, 'change_password', 'تغيير كلمة المرور');
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
        // 8 أحرف على الأقل
        if (strlen($password) < 8) {
            return false;
        }
        
        // حرف كبير
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // حرف صغير
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // رقم
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // رمز خاص
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return false;
        }
        
        return true;
    }
}

/**
 * =============================================
 * دوال مساعدة لل Auth
 * =============================================
 */

/**
 * إنشاء كائن Auth
 * @param PDO $db
 * @return Auth
 */
function auth($db) {
    static $auth = null;
    
    if ($auth === null) {
        $auth = new Auth($db);
    }
    
    return $auth;
}

/**
 * التحقق من تسجيل الدخول وإعادة التوجيه
 * @param PDO $db
 */
function requireLogin($db) {
    $auth = auth($db);
    
    if (!$auth->check()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * التحقق من الصلاحية وإعادة التوجيه
 * @param PDO $db
 * @param string $permission
 */
function requirePermission($db, $permission) {
    $auth = auth($db);
    
    requireLogin($db);
    
    if (!$auth->can($permission)) {
        header('Location: /unauthorized.php');
        exit();
    }
}

/**
 * التحقق من الدور وإعادة التوجيه
 * @param PDO $db
 * @param string $role
 */
function requireRole($db, $role) {
    $auth = auth($db);
    
    requireLogin($db);
    
    if (!$auth->is($role)) {
        header('Location: /unauthorized.php');
        exit();
    }
}

/**
 * عرض رسالة ترحيب للمستخدم
 * @param array $user
 * @return string
 */
function renderUserWelcome($user) {
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
            <p class='font-semibold'>{$user['full_name']}</p>
            <p class='text-xs text-gray-400'>{$user['department']}</p>
        </div>
        <div class='w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center'>
            <span class='text-white font-bold text-lg'>
                " . mb_substr($user['full_name'], 0, 1) . "
            </span>
        </div>
    </div>";
}

/**
 * =============================================
 * نهاية الملف
 * =============================================
 */