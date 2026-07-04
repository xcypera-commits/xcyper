<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/active_projects.php
// المشاريع النشطة - بيانات حقيقية فقط من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة عن المشاريع
    // =============================================
    
    // إجمالي المشاريع النشطة
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('completed', 'archived')");
    $total_active = $stmt->fetchColumn() ?: 0;
    
    // المشاريع المتأخرة
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'delayed'");
    $delayed_count = $stmt->fetchColumn() ?: 0;
    
    // المشاريع الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE priority = 'critical' AND status NOT IN ('completed', 'archived')");
    $critical_count = $stmt->fetchColumn() ?: 0;
    
    // المشاريع حسب الحالة
    $status_counts = [
        'documentation' => 0,
        'testing' => 0,
        'deployment' => 0,
        'delayed' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM projects WHERE status NOT IN ('completed', 'archived') GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = $row['count'];
        }
    }
    
    // =============================================
    // 2. قائمة جميع المشاريع النشطة مع التفاصيل
    // =============================================
    $projects = $db->query("
        SELECT 
            p.*,
            u.name as unit_name,
            u.code as unit_code,
            u.head_name as unit_head,
            (SELECT COUNT(*) FROM incidents WHERE unit_id = p.unit_id AND status IN ('open', 'in-progress')) as unit_incidents
        FROM projects p
        LEFT JOIN units u ON p.unit_id = u.id
        WHERE p.status NOT IN ('completed', 'archived')
        ORDER BY 
            CASE p.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            p.deadline ASC
    ")->fetchAll();
    
    // =============================================
    // 3. المشاريع حسب الأولوية
    // =============================================
    $priority_counts = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    foreach ($projects as $project) {
        if (isset($priority_counts[$project['priority']])) {
            $priority_counts[$project['priority']]++;
        }
    }
    
    // =============================================
    // 4. المشاريع حسب الوحدة
    // =============================================
    $projects_by_unit = [];
    foreach ($projects as $project) {
        $unit = $project['unit_name'] ?? 'بدون وحدة';
        if (!isset($projects_by_unit[$unit])) {
            $projects_by_unit[$unit] = 0;
        }
        $projects_by_unit[$unit]++;
    }
    
    // ترتيب الوحدات حسب عدد المشاريع
    arsort($projects_by_unit);
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
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

function getStatusText($status) {
    return match($status) {
        'documentation' => '📋 توثيق',
        'testing' => '🧪 اختبار',
        'deployment' => '🚀 نشر',
        'delayed' => '⚠️ متأخر',
        'completed' => '✅ مكتمل',
        'archived' => '📦 مؤرشف',
        default => $status
    };
}

function getProgressColor($progress) {
    if ($progress >= 75) return 'bg-green-500';
    if ($progress >= 50) return 'bg-yellow-500';
    if ($progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
}

function getDaysRemaining($deadline) {
    if (!$deadline) return 'غير محدد';
    
    $now = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $interval = $now->diff($deadlineDate);
    
    if ($deadlineDate < $now) {
        return 'متأخر ' . $interval->days . ' يوم';
    } else {
        return $interval->days . ' يوم متبقي';
    }
}

function getDeadlineColor($deadline) {
    if (!$deadline) return 'text-gray-400';
    
    $now = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $interval = $now->diff($deadlineDate);
    
    if ($deadlineDate < $now) {
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
        <h1 class="text-3xl font-bold text-right text-green-300">
            <i class="fas fa-project-diagram ml-2"></i>
            المشاريع النشطة
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="addNewProject()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-plus ml-2"></i>
                مشروع جديد
            </button>
            <button onclick="refreshProjects()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">إجمالي المشاريع</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo $total_active; ?></p>
            </div>
        </div>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">قيد التوثيق</p>
                <p class="text-3xl font-bold text-purple-400"><?php echo $status_counts['documentation']; ?></p>
            </div>
        </div>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">قيد الاختبار</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $status_counts['testing']; ?></p>
            </div>
        </div>
        <div class="bg-slate-800 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">قيد النشر</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $status_counts['deployment']; ?></p>
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">مشاريع متأخرة</span>
            <span class="text-lg font-bold text-red-400"><?php echo $delayed_count; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">مشاريع حرجة</span>
            <span class="text-lg font-bold text-red-400"><?php echo $critical_count; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">وحدات نشطة</span>
            <span class="text-lg font-bold text-green-400"><?php echo count($projects_by_unit); ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- توزيع المشاريع حسب الأولوية والوحدة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع حسب الأولوية -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-300 mb-4 flex items-center">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            توزيع المشاريع حسب الأولوية
        </h3>
        <div class="space-y-4">
            <?php 
            $priorities = [
                'critical' => 'حرجة',
                'high' => 'عالية',
                'medium' => 'متوسطة',
                'low' => 'منخفضة'
            ];
            foreach ($priorities as $key => $label): 
                $count = $priority_counts[$key] ?? 0;
                $percentage = $total_active > 0 ? round(($count / $total_active) * 100, 1) : 0;
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
                    <div class="progress-fill <?php echo getPriorityColor($key); ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- توزيع حسب الوحدة (أكثر 5 وحدات) -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-building ml-2"></i>
            أكثر الوحدات نشاطاً
        </h3>
        <div class="space-y-4">
            <?php 
            $counter = 0;
            foreach ($projects_by_unit as $unit => $count): 
                if ($counter >= 5) break;
                $percentage = $total_active > 0 ? round(($count / $total_active) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $unit; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $count; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php 
                $counter++;
            endforeach; 
            ?>
            <?php if (empty($projects_by_unit)): ?>
            <p class="text-center text-gray-400 py-4">لا توجد مشاريع نشطة</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- جدول المشاريع التفصيلي -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-right text-yellow-300">
            <i class="fas fa-list ml-2"></i>
            قائمة المشاريع النشطة
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-projects" placeholder="بحث في المشاريع..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="priority-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الأولويات</option>
                <option value="critical">حرجة</option>
                <option value="high">عالية</option>
                <option value="medium">متوسطة</option>
                <option value="low">منخفضة</option>
            </select>
            <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الحالات</option>
                <option value="documentation">توثيق</option>
                <option value="testing">اختبار</option>
                <option value="deployment">نشر</option>
                <option value="delayed">متأخر</option>
            </select>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-folder-open text-5xl text-gray-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد مشاريع نشطة</p>
        <p class="text-sm text-gray-500 mt-2">قم بإضافة مشاريع جديدة لعرضها هنا</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="projects-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الأولوية</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوحدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">نسبة الإنجاز</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ التسليم</th>
                    <th class="px-6 py-4 text-sm font-semibold">العميل</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم المشروع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors project-row" 
                    data-priority="<?php echo $project['priority']; ?>"
                    data-status="<?php echo $project['status']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewProjectDetails('<?php echo $project['code']; ?>')" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editProject('<?php echo $project['code']; ?>')" class="text-green-400 hover:text-green-300" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($project['priority'] == 'critical'): ?>
                            <span class="text-red-400" title="مشروع حرج">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo getPriorityColor($project['priority']); ?>">
                            <?php echo getPriorityText($project['priority']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo getStatusText($project['status']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($project['unit_name']): ?>
                        <div class="flex items-center">
                            <span class="text-sm text-gray-300"><?php echo $project['unit_name']; ?></span>
                            <?php if ($project['unit_incidents'] > 0): ?>
                            <span class="mr-2 px-2 py-0.5 bg-red-500 rounded-full text-xs">
                                <?php echo $project['unit_incidents']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span class="text-gray-500">غير محدد</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-16 bg-slate-600 rounded-full h-2 ml-2">
                                <div class="<?php echo getProgressColor($project['progress']); ?> h-2 rounded-full" style="width: <?php echo $project['progress']; ?>%"></div>
                            </div>
                            <span class="text-sm <?php echo $project['progress'] >= 75 ? 'text-green-400' : ($project['progress'] >= 50 ? 'text-yellow-400' : 'text-orange-400'); ?>">
                                <?php echo $project['progress']; ?>%
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($project['deadline']): ?>
                        <div class="text-sm <?php echo getDeadlineColor($project['deadline']); ?>">
                            <?php echo date('Y-m-d', strtotime($project['deadline'])); ?>
                            <span class="block text-xs mt-1">
                                <?php echo getDaysRemaining($project['deadline']); ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <span class="text-gray-500">غير محدد</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $project['client_name'] ?? 'غير محدد'; ?>
                    </td>
                    <td class="px-6 py-4 font-semibold text-green-400">
                        <div class="flex flex-col">
                            <span><?php echo $project['name']; ?></span>
                            <span class="text-xs text-gray-400"><?php echo $project['code']; ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($projects); ?> مشروع
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full ml-1"></span>
                حرج: <?php echo $priority_counts['critical']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-orange-500 rounded-full ml-1"></span>
                عالي: <?php echo $priority_counts['high']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                متوسط: <?php echo $priority_counts['medium']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                منخفض: <?php echo $priority_counts['low']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إضافة مشروع جديد -->
<!-- ============================================= -->
<div id="add-project-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddProjectModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                إضافة مشروع جديد
            </h3>
        </div>

        <form id="add-project-form" onsubmit="saveNewProject(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">كود المشروع</label>
                    <input type="text" name="project_code" required pattern="P-\d{4}" placeholder="P-1234" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم المشروع</label>
                    <input type="text" name="project_name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">العميل</label>
                    <input type="text" name="client_name" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الوحدة</label>
                    <select name="unit_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
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
                    <label class="block text-sm font-semibold mb-2 text-right">الأولوية</label>
                    <select name="priority" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                        <option value="critical">حرجة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ التسليم</label>
                    <input type="date" name="deadline" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الميزانية</label>
                    <input type="number" name="budget" value="0" min="0" step="1000" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نسبة الإنجاز</label>
                    <input type="number" name="progress" value="0" min="0" max="100" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ المشروع
                </button>
                <button type="button" onclick="closeAddProjectModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل المشروع -->
<!-- ============================================= -->
<div id="project-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeProjectDetailsModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400" id="project-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل المشروع
            </h3>
        </div>
        <div id="project-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script>
// دوال المشاريع
function addNewProject() {
    document.getElementById('add-project-modal').classList.remove('hidden');
    document.getElementById('add-project-modal').classList.add('flex');
}

function closeAddProjectModal() {
    document.getElementById('add-project-modal').classList.add('hidden');
    document.getElementById('add-project-modal').classList.remove('flex');
}

function saveNewProject(event) {
    event.preventDefault();
    showLoading();
    
    // محاكاة حفظ البيانات
    setTimeout(() => {
        hideLoading();
        closeAddProjectModal();
        showNotification('تم إضافة المشروع الجديد بنجاح', 'success');
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function viewProjectDetails(projectCode) {
    showLoading();
    
    // محاكاة جلب تفاصيل المشروع
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل المشروع ${projectCode}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="closeProjectDetailsModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('project-details-content').innerHTML = details;
        document.getElementById('project-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل المشروع ${projectCode}`;
        
        hideLoading();
        document.getElementById('project-details-modal').classList.remove('hidden');
        document.getElementById('project-details-modal').classList.add('flex');
    }, 1000);
}

function closeProjectDetailsModal() {
    document.getElementById('project-details-modal').classList.add('hidden');
    document.getElementById('project-details-modal').classList.remove('flex');
}

function editProject(projectCode) {
    showNotification(`فتح نافذة تعديل المشروع ${projectCode}`, 'info');
}

function refreshProjects() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('تم تحديث بيانات المشاريع', 'success');
        location.reload();
    }, 1500);
}

// البحث والتصفية
document.getElementById('search-projects')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.project-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.getElementById('priority-filter')?.addEventListener('change', filterProjects);
document.getElementById('status-filter')?.addEventListener('change', filterProjects);

function filterProjects() {
    const priorityFilter = document.getElementById('priority-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('.project-row');
    
    rows.forEach(row => {
        const priorityMatch = priorityFilter === 'all' || row.dataset.priority === priorityFilter;
        const statusMatch = statusFilter === 'all' || row.dataset.status === statusFilter;
        
        if (priorityMatch && statusMatch) {
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
.project-row {
    transition: all 0.3s ease;
}
.project-row:hover {
    background-color: rgba(51, 65, 85, 0.8);
}
</style>