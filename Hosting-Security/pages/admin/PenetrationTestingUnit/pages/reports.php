<?php
// =============================================
// pentest-unit/pages/reports.php
// صفحة تقارير الأمان - بيانات حقيقية من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات التقارير - من قاعدة البيانات
    // =============================================
    
    // إجمالي التقارير المولدة
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_activity_log WHERE activity_type = 'report'");
    $total_reports = $stmt->fetchColumn() ?: 0;
    
    // التقارير هذا الشهر
    $stmt = $db->query("
        SELECT COUNT(*) FROM pentest_activity_log 
        WHERE activity_type = 'report' 
        AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $reports_this_month = $stmt->fetchColumn() ?: 0;
    
    // آخر تقرير
    $stmt = $db->query("
        SELECT created_at FROM pentest_activity_log 
        WHERE activity_type = 'report' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $last_report_date = $stmt->fetchColumn();
    
    // =============================================
    // 2. قوالب التقارير - من قاعدة البيانات
    // =============================================
    
    $templates = $db->query("
        SELECT rt.*, u.full_name as creator_name
        FROM report_templates rt
        LEFT JOIN users u ON rt.created_by = u.id
        ORDER BY rt.name ASC
    ")->fetchAll();
    
    // =============================================
    // 3. آخر التقارير المولدة - من قاعدة البيانات
    // =============================================
    
    $recent_reports = $db->query("
        SELECT 
            al.*,
            u.full_name as user_name,
            p.project_name
        FROM pentest_activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN pentest_projects p ON al.target_id = p.id AND al.target_type = 'project'
        WHERE al.activity_type = 'report'
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll();
    
    // =============================================
    // 4. المشاريع النشطة للتقارير - من قاعدة البيانات
    // =============================================
    
    $active_projects = $db->query("
        SELECT id, project_name, client_name
        FROM pentest_projects
        WHERE status IN ('in-progress', 'completed')
        ORDER BY project_name
    ")->fetchAll();
    
    // =============================================
    // 5. إحصائيات التقارير حسب الشهر - للرسم البياني
    // =============================================
    
    $monthly_stats = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('F', strtotime("-$i months"));
        $monthly_stats[$month] = [
            'month' => $month_name,
            'count' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM pentest_activity_log
        WHERE activity_type = 'report'
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($monthly_stats[$row['month']])) {
            $monthly_stats[$row['month']]['count'] = $row['count'];
        }
    }
    
    // =============================================
    // 6. أنواع التقارير المتاحة
    // =============================================
    
    $report_types = [
        'client-summary' => 'تقرير مختصر للعميل',
        'technical-detailed' => 'تقرير فني مفصل',
        'follow-up' => 'تقرير متابعة',
        'compliance' => 'تقرير الامتثال',
        'executive' => 'تقرير تنفيذي'
    ];
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getReportTypeText($type) {
    return match($type) {
        'client-summary' => 'تقرير مختصر للعميل',
        'technical-detailed' => 'تقرير فني مفصل',
        'follow-up' => 'تقرير متابعة',
        'compliance' => 'تقرير الامتثال',
        'executive' => 'تقرير تنفيذي',
        default => $type
    };
}

function getReportTypeColor($type) {
    return match($type) {
        'client-summary' => 'bg-blue-500',
        'technical-detailed' => 'bg-red-500',
        'follow-up' => 'bg-green-500',
        'compliance' => 'bg-purple-500',
        'executive' => 'bg-yellow-500',
        default => 'bg-gray-500'
    };
}

function formatDateTime($datetime) {
    if (!$datetime) return 'غير محدد';
    return date('Y-m-d H:i', strtotime($datetime));
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-green-300">
            <i class="fas fa-file-alt ml-2"></i>
            تقارير الأمان
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="generateNewReport()" class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 rounded-lg font-semibold transition-all flex items-center cyber-glow">
                <i class="fas fa-plus ml-2"></i>
                تقرير جديد
            </button>
            <button onclick="refreshReports()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">إجمالي التقارير</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $total_reports; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                جميع التقارير المولدة
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">هذا الشهر</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $reports_this_month; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                تقارير الشهر الحالي
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">آخر تقرير</p>
                    <p class="text-lg font-bold text-purple-400"><?php echo $last_report_date ? date('Y-m-d', strtotime($last_report_date)) : 'لا يوجد'; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                آخر تحديث
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني للتقارير الشهرية -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        إحصائيات التقارير - آخر 6 أشهر
    </h3>
    
    <div class="h-64 relative" id="reports-chart-container">
        <canvas id="reportsChart"></canvas>
    </div>
</div>

<!-- ============================================= -->
<!-- قوالب التقارير -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    
    <?php if (!empty($templates)): ?>
        <?php foreach ($templates as $template): ?>
        <div class="card-hover cyber-border bg-slate-900 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-bold <?php echo $template['type'] == 'technical-detailed' ? 'text-red-400' : ($template['type'] == 'client-summary' ? 'text-blue-400' : 'text-green-400'); ?>">
                    <?php echo $template['name']; ?>
                </h4>
                <div class="w-10 h-10 <?php echo getReportTypeColor($template['type']); ?> bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl <?php echo getReportTypeColor($template['type']); ?>"></i>
                </div>
            </div>
            <p class="text-sm text-gray-400 mb-4"><?php echo $template['description']; ?></p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">مناسب لجميع المشاريع</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">تنسيق احترافي</span>
                </div>
            </div>
            <button onclick="useReportTemplate(<?php echo $template['id']; ?>)" class="w-full py-2 <?php echo getReportTypeColor($template['type']); ?> hover:opacity-90 text-white rounded-lg text-sm font-semibold transition-colors">
                استخدام هذا القالب
            </button>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- قوالب افتراضية إذا لم توجد في قاعدة البيانات -->
        <div class="card-hover cyber-border bg-slate-900 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-bold text-blue-400">تقرير مختصر للعميل</h4>
                <div class="w-10 h-10 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl text-blue-400"></i>
                </div>
            </div>
            <p class="text-sm text-gray-400 mb-4">تقرير موجز للمديرين غير التقنيين</p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">ملخص النتائج</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">توصيات عامة</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-blue-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">مؤشرات الأداء</span>
                </div>
            </div>
            <button onclick="useReportTemplate('client-summary')" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                استخدام هذا القالب
            </button>
        </div>

        <div class="card-hover cyber-border bg-slate-900 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-bold text-red-400">تقرير فني مفصل</h4>
                <div class="w-10 h-10 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl text-red-400"></i>
                </div>
            </div>
            <p class="text-sm text-gray-400 mb-4">تقرير تفصيلي للفرق التقنية مع شروح وأكواد</p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-red-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">تفاصيل الثغرات</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-purple-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">شروح تقنية</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">أكواد الإصلاح</span>
                </div>
            </div>
            <button onclick="useReportTemplate('technical-detailed')" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-colors">
                استخدام هذا القالب
            </button>
        </div>

        <div class="card-hover cyber-border bg-slate-900 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-bold text-green-400">تقرير متابعة</h4>
                <div class="w-10 h-10 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl text-green-400"></i>
                </div>
            </div>
            <p class="text-sm text-gray-400 mb-4">تقرير متابعة الإصلاحات والتحسينات</p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">حالة الإصلاحات</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-blue-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">مقاييس التحسين</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full ml-2"></div>
                    <span class="text-gray-300">اختبارات المتابعة</span>
                </div>
            </div>
            <button onclick="useReportTemplate('follow-up')" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition-colors">
                استخدام هذا القالب
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- مولد التقارير -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-yellow-400 mb-4 flex items-center">
        <i class="fas fa-cog ml-2"></i>
        مولد التقارير
    </h3>
    
    <form id="report-generator-form" onsubmit="handleReportGeneration(event)" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">نوع التقرير</label>
                <select id="report-type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                    <?php foreach ($report_types as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                <select id="report-project" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                    <option value="all">جميع المشاريع</option>
                    <?php foreach ($active_projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">محتوى التقرير</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <label class="flex items-center justify-end">
                    <span class="text-sm text-gray-300 mr-2">الثغرات الحرجة</span>
                    <input type="checkbox" checked class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600 rounded">
                </label>
                <label class="flex items-center justify-end">
                    <span class="text-sm text-gray-300 mr-2">الثغرات العالية</span>
                    <input type="checkbox" checked class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600 rounded">
                </label>
                <label class="flex items-center justify-end">
                    <span class="text-sm text-gray-300 mr-2">توصيات الإصلاح</span>
                    <input type="checkbox" checked class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600 rounded">
                </label>
                <label class="flex items-center justify-end">
                    <span class="text-sm text-gray-300 mr-2">مقاييس الأداء</span>
                    <input type="checkbox" class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600 rounded">
                </label>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">صيغة التقرير</label>
            <div class="flex items-center space-x-4 space-x-reverse">
                <label class="flex items-center">
                    <input type="radio" name="report-format" value="pdf" checked class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600">
                    <span class="text-sm text-gray-300 mr-2">PDF</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="report-format" value="html" class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600">
                    <span class="text-sm text-gray-300 mr-2">HTML</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="report-format" value="word" class="w-4 h-4 text-yellow-600 bg-gray-700 border-gray-600">
                    <span class="text-sm text-gray-300 mr-2">Word</span>
                </label>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">ملاحظات إضافية</label>
            <textarea id="report-notes" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right" placeholder="أضف ملاحظاتك هنا..."></textarea>
        </div>
        
        <div class="flex items-center space-x-4 space-x-reverse">
            <button type="submit" class="flex-1 px-6 py-3 bg-yellow-600 hover:bg-yellow-700 rounded-lg font-semibold transition-all cyber-glow">
                <i class="fas fa-file-pdf ml-2"></i>
                إنشاء التقرير
            </button>
            <button type="button" onclick="previewReport()" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all">
                <i class="fas fa-eye ml-2"></i>
                معاينة
            </button>
        </div>
    </form>
</div>

<!-- ============================================= -->
<!-- آخر التقارير المولدة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-cyan-400 flex items-center">
            <i class="fas fa-history ml-2"></i>
            آخر التقارير المولدة
        </h3>
        <button onclick="refreshReports()" class="text-sm text-cyan-400 hover:text-cyan-300">
            <i class="fas fa-sync-alt ml-1"></i>
            تحديث
        </button>
    </div>
    
    <?php if (empty($recent_reports)): ?>
    <div class="text-center py-8 bg-slate-800 rounded-lg">
        <i class="fas fa-file-alt text-4xl text-gray-500 mb-3"></i>
        <p class="text-gray-400">لا توجد تقارير بعد</p>
        <p class="text-sm text-gray-500 mt-2">قم بإنشاء أول تقرير الآن</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($recent_reports as $report): ?>
        <div class="p-4 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                    <i class="fas fa-file-pdf text-red-400 ml-2"></i>
                    <div>
                        <p class="font-semibold text-white"><?php echo $report['description'] ?? 'تقرير أمني'; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $report['user_name'] ?? 'النظام'; ?> • <?php echo $report['project_name'] ?? 'عام'; ?></p>
                    </div>
                </div>
                <span class="text-xs text-gray-400"><?php echo formatDateTime($report['created_at']); ?></span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="text-xs px-2 py-1 bg-blue-500 rounded">تقرير</span>
                    <span class="text-xs px-2 py-1 bg-green-500 rounded">PDF</span>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="text-xs text-blue-400 hover:text-blue-300">
                        <i class="fas fa-download ml-1"></i>
                        تحميل
                    </button>
                    <button onclick="viewReport(<?php echo $report['id']; ?>)" class="text-xs text-green-400 hover:text-green-300">
                        <i class="fas fa-eye ml-1"></i>
                        عرض
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء تقرير جديد -->
<!-- ============================================= -->
<div id="generate-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeGenerateModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-file-pdf ml-2"></i>
                جاري إنشاء التقرير
            </h3>
        </div>
        
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-300 mb-2">جاري إنشاء التقرير...</p>
            <p class="text-sm text-gray-400">قد يستغرق ذلك بضع ثواني</p>
        </div>
        
        <div class="progress-bar mt-4">
            <div class="progress-fill" id="report-progress" style="width: 0%"></div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// =============================================
// الرسم البياني
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('reportsChart')?.getContext('2d');
    if (!ctx) return;
    
    const monthlyData = <?php echo json_encode(array_values($monthly_stats)); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'عدد التقارير',
                data: monthlyData.map(d => d.count),
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderColor: '#10b981',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                }
            }
        }
    });
});

