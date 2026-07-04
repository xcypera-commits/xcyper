<?php
/**
 * التقارير الشاملة
 * Reports Page
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


// تحديد الفترة الزمنية
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$dateFrom = '';
$dateTo = '';

switch ($period) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d');
        break;
    case 'yesterday':
        $dateFrom = date('Y-m-d', strtotime('-1 day'));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'week':
        $dateFrom = date('Y-m-d', strtotime('-7 days'));
        $dateTo = date('Y-m-d');
        break;
    case 'month':
        $dateFrom = date('Y-m-d', strtotime('-30 days'));
        $dateTo = date('Y-m-d');
        break;
    case 'year':
        $dateFrom = date('Y-m-d', strtotime('-365 days'));
        $dateTo = date('Y-m-d');
        break;
    case 'custom':
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
        break;
}

// جلب إحصائيات المستخدمين
try {
    // إجمالي المستخدمين
    $stmt = $db->query("SELECT COUNT(*) as total FROM users_all WHERE deleted_at IS NULL");
    $totalUsers = $stmt->fetch()['total'];
    
    // المستخدمين حسب النوع
    $stmt = $db->query("
        SELECT user_type, COUNT(*) as count 
        FROM users_all 
        WHERE deleted_at IS NULL 
        GROUP BY user_type
        ORDER BY count DESC
    ");
    $usersByType = $stmt->fetchAll();
    
    // المستخدمين حسب الحالة
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM users_all 
        WHERE deleted_at IS NULL 
        GROUP BY status
    ");
    $usersByStatus = $stmt->fetchAll();
    
    // المستخدمين الجدد في الفترة
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM users_all 
        WHERE created_at BETWEEN ? AND ? 
        AND deleted_at IS NULL
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $newUsers = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    $totalUsers = 0;
    $usersByType = [];
    $usersByStatus = [];
    $newUsers = 0;
}

// جلب إحصائيات الأحداث
try {
    // إجمالي الأحداث في الفترة
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $totalEvents = $stmt->fetch()['total'];
    
    // الأحداث حسب النوع
    $stmt = $db->prepare("
        SELECT event_type, COUNT(*) as count 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY event_type
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $eventsByType = $stmt->fetchAll();
    
    // الأحداث حسب المستوى
    $stmt = $db->prepare("
        SELECT severity, COUNT(*) as count 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY severity
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $eventsBySeverity = $stmt->fetchAll();
    
    // النشاط اليومي
    $stmt = $db->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $dailyActivity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $totalEvents = 0;
    $eventsByType = [];
    $eventsBySeverity = [];
    $dailyActivity = [];
}

// جلب إحصائيات المشاريع
try {
    // إجمالي المشاريع
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $totalProjects = $stmt->fetch()['total'];
    
    // المشاريع حسب الحالة
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM projects 
        GROUP BY status
    ");
    $projectsByStatus = $stmt->fetchAll();
    
    // المشاريع حسب النوع
    $stmt = $db->query("
        SELECT project_type, COUNT(*) as count 
        FROM projects 
        GROUP BY project_type
    ");
    $projectsByType = $stmt->fetchAll();
    
    // المشاريع المنجزة في الفترة
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM projects 
        WHERE status = 'completed' 
        AND updated_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $completedProjects = $stmt->fetch()['count'];
    
    // إجمالي المهام
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_tasks");
    $totalTasks = $stmt->fetch()['total'];
    
    // المهام حسب الحالة
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM project_tasks 
        GROUP BY status
    ");
    $tasksByStatus = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $totalProjects = 0;
    $projectsByStatus = [];
    $projectsByType = [];
    $completedProjects = 0;
    $totalTasks = 0;
    $tasksByStatus = [];
}

// جلب إحصائيات الأمان
try {
    // التهديدات في الفترة
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM user_events 
        WHERE event_type IN ('security_alert', 'threat_detected', 'malware_found')
        AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $totalThreats = $stmt->fetch()['total'];
    
    // التنبيهات الحرجة
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM user_events 
        WHERE severity = 'critical'
        AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $criticalAlerts = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $totalThreats = 0;
    $criticalAlerts = 0;
}

// الحصول على المستخدم الحالي
$currentUser = current_user();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير الشاملة - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
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
        
        /* بطاقات التقارير */
        .report-card {
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
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* أزرار الفترات */
        .period-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .period-btn:hover, .period-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* الرسوم البيانية */
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
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
            
            .period-tabs {
                justify-content: center;
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
        
        /* تنسيق الجداول */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .stats-table th {
            text-align: right;
            padding: 10px;
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .stats-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .badge-count {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
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
            <a href="audit-logs.php" class="nav-link">
                <i class="fas fa-history"></i> سجلات التدقيق
            </a>
            <a href="security-settings.php" class="nav-link">
                <i class="fas fa-cog"></i> إعدادات الأمان
            </a>
            <a href="projects.php" class="nav-link">
                <i class="fas fa-project-diagram"></i> المشاريع
            </a>
            <a href="activity.php" class="nav-link">
                <i class="fas fa-chart-line"></i> النشاطات
            </a>
            <a href="reports.php" class="nav-link active">
                <i class="fas fa-file-alt"></i> التقارير
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-sliders-h"></i> إعدادات النظام
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
                <i class="fas fa-file-alt text-primary me-2"></i>
                التقارير الشاملة
            </h2>
            <div>
                <button class="btn btn-success me-2" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf me-1"></i>
                    PDF
                </button>
                <button class="btn btn-info" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel me-1"></i>
                    Excel
                </button>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- أزرار الفترات -->
        <div class="period-tabs">
            <a href="?period=today" class="period-btn <?php echo $period == 'today' ? 'active' : ''; ?>">
                <i class="fas fa-sun me-1"></i> اليوم
            </a>
            <a href="?period=yesterday" class="period-btn <?php echo $period == 'yesterday' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-day me-1"></i> أمس
            </a>
            <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week me-1"></i> آخر 7 أيام
            </a>
            <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt me-1"></i> آخر 30 يوم
            </a>
            <a href="?period=year" class="period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">
                <i class="fas fa-calendar me-1"></i> آخر سنة
            </a>
            <a href="#" onclick="showCustomRange()" class="period-btn <?php echo $period == 'custom' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h me-1"></i> نطاق مخصص
            </a>
        </div>

        <!-- نطاق مخصص -->
        <?php if ($period == 'custom'): ?>
        <div class="report-card" id="customRange">
            <form method="GET" class="row g-3">
                <input type="hidden" name="period" value="custom">
                <div class="col-md-5">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> عرض
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">إجمالي المستخدمين</div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-change">
                    <span class="text-success">+<?php echo $newUsers; ?></span> جديد في الفترة
                </div>
                <i class="fas fa-users stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">إجمالي الأحداث</div>
                <div class="stat-value"><?php echo number_format($totalEvents); ?></div>
                <div class="stat-change">
                    <span class="text-danger"><?php echo $criticalAlerts; ?></span> حدث حرج
                </div>
                <i class="fas fa-chart-line stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">المشاريع</div>
                <div class="stat-value"><?php echo number_format($totalProjects); ?></div>
                <div class="stat-change">
                    <span class="text-success">+<?php echo $completedProjects; ?></span> مكتمل
                </div>
                <i class="fas fa-project-diagram stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">المهام</div>
                <div class="stat-value"><?php echo number_format($totalTasks); ?></div>
                <div class="stat-change">
                    <span class="text-warning"><?php echo $totalThreats; ?></span> تهديد أمني
                </div>
                <i class="fas fa-tasks stat-icon"></i>
            </div>
        </div>

        <!-- الرسوم البيانية -->
        <div class="row">
            <div class="col-md-6">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie text-primary"></i>
                            توزيع المستخدمين
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar text-success"></i>
                            الأحداث حسب المستوى
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="eventsSeverityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line text-info"></i>
                            النشاط اليومي
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailyActivityChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie text-warning"></i>
                            حالة المشاريع
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="projectsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- جداول إحصائية -->
        <div class="row">
            <div class="col-md-4">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users"></i>
                            المستخدمين حسب النوع
                        </h5>
                    </div>
                    <table class="stats-table">
                        <?php foreach ($usersByType as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['user_type']); ?></td>
                            <td class="text-start">
                                <span class="badge-count"><?php echo $type['count']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-tags"></i>
                            أنواع الأحداث
                        </h5>
                    </div>
                    <table class="stats-table">
                        <?php foreach ($eventsByType as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td class="text-start">
                                <span class="badge-count"><?php echo $event['count']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="report-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-tasks"></i>
                            حالة المهام
                        </h5>
                    </div>
                    <table class="stats-table">
                        <?php foreach ($tasksByStatus as $task): ?>
                        <tr>
                            <td><?php 
                                echo match($task['status']) {
                                    'pending' => 'معلق',
                                    'in_progress' => 'قيد التنفيذ',
                                    'completed' => 'مكتمل',
                                    'cancelled' => 'ملغي',
                                    default => $task['status']
                                };
                            ?></td>
                            <td class="text-start">
                                <span class="badge-count"><?php echo $task['count']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <script>
        // التحكم في الشريط الجانبي
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // إخفاء شاشة التحميل
        window.addEventListener('load', function() {
            document.getElementById('loading').classList.remove('show');
        });
        
        // إظهار نطاق مخصص
        function showCustomRange() {
            window.location.href = '?period=custom&date_from=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>';
        }
        
        // تصدير التقرير
        function exportReport(format) {
            window.location.href = 'export_report.php?format=' + format + '&period=<?php echo $period; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>';
        }
        
        // رسم بياني للمستخدمين
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        new Chart(usersCtx, {
            type: 'pie',
            data: {
                labels: [<?php foreach ($usersByType as $type): ?>'<?php echo $type['user_type']; ?>',<?php endforeach; ?>],
                datasets: [{
                    data: [<?php foreach ($usersByType as $type): ?><?php echo $type['count']; ?>,<?php endforeach; ?>],
                    backgroundColor: ['#1e3c72', '#2a5298', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // رسم بياني للأحداث حسب المستوى
        const severityCtx = document.getElementById('eventsSeverityChart').getContext('2d');
        new Chart(severityCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($eventsBySeverity as $sev): ?>'<?php echo $sev['severity'] ?? 'غير محدد'; ?>',<?php endforeach; ?>],
                datasets: [{
                    data: [<?php foreach ($eventsBySeverity as $sev): ?><?php echo $sev['count']; ?>,<?php endforeach; ?>],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // رسم بياني للنشاط اليومي
        const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($dailyActivity as $day): ?>'<?php echo $day['date']; ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'النشاطات',
                    data: [<?php foreach ($dailyActivity as $day): ?><?php echo $day['count']; ?>,<?php endforeach; ?>],
                    borderColor: '#1e3c72',
                    backgroundColor: 'rgba(30, 60, 114, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // رسم بياني للمشاريع
        const projectsCtx = document.getElementById('projectsChart').getContext('2d');
        new Chart(projectsCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($projectsByStatus as $proj): ?>'<?php echo $proj['status']; ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'عدد المشاريع',
                    data: [<?php foreach ($projectsByStatus as $proj): ?><?php echo $proj['count']; ?>,<?php endforeach; ?>],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>