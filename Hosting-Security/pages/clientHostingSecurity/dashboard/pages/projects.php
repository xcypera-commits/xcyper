<?php
// =============================================
// client-unit/pages/projects.php
// صفحة مشاريع العميل
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: العميل غير محدد</div>';
    return;
}

try {
    // =============================================
    // معالجة طلب عرض مشروع محدد
    // =============================================
    $view_project_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
    $project_details = null;
    
    if ($view_project_id > 0) {
        $stmt = $db->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM client_files WHERE project_id = p.id) as files_count,
                   (SELECT COUNT(*) FROM client_invoices WHERE project_id = p.id) as invoices_count,
                   (SELECT COUNT(*) FROM client_reports WHERE project_id = p.id) as reports_count,
                   (SELECT COUNT(*) FROM client_support_tickets WHERE project_id = p.id) as tickets_count
            FROM client_projects p
            WHERE p.id = ? AND p.client_id = ?
        ");
        $stmt->execute([$view_project_id, $current_client['id']]);
        $project_details = $stmt->fetch();
        
        if ($project_details) {
            // جلب ملفات المشروع
            $stmt = $db->prepare("
                SELECT * FROM client_files 
                WHERE project_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$view_project_id]);
            $project_files = $stmt->fetchAll();
            
            // جلب فواتير المشروع
            $stmt = $db->prepare("
                SELECT * FROM client_invoices 
                WHERE project_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$view_project_id]);
            $project_invoices = $stmt->fetchAll();
            
            // جلب تقارير المشروع
            $stmt = $db->prepare("
                SELECT * FROM client_reports 
                WHERE project_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$view_project_id]);
            $project_reports = $stmt->fetchAll();
        }
    }
    
    // =============================================
    // إحصائيات المشاريع
    // =============================================
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'under_study' THEN 1 ELSE 0 END) as under_study,
            SUM(CASE WHEN status = 'contract_pending' THEN 1 ELSE 0 END) as contract_pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'testing' THEN 1 ELSE 0 END) as testing,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM client_projects
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $stats = $stmt->fetch();
    
    // =============================================
    // جلب جميع المشاريع مع الفلاتر
    // =============================================
    
    $status_filter = $_GET['status'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $sql = "
        SELECT p.*, 
               (SELECT COUNT(*) FROM client_files WHERE project_id = p.id) as files_count,
               (SELECT COUNT(*) FROM client_invoices WHERE project_id = p.id) as invoices_count
        FROM client_projects p
        WHERE p.client_id = ?
    ";
    
    $params = [$current_client['id']];
    
    if (!empty($status_filter)) {
        $sql .= " AND p.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($type_filter)) {
        $sql .= " AND p.project_type = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($search)) {
        $sql .= " AND (p.project_name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    
    // =============================================
    // مراحل المشروع
    // =============================================
    
    $stages = [
        1 => ['name' => 'الطلب', 'color' => 'blue'],
        2 => ['name' => 'الدراسة', 'color' => 'cyan'],
        3 => ['name' => 'العقد', 'color' => 'purple'],
        4 => ['name' => 'التنفيذ', 'color' => 'green'],
        5 => ['name' => 'الفحص', 'color' => 'orange'],
        6 => ['name' => 'التسليم', 'color' => 'green'],
        7 => ['name' => 'الدعم', 'color' => 'blue']
    ];
    
    // دوال مساعدة
    function getStatusColor($status) {
        $colors = [
            'pending' => 'yellow',
            'under_study' => 'blue',
            'contract_pending' => 'purple',
            'in_progress' => 'green',
            'testing' => 'orange',
            'completed' => 'green',
            'cancelled' => 'red'
        ];
        return $colors[$status] ?? 'gray';
    }
    
    function getStatusText($status) {
        $texts = [
            'pending' => 'قيد الانتظار',
            'under_study' => 'قيد الدراسة',
            'contract_pending' => 'بانتظار العقد',
            'in_progress' => 'قيد التنفيذ',
            'testing' => 'قيد الاختبار',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي'
        ];
        return $texts[$status] ?? $status;
    }
    
    function getProjectTypeText($type) {
        $texts = [
            'hosting' => 'استضافة',
            'storage' => 'تخزين سحابي',
            'security' => 'أمن المعلومات',
            'pentest' => 'اختبار اختراق',
            'consultation' => 'استشارة',
            'development' => 'تطوير'
        ];
        return $texts[$type] ?? $type;
    }
    
} catch (Exception $e) {
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
    $projects = [];
    $stats = [];
    $project_details = null;
}
?>

<?php if ($project_details): ?>
<!-- ============================================= -->
<!-- عرض تفاصيل مشروع محدد -->
<!-- ============================================= -->

<!-- شريط التنقل -->
<div class="flex items-center justify-between mb-6">
    <button onclick="window.location.href='?page=projects'" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
        </svg>
        العودة إلى المشاريع
    </button>
    
    <h2 class="text-2xl font-bold text-right">تفاصيل المشروع</h2>
</div>

<!-- معلومات المشروع -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-start justify-between mb-4">
        <div>
            <span class="text-sm text-gray-400"><?php echo $project_details['project_code']; ?></span>
        </div>
        <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($project_details['project_name']); ?></h1>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-slate-700 rounded-lg p-3">
            <p class="text-xs text-gray-400">الحالة</p>
            <p class="font-semibold">
                <span class="px-2 py-1 rounded-full text-xs bg-<?php echo getStatusColor($project_details['status']); ?>-600 bg-opacity-20 text-<?php echo getStatusColor($project_details['status']); ?>-400">
                    <?php echo getStatusText($project_details['status']); ?>
                </span>
            </p>
        </div>
        <div class="bg-slate-700 rounded-lg p-3">
            <p class="text-xs text-gray-400">المرحلة</p>
            <p class="font-semibold"><?php echo $stages[$project_details['stage']]['name'] ?? 'غير محدد'; ?></p>
        </div>
        <div class="bg-slate-700 rounded-lg p-3">
            <p class="text-xs text-gray-400">نوع المشروع</p>
            <p class="font-semibold"><?php echo getProjectTypeText($project_details['project_type']); ?></p>
        </div>
        <div class="bg-slate-700 rounded-lg p-3">
            <p class="text-xs text-gray-400">الأولوية</p>
            <p class="font-semibold"><?php echo $project_details['priority']; ?></p>
        </div>
    </div>
    
    <!-- شريط التقدم -->
    <div class="mb-4">
        <div class="flex items-center justify-between text-sm mb-1">
            <span class="text-gray-400">تقدم المشروع</span>
            <span class="text-blue-400"><?php echo $project_details['progress']; ?>%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $project_details['progress']; ?>%"></div>
        </div>
    </div>
    
    <!-- التواريخ -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <p class="text-gray-400">تاريخ البدء</p>
            <p class="font-semibold"><?php echo date('Y-m-d', strtotime($project_details['start_date'])); ?></p>
        </div>
        <div>
            <p class="text-gray-400">تاريخ التسليم</p>
            <p class="font-semibold"><?php echo $project_details['deadline'] ? date('Y-m-d', strtotime($project_details['deadline'])) : '-'; ?></p>
        </div>
        <div>
            <p class="text-gray-400">تاريخ الإكمال</p>
            <p class="font-semibold"><?php echo $project_details['completion_date'] ? date('Y-m-d', strtotime($project_details['completion_date'])) : '-'; ?></p>
        </div>
    </div>
    
    <!-- الوصف -->
    <?php if ($project_details['description']): ?>
    <div class="mt-4 pt-4 border-t border-slate-700">
        <p class="text-sm text-gray-400 mb-2">وصف المشروع</p>
        <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($project_details['description'])); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- معلومات المالية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-700">
        <div>
            <p class="text-sm text-gray-400">الميزانية</p>
            <p class="text-xl font-bold text-green-400"><?php echo number_format($project_details['budget'], 2); ?> ر.س</p>
        </div>
        <div>
            <p class="text-sm text-gray-400">المدفوع</p>
            <p class="text-xl font-bold text-blue-400"><?php echo number_format($project_details['paid_amount'], 2); ?> ر.س</p>
        </div>
    </div>
    
    <!-- إحصائيات سريعة -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-700">
        <div class="text-center">
            <p class="text-2xl font-bold text-blue-400"><?php echo $project_details['files_count']; ?></p>
            <p class="text-xs text-gray-400">ملفات</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-green-400"><?php echo $project_details['invoices_count']; ?></p>
            <p class="text-xs text-gray-400">فواتير</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-purple-400"><?php echo $project_details['reports_count']; ?></p>
            <p class="text-xs text-gray-400">تقارير</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-yellow-400"><?php echo $project_details['tickets_count']; ?></p>
            <p class="text-xs text-gray-400">تذاكر</p>
        </div>
    </div>