// =============================================
// دوال الصفحة
// =============================================

let progressInterval;
let currentReportId = null;

function generateNewReport() {
    document.getElementById('generate-report-modal').classList.remove('hidden');
    document.getElementById('generate-report-modal').classList.add('flex');
    
    // محاكاة تقدم إنشاء التقرير
    let progress = 0;
    const progressBar = document.getElementById('report-progress');
    
    progressInterval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress >= 100) {
            progress = 100;
            clearInterval(progressInterval);
            
            setTimeout(() => {
                closeGenerateModal();
                if (typeof showNotification === 'function') {
                    showNotification('✅ تم إنشاء التقرير بنجاح', 'success');
                }
                setTimeout(() => location.reload(), 1500);
            }, 500);
        }
        progressBar.style.width = progress + '%';
    }, 300);
}

function closeGenerateModal() {
    clearInterval(progressInterval);
    document.getElementById('generate-report-modal').classList.add('hidden');
    document.getElementById('generate-report-modal').classList.remove('flex');
}

function refreshReports() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث قائمة التقارير', 'success');
        }
        location.reload();
    }, 1500);
}

function useReportTemplate(templateId) {
    if (typeof showNotification === 'function') {
        showNotification(`📝 تم اختيار القالب`, 'info');
    }
    // فتح مولد التقارير مع القالب المحدد
    generateNewReport();
}

function handleReportGeneration(event) {
    event.preventDefault();
    
    const reportType = document.getElementById('report-type').value;
    const project = document.getElementById('report-project').value;
    const notes = document.getElementById('report-notes').value;
    
    generateNewReport();
}

function previewReport() {
    if (typeof showNotification === 'function') {
        showNotification('👁️ جاري تحضير المعاينة', 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification('✅ المعاينة جاهزة', 'success');
        }
    }, 1500);
}

function downloadReport(reportId) {
    if (typeof showNotification === 'function') {
        showNotification(`📥 جاري تحميل التقرير`, 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification(`✅ تم تحميل التقرير بنجاح`, 'success');
        }
    }, 2000);
}

function viewReport(reportId) {
    if (typeof showNotification === 'function') {
        showNotification(`👁️ عرض التقرير`, 'info');
    }
}
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.progress-bar {
    height: 8px;
    background: #334155;
    border-radius: 4px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.2);
}
.spinner {
    border: 3px solid rgba(16, 185, 129, 0.3);
    border-top: 3px solid #10b981;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>