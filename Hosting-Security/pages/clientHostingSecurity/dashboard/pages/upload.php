<?php
// =============================================
// client-unit/pages/upload.php
// صفحة رفع وإدارة ملفات العميل - النسخة المتكاملة
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

$client_id = $current_client['id'];

// =============================================
// معالجة الطلبات (إنشاء مجلد، رفع ملف، حذف، إلخ)
// =============================================
$message = '';
$error = '';

// إنشاء مجلد جديد
if (isset($_POST['create_folder'])) {
    $folder_name = trim($_POST['folder_name']);
    $parent_path = $_POST['current_path'] ?? '/';
    
    if (!empty($folder_name)) {
        // تنظيف اسم المجلد
        $folder_name = preg_replace('/[^a-zA-Z0-9_\-\p{Arabic}]/u', '_', $folder_name);
        
        // المسار الكامل
        $full_path = __DIR__ . "/../../uploads/client/{$client_id}/" . trim($parent_path, '/') . '/' . $folder_name;
        
        if (!file_exists($full_path)) {
            if (mkdir($full_path, 0777, true)) {
                // حفظ في قاعدة البيانات
                $sql = "INSERT INTO client_folders (client_id, folder_name, parent_path, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([$client_id, $folder_name, $parent_path]);
                
                $message = "✅ تم إنشاء مجلد '{$folder_name}' بنجاح";
                
                // تسجيل النشاط
                logActivity($db, $client_id, 'create_folder', 'folder', $db->lastInsertId(), 'إنشاء مجلد: ' . $folder_name);
            } else {
                $error = "❌ فشل إنشاء المجلد";
            }
        } else {
            $error = "❌ المجلد موجود بالفعل";
        }
    } else {
        $error = "❌ الرجاء إدخال اسم المجلد";
    }
}

// حذف ملف أو مجلد
if (isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];
    $item_type = $_POST['item_type'];
    $item_path = $_POST['item_path'];
    
    if ($item_type == 'file') {
        // حذف من قاعدة البيانات
        $sql = "DELETE FROM client_files WHERE id = ? AND client_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$item_id, $client_id]);
        
        // حذف الملف الفعلي
        $full_path = __DIR__ . "/../.." . $item_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        $message = "✅ تم حذف الملف بنجاح";
        
        // تسجيل النشاط
        logActivity($db, $client_id, 'delete', 'file', $item_id, 'حذف ملف');
        
    } else if ($item_type == 'folder') {
        // حذف من قاعدة البيانات
        $sql = "DELETE FROM client_folders WHERE id = ? AND client_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$item_id, $client_id]);
        
        // حذف المجلد الفعلي (اختياري - يمكن تركه)
        // rmdir_recursive(__DIR__ . "/../../uploads/client/{$client_id}/{$item_path}");
        
        $message = "✅ تم حذف المجلد بنجاح";
        
        // تسجيل النشاط
        logActivity($db, $client_id, 'delete', 'folder', $item_id, 'حذف مجلد');
    }
}

