<?php
/**<?php
/**
 * لوحة القيادة التنفيذية - الإدارة العليا
 * Executive Dashboard - Top Management
 */

// =============================================
// تشغيل عرض الأخطاء للتصحيح (يشيله بعدين)
// =============================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =============================================
// بدء الجلسة (مرة واحدة فقط)
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// تعريف الثوابت الأساسية
// =============================================
define('ADMIN_ACCESS', true);
define('BASE_PATH', __DIR__);
define('BASE_URL', '/manager');

// =============================================
// تضمين الملفات الأساسية (بالترتيب الصحيح)
// =============================================

// 1. أولاً نظام الحماية (لو شغال)
if (file_exists('../../../security-init.php')) {
    require_once '../../../security-init.php';
}

// 2. ملف قاعدة البيانات
if (file_exists(BASE_PATH . '/config/database.php')) {
    require_once BASE_PATH . '/config/database.php';
} else {
    die("❌ ملف قاعدة البيانات غير موجود");
}

// 3. الملفات المساعدة
if (file_exists(BASE_PATH . '/includes/functions.php')) {
    require_once BASE_PATH . '/includes/functions.php';
}

if (file_exists(BASE_PATH . '/includes/manager_functions.php')) {
    require_once BASE_PATH . '/includes/manager_functions.php';
}

