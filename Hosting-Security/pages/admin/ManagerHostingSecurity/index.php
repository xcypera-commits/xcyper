<?php

// =============================================
// manager/index.php - لوحة تحكم مدير النظام
// =============================================


// باقي الكود...
// =============================================
// manager/index.php - لوحة تحكم مدير النظام
// =============================================
session_start();
// تشغيل عرض الأخطاء (للتصحيح)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../../security-init.php';
// المسار الأساسي
define('BASE_PATH', __DIR__);
define('BASE_URL', '/manager');

// تحميل الملفات الأساسية
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/manager_functions.php';

// الاتصال بقاعدة البيانات
$db = getDB();

// =============================================
// التحقق من صلاحية المدير
// =============================================
// يمكن تفعيل هذا لاحقاً بعد إضافة نظام تسجيل الدخول

// بدء الجلسة والتحقق من المستخدم


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

// =============================================
// تحديد الصفحة المطلوبة
// =============================================
$page = $_GET['page'] ?? 'operational-dashboard';

// قائمة الصفحات المسموح بها
$allowed_pages = [
    'operational-dashboard',
    'unit-management',
    'active-projects',
    'security-monitoring',
    'performance-review',
    'incident-management',
    'resource-allocation',
    'compliance-check',
    'system-audit',
    'reports-archive',
    'العلوم و التكنلو'
];

// إذا كانت الصفحة غير مسموح بها، انتقل إلى الصفحة الرئيسية
if (!in_array($page, $allowed_pages)) {
    $page = 'operational-dashboard';
}

// عناوين الصفحات
$page_titles = [
    'operational-dashboard' => 'لوحة القيادة التشغيلية',
    'unit-management' => 'إدارة الوحدات الأربع',
    'active-projects' => 'المشاريع النشطة',
    'security-monitoring' => 'المراقبة الأمنية',
    'performance-review' => 'مراجعة أداء الوحدات',
    'incident-management' => 'إدارة الحوادث',
    'resource-allocation' => 'تخصيص الموارد',
    'compliance-check' => 'فحص الامتثال',
    'system-audit' => 'تدقيق النظام',
    'reports-archive' => 'الأرشيف والتقارير',
    'العلوم و التكنلو'
];

// =============================================
// جلب إحصائيات سريعة للعرض في الشريط العلوي
// =============================================
try {
    // عدد المشاريع النشطة
    $active_projects_count = $db->query("SELECT COUNT(*) FROM projects WHERE status != 'completed'")->fetchColumn() ?: 0;
    
    // عدد الحوادث المفتوحة
    $open_incidents_count = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open', 'in-progress')")->fetchColumn() ?: 0;
    
    // عدد التنبيهات الحرجة
    $critical_alerts_count = $db->query("SELECT COUNT(*) FROM alerts WHERE type = 'critical' AND status != 'resolved'")->fetchColumn() ?: 0;
    
    // عدد الموافقات المعلقة
    $pending_approvals_count = $db->query("SELECT COUNT(*) FROM pending_approvals WHERE status = 'pending'")->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    // إذا حدث خطأ، استخدم القيم الافتراضية
    $active_projects_count = 42;
    $open_incidents_count = 7;
    $critical_alerts_count = 3;
    $pending_approvals_count = 5;
}