// إعادة تسمية
if (isset($_POST['rename_item'])) {
    $item_id = $_POST['item_id'];
    $item_type = $_POST['item_type'];
    $new_name = trim($_POST['new_name']);
    
    if (!empty($new_name)) {
        $new_name = preg_replace('/[^a-zA-Z0-9_\-\p{Arabic}]/u', '_', $new_name);
        
        if ($item_type == 'file') {
            // جلب معلومات الملف القديم
            $sql = "SELECT * FROM client_files WHERE id = ? AND client_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$item_id, $client_id]);
            $file = $stmt->fetch();
            
            if ($file) {
                $old_path = __DIR__ . "/../.." . $file['file_path'];
                $ext = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                $dir = dirname($file['file_path']);
                $new_filename = $new_name . '.' . $ext;
                $new_path = $dir . '/' . $new_filename;
                $full_new_path = __DIR__ . "/../.." . $new_path;
                
                // إعادة تسمية الملف الفعلي
                if (rename($old_path, $full_new_path)) {
                    // تحديث قاعدة البيانات
                    $sql = "UPDATE client_files SET file_name = ?, file_path = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$new_filename, $new_path, $item_id]);
                    
                    $message = "✅ تم إعادة التسمية بنجاح";
                    
                    // تسجيل النشاط
                    logActivity($db, $client_id, 'rename', 'file', $item_id, 'إعادة تسمية ملف إلى: ' . $new_filename);
                } else {
                    $error = "❌ فشل إعادة التسمية";
                }
            }
            
        } else if ($item_type == 'folder') {
            // جلب معلومات المجلد القديم
            $sql = "SELECT * FROM client_folders WHERE id = ? AND client_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$item_id, $client_id]);
            $folder = $stmt->fetch();
            
            if ($folder) {
                $old_path = __DIR__ . "/../../uploads/client/{$client_id}/" . trim($folder['parent_path'], '/') . '/' . $folder['folder_name'];
                $new_path = __DIR__ . "/../../uploads/client/{$client_id}/" . trim($folder['parent_path'], '/') . '/' . $new_name;
                
                // إعادة تسمية المجلد الفعلي
                if (rename($old_path, $new_path)) {
                    // تحديث قاعدة البيانات
                    $sql = "UPDATE client_folders SET folder_name = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$new_name, $item_id]);
                    
                    // تحديث مسارات جميع الملفات في هذا المجلد
                    $old_folder_path = trim($folder['parent_path'], '/') . '/' . $folder['folder_name'] . '/';
                    $new_folder_path = trim($folder['parent_path'], '/') . '/' . $new_name . '/';
                    
                    $sql = "UPDATE client_files SET folder_path = REPLACE(folder_path, ?, ?) WHERE client_id = ? AND folder_path LIKE ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$old_folder_path, $new_folder_path, $client_id, $old_folder_path . '%']);
                    
                    $message = "✅ تم إعادة تسمية المجلد بنجاح";
                    
                    // تسجيل النشاط
                    logActivity($db, $client_id, 'rename', 'folder', $item_id, 'إعادة تسمية مجلد إلى: ' . $new_name);
                } else {
                    $error = "❌ فشل إعادة التسمية";
                }
            }
        }
    }
}

// نقل/نسخ العناصر
if (isset($_POST['move_items'])) {
    $item_ids = $_POST['item_ids'] ?? [];
    $target_folder = $_POST['target_folder'] ?? '/';
    $action = $_POST['action'] ?? 'move'; // move or copy
    
    if (!empty($item_ids)) {
        $success_count = 0;
        
        foreach ($item_ids as $item_id) {
            // جلب معلومات الملف
            $sql = "SELECT * FROM client_files WHERE id = ? AND client_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$item_id, $client_id]);
            $file = $stmt->fetch();
            
            if ($file) {
                $old_path = __DIR__ . "/../.." . $file['file_path'];
                $new_folder_path = __DIR__ . "/../../uploads/client/{$client_id}/" . trim($target_folder, '/');
                $new_file_path = $new_folder_path . '/' . basename($file['file_name']);
                $new_relative_path = "/uploads/client/{$client_id}/" . trim($target_folder, '/') . '/' . basename($file['file_name']);
                
                if ($action == 'move') {
                    // نقل الملف
                    if (!file_exists($new_folder_path)) {
                        mkdir($new_folder_path, 0777, true);
                    }
                    
                    if (rename($old_path, $new_file_path)) {
                        // تحديث قاعدة البيانات
                        $sql = "UPDATE client_files SET file_path = ?, folder_path = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$new_relative_path, $target_folder, $item_id]);
                        $success_count++;
                        
                        // تسجيل النشاط
                        logActivity($db, $client_id, 'move', 'file', $item_id, 'نقل ملف إلى ' . $target_folder);
                    }
                    
                } else if ($action == 'copy') {
                    // نسخ الملف
                    if (!file_exists($new_folder_path)) {
                        mkdir($new_folder_path, 0777, true);
                    }
                    
                    if (copy($old_path, $new_file_path)) {
                        // إدخال في قاعدة البيانات
                        $sql = "INSERT INTO client_files (client_id, project_id, file_name, file_path, folder_path, file_type, file_size, description, created_at) 
                                SELECT client_id, project_id, ?, ?, ?, file_type, file_size, description, NOW() FROM client_files WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([basename($file['file_name']), $new_relative_path, $target_folder, $item_id]);
                        $success_count++;
                        
                        // تسجيل النشاط
                        logActivity($db, $client_id, 'copy', 'file', $db->lastInsertId(), 'نسخ ملف إلى ' . $target_folder);
                    }
                }
            }
        }
        
        $message = "✅ تم {$action} {$success_count} ملف بنجاح";
    }
}

