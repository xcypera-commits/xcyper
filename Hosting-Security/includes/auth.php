 
<?php
/**
 * دوال المصادقة
 */

// منع الوصول المباشر
if (!defined('SECURITY_ACCESS')) {
    define('SECURITY_ACCESS', true);
}

 
/**
 * التحقق من تسجيل الدخول
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * الحصول على معرف المستخدم الحالي
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * الحصول على اسم المستخدم الحالي
 */
function current_username() {
    return $_SESSION['username'] ?? 'زائر';
}

/**
 * التحقق من دور المستخدم
 */
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * طلب تسجيل الدخول
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('/pages/clientHostingSecurity/login.php');
    }
}

?>