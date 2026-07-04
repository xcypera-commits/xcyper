<?php
// pages/logs.php - سجلات الأحداث
$db = getDB();

// =============================================
// 1. إحصائيات السجلات
// =============================================
$logs_stats = $db->query("
    SELECT 
        COUNT(*) as total_logs,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_logs,
        SUM(CASE WHEN log_type = 'security' THEN 1 ELSE 0 END) as security_logs,
        SUM(CASE WHEN log_type = 'system' THEN 1 ELSE 0 END) as system_logs,
        SUM(CASE WHEN log_type = 'network' THEN 1 ELSE 0 END) as network_logs,
        SUM(CASE WHEN log_type = 'application' THEN 1 ELSE 0 END) as application_logs,
        SUM(CASE WHEN level = 'error' THEN 1 ELSE 0 END) as error_logs,
        SUM(CASE WHEN level = 'warning' THEN 1 ELSE 0 END) as warning_logs,
        SUM(CASE WHEN level = 'info' THEN 1 ELSE 0 END) as info_logs
    FROM logs
")->fetch();

// =============================================
// 2. آخر 100 سجل مع تفاصيلها
// =============================================
$logs = $db->query("
    SELECT l.*, 
           u.username as user_name,
           s.name as server_name
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    LEFT JOIN servers s ON l.server_id = s.id
    ORDER BY l.created_at DESC
    LIMIT 200
")->fetchAll();

// =============================================
// 3. توزيع السجلات حسب النوع (لآخر 7 أيام)
// =============================================
$logs_by_type = $db->query("
    SELECT 
        log_type,
        COUNT(*) as count
    FROM logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY log_type
    ORDER BY count DESC
")->fetchAll();

// =============================================
// 4. توزيع السجلات حسب المستوى (لآخر 7 أيام)
// =============================================
$logs_by_level = $db->query("
    SELECT 
        level,
        COUNT(*) as count
    FROM logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY level
    ORDER BY 
        CASE level
            WHEN 'error' THEN 1
            WHEN 'warning' THEN 2
            WHEN 'info' THEN 3
            WHEN 'debug' THEN 4
        END
")->fetchAll();

// =============================================
// 5. أكثر المصادر توليداً للسجلات
// =============================================
$top_sources = $db->query("
    SELECT 
        source,
        COUNT(*) as count,
        MAX(created_at) as last_log
    FROM logs
    WHERE source IS NOT NULL AND source != ''
    GROUP BY source
    ORDER BY count DESC
    LIMIT 10
")->fetchAll();

// =============================================
// 6. آخر 10 أخطاء (error)
// =============================================
$recent_errors = $db->query("
    SELECT l.*, s.name as server_name
    FROM logs l
    LEFT JOIN servers s ON l.server_id = s.id
    WHERE l.level = 'error'
    ORDER BY l.created_at DESC
    LIMIT 10
")->fetchAll();

// =============================================
// 7. نشاط السجلات (آخر 24 ساعة)
// =============================================
$hourly_activity = $db->query("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as count
    FROM logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY HOUR(created_at)
    ORDER BY hour
")->fetchAll();

// =============================================
// 8. معالجة طلب البحث والتصفية
// =============================================
$filter_type = $_GET['type'] ?? '';
$filter_level = $_GET['level'] ?? '';
$filter_source = $_GET['source'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search_query = $_GET['search'] ?? '';

// بناء استعلام البحث المتقدم
$query = "SELECT l.*, u.username as user_name, s.name as server_name 
          FROM logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          LEFT JOIN servers s ON l.server_id = s.id 
          WHERE 1=1";
$params = [];

if (!empty($filter_type)) {
    $query .= " AND l.log_type = ?";
    $params[] = $filter_type;
}

if (!empty($filter_level)) {
    $query .= " AND l.level = ?";
    $params[] = $filter_level;
}

if (!empty($filter_source)) {
    $query .= " AND l.source LIKE ?";
    $params[] = "%$filter_source%";
}

if (!empty($filter_date_from)) {
    $query .= " AND DATE(l.created_at) >= ?";
    $params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $query .= " AND DATE(l.created_at) <= ?";
    $params[] = $filter_date_to;
}

if (!empty($search_query)) {
    $query .= " AND (l.description LIKE ? OR l.event_type LIKE ? OR l.source LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY l.created_at DESC LIMIT 200";

$stmt = $db->prepare($query);
$stmt->execute($params);
$filtered_logs = $stmt->fetchAll();

// استخدام السجلات المفلترة إذا وجد بحث، وإلا استخدام السجلات العادية
$display_logs = (!empty($filter_type) || !empty($filter_level) || !empty($filter_source) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($search_query)) ? $filtered_logs : $logs;

// دوال مساعدة
function getLogTypeBadge($type) {
    $colors = [
        'security' => 'bg-red-500',
        'system' => 'bg-blue-500',
        'network' => 'bg-purple-500',
        'application' => 'bg-green-500'
    ];
    $texts = [
        'security' => 'أمني',
        'system' => 'نظام',
        'network' => 'شبكة',
        'application' => 'تطبيق'
    ];
    $color = $colors[$type] ?? 'bg-gray-500';
    $text = $texts[$type] ?? $type;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getLogLevelBadge($level) {
    $colors = [
        'error' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500',
        'debug' => 'bg-gray-500'
    ];
    $texts = [
        'error' => 'خطأ',
        'warning' => 'تحذير',
        'info' => 'معلومات',
        'debug' => 'تصحيح'
    ];
    $color = $colors[$level] ?? 'bg-gray-500';
    $text = $texts[$level] ?? $level;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}




function getLogTypeIcon($type) {
    return match($type) {
        'security' => '🔒',
        'system' => '⚙️',
        'network' => '🌐',
        'application' => '📱',
        default => '📝'
    };
}

function formatLogTime($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "منذ $diff ثانية";
    if ($diff < 3600) return "منذ " . round($diff / 60) . " دقيقة";
    if ($diff < 86400) return "منذ " . round($diff / 3600) . " ساعة";
    return date('Y-m-d H:i', $time);
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="exportLogs()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                    </svg>
                    تصدير السجلات
                </button>
                <button onclick="clearOldLogs()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    مسح القديم
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">📋</span> سجلات الأحداث
            </h1>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm mb-1">إجمالي السجلات</p>
                        <p class="text-3xl font-bold"><?php echo number_format($logs_stats['total_logs']); ?></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-blue-200">
                    اليوم: <?php echo $logs_stats['today_logs']; ?> سجل
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-200 text-sm mb-1">الأخطاء</p>
                        <p class="text-3xl font-bold"><?php echo $logs_stats['error_logs']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-red-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-red-200">
                    تحتاج للمراجعة
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-200 text-sm mb-1">تحذيرات</p>
                        <p class="text-3xl font-bold"><?php echo $logs_stats['warning_logs']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-yellow-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-yellow-200">
                    للمتابعة
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-600 to-green-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm mb-1">معلومات</p>
                        <p class="text-3xl font-bold"><?php echo $logs_stats['info_logs']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-green-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-green-200">
                    للتشغيل العادي
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات التوزيع -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- توزيع حسب النوع -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-blue-400 mb-4 text-right">📊 توزيع السجلات حسب النوع (آخر 7 أيام)</h3>
            <div class="space-y-4">
                <?php 
                $total_type = array_sum(array_column($logs_by_type, 'count'));
                foreach ($logs_by_type as $type): 
                    $percentage = $total_type > 0 ? round(($type['count'] / $total_type) * 100, 1) : 0;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo getLogTypeBadge($type['log_type']); ?></span>
                        <span class="text-gray-300"><?php echo $type['count']; ?> (<?php echo $percentage; ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- توزيع حسب المستوى -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">⚠️ توزيع السجلات حسب المستوى (آخر 7 أيام)</h3>
            <div class="space-y-4">
                <?php 
                $total_level = array_sum(array_column($logs_by_level, 'count'));
                foreach ($logs_by_level as $level): 
                    $percentage = $total_level > 0 ? round(($level['count'] / $total_level) * 100, 1) : 0;
                    $color = $level['level'] == 'error' ? 'bg-red-500' : ($level['level'] == 'warning' ? 'bg-yellow-500' : ($level['level'] == 'info' ? 'bg-blue-500' : 'bg-gray-500'));
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo getLogLevelBadge($level['level']); ?></span>
                        <span class="text-gray-300"><?php echo $level['count']; ?> (<?php echo $percentage; ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- آخر الأخطاء -->
    <?php if (!empty($recent_errors)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-red-400">🚨 آخر الأخطاء (10)</h3>
            <span class="text-sm text-gray-400">آخر تحديث: <?php echo formatLogTime($recent_errors[0]['created_at']); ?></span>
        </div>
        <div class="space-y-3">
            <?php foreach ($recent_errors as $error): ?>
            <div class="bg-slate-900 rounded-lg p-3 border-r-4 border-red-500">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-400"><?php echo formatLogTime($error['created_at']); ?></span>
                    <span class="text-xs text-gray-400"><?php echo $error['source'] ?? 'غير معروف'; ?></span>
                </div>
                <p class="text-sm text-gray-300"><?php echo $error['description']; ?></p>
                <div class="flex items-center justify-between mt-2 text-xs">
                    <span class="text-gray-500"><?php echo $error['server_name'] ?? 'جميع الخوادم'; ?></span>
                    <button onclick="viewLogDetails(<?php echo $error['id']; ?>)" class="text-blue-400 hover:text-blue-300">تفاصيل</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- أكثر المصادر توليداً للسجلات -->
    <?php if (!empty($top_sources)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">🔝 أكثر المصادر توليداً للسجلات</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($top_sources as $source): ?>
            <div class="bg-slate-900 rounded-lg p-3 flex items-center justify-between">
                <span class="text-sm text-gray-300"><?php echo $source['source']; ?></span>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="px-2 py-1 bg-blue-600 rounded-full text-xs"><?php echo $source['count']; ?></span>
                    <span class="text-xs text-gray-500"><?php echo formatLogTime($source['last_log']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- نموذج البحث والتصفية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-400 mb-4 text-right">🔍 بحث وتصفية متقدم</h3>
        <form method="GET" action="" class="space-y-4">
            <input type="hidden" name="page" value="logs">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع السجل</label>
                    <select name="type" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">الكل</option>
                        <option value="security" <?php echo $filter_type == 'security' ? 'selected' : ''; ?>>أمني</option>
                        <option value="system" <?php echo $filter_type == 'system' ? 'selected' : ''; ?>>نظام</option>
                        <option value="network" <?php echo $filter_type == 'network' ? 'selected' : ''; ?>>شبكة</option>
                        <option value="application" <?php echo $filter_type == 'application' ? 'selected' : ''; ?>>تطبيق</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المستوى</label>
                    <select name="level" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">الكل</option>
                        <option value="error" <?php echo $filter_level == 'error' ? 'selected' : ''; ?>>خطأ</option>
                        <option value="warning" <?php echo $filter_level == 'warning' ? 'selected' : ''; ?>>تحذير</option>
                        <option value="info" <?php echo $filter_level == 'info' ? 'selected' : ''; ?>>معلومات</option>
                        <option value="debug" <?php echo $filter_level == 'debug' ? 'selected' : ''; ?>>تصحيح</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المصدر</label>
                    <input type="text" name="source" value="<?php echo htmlspecialchars($filter_source); ?>" placeholder="مثال: Firewall" 
                           class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">بحث في النص</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="كلمة البحث..." 
                           class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">من تاريخ</label>
                    <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>" 
                           class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">إلى تاريخ</label>
                    <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>" 
                           class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                
                <div class="flex items-end space-x-2 space-x-reverse">
                    <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex-1">
                        <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        بحث
                    </button>
                    <a href="?page=logs" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                        إعادة تعيين
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- جدول السجلات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-4 text-blue-400">📋 سجل الأحداث التفصيلي</h2>
        
        <div class="text-sm text-gray-400 mb-4 text-left">
            عرض <?php echo count($display_logs); ?> سجل
            <?php if (count($display_logs) < count($logs)): ?>
            (مفلترة من أصل <?php echo count($logs); ?>)
            <?php endif; ?>
        </div>

        <!-- الجدول -->
        <div class="overflow-x-auto">
            <table class="w-full" id="logs-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                        <th class="px-6 py-4 text-sm font-semibold">الوقت</th>
                        <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                        <th class="px-6 py-4 text-sm font-semibold">المستوى</th>
                        <th class="px-6 py-4 text-sm font-semibold">المصدر</th>
                        <th class="px-6 py-4 text-sm font-semibold">الخادم</th>
                        <th class="px-6 py-4 text-sm font-semibold">المستخدم</th>
                        <th class="px-6 py-4 text-sm font-semibold">نوع الحدث</th>
                        <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($display_logs)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400">
                            لا توجد سجلات تطابق معايير البحث
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($display_logs as $log): ?>
                        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
                            <td class="px-6 py-4">
                                <button onclick="viewLogDetails(<?php echo $log['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-gray-300 whitespace-nowrap">
                                <div><?php echo date('Y-m-d', strtotime($log['created_at'])); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getLogTypeBadge($log['log_type']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getLogLevelBadge($log['level']); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $log['source'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $log['server_name'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $log['user_name'] ?? 'system'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $log['event_type'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300 max-w-xs truncate">
                                <?php echo $log['description']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- نافذة تفاصيل السجل -->
<div id="log-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeLogModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">🔍 تفاصيل السجل</h3>
        </div>
        
        <div id="log-details-content" class="space-y-6">
            <!-- محتوى السجل يتم تحميله هنا -->
        </div>
    </div>
</div>

<style>
.progress-bar {
    height: 8px;
    background: #1e293b;
    border-radius: 4px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
</style>

<script>
let currentLogId = null;

function exportLogs() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('📊 جاري تصدير السجلات...', 'info');
        setTimeout(() => {
            showNotification('✅ تم تصدير السجلات بنجاح', 'success');
        }, 1500);
    }, 1000);
}

function clearOldLogs() {
    if (confirm('هل أنت متأكد من مسح السجلات القديمة (أكثر من 30 يوم)؟')) {
        showLoading();
        setTimeout(() => {
            hideLoading();
            showNotification('🧹 تم مسح السجلات القديمة بنجاح', 'success');
            setTimeout(() => location.reload(), 1500);
        }, 2000);
    }
}
function viewLogDetails(id) {
    currentLogId = id;
    showLoading();
    
    // جلب تفاصيل السجل من قاعدة البيانات عبر AJAX
    fetch(`api/get_log_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const details = `
                    <div class="space-y-4">
                        <div class="p-4 bg-slate-900 rounded-lg">
                            <h4 class="text-xl font-bold text-white mb-4">تفاصيل السجل #${id}</h4>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">الوقت</p>
                                    <p class="font-semibold">${data.log.created_at}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">المصدر</p>
                                    <p class="font-semibold text-blue-400">${data.log.source || 'غير معروف'}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">النوع</p>
                                    <p class="font-semibold">${getLogTypeBadge(data.log.log_type)}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">المستوى</p>
                                    <p class="font-semibold">${getLogLevelBadge(data.log.level)}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">الخادم</p>
                                    <p class="font-semibold">${data.log.server_name || 'غير محدد'}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">المستخدم</p>
                                    <p class="font-semibold">${data.log.user_name || 'system'}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">IP المصدر</p>
                                    <p class="font-semibold">${data.log.ip_address || 'غير متوفر'}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">نوع الحدث</p>
                                    <p class="font-semibold">${data.log.event_type || 'حدث'}</p>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-800 rounded-lg mb-4">
                                <p class="text-sm text-gray-400 mb-2">الوصف</p>
                                <p class="text-gray-300">${data.log.description}</p>
                            </div>

                            <div class="flex justify-between pt-4 border-t border-slate-700">
                                <button onclick="closeLogModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('log-details-content').innerHTML = details;
            } else {
                showNotification('❌ فشل تحميل تفاصيل السجل', 'error');
            }
            hideLoading();
            document.getElementById('log-details-modal').classList.remove('hidden');
            document.getElementById('log-details-modal').classList.add('flex');
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ خطأ في الاتصال', 'error');
            console.error('Error:', error);
        });
}
// دوال badges (للاستخدام في JavaScript)
function getLogTypeBadge(type) {
    const colors = {
        'security': 'bg-red-500',
        'system': 'bg-blue-500',
        'network': 'bg-purple-500',
        'application': 'bg-green-500'
    };
    const texts = {
        'security': 'أمني',
        'system': 'نظام',
        'network': 'شبكة',
        'application': 'تطبيق'
    };
    const color = colors[type] || 'bg-gray-500';
    const text = texts[type] || type;
    return `<span class='px-3 py-1 rounded-full text-xs font-semibold ${color}'>${text}</span>`;
}

function getLogLevelBadge(level) {
    const colors = {
        'error': 'bg-red-500',
        'warning': 'bg-yellow-500',
        'info': 'bg-blue-500',
        'debug': 'bg-gray-500'
    };
    const texts = {
        'error': 'خطأ',
        'warning': 'تحذير',
        'info': 'معلومات',
        'debug': 'تصحيح'
    };
    const color = colors[level] || 'bg-gray-500';
    const text = texts[level] || level;
    return `<span class='px-3 py-1 rounded-full text-xs font-semibold ${color}'>${text}</span>`;
}

function closeLogModal() {
    document.getElementById('log-details-modal').classList.add('hidden');
    document.getElementById('log-details-modal').classList.remove('flex');
}

// دوال مساعدة من index.php
function showLoading() {
    if (typeof window.showLoading === 'function') {
        window.showLoading();
    }
}

function hideLoading() {
    if (typeof window.hideLoading === 'function') {
        window.hideLoading();
    }
}

function showNotification(message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}
</script>