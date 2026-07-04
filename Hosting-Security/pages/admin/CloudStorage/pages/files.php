<?php
// =============================================
// cloud-unit/pages/files.php
// صفحة إدارة الملفات والمجلدات - متصلة بقاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// دوال مساعدة
// =============================================


function getFileTypeColor($ext) {
    $colors = [
        'pdf' => 'text-red-400',
        'doc' => 'text-blue-400', 'docx' => 'text-blue-400',
        'xls' => 'text-green-400', 'xlsx' => 'text-green-400',
        'jpg' => 'text-purple-400', 'jpeg' => 'text-purple-400', 'png' => 'text-purple-400',
        'zip' => 'text-yellow-400', 'rar' => 'text-yellow-400',
        'html' => 'text-orange-400', 'php' => 'text-indigo-400',
        'js' => 'text-yellow-400', 'css' => 'text-pink-400',
        'folder' => 'text-blue-400'
    ];
    
    return $colors[$ext] ?? 'text-gray-400';
}




function getPermissionSymbol($permissions) {
    if (!$permissions) return '---------';
    $perms = '';
    $perms .= ($permissions[0] & 4) ? 'r' : '-';
    $perms .= ($permissions[0] & 2) ? 'w' : '-';
    $perms .= ($permissions[0] & 1) ? 'x' : '-';
    $perms .= ($permissions[1] & 4) ? 'r' : '-';
    $perms .= ($permissions[1] & 2) ? 'w' : '-';
    $perms .= ($permissions[1] & 1) ? 'x' : '-';
    $perms .= ($permissions[2] & 4) ? 'r' : '-';
    $perms .= ($permissions[2] & 2) ? 'w' : '-';
    $perms .= ($permissions[2] & 1) ? 'x' : '-';
    return $perms;
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'upload_files':
                // رفع ملفات متعددة
                $target_dir = __DIR__ . '/../../uploads/cloud/' . date('Y/m/');
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $uploaded_count = 0;
                $files = $_FILES['files'];
                
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] == UPLOAD_ERR_OK) {
                        $file_name = $files['name'][$i];
                        $file_tmp = $files['tmp_name'][$i];
                        $file_size = $files['size'][$i];
                        $file_type = $files['type'][$i];
                        
                        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '_' . date('Ymd_His') . '.' . $extension;
                        $filepath = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($file_tmp, $filepath)) {
                            $relative_path = '/uploads/cloud/' . date('Y/m/') . $new_filename;
                            
                            $sql = "INSERT INTO cloud_files (
                                file_name, file_path, file_type, file_size, mime_type,
                                folder_path, project_id, server_id, uploaded_by, download_count, permissions, owner, group_owner, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, '644', 'www-data', 'www-data', NOW())";
                            
                            $stmt = $db->prepare($sql);
                            $stmt->execute([
                                $file_name,
                                $relative_path,
                                $extension,
                                $file_size,
                                $file_type,
                                $_POST['folder_path'] ?? '/',
                                $_POST['project_id'] ?: null,
                                $_POST['server_id'] ?: null,
                                $_SESSION['user_id'] ?? 1
                            ]);
                            
                            $uploaded_count++;
                        }
                    }
                }
                
                $response['success'] = true;
                $response['message'] = "✅ تم رفع {$uploaded_count} ملف بنجاح";
                break;
                
            case 'create_folder':
                // إنشاء مجلد جديد
                $folder_name = $_POST['folder_name'];
                $parent_path = $_POST['parent_path'] ?? '/';
                $folder_path = rtrim($parent_path, '/') . '/' . $folder_name;
                
                // التحقق من عدم وجود مجلد بنفس الاسم
                $check = $db->prepare("SELECT COUNT(*) FROM cloud_files WHERE file_path = ? AND is_folder = 1");
                $check->execute([$folder_path]);
                
                if ($check->fetchColumn() > 0) {
                    $response['message'] = '❌ يوجد مجلد بنفس الاسم';
                    break;
                }
                
                $sql = "INSERT INTO cloud_files (
                    file_name, file_path, folder_path, is_folder, uploaded_by, permissions, owner, group_owner, created_at
                ) VALUES (?, ?, ?, 1, ?, '755', 'www-data', 'www-data', NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $folder_name,
                    $folder_path,
                    $parent_path,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إنشاء المجلد بنجاح';
                break;
                
            case 'delete_file':
                // حذف ملف أو مجلد
                $stmt = $db->prepare("SELECT file_path, is_folder FROM cloud_files WHERE id = ?");
                $stmt->execute([$_POST['file_id']]);
                $file = $stmt->fetch();
                
                if ($file) {
                    // إذا كان ملف حقيقي (ليس مجلد) نحذف الملف الفعلي
                    if (!$file['is_folder'] && file_exists(__DIR__ . '/../..' . $file['file_path'])) {
                        unlink(__DIR__ . '/../..' . $file['file_path']);
                    }
                    
                    // إذا كان مجلد، نحذف كل الملفات داخله أولاً
                    if ($file['is_folder']) {
                        $db->prepare("DELETE FROM cloud_files WHERE folder_path LIKE ?")->execute([$file['file_path'] . '%']);
                    }
                    
                    $db->prepare("DELETE FROM cloud_files WHERE id = ?")->execute([$_POST['file_id']]);
                }
                
                $response['success'] = true;
                $response['message'] = '✅ تم الحذف بنجاح';
                break;
                
            case 'rename_file':
                // إعادة تسمية ملف أو مجلد
                $new_name = $_POST['new_name'];
                $file_id = $_POST['file_id'];
                
                $stmt = $db->prepare("SELECT file_name, file_path, is_folder FROM cloud_files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch();
                
                if ($file) {
                    $old_path = $file['file_path'];
                    $new_path = dirname($old_path) . '/' . $new_name;
                    
                    $sql = "UPDATE cloud_files SET file_name = ?, file_path = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$new_name, $new_path, $file_id]);
                    
                    // إذا كان مجلد، نحدث مسارات جميع الملفات داخله
                    if ($file['is_folder']) {
                        $sql = "UPDATE cloud_files SET folder_path = REPLACE(folder_path, ?, ?) WHERE folder_path LIKE ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$old_path, $new_path, $old_path . '%']);
                    }
                }
                
                $response['success'] = true;
                $response['message'] = '✅ تم إعادة التسمية';
                break;
                
            case 'move_files':
                // نقل ملفات إلى مجلد آخر
                $ids = json_decode($_POST['file_ids'], true);
                $target_folder = $_POST['target_folder'];
                
                foreach ($ids as $id) {
                    $stmt = $db->prepare("SELECT file_name FROM cloud_files WHERE id = ?");
                    $stmt->execute([$id]);
                    $file = $stmt->fetch();
                    
                    if ($file) {
                        $new_path = rtrim($target_folder, '/') . '/' . $file['file_name'];
                        
                        $sql = "UPDATE cloud_files SET folder_path = ?, file_path = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$target_folder, $new_path, $id]);
                    }
                }
                
                $response['success'] = true;
                $response['message'] = '✅ تم نقل الملفات';
                break;
                
            case 'copy_files':
                // نسخ ملفات
                $ids = json_decode($_POST['file_ids'], true);
                $target_folder = $_POST['target_folder'];
                
                foreach ($ids as $id) {
                    $stmt = $db->prepare("SELECT * FROM cloud_files WHERE id = ?");
                    $stmt->execute([$id]);
                    $file = $stmt->fetch();
                    
                    if ($file) {
                        $new_name = 'نسخة من ' . $file['file_name'];
                        $new_path = rtrim($target_folder, '/') . '/' . $new_name;
                        
                        $sql = "INSERT INTO cloud_files (
                            file_name, file_path, file_type, file_size, mime_type,
                            folder_path, project_id, server_id, is_folder, is_public,
                            uploaded_by, version, permissions, owner, group_owner, download_count, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
                        
                        $copy_stmt = $db->prepare($sql);
                        $copy_stmt->execute([
                            $new_name,
                            $new_path,
                            $file['file_type'],
                            $file['file_size'],
                            $file['mime_type'],
                            $target_folder,
                            $file['project_id'],
                            $file['server_id'],
                            $file['is_folder'],
                            $file['is_public'] ?? 0,
                            $_SESSION['user_id'] ?? 1,
                            $file['version'] ?? '1.0',
                            $file['permissions'] ?? '644',
                            $file['owner'] ?? 'www-data',
                            $file['group_owner'] ?? 'www-data'
                        ]);
                    }
                }
                
                $response['success'] = true;
                $response['message'] = '✅ تم نسخ الملفات';
                break;
                
            case 'compress_files':
                // ضغط ملفات (محاكاة)
                $response['success'] = true;
                $response['message'] = '📦 تم ضغط الملفات بنجاح';
                break;
                
            case 'extract_files':
                // فك ضغط ملفات (محاكاة)
                $response['success'] = true;
                $response['message'] = '📂 تم فك الضغط بنجاح';
                break;
                
            case 'share_files':
                // مشاركة ملفات
                $expiry_days = $_POST['expiry_days'] ?? 7;
                $share_link = bin2hex(random_bytes(16));
                
                // هنا يمكن حفظ رابط المشاركة في جدول منفصل
                
                $response['success'] = true;
                $response['message'] = '🔗 تم إنشاء رابط المشاركة';
                $response['share_link'] = BASE_URL . '/share/' . $share_link;
                break;
                
            case 'change_permissions':
                // تغيير صلاحيات الملف
                $sql = "UPDATE cloud_files SET permissions = ?, owner = ?, group_owner = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['permissions'],
                    $_POST['owner'],
                    $_POST['group_owner'],
                    $_POST['file_id']
                ]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تغيير الصلاحيات';
                break;
                
            case 'update_download_count':
                // تحديث عدد التحميلات
                $sql = "UPDATE cloud_files SET download_count = download_count + 1 WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['file_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تحديث عدد التحميلات';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
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
    
    // المشروع الحالي (اختياري)
    $project_id = $_GET['project'] ?? null;
    $server_id = $_GET['server'] ?? null;
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'name';
    
    // جلب محتويات المجلد الحالي
    $sql = "SELECT * FROM cloud_files WHERE folder_path = ?";
    $params = [$current_path];
    
    if ($project_id) {
        $sql .= " AND (project_id = ? OR project_id IS NULL)";
        $params[] = $project_id;
    }
    
    if ($server_id) {
        $sql .= " AND (server_id = ? OR server_id IS NULL)";
        $params[] = $server_id;
    }
    
    if ($search) {
        $sql .= " AND file_name LIKE ?";
        $params[] = "%$search%";
    }
    
    // الترتيب
    switch ($sort) {
        case 'date':
            $sql .= " ORDER BY created_at DESC";
            break;
        case 'size':
            $sql .= " ORDER BY file_size DESC";
            break;
        case 'type':
            $sql .= " ORDER BY file_type, file_name";
            break;
        default: // name
            $sql .= " ORDER BY is_folder DESC, file_name ASC";
            break;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
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
    
    // إحصائيات الملفات
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_files,
            SUM(CASE WHEN is_folder = 1 THEN 1 ELSE 0 END) as total_folders,
            COALESCE(SUM(CASE WHEN is_folder = 0 THEN file_size ELSE 0 END), 0) as total_size,
            COUNT(DISTINCT file_type) as file_types,
            SUM(download_count) as total_downloads
        FROM cloud_files
    ")->fetch();
    
    // أحدث الملفات
    $recent_files = $db->query("
        SELECT * FROM cloud_files 
        WHERE is_folder = 0
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // أكثر الملفات تحميلاً
    $popular_files = $db->query("
        SELECT * FROM cloud_files 
        WHERE is_folder = 0
        ORDER BY download_count DESC 
        LIMIT 5
    ")->fetchAll();
    
    // قائمة المشاريع للفلتر
    $projects = $db->query("
        SELECT id, project_name 
        FROM cloud_projects 
        WHERE status = 'active'
        ORDER BY project_name
    ")->fetchAll();
    
    // قائمة الخوادم للفلتر
    $servers = $db->query("
        SELECT id, server_name 
        FROM cloud_servers 
        WHERE status = 'online'
        ORDER BY server_name
    ")->fetchAll();
    
    // بنية المجلدات (للشجرة)
    $folder_tree = $db->query("
        SELECT id, file_name, file_path 
        FROM cloud_files 
        WHERE is_folder = 1
        ORDER BY file_name
    ")->fetchAll();
    
} catch (Exception $e) {
    $files = [];
    $recent_files = [];
    $popular_files = [];
    $folder_tree = [];
    $projects = [];
    $servers = [];
    $stats = [
        'total_files' => 0,
        'total_folders' => 0,
        'total_size' => 0,
        'file_types' => 0,
        'total_downloads' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
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
<!-- رأس الصفحة مع الإحصائيات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي الملفات</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo number_format($stats['total_files']); ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">المجلدات</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['total_folders']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">حجم التخزين</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo formatFileSize($stats['total_size']); ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">أنواع الملفات</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo $stats['file_types']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي التحميلات</p>
        <p class="text-2xl font-bold text-orange-400"><?php echo number_format($stats['total_downloads']); ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- شريط الأدوات الرئيسي -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h3 class="text-xl font-bold text-right">📂 إدارة الملفات والمجلدات</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="openUploadModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all cyber-glow flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12"/>
                </svg>
                رفع ملفات
            </button>
            
            <button onclick="openCreateFolderModal()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-5 5h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                مجلد جديد
            </button>
            
            <button onclick="toggleBulkActions()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                تحديد متعدد
            </button>
            
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الإجراءات المتعددة (مخفي افتراضياً) -->
    <div id="bulk-actions-bar" class="hidden items-center justify-between bg-blue-900 bg-opacity-30 rounded-lg p-4 mt-4 border border-blue-500">
        <div class="flex items-center">
            <span class="text-sm text-gray-300 ml-4">تم تحديد <span id="selected-count">0</span> عنصر</span>
            <button onclick="selectAllFiles()" class="text-sm text-blue-400 hover:text-blue-300 ml-3">تحديد الكل</button>
            <button onclick="clearSelection()" class="text-sm text-gray-400 hover:text-gray-300">إلغاء التحديد</button>
        </div>
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="bulkDownload()" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm flex items-center">
                ⬇️ تحميل
            </button>
            <button onclick="bulkMove()" class="px-3 py-1 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm flex items-center">
                📋 نقل
            </button>
            <button onclick="bulkCopy()" class="px-3 py-1 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm flex items-center">
                📑 نسخ
            </button>
            <button onclick="bulkCompress()" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-sm flex items-center">
                📦 ضغط
            </button>
            <button onclick="bulkExtract()" class="px-3 py-1 bg-teal-600 hover:bg-teal-700 rounded-lg text-sm flex items-center">
                📂 استخراج
            </button>
            <button onclick="bulkDelete()" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-lg text-sm flex items-center">
                🗑️ حذف
            </button>
        </div>
    </div>
    
    <!-- فلاتر وبحث -->
    <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-slate-700">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="🔍 بحث في الملفات..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                   value="<?php echo htmlspecialchars($search); ?>">
            <button onclick="searchFiles()" class="absolute left-2 top-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <select id="filter-project" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع المشاريع</option>
            <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project['id']; ?>" <?php echo ($project_id == $project['id']) ? 'selected' : ''; ?>>
                <?php echo $project['project_name']; ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <select id="filter-server" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الخوادم</option>
            <?php foreach ($servers as $server): ?>
            <option value="<?php echo $server['id']; ?>" <?php echo ($server_id == $server['id']) ? 'selected' : ''; ?>>
                <?php echo $server['server_name']; ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <select id="sort-by" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>📝 حسب الاسم</option>
            <option value="date" <?php echo $sort == 'date' ? 'selected' : ''; ?>>📅 حسب التاريخ</option>
            <option value="size" <?php echo $sort == 'size' ? 'selected' : ''; ?>>📊 حسب الحجم</option>
            <option value="type" <?php echo $sort == 'type' ? 'selected' : ''; ?>>📁 حسب النوع</option>
        </select>
    </div>
    
    <!-- مسار التنقل (Breadcrumb) -->
    <div class="flex items-center space-x-2 space-x-reverse mt-4 pt-4 border-t border-slate-700">
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
                <a href="?page=files&path=<?php echo urlencode($crumb['path']); ?><?php echo $project_id ? '&project=' . $project_id : ''; ?><?php echo $server_id ? '&server=' . $server_id : ''; ?>" 
                   class="text-gray-400 hover:text-blue-400 transition-colors">
                    <?php echo htmlspecialchars($crumb['name']); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- أزرار التبديل بين العرضين -->
<!-- ============================================= -->
<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-bold flex items-center">
        <span class="text-xl ml-2">📁</span>
        محتويات المجلد
        <span class="mr-3 px-3 py-1 bg-slate-700 rounded-full text-xs text-gray-400"><?php echo count($files); ?> عنصر</span>
    </h2>
    
    <div class="flex items-center bg-slate-700 rounded-lg p-1">
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

<!-- ============================================= -->
<!-- العرض الشبكي -->
<!-- ============================================= -->
<div id="gridView">
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
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
            <?php foreach ($files as $file): ?>
            <div class="cyber-border bg-slate-800 rounded-lg p-4 hover:shadow-lg transition-all cursor-pointer group file-item"
                 data-id="<?php echo $file['id']; ?>"
                 data-type="<?php echo $file['is_folder'] ? 'folder' : 'file'; ?>"
                 data-path="<?php echo $file['file_path']; ?>"
                 ondblclick="<?php echo $file['is_folder'] ? "navigateToFolder('" . addslashes($file['file_path']) . "')" : "previewFile('" . addslashes($file['file_path']) . "')"; ?>">
                
                <div class="relative">
                    <!-- أيقونة الملف -->
                    <div class="text-5xl text-center mb-3 <?php echo getFileTypeColor($file['is_folder'] ? 'folder' : pathinfo($file['file_name'], PATHINFO_EXTENSION)); ?>">
                        <?php echo getFileIcon($file['file_name'], $file['is_folder']); ?>
                    </div>
                    
                    <!-- مربع اختيار (للتحديد المتعدد) -->
                    <div class="absolute top-0 right-0">
                        <input type="checkbox" class="file-checkbox hidden w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded" value="<?php echo $file['id']; ?>">
                    </div>
                    
                    <!-- قائمة الإجراءات (تظهر عند التحويم) -->
                    <div class="absolute top-0 left-0 hidden group-hover:flex items-center space-x-1 space-x-reverse bg-slate-900 rounded-lg p-1 z-10">
                        <?php if (!$file['is_folder']): ?>
                        <button onclick="downloadFile('<?php echo addslashes($file['file_path']); ?>', <?php echo $file['id']; ?>)" class="p-1 hover:text-blue-400 transition-colors" title="تحميل">
                            ⬇️
                        </button>
                        
                        <button onclick="shareFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-indigo-400 transition-colors" title="مشاركة">
                            🔗
                        </button>
                        
                        <button onclick="changePermissions(<?php echo $file['id']; ?>)" class="p-1 hover:text-cyan-400 transition-colors" title="صلاحيات">
                            🔐
                        </button>
                        <?php endif; ?>
                        
                        <button onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo addslashes($file['file_name']); ?>')" class="p-1 hover:text-yellow-400 transition-colors" title="إعادة تسمية">
                            ✏️
                        </button>
                        
                        <button onclick="moveFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-green-400 transition-colors" title="نقل">
                            📋
                        </button>
                        
                        <button onclick="copyFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-purple-400 transition-colors" title="نسخ">
                            📑
                        </button>
                        
                        <?php if (!$file['is_folder'] && in_array(strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION)), ['zip', 'rar', 'tar', 'gz'])): ?>
                        <button onclick="extractFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-teal-400 transition-colors" title="استخراج">
                            📂
                        </button>
                        <?php endif; ?>
                        
                        <?php if (!$file['is_folder']): ?>
                        <button onclick="compressFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-indigo-400 transition-colors" title="ضغط">
                            📦
                        </button>
                        <?php endif; ?>
                        
                        <button onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo addslashes($file['file_name']); ?>')" class="p-1 hover:text-red-400 transition-colors" title="حذف">
                            🗑️
                        </button>
                    </div>
                </div>
                
                <!-- اسم الملف -->
                <div class="text-center">
                    <p class="font-semibold text-sm truncate" title="<?php echo htmlspecialchars($file['file_name']); ?>">
                        <?php echo htmlspecialchars($file['file_name']); ?>
                    </p>
                    
                    <!-- معلومات إضافية -->
                    <?php if (!$file['is_folder']): ?>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php echo formatFileSize($file['file_size']); ?>
                    </p>
                    <?php else: ?>
                    <p class="text-xs text-blue-400 mt-1">مجلد</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- العرض الجدولي -->
<!-- ============================================= -->
<div id="tableView" class="hidden">
    <div class="cyber-border bg-slate-800 rounded-xl p-6 overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="select-all" class="rounded bg-slate-700 border-slate-600 hidden">
                    </th>
                    <th class="px-4 py-3">الإجراءات</th>
                    <th class="px-4 py-3">الحجم</th>
                    <th class="px-4 py-3">آخر تعديل</th>
                    <th class="px-4 py-3">النوع</th>
                    <th class="px-4 py-3">الصلاحيات</th>
                    <th class="px-4 py-3">المالك</th>
                    <th class="px-4 py-3">الإصدار</th>
                    <th class="px-4 py-3">التحميلات</th>
                    <th class="px-4 py-3">اسم الملف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors cursor-pointer file-row"
                    data-id="<?php echo $file['id']; ?>"
                    data-type="<?php echo $file['is_folder'] ? 'folder' : 'file'; ?>"
                    data-path="<?php echo $file['file_path']; ?>"
                    ondblclick="<?php echo $file['is_folder'] ? "navigateToFolder('" . addslashes($file['file_path']) . "')" : "previewFile('" . addslashes($file['file_path']) . "')"; ?>">
                    
                    <td class="px-4 py-3">
                        <input type="checkbox" class="file-checkbox rounded bg-slate-700 border-slate-600 hidden" value="<?php echo $file['id']; ?>">
                    </td>
                    
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-1 space-x-reverse">
                            <?php if (!$file['is_folder']): ?>
                            <button onclick="downloadFile('<?php echo addslashes($file['file_path']); ?>', <?php echo $file['id']; ?>)" class="p-1 hover:text-blue-400" title="تحميل">⬇️</button>
                            <button onclick="shareFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-indigo-400" title="مشاركة">🔗</button>
                            <button onclick="changePermissions(<?php echo $file['id']; ?>)" class="p-1 hover:text-cyan-400" title="صلاحيات">🔐</button>
                            <?php endif; ?>
                            <button onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo addslashes($file['file_name']); ?>')" class="p-1 hover:text-yellow-400" title="إعادة تسمية">✏️</button>
                            <button onclick="moveFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-green-400" title="نقل">📋</button>
                            <button onclick="copyFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-purple-400" title="نسخ">📑</button>
                            <?php if (!$file['is_folder'] && in_array(strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION)), ['zip', 'rar', 'tar', 'gz'])): ?>
                            <button onclick="extractFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-teal-400" title="استخراج">📂</button>
                            <?php endif; ?>
                            <?php if (!$file['is_folder']): ?>
                            <button onclick="compressFile(<?php echo $file['id']; ?>)" class="p-1 hover:text-indigo-400" title="ضغط">📦</button>
                            <?php endif; ?>
                            <button onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo addslashes($file['file_name']); ?>')" class="p-1 hover:text-red-400" title="حذف">🗑️</button>
                        </div>
                    </td>
                    
                    <td class="px-4 py-3 text-sm">
                        <?php echo !$file['is_folder'] ? formatFileSize($file['file_size']) : '-'; ?>
                    </td>
                    
                    <td class="px-4 py-3 text-sm text-gray-400">
                        <?php echo timeAgo($file['updated_at'] ?? $file['created_at']); ?>
                    </td>
                    
                    <td class="px-4 py-3">
                        <span class="flex items-center <?php echo getFileTypeColor($file['is_folder'] ? 'folder' : pathinfo($file['file_name'], PATHINFO_EXTENSION)); ?>">
                            <?php echo getFileIcon($file['file_name'], $file['is_folder']); ?>
                            <span class="mr-2 text-sm"><?php echo $file['is_folder'] ? 'مجلد' : strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION)); ?></span>
                        </span>
                    </td>
                    
                    <td class="px-4 py-3 text-sm font-mono">
                        <?php echo $file['permissions'] ?? '644'; ?>
                    </td>
                    
                    <td class="px-4 py-3 text-sm text-gray-400">
                        <?php echo $file['owner'] ?? 'www-data'; ?>
                    </td>
                    
                    <td class="px-4 py-3 text-sm text-blue-400">
                        <?php echo $file['version'] ?? '1.0'; ?>
                    </td>
                    
                    <td class="px-4 py-3 text-sm text-yellow-400">
                        <?php echo $file['download_count'] ?? 0; ?>
                    </td>
                    
                    <td class="px-4 py-3 font-semibold text-sm">
                        <?php echo htmlspecialchars($file['file_name']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- أحدث الملفات والأكثر تحميلاً -->
<!-- ============================================= -->
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
        
        <div class="space-y-2">
            <?php foreach ($recent_files as $file): ?>
            <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors cursor-pointer"
                 onclick="previewFile('<?php echo addslashes($file['file_path']); ?>')">
                <span class="text-xs text-gray-400"><?php echo timeAgo($file['created_at']); ?></span>
                <div class="flex items-center flex-1 mr-4">
                    <span class="text-lg ml-2"><?php echo getFileIcon($file['file_name']); ?></span>
                    <span class="text-sm font-semibold truncate"><?php echo htmlspecialchars($file['file_name']); ?></span>
                </div>
                <span class="text-xs text-gray-400"><?php echo formatFileSize($file['file_size']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- الأكثر تحميلاً -->
    <?php if (!empty($popular_files)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            الأكثر تحميلاً
        </h3>
        
        <div class="space-y-2">
            <?php foreach ($popular_files as $file): ?>
            <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors cursor-pointer"
                 onclick="previewFile('<?php echo addslashes($file['file_path']); ?>')">
                <span class="text-xs text-yellow-400">⬇️ <?php echo $file['download_count']; ?></span>
                <div class="flex items-center flex-1 mr-4">
                    <span class="text-lg ml-2"><?php echo getFileIcon($file['file_name']); ?></span>
                    <span class="text-sm font-semibold truncate"><?php echo htmlspecialchars($file['file_name']); ?></span>
                </div>
                <span class="text-xs text-gray-400"><?php echo formatFileSize($file['file_size']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- جميع النوافذ المنبثقة -->
<!-- ============================================= -->

<!-- نافذة رفع الملفات -->
<div id="upload-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeUploadModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">رفع ملفات</h3>
        </div>
        
        <form id="upload-form" enctype="multipart/form-data" onsubmit="handleUpload(event)">
            <input type="hidden" name="action" value="upload_files">
            <input type="hidden" name="folder_path" value="<?php echo htmlspecialchars($current_path); ?>">
            
            <div class="space-y-4">
                <!-- منطقة السحب والإفلات -->
                <div class="upload-area p-8 text-center cursor-pointer"
                     onclick="document.getElementById('file-input').click()"
                     ondragover="event.preventDefault()"
                     ondrop="handleDrop(event)">
                    
                    <svg class="w-16 h-16 text-blue-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    
                    <p class="text-gray-400 mb-2">اسحب وأفلت الملفات هنا</p>
                    <p class="text-sm text-gray-500 mb-4">أو</p>
                    
                    <input type="file" id="file-input" name="files[]" multiple class="hidden" onchange="handleFileSelect(this.files)">
                    
                    <button type="button" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg">
                        اختيار ملفات
                    </button>
                </div>
                
                <!-- قائمة الملفات المختارة -->
                <div id="selected-files" class="space-y-2 max-h-40 overflow-y-auto"></div>
                
                <!-- خيارات إضافية -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                        <select name="project_id" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- عام --</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الخادم</label>
                        <select name="server_id" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="">-- عام --</option>
                            <?php foreach ($servers as $server): ?>
                            <option value="<?php echo $server['id']; ?>"><?php echo $server['server_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeUploadModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    رفع الملفات
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

<!-- نافذة نقل ملفات -->
<div id="move-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeMoveModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-purple-400">نقل العناصر</h3>
        </div>
        
        <form id="move-form" onsubmit="handleMove(event)">
            <input type="hidden" name="action" value="move_files">
            <input type="hidden" name="file_ids" id="move-file-ids">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">المجلد الوجهة</label>
                <select name="target_folder" id="move-folder-select" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500">
                    <option value="/">/ (الرئيسية)</option>
                    <?php foreach ($folder_tree as $folder): ?>
                    <option value="<?php echo htmlspecialchars($folder['file_path']); ?>">
                        <?php echo str_repeat('— ', substr_count($folder['file_path'], '/') - 1) . $folder['file_name']; ?>
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

<!-- نافذة مشاركة ملف -->
<div id="share-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeShareModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-indigo-400">مشاركة الملف</h3>
        </div>
        
        <form id="share-form" onsubmit="handleShare(event)">
            <input type="hidden" name="action" value="share_files">
            <input type="hidden" name="file_id" id="share-file-id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">صلاحية الرابط</label>
                    <select name="expiry_days" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                        <option value="1">يوم واحد</option>
                        <option value="7" selected>7 أيام</option>
                        <option value="30">30 يوم</option>
                        <option value="365">سنة</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">حماية بكلمة مرور</label>
                    <input type="password" name="password" placeholder="اختياري" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeShareModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-lg font-semibold transition-all cyber-glow">
                    إنشاء رابط
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة تغيير الصلاحيات -->
<div id="permissions-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closePermissionsModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-cyan-400">تغيير الصلاحيات</h3>
        </div>
        
        <form id="permissions-form" onsubmit="handlePermissions(event)">
            <input type="hidden" name="action" value="change_permissions">
            <input type="hidden" name="file_id" id="perm-file-id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الصلاحيات</label>
                    <input type="text" name="permissions" id="perm-value" value="644" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg font-mono text-center"
                           placeholder="755">
                </div>
                
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="bg-slate-700 p-2 rounded">المالك</div>
                    <div class="bg-slate-700 p-2 rounded">المجموعة</div>
                    <div class="bg-slate-700 p-2 rounded">الآخرون</div>
                    <div class="font-mono">rwx</div>
                    <div class="font-mono">rwx</div>
                    <div class="font-mono">rwx</div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المالك</label>
                    <input type="text" name="owner" value="www-data" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المجموعة</label>
                    <input type="text" name="group_owner" value="www-data" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closePermissionsModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-cyan-600 hover:bg-cyan-700 rounded-lg font-semibold transition-all cyber-glow">
                    حفظ
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
let selectedFiles = new Set();
let currentView = localStorage.getItem('filesView') || 'grid';
let pendingFiles = [];

// =============================================
// التبديل بين العرضين
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    if (currentView === 'table') {
        showTableView();
    } else {
        showGridView();
    }
    updateViewButtons();
});

function showGridView() {
    currentView = 'grid';
    localStorage.setItem('filesView', 'grid');
    document.getElementById('tableView').classList.add('hidden');
    document.getElementById('gridView').classList.remove('hidden');
    updateViewButtons();
}

function showTableView() {
    currentView = 'table';
    localStorage.setItem('filesView', 'table');
    document.getElementById('gridView').classList.add('hidden');
    document.getElementById('tableView').classList.remove('hidden');
    updateViewButtons();
}

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
    const project = document.getElementById('filter-project').value;
    const server = document.getElementById('filter-server').value;
    let url = '?page=files&path=' + encodeURIComponent(path);
    if (project) url += '&project=' + project;
    if (server) url += '&server=' + server;
    window.location.href = url;
}

function previewFile(path) {
    window.open(path, '_blank');
}

function downloadFile(path, fileId) {
    // تحديث عدد التحميلات
    const formData = new FormData();
    formData.append('action', 'update_download_count');
    formData.append('file_id', fileId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    
    // تحميل الملف
    const link = document.createElement('a');
    link.href = path;
    link.download = path.split('/').pop();
    link.click();
    showNotification('📥 جاري التحميل...', 'info');
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
    pendingFiles = [];
}

function handleFileSelect(files) {
    pendingFiles = Array.from(files);
    displaySelectedFiles();
}

function handleDrop(event) {
    event.preventDefault();
    pendingFiles = Array.from(event.dataTransfer.files);
    displaySelectedFiles();
}

function displaySelectedFiles() {
    const container = document.getElementById('selected-files');
    container.innerHTML = '';
    
    pendingFiles.forEach(file => {
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
    
    if (pendingFiles.length === 0) {
        showNotification('❌ الرجاء اختيار ملفات للرفع', 'error');
        return;
    }
    
    const formData = new FormData(document.getElementById('upload-form'));
    pendingFiles.forEach(file => formData.append('files[]', file));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
// دوال الملفات (إجراءات مفردة)
// =============================================
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
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
    document.getElementById('move-file-ids').value = JSON.stringify([id]);
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
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
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

function copyFile(id) {
    const formData = new FormData();
    formData.append('action', 'copy_files');
    formData.append('file_ids', JSON.stringify([id]));
    formData.append('target_folder', '<?php echo $current_path; ?>');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
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

function shareFile(id) {
    document.getElementById('share-file-id').value = id;
    document.getElementById('share-modal').classList.remove('hidden');
}

function closeShareModal() {
    document.getElementById('share-modal').classList.add('hidden');
    document.getElementById('share-form').reset();
}

function handleShare(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('share-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            if (data.share_link) {
                prompt('رابط المشاركة:', data.share_link);
            }
            closeShareModal();
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

function changePermissions(id) {
    document.getElementById('perm-file-id').value = id;
    document.getElementById('permissions-modal').classList.remove('hidden');
}

function closePermissionsModal() {
    document.getElementById('permissions-modal').classList.add('hidden');
    document.getElementById('permissions-form').reset();
}

function handlePermissions(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('permissions-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closePermissionsModal();
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

function compressFile(id) {
    const formData = new FormData();
    formData.append('action', 'compress_files');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showNotification(data.message, 'success');
    });
}

function extractFile(id) {
    const formData = new FormData();
    formData.append('action', 'extract_files');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showNotification(data.message, 'success');
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
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
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

// =============================================
// دوال التحديد المتعدد
// =============================================
function toggleBulkActions() {
    const bar = document.getElementById('bulk-actions-bar');
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const selectAll = document.getElementById('select-all');
    
    bar.classList.toggle('hidden');
    bar.classList.toggle('flex');
    
    checkboxes.forEach(cb => cb.classList.toggle('hidden'));
    if (selectAll) selectAll.classList.toggle('hidden');
    
    selectedFiles.clear();
    checkboxes.forEach(cb => cb.checked = false);
    if (selectAll) selectAll.checked = false;
    updateSelectedCount();
}

function updateSelectedCount() {
    document.getElementById('selected-count').textContent = selectedFiles.size;
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.file-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
        if (selectAll.checked) {
            selectedFiles.add(cb.value);
        } else {
            selectedFiles.delete(cb.value);
        }
    });
    
    updateSelectedCount();
}

function selectAllFiles() {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const selectAll = document.getElementById('select-all');
    
    checkboxes.forEach(cb => {
        cb.checked = true;
        selectedFiles.add(cb.value);
    });
    
    if (selectAll) selectAll.checked = true;
    updateSelectedCount();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const selectAll = document.getElementById('select-all');
    
    checkboxes.forEach(cb => cb.checked = false);
    if (selectAll) selectAll.checked = false;
    selectedFiles.clear();
    updateSelectedCount();
}

// =============================================
// دوال الإجراءات المتعددة
// =============================================
function bulkDownload() {
    if (selectedFiles.size === 0) {
        showNotification('❌ الرجاء تحديد ملفات', 'error');
        return;
    }
    showNotification(`📥 جاري تحميل ${selectedFiles.size} ملف...`, 'info');
}

function bulkMove() {
    if (selectedFiles.size === 0) {
        showNotification('❌ الرجاء تحديد ملفات', 'error');
        return;
    }
    document.getElementById('move-file-ids').value = JSON.stringify(Array.from(selectedFiles));
    document.getElementById('move-modal').classList.remove('hidden');
}

function bulkCopy() {
    if (selectedFiles.size === 0) {
        showNotification('❌ الرجاء تحديد ملفات', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'copy_files');
    formData.append('file_ids', JSON.stringify(Array.from(selectedFiles)));
    formData.append('target_folder', '<?php echo $current_path; ?>');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
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

function bulkCompress() {
    if (selectedFiles.size === 0) {
        showNotification('❌ الرجاء تحديد ملفات', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'compress_files');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showNotification(data.message, 'success');
    });
}

function bulkExtract() {
    if (selectedFiles.size === 0) {
        showNotification('❌ الرجاء تحديد ملفات', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'extract_files');
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showNotification(data.message, 'success');
    });
}

function bulkDelete() {
    if (selectedFiles.size === 0) {
        showNotification('❌ الرجاء تحديد ملفات', 'error');
        return;
    }
    
    if (confirm(`⚠️ هل أنت متأكد من حذف ${selectedFiles.size} عنصر؟`)) {
        let deleted = 0;
        const total = selectedFiles.size;
        
        selectedFiles.forEach(id => {
            const formData = new FormData();
            formData.append('action', 'delete_file');
            formData.append('file_id', id);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                deleted++;
                if (deleted === total) {
                    showNotification(`✅ تم حذف ${total} عنصر`, 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            });
        });
    }
}

// =============================================
// دوال الفلاتر والبحث
// =============================================
function applyFilters() {
    const project = document.getElementById('filter-project').value;
    const server = document.getElementById('filter-server').value;
    const sort = document.getElementById('sort-by').value;
    const search = document.getElementById('search-input').value;
    
    let url = '?page=files&path=<?php echo urlencode($current_path); ?>';
    if (project) url += '&project=' + project;
    if (server) url += '&server=' + server;
    if (sort) url += '&sort=' + sort;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

function searchFiles() {
    applyFilters();
}

function refreshPage() {
    location.reload();
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
    notification.className = `notification ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg text-sm`;
    notification.textContent = message;
    
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

// ربط أحداث مربعات التحديد
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.file-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                selectedFiles.add(this.value);
            } else {
                selectedFiles.delete(this.value);
            }
            updateSelectedCount();
        });
    });
    
    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', toggleAllCheckboxes);
    }
});

// إغلاق النوافذ بالـ ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUploadModal();
        closeCreateFolderModal();
        closeRenameModal();
        closeMoveModal();
        closeShareModal();
        closePermissionsModal();
    }
});
</script>