// =============================================
// الاتصال بقاعدة البيانات
// =============================================
try {
    $db = getDB();
    if (!$db) {
        throw new Exception("فشل الاتصال بقاعدة البيانات");
    }
} catch (Exception $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// =============================================
// التحقق من تسجيل الدخول والصلاحيات
// =============================================


// التحقق من وجود الكلاسات قبل استخدامها
if (class_exists('SecurityMiddleware')) {
    try {
        $security = new SecurityMiddleware();
        $security->validateSession();
    } catch (Exception $e) {
        error_log("SecurityMiddleware error: " . $e->getMessage());
    }
}

if (class_exists('AccessControl')) {
    try {
        $accessControl = new AccessControl();
        if (!$accessControl->checkPageAccess($_SESSION['user_id'] ?? 0, $_SERVER['REQUEST_URI'] ?? '')) {
            header('Location: /pages/admin/staff_login.php?error=unauthorized');
            exit();
        }
    } catch (Exception $e) {
        error_log("AccessControl error: " . $e->getMessage());
    }
}

if (function_exists('ActivityMonitor') && method_exists('ActivityMonitor', 'logPageView')) {
    try {
        ActivityMonitor::logPageView();
    } catch (Exception $e) {
        error_log("ActivityMonitor error: " . $e->getMessage());
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
// ... باقي كود جلب البيانات
// إحصائيات سريعة
try {
    // إجمالي المستخدمين
    $stmt = $db->query("SELECT COUNT(*) as total FROM users_all WHERE deleted_at IS NULL");
    $totalUsers = $stmt->fetch()['total'];
    
    // إجمالي العملاء
    $stmt = $db->query("SELECT COUNT(*) as total FROM client_clients");
    $totalClients = $stmt->fetch()['total'];
    
    // إجمالي المشاريع
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $totalProjects = $stmt->fetch()['total'];
    
    // المشاريع حسب الحالة
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status");
    $projectsByStatus = $stmt->fetchAll();
    $activeProjects = 0;
    $completedProjects = 0;
    $pendingProjects = 0;
    foreach ($projectsByStatus as $p) {
        if ($p['status'] == 'active') $activeProjects = $p['count'];
        elseif ($p['status'] == 'completed') $completedProjects = $p['count'];
        elseif ($p['status'] == 'pending') $pendingProjects = $p['count'];
    }
    
    // إجمالي المهام
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_tasks");
    $totalTasks = $stmt->fetch()['total'];
    
    // إجمالي الأحداث
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_events");
    $totalEvents = $stmt->fetch()['total'];
    
    // أحداث اليوم
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_events WHERE DATE(created_at) = CURDATE()");
    $todayEvents = $stmt->fetch()['total'];
    
    // الأحداث الحرجة
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_events WHERE severity = 'critical'");
    $criticalEvents = $stmt->fetch()['total'];
    
    // إجمالي التهديدات
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_events WHERE event_type IN ('security_alert', 'threat_detected', 'malware_found')");
    $totalThreats = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log("Executive Dashboard Stats Error: " . $e->getMessage());
    $totalUsers = 0;
    $totalClients = 0;
    $totalProjects = 0;
    $activeProjects = 0;
    $completedProjects = 0;
    $pendingProjects = 0;
    $totalTasks = 0;
    $totalEvents = 0;
    $todayEvents = 0;
    $criticalEvents = 0;
    $totalThreats = 0;
}

// جلب المشاريع الكبرى
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               u.full_name as manager_name,
               (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as tasks_count
        FROM projects p
        LEFT JOIN users_all u ON p.manager_id = u.id
        WHERE p.budget > 100000 OR p.priority = 'critical'
        ORDER BY p.budget DESC
        LIMIT 5
    ");
    $stmt->execute();
    $majorProjects = $stmt->fetchAll();
} catch (PDOException $e) {
    $majorProjects = [];
}

// جلب آخر التقارير
try {
    $stmt = $db->prepare("
        SELECT r.*, u.full_name as creator_name
        FROM reports r
        LEFT JOIN users_all u ON r.created_by = u.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentReports = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentReports = [];
}

// جلب المتابعات النشطة
try {
    $stmt = $db->prepare("
        SELECT f.*, u.full_name as assigned_to_name, p.project_name
        FROM follow_ups f
        LEFT JOIN users_all u ON f.assigned_to = u.id
        LEFT JOIN projects p ON f.project_id = p.id
        WHERE f.status IN ('pending', 'in_progress')
        ORDER BY f.deadline ASC
        LIMIT 5
    ");
    $stmt->execute();
    $activeFollowUps = $stmt->fetchAll();
} catch (PDOException $e) {
    $activeFollowUps = [];
}

// جلب القرارات الاستراتيجية
try {
    $stmt = $db->prepare("
        SELECT d.*, u.full_name as creator_name
        FROM strategic_decisions d
        LEFT JOIN users_all u ON d.created_by = u.id
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $strategicDecisions = $stmt->fetchAll();
} catch (PDOException $e) {
    $strategicDecisions = [];
}

// جلب أعضاء فريق القيادة
try {
    $stmt = $db->prepare("
        SELECT * FROM users_all 
        WHERE user_type IN ('admin', 'manager') 
        AND deleted_at IS NULL
        LIMIT 4
    ");
    $stmt->execute();
    $executiveTeam = $stmt->fetchAll();
} catch (PDOException $e) {
    $executiveTeam = [];
}

// إحصائيات الأداء
$performanceStats = [
    'revenue_growth' => 24,
    'customer_growth' => 18,
    'profit_margin' => 34,
    'customer_satisfaction' => 94,
    'customer_retention' => 92,
    'uptime' => 99.9,
    'mttr' => 1.2,
    'vulnerability_fixed' => 98,
    'threats_blocked' => $totalThreats
];

// المستخدم الحالي
//$currentUser = current_user();

// إنشاء جدول follow_ups إذا لم يكن موجوداً
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS follow_ups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            project_id INT NULL,
            assigned_to INT NULL,
            status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            deadline DATE NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_project (project_id),
            INDEX idx_assigned (assigned_to),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // تجاهل الخطأ إذا كان الجدول موجوداً
}

// إنشاء جدول strategic_decisions إذا لم يكن موجوداً
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS strategic_decisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            type ENUM('strategic', 'financial', 'operational', 'security', 'expansion') DEFAULT 'strategic',
            description TEXT,
            budget DECIMAL(15,2) DEFAULT 0,
            deadline DATE NULL,
            status ENUM('pending', 'approved', 'rejected', 'implemented') DEFAULT 'pending',
            votes JSON,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // تجاهل الخطأ إذا كان الجدول موجوداً
}

// إنشاء جدول reports إذا لم يكن موجوداً
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            type ENUM('financial', 'security', 'performance', 'customer', 'operational') DEFAULT 'performance',
            description TEXT,
            file_path VARCHAR(500),
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // تجاهل الخطأ إذا كان الجدول موجوداً
}

// إضافة بيانات افتراضية للتجربة
try {
    // التحقق من وجود متابعات
    $check = $db->query("SELECT COUNT(*) as count FROM follow_ups")->fetch();
    if ($check['count'] == 0) {
        $insert = $db->prepare("
            INSERT INTO follow_ups (title, description, status, priority, deadline, created_by)
            VALUES 
            ('متابعة مشروع الحكومة الإلكترونية', 'متابعة المرحلة الثالثة من التنفيذ', 'in_progress', 'high', DATE_ADD(NOW(), INTERVAL 15 DAY), ?),
            ('مراجعة تقرير الأمان الربعي', 'مراجعة نتائج التدقيق الأمني', 'pending', 'medium', DATE_ADD(NOW(), INTERVAL 20 DAY), ?)
        ");
        $insert->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    }
    
    // التحقق من وجود قرارات
    $check = $db->query("SELECT COUNT(*) as count FROM strategic_decisions")->fetch();
    if ($check['count'] == 0) {
        $insert = $db->prepare("
            INSERT INTO strategic_decisions (title, type, description, budget, status, created_by)
            VALUES 
            ('توسعة مركز البيانات', 'expansion', 'اقتراح زيادة سعة التخزين بنسبة 40%', 2500000, 'pending', ?),
            ('اعتماد تقنية الذكاء الاصطناعي', 'strategic', 'دمج الذكاء الاصطناعي في عمليات المراقبة الأمنية', 1800000, 'pending', ?)
        ");
        $insert->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    }
    
    // التحقق من وجود تقارير
    $check = $db->query("SELECT COUNT(*) as count FROM reports")->fetch();
    if ($check['count'] == 0) {
        $insert = $db->prepare("
            INSERT INTO reports (title, type, status, created_by, created_at)
            VALUES 
            ('التقرير المالي الربع الأخير', 'financial', 'published', ?, DATE_SUB(NOW(), INTERVAL 15 DAY)),
            ('تدقيق أمني شامل', 'security', 'published', ?, DATE_SUB(NOW(), INTERVAL 10 DAY)),
            ('رضا العملاء السنوي', 'customer', 'published', ?, DATE_SUB(NOW(), INTERVAL 5 DAY))
        ");
        $insert->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    }
} catch (PDOException $e) {
    // تجاهل الأخطاء في الإضافة
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة القيادة التنفيذية - الإدارة العليا</title>
 
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            box-sizing: border-box;
        }
        
        * {
            font-family: 'Cairo', sans-serif;
        }
        
        .executive-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        
        .executive-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(59, 130, 246, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .executive-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
        }
        
        .golden-border {
            border: 2px solid rgba(245, 158, 11, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .golden-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #f59e0b, transparent);
            animation: golden-scan 3s linear infinite;
        }
        
        @keyframes golden-scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .kpi-badge {
            background: linear-gradient(135deg, #1e293b, #334155);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .trend-up {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .trend-down {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .executive-nav {
            background: linear-gradient(90deg, #0f172a, #1e293b);
            border-bottom: 2px solid rgba(245, 158, 11, 0.3);
        }
        
        .dashboard-widget {
            min-height: 200px;
            transition: all 0.3s ease;
        }
        
        .dashboard-widget:hover {
            transform: scale(1.02);
        }
        
        .scrollbar-executive::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollbar-executive::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
        }
        
        .scrollbar-executive::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #f59e0b, #d97706);
            border-radius: 3px;
        }
        
        .decision-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border: 2px solid rgba(139, 92, 246, 0.3);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.2);
        }
        
        .chart-container {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 20px;
        }
        
        .risk-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 8px;
        }
        
        .risk-high {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        
        .risk-medium {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }
        
        .risk-low {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }
        
        .notification-executive {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
            border-left: 4px solid #f59e0b;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .meeting-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
        }
        
        .meeting-card:hover {
            border-color: rgba(59, 130, 246, 0.4);
            transform: translateX(-5px);
        }
        
        .ai-insight {
            background: linear-gradient(145deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.05));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            padding: 20px;
        }
        
        .strategy-item {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
        }
        
        .strategy-item:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(245, 158, 11, 0.4);
            transform: translateY(-2px);
        }
        
        .financial-card {
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .security-card {
            background: linear-gradient(145deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .executive-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.3), transparent);
            margin: 24px 0;
        }
    </style>
</head>
<body class="h-full executive-bg text-gray-100">
    <div id="app" class="h-full w-full flex flex-col overflow-hidden">
        <!-- Executive Header -->
        <header class="executive-nav px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo & Title -->
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-r from-yellow-500 to-amber-500 flex items-center justify-center ml-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <div class="text-right">
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-yellow-400 to-amber-400 bg-clip-text text-transparent">
                            لوحة القيادة التنفيذية
                        </h1>
                        <p class="text-sm text-gray-400">الإدارة العليا للنظام الكلي</p>
                    </div>
                </div>
                
                <!-- Executive Controls -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <!-- أزرار التنقل -->
                    <a href="admin-panel/index.php" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-lg font-semibold transition-all shadow-lg">
                        لوحة تحكم الحماية
                    </a>
                    <a href="dash/dashboard.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg font-semibold transition-all shadow-lg">
                        واجهة الخدمات والعملاء
                    </a>
                    <a href="index.php" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg font-semibold transition-all shadow-lg">
                        إدارة النظام
                    </a>
                    <button onclick="generateExecutiveReport()" class="px-6 py-3 bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 rounded-lg font-semibold transition-all shadow-lg">
                        تقرير تنفيذي
                    </button>
                    <button onclick="refreshExecutiveData()" class="p-3 bg-slate-800 hover:bg-slate-700 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto scrollbar-executive p-8">
            <!-- 📊 Executive Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- 👑 الرئيس التنفيذي Widget -->
                <div class="col-span-1 lg:col-span-3">
                    <div class="executive-card golden-border rounded-2xl p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div class="text-right">
                                <h2 class="text-3xl font-bold text-white mb-2">مرحباً بكم في الإدارة العليا</h2>
                                <p class="text-gray-400">لوحة التحكم التنفيذية للقرارات الاستراتيجية والمتابعة الشاملة</p>
                            </div>
                            <div class="flex items-center space-x-4 space-x-reverse">
                                <div class="text-left">
                                    <p class="text-sm text-gray-400">التاريخ والوقت</p>
                                    <p class="text-xl font-bold text-blue-400" id="executive-datetime">--</p>
                                </div>
                                <div class="w-16 h-16 rounded-full bg-gradient-to-r from-yellow-500 to-amber-500 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="kpi-badge rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-blue-400"><?php echo number_format($totalClients); ?></p>
                                <p class="text-xs text-gray-400">إجمالي العملاء</p>
                                <span class="trend-up text-xs px-2 py-1 rounded-full mt-2 inline-block">+<?php echo $performanceStats['customer_growth']; ?>%</span>
                            </div>
                            <div class="kpi-badge rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-green-400"><?php echo $performanceStats['customer_satisfaction']; ?>%</p>
                                <p class="text-xs text-gray-400">رضا العملاء</p>
                                <span class="trend-up text-xs px-2 py-1 rounded-full mt-2 inline-block">+3.2%</span>
                            </div>
                            <div class="kpi-badge rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-purple-400"><?php echo $activeProjects; ?></p>
                                <p class="text-xs text-gray-400">مشروع نشط</p>
                                <span class="trend-up text-xs px-2 py-1 rounded-full mt-2 inline-block">+8 مشاريع</span>
                            </div>
                            <div class="kpi-badge rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-red-400"><?php echo $performanceStats['uptime']; ?>%</p>
                                <p class="text-xs text-gray-400">أوقات التشغيل</p>
                                <span class="trend-down text-xs px-2 py-1 rounded-full mt-2 inline-block">-0.1%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 📈 نظرة عامة على المؤسسة -->
                <div class="executive-card rounded-2xl p-6 dashboard-widget">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-cyan-400 flex items-center">
                            <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            نظرة عامة على المؤسسة
                        </h3>
                        <select onchange="filterOrganizationView(this.value)" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="monthly">شهري</option>
                            <option value="quarterly">ربع سنوي</option>
                            <option value="yearly">سنوي</option>
                        </select>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Departments Performance -->
                        <div>
                            <p class="text-sm text-gray-400 mb-2">أداء الأقسام</p>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm">التقنية والاستضافة</span>
                                    <div class="flex items-center">
                                        <div class="w-32 h-2 bg-slate-700 rounded-full overflow-hidden ml-2">
                                            <div class="h-full bg-blue-500 rounded-full" style="width: 92%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-blue-400">92%</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm">المبيعات والتسويق</span>
                                    <div class="flex items-center">
                                        <div class="w-32 h-2 bg-slate-700 rounded-full overflow-hidden ml-2">
                                            <div class="h-full bg-green-500 rounded-full" style="width: 85%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-green-400">85%</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm">الدعم الفني</span>
                                    <div class="flex items-center">
                                        <div class="w-32 h-2 bg-slate-700 rounded-full overflow-hidden ml-2">
                                            <div class="h-full bg-yellow-500 rounded-full" style="width: 88%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-yellow-400">88%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="executive-divider"></div>
                        
                        <!-- Growth Metrics -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-slate-800 rounded-lg">
                                <p class="text-2xl font-bold text-green-400">+<?php echo $performanceStats['customer_growth']; ?>%</p>
                                <p class="text-xs text-gray-400">نمو العملاء</p>
                            </div>
                            <div class="text-center p-3 bg-slate-800 rounded-lg">
                                <p class="text-2xl font-bold text-blue-400">+<?php echo $performanceStats['revenue_growth']; ?>%</p>
                                <p class="text-xs text-gray-400">نمو الإيرادات</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تقارير الأداء الشاملة -->
<div class="executive-card rounded-2xl p-6 dashboard-widget">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-blue-400 flex items-center">
            <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            تقارير الأداء الشاملة
        </h3>
        <button onclick="viewAllPerformanceReports()" class="text-sm text-blue-400 hover:text-blue-300">
            عرض الكل
        </button>
    </div>
    
    <div class="space-y-4">
        <!-- Report List -->
        <div class="space-y-3">
            <?php 
            // التأكد من وجود بيانات التقارير
            if (isset($recentReports) && is_array($recentReports) && !empty($recentReports)): 
                foreach ($recentReports as $index => $report): 
                    // قيم افتراضية للون والنص
                    $reportType = $report['type'] ?? 'general';
                    $reportTitle = $report['title'] ?? 'تقرير بدون عنوان';
                    $reportDate = $report['created_at'] ?? date('Y-m-d');
                    $reportStatus = $report['status'] ?? 'published';
                    
                    // تحديد اللون حسب النوع
                    $bgColor = match($reportType) {
                        'financial' => 'bg-green-500',
                        'security' => 'bg-red-500',
                        'customer' => 'bg-blue-500',
                        'performance' => 'bg-purple-500',
                        default => 'bg-gray-500'
                    };
                    
                    $textColor = match($reportType) {
                        'financial' => 'text-green-400',
                        'security' => 'text-red-400',
                        'customer' => 'text-blue-400',
                        'performance' => 'text-purple-400',
                        default => 'text-gray-400'
                    };
                    
                    // أيقونة حسب النوع
                    $iconPath = '';
                    if ($reportType == 'financial') {
                        $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                    } elseif ($reportType == 'security') {
                        $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>';
                    } elseif ($reportType == 'customer') {
                        $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905a3.61 3.61 0 01-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>';
                    } else {
                        $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>';
                    }
                    
                    // نص الحالة
                    $statusText = match($reportType) {
                        'financial' => '+15%',
                        'security' => '⚠️ 3 ثغرات',
                        'customer' => '4.8/5 ⭐',
                        default => '✓ جديد'
                    };
            ?>
            <div class="flex items-center justify-between p-3 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors cursor-pointer" onclick="openReport(<?php echo $report['id'] ?? $index; ?>)">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg <?php echo $bgColor; ?> bg-opacity-20 flex items-center justify-center ml-3">
                        <svg class="w-4 h-4 <?php echo $textColor; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $iconPath; ?>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($reportTitle); ?></p>
                        <p class="text-xs text-gray-400">تم الإنشاء: <?php echo date('Y-m-d', strtotime($reportDate)); ?></p>
                    </div>
                </div>
                <span class="<?php echo $textColor; ?> text-sm"><?php echo $statusText; ?></span>
            </div>
            <?php 
                endforeach; 
            else: 
            ?>
            <div class="text-center py-4 text-gray-400">
                لا توجد تقارير حالياً
            </div>
            <?php endif; ?>
        </div>
        
        <button onclick="generateNewReport()" class="w-full mt-4 px-4 py-3 bg-slate-800 hover:bg-slate-700 rounded-lg border border-dashed border-slate-600 text-gray-400 hover:text-gray-300 transition-colors flex items-center justify-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            إنشاء تقرير جديد
        </button>
    </div>
</div>
                <!-- 💼 مراجعة المشاريع الكبرى -->
<div class="executive-card rounded-2xl p-6 dashboard-widget">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-purple-400 flex items-center">
            <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            مراجعة المشاريع الكبرى
        </h3>
        <button onclick="viewAllMajorProjects()" class="text-sm text-purple-400 hover:text-purple-300">
            عرض الكل
        </button>
    </div>
    
    <div class="space-y-4">
        <!-- Major Projects -->
        <div class="space-y-3">
            <?php 
            // التأكد من وجود بيانات المشاريع
            if (isset($majorProjects) && is_array($majorProjects) && !empty($majorProjects)): 
                foreach ($majorProjects as $project): 
                    // قيم افتراضية للمشروع
                    $projectName = $project['project_name'] ?? $project['name'] ?? 'مشروع بدون اسم';
                    $projectDesc = $project['description'] ?? $project['project_description'] ?? '';
                    $projectBudget = $project['budget'] ?? 0;
                    $projectProgress = $project['progress'] ?? 0;
                    $projectPriority = $project['priority'] ?? 'medium';
                    
                    // تحديد لون المؤشر حسب الأولوية
                    $riskClass = 'risk-low';
                    if ($projectPriority == 'high' || $projectPriority == 'critical') {
                        $riskClass = 'risk-high';
                    } elseif ($projectPriority == 'medium') {
                        $riskClass = 'risk-medium';
                    }
                    
                    // لون نسبة التقدم
                    $progressClass = 'text-blue-400';
                    if ($projectProgress >= 80) {
                        $progressClass = 'text-green-400';
                    } elseif ($projectProgress >= 50) {
                        $progressClass = 'text-yellow-400';
                    }
            ?>
            <div class="p-4 bg-slate-800 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($projectName); ?></h4>
                    <span class="risk-indicator <?php echo $riskClass; ?>"></span>
                </div>
                <p class="text-sm text-gray-400 mb-3"><?php echo htmlspecialchars(mb_substr($projectDesc, 0, 50)); ?>...</p>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400">الميزانية: <?php echo number_format($projectBudget, 2); ?> ر.س</span>
                    <span class="<?php echo $progressClass; ?>"><?php echo $projectProgress; ?>% مكتمل</span>
                </div>
            </div>
            <?php 
                endforeach; 
            else: 
            ?>
            <div class="text-center py-4 text-gray-400">
                لا توجد مشاريع كبرى حالياً
            </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="p-2 bg-red-900 bg-opacity-20 rounded">
                <p class="text-sm font-bold text-red-400"><?php echo $pendingProjects ?? 0; ?></p>
                <p class="text-xs text-gray-400">معلقة</p>
            </div>
            <div class="p-2 bg-yellow-900 bg-opacity-20 rounded">
                <p class="text-sm font-bold text-yellow-400"><?php echo $activeProjects ?? 0; ?></p>
                <p class="text-xs text-gray-400">قيد التنفيذ</p>
            </div>
            <div class="p-2 bg-green-900 bg-opacity-20 rounded">
                <p class="text-sm font-bold text-green-400"><?php echo $completedProjects ?? 0; ?></p>
                <p class="text-xs text-gray-400">مكتملة</p>
            </div>
        </div>
    </div>
</div>            </div>

            <!-- 🏆 مؤشرات الأداء الرئيسية -->
            <div class="executive-card rounded-2xl p-6 mb-8">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-2xl font-bold text-yellow-400 flex items-center">
                        <svg class="w-8 h-8 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        مؤشرات الأداء الرئيسية (KPIs)
                    </h3>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <button onclick="exportKPIs()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm transition-colors">
                            تصدير KPIs
                        </button>
                        <button onclick="compareKPIs()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm transition-colors">
                            مقارنة ربع سنوية
                        </button>
                    </div>
                </div>
                
                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Financial KPIs -->
                    <div class="financial-card rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-green-400">المالية</h4>
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-400">الإيرادات الشهرية</p>
                                <p class="text-2xl font-bold text-white">2.4M ر.س</p>
                                <p class="text-xs text-green-400">+<?php echo $performanceStats['revenue_growth']; ?>% عن الشهر الماضي</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">هامش الربح</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['profit_margin']; ?>%</p>
                                <p class="text-xs text-green-400">+2% عن الربع السابق</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer KPIs -->
                    <div class="rounded-xl p-6" style="background: linear-gradient(145deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05)); border: 1px solid rgba(59, 130, 246, 0.3);">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-blue-400">العملاء</h4>
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-400">رضا العملاء</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['customer_satisfaction']; ?>%</p>
                                <p class="text-xs text-green-400">+3.2% عن الربع السابق</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">نسبة الاحتفاظ</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['customer_retention']; ?>%</p>
                                <p class="text-xs text-green-400">+1.5% عن الربع السابق</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Operational KPIs -->
                    <div class="rounded-xl p-6" style="background: linear-gradient(145deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.05)); border: 1px solid rgba(245, 158, 11, 0.3);">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-yellow-400">التشغيل</h4>
                            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-400">وقت التشغيل</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['uptime']; ?>%</p>
                                <p class="text-xs text-red-400">-0.1% عن الربع السابق</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">متوسط وقت الإصلاح</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['mttr']; ?> س</p>
                                <p class="text-xs text-green-400">-0.3س عن الربع السابق</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security KPIs -->
                    <div class="security-card rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-red-400">الأمان</h4>
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-400">الثغرات المغلقة</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['vulnerability_fixed']; ?>%</p>
                                <p class="text-xs text-green-400">+2% عن الربع السابق</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">التهديدات المصدقة</p>
                                <p class="text-2xl font-bold text-white"><?php echo $performanceStats['threats_blocked']; ?></p>
                                <p class="text-xs text-red-400">+12 عن الربع السابق</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- KPI Charts -->
                <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="chart-container">
                        <h5 class="font-bold text-lg mb-4 text-blue-400">اتجاه الإيرادات السنوي</h5>
                        <div class="h-64 flex items-end justify-between">
                            <?php
                            $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
                            $quarterValues = [1200000, 1500000, 1800000, 2400000];
                            $maxValue = max($quarterValues);
                            foreach ($quarters as $index => $quarter):
                                $height = ($quarterValues[$index] / $maxValue) * 200;
                            ?>
                            <div class="text-center">
                                <div class="w-12 h-<?php echo $height; ?> bg-gradient-to-t from-green-500 to-green-300 rounded-t-lg mx-auto" style="height: <?php echo $height; ?>px;"></div>
                                <p class="text-xs mt-2"><?php echo $quarter; ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h5 class="font-bold text-lg mb-4 text-purple-400">توزيع المشاريع حسب القطاع</h5>
                        <div class="h-64 flex items-center justify-center">
                            <!-- Simplified Pie Chart -->
                            <div class="relative w-40 h-40">
                                <div class="absolute inset-0 rounded-full border-8 border-blue-500"></div>
                                <div class="absolute inset-0 rounded-full border-8 border-green-500" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); transform: rotate(120deg);"></div>
                                <div class="absolute inset-0 rounded-full border-8 border-yellow-500" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); transform: rotate(240deg);"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <p class="text-2xl font-bold"><?php echo $totalProjects; ?></p>
                                        <p class="text-xs text-gray-400">مشروع</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-4">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-500 ml-2"></div>
                                <span class="text-xs">حكومي 40%</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-green-500 ml-2"></div>
                                <span class="text-xs">مالي 35%</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-yellow-500 ml-2"></div>
                                <span class="text-xs">تعليم 25%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 📋 تقارير المتابعة -->
            <div class="executive-card rounded-2xl p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-cyan-400 flex items-center">
                        <svg class="w-8 h-8 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        تقارير المتابعة
                    </h3>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <button onclick="scheduleFollowUp()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm transition-colors">
                            جدولة متابعة
                        </button>
                        <button onclick="exportFollowUps()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm transition-colors">
                            تصدير المتابعات
                        </button>
                    </div>
                </div>
                
                <!-- Follow-up Tabs -->
                <div class="mb-6">
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="switchFollowUpTab('pending')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm transition-colors <?php echo empty($activeFollowUps) ? 'opacity-50' : ''; ?>">
                            قيد المتابعة (<?php echo count($activeFollowUps); ?>)
                        </button>
                        <button onclick="switchFollowUpTab('completed')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm transition-colors">
                            مكتملة (0)
                        </button>
                        <button onclick="switchFollowUpTab('overdue')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm transition-colors">
                            متأخرة (0)
                        </button>
                    </div>
                </div>
                
                <!-- Follow-up List -->
                <div class="space-y-4" id="follow-up-content">
                    <!-- Pending Follow-ups -->
                    <div class="follow-up-tab active" id="tab-pending">
                        <div class="space-y-3">
                            <?php foreach ($activeFollowUps as $followUp): 
                                $riskClass = 'risk-low';
                                if ($followUp['priority'] == 'high' || $followUp['priority'] == 'critical') $riskClass = 'risk-high';
                                elseif ($followUp['priority'] == 'medium') $riskClass = 'risk-medium';
                                $daysLeft = $followUp['deadline'] ? (strtotime($followUp['deadline']) - time()) / 86400 : 0;
                                $deadlineClass = $daysLeft < 2 ? 'text-red-400' : ($daysLeft < 5 ? 'text-yellow-400' : 'text-green-400');
                            ?>
                            <div class="meeting-card rounded-xl p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="font-bold text-lg"><?php echo htmlspecialchars($followUp['title']); ?></h4>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($followUp['description'] ?? ''); ?></p>
                                    </div>
                                    <span class="risk-indicator <?php echo $riskClass; ?>"></span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-400">المسؤول</p>
                                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($followUp['assigned_to_name'] ?? 'غير محدد'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">الموعد النهائي</p>
                                        <p class="text-sm font-semibold <?php echo $deadlineClass; ?>"><?php echo $followUp['deadline'] ?? 'غير محدد'; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">الحالة</p>
                                        <p class="text-sm font-semibold <?php echo $followUp['status'] == 'in_progress' ? 'text-yellow-400' : 'text-blue-400'; ?>">
                                            <?php echo $followUp['status'] == 'in_progress' ? 'قيد التنفيذ' : 'في انتظار المراجعة'; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">الأولوية</p>
                                        <p class="text-sm font-semibold <?php 
                                            echo $followUp['priority'] == 'high' || $followUp['priority'] == 'critical' ? 'text-red-400' : 
                                                ($followUp['priority'] == 'medium' ? 'text-yellow-400' : 'text-green-400'); 
                                        ?>">
                                            <?php 
                                            echo $followUp['priority'] == 'critical' ? 'عاجل' : 
                                                ($followUp['priority'] == 'high' ? 'عالية' : 
                                                ($followUp['priority'] == 'medium' ? 'متوسط' : 'منخفضة')); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <button onclick="viewFollowUpDetails(<?php echo $followUp['id']; ?>)" class="text-blue-400 hover:text-blue-300 text-sm">
                                        عرض التفاصيل
                                    </button>
                                    <button onclick="markFollowUpComplete(<?php echo $followUp['id']; ?>)" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
                                        تعيين كمكتمل
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($activeFollowUps)): ?>
                            <div class="text-center py-8 text-gray-400">
                                لا توجد متابعات نشطة حالياً
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🎯 اتخاذ القرارات الاستراتيجية -->
            <div class="executive-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-2xl font-bold text-green-400 flex items-center">
                        <svg class="w-8 h-8 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        اتخاذ القرارات الاستراتيجية
                    </h3>
                    <button onclick="openNewDecisionModal()" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg font-semibold transition-all">
                        قرار جديد
                    </button>
                </div>
                
                <!-- AI Insights -->
                <div class="ai-insight mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-purple-400 text-lg">رؤى الذكاء الاصطناعي</h4>
                        <span class="text-xs text-gray-400">تم التحديث: اليوم</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm font-semibold mb-2">فرصة النمو</p>
                            <p class="text-xs text-gray-400">سوق الخدمات السحابية ينمو بنسبة 28% سنوياً في المنطقة</p>
                        </div>
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm font-semibold mb-2">مخاطر</p>
                            <p class="text-xs text-gray-400">زيادة في الهجمات الأمنية المستهدفة للقطاع المالي بنسبة 45%</p>
                        </div>
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm font-semibold mb-2">التوصية</p>
                            <p class="text-xs text-gray-400">زيادة الاستثمار في الحلول الأمنية بنسبة 20% خلال الربع القادم</p>
                        </div>
                    </div>
                </div>
                
                <!-- Strategic Decisions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Pending Decisions -->
                    <div class="decision-card rounded-xl p-6">
                        <h4 class="font-bold text-blue-400 text-lg mb-4">قرارات قيد الدراسة</h4>
                        <div class="space-y-4">
                            <?php 
                            $pendingDecisions = array_filter($strategicDecisions, function($d) { return $d['status'] == 'pending'; });
                            foreach ($pendingDecisions as $decision): 
                            ?>
                            <div class="strategy-item">
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="font-semibold"><?php echo htmlspecialchars($decision['title']); ?></h5>
                                    <span class="text-xs text-yellow-400">
                                        <?php 
                                        echo $decision['type'] == 'expansion' ? 'توسعي' : 
                                            ($decision['type'] == 'financial' ? 'مالي' : 
                                            ($decision['type'] == 'security' ? 'أمني' : 'استراتيجي')); 
                                        ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-400 mb-3"><?php echo htmlspecialchars(mb_substr($decision['description'] ?? '', 0, 50)); ?>...</p>
                                <div class="flex items-center justify-between">
                                    <div class="text-xs text-gray-400">
                                        <span class="text-green-400 ml-2">✓ 3 موافقون</span>
                                        <span class="text-red-400 ml-4">✗ 1 معارض</span>
                                    </div>
                                    <button onclick="voteOnDecision(<?php echo $decision['id']; ?>)" class="text-sm text-blue-400 hover:text-blue-300">
                                        التصويت
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($pendingDecisions)): ?>
                            <div class="text-center py-4 text-gray-400">
                                لا توجد قرارات قيد الدراسة
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Approved Decisions -->
                    <div class="decision-card rounded-xl p-6">
                        <h4 class="font-bold text-green-400 text-lg mb-4">قرارات معتمدة</h4>
                        <div class="space-y-4">
                            <?php 
                            $approvedDecisions = array_filter($strategicDecisions, function($d) { return $d['status'] == 'approved' || $d['status'] == 'implemented'; });
                            foreach ($approvedDecisions as $decision): 
                            ?>
                            <div class="strategy-item">
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="font-semibold"><?php echo htmlspecialchars($decision['title']); ?></h5>
                                    <span class="text-xs text-green-400">
                                        <?php echo $decision['status'] == 'implemented' ? 'مكتمل' : 'معتمد'; ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-400 mb-3"><?php echo htmlspecialchars(mb_substr($decision['description'] ?? '', 0, 50)); ?>...</p>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-400">تم الاعتماد: <?php echo date('Y-m-d', strtotime($decision['updated_at'])); ?></span>
                                    <span class="text-green-400">نفذ بنسبة <?php echo rand(60, 100); ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($approvedDecisions)): ?>
                            <div class="text-center py-4 text-gray-400">
                                لا توجد قرارات معتمدة
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Decision Analytics -->
                <div class="rounded-xl p-6" style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9)); border: 1px solid rgba(139, 92, 246, 0.3);">
                    <h4 class="font-bold text-cyan-400 text-lg mb-4">تحليلات القرارات</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-slate-800 rounded-lg">
                            <p class="text-3xl font-bold text-green-400"><?php echo count($approvedDecisions); ?></p>
                            <p class="text-sm text-gray-400">قرارات معتمدة</p>
                        </div>
                        <div class="text-center p-4 bg-slate-800 rounded-lg">
                            <p class="text-3xl font-bold text-yellow-400"><?php echo count($pendingDecisions); ?></p>
                            <p class="text-sm text-gray-400">قرارات قيد الدراسة</p>
                        </div>
                        <div class="text-center p-4 bg-slate-800 rounded-lg">
                            <p class="text-3xl font-bold text-blue-400">92%</p>
                            <p class="text-sm text-gray-400">نسبة تنفيذ القرارات</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 👥 Executive Team -->
            <div class="executive-card rounded-2xl p-6 mt-8">
                <h3 class="text-2xl font-bold text-white mb-6">فريق القيادة التنفيذية</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($executiveTeam as $member): ?>
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-r from-yellow-500 to-amber-500 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h4 class="font-bold text-lg"><?php echo htmlspecialchars($member['full_name'] ?? $member['username']); ?></h4>
                        <p class="text-sm text-gray-400 mb-2">
                            <?php 
                            echo $member['user_type'] == 'admin' ? 'الرئيس التنفيذي' : 
                                ($member['user_type'] == 'manager' ? 'مدير' : 'عضو'); 
                            ?>
                        </p>
                        <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="text-sm text-blue-400 hover:text-blue-300"><?php echo htmlspecialchars($member['email']); ?></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- New Decision Modal -->
    <div id="new-decision-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px);">
        <div class="executive-card rounded-2xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-executive">
            <div class="flex items-center justify-between mb-6">
                <button onclick="closeNewDecisionModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <h3 class="text-2xl font-bold text-right text-green-400">إنشاء قرار استراتيجي جديد</h3>
            </div>
            
            <form id="new-decision-form" class="space-y-6" method="POST">
                <input type="hidden" name="action" value="create_decision">
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">عنوان القرار</label>
                    <input type="text" name="title" required class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right" placeholder="أدخل عنوان القرار الاستراتيجي">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع القرار</label>
                    <select name="type" required class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="strategic">استراتيجي</option>
                        <option value="financial">مالي</option>
                        <option value="operational">تشغيلي</option>
                        <option value="security">أمني</option>
                        <option value="expansion">توسعي</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الوصف التفصيلي</label>
                    <textarea name="description" rows="4" required class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right" placeholder="صف القرار بالتفصيل وأهدافه..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الميزانية المقترحة</label>
                        <input type="text" name="budget" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right" placeholder="المبلغ بالريال السعودي">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الموعد النهائي للدراسة</label>
                        <input type="date" name="deadline" required class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المستهدفون للتصويت</label>
                    <div class="space-y-2">
                        <label class="flex items-center justify-end">
                            <span class="mr-2">فريق القيادة التنفيذية</span>
                            <input type="checkbox" name="targets[]" value="executive" class="w-4 h-4 text-green-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                        <label class="flex items-center justify-end">
                            <span class="mr-2">مجلس الإدارة</span>
                            <input type="checkbox" name="targets[]" value="board" class="w-4 h-4 text-green-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                        <label class="flex items-center justify-end">
                            <span class="mr-2">لجنة المراجعة</span>
                            <input type="checkbox" name="targets[]" value="audit" class="w-4 h-4 text-green-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse pt-4">
                    <button type="button" onclick="closeNewDecisionModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                        إلغاء
                    </button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg font-semibold transition-all">
                        إنشاء القرار
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-executive" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px);">
        <div class="text-center">
            <div class="spinner border-yellow-500 border-t-yellow-300 mx-auto mb-4"></div>
            <p class="text-gray-400">جاري التحميل...</p>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="executive-notifications" class="fixed top-4 left-4 z-50 space-y-2"></div>

    <script>
        // بيانات لوحة الإدارة العليا
        let executiveData = {
            decisions: [],
            followUps: [],
            reports: [],
            kpis: [],
            currentFollowUpTab: 'pending'
        };
        
        // تهيئة لوحة الإدارة العليا
        function initializeExecutiveDashboard() {
            updateDateTime();
            loadExecutiveData();
            setupEventListeners();
        }
        
        // تحديث التاريخ والوقت
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true 
            };
            document.getElementById('executive-datetime').textContent = 
                now.toLocaleDateString('ar-SA', options);
        }
        
        // تحميل البيانات التنفيذية
        function loadExecutiveData() {
            // يمكن إضافة API calls هنا
            console.log('جاري تحميل بيانات الإدارة العليا...');
        }
        
        // إعداد مستمعي الأحداث
        function setupEventListeners() {
            document.getElementById('new-decision-form')?.addEventListener('submit', handleNewDecisionSubmit);
        }
        
        // وظائف التنقل والتصفية
        function filterOrganizationView(view) {
            showExecutiveNotification('تم التبديل إلى العرض ' + view, 'info');
        }
        
        function viewAllPerformanceReports() {
            showExecutiveNotification('فتح صفحة جميع تقارير الأداء', 'info');
        }
        
        function generateNewReport() {
            showExecutiveNotification('فتح نافذة إنشاء تقرير جديد', 'info');
        }
        
        function viewAllMajorProjects() {
            showExecutiveNotification('فتح صفحة جميع المشاريع الكبرى', 'info');
        }
        
        // وظائف مؤشرات الأداء
        function exportKPIs() {
            showExecutiveNotification('جاري تصدير مؤشرات الأداء...', 'info');
        }
        
        function compareKPIs() {
            showExecutiveNotification('فتح نافذة مقارنة المؤشرات الربعية', 'info');
        }
        
        // وظائف المتابعات
        function switchFollowUpTab(tab) {
            executiveData.currentFollowUpTab = tab;
            document.querySelectorAll('.follow-up-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        function scheduleFollowUp() {
            showExecutiveNotification('فتح نافذة جدولة متابعة جديدة', 'info');
        }
        
        function exportFollowUps() {
            showExecutiveNotification('جاري تصدير تقارير المتابعة', 'info');
        }
        
        function viewFollowUpDetails(id) {
            showExecutiveNotification('عرض تفاصيل المتابعة ' + id, 'info');
        }
        
        function markFollowUpComplete(id) {
            showExecutiveNotification('تم تعيين المتابعة ' + id + ' كمكتملة', 'success');
        }
        
        function rescheduleFollowUp(id) {
            showExecutiveNotification('إعادة جدولة المتابعة ' + id, 'info');
        }
        
        // وظائف التقارير
        function openReport(reportId) {
            showExecutiveNotification('فتح التقرير ' + reportId, 'info');
        }
        
        // وظائف القرارات الاستراتيجية
        function openNewDecisionModal() {
            document.getElementById('new-decision-modal').classList.remove('hidden');
            document.getElementById('new-decision-modal').classList.add('flex');
        }
        
        function closeNewDecisionModal() {
            document.getElementById('new-decision-modal').classList.add('hidden');
            document.getElementById('new-decision-modal').classList.remove('flex');
            document.getElementById('new-decision-form').reset();
        }
        
        function handleNewDecisionSubmit(e) {
            e.preventDefault();
            showExecutiveNotification('تم إنشاء القرار الاستراتيجي بنجاح', 'success');
            closeNewDecisionModal();
        }
        
        function voteOnDecision(decisionId) {
            showExecutiveNotification('التصويت على القرار ' + decisionId, 'info');
        }
        
        // وظائف التنفيذ العامة
        function generateExecutiveReport() {
            showExecutiveNotification('جاري إنشاء التقرير التنفيذي...', 'info');
        }
        
        function refreshExecutiveData() {
            showExecutiveNotification('جاري تحديث البيانات...', 'info');
            updateDateTime();
            location.reload();
        }
        
        // إظهار الإشعارات
        function showExecutiveNotification(message, type = 'info') {
            const container = document.getElementById('executive-notifications');
            const notification = document.createElement('div');
            
            const icons = {
                'success': '✅',
                'error': '❌',
                'info': 'ℹ️',
                'warning': '⚠️'
            };
            
            notification.className = 'notification-executive text-white px-6 py-4 rounded-lg shadow-lg max-w-sm';
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="text-xl ml-3">${icons[type] || 'ℹ️'}</span>
                    <p>${message}</p>
                </div>
            `;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // إظهار وإخفاء التحميل
        function showExecutiveLoading() {
            document.getElementById('loading-executive').classList.remove('hidden');
            document.getElementById('loading-executive').classList.add('flex');
        }
        
        function hideExecutiveLoading() {
            document.getElementById('loading-executive').classList.add('hidden');
            document.getElementById('loading-executive').classList.remove('flex');
        }
        
        // بدء التشغيل
        document.addEventListener('DOMContentLoaded', function() {
            initializeExecutiveDashboard();
            // تحديث التاريخ كل دقيقة
            setInterval(updateDateTime, 60000);
        });
    </script>
</body>
</html>