<?php
/**
 * سجلات التدقيق والبحث المتقدم
 * Audit Logs Page
 */

// تعريف ثابت للوصول
define('ADMIN_ACCESS', true);
require_once '../../../../../security-init.php';
// تضمين الملفات الأساسية
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/admin_functions.php';

// طلب تسجيل الدخول وصلاحية المدير


// معالجة طلبات البحث والتصدير
$searchPerformed = false;
$logs = [];
$totalCount = 0;

// بناء شروط البحث
$whereConditions = [];
$params = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    $searchPerformed = true;
    
    // فلترة حسب المستخدم
    if (!empty($_GET['user_id'])) {
        $whereConditions[] = "ue.user_id = ?";
        $params[] = (int)$_GET['user_id'];
    }
    
    // فلترة حسب نوع الحدث
    if (!empty($_GET['event_type'])) {
        $whereConditions[] = "ue.event_type = ?";
        $params[] = $_GET['event_type'];
    }
    
    // فلترة حسب المستوى
    if (!empty($_GET['severity'])) {
        $whereConditions[] = "ue.severity = ?";
        $params[] = $_GET['severity'];
    }
    
    // فلترة حسب التاريخ من
    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "DATE(ue.created_at) >= ?";
        $params[] = $_GET['date_from'];
    }
    
    // فلترة حسب التاريخ إلى
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "DATE(ue.created_at) <= ?";
        $params[] = $_GET['date_to'];
    }
    
    // بحث نصي
    if (!empty($_GET['search'])) {
        $whereConditions[] = "(ue.description LIKE ? OR ue.action LIKE ? OR ua.username LIKE ? OR ua.full_name LIKE ?)";
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // فلترة حسب IP
    if (!empty($_GET['ip_address'])) {
        $whereConditions[] = "ue.ip_address LIKE ?";
        $params[] = '%' . $_GET['ip_address'] . '%';
    }
    
    // فلترة حسب نوع المورد
    if (!empty($_GET['resource_type'])) {
        $whereConditions[] = "ue.resource_type = ?";
        $params[] = $_GET['resource_type'];
    }
}

// بناء جملة WHERE
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// جلب إجمالي النتائج
try {
    $countQuery = "SELECT COUNT(*) as total FROM user_events ue 
                   LEFT JOIN users_all ua ON ue.user_id = ua.id 
                   $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalCount = $stmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Count query error: " . $e->getMessage());
}

// جلب السجلات مع التقسيم
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = ($page - 1) * $limit;

try {
    $query = "SELECT ue.*, ua.username, ua.full_name 
              FROM user_events ue 
              LEFT JOIN users_all ua ON ue.user_id = ua.id 
              $whereClause 
              ORDER BY ue.created_at DESC 
              LIMIT $offset, $limit";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Logs query error: " . $e->getMessage());
    $logs = [];
}

// جلب قوائم الفلترة
try {
    // قائمة المستخدمين
    $users = $db->query("SELECT id, username, full_name FROM users_all WHERE deleted_at IS NULL ORDER BY username")->fetchAll();
    
    // أنواع الأحداث الفريدة
    $eventTypes = $db->query("SELECT DISTINCT event_type FROM user_events ORDER BY event_type")->fetchAll();
    
    // مستويات الخطورة
    $severities = $db->query("SELECT DISTINCT severity FROM user_events WHERE severity IS NOT NULL ORDER BY severity")->fetchAll();
    
    // أنواع الموارد
    $resourceTypes = $db->query("SELECT DISTINCT resource_type FROM user_events WHERE resource_type IS NOT NULL ORDER BY resource_type")->fetchAll();
    
} catch (PDOException $e) {
    $users = [];
    $eventTypes = [];
    $severities = [];
    $resourceTypes = [];
}

