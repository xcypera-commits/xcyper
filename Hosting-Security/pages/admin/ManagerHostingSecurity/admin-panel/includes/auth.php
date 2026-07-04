<?php
/**
 * نظام المصادقة والصلاحيات
 */

// منع الوصول المباشر
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}
// للاختبار فقط - سجل دخول تلقائي
if (!isset($_SESSION['user_id']) && !defined('SKIP_AUTH')) {
    // ابحث عن المستخدم admin
    $stmt = $db->prepare("SELECT * FROM users_all WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['login_time'] = time();
    }
}
/**
 * التحقق من تسجيل الدخول
 */


/**
 * التحقق من صلاحية المدير
 */


/**
 * طلب تسجيل الدخول
 */


/**
 * طلب صلاحية المدير
 */


/**
 * تسجيل الدخول
 */
function login($username, $password) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT * FROM users_all WHERE username = ? AND deleted_at IS NULL");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['login_time'] = time();
            
            // تحديث آخر دخول
            $update = $db->prepare("UPDATE users_all SET last_login = NOW(), last_login_ip = ? WHERE id = ?");
            $update->execute([$_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $user['id']]);
            
            // تسجيل الحدث
            log_activity($user['id'], 'login', ['status' => 'success']);
            
            return true;
        }
        
        log_activity(0, 'login_failed', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
        return false;
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل الخروج
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'logout', []);
    }
    
    $_SESSION = [];
    session_destroy();
}

/**
 * الحصول على المستخدم الحالي
 */
function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM users_all WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * التحقق من الصلاحية
 */
function has_permission($permission) {
    if (!isset($_SESSION['user_id'])) return false;
    
    // المدير له كل الصلاحيات
    if ($_SESSION['user_type'] === 'admin') return true;
    
    // يمكن إضافة نظام صلاحيات متقدم هنا
    return true;
}
?>