// معالجة رفع الملفات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $project_id = $_POST['project_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $current_folder = $_POST['current_folder'] ?? '/';
    
    // إنشاء مجلد للعميل إذا لم يكن موجوداً
    $base_upload_dir = __DIR__ . "/../../uploads/client/{$client_id}/";
    if (!file_exists($base_upload_dir)) {
        mkdir($base_upload_dir, 0777, true);
    }
    
    // المجلد الحالي
    $target_dir = $base_upload_dir . trim($current_folder, '/');
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $files = $_FILES['files'];
    $uploaded_count = 0;
    $failed_count = 0;
    
    // أنواع الملفات المسموح بها
    $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'txt', 'php', 'html', 'css', 'js', 'json', 'xml'];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] == UPLOAD_ERR_OK) {
            $file_name = $files['name'][$i];
            $file_tmp = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // التحقق من نوع الملف
            if (!in_array($file_type, $allowed_types)) {
                $failed_count++;
                continue;
            }
            
            // التحقق من حجم الملف (حد أقصى 50 ميجابايت)
            if ($file_size > 50 * 1024 * 1024) {
                $failed_count++;
                continue;
            }
            
            // تنظيف اسم الملف
            $file_name = preg_replace('/[^a-zA-Z0-9_\-\p{Arabic}.]/u', '_', $file_name);
            
            // التأكد من عدم وجود ملف بنفس الاسم
            $file_path = $target_dir . '/' . $file_name;
            $counter = 1;
            while (file_exists($file_path)) {
                $path_parts = pathinfo($file_name);
                $new_filename = $path_parts['filename'] . '_' . $counter . '.' . $path_parts['extension'];
                $file_path = $target_dir . '/' . $new_filename;
                $counter++;
            }
            
            if ($counter > 1) {
                $file_name = $new_filename;
            }
            
            $relative_path = "/uploads/client/{$client_id}/" . trim($current_folder, '/') . '/' . $file_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                // حفظ معلومات الملف في قاعدة البيانات
                $sql = "INSERT INTO client_files (
                    client_id, project_id, file_name, file_path, folder_path,
                    file_type, file_size, description, download_count, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $client_id,
                    $project_id ?: null,
                    $file_name,
                    $relative_path,
                    $current_folder,
                    $file_type,
                    $file_size,
                    $description
                ]);
                
                if ($result) {
                    $uploaded_count++;
                    
                    // تسجيل النشاط
                    logActivity($db, $client_id, 'upload', 'file', $db->lastInsertId(), 'رفع ملف: ' . $file_name);
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
        } else {
            $failed_count++;
        }
    }
    
    if ($uploaded_count > 0) {
        $message = "✅ تم رفع {$uploaded_count} ملف بنجاح";
        if ($failed_count > 0) {
            $message .= "، فشل رفع {$failed_count} ملف";
        }
    } else {
        $error = "❌ فشل رفع جميع الملفات";
    }
}

