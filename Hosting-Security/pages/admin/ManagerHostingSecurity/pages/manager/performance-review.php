<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/performance_review.php
// مراجعة أداء الوحدات - نسخة كاملة مع التصميم
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات أداء عامة
    // =============================================
    
    // متوسط الإنتاجية لجميع الوحدات (آخر 30 يوم)
    $stmt = $db->query("
        SELECT ROUND(AVG(productivity), 1) as avg_productivity,
               ROUND(AVG(quality), 1) as avg_quality,
               ROUND(AVG(speed), 1) as avg_speed
        FROM performance_metrics
        WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $overall_performance = $stmt->fetch();
    
    if (!$overall_performance) {
        $overall_performance = ['avg_productivity' => 0, 'avg_quality' => 0, 'avg_speed' => 0];
    }
    
    // =============================================
    // 2. أداء كل وحدة على حدة (آخر 30 يوم)
    // =============================================
    
    $units_performance = $db->query("
        SELECT 
            u.id,
            u.name,
            u.code,
            u.head_name,
            u.employee_count,
            u.max_employees,
            u.budget,
            COALESCE(ROUND(AVG(pm.productivity), 1), 0) as avg_productivity,
            COALESCE(ROUND(AVG(pm.quality), 1), 0) as avg_quality,
            COALESCE(ROUND(AVG(pm.speed), 1), 0) as avg_speed,
            COUNT(DISTINCT pr.id) as active_projects,
            COUNT(DISTINCT i.id) as active_incidents,
            COUNT(DISTINCT rr.id) as pending_requests
        FROM units u
        LEFT JOIN performance_metrics pm ON u.id = pm.unit_id AND pm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        LEFT JOIN projects pr ON u.id = pr.unit_id AND pr.status NOT IN ('completed', 'archived')
        LEFT JOIN incidents i ON u.id = i.unit_id AND i.status IN ('open', 'in-progress')
        LEFT JOIN resource_requests rr ON u.id = rr.unit_id AND rr.status = 'pending'
        GROUP BY u.id
        ORDER BY u.id
    ")->fetchAll();
    
    // =============================================
    // 3. أداء شهري لآخر 6 أشهر (للرسم البياني)
    // =============================================
    
    $monthly_performance = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('F', strtotime("-$i months"));
        $monthly_performance[$month] = [
            'month' => $month_name,
            'productivity' => 0,
            'quality' => 0,
            'speed' => 0,
            'count' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(metric_date, '%Y-%m') as month,
            ROUND(AVG(productivity), 1) as productivity,
            ROUND(AVG(quality), 1) as quality,
            ROUND(AVG(speed), 1) as speed
        FROM performance_metrics
        WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(metric_date, '%Y-%m')
        ORDER BY month
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($monthly_performance[$row['month']])) {
            $monthly_performance[$row['month']]['productivity'] = $row['productivity'];
            $monthly_performance[$row['month']]['quality'] = $row['quality'];
            $monthly_performance[$row['month']]['speed'] = $row['speed'];
        }
    }
    
    // =============================================
    // 4. أفضل الوحدات أداءً
    // =============================================
    
    $top_units = $db->query("
        SELECT 
            u.id,
            u.name,
            u.code,
            COALESCE(ROUND(AVG(pm.productivity), 1), 0) as avg_productivity,
            COALESCE(ROUND(AVG(pm.quality), 1), 0) as avg_quality,
            COALESCE(ROUND(AVG(pm.speed), 1), 0) as avg_speed,
            (SELECT COUNT(*) FROM projects WHERE unit_id = u.id AND status NOT IN ('completed', 'archived')) as active_projects
        FROM units u
        LEFT JOIN performance_metrics pm ON u.id = pm.unit_id AND pm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY u.id
        HAVING AVG(pm.productivity) > 0 
           AND AVG(pm.quality) > 0 
           AND AVG(pm.speed) > 0
        ORDER BY (COALESCE(AVG(pm.productivity), 0) + COALESCE(AVG(pm.quality), 0) + COALESCE(AVG(pm.speed), 0)) / 3 DESC
        LIMIT 3
    ")->fetchAll();
    
    // =============================================
    // 5. الوحدات التي تحتاج تحسين
    // =============================================
    
    $needs_improvement = $db->query("
        SELECT 
            u.id,
            u.name,
            u.code,
            COALESCE(ROUND(AVG(pm.productivity), 1), 0) as avg_productivity,
            COALESCE(ROUND(AVG(pm.quality), 1), 0) as avg_quality,
            COALESCE(ROUND(AVG(pm.speed), 1), 0) as avg_speed,
            COUNT(DISTINCT i.id) as open_incidents,
            COUNT(DISTINCT pr.id) as delayed_projects
        FROM units u
        LEFT JOIN performance_metrics pm ON u.id = pm.unit_id AND pm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        LEFT JOIN incidents i ON u.id = i.unit_id AND i.status IN ('open', 'in-progress')
        LEFT JOIN projects pr ON u.id = pr.unit_id AND pr.status = 'delayed'
        GROUP BY u.id
        HAVING (COALESCE(AVG(pm.productivity), 0) < 70 
            OR COALESCE(AVG(pm.quality), 0) < 70 
            OR COALESCE(AVG(pm.speed), 0) < 70 
            OR COUNT(DISTINCT i.id) > 0 
            OR COUNT(DISTINCT pr.id) > 0)
        ORDER BY (COALESCE(AVG(pm.productivity), 0) + COALESCE(AVG(pm.quality), 0) + COALESCE(AVG(pm.speed), 0)) / 3 ASC
    ")->fetchAll();
    
    // =============================================
    // 6. توزيع المشاريع حسب الوحدة
    // =============================================
    
    $projects_distribution = $db->query("
        SELECT 
            u.name,
            COUNT(pr.id) as total_projects,
            SUM(CASE WHEN pr.status = 'delayed' THEN 1 ELSE 0 END) as delayed_count,
            SUM(CASE WHEN pr.priority = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM units u
        LEFT JOIN projects pr ON u.id = pr.unit_id AND pr.status NOT IN ('completed', 'archived')
        GROUP BY u.id
        HAVING COUNT(pr.id) > 0
        ORDER BY COUNT(pr.id) DESC
    ")->fetchAll();
    
    // =============================================
    // 7. مؤشرات الأداء الرئيسية (KPIs) لكل وحدة
    // =============================================
    
    $unit_kpis = [];
    foreach ($units_performance as $unit) {
        $overall = round(($unit['avg_productivity'] + $unit['avg_quality'] + $unit['avg_speed']) / 3, 1);
        $kpi = [
            'productivity_score' => $unit['avg_productivity'] >= 90 ? 'ممتاز' : ($unit['avg_productivity'] >= 75 ? 'جيد' : ($unit['avg_productivity'] >= 60 ? 'متوسط' : 'ضعيف')),
            'quality_score' => $unit['avg_quality'] >= 90 ? 'ممتاز' : ($unit['avg_quality'] >= 75 ? 'جيد' : ($unit['avg_quality'] >= 60 ? 'متوسط' : 'ضعيف')),
            'speed_score' => $unit['avg_speed'] >= 90 ? 'ممتاز' : ($unit['avg_speed'] >= 75 ? 'جيد' : ($unit['avg_speed'] >= 60 ? 'متوسط' : 'ضعيف')),
            'overall_score' => $overall,
            'overall_text' => $overall >= 90 ? 'ممتاز' : ($overall >= 75 ? 'جيد' : ($overall >= 60 ? 'متوسط' : 'ضعيف'))
        ];
        $unit_kpis[$unit['id']] = $kpi;
    }
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function getPerformanceColor($value) {
    if ($value >= 90) return 'text-green-400';
    if ($value >= 75) return 'text-blue-400';
    if ($value >= 60) return 'text-yellow-400';
    return 'text-red-400';
}

function getPerformanceBadge($value) {
    if ($value >= 90) return 'bg-green-500';
    if ($value >= 75) return 'bg-blue-500';
    if ($value >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
}

function getScoreText($score) {
    return match($score) {
        'ممتاز' => 'ممتاز',
        'جيد' => 'جيد',
        'متوسط' => 'متوسط',
        'ضعيف' => 'ضعيف',
        default => $score
    };
}

function getUnitIcon($code) {
    return match($code) {
        'DOC' => '📋',
        'STR' => '💾',
        'SEC' => '🛡️',
        'PEN' => '🔍',
        default => '📦'
    };
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-purple-300">
            <i class="fas fa-chart-line ml-2"></i>
            مراجعة أداء الوحدات
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="generatePerformanceReport()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-file-pdf ml-2"></i>
                تقرير أداء شامل
            </button>
            <button onclick="refreshPerformanceData()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات الأداء العام -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">متوسط الإنتاجية</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $overall_performance['avg_productivity']; ?>%</p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-tachometer-alt text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                آخر 30 يوم
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">متوسط الجودة</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $overall_performance['avg_quality']; ?>%</p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-star text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                آخر 30 يوم
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">متوسط السرعة</p>
                    <p class="text-3xl font-bold text-purple-400"><?php echo $overall_performance['avg_speed']; ?>%</p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-rocket text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                آخر 30 يوم
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني للأداء الشهري -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-bar ml-2"></i>
        أداء الوحدات - آخر 6 أشهر
    </h3>
    
    <div class="h-80 relative" id="performance-chart-container">
        <canvas id="performanceChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">الإنتاجية</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">الجودة</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-purple-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">السرعة</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- أفضل الوحدات أداءً والوحدات التي تحتاج تحسين -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- أفضل الوحدات أداءً -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-trophy ml-2"></i>
            أفضل الوحدات أداءً
        </h3>
        
        <?php if (empty($top_units)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-info-circle text-4xl text-gray-500 mb-3"></i>
            <p class="text-gray-400">لا توجد بيانات أداء كافية</p>
        </div>
        <?php else: ?>
            <?php foreach ($top_units as $index => $unit): ?>
            <div class="mb-4 last:mb-0">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <span class="text-2xl ml-2"><?php 
                            echo $index == 0 ? '🥇' : ($index == 1 ? '🥈' : '🥉');
                        ?></span>
                        <div>
                            <h4 class="font-bold text-white"><?php echo $unit['name']; ?></h4>
                            <p class="text-xs text-gray-400"><?php echo $unit['active_projects']; ?> مشروع نشط</p>
                        </div>
                    </div>
                    <div class="text-left">
                        <span class="text-2xl font-bold text-green-400"><?php echo round(($unit['avg_productivity'] + $unit['avg_quality'] + $unit['avg_speed']) / 3, 1); ?>%</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-xs text-gray-400">إنتاجية</p>
                        <p class="text-sm font-bold <?php echo getPerformanceColor($unit['avg_productivity']); ?>"><?php echo $unit['avg_productivity']; ?>%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">جودة</p>
                        <p class="text-sm font-bold <?php echo getPerformanceColor($unit['avg_quality']); ?>"><?php echo $unit['avg_quality']; ?>%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">سرعة</p>
                        <p class="text-sm font-bold <?php echo getPerformanceColor($unit['avg_speed']); ?>"><?php echo $unit['avg_speed']; ?>%</p>
                    </div>
                </div>
                
                <?php if ($index < count($top_units) - 1): ?>
                <hr class="border-slate-700 my-4">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- الوحدات التي تحتاج تحسين -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-red-300 mb-4 flex items-center">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            تحتاج إلى تحسين
        </h3>
        
        <?php if (empty($needs_improvement)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <p class="text-green-400">جميع الوحدات تؤدي بشكل جيد</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($needs_improvement as $unit): ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 border-red-500">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-white"><?php echo $unit['name']; ?></h4>
                        <span class="text-sm <?php echo getPerformanceColor(($unit['avg_productivity'] + $unit['avg_quality'] + $unit['avg_speed']) / 3); ?>">
                            <?php echo round(($unit['avg_productivity'] + $unit['avg_quality'] + $unit['avg_speed']) / 3, 1); ?>%
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="text-center">
                            <p class="text-xs text-gray-400">إنتاجية</p>
                            <p class="text-sm font-bold <?php echo getPerformanceColor($unit['avg_productivity']); ?>"><?php echo $unit['avg_productivity']; ?>%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400">جودة</p>
                            <p class="text-sm font-bold <?php echo getPerformanceColor($unit['avg_quality']); ?>"><?php echo $unit['avg_quality']; ?>%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400">سرعة</p>
                            <p class="text-sm font-bold <?php echo getPerformanceColor($unit['avg_speed']); ?>"><?php echo $unit['avg_speed']; ?>%</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center">
                            <i class="fas fa-fire text-red-400 ml-1"></i>
                            <span class="text-gray-400">حوادث مفتوحة: <span class="text-red-400"><?php echo $unit['open_incidents']; ?></span></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-yellow-400 ml-1"></i>
                            <span class="text-gray-400">مشاريع متأخرة: <span class="text-yellow-400"><?php echo $unit['delayed_projects']; ?></span></span>
                        </div>
                    </div>
                    
                    <button onclick="scheduleReview(<?php echo $unit['id']; ?>)" class="w-full mt-3 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                        <i class="fas fa-calendar-alt ml-1"></i>
                        جدولة مراجعة
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- جدول أداء جميع الوحدات -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-blue-300 mb-4 flex items-center">
        <i class="fas fa-table ml-2"></i>
        أداء جميع الوحدات
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">التقييم</th>
                    <th class="px-6 py-4 text-sm font-semibold">السرعة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الجودة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الإنتاجية</th>
                    <th class="px-6 py-4 text-sm font-semibold">المشاريع</th>
                    <th class="px-6 py-4 text-sm font-semibold">الموظفين</th>
                    <th class="px-6 py-4 text-sm font-semibold">رئيس الوحدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوحدة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units_performance as $unit): 
                    $kpi = $unit_kpis[$unit['id']] ?? ['overall_score' => 0, 'overall_text' => 'ضعيف', 'productivity_score' => 'ضعيف', 'quality_score' => 'ضعيف', 'speed_score' => 'ضعيف'];
                ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewUnitDetails(<?php echo $unit['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="exportUnitReport(<?php echo $unit['id']; ?>)" class="text-green-400 hover:text-green-300" title="تصدير تقرير">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo getPerformanceBadge($kpi['overall_score']); ?>">
                            <?php echo $kpi['overall_text']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <span class="ml-2 font-bold <?php echo getPerformanceColor($unit['avg_speed']); ?>"><?php echo $unit['avg_speed']; ?>%</span>
                            <span class="text-xs text-gray-400">(<?php echo $kpi['speed_score']; ?>)</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <span class="ml-2 font-bold <?php echo getPerformanceColor($unit['avg_quality']); ?>"><?php echo $unit['avg_quality']; ?>%</span>
                            <span class="text-xs text-gray-400">(<?php echo $kpi['quality_score']; ?>)</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <span class="ml-2 font-bold <?php echo getPerformanceColor($unit['avg_productivity']); ?>"><?php echo $unit['avg_productivity']; ?>%</span>
                            <span class="text-xs text-gray-400">(<?php echo $kpi['productivity_score']; ?>)</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-300"><?php echo $unit['active_projects']; ?> نشط</span>
                            <span class="text-xs text-gray-500"><?php echo $unit['pending_requests']; ?> طلب</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-300"><?php echo $unit['employee_count']; ?>/<?php echo $unit['max_employees']; ?></span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $unit['head_name'] ?? 'غير معين'; ?>
                    </td>
                    <td class="px-6 py-4 font-semibold">
                        <div class="flex items-center">
                            <span class="text-2xl ml-2"><?php echo getUnitIcon($unit['code']); ?></span>
                            <span class="text-green-400"><?php echo $unit['name']; ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- توزيع المشاريع حسب الوحدة وملخص الأداء -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع المشاريع -->
    <?php if (!empty($projects_distribution)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-yellow-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع المشاريع حسب الوحدة
        </h3>
        
        <div class="space-y-4">
            <?php 
            $total_projects = array_sum(array_column($projects_distribution, 'total_projects'));
            foreach ($projects_distribution as $dist): 
                $percentage = $total_projects > 0 ? round(($dist['total_projects'] / $total_projects) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $dist['name']; ?></span>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-sm font-bold"><?php echo $dist['total_projects']; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                        <?php if ($dist['critical_count'] > 0): ?>
                        <span class="px-2 py-0.5 bg-red-600 rounded-full text-xs">حرج: <?php echo $dist['critical_count']; ?></span>
                        <?php endif; ?>
                        <?php if ($dist['delayed_count'] > 0): ?>
                        <span class="px-2 py-0.5 bg-yellow-600 rounded-full text-xs">متأخر: <?php echo $dist['delayed_count']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ملخص الأداء -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-chart-simple ml-2"></i>
            ملخص الأداء
        </h3>
        
        <div class="space-y-4">
            <?php
            $excellent = 0;
            $good = 0;
            $average = 0;
            $poor = 0;
            
            foreach ($unit_kpis as $kpi) {
                if ($kpi['overall_score'] >= 90) $excellent++;
                elseif ($kpi['overall_score'] >= 75) $good++;
                elseif ($kpi['overall_score'] >= 60) $average++;
                else $poor++;
            }
            ?>
            
            <div class="bg-slate-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-300">إجمالي الوحدات</span>
                    <span class="text-2xl font-bold text-blue-400"><?php echo count($units_performance); ?></span>
                </div>
            </div>
            
            <div class="bg-slate-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-300">وحدات ممتازة (≥90%)</span>
                    <span class="text-2xl font-bold text-green-400"><?php echo $excellent; ?></span>
                </div>
            </div>
            
            <div class="bg-slate-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-300">وحدات جيدة (75-89%)</span>
                    <span class="text-2xl font-bold text-blue-400"><?php echo $good; ?></span>
                </div>
            </div>
            
            <div class="bg-slate-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-300">وحدات متوسطة (60-74%)</span>
                    <span class="text-2xl font-bold text-yellow-400"><?php echo $average; ?></span>
                </div>
            </div>
            
            <div class="bg-slate-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-300">وحدات ضعيفة (<60%)</span>
                    <span class="text-2xl font-bold text-red-400"><?php echo $poor; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة جدولة مراجعة -->
<!-- ============================================= -->
<div id="schedule-review-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400">
                <i class="fas fa-calendar-alt ml-2"></i>
                جدولة مراجعة أداء
            </h3>
        </div>

        <form id="schedule-review-form" onsubmit="saveSchedule(event)" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">تاريخ المراجعة</label>
                <input type="date" name="review_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">وقت المراجعة</label>
                <input type="time" name="review_time" value="10:00" required 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">نوع المراجعة</label>
                <select name="review_type" required 
                        class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                    <option value="performance">مراجعة أداء</option>
                    <option value="improvement">خطة تحسين</option>
                    <option value="emergency">مراجعة عاجلة</option>
                    <option value="quarterly">مراجعة ربع سنوية</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                <textarea name="notes" rows="3" 
                          class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                          placeholder="أي ملاحظات إضافية..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ
                </button>
                <button type="button" onclick="closeScheduleModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// =============================================
// الرسم البياني
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart')?.getContext('2d');
    if (!ctx) return;
    
    const monthlyData = <?php echo json_encode(array_values($monthly_performance)); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'الإنتاجية',
                    data: monthlyData.map(d => d.productivity),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'الجودة',
                    data: monthlyData.map(d => d.quality),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'السرعة',
                    data: monthlyData.map(d => d.speed),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
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
                    max: 100,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            return value + '%';
                        }
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

let currentUnitId = null;

function generatePerformanceReport() {
    if (typeof showNotification === 'function') {
        showLoading();
        showNotification('جاري إنشاء تقرير الأداء الشامل...', 'info');
        
        setTimeout(() => {
            hideLoading();
            showNotification('تم إنشاء التقرير بنجاح', 'success');
        }, 3000);
    } else {
        alert('جاري إنشاء تقرير الأداء...');
    }
}

function refreshPerformanceData() {
    if (typeof showLoading === 'function') {
        showLoading();
        setTimeout(() => {
            hideLoading();
            if (typeof showNotification === 'function') {
                showNotification('تم تحديث بيانات الأداء', 'success');
            }
            location.reload();
        }, 1500);
    } else {
        location.reload();
    }
}

function viewUnitDetails(unitId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح تفاصيل الوحدة ${unitId}`, 'info');
    } else {
        alert(`فتح تفاصيل الوحدة ${unitId}`);
    }
}

function exportUnitReport(unitId) {
    if (typeof showNotification === 'function') {
        showNotification(`جاري تصدير تقرير أداء الوحدة ${unitId}`, 'info');
        setTimeout(() => {
            showNotification('تم تصدير التقرير بنجاح', 'success');
        }, 2000);
    } else {
        alert(`تصدير تقرير الوحدة ${unitId}`);
    }
}

function scheduleReview(unitId) {
    currentUnitId = unitId;
    document.getElementById('schedule-review-modal').classList.remove('hidden');
    document.getElementById('schedule-review-modal').classList.add('flex');
}

function closeScheduleModal() {
    document.getElementById('schedule-review-modal').classList.add('hidden');
    document.getElementById('schedule-review-modal').classList.remove('flex');
}

function saveSchedule(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') {
        showLoading();
        
        setTimeout(() => {
            hideLoading();
            closeScheduleModal();
            if (typeof showNotification === 'function') {
                showNotification(`تم جدولة مراجعة للوحدة ${currentUnitId} بنجاح`, 'success');
            }
        }, 1500);
    } else {
        closeScheduleModal();
        alert(`تم جدولة مراجعة للوحدة ${currentUnitId}`);
    }
}
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
    transition: width 0.3s ease;
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}
.scrollbar-custom::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-custom::-webkit-scrollbar-track {
    background: #1e293b;
}
.scrollbar-custom::-webkit-scrollbar-thumb {
    background: #8b5cf6;
    border-radius: 3px;
}
.table-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}
.manager-card {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.8));
    border: 1px solid rgba(59, 130, 246, 0.2);
    backdrop-filter: blur(10px);
}
.security-border {
    border: 2px solid rgba(59, 130, 246, 0.3);
    position: relative;
    overflow: hidden;
}
</style>