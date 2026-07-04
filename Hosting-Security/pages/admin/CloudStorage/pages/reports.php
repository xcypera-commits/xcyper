<?php
// =============================================
// cloud-unit/pages/reports.php
// صفحة تقارير النظام
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// تعريف الدوال المساعدة المفقودة
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (!$datetime) return '-';
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) return 'منذ لحظات';
        if ($diff < 3600) return 'منذ ' . floor($diff / 60) . ' دقيقة';
        if ($diff < 86400) return 'منذ ' . floor($diff / 3600) . ' ساعة';
        if ($diff < 2592000) return 'منذ ' . floor($diff / 86400) . ' يوم';
        return date('Y-m-d', $time);
    }
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'generate_report':
                // إنشاء تقرير جديد
                $report_code = generateReportCode($db);
                
                $sql = "INSERT INTO cloud_reports (
                    report_code, report_name, report_type, period,
                    date_from, date_to, format, summary, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $report_code,
                    $_POST['report_name'],
                    $_POST['report_type'],
                    $_POST['period'],
                    $_POST['date_from'] ?? null,
                    $_POST['date_to'] ?? null,
                    $_POST['format'] ?? 'pdf',
                    $_POST['summary'] ?? null,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $report_id = $db->lastInsertId();
                
                logActivity($db, 'create', 'report', $report_id, 'إنشاء تقرير جديد');
                
                $response['success'] = true;
                $response['message'] = 'تم إنشاء التقرير بنجاح';
                $response['report_id'] = $report_id;
                break;
                
            case 'delete_report':
                // حذف تقرير
                $db->prepare("DELETE FROM cloud_reports WHERE id = ?")->execute([$_POST['report_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم حذف التقرير';
                break;
                
            case 'generate_usage_report':
                // إنشاء تقرير استخدام (مخصص)
                // هنا يمكن إضافة منطق توليد التقرير
                $response['success'] = true;
                $response['message'] = 'تم إنشاء تقرير الاستخدام';
                break;
                
            case 'generate_performance_report':
                // إنشاء تقرير أداء
                $response['success'] = true;
                $response['message'] = 'تم إنشاء تقرير الأداء';
                break;
                
            case 'generate_security_report':
                // إنشاء تقرير أمني
                $response['success'] = true;
                $response['message'] = 'تم إنشاء التقرير الأمني';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $type_filter = $_GET['type'] ?? '';
    $period_filter = $_GET['period'] ?? '';
    $date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $_GET['to'] ?? date('Y-m-d');
    
    // جلب التقارير
    $sql = "
        SELECT r.*, u.full_name as creator_name
        FROM cloud_reports r
        LEFT JOIN users u ON r.created_by = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($type_filter) {
        $sql .= " AND r.report_type = ?";
        $params[] = $type_filter;
    }
    
    if ($period_filter) {
        $sql .= " AND r.period = ?";
        $params[] = $period_filter;
    }
    
    if ($date_from && $date_to) {
        $sql .= " AND DATE(r.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY r.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
    
    // إحصائيات التقارير
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN report_type = 'usage' THEN 1 ELSE 0 END) as usage_count,
            SUM(CASE WHEN report_type = 'performance' THEN 1 ELSE 0 END) as performance_count,
            SUM(CASE WHEN report_type = 'security' THEN 1 ELSE 0 END) as security_count,
            SUM(CASE WHEN report_type = 'backup' THEN 1 ELSE 0 END) as backup_count,
            SUM(CASE WHEN report_type = 'audit' THEN 1 ELSE 0 END) as audit_count,
            SUM(CASE WHEN period = 'daily' THEN 1 ELSE 0 END) as daily_count,
            SUM(CASE WHEN period = 'weekly' THEN 1 ELSE 0 END) as weekly_count,
            SUM(CASE WHEN period = 'monthly' THEN 1 ELSE 0 END) as monthly_count,
            SUM(CASE WHEN period = 'quarterly' THEN 1 ELSE 0 END) as quarterly_count,
            SUM(CASE WHEN period = 'yearly' THEN 1 ELSE 0 END) as yearly_count
        FROM cloud_reports
    ")->fetch();
    
    // إحصائيات شهرية
    $monthly_stats = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total
        FROM cloud_reports
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();
    
    // آخر 5 تقارير
    $recent_reports = $db->query("
        SELECT r.*, u.full_name as creator_name
        FROM cloud_reports r
        LEFT JOIN users u ON r.created_by = u.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // إحصائيات للرسوم البيانية
    $chart_data = [
        'usage' => $stats['usage_count'] ?? 0,
        'performance' => $stats['performance_count'] ?? 0,
        'security' => $stats['security_count'] ?? 0,
        'backup' => $stats['backup_count'] ?? 0,
        'audit' => $stats['audit_count'] ?? 0
    ];
    
    $period_data = [
        'daily' => $stats['daily_count'] ?? 0,
        'weekly' => $stats['weekly_count'] ?? 0,
        'monthly' => $stats['monthly_count'] ?? 0,
        'quarterly' => $stats['quarterly_count'] ?? 0,
        'yearly' => $stats['yearly_count'] ?? 0
    ];
    
} catch (Exception $e) {
    $reports = [];
    $recent_reports = [];
    $monthly_stats = [];
    $stats = [
        'total' => 0,
        'usage_count' => 0,
        'performance_count' => 0,
        'security_count' => 0,
        'backup_count' => 0,
        'audit_count' => 0,
        'daily_count' => 0,
        'weekly_count' => 0,
        'monthly_count' => 0,
        'quarterly_count' => 0,
        'yearly_count' => 0
    ];
    $chart_data = ['usage' => 0, 'performance' => 0, 'security' => 0, 'backup' => 0, 'audit' => 0];
    $period_data = ['daily' => 0, 'weekly' => 0, 'monthly' => 0, 'quarterly' => 0, 'yearly' => 0];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateReportCode($db) {
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cloud_reports WHERE report_code LIKE ?");
    $stmt->execute(["RPT-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "RPT-{$year}-{$number}";
}

function getReportTypeText($type) {
    $texts = [
        'usage' => 'تقرير استخدام',
        'performance' => 'تقرير أداء',
        'security' => 'تقرير أمني',
        'backup' => 'تقرير نسخ احتياطي',
        'audit' => 'تقرير تدقيق',
        'custom' => 'تقرير مخصص'
    ];
    
    return $texts[$type] ?? $type;
}

function getReportTypeBadge($type) {
    $classes = [
        'usage' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'performance' => 'bg-green-600 bg-opacity-20 text-green-400',
        'security' => 'bg-red-600 bg-opacity-20 text-red-400',
        'backup' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'audit' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'custom' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    
    return "<span class='px-2 py-1 rounded-full text-xs font-semibold $class'>" . getReportTypeText($type) . "</span>";
}

function getPeriodText($period) {
    $texts = [
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'yearly' => 'سنوي',
        'custom' => 'مخصص'
    ];
    
    return $texts[$period] ?? $period;
}

function logActivity($db, $type, $target, $target_id, $description) {
    $sql = "INSERT INTO cloud_activity_log (user_id, activity_type, target_type, target_id, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id'] ?? 1, $type, $target, $target_id, $description]);
}
?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري التحميل...</p>
    </div>
</div>

<!-- ============================================= -->
<!-- رأس الصفحة مع الإحصائيات -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-2xl p-8 mb-8 cyber-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">📊</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">تقارير النظام</h1>
                <p class="text-gray-400 mt-1">إنشاء وعرض تقارير الأداء والاستخدام</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="openCreateReportModal()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">+</span>
                تقرير جديد
            </button>
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-all">
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر -->
    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-slate-700">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="بحث في التقارير..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500 text-right">
        </div>
        
        <select id="filter-type" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الأنواع</option>
            <option value="usage">تقرير استخدام</option>
            <option value="performance">تقرير أداء</option>
            <option value="security">تقرير أمني</option>
            <option value="backup">تقرير نسخ احتياطي</option>
            <option value="audit">تقرير تدقيق</option>
        </select>
        
        <select id="filter-period" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الفترات</option>
            <option value="daily">يومي</option>
            <option value="weekly">أسبوعي</option>
            <option value="monthly">شهري</option>
            <option value="quarterly">ربع سنوي</option>
            <option value="yearly">سنوي</option>
        </select>
        
        <input type="date" id="date-from" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
        <input type="date" id="date-to" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي التقارير</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo $stats['total']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">تقارير استخدام</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['usage_count']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">تقارير أداء</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['performance_count']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">تقارير أمنية</p>
        <p class="text-2xl font-bold text-red-400"><?php echo $stats['security_count']; ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات إضافية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- توزيع أنواع التقارير -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توزيع أنواع التقارير</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">استخدام</span>
                    <span class="text-blue-400"><?php echo $stats['usage_count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $usage_percent = $stats['total'] > 0 ? round(($stats['usage_count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $usage_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">أداء</span>
                    <span class="text-green-400"><?php echo $stats['performance_count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $perf_percent = $stats['total'] > 0 ? round(($stats['performance_count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-green-500" style="width: <?php echo $perf_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">أمني</span>
                    <span class="text-red-400"><?php echo $stats['security_count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $sec_percent = $stats['total'] > 0 ? round(($stats['security_count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-red-500" style="width: <?php echo $sec_percent; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- توزيع الفترات الزمنية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">الفترات الزمنية</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">يومي</span>
                    <span class="text-yellow-400"><?php echo $stats['daily_count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $daily_percent = $stats['total'] > 0 ? round(($stats['daily_count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-yellow-500" style="width: <?php echo $daily_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">أسبوعي</span>
                    <span class="text-orange-400"><?php echo $stats['weekly_count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $weekly_percent = $stats['total'] > 0 ? round(($stats['weekly_count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-orange-500" style="width: <?php echo $weekly_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">شهري</span>
                    <span class="text-purple-400"><?php echo $stats['monthly_count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $monthly_percent = $stats['total'] > 0 ? round(($stats['monthly_count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $monthly_percent; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- آخر التقارير -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">آخر التقارير</h3>
        
        <?php if (empty($recent_reports)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد تقارير</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_reports as $report): ?>
                <div class="p-2 bg-slate-700 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold truncate" style="max-width: 150px;"><?php echo $report['report_name']; ?></span>
                        <?php echo getReportTypeBadge($report['report_type']); ?>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400"><?php echo $report['creator_name'] ?? 'النظام'; ?></span>
                        <span class="text-gray-400"><?php echo timeAgo($report['created_at']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- قوالب التقارير السريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-lg font-bold mb-6">⚡ إنشاء تقرير سريع</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <button onclick="generateUsageReport()" class="p-4 bg-slate-700 rounded-lg hover:bg-slate-600 transition-all text-center">
            <div class="w-12 h-12 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center mx-auto mb-3">
                <span class="text-2xl text-blue-400">📊</span>
            </div>
            <h4 class="font-semibold mb-1">تقرير استخدام</h4>
            <p class="text-xs text-gray-400">استخدام التخزين والموارد</p>
        </button>
        
        <button onclick="generatePerformanceReport()" class="p-4 bg-slate-700 rounded-lg hover:bg-slate-600 transition-all text-center">
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center mx-auto mb-3">
                <span class="text-2xl text-green-400">📈</span>
            </div>
            <h4 class="font-semibold mb-1">تقرير أداء</h4>
            <p class="text-xs text-gray-400">أداء الخوادم والخدمات</p>
        </button>
        
        <button onclick="generateSecurityReport()" class="p-4 bg-slate-700 rounded-lg hover:bg-slate-600 transition-all text-center">
            <div class="w-12 h-12 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center mx-auto mb-3">
                <span class="text-2xl text-red-400">🔒</span>
            </div>
            <h4 class="font-semibold mb-1">تقرير أمني</h4>
            <p class="text-xs text-gray-400">التحديثات والثغرات</p>
        </button>
        
        <button onclick="openCreateReportModal()" class="p-4 bg-slate-700 rounded-lg hover:bg-slate-600 transition-all text-center">
            <div class="w-12 h-12 bg-purple-600 bg-opacity-20 rounded-lg flex items-center justify-center mx-auto mb-3">
                <span class="text-2xl text-purple-400">📋</span>
            </div>
            <h4 class="font-semibold mb-1">تقرير مخصص</h4>
            <p class="text-xs text-gray-400">إنشاء تقرير حسب الطلب</p>
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة التقارير -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">📋 جميع التقارير</h3>
        <span class="text-sm text-gray-400">إجمالي <?php echo count($reports); ?> تقرير</span>
    </div>
    
    <?php if (empty($reports)): ?>
        <div class="text-center py-12">
            <div class="text-5xl text-gray-600 mb-4">📭</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد تقارير</h3>
            <p class="text-gray-500">قم بإنشاء أول تقرير الآن</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">الفترة</th>
                        <th class="px-4 py-3">التنسيق</th>
                        <th class="px-4 py-3">المنشئ</th>
                        <th class="px-4 py-3">التاريخ</th>
                        <th class="px-4 py-3">اسم التقرير</th>
                        <th class="px-4 py-3">الكود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button onclick="viewReport(<?php echo $report['id']; ?>)" class="text-blue-400 hover:text-blue-300 text-sm" title="عرض">
                                    عرض
                                </button>
                                <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="text-green-400 hover:text-green-300 text-sm" title="تحميل">
                                    تحميل
                                </button>
                                <button onclick="deleteReport(<?php echo $report['id']; ?>)" class="text-red-400 hover:text-red-300 text-sm" title="حذف">
                                    حذف
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?php echo getReportTypeBadge($report['report_type']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo getPeriodText($report['period']); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-gray-600 bg-opacity-20 text-gray-400 rounded text-xs">
                                <?php echo strtoupper($report['format']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo $report['creator_name'] ?? 'النظام'; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo timeAgo($report['created_at']); ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $report['report_name']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $report['report_code']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء تقرير جديد -->
<!-- ============================================= -->
<div id="create-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateReportModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">إنشاء تقرير جديد</h3>
        </div>
        
        <form id="create-report-form" onsubmit="handleCreateReport(event)">
            <input type="hidden" name="action" value="generate_report">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم التقرير</label>
                    <input type="text" name="report_name" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500 text-right"
                           placeholder="أدخل اسم التقرير">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نوع التقرير</label>
                        <select name="report_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="usage">تقرير استخدام</option>
                            <option value="performance">تقرير أداء</option>
                            <option value="security">تقرير أمني</option>
                            <option value="backup">تقرير نسخ احتياطي</option>
                            <option value="audit">تقرير تدقيق</option>
                            <option value="custom">تقرير مخصص</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الفترة الزمنية</label>
                        <select name="period" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="daily">يومي</option>
                            <option value="weekly">أسبوعي</option>
                            <option value="monthly" selected>شهري</option>
                            <option value="quarterly">ربع سنوي</option>
                            <option value="yearly">سنوي</option>
                            <option value="custom">مخصص</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">من تاريخ</label>
                        <input type="date" name="date_from" value="<?php echo date('Y-m-01'); ?>"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">إلى تاريخ</label>
                        <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تنسيق التقرير</label>
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <label class="flex items-center">
                            <input type="radio" name="format" value="pdf" checked class="ml-2">
                            <span>PDF</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="format" value="excel" class="ml-2">
                            <span>Excel</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="format" value="html" class="ml-2">
                            <span>HTML</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">ملخص التقرير</label>
                    <textarea name="summary" rows="3" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"
                              placeholder="وصف مختصر لمحتوى التقرير..."></textarea>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateReportModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-all cyber-glow">
                    إنشاء التقرير
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة عرض التقرير -->
<!-- ============================================= -->
<div id="view-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeViewReportModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right" id="view-report-title">عرض التقرير</h3>
        </div>
        
        <div id="report-content" class="bg-slate-700 rounded-lg p-6 mb-4 min-h-[400px]">
            <!-- محتوى التقرير سيتم تحميله هنا -->
            <div class="text-center text-gray-400 py-12">
                جاري تحميل التقرير...
            </div>
        </div>
        
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="closeViewReportModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                إغلاق
            </button>
            <button onclick="downloadCurrentReport()" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow">
                تحميل التقرير
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// =============================================
// بيانات الرسوم البيانية
// =============================================
const chartData = <?php echo json_encode($chart_data); ?>;
const periodData = <?php echo json_encode($period_data); ?>;

// تهيئة الرسم البياني لأنواع التقارير
document.addEventListener('DOMContentLoaded', function() {
    // رسم بياني لأنواع التقارير
    const typeCtx = document.getElementById('reportTypeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ['استخدام', 'أداء', 'أمني', 'نسخ احتياطي', 'تدقيق'],
                datasets: [{
                    data: [
                        chartData.usage,
                        chartData.performance,
                        chartData.security,
                        chartData.backup,
                        chartData.audit
                    ],
                    backgroundColor: ['#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#f59e0b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }
    
    // رسم بياني للفترات الزمنية
    const periodCtx = document.getElementById('periodChart');
    if (periodCtx) {
        new Chart(periodCtx, {
            type: 'bar',
            data: {
                labels: ['يومي', 'أسبوعي', 'شهري', 'ربع سنوي', 'سنوي'],
                datasets: [{
                    data: [
                        periodData.daily,
                        periodData.weekly,
                        periodData.monthly,
                        periodData.quarterly,
                        periodData.yearly
                    ],
                    backgroundColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    }
});

// =============================================
// دوال إنشاء تقرير
// =============================================
function openCreateReportModal() {
    document.getElementById('create-report-modal').classList.remove('hidden');
}

function closeCreateReportModal() {
    document.getElementById('create-report-modal').classList.add('hidden');
    document.getElementById('create-report-form').reset();
}

function handleCreateReport(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-report-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeCreateReportModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ', 'error');
        console.error(error);
    });
}

// =============================================
// دوال التقارير السريعة
// =============================================
function generateUsageReport() {
    const formData = new FormData();
    formData.append('action', 'generate_usage_report');
    formData.append('report_name', 'تقرير استخدام ' + new Date().toLocaleDateString('ar-EG'));
    formData.append('report_type', 'usage');
    formData.append('period', 'monthly');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('تم إنشاء تقرير الاستخدام', 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function generatePerformanceReport() {
    const formData = new FormData();
    formData.append('action', 'generate_performance_report');
    formData.append('report_name', 'تقرير أداء ' + new Date().toLocaleDateString('ar-EG'));
    formData.append('report_type', 'performance');
    formData.append('period', 'weekly');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('تم إنشاء تقرير الأداء', 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function generateSecurityReport() {
    const formData = new FormData();
    formData.append('action', 'generate_security_report');
    formData.append('report_name', 'تقرير أمني ' + new Date().toLocaleDateString('ar-EG'));
    formData.append('report_type', 'security');
    formData.append('period', 'daily');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('تم إنشاء التقرير الأمني', 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

// =============================================
// دوال إدارة التقارير
// =============================================
let currentReportId = null;

function viewReport(id) {
    currentReportId = id;
    document.getElementById('view-report-title').textContent = 'عرض التقرير #' + id;
    document.getElementById('view-report-modal').classList.remove('hidden');
    
    // هنا يمكن جلب محتوى التقرير من قاعدة البيانات
    document.getElementById('report-content').innerHTML = `
        <div class="text-center text-gray-300 py-8">
            <p class="mb-4">جاري تحميل التقرير...</p>
            <div class="spinner mx-auto"></div>
        </div>
    `;
    
    // محاكاة تحميل التقرير
    setTimeout(() => {
        document.getElementById('report-content').innerHTML = `
            <div class="space-y-4">
                <h2 class="text-2xl font-bold text-center mb-6">تقرير #${id}</h2>
                <p class="text-gray-300">هذا تقرير تجريبي. في التطبيق الفعلي، سيتم عرض بيانات حقيقية من قاعدة البيانات.</p>
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="bg-slate-600 p-4 rounded-lg">
                        <p class="text-gray-400">إجمالي الملفات</p>
                        <p class="text-2xl font-bold text-blue-400">1,248</p>
                    </div>
                    <div class="bg-slate-600 p-4 rounded-lg">
                        <p class="text-gray-400">المساحة المستخدمة</p>
                        <p class="text-2xl font-bold text-green-400">1.56 TB</p>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}

function closeViewReportModal() {
    document.getElementById('view-report-modal').classList.add('hidden');
}

function downloadReport(id) {
    showNotification('📥 جاري تحميل التقرير...', 'info');
    setTimeout(() => {
        showNotification('تم تحميل التقرير', 'success');
    }, 1500);
}

function downloadCurrentReport() {
    if (currentReportId) {
        downloadReport(currentReportId);
        closeViewReportModal();
    }
}

function deleteReport(id) {
    if (!confirm('⚠️ هل أنت متأكد من حذف هذا التقرير؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_report');
    formData.append('report_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

// =============================================
// دوال الفلاتر
// =============================================
function applyFilters() {
    const type = document.getElementById('filter-type').value;
    const period = document.getElementById('filter-period').value;
    const from = document.getElementById('date-from').value;
    const to = document.getElementById('date-to').value;
    
    let url = '?page=reports';
    if (type) url += '&type=' + type;
    if (period) url += '&period=' + period;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=reports';
}

function refreshPage() {
    location.reload();
}

// =============================================
// دوال مساعدة
// =============================================
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg text-sm`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}

// إغلاق النوافذ بالـ ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateReportModal();
        closeViewReportModal();
    }
});
</script>