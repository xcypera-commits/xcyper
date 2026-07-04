<?php
// =============================================
// cloud-unit/pages/monitoring.php
// صفحة مراقبة استخدام التخزين
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// تعريف الدوال المساعدة المفقودة
if (!function_exists('getServerStatusBadge')) {
    function getServerStatusBadge($status) {
        $classes = [
            'online' => 'bg-green-600 bg-opacity-20 text-green-400',
            'offline' => 'bg-red-600 bg-opacity-20 text-red-400',
            'maintenance' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
            'warning' => 'bg-orange-600 bg-opacity-20 text-orange-400'
        ];
        
        $texts = [
            'online' => 'نشط',
            'offline' => 'متوقف',
            'maintenance' => 'صيانة',
            'warning' => 'تحذير'
        ];
        
        $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
        $text = $texts[$status] ?? $status;
        
        return "<span class='px-2 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (!$datetime) return '-';
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) return 'منذ لحظات';
        if ($diff < 3600) return 'منذ ' . floor($diff / 60) . ' دقيقة';
        if ($diff < 86400) return 'منذ ' . floor($diff / 3600) . ' ساعة';
        if ($diff < 2592000) return 'منذ ' . floor($diff / 86400) . ' يوم';
        return date('Y-m-d', $time);
    }
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'add_monitoring':
                // إضافة سجل مراقبة جديد
                $sql = "INSERT INTO cloud_storage_monitoring (
                    server_id, total_space_gb, used_space_gb, free_space_gb,
                    used_percent, files_count, folders_count, daily_growth_mb,
                    check_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['server_id'],
                    $_POST['total_space_gb'],
                    $_POST['used_space_gb'],
                    $_POST['total_space_gb'] - $_POST['used_space_gb'],
                    round(($_POST['used_space_gb'] / $_POST['total_space_gb']) * 100, 2),
                    $_POST['files_count'] ?? 0,
                    $_POST['folders_count'] ?? 0,
                    $_POST['daily_growth_mb'] ?? 0
                ]);
                
                $response['success'] = true;
                $response['message'] = 'تم إضافة سجل المراقبة';
                break;
                
            case 'resolve_alert':
                // حل تنبيه
                $sql = "UPDATE cloud_storage_alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['alert_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم حل التنبيه';
                break;
                
            case 'add_file_type':
                // إضافة إحصائية نوع ملف
                $sql = "INSERT INTO cloud_file_types_stats (
                    server_id, file_extension, files_count, total_size_mb, percentage, recorded_at
                ) VALUES (?, ?, ?, ?, ?, CURDATE())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['server_id'],
                    $_POST['file_extension'],
                    $_POST['files_count'],
                    $_POST['total_size_mb'],
                    $_POST['percentage']
                ]);
                
                $response['success'] = true;
                $response['message'] = 'تم إضافة إحصائية نوع الملف';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الخادم المحدد
    $server_id = $_GET['server'] ?? null;
    $period = $_GET['period'] ?? 'week'; // day, week, month, year
    
    // جلب إحصائيات التخزين الحالية
    $current_stats = [];
    if ($server_id) {
        $stmt = $db->prepare("
            SELECT s.*, 
                   (SELECT COUNT(*) FROM cloud_files WHERE server_id = s.id AND is_folder = 0) as total_files,
                   (SELECT COUNT(*) FROM cloud_files WHERE server_id = s.id AND is_folder = 1) as total_folders
            FROM cloud_servers s
            WHERE s.id = ?
        ");
        $stmt->execute([$server_id]);
        $current_stats = $stmt->fetch();
    }
    
    // جلب سجل المراقبة للرسم البياني
    $history_sql = "
        SELECT * FROM cloud_storage_monitoring 
        WHERE 1=1
    ";
    
    $history_params = [];
    
    if ($server_id) {
        $history_sql .= " AND server_id = ?";
        $history_params[] = $server_id;
    }
    
    // تحديد الفترة
    switch ($period) {
        case 'day':
            $history_sql .= " AND check_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $history_sql .= " AND check_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $history_sql .= " AND check_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $history_sql .= " AND check_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
    
    $history_sql .= " ORDER BY check_time ASC";
    
    $stmt = $db->prepare($history_sql);
    $stmt->execute($history_params);
    $monitoring_history = $stmt->fetchAll();
    
    // جلب تنبيهات التخزين
    $alerts = $db->query("
        SELECT a.*, s.server_name
        FROM cloud_storage_alerts a
        LEFT JOIN cloud_servers s ON a.server_id = s.id
        WHERE a.is_resolved = 0
        ORDER BY 
            CASE a.severity
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END,
            a.created_at DESC
    ")->fetchAll();
    
    // جلب إحصائيات أنواع الملفات
    $file_types = [];
    if ($server_id) {
        $stmt = $db->prepare("
            SELECT file_extension, SUM(files_count) as total_files, AVG(total_size_mb) as avg_size
            FROM cloud_file_types_stats
            WHERE server_id = ? AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY file_extension
            ORDER BY total_files DESC
            LIMIT 10
        ");
        $stmt->execute([$server_id]);
        $file_types = $stmt->fetchAll();
    } else {
        $file_types = $db->query("
            SELECT file_extension, SUM(files_count) as total_files, AVG(total_size_mb) as avg_size
            FROM cloud_file_types_stats
            WHERE recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY file_extension
            ORDER BY total_files DESC
            LIMIT 10
        ")->fetchAll();
    }
    
    // جلب إحصائيات النمو
    $growth_stats = $db->query("
        SELECT 
            DATE(check_time) as date,
            AVG(used_space_gb) as avg_used,
            AVG(daily_growth_mb) as avg_growth
        FROM cloud_storage_monitoring
        WHERE check_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(check_time)
        ORDER BY date DESC
    ")->fetchAll();
    
    // قائمة الخوادم للاختيار
    $servers = $db->query("
        SELECT id, server_name, ip_address, status
        FROM cloud_servers
        ORDER BY server_name
    ")->fetchAll();
    
    // إحصائيات عامة
    $overall_stats = $db->query("
        SELECT 
            COUNT(DISTINCT server_id) as monitored_servers,
            AVG(used_percent) as avg_usage,
            SUM(used_space_gb) as total_used,
            SUM(total_space_gb) as total_space,
            SUM(files_count) as total_files,
            SUM(folders_count) as total_folders
        FROM cloud_storage_monitoring
        WHERE DATE(check_time) = CURDATE()
    ")->fetch();
    
    // توقع نمو التخزين
    $prediction = $db->query("
        SELECT 
            AVG(daily_growth_mb) as avg_daily_growth,
            MAX(used_space_gb) as max_used,
            MIN(used_space_gb) as min_used
        FROM cloud_storage_monitoring
        WHERE check_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch();
    
} catch (Exception $e) {
    $monitoring_history = [];
    $alerts = [];
    $file_types = [];
    $growth_stats = [];
    $servers = [];
    $overall_stats = [
        'monitored_servers' => 0,
        'avg_usage' => 0,
        'total_used' => 0,
        'total_space' => 0,
        'total_files' => 0,
        'total_folders' => 0
    ];
    $prediction = [
        'avg_daily_growth' => 0,
        'max_used' => 0,
        'min_used' => 0
    ];
    $current_stats = [];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function getSeverityBadge($severity) {
    $classes = [
        'high' => 'bg-red-600 bg-opacity-20 text-red-400',
        'medium' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'low' => 'bg-blue-600 bg-opacity-20 text-blue-400'
    ];
    
    $texts = [
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض'
    ];
    
    $class = $classes[$severity] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$severity] ?? $severity;
    
    return "<span class='px-2 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getAlertTypeBadge($type) {
    $classes = [
        'critical' => 'bg-red-600 bg-opacity-20 text-red-400',
        'warning' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'info' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'success' => 'bg-green-600 bg-opacity-20 text-green-400'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    
    return "<span class='px-2 py-1 rounded-full text-xs font-semibold $class'>$type</span>";
}

function formatStorage($gb) {
    if ($gb < 1024) return round($gb, 1) . ' GB';
    return round($gb / 1024, 2) . ' TB';
}

function calculateDaysUntilFull($current_gb, $total_gb, $daily_growth_mb) {
    if ($daily_growth_mb <= 0) return '∞';
    
    $free_gb = $total_gb - $current_gb;
    $daily_growth_gb = $daily_growth_mb / 1024;
    
    if ($daily_growth_gb <= 0) return '∞';
    
    $days = ceil($free_gb / $daily_growth_gb);
    return $days;
}


?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري التحميل...</p>
    </div>
</div>

<!-- ============================================= -->
<!-- رأس الصفحة مع الاختيارات -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-2xl p-8 mb-8 cyber-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">📊</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">مراقبة التخزين</h1>
                <p class="text-gray-400 mt-1">تحليل ومتابعة استخدام مساحة التخزين</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-all">
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الاختيارات -->
    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-slate-700">
        <select id="server-select" onchange="changeServer()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الخوادم</option>
            <?php foreach ($servers as $server): ?>
            <option value="<?php echo $server['id']; ?>" <?php echo ($server_id == $server['id']) ? 'selected' : ''; ?>>
                <?php echo $server['server_name']; ?> (<?php echo $server['ip_address']; ?>)
            </option>
            <?php endforeach; ?>
        </select>
        
        <select id="period-select" onchange="changePeriod()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="day" <?php echo $period == 'day' ? 'selected' : ''; ?>>آخر 24 ساعة</option>
            <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>آخر 7 أيام</option>
            <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>آخر 30 يوم</option>
            <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>آخر سنة</option>
        </select>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة عامة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">خوادم مراقبة</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $overall_stats['monitored_servers']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">متوسط الاستخدام</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo round($overall_stats['avg_usage'], 1); ?>%</p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي الملفات</p>
        <p class="text-2xl font-bold text-green-400"><?php echo number_format($overall_stats['total_files']); ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">المساحة المستخدمة</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo formatStorage($overall_stats['total_used']); ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- معلومات الخادم الحالي (إذا تم اختيار خادم) -->
<!-- ============================================= -->
<?php if ($server_id && $current_stats): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- بطاقة معلومات الخادم -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">معلومات الخادم</h3>
        
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-400">اسم الخادم:</span>
                <span class="font-semibold"><?php echo $current_stats['server_name']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">عنوان IP:</span>
                <span class="text-sm"><?php echo $current_stats['ip_address']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">الحالة:</span>
                <span><?php echo getServerStatusBadge($current_stats['status']); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">الملفات:</span>
                <span><?php echo number_format($current_stats['total_files']); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">المجلدات:</span>
                <span><?php echo number_format($current_stats['total_folders']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- بطاقة استخدام التخزين -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">استخدام التخزين</h3>
        
        <?php 
        $used = $current_stats['storage_used_gb'];
        $total = $current_stats['storage_gb'];
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        $free = $total - $used;
        ?>
        
        <div class="text-center mb-4">
            <div class="text-4xl font-bold text-blue-400 mb-2"><?php echo $percent; ?>%</div>
            <div class="progress-bar h-4">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 text-center">
            <div>
                <p class="text-sm text-gray-400">مستخدم</p>
                <p class="text-lg font-bold text-yellow-400"><?php echo formatStorage($used); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-400">متاح</p>
                <p class="text-lg font-bold text-green-400"><?php echo formatStorage($free); ?></p>
            </div>
        </div>
    </div>
    
    <!-- بطاقة توقعات النمو -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توقعات النمو</h3>
        
        <?php 
        $days_until_full = calculateDaysUntilFull($used, $total, $prediction['avg_daily_growth']);
        ?>
        
        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-400 mb-1">متوسط النمو اليومي</p>
                <p class="text-2xl font-bold text-purple-400"><?php echo round($prediction['avg_daily_growth'], 1); ?> MB</p>
            </div>
            
            <div>
                <p class="text-sm text-gray-400 mb-1">الأيام حتى الامتلاء</p>
                <p class="text-2xl font-bold <?php echo $days_until_full < 30 ? 'text-red-400' : 'text-green-400'; ?>">
                    <?php echo $days_until_full; ?> يوم
                </p>
            </div>
            
            <?php if ($days_until_full < 30): ?>
            <div class="p-3 bg-red-900 bg-opacity-20 border border-red-800 rounded-lg">
                <p class="text-sm text-red-400">⚠️ تحذير: المساحة ستمتلئ خلال أقل من شهر</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- الرسم البياني لاستخدام التخزين -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">📈 تطور استخدام التخزين</h3>
    
    <div style="position: relative; width: 100%; height: 300px;">
        <canvas id="storageChart"></canvas>
    </div>
</div>

<!-- ============================================= -->
<!-- التنبيهات وإحصائيات أنواع الملفات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- التنبيهات النشطة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">⚠️ تنبيهات نشطة</h3>
        
        <?php if (empty($alerts)): ?>
            <div class="text-center py-8">
                <div class="text-4xl text-green-400 mb-2">✓</div>
                <p class="text-gray-400">لا توجد تنبيهات نشطة</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($alerts as $alert): ?>
                <div class="p-4 bg-slate-700 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <?php echo getSeverityBadge($alert['severity']); ?>
                            <span class="mr-2 text-sm text-gray-400"><?php echo $alert['server_name']; ?></span>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo timeAgo($alert['created_at']); ?></span>
                    </div>
                    <p class="text-sm text-gray-300 mb-2"><?php echo $alert['message']; ?></p>
                    <button onclick="resolveAlert(<?php echo $alert['id']; ?>)" 
                            class="text-xs bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded-lg">
                        حل التنبيه
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- أكثر أنواع الملفات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">📁 أكثر أنواع الملفات</h3>
        
        <?php if (empty($file_types)): ?>
            <p class="text-gray-400 text-center py-8">لا توجد بيانات كافية</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($file_types as $type): 
                    $total_mb = $type['total_files'] * $type['avg_size'];
                ?>
                <div class="flex items-center">
                    <span class="text-2xl ml-3"><?php echo getFileIcon($type['file_extension']); ?></span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold"><?php echo strtoupper($type['file_extension'] ?: 'أخرى'); ?></span>
                            <span class="text-sm text-blue-400"><?php echo number_format($type['total_files']); ?> ملف</span>
                        </div>
                        <div class="progress-bar">
                            <?php 
                            $total_all = array_sum(array_column($file_types, 'total_files'));
                            $percent = $total_all > 0 ? round(($type['total_files'] / $total_all) * 100) : 0;
                            ?>
                            <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-400"><?php echo $percent; ?>%</span>
                            <span class="text-xs text-gray-400"><?php echo formatStorage($total_mb / 1024); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- سجل المراقبة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <h3 class="text-lg font-bold mb-4">📋 سجل المراقبة</h3>
    
    <?php if (empty($monitoring_history)): ?>
        <p class="text-gray-400 text-center py-8">لا توجد بيانات مراقبة</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الخادم</th>
                        <th class="px-4 py-3">المستخدم</th>
                        <th class="px-4 py-3">الاستخدام</th>
                        <th class="px-4 py-3">الملفات</th>
                        <th class="px-4 py-3">المجلدات</th>
                        <th class="px-4 py-3">النمو اليومي</th>
                        <th class="px-4 py-3">وقت الفحص</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($monitoring_history, 0, 10) as $record): 
                        $server_name = '';
                        foreach ($servers as $s) {
                            if ($s['id'] == $record['server_id']) {
                                $server_name = $s['server_name'];
                                break;
                            }
                        }
                    ?>
                    <tr class="border-b border-slate-700">
                        <td class="px-4 py-3 text-sm"><?php echo $server_name ?: 'غير معروف'; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo formatStorage($record['used_space_gb']); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <span class="text-xs ml-2"><?php echo $record['used_percent']; ?>%</span>
                                <div class="progress-bar w-16">
                                    <div class="progress-fill" style="width: <?php echo $record['used_percent']; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo number_format($record['files_count']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo number_format($record['folders_count']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo round($record['daily_growth_mb'], 1); ?> MB</td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo timeAgo($record['check_time']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- JavaScript للرسوم البيانية -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// بيانات الرسم البياني
const chartData = <?php echo json_encode($monitoring_history); ?>;
const chartLabels = chartData.map(d => new Date(d.check_time).toLocaleDateString('ar-EG'));
const chartValues = chartData.map(d => d.used_percent);

// تهيئة الرسم البياني
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('storageChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'استخدام التخزين (%)',
                data: chartValues,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#3b82f6',
                tension: 0.3,
                fill: true
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
                    rtl: true,
                    backgroundColor: '#1e293b',
                    titleColor: '#f1f5f9',
                    bodyColor: '#94a3b8'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { 
                        color: '#94a3b8',
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
});

// =============================================
// دوال التغيير
// =============================================
function changeServer() {
    const server = document.getElementById('server-select').value;
    const period = document.getElementById('period-select').value;
    
    let url = '?page=monitoring';
    if (server) url += '&server=' + server;
    if (period) url += '&period=' + period;
    
    window.location.href = url;
}

function changePeriod() {
    changeServer();
}

function refreshPage() {
    location.reload();
}

// =============================================
// دوال التنبيهات
// =============================================
function resolveAlert(id) {
    const formData = new FormData();
    formData.append('action', 'resolve_alert');
    formData.append('alert_id', id);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

// =============================================
// دوال مساعدة
// =============================================
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg text-sm`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}
</script>