// دوال مساعدة
function isActive($current_page, $target_page) {
    return $current_page === $target_page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الاستضافة السحابي - <?php echo $page_titles[$page]; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome (للأيقونات) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Chart.js (للرسوم البيانية) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ===== التصميم الأساسي ===== */
        * {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #f1f5f9;
        }
        
        /* ===== تأثيرات خاصة ===== */
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        }
        
        /* ===== الحدود الأمنية ===== */
        .security-border {
            border: 2px solid rgba(59, 130, 246, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .security-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* ===== مؤشر الحالة ===== */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 8px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* ===== عناصر القائمة ===== */
        .nav-item {
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
            cursor: pointer;
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(59, 130, 246, 0.1);
            border-right-color: #3b82f6;
        }
        
        /* ===== شارات الوحدات ===== */
        .unit-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .unit-documentation {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .unit-storage {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        
        .unit-security {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .unit-pentest {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        /* ===== أولويات المشاريع ===== */
        .priority-high {
            border-right: 4px solid #ef4444;
        }
        
        .priority-medium {
            border-right: 4px solid #f59e0b;
        }
        
        .priority-low {
            border-right: 4px solid #10b981;
        }
        
        /* ===== شريط التمرير المخصص ===== */
        .scrollbar-custom::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollbar-custom::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        .scrollbar-custom::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 4px;
        }
        
        .scrollbar-custom::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
        
        /* ===== شريط التقدم ===== */
        .progress-bar {
            height: 6px;
            background: #1e293b;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            transition: width 0.3s ease;
        }
        
        /* ===== بطاقات المدير ===== */
        .manager-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.8));
            border: 1px solid rgba(59, 130, 246, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .kpi-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(96, 165, 250, 0.1));
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        /* ===== تنبيه حرج ===== */
        .alert-critical {
            animation: alertPulse 2s ease-in-out infinite;
            border-left: 4px solid #ef4444;
        }
        
        @keyframes alertPulse {
            0%, 100% { border-left-color: #ef4444; }
            50% { border-left-color: #f87171; }
        }
        
        /* ===== توهج العمليات ===== */
        .operation-glow {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
        }
        
        /* ===== خلفية النوافذ المنبثقة ===== */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        /* ===== مؤشر التحميل ===== */
        .spinner {
            border: 3px solid rgba(59, 130, 246, 0.3);
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ===== الإشعارات ===== */
        .notification {
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* ===== رأس الجدول ===== */
        .table-header {
            background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
        }
    </style>
</head>
<body class="h-full gradient-bg text-gray-100">
    <div id="app" class="h-full w-full flex overflow-hidden">
        <!-- ===== القائمة الجانبية (Sidebar) ===== -->
        <aside class="w-64 bg-slate-900 border-l border-slate-700 flex flex-col">
            <!-- الشعار -->
            <div class="p-6 border-b border-slate-700">
                <div class="flex items-center justify-center">
                    <svg class="w-12 h-12 ml-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="#3b82f6" stroke-width="2"/>
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="#3b82f6" stroke-width="2"/>
                    </svg>
                    <div>
                        <h1 class="text-xl font-bold text-blue-400">نظام الاستضافة</h1>
                        <p class="text-xs text-gray-400">مدير النظام والأمن</p>
                    </div>
                </div>
            </div>

            <!-- قائمة التنقل -->
            <nav class="flex-1 overflow-y-auto scrollbar-custom p-4">
                <div class="space-y-2">
                    <a href="?page=operational-dashboard" class="nav-item <?php echo isActive($page, 'operational-dashboard'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>لوحة القيادة التشغيلية</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </a>

                    <a href="?page=unit-management" class="nav-item <?php echo isActive($page, 'unit-management'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>إدارة الوحدات الأربع</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </a>

                    <a href="?page=active-projects" class="nav-item <?php echo isActive($page, 'active-projects'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>المشاريع النشطة</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </a>

                    <a href="?page=security-monitoring" class="nav-item <?php echo isActive($page, 'security-monitoring'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>المراقبة الأمنية</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </a>

                    <a href="?page=performance-review" class="nav-item <?php echo isActive($page, 'performance-review'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>مراجعة أداء الوحدات</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </a>

                    <a href="?page=incident-management" class="nav-item <?php echo isActive($page, 'incident-management'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>إدارة الحوادث</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.858-.833-2.628 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </a>
                    
                    <a href="?page=incident-management" class="nav-item <?php echo isActive($page, 'incident-management'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>العلوم </span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.858-.833-2.628 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </a>

                    <a href="?page=resource-allocation" class="nav-item <?php echo isActive($page, 'resource-allocation'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>تخصيص الموارد</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </a>

                    <a href="?page=compliance-check" class="nav-item <?php echo isActive($page, 'compliance-check'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>فحص الامتثال</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </a>

                    <a href="?page=system-audit" class="nav-item <?php echo isActive($page, 'system-audit'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>تدقيق النظام</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>

                    <a href="?page=reports-archive" class="nav-item <?php echo isActive($page, 'reports-archive'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>الأرشيف والتقارير</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </a>
                </div>
            </nav>

            <!-- معلومات المستخدم -->
            <div class="p-4 border-t border-slate-700">
                <div class="flex items-center justify-end">
                    <div class="text-right ml-3">
                        <p class="text-sm font-semibold"><?php echo $current_user['name']; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $current_user['email']; ?></p>
                        <div class="flex items-center mt-1">
                            <span class="status-indicator bg-green-500"></span>
                            <span class="text-xs text-green-400 mr-1">متصل</span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-600 to-cyan-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.879 6.196 9 9 0 015.121 17.804zM15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ===== المحتوى الرئيسي ===== -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- الشريط العلوي -->
            <header class="bg-slate-900 border-b border-slate-700 px-8 py-4">
                <div class="flex items-center justify-between">
                    <!-- الأزرار الرئيسية -->
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <button onclick="escalateIncident()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.858-.833-2.628 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            تصعيد حادث
                        </button>
                        <button  class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.858-.833-2.628 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                           <a href="<?php echo $_SERVER['REQUEST_URI']; ?>admin-panel/index.php">لوحة التحكم ب الحماية </a>
                        </button>
                        <button onclick="pauseService()" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            إيقاف خدمة مؤقت
                        </button>
                        <button onclick="generateOperationalReport()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all operation-glow flex items-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            إنشاء تقرير تشغيلي
                        </button>
                    </div>

                    <!-- عنوان الصفحة -->
                    <div class="flex items-center">
                        <div class="ml-6 text-right">
                            <h2 class="text-2xl font-bold text-blue-400"><?php echo $page_titles[$page]; ?></h2>
                            <p class="text-sm text-gray-400">مرحباً بك في لوحة إدارة النظام والأمن</p>
                        </div>
                    </div>
                </div>

                <!-- شريط الإحصائيات السريعة -->
                <div class="grid grid-cols-4 gap-4 mt-4">
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">المشاريع النشطة</p>
                            <p class="text-lg font-bold text-blue-400"><?php echo $active_projects_count; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">الحوادث المفتوحة</p>
                            <p class="text-lg font-bold text-red-400"><?php echo $open_incidents_count; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.858-.833-2.628 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">تنبيهات حرجة</p>
                            <p class="text-lg font-bold text-yellow-400"><?php echo $critical_alerts_count; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">موافقات معلقة</p>
                            <p class="text-lg font-bold text-purple-400"><?php echo $pending_approvals_count; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </header>

            <!-- منطقة المحتوى الديناميكي -->
            <div id="content-area" class="flex-1 overflow-y-auto scrollbar-custom p-6">
                <?php
                $page_file = BASE_PATH . '/pages/manager/' . $page . '.php';
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    echo '<div class="text-center p-8 bg-slate-800 rounded-lg">';
                    echo '<p class="text-red-400">⚠️ الصفحة المطلوبة غير موجودة</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- ===== حاوية الإشعارات ===== -->
    <div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

    <!-- ===== مؤشر التحميل ===== -->
    <div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-400">جاري التحميل...</p>
        </div>
    </div>

    <!-- ===== نافذة إيقاف الخدمة الطارئ ===== -->
    <div id="emergency-stop-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-red-400 mb-4 text-right">⚠️ إيقاف خدمة طارئ</h3>
            <p class="text-gray-300 mb-6 text-right">أنت على وشك إيقاف خدمة بشكل طارئ. هذا الإجراء سيؤثر على العملاء.</p>
            
            <div class="mb-6">
                <label class="block text-right text-gray-400 mb-2">سبب الإيقاف</label>
                <select id="stop-reason" class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-right">
                    <option value="security">تهديد أمني حرج</option>
                    <option value="maintenance">صيانة طارئة</option>
                    <option value="failure">عطل نظام</option>
                    <option value="other">سبب آخر</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-right text-gray-400 mb-2">التنبيه للعملاء</label>
                <textarea id="customer-alert" class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-right" rows="3" placeholder="رسالة تنبيه للعملاء..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse">
                <button onclick="confirmEmergencyStop()" class="flex-1 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold">
                    تأكيد الإيقاف
                </button>
                <button onclick="cancelEmergencyStop()" class="flex-1 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold">
                    إلغاء
                </button>
            </div>
        </div>
    </div>

    <!-- ===== السكريبتات ===== -->
    <script>
        // =============================================
        // المتغيرات العامة
        // =============================================
        const currentUser = <?php echo json_encode($current_user); ?>;
        const BASE_URL = '<?php echo BASE_URL; ?>';

        // =============================================
        // دوال الإشعارات
        // =============================================
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const colors = {
                'success': 'bg-green-600',
                'error': 'bg-red-600',
                'info': 'bg-blue-600',
                'warning': 'bg-yellow-600',
                'critical': 'bg-red-700'
            };
            const notification = document.createElement('div');
            notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
            notification.textContent = message;
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // =============================================
        // دوال التحميل
        // =============================================
        function showLoading() {
            document.getElementById('loading-spinner').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-spinner').classList.add('hidden');
        }

        // =============================================
        // دوال الطوارئ
        // =============================================
        function escalateIncident() {
            showNotification('جاري تصعيد الحادث للإدارة العليا...', 'warning');
        }

        function pauseService() {
            document.getElementById('emergency-stop-modal').classList.remove('hidden');
        }

        function confirmEmergencyStop() {
            const reason = document.getElementById('stop-reason').value;
            const alertMessage = document.getElementById('customer-alert').value;
            
            showNotification('تم إيقاف الخدمة بشكل طارئ. جاري إخطار العملاء...', 'critical');
            document.getElementById('emergency-stop-modal').classList.add('hidden');
            
            // إعادة تعيين الحقول
            document.getElementById('stop-reason').value = 'security';
            document.getElementById('customer-alert').value = '';
        }

        function cancelEmergencyStop() {
            document.getElementById('emergency-stop-modal').classList.add('hidden');
            showNotification('تم إلغاء عملية الإيقاف الطارئ', 'info');
        }

        function generateOperationalReport() {
            showNotification('جاري إنشاء التقرير التشغيلي الشهري...', 'info');
            showLoading();
            
            setTimeout(() => {
                hideLoading();
                showNotification('تم إنشاء التقرير بنجاح', 'success');
            }, 2000);
        }

        // =============================================
        // دوال إدارة الوحدات
        // =============================================
        function refreshUnitStatus() {
            showNotification('جاري تحديث حالة الوحدات...', 'info');
        }

        function handleAlert(alertId) {
            showNotification(`جاري معالجة التنبيه ${alertId}`, 'warning');
        }

        function viewAllActivities() {
            showNotification('فتح سجل النشاطات الكامل', 'info');
        }

        // =============================================
        // دوال الموافقات
        // =============================================
        function approveRequest(requestId) {
            showNotification(`تمت الموافقة على الطلب ${requestId}`, 'success');
        }

        function rejectRequest(requestId) {
            showNotification(`تم رفض الطلب ${requestId}`, 'error');
        }

        function reviewRequest(requestId) {
            showNotification(`مراجعة الطلب ${requestId}`, 'info');
        }

        // =============================================
        // دوال المشاريع
        // =============================================
        function reassignProject() {
            showNotification('فتح نافذة إعادة توزيع المشروع', 'info');
        }

        function filterProjects(filter) {
            showNotification(`تصفية المشاريع حسب: ${filter}`, 'info');
        }

        function reviewProject(projectId) {
            showNotification(`مراجعة مشروع ${projectId}`, 'info');
        }

        // =============================================
        // دوال الأمن
        // =============================================
        function triggerSecurityScan() {
            showLoading();
            showNotification('بدء فحص أمني شامل للنظام', 'warning');
            
            setTimeout(() => {
                hideLoading();
                showNotification('اكتمل الفحص الأمني - لا توجد ثغرات حرجة', 'success');
            }, 3000);
        }

        function blockIP() {
            showNotification('فتح نافذة حظر عناوين IP', 'info');
        }

        function viewThreatMap() {
            showNotification('فتح خريطة التهديدات العالمية', 'info');
        }

        function investigateEvent(eventId) {
            showNotification(`التحقيق في الحدث ${eventId}`, 'info');
        }

        // =============================================
        // دوال الأداء
        // =============================================
        function generatePerformanceReport() {
            showNotification('جاري إنشاء تقرير أداء الوحدات', 'info');
        }

        function scheduleReviewMeeting() {
            showNotification('جدولة اجتماع مراجعة الأداء', 'info');
        }

        // =============================================
        // دوال الحوادث
        // =============================================
        function declareMajorIncident() {
            showNotification('إعلان حادث كبير - تفعيل خطة الطوارئ', 'critical');
        }

        function assignIncident() {
            showNotification('تكليف فريق بالحادث', 'info');
        }

        function updateIncident(incidentId) {
            showNotification(`تحديث حالة الحادث ${incidentId}`, 'info');
        }

        // =============================================
        // دوال الموارد
        // =============================================
        function allocateResources() {
            showNotification('فتح نافذة تخصيص الموارد', 'info');
        }

        function requestAdditionalResources() {
            showNotification('إنشاء طلب موارد إضافية', 'info');
        }

        function approveResource(requestId) {
            showNotification(`موافقة على طلب الموارد ${requestId}`, 'success');
        }

        function rejectResource(requestId) {
            showNotification(`رفض طلب الموارد ${requestId}`, 'error');
        }

        // =============================================
        // دوال الامتثال
        // =============================================
        function runComplianceScan() {
            showNotification('بدء فحص الامتثال للمعايير', 'info');
        }

        function generateComplianceReport() {
            showNotification('إنشاء تقرير الامتثال الربعي', 'info');
        }

        function scheduleAudit() {
            showNotification('فتح نافذة جدولة تدقيق', 'info');
        }

        function fixViolation(violationId) {
            showNotification(`معالجة الانحراف ${violationId}`, 'success');
        }

        // =============================================
        // دوال التدقيق
        // =============================================
        function initiateAudit() {
            showNotification('بدء عملية تدقيق جديدة', 'info');
        }

        function viewAuditHistory() {
            showNotification('فتح سجل التدقيقات', 'info');
        }

        function generateAuditReport() {
            showNotification('إنشاء تقرير التدقيق', 'info');
        }

        function viewAuditDetails(auditId) {
            showNotification(`عرض تفاصيل التدقيق ${auditId}`, 'info');
        }

        // =============================================
        // دوال الأرشيف
        // =============================================
        function searchReports() {
            showNotification('فتح البحث المتقدم عن التقارير', 'info');
        }

        function bulkExport() {
            showNotification('بدء تصدير التقارير المجمع', 'info');
        }

        function backupArchive() {
            showNotification('بدء نسخ الأرشيف احتياطياً', 'info');
        }

        function viewReport(reportId) {
            showNotification(`فتح التقرير ${reportId}`, 'info');
        }

        function downloadReport(reportId) {
            showNotification(`تحميل التقرير ${reportId}`, 'success');
        }

        // =============================================
        // تهيئة الصفحة
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ لوحة تحكم المدير جاهزة');
            
            // تحديث البيانات الحية كل 30 ثانية
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    console.log('🔄 تحديث البيانات...');
                }
            }, 30000);
        });
    </script>
    <!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 مساعد الروبوت الذكي</title>
    <style>
        /* ===== ملف CSS مدمج ===== */
        :root {
            /* ألوان داكنة متطورة */
            --primary-color: #3a86ff;
            --secondary-color: #1e4b8c;
            --accent-color: #00b4d8;
            --bot-color: #3a86ff;
            --user-color: #00b894;
            --bg-color: #1a1b2e;
            --bg-secondary: #252a41;
            --text-color: #e9ecef;
            --text-muted: #adb5bd;
            --border-color: #343a52;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --hover-color: #2c3152;
            
            /* ألوان إضافية */
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #e17055;
            --info: #74b9ff;
        }

        /* زر الروبوت العائم - تصميم إيموجي */
        .chatbot-toggle {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(58, 134, 255, 0.3);
            z-index: 9999;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.2);
            font-size: 40px;
            animation: robotFloat 3s ease-in-out infinite;
        }

        @keyframes robotFloat {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-10px) rotate(5deg);
            }
        }

        .chatbot-toggle:hover {
            transform: scale(1.15) rotate(360deg);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 15px 40px rgba(58, 134, 255, 0.5);
        }

        .chatbot-icon {
            font-size: 40px;
            line-height: 1;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
        }

        .chatbot-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: bold;
            border: 3px solid var(--bg-color);
            box-shadow: 0 4px 12px rgba(225, 112, 85, 0.4);
            animation: notificationPulse 2s infinite;
        }

        @keyframes notificationPulse {
            0%, 100% {
                transform: scale(1);
                background: var(--danger);
            }
            50% {
                transform: scale(1.15);
                background: #ff6b6b;
            }
        }

        /* نافذة الدردشة - تصميم داكن */
        .chatbot-window {
            position: fixed;
            bottom: 120px;
            left: 30px;
            width: 380px;
            max-width: 90vw;
            height: 600px;
            max-height: 80vh;
            background: var(--bg-color);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            display: none;
            flex-direction: column;
            z-index: 9998;
            overflow: hidden;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }

        .chatbot-window.active {
            display: flex;
            animation: windowSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes windowSlideIn {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes windowSlideOut {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
                display: none;
            }
        }

        .chatbot-window.closing {
            animation: windowSlideOut 0.3s ease forwards;
        }

        /* هيدر الدردشة */
        .chatbot-header {
            background: linear-gradient(135deg, var(--bg-secondary), #1e1f33);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            border-bottom: 2px solid var(--primary-color);
        }

        .chatbot-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color), var(--success));
            transform: scaleX(0);
            animation: headerGlow 3s infinite;
        }

        @keyframes headerGlow {
            0%, 100% {
                transform: scaleX(0.3);
                opacity: 0.5;
            }
            50% {
                transform: scaleX(1);
                opacity: 1;
            }
        }

        .chatbot-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(58, 134, 255, 0.3);
        }

        .chatbot-title h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chatbot-title p {
            margin: 6px 0 0;
            font-size: 12px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
        }

        .chatbot-title p::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            display: inline-block;
            animation: onlinePulse 2s infinite;
        }

        @keyframes onlinePulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(0, 184, 148, 0.5);
            }
            50% {
                box-shadow: 0 0 0 5px rgba(0, 184, 148, 0);
            }
        }

        /* زر الإغلاق */
        .chatbot-close {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chatbot-close:hover {
            background: var(--danger);
            transform: rotate(90deg) scale(1.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* منطقة الرسائل */
        .chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: var(--bg-color);
            scroll-behavior: smooth;
        }

        /* شريط التمرير */
        .chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chatbot-messages::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 3px;
        }

        .chatbot-messages::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .chatbot-messages::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        /* الرسائل */
        .message {
            display: flex;
            margin-bottom: 20px;
            gap: 12px;
            animation: messageFadeIn 0.4s ease;
        }

        @keyframes messageFadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bot-message {
            justify-content: flex-start;
        }

        .user-message {
            justify-content: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bot-color), var(--secondary-color));
            color: white;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }

        .user-message .message-avatar {
            background: linear-gradient(135deg, var(--user-color), #00a187);
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.3);
        }

        .message-content {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }

        .bot-message .message-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-top-right-radius: 5px;
            color: var(--text-color);
        }

        .user-message .message-content {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom-left-radius: 5px;
        }

        .message-content p {
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
        }

        .message-time {
            display: block;
            font-size: 10px;
            opacity: 0.7;
            margin-top: 6px;
            text-align: left;
            font-family: 'Courier New', monospace;
        }

        .user-message .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        /* الاقتراحات السريعة */
        .chatbot-suggestions {
            padding: 16px 20px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-height: 120px;
            overflow-y: auto;
        }

        .suggestion-btn {
            padding: 10px 18px;
            background: rgba(58, 134, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .suggestion-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 134, 255, 0.3);
        }

        /* منطقة الإدخال */
        .chatbot-input-area {
            padding: 16px 20px;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--bg-color);
            border-radius: 30px;
            padding: 4px 16px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
        }

        .chatbot-input {
            flex: 1;
            padding: 12px 0;
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
            color: var(--text-color);
        }

        .chatbot-input::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        .input-action-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
            opacity: 0.8;
            transition: all 0.3s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .input-action-btn:hover {
            opacity: 1;
            color: var(--primary-color);
            background: rgba(58, 134, 255, 0.1);
        }

        .send-btn {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 16px rgba(58, 134, 255, 0.3);
        }

        .send-btn:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.5);
        }

        .send-btn svg {
            width: 24px;
            height: 24px;
            fill: white;
        }

        /* الفوتر */
        .chatbot-footer {
            padding: 12px 20px;
            background: var(--bg-color);
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-footer p {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #chatbot-time {
            color: var(--accent-color);
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        /* مؤشر الكتابة */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border-radius: 18px;
            border: 1px solid var(--border-color);
            width: fit-content;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            animation: typingBounce 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typingBounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-8px);
                opacity: 1;
            }
        }

        /* رسائل الخطأ */
        .error-message {
            background: rgba(225, 112, 85, 0.1) !important;
            border-color: var(--danger) !important;
            color: var(--danger) !important;
        }

        /* رسائل النجاح */
        .success-message {
            background: rgba(0, 184, 148, 0.1) !important;
            border-color: var(--success) !important;
            color: var(--success) !important;
        }

        /* التجاوب مع الشاشات الصغيرة */
        @media (max-width: 768px) {
            .chatbot-toggle {
                bottom: 20px;
                left: 20px;
                width: 60px;
                height: 60px;
                font-size: 35px;
            }

            .chatbot-icon {
                font-size: 35px;
            }

            .chatbot-window {
                bottom: 100px;
                left: 20px;
                width: calc(100vw - 40px);
                height: 70vh;
                border-radius: 20px;
            }

            .chatbot-suggestions {
                max-height: 100px;
            }

            .message-content {
                max-width: 80%;
            }
        }

        @media (max-width: 480px) {
            .chatbot-toggle {
                width: 55px;
                height: 55px;
                font-size: 32px;
            }

            .chatbot-icon {
                font-size: 32px;
            }

            .chatbot-notification {
                width: 24px;
                height: 24px;
                font-size: 11px;
            }

            .message-avatar {
                width: 36px;
                height: 36px;
                min-width: 36px;
                font-size: 20px;
            }

            .message-content {
                padding: 12px 14px;
                font-size: 13px;
            }

            .send-btn {
                width: 48px;
                height: 48px;
            }
        }

        /* دعم الوضع الليلي */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0a0b14;
                --bg-secondary: #151824;
                --text-color: #e9ecef;
                --border-color: #2a2e3a;
            }
        }
    </style>
