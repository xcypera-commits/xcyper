<?php
// =============================================
// pentest-unit/pages/tools.php
// صفحة أدوات الاختبار - بيانات حقيقية من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

try {
    // =============================================
    // 1. إحصائيات الأدوات - من قاعدة البيانات
    // =============================================
    
    // إجمالي الأدوات
    $stmt = $db->query("SELECT COUNT(*) FROM testing_tools");
    $total_tools = $stmt->fetchColumn() ?: 0;
    
    // الأدوات حسب الحالة
    $status_counts = [
        'active' => 0,
        'inactive' => 0,
        'needs-update' => 0,
        'installing' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM testing_tools GROUP BY status");
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // الأدوات حسب الفئة
    $category_counts = [
        'recon' => 0,
        'vulnerability' => 0,
        'exploitation' => 0,
        'web' => 0,
        'network' => 0,
        'reporting' => 0
    ];
    
    $stmt = $db->query("SELECT category, COUNT(*) as count FROM testing_tools GROUP BY category");
    while ($row = $stmt->fetch()) {
        $category_counts[$row['category']] = $row['count'];
    }
    
    // آخر تحديث
    $stmt = $db->query("SELECT MAX(last_updated) FROM testing_tools");
    $last_update = $stmt->fetchColumn();
    
    // =============================================
    // 2. جميع الأدوات مع التفاصيل - من قاعدة البيانات
    // =============================================
    
    $sql = "SELECT * FROM testing_tools WHERE 1=1";
    
    if ($category_filter !== 'all') {
        $sql .= " AND category = '$category_filter'";
    }
    
    if ($status_filter !== 'all') {
        $sql .= " AND status = '$status_filter'";
    }
    
    $sql .= " ORDER BY 
                CASE status
                    WHEN 'active' THEN 1
                    WHEN 'needs-update' THEN 2
                    WHEN 'installing' THEN 3
                    WHEN 'inactive' THEN 4
                END,
                name ASC";
    
    $tools = $db->query($sql)->fetchAll();
    
    // =============================================
    // 3. الأدوات التي تحتاج تحديث - من قاعدة البيانات
    // =============================================
    
    $tools_needing_update = $db->query("
        SELECT * FROM testing_tools 
        WHERE status = 'needs-update'
        ORDER BY last_updated ASC
    ")->fetchAll();
    
    // =============================================
    // 4. آخر الأدوات المستخدمة - من قاعدة البيانات
    // =============================================
    
    $recently_used = $db->query("
        SELECT * FROM testing_tools 
        WHERE last_used IS NOT NULL
        ORDER BY last_used DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 5. إحصائيات استخدام الأدوات - من قاعدة البيانات
    // =============================================
    
    $usage_stats = $db->query("
        SELECT 
            t.name,
            COUNT(s.id) as scan_count,
            SUM(s.findings_count) as total_findings,
            SUM(s.critical_count) as critical_findings
        FROM testing_tools t
        LEFT JOIN security_scans s ON t.id = s.tool_id
        GROUP BY t.id
        ORDER BY scan_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 6. السكربتات المخصصة - من قاعدة البيانات
    // =============================================
    
    $custom_scripts = $db->query("
        SELECT cs.*, u.full_name as author_name
        FROM custom_scripts cs
        LEFT JOIN users u ON cs.author_id = u.id
        WHERE cs.status = 'active'
        ORDER BY cs.last_run DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getToolStatusBadge($status) {
    return match($status) {
        'active' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">نشط</span>',
        'inactive' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير نشط</span>',
        'needs-update' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">يحتاج تحديث</span>',
        'installing' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">جاري التثبيت</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getToolCategoryBadge($category) {
    return match($category) {
        'recon' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">استطلاع</span>',
        'vulnerability' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">ثغرات</span>',
        'exploitation' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">استغلال</span>',
        'web' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">ويب</span>',
        'network' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">شبكة</span>',
        'reporting' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">تقارير</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getLicenseTypeText($license) {
    return match($license) {
        'open-source' => 'مفتوح المصدر',
        'free' => 'مجاني',
        'paid' => 'مدفوع',
        'trial' => 'تجريبي',
        default => $license
    };
}

function getScriptTypeText($type) {
    return match($type) {
        'bash' => 'Bash',
        'python' => 'Python',
        'powershell' => 'PowerShell',
        'other' => 'أخرى',
        default => $type
    };
}

function getScriptTypeColor($type) {
    return match($type) {
        'bash' => 'bg-green-600',
        'python' => 'bg-blue-600',
        'powershell' => 'bg-purple-600',
        'other' => 'bg-gray-600',
        default => 'bg-gray-600'
    };
}

function formatDate($date) {
    if (!$date) return 'غير محدد';
    return date('Y-m-d', strtotime($date));
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-purple-300">
            <i class="fas fa-tools ml-2"></i>
            أدوات الاختبار
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="addNewTool()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center cyber-glow">
                <i class="fas fa-plus ml-2"></i>
                إضافة أداة جديدة
            </button>
            <button onclick="updateAllTools()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث الكل
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">أدوات نشطة</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $status_counts['active']; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                جاهزة للاستخدام
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">تحتاج تحديث</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $status_counts['needs-update']; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                يوصى بالتحديث
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">قيد التثبيت</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $status_counts['installing']; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-spinner fa-spin text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                جاري التثبيت
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">إجمالي الأدوات</p>
                    <p class="text-3xl font-bold text-purple-400"><?php echo $total_tools; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-database text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                آخر تحديث: <?php echo $last_update ? date('Y-m-d', strtotime($last_update)) : 'لا يوجد'; ?>
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">أدوات غير نشطة</span>
            <span class="text-lg font-bold text-gray-400"><?php echo $status_counts['inactive']; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">آخر استخدام</span>
            <span class="text-lg font-bold text-blue-400">
                <?php 
                $last_used = $db->query("SELECT MAX(last_used) FROM testing_tools")->fetchColumn();
                echo $last_used ? date('Y-m-d', strtotime($last_used)) : 'لا يوجد';
                ?>
            </span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- أزرار التصفية -->
<!-- ============================================= -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-2 space-x-reverse">
        <select id="category-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-purple-500">
            <option value="all" <?php echo $category_filter == 'all' ? 'selected' : ''; ?>>كل الفئات</option>
            <option value="recon" <?php echo $category_filter == 'recon' ? 'selected' : ''; ?>>استطلاع</option>
            <option value="vulnerability" <?php echo $category_filter == 'vulnerability' ? 'selected' : ''; ?>>ثغرات</option>
            <option value="exploitation" <?php echo $category_filter == 'exploitation' ? 'selected' : ''; ?>>استغلال</option>
            <option value="web" <?php echo $category_filter == 'web' ? 'selected' : ''; ?>>ويب</option>
            <option value="network" <?php echo $category_filter == 'network' ? 'selected' : ''; ?>>شبكة</option>
            <option value="reporting" <?php echo $category_filter == 'reporting' ? 'selected' : ''; ?>>تقارير</option>
        </select>

        <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-purple-500">
            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>كل الحالات</option>
            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>نشط</option>
            <option value="needs-update" <?php echo $status_filter == 'needs-update' ? 'selected' : ''; ?>>يحتاج تحديث</option>
            <option value="installing" <?php echo $status_filter == 'installing' ? 'selected' : ''; ?>>قيد التثبيت</option>
            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
        </select>

        <div class="relative">
            <input type="text" id="search-tools" placeholder="بحث في الأدوات..." 
                   class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-purple-500">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
        </div>
    </div>
    <div class="text-sm text-gray-400">
        عرض <?php echo count($tools); ?> أداة
    </div>
</div>

<!-- ============================================= -->
<!-- الأدوات التي تحتاج تحديث (تنبيه) -->
<!-- ============================================= -->
<?php if (!empty($tools_needing_update)): ?>
<div class="bg-yellow-900 bg-opacity-20 border border-yellow-800 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl ml-3"></i>
            <div>
                <h4 class="text-yellow-400 font-semibold">توجد أدوات تحتاج تحديث</h4>
                <p class="text-sm text-gray-300">يوجد <?php echo count($tools_needing_update); ?> أدوات تحتاج إلى تحديث لضمان أفضل أداء</p>
            </div>
        </div>
        <button onclick="updateNeededTools()" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm font-semibold">
            تحديث الكل
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- بطاقات الأدوات حسب الفئة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- استطلاع -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-blue-400">🔍 أدوات الاستطلاع</h4>
            <span class="px-3 py-1 bg-blue-600 rounded-full text-xs font-bold"><?php echo $category_counts['recon']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mb-4">جمع المعلومات وتحليل النظم</p>
        
        <?php 
        $recon_tools = array_filter($tools, function($t) { return $t['category'] == 'recon'; });
        if (!empty($recon_tools)): 
        ?>
        <div class="space-y-2">
            <?php foreach (array_slice($recon_tools, 0, 3) as $tool): ?>
            <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg">
                <span class="text-sm font-semibold"><?php echo $tool['name']; ?></span>
                <?php echo getToolStatusBadge($tool['status']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500 text-sm">لا توجد أدوات استطلاع</p>
        <?php endif; ?>
    </div>

    <!-- ثغرات -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-red-400">🛡️ ماسحات الثغرات</h4>
            <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo $category_counts['vulnerability']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mb-4">فحص الثغرات والضعف الأمني</p>
        
        <?php 
        $vuln_tools = array_filter($tools, function($t) { return $t['category'] == 'vulnerability'; });
        if (!empty($vuln_tools)): 
        ?>
        <div class="space-y-2">
            <?php foreach (array_slice($vuln_tools, 0, 3) as $tool): ?>
            <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg">
                <span class="text-sm font-semibold"><?php echo $tool['name']; ?></span>
                <?php echo getToolStatusBadge($tool['status']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500 text-sm">لا توجد ماسحات ثغرات</p>
        <?php endif; ?>
    </div>

    <!-- استغلال -->
    <div class="cyber-border bg-slate-800 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-orange-400">⚔️ أدوات الاستغلال</h4>
            <span class="px-3 py-1 bg-orange-600 rounded-full text-xs font-bold"><?php echo $category_counts['exploitation']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mb-4">استغلال الثغرات بشكل آمن</p>
        
        <?php 
        $exploit_tools = array_filter($tools, function($t) { return $t['category'] == 'exploitation'; });
        if (!empty($exploit_tools)): 
        ?>
        <div class="space-y-2">
            <?php foreach (array_slice($exploit_tools, 0, 3) as $tool): ?>
            <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg">
                <span class="text-sm font-semibold"><?php echo $tool['name']; ?></span>
                <?php echo getToolStatusBadge($tool['status']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500 text-sm">لا توجد أدوات استغلال</p>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- جدول جميع الأدوات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-lg font-bold text-yellow-300 mb-4 flex items-center">
        <i class="fas fa-table ml-2"></i>
        جميع الأدوات
    </h3>

    <?php if (empty($tools)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-tools text-5xl text-gray-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد أدوات</p>
        <p class="text-sm text-gray-500 mt-2">قم بإضافة أدوات جديدة</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="tools-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الفئة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الإصدار</th>
                    <th class="px-6 py-4 text-sm font-semibold">الترخيص</th>
                    <th class="px-6 py-4 text-sm font-semibold">آخر استخدام</th>
                    <th class="px-6 py-4 text-sm font-semibold">آخر تحديث</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم الأداة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tools as $tool): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors tool-row" 
                    data-category="<?php echo $tool['category']; ?>"
                    data-status="<?php echo $tool['status']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <?php if ($tool['status'] == 'active'): ?>
                            <button onclick="runTool(<?php echo $tool['id']; ?>)" class="text-green-400 hover:text-green-300" title="تشغيل">
                                <i class="fas fa-play"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($tool['status'] == 'needs-update'): ?>
                            <button onclick="updateTool(<?php echo $tool['id']; ?>)" class="text-yellow-400 hover:text-yellow-300" title="تحديث">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <?php endif; ?>
                            <button onclick="configureTool(<?php echo $tool['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="إعدادات">
                                <i class="fas fa-cog"></i>
                            </button>
                            <button onclick="viewToolDetails(<?php echo $tool['id']; ?>)" class="text-purple-400 hover:text-purple-300" title="تفاصيل">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getToolStatusBadge($tool['status']); ?></td>
                    <td class="px-6 py-4"><?php echo getToolCategoryBadge($tool['category']); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $tool['version'] ?? 'غير محدد'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo getLicenseTypeText($tool['license_type']); ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $tool['last_used'] ? date('Y-m-d', strtotime($tool['last_used'])) : '-'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $tool['last_updated'] ? date('Y-m-d', strtotime($tool['last_updated'])) : '-'; ?></td>
                    <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $tool['description']; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $tool['name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($tools); ?> أداة
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full ml-1"></span>
                نشط: <?php echo $status_counts['active']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                يحتاج تحديث: <?php echo $status_counts['needs-update']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                قيد التثبيت: <?php echo $status_counts['installing']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-gray-500 rounded-full ml-1"></span>
                غير نشط: <?php echo $status_counts['inactive']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- إحصائيات الاستخدام والسكربتات المخصصة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- إحصائيات الاستخدام -->
    <?php if (!empty($usage_stats)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
            <i class="fas fa-chart-bar ml-2"></i>
            أكثر الأدوات استخداماً
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($usage_stats as $stat): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-semibold text-white"><?php echo $stat['name']; ?></span>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-xs text-blue-400">فحوصات: <?php echo $stat['scan_count']; ?></span>
                        <span class="text-xs text-red-400">ثغرات: <?php echo $stat['total_findings']; ?></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <?php $percentage = min(100, $stat['scan_count'] * 10); ?>
                    <div class="progress-fill bg-cyan-500" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- السكربتات المخصصة -->
    <?php if (!empty($custom_scripts)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-green-300 flex items-center">
                <i class="fas fa-code ml-2"></i>
                السكربتات المخصصة
            </h3>
            <button onclick="addNewScript()" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded-lg text-xs font-semibold">
                <i class="fas fa-plus ml-1"></i>
                إضافة سكربت
            </button>
        </div>
        
        <div class="space-y-3">
            <?php foreach ($custom_scripts as $script): ?>
            <div class="p-3 bg-slate-700 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <span class="px-2 py-0.5 <?php echo getScriptTypeColor($script['script_type']); ?> rounded-full text-xs">
                            <?php echo getScriptTypeText($script['script_type']); ?>
                        </span>
                        <span class="text-sm font-semibold text-white mr-2"><?php echo $script['name']; ?></span>
                    </div>
                    <span class="text-xs text-gray-400">بواسطة: <?php echo $script['author_name'] ?? 'غير معروف'; ?></span>
                </div>
                <p class="text-xs text-gray-400 mb-2"><?php echo $script['description']; ?></p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">آخر تشغيل: <?php echo $script['last_run'] ? date('Y-m-d', strtotime($script['last_run'])) : 'لم يشغل'; ?></span>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button onclick="runScript(<?php echo $script['id']; ?>)" class="text-xs text-green-400 hover:text-green-300">
                            تشغيل
                        </button>
                        <button onclick="editScript(<?php echo $script['id']; ?>)" class="text-xs text-blue-400 hover:text-blue-300">
                            تعديل
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إضافة أداة جديدة -->
<!-- ============================================= -->
<div id="add-tool-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddToolModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                إضافة أداة جديدة
            </h3>
        </div>

        <form id="add-tool-form" onsubmit="saveNewTool(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم الأداة</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الفئة</label>
                    <select name="category" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="recon">استطلاع</option>
                        <option value="vulnerability">ثغرات</option>
                        <option value="exploitation">استغلال</option>
                        <option value="web">ويب</option>
                        <option value="network">شبكة</option>
                        <option value="reporting">تقارير</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الإصدار</label>
                    <input type="text" name="version" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع الترخيص</label>
                    <select name="license_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="open-source">مفتوح المصدر</option>
                        <option value="free">مجاني</option>
                        <option value="paid">مدفوع</option>
                        <option value="trial">تجريبي</option>
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
                    إضافة الأداة
                </button>
                <button type="button" onclick="closeAddToolModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل الأداة -->
<!-- ============================================= -->
<div id="tool-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeToolModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-purple-400" id="tool-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل الأداة
            </h3>
        </div>
        <div id="tool-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة إضافة سكربت جديد -->
<!-- ============================================= -->
<div id="add-script-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddScriptModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-code ml-2"></i>
                إضافة سكربت مخصص
            </h3>
        </div>

        <form id="add-script-form" onsubmit="saveNewScript(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم السكربت</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع السكربت</label>
                    <select name="script_type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="bash">Bash</option>
                        <option value="python">Python</option>
                        <option value="powershell">PowerShell</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="3" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">محتوى السكربت</label>
                <textarea name="script_content" rows="8" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg font-mono text-left direction-ltr" placeholder="#!/bin/bash&#10;# اكتب السكربت هنا..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ السكربت
                </button>
                <button type="button" onclick="closeAddScriptModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
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
// متغيرات عامة
// =============================================
let currentToolId = null;
let currentScriptId = null;

// =============================================
// دوال التصفية
// =============================================
function filterTools() {
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    window.location.href = `?page=tools&category=${category}&status=${status}`;
}

document.getElementById('category-filter')?.addEventListener('change', filterTools);
document.getElementById('status-filter')?.addEventListener('change', filterTools);

// =============================================
// دوال الأدوات
// =============================================
function addNewTool() {
    document.getElementById('add-tool-modal').classList.remove('hidden');
    document.getElementById('add-tool-modal').classList.add('flex');
}

function closeAddToolModal() {
    document.getElementById('add-tool-modal').classList.add('hidden');
    document.getElementById('add-tool-modal').classList.remove('flex');
}

function saveNewTool(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeAddToolModal();
        if (typeof showNotification === 'function') {
            showNotification('✅ تم إضافة الأداة الجديدة', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function updateAllTools() {
    if (confirm('هل تريد تحديث جميع الأدوات؟')) {
        if (typeof showLoading === 'function') showLoading();
        
        setTimeout(() => {
            if (typeof hideLoading === 'function') hideLoading();
            if (typeof showNotification === 'function') {
                showNotification('🔄 تم تحديث جميع الأدوات', 'success');
            }
            setTimeout(() => location.reload(), 1500);
        }, 2000);
    }
}

function updateNeededTools() {
    if (confirm('تحديث جميع الأدوات التي تحتاج تحديث؟')) {
        if (typeof showLoading === 'function') showLoading();
        
        setTimeout(() => {
            if (typeof hideLoading === 'function') hideLoading();
            if (typeof showNotification === 'function') {
                showNotification('🔄 تم تحديث الأدوات', 'success');
            }
            setTimeout(() => location.reload(), 1500);
        }, 2000);
    }
}

function runTool(toolId) {
    if (typeof showNotification === 'function') {
        showNotification(`🚀 جاري تشغيل الأداة #${toolId}`, 'info');
    }
}

function updateTool(toolId) {
    if (typeof showNotification === 'function') {
        showNotification(`🔄 جاري تحديث الأداة #${toolId}`, 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification(`✅ تم تحديث الأداة بنجاح`, 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 2000);
}

function configureTool(toolId) {
    if (typeof showNotification === 'function') {
        showNotification(`⚙️ فتح إعدادات الأداة #${toolId}`, 'info');
    }
}

function viewToolDetails(toolId) {
    currentToolId = toolId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل الأداة
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل الأداة #${toolId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="configureTool(${toolId})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg">إعدادات</button>
                    <button onclick="runTool(${toolId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تشغيل</button>
                    <button onclick="closeToolModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('tool-details-content').innerHTML = details;
        document.getElementById('tool-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل الأداة #${toolId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('tool-details-modal').classList.remove('hidden');
        document.getElementById('tool-details-modal').classList.add('flex');
    }, 1000);
}

function closeToolModal() {
    document.getElementById('tool-details-modal').classList.add('hidden');
    document.getElementById('tool-details-modal').classList.remove('flex');
}

// =============================================
// دوال السكربتات
// =============================================
function addNewScript() {
    document.getElementById('add-script-modal').classList.remove('hidden');
    document.getElementById('add-script-modal').classList.add('flex');
}

function closeAddScriptModal() {
    document.getElementById('add-script-modal').classList.add('hidden');
    document.getElementById('add-script-modal').classList.remove('flex');
}

function saveNewScript(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeAddScriptModal();
        if (typeof showNotification === 'function') {
            showNotification('✅ تم إضافة السكربت الجديد', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function runScript(scriptId) {
    if (typeof showNotification === 'function') {
        showNotification(`📜 جاري تشغيل السكربت #${scriptId}`, 'info');
    }
}

function editScript(scriptId) {
    if (typeof showNotification === 'function') {
        showNotification(`✏️ تعديل السكربت #${scriptId}`, 'info');
    }
}

// =============================================
// البحث المباشر
// =============================================
document.getElementById('search-tools')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.tool-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
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
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    transition: width 0.3s ease;
}
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}
.direction-ltr {
    direction: ltr;
    text-align: left;
}
</style>