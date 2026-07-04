<?php
/**
 * لوحة التحكم الرئيسية للمدير
 * Admin Dashboard
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
//require_admin();

// الحصول على إحصائيات
$stats = get_dashboard_stats();
$recentEvents = get_recent_events(15);
$userStats = get_user_stats();

// إحصائيات إضافية
try {
    // النشاط اليومي لآخر 7 أيام
    $stmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM user_events 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $weeklyActivity = $stmt->fetchAll();
    
    // أكثر المستخدمين نشاطاً
    $stmt = $db->query("
        SELECT ua.full_name, ua.username, COUNT(ue.id) as action_count
        FROM user_events ue
        JOIN users_all ua ON ue.user_id = ua.id
        WHERE ue.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY ue.user_id
        ORDER BY action_count DESC
        LIMIT 5
    ");
    $topUsers = $stmt->fetchAll();
    
    // توزيع الأحداث حسب النوع
    $stmt = $db->query("
        SELECT event_type, COUNT(*) as count 
        FROM user_events 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY event_type
        ORDER BY count DESC
        LIMIT 5
    ");
    $eventTypes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $weeklyActivity = [];
    $topUsers = [];
    $eventTypes = [];
}

// الحصول على معلومات المستخدم الحالي
$currentUser = current_user();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
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
        
        .sidebar-header h4 {
            margin: 15px 0 5px;
            font-size: 1.2rem;
        }
        
        .sidebar-header small {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            margin: 5px 0;
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
            font-size: 1.1rem;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: #ffd700;
            padding-right: 35px;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3.5rem;
            opacity: 0.15;
            transition: opacity 0.3s;
        }
        
        .stat-card:hover .stat-icon {
            opacity: 0.25;
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            line-height: 1.2;
        }
        
        .stat-change {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .change-up { color: var(--success-color); }
        .change-down { color: var(--danger-color); }
        
        /* بطاقات المحتوى */
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
        
        .card-title i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-critical {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            color: white;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #5fa8f4 0%, #2a6df4 100%);
            color: white;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: white;
        }
        
        /* الجداول */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-custom th {
            text-align: right;
            padding: 12px 8px;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .table-custom td {
            padding: 12px 8px;
            border-bottom: 1px solid #f0f0f0;
            color: #2c3e50;
        }
        
        .table-custom tr:hover td {
            background-color: #f8f9fa;
        }
        
        /* شارات المستخدمين */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* ألوان مخصصة */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }
        
        .bg-gradient-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        /* التجاوب مع الشاشات */
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
        }
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }
        
        .severity-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .severity-critical { background: #dc3545; color: white; }
        .severity-warning { background: #ffc107; color: black; }
        .severity-info { background: #17a2b8; color: white; }
        
        /* تحميل البيانات */
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
            <h4><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></h4>
            <small>
                <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i>
                متصل
            </small>
        </div>
        
        <div class="nav-menu">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    لوحة التحكم
                </a>
            </div>
            
            <div class="nav-item">
                <a href="users-management.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    إدارة المستخدمين
                </a>
            </div>
            
            <div class="nav-item">
                <a href="roles-permissions.php" class="nav-link">
                    <i class="fas fa-key"></i>
                    الأدوار والصلاحيات
                </a>
            </div>
            
            <div class="nav-item">
                <a href="audit-logs.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    سجلات التدقيق
                </a>
            </div>
            
            <div class="nav-item">
                <a href="security-settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    إعدادات الأمان
                </a>
            </div>
            
            <div class="nav-item">
                <a href="projects.php" class="nav-link">
                    <i class="fas fa-project-diagram"></i>
                    المشاريع
                </a>
            </div>
            
            <div class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    التقارير
                </a>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
            
            <div class="nav-item">
                <a href="../../index.php" class="nav-link">
                    <i class="fas fa-globe"></i>
                    الموقع الرئيسي
                </a>
            </div>
            
            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل خروج
                </a>
            </div>
        </div>
        
        <div class="text-center p-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
            <small>
                <i class="far fa-clock"></i>
                <span id="liveClock"></span>
            </small>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- شريط الأدوات -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-chart-pie text-primary me-2"></i>
                لوحة التحكم
            </h2>
            <div>
                <span class="badge bg-light text-dark p-2 ms-2">
                    <i class="far fa-calendar-alt me-1"></i>
                    <?php echo date('Y-m-d'); ?>
                </span>
                <span class="badge bg-primary p-2">
                    <i class="fas fa-user-shield me-1"></i>
                    مدير النظام
                </span>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- بطاقات الإحصائيات -->
        <div class="stats-grid">
            <!-- المستخدمين -->
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-users text-primary me-1"></i>
                    إجمالي المستخدمين
                </div>
                <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-change">
                    <span class="change-up">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo $userStats['new_this_month'] ?? 0; ?> جديد هذا الشهر
                    </span>
                </div>
                <i class="fas fa-users stat-icon"></i>
            </div>
            
            <!-- النشاط اليومي -->
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-chart-line text-success me-1"></i>
                    النشاط اليومي
                </div>
                <div class="stat-value"><?php echo number_format($stats['events_today']); ?></div>
                <div class="stat-change">
                    <span class="<?php echo $stats['critical_events'] > 0 ? 'change-down' : 'change-up'; ?>">
                        <i class="fas <?php echo $stats['critical_events'] > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                        <?php echo $stats['critical_events']; ?> حدث حرج
                    </span>
                </div>
                <i class="fas fa-activity stat-icon"></i>
            </div>
            
            <!-- المستخدمين النشطين -->
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-user-clock text-info me-1"></i>
                    مستخدمين نشطين اليوم
                </div>
                <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                <div class="stat-change">
                    <span class="text-muted">
                        <i class="far fa-clock"></i>
                        من أصل <?php echo $stats['users']; ?> مستخدم
                    </span>
                </div>
                <i class="fas fa-user-check stat-icon"></i>
            </div>
            
            <!-- المشاريع -->
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-briefcase text-warning me-1"></i>
                    المشاريع النشطة
                </div>
                <div class="stat-value"><?php echo $stats['active_projects']; ?></div>
                <div class="stat-change">
                    <span class="text-muted">
                        <i class="fas fa-folder-open"></i>
                        من أصل <?php echo $stats['projects']; ?> مشروع
                    </span>
                </div>
                <i class="fas fa-project-diagram stat-icon"></i>
            </div>
        </div>

        <!-- صف الرسوم البيانية -->
        <div class="row">
            <!-- النشاط الأسبوعي -->
            <div class="col-md-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            النشاط اليومي لآخر 7 أيام
                        </h5>
                        <select class="form-select form-select-sm w-auto" id="chartRange">
                            <option value="7">آخر 7 أيام</option>
                            <option value="30">آخر 30 يوم</option>
                            <option value="90">آخر 3 أشهر</option>
                        </select>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- توزيع المستخدمين -->
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie"></i>
                            توزيع المستخدمين
                        </h5>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- صف إضافي -->
        <div class="row mt-3">
            <!-- أكثر المستخدمين نشاطاً -->
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-crown text-warning"></i>
                            أكثر المستخدمين نشاطاً (آخر 7 أيام)
                        </h5>
                        <span class="badge bg-info"><?php echo count($topUsers); ?> مستخدم</span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>المستخدم</th>
                                    <th>النشاطات</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topUsers as $index => $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo mb_substr($user['full_name'] ?? $user['username'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong>
                                                <br>
                                                <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($user['action_count']); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $totalActions = array_sum(array_column($topUsers, 'action_count'));
                                        $percentage = $totalActions > 0 ? round(($user['action_count'] / $totalActions) * 100) : 0;
                                        ?>
                                        <div class="progress" style="height: 5px; width: 80px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($topUsers)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <br>
                                        لا توجد بيانات كافية
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- أنواع الأحداث -->
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-tags text-info"></i>
                            أنواع الأحداث (آخر 7 أيام)
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>نوع الحدث</th>
                                    <th>العدد</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalEvents = array_sum(array_column($eventTypes, 'count'));
                                foreach ($eventTypes as $event): 
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge-custom badge-info">
                                            <?php echo htmlspecialchars($event['event_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($event['count']); ?></strong>
                                    </td>
                                    <td>
                                        <?php $percentage = $totalEvents > 0 ? round(($event['count'] / $totalEvents) * 100) : 0; ?>
                                        <div class="progress" style="height: 5px; width: 100px;">
                                            <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $percentage; ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($eventTypes)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <br>
                                        لا توجد بيانات
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخر الأحداث -->
        <div class="content-card mt-3">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-history"></i>
                    آخر الأحداث في النظام
                </h5>
                <a href="audit-logs.php" class="btn btn-sm btn-primary">
                    عرض الكل <i class="fas fa-arrow-left me-1"></i>
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>المستخدم</th>
                            <th>الحدث</th>
                            <th>الإجراء</th>
                            <th>الوصف</th>
                            <th>المستوى</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEvents as $event): ?>
                        <tr>
                            <td>
                                <span title="<?php echo $event['created_at']; ?>">
                                    <?php echo date('H:i:s', strtotime($event['created_at'])); ?>
                                </span>
                                <br>
                                <small class="text-muted">
                                    <?php echo date('Y-m-d', strtotime($event['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($event['user_id']): ?>
                                <div class="user-info">
                                    <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                        <?php echo mb_substr($event['full_name'] ?? $event['username'] ?? 'ن', 0, 1); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($event['full_name'] ?? $event['username'] ?? 'نظام'); ?></strong>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">نظام</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-custom <?php 
                                    echo match($event['event_type']) {
                                        'login', 'logout' => 'badge-info',
                                        'security_alert', 'threat_detected' => 'badge-critical',
                                        default => 'badge-success'
                                    };
                                ?>">
                                    <?php echo htmlspecialchars($event['event_type']); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($event['action']); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(mb_substr($event['description'] ?? '', 0, 50)) . '...'; ?></small>
                            </td>
                            <td>
                                <?php if ($event['severity']): ?>
                                <span class="severity-badge severity-<?php echo $event['severity']; ?>">
                                    <?php echo $event['severity']; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted font-monospace">
                                    <?php echo $event['ip_address'] ?? '-'; ?>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentEvents)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <br>
                                لا توجد أحداث حديثة
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // التحكم في الشريط الجانبي للجوال
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // الساعة المباشرة
        function updateClock() {
            const now = new Date();
            const options = { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
                timeZone: 'Asia/Riyadh'
            };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('ar-SA', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
        
        // إخفاء شاشة التحميل
        window.addEventListener('load', function() {
            document.getElementById('loading').classList.remove('show');
        });
        
        // الرسم البياني للنشاط الأسبوعي
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    foreach (array_reverse($weeklyActivity) as $day) {
                        echo "'" . date('Y-m-d', strtotime($day['date'])) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'النشاطات',
                    data: [<?php 
                        foreach (array_reverse($weeklyActivity) as $day) {
                            echo $day['count'] . ",";
                        }
                    ?>],
                    borderColor: '#1e3c72',
                    backgroundColor: 'rgba(30, 60, 114, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // الرسم البياني للمستخدمين
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    foreach ($userStats['by_type'] ?? [] as $type) {
                        echo "'" . $type['user_type'] . "',";
                    }
                ?>],
                datasets: [{
                    data: [<?php 
                        foreach ($userStats['by_type'] ?? [] as $type) {
                            echo $type['count'] . ",";
                        }
                    ?>],
                    backgroundColor: [
                        '#1e3c72',
                        '#2a5298',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#17a2b8'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
        
        // تحديث الرسم البياني عند تغيير النطاق
        document.getElementById('chartRange').addEventListener('change', function() {
            document.getElementById('loading').classList.add('show');
            // هنا يمكن إضافة طلب AJAX لتحديث الرسم البياني
            setTimeout(() => {
                document.getElementById('loading').classList.remove('show');
            }, 1000);
        });
        
        // تحديث البيانات كل 30 ثانية
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>