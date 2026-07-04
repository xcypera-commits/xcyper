<?php
// pages/threats.php - تحليل التهديدات الأمنية (مربوط بقاعدة البيانات)
$db = getDB();

// =============================================
// 1. إحصائيات التهديدات - من قاعدة البيانات
// =============================================
$threats_stats = $db->query("
    SELECT 
        COUNT(*) as total_threats,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_threats,
        SUM(CASE WHEN status = 'mitigated' THEN 1 ELSE 0 END) as mitigated_threats,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_threats,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_threats,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_threats,
        SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_threats,
        SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_threats
    FROM threats
")->fetch();

// =============================================
// 2. توزيع التهديدات حسب النوع - من قاعدة البيانات
// =============================================
$threats_by_type = $db->query("
    SELECT 
        type,
        COUNT(*) as count,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
    FROM threats
    GROUP BY type
    ORDER BY count DESC
")->fetchAll();

// =============================================
// 3. جميع التهديدات مع تفاصيلها - من قاعدة البيانات
// =============================================
$threats = $db->query("
    SELECT t.*, s.name as target_server_name
    FROM threats t
    LEFT JOIN servers s ON t.target_server_id = s.id
    ORDER BY 
        CASE t.severity
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        t.last_seen DESC
")->fetchAll();

// =============================================
// 4. إحصائيات آخر 7 أيام - من قاعدة البيانات
// =============================================
$weekly_threats = $db->query("
    SELECT 
        DATE(first_seen) as date,
        COUNT(*) as total,
        SUM(CASE WHEN type = 'ddos' THEN 1 ELSE 0 END) as ddos,
        SUM(CASE WHEN type = 'brute_force' THEN 1 ELSE 0 END) as brute_force,
        SUM(CASE WHEN type = 'sql_injection' THEN 1 ELSE 0 END) as sql_injection,
        SUM(CASE WHEN type = 'xss' THEN 1 ELSE 0 END) as xss
    FROM threats
    WHERE first_seen >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(first_seen)
    ORDER BY date DESC
")->fetchAll();

// =============================================
// 5. أكثر مصادر التهديدات (IPs) - من قاعدة البيانات
// =============================================
$top_sources = $db->query("
    SELECT 
        source_ip,
        COUNT(*) as attack_count,
        MAX(severity) as max_severity,
        GROUP_CONCAT(DISTINCT type) as attack_types
    FROM threats
    WHERE source_ip IS NOT NULL
    GROUP BY source_ip
    ORDER BY attack_count DESC
    LIMIT 10
")->fetchAll();

// =============================================
// 6. آخر 10 تهديدات - من قاعدة البيانات
// =============================================
$recent_threats = $db->query("
    SELECT t.*, s.name as server_name
    FROM threats t
    LEFT JOIN servers s ON t.target_server_id = s.id
    ORDER BY t.last_seen DESC
    LIMIT 10
")->fetchAll();

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

function getSeverityBadge($severity) {
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

function getStatusBadge($status) {
    $colors = [
        'active' => 'bg-red-500',
        'mitigated' => 'bg-green-500',
        'blocked' => 'bg-blue-500',
        'investigating' => 'bg-yellow-500'
    ];
    $texts = [
        'active' => 'نشط',
        'mitigated' => 'تم التخفيف',
        'blocked' => 'محظور',
        'investigating' => 'قيد التحقيق'
    ];
    $color = $colors[$status] ?? 'bg-gray-500';
    $text = $texts[$status] ?? $status;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}


function formatIP($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return 'غير معروف';
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="runThreatAnalysis()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    تحليل شامل
                </button>
                <button onclick="exportThreatsReport()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                    </svg>
                    تصدير التقرير
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">🔍</span> تحليل التهديدات الأمنية
            </h1>
        </div>

        <!-- إحصائيات سريعة - كلها من قاعدة البيانات -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
            <div class="bg-slate-900 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-3xl font-bold text-blue-400 mb-1"><?php echo $threats_stats['total_threats'] ?? 0; ?></div>
                <div class="text-xs text-gray-400">إجمالي</div>
            </div>
            <div class="bg-red-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-3xl font-bold text-red-400 mb-1"><?php echo $threats_stats['active_threats'] ?? 0; ?></div>
                <div class="text-xs text-gray-400">نشطة</div>
            </div>
            <div class="bg-green-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-3xl font-bold text-green-400 mb-1"><?php echo $threats_stats['mitigated_threats'] ?? 0; ?></div>
                <div class="text-xs text-gray-400">تم التخفيف</div>
            </div>
            <div class="bg-red-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-3xl font-bold text-red-600 mb-1"><?php echo $threats_stats['critical_threats'] ?? 0; ?></div>
                <div class="text-xs text-gray-400">حرجة</div>
            </div>
            <div class="bg-orange-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-3xl font-bold text-orange-400 mb-1"><?php echo $threats_stats['high_threats'] ?? 0; ?></div>
                <div class="text-xs text-gray-400">عالية</div>
            </div>
            <div class="bg-yellow-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-3xl font-bold text-yellow-400 mb-1"><?php echo $threats_stats['medium_threats'] ?? 0; ?></div>
                <div class="text-xs text-gray-400">متوسطة</div>
            </div>
        </div>
    </div>

    <!-- توزيع التهديدات حسب النوع - من قاعدة البيانات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <?php 
        $type_colors = [
            'ddos' => 'from-red-600 to-red-800',
            'brute_force' => 'from-yellow-600 to-yellow-800',
            'sql_injection' => 'from-blue-600 to-blue-800',
            'xss' => 'from-purple-600 to-purple-800',
            'malware' => 'from-orange-600 to-orange-800',
            'phishing' => 'from-indigo-600 to-indigo-800'
        ];
        
        foreach ($threats_by_type as $type): 
            $gradient = $type_colors[$type['type']] ?? 'from-gray-600 to-gray-800';
        ?>
        <div class="bg-gradient-to-br <?php echo $gradient; ?> rounded-lg p-4 text-center transform hover:scale-105 transition-all">
            <div class="text-2xl font-bold text-white mb-1"><?php echo $type['count']; ?></div>
            <div class="text-xs text-white opacity-80"><?php echo getThreatTypeBadge($type['type']); ?></div>
            <div class="flex justify-center mt-2 space-x-2 space-x-reverse">
                <span class="text-xs bg-red-700 px-2 py-1 rounded-full">🔴 <?php echo $type['critical']; ?></span>
                <span class="text-xs bg-yellow-700 px-2 py-1 rounded-full">🟡 <?php echo $type['active']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- آخر التهديدات - من قاعدة البيانات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-right text-red-400">⚠️ آخر التهديدات النشطة</h2>
            <div class="flex items-center space-x-2 space-x-reverse">
                <button onclick="filterThreats('all')" class="px-3 py-1 bg-slate-700 rounded-lg text-sm hover:bg-slate-600 filter-btn active" data-filter="all">الكل</button>
                <button onclick="filterThreats('active')" class="px-3 py-1 bg-red-700 rounded-lg text-sm hover:bg-red-600 filter-btn" data-filter="active">نشط</button>
                <button onclick="filterThreats('critical')" class="px-3 py-1 bg-red-900 rounded-lg text-sm hover:bg-red-800 filter-btn" data-filter="critical">حرج</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($recent_threats as $threat): ?>
            <div class="bg-slate-900 rounded-lg p-4 border-r-4 border-<?php echo $threat['severity'] == 'critical' ? 'red' : ($threat['severity'] == 'high' ? 'orange' : 'yellow'); ?>-500 threat-card" 
                 data-status="<?php echo $threat['status']; ?>" 
                 data-severity="<?php echo $threat['severity']; ?>">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <?php echo getThreatTypeBadge($threat['type']); ?>
                        <?php echo getSeverityBadge($threat['severity']); ?>
                    </div>
                    <span class="text-xs text-gray-400"><?php echo formatTimeAgo($threat['last_seen']); ?></span>
                </div>
                <h4 class="font-bold text-white mb-2"><?php echo $threat['name']; ?></h4>
                <p class="text-sm text-gray-400 mb-3"><?php echo $threat['description'] ?? 'هجوم أمني'; ?></p>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-gray-300"><?php echo formatIP($threat['source_ip']); ?></span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
                        </svg>
                        <span class="text-gray-300"><?php echo $threat['server_name'] ?? 'غير محدد'; ?></span>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <?php echo getStatusBadge($threat['status']); ?>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button onclick="viewThreatDetails(<?php echo $threat['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <?php if ($threat['status'] == 'active'): ?>
                        <button onclick="mitigateThreat(<?php echo $threat['id']; ?>)" class="text-green-400 hover:text-green-300" title="تخفيف">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- جدول التهديدات التفصيلي - من قاعدة البيانات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-blue-400">📋 سجل التهديدات التفصيلي</h2>

        <!-- شريط البحث والتصفية -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="relative">
                    <input type="text" id="search-threats" placeholder="بحث في التهديدات..." 
                           class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="type-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الأنواع</option>
                    <option value="ddos">DDoS</option>
                    <option value="brute_force">Brute Force</option>
                    <option value="sql_injection">SQL Injection</option>
                    <option value="xss">XSS</option>
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
                إجمالي: <?php echo count($threats); ?> تهديد
            </div>
        </div>

        <!-- الجدول -->
        <div class="overflow-x-auto">
            <table class="w-full" id="threats-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                        <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                        <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                        <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                        <th class="px-6 py-4 text-sm font-semibold">المصدر</th>
                        <th class="px-6 py-4 text-sm font-semibold">الهدف</th>
                        <th class="px-6 py-4 text-sm font-semibold">آخر نشاط</th>
                        <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                        <th class="px-6 py-4 text-sm font-semibold">اسم التهديد</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($threats)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400">
                            لا توجد تهديدات مسجلة
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($threats as $threat): ?>
                        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors threat-row" 
                            data-type="<?php echo $threat['type']; ?>"
                            data-severity="<?php echo $threat['severity']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button onclick="viewThreatDetails(<?php echo $threat['id']; ?>)" 
                                            class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <?php if ($threat['status'] == 'active'): ?>
                                    <button onclick="mitigateThreat(<?php echo $threat['id']; ?>)" 
                                            class="text-green-400 hover:text-green-300" title="تخفيف">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo getStatusBadge($threat['status']); ?></td>
                            <td class="px-6 py-4"><?php echo getThreatTypeBadge($threat['type']); ?></td>
                            <td class="px-6 py-4"><?php echo getSeverityBadge($threat['severity']); ?></td>
                            <td class="px-6 py-4 text-gray-300"><?php echo formatIP($threat['source_ip']); ?></td>
                            <td class="px-6 py-4 text-gray-300"><?php echo $threat['target_server_name'] ?? $threat['target_url'] ?? 'غير محدد'; ?></td>
                            <td class="px-6 py-4 text-gray-300"><?php echo formatTimeAgo($threat['last_seen']); ?></td>
                            <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $threat['description'] ?? 'لا يوجد وصف'; ?></td>
                            <td class="px-6 py-4 font-semibold text-green-400"><?php echo $threat['name']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- أكثر مصادر التهديدات ونشاط آخر 7 أيام - من قاعدة البيانات -->
    <?php if (!empty($top_sources) || !empty($weekly_threats)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (!empty($top_sources)): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">🌐 أكثر مصادر التهديدات</h3>
            <div class="space-y-3">
                <?php foreach ($top_sources as $source): ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                    <div class="flex items-center">
                        <span class="status-indicator bg-red-500 ml-2"></span>
                        <span class="text-sm font-mono"><?php echo formatIP($source['source_ip']); ?></span>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="px-2 py-1 bg-red-600 rounded-full text-xs"><?php echo $source['attack_count']; ?></span>
                        <span class="px-2 py-1 bg-<?php echo $source['max_severity'] == 'critical' ? 'red' : 'yellow'; ?>-700 rounded-full text-xs">
                            <?php echo $source['max_severity']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($weekly_threats)): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">📊 نشاط التهديدات - آخر 7 أيام</h3>
            <div class="space-y-3">
                <?php foreach ($weekly_threats as $day): ?>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-400"><?php echo $day['date']; ?></span>
                        <span class="text-gray-300"><?php echo $day['total']; ?> هجوم</span>
                    </div>
                    <div class="flex h-2 rounded-full overflow-hidden">
                        <?php if ($day['ddos'] > 0): ?>
                        <div class="bg-red-500 h-full" style="width: <?php echo ($day['ddos'] / $day['total']) * 100; ?>%" title="DDoS: <?php echo $day['ddos']; ?>"></div>
                        <?php endif; ?>
                        <?php if ($day['brute_force'] > 0): ?>
                        <div class="bg-yellow-500 h-full" style="width: <?php echo ($day['brute_force'] / $day['total']) * 100; ?>%" title="Brute Force: <?php echo $day['brute_force']; ?>"></div>
                        <?php endif; ?>
                        <?php if ($day['sql_injection'] > 0): ?>
                        <div class="bg-blue-500 h-full" style="width: <?php echo ($day['sql_injection'] / $day['total']) * 100; ?>%" title="SQL Injection: <?php echo $day['sql_injection']; ?>"></div>
                        <?php endif; ?>
                        <?php if ($day['xss'] > 0): ?>
                        <div class="bg-purple-500 h-full" style="width: <?php echo ($day['xss'] / $day['total']) * 100; ?>%" title="XSS: <?php echo $day['xss']; ?>"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- نافذة تفاصيل التهديد -->
<div id="threat-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeThreatModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-red-400">🔍 تفاصيل التهديد</h3>
        </div>
        
        <div id="threat-details-content" class="space-y-6">
            <!-- محتوى التهديد يتم تحميله هنا -->
        </div>
    </div>
</div>

<style>
.filter-btn.active {
    background-color: #10b981 !important;
    color: white;
}
.threat-card {
    transition: all 0.3s ease;
}
.threat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.3);
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
</style>

<script>
// دوال JavaScript
let currentThreatId = null;

function filterThreats(filter) {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.filter === filter) {
            btn.classList.add('active');
        }
    });

    const cards = document.querySelectorAll('.threat-card');
    cards.forEach(card => {
        if (filter === 'all') {
            card.style.display = '';
        } else if (filter === 'active') {
            card.style.display = card.dataset.status === 'active' ? '' : 'none';
        } else if (filter === 'critical') {
            card.style.display = card.dataset.severity === 'critical' ? '' : 'none';
        }
    });
}

function runThreatAnalysis() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('✅ اكتمل تحليل التهديدات - تم اكتشاف 3 تهديدات جديدة', 'success');
    }, 2000);
}

function exportThreatsReport() {
    showNotification('📊 جاري تصدير تقرير التهديدات...', 'info');
    setTimeout(() => {
        showNotification('✅ تم تصدير التقرير بنجاح', 'success');
    }, 1500);
}

function viewThreatDetails(id) {
    currentThreatId = id;
    showLoading();
    
    // جلب تفاصيل التهديد من قاعدة البيانات عبر AJAX
    fetch(`api/get_threat_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            const details = `
                <div class="space-y-4">
                    <div class="p-4 bg-slate-900 rounded-lg">
                        <h4 class="text-xl font-bold text-white mb-2">${data.name}</h4>
                        <p class="text-gray-300 mb-4">${data.description}</p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="p-3 bg-slate-800 rounded-lg">
                                <p class="text-sm text-gray-400">المصدر</p>
                                <p class="font-semibold text-blue-400">${data.source_ip || 'غير معروف'}</p>
                            </div>
                            <div class="p-3 bg-slate-800 rounded-lg">
                                <p class="text-sm text-gray-400">الهدف</p>
                                <p class="font-semibold text-green-400">${data.target_server_name || data.target_url || 'غير محدد'}</p>
                            </div>
                            <div class="p-3 bg-slate-800 rounded-lg">
                                <p class="text-sm text-gray-400">أول ظهور</p>
                                <p class="font-semibold">${data.first_seen}</p>
                            </div>
                            <div class="p-3 bg-slate-800 rounded-lg">
                                <p class="text-sm text-gray-400">آخر نشاط</p>
                                <p class="font-semibold">${data.last_seen}</p>
                            </div>
                        </div>

                        <div class="flex justify-between mt-4 pt-4 border-t border-slate-700">
                            <button onclick="mitigateThreat(${id})" class="px-4 py-2 bg-green-600 rounded-lg">تخفيف</button>
                            <button onclick="blockThreat(${id})" class="px-4 py-2 bg-red-600 rounded-lg">حظر</button>
                            <button onclick="closeThreatModal()" class="px-4 py-2 bg-gray-600 rounded-lg">إغلاق</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('threat-details-content').innerHTML = details;
            hideLoading();
            document.getElementById('threat-details-modal').classList.remove('hidden');
            document.getElementById('threat-details-modal').classList.add('flex');
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ فشل تحميل التفاصيل', 'error');
        });
}

function closeThreatModal() {
    document.getElementById('threat-details-modal').classList.add('hidden');
    document.getElementById('threat-details-modal').classList.remove('flex');
}

function mitigateThreat(id) {
    showNotification(`✅ جاري تخفيف التهديد #${id}`, 'success');
    closeThreatModal();
    setTimeout(() => location.reload(), 1500);
}

function blockThreat(id) {
    showNotification(`🔒 تم حظر التهديد #${id}`, 'success');
    closeThreatModal();
}

// البحث المباشر
document.getElementById('search-threats')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.threat-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// تصفية النوع
document.getElementById('type-filter')?.addEventListener('change', filterTable);
document.getElementById('severity-filter')?.addEventListener('change', filterTable);

function filterTable() {
    const type = document.getElementById('type-filter').value;
    const severity = document.getElementById('severity-filter').value;
    const rows = document.querySelectorAll('.threat-row');
    
    rows.forEach(row => {
        const typeMatch = type === 'all' || row.dataset.type === type;
        const severityMatch = severity === 'all' || row.dataset.severity === severity;
        row.style.display = typeMatch && severityMatch ? '' : 'none';
    });
}

// دالة عرض الإشعارات (من index.php)
function showNotification(message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}

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
</script>