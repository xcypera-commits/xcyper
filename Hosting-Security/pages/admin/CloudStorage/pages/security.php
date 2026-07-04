<?php
// =============================================
// cloud-unit/pages/security.php
// صفحة التحديثات الأمنية
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// تعريف الدوال المساعدة المفقودة
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

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'scan_updates':
                // فحص التحديثات (محاكاة)
                // في الواقع، هنا يتم الاتصال بالخوادم لفحص التحديثات المتاحة
                
                $response['success'] = true;
                $response['message'] = 'تم فحص التحديثات بنجاح';
                break;
                
            case 'apply_update':
                // تطبيق تحديث
                $sql = "UPDATE cloud_security_updates SET 
                        status = 'applied', 
                        applied_at = NOW(),
                        applied_by = ? 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_SESSION['user_id'] ?? 1, $_POST['update_id']]);
                
                logActivity($db, 'apply', 'security', $_POST['update_id'], 'تطبيق تحديث أمني');
                
                $response['success'] = true;
                $response['message'] = 'تم تطبيق التحديث بنجاح';
                break;
                
            case 'schedule_update':
                // جدولة تحديث
                $sql = "UPDATE cloud_security_updates SET 
                        status = 'scheduled', 
                        scheduled_for = ? 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['scheduled_for'], $_POST['update_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم جدولة التحديث';
                break;
                
            case 'skip_update':
                // تخطي تحديث
                $sql = "UPDATE cloud_security_updates SET status = 'skipped' WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['update_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم تخطي التحديث';
                break;
                
            case 'add_update':
                // إضافة تحديث جديد
                $update_code = generateUpdateCode($db);
                
                $sql = "INSERT INTO cloud_security_updates (
                    update_code, update_name, package_name, current_version,
                    available_version, severity, description, cve_id,
                    server_id, project_id, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $update_code,
                    $_POST['update_name'],
                    $_POST['package_name'],
                    $_POST['current_version'],
                    $_POST['available_version'],
                    $_POST['severity'],
                    $_POST['description'],
                    $_POST['cve_id'] ?? null,
                    $_POST['server_id'] ?: null,
                    $_POST['project_id'] ?: null
                ]);
                
                $response['success'] = true;
                $response['message'] = 'تم إضافة التحديث';
                break;
                
            case 'delete_update':
                // حذف تحديث
                $db->prepare("DELETE FROM cloud_security_updates WHERE id = ?")->execute([$_POST['update_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم حذف التحديث';
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
// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // للتصحيح - نتأكد من وجود بيانات
    $check_total = $db->query("SELECT COUNT(*) as total FROM cloud_security_updates")->fetch();
    
    // عرض رسالة تصحيح (تظهر في كود HTML)
    echo "<!-- DEBUG: إجمالي التحديثات في قاعدة البيانات = " . $check_total['total'] . " -->";
    
    // إذا مافيش بيانات، نعرض رسالة للمستخدم
    if ($check_total['total'] == 0) {
        echo '<div class="bg-yellow-600 p-4 rounded-lg mb-4 text-center">';
        echo '⚠️ لا توجد تحديثات أمنية في قاعدة البيانات. أضف بعض التحديثات لعرضها.';
        echo '</div>';
    }
    
    // الفلاتر
    $severity_filter = $_GET['severity'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $server_filter = $_GET['server'] ?? '';
    
    // جلب التحديثات الأمنية - استعلام معدل مع COALESCE
    $sql = "
        SELECT u.*, 
               COALESCE(s.server_name, '-') as server_name,
               COALESCE(p.project_name, '-') as project_name
        FROM cloud_security_updates u
        LEFT JOIN cloud_servers s ON u.server_id = s.id
        LEFT JOIN cloud_projects p ON u.project_id = p.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($severity_filter) {
        $sql .= " AND u.severity = ?";
        $params[] = $severity_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND u.status = ?";
        $params[] = $status_filter;
    }
    
    if ($server_filter) {
        $sql .= " AND u.server_id = ?";
        $params[] = $server_filter;
    }
    
    $sql .= " ORDER BY 
                CASE u.severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END,
                u.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $updates = $stmt->fetchAll();
    
    // للتصحيح - عدد النتائج بعد الفلتر
    echo "<!-- DEBUG: عدد التحديثات بعد الفلتر = " . count($updates) . " -->";
    
    // إحصائيات التحديثات - استعلام معدل
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN severity = 'critical' AND status = 'pending' THEN 1 ELSE 0 END), 0) as critical_pending,
            COALESCE(SUM(CASE WHEN severity = 'high' AND status = 'pending' THEN 1 ELSE 0 END), 0) as high_pending,
            COALESCE(SUM(CASE WHEN severity = 'medium' AND status = 'pending' THEN 1 ELSE 0 END), 0) as medium_pending,
            COALESCE(SUM(CASE WHEN severity = 'low' AND status = 'pending' THEN 1 ELSE 0 END), 0) as low_pending,
            COALESCE(SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END), 0) as applied,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END), 0) as scheduled,
            COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed
        FROM cloud_security_updates
    ";
    
    $stmt_stats = $db->prepare($stats_sql);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch();
    
    // إحصائيات حسب الشهر - استعلام معدل
    $monthly_sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END), 0) as critical
        FROM cloud_security_updates
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ";
    
    $stmt_monthly = $db->prepare($monthly_sql);
    $stmt_monthly->execute();
    $monthly_stats = $stmt_monthly->fetchAll();
    
    // قائمة الخوادم للفلتر
    $servers_sql = "SELECT id, server_name FROM cloud_servers ORDER BY server_name";
    $stmt_servers = $db->prepare($servers_sql);
    $stmt_servers->execute();
    $servers = $stmt_servers->fetchAll();
    
    // قائمة المشاريع
    $projects_sql = "SELECT id, project_name FROM cloud_projects WHERE status = 'active' ORDER BY project_name";
    $stmt_projects = $db->prepare($projects_sql);
    $stmt_projects->execute();
    $projects = $stmt_projects->fetchAll();
    
    // آخر التحديثات المطبقة
    $recent_applied_sql = "
        SELECT u.*, COALESCE(s.server_name, '-') as server_name
        FROM cloud_security_updates u
        LEFT JOIN cloud_servers s ON u.server_id = s.id
        WHERE u.status = 'applied'
        ORDER BY u.applied_at DESC
        LIMIT 5
    ";
    $stmt_recent = $db->prepare($recent_applied_sql);
    $stmt_recent->execute();
    $recent_applied = $stmt_recent->fetchAll();
    
    // التحديثات المجدولة
    $scheduled_sql = "
        SELECT u.*, COALESCE(s.server_name, '-') as server_name
        FROM cloud_security_updates u
        LEFT JOIN cloud_servers s ON u.server_id = s.id
        WHERE u.status = 'scheduled'
        ORDER BY u.scheduled_for ASC
        LIMIT 5
    ";
    $stmt_scheduled = $db->prepare($scheduled_sql);
    $stmt_scheduled->execute();
    $scheduled_updates = $stmt_scheduled->fetchAll();
    
} catch (Exception $e) {
    $updates = [];
    $recent_applied = [];
    $scheduled_updates = [];
    $monthly_stats = [];
    $servers = [];
    $projects = [];
    $stats = [
        'total' => 0,
        'critical_pending' => 0,
        'high_pending' => 0,
        'medium_pending' => 0,
        'low_pending' => 0,
        'applied' => 0,
        'pending' => 0,
        'scheduled' => 0,
        'failed' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateUpdateCode($db) {
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cloud_security_updates WHERE update_code LIKE ?");
    $stmt->execute(["SEC-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "SEC-{$year}-{$number}";
}

function getSeverityBadge($severity) {
    $classes = [
        'critical' => 'bg-red-600 bg-opacity-20 text-red-400',
        'high' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'medium' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'low' => 'bg-blue-600 bg-opacity-20 text-blue-400'
    ];
    
    $texts = [
        'critical' => 'حرج',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض'
    ];
    
    $class = $classes[$severity] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$severity] ?? $severity;
    
    return "<span class='px-2 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getUpdateStatusBadge($status) {
    $classes = [
        'pending' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'applied' => 'bg-green-600 bg-opacity-20 text-green-400',
        'scheduled' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'failed' => 'bg-red-600 bg-opacity-20 text-red-400',
        'skipped' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'pending' => 'معلق',
        'applied' => 'مطبق',
        'scheduled' => 'مجدول',
        'failed' => 'فشل',
        'skipped' => 'تم تخطيه'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-2 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function logActivity($db, $type, $target, $target_id, $description) {
    $sql = "INSERT INTO cloud_activity_log (user_id, activity_type, target_type, target_id, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id'] ?? 1, $type, $target, $target_id, $description]);
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
<!-- رأس الصفحة مع الإحصائيات -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-2xl p-8 mb-8 cyber-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-red-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">🔒</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">التحديثات الأمنية</h1>
                <p class="text-gray-400 mt-1">إدارة وتطبيق التحديثات الأمنية للخوادم والمشاريع</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="scanForUpdates()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">🔍</span>
                فحص التحديثات
            </button>
            <button onclick="openAddUpdateModal()" class="px-5 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">+</span>
                إضافة تحديث
            </button>
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-all">
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر -->
    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-slate-700">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="بحث في التحديثات..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-red-500 text-right">
        </div>
        
        <select id="filter-severity" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع المستويات</option>
            <option value="critical">حرج</option>
            <option value="high">عالي</option>
            <option value="medium">متوسط</option>
            <option value="low">منخفض</option>
        </select>
        
        <select id="filter-status" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الحالات</option>
            <option value="pending">معلق</option>
            <option value="applied">مطبق</option>
            <option value="scheduled">مجدول</option>
            <option value="failed">فشل</option>
        </select>
        
        <select id="filter-server" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الخوادم</option>
            <?php foreach ($servers as $server): ?>
            <option value="<?php echo $server['id']; ?>"><?php echo $server['server_name']; ?></option>
            <?php endforeach; ?>
        </select>
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي التحديثات</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">تحديثات حرجة</p>
        <p class="text-2xl font-bold text-red-400"><?php echo $stats['critical_pending']; ?></p>
        <?php if ($stats['critical_pending'] > 0): ?>
        <div class="mt-1 text-xs text-red-400">بحاجة لتطبيق فوري</div>
        <?php endif; ?>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">عالية الخطورة</p>
        <p class="text-2xl font-bold text-orange-400"><?php echo $stats['high_pending']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">تحديثات مطبقة</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['applied']; ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات إضافية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- توزيع مستويات الخطورة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">مستويات الخطورة</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">حرج</span>
                    <span class="text-red-400"><?php echo $stats['critical_pending']; ?> معلق</span>
                </div>
                <div class="progress-bar">
                    <?php $critical_percent = $stats['total'] > 0 ? round(($stats['critical_pending'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-red-500" style="width: <?php echo $critical_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">عالي</span>
                    <span class="text-orange-400"><?php echo $stats['high_pending']; ?> معلق</span>
                </div>
                <div class="progress-bar">
                    <?php $high_percent = $stats['total'] > 0 ? round(($stats['high_pending'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-orange-500" style="width: <?php echo $high_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">متوسط</span>
                    <span class="text-yellow-400"><?php echo $stats['medium_pending']; ?> معلق</span>
                </div>
                <div class="progress-bar">
                    <?php $medium_percent = $stats['total'] > 0 ? round(($stats['medium_pending'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-yellow-500" style="width: <?php echo $medium_percent; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- آخر التحديثات المطبقة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">آخر التحديثات المطبقة</h3>
        
        <?php if (empty($recent_applied)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد تحديثات مطبقة</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_applied as $update): ?>
                <div class="p-3 bg-slate-700 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold"><?php echo $update['update_name']; ?></span>
                        <?php echo getSeverityBadge($update['severity']); ?>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400"><?php echo $update['server_name'] ?? 'عام'; ?></span>
                        <span class="text-gray-400"><?php echo timeAgo($update['applied_at']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- التحديثات المجدولة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">التحديثات المجدولة</h3>
        
        <?php if (empty($scheduled_updates)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد تحديثات مجدولة</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($scheduled_updates as $update): ?>
                <div class="p-3 bg-slate-700 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold"><?php echo $update['update_name']; ?></span>
                        <?php echo getSeverityBadge($update['severity']); ?>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400"><?php echo $update['server_name'] ?? 'عام'; ?></span>
                        <span class="text-gray-400"><?php echo date('Y-m-d H:i', strtotime($update['scheduled_for'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة التحديثات الأمنية -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">📋 التحديثات الأمنية</h3>
        <span class="text-sm text-gray-400">إجمالي <?php echo count($updates); ?> تحديث</span>
    </div>
    
    <?php if (empty($updates)): ?>
        <div class="text-center py-12">
            <div class="text-5xl text-gray-600 mb-4">🔒</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد تحديثات أمنية</h3>
            <p class="text-gray-500">جميع الأنظمة محدثة وآمنة</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">الخطورة</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">الإصدارات</th>
                        <th class="px-4 py-3">CVE</th>
                        <th class="px-4 py-3">الهدف</th>
                        <th class="px-4 py-3">التاريخ</th>
                        <th class="px-4 py-3">اسم الحزمة</th>
                        <th class="px-4 py-3">اسم التحديث</th>
                        <th class="px-4 py-3">الكود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($updates as $update): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <?php if ($update['status'] == 'pending'): ?>
                                <button onclick="applyUpdate(<?php echo $update['id']; ?>)" class="text-green-400 hover:text-green-300 text-sm" title="تطبيق">
                                    تطبيق
                                </button>
                                <button onclick="openScheduleModal(<?php echo $update['id']; ?>)" class="text-blue-400 hover:text-blue-300 text-sm" title="جدولة">
                                    جدولة
                                </button>
                                <button onclick="skipUpdate(<?php echo $update['id']; ?>)" class="text-gray-400 hover:text-gray-300 text-sm" title="تخطي">
                                    تخطي
                                </button>
                                <?php endif; ?>
                                <button onclick="deleteUpdate(<?php echo $update['id']; ?>)" class="text-red-400 hover:text-red-300 text-sm" title="حذف">
                                    حذف
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?php echo getSeverityBadge($update['severity']); ?></td>
                        <td class="px-4 py-3"><?php echo getUpdateStatusBadge($update['status']); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <span class="text-red-400"><?php echo $update['current_version']; ?></span>
                            <span class="text-gray-600 mx-1">→</span>
                            <span class="text-green-400"><?php echo $update['available_version']; ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $update['cve_id'] ?? '-'; ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo $update['server_name'] ?? $update['project_name'] ?? 'عام'; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo timeAgo($update['created_at']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $update['package_name']; ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $update['update_name']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $update['update_code']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إضافة تحديث جديد -->
<!-- ============================================= -->
<div id="add-update-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddUpdateModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">إضافة تحديث أمني</h3>
        </div>
        
        <form id="add-update-form" onsubmit="handleAddUpdate(event)">
            <input type="hidden" name="action" value="add_update">
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">اسم التحديث</label>
                        <input type="text" name="update_name" required 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-red-500 text-right">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">اسم الحزمة</label>
                        <input type="text" name="package_name" required 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الإصدار الحالي</label>
                        <input type="text" name="current_version" required 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الإصدار الجديد</label>
                        <input type="text" name="available_version" required 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">مستوى الخطورة</label>
                        <select name="severity" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="critical">حرج</option>
                            <option value="high">عالي</option>
                            <option value="medium">متوسط</option>
                            <option value="low">منخفض</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">CVE ID</label>
                        <input type="text" name="cve_id" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"
                               placeholder="CVE-2024-xxxx">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الخادم</label>
                        <select name="server_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- بدون خادم --</option>
                            <?php foreach ($servers as $server): ?>
                            <option value="<?php echo $server['id']; ?>"><?php echo $server['server_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                        <select name="project_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- بدون مشروع --</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                    <textarea name="description" rows="3" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"></textarea>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeAddUpdateModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all cyber-glow">
                    إضافة التحديث
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة جدولة تحديث -->
<!-- ============================================= -->
<div id="schedule-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">جدولة التحديث</h3>
        </div>
        
        <form id="schedule-form" onsubmit="handleSchedule(event)">
            <input type="hidden" name="action" value="schedule_update">
            <input type="hidden" name="update_id" id="schedule-update-id">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">تاريخ ووقت التطبيق</label>
                <input type="datetime-local" name="scheduled_for" required 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-400 mt-2">سيتم تطبيق التحديث في الوقت المحدد</p>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeScheduleModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    جدولة
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
// =============================================
// دوال فحص التحديثات
// =============================================
function scanForUpdates() {
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'scan_updates');
    
    setTimeout(() => {
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        });
    }, 2000); // محاكاة وقت الفحص
}

// =============================================
// دوال إضافة تحديث
// =============================================
function openAddUpdateModal() {
    document.getElementById('add-update-modal').classList.remove('hidden');
}

function closeAddUpdateModal() {
    document.getElementById('add-update-modal').classList.add('hidden');
    document.getElementById('add-update-form').reset();
}

function handleAddUpdate(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('add-update-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeAddUpdateModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ', 'error');
        console.error(error);
    });
}

// =============================================
// دوال تطبيق التحديثات
// =============================================
function applyUpdate(id) {
    if (!confirm('تطبيق هذا التحديث الآن؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'apply_update');
    formData.append('update_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function skipUpdate(id) {
    if (!confirm('تخطي هذا التحديث؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'skip_update');
    formData.append('update_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function deleteUpdate(id) {
    if (!confirm('⚠️ هل أنت متأكد من حذف هذا التحديث؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_update');
    formData.append('update_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

// =============================================
// دوال جدولة التحديثات
// =============================================
function openScheduleModal(id) {
    document.getElementById('schedule-update-id').value = id;
    document.getElementById('schedule-modal').classList.remove('hidden');
}

function closeScheduleModal() {
    document.getElementById('schedule-modal').classList.add('hidden');
    document.getElementById('schedule-form').reset();
}

function handleSchedule(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('schedule-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeScheduleModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    });
}

// =============================================
// دوال الفلاتر
// =============================================
function applyFilters() {
    const severity = document.getElementById('filter-severity').value;
    const status = document.getElementById('filter-status').value;
    const server = document.getElementById('filter-server').value;
    
    let url = '?page=security';
    if (severity) url += '&severity=' + severity;
    if (status) url += '&status=' + status;
    if (server) url += '&server=' + server;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=security';
}

function refreshPage() {
    location.reload();
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

// إغلاق النوافذ بالـ ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddUpdateModal();
        closeScheduleModal();
    }
});
</script>