</div>

<!-- تبويبات المحتوى -->
<div class="border-b border-slate-700 mb-6">
    <div class="flex space-x-6 space-x-reverse">
        <button class="tab-btn active px-4 py-2 text-blue-400 border-b-2 border-blue-400" onclick="showTab('files')">
            الملفات (<?php echo count($project_files ?? []); ?>)
        </button>
        <button class="tab-btn px-4 py-2 text-gray-400 hover:text-blue-400" onclick="showTab('invoices')">
            الفواتير (<?php echo count($project_invoices ?? []); ?>)
        </button>
        <button class="tab-btn px-4 py-2 text-gray-400 hover:text-blue-400" onclick="showTab('reports')">
            التقارير (<?php echo count($project_reports ?? []); ?>)
        </button>
    </div>
</div>

<!-- محتوى التبويبات -->
<div id="tab-files" class="tab-content">
    <?php if (empty($project_files)): ?>
        <p class="text-gray-400 text-center py-8">لا توجد ملفات لهذا المشروع</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($project_files as $file): ?>
            <div class="bg-slate-700 rounded-lg p-4 hover:bg-slate-600 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-400"><?php echo timeAgo($file['created_at']); ?></span>
                    <span class="text-xs bg-blue-600 bg-opacity-20 text-blue-400 px-2 py-1 rounded">
                        <?php echo $file['file_type']; ?>
                    </span>
                </div>
                <h4 class="font-semibold text-sm mb-2 truncate"><?php echo htmlspecialchars($file['file_name']); ?></h4>
                <p class="text-xs text-gray-400 mb-3"><?php echo formatFileSize($file['file_size']); ?></p>
                <button onclick="downloadFile('<?php echo $file['file_path']; ?>')" class="text-xs bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">
                    تحميل
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="tab-invoices" class="tab-content hidden">
    <?php if (empty($project_invoices)): ?>
        <p class="text-gray-400 text-center py-8">لا توجد فواتير لهذا المشروع</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">رقم الفاتورة</th>
                        <th class="px-4 py-3">العنوان</th>
                        <th class="px-4 py-3">المبلغ</th>
                        <th class="px-4 py-3">تاريخ الإصدار</th>
                        <th class="px-4 py-3">تاريخ الاستحقاق</th>
                        <th class="px-4 py-3">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($project_invoices as $invoice): ?>
                    <tr class="border-b border-slate-600">
                        <td class="px-4 py-3 text-sm"><?php echo $invoice['invoice_code']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($invoice['title']); ?></td>
                        <td class="px-4 py-3 text-sm font-semibold"><?php echo number_format($invoice['total_amount'], 2); ?> ر.س</td>
                        <td class="px-4 py-3 text-sm"><?php echo date('Y-m-d', strtotime($invoice['issue_date'])); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full bg-<?php echo getStatusColor($invoice['status']); ?>-600 bg-opacity-20 text-<?php echo getStatusColor($invoice['status']); ?>-400">
                                <?php echo $invoice['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="tab-reports" class="tab-content hidden">
    <?php if (empty($project_reports)): ?>
        <p class="text-gray-400 text-center py-8">لا توجد تقارير لهذا المشروع</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($project_reports as $report): ?>
            <div class="bg-slate-700 rounded-lg p-4 hover:bg-slate-600 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-400"><?php echo $report['report_code']; ?></span>
                    <span class="text-xs bg-<?php echo $report['status'] == 'ready' ? 'green' : 'yellow'; ?>-600 bg-opacity-20 text-<?php echo $report['status'] == 'ready' ? 'green' : 'yellow'; ?>-400 px-2 py-1 rounded">
                        <?php echo $report['status']; ?>
                    </span>
                </div>
                <h4 class="font-semibold text-sm mb-2"><?php echo htmlspecialchars($report['title']); ?></h4>
                <p class="text-xs text-gray-400 mb-3"><?php echo date('Y-m-d', strtotime($report['created_at'])); ?></p>
                <?php if ($report['status'] == 'ready'): ?>
                <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="text-xs bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">
                    تحميل
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ============================================= -->
<!-- عرض قائمة المشاريع -->
<!-- ============================================= -->

<!-- رأس الصفحة -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold text-right">مشاريعي</h2>
    <button onclick="requestNewService()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold">
        طلب مشروع جديد
    </button>
</div>

<!-- إحصائيات سريعة -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 mb-8">
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">الكل</p>
        <p class="text-lg font-bold text-blue-400"><?php echo $stats['total'] ?? 0; ?></p>
    </div>
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">قيد الانتظار</p>
        <p class="text-lg font-bold text-yellow-400"><?php echo $stats['pending'] ?? 0; ?></p>
    </div>
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">قيد الدراسة</p>
        <p class="text-lg font-bold text-blue-400"><?php echo $stats['under_study'] ?? 0; ?></p>
    </div>
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">بانتظار العقد</p>
        <p class="text-lg font-bold text-purple-400"><?php echo $stats['contract_pending'] ?? 0; ?></p>
    </div>
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">قيد التنفيذ</p>
        <p class="text-lg font-bold text-green-400"><?php echo $stats['in_progress'] ?? 0; ?></p>
    </div>
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">قيد الاختبار</p>
        <p class="text-lg font-bold text-orange-400"><?php echo $stats['testing'] ?? 0; ?></p>
    </div>
    <div class="bg-slate-800 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-400">مكتمل</p>
        <p class="text-lg font-bold text-green-400"><?php echo $stats['completed'] ?? 0; ?></p>
    </div>
</div>

<!-- شريط البحث والفلاتر -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="page" value="projects">
        
        <div class="relative md:col-span-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="بحث في المشاريع..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
            <button type="submit" class="absolute left-2 top-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <select name="status" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الحالات</option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
            <option value="under_study" <?php echo $status_filter == 'under_study' ? 'selected' : ''; ?>>قيد الدراسة</option>
            <option value="contract_pending" <?php echo $status_filter == 'contract_pending' ? 'selected' : ''; ?>>بانتظار العقد</option>
            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
            <option value="testing" <?php echo $status_filter == 'testing' ? 'selected' : ''; ?>>قيد الاختبار</option>
            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>مكتمل</option>
        </select>
        
        <select name="type" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الأنواع</option>
            <option value="hosting" <?php echo $type_filter == 'hosting' ? 'selected' : ''; ?>>استضافة</option>
            <option value="storage" <?php echo $type_filter == 'storage' ? 'selected' : ''; ?>>تخزين سحابي</option>
            <option value="security" <?php echo $type_filter == 'security' ? 'selected' : ''; ?>>أمن المعلومات</option>
            <option value="pentest" <?php echo $type_filter == 'pentest' ? 'selected' : ''; ?>>اختبار اختراق</option>
            <option value="development" <?php echo $type_filter == 'development' ? 'selected' : ''; ?>>تطوير</option>
        </select>
        
        <?php if ($search || $status_filter || $type_filter): ?>
        <div class="md:col-span-4 flex justify-end">
            <a href="?page=projects" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                إعادة تعيين
            </a>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- قائمة المشاريع -->
<?php if (empty($projects)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
        <p class="text-2xl font-bold text-gray-400 mb-2">لا توجد مشاريع</p>
        <p class="text-gray-500 mb-6">قم بطلب مشروع جديد للبدء</p>
        <button onclick="requestNewService()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold">
            طلب مشروع جديد
        </button>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach ($projects as $project): 
            $color = getStatusColor($project['status']);
        ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6 hover:shadow-lg transition-all cursor-pointer"
             onclick="window.location.href='?page=projects&view=<?php echo $project['id']; ?>'">
            
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-400"><?php echo $project['project_code']; ?></span>
                <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $color; ?>-600 bg-opacity-20 text-<?php echo $color; ?>-400">
                    <?php echo getStatusText($project['status']); ?>
                </span>
            </div>
            
            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($project['project_name']); ?></h3>
            <p class="text-sm text-gray-400 mb-3"><?php echo getProjectTypeText($project['project_type']); ?></p>
            
            <div class="flex items-center justify-between text-sm mb-3">
                <span class="text-gray-400">المرحلة: <?php echo $stages[$project['stage']]['name'] ?? 'غير محدد'; ?></span>
                <span class="text-gray-400">التقدم: <?php echo $project['progress']; ?>%</span>
            </div>
            
            <div class="progress-bar mb-4">
                <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
            </div>
            
            <div class="flex items-center justify-between text-xs text-gray-400">
                <span>📄 <?php echo $project['files_count']; ?> ملف</span>
                <span>💰 <?php echo number_format($project['budget'] ?? 0, 0); ?> ر.س</span>
                <span>📅 <?php echo date('Y-m-d', strtotime($project['start_date'])); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<!-- JavaScript -->
<script>
function showTab(tab) {
    // إخفاء كل المحتوى
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // إظهار المحتوى المحدد
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    // تحديث الأزرار
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-blue-400', 'border-b-2', 'border-blue-400');
        btn.classList.add('text-gray-400');
    });
    
    event.target.classList.add('active', 'text-blue-400', 'border-b-2', 'border-blue-400');
    event.target.classList.remove('text-gray-400');
}

function downloadFile(path) {
    window.open(path, '_blank');
}

function downloadReport(id) {
    window.location.href = '?page=reports&download=' + id;
}
</script>