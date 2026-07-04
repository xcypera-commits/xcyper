<?php
/**
 * الصفحة الرئيسية للوحة تحكم المدير
 * تقوم بالتحقق من تسجيل الدخول وتوجيه المستخدم
 */
//require_once '../../../../security-init.php';
require_once '../../../../security-init.php';
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) ) {
    header('Location: ../../staff_login.php');
    exit();
}

// توجيه إلى لوحة التحكم
header('Location: pages/dashboard.php');
exit();


/*
admin-panel/
│
├── index.php                               # الصفحة الرئيسية (توجيه تلقائي)
│
├── config/
│   └── database.php                        # اتصال قاعدة البيانات
│
├── includes/
│   ├── functions.php                        # دوال مساعدة عامة
│   ├── auth.php                              # التحقق من المستخدم
│   └── admin_functions.php                   # دوال خاصة بالمدير
│
└── pages/
    ├── dashboard.php                          # لوحة التحكم الرئيسية
    ├── users-management.php                    # إدارة المستخدمين
    ├── roles-permissions.php                    # إدارة الأدوار والصلاحيات
    ├── audit-logs.php                            # سجلات التدقيق والبحث المتقدم
    ├── security-settings.php                      # إعدادات الأمان المتقدمة
    ├── projects.php                                # إدارة المشاريع
    ├── reports.php                                  # التقارير الشاملة
    ├── activity.php                                  # سجل النشاطات
    └── settings.php                                   # إعدادات النظام
    */
?>