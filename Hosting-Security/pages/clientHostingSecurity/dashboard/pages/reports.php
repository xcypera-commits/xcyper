<?php
// =============================================
// client-unit/pages/reports.php
// صفحة التقارير والنتائج
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: العميل غير محدد</div>';
    return;
}

// =============================================
// جلب البيانات
// =============================================
try {
    // التقارير
    $reports = $db->prepare("
        SELECT r.*, p.project_name
        FROM client_reports r
        LEFT JOIN client_projects p ON r.project_id = p.id
        WHERE r.client_id = ?
        ORDER BY r.created_at DESC
    ");
    $reports->execute([$current_client['id']]);
    $reports = $reports->fetchAll();
    
    // إحصائيات التقارير
    $stats = [
        'total' => count($reports),
        'ready' => 0,
        'generating' => 0,
        'sent' => 0
    ];
    
    foreach ($reports as $report) {
        if ($report['status'] == 'ready') $stats['ready']++;
        elseif ($report['status'] == 'generating') $stats['generating']++;
        elseif ($report['status'] == 'sent') $stats['sent']++;
    }
    
    // المشاريع النشطة (للتقارير)
    $projects = $db->prepare("
        SELECT id, project_name 
        FROM client_projects 
        WHERE client_id = ? AND status IN ('completed', 'in_progress')
        ORDER BY project_name
    ");
    $projects->execute([$current_client['id']]);
    $projects = $projects->fetchAll();
    
} catch (Exception $e) {
    $reports = [];
    $projects = [];
    $stats = [
        'total' => 0,
        'ready' => 0,
        'generating' => 0,
        'sent' => 0
    ];
}
?>

<!-- ============================================= -->
<!-- الهيدر -->
<!-- ============================================= -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 mb-8 border border-slate-700">
    <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 to-transparent"></div>
    
    <div class="relative z-10">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/20 flex items-center justify-center backdrop-blur-sm border border-amber-500/30">
                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-l from-amber-400 to-orange-400 bg-clip-text text-transparent">التقارير والنتائج</h1>
                <p class="text-slate-400 mt-1">تقارير شاملة عن مشاريعك ونتائج الفحوصات</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-amber-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">إجمالي التقارير</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['total']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 group-hover:bg-amber-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-green-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">جاهزة</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['ready']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-500/10 group-hover:bg-green-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-yellow-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">قيد الإنشاء</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['generating']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-yellow-500/10 group-hover:bg-yellow-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-blue-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">مرسلة</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['sent']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 group-hover:bg-blue-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة التقارير -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
    <div class="p-4 bg-slate-900/50 border-b border-slate-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-white">التقارير المتاحة</h3>
        <button onclick="requestNewReport()" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 rounded-lg text-sm font-medium transition-all flex items-center">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            طلب تقرير جديد
        </button>
    </div>
    
    <!-- رأس الجدول -->
    <div class="grid grid-cols-12 gap-4 p-4 bg-slate-900/30 border-b border-slate-700 text-sm font-medium text-slate-400">
        <div class="col-span-3">عنوان التقرير</div>
        <div class="col-span-2">النوع</div>
        <div class="col-span-2">المشروع</div>
        <div class="col-span-2">تاريخ الإنشاء</div>
        <div class="col-span-2">الحالة</div>
        <div class="col-span-1"></div>
    </div>
    
    <!-- محتوى الجدول -->
    <div class="divide-y divide-slate-700">
        <?php if (empty($reports)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-slate-400 mb-4">لا توجد تقارير بعد</p>
            <button onclick="requestNewReport()" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 rounded-lg text-sm font-medium">
                طلب أول تقرير
            </button>
        </div>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
            <div class="grid grid-cols-12 gap-4 p-4 hover:bg-slate-700/50 transition-all">
                <!-- عنوان التقرير -->
                <div class="col-span-3">
                    <span class="text-white font-medium"><?php echo htmlspecialchars($report['title']); ?></span>
                    <div class="text-xs text-slate-500 mt-1"><?php echo $report['report_code']; ?></div>
                </div>
                
                <!-- النوع -->
                <div class="col-span-2">
                    <?php
                    $type_texts = [
                        'progress' => 'تقرير تقدم',
                        'security' => 'تقرير أمني',
                        'performance' => 'تقرير أداء',
                        'backup' => 'تقرير نسخ احتياطي',
                        'audit' => 'تقرير تدقيق',
                        'summary' => 'تقرير ملخص'
                    ];
                    $type = $type_texts[$report['report_type']] ?? $report['report_type'];
                    ?>
                    <span class="text-slate-300"><?php echo $type; ?></span>
                </div>
                
                <!-- المشروع -->
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo $report['project_name'] ?? 'عام'; ?></span>
                </div>
                
                <!-- تاريخ الإنشاء -->
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo date('Y-m-d', strtotime($report['created_at'])); ?></span>
                </div>
                
                <!-- الحالة -->
                <div class="col-span-2">
                    <?php
                    $status_colors = [
                        'generating' => 'bg-yellow-500/20 text-yellow-400',
                        'ready' => 'bg-green-500/20 text-green-400',
                        'sent' => 'bg-blue-500/20 text-blue-400',
                        'archived' => 'bg-slate-500/20 text-slate-400'
                    ];
                    
                    $status_texts = [
                        'generating' => 'قيد الإنشاء',
                        'ready' => 'جاهز',
                        'sent' => 'مرسل',
                        'archived' => 'مؤرشف'
                    ];
                    
                    $color = $status_colors[$report['status']] ?? 'bg-slate-500/20 text-slate-400';
                    $text = $status_texts[$report['status']] ?? $report['status'];
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $color; ?>">
                        <?php echo $text; ?>
                    </span>
                </div>
                
                <!-- الإجراءات -->
                <div class="col-span-1 flex items-center justify-end space-x-2 space-x-reverse">
                    <?php if ($report['status'] == 'ready'): ?>
                    <button onclick="viewReport(<?php echo $report['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="عرض">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    
                    <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="تحميل">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- أنواع التقارير المتاحة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-blue-500/50 transition-all group cursor-pointer" onclick="requestReportType('progress')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 group-hover:bg-blue-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <span class="text-xs px-2 py-1 bg-blue-500/20 text-blue-400 rounded-full">شهري</span>
        </div>
        <h3 class="text-lg font-semibold text-white mb-1">تقرير التقدم</h3>
        <p class="text-sm text-slate-400">متابعة تقدم المشاريع والإنجازات</p>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-red-500/50 transition-all group cursor-pointer" onclick="requestReportType('security')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-red-500/10 group-hover:bg-red-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <span class="text-xs px-2 py-1 bg-red-500/20 text-red-400 rounded-full">أمني</span>
        </div>
        <h3 class="text-lg font-semibold text-white mb-1">تقرير أمني</h3>
        <p class="text-sm text-slate-400">نتائج الفحوصات الأمنية والثغرات</p>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-green-500/50 transition-all group cursor-pointer" onclick="requestReportType('performance')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-green-500/10 group-hover:bg-green-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-xs px-2 py-1 bg-green-500/20 text-green-400 rounded-full">أداء</span>
        </div>
        <h3 class="text-lg font-semibold text-white mb-1">تقرير أداء</h3>
        <p class="text-sm text-slate-400">تحليل أداء الخوادم والتطبيقات</p>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-purple-500/50 transition-all group cursor-pointer" onclick="requestReportType('backup')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 group-hover:bg-purple-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <span class="text-xs px-2 py-1 bg-purple-500/20 text-purple-400 rounded-full">نسخ احتياطي</span>
        </div>
        <h3 class="text-lg font-semibold text-white mb-1">تقرير النسخ الاحتياطي</h3>
        <p class="text-sm text-slate-400">حالة النسخ الاحتياطي واستعادتها</p>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة طلب تقرير جديد -->
<!-- ============================================= -->
<div id="request-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeRequestModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">طلب تقرير جديد</h3>
        </div>
        
        <form id="request-report-form" onsubmit="handleRequestReport(event)" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">نوع التقرير</label>
                <select id="report-type" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-amber-500 text-white">
                    <option value="progress">تقرير تقدم</option>
                    <option value="security">تقرير أمني</option>
                    <option value="performance">تقرير أداء</option>
                    <option value="backup">تقرير نسخ احتياطي</option>
                    <option value="audit">تقرير تدقيق</option>
                    <option value="summary">تقرير ملخص</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">المشروع</label>
                <select id="report-project" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-amber-500 text-white">
                    <option value="">جميع المشاريع</option>
                    <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">الفترة</label>
                <select id="report-period" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-amber-500 text-white">
                    <option value="weekly">آخر أسبوع</option>
                    <option value="monthly">آخر شهر</option>
                    <option value="quarterly">آخر 3 أشهر</option>
                    <option value="yearly">آخر سنة</option>
                    <option value="custom">مخصص</option>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4" id="custom-dates" style="display: none;">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">من تاريخ</label>
                    <input type="date" id="date-from" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-amber-500 text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">إلى تاريخ</label>
                    <input type="date" id="date-to" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-amber-500 text-white">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">تنسيق التقرير</label>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <label class="flex items-center">
                        <input type="radio" name="format" value="pdf" checked class="ml-2">
                        <span class="text-white">PDF</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="excel" class="ml-2">
                        <span class="text-white">Excel</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="html" class="ml-2">
                        <span class="text-white">HTML</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 rounded-lg font-medium transition-all">
                طلب التقرير
            </button>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة عرض التقرير -->
<!-- ============================================= -->
<div id="view-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-slate-700">
        <div class="flex items-center justify-between mb-6 sticky top-0 bg-slate-800 py-2">
            <button onclick="closeViewModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">معاينة التقرير</h3>
        </div>
        
        <div id="report-preview" class="bg-slate-900 rounded-lg p-6">
            <!-- محتوى التقرير سيتم تحميله عبر JavaScript -->
            <div class="text-center text-slate-400 py-12">
                جاري تحميل التقرير...
            </div>
        </div>
        
        <div class="flex items-center justify-end space-x-3 space-x-reverse mt-6">
            <button onclick="closeViewModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm">
                إغلاق
            </button>
            <button onclick="downloadCurrentReport()" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 rounded-lg text-sm">
                تحميل التقرير
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
let currentReportId = null;

// إظهار/إخفاء التواريخ المخصصة
document.getElementById('report-period')?.addEventListener('change', function() {
    const customDates = document.getElementById('custom-dates');
    if (this.value === 'custom') {
        customDates.style.display = 'grid';
    } else {
        customDates.style.display = 'none';
    }
});

// طلب تقرير جديد
function requestNewReport() {
    document.getElementById('request-report-modal').classList.remove('hidden');
}

function requestReportType(type) {
    document.getElementById('report-type').value = type;
    requestNewReport();
}

function closeRequestModal() {
    document.getElementById('request-report-modal').classList.add('hidden');
    document.getElementById('request-report-form').reset();
    document.getElementById('custom-dates').style.display = 'none';
}

function handleRequestReport(event) {
    event.preventDefault();
    
    showNotification('جاري إرسال طلب التقرير...', 'info');
    
    setTimeout(() => {
        closeRequestModal();
        showNotification('تم إرسال الطلب بنجاح', 'success');
    }, 1500);
}

// عرض التقرير
function viewReport(id) {
    currentReportId = id;
    
    // محاكاة تحميل التقرير
    document.getElementById('report-preview').innerHTML = `
        <div class="space-y-6">
            <div class="text-center border-b border-slate-700 pb-4">
                <h2 class="text-2xl font-bold text-white">تقرير تقدم المشروع</h2>
                <p class="text-slate-400">الفترة: 2024-01-01 إلى 2024-01-31</p>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-slate-800 p-4 rounded-lg text-center">
                    <p class="text-3xl font-bold text-blue-400">65%</p>
                    <p class="text-sm text-slate-400">تقدم المشروع</p>
                </div>
                <div class="bg-slate-800 p-4 rounded-lg text-center">
                    <p class="text-3xl font-bold text-green-400">124</p>
                    <p class="text-sm text-slate-400">مهمة مكتملة</p>
                </div>
                <div class="bg-slate-800 p-4 rounded-lg text-center">
                    <p class="text-3xl font-bold text-amber-400">8</p>
                    <p class="text-sm text-slate-400">متبقي</p>
                </div>
            </div>
            
            <div class="border-t border-slate-700 pt-4">
                <h3 class="font-bold text-white mb-3">ملخص التقرير</h3>
                <p class="text-slate-300 leading-relaxed">
                    تم خلال هذه الفترة إنجاز 65% من المشروع. تم الانتهاء من مرحلة التصميم وبدء مرحلة التطوير.
                    تم رفع 124 مهمة من أصل 132 مهمة. العمل جاري على استكمال المهام المتبقية.
                </p>
            </div>
            
            <div class="border-t border-slate-700 pt-4">
                <h3 class="font-bold text-white mb-3">الإنجازات</h3>
                <ul class="list-disc list-inside space-y-1 text-slate-300">
                    <li>اكتمال تصميم واجهات المستخدم</li>
                    <li>تطوير 80% من واجهات API</li>
                    <li>اختبار وتدقيق قاعدة البيانات</li>
                    <li>تجهيز بيئة الاختبار</li>
                </ul>
            </div>
        </div>
    `;
    
    document.getElementById('view-report-modal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('view-report-modal').classList.add('hidden');
}

function downloadReport(id) {
        showNotification('جاري تحميل التقرير...', 'info');
    
    setTimeout(() => {
        showNotification('تم تحميل التقرير بنجاح', 'success');
    }, 1500);
}

function downloadCurrentReport() {
    if (currentReportId) {
        downloadReport(currentReportId);
    }
}

// دالة الإشعارات
function showNotification(message, type = 'info') {
    // إنشاء عنصر الإشعار
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white font-medium z-50 animate-fade-in-up`;
    
    // تحديد الألوان حسب نوع الإشعار
    if (type === 'success') {
        notification.classList.add('bg-green-600');
    } else if (type === 'error') {
        notification.classList.add('bg-red-600');
    } else if (type === 'warning') {
        notification.classList.add('bg-yellow-600');
    } else {
        notification.classList.add('bg-blue-600');
    }
    
    notification.textContent = message;
    
    // إضافة الإشعار للصفحة
    document.body.appendChild(notification);
    
    // إزالة الإشعار بعد 3 ثواني
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// إضافة CSS للحركات
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }
`;
document.head.appendChild(style);
</script>
