<?php
/**
 * سجل النشاطات والحركات
 * Activity Log Page
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
$period = isset($_GET['period']) ? $_GET['period'] : 'today';
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
    case 'custom':
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
        break;
}

// جلب النشاطات
try {
    $query = "
        SELECT a.*, u.username, u.full_name, u.user_type
        FROM activity_log a
        LEFT JOIN users_all u ON a.user_id = u.id
        WHERE DATE(a.created_at) BETWEEN ? AND ?
        ORDER BY a.created_at DESC
        LIMIT 1000
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$dateFrom, $dateTo]);
    $activities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $activities = [];
    set_error('خطأ في جلب النشاطات: ' . $e->getMessage());
}

// إحصائيات النشاطات
$stats = [
    'total' => count($activities),
    'by_type' => [],
    'by_user' => [],
    'hourly' => array_fill(0, 24, 0)
];

foreach ($activities as $activity) {
    // إحصائيات حسب النوع
    $type = $activity['action_type'] ?? 'other';
    $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
    
    // إحصائيات حسب المستخدم
    $user = $activity['username'] ?? 'system';
    $stats['by_user'][$user] = ($stats['by_user'][$user] ?? 0) + 1;
    
    // إحصائيات حسب الساعة
    $hour = (int)date('H', strtotime($activity['created_at']));
    $stats['hourly'][$hour]++;
}

// ترتيب الإحصائيات
arsort($stats['by_type']);
arsort($stats['by_user']);

// الحصول على المستخدم الحالي
$currentUser = current_user();

// إنشاء جدول النشاطات إذا لم يكن موجوداً
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS activity_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action_type VARCHAR(50) NOT NULL,
            target_type VARCHAR(50),
            target_id INT,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_action (action_type),
            INDEX idx_created (created_at),
            INDEX idx_target (target_type, target_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // تجاهل الخطأ إذا كان الجدول موجوداً
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل النشاطات - نظام الحماية</title>


    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/ar.js"></script>
    
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
        
        /* شريط الفترات */
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
        
        .period-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .period-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* شارات الأنشطة */
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .icon-login { background: #28a745; }
        .icon-logout { background: #dc3545; }
        .icon-create { background: #17a2b8; }
        .icon-update { background: #ffc107; color: #000; }
        .icon-delete { background: #dc3545; }
        .icon-view { background: #6f42c1; }
        .icon-download { background: #28a745; }
        .icon-upload { background: #fd7e14; }
        
        /* التوقيت */
        .time-badge {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #495057;
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
        
        /* تنسيق الجدول */
        .table-activity {
            font-size: 0.9rem;
        }
        
        .table-activity td {
            vertical-align: middle;
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* الرسم البياني */
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        
        /* قائمة الأنشطة الأكثر تكراراً */
        .top-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .top-item:last-child {
            border-bottom: none;
        }
        
        .top-count {
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
            <a href="activity.php" class="nav-link active">
                <i class="fas fa-chart-line"></i> سجل النشاطات
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
                <i class="fas fa-chart-line text-primary me-2"></i>
                سجل النشاطات والحركات
            </h2>
            <div>
                <span class="badge bg-info p-2">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?php echo date('Y-m-d'); ?>
                </span>
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
            <a href="#" onclick="showCustomRange()" class="period-btn <?php echo $period == 'custom' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h me-1"></i> نطاق مخصص
            </a>
        </div>

        <!-- نطاق مخصص -->
        <?php if ($period == 'custom'): ?>
        <div class="content-card" id="customRange">
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
                <div class="stat-title">إجمالي النشاطات</div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <i class="fas fa-chart-bar stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">أكثر نوع نشاط</div>
                <div class="stat-value">
                    <?php 
                    $topType = array_key_first($stats['by_type']);
                    echo $topType ?: '-';
                    ?>
                </div>
                <i class="fas fa-tag stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">أكثر مستخدم نشاطاً</div>
                <div class="stat-value">
                    <?php 
                    $topUser = array_key_first($stats['by_user']);
                    echo $topUser ?: '-';
                    ?>
                </div>
                <i class="fas fa-user stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">ذروة النشاط</div>
                <div class="stat-value">
                    <?php 
                    $peakHour = array_search(max($stats['hourly']), $stats['hourly']);
                    echo $peakHour !== false ? $peakHour . ':00' : '-';
                    ?>
                </div>
                <i class="fas fa-clock stat-icon"></i>
            </div>
        </div>

        <!-- الرسم البياني -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-chart-area"></i>
                    توزيع النشاطات على مدار اليوم
                </h5>
            </div>
            <div class="chart-container">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <div class="row">
            <!-- أكثر أنواع النشاطات -->
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-tags"></i>
                            أكثر أنواع النشاطات
                        </h5>
                    </div>
                    <ul class="top-list">
                        <?php 
                        $count = 0;
                        foreach ($stats['by_type'] as $type => $typeCount): 
                            if ($count++ >= 5) break;
                            $percentage = round(($typeCount / $stats['total']) * 100, 1);
                        ?>
                        <li class="top-item">
                            <span>
                                <i class="fas fa-circle me-2" style="color: var(--primary-color); font-size: 0.5rem;"></i>
                                <?php echo htmlspecialchars($type); ?>
                            </span>
                            <span>
                                <span class="top-count"><?php echo $typeCount; ?></span>
                                <small class="text-muted ms-2"><?php echo $percentage; ?>%</small>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- أكثر المستخدمين نشاطاً -->
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users"></i>
                            أكثر المستخدمين نشاطاً
                        </h5>
                    </div>
                    <ul class="top-list">
                        <?php 
                        $count = 0;
                        foreach ($stats['by_user'] as $user => $userCount): 
                            if ($count++ >= 5) break;
                            $percentage = round(($userCount / $stats['total']) * 100, 1);
                        ?>
                        <li class="top-item">
                            <span>
                                <i class="fas fa-user-circle me-2" style="color: var(--primary-color);"></i>
                                <?php echo htmlspecialchars($user); ?>
                            </span>
                            <span>
                                <span class="top-count"><?php echo $userCount; ?></span>
                                <small class="text-muted ms-2"><?php echo $percentage; ?>%</small>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- إحصائيات سريعة -->
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            معلومات إضافية
                        </h5>
                    </div>
                    <table class="table table-sm">
                        <tr>
                            <th>الفترة:</th>
                            <td><?php echo $dateFrom; ?> إلى <?php echo $dateTo; ?></td>
                        </tr>
                        <tr>
                            <th>متوسط النشاط يومياً:</th>
                            <td><?php 
                                $days = max(1, (strtotime($dateTo) - strtotime($dateFrom)) / 86400 + 1);
                                echo round($stats['total'] / $days, 1);
                            ?></td>
                        </tr>
                        <tr>
                            <th>أعلى ساعة نشاط:</th>
                            <td><?php 
                                $peak = array_search(max($stats['hourly']), $stats['hourly']);
                                echo $peak . ':00 (' . max($stats['hourly']) . ' نشاط)';
                            ?></td>
                        </tr>
                        <tr>
                            <th>أقل ساعة نشاط:</th>
                            <td><?php 
                                $lowest = array_search(min($stats['hourly']), $stats['hourly']);
                                echo $lowest . ':00 (' . min($stats['hourly']) . ' نشاط)';
                            ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- جدول النشاطات -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-list"></i>
                    تفاصيل النشاطات
                </h5>
                <span class="badge bg-primary"><?php echo count($activities); ?> نشاط</span>
            </div>
            
            <div class="table-responsive">
                <table id="activityTable" class="table table-hover table-activity">
                    <thead class="table-light">
                        <tr>
                            <th>الوقت</th>
                            <th>المستخدم</th>
                            <th>نوع النشاط</th>
                            <th>الوصف</th>
                            <th>الهدف</th>
                            <th>عنوان IP</th>
                            <th>المتصفح</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): 
                            $iconClass = 'icon-view';
                            if (strpos($activity['action_type'], 'login') !== false) $iconClass = 'icon-login';
                            elseif (strpos($activity['action_type'], 'logout') !== false) $iconClass = 'icon-logout';
                            elseif (strpos($activity['action_type'], 'create') !== false) $iconClass = 'icon-create';
                            elseif (strpos($activity['action_type'], 'update') !== false || strpos($activity['action_type'], 'edit') !== false) $iconClass = 'icon-update';
                            elseif (strpos($activity['action_type'], 'delete') !== false) $iconClass = 'icon-delete';
                            elseif (strpos($activity['action_type'], 'upload') !== false) $iconClass = 'icon-upload';
                            elseif (strpos($activity['action_type'], 'download') !== false) $iconClass = 'icon-download';
                        ?>
                        <tr>
                            <td>
                                <div class="time-badge">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('H:i:s', strtotime($activity['created_at'])); ?>
                                </div>
                                <small class="text-muted d-block">
                                    <?php echo date('Y-m-d', strtotime($activity['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($activity['user_id']): ?>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo mb_substr($activity['full_name'] ?? $activity['username'] ?? 'ن', 0, 1); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['full_name'] ?? $activity['username'] ?? 'نظام'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['user_type'] ?? ''); ?></small>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="user-info">
                                    <div class="user-avatar" style="background: #6c757d;">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div>
                                        <strong>نظام</strong>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="activity-icon <?php echo $iconClass; ?>">
                                        <i class="fas <?php 
                                            echo match($iconClass) {
                                                'icon-login' => 'fa-sign-in-alt',
                                                'icon-logout' => 'fa-sign-out-alt',
                                                'icon-create' => 'fa-plus',
                                                'icon-update' => 'fa-edit',
                                                'icon-delete' => 'fa-trash',
                                                'icon-upload' => 'fa-upload',
                                                'icon-download' => 'fa-download',
                                                default => 'fa-eye'
                                            };
                                        ?>"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($activity['action_type']); ?></span>
                                </div>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(mb_substr($activity['description'] ?? '', 0, 100)); ?></small>
                                <?php if (strlen($activity['description'] ?? '') > 100): ?>
                                <span class="text-primary" style="cursor: pointer;" onclick="showFullDescription(<?php echo $activity['id']; ?>)">
                                    ... المزيد
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($activity['target_type']): ?>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($activity['target_type']); ?> #<?php echo $activity['target_id']; ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted font-monospace">
                                    <?php echo $activity['ip_address'] ?? '-'; ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted" title="<?php echo htmlspecialchars($activity['user_agent'] ?? ''); ?>">
                                    <?php 
                                    $agent = $activity['user_agent'] ?? '';
                                    if (strpos($agent, 'Chrome') !== false) echo '<i class="fab fa-chrome"></i> Chrome';
                                    elseif (strpos($agent, 'Firefox') !== false) echo '<i class="fab fa-firefox"></i> Firefox';
                                    elseif (strpos($agent, 'Safari') !== false) echo '<i class="fab fa-safari"></i> Safari';
                                    elseif (strpos($agent, 'Edge') !== false) echo '<i class="fab fa-edge"></i> Edge';
                                    else echo '<i class="fas fa-globe"></i> متصفح';
                                    ?>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <br>
                                لا توجد نشاطات في هذه الفترة
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // تهيئة DataTable
        $(document).ready(function() {
            $('#activityTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
                },
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { type: 'date', targets: 0 }
                ]
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
        
        // إظهار نطاق مخصص
        function showCustomRange() {
            window.location.href = '?period=custom&date_from=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>';
        }
        
        // الرسم البياني
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php for ($i = 0; $i < 24; $i++): ?>'<?php echo $i; ?>:00',<?php endfor; ?>],
                datasets: [{
                    label: 'عدد النشاطات',
                    data: [<?php echo implode(',', $stats['hourly']); ?>],
                    borderColor: '#1e3c72',
                    backgroundColor: 'rgba(30, 60, 114, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#2a5298',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `النشاطات: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'عدد النشاطات'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'الساعة'
                        }
                    }
                }
            }
        });
        
        // تحديث كل 5 دقائق
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 300000);
    </script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
</body>
</html>