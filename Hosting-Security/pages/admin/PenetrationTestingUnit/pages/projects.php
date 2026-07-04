<?php
// =============================================
// pentest-unit/pages/projects.php
// صفحة المشاريع للفحص
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$filter = $_GET['filter'] ?? 'all';

try {
    // =============================================
    // 1. إحصائيات المشاريع
    // =============================================
    
    // إجمالي المشاريع
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_projects");
    $total_projects = $stmt->fetchColumn() ?: 0;
    
    // المشاريع قيد الفحص
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_projects WHERE status = 'in-progress'");
    $in_progress_count = $stmt->fetchColumn() ?: 0;
    
    // المشاريع المكتملة
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_projects WHERE status = 'completed'");
    $completed_count = $stmt->fetchColumn() ?: 0;
    
    // المشاريع الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_projects WHERE severity IN ('critical', 'high') AND status = 'in-progress'");
    $critical_count = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. جميع المشاريع مع التفاصيل
    // =============================================
    
    $sql = "SELECT 
                p.*,
                u.full_name as tester_name,
                (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id) as total_vulns,
                (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id AND severity = 'critical') as critical_vulns,
                (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id AND severity = 'high') as high_vulns,
                (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id AND severity = 'medium') as medium_vulns,
                (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id AND severity = 'low') as low_vulns,
                (SELECT COUNT(*) FROM security_scans WHERE project_id = p.id) as total_scans,
                (SELECT MAX(started_at) FROM security_scans WHERE project_id = p.id) as last_scan
            FROM pentest_projects p
            LEFT JOIN users u ON p.tester_id = u.id";
    
    // تطبيق التصفية
    switch($filter) {
        case 'critical':
            $sql .= " WHERE p.severity IN ('critical', 'high') AND p.status = 'in-progress'";
            break;
        case 'in-progress':
            $sql .= " WHERE p.status = 'in-progress'";
            break;
        case 'completed':
            $sql .= " WHERE p.status = 'completed'";
            break;
    }
    
    $sql .= " ORDER BY 
                CASE p.severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                p.deadline ASC";
    
    $projects = $db->query($sql)->fetchAll();
    
    // =============================================
    // 3. إحصائيات إضافية للمشاريع
    // =============================================
    
    // توزيع المشاريع حسب الخطورة
    $severity_distribution = $db->query("
        SELECT severity, COUNT(*) as count 
        FROM pentest_projects 
        WHERE status = 'in-progress'
        GROUP BY severity
    ")->fetchAll();
    
    // المختبرين المتاحين
    $testers = $db->query("
        SELECT id, full_name 
        FROM users 
        WHERE role IN ('pentester', 'admin') 
        AND is_active = 1
        ORDER BY full_name
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getProjectStatusBadge($status) {
    return match($status) {
        'pending' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">في انتظار</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">قيد الفحص</span>',
        'completed' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">مكتمل</span>',
        'cancelled' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">ملغي</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getSeverityBadge($severity) {
    return match($severity) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'high' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">عالي</span>',
        'medium' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">متوسط</span>',
        'low' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منخفض</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getDaysRemaining($deadline) {
    if (!$deadline) return 'غير محدد';
    
    $now = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $interval = $now->diff($deadlineDate);
    
    if ($deadlineDate < $now) {
        return '<span class="text-red-400">متأخر ' . $interval->days . ' يوم</span>';
    } else {
        return '<span class="text-green-400">' . $interval->days . ' يوم متبقي</span>';
    }
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وأزرار التحكم -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-yellow-300">
            <i class="fas fa-project-diagram ml-2"></i>
            المشاريع للفحص
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="addNewProject()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center cyber-glow">
                <i class="fas fa-plus ml-2"></i>
                مشروع جديد
            </button>
            <button onclick="refreshProjects()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">إجمالي المشاريع</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo $total_projects; ?></p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">قيد الفحص</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $in_progress_count; ?></p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">مشاريع حرجة</p>
                <p class="text-3xl font-bold text-red-400"><?php echo $critical_count; ?></p>
            </div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-center">
                <p class="text-gray-400 text-sm">مكتملة</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $completed_count; ?></p>
            </div>
        </div>
    </div>

    <!-- أزرار التصفية -->
    <div class="flex items-center justify-between mt-4">
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="filterProjects('all')" class="px-4 py-2 <?php echo $filter == 'all' ? 'bg-yellow-600' : 'bg-slate-700'; ?> hover:bg-yellow-700 rounded-lg text-sm font-semibold transition-all">
                الكل
            </button>
            <button onclick="filterProjects('critical')" class="px-4 py-2 <?php echo $filter == 'critical' ? 'bg-red-600' : 'bg-slate-700'; ?> hover:bg-red-700 rounded-lg text-sm font-semibold transition-all">
                حرجة
            </button>
            <button onclick="filterProjects('in-progress')" class="px-4 py-2 <?php echo $filter == 'in-progress' ? 'bg-green-600' : 'bg-slate-700'; ?> hover:bg-green-700 rounded-lg text-sm font-semibold transition-all">
                قيد الفحص
            </button>
            <button onclick="filterProjects('completed')" class="px-4 py-2 <?php echo $filter == 'completed' ? 'bg-blue-600' : 'bg-slate-700'; ?> hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all">
                مكتملة
            </button>
        </div>
        <div class="text-sm text-gray-400">
            عرض <?php echo count($projects); ?> مشروع
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- بطاقات المشاريع -->
<!-- ============================================= -->
<?php if (empty($projects)): ?>
<div class="text-center py-12 bg-slate-800 rounded-lg">
    <i class="fas fa-folder-open text-5xl text-gray-500 mb-4"></i>
    <p class="text-xl text-gray-400">لا توجد مشاريع</p>
    <p class="text-sm text-gray-500 mt-2">قم بإضافة مشاريع جديدة للفحص</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <?php foreach ($projects as $project): ?>
    <div class="card-hover cyber-border bg-slate-900 rounded-xl p-6 project-card" data-status="<?php echo $project['status']; ?>" data-severity="<?php echo $project['severity']; ?>">
        <!-- رأس البطاقة -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2 space-x-reverse">
                <?php echo getSeverityBadge($project['severity']); ?>
                <?php echo getProjectStatusBadge($project['status']); ?>
            </div>
            <span class="text-xs text-gray-400">#<?php echo $project['project_code']; ?></span>
        </div>

        <!-- معلومات المشروع -->
        <h3 class="text-xl font-bold text-yellow-400 mb-2"><?php echo $project['project_name']; ?></h3>
        <p class="text-sm text-gray-400 mb-4">العميل: <?php echo $project['client_name'] ?? 'غير محدد'; ?></p>
        
        <!-- وصف المشروع -->
        <?php if (!empty($project['description'])): ?>
        <p class="text-sm text-gray-300 mb-4 line-clamp-2"><?php echo $project['description']; ?></p>
        <?php endif; ?>

        <!-- إحصائيات الثغرات -->
        <div class="grid grid-cols-4 gap-2 mb-4 text-center">
            <div>
                <p class="text-xs text-gray-400">حرجة</p>
                <p class="text-sm font-bold text-red-400"><?php echo $project['critical_vulns']; ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400">عالية</p>
                <p class="text-sm font-bold text-yellow-400"><?php echo $project['high_vulns']; ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400">متوسطة</p>
                <p class="text-sm font-bold text-blue-400"><?php echo $project['medium_vulns']; ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400">منخفضة</p>
                <p class="text-sm font-bold text-green-400"><?php echo $project['low_vulns']; ?></p>
            </div>
        </div>

        <!-- شريط التقدم -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-400">تقدم الفحص</span>
                <span class="text-xs text-yellow-400"><?php echo $project['progress']; ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
            </div>
        </div>

        <!-- معلومات إضافية -->
        <div class="space-y-2 text-sm mb-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-400">
                    <i class="fas fa-calendar ml-1"></i>
                    البداية:
                </span>
                <span class="text-gray-300"><?php echo $project['start_date'] ?? 'غير محدد'; ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400">
                    <i class="fas fa-hourglass-end ml-1"></i>
                    التسليم:
                </span>
                <span><?php echo getDaysRemaining($project['deadline']); ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400">
                    <i class="fas fa-user ml-1"></i>
                    المختبر:
                </span>
                <span class="text-blue-400"><?php echo $project['tester_name'] ?? 'غير معين'; ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400">
                    <i class="fas fa-search ml-1"></i>
                    آخر فحص:
                </span>
                <span class="text-gray-300"><?php echo $project['last_scan'] ? date('Y-m-d', strtotime($project['last_scan'])) : 'لم يبدأ'; ?></span>
            </div>
        </div>

        <!-- أزرار التحكم -->
        <div class="grid grid-cols-3 gap-2 mt-4">
            <button onclick="viewProjectDetails(<?php echo $project['id']; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-eye ml-1"></i>
                عرض
            </button>
            <button onclick="startProjectScan(<?php echo $project['id']; ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-play ml-1"></i>
                فحص
            </button>
            <button onclick="assignTester(<?php echo $project['id']; ?>)" class="bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-user-plus ml-1"></i>
                تعيين
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ============================================= -->
<!-- جدول المشاريع التفصيلي -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-right text-blue-300">
            <i class="fas fa-table ml-2"></i>
            تفاصيل جميع المشاريع
        </h3>
        <button onclick="exportProjects()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold transition-colors flex items-center">
            <i class="fas fa-download ml-1"></i>
            تصدير
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الخطورة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الثغرات</th>
                    <th class="px-6 py-4 text-sm font-semibold">التقدم</th>
                    <th class="px-6 py-4 text-sm font-semibold">المختبر</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ التسليم</th>
                    <th class="px-6 py-4 text-sm font-semibold">العميل</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم المشروع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewProjectDetails(<?php echo $project['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editProject(<?php echo $project['id']; ?>)" class="text-green-400 hover:text-green-300" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($project['status'] != 'completed'): ?>
                            <button onclick="startProjectScan(<?php echo $project['id']; ?>)" class="text-yellow-400 hover:text-yellow-300" title="بدء فحص">
                                <i class="fas fa-play"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getProjectStatusBadge($project['status']); ?></td>
                    <td class="px-6 py-4"><?php echo getSeverityBadge($project['severity']); ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-1 space-x-reverse">
                            <span class="text-red-400"><?php echo $project['critical_vulns']; ?></span>/
                            <span class="text-yellow-400"><?php echo $project['high_vulns']; ?></span>/
                            <span class="text-blue-400"><?php echo $project['medium_vulns']; ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-700 rounded-full h-2 ml-2">
                                <div class="<?php echo getProgressColor($project['progress']); ?> h-2 rounded-full" style="width: <?php echo $project['progress']; ?>%"></div>
                            </div>
                            <span class="text-sm"><?php echo $project['progress']; ?>%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $project['tester_name'] ?? 'غير معين'; ?></td>
                    <td class="px-6 py-4"><?php echo getDaysRemaining($project['deadline']); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $project['client_name'] ?? 'غير محدد'; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $project['project_name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة إضافة مشروع جديد -->
<!-- ============================================= -->
<div id="add-project-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddProjectModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-yellow-400">
                <i class="fas fa-plus-circle ml-2"></i>
                إضافة مشروع جديد
            </h3>
        </div>

        <form id="add-project-form" onsubmit="saveNewProject(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">كود المشروع</label>
                    <input type="text" name="project_code" required pattern="P\d{4}-\d{3}" placeholder="P2024-001" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم المشروع</label>
                    <input type="text" name="project_name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">العميل</label>
                    <input type="text" name="client_name" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">مستوى الخطورة</label>
                    <select name="severity" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                        <option value="low">منخفض</option>
                        <option value="medium" selected>متوسط</option>
                        <option value="high">عالي</option>
                        <option value="critical">حرج</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ البدء</label>
                    <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ التسليم</label>
                    <input type="date" name="deadline" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المختبر</label>
                    <select name="tester_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
                        <option value="">غير معين</option>
                        <?php foreach ($testers as $tester): ?>
                        <option value="<?php echo $tester['id']; ?>"><?php echo $tester['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right"></textarea>
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
<!-- نافذة تعيين مختبر -->
<!-- ============================================= -->
<div id="assign-tester-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAssignTesterModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-purple-400">
                <i class="fas fa-user-plus ml-2"></i>
                تعيين مختبر للمشروع
            </h3>
        </div>

        <form id="assign-tester-form" onsubmit="saveTesterAssignment(event)" class="space-y-4">
            <input type="hidden" id="assign-project-id" name="project_id">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">اختر المختبر</label>
                <select id="assign-tester-id" name="tester_id" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500 text-right">
                    <option value="">اختر المختبر</option>
                    <?php foreach ($testers as $tester): ?>
                    <option value="<?php echo $tester['id']; ?>"><?php echo $tester['full_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-check ml-2"></i>
                    تعيين
                </button>
                <button type="button" onclick="closeAssignTesterModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
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
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
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
// =============================================
// متغيرات عامة
// =============================================
let currentProjectId = null;

// =============================================
// دوال التصفية
// =============================================
function filterProjects(filter) {
    window.location.href = `?page=projects&filter=${filter}`;
}

// =============================================
// دوال المشاريع
// =============================================
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
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeAddProjectModal();
        if (typeof showNotification === 'function') {
            showNotification('✅ تم إضافة المشروع الجديد بنجاح', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshProjects() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث بيانات المشاريع', 'success');
        }
        location.reload();
    }, 1500);
}

function viewProjectDetails(projectId) {
    currentProjectId = projectId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل المشروع
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل المشروع #${projectId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="startProjectScan(${projectId})" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg">بدء فحص</button>
                    <button onclick="editProject(${projectId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تعديل</button>
                    <button onclick="closeProjectDetailsModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('project-details-content').innerHTML = details;
        document.getElementById('project-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل المشروع #${projectId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('project-details-modal').classList.remove('hidden');
        document.getElementById('project-details-modal').classList.add('flex');
    }, 1000);
}

function closeProjectDetailsModal() {
    document.getElementById('project-details-modal').classList.add('hidden');
    document.getElementById('project-details-modal').classList.remove('flex');
}

function startProjectScan(projectId) {
    if (typeof showNotification === 'function') {
        showNotification(`🚀 بدء فحص المشروع #${projectId}`, 'info');
    }
    closeProjectDetailsModal();
}

function editProject(projectId) {
    if (typeof showNotification === 'function') {
        showNotification(`✏️ تعديل المشروع #${projectId}`, 'info');
    }
}

function assignTester(projectId) {
    currentProjectId = projectId;
    document.getElementById('assign-project-id').value = projectId;
    document.getElementById('assign-tester-modal').classList.remove('hidden');
    document.getElementById('assign-tester-modal').classList.add('flex');
}

function closeAssignTesterModal() {
    document.getElementById('assign-tester-modal').classList.add('hidden');
    document.getElementById('assign-tester-modal').classList.remove('flex');
}

function saveTesterAssignment(event) {
    event.preventDefault();
    
    const testerId = document.getElementById('assign-tester-id').value;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeAssignTesterModal();
        if (typeof showNotification === 'function') {
            showNotification(`👤 تم تعيين مختبر للمشروع #${currentProjectId}`, 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function exportProjects() {
    if (typeof showNotification === 'function') {
        showNotification('📥 جاري تصدير بيانات المشاريع', 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification('✅ تم تصدير البيانات بنجاح', 'success');
        }
    }, 2000);
}
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.project-card {
    transition: all 0.3s ease;
}
.project-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(245, 158, 11, 0.2);
}
</style>