// إحصائيات سريعة
try {
    // إجمالي الأحداث
    $totalEvents = $db->query("SELECT COUNT(*) FROM user_events")->fetchColumn();
    
    // أحداث اليوم
    $todayEvents = $db->query("SELECT COUNT(*) FROM user_events WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // أحداث الأمس
    $yesterdayEvents = $db->query("SELECT COUNT(*) FROM user_events WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
    
    // الأحداث الحرجة
    $criticalEvents = $db->query("SELECT COUNT(*) FROM user_events WHERE severity = 'critical'")->fetchColumn();
    
    // آخر 7 أيام
    $last7Days = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM user_events 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    $totalEvents = 0;
    $todayEvents = 0;
    $yesterdayEvents = 0;
    $criticalEvents = 0;
    $last7Days = [];
}

// معالجة طلب التصدير
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportLogsToCSV($logs, $whereClause, $params);
}

function exportLogsToCSV($logs, $whereClause, $params) {
    global $db;
    
    // جلب جميع السجلات للتصدير
    $query = "SELECT ue.*, ua.username, ua.full_name 
              FROM user_events ue 
              LEFT JOIN users_all ua ON ue.user_id = ua.id 
              $whereClause 
              ORDER BY ue.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $allLogs = $stmt->fetchAll();
    
    // تحديد اسم الملف
    $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
    
    // إعداد headers للتحميل
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // فتح ملف الإخراج
    $output = fopen('php://output', 'w');
    
    // إضافة BOM للعربية
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // كتابة رؤوس الأعمدة
    fputcsv($output, [
        'التاريخ',
        'الوقت',
        'المستخدم',
        'نوع الحدث',
        'الإجراء',
        'الوصف',
        'المستوى',
        'عنوان IP',
        'المتصفح',
        'الجهاز',
        'الموقع',
        'معرف الجلسة',
        'نوع المورد',
        'معرف المورد',
        'معرف العميل',
        'معرف المشروع',
        'الحالة',
        'وقت الاستجابة'
    ]);
    
    // كتابة البيانات
    foreach ($allLogs as $log) {
        $createdAt = strtotime($log['created_at']);
        fputcsv($output, [
            date('Y-m-d', $createdAt),
            date('H:i:s', $createdAt),
            $log['username'] ?? $log['full_name'] ?? 'نظام',
            $log['event_type'],
            $log['action'],
            $log['description'],
            $log['severity'] ?? 'info',
            $log['ip_address'] ?? '-',
            $log['user_agent'] ?? '-',
            $log['device'] ?? '-',
            $log['location'] ?? '-',
            $log['session_id'] ?? '-',
            $log['resource_type'] ?? '-',
            $log['resource_id'] ?? '-',
            $log['client_id'] ?? '-',
            $log['project_id'] ?? '-',
            $log['status'] ?? 'success',
            $log['response_time'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit();
}

// الحصول على المستخدم الحالي
$currentUser = current_user();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجلات التدقيق - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-right: 4px solid transparent;
            text-decoration: none;
        }
        
        .nav-link i {
            margin-left: 12px;
            width: 20px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: #ffd700;
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15);
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* بطاقات الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.15;
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-change {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .change-up { color: var(--success-color); }
        .change-down { color: var(--danger-color); }
        
        /* بطاقة المحتوى */
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* شريط البحث والفلترة */
        .filter-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .filter-section .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 5px;
        }
        
        /* شارات المستوى */
        .severity-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
        }
        
        .severity-critical {
            background: #dc3545;
            color: white;
        }
        
        .severity-error {
            background: #fd7e14;
            color: white;
        }
        
        .severity-warning {
            background: #ffc107;
            color: black;
        }
        
        .severity-info {
            background: #17a2b8;
            color: white;
        }
        
        /* شارات الأحداث */
        .event-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .event-auth {
            background: #6f42c1;
            color: white;
        }
        
        .event-file {
            background: #28a745;
            color: white;
        }
        
        .event-project {
            background: #fd7e14;
            color: white;
        }
        
        .event-security {
            background: #dc3545;
            color: white;
        }
        
        .event-user {
            background: #17a2b8;
            color: white;
        }
        
        /* تفاصيل السجل */
        .log-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            display: none;
            border: 1px solid #dee2e6;
        }
        
        .log-details.show {
            display: block;
        }
        
        .details-table {
            width: 100%;
            font-size: 0.9rem;
        }
        
        .details-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .details-table td:first-child {
            font-weight: bold;
            width: 150px;
            background: #e9ecef;
        }
        
        /* تنسيق الجدول */
        .table-audit {
            font-size: 0.9rem;
        }
        
        .table-audit th {
            background: #f8f9fa;
            white-space: nowrap;
        }
        
        .table-audit td {
            vertical-align: middle;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        /* ترقيم الصفحات */
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .page-link {
            color: var(--primary-color);
        }
        
        /* رسوم بيانية بسيطة */
        .activity-chart {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 100px;
            margin-top: 20px;
        }
        
        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, var(--primary-color), var(--secondary-color));
            border-radius: 4px 4px 0 0;
            min-width: 30px;
            position: relative;
            transition: height 0.3s;
        }
        
        .chart-bar:hover {
            opacity: 0.8;
        }
        
        .chart-bar span {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7rem;
            color: #6c757d;
            white-space: nowrap;
        }
        
        /* التجاوب */
        @media (max-width: 768px) {
            .sidebar {
                right: -280px;
                transition: right 0.3s;
            }
            
            .sidebar.show {
                right: 0;
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        /* شاشة التحميل */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading.show {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* أيقونة JSON */
        .json-view {
            background: #2d2d2d;
            color: #00ff00;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.8rem;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- شاشة التحميل -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- زر القائمة للجوال -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- الشريط الجانبي -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-3x"></i>
            <h4 class="mt-2"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></h4>
            <small>مدير النظام</small>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> لوحة التحكم
            </a>
            <a href="users-management.php" class="nav-link">
                <i class="fas fa-users"></i> إدارة المستخدمين
            </a>
            <a href="roles-permissions.php" class="nav-link">
                <i class="fas fa-key"></i> الأدوار والصلاحيات
            </a>
            <a href="audit-logs.php" class="nav-link active">
                <i class="fas fa-history"></i> سجلات التدقيق
            </a>
            <a href="security-settings.php" class="nav-link">
                <i class="fas fa-cog"></i> إعدادات الأمان
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="../../index.php" class="nav-link">
                <i class="fas fa-globe"></i> الموقع الرئيسي
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> تسجيل خروج
            </a>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-history text-primary me-2"></i>
                سجلات التدقيق
            </h2>
            <div>
                <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                    <i class="fas fa-file-csv me-1"></i>
                    تصدير CSV
                </a>
                <button class="btn btn-primary ms-2" onclick="toggleFilters()">
                    <i class="fas fa-sliders-h me-1"></i>
                    بحث متقدم
                </button>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">إجمالي الأحداث</div>
                <div class="stat-value"><?php echo number_format($totalEvents); ?></div>
                <div class="stat-change">
                    <i class="fas fa-database"></i> في قاعدة البيانات
                </div>
                <i class="fas fa-chart-bar stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">أحداث اليوم</div>
                <div class="stat-value"><?php echo number_format($todayEvents); ?></div>
                <div class="stat-change <?php echo $todayEvents > $yesterdayEvents ? 'change-up' : 'change-down'; ?>">
                    <i class="fas <?php echo $todayEvents > $yesterdayEvents ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                    <?php 
                    $change = $yesterdayEvents > 0 ? round((($todayEvents - $yesterdayEvents) / $yesterdayEvents) * 100) : 0;
                    echo abs($change) . '% عن الأمس';
                    ?>
                </div>
                <i class="fas fa-calendar-day stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">الأحداث الحرجة</div>
                <div class="stat-value"><?php echo number_format($criticalEvents); ?></div>
                <div class="stat-change text-danger">
                    <i class="fas fa-exclamation-triangle"></i> تحتاج مراجعة
                </div>
                <i class="fas fa-bolt stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">نتائج البحث</div>
                <div class="stat-value"><?php echo number_format($totalCount); ?></div>
                <div class="stat-change">
                    <i class="fas fa-search"></i> <?php echo count($logs); ?> معروض
                </div>
                <i class="fas fa-list stat-icon"></i>
            </div>
        </div>

        <!-- رسم بياني للنشاط -->
        <?php if (!empty($last7Days)): ?>
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-chart-line"></i>
                    النشاط خلال آخر 7 أيام
                </h5>
            </div>
            
            <div class="activity-chart">
                <?php 
                $maxCount = max(array_column($last7Days, 'count'));
                foreach (array_reverse($last7Days) as $day): 
                    $height = $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0;
                ?>
                <div class="chart-bar" style="height: <?php echo $height; ?>px;">
                    <span><?php echo $day['count']; ?></span>
                    <div style="text-align: center; margin-top: 5px; font-size: 0.7rem;">
                        <?php echo date('d/m', strtotime($day['date'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- قسم البحث المتقدم -->
        <div class="filter-section" id="filtersSection" style="<?php echo empty($_GET) ? 'display: none;' : ''; ?>">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">المستخدم</label>
                    <select name="user_id" class="form-select select2">
                        <option value="">كل المستخدمين</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                            <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">نوع الحدث</label>
                    <select name="event_type" class="form-select select2">
                        <option value="">كل الأحداث</option>
                        <?php foreach ($eventTypes as $type): ?>
                        <option value="<?php echo $type['event_type']; ?>" 
                            <?php echo (isset($_GET['event_type']) && $_GET['event_type'] == $type['event_type']) ? 'selected' : ''; ?>>
                            <?php echo $type['event_type']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">المستوى</label>
                    <select name="severity" class="form-select">
                        <option value="">الكل</option>
                        <option value="info" <?php echo (isset($_GET['severity']) && $_GET['severity'] == 'info') ? 'selected' : ''; ?>>معلومات</option>
                        <option value="warning" <?php echo (isset($_GET['severity']) && $_GET['severity'] == 'warning') ? 'selected' : ''; ?>>تحذير</option>
                        <option value="error" <?php echo (isset($_GET['severity']) && $_GET['severity'] == 'error') ? 'selected' : ''; ?>>خطأ</option>
                        <option value="critical" <?php echo (isset($_GET['severity']) && $_GET['severity'] == 'critical') ? 'selected' : ''; ?>>حرج</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">نوع المورد</label>
                    <select name="resource_type" class="form-select">
                        <option value="">الكل</option>
                        <?php foreach ($resourceTypes as $type): ?>
                        <option value="<?php echo $type['resource_type']; ?>" 
                            <?php echo (isset($_GET['resource_type']) && $_GET['resource_type'] == $type['resource_type']) ? 'selected' : ''; ?>>
                            <?php echo $type['resource_type']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-control" 
                           value="<?php echo $_GET['ip_address'] ?? ''; ?>" 
                           placeholder="192.168.1.1">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">بحث نصي</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               value="<?php echo $_GET['search'] ?? ''; ?>" 
                               placeholder="بحث في الوصف والإجراء...">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <a href="audit-logs.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- جدول السجلات -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-list"></i>
                    سجلات الأحداث
                </h5>
                <span class="badge bg-primary"><?php echo $totalCount; ?> نتيجة</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-audit">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>المستخدم</th>
                            <th>الحدث</th>
                            <th>الإجراء</th>
                            <th>الوصف</th>
                            <th>المستوى</th>
                            <th>IP</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $createdAt = strtotime($log['created_at']);
                        ?>
                        <tr>
                            <td>
                                <span class="fw-bold"><?php echo date('Y-m-d', $createdAt); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?php echo date('H:i:s', $createdAt); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['user_id']): ?>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo mb_substr($log['full_name'] ?? $log['username'] ?? 'ن', 0, 1); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'نظام'); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $log['user_id']; ?></small>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-robot me-1"></i>
                                    نظام
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="event-badge event-<?php 
                                    echo match($log['event_type']) {
                                        'login', 'logout' => 'auth',
                                        'file_upload', 'file_download' => 'file',
                                        'project_create', 'project_update' => 'project',
                                        'security_alert', 'threat_detected' => 'security',
                                        default => 'info'
                                    };
                                ?>">
                                    <?php echo htmlspecialchars($log['event_type']); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($log['action']); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(mb_substr($log['description'] ?? '', 0, 50)); ?></small>
                                <?php if (strlen($log['description'] ?? '') > 50): ?>
                                <span class="text-primary" style="cursor: pointer;" onclick="showFullDescription(<?php echo $log['id']; ?>)">
                                    ... المزيد
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['severity']): ?>
                                <span class="severity-badge severity-<?php echo $log['severity']; ?>">
                                    <?php echo $log['severity']; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted font-monospace">
                                    <?php echo $log['ip_address'] ?? '-'; ?>
                                </small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="toggleDetails(<?php echo $log['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!empty($log['details'])): ?>
                                <button class="btn btn-sm btn-secondary" onclick="showJSON(<?php echo $log['id']; ?>)">
                                    <i class="fas fa-code"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="details-<?php echo $log['id']; ?>" class="log-details-row" style="display: none;">
                            <td colspan="9">
                                <div class="log-details show">
                                    <table class="details-table">
                                        <tr>
                                            <td>معرف الحدث</td>
                                            <td><?php echo $log['event_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>معرف الجلسة</td>
                                            <td><?php echo $log['session_id'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>معرف الطلب</td>
                                            <td><?php echo $log['request_id'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>طريقة الطلب</td>
                                            <td><?php echo $log['request_method'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>الرابط</td>
                                            <td><small><?php echo $log['request_url'] ?? '-'; ?></small></td>
                                        </tr>
                                        <tr>
                                            <td>رمز الاستجابة</td>
                                            <td><?php echo $log['response_code'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>وقت الاستجابة</td>
                                            <td><?php echo $log['response_time'] ? $log['response_time'] . ' ms' : '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>نوع المورد</td>
                                            <td><?php echo $log['resource_type'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>معرف المورد</td>
                                            <td><?php echo $log['resource_id'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>اسم المورد</td>
                                            <td><?php echo $log['resource_name'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>معرف العميل</td>
                                            <td><?php echo $log['client_id'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>معرف المشروع</td>
                                            <td><?php echo $log['project_id'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>المتصفح</td>
                                            <td><small><?php echo $log['user_agent'] ?? '-'; ?></small></td>
                                        </tr>
                                        <tr>
                                            <td>الجهاز</td>
                                            <td><?php echo $log['device'] ?? '-'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>الموقع</td>
                                            <td><?php echo $log['location'] ?? '-'; ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <?php if (!empty($log['details'])): ?>
                                <div id="json-<?php echo $log['id']; ?>" class="log-details">
                                    <h6 class="mb-2">تفاصيل إضافية (JSON):</h6>
                                    <pre class="json-view"><?php 
                                        $details = json_decode($log['details'], true);
                                        echo htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                    ?></pre>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <br>
                                لا توجد سجلات مطابقة لمعايير البحث
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- ترقيم الصفحات -->
            <?php if ($totalCount > $limit): 
                $totalPages = ceil($totalCount / $limit);
                $currentPage = $page;
            ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination">
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                            <i class="fas fa-chevron-right"></i> السابق
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): 
                        if ($i == 1 || $i == $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)):
                    ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php elseif ($i == $currentPage - 3 || $i == $currentPage + 3): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; endfor; ?>
                    
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                            التالي <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- مودال عرض التفاصيل -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        تفاصيل الحدث
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetails">
                    <!-- تملأ بالجافاسكريبت -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // تهيئة Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
        
        // التحكم في الشريط الجانبي
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // إخفاء شاشة التحميل
        window.addEventListener('load', function() {
            document.getElementById('loading').classList.remove('show');
        });
        
        // إظهار/إخفاء فلاتر البحث
        function toggleFilters() {
            const filters = document.getElementById('filtersSection');
            filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
        }
        
        // إظهار/إخفاء تفاصيل السجل
        function toggleDetails(id) {
            const row = document.getElementById('details-' + id);
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
        
        // إظهار JSON
        function showJSON(id) {
            const jsonDiv = document.getElementById('json-' + id);
            jsonDiv.style.display = jsonDiv.style.display === 'none' ? 'block' : 'none';
        }
        
        // إظهار الوصف الكامل
        function showFullDescription(id) {
            // يمكن تنفيذها لاحقاً
        }
        
        // تحديث الصفحة كل 60 ثانية
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 60000);
        
        // اختصارات لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // Ctrl + F: التركيز على البحث
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
            
            // Ctrl + E: تصدير
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = '?export=csv&<?php echo http_build_query($_GET); ?>';
            }
        });
    </script>
</body>
</html>