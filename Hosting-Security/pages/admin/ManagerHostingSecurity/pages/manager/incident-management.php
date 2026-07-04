<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/incident_management.php
// إدارة الحوادث - نسخة كاملة ومفصلة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة عن الحوادث
    // =============================================
    
    // إجمالي الحوادث
    $stmt = $db->query("SELECT COUNT(*) FROM incidents");
    $total_incidents = $stmt->fetchColumn() ?: 0;
    
    // الحوادث المفتوحة
    $stmt = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open', 'in-progress')");
    $open_incidents = $stmt->fetchColumn() ?: 0;
    
    // الحوادث الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM incidents WHERE severity = 'critical' AND status IN ('open', 'in-progress')");
    $critical_incidents = $stmt->fetchColumn() ?: 0;
    
    // الحوادث المغلقة هذا الشهر
    $stmt = $db->query("
        SELECT COUNT(*) FROM incidents 
        WHERE status = 'resolved' 
        AND resolved_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $resolved_this_month = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. توزيع الحوادث حسب الحالة
    // =============================================
    
    $status_counts = [
        'open' => 0,
        'in-progress' => 0,
        'resolved' => 0,
        'closed' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM incidents GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = $row['count'];
        }
    }
    
    // =============================================
    // 3. توزيع الحوادث حسب الشدة
    // =============================================
    
    $severity_counts = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    $stmt = $db->query("SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity");
    while ($row = $stmt->fetch()) {
        if (isset($severity_counts[$row['severity']])) {
            $severity_counts[$row['severity']] = $row['count'];
        }
    }
    
    // =============================================
    // 4. توزيع الحوادث حسب النوع
    // =============================================
    
    $type_counts = [
        'breach' => 0,
        'outage' => 0,
        'attack' => 0,
        'data_loss' => 0,
        'compliance' => 0
    ];
    
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM incidents GROUP BY type");
    while ($row = $stmt->fetch()) {
        if (isset($type_counts[$row['type']])) {
            $type_counts[$row['type']] = $row['count'];
        }
    }
    
    // =============================================
    // 5. قائمة جميع الحوادث مع التفاصيل
    // =============================================
    
    $incidents = $db->query("
        SELECT 
            i.*,
            u1.full_name as assigned_to_name,
            u2.full_name as created_by_name,
            un.name as unit_name
        FROM incidents i
        LEFT JOIN users u1 ON i.assigned_to = u1.id
        LEFT JOIN users u2 ON i.created_by = u2.id
        LEFT JOIN units un ON i.unit_id = un.id
        ORDER BY 
            CASE i.status
                WHEN 'open' THEN 1
                WHEN 'in-progress' THEN 2
                WHEN 'resolved' THEN 3
                WHEN 'closed' THEN 4
            END,
            CASE i.severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            i.detected_at DESC
    ")->fetchAll();
    
    // =============================================
    // 6. آخر 5 حوادث حرجة
    // =============================================
    
    $critical_incidents_list = $db->query("
        SELECT 
            i.*,
            u.full_name as assigned_to_name,
            un.name as unit_name
        FROM incidents i
        LEFT JOIN users u ON i.assigned_to = u.id
        LEFT JOIN units un ON i.unit_id = un.id
        WHERE i.severity = 'critical' AND i.status IN ('open', 'in-progress')
        ORDER BY i.detected_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 7. متوسط وقت الحل حسب الشدة
    // =============================================
    
    $resolution_time_by_severity = $db->query("
        SELECT 
            severity,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, detected_at, resolved_at)), 1) as avg_hours,
            COUNT(*) as count
        FROM incidents
        WHERE resolved_at IS NOT NULL
        GROUP BY severity
        ORDER BY 
            CASE severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END
    ")->fetchAll();
    
    // =============================================
    // 8. أكثر الوحدات تعرضًا للحوادث
    // =============================================
    
    $top_units_incidents = $db->query("
        SELECT 
            un.name,
            un.code,
            COUNT(i.id) as incident_count,
            SUM(CASE WHEN i.severity = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM units un
        LEFT JOIN incidents i ON un.id = i.unit_id
        GROUP BY un.id
        HAVING incident_count > 0
        ORDER BY incident_count DESC, critical_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 9. إحصائيات شهرية لآخر 6 أشهر
    // =============================================
    
    $monthly_stats = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('F', strtotime("-$i months"));
        $monthly_stats[$month] = [
            'month' => $month_name,
            'total' => 0,
            'critical' => 0,
            'resolved' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(detected_at, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM incidents
        WHERE detected_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(detected_at, '%Y-%m')
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($monthly_stats[$row['month']])) {
            $monthly_stats[$row['month']]['total'] = $row['total'];
            $monthly_stats[$row['month']]['critical'] = $row['critical'];
            $monthly_stats[$row['month']]['resolved'] = $row['resolved'];
        }
    }
    
    // =============================================
    // 10. أكثر المستخدمين تكليفًا بالحوادث
    // =============================================
    
    $top_assignees = $db->query("
        SELECT 
            u.full_name,
            COUNT(i.id) as incident_count,
            SUM(CASE WHEN i.severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
        FROM users u
        LEFT JOIN incidents i ON u.id = i.assigned_to
        GROUP BY u.id
        HAVING incident_count > 0
        ORDER BY incident_count DESC, critical_count DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function getIncidentStatusBadge($status) {
    return match($status) {
        'open' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">مفتوح</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد المعالجة</span>',
        'resolved' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تم الحل</span>',
        'closed' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">مغلق</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getIncidentSeverityBadge($severity) {
    return match($severity) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'high' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">عالي</span>',
        'medium' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">متوسط</span>',
        'low' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منخفض</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getIncidentTypeBadge($type) {
    return match($type) {
        'breach' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">اختراق</span>',
        'outage' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">انقطاع</span>',
        'attack' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">هجوم</span>',
        'data_loss' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">فقدان بيانات</span>',
        'compliance' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">امتثال</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}



?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-red-300">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            إدارة الحوادث
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="createNewIncident()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-plus ml-2"></i>
                تسجيل حادث جديد
            </button>
            <button onclick="refreshIncidents()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
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
                    <p class="text-blue-200 text-sm mb-1">إجمالي الحوادث</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $total_incidents; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-database text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                جميع الحوادث المسجلة
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-200 text-sm mb-1">حوادث مفتوحة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $open_incidents; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                بحاجة للمعالجة
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-900 to-orange-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-200 text-sm mb-1">حوادث حرجة</p>
                    <p class="text-3xl font-bold text-orange-400"><?php echo $critical_incidents; ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-fire text-2xl text-orange-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-orange-200">
                أولوية قصوى
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">تم الحل هذا الشهر</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $resolved_this_month; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                إنجاز الشهر الحالي
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">قيد المعالجة</span>
            <span class="text-lg font-bold text-yellow-400"><?php echo $status_counts['in-progress']; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">عالية الشدة</span>
            <span class="text-lg font-bold text-orange-400"><?php echo $severity_counts['high']; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">متوسط وقت الحل</span>
            <span class="text-lg font-bold text-blue-400">
                <?php 
                $avg_time = 0;
                $count = 0;
                foreach ($resolution_time_by_severity as $rt) {
                    $avg_time += $rt['avg_hours'] * $rt['count'];
                    $count += $rt['count'];
                }
                echo $count > 0 ? round($avg_time / $count, 1) : 0;
                ?> ساعة
            </span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني للحوادث الشهرية -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        إحصائيات الحوادث - آخر 6 أشهر
    </h3>
    
    <div class="h-80 relative" id="incidents-chart-container">
        <canvas id="incidentsChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">إجمالي الحوادث</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">حوادث حرجة</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تم الحل</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الحوادث الحرجة الأخيرة -->
<!-- ============================================= -->
<?php if (!empty($critical_incidents_list)): ?>
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-red-300 flex items-center">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            آخر الحوادث الحرجة
        </h3>
        <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo count($critical_incidents_list); ?></span>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($critical_incidents_list as $incident): ?>
        <div class="bg-slate-800 rounded-lg p-4 border-r-4 border-red-500 card-hover">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-bold text-white"><?php echo $incident['name']; ?></h4>
                <span class="text-xs text-gray-400">#<?php echo $incident['id']; ?></span>
            </div>
            
            <p class="text-xs text-gray-400 mb-3 line-clamp-2"><?php echo $incident['description'] ?? 'لا يوجد وصف'; ?></p>
            
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-gray-400">النوع:</span>
                <span><?php echo getIncidentTypeBadge($incident['type']); ?></span>
            </div>
            
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-gray-400">المسؤول:</span>
                <span class="text-blue-400"><?php echo $incident['assigned_to_name'] ?? 'غير معين'; ?></span>
            </div>
            
            <div class="flex items-center justify-between text-xs mb-3">
                <span class="text-gray-400">تاريخ الاكتشاف:</span>
                <span class="text-gray-300"><?php echo date('Y-m-d', strtotime($incident['detected_at'])); ?></span>
            </div>
            
            <div class="flex space-x-2 space-x-reverse mt-2">
                <button onclick="viewIncidentDetails(<?php echo $incident['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                    <i class="fas fa-eye ml-1"></i>
                    تفاصيل
                </button>
                <button onclick="assignIncident(<?php echo $incident['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                    <i class="fas fa-user-plus ml-1"></i>
                    تكليف
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- توزيع الحوادث (2 Columns) -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع حسب الحالة -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع الحوادث حسب الحالة
        </h3>
        
        <div class="space-y-4">
            <?php 
            $status_labels = [
                'open' => 'مفتوحة',
                'in-progress' => 'قيد المعالجة',
                'resolved' => 'تم الحل',
                'closed' => 'مغلقة'
            ];
            $total_status = array_sum($status_counts);
            foreach ($status_labels as $key => $label): 
                $count = $status_counts[$key] ?? 0;
                $percentage = $total_status > 0 ? round(($count / $total_status) * 100, 1) : 0;
                $color = $key == 'open' ? 'bg-red-500' : ($key == 'in-progress' ? 'bg-yellow-500' : ($key == 'resolved' ? 'bg-green-500' : 'bg-gray-500'));
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $label; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $count; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- توزيع حسب الشدة -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-orange-300 mb-4 flex items-center">
            <i class="fas fa-chart-bar ml-2"></i>
            توزيع الحوادث حسب الشدة
        </h3>
        
        <div class="space-y-4">
            <?php 
            $severity_labels = [
                'critical' => 'حرجة',
                'high' => 'عالية',
                'medium' => 'متوسطة',
                'low' => 'منخفضة'
            ];
            $total_severity = array_sum($severity_counts);
            foreach ($severity_labels as $key => $label): 
                $count = $severity_counts[$key] ?? 0;
                $percentage = $total_severity > 0 ? round(($count / $total_severity) * 100, 1) : 0;
                $color = $key == 'critical' ? 'bg-red-600' : ($key == 'high' ? 'bg-orange-500' : ($key == 'medium' ? 'bg-yellow-500' : 'bg-blue-500'));
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $label; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $count; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- متوسط وقت الحل وأكثر الوحدات تضررًا -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- متوسط وقت الحل حسب الشدة -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-clock ml-2"></i>
            متوسط وقت الحل حسب الشدة
        </h3>
        
        <?php if (empty($resolution_time_by_severity)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-info-circle text-4xl text-gray-500 mb-3"></i>
            <p class="text-gray-400">لا توجد بيانات كافية</p>
        </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($resolution_time_by_severity as $rt): ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-300"><?php echo getIncidentSeverityBadge($rt['severity']); ?></span>
                        <div class="flex items-center">
                            <span class="text-sm font-bold <?php echo getSeverityColor($rt['severity']); ?>"><?php echo $rt['avg_hours']; ?> ساعة</span>
                            <span class="text-xs text-gray-400 mr-2">(<?php echo $rt['count']; ?> حادث)</span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <?php 
                        $max_hours = 48; // 48 ساعة كحد أقصى للقياس
                        $percentage = min(100, ($rt['avg_hours'] / $max_hours) * 100);
                        ?>
                        <div class="progress-fill <?php echo $rt['severity'] == 'critical' ? 'bg-red-500' : ($rt['severity'] == 'high' ? 'bg-orange-500' : ($rt['severity'] == 'medium' ? 'bg-yellow-500' : 'bg-blue-500')); ?>" 
                             style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- أكثر الوحدات تضررًا -->
    <?php if (!empty($top_units_incidents)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-300 mb-4 flex items-center">
            <i class="fas fa-building ml-2"></i>
            أكثر الوحدات تضررًا
        </h3>
        
        <div class="space-y-4">
            <?php 
            $total_unit_incidents = array_sum(array_column($top_units_incidents, 'incident_count'));
            foreach ($top_units_incidents as $unit): 
                $percentage = $total_unit_incidents > 0 ? round(($unit['incident_count'] / $total_unit_incidents) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $unit['name']; ?></span>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-sm font-bold"><?php echo $unit['incident_count']; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                        <?php if ($unit['critical_count'] > 0): ?>
                        <span class="px-2 py-0.5 bg-red-600 rounded-full text-xs">حرج: <?php echo $unit['critical_count']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- أكثر المستخدمين تكليفًا -->
<!-- ============================================= -->
<?php if (!empty($top_assignees)): ?>
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-users ml-2"></i>
        أكثر المستخدمين تكليفًا بالحوادث
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($top_assignees as $assignee): ?>
        <div class="bg-slate-800 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="font-semibold text-white"><?php echo $assignee['full_name']; ?></p>
                <div class="flex items-center space-x-2 space-x-reverse mt-1">
                    <span class="text-xs px-2 py-0.5 bg-red-600 rounded-full">حرج: <?php echo $assignee['critical_count']; ?></span>
                    <span class="text-xs px-2 py-0.5 bg-green-600 rounded-full">تم الحل: <?php echo $assignee['resolved_count']; ?></span>
                </div>
            </div>
            <div class="text-center">
                <span class="text-2xl font-bold text-blue-400"><?php echo $assignee['incident_count']; ?></span>
                <p class="text-xs text-gray-400">حادث</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- جدول جميع الحوادث -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-yellow-300 flex items-center">
            <i class="fas fa-table ml-2"></i>
            جميع الحوادث
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-incidents" placeholder="بحث في الحوادث..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الحالات</option>
                <option value="open">مفتوح</option>
                <option value="in-progress">قيد المعالجة</option>
                <option value="resolved">تم الحل</option>
                <option value="closed">مغلق</option>
            </select>
            <select id="severity-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الشدات</option>
                <option value="critical">حرج</option>
                <option value="high">عالي</option>
                <option value="medium">متوسط</option>
                <option value="low">منخفض</option>
            </select>
        </div>
    </div>

    <?php if (empty($incidents)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد حوادث مسجلة</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="incidents-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوحدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">المسؤول</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ الاكتشاف</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ الحل</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم الحادث</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incidents as $incident): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors incident-row" 
                    data-status="<?php echo $incident['status']; ?>"
                    data-severity="<?php echo $incident['severity']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewIncidentDetails(<?php echo $incident['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($incident['status'] != 'resolved' && $incident['status'] != 'closed'): ?>
                            <button onclick="updateIncidentStatus(<?php echo $incident['id']; ?>)" class="text-green-400 hover:text-green-300" title="تحديث الحالة">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="assignIncident(<?php echo $incident['id']; ?>)" class="text-purple-400 hover:text-purple-300" title="تكليف">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getIncidentStatusBadge($incident['status']); ?></td>
                    <td class="px-6 py-4"><?php echo getIncidentSeverityBadge($incident['severity']); ?></td>
                    <td class="px-6 py-4"><?php echo getIncidentTypeBadge($incident['type']); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $incident['unit_name'] ?? 'غير محدد'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $incident['assigned_to_name'] ?? 'غير معين'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo date('Y-m-d', strtotime($incident['detected_at'])); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $incident['resolved_at'] ? date('Y-m-d', strtotime($incident['resolved_at'])) : '-'; ?></td>
                    <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $incident['description'] ?? '-'; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $incident['name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($incidents); ?> حادث
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full ml-1"></span>
                مفتوح: <?php echo $status_counts['open']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                قيد المعالجة: <?php echo $status_counts['in-progress']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full ml-1"></span>
                تم الحل: <?php echo $status_counts['resolved']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء حادث جديد -->
<!-- ============================================= -->
<div id="create-incident-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateIncidentModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-red-400">
                <i class="fas fa-plus-circle ml-2"></i>
                تسجيل حادث جديد
            </h3>
        </div>

        <form id="create-incident-form" onsubmit="saveNewIncident(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم الحادث</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">النوع</label>
                    <select name="type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                        <option value="breach">اختراق</option>
                        <option value="outage">انقطاع</option>
                        <option value="attack">هجوم</option>
                        <option value="data_loss">فقدان بيانات</option>
                        <option value="compliance">امتثال</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الشدة</label>
                    <select name="severity" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                        <option value="critical">حرج</option>
                        <option value="high">عالي</option>
                        <option value="medium" selected>متوسط</option>
                        <option value="low">منخفض</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الوحدة</label>
                    <select name="unit_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                        <option value="">اختر الوحدة</option>
                        <?php
                        $units = $db->query("SELECT id, name FROM units ORDER BY name")->fetchAll();
                        foreach ($units as $unit):
                        ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المسؤول</label>
                    <select name="assigned_to" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                        <option value="">غير معين</option>
                        <?php
                        $users = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();
                        foreach ($users as $user):
                        ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ الاكتشاف</label>
                    <input type="date" name="detected_at" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">التأثير</label>
                <textarea name="impact" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder">الأنظمة المتأثرة، العملاء المتأثرين، etc..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    تسجيل الحادث
                </button>
                <button type="button" onclick="closeCreateIncidentModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل الحادث -->
<!-- ============================================= -->
<div id="incident-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeIncidentDetailsModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400" id="incident-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل الحادث
            </h3>
        </div>
        <div id="incident-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
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
    const ctx = document.getElementById('incidentsChart')?.getContext('2d');
    if (!ctx) return;
    
    const monthlyData = <?php echo json_encode(array_values($monthly_stats)); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'إجمالي الحوادث',
                    data: monthlyData.map(d => d.total),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                },
                {
                    label: 'حوادث حرجة',
                    data: monthlyData.map(d => d.critical),
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1
                },
                {
                    label: 'تم الحل',
                    data: monthlyData.map(d => d.resolved),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
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

let currentIncidentId = null;

function createNewIncident() {
    document.getElementById('create-incident-modal').classList.remove('hidden');
    document.getElementById('create-incident-modal').classList.add('flex');
}

function closeCreateIncidentModal() {
    document.getElementById('create-incident-modal').classList.add('hidden');
    document.getElementById('create-incident-modal').classList.remove('flex');
}

function saveNewIncident(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeCreateIncidentModal();
        if (typeof showNotification === 'function') {
            showNotification('تم تسجيل الحادث الجديد بنجاح', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshIncidents() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('تم تحديث بيانات الحوادث', 'success');
        }
        location.reload();
    }, 1500);
}

function viewIncidentDetails(incidentId) {
    currentIncidentId = incidentId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل الحادث
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل الحادث #${incidentId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="updateIncidentStatus(${incidentId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تحديث الحالة</button>
                    <button onclick="closeIncidentDetailsModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('incident-details-content').innerHTML = details;
        document.getElementById('incident-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل الحادث #${incidentId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('incident-details-modal').classList.remove('hidden');
        document.getElementById('incident-details-modal').classList.add('flex');
    }, 1000);
}

function closeIncidentDetailsModal() {
    document.getElementById('incident-details-modal').classList.add('hidden');
    document.getElementById('incident-details-modal').classList.remove('flex');
}

function updateIncidentStatus(incidentId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح نافذة تحديث حالة الحادث #${incidentId}`, 'info');
    }
    closeIncidentDetailsModal();
}

function assignIncident(incidentId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح نافذة تكليف الحادث #${incidentId}`, 'info');
    }
}

// البحث والتصفية
document.getElementById('search-incidents')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.incident-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.getElementById('status-filter')?.addEventListener('change', filterIncidents);
document.getElementById('severity-filter')?.addEventListener('change', filterIncidents);

function filterIncidents() {
    const statusFilter = document.getElementById('status-filter').value;
    const severityFilter = document.getElementById('severity-filter').value;
    const rows = document.querySelectorAll('.incident-row');
    
    rows.forEach(row => {
        const statusMatch = statusFilter === 'all' || row.dataset.status === statusFilter;
        const severityMatch = severityFilter === 'all' || row.dataset.severity === severityFilter;
        
        if (statusMatch && severityMatch) {
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
    background: #ef4444;
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
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>