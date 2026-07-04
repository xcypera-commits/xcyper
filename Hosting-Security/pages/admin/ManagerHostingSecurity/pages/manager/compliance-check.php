<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/compliance_check.php
// فحص الامتثال - نسخة كاملة ومفصلة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة عن الامتثال
    // =============================================
    
    // متوسط الامتثال العام
    $stmt = $db->query("SELECT ROUND(AVG(compliance_rate), 1) FROM compliance_standards");
    $avg_compliance = $stmt->fetchColumn() ?: 0;
    
    // المعايير المتوافقة
    $stmt = $db->query("SELECT COUNT(*) FROM compliance_standards WHERE status = 'compliant'");
    $compliant_count = $stmt->fetchColumn() ?: 0;
    
    // المعايير قيد التقدم
    $stmt = $db->query("SELECT COUNT(*) FROM compliance_standards WHERE status = 'in-progress'");
    $in_progress_count = $stmt->fetchColumn() ?: 0;
    
    // المعايير غير المتوافقة
    $stmt = $db->query("SELECT COUNT(*) FROM compliance_standards WHERE status = 'non-compliant'");
    $non_compliant_count = $stmt->fetchColumn() ?: 0;
    
    // الانحرافات النشطة
    $stmt = $db->query("SELECT COUNT(*) FROM violations WHERE status IN ('open', 'in-progress')");
    $active_violations = $stmt->fetchColumn() ?: 0;
    
    // الانحرافات الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM violations WHERE severity = 'critical' AND status IN ('open', 'in-progress')");
    $critical_violations = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. قائمة معايير الامتثال
    // =============================================
    
    $standards = $db->query("
        SELECT 
            cs.*,
            u.name as responsible_unit_name
        FROM compliance_standards cs
        LEFT JOIN units u ON cs.responsible_unit = u.id
        ORDER BY 
            CASE cs.status
                WHEN 'non-compliant' THEN 1
                WHEN 'in-progress' THEN 2
                WHEN 'compliant' THEN 3
            END,
            cs.compliance_rate DESC
    ")->fetchAll();
    
    // =============================================
    // 3. قائمة الانحرافات
    // =============================================
    
    $violations = $db->query("
        SELECT 
            v.*,
            cs.name as standard_name,
            cs.code as standard_code,
            u.full_name as assigned_to_name
        FROM violations v
        LEFT JOIN compliance_standards cs ON v.standard_id = cs.id
        LEFT JOIN users u ON v.assigned_to = u.id
        ORDER BY 
            CASE v.severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            CASE v.status
                WHEN 'open' THEN 1
                WHEN 'in-progress' THEN 2
                WHEN 'resolved' THEN 3
            END,
            v.detected_date DESC
    ")->fetchAll();
    
    // =============================================
    // 4. إحصائيات الانحرافات حسب الشدة
    // =============================================
    
    $violations_by_severity = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    $stmt = $db->query("SELECT severity, COUNT(*) as count FROM violations WHERE status IN ('open', 'in-progress') GROUP BY severity");
    while ($row = $stmt->fetch()) {
        if (isset($violations_by_severity[$row['severity']])) {
            $violations_by_severity[$row['severity']] = $row['count'];
        }
    }
    
    // =============================================
    // 5. إحصائيات الانحرافات حسب الحالة
    // =============================================
    
    $violations_by_status = [
        'open' => 0,
        'in-progress' => 0,
        'resolved' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM violations GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($violations_by_status[$row['status']])) {
            $violations_by_status[$row['status']] = $row['count'];
        }
    }
    
    // =============================================
    // 6. آخر التدقيقات
    // =============================================
    
    $recent_audits = $db->query("
        SELECT 
            cs.name,
            cs.code,
            cs.last_audit,
            cs.next_audit,
            cs.compliance_rate,
            cs.status,
            u.name as unit_name
        FROM compliance_standards cs
        LEFT JOIN units u ON cs.responsible_unit = u.id
        WHERE cs.last_audit IS NOT NULL
        ORDER BY cs.last_audit DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 7. توزيع الامتثال حسب المعيار
    // =============================================
    
    $compliance_distribution = [];
    foreach ($standards as $standard) {
        $compliance_distribution[] = [
            'name' => $standard['name'],
            'rate' => $standard['compliance_rate'],
            'code' => $standard['code']
        ];
    }
    
    // =============================================
    // 8. أكثر الوحدات مسؤولية عن المعايير
    // =============================================
    
    $units_responsibility = $db->query("
        SELECT 
            u.name,
            COUNT(cs.id) as standards_count,
            ROUND(AVG(cs.compliance_rate), 1) as avg_compliance
        FROM units u
        LEFT JOIN compliance_standards cs ON u.id = cs.responsible_unit
        GROUP BY u.id
        HAVING standards_count > 0
        ORDER BY standards_count DESC, avg_compliance DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function getComplianceStatusBadge($status) {
    return match($status) {
        'compliant' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">متوافق</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد التقدم</span>',
        'non-compliant' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">غير متوافق</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getViolationSeverityBadge($severity) {
    return match($severity) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'high' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">عالي</span>',
        'medium' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">متوسط</span>',
        'low' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منخفض</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getViolationStatusBadge($status) {
    return match($status) {
        'open' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">مفتوح</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد المعالجة</span>',
        'resolved' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تم الحل</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}


function getComplianceColor($rate) {
    if ($rate >= 90) return 'text-green-400';
    if ($rate >= 70) return 'text-yellow-400';
    if ($rate >= 50) return 'text-orange-400';
    return 'text-red-400';
}

function getComplianceProgressColor($rate) {
    if ($rate >= 90) return 'bg-green-500';
    if ($rate >= 70) return 'bg-yellow-500';
    if ($rate >= 50) return 'bg-orange-500';
    return 'bg-red-500';
}

function formatDate($date) {
    if (!$date) return 'غير محدد';
    return date('Y-m-d', strtotime($date));
}

function getDaysUntil($date) {
    if (!$date) return 'غير محدد';
    
    $now = new DateTime();
    $target = new DateTime($date);
    $interval = $now->diff($target);
    
    if ($target < $now) {
        return 'تجاوز الموعد';
    } else {
        return $interval->days . ' يوم متبقي';
    }
}

function getDaysColor($date) {
    if (!$date) return 'text-gray-400';
    
    $now = new DateTime();
    $target = new DateTime($date);
    $interval = $now->diff($target);
    
    if ($target < $now) {
        return 'text-red-400 font-bold';
    } elseif ($interval->days <= 7) {
        return 'text-orange-400';
    } elseif ($interval->days <= 30) {
        return 'text-yellow-400';
    } else {
        return 'text-green-400';
    }
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-indigo-300">
            <i class="fas fa-shield-alt ml-2"></i>
            فحص الامتثال
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="runComplianceScan()" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-lg font-semibold transition-all flex items-center operation-glow">
                <i class="fas fa-search ml-2"></i>
                فحص امتثال شامل
            </button>
            <button onclick="refreshCompliance()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات KPIs الرئيسية -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">متوسط الامتثال</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $avg_compliance; ?>%</p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                جميع المعايير
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">معايير متوافقة</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $compliant_count; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                من أصل <?php echo count($standards); ?> معيار
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">قيد التقدم</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $in_progress_count; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-spinner text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                تحتاج متابعة
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-200 text-sm mb-1">انحرافات نشطة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $active_violations; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                حرجة: <?php echo $critical_violations; ?>
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">معايير غير متوافقة</span>
            <span class="text-lg font-bold text-red-400"><?php echo $non_compliant_count; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">وحدات مسؤولة</span>
            <span class="text-lg font-bold text-green-400"><?php echo count($units_responsibility); ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- معايير الامتثال والانحرافات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- معايير الامتثال -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-blue-300 flex items-center">
                <i class="fas fa-list ml-2"></i>
                معايير الامتثال
            </h3>
            <span class="px-3 py-1 bg-blue-600 rounded-full text-xs font-bold"><?php echo count($standards); ?></span>
        </div>
        
        <?php if (empty($standards)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-info-circle text-4xl text-gray-500 mb-3"></i>
            <p class="text-gray-400">لا توجد معايير امتثال</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($standards as $standard): ?>
                <div class="p-4 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="font-bold text-white"><?php echo $standard['name']; ?></h4>
                            <p class="text-xs text-gray-400"><?php echo $standard['code']; ?></p>
                        </div>
                        <?php echo getComplianceStatusBadge($standard['status']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400">نسبة الامتثال</span>
                            <span class="text-sm font-bold <?php echo getComplianceColor($standard['compliance_rate']); ?>">
                                <?php echo $standard['compliance_rate']; ?>%
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo getComplianceProgressColor($standard['compliance_rate']); ?>" 
                                 style="width: <?php echo $standard['compliance_rate']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-400">الوحدة المسؤولة:</span>
                            <span class="text-blue-400 mr-1"><?php echo $standard['responsible_unit_name'] ?? 'غير محدد'; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">آخر تدقيق:</span>
                            <span class="text-gray-300 mr-1"><?php echo formatDate($standard['last_audit']); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($standard['next_audit']): ?>
                    <div class="mt-2 text-xs">
                        <span class="text-gray-400">التدقيق القادم:</span>
                        <span class="<?php echo getDaysColor($standard['next_audit']); ?> mr-1">
                            <?php echo formatDate($standard['next_audit']); ?> (<?php echo getDaysUntil($standard['next_audit']); ?>)
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex space-x-2 space-x-reverse mt-3">
                        <button onclick="viewStandardDetails(<?php echo $standard['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <button onclick="scheduleAudit(<?php echo $standard['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-calendar-alt ml-1"></i>
                            جدولة تدقيق
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- الانحرافات النشطة -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-red-300 flex items-center">
                <i class="fas fa-exclamation-triangle ml-2"></i>
                الانحرافات النشطة
            </h3>
            <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo $active_violations; ?></span>
        </div>
        
        <?php if (empty($violations)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <p class="text-green-400">لا توجد انحرافات</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($violations as $violation): 
                    if ($violation['status'] == 'resolved') continue;
                ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 border-<?php echo $violation['severity'] == 'critical' ? 'red' : ($violation['severity'] == 'high' ? 'orange' : ($violation['severity'] == 'medium' ? 'yellow' : 'blue')); ?>-500">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-white"><?php echo $violation['title']; ?></h4>
                        <?php echo getViolationSeverityBadge($violation['severity']); ?>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-2"><?php echo $violation['description']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                        <div>
                            <span class="text-gray-400">المعيار:</span>
                            <span class="text-blue-400 mr-1"><?php echo $violation['standard_code']; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">تاريخ الاكتشاف:</span>
                            <span class="text-gray-300 mr-1"><?php echo formatDate($violation['detected_date']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs mb-3">
                        <div>
                            <span class="text-gray-400">المسؤول:</span>
                            <span class="text-green-400 mr-1"><?php echo $violation['assigned_to_name'] ?? 'غير معين'; ?></span>
                        </div>
                        <div>
                            <?php echo getViolationStatusBadge($violation['status']); ?>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="viewViolationDetails(<?php echo $violation['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <?php if ($violation['status'] != 'resolved'): ?>
                        <button onclick="resolveViolation(<?php echo $violation['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            حل
                        </button>
                        <?php endif; ?>
                        <button onclick="assignViolation(<?php echo $violation['id']; ?>)" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-user-plus ml-1"></i>
                            تكليف
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات الانحرافات وأحدث التدقيقات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- إحصائيات الانحرافات -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-orange-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع الانحرافات
        </h3>
        
        <div class="grid grid-cols-2 gap-6">
            <!-- حسب الشدة -->
            <div>
                <h4 class="text-sm font-semibold text-gray-300 mb-3 text-center">حسب الشدة</h4>
                <div class="space-y-3">
                    <?php 
                    $severity_labels = [
                        'critical' => 'حرجة',
                        'high' => 'عالية',
                        'medium' => 'متوسطة',
                        'low' => 'منخفضة'
                    ];
                    $total_violations = array_sum($violations_by_severity);
                    foreach ($severity_labels as $key => $label): 
                        $count = $violations_by_severity[$key] ?? 0;
                        $percentage = $total_violations > 0 ? round(($count / $total_violations) * 100, 1) : 0;
                        $color = $key == 'critical' ? 'bg-red-500' : ($key == 'high' ? 'bg-orange-500' : ($key == 'medium' ? 'bg-yellow-500' : 'bg-blue-500'));
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400"><?php echo $label; ?></span>
                            <span class="text-xs font-bold"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- حسب الحالة -->
            <div>
                <h4 class="text-sm font-semibold text-gray-300 mb-3 text-center">حسب الحالة</h4>
                <div class="space-y-3">
                    <?php 
                    $status_labels = [
                        'open' => 'مفتوحة',
                        'in-progress' => 'قيد المعالجة',
                        'resolved' => 'تم الحل'
                    ];
                    $total_all = array_sum($violations_by_status);
                    foreach ($status_labels as $key => $label): 
                        $count = $violations_by_status[$key] ?? 0;
                        $percentage = $total_all > 0 ? round(($count / $total_all) * 100, 1) : 0;
                        $color = $key == 'open' ? 'bg-red-500' : ($key == 'in-progress' ? 'bg-yellow-500' : 'bg-green-500');
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400"><?php echo $label; ?></span>
                            <span class="text-xs font-bold"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- أحدث التدقيقات -->
    <?php if (!empty($recent_audits)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
            <i class="fas fa-history ml-2"></i>
            آخر التدقيقات
        </h3>
        
        <div class="space-y-3">
            <?php foreach ($recent_audits as $audit): ?>
            <div class="p-3 bg-slate-800 rounded-lg flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-white text-sm"><?php echo $audit['name']; ?></h4>
                    <p class="text-xs text-gray-400"><?php echo $audit['code']; ?> | <?php echo $audit['unit_name'] ?? 'غير محدد'; ?></p>
                </div>
                <div class="text-left">
                    <span class="text-xs text-gray-400"><?php echo formatDate($audit['last_audit']); ?></span>
                    <div class="flex items-center mt-1">
                        <span class="text-xs font-bold <?php echo getComplianceColor($audit['compliance_rate']); ?> ml-2">
                            <?php echo $audit['compliance_rate']; ?>%
                        </span>
                        <?php echo getComplianceStatusBadge($audit['status']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- مسؤوليات الوحدات -->
<!-- ============================================= -->
<?php if (!empty($units_responsibility)): ?>
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
        <i class="fas fa-building ml-2"></i>
        مسؤوليات الوحدات
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($units_responsibility as $unit): ?>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-bold text-white"><?php echo $unit['name']; ?></h4>
                <span class="px-2 py-1 bg-blue-600 rounded-full text-xs"><?php echo $unit['standards_count']; ?> معايير</span>
            </div>
            
            <div class="mb-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-400">متوسط الامتثال</span>
                    <span class="text-sm font-bold <?php echo getComplianceColor($unit['avg_compliance']); ?>">
                        <?php echo $unit['avg_compliance']; ?>%
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo getComplianceProgressColor($unit['avg_compliance']); ?>" 
                         style="width: <?php echo $unit['avg_compliance']; ?>%"></div>
                </div>
            </div>
            
            <button onclick="viewUnitCompliance(<?php echo (int)$unit['id']; ?>)" class="w-full mt-2 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
    <i class="fas fa-eye ml-1"></i>
    عرض التفاصيل
</button>
            
 
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- جدول جميع الانحرافات -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-yellow-300 flex items-center">
            <i class="fas fa-table ml-2"></i>
            جميع الانحرافات
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-violations" placeholder="بحث في الانحرافات..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="severity-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الشدات</option>
                <option value="critical">حرجة</option>
                <option value="high">عالية</option>
                <option value="medium">متوسطة</option>
                <option value="low">منخفضة</option>
            </select>
            <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الحالات</option>
                <option value="open">مفتوحة</option>
                <option value="in-progress">قيد المعالجة</option>
                <option value="resolved">تم الحل</option>
            </select>
        </div>
    </div>

    <?php if (empty($violations)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد انحرافات</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="violations-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">المعيار</th>
                    <th class="px-6 py-4 text-sm font-semibold">المسؤول</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ الاكتشاف</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                    <th class="px-6 py-4 text-sm font-semibold">عنوان الانحراف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $violation): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors violation-row" 
                    data-severity="<?php echo $violation['severity']; ?>"
                    data-status="<?php echo $violation['status']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewViolationDetails(<?php echo $violation['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($violation['status'] != 'resolved'): ?>
                            <button onclick="resolveViolation(<?php echo $violation['id']; ?>)" class="text-green-400 hover:text-green-300" title="حل">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="assignViolation(<?php echo $violation['id']; ?>)" class="text-purple-400 hover:text-purple-300" title="تكليف">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getViolationSeverityBadge($violation['severity']); ?></td>
                    <td class="px-6 py-4"><?php echo getViolationStatusBadge($violation['status']); ?></td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-300"><?php echo $violation['standard_code']; ?></span>
                            <span class="text-xs text-gray-500"><?php echo $violation['standard_name']; ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $violation['assigned_to_name'] ?? 'غير معين'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo formatDate($violation['detected_date']); ?></td>
                    <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $violation['description']; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $violation['title']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($violations); ?> انحراف
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-600 rounded-full ml-1"></span>
                حرج: <?php echo $violations_by_severity['critical']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-orange-500 rounded-full ml-1"></span>
                عالي: <?php echo $violations_by_severity['high']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                متوسط: <?php echo $violations_by_severity['medium']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                منخفض: <?php echo $violations_by_severity['low']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل المعيار -->
<!-- ============================================= -->
<div id="standard-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeStandardModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400" id="standard-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل المعيار
            </h3>
        </div>
        <div id="standard-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل الانحراف -->
<!-- ============================================= -->
<div id="violation-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeViolationModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-red-400" id="violation-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل الانحراف
            </h3>
        </div>
        <div id="violation-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة جدولة تدقيق -->
<!-- ============================================= -->
<div id="schedule-audit-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeScheduleAuditModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-calendar-alt ml-2"></i>
                جدولة تدقيق
            </h3>
        </div>

        <form id="schedule-audit-form" onsubmit="saveAuditSchedule(event)" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">تاريخ التدقيق</label>
                <input type="date" name="audit_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                <textarea name="notes" rows="3" 
                          class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"
                          placeholder="أي ملاحظات إضافية..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ
                </button>
                <button type="button" onclick="closeScheduleAuditModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
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
// دوال الصفحة
// =============================================

let currentStandardId = null;
let currentViolationId = null;

function runComplianceScan() {
    if (typeof showLoading === 'function') showLoading();
    if (typeof showNotification === 'function') {
        showNotification('بدء فحص الامتثال الشامل...', 'info');
    }
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('اكتمل فحص الامتثال', 'success');
        }
    }, 3000);
}

function refreshCompliance() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('تم تحديث بيانات الامتثال', 'success');
        }
        location.reload();
    }, 1500);
}

function viewStandardDetails(standardId) {
    currentStandardId = standardId;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل المعيار #${standardId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="scheduleAudit(${standardId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">جدولة تدقيق</button>
                    <button onclick="closeStandardModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('standard-details-content').innerHTML = details;
        document.getElementById('standard-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل المعيار #${standardId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('standard-details-modal').classList.remove('hidden');
        document.getElementById('standard-details-modal').classList.add('flex');
    }, 1000);
}

function closeStandardModal() {
    document.getElementById('standard-details-modal').classList.add('hidden');
    document.getElementById('standard-details-modal').classList.remove('flex');
}

function viewViolationDetails(violationId) {
    currentViolationId = violationId;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل الانحراف #${violationId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="resolveViolation(${violationId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">حل</button>
                    <button onclick="assignViolation(${violationId})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg">تكليف</button>
                    <button onclick="closeViolationModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('violation-details-content').innerHTML = details;
        document.getElementById('violation-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل الانحراف #${violationId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('violation-details-modal').classList.remove('hidden');
        document.getElementById('violation-details-modal').classList.add('flex');
    }, 1000);
}

function closeViolationModal() {
    document.getElementById('violation-details-modal').classList.add('hidden');
    document.getElementById('violation-details-modal').classList.remove('flex');
}

function scheduleAudit(standardId) {
    currentStandardId = standardId;
    closeStandardModal();
    document.getElementById('schedule-audit-modal').classList.remove('hidden');
    document.getElementById('schedule-audit-modal').classList.add('flex');
}

function closeScheduleAuditModal() {
    document.getElementById('schedule-audit-modal').classList.add('hidden');
    document.getElementById('schedule-audit-modal').classList.remove('flex');
}

function saveAuditSchedule(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeScheduleAuditModal();
        if (typeof showNotification === 'function') {
            showNotification('تم جدولة التدقيق بنجاح', 'success');
        }
    }, 1500);
}

function resolveViolation(violationId) {
    if (typeof showNotification === 'function') {
        showNotification(`تم حل الانحراف #${violationId}`, 'success');
    }
    closeViolationModal();
    setTimeout(() => location.reload(), 1500);
}

function assignViolation(violationId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح نافذة تكليف الانحراف #${violationId}`, 'info');
    }
}

function viewUnitCompliance(unitId) {
    if (typeof showNotification === 'function') {
        showNotification(`عرض امتثال الوحدة ${unitId}`, 'info');
    }
}

// البحث والتصفية
document.getElementById('search-violations')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.violation-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.getElementById('severity-filter')?.addEventListener('change', filterViolations);
document.getElementById('status-filter')?.addEventListener('change', filterViolations);

function filterViolations() {
    const severityFilter = document.getElementById('severity-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('.violation-row');
    
    rows.forEach(row => {
        const severityMatch = severityFilter === 'all' || row.dataset.severity === severityFilter;
        const statusMatch = statusFilter === 'all' || row.dataset.status === statusFilter;
        
        if (severityMatch && statusMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// استدعاء دوال الإشعارات من الصفحة الرئيسية
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
    background: #6366f1;
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
.operation-glow {
    box-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
}
</style>