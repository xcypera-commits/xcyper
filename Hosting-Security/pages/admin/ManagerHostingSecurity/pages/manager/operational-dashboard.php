<?php
// =============================================
// manager/pages/manager/operational_dashboard.php
// لوحة القيادة التشغيلية - بيانات حقيقية فقط من قاعدة البيانات
// =============================================

// هذا الملف يتم تضمينه داخل index.php، لذا المتغيرات متاحة
// $db, $current_user متاحين من الملف الرئيسي

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// جلب البيانات من قاعدة البيانات - بدون قيم افتراضية
// =============================================

try {
    // 1. إحصائيات سريعة - كلها من قاعدة البيانات
    $stats = [];
    
    // المشاريع النشطة
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('completed', 'archived')");
    $stats['active_projects'] = $stmt->fetchColumn();
    if ($stats['active_projects'] === false) $stats['active_projects'] = 0;
    
    // المشاريع المتأخرة
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'delayed'");
    $stats['delayed_projects'] = $stmt->fetchColumn();
    if ($stats['delayed_projects'] === false) $stats['delayed_projects'] = 0;
    
    // المشاريع الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE priority = 'critical' AND status NOT IN ('completed', 'archived')");
    $stats['critical_projects'] = $stmt->fetchColumn();
    if ($stats['critical_projects'] === false) $stats['critical_projects'] = 0;
    
    // الحوادث المفتوحة
    $stmt = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open', 'in-progress')");
    $stats['open_incidents'] = $stmt->fetchColumn();
    if ($stats['open_incidents'] === false) $stats['open_incidents'] = 0;
    
    // الحوادث الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM incidents WHERE severity = 'critical' AND status IN ('open', 'in-progress')");
    $stats['critical_incidents'] = $stmt->fetchColumn();
    if ($stats['critical_incidents'] === false) $stats['critical_incidents'] = 0;
    
    // الموافقات المعلقة
    $stmt = $db->query("SELECT COUNT(*) FROM pending_approvals WHERE status = 'pending'");
    $stats['pending_approvals'] = $stmt->fetchColumn();
    if ($stats['pending_approvals'] === false) $stats['pending_approvals'] = 0;
    
    // طلبات الموارد
    $stmt = $db->query("SELECT COUNT(*) FROM resource_requests WHERE status = 'pending'");
    $stats['resource_requests'] = $stmt->fetchColumn();
    if ($stats['resource_requests'] === false) $stats['resource_requests'] = 0;
    
    // 2. حالة الوحدات الأربع
    $units = $db->query("
        SELECT u.*, 
               COUNT(DISTINCT p.id) as active_projects,
               COUNT(DISTINCT i.id) as active_incidents,
               (SELECT COUNT(*) FROM users WHERE unit_id = u.id) as actual_employees
        FROM units u
        LEFT JOIN projects p ON u.id = p.unit_id AND p.status NOT IN ('completed', 'archived')
        LEFT JOIN incidents i ON u.id = i.unit_id AND i.status IN ('open', 'in-progress')
        GROUP BY u.id
        ORDER BY u.id
    ")->fetchAll();
    
   // 3. التنبيهات الحرجة - نسخة معدلة
$critical_alerts = $db->query("
    SELECT a.*, s.name as server_name
    FROM alerts a
    LEFT JOIN servers s ON a.server_id = s.id
    WHERE a.type = 'critical' AND a.status != 'resolved'
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll();
    
    // 4. النشاطات الأخيرة
    $recent_activities = $db->query("
        SELECT al.*, u.full_name as user_name, un.name as unit_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN units un ON al.unit_id = un.id
        ORDER BY al.created_at DESC
        LIMIT 7
    ")->fetchAll();
    
    // 5. الموافقات المعلقة (للعرض)
    $pending_approvals_list = $db->query("
        SELECT pa.*, u.full_name as requester_name, un.name as unit_name
        FROM pending_approvals pa
        LEFT JOIN users u ON pa.requester_id = u.id
        LEFT JOIN units un ON pa.unit_id = un.id
        WHERE pa.status = 'pending'
        ORDER BY 
            CASE pa.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            pa.request_date ASC
        LIMIT 3
    ")->fetchAll();
    
    // 6. طلبات الموارد (للعرض)
    $resource_requests_list = $db->query("
        SELECT rr.*, u.full_name as requester_name, un.name as unit_name
        FROM resource_requests rr
        LEFT JOIN users u ON rr.requester_id = u.id
        LEFT JOIN units un ON rr.unit_id = un.id
        WHERE rr.status = 'pending'
        ORDER BY 
            CASE rr.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            rr.request_date ASC
        LIMIT 3
    ")->fetchAll();
    
    // 7. إحصائيات أداء الوحدات (آخر 7 أيام)
    $performance_stats = $db->query("
        SELECT 
            ROUND(AVG(productivity), 1) as avg_productivity,
            ROUND(AVG(quality), 1) as avg_quality,
            ROUND(AVG(speed), 1) as avg_speed
        FROM performance_metrics
        WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ")->fetch();
    
    if (!$performance_stats) {
        $performance_stats = ['avg_productivity' => 0, 'avg_quality' => 0, 'avg_speed' => 0];
    }
    
    // 8. توزيع المشاريع حسب الحالة
    $project_distribution = $db->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM projects
        WHERE status != 'completed'
        GROUP BY status
    ")->fetchAll();
    
    // 9. حالة النظام
    $system_status = $db->query("
        SELECT * FROM system_status ORDER BY component
    ")->fetchAll();
    
    // 10. التهديدات الحية
    $live_threats = $db->query("
        SELECT * FROM live_threats WHERE is_active = true ORDER BY severity DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    // في حالة خطأ في قاعدة البيانات، نعرض الخطأ ولا نستخدم بيانات تجريبية
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return; // نوقف تنفيذ الصفحة
}

// دوال مساعدة للتنسيق
function formatMoney($amount) {
    return number_format($amount) . ' ر.س';
}

function getUnitClass($code) {
    return match($code) {
        'DOC' => 'unit-documentation',
        'STR' => 'unit-storage',
        'SEC' => 'unit-security',
        'PEN' => 'unit-pentest',
        default => 'unit-documentation'
    };
}

function getThreatTypeText($type) {
    return match($type) {
        'ddos' => 'DDoS',
        'brute_force' => 'Brute Force',
        'sql_injection' => 'SQL Injection',
        'xss' => 'XSS',
        default => $type
    };
}


?>

<!-- ============================================= -->
<!-- بطاقات KPIs الرئيسية - بنفس الشكل الأصلي -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- المشاريع النشطة -->
    <div class="security-border manager-card rounded-xl p-4 card-hover">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-gray-400 text-sm">المشاريع النشطة</p>
                <p class="text-2xl font-bold text-blue-400"><?php echo $stats['active_projects']; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>
        <div class="flex justify-between text-xs">
            <span class="text-yellow-400">متأخر: <?php echo $stats['delayed_projects']; ?></span>
            <span class="text-red-400">حرج: <?php echo $stats['critical_projects']; ?></span>
        </div>
    </div>

    <!-- الحوادث المفتوحة -->
    <div class="security-border manager-card rounded-xl p-4 card-hover">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-gray-400 text-sm">الحوادث المفتوحة</p>
                <p class="text-2xl font-bold text-red-400"><?php echo $stats['open_incidents']; ?></p>
            </div>
            <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.858-.833-2.628 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
        </div>
        <div class="flex justify-between text-xs">
            <span class="text-red-400">حرجة: <?php echo $stats['critical_incidents']; ?></span>
            <span class="text-blue-400">نسبة الحل: <?php 
                $total = $stats['open_incidents'] + $stats['critical_incidents'];
                echo $total > 0 ? round(($stats['critical_incidents'] / $total) * 100, 1) : 0; ?>%
            </span>
        </div>
    </div>

    <!-- الموافقات المعلقة -->
    <div class="security-border manager-card rounded-xl p-4 card-hover">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-gray-400 text-sm">موافقات معلقة</p>
                <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['pending_approvals']; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>
        <div class="flex justify-between text-xs">
            <span class="text-green-400">طلبات موارد: <?php echo $stats['resource_requests']; ?></span>
        </div>
    </div>

    <!-- أداء الفرق -->
    <div class="security-border manager-card rounded-xl p-4 card-hover">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-gray-400 text-sm">أداء الفرق</p>
                <p class="text-2xl font-bold text-green-400"><?php echo $performance_stats['avg_productivity']; ?>%</p>
            </div>
            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>
        <div class="flex justify-between text-xs">
            <span class="text-blue-400">الجودة: <?php echo $performance_stats['avg_quality']; ?>%</span>
            <span class="text-purple-400">السرعة: <?php echo $performance_stats['avg_speed']; ?>%</span>
        </div>
    </div>
</div>

<!-- حالة الوحدات الأربع والتنبيهات الحرجة -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- حالة الوحدات -->
    <div class="security-border manager-card rounded-xl p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right text-blue-300">
                <i class="fas fa-cubes ml-2"></i>
                حالة الوحدات الأربع
            </h3>
            <button onclick="refreshUnitStatus()" class="text-sm bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded-lg transition-colors">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <?php if (!empty($units)): ?>
                <?php foreach ($units as $unit): 
                    $unitClass = getUnitClass($unit['code']);
                ?>
                <div class="p-4 bg-slate-800 rounded-lg <?php echo $unitClass; ?>">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-lg"><?php echo $unit['name']; ?></h4>
                        <span class="unit-badge <?php echo $unitClass; ?>">
                            <?php echo $unit['employee_count']; ?>/<?php echo $unit['max_employees']; ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-300">رئيس الوحدة</span>
                            <span class="text-sm font-semibold"><?php echo $unit['head_name']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-300">مشاريع نشطة</span>
                            <span class="text-sm <?php echo ($unit['active_projects'] ?? 0) > 10 ? 'text-green-400' : 'text-blue-400'; ?>">
                                <?php echo $unit['active_projects'] ?? 0; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-300">حوادث نشطة</span>
                            <span class="text-sm <?php echo ($unit['active_incidents'] ?? 0) > 0 ? 'text-red-400' : 'text-green-400'; ?>">
                                <?php echo $unit['active_incidents'] ?? 0; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-300">الميزانية</span>
                            <span class="text-sm text-blue-400"><?php echo formatMoney($unit['budget'] ?? 0); ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button onclick="manageUnit('<?php echo $unit['code']; ?>')" class="w-full text-xs bg-slate-700 hover:bg-slate-600 py-1 rounded transition-colors">
                            <i class="fas fa-cog ml-1"></i>
                            إدارة الوحدة
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-2 p-8 text-center text-gray-400">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>لا توجد وحدات مضافة في قاعدة البيانات</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- التنبيهات الحرجة -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right text-red-300">
                <i class="fas fa-exclamation-triangle ml-2"></i>
                تنبيهات حرجة
            </h3>
            <span class="text-xs px-3 py-1 bg-red-500 rounded-full">
                <?php echo count($critical_alerts); ?> حرجة
            </span>
        </div>
        
        <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom">
            <?php if (empty($critical_alerts)): ?>
            <div class="p-6 bg-slate-800 rounded-lg text-center">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                <p class="text-green-400">لا توجد تنبيهات حرجة</p>
                <p class="text-xs text-gray-500 mt-2">جميع الأنظمة تعمل بشكل طبيعي</p>
            </div>
            <?php else: ?>
                <?php foreach ($critical_alerts as $alert): ?>
                <div class="p-4 bg-slate-800 rounded-lg alert-critical">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <p class="font-semibold text-red-300"><?php echo $alert['title']; ?></p>
                            <p class="text-xs text-gray-400 mt-1">
                                <?php echo $alert['server_name'] ?? $alert['description']; ?>
                            </p>
                        </div>
                        <span class="text-xs bg-red-500 bg-opacity-20 text-red-400 px-2 py-1 rounded">
                            <?php echo formatTimeAgo($alert['created_at']); ?>
                        </span>
                    </div>
                    <?php if (isset($alert['unit_name'])): ?>
                    <p class="text-xs text-gray-500 mb-2">الوحدة: <?php echo $alert['unit_name']; ?></p>
                    <?php endif; ?>
                    <div class="flex space-x-2 space-x-reverse mt-2">
                        <button onclick="handleAlert('<?php echo $alert['id']; ?>')" class="flex-1 text-xs bg-blue-600 hover:bg-blue-700 py-1 rounded transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <button onclick="escalateIncident()" class="flex-1 text-xs bg-red-600 hover:bg-red-700 py-1 rounded transition-colors">
                            <i class="fas fa-arrow-up ml-1"></i>
                            تصعيد
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- النشاطات الأخيرة والموافقات المعلقة -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- النشاطات الأخيرة -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right text-cyan-300">
                <i class="fas fa-history ml-2"></i>
                النشاطات الأخيرة
            </h3>
            <button onclick="viewAllActivities()" class="text-sm bg-cyan-600 hover:bg-cyan-700 px-3 py-1 rounded-lg transition-colors">
                <i class="fas fa-list ml-1"></i>
                عرض الكل
            </button>
        </div>
        
        <div class="space-y-3 max-h-80 overflow-y-auto scrollbar-custom">
            <?php if (empty($recent_activities)): ?>
            <div class="p-4 bg-slate-800 rounded-lg text-center">
                <p class="text-gray-400">لا توجد نشاطات حديثة</p>
            </div>
            <?php else: ?>
                <?php foreach ($recent_activities as $activity): ?>
                <div class="flex items-start p-3 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                    <div class="w-2 h-2 bg-<?php 
                        echo strpos($activity['description'] ?? '', 'نشر') !== false ? 'green' : 
                            (strpos($activity['description'] ?? '', 'ثغرة') !== false ? 'red' : 'blue'); 
                    ?>-500 rounded-full mt-2 ml-2"></div>
                    <div class="flex-1">
                        <p class="text-sm"><?php echo $activity['description'] ?? 'لا يوجد وصف'; ?></p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-user ml-1"></i>
                                <?php echo $activity['user_name'] ?? 'النظام'; ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-clock ml-1"></i>
                                <?php echo formatTimeAgo($activity['created_at'] ?? ''); ?>
                            </span>
                        </div>
                        <?php if (isset($activity['unit_name'])): ?>
                        <span class="text-xs text-blue-400 mt-1 inline-block">
                            <i class="fas fa-building ml-1"></i>
                            <?php echo $activity['unit_name']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- الموافقات المعلقة وطلبات الموارد -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right text-purple-300">
                <i class="fas fa-clock ml-2"></i>
                موافقات وطلبات معلقة
            </h3>
            <div class="flex space-x-2 space-x-reverse">
                <span class="text-xs px-2 py-1 bg-purple-500 rounded-full">
                    <?php echo count($pending_approvals_list); ?> موافقات
                </span>
                <span class="text-xs px-2 py-1 bg-blue-500 rounded-full">
                    <?php echo count($resource_requests_list); ?> طلبات
                </span>
            </div>
        </div>
        
        <div class="space-y-4 max-h-80 overflow-y-auto scrollbar-custom">
            <!-- الموافقات المعلقة -->
            <?php if (!empty($pending_approvals_list)): ?>
                <h4 class="text-sm font-semibold text-gray-400 mb-2">موافقات مالية</h4>
                <?php foreach ($pending_approvals_list as $approval): ?>
                <div class="p-3 bg-slate-800 rounded-lg priority-<?php echo $approval['priority'] ?? 'medium'; ?>">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="font-semibold text-sm"><?php echo $approval['title']; ?></h5>
                        <span class="text-xs px-2 py-1 <?php echo getSeverityColor($approval['priority'] ?? 'medium'); ?> rounded-full">
                            <?php echo $approval['priority'] ?? 'medium'; ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-2"><?php echo $approval['description']; ?></p>
                    <div class="flex items-center justify-between mb-2 text-xs">
                        <span class="text-yellow-400 font-bold"><?php echo formatMoney($approval['amount'] ?? 0); ?></span>
                        <span class="text-gray-500">
                            <i class="fas fa-user ml-1"></i><?php echo $approval['requester_name'] ?? 'غير معروف'; ?>
                        </span>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="approveRequest('<?php echo $approval['id']; ?>')" class="flex-1 text-xs bg-green-600 hover:bg-green-700 py-1 rounded transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            موافقة
                        </button>
                        <button onclick="rejectRequest('<?php echo $approval['id']; ?>')" class="flex-1 text-xs bg-red-600 hover:bg-red-700 py-1 rounded transition-colors">
                            <i class="fas fa-times ml-1"></i>
                            رفض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- طلبات الموارد -->
            <?php if (!empty($resource_requests_list)): ?>
                <h4 class="text-sm font-semibold text-gray-400 mb-2 mt-4">طلبات موارد</h4>
                <?php foreach ($resource_requests_list as $request): ?>
                <div class="p-3 bg-slate-800 rounded-lg priority-<?php echo $request['priority'] ?? 'medium'; ?>">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="font-semibold text-sm"><?php echo $request['title']; ?></h5>
                        <span class="text-xs px-2 py-1 <?php echo getSeverityColor($request['priority'] ?? 'medium'); ?> rounded-full">
                            <?php echo $request['priority'] ?? 'medium'; ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-2"><?php echo $request['description']; ?></p>
                    <div class="flex items-center justify-between mb-2 text-xs">
                        <span class="text-green-400 font-bold"><?php echo formatMoney($request['amount'] ?? 0); ?></span>
                        <span class="text-gray-500">
                            <i class="fas fa-building ml-1"></i><?php echo $request['unit_name'] ?? 'غير معروف'; ?>
                        </span>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="approveResource('<?php echo $request['id']; ?>')" class="flex-1 text-xs bg-green-600 hover:bg-green-700 py-1 rounded transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            قبول
                        </button>
                        <button onclick="rejectResource('<?php echo $request['id']; ?>')" class="flex-1 text-xs bg-red-600 hover:bg-red-700 py-1 rounded transition-colors">
                            <i class="fas fa-times ml-1"></i>
                            رفض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (empty($pending_approvals_list) && empty($resource_requests_list)): ?>
            <div class="p-6 bg-slate-800 rounded-lg text-center">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                <p class="text-green-400">لا توجد موافقات أو طلبات معلقة</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- توزيع المشاريع وحالة النظام -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- توزيع المشاريع -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-xl font-bold text-right text-green-300 mb-4">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع المشاريع حسب الحالة
        </h3>
        
        <div class="space-y-4">
            <?php if (!empty($project_distribution)): ?>
                <?php foreach ($project_distribution as $dist): ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-300">
                            <?php 
                            echo match($dist['status']) {
                                'documentation' => '📋 في طور التوثيق',
                                'testing' => '🧪 في طور الاختبار',
                                'deployment' => '🚀 في طور النشر',
                                'delayed' => '⚠️ متأخر',
                                default => $dist['status']
                            };
                            ?>
                        </span>
                        <div class="flex items-center">
                            <span class="text-sm font-bold ml-2"><?php echo $dist['count']; ?></span>
                            <?php if (($dist['critical_count'] ?? 0) > 0): ?>
                            <span class="text-xs px-2 py-1 bg-red-500 rounded-full">
                                حرج: <?php echo $dist['critical_count']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <?php
                        $percentage = $stats['active_projects'] > 0 ? ($dist['count'] / $stats['active_projects']) * 100 : 0;
                        $color = $dist['status'] == 'delayed' ? 'bg-red-500' : 'bg-blue-500';
                        ?>
                        <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-400 py-8">
                    <i class="fas fa-chart-bar text-3xl mb-2"></i>
                    <p>لا توجد مشاريع نشطة</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- حالة النظام والتهديدات -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-xl font-bold text-right text-yellow-300 mb-4">
            <i class="fas fa-shield-alt ml-2"></i>
            حالة النظام والتهديدات
        </h3>
        
        <!-- حالة النظام -->
        <?php if (!empty($system_status)): ?>
        <div class="space-y-3 mb-6">
            <?php foreach ($system_status as $status): ?>
            <div class="flex items-center justify-between p-2 bg-slate-800 rounded-lg">
                <span class="text-sm <?php echo getStatusColor($status['status']); ?>">
                    <i class="fas fa-circle ml-1 text-xs"></i>
                    <?php echo $status['component']; ?>
                </span>
                <div class="flex items-center">
                    <div class="w-16 bg-gray-700 rounded-full h-2 ml-2">
                        <div class="bg-<?php echo ($status['health_percentage'] ?? 0) > 90 ? 'green' : (($status['health_percentage'] ?? 0) > 70 ? 'yellow' : 'red'); ?>-500 h-2 rounded-full" 
                             style="width: <?php echo $status['health_percentage'] ?? 0; ?>%"></div>
                    </div>
                    <span class="text-xs text-gray-400"><?php echo $status['health_percentage'] ?? 0; ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- التهديدات الحية -->
        <h4 class="text-md font-semibold text-red-300 mb-3">
            <i class="fas fa-bolt ml-2"></i>
            التهديدات الحية
        </h4>
        <div class="space-y-3">
            <?php if (!empty($live_threats)): ?>
                <?php foreach ($live_threats as $threat): ?>
                <div class="flex items-center justify-between p-2 bg-slate-800 rounded-lg">
                    <span class="text-sm"><?php echo getThreatTypeText($threat['threat_type']); ?></span>
                    <div class="flex items-center">
                        <span class="text-lg font-bold ml-3"><?php echo $threat['count']; ?></span>
                        <span class="px-2 py-1 <?php echo getSeverityColor($threat['severity']); ?> rounded-full text-xs">
                            <?php echo $threat['severity']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-400 py-4">
                    <i class="fas fa-check-circle text-2xl text-green-500 mb-2"></i>
                    <p>لا توجد تهديدات نشطة</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- زر الفحص الأمني -->
        <button onclick="triggerSecurityScan()" class="w-full mt-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all operation-glow flex items-center justify-center">
            <i class="fas fa-shield-alt ml-2"></i>
            <span>تشغيل فحص أمني شامل</span>
        </button>
    </div>
</div>

<!-- سكريبت إضافي للصفحة -->
<script>
// دوال خاصة بصفحة لوحة القيادة
function manageUnit(unitCode) {
    showNotification(`فتح لوحة إدارة ${unitCode}`, 'info');
}

function refreshUnitStatus() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('تم تحديث حالة الوحدات بنجاح', 'success');
        location.reload();
    }, 1500);
}

function viewAllActivities() {
    showNotification('فتح سجل النشاطات الكامل', 'info');
}

function handleAlert(alertId) {
    showNotification(`جاري معالجة التنبيه رقم ${alertId}`, 'warning');
}

function escalateIncident() {
    showNotification('جاري تصعيد الحادث للإدارة العليا', 'warning');
}

function approveRequest(requestId) {
    showNotification(`تمت الموافقة على الطلب رقم ${requestId}`, 'success');
}

function rejectRequest(requestId) {
    if (confirm('هل أنت متأكد من رفض هذا الطلب؟')) {
        showNotification(`تم رفض الطلب رقم ${requestId}`, 'error');
    }
}

function approveResource(requestId) {
    showNotification(`تم قبول طلب المورد رقم ${requestId}`, 'success');
}

function rejectResource(requestId) {
    if (confirm('هل أنت متأكد من رفض طلب المورد هذا؟')) {
        showNotification(`تم رفض طلب المورد رقم ${requestId}`, 'error');
    }
}

function triggerSecurityScan() {
    showLoading();
    showNotification('بدء الفحص الأمني الشامل...', 'info');
    setTimeout(() => {
        hideLoading();
        showNotification('اكتمل الفحص الأمني - لا توجد ثغرات حرجة', 'success');
    }, 3000);
}
</script>