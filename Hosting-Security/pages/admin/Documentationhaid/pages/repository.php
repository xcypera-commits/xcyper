<?php
// =============================================
// documentation-unit/pages/repository.php
// صفحة مستودع التوثيق والملفات - النسخة المتكاملة
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
            case 'upload_file':
                // رفع ملف جديد
                $target_dir = __DIR__ . '/../../uploads/repository/' . date('Y/m/');
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file = $_FILES['file'];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . date('Ymd_His') . '.' . $extension;
                $filepath = $target_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $relative_path = '/uploads/repository/' . date('Y/m/') . $filename;
                    
                    $sql = "INSERT INTO repository_files (
                        file_name, file_path, file_type, file_size, mime_type,
                        folder_path, project_id, document_id, uploaded_by, version,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $file['name'],
                        $relative_path,
                        $extension,
                        $file['size'],
                        $file['type'],
                        $_POST['folder_path'] ?? '/',
                        $_POST['project_id'] ?: null,
                        $_POST['document_id'] ?: null,
                        $_SESSION['user_id'] ?? 1,
                        $_POST['version'] ?? '1.0'
                    ]);
                    
                    logActivity($db, 'upload', 'file', $db->lastInsertId(), 'رفع ملف: ' . $file['name']);
                    
                    $response['success'] = true;
                    $response['message'] = '✅ تم رفع الملف بنجاح';
                } else {
                    $response['message'] = '❌ فشل في رفع الملف';
                }
                break;
                
            case 'create_folder':
                // إنشاء مجلد جديد
                $folder_path = $_POST['folder_path'] . '/' . $_POST['folder_name'];
                
                $sql = "INSERT INTO repository_files (
                    file_name, file_path, file_type, file_size, mime_type,
                    folder_path, uploaded_by, created_at
                ) VALUES (?, ?, 'folder', 0, 'folder', ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['folder_name'],
                    $folder_path,
                    $_POST['parent_path'] ?? '/',
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إنشاء المجلد بنجاح';
                break;
                
            case 'delete_file':
                // حذف ملف
                $stmt = $db->prepare("SELECT file_path FROM repository_files WHERE id = ?");
                $stmt->execute([$_POST['file_id']]);
                $file = $stmt->fetch();
                
                if ($file) {
                    $full_path = __DIR__ . '/../..' . $file['file_path'];
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                    
                    $db->prepare("DELETE FROM repository_files WHERE id = ?")->execute([$_POST['file_id']]);
                    
                    $response['success'] = true;
                    $response['message'] = '✅ تم حذف الملف';
                }
                break;
                
            case 'rename_file':
                // إعادة تسمية ملف
                $sql = "UPDATE repository_files SET file_name = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['new_name'], $_POST['file_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إعادة التسمية';
                break;
                
            case 'move_file':
                // نقل ملف
                $sql = "UPDATE repository_files SET folder_path = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['new_path'], $_POST['file_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم نقل الملف';
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
    // المسار الحالي
    $current_path = $_GET['path'] ?? '/';
    $current_path = urldecode($current_path);
    
    // جلب محتويات المجلد الحالي
    $stmt = $db->prepare("
        SELECT * FROM repository_files 
        WHERE folder_path = ? 
        ORDER BY 
            CASE WHEN file_type = 'folder' THEN 0 ELSE 1 END,
            file_name ASC
    ");
    $stmt->execute([$current_path]);
    $files = $stmt->fetchAll();
    
    // جلب مسار التنقل (breadcrumb)
    $breadcrumbs = [];
    if ($current_path != '/') {
        $parts = explode('/', trim($current_path, '/'));
        $path = '';
        $breadcrumbs[] = ['name' => 'الرئيسية', 'path' => '/'];
        
        foreach ($parts as $part) {
            $path .= '/' . $part;
            $breadcrumbs[] = ['name' => $part, 'path' => $path];
        }
    } else {
        $breadcrumbs[] = ['name' => 'الرئيسية', 'path' => '/'];
    }
    
    // إحصائيات المستودع
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_files,
            SUM(CASE WHEN file_type != 'folder' THEN 1 ELSE 0 END) as actual_files,
            SUM(CASE WHEN file_type = 'folder' THEN 1 ELSE 0 END) as folders,
            COALESCE(SUM(file_size), 0) as total_size,
            COUNT(DISTINCT file_type) as file_types,
            SUM(download_count) as total_downloads
        FROM repository_files
        WHERE file_type != 'folder'
    ")->fetch();
    
    // أحدث الملفات
    $recent_files = $db->query("
        SELECT * FROM repository_files 
        WHERE file_type != 'folder'
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll();
    
    // أكثر الملفات تحميلاً
    $popular_files = $db->query("
        SELECT * FROM repository_files 
        WHERE file_type != 'folder'
        ORDER BY download_count DESC 
        LIMIT 5
    ")->fetchAll();
    
    // أنواع الملفات
    $file_types = $db->query("
        SELECT file_type, COUNT(*) as count 
        FROM repository_files 
        WHERE file_type != 'folder'
        GROUP BY file_type 
        ORDER BY count DESC
    ")->fetchAll();
    
    // جلب إحصائيات التصنيفات للمستندات
    $doc_stats = [
        'all' => $db->query("SELECT COUNT(*) FROM documents WHERE status != 'archived'")->fetchColumn() ?: 0,
        'technical' => $db->query("SELECT COUNT(*) FROM documents WHERE document_type = 'technical' AND status != 'archived'")->fetchColumn() ?: 0,
        'security' => $db->query("SELECT COUNT(*) FROM documents WHERE document_type = 'security' AND status != 'archived'")->fetchColumn() ?: 0,
        'operational' => $db->query("SELECT COUNT(*) FROM documents WHERE document_type IN ('deployment', 'operation_manual') AND status != 'archived'")->fetchColumn() ?: 0,
        'monthly' => $db->query("SELECT COUNT(*) FROM documents WHERE document_type = 'report' AND status != 'archived'")->fetchColumn() ?: 0,
        'archived' => $db->query("SELECT COUNT(*) FROM documents WHERE status = 'archived'")->fetchColumn() ?: 0,
        'approved' => $db->query("SELECT COUNT(*) FROM documents WHERE status = 'approved'")->fetchColumn() ?: 0,
    ];
    
    // جلب المستندات حسب التصنيف
    $category = $_GET['category'] ?? 'all';
    
    $sql = "SELECT d.*, p.project_name 
            FROM documents d
            LEFT JOIN documentation_projects p ON d.project_id = p.id
            WHERE 1=1";
    
    switch ($category) {
        case 'technical':
            $sql .= " AND d.document_type = 'technical' AND d.status != 'archived'";
            break;
        case 'security':
            $sql .= " AND d.document_type = 'security' AND d.status != 'archived'";
            break;
        case 'operational':
            $sql .= " AND d.document_type IN ('deployment', 'operation_manual') AND d.status != 'archived'";
            break;
        case 'monthly':
            $sql .= " AND d.document_type = 'report' AND d.status != 'archived'";
            break;
        case 'archived':
            $sql .= " AND d.status = 'archived'";
            break;
        default:
            $sql .= " AND d.status != 'archived'";
    }
    
    $sql .= " ORDER BY d.created_at DESC LIMIT 50";
    $documents = $db->query($sql)->fetchAll();
    
    // آخر المستندات المضافة
    $recent_documents = $db->query("
        SELECT d.*, p.project_name 
        FROM documents d
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        WHERE d.status != 'archived'
        ORDER BY d.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $files = [];
    $recent_files = [];
    $popular_files = [];
    $file_types = [];
    $documents = [];
    $recent_documents = [];
    $doc_stats = [];
    $stats = [
        'total_files' => 0,
        'actual_files' => 0,
        'folders' => 0,
        'total_size' => 0,
        'total_downloads' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function getFileIcon($type) {
    $icons = [
        'pdf' => '📄',
        'docx' => '📝',
        'doc' => '📝',
        'xlsx' => '📊',
        'xls' => '📊',
        'pptx' => '📽️',
        'ppt' => '📽️',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'txt' => '📃',
        'md' => '📑',
        'html' => '🌐',
        'css' => '🎨',
        'js' => '⚙️',
        'php' => '🐘',
        'sql' => '🗄️',
        'json' => '📦',
        'xml' => '📋',
        'zip' => '📦',
        'rar' => '📦',
        'folder' => '📁'
    ];
    
    return $icons[strtolower($type)] ?? '📄';
}


function getFileTypeColor($type) {
    $colors = [
        'pdf' => 'text-red-400',
        'docx' => 'text-blue-400',
        'xlsx' => 'text-green-400',
        'pptx' => 'text-orange-400',
        'jpg' => 'text-purple-400',
        'png' => 'text-purple-400',
        'txt' => 'text-gray-400',
        'md' => 'text-gray-400',
        'html' => 'text-orange-400',
        'php' => 'text-indigo-400',
        'js' => 'text-yellow-400',
        'zip' => 'text-yellow-400',
        'folder' => 'text-blue-400'
    ];
    
    return $colors[strtolower($type)] ?? 'text-gray-400';
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
<!-- رأس الصفحة الفاخر -->
<!-- ============================================= -->
<div class="bg-gradient-to-l from-indigo-900 via-blue-900 to-slate-900 rounded-2xl p-8 mb-8 cyber-border shadow-2xl">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg transform hover:scale-110 transition-transform">
                <span class="text-4xl">🗃️</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center">
                    إدارة مستودع التوثيق
                    <span class="mr-3 px-3 py-1 bg-blue-600 bg-opacity-30 rounded-full text-sm text-blue-200">v2.0</span>
                </h1>
                <p class="text-blue-200 mt-1 flex items-center">
                    <span class="ml-2">📋</span>
                    نظام التوثيق الفني الشامل للمشاريع
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="openCreateFolderModal()" class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 rounded-xl font-semibold transition-all flex items-center shadow-lg transform hover:scale-105">
                <span class="ml-2 text-xl"></span>
                مجلد جديد
            </button>
            <button onclick="openUploadModal()" class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 rounded-xl font-semibold transition-all flex items-center shadow-lg transform hover:scale-105">
                <span class="ml-2 text-xl">⬆</span>
                رفع مستند
            </button>
            <button onclick="exportArchive()" class="px-5 py-3 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 rounded-xl font-semibold transition-all flex items-center shadow-lg transform hover:scale-105">
                <span class="ml-2 text-xl">📦</span>
                تصدير الأرشيف
            </button>
        </div>
    </div>
    
    <!-- مسار التنقل (Breadcrumb) -->
    <div class="flex items-center space-x-2 space-x-reverse mt-4 pt-4 border-t border-slate-600">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="text-gray-600">/</span>
            <?php endif; ?>
            
            <?php if ($index == count($breadcrumbs) - 1): ?>
                <span class="text-blue-400 font-semibold"><?php echo htmlspecialchars($crumb['name']); ?></span>
            <?php else: ?>
                <a href="?page=repository&path=<?php echo urlencode($crumb['path']); ?>" class="text-gray-400 hover:text-blue-400 transition-colors">
                    <?php echo htmlspecialchars($crumb['name']); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- شريط البحث السريع -->
    <div class="mt-6 flex items-center bg-slate-800 bg-opacity-50 rounded-xl p-2">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="🔍 بحث في المستودع..." 
                   class="w-full px-6 py-3 bg-slate-700 border border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 text-right pr-12">
            <button class="absolute left-3 top-3 text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        <select class="mr-3 px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-sm focus:outline-none focus:border-blue-500">
            <option>جميع الأنواع</option>
            <option>PDF</option>
            <option>DOCX</option>
            <option>MD</option>
        </select>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي الملفات</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['actual_files'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">المجلدات</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['folders'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">حجم المستودع</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo formatFileSize($stats['total_size'] ?? 0); ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي التحميلات</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo number_format($stats['total_downloads'] ?? 0); ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">أنواع الملفات</p>
        <p class="text-2xl font-bold text-orange-400"><?php echo count($file_types); ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- التصنيفات الرهيبة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-8">
    
    <a href="?page=repository&category=all" 
       class="cyber-border bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 hover:from-slate-700 hover:to-slate-800 transition-all transform hover:scale-105 <?php echo ($_GET['category'] ?? 'all') == 'all' ? 'ring-2 ring-blue-500 shadow-lg' : ''; ?>">
        <div class="flex items-center justify-between">
            <span class="text-4xl">🔧</span>
            <span class="text-3xl font-bold text-blue-400"><?php echo $doc_stats['all']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mt-3 font-semibold">جميع المستندات</p>
        <div class="mt-2 text-xs text-gray-500">كل مستندات النظام</div>
    </a>
    
    <a href="?page=repository&category=technical" 
       class="cyber-border bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 hover:from-slate-700 hover:to-slate-800 transition-all transform hover:scale-105 <?php echo ($_GET['category'] ?? '') == 'technical' ? 'ring-2 ring-blue-500 shadow-lg' : ''; ?>">
        <div class="flex items-center justify-between">
            <span class="text-4xl">📁</span>
            <span class="text-3xl font-bold text-blue-400"><?php echo $doc_stats['technical']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mt-3 font-semibold">التوثيق الفني</p>
        <div class="mt-2 text-xs text-gray-500">مستندات تقنية وهيكلية</div>
    </a>
    
    <a href="?page=repository&category=security" 
       class="cyber-border bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 hover:from-slate-700 hover:to-slate-800 transition-all transform hover:scale-105 <?php echo ($_GET['category'] ?? '') == 'security' ? 'ring-2 ring-blue-500 shadow-lg' : ''; ?>">
        <div class="flex items-center justify-between">
            <span class="text-4xl">🛡️</span>
            <span class="text-3xl font-bold text-blue-400"><?php echo $doc_stats['security']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mt-3 font-semibold">تقارير الأمان</p>
        <div class="mt-2 text-xs text-gray-500">تقارير أمنية وثغرات</div>
    </a>
    
    <a href="?page=repository&category=operational" 
       class="cyber-border bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 hover:from-slate-700 hover:to-slate-800 transition-all transform hover:scale-105 <?php echo ($_GET['category'] ?? '') == 'operational' ? 'ring-2 ring-blue-500 shadow-lg' : ''; ?>">
        <div class="flex items-center justify-between">
            <span class="text-4xl">💾</span>
            <span class="text-3xl font-bold text-blue-400"><?php echo $doc_stats['operational']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mt-3 font-semibold">التوثيق التشغيلي</p>
        <div class="mt-2 text-xs text-gray-500">أدلة التثبيت والتشغيل</div>
    </a>
    
    <a href="?page=repository&category=monthly" 
       class="cyber-border bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 hover:from-slate-700 hover:to-slate-800 transition-all transform hover:scale-105 <?php echo ($_GET['category'] ?? '') == 'monthly' ? 'ring-2 ring-blue-500 shadow-lg' : ''; ?>">
        <div class="flex items-center justify-between">
            <span class="text-4xl">📊</span>
            <span class="text-3xl font-bold text-blue-400"><?php echo $doc_stats['monthly']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mt-3 font-semibold">التقارير الشهرية</p>
        <div class="mt-2 text-xs text-gray-500">تقارير دورية</div>
    </a>
    
    <a href="?page=repository&category=archived" 
       class="cyber-border bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 hover:from-slate-700 hover:to-slate-800 transition-all transform hover:scale-105 <?php echo ($_GET['category'] ?? '') == 'archived' ? 'ring-2 ring-blue-500 shadow-lg' : ''; ?>">
        <div class="flex items-center justify-between">
            <span class="text-4xl">🗄️</span>
            <span class="text-3xl font-bold text-blue-400"><?php echo $doc_stats['archived']; ?></span>
        </div>
        <p class="text-sm text-gray-400 mt-3 font-semibold">الأرشيف</p>
        <div class="mt-2 text-xs text-gray-500">مستندات مؤرشفة</div>
    </a>
    
    <div class="cyber-border bg-gradient-to-br from-purple-900 to-indigo-900 rounded-xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-4xl">✓</span>
            <span class="text-3xl font-bold text-green-400"><?php echo $doc_stats['approved']; ?></span>
        </div>
        <p class="text-sm text-gray-300 mt-3 font-semibold">المعتمد</p>
        <div class="mt-2 text-xs text-gray-400">مستندات معتمدة</div>
    </div>
</div>

<!-- ============================================= -->
<!-- أزرار التبديل بين العرضين -->
<!-- ============================================= -->
<div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-bold flex items-center">
        <span class="text-2xl ml-2">
            <?php
            $icons = [
                'all' => '🔧',
                'technical' => '📁',
                'security' => '🛡️',
                'operational' => '💾',
                'monthly' => '📊',
                'archived' => '🗄️'
            ];
            echo $icons[$_GET['category'] ?? 'all'] ?? '📄';
            ?>
        </span>
        <?php
        $titles = [
            'all' => 'جميع المستندات',
            'technical' => 'التوثيق الفني',
            'security' => 'تقارير الأمان',
            'operational' => 'التوثيق التشغيلي',
            'monthly' => 'التقارير الشهرية',
            'archived' => 'الأرشيف'
        ];
        echo $titles[$_GET['category'] ?? 'all'] ?? 'المستودع';
        ?>
    </h2>
    
    <div class="flex items-center space-x-2 space-x-reverse">
        <select class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-xl text-sm focus:outline-none focus:border-blue-500">
            <option>📅 أحدث أولاً</option>
            <option>📅 أقدم أولاً</option>
            <option>📊 حسب الحجم</option>
            <option>📝 حسب الاسم</option>
        </select>
        
        <div class="flex items-center bg-slate-700 rounded-xl p-1">
            <button id="gridViewBtn" onclick="showGridView()" class="px-3 py-1 rounded-lg text-sm flex items-center transition-all bg-blue-600 text-white">
                <span class="ml-1">📊</span>
                شبكي
            </button>
            <button id="tableViewBtn" onclick="showTableView()" class="px-3 py-1 rounded-lg text-sm flex items-center transition-all hover:text-blue-400">
                <span class="ml-1">📋</span>
                جدول
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- العرض الشبكي (التصنيفات والبطاقات) -->
<!-- ============================================= -->
<div id="gridView">
    <?php if (empty($documents)): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
            <span class="text-7xl mb-4 block">📂</span>
            <h3 class="text-2xl font-bold text-gray-400 mb-2">لا توجد مستندات</h3>
            <p class="text-gray-500 mb-6">هذا التصنيف لا يحتوي على مستندات بعد</p>
            <button onclick="openUploadModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-xl font-semibold transition-all inline-flex items-center">
                <span class="ml-2">⬆️</span>
                رفع أول مستند
            </button>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php foreach ($documents as $doc): ?>
            <div class="cyber-border bg-slate-800 rounded-xl p-5 hover:shadow-xl transition-all transform hover:scale-105 group">
                <div class="flex items-start justify-between mb-3">
                    <?php
                    $icons = [
                        'security' => '🛡️',
                        'technical' => '⚙️',
                        'api' => '🔌',
                        'user_guide' => '📘',
                        'report' => '📊',
                        'requirements' => '📋'
                    ];
                    $icon = $icons[$doc['document_type']] ?? '📄';
                    ?>
                    <span class="text-4xl"><?php echo $icon; ?></span>
                    <span class="px-3 py-1 bg-<?php echo $doc['status'] == 'approved' ? 'green' : 'blue'; ?>-600 bg-opacity-20 text-<?php echo $doc['status'] == 'approved' ? 'green' : 'blue'; ?>-400 rounded-full text-xs">
                        <?php echo $doc['status'] == 'approved' ? '✓ معتمد' : '⏳ مسودة'; ?>
                    </span>
                </div>
                
                <h4 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($doc['title']); ?></h4>
                <p class="text-sm text-gray-400 mb-3"><?php echo htmlspecialchars($doc['project_name'] ?? 'عام'); ?></p>
                
                <div class="flex items-center justify-between text-sm text-gray-400 mb-3">
                    <span>📅 <?php echo date('Y-m-d', strtotime($doc['created_date'])); ?></span>
                    <span>📄 <?php echo strtoupper($doc['format'] ?? 'PDF'); ?></span>
                    <span>💾 <?php echo formatFileSize($doc['file_size']); ?></span>
                </div>
                
                <div class="flex items-center justify-between pt-3 border-t border-slate-700">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button onclick="downloadFile('<?php echo $doc['file_path']; ?>')" class="p-2 hover:bg-slate-700 rounded-lg transition-colors" title="تحميل">
                            ⬇️
                        </button>
                        <button onclick="previewFile('<?php echo $doc['file_path']; ?>')" class="p-2 hover:bg-slate-700 rounded-lg transition-colors" title="معاينة">
                            👁️
                        </button>
                        <button onclick="editDocument(<?php echo $doc['id']; ?>)" class="p-2 hover:bg-slate-700 rounded-lg transition-colors" title="تعديل">
                            ✏️
                        </button>
                    </div>
                    <span class="text-xs text-gray-500">الإصدار <?php echo $doc['version']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- آخر المستندات المضافة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <span class="text-2xl ml-2">🕒</span>
            آخر المستندات المضافة
            <span class="mr-3 px-3 py-1 bg-blue-600 bg-opacity-20 text-blue-400 rounded-full text-xs">محدث الآن</span>
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-700 text-right">
                        <th class="px-4 py-3 text-sm font-semibold text-gray-400">اسم الملف</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-400">النوع</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-400">التاريخ</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-400">الحجم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_documents as $doc): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <span class="text-xl ml-3">📄</span>
                                <span class="text-sm font-medium"><?php echo htmlspecialchars($doc['title']); ?>.<?php echo strtolower($doc['format'] ?? 'pdf'); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 bg-blue-600 bg-opacity-20 text-blue-400 rounded-full text-xs font-bold">
                                <?php echo strtoupper($doc['format'] ?? 'PDF'); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo date('Y-m-d', strtotime($doc['created_date'])); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo formatFileSize($doc['file_size']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- الأمثلة الثابتة -->
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <span class="text-xl ml-3">📄</span>
                                <span class="text-sm font-medium">تقرير_الأمان_الشهري.pdf</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 bg-red-600 bg-opacity-20 text-red-400 rounded-full text-xs font-bold">PDF</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400">2024-01-15</td>
                        <td class="px-4 py-3 text-sm text-gray-400">2.4 MB</td>
                    </tr>
                    
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <span class="text-xl ml-3">📄</span>
                                <span class="text-sm font-medium">توثيق_الخوادم.docx</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 bg-blue-600 bg-opacity-20 text-blue-400 rounded-full text-xs font-bold">DOCX</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400">2024-01-14</td>
                        <td class="px-4 py-3 text-sm text-gray-400">1.8 MB</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- العرض الجدولي (القديم) -->
<!-- ============================================= -->
<div id="tableView" class="hidden">
    <!-- عرض الملفات والمجلدات بشكل جدولي -->
    <?php if (empty($files)): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
            <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <h3 class="text-2xl font-bold text-gray-400 mb-2">هذا المجلد فارغ</h3>
            <p class="text-gray-500 mb-6">قم برفع ملفات أو إنشاء مجلد جديد</p>
            <div class="flex items-center justify-center space-x-4 space-x-reverse">
                <button onclick="openUploadModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all inline-flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12"/>
                    </svg>
                    رفع ملفات
                </button>
                
                <button onclick="openCreateFolderModal()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all inline-flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-5 5h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    مجلد جديد
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- عرض شبكي للملفات -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
            <?php foreach ($files as $file): ?>
            <div class="cyber-border bg-slate-800 rounded-lg p-4 hover:shadow-lg transition-all cursor-pointer group"
                 ondblclick="<?php echo $file['file_type'] == 'folder' ? "navigateToFolder('" . addslashes($file['file_path']) . "')" : "previewFile('" . addslashes($file['file_path']) . "')"; ?>">
                
                <div class="relative">
                    <!-- أيقونة الملف -->
                    <div class="text-5xl text-center mb-3 <?php echo getFileTypeColor($file['file_type']); ?>">
                        <?php echo getFileIcon($file['file_type']); ?>
                    </div>
                    
                    <!-- قائمة الإجراءات (تظهر عند التحويم) -->
                    <div class="absolute top-0 left-0 hidden group-hover:flex items-center space-x-1 space-x-reverse bg-slate-900 rounded-lg p-1">
                        <?php if ($file['file_type'] != 'folder'): ?>
                        <button onclick="downloadFile('<?php echo addslashes($file['file_path']); ?>')" class="p-1 hover:text-blue-400 transition-colors" title="تحميل">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <button onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo addslashes($file['file_name']); ?>')" class="p-1 hover:text-yellow-400 transition-colors" title="إعادة تسمية">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        
                        <button onclick="moveFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-green-400 transition-colors" title="نقل">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h4m-4 6h8m-8-3h12M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        
                        <button onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo addslashes($file['file_name']); ?>')" class="p-1 hover:text-red-400 transition-colors" title="حذف">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- اسم الملف -->
                <div class="text-center">
                    <p class="font-semibold text-sm truncate" title="<?php echo htmlspecialchars($file['file_name']); ?>">
                        <?php echo htmlspecialchars($file['file_name']); ?>
                    </p>
                    
                    <!-- معلومات إضافية للملفات -->
                    <?php if ($file['file_type'] != 'folder'): ?>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php echo formatFileSize($file['file_size']); ?> • 
                        <?php echo $file['download_count'] ?? 0; ?> تحميل
                    </p>
                    <?php else: ?>
                    <p class="text-xs text-blue-400 mt-1">مجلد</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- عرض جدولي للملفات -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6 overflow-x-auto">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                تفاصيل الملفات
            </h3>
            
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-2">النوع</th>
                        <th class="px-4 py-2">الحجم</th>
                        <th class="px-4 py-2">التاريخ</th>
                        <th class="px-4 py-2">التحميلات</th>
                        <th class="px-4 py-2">الإصدار</th>
                        <th class="px-4 py-2">اسم الملف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors cursor-pointer"
                        ondblclick="<?php echo $file['file_type'] == 'folder' ? "navigateToFolder('" . addslashes($file['file_path']) . "')" : "previewFile('" . addslashes($file['file_path']) . "')"; ?>">
                        
                        <td class="px-4 py-2">
                            <span class="flex items-center <?php echo getFileTypeColor($file['file_type']); ?>">
                                <?php echo getFileIcon($file['file_type']); ?>
                                <span class="mr-2 text-sm"><?php echo strtoupper($file['file_type']); ?></span>
                            </span>
                        </td>
                        
                        <td class="px-4 py-2 text-sm">
                            <?php echo $file['file_type'] != 'folder' ? formatFileSize($file['file_size']) : '-'; ?>
                        </td>
                        
                        <td class="px-4 py-2 text-sm text-gray-400">
                            <?php echo timeAgo($file['created_at']); ?>
                        </td>
                        
                        <td class="px-4 py-2 text-sm text-center">
                            <?php echo $file['download_count'] ?? 0; ?>
                        </td>
                        
                        <td class="px-4 py-2 text-sm text-blue-400">
                            <?php echo $file['version'] ?? '1.0'; ?>
                        </td>
                        
                        <td class="px-4 py-2 font-semibold text-sm">
                            <?php echo htmlspecialchars($file['file_name']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- الملفات الحديثة والأكثر تحميلاً -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            
            <!-- أحدث الملفات -->
            <?php if (!empty($recent_files)): ?>
            <div class="cyber-border bg-slate-800 rounded-xl p-6">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    أحدث الملفات
                </h3>
                
                <div class="space-y-3">
                    <?php foreach ($recent_files as $file): ?>
                    <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors cursor-pointer"
                         onclick="previewFile('<?php echo addslashes($file['file_path']); ?>')">
                        <span class="text-xs text-gray-400"><?php echo timeAgo($file['created_at']); ?></span>
                        <div class="flex items-center flex-1 mr-4">
                            <span class="<?php echo getFileTypeColor($file['file_type']); ?> ml-2">
                                <?php echo getFileIcon($file['file_type']); ?>
                            </span>
                            <span class="text-sm font-semibold truncate"><?php echo htmlspecialchars($file['file_name']); ?></span>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo formatFileSize($file['file_size']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- أكثر الملفات تحميلاً -->
            <?php if (!empty($popular_files)): ?>
            <div class="cyber-border bg-slate-800 rounded-xl p-6">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    الأكثر تحميلاً
                </h3>
                
                <div class="space-y-3">
                    <?php foreach ($popular_files as $file): ?>
                    <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors cursor-pointer"
                         onclick="previewFile('<?php echo addslashes($file['file_path']); ?>')">
                        <span class="text-xs text-yellow-400"><?php echo $file['download_count']; ?> تحميل</span>
                        <div class="flex items-center flex-1 mr-4">
                            <span class="<?php echo getFileTypeColor($file['file_type']); ?> ml-2">
                                <?php echo getFileIcon($file['file_type']); ?>
                            </span>
                            <span class="text-sm font-semibold truncate"><?php echo htmlspecialchars($file['file_name']); ?></span>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo formatFileSize($file['file_size']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- جميع النوافذ المنبثقة (كما هي) -->
<!-- ============================================= -->
<!-- نافذة رفع الملفات -->
<div id="upload-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeUploadModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">رفع ملفات</h3>
        </div>
        
        <form id="upload-form" enctype="multipart/form-data" onsubmit="handleUpload(event)">
            <input type="hidden" name="action" value="upload_file">
            <input type="hidden" name="folder_path" value="<?php echo htmlspecialchars($current_path); ?>">
            
            <div class="space-y-4">
                <!-- منطقة السحب والإفلات -->
                <div class="border-2 border-dashed border-slate-600 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer"
                     onclick="document.getElementById('file-input').click()"
                     ondragover="event.preventDefault()"
                     ondrop="handleDrop(event)">
                    
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    
                    <p class="text-sm text-gray-400 mb-2">اسحب وأفلت الملفات هنا</p>
                    <p class="text-xs text-gray-500">أو</p>
                    
                    <input type="file" id="file-input" name="file" multiple class="hidden" onchange="handleFileSelect(this.files)">
                    
                    <button type="button" class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                        اختيار ملفات
                    </button>
                </div>
                
                <!-- قائمة الملفات المختارة -->
                <div id="selected-files" class="space-y-2 max-h-40 overflow-y-auto"></div>
                
                <!-- خيارات إضافية -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الإصدار</label>
                    <input type="text" name="version" value="1.0" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeUploadModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    رفع
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة إنشاء مجلد جديد -->
<div id="create-folder-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateFolderModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400">إنشاء مجلد جديد</h3>
        </div>
        
        <form id="create-folder-form" onsubmit="handleCreateFolder(event)">
            <input type="hidden" name="action" value="create_folder">
            <input type="hidden" name="parent_path" value="<?php echo htmlspecialchars($current_path); ?>">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">اسم المجلد</label>
                <input type="text" name="folder_name" required 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"
                       placeholder="أدخل اسم المجلد">
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateFolderModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow">
                    إنشاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة إعادة تسمية -->
<div id="rename-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeRenameModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-yellow-400">إعادة تسمية</h3>
        </div>
        
        <form id="rename-form" onsubmit="handleRename(event)">
            <input type="hidden" name="action" value="rename_file">
            <input type="hidden" name="file_id" id="rename-file-id">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الاسم الجديد</label>
                <input type="text" id="rename-new-name" name="new_name" required 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right">
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeRenameModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-yellow-600 hover:bg-yellow-700 rounded-lg font-semibold transition-all cyber-glow">
                    حفظ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة نقل ملف -->
<div id="move-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeMoveModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-purple-400">نقل ملف</h3>
        </div>
        
        <form id="move-form" onsubmit="handleMove(event)">
            <input type="hidden" name="action" value="move_file">
            <input type="hidden" name="file_id" id="move-file-id">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">المجلد الوجهة</label>
                <select name="new_path" id="move-folder-select" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500">
                    <option value="/">/ (الرئيسية)</option>
                    <?php 
                    $folders = $db->query("SELECT file_path, file_name FROM repository_files WHERE file_type = 'folder' ORDER BY file_name")->fetchAll();
                    foreach ($folders as $folder): 
                    ?>
                    <option value="<?php echo htmlspecialchars($folder['file_path']); ?>">
                        <?php echo htmlspecialchars($folder['file_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeMoveModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-all cyber-glow">
                    نقل
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript للتبديل بين العرضين -->
<!-- ============================================= -->
<script>
// =============================================
// المتغيرات العامة
// =============================================
let selectedFiles = [];
let currentView = localStorage.getItem('repositoryView') || 'grid';

// =============================================
// التبديل بين العرض الشبكي والعرض الجدولي
// =============================================

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تطبيق العرض المحفوظ
    if (currentView === 'table') {
        showTableView();
    } else {
        showGridView();
    }
    
    // تحديث الأزرار
    updateViewButtons();
});

// إظهار العرض الشبكي (التصنيفات والبطاقات)
function showGridView() {
    currentView = 'grid';
    localStorage.setItem('repositoryView', 'grid');
    
    // إخفاء العرض الجدولي
    document.getElementById('tableView').classList.add('hidden');
    // إظهار العرض الشبكي
    document.getElementById('gridView').classList.remove('hidden');
    
    // تحديث الأزرار
    updateViewButtons();
    
    showNotification('📊 تم التبديل إلى العرض الشبكي', 'info');
}

// إظهار العرض الجدولي (الملفات والمجلدات)
function showTableView() {
    currentView = 'table';
    localStorage.setItem('repositoryView', 'table');
    
    // إخفاء العرض الشبكي
    document.getElementById('gridView').classList.add('hidden');
    // إظهار العرض الجدولي
    document.getElementById('tableView').classList.remove('hidden');
    
    // تحديث الأزرار
    updateViewButtons();
    
    showNotification('📋 تم التبديل إلى العرض الجدولي', 'info');
}

// تحديث شكل أزرار العرض
function updateViewButtons() {
    const gridBtn = document.getElementById('gridViewBtn');
    const tableBtn = document.getElementById('tableViewBtn');
    
    if (currentView === 'grid') {
        gridBtn.classList.add('bg-blue-600', 'text-white');
        gridBtn.classList.remove('hover:text-blue-400');
        tableBtn.classList.remove('bg-blue-600', 'text-white');
        tableBtn.classList.add('hover:text-blue-400');
    } else {
        tableBtn.classList.add('bg-blue-600', 'text-white');
        tableBtn.classList.remove('hover:text-blue-400');
        gridBtn.classList.remove('bg-blue-600', 'text-white');
        gridBtn.classList.add('hover:text-blue-400');
    }
}

// =============================================
// دوال التنقل
// =============================================
function navigateToFolder(path) {
    window.location.href = '?page=repository&path=' + encodeURIComponent(path);
}

// =============================================
// دوال رفع الملفات
// =============================================
function openUploadModal() {
    document.getElementById('upload-modal').classList.remove('hidden');
}

function closeUploadModal() {
    document.getElementById('upload-modal').classList.add('hidden');
    document.getElementById('upload-form').reset();
    document.getElementById('selected-files').innerHTML = '';
    selectedFiles = [];
}

function handleFileSelect(files) {
    selectedFiles = Array.from(files);
    displaySelectedFiles();
}

function handleDrop(event) {
    event.preventDefault();
    selectedFiles = Array.from(event.dataTransfer.files);
    displaySelectedFiles();
}

function displaySelectedFiles() {
    const container = document.getElementById('selected-files');
    container.innerHTML = '';
    
    selectedFiles.forEach(file => {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-2 bg-slate-700 rounded-lg';
        div.innerHTML = `
            <span class="text-xs text-gray-400">${(file.size / 1024).toFixed(1)} KB</span>
            <span class="text-sm flex-1 mr-4">${file.name}</span>
            <span class="text-blue-400">${file.name.split('.').pop().toUpperCase()}</span>
        `;
        container.appendChild(div);
    });
}

function handleUpload(event) {
    event.preventDefault();
    
    if (selectedFiles.length === 0) {
        showNotification('❌ الرجاء اختيار ملفات للرفع', 'error');
        return;
    }
    
    const formData = new FormData(document.getElementById('upload-form'));
    
    // إضافة الملفات للنموذج
    selectedFiles.forEach(file => {
        formData.append('files[]', file);
    });
    
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
        closeUploadModal();
        
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

// =============================================
// دوال المجلدات
// =============================================
function openCreateFolderModal() {
    document.getElementById('create-folder-modal').classList.remove('hidden');
}

function closeCreateFolderModal() {
    document.getElementById('create-folder-modal').classList.add('hidden');
    document.getElementById('create-folder-form').reset();
}

function handleCreateFolder(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-folder-form'));
    
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
        closeCreateFolderModal();
        
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

// =============================================
// دوال الملفات
// =============================================
function previewFile(path) {
    showNotification('🔍 جاري فتح الملف...', 'info');
    setTimeout(() => {
        window.open(path, '_blank');
    }, 500);
}

function downloadFile(path) {
    showNotification('📥 جاري تحميل الملف...', 'info');
    setTimeout(() => {
        const link = document.createElement('a');
        link.href = path;
        link.download = path.split('/').pop();
        link.click();
        showNotification('✅ تم التحميل', 'success');
    }, 500);
}

function editDocument(id) {
    window.location.href = '?page=creation&edit=' + id;
}

function renameFile(id, currentName) {
    document.getElementById('rename-file-id').value = id;
    document.getElementById('rename-new-name').value = currentName;
    document.getElementById('rename-modal').classList.remove('hidden');
}

function closeRenameModal() {
    document.getElementById('rename-modal').classList.add('hidden');
}

function handleRename(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('rename-form'));
    
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
        closeRenameModal();
        
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

function moveFile(id) {
    document.getElementById('move-file-id').value = id;
    document.getElementById('move-modal').classList.remove('hidden');
}

function closeMoveModal() {
    document.getElementById('move-modal').classList.add('hidden');
}

function handleMove(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('move-form'));
    
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
        closeMoveModal();
        
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

function deleteFile(id, name) {
    if (confirm(`⚠️ هل أنت متأكد من حذف "${name}"؟`)) {
        const formData = new FormData();
        formData.append('action', 'delete_file');
        formData.append('file_id', id);
        
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

function exportArchive() {
    showNotification('📦 جاري تصدير الأرشيف...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير', 'success');
    }, 2000);
}

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

// إغلاق النوافذ عند الضغط على ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUploadModal();
        closeCreateFolderModal();
        closeRenameModal();
        closeMoveModal();
    }
});
</script>

<!-- ============================================= -->
<!-- CSS إضافي -->
<!-- ============================================= -->
<style>
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
.table-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}
.group:hover .group-hover\:flex {
    display: flex;
}
</style>