// =============================================
// جلب بيانات المجلدات والملفات
// =============================================
try {
    // المجلد الحالي من GET
    $current_path = isset($_GET['path']) ? '/' . trim($_GET['path'], '/') . '/' : '/';
    $current_path = str_replace('//', '/', $current_path);
    
    // جلب جميع المجلدات للعميل
    $folders = $db->prepare("
        SELECT * FROM client_folders 
        WHERE client_id = ? AND parent_path = ?
        ORDER BY folder_name
    ");
    $folders->execute([$client_id, $current_path]);
    $folders = $folders->fetchAll();
    
    // جلب جميع الملفات للعميل في المجلد الحالي
    $files = $db->prepare("
        SELECT f.*, p.project_name 
        FROM client_files f
        LEFT JOIN client_projects p ON f.project_id = p.id
        WHERE f.client_id = ? AND f.folder_path = ?
        ORDER BY f.created_at DESC
    ");
    $files->execute([$client_id, $current_path]);
    $files = $files->fetchAll();
    
    // جلب مسار التنقل (breadcrumb)
    $path_parts = explode('/', trim($current_path, '/'));
    $breadcrumb = [];
    $cumulative_path = '';
    
    foreach ($path_parts as $part) {
        if (!empty($part)) {
            $cumulative_path .= '/' . $part;
            $breadcrumb[] = [
                'name' => $part,
                'path' => $cumulative_path
            ];
        }
    }
    
    // جلب مشاريع العميل
    $projects = $db->prepare("
        SELECT id, project_name, project_code 
        FROM client_projects 
        WHERE client_id = ? AND status IN ('pending', 'under_study', 'in_progress', 'testing')
        ORDER BY project_name
    ");
    $projects->execute([$client_id]);
    $projects = $projects->fetchAll();
    
    // إحصائيات الملفات
    $stats = $db->prepare("
        SELECT 
            COUNT(*) as total_files,
            COALESCE(SUM(file_size), 0) as total_size,
            COUNT(DISTINCT file_type) as file_types,
            COUNT(DISTINCT folder_path) as total_folders
        FROM client_files
        WHERE client_id = ?
    ");
    $stats->execute([$client_id]);
    $stats = $stats->fetch();
    
} catch (Exception $e) {
    $folders = [];
    $files = [];
    $projects = [];
    $breadcrumb = [];
    $stats = ['total_files' => 0, 'total_size' => 0, 'file_types' => 0, 'total_folders' => 0];
}

// =============================================
// دوال مساعدة
// =============================================



function logActivity($db, $client_id, $activity_type, $target_type, $target_id, $description) {
    $sql = "INSERT INTO client_activity_log (client_id, activity_type, target_type, target_id, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$client_id, $activity_type, $target_type, $target_id, $description]);
}

function getFileIcon($ext) {
    $icons = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xls' => '📊',
        'xlsx' => '📊',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'zip' => '🗜️',
        'rar' => '🗜️',
        'txt' => '📃',
        'php' => '🐘',
        'html' => '🌐',
        'css' => '🎨',
        'js' => '⚡',
        'json' => '📋',
        'xml' => '📌'
    ];
    return $icons[$ext] ?? '📄';
}
?>

<!-- ============================================= -->
<!-- الهيدر الجديد مع الإحصائيات -->
<!-- ============================================= -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 mb-8 border border-slate-700">
    <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 to-transparent"></div>
    
    <div class="relative z-10">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="w-16 h-16 rounded-2xl bg-purple-500/20 flex items-center justify-center backdrop-blur-sm border border-purple-500/30">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-l from-purple-400 to-pink-400 bg-clip-text text-transparent">مدير الملفات</h1>
                    <p class="text-slate-400 mt-1">رفع وإدارة وتنظيم ملفاتك بكل سهولة</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2 space-x-reverse">
                <div class="bg-slate-800/80 backdrop-blur-sm rounded-xl px-4 py-2 border border-slate-700">
                    <span class="text-sm text-slate-400 ml-2">📁 <?php echo $stats['total_folders']; ?> مجلد</span>
                    <span class="text-sm text-slate-400 ml-2">📄 <?php echo $stats['total_files']; ?> ملف</span>
                    <span class="text-sm text-slate-400">💾 <?php echo formatFileSize($stats['total_size']); ?></span>
                </div>
                
                <button onclick="openCreateFolderModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium transition-all flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-5 6h10a2 2 0 002-2V9.828a2 2 0 00-.586-1.414l-4.828-4.828A2 2 0 0014.172 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    مجلد جديد
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- رسائل النجاح والخطأ -->
<!-- ============================================= -->
<?php if ($message): ?>
    <div class="bg-green-600/20 border border-green-600 text-green-400 p-4 rounded-lg mb-6 flex items-center">
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-600/20 border border-red-600 text-red-400 p-4 rounded-lg mb-6 flex items-center">
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- مسار التنقل (Breadcrumb) -->
<!-- ============================================= -->
<div class="flex items-center space-x-2 space-x-reverse mb-6 overflow-x-auto pb-2">
    <a href="?page=upload&path=/" class="flex items-center px-3 py-1.5 bg-slate-800/80 hover:bg-slate-700 rounded-lg text-sm transition-all">
        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2 2 2 2-2 2 2 2-2 2 2 2-2 2 2"/>
        </svg>
        الرئيسية
    </a>
    
    <?php foreach ($breadcrumb as $index => $crumb): ?>
    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="?page=upload&path=<?php echo urlencode($crumb['path']); ?>" class="px-3 py-1.5 bg-slate-800/80 hover:bg-slate-700 rounded-lg text-sm transition-all">
        <?php echo htmlspecialchars($crumb['name']); ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ============================================= -->
<!-- نموذج رفع الملفات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- منطقة الرفع -->
    <div class="lg:col-span-2 bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            رفع ملفات جديدة إلى المسار الحالي
        </h3>
        
        <form id="upload-form" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="current_folder" value="<?php echo htmlspecialchars($current_path); ?>">
            
            <!-- منطقة السحب والإفلات -->
            <div id="drop-zone" class="border-2 border-dashed border-slate-700 hover:border-purple-500/50 rounded-xl p-8 text-center transition-all cursor-pointer mb-4"
                 ondragover="handleDragOver(event)" 
                 ondrop="handleDrop(event)"
                 onclick="document.getElementById('file-input').click()">
                
                <svg class="w-16 h-16 text-purple-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                
                <p class="text-lg font-semibold mb-2">اسحب وأفلت الملفات هنا</p>
                <p class="text-slate-400 text-sm mb-4">أو</p>
                
                <input type="file" id="file-input" name="files[]" multiple class="hidden" onchange="handleFileSelect(event)">
                
                <button type="button" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg">
                    اختيار الملفات
                </button>
                
                <p class="text-xs text-slate-500 mt-4">
                    الحد الأقصى لكل ملف: 50MB<br>
                    الصيغ المدعومة: PDF, DOC, XLS, JPG, PNG, ZIP, PHP, HTML, JS, CSS
                </p>
            </div>
            
            <!-- قائمة الملفات المختارة -->
            <div id="files-list" class="hidden space-y-2 mb-4">
                <h4 class="font-semibold mb-2">الملفات المختارة:</h4>
                <div id="selected-files" class="space-y-2 max-h-48 overflow-y-auto"></div>
            </div>
            
            <!-- اختيار المشروع والوصف -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">المشروع (اختياري)</label>
                    <select name="project_id" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                        <option value="">-- بدون مشروع --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">وصف الملفات (اختياري)</label>
                    <input type="text" name="description" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500" 
                           placeholder="وصف عام للملفات">
                </div>
            </div>
            
            <!-- زر الرفع -->
            <button type="submit" id="upload-btn" class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg font-semibold transition-all" disabled>
                بدء الرفع
            </button>
        </form>
        
        <!-- شريط التقدم -->
        <div id="upload-progress" class="hidden mt-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-slate-400">جاري الرفع...</span>
                <span class="text-sm text-purple-400" id="progress-percent">0%</span>
            </div>
            <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>
    
    <!-- إرشادات وتعليمات -->
    <div class="lg:col-span-1 bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            إرشادات
        </h3>
        
        <div class="space-y-4">
            <div class="bg-slate-900/50 rounded-lg p-3">
                <h4 class="font-semibold text-sm mb-2">📋 أنواع الملفات</h4>
                <p class="text-xs text-slate-400">PDF, DOC, XLS, JPG, PNG, ZIP, PHP, HTML, JS, CSS, JSON</p>
            </div>
            
            <div class="bg-slate-900/50 rounded-lg p-3">
                <h4 class="font-semibold text-sm mb-2">⚖️ الحجم المسموح</h4>
                <p class="text-xs text-slate-400">50 ميجابايت لكل ملف</p>
            </div>
            
            <div class="bg-slate-900/50 rounded-lg p-3">
                <h4 class="font-semibold text-sm mb-2">📁 إنشاء مجلدات</h4>
                <p class="text-xs text-slate-400">يمكنك إنشاء مجلدات لتنظيم ملفاتك والنقر على المجلد للدخول إليه</p>
            </div>
            
            <div class="bg-slate-900/50 rounded-lg p-3">
                <h4 class="font-semibold text-sm mb-2">🔄 إدارة الملفات</h4>
                <p class="text-xs text-slate-400">يمكنك نقل ونسخ وإعادة تسمية وحذف الملفات بسهولة</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- محتوى المجلد الحالي -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold flex items-center">
            <svg class="w-5 h-5 ml-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            محتوى المجلد الحالي
        </h3>
        <span class="text-sm text-slate-400"><?php echo count($folders) + count($files); ?> عنصر</span>
    </div>
    
    <?php if (empty($folders) && empty($files)): ?>
        <div class="text-center py-12">
            <svg class="w-20 h-20 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-slate-400 mb-4">هذا المجلد فارغ</p>
            <p class="text-sm text-slate-500">يمكنك إنشاء مجلد جديد أو رفع ملفات</p>
        </div>
    <?php else: ?>
        <!-- المجلدات -->
        <?php if (!empty($folders)): ?>
            <h4 class="text-sm font-medium text-blue-400 mb-2 flex items-center">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                المجلدات
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                <?php foreach ($folders as $folder): ?>
                <div class="bg-slate-900/50 rounded-lg p-4 hover:bg-slate-800 transition-all group relative" 
                     ondblclick="window.location.href='?page=upload&path=<?php echo urlencode(trim($current_path, '/') . '/' . $folder['folder_name']); ?>'">
                    
                    <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity flex space-x-1 space-x-reverse">
                        <button onclick="event.stopPropagation(); renameFolder(<?php echo $folder['id']; ?>, '<?php echo $folder['folder_name']; ?>')" class="p-1 bg-blue-600/80 hover:bg-blue-600 rounded-lg text-xs" title="إعادة تسمية">
                            ✏️
                        </button>
                        <button onclick="event.stopPropagation(); deleteFolder(<?php echo $folder['id']; ?>, '<?php echo $folder['folder_name']; ?>', '<?php echo $current_path; ?>')" class="p-1 bg-red-600/80 hover:bg-red-600 rounded-lg text-xs" title="حذف">
                            🗑️
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($folder['folder_name']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo date('Y-m-d', strtotime($folder['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- الملفات -->
        <?php if (!empty($files)): ?>
            <h4 class="text-sm font-medium text-green-400 mb-2 flex items-center">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                </svg>
                الملفات
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($files as $file): ?>
                <div class="bg-slate-900/50 rounded-lg p-4 hover:bg-slate-800 transition-all group relative">
                    
                    <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity flex space-x-1 space-x-reverse">
                        <button onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo $file['file_name']; ?>')" class="p-1 bg-blue-600/80 hover:bg-blue-600 rounded-lg text-xs" title="إعادة تسمية">
                            ✏️
                        </button>
                        <button onclick="moveFile(<?php echo $file['id']; ?>, '<?php echo $file['file_name']; ?>')" class="p-1 bg-green-600/80 hover:bg-green-600 rounded-lg text-xs" title="نقل">
                            📦
                        </button>
                        <a href="<?php echo $file['file_path']; ?>" download class="p-1 bg-purple-600/80 hover:bg-purple-600 rounded-lg text-xs" title="تحميل">
                            ⬇️
                        </a>
                        <button onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo $file['file_name']; ?>')" class="p-1 bg-red-600/80 hover:bg-red-600 rounded-lg text-xs" title="حذف">
                            🗑️
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-4xl mb-2"><?php echo getFileIcon($file['file_type']); ?></div>
                        <p class="text-sm font-medium truncate" title="<?php echo htmlspecialchars($file['file_name']); ?>">
                            <?php echo htmlspecialchars($file['file_name']); ?>
                        </p>
                        <p class="text-xs text-slate-500"><?php echo formatFileSize($file['file_size']); ?></p>
                        <p class="text-xs text-slate-600"><?php echo timeAgo($file['created_at']); ?></p>
                        <?php if ($file['project_name']): ?>
                        <span class="inline-block px-2 py-0.5 mt-1 bg-blue-600/20 text-blue-400 rounded-full text-xs">
                            <?php echo $file['project_name']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء مجلد جديد -->
<!-- ============================================= -->
<div id="create-folder-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateFolderModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">إنشاء مجلد جديد</h3>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            
            <div>
                <label class="block text-sm font-medium mb-2">اسم المجلد</label>
                <input type="text" name="folder_name" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500 text-white" 
                       placeholder="أدخل اسم المجلد" autofocus>
            </div>
            
            <div class="bg-slate-900/50 rounded-lg p-3">
                <p class="text-sm text-slate-400 mb-1">المسار الحالي:</p>
                <p class="text-xs font-mono text-slate-300">/<?php echo trim($current_path, '/'); ?></p>
            </div>
            
            <button type="submit" name="create_folder" class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg font-medium transition-all">
                إنشاء
            </button>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة إعادة تسمية -->
<!-- ============================================= -->
<div id="rename-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeRenameModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white" id="rename-modal-title">إعادة تسمية</h3>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="item_id" id="rename-item-id">
            <input type="hidden" name="item_type" id="rename-item-type">
            
            <div>
                <label class="block text-sm font-medium mb-2">الاسم الجديد</label>
                <input type="text" name="new_name" id="rename-new-name" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500 text-white" autofocus>
            </div>
            
            <button type="submit" name="rename_item" class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg font-medium transition-all">
                حفظ
            </button>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة نقل/نسخ الملفات -->
<!-- ============================================= -->
<div id="move-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeMoveModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white" id="move-modal-title">نقل الملف</h3>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="item_ids[]" id="move-item-id">
            <input type="hidden" name="action" value="move">
            
            <div>
                <label class="block text-sm font-medium mb-2">اختر المجلد الهدف</label>
                <select name="target_folder" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500 text-white" required>
                    <option value="/">الرئيسية</option>
                    <?php
                    // جلب جميع المجلدات لعرضها
                    $all_folders = $db->prepare("SELECT folder_name, parent_path FROM client_folders WHERE client_id = ? ORDER BY parent_path, folder_name");
                    $all_folders->execute([$client_id]);
                    $all_folders = $all_folders->fetchAll();
                    
                    foreach ($all_folders as $f) {
                        $full_path = trim($f['parent_path'], '/') . '/' . $f['folder_name'];
                        echo "<option value=\"/" . trim($full_path, '/') . "\">/" . $full_path . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="flex items-center space-x-2 space-x-reverse">
                <button type="submit" name="move_items" class="flex-1 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg font-medium transition-all">
                    نقل
                </button>
                <button type="button" onclick="closeMoveModal()" class="flex-1 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition-all">
                    إلغاء
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
// رفع الملفات
// =============================================
let selectedFiles = [];

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('drop-zone').classList.add('border-purple-500/50', 'bg-purple-500/5');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('drop-zone').classList.remove('border-purple-500/50', 'bg-purple-500/5');
    
    const files = e.dataTransfer.files;
    handleFiles(files);
}

function handleFileSelect(e) {
    const files = e.target.files;
    handleFiles(files);
}

function handleFiles(files) {
    selectedFiles = Array.from(files);
    displaySelectedFiles();
    document.getElementById('upload-btn').disabled = false;
}

function displaySelectedFiles() {
    const container = document.getElementById('selected-files');
    const filesList = document.getElementById('files-list');
    
    container.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const fileSize = (file.size / 1024).toFixed(1);
        const fileType = file.name.split('.').pop().toUpperCase();
        
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-2 bg-slate-900 rounded-lg';
        div.innerHTML = `
            <div class="flex items-center">
                <button onclick="removeFile(${index})" class="text-red-400 hover:text-red-300 ml-2 text-sm">
                    ✕
                </button>
                <span class="text-xs text-slate-500">${fileSize} KB</span>
            </div>
            <span class="text-sm flex-1 mr-4 truncate">${file.name}</span>
            <span class="text-xs bg-purple-600/20 text-purple-400 px-2 py-1 rounded">${fileType}</span>
        `;
        container.appendChild(div);
    });
    
    filesList.classList.remove('hidden');
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    if (selectedFiles.length === 0) {
        document.getElementById('files-list').classList.add('hidden');
        document.getElementById('upload-btn').disabled = true;
    } else {
        displaySelectedFiles();
    }
}

// شريط التقدم
document.getElementById('upload-form').addEventListener('submit', function(e) {
    if (selectedFiles.length === 0) {
        e.preventDefault();
        showNotification('الرجاء اختيار ملفات للرفع', 'warning');
        return;
    }
    
    document.getElementById('upload-progress').classList.remove('hidden');
    
    let progress = 0;
    const interval = setInterval(function() {
        progress += Math.random() * 15;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
        }
        document.getElementById('progress-bar').style.width = progress + '%';
        document.getElementById('progress-percent').textContent = Math.floor(progress) + '%';
    }, 300);
});

// =============================================
// إدارة المجلدات والملفات
// =============================================
function openCreateFolderModal() {
    document.getElementById('create-folder-modal').classList.remove('hidden');
}

function closeCreateFolderModal() {
    document.getElementById('create-folder-modal').classList.add('hidden');
}

// إعادة تسمية
function renameFile(id, currentName) {
    document.getElementById('rename-modal-title').textContent = 'إعادة تسمية الملف';
    document.getElementById('rename-item-id').value = id;
    document.getElementById('rename-item-type').value = 'file';
    document.getElementById('rename-new-name').value = currentName.substring(0, currentName.lastIndexOf('.'));
    document.getElementById('rename-modal').classList.remove('hidden');
}

function renameFolder(id, currentName) {
    document.getElementById('rename-modal-title').textContent = 'إعادة تسمية المجلد';
    document.getElementById('rename-item-id').value = id;
    document.getElementById('rename-item-type').value = 'folder';
    document.getElementById('rename-new-name').value = currentName;
    document.getElementById('rename-modal').classList.remove('hidden');
}

function closeRenameModal() {
    document.getElementById('rename-modal').classList.add('hidden');
}

// حذف
function deleteFile(id, name) {
    if (confirm(`هل أنت متأكد من حذف الملف "${name}"؟`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="item_id" value="${id}">
            <input type="hidden" name="item_type" value="file">
            <input type="hidden" name="item_path" value="${name}">
            <input type="hidden" name="delete_item" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteFolder(id, name, path) {
    if (confirm(`هل أنت متأكد من حذف المجلد "${name}"؟\nسيتم نقل جميع الملفات إلى المجلد الرئيسي`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="item_id" value="${id}">
            <input type="hidden" name="item_type" value="folder">
            <input type="hidden" name="item_path" value="${path}${name}">
            <input type="hidden" name="delete_item" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// نقل
function moveFile(id, name) {
    document.getElementById('move-modal-title').textContent = `نقل الملف: ${name}`;
    document.getElementById('move-item-id').value = id;
    document.getElementById('move-modal').classList.remove('hidden');
}

function closeMoveModal() {
    document.getElementById('move-modal').classList.add('hidden');
}

// إشعارات
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium animate-fade-in-up`;
    
    if (type === 'success') notification.classList.add('bg-green-600');
    else if (type === 'error') notification.classList.add('bg-red-600');
    else if (type === 'warning') notification.classList.add('bg-yellow-600');
    else notification.classList.add('bg-blue-600');
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('animate-fade-out-down');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from { opacity: 0; transform: translate(-50%, 20px); }
        to { opacity: 1; transform: translate(-50%, 0); }
    }
    
    @keyframes fadeOutDown {
        from { opacity: 1; transform: translate(-50%, 0); }
        to { opacity: 0; transform: translate(-50%, 20px); }
    }
    
    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }
    
    .animate-fade-out-down {
        animation: fadeOutDown 0.3s ease-out forwards;
    }
`;
document.head.appendChild(style);
</script>