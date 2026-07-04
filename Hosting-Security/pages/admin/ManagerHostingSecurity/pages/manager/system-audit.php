<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/system_audit.php
// تدقيق النظام - نسخة كاملة ومفصلة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة عن التدقيقات
    // =============================================
    
    // إجمالي التدقيقات
    $stmt = $db->query("SELECT COUNT(*) FROM system_audits");
    $total_audits = $stmt->fetchColumn() ?: 0;
    
    // التدقيقات المكتملة
    $stmt = $db->query("SELECT COUNT(*) FROM system_audits WHERE status = 'completed'");
    $completed_audits = $stmt->fetchColumn() ?: 0;
    
    // التدقيقات قيد التنفيذ
    $stmt = $db->query("SELECT COUNT(*) FROM system_audits WHERE status = 'in-progress'");
    $in_progress_audits = $stmt->fetchColumn() ?: 0;
    
    // التدقيقات المجدولة
    $stmt = $db->query("SELECT COUNT(*) FROM system_audits WHERE status = 'scheduled'");
    $scheduled_audits = $stmt->fetchColumn() ?: 0;
    
    // إجمالي النتائج
    $stmt = $db->query("SELECT COUNT(*) FROM audit_findings");
    $total_findings = $stmt->fetchColumn() ?: 0;
    
    // النتائج الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM audit_findings WHERE severity = 'critical' AND status != 'resolved'");
    $critical_findings = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. قائمة التدقيقات
    // =============================================
    
    $audits = $db->query("
        SELECT 
            sa.*,
            u.full_name as auditor_name,
            u2.full_name as created_by_name
        FROM system_audits sa
        LEFT JOIN users u ON sa.auditor_id = u.id
        LEFT JOIN users u2 ON sa.created_by = u2.id
        ORDER BY 
            CASE sa.status
                WHEN 'in-progress' THEN 1
                WHEN 'scheduled' THEN 2
                WHEN 'completed' THEN 3
            END,
            sa.scheduled_date DESC
    ")->fetchAll();
    
    // =============================================
    // 3. قائمة نتائج التدقيق
    // =============================================
    
    $findings = $db->query("
        SELECT 
            af.*,
            sa.name as audit_name,
            sa.code as audit_code,
            u.full_name as assigned_to_name,
            u2.full_name as created_by_name
        FROM audit_findings af
        LEFT JOIN system_audits sa ON af.audit_id = sa.id
        LEFT JOIN users u ON af.assigned_to = u.id
        LEFT JOIN users u2 ON af.created_by = u2.id
        ORDER BY 
            CASE af.severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            CASE af.status
                WHEN 'open' THEN 1
                WHEN 'in-progress' THEN 2
                WHEN 'resolved' THEN 3
            END,
            af.detected_date DESC
    ")->fetchAll();
    
    // =============================================
    // 4. إحصائيات النتائج حسب الشدة
    // =============================================
    
    $findings_by_severity = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    $stmt = $db->query("SELECT severity, COUNT(*) as count FROM audit_findings WHERE status != 'resolved' GROUP BY severity");
    while ($row = $stmt->fetch()) {
        if (isset($findings_by_severity[$row['severity']])) {
            $findings_by_severity[$row['severity']] = $row['count'];
        }
    }
    
    // =============================================
    // 5. إحصائيات النتائج حسب الحالة
    // =============================================
    
    $findings_by_status = [
        'open' => 0,
        'in-progress' => 0,
        'resolved' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM audit_findings GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($findings_by_status[$row['status']])) {
            $findings_by_status[$row['status']] = $row['count'];
        }
    }
    
    // =============================================
    // 6. آخر التدقيقات المكتملة
    // =============================================
    
    $recent_completed = $db->query("
        SELECT 
            sa.*,
            u.full_name as auditor_name,
            COUNT(af.id) as findings_count,
            SUM(CASE WHEN af.severity = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM system_audits sa
        LEFT JOIN users u ON sa.auditor_id = u.id
        LEFT JOIN audit_findings af ON sa.id = af.audit_id
        WHERE sa.status = 'completed'
        GROUP BY sa.id
        ORDER BY sa.completed_date DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 7. أكثر المدققين نشاطًا
    // =============================================
    
    $top_auditors = $db->query("
        SELECT 
            u.full_name,
            COUNT(sa.id) as audits_count,
            SUM(CASE WHEN sa.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            COUNT(af.id) as findings_found
        FROM users u
        LEFT JOIN system_audits sa ON u.id = sa.auditor_id
        LEFT JOIN audit_findings af ON sa.id = af.audit_id
        WHERE u.role IN ('auditor', 'admin')
        GROUP BY u.id
        HAVING audits_count > 0
        ORDER BY audits_count DESC, completed_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 8. توزيع النتائج حسب النوع
    // =============================================
    
    $findings_by_category = $db->query("
        SELECT 
            category,
            COUNT(*) as count,
            SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM audit_findings
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll();
    
    // =============================================
    // 9. متوسط وقت حل النتائج
    // =============================================
    
    $stmt = $db->query("
        SELECT 
            ROUND(AVG(TIMESTAMPDIFF(DAY, detected_date, resolved_date)), 1) as avg_days
        FROM audit_findings
        WHERE resolved_date IS NOT NULL
    ");
    $avg_resolution_days = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 10. إحصائيات شهرية لآخر 6 أشهر
    // =============================================
    
    $monthly_stats = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('F', strtotime("-$i months"));
        $monthly_stats[$month] = [
            'month' => $month_name,
            'audits' => 0,
            'findings' => 0,
            'critical' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(scheduled_date, '%Y-%m') as month,
            COUNT(*) as audits
        FROM system_audits
        WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m')
    ");
    while ($row = $stmt->fetch()) {
        if (isset($monthly_stats[$row['month']])) {
            $monthly_stats[$row['month']]['audits'] = $row['audits'];
        }
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(detected_date, '%Y-%m') as month,
            COUNT(*) as findings,
            SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical
        FROM audit_findings
        WHERE detected_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(detected_date, '%Y-%m')
    ");
    while ($row = $stmt->fetch()) {
        if (isset($monthly_stats[$row['month']])) {
            $monthly_stats[$row['month']]['findings'] = $row['findings'];
            $monthly_stats[$row['month']]['critical'] = $row['critical'];
        }
    }
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function getAuditStatusBadge($status) {
    return match($status) {
        'scheduled' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">مجدول</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد التنفيذ</span>',
        'completed' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">مكتمل</span>',
        'cancelled' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">ملغي</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getFindingSeverityBadge($severity) {
    return match($severity) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'high' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">عالي</span>',
        'medium' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">متوسط</span>',
        'low' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منخفض</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getFindingStatusBadge($status) {
    return match($status) {
        'open' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">مفتوح</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد المعالجة</span>',
        'resolved' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تم الحل</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getFindingCategoryText($category) {
    return match($category) {
        'security' => 'أمني',
        'performance' => 'أداء',
        'compliance' => 'امتثال',
        'documentation' => 'توثيق',
        'access' => 'صلاحيات',
        'configuration' => 'تكوين',
        'other' => 'أخرى',
        default => $category
    };
}



function formatDate($date) {
    if (!$date) return 'غير محدد';
    return date('Y-m-d', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return 'غير محدد';
    return date('Y-m-d H:i', strtotime($datetime));
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
    } elseif ($interval->days <= 3) {
        return 'text-orange-400';
    } elseif ($interval->days <= 7) {
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
        <h1 class="text-3xl font-bold text-right text-purple-300">
            <i class="fas fa-clipboard-check ml-2"></i>
            تدقيق النظام
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="createNewAudit()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-plus ml-2"></i>
                تدقيق جديد
            </button>
            <button onclick="refreshAuditData()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
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
                    <p class="text-blue-200 text-sm mb-1">إجمالي التدقيقات</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $total_audits; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                مكتمل: <?php echo $completed_audits; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">قيد التنفيذ</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $in_progress_audits; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-spinner text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                مجدول: <?php echo $scheduled_audits; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-200 text-sm mb-1">نتائج التدقيق</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $total_findings; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                حرجة: <?php echo $critical_findings; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">متوسط وقت الحل</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $avg_resolution_days; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                يوم
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">نتائج مفتوحة</span>
            <span class="text-lg font-bold text-red-400"><?php echo $findings_by_status['open'] ?? 0; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">نتائج قيد المعالجة</span>
            <span class="text-lg font-bold text-yellow-400"><?php echo $findings_by_status['in-progress'] ?? 0; ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني الشهري -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        إحصائيات التدقيقات - آخر 6 أشهر
    </h3>
    <div class="h-80 relative" id="audit-chart-container">
    <canvas id="auditChart"></canvas>
</div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تدقيقات</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-orange-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">نتائج</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">نتائج حرجة</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- التدقيقات والنتائج الحرجة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- التدقيقات الحالية -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-blue-300 flex items-center">
                <i class="fas fa-clipboard-list ml-2"></i>
                التدقيقات الحالية
            </h3>
            <span class="px-3 py-1 bg-blue-600 rounded-full text-xs font-bold"><?php echo count($audits); ?></span>
        </div>
        
        <?php if (empty($audits)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-info-circle text-4xl text-gray-500 mb-3"></i>
            <p class="text-gray-400">لا توجد تدقيقات</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($audits as $audit): ?>
                <div class="p-4 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="font-bold text-white"><?php echo $audit['name']; ?></h4>
                            <p class="text-xs text-gray-400"><?php echo $audit['code']; ?></p>
                        </div>
                        <?php echo getAuditStatusBadge($audit['status']); ?>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-3"><?php echo $audit['description']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                        <div>
                            <span class="text-gray-400">المدقق:</span>
                            <span class="text-blue-400 mr-1"><?php echo $audit['auditor_name'] ?? 'غير معين'; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">النطاق:</span>
                            <span class="text-gray-300 mr-1"><?php echo $audit['scope']; ?></span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-400">تاريخ التدقيق:</span>
                            <span class="<?php echo getDaysColor($audit['scheduled_date']); ?> mr-1">
                                <?php echo formatDate($audit['scheduled_date']); ?>
                            </span>
                        </div>
                        <?php if ($audit['status'] == 'scheduled'): ?>
                        <div>
                            <span class="text-gray-400">المتبقي:</span>
                            <span class="<?php echo getDaysColor($audit['scheduled_date']); ?> mr-1">
                                <?php echo getDaysUntil($audit['scheduled_date']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex space-x-2 space-x-reverse mt-3">
                        <button onclick="viewAuditDetails(<?php echo $audit['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <?php if ($audit['status'] == 'scheduled'): ?>
                        <button onclick="startAudit(<?php echo $audit['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-play ml-1"></i>
                            بدء
                        </button>
                        <?php endif; ?>
                        <?php if ($audit['status'] == 'in-progress'): ?>
                        <button onclick="completeAudit(<?php echo $audit['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            إكمال
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- النتائج الحرجة -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-red-300 flex items-center">
                <i class="fas fa-exclamation-triangle ml-2"></i>
                النتائج الحرجة
            </h3>
            <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo $critical_findings; ?></span>
        </div>
        
        <?php if (empty($findings) || $critical_findings == 0): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <p class="text-green-400">لا توجد نتائج حرجة</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($findings as $finding): 
                    if ($finding['severity'] != 'critical' || $finding['status'] == 'resolved') continue;
                ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 border-red-500">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-white"><?php echo $finding['title']; ?></h4>
                        <?php echo getFindingSeverityBadge($finding['severity']); ?>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-2"><?php echo $finding['description']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                        <div>
                            <span class="text-gray-400">التدقيق:</span>
                            <span class="text-blue-400 mr-1"><?php echo $finding['audit_code']; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">الفئة:</span>
                            <span class="text-gray-300 mr-1"><?php echo getFindingCategoryText($finding['category']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs mb-3">
                        <div>
                            <span class="text-gray-400">المسؤول:</span>
                            <span class="text-green-400 mr-1"><?php echo $finding['assigned_to_name'] ?? 'غير معين'; ?></span>
                        </div>
                        <div>
                            <?php echo getFindingStatusBadge($finding['status']); ?>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="viewFindingDetails(<?php echo $finding['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <?php if ($finding['status'] != 'resolved'): ?>
                        <button onclick="resolveFinding(<?php echo $finding['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            حل
                        </button>
                        <?php endif; ?>
                        <button onclick="assignFinding(<?php echo $finding['id']; ?>)" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-1 rounded text-xs transition-colors">
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
<!-- إحصائيات النتائج وأكثر المدققين نشاطًا -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع النتائج -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-orange-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع النتائج
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
                    $total_active = array_sum($findings_by_severity);
                    foreach ($severity_labels as $key => $label): 
                        $count = $findings_by_severity[$key] ?? 0;
                        $percentage = $total_active > 0 ? round(($count / $total_active) * 100, 1) : 0;
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
                    $total_all = array_sum($findings_by_status);
                    foreach ($status_labels as $key => $label): 
                        $count = $findings_by_status[$key] ?? 0;
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
        
        <!-- توزيع حسب الفئة -->
        <?php if (!empty($findings_by_category)): ?>
        <div class="mt-6">
            <h4 class="text-sm font-semibold text-gray-300 mb-3 text-center">حسب الفئة</h4>
            <div class="space-y-3">
                <?php foreach ($findings_by_category as $category): ?>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400"><?php echo getFindingCategoryText($category['category']); ?></span>
                    <div class="flex items-center">
                        <span class="text-xs font-bold ml-2"><?php echo $category['count']; ?></span>
                        <?php if ($category['critical_count'] > 0): ?>
                        <span class="px-2 py-0.5 bg-red-600 rounded-full text-xs">حرج: <?php echo $category['critical_count']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- أكثر المدققين نشاطًا -->
    <?php if (!empty($top_auditors)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-users ml-2"></i>
            أكثر المدققين نشاطًا
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($top_auditors as $auditor): ?>
            <div class="bg-slate-800 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-white"><?php echo $auditor['full_name']; ?></p>
                    <div class="flex items-center space-x-2 space-x-reverse mt-1">
                        <span class="text-xs px-2 py-0.5 bg-blue-600 rounded-full">تدقيقات: <?php echo $auditor['audits_count']; ?></span>
                        <span class="text-xs px-2 py-0.5 bg-green-600 rounded-full">مكتمل: <?php echo $auditor['completed_count']; ?></span>
                    </div>
                </div>
                <div class="text-center">
                    <span class="text-2xl font-bold text-yellow-400"><?php echo $auditor['findings_found']; ?></span>
                    <p class="text-xs text-gray-400">نتيجة</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- آخر التدقيقات المكتملة -->
<!-- ============================================= -->
<?php if (!empty($recent_completed)): ?>
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-history ml-2"></i>
        آخر التدقيقات المكتملة
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($recent_completed as $audit): ?>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-bold text-white"><?php echo $audit['name']; ?></h4>
                <span class="text-xs text-gray-400"><?php echo $audit['code']; ?></span>
            </div>
            
            <p class="text-xs text-gray-400 mb-3">المدقق: <?php echo $audit['auditor_name'] ?? 'غير معين'; ?></p>
            
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-gray-400">تاريخ الإكمال:</span>
                <span class="text-xs text-green-400"><?php echo formatDate($audit['completed_date']); ?></span>
            </div>
            
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">النتائج:</span>
                <div class="flex items-center">
                    <span class="text-sm font-bold ml-2"><?php echo $audit['findings_count']; ?></span>
                    <?php if ($audit['critical_count'] > 0): ?>
                    <span class="px-2 py-0.5 bg-red-600 rounded-full text-xs">حرج: <?php echo $audit['critical_count']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <button onclick="viewAuditDetails(<?php echo $audit['id']; ?>)" class="w-full mt-3 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                <i class="fas fa-eye ml-1"></i>
                عرض التفاصيل
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- جدول جميع النتائج -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-yellow-300 flex items-center">
            <i class="fas fa-table ml-2"></i>
            جميع نتائج التدقيق
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-findings" placeholder="بحث في النتائج..." 
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

    <?php if (empty($findings)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد نتائج تدقيق</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="findings-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">التدقيق</th>
                    <th class="px-6 py-4 text-sm font-semibold">الفئة</th>
                    <th class="px-6 py-4 text-sm font-semibold">المسؤول</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ الاكتشاف</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                    <th class="px-6 py-4 text-sm font-semibold">عنوان النتيجة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($findings as $finding): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors finding-row" 
                    data-severity="<?php echo $finding['severity']; ?>"
                    data-status="<?php echo $finding['status']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewFindingDetails(<?php echo $finding['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($finding['status'] != 'resolved'): ?>
                            <button onclick="resolveFinding(<?php echo $finding['id']; ?>)" class="text-green-400 hover:text-green-300" title="حل">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="assignFinding(<?php echo $finding['id']; ?>)" class="text-purple-400 hover:text-purple-300" title="تكليف">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getFindingSeverityBadge($finding['severity']); ?></td>
                    <td class="px-6 py-4"><?php echo getFindingStatusBadge($finding['status']); ?></td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-300"><?php echo $finding['audit_code']; ?></span>
                            <span class="text-xs text-gray-500"><?php echo $finding['audit_name']; ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo getFindingCategoryText($finding['category']); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $finding['assigned_to_name'] ?? 'غير معين'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo formatDate($finding['detected_date']); ?></td>
                    <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $finding['description']; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $finding['title']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($findings); ?> نتيجة
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-600 rounded-full ml-1"></span>
                حرج: <?php echo $findings_by_severity['critical']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-orange-500 rounded-full ml-1"></span>
                عالي: <?php echo $findings_by_severity['high']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                متوسط: <?php echo $findings_by_severity['medium']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                منخفض: <?php echo $findings_by_severity['low']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء تدقيق جديد -->
<!-- ============================================= -->
<div id="create-audit-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateAuditModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                تدقيق جديد
            </h3>
        </div>

        <form id="create-audit-form" onsubmit="saveNewAudit(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">كود التدقيق</label>
                    <input type="text" name="code" required pattern="AUD-\d{4}" placeholder="AUD-1234" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم التدقيق</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المدقق</label>
                    <select name="auditor_id" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">اختر المدقق</option>
                        <?php
                        $auditors = $db->query("SELECT id, full_name FROM users WHERE role IN ('auditor', 'admin') ORDER BY full_name")->fetchAll();
                        foreach ($auditors as $auditor):
                        ?>
                        <option value="<?php echo $auditor['id']; ?>"><?php echo $auditor['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ التدقيق</label>
                    <input type="date" name="scheduled_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">النطاق</label>
                    <input type="text" name="scope" required placeholder="مثال: جميع الأنظمة، أمن المعلومات، إلخ" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ
                </button>
                <button type="button" onclick="closeCreateAuditModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل التدقيق -->
<!-- ============================================= -->
<div id="audit-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAuditModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400" id="audit-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل التدقيق
            </h3>
        </div>
        <div id="audit-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل النتيجة -->
<!-- ============================================= -->
<div id="finding-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeFindingModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-red-400" id="finding-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل النتيجة
            </h3>
        </div>
        <div id="finding-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>// =============================================
// الرسم البياني
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('auditChart')?.getContext('2d');
    if (!ctx) return;
    
    const monthlyData = <?php echo json_encode(array_values($monthly_stats)); ?>;
    
    console.log('📊 بيانات الرسم البياني:', monthlyData); // أضف هذا السطر للتصحيح
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'تدقيقات',
                    data: monthlyData.map(d => d.audits),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                },
                {
                    label: 'نتائج',
                    data: monthlyData.map(d => d.findings),
                    backgroundColor: 'rgba(249, 115, 22, 0.7)',
                    borderColor: '#f97316',
                    borderWidth: 1
                },
                {
                    label: 'نتائج حرجة',
                    data: monthlyData.map(d => d.critical),
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1
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

let currentAuditId = null;
let currentFindingId = null;

function createNewAudit() {
    document.getElementById('create-audit-modal').classList.remove('hidden');
    document.getElementById('create-audit-modal').classList.add('flex');
}

function closeCreateAuditModal() {
    document.getElementById('create-audit-modal').classList.add('hidden');
    document.getElementById('create-audit-modal').classList.remove('flex');
}

function saveNewAudit(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeCreateAuditModal();
        if (typeof showNotification === 'function') {
            showNotification('تم إنشاء التدقيق الجديد بنجاح', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshAuditData() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('تم تحديث بيانات التدقيق', 'success');
        }
        location.reload();
    }, 1500);
}

function viewAuditDetails(auditId) {
    currentAuditId = auditId;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل التدقيق #${auditId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="startAudit(${auditId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">بدء التدقيق</button>
                    <button onclick="closeAuditModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('audit-details-content').innerHTML = details;
        document.getElementById('audit-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل التدقيق #${auditId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('audit-details-modal').classList.remove('hidden');
        document.getElementById('audit-details-modal').classList.add('flex');
    }, 1000);
}

function closeAuditModal() {
    document.getElementById('audit-details-modal').classList.add('hidden');
    document.getElementById('audit-details-modal').classList.remove('flex');
}

function viewFindingDetails(findingId) {
    currentFindingId = findingId;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل النتيجة #${findingId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="resolveFinding(${findingId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">حل</button>
                    <button onclick="assignFinding(${findingId})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg">تكليف</button>
                    <button onclick="closeFindingModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('finding-details-content').innerHTML = details;
        document.getElementById('finding-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل النتيجة #${findingId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('finding-details-modal').classList.remove('hidden');
        document.getElementById('finding-details-modal').classList.add('flex');
    }, 1000);
}

function closeFindingModal() {
    document.getElementById('finding-details-modal').classList.add('hidden');
    document.getElementById('finding-details-modal').classList.remove('flex');
}

function startAudit(auditId) {
    if (typeof showNotification === 'function') {
        showNotification(`بدء التدقيق #${auditId}`, 'info');
    }
    closeAuditModal();
}

function completeAudit(auditId) {
    if (typeof showNotification === 'function') {
        showNotification(`تم إكمال التدقيق #${auditId}`, 'success');
    }
    setTimeout(() => location.reload(), 1500);
}

function resolveFinding(findingId) {
    if (typeof showNotification === 'function') {
        showNotification(`تم حل النتيجة #${findingId}`, 'success');
    }
    closeFindingModal();
    setTimeout(() => location.reload(), 1500);
}

function assignFinding(findingId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح نافذة تكليف النتيجة #${findingId}`, 'info');
    }
}

// البحث والتصفية
document.getElementById('search-findings')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.finding-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.getElementById('severity-filter')?.addEventListener('change', filterFindings);
document.getElementById('status-filter')?.addEventListener('change', filterFindings);

function filterFindings() {
    const severityFilter = document.getElementById('severity-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('.finding-row');
    
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
    background: #a855f7;
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
    box-shadow: 0 0 15px rgba(168, 85, 247, 0.5);
}
</style>