<?php
// =============================================
// client-unit/pages/security.php
// صفحة الأمان والفحص
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

$client_id = $current_client['id'];

// =============================================
// جلب بيانات الأمان
// =============================================
try {
    // جلب سجلات الأمان
    $security_logs = $db->prepare("
        SELECT h.*, s.site_name, p.project_name
        FROM hosting_security_logs h
        LEFT JOIN hosting_sites s ON h.site_id = s.id
        LEFT JOIN client_projects p ON s.project_id = p.id
        WHERE p.client_id = ?
        ORDER BY h.created_at DESC
        LIMIT 50
    ");
    $security_logs->execute([$client_id]);
    $security_logs = $security_logs->fetchAll();
    
    // جلب تقارير الفحص الأمني
    $scan_reports = $db->prepare("
        SELECT r.*, p.project_name
        FROM client_reports r
        LEFT JOIN client_projects p ON r.project_id = p.id
        WHERE r.client_id = ? AND r.report_type = 'security'
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $scan_reports->execute([$client_id]);
    $scan_reports = $scan_reports->fetchAll();
    
    // جلب مشاكل SSL
    $ssl_issues = $db->prepare("
        SELECT d.*, p.project_name
        FROM client_domains d
        LEFT JOIN client_projects p ON d.project_id = p.id
        WHERE d.client_id = ? 
        AND d.ssl_status != 'active'
        AND d.expiry_date > CURDATE()
        ORDER BY d.ssl_expiry ASC
    ");
    $ssl_issues->execute([$client_id]);
    $ssl_issues = $ssl_issues->fetchAll();
    
    // إحصائيات الأمان
    $stats = [
        'total_logs' => 0,
        'critical' => 0,
        'warning' => 0,
        'info' => 0,
        'attacks' => 0,
        'malware' => 0,
        'failed_logins' => 0,
        'file_changes' => 0
    ];
    
    foreach ($security_logs as $log) {
        $stats['total_logs']++;
        
        if ($log['severity'] == 'critical') $stats['critical']++;
        elseif ($log['severity'] == 'warning') $stats['warning']++;
        elseif ($log['severity'] == 'info') $stats['info']++;
        
        if ($log['event_type'] == 'attack_detected') $stats['attacks']++;
        elseif ($log['event_type'] == 'malware_detected') $stats['malware']++;
        elseif ($log['event_type'] == 'failed_login') $stats['failed_logins']++;
        elseif ($log['event_type'] == 'file_change') $stats['file_changes']++;
    }
    
    // جلب مشاريع العميل (للفحص الجديد)
    $projects = $db->prepare("
        SELECT id, project_name 
        FROM client_projects 
        WHERE client_id = ? AND status IN ('in_progress', 'completed', 'testing')
        ORDER BY project_name
    ");
    $projects->execute([$client_id]);
    $projects = $projects->fetchAll();
    
    // جلب مواقع الاستضافة (للفحص الجديد)
    $sites = $db->prepare("
        SELECT h.id, h.site_name, p.project_name
        FROM hosting_sites h
        JOIN client_projects p ON h.project_id = p.id
        WHERE p.client_id = ? AND h.status = 'active'
        ORDER BY h.site_name
    ");
    $sites->execute([$client_id]);
    $sites = $sites->fetchAll();
    
} catch (Exception $e) {
    $security_logs = [];
    $scan_reports = [];
    $ssl_issues = [];
    $projects = [];
    $sites = [];
    $stats = [
        'total_logs' => 0, 'critical' => 0, 'warning' => 0, 'info' => 0,
        'attacks' => 0, 'malware' => 0, 'failed_logins' => 0, 'file_changes' => 0
    ];
}

// =============================================
// دالة لتنسيق نوع الحدث
// =============================================
function formatEventType($type) {
    $types = [
        'login' => 'تسجيل دخول',
        'logout' => 'تسجيل خروج',
        'failed_login' => 'محاولة دخول فاشلة',
        'file_change' => 'تغيير في الملفات',
        'permission_change' => 'تغيير الصلاحيات',
        'malware_detected' => 'اشتباه ببرمجية خبيثة',
        'attack_detected' => 'هجوم أمني'
    ];
    return $types[$type] ?? $type;
}

// =============================================
// دالة لتنسيق شدة الحدث
// =============================================
function formatSeverity($severity) {
    $severities = [
        'info' => ['bg-blue-500/20', 'text-blue-400', 'معلوماتي'],
        'warning' => ['bg-yellow-500/20', 'text-yellow-400', 'تحذير'],
        'critical' => ['bg-red-500/20', 'text-red-400', 'خطير']
    ];
    return $severities[$severity] ?? ['bg-slate-500/20', 'text-slate-400', $severity];
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
            <div class="w-16 h-16 rounded-2xl bg-red-500/20 flex items-center justify-center backdrop-blur-sm border border-red-500/30">
                <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-l from-red-400 to-rose-400 bg-clip-text text-transparent">الأمان والفحص</h1>
                <p class="text-slate-400 mt-1">مراقبة أمنية شاملة وفحص دوري للثغرات</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات الأمان -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <!-- إجمالي الأحداث -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-blue-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">إجمالي الأحداث</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['total_logs']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 group-hover:bg-blue-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- حرجة -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-red-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">حرجة</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['critical']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-500/10 group-hover:bg-red-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- هجمات -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-orange-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">هجمات</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['attacks']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-orange-500/10 group-hover:bg-orange-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- برمجيات خبيثة -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-purple-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">برمجيات خبيثة</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['malware']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 group-hover:bg-purple-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- تنبيهات SSL والمشاكل -->
<!-- ============================================= -->
<?php if (!empty($ssl_issues)): ?>
<div class="mb-8">
    <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
        <div class="flex items-start space-x-3 space-x-reverse">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-yellow-400 mb-2">تنبيهات SSL وشهادات الأمان</h3>
                <div class="space-y-2">
                    <?php foreach ($ssl_issues as $issue): ?>
                    <div class="flex items-center justify-between bg-slate-800/50 p-3 rounded-lg">
                        <div>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($issue['domain_name']); ?></span>
                            <span class="text-sm text-slate-400 mr-3">(<?php echo htmlspecialchars($issue['project_name']); ?>)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm text-yellow-400 ml-3">
                                <?php 
                                if ($issue['ssl_status'] == 'none') echo 'لا يوجد شهادة SSL';
                                elseif ($issue['ssl_status'] == 'pending') echo 'شهادة SSL قيد الانتظار';
                                elseif ($issue['ssl_status'] == 'expired') echo 'شهادة SSL منتهية';
                                ?>
                            </span>
                            <button onclick="requestSSL(<?php echo $issue['id']; ?>)" class="px-3 py-1 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-xs">
                                طلب شهادة
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- زر الفحص السريع -->
<!-- ============================================= -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-white">سجل الأمان</h2>
    <div class="flex items-center space-x-3 space-x-reverse">
        <button onclick="quickScan()" class="px-4 py-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 rounded-lg text-sm font-medium transition-all flex items-center">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            فحص سريع
        </button>
        <button onclick="openScanModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-medium transition-all flex items-center">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            فحص متقدم
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- سجل الأمان -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden mb-8">
    <!-- رأس الجدول -->
    <div class="grid grid-cols-12 gap-4 p-4 bg-slate-900/30 border-b border-slate-700 text-sm font-medium text-slate-400">
        <div class="col-span-3">الحدث</div>
        <div class="col-span-2">النوع</div>
        <div class="col-span-2">الموقع/المشروع</div>
        <div class="col-span-2">IP</div>
        <div class="col-span-2">التاريخ</div>
        <div class="col-span-1">الشدة</div>
    </div>
    
    <!-- محتوى الجدول -->
    <div class="divide-y divide-slate-700">
        <?php if (empty($security_logs)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <p class="text-slate-400">لا توجد أحداث أمنية مسجلة</p>
        </div>
        <?php else: ?>
            <?php foreach ($security_logs as $log): ?>
            <?php 
                $severity = formatSeverity($log['severity']);
                $event_type = formatEventType($log['event_type']);
            ?>
            <div class="grid grid-cols-12 gap-4 p-4 hover:bg-slate-700/50 transition-all">
                <!-- الحدث -->
                <div class="col-span-3">
                    <span class="text-white font-medium"><?php echo htmlspecialchars($log['description'] ?? $event_type); ?></span>
                </div>
                
                <!-- النوع -->
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo $event_type; ?></span>
                </div>
                
                <!-- الموقع/المشروع -->
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo htmlspecialchars($log['site_name'] ?? $log['project_name'] ?? 'عام'); ?></span>
                </div>
                
                <!-- IP -->
                <div class="col-span-2">
                    <span class="text-slate-300 font-mono text-sm"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></span>
                </div>
                
                <!-- التاريخ -->
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></span>
                </div>
                
                <!-- الشدة -->
                <div class="col-span-1">
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $severity[0]; ?> <?php echo $severity[1]; ?>">
                        <?php echo $severity[2]; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- تقارير الفحص الأمني -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
    <div class="p-4 bg-slate-900/50 border-b border-slate-700">
        <h3 class="text-lg font-semibold text-white">تقارير الفحص الأمني</h3>
    </div>
    
    <div class="divide-y divide-slate-700">
        <?php if (empty($scan_reports)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-slate-400 mb-4">لا توجد تقارير فحص أمني بعد</p>
            <button onclick="openScanModal()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium">
                إجراء أول فحص
            </button>
        </div>
        <?php else: ?>
            <?php foreach ($scan_reports as $report): ?>
            <div class="grid grid-cols-12 gap-4 p-4 hover:bg-slate-700/50 transition-all">
                <div class="col-span-4">
                    <span class="text-white font-medium"><?php echo htmlspecialchars($report['title']); ?></span>
                    <div class="text-xs text-slate-500 mt-1"><?php echo $report['report_code']; ?></div>
                </div>
                
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo htmlspecialchars($report['project_name'] ?? 'عام'); ?></span>
                </div>
                
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo date('Y-m-d', strtotime($report['created_at'])); ?></span>
                </div>
                
                <div class="col-span-2">
                    <?php
                    $status_colors = [
                        'generating' => 'bg-yellow-500/20 text-yellow-400',
                        'ready' => 'bg-green-500/20 text-green-400',
                        'sent' => 'bg-blue-500/20 text-blue-400'
                    ];
                    $status_texts = [
                        'generating' => 'قيد الإنشاء',
                        'ready' => 'جاهز',
                        'sent' => 'مرسل'
                    ];
                    $color = $status_colors[$report['status']] ?? 'bg-slate-500/20 text-slate-400';
                    $text = $status_texts[$report['status']] ?? $report['status'];
                    ?>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $color; ?>">
                        <?php echo $text; ?>
                    </span>
                </div>
                
                <div class="col-span-2 flex items-center justify-end space-x-2 space-x-reverse">
                    <?php if ($report['status'] == 'ready'): ?>
                    <button onclick="viewReport(<?php echo $report['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="عرض">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
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
<!-- نافذة الفحص المتقدم -->
<!-- ============================================= -->
<div id="scan-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeScanModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">فحص أمني متقدم</h3>
        </div>
        
        <form id="scan-form" onsubmit="handleScanRequest(event)" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">نوع الفحص</label>
                <select id="scan-type" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-red-500 text-white">
                    <option value="quick">فحص سريع (5-10 دقائق)</option>
                    <option value="full">فحص شامل (30-60 دقيقة)</option>
                    <option value="vulnerability">فحص الثغرات الأمنية</option>
                    <option value="malware">فحص البرمجيات الخبيثة</option>
                    <option value="penetration">اختبار الاختراق</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">نطاق الفحص</label>
                <select id="scan-target" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-red-500 text-white">
                    <option value="all">جميع المشاريع والمواقع</option>
                    <optgroup label="المشاريع">
                        <?php foreach ($projects as $project): ?>
                        <option value="project_<?php echo $project['id']; ?>">مشروع: <?php echo htmlspecialchars($project['project_name']); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="مواقع الاستضافة">
                        <?php foreach ($sites as $site): ?>
                        <option value="site_<?php echo $site['id']; ?>">موقع: <?php echo htmlspecialchars($site['site_name']); ?> (<?php echo htmlspecialchars($site['project_name']); ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">عمق الفحص</label>
                <select id="scan-depth" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-red-500 text-white">
                    <option value="basic">أساسي</option>
                    <option value="standard" selected>قياسي</option>
                    <option value="deep">عميق</option>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" id="scan-ports" checked class="ml-2">
                    <span class="text-white text-sm">فحص المنافذ</span>
                </label>
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" id="scan-config" checked class="ml-2">
                    <span class="text-white text-sm">فحص الإعدادات</span>
                </label>
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" id="scan-files" checked class="ml-2">
                    <span class="text-white text-sm">فحص الملفات</span>
                </label>
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" id="scan-db" class="ml-2">
                    <span class="text-white text-sm">فحص قاعدة البيانات</span>
                </label>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">إشعار عند الانتهاء</label>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <label class="flex items-center">
                        <input type="radio" name="notify" value="email" checked class="ml-2">
                        <span class="text-white">بريد إلكتروني</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="notify" value="sms" class="ml-2">
                        <span class="text-white">رسالة نصية</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="notify" value="both" class="ml-2">
                        <span class="text-white">كلاهما</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 rounded-lg font-medium transition-all">
                بدء الفحص
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
            <h3 class="text-2xl font-bold text-white">تقرير الفحص الأمني</h3>
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
            <button onclick="downloadCurrentReport()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm">
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

// فتح نافذة الفحص المتقدم
function openScanModal() {
    document.getElementById('scan-modal').classList.remove('hidden');
}

function closeScanModal() {
    document.getElementById('scan-modal').classList.add('hidden');
}

// فحص سريع
function quickScan() {
    showNotification('جاري إجراء الفحص السريع...', 'info');
    
    setTimeout(() => {
        showNotification('تم الفحص السريع بنجاح! لا توجد ثغرات أمنية', 'success');
    }, 2000);
}

// معالجة طلب الفحص
function handleScanRequest(event) {
    event.preventDefault();
    
    const scanType = document.getElementById('scan-type').value;
    const scanTypeText = {
        'quick': 'سريع',
        'full': 'شامل',
        'vulnerability': 'ثغرات أمنية',
        'malware': 'برمجيات خبيثة',
        'penetration': 'اختبار اختراق'
    }[scanType];
    
    showNotification(`جاري بدء الفحص ${scanTypeText}...`, 'info');
    
    setTimeout(() => {
        closeScanModal();
        showNotification('تم بدء الفحص بنجاح. سيتم إشعارك عند الانتهاء', 'success');
    }, 1500);
}

// طلب شهادة SSL
function requestSSL(domainId) {
    showNotification('جاري طلب شهادة SSL...', 'info');
    
    setTimeout(() => {
        showNotification('تم طلب الشهادة بنجاح. سيتم تفعيلها خلال 24 ساعة', 'success');
    }, 1500);
}

// عرض التقرير
function viewReport(id) {
    currentReportId = id;
    
    document.getElementById('report-preview').innerHTML = `
        <div class="space-y-6">
            <div class="text-center border-b border-slate-700 pb-4">
                <h2 class="text-2xl font-bold text-white">تقرير الفحص الأمني الشامل</h2>
                <p class="text-slate-400">تاريخ الفحص: 2024-01-15</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-red-500/10 p-4 rounded-lg border border-red-500/30">
                    <p class="text-sm text-red-400 mb-1">ثغرات حرجة</p>
                    <p class="text-3xl font-bold text-white">2</p>
                </div>
                <div class="bg-yellow-500/10 p-4 rounded-lg border border-yellow-500/30">
                    <p class="text-sm text-yellow-400 mb-1">ثغرات متوسطة</p>
                    <p class="text-3xl font-bold text-white">5</p>
                </div>
                <div class="bg-blue-500/10 p-4 rounded-lg border border-blue-500/30">
                    <p class="text-sm text-blue-400 mb-1">ثغرات منخفضة</p>
                    <p class="text-3xl font-bold text-white">12</p>
                </div>
                <div class="bg-green-500/10 p-4 rounded-lg border border-green-500/30">
                    <p class="text-sm text-green-400 mb-1">فحوصات آمنة</p>
                    <p class="text-3xl font-bold text-white">156</p>
                </div>
            </div>
            
            <div class="border-t border-slate-700 pt-4">
                <h3 class="font-bold text-white mb-3">الثغرات المكتشفة</h3>
                <div class="space-y-2">
                    <div class="bg-slate-800 p-3 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-red-400 font-medium">حرجة</span>
                            <span class="text-white mr-3">SQL Injection في صفحة تسجيل الدخول</span>
                        </div>
                        <button class="text-sm text-blue-400 hover:text-blue-300">تفاصيل</button>
                    </div>
                    <div class="bg-slate-800 p-3 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-red-400 font-medium">حرجة</span>
                            <span class="text-white mr-3">ثغرة XSS في نموذج البحث</span>
                        </div>
                        <button class="text-sm text-blue-400 hover:text-blue-300">تفاصيل</button>
                    </div>
                    <div class="bg-slate-800 p-3 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-yellow-400 font-medium">متوسطة</span>
                            <span class="text-white mr-3">نسخة PHP قديمة (7.4) تحتاج تحديث</span>
                        </div>
                        <button class="text-sm text-blue-400 hover:text-blue-300">تفاصيل</button>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-slate-700 pt-4">
                <h3 class="font-bold text-white mb-3">التوصيات</h3>
                <ul class="list-disc list-inside space-y-1 text-slate-300">
                    <li>تحديث PHP إلى الإصدار 8.2</li>
                    <li>تفعيل Web Application Firewall</li>
                    <li>تغيير كلمات المرور الافتراضية</li>
                    <li>تفعيل Two-Factor Authentication</li>
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
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white font-medium z-50 animate-fade-in-up`;
    
    if (type === 'success') notification.classList.add('bg-green-600');
    else if (type === 'error') notification.classList.add('bg-red-600');
    else if (type === 'warning') notification.classList.add('bg-yellow-600');
    else notification.classList.add('bg-blue-600');
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// إضافة CSS للحركات
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }
`;
document.head.appendChild(style);
</script>