<?php
// =============================================
// client-unit/index.php
// الصفحة الرئيسية - لوحة العميل
// =============================================
// تشغيل عرض الأخطاء للتصحيح
/*
define('SECURITY_ACCESS', true);
$rootPath = dirname(__DIR__); // عدل الرقم حسب عمق مجلدك
require_once $rootPath . '../../../security-init.php';
require_once $rootPath . '/security-functions.php';
*/
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// المسار الأساسي
define('BASE_PATH', __DIR__);
define('BASE_URL', '/client-unit');

require_once '../../../security-init.php';
// تحميل الملفات الأساسية
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/client_functions.php';

// الاتصال بقاعدة البيانات
$db = getDB();


// التحقق من تسجيل الدخول
if (!isset($_SESSION['client_id'])) {
    header('Location: ../login.php');
    exit();
}


// =============================================
// جلب بيانات العميل الحالي
// =============================================
try {
    $stmt = $db->prepare("SELECT * FROM client_clients WHERE id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    $current_client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_client) {
        // العميل مش موجود في قاعدة البيانات (اتحذف مثلًا)
        session_destroy();
        header('Location: ../login.php?error=not_found');
        exit();
    }
    
} catch (Exception $e) {
    // سجل الخطأ وارمسه على صفحة خطأ
    error_log("Client data error: " . $e->getMessage());
    die("❌ حدث خطأ في جلب بيانات العميل. الرجاء المحاولة لاحقاً.");
}

// ✅ بيانات العميل جاهزة للاستخدام
$client_id = $current_client['id'];
$client_name = $current_client['full_name'] ?? $current_client['name'] ?? 'عميل';
$client_email = $current_client['email'] ?? '';
$client_phone = $current_client['phone'] ?? '';
$client_balance = $current_client['balance'] ?? 0;
$client_company = $current_client['company_name'] ?? '';

