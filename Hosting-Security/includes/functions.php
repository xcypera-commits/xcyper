<?php
/**
 * دوال مساعدة عامة
 */

// منع الوصول المباشر
if (!defined('SECURITY_ACCESS')) {
    define('SECURITY_ACCESS', true);
}

/**
 * تنظيف المدخلات
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * تنسيق التاريخ
 */
function format_date($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * إنشاء رسالة خطأ
 */
function set_error($message) {
    $_SESSION['error'] = $message;
}

/**
 * إنشاء رسالة نجاح
 */
function set_success($message) {
    $_SESSION['success'] = $message;
}

/**
 * عرض الرسائل
 */
function display_messages() {
    $output = '';
    if (isset($_SESSION['error'])) {
        $output .= '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        $output .= '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    return $output;
}

/**
 * التحقق من طريقة الطلب
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * التحقق من طريقة الطلب
 */
function is_get() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * إعادة التوجيه
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * الحصول على قيمة من POST
 */
function post($key, $default = '') {
    return isset($_POST[$key]) ? sanitize_input($_POST[$key]) : $default;
}

/**
 * الحصول على قيمة من GET
 */
function get($key, $default = '') {
    return isset($_GET[$key]) ? sanitize_input($_GET[$key]) : $default;
}

/**
 * الحصول على قيمة من SESSION
 */
function session($key, $default = '') {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

/**
 * التحقق من وجود ملف
 */
function file_exists_safe($path) {
    return file_exists($path) && is_file($path);
}

/**
 * إنشاء رابط
 */
function url($path = '') {
    $base = 'http://' . $_SERVER['HTTP_HOST'] . '/Hosting-Security';
    return $base . '/' . ltrim($path, '/');
}

/**
 * إنشاء رابط آمن
 */
function secure_url($path = '') {
    $base = 'https://' . $_SERVER['HTTP_HOST'] . '/Hosting-Security';
    return $base . '/' . ltrim($path, '/');
}
?>