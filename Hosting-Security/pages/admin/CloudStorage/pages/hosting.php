<?php
// =============================================
// cloud-unit/pages/hosting.php
// صفحة إدارة استضافة المواقع
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// دالة مساعدة لتنسيق حجم الملفات
function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
}
// =============================================
// نظام استضافة المواقع المتكامل
// =============================================

// إنشاء الجداول اللازمة



// =============================================
// معالجة الإجراءات
// =============================================
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_site':
                // استضافة موقع جديد
                $site_name = trim($_POST['site_name'] ?? '');
                $site_domain = trim($_POST['site_domain'] ?? '');
                $site_type = $_POST['site_type'] ?? 'html';
                $site_description = trim($_POST['site_description'] ?? '');
                $use_ssl = isset($_POST['use_ssl']);
                
                if (empty($site_name) || empty($site_domain)) {
                    throw new Exception('اسم الموقع والنطاق مطلوبان');
                }
                
                // تنظيف اسم الموقع لعمل مجلد
                $folder_name = preg_replace('/[^a-z0-9]/', '_', strtolower($site_name));
                $folder_name = substr($folder_name, 0, 50); // الحد الأقصى 50 حرف
                
                // المسار الكامل للموقع
                $sites_base = __DIR__ . '/../../sites/';
                $site_folder = $sites_base . $folder_name;
                
                // التأكد من عدم وجود مجلد مكرر
                if (is_dir($site_folder)) {
                    $folder_name .= '_' . time();
                    $site_folder = $sites_base . $folder_name;
                }
                
                // إنشاء هيكل الموقع
                if (!is_dir($sites_base)) {
                    mkdir($sites_base, 0755, true);
                }
                
                mkdir($site_folder, 0755, true);
                mkdir($site_folder . '/public', 0755, true);
                mkdir($site_folder . '/logs', 0755, true);
                mkdir($site_folder . '/backups', 0755, true);
                mkdir($site_folder . '/config', 0755, true);
                
                // إنشاء ملف .htaccess للحماية
                $htaccess_content = "Options -Indexes\n";
                $htaccess_content .= "RewriteEngine On\n";
                $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
                $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
                file_put_contents($site_folder . '/.htaccess', $htaccess_content);
                
                // إنشاء ملف HTML افتراضي حسب نوع الموقع
                $index_content = '';
                $index_file = '';
                
                switch ($site_type) {
                    case 'wordpress':
                        $index_file = $site_folder . '/public/index.php';
                        $index_content = '<?php
/**
 * WordPress Installation - ' . $site_name . '
 * هذا ملف WordPress افتراضي
 * قم بتحميل ملفات WordPress هنا
 */
echo "<h1>مرحباً بكم في موقع ' . $site_name . '</h1>";
echo "<p>يتم تجهيز الموقع...</p>";
?>
';
                        break;
                        
                    case 'php':
                        $index_file = $site_folder . '/public/index.php';
                        $index_content = '<?php
/**
 * PHP Site - ' . $site_name . '
 */
$site_name = "' . $site_name . '";
$current_time = date("Y-m-d H:i:s");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md">
            <h1 class="text-3xl font-bold text-blue-600 mb-4"><?php echo $site_name; ?></h1>
            <p class="text-gray-600 mb-6">موقع PHP قيد الإنشاء</p>
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <p class="text-sm text-gray-600 mb-2">معلومات الخادم:</p>
                <p class="text-xs text-gray-500">PHP Version: <?php echo phpversion(); ?></p>
                <p class="text-xs text-gray-500">الوقت الحالي: <?php echo $current_time; ?></p>
            </div>
            <p class="text-sm text-gray-400">تم النشر بنجاح بواسطة نظام الاستضافة 🎉</p>
        </div>
    </div>
</body>
</html>';
                        break;
                        
                    case 'laravel':
                        $index_file = $site_folder . '/public/index.php';
                        $index_content = '<?php
/**
 * Laravel Application - ' . $site_name . '
 * يرجى رفع ملفات Laravel هنا
 */
echo "<h1>Laravel Application</h1>";
echo "<p>Site: ' . $site_name . '</p>";
echo "<p>Upload your Laravel files to this directory</p>";
';
                        break;
                        
                    case 'nodejs':
                        $index_file = $site_folder . '/app.js';
                        $index_content = '// Node.js Application - ' . $site_name . '
const express = require("express");
const app = express();
const port = process.env.PORT || 3000;

app.get("/", (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $site_name . '</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100">
            <div class="min-h-screen flex items-center justify-center">
                <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                    <h1 class="text-3xl font-bold text-green-600 mb-4">' . $site_name . '</h1>
                    <p class="text-gray-600 mb-6">Node.js Application</p>
                    <p class="text-sm text-gray-400">🚀 تم النشر بنجاح</p>
                </div>
            </div>
        </body>
        </html>
    `);
});

app.listen(port, () => {
    console.log(`Server running on port ${port}`);
});
';
                        
                        // إنشاء package.json لـ Node.js
                        $package_json = [
                            'name' => $folder_name,
                            'version' => '1.0.0',
                            'description' => $site_description,
                            'main' => 'app.js',
                            'scripts' => [
                                'start' => 'node app.js'
                            ],
                            'dependencies' => [
                                'express' => '^4.18.2'
                            ]
                        ];
                        file_put_contents($site_folder . '/package.json', json_encode($package_json, JSON_PRETTY_PRINT));
                        break;
                        
                    default: // html
                        $index_file = $site_folder . '/public/index.html';
                        $index_content = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($site_name) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="bg-white/10 backdrop-blur-lg p-8 rounded-2xl shadow-2xl text-center border border-white/20">
        <div class="w-24 h-24 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <span class="text-4xl text-white">🚀</span>
        </div>
        <h1 class="text-4xl font-bold text-white mb-4">' . htmlspecialchars($site_name) . '</h1>
        <p class="text-white/80 mb-6">' . htmlspecialchars($site_description ?: 'موقع قيد الإنشاء') . '</p>
        
        <div class="grid grid-cols-2 gap-4 max-w-sm mx-auto mb-6">
            <div class="bg-white/20 rounded-lg p-3">
                <div class="text-white font-bold">' . date('Y') . '</div>
                <div class="text-xs text-white/70">السنة</div>
            </div>
            <div class="bg-white/20 rounded-lg p-3">
                <div class="text-white font-bold">' . date('m/d') . '</div>
                <div class="text-xs text-white/70">التاريخ</div>
            </div>
        </div>
        
        <div class="bg-white/10 rounded-lg p-4">
            <p class="text-white/60 text-sm mb-2">روابط سريعة</p>
            <div class="flex items-center justify-center gap-4">
                <a href="#" class="text-white/80 hover:text-white transition-colors">📁 الملفات</a>
                <a href="#" class="text-white/80 hover:text-white transition-colors">📊 إحصائيات</a>
                <a href="#" class="text-white/80 hover:text-white transition-colors">⚙️ إعدادات</a>
            </div>
        </div>
        
        <p class="text-white/50 text-xs mt-6">تم النشر بواسطة نظام الاستضافة السحابية</p>
    </div>
</body>
</html>';
                        break;
                }
                
                if ($index_file && $index_content) {
                    file_put_contents($index_file, $index_content);
                }
                
                // حساب حجم المجلد
                $disk_usage = 0;
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($site_folder, RecursiveDirectoryIterator::SKIP_DOTS));
                foreach ($files as $file) {
                    $disk_usage += $file->getSize();
                }
                
                // حفظ في قاعدة البيانات
                $stmt = $db->prepare("
                    INSERT INTO hosting_sites (
                        site_name, domain, site_type, folder_path, description, disk_usage, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $site_name,
                    $site_domain,
                    $site_type,
                    $site_folder,
                    $site_description,
                    $disk_usage,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $site_id = $db->lastInsertId();
                
                // إضافة النطاق الرئيسي
                if ($use_ssl) {
                    $domain_stmt = $db->prepare("
                        INSERT INTO site_domains (site_id, domain, is_primary, ssl_enabled) 
                        VALUES (?, ?, TRUE, TRUE)
                    ");
                } else {
                    $domain_stmt = $db->prepare("
                        INSERT INTO site_domains (site_id, domain, is_primary) 
                        VALUES (?, ?, TRUE)
                    ");
                }
                $domain_stmt->execute([$site_id, $site_domain]);
                
                // إنشاء رابط الوصول
                $access_url = '/sites/' . $folder_name . '/public/';
                
                $message = "✅ تم استضافة الموقع بنجاح!";
                $message .= "<br><a href='$access_url' target='_blank' class='text-cyan-400 underline'>🔗 افتح الموقع</a>";
                
                break;
                
            case 'delete_site':
                // حذف موقع
                $site_id = $_POST['site_id'] ?? 0;
                
                if ($site_id) {
                    // جلب معلومات الموقع
                    $stmt = $db->prepare("SELECT folder_path FROM hosting_sites WHERE id = ?");
                    $stmt->execute([$site_id]);
                    $site = $stmt->fetch();
                    
                    if ($site && is_dir($site['folder_path'])) {
                        // حذف المجلد
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($site['folder_path'], RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        
                        foreach ($files as $fileinfo) {
                            if ($fileinfo->isDir()) {
                                rmdir($fileinfo->getRealPath());
                            } else {
                                unlink($fileinfo->getRealPath());
                            }
                        }
                        rmdir($site['folder_path']);
                    }
                    
                    // حذف من قاعدة البيانات
                    $db->prepare("DELETE FROM hosting_sites WHERE id = ?")->execute([$site_id]);
                    
                    $message = "✅ تم حذف الموقع بنجاح";
                }
                break;
                
            case 'toggle_status':
                // تغيير حالة الموقع
                $site_id = $_POST['site_id'] ?? 0;
                $status = $_POST['status'] ?? 'active';
                
                $stmt = $db->prepare("UPDATE hosting_sites SET status = ? WHERE id = ?");
                $stmt->execute([$status, $site_id]);
                
                $message = "✅ تم تغيير حالة الموقع";
                break;
        }
        
    } catch (Exception $e) {
        $error = "❌ خطأ: " . $e->getMessage();
    }
}

// =============================================
// جلب المواقع المستضافة
// =============================================
try {
    $sites = $db->query("
        SELECT h.*, 
               COUNT(d.id) as domains_count,
               SUM(CASE WHEN d.is_primary = 1 THEN 1 ELSE 0 END) as primary_domains
        FROM hosting_sites h
        LEFT JOIN site_domains d ON h.id = d.site_id
        GROUP BY h.id
        ORDER BY h.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $sites = [];
}

// =============================================
// إحصائيات سريعة
// =============================================
try {
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_sites,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_sites,
            SUM(CASE WHEN site_type = 'wordpress' THEN 1 ELSE 0 END) as wordpress_sites,
            SUM(CASE WHEN site_type = 'html' THEN 1 ELSE 0 END) as html_sites,
            SUM(CASE WHEN site_type = 'php' THEN 1 ELSE 0 END) as php_sites,
            SUM(disk_usage) as total_disk_usage,
            SUM(views) as total_views
        FROM hosting_sites
    ")->fetch();
} catch (Exception $e) {
    $stats = [
        'total_sites' => 0,
        'active_sites' => 0,
        'wordpress_sites' => 0,
        'html_sites' => 0,
        'php_sites' => 0,
        'total_disk_usage' => 0,
        'total_views' => 0
    ];
}


function getSiteTypeIcon($type) {
    $icons = [
        'wordpress' => '🔵',
        'html' => '🌐',
        'php' => '🐘',
        'laravel' => '🎯',
        'nodejs' => '🟢',
        'other' => '📦'
    ];
    return $icons[$type] ?? '📁';
}

function getSiteTypeText($type) {
    $texts = [
        'wordpress' => 'ووردبريس',
        'html' => 'HTML/CSS/JS',
        'php' => 'PHP',
        'laravel' => 'لارافيل',
        'nodejs' => 'Node.js',
        'other' => 'أخرى'
    ];
    return $texts[$type] ?? $type;
}
?>

<!-- ============================================= -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌐 إدارة استضافة المواقع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .card-gradient {
            background: linear-gradient(145deg, #1e293b, #0f172a);
        }
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.3);
        }
        .cyber-border {
            border: 1px solid rgba(6, 182, 212, 0.2);
        }
    </style>
</head>
<body class="gradient-bg text-white font-sans min-h-screen p-6">

<!-- ============================================= -->
<!-- حاوية الإشعارات -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<!-- ============================================= -->
<!-- رأس الصفحة والإحصائيات -->
<!-- ============================================= -->
<div class="max-w-7xl mx-auto">
    
    <!-- رأس الصفحة -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                <span class="text-3xl text-white">🌐</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-l from-cyan-400 to-blue-400 bg-clip-text text-transparent">
                    منصة استضافة المواقع
                </h1>
                <p class="text-gray-400 mt-1">قم باستضافة وتشغيل مواقعك بسهولة</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm transition-all">
                🔄 تحديث
            </button>
        </div>
    </div>
    
    <!-- رسائل النجاح/الخطأ -->
    <?php if ($message): ?>
    <div class="bg-green-600 bg-opacity-20 border border-green-600 text-green-400 p-4 rounded-lg mb-6 text-center">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="bg-red-600 bg-opacity-20 border border-red-600 text-red-400 p-4 rounded-lg mb-6 text-center">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="card-gradient rounded-xl p-6 cyber-border">
            <div class="text-sm text-gray-400 mb-1">إجمالي المواقع</div>
            <div class="text-3xl font-bold text-cyan-400"><?php echo $stats['total_sites']; ?></div>
            <div class="text-xs text-gray-500 mt-2">
                <span class="text-green-400"><?php echo $stats['active_sites']; ?></span> نشط
            </div>
        </div>
        
        <div class="card-gradient rounded-xl p-6 cyber-border">
            <div class="text-sm text-gray-400 mb-1">المساحة المستخدمة</div>
            <div class="text-3xl font-bold text-blue-400"><?php echo formatBytes($stats['total_disk_usage']); ?></div>
            <div class="text-xs text-gray-500 mt-2">
                إجمالي الملفات
            </div>
        </div>
        
        <div class="card-gradient rounded-xl p-6 cyber-border">
            <div class="text-sm text-gray-400 mb-1">عدد الزيارات</div>
            <div class="text-3xl font-bold text-purple-400"><?php echo number_format($stats['total_views']); ?></div>
            <div class="text-xs text-gray-500 mt-2">
                جميع المواقع
            </div>
        </div>
        
        <div class="card-gradient rounded-xl p-6 cyber-border">
            <div class="text-sm text-gray-400 mb-1">توزيع الأنواع</div>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400">ووردبريس: <?php echo $stats['wordpress_sites']; ?></span>
                <span class="text-xs text-gray-400">HTML: <?php echo $stats['html_sites']; ?></span>
                <span class="text-xs text-gray-400">PHP: <?php echo $stats['php_sites']; ?></span>
            </div>
        </div>
    </div>
    
    <!-- ============================================= -->
    <!-- نموذج إضافة موقع جديد -->
    <!-- ============================================= -->
    <div class="card-gradient rounded-2xl p-8 mb-8 cyber-border">
        <h2 class="text-xl font-bold mb-6 flex items-center">
            <span class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center ml-3">➕</span>
            إضافة موقع جديد
        </h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_site">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300">اسم الموقع</label>
                    <input type="text" name="site_name" required 
                           class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 text-white"
                           placeholder="مثلاً: موقعي الأول">
                    <p class="text-xs text-gray-500 mt-1">سيتم استخدامه لإنشاء المجلد</p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300">النطاق (Domain)</label>
                    <input type="text" name="site_domain" required 
                           class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 text-white"
                           placeholder="example.com">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300">نوع الموقع</label>
                    <select name="site_type" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg">
                        <option value="html">🌐 HTML/CSS/JS (موقع ثابت)</option>
                        <option value="php">🐘 PHP (موقع ديناميكي)</option>
                        <option value="wordpress">🔵 WordPress</option>
                        <option value="laravel">🎯 Laravel</option>
                        <option value="nodejs">🟢 Node.js</option>
                        <option value="other">📦 أخرى</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300">خيارات إضافية</label>
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <label class="flex items-center">
                            <input type="checkbox" name="use_ssl" class="ml-2">
                            <span class="text-sm text-gray-300">🔒 تفعيل SSL</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">وصف الموقع</label>
                <textarea name="site_description" rows="3" 
                          class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"
                          placeholder="وصف مختصر للموقع..."></textarea>
            </div>
            
            <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
                <h3 class="font-semibold mb-2 text-cyan-400">📁 هيكل الموقع</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-400">
                    <div>• public/ - الملفات العامة</div>
                    <div>• logs/ - سجلات الموقع</div>
                    <div>• backups/ - النسخ الاحتياطية</div>
                    <div>• config/ - ملفات الإعدادات</div>
                    <div>• .htaccess - قواعد إعادة التوجيه</div>
                </div>
            </div>
            
            <button type="submit" class="w-full md:w-auto px-8 py-4 bg-gradient-to-l from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 rounded-lg font-semibold transition-all text-lg">
                ✅ استضافة الموقع الآن
            </button>
        </form>
    </div>
    <!-- قائمة المواقع المستضافة -->
<div class="card-gradient rounded-2xl p-8 cyber-border">
    <h2 class="text-xl font-bold mb-6 flex items-center">
        <span class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center ml-3">📋</span>
        المواقع المستضافة (<?php echo count($sites); ?>)
    </h2>
    
    <?php if (empty($sites)): ?>
    <div class="text-center py-12">
        <div class="text-6xl text-gray-700 mb-4">🌐</div>
        <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد مواقع مستضافة</h3>
        <p class="text-gray-500">قم بإضافة موقع جديد للبدء</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <?php foreach ($sites as $site): 
            // ✅ قيم افتراضية لكل عمود
            $site_id = $site['id'] ?? 0;
            $site_name = $site['site_name'] ?? $site['name'] ?? 'موقع بدون اسم';
            $site_folder = !empty($site['folder_path']) ? basename($site['folder_path']) : 'site_' . $site_id;
            $site_url = $site_folder ? '/sites/' . $site_folder . '/public/' : '#';
            $site_type = $site['site_type'] ?? 'html';
            $site_domain = $site['domain'] ?? $site['domain_name'] ?? 'لا يوجد نطاق';
            $site_description = $site['description'] ?? '';
            $site_status = $site['status'] ?? 'active';
            $site_disk_usage = $site['disk_usage'] ?? 0;
            $site_views = $site['views'] ?? 0;
            $site_created = $site['created_at'] ?? date('Y-m-d');
            
            // تحديد لون الحالة
            $status_color = $site_status == 'active' ? 'text-green-400' : ($site_status == 'pending' ? 'text-yellow-400' : 'text-gray-400');
            $status_bg = $site_status == 'active' ? 'bg-green-600/20' : ($site_status == 'pending' ? 'bg-yellow-600/20' : 'bg-gray-600/20');
            
            // أيقونة حسب نوع الموقع
            $type_icon = match($site_type) {
                'wordpress' => '🔵',
                'php' => '🐘',
                'laravel' => '🎯',
                'nodejs' => '🟢',
                'html', 'static' => '🌐',
                default => '📁'
            };
        ?>
        <div class="bg-gray-800/50 rounded-xl p-5 border border-gray-700 hover:border-cyan-700 transition-all">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center">
                    <span class="text-2xl ml-3"><?php echo $type_icon; ?></span>
                    <div>
                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($site_name); ?></h3>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($site_domain); ?></p>
                    </div>
                </div>
                <span class="px-3 py-1 <?php echo $status_bg; ?> <?php echo $status_color; ?> text-xs rounded-full">
                    <?php 
                    echo match($site_status) {
                        'active' => 'نشط',
                        'pending' => 'قيد الانتظار',
                        'suspended' => 'موقوف',
                        default => $site_status
                    };
                    ?>
                </span>
            </div>
            
            <?php if ($site_description): ?>
            <p class="text-sm text-gray-400 mb-3"><?php echo htmlspecialchars($site_description); ?></p>
            <?php endif; ?>
            
            <div class="grid grid-cols-3 gap-2 mb-3 text-xs">
                <div class="bg-gray-700/50 rounded p-2 text-center">
                    <span class="text-gray-400">النوع</span>
                    <div class="font-semibold">
                        <?php 
                        echo match($site_type) {
                            'wordpress' => 'ووردبريس',
                            'php' => 'PHP',
                            'laravel' => 'لارافيل',
                            'nodejs' => 'Node.js',
                            'html', 'static' => 'HTML/CSS',
                            default => 'أخرى'
                        };
                        ?>
                    </div>
                </div>
                <div class="bg-gray-700/50 rounded p-2 text-center">
                    <span class="text-gray-400">المساحة</span>
                    <div class="font-semibold"><?php echo formatBytes($site_disk_usage); ?></div>
                </div>
                <div class="bg-gray-700/50 rounded p-2 text-center">
                    <span class="text-gray-400">الزيارات</span>
                    <div class="font-semibold"><?php echo number_format($site_views); ?></div>
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <?php if ($site_folder && $site_status == 'active'): ?>
                    <a href="<?php echo $site_url; ?>" target="_blank" 
                       class="px-3 py-1 bg-cyan-600 hover:bg-cyan-700 rounded-lg text-sm transition-colors flex items-center">
                        <span class="ml-1">🔗</span> فتح
                    </a>
                    <?php else: ?>
                    <span class="px-3 py-1 bg-gray-700 rounded-lg text-sm text-gray-500 cursor-not-allowed flex items-center">
                        <span class="ml-1">🔗</span> غير متاح
                    </span>
                    <?php endif; ?>
                    
                    <button onclick="openFileManager('<?php echo $site_folder; ?>')" 
                            class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-colors">
                        📁 الملفات
                    </button>
                    
                    <button onclick="showSiteStats(<?php echo $site_id; ?>)" 
                            class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-colors">
                        📊 إحصائيات
                    </button>
                </div>
                
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="text-xs text-gray-500"><?php echo date('Y-m-d', strtotime($site_created)); ?></span>
                    
                    <div class="relative group">
                        <button class="text-gray-400 hover:text-white">⋮</button>
                        <div class="absolute left-0 bottom-full mb-2 hidden group-hover:block bg-gray-800 rounded-lg shadow-xl py-1 min-w-[120px] z-10">
                            <form method="POST" class="block">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
                                <input type="hidden" name="status" value="<?php echo $site_status == 'active' ? 'inactive' : 'active'; ?>">
                                <button type="submit" class="w-full text-right px-4 py-2 text-sm hover:bg-gray-700">
                                    <?php echo $site_status == 'active' ? '⏸️ تعطيل' : '▶️ تفعيل'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" class="block" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموقع؟');">
                                <input type="hidden" name="action" value="delete_site">
                                <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
                                <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-400 hover:bg-gray-700">
                                    🗑️ حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
    
    <!-- ============================================= -->
    <!-- معلومات إضافية -->
    <!-- ============================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <div class="card-gradient rounded-xl p-6 cyber-border">
            <h3 class="font-bold mb-4 flex items-center">
                <span class="w-6 h-6 bg-cyan-600 rounded flex items-center justify-center ml-2 text-sm">📌</span>
                كيفية رفع الملفات
            </h3>
            <ul class="space-y-2 text-sm text-gray-400">
                <li>• الملفات العامة توضع في مجلد <code class="bg-gray-800 px-2 py-1 rounded">public/</code></li>
                <li>• الصفحة الرئيسية تكون <code class="bg-gray-800 px-2 py-1 rounded">index.html</code> أو <code class="bg-gray-800 px-2 py-1 rounded">index.php</code></li>
                <li>• يمكنك استخدام FTP أو مدير الملفات لرفع الملفات</li>
                <li>• مسار الموقع بعد الرفع: <code class="bg-gray-800 px-2 py-1 rounded">/sites/اسم_الموقع/public/</code></li>
            </ul>
        </div>
        
        <div class="card-gradient rounded-xl p-6 cyber-border">
            <h3 class="font-bold mb-4 flex items-center">
                <span class="w-6 h-6 bg-purple-600 rounded flex items-center justify-center ml-2 text-sm">⚡</span>
                أنواع المواقع المدعومة
            </h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-gray-800/50 rounded p-2">
                    <span class="text-cyan-400">🌐 HTML/CSS/JS</span>
                    <p class="text-gray-400 text-xs">مواقع ثابتة</p>
                </div>
                <div class="bg-gray-800/50 rounded p-2">
                    <span class="text-green-400">🐘 PHP</span>
                    <p class="text-gray-400 text-xs">مواقع ديناميكية</p>
                </div>
                <div class="bg-gray-800/50 rounded p-2">
                    <span class="text-blue-400">🔵 WordPress</span>
                    <p class="text-gray-400 text-xs">نظام إدارة محتوى</p>
                </div>
                <div class="bg-gray-800/50 rounded p-2">
                    <span class="text-red-400">🎯 Laravel</span>
                    <p class="text-gray-400 text-xs">إطار عمل PHP</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
function refreshPage() {
    location.reload();
}

function openFileManager(folder) {
    window.location.href = '?page=files&path=' + folder;
}

function showSiteStats(siteId) {
    alert('عرض إحصائيات الموقع ' + siteId);
    // هنا يمكن إضافة عرض الإحصائيات
}

function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg text-sm mb-2 animate-pulse`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// معاينة الموقع قبل النشر
function previewSite() {
    const name = document.querySelector('[name="site_name"]').value;
    const type = document.querySelector('[name="site_type"]').value;
    
    if (!name) {
        showNotification('الرجاء إدخال اسم الموقع', 'error');
        return;
    }
    
    showNotification('جاري تجهيز المعاينة...', 'info');
    setTimeout(() => {
        window.open('#', '_blank');
    }, 1000);
}
</script>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
</body>
</html>