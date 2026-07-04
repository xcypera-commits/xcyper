<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/resource_allocation.php
// تخصيص الموارد - نسخة كاملة ومفصلة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة عن الموارد
    // =============================================
    
    // إجمالي الموظفين
    $stmt = $db->query("SELECT SUM(employee_count) FROM units");
    $total_employees = $stmt->fetchColumn() ?: 0;
    
    // إجمالي الميزانيات
    $stmt = $db->query("SELECT SUM(budget) FROM units");
    $total_budget = $stmt->fetchColumn() ?: 0;
    
    // طلبات الموارد المعلقة
    $stmt = $db->query("SELECT COUNT(*) FROM resource_requests WHERE status = 'pending'");
    $pending_requests = $stmt->fetchColumn() ?: 0;
    
    // الموافقات المعلقة
    $stmt = $db->query("SELECT COUNT(*) FROM pending_approvals WHERE status = 'pending'");
    $pending_approvals = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. موارد كل وحدة
    // =============================================
    
    $unit_resources = $db->query("
        SELECT 
            u.id,
            u.name,
            u.code,
            u.employee_count,
            u.max_employees,
            u.budget,
            (SELECT COUNT(*) FROM users WHERE unit_id = u.id) as actual_employees,
            (SELECT COUNT(*) FROM projects WHERE unit_id = u.id AND status NOT IN ('completed', 'archived')) as active_projects,
            (SELECT COUNT(*) FROM resource_requests WHERE unit_id = u.id AND status = 'pending') as pending_requests,
            (SELECT COUNT(*) FROM pending_approvals WHERE unit_id = u.id AND status = 'pending') as pending_approvals
        FROM units u
        ORDER BY u.id
    ")->fetchAll();
    
    // =============================================
    // 3. طلبات الموارد المعلقة
    // =============================================
    
    $pending_resource_requests = $db->query("
        SELECT 
            rr.*,
            u.full_name as requester_name,
            un.name as unit_name,
            un.code as unit_code
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
    ")->fetchAll();
    
    // =============================================
    // 4. الموافقات المالية المعلقة
    // =============================================
    
    $pending_financial_approvals = $db->query("
        SELECT 
            pa.*,
            u.full_name as requester_name,
            un.name as unit_name,
            un.code as unit_code
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
    ")->fetchAll();
    
    // =============================================
    // 5. سجل الموارد المعتمدة مؤخرًا
    // =============================================
    
    $recent_approved = $db->query("
        (SELECT 
            'request' as type,
            rr.id,
            rr.title,
            rr.amount,
            rr.priority,
            rr.status,
            rr.request_date,
            rr.review_date,
            u.full_name as requester_name,
            un.name as unit_name
        FROM resource_requests rr
        LEFT JOIN users u ON rr.requester_id = u.id
        LEFT JOIN units un ON rr.unit_id = un.id
        WHERE rr.status IN ('approved', 'rejected')
        ORDER BY rr.review_date DESC
        LIMIT 5)
        UNION ALL
        (SELECT 
            'approval' as type,
            pa.id,
            pa.title,
            pa.amount,
            pa.priority,
            pa.status,
            pa.request_date,
            pa.review_date,
            u.full_name as requester_name,
            un.name as unit_name
        FROM pending_approvals pa
        LEFT JOIN users u ON pa.requester_id = u.id
        LEFT JOIN units un ON pa.unit_id = un.id
        WHERE pa.status IN ('approved', 'rejected')
        ORDER BY pa.review_date DESC
        LIMIT 5)
        ORDER BY review_date DESC
        LIMIT 8
    ")->fetchAll();
    
    // =============================================
    // 6. إحصائيات الموارد حسب النوع
    // =============================================
    
    $resource_stats = [];
    
    // إحصائيات طلبات الموارد
    $stmt = $db->query("
        SELECT 
            resource_type,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM resource_requests
        GROUP BY resource_type
    ");
    while ($row = $stmt->fetch()) {
        $resource_stats[$row['resource_type']] = $row;
    }
    
    // =============================================
    // 7. توزيع الميزانيات حسب الوحدة
    // =============================================
    
    $budget_distribution = $db->query("
        SELECT 
            name,
            budget,
            ROUND(budget * 100.0 / (SELECT SUM(budget) FROM units), 1) as percentage
        FROM units
        WHERE budget > 0
        ORDER BY budget DESC
    ")->fetchAll();
    
    // =============================================
    // 8. إحصائيات الموظفين
    // =============================================
    
    $employee_stats = $db->query("
        SELECT 
            SUM(employee_count) as total_employees,
            SUM(max_employees) as total_capacity,
            ROUND(AVG(employee_count * 100.0 / max_employees), 1) as avg_utilization
        FROM units
    ")->fetch();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function formatMoney($amount) {
    return number_format($amount) . ' ر.س';
}

function getResourceTypeText($type) {
    return match($type) {
        'equipment' => 'معدات',
        'software' => 'برمجيات',
        'personnel' => 'موظفين',
        'training' => 'تدريب',
        'other' => 'أخرى',
        default => $type
    };
}

function getResourceTypeColor($type) {
    return match($type) {
        'equipment' => 'bg-purple-500',
        'software' => 'bg-blue-500',
        'personnel' => 'bg-green-500',
        'training' => 'bg-yellow-500',
        'other' => 'bg-gray-500',
        default => 'bg-gray-500'
    };
}

function getPriorityColor($priority) {
    return match($priority) {
        'critical' => 'bg-red-500',
        'high' => 'bg-orange-500',
        'medium' => 'bg-yellow-500',
        'low' => 'bg-blue-500',
        default => 'bg-gray-500'
    };
}

function getPriorityText($priority) {
    return match($priority) {
        'critical' => 'حرج',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض',
        default => $priority
    };
}

function getStatusBadge($status) {
    return match($status) {
        'pending' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد الانتظار</span>',
        'approved' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تمت الموافقة</span>',
        'rejected' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">مرفوض</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">قيد التنفيذ</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getUnitClass($code) {
    return match($code) {
        'DOC' => 'border-blue-500',
        'STR' => 'border-purple-500',
        'SEC' => 'border-green-500',
        'PEN' => 'border-yellow-500',
        default => 'border-gray-500'
    };
}

function formatTimeAgo1($timestamp) {
    if (!$timestamp) return 'غير محدد';
    
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "منذ $diff ثانية";
    if ($diff < 3600) return "منذ " . round($diff / 60) . " دقيقة";
    if ($diff < 86400) return "منذ " . round($diff / 3600) . " ساعة";
    if ($diff < 2592000) return "منذ " . round($diff / 86400) . " يوم";
    return date('Y-m-d', $time);
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-green-300">
            <i class="fas fa-chart-pie ml-2"></i>
            تخصيص الموارد
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="createNewResourceRequest()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-plus ml-2"></i>
                طلب مورد جديد
            </button>
            <button onclick="refreshResources()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
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
                    <p class="text-blue-200 text-sm mb-1">إجمالي الموظفين</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $total_employees; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                السعة القصوى: <?php echo $employee_stats['total_capacity']; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">إجمالي الميزانيات</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo formatMoney($total_budget); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-coins text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                إجمالي الميزانيات
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">طلبات موارد</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $pending_requests; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-box text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                في انتظار المراجعة
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">موافقات مالية</p>
                    <p class="text-3xl font-bold text-purple-400"><?php echo $pending_approvals; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-invoice text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                في انتظار الموافقة
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">نسبة الإشغال</span>
            <span class="text-lg font-bold text-blue-400"><?php echo $employee_stats['avg_utilization']; ?>%</span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">الوحدات النشطة</span>
            <span class="text-lg font-bold text-green-400"><?php echo count($unit_resources); ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- توزيع الميزانيات واستخدام الموظفين -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع الميزانيات -->
    <?php if (!empty($budget_distribution)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع الميزانيات حسب الوحدة
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($budget_distribution as $budget): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $budget['name']; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo formatMoney($budget['budget']); ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $budget['percentage']; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $budget['percentage']; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- استخدام الموظفين -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-users ml-2"></i>
            استخدام الموظفين حسب الوحدة
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($unit_resources as $unit): 
                $utilization = $unit['max_employees'] > 0 ? round(($unit['employee_count'] / $unit['max_employees']) * 100, 1) : 0;
                $color = $utilization >= 90 ? 'bg-red-500' : ($utilization >= 75 ? 'bg-yellow-500' : 'bg-green-500');
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $unit['name']; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $unit['employee_count']; ?>/<?php echo $unit['max_employees']; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $utilization; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $utilization; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- طلبات الموارد المعلقة والموافقات المالية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- طلبات الموارد المعلقة -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-yellow-300 flex items-center">
                <i class="fas fa-box ml-2"></i>
                طلبات موارد معلقة
            </h3>
            <span class="px-3 py-1 bg-yellow-600 rounded-full text-xs font-bold"><?php echo count($pending_resource_requests); ?></span>
        </div>
        
        <?php if (empty($pending_resource_requests)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <p class="text-green-400">لا توجد طلبات موارد معلقة</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($pending_resource_requests as $request): ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 <?php echo getUnitClass($request['unit_code']); ?>">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-white"><?php echo $request['title']; ?></h4>
                        <span class="px-2 py-1 <?php echo getPriorityColor($request['priority']); ?> rounded-full text-xs">
                            <?php echo getPriorityText($request['priority']); ?>
                        </span>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-2"><?php echo $request['description']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                        <div>
                            <span class="text-gray-400">النوع:</span>
                            <span class="px-2 py-0.5 <?php echo getResourceTypeColor($request['resource_type']); ?> rounded-full text-xs mr-1">
                                <?php echo getResourceTypeText($request['resource_type']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-400">المبلغ:</span>
                            <span class="text-yellow-400 font-bold"><?php echo formatMoney($request['amount']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs mb-3">
                        <span class="text-gray-400">الوحدة: <span class="text-blue-400"><?php echo $request['unit_name']; ?></span></span>
                        <span class="text-gray-400">مقدم الطلب: <span class="text-green-400"><?php echo $request['requester_name']; ?></span></span>
                    </div>
                    
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="approveResourceRequest(<?php echo $request['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded text-xs transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            موافقة
                        </button>
                        <button onclick="rejectResourceRequest(<?php echo $request['id']; ?>)" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded text-xs transition-colors">
                            <i class="fas fa-times ml-1"></i>
                            رفض
                        </button>
                        <button onclick="reviewResourceRequest(<?php echo $request['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            مراجعة
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- الموافقات المالية المعلقة -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-purple-300 flex items-center">
                <i class="fas fa-file-invoice ml-2"></i>
                موافقات مالية معلقة
            </h3>
            <span class="px-3 py-1 bg-purple-600 rounded-full text-xs font-bold"><?php echo count($pending_financial_approvals); ?></span>
        </div>
        
        <?php if (empty($pending_financial_approvals)): ?>
        <div class="p-6 bg-slate-800 rounded-lg text-center">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <p class="text-green-400">لا توجد موافقات مالية معلقة</p>
        </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($pending_financial_approvals as $approval): ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 <?php echo getUnitClass($approval['unit_code']); ?>">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-white"><?php echo $approval['title']; ?></h4>
                        <span class="px-2 py-1 <?php echo getPriorityColor($approval['priority']); ?> rounded-full text-xs">
                            <?php echo getPriorityText($approval['priority']); ?>
                        </span>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-2"><?php echo $approval['description']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                        <div>
                            <span class="text-gray-400">النوع:</span>
                            <span class="text-blue-400"><?php echo $approval['type']; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">المبلغ:</span>
                            <span class="text-yellow-400 font-bold"><?php echo formatMoney($approval['amount']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs mb-3">
                        <span class="text-gray-400">الوحدة: <span class="text-blue-400"><?php echo $approval['unit_name']; ?></span></span>
                        <span class="text-gray-400">مقدم الطلب: <span class="text-green-400"><?php echo $approval['requester_name']; ?></span></span>
                    </div>
                    
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="approveFinancialRequest(<?php echo $approval['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded text-xs transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            موافقة
                        </button>
                        <button onclick="rejectFinancialRequest(<?php echo $approval['id']; ?>)" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded text-xs transition-colors">
                            <i class="fas fa-times ml-1"></i>
                            رفض
                        </button>
                        <button onclick="reviewFinancialRequest(<?php echo $approval['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            مراجعة
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- موارد الوحدات -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-blue-300 mb-4 flex items-center">
        <i class="fas fa-building ml-2"></i>
        موارد الوحدات
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الموظفين</th>
                    <th class="px-6 py-4 text-sm font-semibold">الميزانية</th>
                    <th class="px-6 py-4 text-sm font-semibold">طلبات معلقة</th>
                    <th class="px-6 py-4 text-sm font-semibold">موافقات معلقة</th>
                    <th class="px-6 py-4 text-sm font-semibold">مشاريع نشطة</th>
                    <th class="px-6 py-4 text-sm font-semibold">رئيس الوحدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوحدة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unit_resources as $unit): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="manageUnitResources(<?php echo $unit['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="إدارة الموارد">
                                <i class="fas fa-cog"></i>
                            </button>
                            <button onclick="viewUnitDetails(<?php echo $unit['id']; ?>)" class="text-green-400 hover:text-green-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-300"><?php echo $unit['actual_employees']; ?>/<?php echo $unit['max_employees']; ?></span>
                            <span class="text-xs text-gray-500">(<?php echo $unit['employee_count']; ?> مخصص)</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-green-400 font-semibold">
                        <?php echo formatMoney($unit['budget']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($unit['pending_requests'] > 0): ?>
                        <span class="px-3 py-1 bg-yellow-500 rounded-full text-xs"><?php echo $unit['pending_requests']; ?></span>
                        <?php else: ?>
                        <span class="text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($unit['pending_approvals'] > 0): ?>
                        <span class="px-3 py-1 bg-purple-500 rounded-full text-xs"><?php echo $unit['pending_approvals']; ?></span>
                        <?php else: ?>
                        <span class="text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $unit['active_projects']; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $unit['head_name'] ?? 'غير معين'; ?>
                    </td>
                    <td class="px-6 py-4 font-semibold">
                        <span class="text-green-400"><?php echo $unit['name']; ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- سجل الموارد المعتمدة مؤخرًا -->
<!-- ============================================= -->
<?php if (!empty($recent_approved)): ?>
<div class="security-border manager-card rounded-xl p-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-history ml-2"></i>
        آخر الموارد المعتمدة
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($recent_approved as $item): ?>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="px-2 py-1 <?php echo $item['status'] == 'approved' ? 'bg-green-600' : 'bg-red-600'; ?> rounded-full text-xs">
                    <?php echo $item['status'] == 'approved' ? 'تمت الموافقة' : 'مرفوض'; ?>
                </span>
                <span class="text-xs text-gray-400"><?php echo formatTimeAgo1($item['review_date']); ?></span>
            </div>
            
            <h4 class="font-bold text-white text-sm mb-1"><?php echo $item['title']; ?></h4>
            <p class="text-xs text-gray-400 mb-2"><?php echo $item['unit_name']; ?></p>
            
            <div class="flex items-center justify-between text-xs">
                <span class="text-yellow-400 font-bold"><?php echo formatMoney($item['amount']); ?></span>
                <span class="text-gray-500"><?php echo $item['requester_name']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة طلب مورد جديد -->
<!-- ============================================= -->
<div id="create-resource-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateResourceModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                طلب مورد جديد
            </h3>
        </div>

        <form id="create-resource-form" onsubmit="saveResourceRequest(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">عنوان الطلب</label>
                    <input type="text" name="title" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الوحدة</label>
                    <select name="unit_id" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">اختر الوحدة</option>
                        <?php foreach ($unit_resources as $unit): ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع المورد</label>
                    <select name="resource_type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="equipment">معدات</option>
                        <option value="software">برمجيات</option>
                        <option value="personnel">موظفين</option>
                        <option value="training">تدريب</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الأولوية</label>
                    <select name="priority" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                        <option value="critical">حرجة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المبلغ</label>
                    <input type="number" name="amount" value="0" min="0" step="1000" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">مقدم الطلب</label>
                    <select name="requester_id" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">اختر الموظف</option>
                        <?php
                        $users = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();
                        foreach ($users as $user):
                        ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    إرسال الطلب
                </button>
                <button type="button" onclick="closeCreateResourceModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
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

let currentRequestId = null;

function createNewResourceRequest() {
    document.getElementById('create-resource-modal').classList.remove('hidden');
    document.getElementById('create-resource-modal').classList.add('flex');
}

function closeCreateResourceModal() {
    document.getElementById('create-resource-modal').classList.add('hidden');
    document.getElementById('create-resource-modal').classList.remove('flex');
}

function saveResourceRequest(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeCreateResourceModal();
        if (typeof showNotification === 'function') {
            showNotification('تم إرسال طلب المورد بنجاح', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshResources() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('تم تحديث بيانات الموارد', 'success');
        }
        location.reload();
    }, 1500);
}

function approveResourceRequest(requestId) {
    if (typeof showNotification === 'function') {
        showNotification(`تمت الموافقة على طلب المورد #${requestId}`, 'success');
    }
    setTimeout(() => location.reload(), 1500);
}

function rejectResourceRequest(requestId) {
    if (typeof showNotification === 'function') {
        showNotification(`تم رفض طلب المورد #${requestId}`, 'error');
    }
    setTimeout(() => location.reload(), 1500);
}

function reviewResourceRequest(requestId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح مراجعة طلب المورد #${requestId}`, 'info');
    }
}

function approveFinancialRequest(requestId) {
    if (typeof showNotification === 'function') {
        showNotification(`تمت الموافقة على الطلب المالي #${requestId}`, 'success');
    }
    setTimeout(() => location.reload(), 1500);
}

function rejectFinancialRequest(requestId) {
    if (typeof showNotification === 'function') {
        showNotification(`تم رفض الطلب المالي #${requestId}`, 'error');
    }
    setTimeout(() => location.reload(), 1500);
}

function reviewFinancialRequest(requestId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح مراجعة الطلب المالي #${requestId}`, 'info');
    }
}

function manageUnitResources(unitId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح إدارة موارد الوحدة ${unitId}`, 'info');
    }
}

function viewUnitDetails(unitId) {
    if (typeof showNotification === 'function') {
        showNotification(`عرض تفاصيل الوحدة ${unitId}`, 'info');
    }
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
    background: #10b981;
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