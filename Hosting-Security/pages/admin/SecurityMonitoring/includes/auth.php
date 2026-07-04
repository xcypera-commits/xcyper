<?php
// includes/auth.php
session_start();

// التحقق من تسجيل دخول العميل (للتطوير نستخدم client_id=1)
// في الإنتاج الفعلي، نستخدم requireClientLogin($db)
if (!isset($_SESSION['user_id'])) {
    // للاختبار، نسجل دخول العميل الأول تلقائياً
    //$_SESSION['client_id'] = 1;
}


// بيانات المستخدم الحالي (مؤقتة للتطوير)
$current_user = [
    'id' => $_SESSION['user_id'] ?? 0,
    'name' => $_SESSION['full_name'] ?? 'موظف',
    'email' => $_SESSION['user_email'] ?? '',
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['user_role'] ?? 'viewer',
    'department' => $_SESSION['user_department'] ?? '',
    'can_manage' => $_SESSION['can_manage'] ?? 0,
    'avatar' => '' // ممكن تجيبها من قاعدة البيانات لو عندك حقل صورة
];

?>