// =============================================
// جلب مشاريع العميل
// =============================================
try {
    $stmt = $db->prepare("SELECT * FROM client_projects WHERE client_id = ? ORDER BY id DESC");
    $stmt->execute([$client_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $projects = [];
    error_log("Error fetching projects: " . $e->getMessage());
}

// =============================================
// جلب فواتير العميل
// =============================================
try {
    $stmt = $db->prepare("SELECT * FROM client_invoices WHERE client_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$client_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $invoices = [];
    error_log("Error fetching invoices: " . $e->getMessage());
}

// =============================================
// جلب إحصائيات سريعة
// =============================================
$total_projects = count($projects);
$total_invoices = count($invoices);
$pending_invoices = 0;
foreach ($invoices as $inv) {
    if ($inv['status'] == 'pending') $pending_invoices++;
}

// ✅ باقي كود الصفحة يبدأ من هنا

// الآن يمكنك استخدام $current_client في باقي الكود
// =============================================
// تحديد الصفحة المطلوبة
// =============================================
$page = $_GET['page'] ?? 'dashboard';

// قائمة الصفحات المسموح بها
// قائمة الصفحات المسموح بها
$allowed_pages = [
    'dashboard',
    'projects',
    'upload',
    'contracts',
    'billing',
    'reports',
    'security',
    'support',
    'hosting'  // <-- أضف هذا السطر
];
// إذا كانت الصفحة غير مسموح بها، انتقل إلى الصفحة الرئيسية
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// عناوين الصفحات
$page_titles = [
    'dashboard' => 'اللوحة الرئيسية',
    'projects' => 'مشاريعي',
    'upload' => 'رفع ملفات',
    'contracts' => 'العقود والموافقات',
    'billing' => 'الفواتير والمدفوعات',
    'reports' => 'التقارير والنتائج',
    'security' => 'الأمان والفحص',
    'support' => 'الدعم والملاحظات',
    'hosting' => 'استضافة المواقع'  // <-- أضف هذا السطر
];

// رسائل الترحيب
$welcome_messages = [
    'dashboard' => 'مرحباً بك في لوحة التحكم الخاصة بك',
    'projects' => 'إدارة ومتابعة مشاريعك',
    'upload' => 'رفع الملفات إلى السحابة',
    'contracts' => 'مراجعة وإدارة العقود',
    'billing' => 'الفواتير والمدفوعات',
    'reports' => 'التقارير ونتائج المشاريع',
    'security' => 'إعدادات الأمان والفحص',
    'support' => 'الدعم الفني والملاحظات',
    'hosting' => 'إدارة مواقع الاستضافة'  // <-- أضف هذا السطر
];

// =============================================
// جلب إحصائيات سريعة من قاعدة البيانات
// =============================================
try {
    // عدد المشاريع النشطة
    $stmt = $db->prepare("SELECT COUNT(*) FROM client_projects WHERE client_id = ? AND status IN ('pending', 'under_study', 'in_progress', 'testing')");
    $stmt->execute([$current_client['id']]);
    $active_projects = $stmt->fetchColumn() ?: 0;
    
    // عدد الفواتير المعلقة
    $stmt = $db->prepare("SELECT COUNT(*) FROM client_invoices WHERE client_id = ? AND status = 'pending'");
    $stmt->execute([$current_client['id']]);
    $pending_invoices = $stmt->fetchColumn() ?: 0;
    
    // عدد التقارير الجاهزة
    $stmt = $db->prepare("SELECT COUNT(*) FROM client_reports WHERE client_id = ? AND status = 'ready'");
    $stmt->execute([$current_client['id']]);
    $ready_reports = $stmt->fetchColumn() ?: 0;
    
    // مساحة التخزين المستخدمة
    $stmt = $db->prepare("SELECT COALESCE(SUM(file_size), 0) FROM client_files WHERE client_id = ?");
    $stmt->execute([$current_client['id']]);
    $used_storage = $stmt->fetchColumn() ?: 0;
    $total_storage = 100 * 1024 * 1024 * 1024; // 100 GB
    $storage_percent = $total_storage > 0 ? round(($used_storage / $total_storage) * 100, 1) : 0;
    $used_storage_gb = round($used_storage / (1024 * 1024 * 1024), 1);
    
    // عدد مواقع الاستضافة
$stmt = $db->prepare("
    SELECT COUNT(*) FROM hosting_sites 
    WHERE client_id = ? AND status = 'active'
");
$stmt->execute([$current_client['id']]);
$hosting_sites_count = $stmt->fetchColumn() ?: 0;


    // آخر المشاريع
    $stmt = $db->prepare("
        SELECT * FROM client_projects 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$current_client['id']]);
    $recent_projects = $stmt->fetchAll();
    
    // آخر الفواتير
    $stmt = $db->prepare("
        SELECT i.*, p.project_name 
        FROM client_invoices i
        LEFT JOIN client_projects p ON i.project_id = p.id
        WHERE i.client_id = ? 
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$current_client['id']]);
    $recent_invoices = $stmt->fetchAll();
    
    // آخر التذاكر
    $stmt = $db->prepare("
        SELECT * FROM client_support_tickets 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$current_client['id']]);
    $recent_tickets = $stmt->fetchAll();
    
    // ملخص الفواتير
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END), 0) as due_now,
            COALESCE(SUM(CASE WHEN status = 'paid' AND MONTH(paid_date) = MONTH(NOW()) THEN total_amount ELSE 0 END), 0) as paid_month,
            COALESCE(SUM(CASE WHEN status IN ('pending', 'overdue') THEN total_amount ELSE 0 END), 0) as total_due
        FROM client_invoices
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $invoice_summary = $stmt->fetch();
    
    // ملخص العقود
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END), 0) as under_review,
            COALESCE(SUM(CASE WHEN status = 'signed' AND signed_by_client = 0 THEN 1 ELSE 0 END), 0) as pending_signature,
            COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active,
            COALESCE(SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END), 0) as expired
        FROM client_contracts
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $contract_summary = $stmt->fetch();
    
    // آخر النشاطات
    $stmt = $db->prepare("
        SELECT * FROM client_activity_log 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$current_client['id']]);
    $recent_activities = $stmt->fetchAll();
    
    // عدد التذاكر المفتوحة
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END), 0) as open_tickets,
            COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_tickets,
            COALESCE(SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END), 0) as closed_tickets
        FROM client_support_tickets
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $ticket_summary = $stmt->fetch();
    
} catch (Exception $e) {
    // في حالة الخطأ، نستخدم قيم افتراضية
    $active_projects = 0;
    $pending_invoices = 0;
    $ready_reports = 0;
    $used_storage = 0;
    $used_storage_gb = 0;
    $storage_percent = 0;
    $recent_projects = [];
    $recent_invoices = [];
    $recent_tickets = [];
    $recent_activities = [];
    $invoice_summary = ['due_now' => 0, 'paid_month' => 0, 'total_due' => 0];
    $contract_summary = ['under_review' => 0, 'pending_signature' => 0, 'active' => 0, 'expired' => 0];
    $ticket_summary = ['open_tickets' => 0, 'in_progress_tickets' => 0, 'closed_tickets' => 0];
}

// دوال مساعدة
function isActive($current_page, $target_page) {
    return $current_page === $target_page ? 'active' : '';
}

function getProjectStageName($stage) {
    $stages = [
        1 => 'الطلب',
        2 => 'الدراسة',
        3 => 'العقد',
        4 => 'التنفيذ',
        5 => 'الفحص',
        6 => 'التسليم',
        7 => 'الدعم'
    ];
    return $stages[$stage] ?? 'غير محدد';
}

