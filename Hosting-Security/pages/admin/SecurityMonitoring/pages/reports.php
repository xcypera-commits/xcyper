<?php
// pages/reports.php - تقارير الأداء اليومية (محدث لاستخدام daily_reports)
$db = getDB();

// =============================================
// 1. إحصائيات التقارير من جدول daily_reports
// =============================================
$reports_stats = $db->query("
    SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN report_type = 'security' THEN 1 ELSE 0 END) as security_reports,
        SUM(CASE WHEN report_type = 'performance' THEN 1 ELSE 0 END) as performance_reports,
        SUM(CASE WHEN report_type = 'network' THEN 1 ELSE 0 END) as network_reports,
        SUM(CASE WHEN report_type = 'incident' THEN 1 ELSE 0 END) as incident_reports,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_reports,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_reports
    FROM daily_reports
")->fetch();

// =============================================
// 2. جميع التقارير مع تفاصيلها من daily_reports
// =============================================
$reports = $db->query("
    SELECT dr.*, u.full_name as generated_by_name,
           rs.total_alerts, rs.total_threats, rs.critical_events
    FROM daily_reports dr
    LEFT JOIN users u ON dr.generated_by = u.id
    LEFT JOIN report_statistics rs ON dr.id = rs.report_id
    ORDER BY dr.created_at DESC
    LIMIT 50
")->fetchAll();

// =============================================
// 3. إحصائيات إضافية (آخر 7 أيام)
// =============================================
$weekly_reports = $db->query("
    SELECT 
        DATE(report_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN report_type = 'security' THEN 1 ELSE 0 END) as security,
        SUM(CASE WHEN report_type = 'performance' THEN 1 ELSE 0 END) as performance,
        SUM(CASE WHEN report_type = 'network' THEN 1 ELSE 0 END) as network
    FROM daily_reports
    WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(report_date)
    ORDER BY date DESC
")->fetchAll();

// =============================================
// 4. إحصائيات الأداء (من security_statistics)
// =============================================
$performance_stats = $db->query("
    SELECT 
        ROUND(AVG(total_attacks), 1) as avg_attacks,
        ROUND(AVG(blocked_attacks), 1) as avg_blocked,
        ROUND(AVG(avg_response_time), 2) as avg_response,
        ROUND(AVG(system_uptime), 2) as avg_uptime
    FROM security_statistics 
    WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetch();

// =============================================
// 5. آخر 5 تقارير أمنية
// =============================================
$latest_security = $db->query("
    SELECT * FROM daily_reports 
    WHERE report_type = 'security' 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// دوال مساعدة إضافية
function getReportTypeBadge($type) {
    $colors = [
        'security' => 'bg-red-500',
        'performance' => 'bg-blue-500',
        'network' => 'bg-purple-500',
        'incident' => 'bg-yellow-500',
        'compliance' => 'bg-green-500'
    ];
    $texts = [
        'security' => 'أمني',
        'performance' => 'أداء',
        'network' => 'شبكة',
        'incident' => 'حوادث',
        'compliance' => 'امتثال'
    ];
    $color = $colors[$type] ?? 'bg-gray-500';
    $text = $texts[$type] ?? $type;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getReportPeriodText($period) {
    return match($period) {
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'yearly' => 'سنوي',
        default => $period
    };
}

function getStatusBadge($status) {
    $colors = [
        'draft' => 'bg-yellow-500',
        'published' => 'bg-green-500',
        'archived' => 'bg-gray-500'
    ];
    $texts = [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف'
    ];
    $color = $colors[$status] ?? 'bg-gray-500';
    $text = $texts[$status] ?? $status;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <a href="?page=client_reports" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    تقارير العملاء
                </a>
                <button onclick="generateNewReport()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    إنشاء تقرير جديد
                </button>
                <button onclick="exportAllReports()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                    </svg>
                    تصدير الكل
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">📊</span> تقارير الأداء اليومية
            </h1>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-slate-900 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-blue-400 mb-2"><?php echo $reports_stats['total_reports'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">إجمالي التقارير</div>
            </div>
            <div class="bg-red-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-red-400 mb-2"><?php echo $reports_stats['security_reports'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">تقارير أمنية</div>
            </div>
            <div class="bg-blue-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-blue-400 mb-2"><?php echo $reports_stats['performance_reports'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">تقارير أداء</div>
            </div>
            <div class="bg-purple-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-purple-400 mb-2"><?php echo $reports_stats['network_reports'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">تقارير شبكة</div>
            </div>
            <div class="bg-green-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-green-400 mb-2"><?php echo $reports_stats['published_reports'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">منشورة</div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الأداء -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- بطاقة الأداء العام -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h2 class="text-xl font-bold text-right mb-4 text-cyan-400">📈 مؤشرات الأداء (آخر 7 أيام)</h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2 text-sm">
                        <span class="text-gray-400">متوسط الهجمات اليومية</span>
                        <span class="text-red-400 font-semibold"><?php echo $performance_stats['avg_attacks'] ?? 0; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($performance_stats['avg_attacks'] ?? 0) / 2); ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2 text-sm">
                        <span class="text-gray-400">نسبة الحظر</span>
                        <span class="text-green-400 font-semibold"><?php 
                            $blocked = $performance_stats['avg_blocked'] ?? 0;
                            $attacks = $performance_stats['avg_attacks'] ?? 1;
                            echo round(($blocked / max(1, $attacks)) * 100, 1); ?>%
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo round(($blocked / max(1, $attacks)) * 100, 1); ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2 text-sm">
                        <span class="text-gray-400">متوسط زمن الاستجابة</span>
                        <span class="text-yellow-400 font-semibold"><?php echo $performance_stats['avg_response'] ?? 0; ?> ث</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, (($performance_stats['avg_response'] ?? 0) / 3) * 100); ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2 text-sm">
                        <span class="text-gray-400">نسبة التشغيل</span>
                        <span class="text-cyan-400 font-semibold"><?php echo $performance_stats['avg_uptime'] ?? 99.98; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $performance_stats['avg_uptime'] ?? 99.98; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخر التقارير الأمنية -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h2 class="text-xl font-bold text-right mb-4 text-red-400">🛡️ آخر التقارير الأمنية</h2>
            <div class="space-y-3">
                <?php if (empty($latest_security)): ?>
                <p class="text-center text-gray-400 py-4">لا توجد تقارير أمنية حديثة</p>
                <?php else: ?>
                    <?php foreach ($latest_security as $report): ?>
                    <div class="bg-slate-900 rounded-lg p-4 border-r-4 border-red-500">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-400"><?php echo date('Y-m-d', strtotime($report['report_date'] ?? $report['created_at'])); ?></span>
                            <?php echo getStatusBadge($report['status'] ?? 'published'); ?>
                        </div>
                        <h4 class="font-semibold text-white mb-2"><?php echo $report['title']; ?></h4>
                        <p class="text-xs text-gray-400 mb-2"><?php echo $report['summary'] ?? 'ملخص التقرير الأمني'; ?></p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">بواسطة: <?php echo $report['generated_by_name'] ?? 'النظام'; ?></span>
                            <button onclick="viewReport(<?php echo $report['id']; ?>)" class="text-xs text-blue-400 hover:text-blue-300">عرض التفاصيل</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- أنواع التقارير -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-green-400">📋 أنواع التقارير</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <button onclick="generateReportType('security')" class="bg-slate-900 p-6 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all transform hover:scale-105">
                <svg class="w-16 h-16 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span class="text-lg font-semibold">تقرير الأمان</span>
                <span class="text-sm text-gray-400 mt-2">آخر تحديث: <?php echo date('Y-m-d'); ?></span>
            </button>

            <button onclick="generateReportType('performance')" class="bg-slate-900 p-6 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all transform hover:scale-105">
                <svg class="w-16 h-16 text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-lg font-semibold">تقرير الأداء</span>
                <span class="text-sm text-gray-400 mt-2">تحليل أداء الخوادم</span>
            </button>

            <button onclick="generateReportType('network')" class="bg-slate-900 p-6 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all transform hover:scale-105">
                <svg class="w-16 h-16 text-purple-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span class="text-lg font-semibold">تقرير الشبكة</span>
                <span class="text-sm text-gray-400 mt-2">تحليل حركة الشبكة</span>
            </button>

            <button onclick="generateReportType('incident')" class="bg-slate-900 p-6 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all transform hover:scale-105">
                <svg class="w-16 h-16 text-yellow-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <span class="text-lg font-semibold">تقرير الحوادث</span>
                <span class="text-sm text-gray-400 mt-2">سجل الحوادث الأمنية</span>
            </button>
        </div>
    </div>

    <!-- جدول التقارير المحفوظة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-blue-400">📋 سجل التقارير المحفوظة</h2>

        <!-- شريط البحث والتصفية -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="relative">
                    <input type="text" id="search-reports" placeholder="بحث في التقارير..." 
                           class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="type-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الأنواع</option>
                    <option value="security">أمني</option>
                    <option value="performance">أداء</option>
                    <option value="network">شبكة</option>
                    <option value="incident">حوادث</option>
                </select>
            </div>
            <div class="text-sm text-gray-400">
                إجمالي: <?php echo count($reports); ?> تقرير
            </div>
        </div>

        <!-- الجدول -->
        <div class="overflow-x-auto">
            <table class="w-full" id="reports-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">التاريخ</th>
                        <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                        <th class="px-6 py-4 text-sm font-semibold">عنوان التقرير</th>
                        <th class="px-6 py-4 text-sm font-semibold">الملخص</th>
                        <th class="px-6 py-4 text-sm font-semibold">التنبيهات</th>
                        <th class="px-6 py-4 text-sm font-semibold">التهديدات</th>
                        <th class="px-6 py-4 text-sm font-semibold">المنشئ</th>
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400">
                            لا توجد تقارير محفوظة. قم بإنشاء تقرير جديد الآن!
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors report-row" data-type="<?php echo $report['report_type']; ?>">
                            <td class="px-6 py-4">
                                <div class="font-semibold"><?php echo date('Y-m-d', strtotime($report['report_date'] ?? $report['created_at'])); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('l', strtotime($report['report_date'] ?? $report['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4"><?php echo getReportTypeBadge($report['report_type']); ?></td>
                            <td class="px-6 py-4 font-semibold text-green-400"><?php echo $report['title']; ?></td>
                            <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $report['summary'] ?? 'لا يوجد ملخص'; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-red-500 rounded-full text-xs"><?php echo $report['total_alerts'] ?? 0; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-yellow-500 rounded-full text-xs"><?php echo $report['total_threats'] ?? 0; ?></span>
                            </td>
                            <td class="px-6 py-4 text-gray-300"><?php echo $report['generated_by_name'] ?? 'النظام'; ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button onclick="viewReport(<?php echo $report['id']; ?>)" 
                                            class="text-blue-400 hover:text-blue-300 transition-all transform hover:scale-110" 
                                            title="عرض التقرير">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button onclick="downloadReport(<?php echo $report['id']; ?>)" 
                                            class="text-green-400 hover:text-green-300 transition-all transform hover:scale-110" 
                                            title="تحميل التقرير">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- نافذة عرض التقرير -->
<div id="report-view-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeReportModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400">📄 عرض التقرير</h3>
        </div>
        
        <div id="report-content" class="space-y-6">
            <!-- محتوى التقرير يتم تحميله هنا -->
        </div>
    </div>
</div>

<style>
.filter-btn {
    transition: all 0.3s ease;
}
.filter-btn.active {
    background-color: #10b981;
    color: white;
}
.filter-btn:hover:not(.active) {
    background-color: rgba(16, 185, 129, 0.2);
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.report-row {
    transition: all 0.3s ease;
}
.report-row:hover {
    background-color: rgba(30, 41, 59, 0.8);
}
</style>

<script>
// دوال JavaScript خاصة بصفحة التقارير
let currentReportId = null;

function generateNewReport() {
    showLoading();
    
    // محاكاة إنشاء تقرير جديد
    setTimeout(() => {
        hideLoading();
        showNotification('✅ تم إنشاء تقرير جديد', 'success');
        setTimeout(() => location.reload(), 1500);
    }, 2000);
}

function generateReportType(type) {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification(`جاري إنشاء تقرير ${type}...`, 'info');
        setTimeout(() => {
            showNotification(`تم إنشاء تقرير ${type} بنجاح`, 'success');
        }, 1500);
    }, 1000);
}

function exportAllReports() {
    showNotification('📊 جاري تحضير جميع التقارير للتصدير...', 'info');
    setTimeout(() => {
        showNotification('✅ تم تصدير التقارير بنجاح', 'success');
    }, 2000);
}

function viewReport(id) {
    currentReportId = id;
    showLoading();
    
    // محاكاة جلب بيانات التقرير
    setTimeout(() => {
        const reportContent = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-900 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تقرير #${id}</h4>
                    
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm text-gray-400 mb-1">إجمالي الهجمات</p>
                            <p class="text-3xl font-bold text-red-400">245</p>
                        </div>
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm text-gray-400 mb-1">تم الحظر</p>
                            <p class="text-3xl font-bold text-green-400">238</p>
                        </div>
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm text-gray-400 mb-1">نسبة الحظر</p>
                            <p class="text-3xl font-bold text-blue-400">97.1%</p>
                        </div>
                        <div class="p-4 bg-slate-800 rounded-lg">
                            <p class="text-sm text-gray-400 mb-1">وقت التشغيل</p>
                            <p class="text-3xl font-bold text-cyan-400">99.98%</p>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-slate-700">
                        <button onclick="downloadReport(${id})" class="px-6 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تحميل PDF</button>
                        <button onclick="printReport(${id})" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg">طباعة</button>
                        <button onclick="closeReportModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('report-content').innerHTML = reportContent;
        hideLoading();
        document.getElementById('report-view-modal').classList.remove('hidden');
        document.getElementById('report-view-modal').classList.add('flex');
    }, 1000);
}

function closeReportModal() {
    document.getElementById('report-view-modal').classList.add('hidden');
    document.getElementById('report-view-modal').classList.remove('flex');
}

function downloadReport(id) {
    showNotification(`📥 جاري تحميل التقرير #${id}...`, 'info');
    setTimeout(() => {
        showNotification('✅ تم تحميل التقرير بنجاح', 'success');
    }, 1500);
}

function printReport(id) {
    showNotification('🖨️ جاري تجهيز التقرير للطباعة...', 'info');
}

// البحث المباشر في الجدول
document.getElementById('search-reports')?.addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.report-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// تصفية حسب النوع
document.getElementById('type-filter')?.addEventListener('change', function(e) {
    const type = e.target.value;
    const rows = document.querySelectorAll('.report-row');
    
    rows.forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

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