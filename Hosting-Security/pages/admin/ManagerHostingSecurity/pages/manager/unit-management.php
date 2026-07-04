<?php
// =============================================
// manager/pages/manager/unit_management.php
// إدارة الوحدات الأربع - بيانات حقيقية فقط من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// جلب جميع بيانات الوحدات من قاعدة البيانات
// =============================================

try {
    // 1. إحصائيات عامة عن الوحدات
    $stats = [];
    
    // إجمالي عدد الوحدات
    $stmt = $db->query("SELECT COUNT(*) FROM units");
    $stats['total_units'] = $stmt->fetchColumn();
    if ($stats['total_units'] === false) $stats['total_units'] = 0;
    
    // إجمالي عدد الموظفين في جميع الوحدات
    $stmt = $db->query("SELECT SUM(employee_count) FROM units");
    $stats['total_employees'] = $stmt->fetchColumn();
    if ($stats['total_employees'] === false) $stats['total_employees'] = 0;
    
    // إجمالي الميزانيات
    $stmt = $db->query("SELECT SUM(budget) FROM units");
    $stats['total_budget'] = $stmt->fetchColumn();
    if ($stats['total_budget'] === false) $stats['total_budget'] = 0;
    
    // عدد المشاريع النشطة في جميع الوحدات
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('completed', 'archived')");
    $stats['total_projects'] = $stmt->fetchColumn();
    if ($stats['total_projects'] === false) $stats['total_projects'] = 0;
    
    // 2. بيانات جميع الوحدات مع إحصائياتها
    $units = $db->query("
        SELECT 
            u.*,
            COUNT(DISTINCT p.id) as active_projects,
            COUNT(DISTINCT i.id) as active_incidents,
            COUNT(DISTINCT rr.id) as pending_requests,
            COUNT(DISTINCT pa.id) as pending_approvals,
            (SELECT COUNT(*) FROM users WHERE unit_id = u.id) as actual_employees
        FROM units u
        LEFT JOIN projects p ON u.id = p.unit_id AND p.status NOT IN ('completed', 'archived')
        LEFT JOIN incidents i ON u.id = i.unit_id AND i.status IN ('open', 'in-progress')
        LEFT JOIN resource_requests rr ON u.id = rr.unit_id AND rr.status = 'pending'
        LEFT JOIN pending_approvals pa ON u.id = pa.unit_id AND pa.status = 'pending'
        GROUP BY u.id
        ORDER BY u.id
    ")->fetchAll();
    
    // 3. الموظفين في كل وحدة
    $unit_members = [];
    foreach ($units as $unit) {
        $stmt = $db->prepare("
            SELECT id, full_name, username, role, email
            FROM users
            WHERE unit_id = ?
            ORDER BY role, full_name
            LIMIT 5
        ");
        $stmt->execute([$unit['id']]);
        $unit_members[$unit['id']] = $stmt->fetchAll();
    }
    
    // 4. المشاريع الأخيرة لكل وحدة
    $recent_projects = [];
    foreach ($units as $unit) {
        $stmt = $db->prepare("
            SELECT name, status, priority, progress, deadline
            FROM projects
            WHERE unit_id = ? AND status NOT IN ('completed', 'archived')
            ORDER BY deadline ASC
            LIMIT 3
        ");
        $stmt->execute([$unit['id']]);
        $recent_projects[$unit['id']] = $stmt->fetchAll();
    }
    
    // 5. أداء الوحدات (آخر 30 يوم)
    $performance = $db->query("
        SELECT 
            unit_id,
            ROUND(AVG(productivity), 1) as avg_productivity,
            ROUND(AVG(quality), 1) as avg_quality,
            ROUND(AVG(speed), 1) as avg_speed
        FROM performance_metrics
        WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY unit_id
    ")->fetchAll();
    
    // تحويل الأداء إلى مصفوفة مفهرسة بمعرف الوحدة
    $performance_by_unit = [];
    foreach ($performance as $perf) {
        $performance_by_unit[$perf['unit_id']] = $perf;
    }
    
} catch (Exception $e) {
    // في حالة خطأ في قاعدة البيانات، نعرض الخطأ ولا نستخدم بيانات تجريبية
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

function getUnitClass($code) {
    return match($code) {
        'DOC' => 'unit-documentation',
        'STR' => 'unit-storage',
        'SEC' => 'unit-security',
        'PEN' => 'unit-pentest',
        default => 'unit-documentation'
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

function getPriorityColor($priority) {
    return match($priority) {
        'critical' => 'bg-red-500',
        'high' => 'bg-orange-500',
        'medium' => 'bg-yellow-500',
        'low' => 'bg-blue-500',
        default => 'bg-gray-500'
    };
}

function getProgressColor($progress) {
    if ($progress >= 75) return 'text-green-400';
    if ($progress >= 50) return 'text-yellow-400';
    if ($progress >= 25) return 'text-orange-400';
    return 'text-red-400';
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-blue-300">
            <i class="fas fa-cubes ml-2"></i>
            إدارة الوحدات الأربع
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="addNewUnit()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-plus ml-2"></i>
                إضافة وحدة جديدة
            </button>
            <button onclick="refreshUnitData()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">إجمالي الوحدات</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo $stats['total_units']; ?></p>
            </div>
        </div>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">إجمالي الموظفين</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $stats['total_employees']; ?></p>
            </div>
        </div>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">إجمالي الميزانيات</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo formatMoney($stats['total_budget']); ?></p>
            </div>
        </div>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">المشاريع النشطة</p>
                <p class="text-3xl font-bold text-purple-400"><?php echo $stats['total_projects']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- بطاقات الوحدات الأربع -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <?php foreach ($units as $unit): 
        $unitClass = getUnitClass($unit['code']);
        $unitIcon = getUnitIcon($unit['code']);
        $perf = $performance_by_unit[$unit['id']] ?? ['avg_productivity' => 0, 'avg_quality' => 0, 'avg_speed' => 0];
    ?>
    <div class="security-border manager-card rounded-xl p-6 <?php echo $unitClass; ?>">
        <!-- رأس الوحدة -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <span class="text-4xl ml-3"><?php echo $unitIcon; ?></span>
                <div>
                    <h2 class="text-2xl font-bold"><?php echo $unit['name']; ?></h2>
                    <p class="text-sm opacity-80">الكود: <?php echo $unit['code']; ?></p>
                </div>
            </div>
            <div class="text-left">
                <span class="px-3 py-1 bg-slate-700 rounded-full text-xs">
                    <?php echo $unit['status'] ?? 'active'; ?>
                </span>
            </div>
        </div>

        <!-- معلومات أساسية -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-slate-800 rounded-lg p-3">
                <p class="text-xs text-gray-400">رئيس الوحدة</p>
                <p class="font-semibold"><?php echo $unit['head_name'] ?? 'غير معين'; ?></p>
            </div>
            <div class="bg-slate-800 rounded-lg p-3">
                <p class="text-xs text-gray-400">الموظفين</p>
                <p class="font-semibold"><?php echo $unit['employee_count']; ?> / <?php echo $unit['max_employees']; ?></p>
            </div>
            <div class="bg-slate-800 rounded-lg p-3">
                <p class="text-xs text-gray-400">الميزانية</p>
                <p class="font-semibold text-blue-400"><?php echo formatMoney($unit['budget']); ?></p>
            </div>
            <div class="bg-slate-800 rounded-lg p-3">
                <p class="text-xs text-gray-400">المشاريع النشطة</p>
                <p class="font-semibold text-green-400"><?php echo $unit['active_projects'] ?? 0; ?></p>
            </div>
        </div>

        <!-- مؤشرات الأداء -->
        <div class="grid grid-cols-3 gap-2 mb-4">
            <div class="text-center">
                <p class="text-xs text-gray-400">الإنتاجية</p>
                <p class="text-lg font-bold <?php echo $perf['avg_productivity'] >= 70 ? 'text-green-400' : 'text-yellow-400'; ?>">
                    <?php echo $perf['avg_productivity']; ?>%
                </p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400">الجودة</p>
                <p class="text-lg font-bold <?php echo $perf['avg_quality'] >= 70 ? 'text-green-400' : 'text-yellow-400'; ?>">
                    <?php echo $perf['avg_quality']; ?>%
                </p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400">السرعة</p>
                <p class="text-lg font-bold <?php echo $perf['avg_speed'] >= 70 ? 'text-green-400' : 'text-yellow-400'; ?>">
                    <?php echo $perf['avg_speed']; ?>%
                </p>
            </div>
        </div>

        <!-- المشاريع الأخيرة -->
        <?php if (!empty($recent_projects[$unit['id']])): ?>
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-300 mb-2">أحدث المشاريع:</h4>
            <div class="space-y-2">
                <?php foreach ($recent_projects[$unit['id']] as $project): ?>
                <div class="flex items-center justify-between text-xs">
                    <span class="truncate max-w-[150px]"><?php echo $project['name']; ?></span>
                    <div class="flex items-center">
                        <span class="px-2 py-0.5 <?php echo getPriorityColor($project['priority']); ?> rounded-full ml-2">
                            <?php echo $project['priority']; ?>
                        </span>
                        <span class="<?php echo getProgressColor($project['progress']); ?>">
                            <?php echo $project['progress']; ?>%
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- أعضاء الفريق -->
        <?php if (!empty($unit_members[$unit['id']])): ?>
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-300 mb-2">أعضاء الفريق:</h4>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($unit_members[$unit['id']] as $member): ?>
                <div class="bg-slate-700 rounded-full px-3 py-1 text-xs flex items-center">
                    <i class="fas fa-user-circle ml-1"></i>
                    <?php echo explode(' ', $member['full_name'])[0]; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- طلبات وموافقات -->
        <div class="flex items-center justify-between text-sm mb-4">
            <div class="flex items-center">
                <i class="fas fa-clock text-yellow-400 ml-1"></i>
                <span>طلبات معلقة: <?php echo $unit['pending_requests'] ?? 0; ?></span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-check-circle text-purple-400 ml-1"></i>
                <span>موافقات: <?php echo $unit['pending_approvals'] ?? 0; ?></span>
            </div>
        </div>

        <!-- أزرار التحكم -->
        <div class="grid grid-cols-3 gap-2 mt-4">
            <button onclick="manageUnitDetails('<?php echo $unit['code']; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-eye ml-1"></i>
                عرض
            </button>
            <button onclick="editUnit('<?php echo $unit['code']; ?>')" class="bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-edit ml-1"></i>
                تعديل
            </button>
            <button onclick="unitReports('<?php echo $unit['code']; ?>')" class="bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-chart-bar ml-1"></i>
                تقارير
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ============================================= -->
<!-- جدول تفصيلي لجميع الوحدات -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-right text-green-300">
            <i class="fas fa-table ml-2"></i>
            تفاصيل جميع الوحدات
        </h3>
        <button onclick="exportUnitsData()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-colors flex items-center">
            <i class="fas fa-download ml-1"></i>
            تصدير البيانات
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الموظفين</th>
                    <th class="px-6 py-4 text-sm font-semibold">المشاريع</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحوادث</th>
                    <th class="px-6 py-4 text-sm font-semibold">الميزانية</th>
                    <th class="px-6 py-4 text-sm font-semibold">الإنتاجية</th>
                    <th class="px-6 py-4 text-sm font-semibold">رئيس الوحدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم الوحدة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units as $unit): 
                    $perf = $performance_by_unit[$unit['id']] ?? ['avg_productivity' => 0];
                ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="manageUnitDetails('<?php echo $unit['code']; ?>')" class="text-blue-400 hover:text-blue-300" title="عرض">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editUnit('<?php echo $unit['code']; ?>')" class="text-green-400 hover:text-green-300" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo ($unit['status'] ?? 'active') == 'active' ? 'bg-green-500' : 'bg-yellow-500'; ?>">
                            <?php echo $unit['status'] ?? 'active'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $unit['employee_count']; ?>/<?php echo $unit['max_employees']; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $unit['active_projects'] ?? 0; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <span class="<?php echo ($unit['active_incidents'] ?? 0) > 0 ? 'text-red-400' : 'text-green-400'; ?>">
                            <?php echo $unit['active_incidents'] ?? 0; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-blue-400">
                        <?php echo formatMoney($unit['budget']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="<?php echo $perf['avg_productivity'] >= 70 ? 'text-green-400' : 'text-yellow-400'; ?>">
                            <?php echo $perf['avg_productivity']; ?>%
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $unit['head_name'] ?? 'غير معين'; ?>
                    </td>
                    <td class="px-6 py-4 font-semibold <?php echo getUnitClass($unit['code']); ?>">
                        <?php echo $unit['name']; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة إضافة وحدة جديدة -->
<!-- ============================================= -->
<div id="add-unit-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddUnitModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                إضافة وحدة جديدة
            </h3>
        </div>

        <form id="add-unit-form" onsubmit="saveNewUnit(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم الوحدة</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الكود</label>
                    <input type="text" name="code" required maxlength="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">رئيس الوحدة</label>
                    <input type="text" name="head_name" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">عدد الموظفين</label>
                    <input type="number" name="employee_count" value="0" min="0" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الحد الأقصى</label>
                    <input type="number" name="max_employees" value="10" min="1" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الميزانية</label>
                    <input type="number" name="budget" value="0" min="0" step="1000" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ الوحدة
                </button>
                <button type="button" onclick="closeAddUnitModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل الوحدة -->
<!-- ============================================= -->
<div id="unit-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeUnitDetailsModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400" id="unit-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل الوحدة
            </h3>
        </div>
        <div id="unit-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script>
// دوال الوحدات
function addNewUnit() {
    document.getElementById('add-unit-modal').classList.remove('hidden');
    document.getElementById('add-unit-modal').classList.add('flex');
}

function closeAddUnitModal() {
    document.getElementById('add-unit-modal').classList.add('hidden');
    document.getElementById('add-unit-modal').classList.remove('flex');
}

function saveNewUnit(event) {
    event.preventDefault();
    showLoading();
    
    // محاكاة حفظ البيانات
    setTimeout(() => {
        hideLoading();
        closeAddUnitModal();
        showNotification('تم إضافة الوحدة الجديدة بنجاح', 'success');
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function manageUnitDetails(unitCode) {
    showLoading();
    
    // محاكاة جلب تفاصيل الوحدة
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل وحدة ${unitCode}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="closeUnitDetailsModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('unit-details-content').innerHTML = details;
        document.getElementById('unit-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل وحدة ${unitCode}`;
        
        hideLoading();
        document.getElementById('unit-details-modal').classList.remove('hidden');
        document.getElementById('unit-details-modal').classList.add('flex');
    }, 1000);
}

function closeUnitDetailsModal() {
    document.getElementById('unit-details-modal').classList.add('hidden');
    document.getElementById('unit-details-modal').classList.remove('flex');
}

function editUnit(unitCode) {
    showNotification(`فتح نافذة تعديل وحدة ${unitCode}`, 'info');
}

function unitReports(unitCode) {
    showNotification(`عرض تقارير وحدة ${unitCode}`, 'info');
}

function refreshUnitData() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('تم تحديث بيانات الوحدات', 'success');
        location.reload();
    }, 1500);
}

function exportUnitsData() {
    showNotification('جاري تصدير بيانات الوحدات...', 'info');
    setTimeout(() => {
        showNotification('تم تصدير البيانات بنجاح', 'success');
    }, 2000);
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
.unit-documentation {
    border-right: 4px solid #3b82f6;
}
.unit-storage {
    border-right: 4px solid #8b5cf6;
}
.unit-security {
    border-right: 4px solid #10b981;
}
.unit-pentest {
    border-right: 4px solid #f59e0b;
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
</style>