function getProjectStatusColor($status) {
    $colors = [
        'pending' => 'yellow',
        'under_study' => 'blue',
        'contract_pending' => 'purple',
        'in_progress' => 'green',
        'testing' => 'orange',
        'completed' => 'green',
        'cancelled' => 'red'
    ];
    return $colors[$status] ?? 'gray';
}

function getInvoiceStatusColor($status) {
    $colors = [
        'draft' => 'gray',
        'sent' => 'blue',
        'pending' => 'yellow',
        'paid' => 'green',
        'overdue' => 'red',
        'cancelled' => 'gray'
    ];
    return $colors[$status] ?? 'gray';
}

function getTicketStatusColor($status) {
    $colors = [
        'open' => 'green',
        'in_progress' => 'blue',
        'waiting' => 'yellow',
        'resolved' => 'purple',
        'closed' => 'gray'
    ];
    return $colors[$status] ?? 'gray';
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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Chart.js -->
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
        
        /* ===== الحدود التقنية ===== */
        .cyber-border {
            border: 2px solid rgba(59, 130, 246, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .cyber-border::before {
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
        
        /* ===== منطقة رفع الملفات ===== */
        .drop-zone {
            border: 3px dashed #3b82f6;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .drop-zone.active {
            background: rgba(59, 130, 246, 0.1);
            border-color: #60a5fa;
        }
        
        /* ===== توهج العمليات ===== */
        .cyber-glow {
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
        
        /* ===== مربع البحث ===== */
        .search-box {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
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
                    <svg class="w-10 h-10 ml-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div>
                        <h1 class="text-xl font-bold text-blue-400">نظام الاستضافة السحابي</h1>
                        <p class="text-xs text-gray-400">لوحة العميل</p>
                    </div>
                </div>
            </div>

            <!-- قائمة التنقل -->
            <nav class="flex-1 overflow-y-auto scrollbar-custom p-4">
                <div class="space-y-2">
                    <a href="?page=dashboard" class="nav-item <?php echo isActive($page, 'dashboard'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>اللوحة الرئيسية</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>

                    <a href="?page=projects" class="nav-item <?php echo isActive($page, 'projects'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>مشاريعي</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </a>

                    <a href="?page=upload" class="nav-item <?php echo isActive($page, 'upload'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>رفع ملفات</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </a>

                     <a href="?page=hosting" class="nav-item <?php echo isActive($page, 'hosting'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>استضافة مواقع </span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </a>

                    <a href="?page=contracts" class="nav-item <?php echo isActive($page, 'contracts'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>العقود والموافقات</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </a>

                    <a href="?page=billing" class="nav-item <?php echo isActive($page, 'billing'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>الفواتير والمدفوعات</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </a>

                    <a href="?page=reports" class="nav-item <?php echo isActive($page, 'reports'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>التقارير والنتائج</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </a>

                    <a href="?page=security" class="nav-item <?php echo isActive($page, 'security'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>الأمان والفحص</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </a>

                    <a href="?page=support" class="nav-item <?php echo isActive($page, 'support'); ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>الدعم والملاحظات</span>
                        <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </a>
                </div>
            </nav>

            <!-- معلومات العميل -->
            <div class="p-4 border-t border-slate-700">
                <div class="flex items-center justify-end">
                    <div class="text-right ml-3">
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($current_client['full_name']); ?></p>
                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($current_client['company_name'] ?? 'عميل'); ?></p>
                        <div class="flex items-center mt-1">
                            <span class="status-indicator bg-green-500"></span>
                            <span class="text-xs text-green-400 mr-1">متصل</span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">
                            <?php echo mb_substr($current_client['full_name'], 0, 1); ?>
                        </span>
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
                        <button onclick="requestNewService()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold transition-all cyber-glow flex items-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            طلب خدمة جديدة
                        </button>
                        <button onclick="refreshData()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-semibold transition-all flex items-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            تحديث البيانات
                        </button>
                        
                        <?php if ($pending_invoices > 0): ?>
                        <div class="relative">
                            <div class="w-3 h-3 bg-red-500 rounded-full absolute -top-1 -left-1 blink"></div>
                            <a href="?page=billing" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                فواتير مستحقة (<?php echo $pending_invoices; ?>)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- عنوان الصفحة -->
                    <div class="flex items-center">
                        <div class="ml-6 text-right">
                            <h2 class="text-2xl font-bold text-blue-400"><?php echo $page_titles[$page]; ?></h2>
                            <p class="text-sm text-gray-400"><?php echo $welcome_messages[$page]; ?></p>
                        </div>
                    </div>
                </div>

                <!-- شريط الإحصائيات السريعة -->
                <div class="grid grid-cols-4 gap-4 mt-4">

                    <!-- إحصائية مواقع الاستضافة -->
<div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
    <div>
        <p class="text-xs text-gray-400">مواقع الاستضافة</p>
        <p class="text-lg font-bold text-purple-400"><?php echo $hosting_sites_count; ?></p>
    </div>
    <div class="w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
        </svg>
    </div>
</div>
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">مشاريع نشطة</p>
                            <p class="text-lg font-bold text-blue-400"><?php echo $active_projects; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">فواتير معلقة</p>
                            <p class="text-lg font-bold text-yellow-400"><?php echo $pending_invoices; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">تقارير جاهزة</p>
                            <p class="text-lg font-bold text-green-400"><?php echo $ready_reports; ?></p>
                        </div>
                        <div class="w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">مساحة التخزين</p>
                            <p class="text-lg font-bold text-cyan-400"><?php echo $used_storage_gb; ?> GB</p>
                        </div>
                        <div class="w-8 h-8 bg-cyan-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </header>

            <!-- منطقة المحتوى الديناميكي -->
            <div id="content-area" class="flex-1 overflow-y-auto scrollbar-custom p-6">
                <?php
                $page_file = BASE_PATH . '/pages/' . $page . '.php';
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

    <!-- ===== نافذة طلب خدمة جديدة ===== -->
    <div id="service-request-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <button onclick="closeServiceRequestModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <h3 class="text-2xl font-bold text-right text-blue-400">طلب خدمة جديدة</h3>
            </div>
            
            <form id="service-request-form" onsubmit="handleServiceRequest(event)" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع الخدمة <span class="text-red-400">*</span></label>
                    <select id="service-type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                        <option value="">اختر نوع الخدمة</option>
                        <option value="hosting">استضافة موقع ويب</option>
                        <option value="storage">تخزين سحابي للبيانات</option>
                        <option value="security">حماية ومراقبة</option>
                        <option value="pentest">اختبار اختراق</option>
                        <option value="consultation">استشارة تقنية</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم المشروع <span class="text-red-400">*</span></label>
                    <input type="text" id="project-name" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="أدخل اسم المشروع">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">وصف المشروع</label>
                    <textarea id="project-description" rows="4" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="صف متطلباتك بالتفصيل..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الميزانية (ر.س)</label>
                        <input type="number" id="project-budget" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الموعد النهائي</label>
                        <input type="date" id="project-deadline" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse pt-4">
                    <button type="button" onclick="closeServiceRequestModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                        إلغاء
                    </button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </div>
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

    <!-- ===== السكريبتات ===== -->
    <script>
        // =============================================
        // المتغيرات العامة
        // =============================================
        const currentClient = <?php echo json_encode($current_client); ?>;
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let filesToUpload = [];

        // =============================================
        // دوال الإشعارات والتحميل
        // =============================================
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const colors = {
                'success': 'bg-green-600',
                'error': 'bg-red-600',
                'info': 'bg-blue-600',
                'warning': 'bg-yellow-600'
            };
            
            const notification = document.createElement('div');
            notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
            notification.innerHTML = `<div class="flex items-center">${message}</div>`;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        function showLoading() {
            document.getElementById('loading-spinner').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-spinner').classList.add('hidden');
        }

        // =============================================
        // دوال التنقل
        // =============================================
        function navigateTo(section) {
            window.location.href = `?page=${section}`;
        }

        function refreshData() {
            showLoading();
            setTimeout(() => {
                hideLoading();
                showNotification('🔄 تم تحديث البيانات', 'success');
                location.reload();
            }, 1000);
        }

        // =============================================
        // دوال طلب الخدمة
        // =============================================
        function requestNewService() {
            document.getElementById('service-request-modal').classList.remove('hidden');
        }

        function closeServiceRequestModal() {
            document.getElementById('service-request-modal').classList.add('hidden');
            document.getElementById('service-request-form').reset();
        }

        function handleServiceRequest(event) {
            event.preventDefault();
            
            const serviceType = document.getElementById('service-type').value;
            const projectName = document.getElementById('project-name').value;
            
            showLoading();
            
            // محاكاة إرسال الطلب
            setTimeout(() => {
                hideLoading();
                closeServiceRequestModal();
                showNotification(`✅ تم إرسال طلب خدمة "${projectName}" بنجاح`, 'success');
            }, 1500);
        }

        // =============================================
        // دوال إضافية
        // =============================================
        function showAllActivities() {
            navigateTo('projects');
        }

        // =============================================
        // تهيئة الصفحة
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ لوحة العميل جاهزة');
            
            // تحديث البيانات كل 30 ثانية
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    console.log('🔄 تحديث البيانات...');
                }
            }, 30000);
            
            // اختصارات لوحة المفاتيح
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeServiceRequestModal();
                }
            });
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
            companyPhone: "+967 771241661", // رقم الدعم
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