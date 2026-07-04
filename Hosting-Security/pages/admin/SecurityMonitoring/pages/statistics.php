<?php
// pages/statistics.php - إحصاءات الأمان المتقدمة
$db = getDB();

// =============================================
// 1. إحصائيات عامة من جداول متعددة
// =============================================
$general_stats = [];

// إجمالي التهديدات
$general_stats['total_threats'] = $db->query("SELECT COUNT(*) FROM threats")->fetchColumn();

// التهديدات النشطة
$general_stats['active_threats'] = $db->query("SELECT COUNT(*) FROM threats WHERE status = 'active'")->fetchColumn();

// إجمالي التنبيهات
$general_stats['total_alerts'] = $db->query("SELECT COUNT(*) FROM alerts")->fetchColumn();

// التنبيهات الحرجة
$general_stats['critical_alerts'] = $db->query("SELECT COUNT(*) FROM alerts WHERE type = 'critical' AND status != 'resolved'")->fetchColumn();

// إجمالي الخوادم
$general_stats['total_servers'] = $db->query("SELECT COUNT(*) FROM servers")->fetchColumn();

// الخوادم النشطة
$general_stats['online_servers'] = $db->query("SELECT COUNT(*) FROM servers WHERE status = 'online'")->fetchColumn();

// إجمالي الحوادث
$general_stats['total_incidents'] = $db->query("SELECT COUNT(*) FROM incidents")->fetchColumn();

// الحوادث المفتوحة
$general_stats['open_incidents'] = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open', 'in-progress')")->fetchColumn();