</head>
<body>
    <!-- زر الروبوت العائم - شكل إيموجي -->
    <div id="chatbot-toggle" class="chatbot-toggle">
        <div class="chatbot-icon">
            🤖
        </div>
        <div class="chatbot-notification" id="chatbot-notification">3</div>
    </div>

    <!-- نافذة الدردشة -->
    <div id="chatbot-window" class="chatbot-window">
        <!-- الهيدر -->
        <div class="chatbot-header">
            <div class="chatbot-avatar">
                🤖
            </div>
            <div class="chatbot-title">
                <h3>
                    مساعد الروبوت الذكي
                    <span style="font-size: 14px; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px;">v2.0</span>
                </h3>
                <p>نظام الذكاء الاصطناعي | متصل</p>
            </div>
            <button class="chatbot-close" id="chatbot-close" aria-label="إغلاق">
                ✕
            </button>
        </div>

        <!-- منطقة المحادثة -->
        <div class="chatbot-messages" id="chatbot-messages">
            <!-- الرسالة الترحيبية -->
            <div class="message bot-message">
                <div class="message-avatar">
                    🤖
                </div>
                <div class="message-content">
                    <p>مرحباً! 👋</p>
                    <p>أنا مساعد الروبوت الذكي، جاهز لمساعدتك في خدماتنا:</p>
                    <p style="margin-top: 10px;">
                        • استضافة المواقع ☁️<br>
                        • تخزين سحابي 💾<br>
                        • حماية أمنية 🔒<br>
                        • اختبار اختراق 🛡️<br>
                        • دعم فني 24/7 ⚡
                    </p>
                    <span class="message-time">الآن</span>
                </div>
            </div>
        </div>

        <!-- الاقتراحات السريعة -->
        <div class="chatbot-suggestions" id="chatbot-suggestions">
            <button class="suggestion-btn" data-question="ما هي خدمات الاستضافة لديكم؟">
                <span>☁️</span> خدمات الاستضافة
            </button>
            <button class="suggestion-btn" data-question="كيف أقدم طلب جديد؟">
                <span>📝</span> طلب جديد
            </button>
            <button class="suggestion-btn" data-question="ما هي تكاليف التخزين السحابي؟">
                <span>💾</span> التخزين السحابي
            </button>
            <button class="suggestion-btn" data-question="كيف أتابع حالة مشروعي؟">
                <span>📊</span> متابعة المشروع
            </button>
            <button class="suggestion-btn" data-question="ما هي خدمات الحماية؟">
                <span>🔒</span> خدمات الحماية
            </button>
            <button class="suggestion-btn" data-question="كيف أتواصل مع الدعم الفني؟">
                <span>🎧</span> الدعم الفني
            </button>
        </div>

        <!-- منطقة الإدخال -->
        <div class="chatbot-input-area">
            <div class="input-wrapper">
                <input type="text" 
                       id="chatbot-input" 
                       class="chatbot-input" 
                       placeholder="اكتب سؤالك هنا..."
                       autocomplete="off"
                       aria-label="مربع النص">
                <button class="input-action-btn" id="emoji-btn" aria-label="إضافة إيموجي">
                    😊
                </button>
            </div>
            <button class="send-btn" id="send-btn" aria-label="إرسال">
                <svg viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>

        <!-- الفوتر -->
        <div class="chatbot-footer">
            <p>
                <span>🤖 مساعد ذكي</span>
                <span style="color: var(--success);">● متصل</span>
            </p>
            <p>
                <span id="chatbot-time">--:--</span>
                <span>| ⚡ 100%</span>
            </p>
        </div>
    </div>

    <script>
        // ============================================
        // ✨ إعدادات التوصيل مع N8N
        // ============================================
        const ChatbotConfig = {
            n8n: {
                // 👇 غير هذا الرابط إلى الرابط الجديد من N8N
                webhookUrl: "https://xcyper.app.n8n.cloud/webhook-test/b8f8f120-01a5-4f1b-9793-68d337d77663",
                
                // 👇 لو طلب منك API Key، حطه هنا
                apiKey: "",  // اتركه فاضي إذا ما طلب
                
                // 👇 إعدادات الاتصال
                timeout: 30000,
                retryAttempts: 3,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            },
            companyPhone: "+966500000000", // رقم الدعم
            companyName: "شركة الاستضافة",
            version: "2.0"
        };

        // ============================================
        // 🤖 كامل وظائف الروبوت
        // ============================================

        class Chatbot {
            constructor() {
                this.config = ChatbotConfig;
                this.isOpen = false;
                this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                this.init();
            }

            init() {
                // تعريف العناصر
                this.toggle = document.getElementById('chatbot-toggle');
                this.window = document.getElementById('chatbot-window');
                this.closeBtn = document.getElementById('chatbot-close');
                this.messages = document.getElementById('chatbot-messages');
                this.input = document.getElementById('chatbot-input');
                this.sendBtn = document.getElementById('send-btn');
                this.suggestions = document.getElementById('chatbot-suggestions');
                this.notification = document.getElementById('chatbot-notification');
                this.timeEl = document.getElementById('chatbot-time');

                // أحداث
                this.toggle?.addEventListener('click', () => this.toggleWindow());
                this.closeBtn?.addEventListener('click', () => this.closeWindow());
                this.sendBtn?.addEventListener('click', () => this.sendMessage());
                this.input?.addEventListener('keypress', (e) => e.key === 'Enter' && this.sendMessage());

                // اقتراحات سريعة
                this.suggestions?.querySelectorAll('.suggestion-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const question = e.currentTarget.dataset.question;
                        this.addMessage(question, 'user');
                        this.sendToN8N(question);
                    });
                });

                // وقت
                this.updateTime();
                setInterval(() => this.updateTime(), 60000);
                
                console.log('🤖 Chatbot initialized with URL:', this.config.n8n.webhookUrl);
            }

            toggleWindow() {
                this.isOpen ? this.closeWindow() : this.openWindow();
            }

            openWindow() {
                this.window?.classList.add('active');
                this.isOpen = true;
                this.notification.style.display = 'none';
                this.input?.focus();
            }

            closeWindow() {
                this.window?.classList.add('closing');
                setTimeout(() => {
                    this.window?.classList.remove('active', 'closing');
                    this.isOpen = false;
                }, 300);
            }

            async sendMessage() {
                const message = this.input?.value.trim();
                if (!message) return;
                
                this.addMessage(message, 'user');
                this.input.value = '';
                await this.sendToN8N(message);
            }

            addMessage(text, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}-message`;
                
                messageDiv.innerHTML = `
                    <div class="message-avatar">${sender === 'bot' ? '🤖' : '👤'}</div>
                    <div class="message-content">
                        <p>${this.escapeHtml(text)}</p>
                        <span class="message-time">${this.getCurrentTime()}</span>
                    </div>
                `;
                
                this.messages?.appendChild(messageDiv);
                this.messages?.scrollTo(0, this.messages.scrollHeight);
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            showTyping() {
                const typingDiv = document.createElement('div');
                typingDiv.className = 'message bot-message';
                typingDiv.id = 'typing-indicator';
                typingDiv.innerHTML = `
                    <div class="message-avatar">🤖</div>
                    <div class="typing-indicator">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                `;
                this.messages?.appendChild(typingDiv);
                this.messages?.scrollTo(0, this.messages.scrollHeight);
            }

            hideTyping() {
                document.getElementById('typing-indicator')?.remove();
            }

            async sendToN8N(message) {
                this.showTyping();
                
                try {
                    console.log('📤 Sending to N8N:', message);
                    
                    const response = await fetch(this.config.n8n.webhookUrl, {
                        method: 'POST',
                        headers: this.config.n8n.headers,
                        body: JSON.stringify({
                            message: message,
                            sessionId: this.sessionId,
                            timestamp: Date.now()
                        })
                    });

                    console.log('📡 Response status:', response.status);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    let data;
                    const contentType = response.headers.get('content-type');
                    if (contentType?.includes('application/json')) {
                        data = await response.json();
                    } else {
                        data = { message: await response.text() };
                    }

                    console.log('📨 N8N response:', data);
                    
                    this.hideTyping();
                    
                    const botResponse = data.message || data.response || data.output || JSON.stringify(data);
                    this.addMessage(botResponse, 'bot');
                    
                } catch (error) {
                    console.error('❌ Error:', error);
                    this.hideTyping();
                    
                    let errorMessage = '❌ عذراً، حدث خطأ في الاتصال. ';
                    
                    if (error.message.includes('404')) {
                        errorMessage += 'الـ Webhook غير مفعل. تأكد من تفعيل الـ Workflow في N8N.';
                    } else if (error.message.includes('Failed to fetch')) {
                        errorMessage += 'لا يمكن الوصول للخادم. تأكد من الرابط.';
                    } else {
                        errorMessage += 'يرجى المحاولة مرة أخرى.';
                    }
                    
                    errorMessage += `\n\n📞 للدعم المباشر: ${this.config.companyPhone}`;
                    
                    this.addMessage(errorMessage, 'bot');
                }
            }

            updateTime() {
                if (this.timeEl) {
                    const now = new Date();
                    this.timeEl.textContent = now.toLocaleTimeString('ar-SA', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                }
            }

            getCurrentTime() {
                return new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
            }
        }

        // تشغيل الروبوت
        document.addEventListener('DOMContentLoaded', () => {
            window.Chatbot = new Chatbot();
        });
    </script>
</body>
</html>
</body>
</html>