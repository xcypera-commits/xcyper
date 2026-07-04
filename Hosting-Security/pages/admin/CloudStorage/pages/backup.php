<?php
// =============================================
// cloud-unit/pages/backup.php
// صفحة النسخ الاحتياطي والمزامنة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'create_backup':
                // إنشاء نسخة احتياطية جديدة
                $backup_code = generateBackupCode($db);
                $backup_name = $_POST['backup_name'] ?? 'نسخة احتياطية ' . date('Y-m-d H:i');
                
                $sql = "INSERT INTO cloud_backups (
                    backup_code, backup_name, project_id, server_id, backup_type,
                    destination, status, started_at, retention_days, is_automated,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 'in_progress', NOW(), ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $backup_code,
                    $backup_name,
                    $_POST['project_id'] ?: null,
                    $_POST['server_id'] ?: null,
                    $_POST['backup_type'] ?? 'full',
                    $_POST['destination'] ?? 'local',
                    $_POST['retention_days'] ?? 30,
                    $_POST['is_automated'] ?? 0,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $backup_id = $db->lastInsertId();
                
                logActivity($db, 'create', 'backup', $backup_id, 'إنشاء نسخة احتياطية جديدة');
                
                $response['success'] = true;
                $response['message'] = 'تم بدء النسخ الاحتياطي بنجاح';
                $response['backup_id'] = $backup_id;
                break;
                
            case 'complete_backup':
                // إكمال نسخة احتياطية
                $sql = "UPDATE cloud_backups SET 
                        status = 'completed', 
                        completed_at = NOW(),
                        size_mb = ?,
                        files_count = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['size_mb'],
                    $_POST['files_count'],
                    $_POST['backup_id']
                ]);
                
                $response['success'] = true;
                $response['message'] = 'تم إكمال النسخة الاحتياطية';
                break;
                
            case 'fail_backup':
                // فشل نسخة احتياطية
                $sql = "UPDATE cloud_backups SET status = 'failed', completed_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['backup_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم تسجيل فشل النسخة الاحتياطية';
                break;
                
            case 'delete_backup':
                // حذف نسخة احتياطية
                $db->prepare("DELETE FROM cloud_backups WHERE id = ?")->execute([$_POST['backup_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم حذف النسخة الاحتياطية';
                break;
                
            case 'restore_backup':
                // استعادة نسخة احتياطية
                $sql = "UPDATE cloud_backups SET restored_at = NOW(), restored_by = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_SESSION['user_id'] ?? 1, $_POST['backup_id']]);
                
                logActivity($db, 'restore', 'backup', $_POST['backup_id'], 'استعادة نسخة احتياطية');
                
                $response['success'] = true;
                $response['message'] = 'تم بدء عملية الاستعادة';
                break;
                
            case 'create_schedule':
                // إنشاء جدول نسخ احتياطي
                $sql = "INSERT INTO cloud_backup_schedules (
                    schedule_name, project_id, server_id, backup_type, frequency,
                    scheduled_time, scheduled_day, scheduled_date, destination,
                    retention_days, is_active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['schedule_name'],
                    $_POST['project_id'] ?: null,
                    $_POST['server_id'] ?: null,
                    $_POST['backup_type'],
                    $_POST['frequency'],
                    $_POST['scheduled_time'] ?? null,
                    $_POST['scheduled_day'] ?? null,
                    $_POST['scheduled_date'] ?? null,
                    $_POST['destination'] ?? 'local',
                    $_POST['retention_days'] ?? 30,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $response['success'] = true;
                $response['message'] = 'تم إنشاء جدول النسخ الاحتياطي';
                break;
                
            case 'toggle_schedule':
                // تفعيل/تعطيل جدول
                $sql = "UPDATE cloud_backup_schedules SET is_active = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['is_active'], $_POST['schedule_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم تحديث حالة الجدول';
                break;
                
            case 'delete_schedule':
                // حذف جدول
                $db->prepare("DELETE FROM cloud_backup_schedules WHERE id = ?")->execute([$_POST['schedule_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم حذف الجدول';
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
    // الفلاتر
    $project_filter = $_GET['project'] ?? '';
    $server_filter = $_GET['server'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    
    // جلب النسخ الاحتياطية
    $sql = "
        SELECT b.*, 
               p.project_name,
               s.server_name,
               u.full_name as creator_name,
               ru.full_name as restored_by_name
        FROM cloud_backups b
        LEFT JOIN cloud_projects p ON b.project_id = p.id
        LEFT JOIN cloud_servers s ON b.server_id = s.id
        LEFT JOIN users u ON b.created_by = u.id
        LEFT JOIN users ru ON b.restored_by = ru.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($project_filter) {
        $sql .= " AND b.project_id = ?";
        $params[] = $project_filter;
    }
    
    if ($server_filter) {
        $sql .= " AND b.server_id = ?";
        $params[] = $server_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND b.status = ?";
        $params[] = $status_filter;
    }
    
    if ($type_filter) {
        $sql .= " AND b.backup_type = ?";
        $params[] = $type_filter;
    }
    
    $sql .= " ORDER BY b.created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $backups = $stmt->fetchAll();
    
    // إحصائيات النسخ الاحتياطي
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN backup_type = 'full' THEN 1 ELSE 0 END) as full_backups,
            SUM(CASE WHEN backup_type = 'incremental' THEN 1 ELSE 0 END) as incremental_backups,
            SUM(CASE WHEN destination = 'local' THEN 1 ELSE 0 END) as local_backups,
            SUM(CASE WHEN destination = 'remote' THEN 1 ELSE 0 END) as remote_backups,
            COALESCE(SUM(size_mb), 0) as total_size_mb
        FROM cloud_backups
    ")->fetch();
    
    // جداول النسخ الاحتياطي
    $schedules = $db->query("
        SELECT s.*, 
               p.project_name,
               srv.server_name
        FROM cloud_backup_schedules s
        LEFT JOIN cloud_projects p ON s.project_id = p.id
        LEFT JOIN cloud_servers srv ON s.server_id = srv.id
        ORDER BY s.is_active DESC, s.created_at DESC
    ")->fetchAll();
    
    // قائمة المشاريع للفلتر
    $projects = $db->query("
        SELECT id, project_name 
        FROM cloud_projects 
        WHERE status = 'active'
        ORDER BY project_name
    ")->fetchAll();
    
    // قائمة الخوادم للفلتر
    $servers = $db->query("
        SELECT id, server_name 
        FROM cloud_servers 
        WHERE status = 'online'
        ORDER BY server_name
    ")->fetchAll();
    
    // آخر 5 نسخ احتياطية
    $recent_backups = $db->query("
        SELECT b.*, p.project_name, s.server_name
        FROM cloud_backups b
        LEFT JOIN cloud_projects p ON b.project_id = p.id
        LEFT JOIN cloud_servers s ON b.server_id = s.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $backups = [];
    $schedules = [];
    $recent_backups = [];
    $projects = [];
    $servers = [];
    $stats = [
        'total' => 0,
        'completed' => 0,
        'failed' => 0,
        'in_progress' => 0,
        'full_backups' => 0,
        'incremental_backups' => 0,
        'local_backups' => 0,
        'remote_backups' => 0,
        'total_size_mb' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateBackupCode($db) {
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cloud_backups WHERE backup_code LIKE ?");
    $stmt->execute(["BAK-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "BAK-{$year}-{$number}";
}

function getBackupStatusBadge($status) {
    $classes = [
        'pending' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'in_progress' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'completed' => 'bg-green-600 bg-opacity-20 text-green-400',
        'failed' => 'bg-red-600 bg-opacity-20 text-red-400',
        'restoring' => 'bg-purple-600 bg-opacity-20 text-purple-400'
    ];
    
    $texts = [
        'pending' => 'معلق',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'failed' => 'فاشل',
        'restoring' => 'جاري الاستعادة'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getBackupTypeBadge($type) {
    $classes = [
        'full' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'incremental' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'differential' => 'bg-green-600 bg-opacity-20 text-green-400',
        'mirror' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'snapshot' => 'bg-cyan-600 bg-opacity-20 text-cyan-400'
    ];
    
    $texts = [
        'full' => 'كامل',
        'incremental' => 'تزايدي',
        'differential' => 'تفاضلي',
        'mirror' => 'مرآة',
        'snapshot' => 'لقطة'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getDestinationBadge($dest) {
    $classes = [
        'local' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'remote' => 'bg-green-600 bg-opacity-20 text-green-400',
        'both' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'cloud' => 'bg-cyan-600 bg-opacity-20 text-cyan-400'
    ];
    
    $texts = [
        'local' => 'محلي',
        'remote' => 'بعيد',
        'both' => 'محلي وبعيد',
        'cloud' => 'سحابي'
    ];
    
    $class = $classes[$dest] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$dest] ?? $dest;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getFrequencyText($frequency) {
    $texts = [
        'hourly' => 'كل ساعة',
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'yearly' => 'سنوي'
    ];
    
    return $texts[$frequency] ?? $frequency;
}

function formatSizeMB($mb) {
    if ($mb < 1024) return round($mb, 1) . ' MB';
    if ($mb < 1024 * 1024) return round($mb / 1024, 1) . ' GB';
    return round($mb / (1024 * 1024), 1) . ' TB';
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
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">💾</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">النسخ الاحتياطي</h1>
                <p class="text-gray-400 mt-1">إدارة النسخ الاحتياطية وجداول المزامنة</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="openCreateBackupModal()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">+</span>
                نسخ احتياطي جديد
            </button>
            <button onclick="openCreateScheduleModal()" class="px-5 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">📅</span>
                جدولة جديدة
            </button>
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-all">
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر -->
    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-slate-700">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="بحث في النسخ الاحتياطية..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
        </div>
        
        <select id="filter-project" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع المشاريع</option>
            <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
            <?php endforeach; ?>
        </select>
        
        <select id="filter-server" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الخوادم</option>
            <?php foreach ($servers as $server): ?>
            <option value="<?php echo $server['id']; ?>"><?php echo $server['server_name']; ?></option>
            <?php endforeach; ?>
        </select>
        
        <select id="filter-status" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الحالات</option>
            <option value="pending">معلق</option>
            <option value="in_progress">قيد التنفيذ</option>
            <option value="completed">مكتمل</option>
            <option value="failed">فاشل</option>
        </select>
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي النسخ</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">نسخ مكتملة</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['completed']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">قيد التنفيذ</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['in_progress']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">نسخ فاشلة</p>
        <p class="text-2xl font-bold text-red-400"><?php echo $stats['failed']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">الحجم الكلي</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo formatSizeMB($stats['total_size_mb']); ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات إضافية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- توزيع أنواع النسخ -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توزيع أنواع النسخ</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">كامل</span>
                    <span class="text-purple-400"><?php echo $stats['full_backups']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $full_percent = $stats['total'] > 0 ? round(($stats['full_backups'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $full_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">تزايدي</span>
                    <span class="text-blue-400"><?php echo $stats['incremental_backups']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $inc_percent = $stats['total'] > 0 ? round(($stats['incremental_backups'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $inc_percent; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 pt-4 border-t border-slate-700">
            <div class="flex items-center justify-between">
                <span class="text-gray-400">محلي</span>
                <span class="text-blue-400"><?php echo $stats['local_backups']; ?></span>
            </div>
            <div class="flex items-center justify-between mt-2">
                <span class="text-gray-400">بعيد</span>
                <span class="text-green-400"><?php echo $stats['remote_backups']; ?></span>
            </div>
        </div>
    </div>
    
    <!-- آخر النسخ الاحتياطية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">آخر النسخ</h3>
        
        <?php if (empty($recent_backups)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد نسخ احتياطية</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_backups as $backup): ?>
                <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-semibold"><?php echo $backup['backup_name']; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $backup['project_name'] ?? $backup['server_name']; ?></p>
                    </div>
                    <?php echo getBackupStatusBadge($backup['status']); ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- إحصائيات إضافية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">معلومات إضافية</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">نسبة النجاح</span>
                    <span class="text-green-400">
                        <?php 
                        $success_rate = $stats['total'] > 0 
                            ? round(($stats['completed'] / $stats['total']) * 100, 1) 
                            : 0;
                        echo $success_rate; ?>%
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-green-500" style="width: <?php echo $success_rate; ?>%"></div>
                </div>
            </div>
            
            <div class="pt-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400">متوسط حجم النسخة</span>
                    <span class="text-blue-400">
                        <?php 
                        $avg_size = $stats['completed'] > 0 
                            ? round($stats['total_size_mb'] / $stats['completed'], 1) 
                            : 0;
                        echo formatSizeMB($avg_size); 
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- جداول النسخ الاحتياطي -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">📅 جداول النسخ الاحتياطي</h3>
        <button onclick="openCreateScheduleModal()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
            + جدول جديد
        </button>
    </div>
    
    <?php if (empty($schedules)): ?>
        <p class="text-gray-400 text-center py-8">لا توجد جداول نسخ احتياطي</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">التكرار</th>
                        <th class="px-4 py-3">الوقت</th>
                        <th class="px-4 py-3">الاحتفاظ</th>
                        <th class="px-4 py-3">الهدف</th>
                        <th class="px-4 py-3">الجهة</th>
                        <th class="px-4 py-3">اسم الجدول</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button onclick="toggleSchedule(<?php echo $schedule['id']; ?>, <?php echo $schedule['is_active'] ? 0 : 1; ?>)" 
                                        class="text-<?php echo $schedule['is_active'] ? 'yellow' : 'green'; ?>-400 hover:text-<?php echo $schedule['is_active'] ? 'yellow' : 'green'; ?>-300">
                                    <?php echo $schedule['is_active'] ? 'تعطيل' : 'تفعيل'; ?>
                                </button>
                                <button onclick="deleteSchedule(<?php echo $schedule['id']; ?>)" class="text-red-400 hover:text-red-300">
                                    حذف
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $schedule['is_active'] ? 'bg-green-600 bg-opacity-20 text-green-400' : 'bg-gray-600 bg-opacity-20 text-gray-400'; ?>">
                                <?php echo $schedule['is_active'] ? 'نشط' : 'معطل'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3"><?php echo getBackupTypeBadge($schedule['backup_type']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo getFrequencyText($schedule['frequency']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $schedule['scheduled_time'] ?? '-'; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $schedule['retention_days']; ?> يوم</td>
                        <td class="px-4 py-3"><?php echo getDestinationBadge($schedule['destination']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $schedule['project_name'] ?? $schedule['server_name'] ?? 'عام'; ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $schedule['schedule_name']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- قائمة النسخ الاحتياطية -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">📋 سجل النسخ الاحتياطي</h3>
        <span class="text-sm text-gray-400">إجمالي <?php echo count($backups); ?> نسخة</span>
    </div>
    
    <?php if (empty($backups)): ?>
        <div class="text-center py-12">
            <div class="text-5xl text-gray-600 mb-4">💾</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد نسخ احتياطية</h3>
            <p class="text-gray-500">قم بإنشاء أول نسخة احتياطية الآن</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">الوجهة</th>
                        <th class="px-4 py-3">الحجم</th>
                        <th class="px-4 py-3">التاريخ</th>
                        <th class="px-4 py-3">المدة</th>
                        <th class="px-4 py-3">الاستعادة</th>
                        <th class="px-4 py-3">الهدف</th>
                        <th class="px-4 py-3">الاسم</th>
                        <th class="px-4 py-3">الكود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): 
                        $duration = null;
                        if ($backup['started_at'] && $backup['completed_at']) {
                            $start = strtotime($backup['started_at']);
                            $end = strtotime($backup['completed_at']);
                            $duration = $end - $start;
                        }
                    ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <?php if ($backup['status'] == 'completed'): ?>
                                <button onclick="restoreBackup(<?php echo $backup['id']; ?>)" class="text-blue-400 hover:text-blue-300 text-sm" title="استعادة">
                                    استعادة
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($backup['status'] == 'in_progress'): ?>
                                <button onclick="completeBackup(<?php echo $backup['id']; ?>)" class="text-green-400 hover:text-green-300 text-sm" title="إكمال">
                                    إكمال
                                </button>
                                <button onclick="failBackup(<?php echo $backup['id']; ?>)" class="text-red-400 hover:text-red-300 text-sm" title="فشل">
                                    فشل
                                </button>
                                <?php endif; ?>
                                
                                <button onclick="deleteBackup(<?php echo $backup['id']; ?>)" class="text-red-400 hover:text-red-300 text-sm" title="حذف">
                                    حذف
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?php echo getBackupStatusBadge($backup['status']); ?></td>
                        <td class="px-4 py-3"><?php echo getBackupTypeBadge($backup['backup_type']); ?></td>
                        <td class="px-4 py-3"><?php echo getDestinationBadge($backup['destination']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $backup['size_mb'] ? formatSizeMB($backup['size_mb']) : '-'; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo timeAgo($backup['created_at']); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $duration ? floor($duration / 60) . ' د' : '-'; ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($backup['restored_at']): ?>
                            <span class="text-green-400"><?php echo timeAgo($backup['restored_at']); ?></span>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo $backup['project_name'] ?? $backup['server_name'] ?? 'عام'; ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $backup['backup_name']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $backup['backup_code']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء نسخة احتياطية جديدة -->
<!-- ============================================= -->
<div id="create-backup-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateBackupModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">إنشاء نسخة احتياطية جديدة</h3>
        </div>
        
        <form id="create-backup-form" onsubmit="handleCreateBackup(event)">
            <input type="hidden" name="action" value="create_backup">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم النسخة</label>
                    <input type="text" name="backup_name" value="نسخة احتياطية <?php echo date('Y-m-d H:i'); ?>" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                        <select name="project_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- بدون مشروع --</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الخادم</label>
                        <select name="server_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- بدون خادم --</option>
                            <?php foreach ($servers as $server): ?>
                            <option value="<?php echo $server['id']; ?>"><?php echo $server['server_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نوع النسخة</label>
                        <select name="backup_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="full">كامل</option>
                            <option value="incremental">تزايدي</option>
                            <option value="differential">تفاضلي</option>
                            <option value="snapshot">لقطة</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الوجهة</label>
                        <select name="destination" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="local">محلي</option>
                            <option value="remote">بعيد</option>
                            <option value="both">محلي وبعيد</option>
                            <option value="cloud">سحابي</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الاحتفاظ بالنسخة (أيام)</label>
                    <input type="number" name="retention_days" value="30" min="1" max="365"
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateBackupModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    بدء النسخ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء جدول جديد -->
<!-- ============================================= -->
<div id="create-schedule-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateScheduleModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">جدولة نسخ احتياطي جديد</h3>
        </div>
        
        <form id="create-schedule-form" onsubmit="handleCreateSchedule(event)">
            <input type="hidden" name="action" value="create_schedule">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم الجدول</label>
                    <input type="text" name="schedule_name" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="مثال: نسخ يومي لقاعدة البيانات">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                        <select name="project_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- بدون مشروع --</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الخادم</label>
                        <select name="server_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- بدون خادم --</option>
                            <?php foreach ($servers as $server): ?>
                            <option value="<?php echo $server['id']; ?>"><?php echo $server['server_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نوع النسخة</label>
                        <select name="backup_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="full">كامل</option>
                            <option value="incremental">تزايدي</option>
                            <option value="differential">تفاضلي</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">التكرار</label>
                        <select name="frequency" id="frequency" onchange="updateScheduleFields()" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="hourly">كل ساعة</option>
                            <option value="daily" selected>يومي</option>
                            <option value="weekly">أسبوعي</option>
                            <option value="monthly">شهري</option>
                        </select>
                    </div>
                </div>
                
                <div id="time-field" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الوقت</label>
                        <input type="time" name="scheduled_time" value="02:00" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div id="day-field">
                        <label class="block text-sm font-semibold mb-2 text-right">اليوم</label>
                        <select name="scheduled_day" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="1">الأحد</option>
                            <option value="2">الإثنين</option>
                            <option value="3">الثلاثاء</option>
                            <option value="4">الأربعاء</option>
                            <option value="5">الخميس</option>
                            <option value="6">الجمعة</option>
                            <option value="7">السبت</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الوجهة</label>
                        <select name="destination" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="local">محلي</option>
                            <option value="remote">بعيد</option>
                            <option value="both">محلي وبعيد</option>
                            <option value="cloud">سحابي</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الاحتفاظ (أيام)</label>
                        <input type="number" name="retention_days" value="30" min="1" max="365"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateScheduleModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow">
                    إنشاء الجدول
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
// تحديث حقول الجدول حسب التكرار
// =============================================
function updateScheduleFields() {
    const frequency = document.getElementById('frequency').value;
    const dayField = document.getElementById('day-field');
    
    if (frequency === 'weekly') {
        dayField.style.display = 'block';
    } else if (frequency === 'monthly') {
        dayField.innerHTML = `
            <label class="block text-sm font-semibold mb-2 text-right">اليوم</label>
            <input type="number" name="scheduled_date" min="1" max="31" value="1" 
                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
        `;
    } else {
        dayField.style.display = 'none';
    }
}

// =============================================
// دوال النسخ الاحتياطي
// =============================================
function openCreateBackupModal() {
    document.getElementById('create-backup-modal').classList.remove('hidden');
}

function closeCreateBackupModal() {
    document.getElementById('create-backup-modal').classList.add('hidden');
    document.getElementById('create-backup-form').reset();
}

function handleCreateBackup(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-backup-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeCreateBackupModal();
        
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

function completeBackup(id) {
    const size = prompt('أدخل حجم النسخة (MB):', '100');
    if (!size) return;
    
    const files = prompt('أدخل عدد الملفات:', '0');
    if (!files) return;
    
    const formData = new FormData();
    formData.append('action', 'complete_backup');
    formData.append('backup_id', id);
    formData.append('size_mb', size);
    formData.append('files_count', files);
    
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

function failBackup(id) {
    if (!confirm('تأكيد فشل النسخة الاحتياطية؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'fail_backup');
    formData.append('backup_id', id);
    
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
            showNotification(data.message, 'warning');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function restoreBackup(id) {
    if (!confirm('بدء عملية استعادة النسخة الاحتياطية؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'restore_backup');
    formData.append('backup_id', id);
    
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

function deleteBackup(id) {
    if (!confirm('⚠️ هل أنت متأكد من حذف هذه النسخة؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_backup');
    formData.append('backup_id', id);
    
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
// دوال الجداول
// =============================================
function openCreateScheduleModal() {
    document.getElementById('create-schedule-modal').classList.remove('hidden');
    updateScheduleFields();
}

function closeCreateScheduleModal() {
    document.getElementById('create-schedule-modal').classList.add('hidden');
    document.getElementById('create-schedule-form').reset();
}

function handleCreateSchedule(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-schedule-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeCreateScheduleModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    });
}

function toggleSchedule(id, active) {
    const formData = new FormData();
    formData.append('action', 'toggle_schedule');
    formData.append('schedule_id', id);
    formData.append('is_active', active);
    
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

function deleteSchedule(id) {
    if (!confirm('⚠️ هل أنت متأكد من حذف هذا الجدول؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('schedule_id', id);
    
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
// دوال الفلاتر
// =============================================
function applyFilters() {
    const project = document.getElementById('filter-project').value;
    const server = document.getElementById('filter-server').value;
    const status = document.getElementById('filter-status').value;
    
    let url = '?page=backup';
    if (project) url += '&project=' + project;
    if (server) url += '&server=' + server;
    if (status) url += '&status=' + status;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=backup';
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
        closeCreateBackupModal();
        closeCreateScheduleModal();
    }
});
</script>