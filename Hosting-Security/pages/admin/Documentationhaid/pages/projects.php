<?php
// =============================================
// documentation-unit/pages/projects.php
// صفحة إدارة المشاريع - وحدة التوثيق الفني
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'add_project':
                // إضافة مشروع جديد
                $project_code = generateProjectCode($_POST['project_type']);
                
                $sql = "INSERT INTO documentation_projects (
                    project_code, project_name, client_name, client_company,
                    project_type, priority, status, assigned_team,
                    project_manager, technical_lead, start_date, deadline,
                    description, security_level, repository_path, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $project_code,
                    $_POST['project_name'],
                    $_POST['client_name'] ?? null,
                    $_POST['client_company'] ?? null,
                    $_POST['project_type'],
                    $_POST['priority'],
                    $_POST['status'] ?? 'new',
                    $_POST['assigned_team'] ?? null,
                    $_POST['project_manager'] ?? null,
                    $_POST['technical_lead'] ?? null,
                    $_POST['start_date'] ?? date('Y-m-d'),
                    $_POST['deadline'] ?? null,
                    $_POST['description'] ?? null,
                    $_POST['security_level'] ?? 'normal',
                    $_POST['repository_path'] ?? null,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $project_id = $db->lastInsertId();
                
                // تسجيل النشاط
                logActivity($db, 'create', 'project', $project_id, 'إضافة مشروع جديد: ' . $_POST['project_name']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إضافة المشروع بنجاح';
                break;
                
            case 'edit_project':
                // تحديث مشروع
                $sql = "UPDATE documentation_projects SET
                    project_name = ?,
                    client_name = ?,
                    client_company = ?,
                    project_type = ?,
                    priority = ?,
                    status = ?,
                    assigned_team = ?,
                    project_manager = ?,
                    technical_lead = ?,
                    start_date = ?,
                    deadline = ?,
                    description = ?,
                    security_level = ?,
                    repository_path = ?
                WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['project_name'],
                    $_POST['client_name'] ?? null,
                    $_POST['client_company'] ?? null,
                    $_POST['project_type'],
                    $_POST['priority'],
                    $_POST['status'],
                    $_POST['assigned_team'] ?? null,
                    $_POST['project_manager'] ?? null,
                    $_POST['technical_lead'] ?? null,
                    $_POST['start_date'],
                    $_POST['deadline'] ?? null,
                    $_POST['description'] ?? null,
                    $_POST['security_level'] ?? 'normal',
                    $_POST['repository_path'] ?? null,
                    $_POST['project_id']
                ]);
                
                logActivity($db, 'update', 'project', $_POST['project_id'], 'تحديث مشروع: ' . $_POST['project_name']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تحديث المشروع بنجاح';
                break;
                
            case 'delete_project':
                // حذف مشروع
                $sql = "DELETE FROM documentation_projects WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['project_id']]);
                
                logActivity($db, 'delete', 'project', $_POST['project_id'], 'حذف مشروع');
                
                $response['success'] = true;
                $response['message'] = '✅ تم حذف المشروع بنجاح';
                break;
                
            case 'update_progress':
                // تحديث نسبة التقدم
                $sql = "UPDATE documentation_projects SET progress = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['progress'], $_POST['project_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تحديث نسبة التقدم';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    // إذا كان طلب AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // إذا كان طلب عادي
    $_SESSION['flash_message'] = $response;
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $status_filter = $_GET['status'] ?? '';
    $priority_filter = $_GET['priority'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // بناء استعلام المشاريع
    $sql = "
        SELECT 
            p.*,
            COUNT(DISTINCT d.id) as documents_count,
            SUM(CASE WHEN d.status = 'draft' THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN d.status = 'under_review' THEN 1 ELSE 0 END) as review_count,
            SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            COALESCE(SUM(d.pages), 0) as total_pages,
            DATEDIFF(p.deadline, CURDATE()) as days_remaining
        FROM documentation_projects p
        LEFT JOIN documents d ON p.id = d.project_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status_filter) {
        $sql .= " AND p.status = ?";
        $params[] = $status_filter;
    }
    
    if ($priority_filter) {
        $sql .= " AND p.priority = ?";
        $params[] = $priority_filter;
    }
    
    if ($type_filter) {
        $sql .= " AND p.project_type = ?";
        $params[] = $type_filter;
    }
    
    if ($search) {
        $sql .= " AND (p.project_name LIKE ? OR p.client_name LIKE ? OR p.project_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " GROUP BY p.id ORDER BY 
        CASE p.priority
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        p.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    
    // إحصائيات المشاريع
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold_count,
            SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_count,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count
        FROM documentation_projects
    ")->fetch();
    
    // قائمة المستخدمين للمشرفين
    $users = $db->query("SELECT id, full_name, role FROM users ORDER BY full_name")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// =============================================
// دوال مساعدة
// =============================================
function generateProjectCode($type) {
    global $db;
    $prefixes = [
        'hosting' => 'HOST',
        'storage' => 'STOR',
        'security' => 'SEC',
        'ecommerce' => 'ECOMM',
        'cloud' => 'CLOUD',
        'network' => 'NET'
    ];
    
    $prefix = $prefixes[$type] ?? 'PROJ';
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM documentation_projects WHERE project_code LIKE ?");
    $stmt->execute(["{$prefix}-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$number}";
}

function logActivity($db, $type, $target, $target_id, $description) {
    $sql = "INSERT INTO documentation_activity_log (user_id, activity_type, target_type, target_id, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id'] ?? 1, $type, $target, $target_id, $description]);
}

function getStatusBadge($status) {
    $classes = [
        'new' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'under_analysis' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'in_progress' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'completed' => 'bg-green-600 bg-opacity-20 text-green-400',
        'on_hold' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'cancelled' => 'bg-red-600 bg-opacity-20 text-red-400',
        'archived' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'new' => 'جديد',
        'under_analysis' => 'قيد التحليل',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'on_hold' => 'معلق',
        'cancelled' => 'ملغي',
        'archived' => 'مؤرشف'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getPriorityBadge($priority) {
    $classes = [
        'critical' => 'bg-red-600 bg-opacity-20 text-red-400',
        'high' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'medium' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'low' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'critical' => 'حرج',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض'
    ];
    
    $class = $classes[$priority] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$priority] ?? $priority;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getTypeBadge($type) {
    $classes = [
        'hosting' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'storage' => 'bg-green-600 bg-opacity-20 text-green-400',
        'security' => 'bg-red-600 bg-opacity-20 text-red-400',
        'ecommerce' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'cloud' => 'bg-cyan-600 bg-opacity-20 text-cyan-400',
        'network' => 'bg-yellow-600 bg-opacity-20 text-yellow-400'
    ];
    
    $texts = [
        'hosting' => 'استضافة',
        'storage' => 'تخزين',
        'security' => 'أمني',
        'ecommerce' => 'تجارة إلكترونية',
        'cloud' => 'سحابي',
        'network' => 'شبكات'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}
?>

<!-- ============================================= -->
<!-- رأس الصفحة مع الإحصائيات السريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <!-- إجمالي المشاريع -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي المشاريع</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total'] ?? 0; ?></p>
    </div>
    
    <!-- المشاريع الجديدة -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مشاريع جديدة</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['new_count'] ?? 0; ?></p>
    </div>
    
    <!-- قيد التنفيذ -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">قيد التنفيذ</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['in_progress_count'] ?? 0; ?></p>
    </div>
    
    <!-- مكتملة -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مكتملة</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['completed_count'] ?? 0; ?></p>
    </div>
    
    <!-- حرجة -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مشاريع حرجة</p>
        <p class="text-2xl font-bold text-red-400"><?php echo $stats['critical_count'] ?? 0; ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- شريط البحث والفلاتر -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h3 class="text-xl font-bold text-right">إدارة المشاريع</h3>
        
        <button onclick="openProjectModal('add')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow flex items-center justify-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            مشروع جديد
        </button>
    </div>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="page" value="projects">
        
        <!-- بحث -->
        <div class="relative">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="🔍 بحث في المشاريع..." 
                   class="w-full px-4 py-3 search-box rounded-lg text-right pr-12">
            <button type="submit" class="absolute left-2 top-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <!-- فلتر الحالة -->
        <select name="status" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">جميع الحالات</option>
            <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>جديد</option>
            <option value="under_analysis" <?php echo $status_filter == 'under_analysis' ? 'selected' : ''; ?>>قيد التحليل</option>
            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>مكتمل</option>
            <option value="on_hold" <?php echo $status_filter == 'on_hold' ? 'selected' : ''; ?>>معلق</option>
        </select>
        
        <!-- فلتر الأولوية -->
        <select name="priority" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">جميع الأولويات</option>
            <option value="critical" <?php echo $priority_filter == 'critical' ? 'selected' : ''; ?>>حرجة</option>
            <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>عالية</option>
            <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>متوسطة</option>
            <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>منخفضة</option>
        </select>
        
        <!-- فلتر النوع -->
        <select name="type" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">جميع الأنواع</option>
            <option value="hosting" <?php echo $type_filter == 'hosting' ? 'selected' : ''; ?>>استضافة</option>
            <option value="storage" <?php echo $type_filter == 'storage' ? 'selected' : ''; ?>>تخزين</option>
            <option value="security" <?php echo $type_filter == 'security' ? 'selected' : ''; ?>>أمني</option>
            <option value="ecommerce" <?php echo $type_filter == 'ecommerce' ? 'selected' : ''; ?>>تجارة إلكترونية</option>
            <option value="cloud" <?php echo $type_filter == 'cloud' ? 'selected' : ''; ?>>سحابي</option>
            <option value="network" <?php echo $type_filter == 'network' ? 'selected' : ''; ?>>شبكات</option>
        </select>
        
        <!-- زر إعادة تعيين -->
        <?php if ($search || $status_filter || $priority_filter || $type_filter): ?>
        <div class="md:col-span-4 flex justify-end">
            <a href="?page=projects" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm transition-all">
                ✕ إعادة تعيين الفلاتر
            </a>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- ============================================= -->
<!-- عرض المشاريع -->
<!-- ============================================= -->

<?php if (empty($projects)): ?>
    <!-- لا توجد مشاريع -->
    <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
        <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-2xl font-bold text-gray-400 mb-2">لا توجد مشاريع</h3>
        <p class="text-gray-500 mb-6">لم يتم العثور على مشاريع تطابق معايير البحث</p>
        <button onclick="openProjectModal('add')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all inline-flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة مشروع جديد
        </button>
    </div>
<?php else: ?>
    <!-- عرض المشاريع في بطاقات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($projects as $project): ?>
        <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6 project-card relative">
            <!-- شارات الحالة والأولوية -->
            <div class="absolute top-4 left-4 flex space-x-2 space-x-reverse">
                <?php echo getPriorityBadge($project['priority']); ?>
                <?php echo getTypeBadge($project['project_type']); ?>
            </div>
            
            <!-- رمز المشروع -->
            <div class="text-left mb-2">
                <span class="text-sm text-gray-400"><?php echo $project['project_code']; ?></span>
            </div>
            
            <!-- اسم المشروع والعميل -->
            <h3 class="text-xl font-bold mb-2 pl-16"><?php echo htmlspecialchars($project['project_name']); ?></h3>
            <p class="text-sm text-gray-400 mb-4">العميل: <?php echo htmlspecialchars($project['client_name'] ?? 'غير محدد'); ?></p>
            
            <!-- إحصائيات سريعة -->
            <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                <div class="bg-slate-900 rounded-lg p-2">
                    <p class="text-xs text-gray-400">مستندات</p>
                    <p class="text-lg font-bold text-blue-400"><?php echo $project['documents_count']; ?></p>
                </div>
                <div class="bg-slate-900 rounded-lg p-2">
                    <p class="text-xs text-gray-400">صفحات</p>
                    <p class="text-lg font-bold text-green-400"><?php echo $project['total_pages']; ?></p>
                </div>
                <div class="bg-slate-900 rounded-lg p-2">
                    <p class="text-xs text-gray-400">مراجعة</p>
                    <p class="text-lg font-bold text-yellow-400"><?php echo $project['review_count']; ?></p>
                </div>
            </div>
            
            <!-- شريط التقدم -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">تقدم المشروع</span>
                    <span class="text-blue-400"><?php echo $project['progress']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
                </div>
            </div>
            
            <!-- تفاصيل إضافية -->
            <div class="flex items-center justify-between text-xs text-gray-400 mb-4">
                <div class="flex items-center">
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>بداية: <?php echo date('Y-m-d', strtotime($project['start_date'])); ?></span>
                </div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="<?php echo $project['days_remaining'] < 0 ? 'text-red-400' : ''; ?>">
                        <?php echo $project['days_remaining'] > 0 ? $project['days_remaining'] . ' يوم' : 'م expired'; ?>
                    </span>
                </div>
            </div>
            
            <!-- حالة المشروع -->
            <div class="flex items-center justify-between mb-4">
                <?php echo getStatusBadge($project['status']); ?>
                <span class="text-xs text-gray-400">المسؤول: <?php echo htmlspecialchars($project['project_manager'] ?? 'غير محدد'); ?></span>
            </div>
            
            <!-- أزرار الإجراءات -->
            <div class="flex items-center justify-between gap-2">
                <button onclick="viewProjectDetails(<?php echo $project['id']; ?>)" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm transition-all">
                    عرض التفاصيل
                </button>
                <button onclick="openProjectModal('edit', <?php echo htmlspecialchars(json_encode($project)); ?>)" class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo addslashes($project['project_name']); ?>')" class="px-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- عرض المشاريع في جدول (اختياري) -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6 overflow-x-auto">
        <h3 class="text-xl font-bold mb-4 text-right">جميع المشاريع</h3>
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-4 py-3">الإجراءات</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">الأولوية</th>
                    <th class="px-4 py-3">النوع</th>
                    <th class="px-4 py-3">المستندات</th>
                    <th class="px-4 py-3">التقدم</th>
                    <th class="px-4 py-3">تاريخ التسليم</th>
                    <th class="px-4 py-3">العميل</th>
                    <th class="px-4 py-3">اسم المشروع</th>
                    <th class="px-4 py-3">الرمز</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewProjectDetails(<?php echo $project['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button onclick="openProjectModal('edit', <?php echo htmlspecialchars(json_encode($project)); ?>)" class="text-yellow-400 hover:text-yellow-300" title="تعديل">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-4 py-3"><?php echo getStatusBadge($project['status']); ?></td>
                    <td class="px-4 py-3"><?php echo getPriorityBadge($project['priority']); ?></td>
                    <td class="px-4 py-3"><?php echo getTypeBadge($project['project_type']); ?></td>
                    <td class="px-4 py-3 text-center"><?php echo $project['documents_count']; ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <span class="text-sm ml-2"><?php echo $project['progress']; ?>%</span>
                            <div class="progress-bar w-16">
                                <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 <?php echo $project['days_remaining'] < 0 ? 'text-red-400' : ''; ?>">
                        <?php echo date('Y-m-d', strtotime($project['deadline'])); ?>
                    </td>
                    <td class="px-4 py-3"><?php echo htmlspecialchars($project['client_name'] ?? '-'); ?></td>
                    <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($project['project_name']); ?></td>
                    <td class="px-4 py-3 text-gray-400"><?php echo $project['project_code']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة إضافة/تعديل مشروع -->
<!-- ============================================= -->
<div id="project-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-3xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeProjectModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right" id="modal-title">إضافة مشروع جديد</h3>
        </div>
        
        <form id="project-form" method="POST" onsubmit="handleProjectSubmit(event)">
            <input type="hidden" name="action" id="form-action" value="add_project">
            <input type="hidden" name="project_id" id="project-id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- اسم المشروع -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">اسم المشروع <span class="text-red-400">*</span></label>
                    <input type="text" id="project-name" name="project_name" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="أدخل اسم المشروع">
                </div>
                
                <!-- العميل والشركة -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم العميل</label>
                    <input type="text" id="client-name" name="client_name" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="اسم العميل">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الشركة</label>
                    <input type="text" id="client-company" name="client_company" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="اسم الشركة">
                </div>
                
                <!-- نوع المشروع والأولوية -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع المشروع <span class="text-red-400">*</span></label>
                    <select id="project-type" name="project_type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="hosting">استضافة</option>
                        <option value="storage">تخزين</option>
                        <option value="security">أمني</option>
                        <option value="ecommerce">تجارة إلكترونية</option>
                        <option value="cloud">سحابي</option>
                        <option value="network">شبكات</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الأولوية <span class="text-red-400">*</span></label>
                    <select id="priority" name="priority" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                        <option value="critical">حرجة</option>
                    </select>
                </div>
                
                <!-- الحالة ومستوى الأمان -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الحالة</label>
                    <select id="status" name="status" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="new">جديد</option>
                        <option value="under_analysis">قيد التحليل</option>
                        <option value="in_progress">قيد التنفيذ</option>
                        <option value="on_hold">معلق</option>
                        <option value="completed">مكتمل</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">مستوى الأمان</label>
                    <select id="security-level" name="security_level" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="normal">عادي</option>
                        <option value="sensitive">حساس</option>
                        <option value="critical">حرج</option>
                    </select>
                </div>
                
                <!-- الفريق والمسؤولين -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الفريق المسؤول</label>
                    <input type="text" id="assigned-team" name="assigned_team" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="اسم الفريق">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">مدير المشروع</label>
                    <select id="project-manager" name="project_manager" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- اختر المدير --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['full_name']; ?>"><?php echo $user['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المشرف الفني</label>
                    <input type="text" id="technical-lead" name="technical_lead" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="اسم المشرف الفني">
                </div>
                
                <!-- التواريخ -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ البداية <span class="text-red-400">*</span></label>
                    <input type="date" id="start-date" name="start_date" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ التسليم</label>
                    <input type="date" id="deadline" name="deadline" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <!-- مسار المستودع -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">مسار المستودع</label>
                    <input type="text" id="repository-path" name="repository_path" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="/repositories/project-name">
                </div>
                
                <!-- الوصف -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">وصف المشروع</label>
                    <textarea id="description" name="description" rows="4" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="أدخل وصف المشروع..."></textarea>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeProjectModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow" id="modal-submit">
                    حفظ المشروع
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تحديث التقدم -->
<!-- ============================================= -->
<div id="progress-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeProgressModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">تحديث تقدم المشروع</h3>
        </div>
        
        <form id="progress-form" method="POST" onsubmit="handleProgressSubmit(event)">
            <input type="hidden" name="action" value="update_progress">
            <input type="hidden" id="progress-project-id" name="project_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نسبة التقدم</label>
                    <input type="range" id="progress-value" name="progress" min="0" max="100" value="0" 
                           class="w-full" oninput="document.getElementById('progress-display').textContent = this.value + '%'">
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-400">0%</span>
                        <span class="text-lg font-bold text-blue-400" id="progress-display">0%</span>
                        <span class="text-xs text-gray-400">100%</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeProgressModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    تحديث
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript الخاص بالصفحة -->
<!-- ============================================= -->
<script>
// متغيرات عامة
let currentProjectId = null;

// دوال فتح وإغلاق النوافذ
function openProjectModal(action, project = null) {
    const modal = document.getElementById('project-modal');
    const title = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const submitBtn = document.getElementById('modal-submit');
    
    if (action === 'add') {
        title.textContent = '➕ إضافة مشروع جديد';
        formAction.value = 'add_project';
        submitBtn.textContent = 'إضافة المشروع';
        document.getElementById('project-form').reset();
        document.getElementById('project-id').value = '';
        document.getElementById('start-date').value = new Date().toISOString().split('T')[0];
    } else {
        title.textContent = '✏️ تعديل المشروع';
        formAction.value = 'edit_project';
        submitBtn.textContent = 'حفظ التعديلات';
        
        // تعبئة البيانات
        document.getElementById('project-id').value = project.id;
        document.getElementById('project-name').value = project.project_name;
        document.getElementById('client-name').value = project.client_name || '';
        document.getElementById('client-company').value = project.client_company || '';
        document.getElementById('project-type').value = project.project_type;
        document.getElementById('priority').value = project.priority;
        document.getElementById('status').value = project.status;
        document.getElementById('security-level').value = project.security_level || 'normal';
        document.getElementById('assigned-team').value = project.assigned_team || '';
        document.getElementById('project-manager').value = project.project_manager || '';
        document.getElementById('technical-lead').value = project.technical_lead || '';
        document.getElementById('start-date').value = project.start_date;
        document.getElementById('deadline').value = project.deadline || '';
        document.getElementById('repository-path').value = project.repository_path || '';
        document.getElementById('description').value = project.description || '';
    }
    
    modal.classList.remove('hidden');
}

function closeProjectModal() {
    document.getElementById('project-modal').classList.add('hidden');
}

function openProgressModal(projectId, currentProgress) {
    currentProjectId = projectId;
    document.getElementById('progress-project-id').value = projectId;
    document.getElementById('progress-value').value = currentProgress;
    document.getElementById('progress-display').textContent = currentProgress + '%';
    document.getElementById('progress-modal').classList.remove('hidden');
}

function closeProgressModal() {
    document.getElementById('progress-modal').classList.add('hidden');
}

// دوال معالجة النماذج
function handleProjectSubmit(event) {
    event.preventDefault();
    
    const form = document.getElementById('project-form');
    const formData = new FormData(form);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeProjectModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في الاتصال', 'error');
        console.error(error);
    });
}

function handleProgressSubmit(event) {
    event.preventDefault();
    
    const form = document.getElementById('progress-form');
    const formData = new FormData(form);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeProgressModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في الاتصال', 'error');
        console.error(error);
    });
}

function deleteProject(projectId, projectName) {
    if (confirm(`هل أنت متأكد من حذف المشروع "${projectName}"؟`)) {
        const formData = new FormData();
        formData.append('action', 'delete_project');
        formData.append('project_id', projectId);
        
        showLoading();
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ حدث خطأ في الاتصال', 'error');
            console.error(error);
        });
    }
}

function viewProjectDetails(projectId) {
    window.location.href = '?page=projects&view=' + projectId;
}

function updateProgress(projectId, currentProgress) {
    openProgressModal(projectId, currentProgress);
}

// دوال مساعدة
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'info': 'ℹ️',
        'warning': '⚠️'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
    notification.innerHTML = `<div class="flex items-center"><span class="ml-3">${icons[type]}</span><span>${message}</span></div>`;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ صفحة المشاريع جاهزة');
    
    // تعيين تاريخ اليوم للحقل start-date إذا كان فارغاً
    if (!document.getElementById('start-date').value) {
        document.getElementById('start-date').value = new Date().toISOString().split('T')[0];
    }
});
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
/* شريط التقدم */
.progress-bar {
    height: 6px;
    background: #334155;
    border-radius: 3px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    transition: width 0.3s ease;
}

/* بطاقات المشاريع */
.project-card {
    border-right: 4px solid #3b82f6;
    transition: all 0.3s ease;
}
.project-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
}

/* مربع البحث */
.search-box {
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid #334155;
    transition: all 0.3s ease;
}
.search-box:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

/* رأس الجدول */
.table-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}

/* النوافذ المنبثقة */
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

/* مؤشر التحميل */
.spinner {
    border: 3px solid rgba(59, 130, 246, 0.3);
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* الإشعارات */
.notification {
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>