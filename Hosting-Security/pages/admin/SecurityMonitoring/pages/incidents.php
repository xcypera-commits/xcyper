<?php
// pages/incidents.php - إدارة الحوادث الأمنية
$db = getDB();

// =============================================
// 1. إحصائيات الحوادث
// =============================================
$incidents_stats = $db->query("
    SELECT 
        COUNT(*) as total_incidents,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_incidents,
        SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_incidents,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_incidents,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_incidents,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_incidents,
        SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_incidents,
        SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_incidents,
        ROUND(AVG(CASE WHEN resolved_at IS NOT NULL 
                  THEN TIMESTAMPDIFF(HOUR, detected_at, resolved_at) 
                  ELSE NULL END), 1) as avg_resolution_hours
    FROM incidents
")->fetch();

// =============================================
// 2. جميع الحوادث مع تفاصيلها
// =============================================
$incidents = $db->query("
    SELECT i.*, 
           u1.full_name as assigned_to_name,
           u2.full_name as created_by_name,
           (SELECT COUNT(*) FROM logs WHERE description LIKE CONCAT('%', i.name, '%')) as related_logs
    FROM incidents i
    LEFT JOIN users u1 ON i.assigned_to = u1.id
    LEFT JOIN users u2 ON i.created_by = u2.id
    ORDER BY 
        CASE i.status
            WHEN 'open' THEN 1
            WHEN 'in-progress' THEN 2
            WHEN 'resolved' THEN 3
            WHEN 'closed' THEN 4
        END,
        CASE i.severity
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        i.detected_at DESC
")->fetchAll();

// =============================================
// 3. الحوادث حسب الشهر (آخر 6 أشهر)
// =============================================
$monthly_incidents = $db->query("
    SELECT 
        DATE_FORMAT(detected_at, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high
    FROM incidents
    WHERE detected_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(detected_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// =============================================
// 4. الحوادث حسب النوع
// =============================================
$incidents_by_type = $db->query("
    SELECT 
        type,
        COUNT(*) as count
    FROM incidents
    GROUP BY type
    ORDER BY count DESC
")->fetchAll();

// =============================================
// 5. آخر 5 حوادث مفتوحة
// =============================================
$recent_open = $db->query("
    SELECT i.*, u.full_name as assigned_to_name
    FROM incidents i
    LEFT JOIN users u ON i.assigned_to = u.id
    WHERE i.status IN ('open', 'in-progress')
    ORDER BY i.detected_at DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 6. أكثر المستخدمين تكليفاً بالحوادث
// =============================================
$top_assignees = $db->query("
    SELECT 
        u.full_name,
        u.username,
        COUNT(i.id) as incident_count,
        SUM(CASE WHEN i.severity = 'critical' THEN 1 ELSE 0 END) as critical_count
    FROM users u
    LEFT JOIN incidents i ON u.id = i.assigned_to
    GROUP BY u.id
    HAVING incident_count > 0
    ORDER BY incident_count DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 7. متوسط وقت الحل حسب الشدة
// =============================================
$resolution_time_by_severity = $db->query("
    SELECT 
        severity,
        ROUND(AVG(TIMESTAMPDIFF(HOUR, detected_at, resolved_at)), 1) as avg_hours,
        COUNT(*) as count
    FROM incidents
    WHERE resolved_at IS NOT NULL
    GROUP BY severity
    ORDER BY 
        CASE severity
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END
")->fetchAll();

// دوال مساعدة
function getIncidentStatusBadge($status) {
    $colors = [
        'open' => 'bg-red-500',
        'in-progress' => 'bg-yellow-500',
        'resolved' => 'bg-green-500',
        'closed' => 'bg-gray-500'
    ];
    $texts = [
        'open' => 'مفتوح',
        'in-progress' => 'قيد المعالجة',
        'resolved' => 'تم الحل',
        'closed' => 'مغلق'
    ];
    $color = $colors[$status] ?? 'bg-gray-500';
    $text = $texts[$status] ?? $status;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getIncidentSeverityBadge($severity) {
    $colors = [
        'critical' => 'bg-red-600',
        'high' => 'bg-orange-500',
        'medium' => 'bg-yellow-500',
        'low' => 'bg-blue-500'
    ];
    $texts = [
        'critical' => 'حرج',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض'
    ];
    $color = $colors[$severity] ?? 'bg-gray-500';
    $text = $texts[$severity] ?? $severity;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getIncidentTypeBadge($type) {
    $colors = [
        'breach' => 'bg-red-500',
        'outage' => 'bg-orange-500',
        'attack' => 'bg-yellow-500',
        'data_loss' => 'bg-purple-500',
        'compliance' => 'bg-blue-500'
    ];
    $texts = [
        'breach' => 'اختراق',
        'outage' => 'انقطاع',
        'attack' => 'هجوم',
        'data_loss' => 'فقدان بيانات',
        'compliance' => 'امتثال'
    ];
    $color = $colors[$type] ?? 'bg-gray-500';
    $text = $texts[$type] ?? $type;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function formatIncidentTime($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 3600) return "منذ " . round($diff / 60) . " دقيقة";
    if ($diff < 86400) return "منذ " . round($diff / 3600) . " ساعة";
    return date('Y-m-d', $time);
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="createNewIncident()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    تسجيل حادث جديد
                </button>
                <button onclick="exportIncidentsReport()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                    </svg>
                    تصدير التقرير
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">🚨</span> إدارة الحوادث الأمنية
            </h1>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-200 text-sm mb-1">إجمالي الحوادث</p>
                        <p class="text-3xl font-bold"><?php echo $incidents_stats['total_incidents']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-red-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-red-200">
                    حرجة: <?php echo $incidents_stats['critical_incidents']; ?>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-200 text-sm mb-1">مفتوحة</p>
                        <p class="text-3xl font-bold"><?php echo $incidents_stats['open_incidents']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-yellow-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-yellow-200">
                    بحاجة للبدء
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm mb-1">قيد المعالجة</p>
                        <p class="text-3xl font-bold"><?php echo $incidents_stats['in_progress_incidents']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-blue-200">
                    جاري العمل عليها
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-600 to-green-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm mb-1">تم الحل</p>
                        <p class="text-3xl font-bold"><?php echo $incidents_stats['resolved_incidents']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-green-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-green-200">
                    متوسط الحل: <?php echo $incidents_stats['avg_resolution_hours'] ?? 0; ?> ساعة
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات متقدمة -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- توزيع الحوادث حسب النوع -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-blue-400 mb-4 text-right">📊 توزيع الحوادث حسب النوع</h3>
            <div class="space-y-4">
                <?php foreach ($incidents_by_type as $type): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo getIncidentTypeBadge($type['type']); ?></span>
                        <span class="text-gray-300"><?php echo $type['count']; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($type['count'] / max(1, $incidents_stats['total_incidents'])) * 100; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- متوسط وقت الحل حسب الشدة -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">⏱️ متوسط وقت الحل حسب الشدة</h3>
            <div class="space-y-4">
                <?php foreach ($resolution_time_by_severity as $time): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo getIncidentSeverityBadge($time['severity']); ?></span>
                        <span class="text-gray-300"><?php echo $time['avg_hours']; ?> ساعة (<?php echo $time['count']; ?> حادث)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($time['avg_hours'] / 24) * 100); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- آخر الحوادث المفتوحة -->
    <?php if (!empty($recent_open)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-red-400">⚠️ آخر الحوادث المفتوحة</h3>
            <span class="text-sm text-gray-400">يحتاج متابعة فورية</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($recent_open as $incident): ?>
            <div class="bg-slate-900 rounded-lg p-4 border-r-4 border-<?php echo $incident['severity'] == 'critical' ? 'red' : ($incident['severity'] == 'high' ? 'orange' : 'yellow'); ?>-500 incident-card">
                <div class="flex items-center justify-between mb-2">
                    <?php echo getIncidentSeverityBadge($incident['severity']); ?>
                    <span class="text-xs text-gray-400"><?php echo formatIncidentTime($incident['detected_at']); ?></span>
                </div>
                <h4 class="font-bold text-white mb-2"><?php echo $incident['name']; ?></h4>
                <p class="text-sm text-gray-400 mb-3"><?php echo $incident['description'] ?? 'لا يوجد وصف'; ?></p>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-300">👤 <?php echo $incident['assigned_to_name'] ?? 'غير معين'; ?></span>
                    <span class="text-gray-300">📊 <?php echo $incident['type']; ?></span>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <?php echo getIncidentStatusBadge($incident['status']); ?>
                    <button onclick="viewIncidentDetails(<?php echo $incident['id']; ?>)" class="text-blue-400 hover:text-blue-300 text-sm">عرض التفاصيل</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- أكثر المسؤولين تكليفاً -->
    <?php if (!empty($top_assignees)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">👥 أكثر المسؤولين تكليفاً</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($top_assignees as $assignee): ?>
            <div class="bg-slate-900 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-white"><?php echo $assignee['full_name']; ?></p>
                    <p class="text-xs text-gray-400">@<?php echo $assignee['username']; ?></p>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="px-3 py-1 bg-red-600 rounded-full text-xs">🔴 <?php echo $assignee['critical_count']; ?></span>
                    <span class="px-3 py-1 bg-blue-600 rounded-full text-xs">📊 <?php echo $assignee['incident_count']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- جدول الحوادث التفصيلي -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-green-400">📋 سجل الحوادث التفصيلي</h2>

        <!-- شريط البحث والتصفية -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="relative">
                    <input type="text" id="search-incidents" placeholder="بحث في الحوادث..." 
                           class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="status-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الحالات</option>
                    <option value="open">مفتوح</option>
                    <option value="in-progress">قيد المعالجة</option>
                    <option value="resolved">تم الحل</option>
                    <option value="closed">مغلق</option>
                </select>
                <select id="severity-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل المستويات</option>
                    <option value="critical">حرج</option>
                    <option value="high">عالي</option>
                    <option value="medium">متوسط</option>
                    <option value="low">منخفض</option>
                </select>
            </div>
            <div class="text-sm text-gray-400">
                إجمالي: <?php echo count($incidents); ?> حادث
            </div>
        </div>

        <!-- الجدول -->
        <div class="overflow-x-auto">
            <table class="w-full" id="incidents-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                        <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                        <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                        <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                        <th class="px-6 py-4 text-sm font-semibold">اسم الحادث</th>
                        <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                        <th class="px-6 py-4 text-sm font-semibold">التأثير</th>
                        <th class="px-6 py-4 text-sm font-semibold">المسؤول</th>
                        <th class="px-6 py-4 text-sm font-semibold">تاريخ الاكتشاف</th>
                        <th class="px-6 py-4 text-sm font-semibold">تاريخ الحل</th>
                        <th class="px-6 py-4 text-sm font-semibold">السجلات المرتبطة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incidents)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-8 text-gray-400">
                            لا توجد حوادث مسجلة
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($incidents as $incident): ?>
                        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors incident-row" 
                            data-status="<?php echo $incident['status']; ?>"
                            data-severity="<?php echo $incident['severity']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button onclick="viewIncidentDetails(<?php echo $incident['id']; ?>)" 
                                            class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <?php if ($incident['status'] != 'resolved' && $incident['status'] != 'closed'): ?>
                                    <button onclick="updateIncidentStatus(<?php echo $incident['id']; ?>)" 
                                            class="text-green-400 hover:text-green-300" title="تحديث الحالة">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getIncidentStatusBadge($incident['status']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getIncidentSeverityBadge($incident['severity']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getIncidentTypeBadge($incident['type']); ?>
                            </td>
                            <td class="px-6 py-4 font-semibold text-green-400">
                                <?php echo $incident['name']; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300 max-w-xs truncate">
                                <?php echo $incident['description'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $incident['impact'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $incident['assigned_to_name'] ?? 'غير معين'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo date('Y-m-d H:i', strtotime($incident['detected_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <?php echo $incident['resolved_at'] ? date('Y-m-d H:i', strtotime($incident['resolved_at'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($incident['related_logs'] > 0): ?>
                                <span class="px-3 py-1 bg-blue-600 rounded-full text-xs"><?php echo $incident['related_logs']; ?></span>
                                <?php else: ?>
                                <span class="text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- نافذة تفاصيل الحادث -->
<div id="incident-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeIncidentModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-red-400">🔍 تفاصيل الحادث</h3>
        </div>
        
        <div id="incident-details-content" class="space-y-6">
            <!-- محتوى الحادث يتم تحميله هنا -->
        </div>
    </div>
</div>

<!-- نافذة إنشاء حادث جديد -->
<div id="create-incident-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateIncidentModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400">➕ تسجيل حادث جديد</h3>
        </div>
        
        <form id="create-incident-form" class="space-y-4" onsubmit="saveNewIncident(event)">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم الحادث</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">النوع</label>
                    <select name="type" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="breach">اختراق</option>
                        <option value="outage">انقطاع</option>
                        <option value="attack">هجوم</option>
                        <option value="data_loss">فقدان بيانات</option>
                        <option value="compliance">امتثال</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الشدة</label>
                    <select name="severity" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="critical">حرج</option>
                        <option value="high">عالي</option>
                        <option value="medium">متوسط</option>
                        <option value="low">منخفض</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المسؤول</label>
                    <select name="assigned_to" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">غير معين</option>
                        <?php
                        $users = $db->query("SELECT id, full_name FROM users WHERE is_active = 1")->fetchAll();
                        foreach ($users as $user):
                        ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="3" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">التأثير</label>
                <textarea name="impact" rows="2" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>
            
            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    حفظ الحادث
                </button>
                <button type="button" onclick="closeCreateIncidentModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.incident-card {
    transition: all 0.3s ease;
}
.incident-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.3);
}
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
let currentIncidentId = null;

function createNewIncident() {
    document.getElementById('create-incident-modal').classList.remove('hidden');
    document.getElementById('create-incident-modal').classList.add('flex');
}

function closeCreateIncidentModal() {
    document.getElementById('create-incident-modal').classList.add('hidden');
    document.getElementById('create-incident-modal').classList.remove('flex');
}

function saveNewIncident(event) {
    event.preventDefault();
    showLoading();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    
    // محاكاة حفظ الحادث
    setTimeout(() => {
        hideLoading();
        closeCreateIncidentModal();
        showNotification('✅ تم تسجيل الحادث بنجاح', 'success');
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function exportIncidentsReport() {
    showNotification('📊 جاري تصدير تقرير الحوادث...', 'info');
    setTimeout(() => {
        showNotification('✅ تم تصدير التقرير بنجاح', 'success');
    }, 2000);
}

function viewIncidentDetails(id) {
    currentIncidentId = id;
    showLoading();
    
    // جلب تفاصيل الحادث من قاعدة البيانات
    fetch(`api/get_incident_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const details = `
                    <div class="space-y-4">
                        <div class="p-4 bg-slate-900 rounded-lg">
                            <h4 class="text-xl font-bold text-white mb-4">${data.incident.name}</h4>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">الحالة</p>
                                    <p class="font-semibold">${data.incident.status}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">الشدة</p>
                                    <p class="font-semibold">${data.incident.severity}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">النوع</p>
                                    <p class="font-semibold">${data.incident.type}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">المسؤول</p>
                                    <p class="font-semibold">${data.incident.assigned_to_name || 'غير معين'}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">تاريخ الاكتشاف</p>
                                    <p class="font-semibold">${data.incident.detected_at}</p>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-lg">
                                    <p class="text-sm text-gray-400">تاريخ الحل</p>
                                    <p class="font-semibold">${data.incident.resolved_at || 'لم يحل بعد'}</p>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-800 rounded-lg mb-4">
                                <p class="text-sm text-gray-400 mb-2">الوصف</p>
                                <p class="text-gray-300">${data.incident.description || 'لا يوجد وصف'}</p>
                            </div>

                            <div class="p-4 bg-slate-800 rounded-lg mb-4">
                                <p class="text-sm text-gray-400 mb-2">التأثير</p>
                                <p class="text-gray-300">${data.incident.impact || 'لا يوجد تأثير محدد'}</p>
                            </div>

                            <div class="flex justify-between pt-4 border-t border-slate-700">
                                ${data.incident.status != 'resolved' && data.incident.status != 'closed' ? 
                                    `<button onclick="resolveIncident(${id})" class="px-4 py-2 bg-green-600 rounded-lg">تحديد كحل</button>` : ''}
                                <button onclick="closeIncidentModal()" class="px-4 py-2 bg-gray-600 rounded-lg">إغلاق</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('incident-details-content').innerHTML = details;
            } else {
                showNotification('❌ فشل تحميل تفاصيل الحادث', 'error');
            }
            hideLoading();
            document.getElementById('incident-details-modal').classList.remove('hidden');
            document.getElementById('incident-details-modal').classList.add('flex');
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ خطأ في الاتصال', 'error');
            console.error('Error:', error);
        });
}

function closeIncidentModal() {
    document.getElementById('incident-details-modal').classList.add('hidden');
    document.getElementById('incident-details-modal').classList.remove('flex');
}

function updateIncidentStatus(id) {
    showNotification(`جاري تحديث حالة الحادث #${id}`, 'info');
}

function resolveIncident(id) {
    if (confirm('هل أنت متأكد من تحديد هذا الحادث كحل؟')) {
        showLoading();
        setTimeout(() => {
            hideLoading();
            closeIncidentModal();
            showNotification('✅ تم تحديث حالة الحادث', 'success');
            setTimeout(() => location.reload(), 1500);
        }, 1500);
    }
}

// البحث المباشر
document.getElementById('search-incidents')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.incident-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// تصفية الحالة والشدة
document.getElementById('status-filter')?.addEventListener('change', filterTable);
document.getElementById('severity-filter')?.addEventListener('change', filterTable);

function filterTable() {
    const status = document.getElementById('status-filter').value;
    const severity = document.getElementById('severity-filter').value;
    const rows = document.querySelectorAll('.incident-row');
    
    rows.forEach(row => {
        const statusMatch = status === 'all' || row.dataset.status === status;
        const severityMatch = severity === 'all' || row.dataset.severity === severity;
        row.style.display = statusMatch && severityMatch ? '' : 'none';
    });
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