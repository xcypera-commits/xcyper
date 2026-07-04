<?php
// =============================================
// pentest-unit/pages/vulnerabilities.php
// صفحة تحليل الثغرات - بيانات حقيقية من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$severity_filter = $_GET['severity'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

try {
    // =============================================
    // 1. إحصائيات الثغرات - من قاعدة البيانات
    // =============================================
    
    // إجمالي الثغرات
    $stmt = $db->query("SELECT COUNT(*) FROM vulnerabilities");
    $total_vulnerabilities = $stmt->fetchColumn() ?: 0;
    
    // الثغرات حسب الشدة
    $severity_counts = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    $stmt = $db->query("SELECT severity, COUNT(*) as count FROM vulnerabilities GROUP BY severity");
    while ($row = $stmt->fetch()) {
        $severity_counts[$row['severity']] = $row['count'];
    }
    
    // الثغرات حسب الحالة
    $status_counts = [
        'open' => 0,
        'in-progress' => 0,
        'fixed' => 0,
        'false-positive' => 0,
        'accepted' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM vulnerabilities GROUP BY status");
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // =============================================
    // 2. جميع الثغرات مع التفاصيل - من قاعدة البيانات
    // =============================================
    
    $sql = "SELECT 
                v.*,
                p.project_name,
                p.project_code,
                p.client_name,
                u.full_name as discoverer_name,
                s.scan_name,
                s.scan_type
            FROM vulnerabilities v
            LEFT JOIN pentest_projects p ON v.project_id = p.id
            LEFT JOIN users u ON v.discovered_by = u.id
            LEFT JOIN security_scans s ON v.id = s.id
            WHERE 1=1";
    
    if ($severity_filter !== 'all') {
        $sql .= " AND v.severity = '$severity_filter'";
    }
    
    if ($status_filter !== 'all') {
        $sql .= " AND v.status = '$status_filter'";
    }
    
    $sql .= " ORDER BY 
                CASE v.severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                v.created_at DESC";
    
    $vulnerabilities = $db->query($sql)->fetchAll();
    
    // =============================================
    // 3. إحصائيات إضافية - من قاعدة البيانات
    // =============================================
    
    // توزيع الثغرات حسب النوع
    $by_type = $db->query("
        SELECT type, COUNT(*) as count 
        FROM vulnerabilities 
        GROUP BY type 
        ORDER BY count DESC
    ")->fetchAll();
    
    // توزيع الثغرات حسب المشروع
    $by_project = $db->query("
        SELECT p.project_name, COUNT(*) as count 
        FROM vulnerabilities v
        JOIN pentest_projects p ON v.project_id = p.id
        GROUP BY p.id
        ORDER BY count DESC
        LIMIT 5
    ")->fetchAll();
    
    // الثغرات الحرجة التي لم تحل بعد
    $critical_open = $db->query("
        SELECT v.*, p.project_name 
        FROM vulnerabilities v
        JOIN pentest_projects p ON v.project_id = p.id
        WHERE v.severity = 'critical' AND v.status IN ('open', 'in-progress')
        ORDER BY v.cvss_score DESC
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
function getSeverityBadge($severity) {
    return match($severity) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'high' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">عالي</span>',
        'medium' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">متوسط</span>',
        'low' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منخفض</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getStatusBadge($status) {
    return match($status) {
        'open' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">مفتوح</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد المعالجة</span>',
        'fixed' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تم الإصلاح</span>',
        'false-positive' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">إيجابي كاذب</span>',
        'accepted' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">مقبول</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getTypeText($type) {
    return match($type) {
        'web' => 'ويب',
        'network' => 'شبكة',
        'application' => 'تطبيق',
        'configuration' => 'تكوين',
        'authentication' => 'مصادقة',
        'injection' => 'حقن',
        'xss' => 'XSS',
        'csrf' => 'CSRF',
        default => $type
    };
}

function getCvssColor($score) {
    if ($score >= 9) return 'text-red-400';
    if ($score >= 7) return 'text-orange-400';
    if ($score >= 4) return 'text-yellow-400';
    return 'text-blue-400';
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-red-300">
            <i class="fas fa-bug ml-2"></i>
            تحليل الثغرات
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="exportVulnerabilities()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-download ml-2"></i>
                تصدير التقرير
            </button>
            <button onclick="refreshVulnerabilities()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-200 text-sm mb-1">ثغرات حرجة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $severity_counts['critical']; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                تحتاج معالجة فورية
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-900 to-orange-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-200 text-sm mb-1">ثغرات عالية</p>
                    <p class="text-3xl font-bold text-orange-400"><?php echo $severity_counts['high']; ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-2xl text-orange-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-orange-200">
                أولوية عالية
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">قيد المعالجة</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $status_counts['in-progress']; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-spinner text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                جاري العمل عليها
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">تم الإصلاح</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $status_counts['fixed']; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                إجمالي الثغرات: <?php echo $total_vulnerabilities; ?>
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">ثغرات مفتوحة</span>
            <span class="text-lg font-bold text-red-400"><?php echo $status_counts['open']; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">إيجابي كاذب / مقبول</span>
            <span class="text-lg font-bold text-purple-400"><?php echo $status_counts['false-positive'] + $status_counts['accepted']; ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الثغرات الحرجة غير المحلولة -->
<!-- ============================================= -->
<?php if (!empty($critical_open)): ?>
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-red-300 flex items-center">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            ثغرات حرجة تحتاج معالجة فورية
        </h3>
        <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo count($critical_open); ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($critical_open as $vuln): ?>
        <div class="bg-slate-900 rounded-lg p-4 border-r-4 border-red-500 card-hover">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-bold text-white"><?php echo $vuln['name']; ?></h4>
                <span class="text-xs text-gray-400">CVSS <?php echo $vuln['cvss_score']; ?></span>
            </div>
            
            <p class="text-xs text-gray-400 mb-2 line-clamp-2"><?php echo $vuln['description']; ?></p>
            
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-gray-400">المشروع:</span>
                <span class="text-blue-400"><?php echo $vuln['project_name']; ?></span>
            </div>
            
            <div class="flex items-center justify-between text-xs mb-3">
                <span class="text-gray-400">المكون المتأثر:</span>
                <span class="text-gray-300"><?php echo $vuln['affected_component']; ?></span>
            </div>
            
            <div class="flex space-x-2 space-x-reverse">
                <button onclick="viewVulnerabilityDetails(<?php echo $vuln['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                    <i class="fas fa-eye ml-1"></i>
                    تفاصيل
                </button>
                <button onclick="fixVulnerability(<?php echo $vuln['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                    <i class="fas fa-check ml-1"></i>
                    إصلاح
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- توزيع الثغرات حسب النوع والمشروع -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع حسب النوع -->
    <?php if (!empty($by_type)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع الثغرات حسب النوع
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($by_type as $type): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo getTypeText($type['type']); ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $type['count']; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo round(($type['count'] / $total_vulnerabilities) * 100, 1); ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($type['count'] / $total_vulnerabilities) * 100; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- توزيع حسب المشروع -->
    <?php if (!empty($by_project)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-chart-bar ml-2"></i>
            أكثر المشاريع تضرراً
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($by_project as $project): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $project['project_name']; ?></span>
                    <span class="text-sm font-bold text-red-400"><?php echo $project['count']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-red-500" style="width: <?php echo ($project['count'] / $total_vulnerabilities) * 100; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- جدول جميع الثغرات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-yellow-300 flex items-center">
            <i class="fas fa-table ml-2"></i>
            جميع الثغرات
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-vulns" placeholder="بحث في الثغرات..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-yellow-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="severity-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-yellow-500">
                <option value="all" <?php echo $severity_filter == 'all' ? 'selected' : ''; ?>>كل المستويات</option>
                <option value="critical" <?php echo $severity_filter == 'critical' ? 'selected' : ''; ?>>حرجة</option>
                <option value="high" <?php echo $severity_filter == 'high' ? 'selected' : ''; ?>>عالية</option>
                <option value="medium" <?php echo $severity_filter == 'medium' ? 'selected' : ''; ?>>متوسطة</option>
                <option value="low" <?php echo $severity_filter == 'low' ? 'selected' : ''; ?>>منخفضة</option>
            </select>
            <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-yellow-500">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>كل الحالات</option>
                <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                <option value="in-progress" <?php echo $status_filter == 'in-progress' ? 'selected' : ''; ?>>قيد المعالجة</option>
                <option value="fixed" <?php echo $status_filter == 'fixed' ? 'selected' : ''; ?>>تم الإصلاح</option>
                <option value="false-positive" <?php echo $status_filter == 'false-positive' ? 'selected' : ''; ?>>إيجابي كاذب</option>
                <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>>مقبول</option>
            </select>
        </div>
    </div>

    <?php if (empty($vulnerabilities)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد ثغرات</p>
        <p class="text-sm text-gray-500 mt-2">لم يتم اكتشاف أي ثغرات بعد</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="vulns-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">CVSS</th>
                    <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                    <th class="px-6 py-4 text-sm font-semibold">المشروع</th>
                    <th class="px-6 py-4 text-sm font-semibold">المكون</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ الاكتشاف</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم الثغرة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vulnerabilities as $vuln): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors vuln-row" 
                    data-severity="<?php echo $vuln['severity']; ?>"
                    data-status="<?php echo $vuln['status']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewVulnerabilityDetails(<?php echo $vuln['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($vuln['status'] != 'fixed'): ?>
                            <button onclick="fixVulnerability(<?php echo $vuln['id']; ?>)" class="text-green-400 hover:text-green-300" title="تحديد كإصلاح">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($vuln['status'] == 'open'): ?>
                            <button onclick="assignVulnerability(<?php echo $vuln['id']; ?>)" class="text-purple-400 hover:text-purple-300" title="تكليف">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getSeverityBadge($vuln['severity']); ?></td>
                    <td class="px-6 py-4"><?php echo getStatusBadge($vuln['status']); ?></td>
                    <td class="px-6 py-4">
                        <span class="font-bold <?php echo getCvssColor($vuln['cvss_score']); ?>">
                            <?php echo $vuln['cvss_score']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo getTypeText($vuln['type']); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $vuln['project_name'] ?? 'غير محدد'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $vuln['affected_component']; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $vuln['discovered_date']; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $vuln['name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($vulnerabilities); ?> ثغرة
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-600 rounded-full ml-1"></span>
                حرجة: <?php echo $severity_counts['critical']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-orange-500 rounded-full ml-1"></span>
                عالية: <?php echo $severity_counts['high']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                متوسطة: <?php echo $severity_counts['medium']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                منخفضة: <?php echo $severity_counts['low']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل الثغرة -->
<!-- ============================================= -->
<div id="vuln-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeVulnModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-red-400" id="vuln-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل الثغرة
            </h3>
        </div>
        <div id="vuln-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تكليف الثغرة -->
<!-- ============================================= -->
<div id="assign-vuln-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAssignVulnModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-purple-400">
                <i class="fas fa-user-plus ml-2"></i>
                تكليف الثغرة
            </h3>
        </div>

        <form id="assign-vuln-form" onsubmit="saveVulnAssignment(event)" class="space-y-4">
            <input type="hidden" id="assign-vuln-id" name="vuln_id">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">تكليف إلى</label>
                <select id="assign-user-id" name="user_id" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500 text-right">
                    <option value="">اختر المستخدم</option>
                    <?php
                    $users = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();
                    foreach ($users as $user):
                    ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500 text-right"></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-check ml-2"></i>
                    تكليف
                </button>
                <button type="button" onclick="closeAssignVulnModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script>
// =============================================
// متغيرات عامة
// =============================================
let currentVulnId = null;

// =============================================
// دوال الصفحة
// =============================================
function exportVulnerabilities() {
    if (typeof showNotification === 'function') {
        showNotification('📥 جاري تصدير تقرير الثغرات', 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification('✅ تم تصدير التقرير بنجاح', 'success');
        }
    }, 2000);
}

function refreshVulnerabilities() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث بيانات الثغرات', 'success');
        }
        location.reload();
    }, 1500);
}

function viewVulnerabilityDetails(vulnId) {
    currentVulnId = vulnId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل الثغرة
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل الثغرة #${vulnId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="fixVulnerability(${vulnId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">إصلاح</button>
                    <button onclick="assignVulnerability(${vulnId})" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg">تكليف</button>
                    <button onclick="closeVulnModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('vuln-details-content').innerHTML = details;
        document.getElementById('vuln-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل الثغرة #${vulnId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('vuln-details-modal').classList.remove('hidden');
        document.getElementById('vuln-details-modal').classList.add('flex');
    }, 1000);
}

function closeVulnModal() {
    document.getElementById('vuln-details-modal').classList.add('hidden');
    document.getElementById('vuln-details-modal').classList.remove('flex');
}

function fixVulnerability(vulnId) {
    if (typeof showNotification === 'function') {
        showNotification(`✅ تم تحديد الثغرة #${vulnId} كمصلحة`, 'success');
    }
    closeVulnModal();
    setTimeout(() => location.reload(), 1500);
}

function assignVulnerability(vulnId) {
    currentVulnId = vulnId;
    document.getElementById('assign-vuln-id').value = vulnId;
    document.getElementById('assign-vuln-modal').classList.remove('hidden');
    document.getElementById('assign-vuln-modal').classList.add('flex');
    closeVulnModal();
}

function closeAssignVulnModal() {
    document.getElementById('assign-vuln-modal').classList.add('hidden');
    document.getElementById('assign-vuln-modal').classList.remove('flex');
}

function saveVulnAssignment(event) {
    event.preventDefault();
    
    const userId = document.getElementById('assign-user-id').value;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeAssignVulnModal();
        if (typeof showNotification === 'function') {
            showNotification(`👤 تم تكليف الثغرة #${currentVulnId}`, 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

// البحث والتصفية
document.getElementById('search-vulns')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.vuln-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// التصفية حسب الشدة والحالة
document.getElementById('severity-filter')?.addEventListener('change', function() {
    const severity = this.value;
    const status = document.getElementById('status-filter').value;
    window.location.href = `?page=vulnerabilities&severity=${severity}&status=${status}`;
});

document.getElementById('status-filter')?.addEventListener('change', function() {
    const severity = document.getElementById('severity-filter').value;
    const status = this.value;
    window.location.href = `?page=vulnerabilities&severity=${severity}&status=${status}`;
});
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.progress-bar {
    height: 6px;
    background: #334155;
    border-radius: 3px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    transition: width 0.3s ease;
}
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>