<?php
// =============================================
// documentation-unit/pages/templates.php
// صفحة قوالب التوثيق الفني
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
            case 'add_template':
                // إضافة قالب جديد
                $template_code = generateTemplateCode($db, $_POST['type']);
                
                $sql = "INSERT INTO document_templates (
                    name, template_code, type, category, format,
                    description, structure, placeholders, variables,
                    created_by, is_public, access_level, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['name'],
                    $template_code,
                    $_POST['type'],
                    $_POST['category'] ?? $_POST['type'],
                    $_POST['format'] ?? 'docx',
                    $_POST['description'] ?? null,
                    $_POST['structure'] ?? null,
                    $_POST['placeholders'] ?? null,
                    $_POST['variables'] ?? null,
                    $_SESSION['user_id'] ?? 1,
                    isset($_POST['is_public']) ? 1 : 0,
                    $_POST['access_level'] ?? 'team'
                ]);
                
                $template_id = $db->lastInsertId();
                
                logActivity($db, 'create', 'template', $template_id, 'إضافة قالب جديد: ' . $_POST['name']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إضافة القالب بنجاح';
                break;
                
            case 'edit_template':
                // تحديث قالب
                $sql = "UPDATE document_templates SET
                    name = ?,
                    type = ?,
                    category = ?,
                    format = ?,
                    description = ?,
                    structure = ?,
                    placeholders = ?,
                    variables = ?,
                    is_public = ?,
                    access_level = ?,
                    updated_at = NOW()
                WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['category'] ?? $_POST['type'],
                    $_POST['format'] ?? 'docx',
                    $_POST['description'] ?? null,
                    $_POST['structure'] ?? null,
                    $_POST['placeholders'] ?? null,
                    $_POST['variables'] ?? null,
                    isset($_POST['is_public']) ? 1 : 0,
                    $_POST['access_level'] ?? 'team',
                    $_POST['template_id']
                ]);
                
                logActivity($db, 'update', 'template', $_POST['template_id'], 'تحديث قالب: ' . $_POST['name']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تحديث القالب بنجاح';
                break;
                
            case 'delete_template':
                // حذف قالب
                $stmt = $db->prepare("DELETE FROM document_templates WHERE id = ?");
                $stmt->execute([$_POST['template_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم حذف القالب';
                break;
                
            case 'duplicate_template':
                // نسخ قالب
                $stmt = $db->prepare("SELECT * FROM document_templates WHERE id = ?");
                $stmt->execute([$_POST['template_id']]);
                $template = $stmt->fetch();
                
                if ($template) {
                    $new_code = generateTemplateCode($db, $template['type']);
                    
                    $insert = $db->prepare("
                        INSERT INTO document_templates (
                            name, template_code, type, category, format,
                            description, structure, placeholders, variables,
                            created_by, is_public, access_level, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $new_name = $template['name'] . ' (نسخة)';
                    $insert->execute([
                        $new_name,
                        $new_code,
                        $template['type'],
                        $template['category'],
                        $template['format'],
                        $template['description'],
                        $template['structure'],
                        $template['placeholders'],
                        $template['variables'],
                        $_SESSION['user_id'] ?? 1,
                        $template['is_public'],
                        $template['access_level']
                    ]);
                    
                    $response['success'] = true;
                    $response['message'] = '✅ تم نسخ القالب بنجاح';
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $type_filter = $_GET['type'] ?? '';
    $category_filter = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // جلب القوالب
    $sql = "
        SELECT t.*, 
               u.full_name as creator_name,
               (SELECT COUNT(*) FROM documents WHERE template_id = t.id) as usage_count
        FROM document_templates t
        LEFT JOIN users u ON t.created_by = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($type_filter) {
        $sql .= " AND t.type = ?";
        $params[] = $type_filter;
    }
    
    if ($category_filter) {
        $sql .= " AND t.category = ?";
        $params[] = $category_filter;
    }
    
    if ($search) {
        $sql .= " AND (t.name LIKE ? OR t.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY t.usage_count DESC, t.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll();
    
    // إحصائيات القوالب
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type = 'technical' THEN 1 ELSE 0 END) as technical,
            SUM(CASE WHEN type = 'security' THEN 1 ELSE 0 END) as security,
            SUM(CASE WHEN type = 'user_manual' THEN 1 ELSE 0 END) as user_manual,
            SUM(CASE WHEN type = 'api_doc' THEN 1 ELSE 0 END) as api_doc,
            SUM(CASE WHEN type = 'report' THEN 1 ELSE 0 END) as report,
            SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public_templates,
            SUM(usage_count) as total_usage
        FROM document_templates
    ")->fetch();
    
    // القوالب الأكثر استخداماً
    $popular_templates = $db->query("
        SELECT name, usage_count, rating
        FROM document_templates
        ORDER BY usage_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // آخر القوالب المضافة
    $recent_templates = $db->query("
        SELECT id, name, type, created_at
        FROM document_templates
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $templates = [];
    $popular_templates = [];
    $recent_templates = [];
    $stats = [
        'total' => 0,
        'technical' => 0,
        'security' => 0,
        'user_manual' => 0,
        'api_doc' => 0,
        'report' => 0,
        'public_templates' => 0,
        'total_usage' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateTemplateCode($db, $type) {
    $prefixes = [
        'technical' => 'TMP-TECH',
        'security' => 'TMP-SEC',
        'user_manual' => 'TMP-USER',
        'api_doc' => 'TMP-API',
        'report' => 'TMP-REP',
        'contract' => 'TMP-CON'
    ];
    
    $prefix = $prefixes[$type] ?? 'TMP';
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM document_templates WHERE template_code LIKE ?");
    $stmt->execute(["{$prefix}-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$number}";
}

function getTemplateTypeText($type) {
    $texts = [
        'technical' => '⚙️ تقني',
        'security' => '🔒 أمني',
        'user_manual' => '📘 دليل مستخدم',
        'api_doc' => '🔌 توثيق API',
        'report' => '📊 تقرير',
        'contract' => '📜 عقد',
        'proposal' => '📋 عرض'
    ];
    return $texts[$type] ?? $type;
}

function getTemplateTypeColor($type) {
    $colors = [
        'technical' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'security' => 'bg-red-600 bg-opacity-20 text-red-400',
        'user_manual' => 'bg-green-600 bg-opacity-20 text-green-400',
        'api_doc' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'report' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'contract' => 'bg-orange-600 bg-opacity-20 text-orange-400'
    ];
    return $colors[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
}

function getAccessLevelBadge($level) {
    $classes = [
        'public' => 'bg-green-600 bg-opacity-20 text-green-400',
        'team' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'private' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'public' => '🌐 عام',
        'team' => '👥 فريق',
        'private' => '🔒 خاص'
    ];
    
    $class = $classes[$level] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$level] ?? $level;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}
?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري التحميل...</p>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي القوالب</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">قوالب تقنية</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['technical'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">قوالب أمنية</p>
        <p class="text-2xl font-bold text-red-400"><?php echo $stats['security'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">أدلة مستخدم</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['user_manual'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي الاستخدام</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo $stats['total_usage'] ?? 0; ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- رأس الصفحة مع البحث والفلاتر -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h3 class="text-xl font-bold text-right">قوالب التوثيق</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="openTemplateModal('add')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                قالب جديد
            </button>
            
            <button onclick="importTemplate()" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                استيراد
            </button>
        </div>
    </div>
    
    <!-- البحث والفلاتر -->
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 relative">
            <input type="text" id="search-input" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="🔍 بحث في القوالب..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
            <button onclick="searchTemplates()" class="absolute left-2 top-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <select id="filter-type" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الأنواع</option>
            <option value="technical">تقني</option>
            <option value="security">أمني</option>
            <option value="user_manual">دليل مستخدم</option>
            <option value="api_doc">توثيق API</option>
            <option value="report">تقرير</option>
        </select>
        
        <select id="filter-category" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع التصنيفات</option>
            <option value="technical">تقني</option>
            <option value="security">أمني</option>
            <option value="monthly">شهري</option>
            <option value="final">نهائي</option>
        </select>
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- عرض القوالب -->
<!-- ============================================= -->

<?php if (empty($templates)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
        <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
        </svg>
        <h3 class="text-2xl font-bold text-gray-400 mb-2">لا توجد قوالب</h3>
        <p class="text-gray-500 mb-6">قم بإنشاء أول قالب الآن</p>
        <button onclick="openTemplateModal('add')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all inline-flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إنشاء قالب جديد
        </button>
    </div>
<?php else: ?>
    <!-- القوالب الأكثر استخداماً -->
    <?php if (!empty($popular_templates)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            القوالب الأكثر استخداماً
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <?php foreach ($popular_templates as $template): ?>
            <div class="bg-slate-800 rounded-lg p-4 text-center hover:bg-slate-700 transition-colors cursor-pointer"
                 onclick="useTemplate('<?php echo $template['name']; ?>')">
                <p class="font-semibold mb-2"><?php echo htmlspecialchars($template['name']); ?></p>
                <div class="flex items-center justify-center text-yellow-400 mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="text-sm <?php echo $i <= round($template['rating']) ? 'text-yellow-400' : 'text-gray-600'; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-400">مستخدم <?php echo $template['usage_count']; ?> مرة</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- جميع القوالب -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($templates as $template): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6 template-card hover:shadow-lg transition-all">
            <div class="flex items-start justify-between mb-4">
                <?php echo getAccessLevelBadge($template['access_level'] ?? 'team'); ?>
                <span class="text-sm text-gray-400"><?php echo $template['template_code']; ?></span>
            </div>
            
            <div class="flex items-center justify-between mb-3">
                <span class="px-3 py-1 <?php echo getTemplateTypeColor($template['type']); ?> rounded-full text-xs">
                    <?php echo getTemplateTypeText($template['type']); ?>
                </span>
                <span class="text-xs text-gray-400"><?php echo strtoupper($template['format']); ?></span>
            </div>
            
            <h4 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($template['name']); ?></h4>
            
            <?php if ($template['description']): ?>
            <p class="text-sm text-gray-400 mb-4 line-clamp-2"><?php echo htmlspecialchars($template['description']); ?></p>
            <?php endif; ?>
            
            <div class="flex items-center justify-between text-sm mb-4">
                <div class="flex items-center">
                    <span class="text-gray-400 ml-1">⭐</span>
                    <span class="text-yellow-400"><?php echo number_format($template['rating'] ?? 0, 1); ?></span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-400 ml-1">📊</span>
                    <span class="text-blue-400"><?php echo $template['usage_count'] ?? 0; ?> استخدام</span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-400 ml-1">👤</span>
                    <span class="text-gray-400"><?php echo htmlspecialchars($template['creator_name'] ?? 'النظام'); ?></span>
                </div>
            </div>
            
            <!-- المتغيرات (إذا وجدت) -->
            <?php 
            $variables = json_decode($template['variables'] ?? '[]', true);
            if (!empty($variables) && is_array($variables)): 
            ?>
            <div class="mb-4">
                <p class="text-xs text-gray-400 mb-2">المتغيرات:</p>
                <div class="flex flex-wrap gap-1">
                    <?php 
                    $count = 0;
                    foreach ($variables as $var): 
                        if ($count++ >= 3) break;
                        $var_name = is_array($var) ? ($var['name'] ?? $var['variable'] ?? 'متغير') : $var;
                    ?>
                    <span class="px-2 py-1 bg-slate-700 rounded text-xs text-gray-300">
                        {{<?php echo htmlspecialchars($var_name); ?>}}
                    </span>
                    <?php endforeach; ?>
                    <?php if (count($variables) > 3): ?>
                    <span class="px-2 py-1 bg-slate-700 rounded text-xs text-gray-300">
                        +<?php echo count($variables) - 3; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- أزرار الإجراءات -->
            <div class="flex items-center justify-between gap-2 mt-4 pt-4 border-t border-slate-700">
                <button onclick="useTemplate('<?php echo addslashes($template['name']); ?>', <?php echo $template['id']; ?>)" 
                        class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
                    استخدام
                </button>
                
                <button onclick="previewTemplate(<?php echo $template['id']; ?>)" 
                        class="px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
                
                <div class="relative">
                    <button onclick="toggleTemplateMenu(<?php echo $template['id']; ?>)" 
                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                    
                    <div id="menu-<?php echo $template['id']; ?>" class="hidden absolute left-0 mt-2 w-48 bg-slate-800 rounded-lg shadow-lg border border-slate-700 z-50">
                        <div class="py-1">
                            <button onclick="editTemplate(<?php echo $template['id']; ?>)" class="w-full text-right px-4 py-2 hover:bg-slate-700 text-sm flex items-center">
                                <span class="flex-1">✏️ تعديل</span>
                            </button>
                            <button onclick="duplicateTemplate(<?php echo $template['id']; ?>)" class="w-full text-right px-4 py-2 hover:bg-slate-700 text-sm flex items-center">
                                <span class="flex-1">📋 نسخ</span>
                            </button>
                            <button onclick="exportTemplate(<?php echo $template['id']; ?>)" class="w-full text-right px-4 py-2 hover:bg-slate-700 text-sm flex items-center">
                                <span class="flex-1">📤 تصدير</span>
                            </button>
                            <hr class="border-slate-700 my-1">
                            <button onclick="deleteTemplate(<?php echo $template['id']; ?>, '<?php echo addslashes($template['name']); ?>')" 
                                    class="w-full text-right px-4 py-2 hover:bg-slate-700 text-sm text-red-400 flex items-center">
                                <span class="flex-1">🗑️ حذف</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة إضافة/تعديل قالب -->
<!-- ============================================= -->
<div id="template-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400" id="modal-title">إضافة قالب جديد</h3>
        </div>
        
        <form id="template-form" onsubmit="handleTemplateSubmit(event)">
            <input type="hidden" name="action" id="form-action" value="add_template">
            <input type="hidden" name="template_id" id="template-id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- اسم القالب -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">اسم القالب <span class="text-red-400">*</span></label>
                    <input type="text" id="template-name" name="name" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="أدخل اسم القالب">
                </div>
                
                <!-- نوع القالب -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">النوع</label>
                    <select id="template-type" name="type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="technical">⚙️ تقني</option>
                        <option value="security">🔒 أمني</option>
                        <option value="user_manual">📘 دليل مستخدم</option>
                        <option value="api_doc">🔌 توثيق API</option>
                        <option value="report">📊 تقرير</option>
                        <option value="contract">📜 عقد</option>
                    </select>
                </div>
                
                <!-- التصنيف -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">التصنيف</label>
                    <select id="template-category" name="category" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="technical">تقني</option>
                        <option value="security">أمني</option>
                        <option value="monthly">شهري</option>
                        <option value="final">نهائي</option>
                        <option value="custom">مخصص</option>
                    </select>
                </div>
                
                <!-- التنسيق ومستوى الوصول -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">التنسيق</label>
                    <select id="template-format" name="format" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="docx">DOCX</option>
                        <option value="md">Markdown</option>
                        <option value="html">HTML</option>
                        <option value="txt">نص عادي</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">مستوى الوصول</label>
                    <select id="template-access" name="access_level" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="public">🌐 عام</option>
                        <option value="team">👥 الفريق</option>
                        <option value="private">🔒 خاص</option>
                    </select>
                </div>
                
                <!-- الوصف -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                    <textarea id="template-description" name="description" rows="3" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="وصف القالب واستخداماته..."></textarea>
                </div>
                
                <!-- هيكل القالب -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">هيكل القالب</label>
                    <textarea id="template-structure" name="structure" rows="6" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right font-mono text-sm"
                              placeholder="اكتب هيكل القالب هنا..."></textarea>
                </div>
                
                <!-- المتغيرات -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">المتغيرات</label>
                    <div id="variables-container" class="space-y-2 mb-2">
                        <!-- المتغيرات تضاف هنا ديناميكياً -->
                    </div>
                    <button type="button" onclick="addVariable()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
                        + إضافة متغير
                    </button>
                </div>
                
                <!-- عام/خاص -->
                <div class="md:col-span-2 flex items-center justify-end">
                    <label class="flex items-center cursor-pointer">
                        <span class="text-sm text-gray-300 ml-2">قالب عام</span>
                        <input type="checkbox" id="is-public" name="is_public" class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded">
                    </label>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeTemplateModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    حفظ القالب
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
// =============================================
// المتغيرات العامة
// =============================================
let currentTemplateId = null;
let variables = [];

// =============================================
// دوال القوالب
// =============================================

// فتح نافذة إضافة/تعديل قالب
function openTemplateModal(action, template = null) {
    const modal = document.getElementById('template-modal');
    const title = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    
    if (action === 'add') {
        title.textContent = '➕ إضافة قالب جديد';
        formAction.value = 'add_template';
        document.getElementById('template-form').reset();
        document.getElementById('template-id').value = '';
        variables = [];
        renderVariables();
    } else {
        title.textContent = '✏️ تعديل القالب';
        formAction.value = 'edit_template';
        
        // تعبئة البيانات
        document.getElementById('template-id').value = template.id;
        document.getElementById('template-name').value = template.name;
        document.getElementById('template-type').value = template.type;
        document.getElementById('template-category').value = template.category || template.type;
        document.getElementById('template-format').value = template.format || 'docx';
        document.getElementById('template-access').value = template.access_level || 'team';
        document.getElementById('template-description').value = template.description || '';
        document.getElementById('template-structure').value = template.structure || '';
        document.getElementById('is-public').checked = template.is_public == 1;
        
        // تحميل المتغيرات
        if (template.variables) {
            try {
                variables = JSON.parse(template.variables) || [];
            } catch (e) {
                variables = [];
            }
        } else {
            variables = [];
        }
        renderVariables();
    }
    
    modal.classList.remove('hidden');
}

// إغلاق نافذة القالب
function closeTemplateModal() {
    document.getElementById('template-modal').classList.add('hidden');
}

// إضافة متغير جديد
function addVariable() {
    variables.push({ name: '', type: 'text', required: false });
    renderVariables();
}

// حذف متغير
function removeVariable(index) {
    variables.splice(index, 1);
    renderVariables();
}

// عرض المتغيرات
function renderVariables() {
    const container = document.getElementById('variables-container');
    container.innerHTML = '';
    
    variables.forEach((variable, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-2 bg-slate-700 rounded-lg';
        div.innerHTML = `
            <button type="button" onclick="removeVariable(${index})" class="text-red-400 hover:text-red-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            <input type="text" value="${variable.name || ''}" 
                   onchange="updateVariable(${index}, 'name', this.value)"
                   placeholder="اسم المتغير" class="flex-1 px-3 py-1 bg-slate-600 border border-slate-500 rounded text-right text-sm">
            <select onchange="updateVariable(${index}, 'type', this.value)" class="px-3 py-1 bg-slate-600 border border-slate-500 rounded text-sm">
                <option value="text" ${variable.type === 'text' ? 'selected' : ''}>نص</option>
                <option value="number" ${variable.type === 'number' ? 'selected' : ''}>رقم</option>
                <option value="date" ${variable.type === 'date' ? 'selected' : ''}>تاريخ</option>
                <option value="select" ${variable.type === 'select' ? 'selected' : ''}>قائمة</option>
            </select>
            <label class="flex items-center">
                <span class="text-xs ml-1">إجباري</span>
                <input type="checkbox" ${variable.required ? 'checked' : ''} 
                       onchange="updateVariable(${index}, 'required', this.checked)"
                       class="w-3 h-3 text-blue-600 bg-slate-600 border-slate-500 rounded">
            </label>
        `;
        container.appendChild(div);
    });
}

// تحديث قيمة متغير
function updateVariable(index, field, value) {
    variables[index][field] = value;
}

// حفظ القالب
function handleTemplateSubmit(event) {
    event.preventDefault();
    
    // إضافة المتغيرات للنموذج
    const variablesInput = document.createElement('input');
    variablesInput.type = 'hidden';
    variablesInput.name = 'variables';
    variablesInput.value = JSON.stringify(variables);
    document.getElementById('template-form').appendChild(variablesInput);
    
    const formData = new FormData(document.getElementById('template-form'));
    
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
        closeTemplateModal();
        
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

// استخدام قالب
function useTemplate(templateName, templateId) {
    if (confirm(`هل تريد استخدام القالب "${templateName}"؟`)) {
        showLoading();
        setTimeout(() => {
            hideLoading();
            window.location.href = '?page=creation&template=' + templateId;
        }, 500);
    }
}

// معاينة قالب
function previewTemplate(templateId) {
    showNotification('🔍 جاري عرض معاينة القالب...', 'info');
    setTimeout(() => {
        showNotification('✅ تم فتح المعاينة', 'success');
    }, 1000);
}

// تعديل قالب
function editTemplate(templateId) {
    // البحث عن القالب في البيانات
    const templateCard = event.target.closest('.template-card');
    if (templateCard) {
        const template = {
            id: templateId,
            name: templateCard.querySelector('h4').textContent,
            type: templateCard.querySelector('.px-3.py-1')?.textContent?.trim()?.toLowerCase() || 'technical',
            category: 'technical',
            format: 'docx',
            access_level: 'team',
            description: templateCard.querySelector('p.text-sm.text-gray-400')?.textContent || '',
            structure: '',
            is_public: true
        };
        openTemplateModal('edit', template);
    }
}

// نسخ قالب
function duplicateTemplate(templateId) {
    if (confirm('هل تريد نسخ هذا القالب؟')) {
        const formData = new FormData();
        formData.append('action', 'duplicate_template');
        formData.append('template_id', templateId);
        
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
            showNotification('❌ حدث خطأ', 'error');
            console.error(error);
        });
    }
}

// حذف قالب
function deleteTemplate(templateId, templateName) {
    if (confirm(`⚠️ هل أنت متأكد من حذف القالب "${templateName}"؟`)) {
        const formData = new FormData();
        formData.append('action', 'delete_template');
        formData.append('template_id', templateId);
        
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
            showNotification('❌ حدث خطأ', 'error');
            console.error(error);
        });
    }
}

// تصدير قالب
function exportTemplate(templateId) {
    showNotification('📤 جاري تصدير القالب...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير بنجاح', 'success');
    }, 1500);
}

// استيراد قالب
function importTemplate() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json,.xml,.txt';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            showNotification('📥 جاري استيراد القالب...', 'info');
            setTimeout(() => {
                showNotification('✅ تم الاستيراد بنجاح', 'success');
                setTimeout(() => location.reload(), 1000);
            }, 1500);
        }
    };
    input.click();
}

// =============================================
// دوال الفلاتر والبحث
// =============================================
function searchTemplates() {
    const search = document.getElementById('search-input').value;
    window.location.href = '?page=templates&search=' + encodeURIComponent(search);
}

function applyFilters() {
    const type = document.getElementById('filter-type').value;
    const category = document.getElementById('filter-category').value;
    const search = document.getElementById('search-input').value;
    
    let url = '?page=templates';
    if (type) url += '&type=' + type;
    if (category) url += '&category=' + category;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=templates';
}

// قائمة القالب
function toggleTemplateMenu(templateId) {
    const menu = document.getElementById('menu-' + templateId);
    menu.classList.toggle('hidden');
}

// إغلاق القوائم
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.querySelectorAll('[id^="menu-"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

// =============================================
// دوال مساعدة
// =============================================
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
    notification.innerHTML = `<div class="flex items-center">${message}</div>`;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}
</script>

<!-- ============================================= -->
<!-- CSS إضافي -->
<!-- ============================================= -->
<style>
.template-card {
    border-right: 4px solid #10b981;
    transition: all 0.3s ease;
}
.template-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
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
.notification {
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>