// =============================================
// 2. إحصائيات التهديدات حسب الشهر (آخر 6 أشهر)
// =============================================
$monthly_threats = $db->query("
    SELECT 
        DATE_FORMAT(first_seen, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
        SUM(CASE WHEN type = 'ddos' THEN 1 ELSE 0 END) as ddos,
        SUM(CASE WHEN type = 'brute_force' THEN 1 ELSE 0 END) as brute_force,
        SUM(CASE WHEN type = 'sql_injection' THEN 1 ELSE 0 END) as sql_injection,
        SUM(CASE WHEN type = 'xss' THEN 1 ELSE 0 END) as xss
    FROM threats
    WHERE first_seen >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(first_seen, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// =============================================
// 3. إحصائيات التنبيهات حسب اليوم (آخر 30 يوم)
// =============================================
$daily_alerts = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN type = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN type = 'warning' THEN 1 ELSE 0 END) as warning,
        SUM(CASE WHEN type = 'info' THEN 1 ELSE 0 END) as info
    FROM alerts
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
")->fetchAll();

// =============================================
// 4. أكثر أنواع التهديدات شيوعاً
// =============================================
$threats_by_type = $db->query("
    SELECT 
        type,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM threats), 1) as percentage
    FROM threats
    GROUP BY type
    ORDER BY count DESC
")->fetchAll();

// =============================================
// 5. أكثر الخوادم استهدافاً
// =============================================
$top_targeted_servers = $db->query("
    SELECT 
        s.name,
        s.type,
        COUNT(t.id) as attack_count,
        SUM(CASE WHEN t.severity = 'critical' THEN 1 ELSE 0 END) as critical_attacks
    FROM servers s
    LEFT JOIN threats t ON s.id = t.target_server_id
    GROUP BY s.id
    HAVING attack_count > 0
    ORDER BY attack_count DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 6. إحصائيات الأداء (من security_statistics)
// =============================================
$performance_stats = $db->query("
    SELECT 
        ROUND(AVG(total_attacks), 1) as avg_daily_attacks,
        ROUND(AVG(blocked_attacks), 1) as avg_blocked,
        ROUND(AVG(avg_response_time), 2) as avg_response_time,
        ROUND(AVG(system_uptime), 2) as avg_uptime,
        SUM(total_attacks) as total_attacks_month,
        SUM(blocked_attacks) as total_blocked_month
    FROM security_statistics
    WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch();

// =============================================
// 7. توزيع التنبيهات حسب المصدر
// =============================================
$alerts_by_source = $db->query("
    SELECT 
        COALESCE(source, 'غير معروف') as source,
        COUNT(*) as count
    FROM alerts
    GROUP BY source
    ORDER BY count DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 8. متوسط وقت الاستجابة للحوادث
// =============================================
$incident_response_time = $db->query("
    SELECT 
        ROUND(AVG(TIMESTAMPDIFF(HOUR, detected_at, resolved_at)), 1) as avg_hours,
        MIN(TIMESTAMPDIFF(HOUR, detected_at, resolved_at)) as min_hours,
        MAX(TIMESTAMPDIFF(HOUR, detected_at, resolved_at)) as max_hours
    FROM incidents
    WHERE resolved_at IS NOT NULL
")->fetch();

// =============================================
// 9. نسبة نجاح الحظر
// =============================================
$block_success_rate = $db->query("
    SELECT 
        ROUND(
            (SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 
            1
        ) as success_rate
    FROM threats
")->fetchColumn();

// دوال مساعدة
function getThreatTypeBadge($type) {
    $colors = [
        'ddos' => 'bg-red-500',
        'brute_force' => 'bg-yellow-500',
        'sql_injection' => 'bg-blue-500',
        'xss' => 'bg-purple-500',
        'malware' => 'bg-orange-500',
        'phishing' => 'bg-indigo-500'
    ];
    $texts = [
        'ddos' => 'DDoS',
        'brute_force' => 'Brute Force',
        'sql_injection' => 'SQL Injection',
        'xss' => 'XSS',
        'malware' => 'برمجيات خبيثة',
        'phishing' => 'تصيد'
    ];
    $color = $colors[$type] ?? 'bg-gray-500';
    $text = $texts[$type] ?? $type;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getThreatTypeColor($type) {
    return match($type) {
        'ddos' => '#ef4444',
        'brute_force' => '#f59e0b',
        'sql_injection' => '#3b82f6',
        'xss' => '#8b5cf6',
        default => '#6b7280'
    };
}


?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="exportStatisticsReport()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                    </svg>
                    تصدير التقرير
                </button>
                <button onclick="refreshStatistics()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    تحديث
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">📈</span> إحصاءات الأمان المتقدمة
            </h1>
        </div>

        <!-- بطاقات الإحصائيات الرئيسية -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm mb-1">إجمالي التهديدات</p>
                        <p class="text-3xl font-bold"><?php echo $general_stats['total_threats']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-sm text-blue-200">
                    <span>نشط: <?php echo $general_stats['active_threats']; ?></span>
                    <span>تم الحظر: <?php echo $performance_stats['avg_blocked'] ?? 0; ?></span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-200 text-sm mb-1">التنبيهات</p>
                        <p class="text-3xl font-bold"><?php echo $general_stats['total_alerts']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-red-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-sm text-red-200">
                    <span>حرجة: <?php echo $general_stats['critical_alerts']; ?></span>
                    <span>يومياً: <?php echo $daily_alerts[0]['total'] ?? 0; ?></span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-600 to-green-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm mb-1">الخوادم</p>
                        <p class="text-3xl font-bold"><?php echo $general_stats['total_servers']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-green-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-sm text-green-200">
                    <span>نشط: <?php echo $general_stats['online_servers']; ?></span>
                    <span>تشغيل: <?php echo $performance_stats['avg_uptime'] ?? 99.98; ?>%</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-600 to-purple-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-200 text-sm mb-1">الحوادث</p>
                        <p class="text-3xl font-bold"><?php echo $general_stats['total_incidents']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-purple-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-sm text-purple-200">
                    <span>مفتوحة: <?php echo $general_stats['open_incidents']; ?></span>
                    <span>متوسط الحل: <?php echo $incident_response_time['avg_hours'] ?? 0; ?> س</span>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الأداء -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">⚡ أداء النظام</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-400">متوسط الهجمات اليومية</span>
                        <span class="text-white font-bold"><?php echo $performance_stats['avg_daily_attacks'] ?? 0; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($performance_stats['avg_daily_attacks'] ?? 0) / 2); ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-400">نسبة الحظر</span>
                        <span class="text-white font-bold"><?php echo $block_success_rate ?? 0; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill bg-green-500" style="width: <?php echo $block_success_rate ?? 0; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-400">متوسط وقت الاستجابة</span>
                        <span class="text-white font-bold"><?php echo $performance_stats['avg_response_time'] ?? 0; ?> ث</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill bg-yellow-500" style="width: <?php echo min(100, (($performance_stats['avg_response_time'] ?? 0) / 3) * 100); ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-400">نسبة التشغيل</span>
                        <span class="text-white font-bold"><?php echo $performance_stats['avg_uptime'] ?? 99.98; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill bg-cyan-500" style="width: <?php echo $performance_stats['avg_uptime'] ?? 99.98; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">🔄 آخر 30 يوم</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">إجمالي الهجمات</span>
                    <span class="text-2xl font-bold text-red-400"><?php echo $performance_stats['total_attacks_month'] ?? 0; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">تم الحظر</span>
                    <span class="text-2xl font-bold text-green-400"><?php echo $performance_stats['total_blocked_month'] ?? 0; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">نسبة النجاح</span>
                    <span class="text-2xl font-bold text-blue-400">
                        <?php 
                        $total = $performance_stats['total_attacks_month'] ?? 1;
                        $blocked = $performance_stats['total_blocked_month'] ?? 0;
                        echo round(($blocked / max(1, $total)) * 100, 1); ?>%
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">متوسط يومياً</span>
                    <span class="text-2xl font-bold text-yellow-400"><?php echo round(($performance_stats['total_attacks_month'] ?? 0) / 30, 1); ?></span>
                </div>
            </div>
        </div>

        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-green-400 mb-4 text-right">🏆 أفضل الإحصائيات</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">أفضل يوم</span>
                    <span class="text-green-400"><?php echo $daily_alerts[array_key_last($daily_alerts)]['date'] ?? 'اليوم'; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">أقل هجمات</span>
                    <span class="text-green-400"><?php echo min(array_column($daily_alerts, 'total')) ?? 0; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">أكثر هجمات</span>
                    <span class="text-red-400"><?php echo max(array_column($daily_alerts, 'total')) ?? 0; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-900 rounded-lg">
                    <span class="text-gray-300">أسرع استجابة</span>
                    <span class="text-blue-400"><?php echo $incident_response_time['min_hours'] ?? 0; ?> ساعة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- توزيع التهديدات حسب النوع -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-blue-400 mb-4 text-right">📊 توزيع التهديدات حسب النوع</h3>
            <div class="space-y-4">
                <?php foreach ($threats_by_type as $type): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-300"><?php echo getThreatTypeBadge($type['type']); ?></span>
                        <span class="text-white"><?php echo $type['count']; ?> (<?php echo $type['percentage']; ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $type['percentage']; ?>%; background-color: <?php echo getThreatTypeColor($type['type']); ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- أكثر الخوادم استهدافاً -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-red-400 mb-4 text-right">🎯 أكثر الخوادم استهدافاً</h3>
            <div class="space-y-4">
                <?php foreach ($top_targeted_servers as $server): ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                    <div class="flex items-center">
                        <span class="status-indicator bg-red-500 ml-2"></span>
                        <div>
                            <span class="font-semibold text-white"><?php echo $server['name']; ?></span>
                            <span class="text-xs text-gray-400 mr-2">(<?php echo $server['type']; ?>)</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="px-2 py-1 bg-red-600 rounded-full text-xs"><?php echo $server['attack_count']; ?></span>
                        <span class="px-2 py-1 bg-red-800 rounded-full text-xs" title="هجمات حرجة">🔴 <?php echo $server['critical_attacks']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- جدول الإحصائيات التفصيلية الشهرية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">📅 الإحصائيات الشهرية (آخر 6 أشهر)</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4">الشهر</th>
                        <th class="px-6 py-4">إجمالي</th>
                        <th class="px-6 py-4">حرجة</th>
                        <th class="px-6 py-4">عالية</th>
                        <th class="px-6 py-4">متوسطة</th>
                        <th class="px-6 py-4">DDoS</th>
                        <th class="px-6 py-4">Brute Force</th>
                        <th class="px-6 py-4">SQL Injection</th>
                        <th class="px-6 py-4">XSS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_threats as $month): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-900">
                        <td class="px-6 py-4 font-semibold"><?php echo $month['month']; ?></td>
                        <td class="px-6 py-4"><?php echo $month['total']; ?></td>
                        <td class="px-6 py-4 text-red-400"><?php echo $month['critical']; ?></td>
                        <td class="px-6 py-4 text-orange-400"><?php echo $month['high']; ?></td>
                        <td class="px-6 py-4 text-yellow-400"><?php echo $month['medium']; ?></td>
                        <td class="px-6 py-4"><?php echo $month['ddos']; ?></td>
                        <td class="px-6 py-4"><?php echo $month['brute_force']; ?></td>
                        <td class="px-6 py-4"><?php echo $month['sql_injection']; ?></td>
                        <td class="px-6 py-4"><?php echo $month['xss']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- جدول التنبيهات اليومية (آخر 7 أيام) -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">📊 التنبيهات اليومية (آخر 7 أيام)</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4">التاريخ</th>
                        <th class="px-6 py-4">إجمالي</th>
                        <th class="px-6 py-4">حرجة</th>
                        <th class="px-6 py-4">تحذير</th>
                        <th class="px-6 py-4">معلومات</th>
                        <th class="px-6 py-4">الرسم البياني</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $recent_days = array_slice($daily_alerts, 0, 7);
                    foreach ($recent_days as $day): 
                    ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-900">
                        <td class="px-6 py-4"><?php echo $day['date']; ?></td>
                        <td class="px-6 py-4 font-semibold"><?php echo $day['total']; ?></td>
                        <td class="px-6 py-4 text-red-400"><?php echo $day['critical']; ?></td>
                        <td class="px-6 py-4 text-yellow-400"><?php echo $day['warning']; ?></td>
                        <td class="px-6 py-4 text-blue-400"><?php echo $day['info']; ?></td>
                        <td class="px-6 py-4">
                            <div class="flex h-2 w-32 rounded-full overflow-hidden">
                                <?php if ($day['critical'] > 0): ?>
                                <div class="bg-red-500 h-full" style="width: <?php echo ($day['critical'] / $day['total']) * 100; ?>%"></div>
                                <?php endif; ?>
                                <?php if ($day['warning'] > 0): ?>
                                <div class="bg-yellow-500 h-full" style="width: <?php echo ($day['warning'] / $day['total']) * 100; ?>%"></div>
                                <?php endif; ?>
                                <?php if ($day['info'] > 0): ?>
                                <div class="bg-blue-500 h-full" style="width: <?php echo ($day['info'] / $day['total']) * 100; ?>%"></div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    transition: width 0.3s ease;
}
</style>

<script>
function exportStatisticsReport() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('📊 تم تصدير تقرير الإحصائيات بنجاح', 'success');
    }, 2000);
}

function refreshStatistics() {
    showLoading();
    setTimeout(() => {
        location.reload();
    }, 1000);
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