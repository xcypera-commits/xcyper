<?php
// index.php - بوابة النظام الرئيسية

// ============================================
// المسارات السرية (غيرها لأي أرقام عشوائية)
// ============================================
define('EMPLOYEE_SECRET', '8f7a3b2c9d1e5f4a'); // رابط الموظفين
define('CLIENT_SECRET', 'd4e5f6a7b8c9d1e2');  // رابط العملاء

// الحصول على المسار من الرابط
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// إزالة مسار المجلد الرئيسي من الرابط
$base_folder = '/Hosting-Security/';
$path = str_replace($base_folder, '', $request_uri);
$path = strtok($path, '?'); // إزالة الـ query string إن وجد
$secret_key = trim($path, '/'); // المفتاح السري

// للتصحيح (أزلها بعد التأكد من العمل)
// echo "المفتاح المستلم: " . $secret_key; 

// ============================================
// 1. التحقق من الرابط السري للموظفين
// ============================================
if (!empty($secret_key) && $secret_key === EMPLOYEE_SECRET) {
    // بدأ الجلسة
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // تخزين أنه جا من الرابط السري
    $_SESSION['access_type'] = 'employee_secret';
    
    // توجيهه لصفحة دخول الموظفين
    header('Location: /Hosting-Security/pages/admin/staff_login.php');
    exit;
}

// ============================================
// 2. التحقق من الرابط السري للعملاء
// ============================================
if (!empty($secret_key) && $secret_key === CLIENT_SECRET) {
    // بدأ الجلسة
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // تخزين أنه جا من الرابط السري
    $_SESSION['access_type'] = 'client_secret';
    
    // توجيهه لصفحة دخول العملاء
    header('Location: /Hosting-Security/pages/clientHostingSecurity/login.php');
    exit;
}

// ============================================
// 3. أي حالة أخرى - وجهه لصفحة عرض العميل
// ============================================
header('Location: /Hosting-Security/pages/HOM/index.php');
exit;
?>