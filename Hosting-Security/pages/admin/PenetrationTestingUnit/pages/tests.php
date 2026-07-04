<?php
// =============================================
// pentest-unit/pages/tests.php
// صفحة اختبارات الأمان
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$filter = $_GET['filter'] ?? 'all';

try {
    // =============================================
    // 1. إحصائيات سريعة
    // =============================================
    
    // إجمالي الفحوصات
    $stmt = $db->query("SELECT COUNT(*) FROM security_scans");
    $total_scans = $stmt->fetchColumn() ?: 0;
    
    // فحوصات قيد التنفيذ
    $stmt = $db->query("SELECT COUNT(*) FROM security_scans WHERE status = 'in-progress'");
    $in_progress_scans = $stmt->fetchColumn() ?: 0;
    
    // فحوصات مكتملة
    $stmt = $db->query("SELECT COUNT(*) FROM security_scans WHERE status = 'completed'");
    $completed_scans = $stmt->fetchColumn() ?: 0;
    
    // إجمالي الثغرات المكتشفة
    $stmt = $db->query("SELECT SUM(findings_count) FROM security_scans WHERE status = 'completed'");
    $total_findings = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. جميع الفحوصات مع التفاصيل
    // =============================================
    
    $scans = $db->query("
        SELECT 
            s.*,
            p.project_name,
            p.client_name,
            t.name as tool_name,
            u.full_name as performer_name,
            (SELECT COUNT(*) FROM vulnerabilities WHERE id = s.id) as vulns_found
        FROM security_scans s
        LEFT JOIN pentest_projects p ON s.project_id = p.id
        LEFT JOIN testing_tools t ON s.tool_id = t.id
        LEFT JOIN users u ON s.performed_by = u.id
        ORDER BY 
            CASE s.status
                WHEN 'in-progress' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'completed' THEN 3
                ELSE 4
            END,
            s.created_at DESC
    ")->fetchAll();
    
    // =============================================
    // 3. إحصائيات الفحوصات حسب النوع
    // =============================================
    
    $scans_by_type = $db->query("
        SELECT scan_type, COUNT(*) as count,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM security_scans
        GROUP BY scan_type
    ")->fetchAll();
    
    // =============================================
    // 4. أدوات الفحص النشطة
    // =============================================
    
    $active_tools = $db->query("
        SELECT id, name, version, status
        FROM testing_tools
        WHERE status = 'active'
        ORDER BY name
    ")->fetchAll();
    
    // =============================================
    // 5. آخر 5 فحوصات مكتملة
    // =============================================
    
    $recent_completed = $db->query("
        SELECT 
            s.*,
            p.project_name,
            t.name as tool_name
        FROM security_scans s
        LEFT JOIN pentest_projects p ON s.project_id = p.id
        LEFT JOIN testing_tools t ON s.tool_id = t.id
        WHERE s.status = 'completed'
        ORDER BY s.completed_at DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getScanStatusBadge($status) {
    return match($status) {
        'pending' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">في الانتظار</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500 scanning-pulse">قيد التنفيذ</span>',
        'completed' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">مكتمل</span>',
        'failed' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">فشل</span>',
        'stopped' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">متوقف</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getScanTypeBadge($type) {
    return match($type) {
        'comprehensive' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">شامل</span>',
        'vulnerability' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">ثغرات</span>',
        'port' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منافذ</span>',
        'web' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">ويب</span>',
        'network' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">شبكة</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function formatDuration($seconds) {
    if (!$seconds) return 'لم يكتمل';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return "{$hours} ساعة {$minutes} دقيقة";
    } elseif ($minutes > 0) {
        return "{$minutes} دقيقة";
    } else {
        return "{$seconds} ثانية";
    }
}

function getScanProgress($scan) {
    if ($scan['status'] == 'completed') return 100;
    if ($scan['status'] == 'in-progress') {
        $duration = time() - strtotime($scan['started_at']);
        $estimated = 3600; // ساعة افتراضية
        return min(95, round(($duration / $estimated) * 100));
    }
    return 0;
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وأزرار التحكم -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-blue-300">
            <i class="fas fa-shield-alt ml-2"></i>
            اختبارات الأمان
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="startNewScan()" class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 rounded-lg font-semibold transition-all flex items-center cyber-glow">
                <i class="fas fa-play ml-2"></i>
                فحص جديد
            </button>
            <button onclick="refreshScans()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">إجمالي الفحوصات</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo $total_scans; ?></p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">قيد التنفيذ</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $in_progress_scans; ?></p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">مكتملة</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $completed_scans; ?></p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">ثغرات مكتشفة</p>
                <p class="text-3xl font-bold text-red-400"><?php echo $total_findings; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الفحوصات النشطة حالياً -->
<!-- ============================================= -->
<?php if ($in_progress_scans > 0): ?>
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-xl font-bold text-right text-green-400 mb-6 flex items-center">
        <i class="fas fa-sync-alt fa-spin ml-2"></i>
        الفحوصات النشطة حالياً
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($scans as $scan): ?>
            <?php if ($scan['status'] == 'in-progress'): ?>
            <div class="cyber-border bg-slate-900 rounded-lg p-6 relative overflow-hidden">
                <div class="scanning-animation absolute inset-0 pointer-events-none"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-green-400"><?php echo $scan['scan_name']; ?></h4>
                        <?php echo getScanStatusBadge($scan['status']); ?>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">النوع:</span>
                            <?php echo getScanTypeBadge($scan['scan_type']); ?>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">الهدف:</span>
                            <span class="text-blue-400"><?php echo $scan['target']; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">المشروع:</span>
                            <span class="text-yellow-400"><?php echo $scan['project_name'] ?? 'عام'; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">الأداة:</span>
                            <span class="text-purple-400"><?php echo $scan['tool_name']; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">المختبر:</span>
                            <span class="text-green-400"><?php echo $scan['performer_name'] ?? 'غير معين'; ?></span>
                        </div>
                    </div>

                    <?php $progress = getScanProgress($scan); ?>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400">التقدم</span>
                            <span class="text-xs text-green-400"><?php echo $progress; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-400 mb-4">
                        <span>بداية: <?php echo date('H:i', strtotime($scan['started_at'])); ?></span>
                        <span>الثغرات: <?php echo $scan['findings_count'] ?? 0; ?></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="pauseScan(<?php echo $scan['id']; ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                            <i class="fas fa-pause ml-1"></i>
                            إيقاف
                        </button>
                        <button onclick="viewScanDetails(<?php echo $scan['id']; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- أنواع اختبارات الأمان -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    
    <!-- Reconnaissance -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-blue-400">Reconnaissance</h4>
            <div class="w-12 h-12 bg-blue-600 bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-search text-2xl text-blue-400"></i>
            </div>
        </div>
        <p class="text-sm text-gray-400 mb-4">جمع المعلومات وتحليل النظم</p>
        <div class="space-y-2">
            <button onclick="runTest('recon', 'subdomain')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-globe ml-2 text-blue-400"></i>
                فحص النطاقات الفرعية
            </button>
            <button onclick="runTest('recon', 'dns')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-server ml-2 text-blue-400"></i>
                استعلام DNS
            </button>
            <button onclick="runTest('recon', 'whois')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-info-circle ml-2 text-blue-400"></i>
                استعلام WHOIS
            </button>
            <button onclick="runTest('recon', 'tech')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-code ml-2 text-blue-400"></i>
                كشف التقنيات
            </button>
        </div>
    </div>

    <!-- Vulnerability Scan -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-red-400">Vulnerability Scan</h4>
            <div class="w-12 h-12 bg-red-600 bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-bug text-2xl text-red-400"></i>
            </div>
        </div>
        <p class="text-sm text-gray-400 mb-4">فحص الثغرات والضعف الأمني</p>
        <div class="space-y-2">
            <button onclick="runTest('vuln', 'web')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-globe ml-2 text-red-400"></i>
                فحص ثغرات الويب
            </button>
            <button onclick="runTest('vuln', 'network')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-network-wired ml-2 text-red-400"></i>
                فحص ثغرات الشبكة
            </button>
            <button onclick="runTest('vuln', 'config')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-cogs ml-2 text-red-400"></i>
                فحص التكوينات
            </button>
            <button onclick="runTest('vuln', 'database')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-database ml-2 text-red-400"></i>
                فحص قواعد البيانات
            </button>
        </div>
    </div>

    <!-- Exploitation -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-yellow-400">Exploitation</h4>
            <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-skull text-2xl text-yellow-400"></i>
            </div>
        </div>
        <p class="text-sm text-gray-400 mb-4">استغلال الثغرات بشكل آمن</p>
        <div class="space-y-2">
            <button onclick="runTest('exploit', 'sqli')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-database ml-2 text-yellow-400"></i>
                اختبار SQL Injection
            </button>
            <button onclick="runTest('exploit', 'xss')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-code ml-2 text-yellow-400"></i>
                اختبار XSS
            </button>
            <button onclick="runTest('exploit', 'csrf')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-exchange-alt ml-2 text-yellow-400"></i>
                اختبار CSRF
            </button>
            <button onclick="runTest('exploit', 'upload')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-upload ml-2 text-yellow-400"></i>
                رفع ملفات خبيثة
            </button>
        </div>
    </div>

    <!-- Post Exploitation -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-green-400">Post-Exploitation</h4>
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-2xl text-green-400"></i>
            </div>
        </div>
        <p class="text-sm text-gray-400 mb-4">تقييم الأثر والتقرير النهائي</p>
        <div class="space-y-2">
            <button onclick="runTest('post', 'impact')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-exclamation-triangle ml-2 text-green-400"></i>
                تقرير التأثير
            </button>
            <button onclick="runTest('post', 'remediation')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-tools ml-2 text-green-400"></i>
                خطة المعالجة
            </button>
            <button onclick="runTest('post', 'report')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-file-pdf ml-2 text-green-400"></i>
                التقرير النهائي
            </button>
            <button onclick="runTest('post', 'evidence')" class="w-full text-right px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm transition-colors">
                <i class="fas fa-camera ml-2 text-green-400"></i>
                جمع الأدلة
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات الفحوصات حسب النوع -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- إحصائيات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع الفحوصات حسب النوع
        </h3>
        
        <div class="space-y-4">
            <?php 
            $type_labels = [
                'comprehensive' => 'شامل',
                'vulnerability' => 'ثغرات',
                'port' => 'منافذ',
                'web' => 'ويب',
                'network' => 'شبكة'
            ];
            foreach ($scans_by_type as $stat): 
                $percentage = $stat['count'] > 0 ? round(($stat['completed'] / $stat['count']) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $type_labels[$stat['scan_type']] ?? $stat['scan_type']; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $stat['completed']; ?>/<?php echo $stat['count']; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- آخر الفحوصات المكتملة -->
    <?php if (!empty($recent_completed)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
            <i class="fas fa-history ml-2"></i>
            آخر الفحوصات المكتملة
        </h3>
        
        <div class="space-y-3">
            <?php foreach ($recent_completed as $scan): ?>
            <div class="p-3 bg-slate-900 rounded-lg">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-semibold text-sm"><?php echo $scan['scan_name']; ?></span>
                    <?php echo getScanTypeBadge($scan['scan_type']); ?>
                </div>
                <p class="text-xs text-gray-400 mb-2"><?php echo $scan['project_name'] ?? 'فحص عام'; ?></p>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500">الثغرات: <?php echo $scan['findings_count']; ?></span>
                    <span class="text-gray-500">المدة: <?php echo formatDuration($scan['duration']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- جدول جميع الفحوصات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-right text-yellow-300">
            <i class="fas fa-table ml-2"></i>
            سجل الفحوصات
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-scans" placeholder="بحث في الفحوصات..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-yellow-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-yellow-500">
                <option value="all">كل الحالات</option>
                <option value="in-progress">قيد التنفيذ</option>
                <option value="completed">مكتمل</option>
                <option value="pending">في الانتظار</option>
            </select>
            <select id="type-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-yellow-500">
                <option value="all">كل الأنواع</option>
                <option value="comprehensive">شامل</option>
                <option value="vulnerability">ثغرات</option>
                <option value="port">منافذ</option>
                <option value="web">ويب</option>
                <option value="network">شبكة</option>
            </select>
        </div>
    </div>

    <?php if (empty($scans)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-search text-5xl text-gray-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد فحوصات</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="scans-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                    <th class="px-6 py-4 text-sm font-semibold">الثغرات</th>
                    <th class="px-6 py-4 text-sm font-semibold">المختبر</th>
                    <th class="px-6 py-4 text-sm font-semibold">الأداة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الهدف</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ البدء</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم الفحص</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scans as $scan): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors scan-row" 
                    data-status="<?php echo $scan['status']; ?>"
                    data-type="<?php echo $scan['scan_type']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewScanDetails(<?php echo $scan['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($scan['status'] == 'completed'): ?>
                            <button onclick="downloadScanReport(<?php echo $scan['id']; ?>)" class="text-green-400 hover:text-green-300" title="تحميل التقرير">
                                <i class="fas fa-download"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($scan['status'] == 'in-progress'): ?>
                            <button onclick="stopScan(<?php echo $scan['id']; ?>)" class="text-red-400 hover:text-red-300" title="إيقاف">
                                <i class="fas fa-stop"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getScanStatusBadge($scan['status']); ?></td>
                    <td class="px-6 py-4"><?php echo getScanTypeBadge($scan['scan_type']); ?></td>
                    <td class="px-6 py-4">
                        <span class="font-semibold <?php echo $scan['findings_count'] > 0 ? 'text-red-400' : 'text-gray-400'; ?>">
                            <?php echo $scan['findings_count'] ?? 0; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $scan['performer_name'] ?? 'غير معين'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $scan['tool_name']; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $scan['target']; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo date('Y-m-d H:i', strtotime($scan['created_at'])); ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $scan['scan_name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($scans); ?> فحص
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full ml-1"></span>
                قيد التنفيذ: <?php echo $in_progress_scans; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                مكتمل: <?php echo $completed_scans; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل الفحص -->
<!-- ============================================= -->
<div id="scan-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeScanModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400" id="scan-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل الفحص
            </h3>
        </div>
        <div id="scan-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script>
// =============================================
// متغيرات عامة
// =============================================
let currentScanId = null;

// =============================================
// دوال الاختبارات
// =============================================
function runTest(category, test) {
    let message = '';
    switch(category) {
        case 'recon':
            message = `🔍 بدء فحص الاستطلاع: ${test}`;
            break;
        case 'vuln':
            message = `🛡️ بدء فحص الثغرات: ${test}`;
            break;
        case 'exploit':
            message = `⚔️ بدء اختبار الاستغلال: ${test}`;
            break;
        case 'post':
            message = `📊 بدء مرحلة ما بعد الاستغلال: ${test}`;
            break;
    }
    
    if (typeof showNotification === 'function') {
        showNotification(message, 'info');
    }
    
    // فتح نافذة الفحص الجديد مع تعبئة بعض الحقول
    document.getElementById('scan-name').value = `${category} - ${test}`;
    document.getElementById('scan-type').value = 
        category === 'recon' ? 'port' : 
        category === 'vuln' ? 'vulnerability' : 
        category === 'exploit' ? 'web' : 'network';
    
    startNewScan();
}

function startNewScan() {
    window.startNewScan(); // من index.php
}

function refreshScans() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث بيانات الفحوصات', 'success');
        }
        location.reload();
    }, 1500);
}

function viewScanDetails(scanId) {
    currentScanId = scanId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل الفحص
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل الفحص #${scanId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="downloadScanReport(${scanId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تحميل التقرير</button>
                    <button onclick="closeScanModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('scan-details-content').innerHTML = details;
        document.getElementById('scan-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل الفحص #${scanId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('scan-details-modal').classList.remove('hidden');
        document.getElementById('scan-details-modal').classList.add('flex');
    }, 1000);
}

function closeScanModal() {
    document.getElementById('scan-details-modal').classList.add('hidden');
    document.getElementById('scan-details-modal').classList.remove('flex');
}

function pauseScan(scanId) {
    if (confirm('هل أنت متأكد من إيقاف الفحص مؤقتاً؟')) {
        if (typeof showNotification === 'function') {
            showNotification(`⏸️ تم إيقاف الفحص #${scanId} مؤقتاً`, 'warning');
        }
        setTimeout(() => location.reload(), 1500);
    }
}

function stopScan(scanId) {
    if (confirm('هل أنت متأكد من إيقاف الفحص نهائياً؟')) {
        if (typeof showNotification === 'function') {
            showNotification(`🛑 تم إيقاف الفحص #${scanId}`, 'error');
        }
        setTimeout(() => location.reload(), 1500);
    }
}

function downloadScanReport(scanId) {
    if (typeof showNotification === 'function') {
        showNotification(`📥 جاري تحميل تقرير الفحص #${scanId}`, 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification(`✅ تم تحميل التقرير بنجاح`, 'success');
        }
    }, 2000);
}

// البحث والتصفية
document.getElementById('search-scans')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.scan-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.getElementById('status-filter')?.addEventListener('change', filterScans);
document.getElementById('type-filter')?.addEventListener('change', filterScans);

function filterScans() {
    const statusFilter = document.getElementById('status-filter').value;
    const typeFilter = document.getElementById('type-filter').value;
    const rows = document.querySelectorAll('.scan-row');
    
    rows.forEach(row => {
        const statusMatch = statusFilter === 'all' || row.dataset.status === statusFilter;
        const typeMatch = typeFilter === 'all' || row.dataset.type === typeFilter;
        
        if (statusMatch && typeMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.scanning-animation {
    background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.1), transparent);
    background-size: 200% 100%;
    animation: scanning 2s linear infinite;
}
@keyframes scanning {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.scanning-pulse {
    animation: pulse 1.5s ease-in-out infinite;
}
</style>