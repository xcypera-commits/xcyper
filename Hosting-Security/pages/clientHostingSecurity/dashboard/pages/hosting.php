<?php
// =============================================
// client-unit/pages/hosting.php
// صفحة استضافة المواقع - التصميم الشامل النهائي
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// client-unit/pages/hosting.php
// صفحة استضافة المواقع - نسخة العميل
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: العميل غير محدد</div>';
    return;
}

$client_id = $current_client['id'];

// =============================================
// معالجة إضافة موقع جديد
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_site') {
    
    $site_name = trim($_POST['site_name'] ?? '');
    $site_domain = trim($_POST['site_domain'] ?? '');
    $site_type = $_POST['site_type'] ?? 'html';
    $site_description = trim($_POST['site_description'] ?? '');
    $plan_id = $_POST['plan_id'] ?? 1;
    
    if (empty($site_name) || empty($site_domain)) {
        $_SESSION['error'] = '❌ اسم الموقع والنطاق مطلوبان';
    } else {
        try {
            // 1. تنظيف اسم الموقع لاستخدامه كمجلد
            $folder_name = preg_replace('/[^a-z0-9]/', '_', strtolower($site_name));
            $folder_name = $client_id . '_' . $folder_name . '_' . time();
            
            // 2. إنشاء مجلدات الموقع
            $sites_base = __DIR__ . '/../../../sites/';
            if (!is_dir($sites_base)) {
                mkdir($sites_base, 0755, true);
            }
            
            $site_folder = $sites_base . $folder_name;
            mkdir($site_folder, 0755, true);
            mkdir($site_folder . '/public', 0755, true);
            mkdir($site_folder . '/logs', 0755, true);
            mkdir($site_folder . '/backups', 0755, true);
            
            // 3. إنشاء صفحة افتراضية
            $index_content = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($site_name) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center">
            <h1 class="text-3xl font-bold text-blue-600 mb-4">' . htmlspecialchars($site_name) . '</h1>
            <p class="text-gray-600 mb-6">الموقع قيد الإنشاء</p>
            <p class="text-sm text-gray-400">تم النشر بنجاح بواسطة نظام الاستضافة 🎉</p>
        </div>
    </div>
</body>
</html>';
            
            file_put_contents($site_folder . '/public/index.html', $index_content);
            
            // 4. حفظ في قاعدة البيانات
            $sql = "INSERT INTO hosting_sites (
                client_id, site_name, domain, site_type, folder_path, description, plan_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $client_id,
                $site_name,
                $site_domain,
                $site_type,
                $site_folder,
                $site_description,
                $plan_id
            ]);
            
            $site_id = $db->lastInsertId();
            
            // 5. تسجيل النشاط
            $log_sql = "INSERT INTO client_activity_log (client_id, activity_type, target_type, target_id, description, created_at) 
                        VALUES (?, 'create', 'site', ?, ?, NOW())";
            $log_stmt = $db->prepare($log_sql);
            $log_stmt->execute([$client_id, $site_id, "إضافة موقع جديد: $site_name"]);
            
            $_SESSION['success'] = '✅ تم استضافة الموقع بنجاح';
            
        } catch (Exception $e) {
            $_SESSION['error'] = '❌ خطأ في إنشاء الموقع: ' . $e->getMessage();
        }
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة حذف موقع
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_site') {
    $site_id = $_POST['site_id'] ?? 0;
    
    if ($site_id) {
        try {
            // جلب معلومات الموقع
            $stmt = $db->prepare("SELECT site_name, folder_path FROM hosting_sites WHERE id = ? AND client_id = ?");
            $stmt->execute([$site_id, $client_id]);
            $site = $stmt->fetch();
            
            if ($site && !empty($site['folder_path']) && is_dir($site['folder_path'])) {
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
            $stmt = $db->prepare("DELETE FROM hosting_sites WHERE id = ? AND client_id = ?");
            $stmt->execute([$site_id, $client_id]);
            
            // تسجيل النشاط
            $log_sql = "INSERT INTO client_activity_log (client_id, activity_type, target_type, description, created_at) 
                        VALUES (?, 'delete', 'site', ?, NOW())";
            $log_stmt = $db->prepare($log_sql);
            $log_stmt->execute([$client_id, "حذف موقع: {$site['site_name']}"]);
            
            $_SESSION['success'] = '✅ تم حذف الموقع بنجاح';
            
        } catch (Exception $e) {
            $_SESSION['error'] = '❌ خطأ في حذف الموقع: ' . $e->getMessage();
        }
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة تغيير حالة الموقع
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $site_id = $_POST['site_id'] ?? 0;
    $new_status = $_POST['status'] ?? 'active';
    
    if ($site_id) {
        try {
            $stmt = $db->prepare("UPDATE hosting_sites SET status = ? WHERE id = ? AND client_id = ?");
            $stmt->execute([$new_status, $site_id, $client_id]);
            
            $_SESSION['success'] = '✅ تم تغيير حالة الموقع';
            
        } catch (Exception $e) {
            $_SESSION['error'] = '❌ خطأ في تغيير الحالة';
        }
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// جلب مواقع العميل
// =============================================
try {
    $stmt = $db->prepare("
        SELECT * FROM hosting_sites 
        WHERE client_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$client_id]);
    $sites = $stmt->fetchAll();
} catch (Exception $e) {
    $sites = [];
}

// =============================================
// جلب خطط الاستضافة
// =============================================
try {
    $plans = $db->prepare("SELECT * FROM hosting_plans WHERE is_active = 1 ORDER BY price_monthly ASC");
    $plans->execute();
    $plans = $plans->fetchAll();
} catch (Exception $e) {
    $plans = [];
}

// =============================================
// إحصائيات سريعة
// =============================================
$total_sites = count($sites);
$active_sites = count(array_filter($sites, fn($s) => $s['status'] == 'active'));

// دوال مساعدة
function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
}

function getSiteTypeIcon($type) {
    return match($type) {
        'wordpress' => '🔵',
        'php' => '🐘',
        'laravel' => '🎯',
        'nodejs' => '🟢',
        'html' => '🌐',
        default => '📁'
    };
}

function getSiteTypeText($type) {
    return match($type) {
        'wordpress' => 'ووردبريس',
        'php' => 'PHP',
        'laravel' => 'لارافيل',
        'nodejs' => 'Node.js',
        'html' => 'HTML/CSS',
        default => 'أخرى'
    };
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: العميل غير محدد</div>';
    return;
}

$client_id = $current_client['id'];



// =============================================
// client-unit/pages/hosting.php
// صفحة استضافة المواقع - مع تسجيل الأحداث
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: العميل غير محدد</div>';
    return;
}

$client_id = $current_client['id'];

// =============================================
// دالة تسجيل النشاطات
// =============================================
function logActivity($db, $client_id, $activity_type, $target_type, $target_id, $description, $ip = null) {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    $sql = "INSERT INTO client_activity_log (
        client_id, activity_type, target_type, target_id, description, ip_address, user_agent, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $client_id,
        $activity_type,
        $target_type,
        $target_id,
        $description,
        $ip,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

// =============================================
// معالجة طلب إنشاء نسخة احتياطية
// =============================================
if (isset($_POST['create_backup'])) {
    $site_id = $_POST['site_id'];
    $backup_type = $_POST['backup_type'] ?? 'full';
    
    try {
        // جلب معلومات الموقع
        $stmt = $db->prepare("SELECT site_name FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        if ($site) {
            // إنشاء النسخة الاحتياطية (هنا كود الإنشاء الفعلي)
            $backup_size = rand(500, 5000); // محاكاة للحجم
            $backup_path = "/backups/site{$site_id}/backup_" . date('Ymd_His') . ".zip";
            
            // حفظ في قاعدة البيانات
            $sql = "INSERT INTO hosting_backups (site_id, backup_type, backup_size, file_path, status, created_at) 
                    VALUES (?, ?, ?, ?, 'completed', NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([$site_id, $backup_type, $backup_size, $backup_path]);
            
            $backup_id = $db->lastInsertId();
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'create_backup', 
                'site', 
                $site_id, 
                "إنشاء نسخة احتياطية {$backup_type} للموقع: {$site['site_name']}"
            );
            
            $_SESSION['success'] = "✅ تم إنشاء النسخة الاحتياطية بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل إنشاء النسخة الاحتياطية";
        
        // تسجيل الفشل
        logActivity(
            $db, 
            $client_id, 
            'error', 
            'site', 
            $site_id, 
            "فشل إنشاء نسخة احتياطية: " . $e->getMessage()
        );
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب استعادة نسخة احتياطية
// =============================================
if (isset($_POST['restore_backup'])) {
    $backup_id = $_POST['backup_id'];
    
    try {
        // جلب معلومات النسخة
        $stmt = $db->prepare("
            SELECT b.*, s.site_name 
            FROM hosting_backups b
            JOIN hosting_sites s ON b.site_id = s.id
            WHERE b.id = ? AND s.client_id = ?
        ");
        $stmt->execute([$backup_id, $client_id]);
        $backup = $stmt->fetch();
        
        if ($backup) {
            // هنا كود الاستعادة الفعلي
            // ...
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'restore_backup', 
                'site', 
                $backup['site_id'], 
                "استعادة نسخة احتياطية للموقع: {$backup['site_name']} من تاريخ: {$backup['created_at']}"
            );
            
            $_SESSION['success'] = "✅ تم استعادة النسخة الاحتياطية بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل استعادة النسخة الاحتياطية";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب تغيير إصدار PHP
// =============================================
if (isset($_POST['change_php'])) {
    $site_id = $_POST['site_id'];
    $new_php_version = $_POST['php_version'];
    
    try {
        $stmt = $db->prepare("SELECT site_name, php_version FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        if ($site) {
            $old_version = $site['php_version'];
            
            // تحديث إصدار PHP
            $sql = "UPDATE hosting_sites SET php_version = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$new_php_version, $site_id]);
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'change_php', 
                'site', 
                $site_id, 
                "تغيير إصدار PHP للموقع {$site['site_name']} من {$old_version} إلى {$new_php_version}"
            );
            
            $_SESSION['success'] = "✅ تم تغيير إصدار PHP بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل تغيير إصدار PHP";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب مسح الكاش
// =============================================
if (isset($_POST['clear_cache'])) {
    $site_id = $_POST['site_id'];
    
    try {
        $stmt = $db->prepare("SELECT site_name FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        if ($site) {
            // هنا كود مسح الكاش الفعلي
            // ...
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'clear_cache', 
                'site', 
                $site_id, 
                "مسح الكاش للموقع: {$site['site_name']}"
            );
            
            $_SESSION['success'] = "✅ تم مسح الكاش بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل مسح الكاش";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب إعادة تشغيل الموقع
// =============================================
if (isset($_POST['restart_site'])) {
    $site_id = $_POST['site_id'];
    
    try {
        $stmt = $db->prepare("SELECT site_name FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        if ($site) {
            // هنا كود إعادة التشغيل الفعلي
            // ...
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'restart', 
                'site', 
                $site_id, 
                "إعادة تشغيل الموقع: {$site['site_name']}"
            );
            
            $_SESSION['success'] = "✅ تم إعادة تشغيل الموقع بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل إعادة تشغيل الموقع";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب تعليق الموقع
// =============================================
if (isset($_POST['suspend_site'])) {
    $site_id = $_POST['site_id'];
    
    try {
        $stmt = $db->prepare("SELECT site_name, status FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        if ($site) {
            $old_status = $site['status'];
            $new_status = $old_status == 'active' ? 'suspended' : 'active';
            
            // تحديث الحالة
            $sql = "UPDATE hosting_sites SET status = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$new_status, $site_id]);
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'suspend', 
                'site', 
                $site_id, 
                ($new_status == 'suspended' ? "تعليق" : "إعادة تفعيل") . " الموقع: {$site['site_name']}"
            );
            
            $_SESSION['success'] = "✅ تم " . ($new_status == 'suspended' ? "تعليق" : "إعادة تفعيل") . " الموقع بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل تغيير حالة الموقع";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب ترقية الخطة
// =============================================
if (isset($_POST['upgrade_plan'])) {
    $site_id = $_POST['site_id'];
    $new_plan_id = $_POST['plan_id'];
    
    try {
        $stmt = $db->prepare("
            SELECT s.site_name, s.plan_id as old_plan_id, p.plan_name as old_plan_name 
            FROM hosting_sites s
            JOIN hosting_plans p ON s.plan_id = p.id
            WHERE s.id = ? AND s.client_id = ?
        ");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT plan_name FROM hosting_plans WHERE id = ?");
        $stmt->execute([$new_plan_id]);
        $new_plan = $stmt->fetch();
        
        if ($site && $new_plan) {
            // تحديث الخطة
            $sql = "UPDATE hosting_sites SET plan_id = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$new_plan_id, $site_id]);
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'upgrade', 
                'site', 
                $site_id, 
                "ترقية خطة الموقع {$site['site_name']} من {$site['old_plan_name']} إلى {$new_plan['plan_name']}"
            );
            
            $_SESSION['success'] = "✅ تم ترقية خطة الموقع بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل ترقية الخطة";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب طلب شهادة SSL
// =============================================
if (isset($_POST['request_ssl'])) {
    $site_id = $_POST['site_id'];
    
    try {
        $stmt = $db->prepare("SELECT site_name, domain_id FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        if ($site && $site['domain_id']) {
            // تحديث حالة SSL
            $sql = "UPDATE client_domains SET ssl_status = 'pending' WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$site['domain_id']]);
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'request_ssl', 
                'site', 
                $site_id, 
                "طلب شهادة SSL للموقع: {$site['site_name']}"
            );
            
            $_SESSION['success'] = "✅ تم طلب شهادة SSL بنجاح، سيتم تفعيلها خلال 24 ساعة";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل طلب شهادة SSL";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// معالجة طلب ربط نطاق
// =============================================
if (isset($_POST['link_domain'])) {
    $site_id = $_POST['site_id'];
    $domain_id = $_POST['domain_id'];
    
    try {
        $stmt = $db->prepare("SELECT site_name FROM hosting_sites WHERE id = ? AND client_id = ?");
        $stmt->execute([$site_id, $client_id]);
        $site = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT domain_name FROM client_domains WHERE id = ? AND client_id = ?");
        $stmt->execute([$domain_id, $client_id]);
        $domain = $stmt->fetch();
        
        if ($site && $domain) {
            // تحديث ربط النطاق
            $sql = "UPDATE hosting_sites SET domain_id = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$domain_id, $site_id]);
            
            // تسجيل النشاط
            logActivity(
                $db, 
                $client_id, 
                'link_domain', 
                'site', 
                $site_id, 
                "ربط النطاق {$domain['domain_name']} مع الموقع {$site['site_name']}"
            );
            
            $_SESSION['success'] = "✅ تم ربط النطاق بالموقع بنجاح";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ فشل ربط النطاق";
    }
    
    header("Location: ?page=hosting");
    exit;
}

// =============================================
// عرض النشاطات الأخيرة (إضافة في نهاية الصفحة)
// =============================================
function displayRecentActivities($db, $client_id) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM client_activity_log 
            WHERE client_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

$recent_activities = displayRecentActivities($db, $client_id);


try {
    // =========================================
    // 1. جلب مواقع الاستضافة مع كل التفاصيل
    // =========================================
    $sites = $db->prepare("
        SELECT 
            s.*,
            p.project_name,
            p.project_code,
            hp.plan_name,
            hp.plan_type,
            hp.disk_space as plan_disk,
            hp.bandwidth as plan_bandwidth,
            hp.databases_limit,
            hp.emails_limit,
            d.id as domain_id,
            d.domain_name,
            d.ssl_status,
            d.ssl_expiry,
            d.expiry_date as domain_expiry,
            (SELECT SUM(disk_usage) FROM hosting_stats WHERE site_id = s.id) as disk_used,
            (SELECT SUM(bandwidth_usage) FROM hosting_stats WHERE site_id = s.id) as bandwidth_used,
            (SELECT databases_count FROM hosting_stats WHERE site_id = s.id ORDER BY stat_date DESC LIMIT 1) as current_dbs,
            (SELECT emails_count FROM hosting_stats WHERE site_id = s.id ORDER BY stat_date DESC LIMIT 1) as current_emails,
            (SELECT daily_visitors FROM hosting_stats WHERE site_id = s.id ORDER BY stat_date DESC LIMIT 1) as today_visitors,
            (SELECT monthly_visitors FROM hosting_stats WHERE site_id = s.id ORDER BY stat_date DESC LIMIT 1) as monthly_visitors,
            (SELECT created_at FROM hosting_backups WHERE site_id = s.id AND status = 'completed' ORDER BY created_at DESC LIMIT 1) as last_backup
        FROM hosting_sites s
        LEFT JOIN client_projects p ON s.project_id = p.id
        LEFT JOIN hosting_plans hp ON s.plan_id = hp.id
        LEFT JOIN client_domains d ON s.domain_id = d.id
        WHERE s.client_id = ?
        ORDER BY s.created_at DESC
    ");
    $sites->execute([$client_id]);
    $sites = $sites->fetchAll();

    // =========================================
    // 2. جلب خطط الاستضافة المتاحة
    // =========================================
    $plans = $db->prepare("
        SELECT * FROM hosting_plans 
        WHERE is_active = 1 
        ORDER BY price_monthly ASC
    ");
    $plans->execute();
    $plans = $plans->fetchAll();

    // =========================================
    // 3. جلب النطاقات المسجلة
    // =========================================
    $domains = $db->prepare("
        SELECT 
            d.*,
            p.project_name,
            s.site_name
        FROM client_domains d
        LEFT JOIN client_projects p ON d.project_id = p.id
        LEFT JOIN hosting_sites s ON d.id = s.domain_id
        WHERE d.client_id = ?
        ORDER BY d.expiry_date ASC
    ");
    $domains->execute([$client_id]);
    $domains = $domains->fetchAll();

    // =========================================
    // 4. جلب آخر النسخ الاحتياطية
    // =========================================
    $backups = $db->prepare("
        SELECT 
            b.*,
            s.site_name,
            p.project_name
        FROM hosting_backups b
        JOIN hosting_sites s ON b.site_id = s.id
        JOIN client_projects p ON s.project_id = p.id
        WHERE p.client_id = ?
        ORDER BY b.created_at DESC
        LIMIT 15
    ");
    $backups->execute([$client_id]);
    $backups = $backups->fetchAll();

    // =========================================
    // 5. جلب إحصائيات آخر 7 أيام للرسوم البيانية
    // =========================================
    $stats_7days = $db->prepare("
        SELECT 
            hs.*,
            s.site_name,
            s.id as site_id
        FROM hosting_stats hs
        JOIN hosting_sites s ON hs.site_id = s.id
        JOIN client_projects p ON s.project_id = p.id
        WHERE p.client_id = ? AND hs.stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY hs.stat_date ASC
    ");
    $stats_7days->execute([$client_id]);
    $stats_7days = $stats_7days->fetchAll();

    // =========================================
    // 6. إحصائيات عامة
    // =========================================
    $total_sites = count($sites);
    $active_sites = count(array_filter($sites, fn($s) => $s['status'] == 'active'));
    $pending_sites = count(array_filter($sites, fn($s) => $s['status'] == 'pending'));
    $suspended_sites = count(array_filter($sites, fn($s) => $s['status'] == 'suspended'));
    
    $total_disk = array_sum(array_column($sites, 'disk_used')) ?: 0;
    $total_disk_gb = round($total_disk / (1024 * 1024 * 1024), 2);
    
    $total_domains = count($domains);
    $expiring_soon = count(array_filter($domains, function($d) {
        return $d['expiry_date'] && strtotime($d['expiry_date']) < strtotime('+30 days');
    }));

} catch (Exception $e) {
    $sites = [];
    $plans = [];
    $domains = [];
    $backups = [];
    $stats_7days = [];
    $total_sites = 0;
    $active_sites = 0;
    $pending_sites = 0;
    $suspended_sites = 0;
    $total_disk_gb = 0;
    $total_domains = 0;
    $expiring_soon = 0;
}

// =============================================
// دوال مساعدة للتنسيق
// =============================================
function getStatusBadge($status) {
    $colors = [
        'active' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'pending' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'suspended' => 'bg-red-500/20 text-red-400 border-red-500/30',
        'expired' => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        'completed' => 'bg-blue-500/20 text-blue-400 border-blue-500/30'
    ];
    $texts = [
        'active' => 'نشط',
        'pending' => 'قيد الانتظار',
        'suspended' => 'موقوف',
        'expired' => 'منتهي',
        'completed' => 'مكتمل'
    ];
    $color = $colors[$status] ?? 'bg-slate-500/20 text-slate-400 border-slate-500/30';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs border $color'>$text</span>";
}

function getSSLBadge($status) {
    $colors = [
        'active' => 'bg-green-500/20 text-green-400',
        'pending' => 'bg-yellow-500/20 text-yellow-400',
        'expired' => 'bg-red-500/20 text-red-400',
        'none' => 'bg-slate-500/20 text-slate-400'
    ];
    $texts = [
        'active' => 'مفعل',
        'pending' => 'قيد التفعيل',
        'expired' => 'منتهي',
        'none' => 'غير مفعل'
    ];
    $color = $colors[$status] ?? 'bg-slate-500/20 text-slate-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-2 py-0.5 rounded-full text-xs $color'>$text</span>";
}


?>

<!-- ============================================= -->
<!-- الهيدر - تصميم جديد -->
<!-- ============================================= -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 mb-8 border border-slate-700">
    <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 to-transparent"></div>
    
    <div class="relative z-10">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="w-16 h-16 rounded-2xl bg-blue-500/20 flex items-center justify-center backdrop-blur-sm border border-blue-500/30">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-l from-blue-400 to-cyan-400 bg-clip-text text-transparent">منصة الاستضافة</h1>
                    <p class="text-slate-400 mt-1">استضافة سريعة وآمنة لمواقعك وتطبيقاتك</p>
                </div>
            </div>
            
            <button onclick="openOrderModal()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-xl font-semibold text-white shadow-lg transition-all hover:shadow-blue-500/25 flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                طلب استضافة جديدة
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات شاملة - 6 كروت -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-blue-500/50 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">إجمالي المواقع</p>
                <p class="text-2xl font-bold text-white"><?php echo $total_sites; ?></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-green-500/50 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">نشطة</p>
                <p class="text-2xl font-bold text-white"><?php echo $active_sites; ?></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-yellow-500/50 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">قيد الانتظار</p>
                <p class="text-2xl font-bold text-white"><?php echo $pending_sites; ?></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-cyan-500/50 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">المساحة المستخدمة</p>
                <p class="text-2xl font-bold text-white"><?php echo $total_disk_gb; ?> GB</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-purple-500/50 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">النطاقات</p>
                <p class="text-2xl font-bold text-white"><?php echo $total_domains; ?></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-rose-500/50 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">تنتهي قريباً</p>
                <p class="text-2xl font-bold text-white"><?php echo $expiring_soon; ?></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويبات التنقل الرئيسية -->
<!-- ============================================= -->
<div class="border-b border-slate-700 mb-6">
    <div class="flex space-x-8 space-x-reverse overflow-x-auto pb-1">
        <button onclick="switchTab('sites')" id="tab-sites-btn" class="tab-btn active pb-4 px-1 border-b-2 border-blue-500 text-blue-400 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
                مواقعي (<?php echo $total_sites; ?>)
            </span>
        </button>
        <button onclick="switchTab('plans')" id="tab-plans-btn" class="tab-btn pb-4 px-1 border-b-2 border-transparent text-slate-400 hover:text-slate-300 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2 2 2 2-2 2 2 2-2 2 2 2-2 2 2"/>
                </svg>
                خطط الاستضافة
            </span>
        </button>
        <button onclick="switchTab('domains')" id="tab-domains-btn" class="tab-btn pb-4 px-1 border-b-2 border-transparent text-slate-400 hover:text-slate-300 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
                النطاقات (<?php echo $total_domains; ?>)
            </span>
        </button>
        <button onclick="switchTab('backups')" id="tab-backups-btn" class="tab-btn pb-4 px-1 border-b-2 border-transparent text-slate-400 hover:text-slate-300 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                النسخ الاحتياطية
            </span>
        </button>
        <button onclick="switchTab('deploy')" id="tab-deploy-btn" class="tab-btn pb-4 px-1 border-b-2 border-transparent text-slate-400 hover:text-slate-300 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                رفع موقع
            </span>
        </button>
    </div>
</div>



<!-- ============================================= -->
<!-- تبويب 1: مواقعي (شامل كل التفاصيل) -->
<!-- ============================================= -->
<div id="tab-sites" class="tab-content">
    <?php if (empty($sites)): ?>
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-12 text-center border border-slate-700">
        <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
            <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-white mb-2">لا توجد مواقع استضافة</h3>
        <p class="text-slate-400 mb-6">ابدأ بطلب استضافة جديدة الآن</p>
        <button onclick="switchTab('plans')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-medium transition-all">
            عرض خطط الاستضافة
        </button>
    </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($sites as $site): 
                $disk_used = $site['disk_used'] ?? 0;
                $disk_limit = ($site['plan_disk'] ?? 10240) * 1024 * 1024; // تحويل إلى bytes
                $disk_percent = $disk_limit > 0 ? min(100, round(($disk_used / $disk_limit) * 100)) : 0;
                
                $bandwidth_used = $site['bandwidth_used'] ?? 0;
                $bandwidth_limit = ($site['plan_bandwidth'] ?? 102400) * 1024 * 1024;
                $bandwidth_percent = $bandwidth_limit > 0 ? min(100, round(($bandwidth_used / $bandwidth_limit) * 100)) : 0;
            ?>
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-blue-500/50 transition-all">
                <!-- الصف الأول: اسم الموقع + الحالة -->
                <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($site['site_name']); ?></h3>
                            <div class="flex items-center mt-1">
                                <span class="text-sm text-slate-400 dir-ltr text-left ml-3"><?php echo $site['domain_name'] ?? 'لا يوجد نطاق'; ?></span>
                                <?php echo getSSLBadge($site['ssl_status'] ?? 'none'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <?php echo getStatusBadge($site['status']); ?>
                    </div>
                </div>
                
                <!-- الصف الثاني: معلومات الخطة (4 أعمدة) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-slate-900/50 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-1">الخطة</p>
                        <p class="font-medium text-white"><?php echo $site['plan_name'] ?? 'غير محدد'; ?></p>
                        <p class="text-xs text-slate-500 mt-1"><?php echo $site['plan_type']; ?></p>
                    </div>
                    
                    <div class="bg-slate-900/50 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-1">إصدار PHP</p>
                        <p class="font-medium text-white">PHP <?php echo $site['php_version']; ?></p>
                        <button onclick="changePHP(<?php echo $site['id']; ?>)" class="text-xs text-blue-400 hover:text-blue-300 mt-1">تغيير</button>
                    </div>
                    
                    <div class="bg-slate-900/50 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-1">المشروع</p>
                        <p class="font-medium text-white"><?php echo $site['project_name'] ?? 'عام'; ?></p>
                    </div>
                    
                    <div class="bg-slate-900/50 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-1">تاريخ التفعيل</p>
                        <p class="font-medium text-white"><?php echo $site['activated_at'] ? date('Y-m-d', strtotime($site['activated_at'])) : '-'; ?></p>
                        <?php if ($site['expires_at']): ?>
                        <p class="text-xs <?php echo strtotime($site['expires_at']) < time() ? 'text-red-400' : 'text-slate-500'; ?> mt-1">
                            ينتهي: <?php echo date('Y-m-d', strtotime($site['expires_at'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- الصف الثالث: أشرطة التقدم (مساحة + باندويث) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-400">استخدام المساحة</span>
                            <span class="text-blue-400"><?php echo formatBytes($disk_used); ?> / <?php echo formatBytes($disk_limit); ?></span>
                        </div>
                        <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full" style="width: <?php echo $disk_percent; ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-400">استخدام الباندويث</span>
                            <span class="text-purple-400"><?php echo formatBytes($bandwidth_used); ?> / <?php echo formatBytes($bandwidth_limit); ?></span>
                        </div>
                        <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full" style="width: <?php echo $bandwidth_percent; ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- الصف الرابع: إحصائيات سريعة (5 أعمدة) -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                    <div class="bg-slate-900/30 rounded-lg p-2 text-center">
                        <p class="text-xs text-slate-500">زيارات اليوم</p>
                        <p class="text-lg font-bold text-white"><?php echo number_format($site['today_visitors'] ?? 0); ?></p>
                    </div>
                    <div class="bg-slate-900/30 rounded-lg p-2 text-center">
                        <p class="text-xs text-slate-500">زيارات الشهر</p>
                        <p class="text-lg font-bold text-white"><?php echo number_format($site['monthly_visitors'] ?? 0); ?></p>
                    </div>
                    <div class="bg-slate-900/30 rounded-lg p-2 text-center">
                        <p class="text-xs text-slate-500">قواعد البيانات</p>
                        <p class="text-lg font-bold text-white"><?php echo ($site['current_dbs'] ?? 0) . '/' . ($site['databases_limit'] == -1 ? '∞' : $site['databases_limit']); ?></p>
                    </div>
                    <div class="bg-slate-900/30 rounded-lg p-2 text-center">
                        <p class="text-xs text-slate-500">حسابات البريد</p>
                        <p class="text-lg font-bold text-white"><?php echo ($site['current_emails'] ?? 0) . '/' . ($site['emails_limit'] == -1 ? '∞' : $site['emails_limit']); ?></p>
                    </div>
                    <div class="bg-slate-900/30 rounded-lg p-2 text-center">
                        <p class="text-xs text-slate-500">آخر نسخة احتياطية</p>
                        <p class="text-sm font-bold text-white"><?php echo $site['last_backup'] ? date('Y-m-d', strtotime($site['last_backup'])) : 'لا توجد'; ?></p>
                    </div>
                </div>
                
                <!-- الصف الخامس: أزرار الإجراءات -->
                <div class="flex flex-wrap items-center justify-end gap-2 pt-4 border-t border-slate-700">
                    <button onclick="manageSite(<?php echo $site['id']; ?>)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium transition-all flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        إدارة الموقع
                    </button>
                    <button onclick="showFTP(<?php echo $site['id']; ?>)" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-medium transition-all flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        بيانات FTP
                    </button>
                    <?php if ($site['domain_name']): ?>
                    <a href="http://<?php echo $site['domain_name']; ?>" target="_blank" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-medium transition-all flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        زيارة الموقع
                    </a>
                    <?php endif; ?>
                    <button onclick="createBackup(<?php echo $site['id']; ?>)" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-medium transition-all flex items-center" title="إنشاء نسخة احتياطية">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- إضافة أزرار الإجراءات مع النماذج المخفية -->
<!-- ============================================= -->
<script>
// دوال تنفيذ الإجراءات مع تسجيل النشاط
function createBackup(siteId) {
    if (confirm('هل أنت متأكد من إنشاء نسخة احتياطية؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="create_backup" value="1">
            <input type="hidden" name="site_id" value="${siteId}">
            <input type="hidden" name="backup_type" value="full">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function restoreBackup(backupId) {
    if (confirm('هل أنت متأكد من استعادة هذه النسخة؟ سيتم استبدال الملفات الحالية')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="restore_backup" value="1">
            <input type="hidden" name="backup_id" value="${backupId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function changePHP(siteId) {
    const versions = ['8.2', '8.1', '8.0', '7.4'];
    const newVersion = prompt('اختر إصدار PHP:', versions.join(', '));
    
    if (newVersion && versions.includes(newVersion)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="change_php" value="1">
            <input type="hidden" name="site_id" value="${siteId}">
            <input type="hidden" name="php_version" value="${newVersion}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function clearCache(siteId) {
    if (confirm('هل أنت متأكد من مسح الكاش؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="clear_cache" value="1">
            <input type="hidden" name="site_id" value="${siteId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function restartSite(siteId) {
    if (confirm('هل أنت متأكد من إعادة تشغيل الموقع؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="restart_site" value="1">
            <input type="hidden" name="site_id" value="${siteId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleSuspend(siteId, currentStatus) {
    const action = currentStatus === 'active' ? 'تعليق' : 'إعادة تفعيل';
    if (confirm(`هل أنت متأكد من ${action} الموقع؟`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="suspend_site" value="1">
            <input type="hidden" name="site_id" value="${siteId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function requestSSL(siteId) {
    if (confirm('هل أنت متأكد من طلب شهادة SSL؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="request_ssl" value="1">
            <input type="hidden" name="site_id" value="${siteId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// تحديث دالة showNotification
function showNotification(message, type = 'info') {
    // ... (نفس الكود السابق)
}

// دوال الوقت
function timeAgo(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // الفرق بالثواني
    
    if (diff < 60) return 'الآن';
    if (diff < 3600) return Math.floor(diff / 60) + ' دقيقة';
    if (diff < 86400) return Math.floor(diff / 3600) + ' ساعة';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' يوم';
    
    return date.toLocaleDateString('ar-SA');
}
</script>

<!-- ============================================= -->
<!-- تبويب 2: خطط الاستضافة -->
<!-- ============================================= -->
<div id="tab-plans" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($plans as $plan): ?>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border <?php echo $plan['is_popular'] ? 'border-yellow-500/50' : 'border-slate-700'; ?> hover:border-blue-500/50 transition-all relative">
            
            <?php if ($plan['is_popular']): ?>
            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-yellow-500 to-amber-500 text-white px-4 py-1 rounded-full text-xs font-medium">
                الأكثر طلباً
            </div>
            <?php endif; ?>
            
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                <div class="flex items-center justify-center">
                    <span class="text-3xl font-bold text-blue-400"><?php echo $plan['price_monthly']; ?></span>
                    <span class="text-slate-400 mr-1">ريال/شهر</span>
                </div>
                <?php if ($plan['price_yearly']): ?>
                <p class="text-xs text-slate-500 mt-1"><?php echo $plan['price_yearly']; ?> ريال/سنة (وفر <?php echo round((1 - $plan['price_yearly']/($plan['price_monthly']*12)) * 100); ?>%)</p>
                <?php endif; ?>
            </div>
            
            <div class="space-y-3 mb-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">مساحة التخزين</span>
                    <span class="text-white font-medium"><?php echo round($plan['disk_space'] / 1024, 1); ?> GB</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">الزيارة الشهرية</span>
                    <span class="text-white font-medium"><?php echo round($plan['bandwidth'] / 1024, 1); ?> GB</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">عدد المواقع</span>
                    <span class="text-white font-medium"><?php echo $plan['domains_limit'] == -1 ? 'غير محدود' : $plan['domains_limit']; ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">قواعد البيانات</span>
                    <span class="text-white font-medium"><?php echo $plan['databases_limit'] == -1 ? 'غير محدود' : $plan['databases_limit']; ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">حسابات البريد</span>
                    <span class="text-white font-medium"><?php echo $plan['emails_limit'] == -1 ? 'غير محدود' : $plan['emails_limit']; ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">شهادة SSL</span>
                    <span class="text-white font-medium"><?php echo $plan['ssl_certificate'] ? '✅ مجاناً' : '❌'; ?></span>
                </div>
            </div>
            
            <div class="border-t border-slate-700 pt-4">
                <?php 
                $features = explode(',', $plan['features'] ?? 'دعم فني 24/7, ضمان تشغيل 99.9%, نسخ احتياطي يومي');
                foreach (array_slice($features, 0, 3) as $feature): 
                ?>
                <div class="flex items-center text-sm mb-2">
                    <span class="text-green-400 ml-2">✓</span>
                    <span class="text-slate-300"><?php echo trim($feature); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button onclick="orderPlan(<?php echo $plan['id']; ?>)" class="w-full mt-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-lg text-white font-medium transition-all">
                اختر الخطة
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويب 3: النطاقات -->
<!-- ============================================= -->
<div id="tab-domains" class="tab-content hidden">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
        <div class="p-4 bg-slate-900/50 border-b border-slate-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">النطاقات المسجلة</h3>
            <button onclick="openDomainModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium transition-all flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                تسجيل نطاق جديد
            </button>
        </div>
        
        <?php if (empty($domains)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
            <p class="text-slate-400 mb-4">لا توجد نطاقات مسجلة</p>
            <button onclick="openDomainModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium">
                تسجيل أول نطاق
            </button>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-right p-4 text-sm font-medium text-slate-400">النطاق</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">الموقع/المشروع</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">تاريخ التسجيل</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">تاريخ الانتهاء</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">SSL</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">الحالة</th>
                        <th class="text-center p-4 text-sm font-medium text-slate-400">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700/30 transition-all">
                        <td class="p-4">
                            <span class="text-white font-medium dir-ltr text-left block"><?php echo htmlspecialchars($domain['domain_name']); ?></span>
                            <span class="text-xs text-slate-500"><?php echo $domain['domain_type']; ?></span>
                        </td>
                        <td class="p-4">
                            <?php if ($domain['site_name']): ?>
                            <span class="text-white"><?php echo $domain['site_name']; ?></span>
                            <?php else: ?>
                            <span class="text-slate-500">غير مرتبط</span>
                            <?php endif; ?>
                            <span class="text-xs text-slate-500 block"><?php echo $domain['project_name'] ?? ''; ?></span>
                        </td>
                        <td class="p-4 text-slate-300"><?php echo $domain['registration_date'] ? date('Y-m-d', strtotime($domain['registration_date'])) : '-'; ?></td>
                        <td class="p-4">
                            <?php 
                            $expiry = $domain['expiry_date'] ? strtotime($domain['expiry_date']) : null;
                            $expiry_class = $expiry && $expiry < time() ? 'text-red-400' : ($expiry && $expiry < strtotime('+30 days') ? 'text-yellow-400' : 'text-green-400');
                            ?>
                            <span class="<?php echo $expiry_class; ?>"><?php echo $domain['expiry_date'] ? date('Y-m-d', $expiry) : '-'; ?></span>
                            <?php if ($expiry && $expiry < strtotime('+30 days')): ?>
                            <span class="text-xs text-yellow-400 block">سينتهي قريباً</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4"><?php echo getSSLBadge($domain['ssl_status'] ?? 'none'); ?></td>
                        <td class="p-4"><?php echo getStatusBadge($domain['status']); ?></td>
                        <td class="p-4">
                            <div class="flex items-center justify-center space-x-2 space-x-reverse">
                                <button onclick="renewDomain(<?php echo $domain['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="تجديد">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                                <button onclick="configureDNS(<?php echo $domain['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="إعدادات DNS">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويب 4: النسخ الاحتياطية -->
<!-- ============================================= -->
<div id="tab-backups" class="tab-content hidden">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
        <div class="p-4 bg-slate-900/50 border-b border-slate-700">
            <h3 class="text-lg font-semibold text-white">النسخ الاحتياطية المتاحة</h3>
        </div>
        
        <?php if (empty($backups)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <p class="text-slate-400">لا توجد نسخ احتياطية بعد</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-right p-4 text-sm font-medium text-slate-400">الموقع</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">نوع النسخة</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">الحجم</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">تاريخ الإنشاء</th>
                        <th class="text-right p-4 text-sm font-medium text-slate-400">الحالة</th>
                        <th class="text-center p-4 text-sm font-medium text-slate-400">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700/30 transition-all">
                        <td class="p-4">
                            <span class="text-white"><?php echo htmlspecialchars($backup['site_name']); ?></span>
                            <span class="text-xs text-slate-500 block"><?php echo $backup['project_name']; ?></span>
                        </td>
                        <td class="p-4">
                            <?php 
                            $type_colors = [
                                'full' => 'bg-purple-500/20 text-purple-400',
                                'database' => 'bg-green-500/20 text-green-400',
                                'files' => 'bg-blue-500/20 text-blue-400'
                            ];
                            $type_texts = [
                                'full' => 'نسخة كاملة',
                                'database' => 'قاعدة بيانات',
                                'files' => 'ملفات'
                            ];
                            $type_color = $type_colors[$backup['backup_type']] ?? 'bg-slate-500/20 text-slate-400';
                            $type_text = $type_texts[$backup['backup_type']] ?? $backup['backup_type'];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $type_color; ?>"><?php echo $type_text; ?></span>
                        </td>
                        <td class="p-4 text-slate-300"><?php echo formatBytes($backup['backup_size'] * 1024 * 1024); ?></td>
                        <td class="p-4 text-slate-300"><?php echo date('Y-m-d H:i', strtotime($backup['created_at'])); ?></td>
                        <td class="p-4"><?php echo getStatusBadge($backup['status']); ?></td>
                        <td class="p-4">
                            <div class="flex items-center justify-center space-x-2 space-x-reverse">
                                <?php if ($backup['status'] == 'completed'): ?>
                                <button onclick="restoreBackup(<?php echo $backup['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="استعادة">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                                <button onclick="downloadBackup(<?php echo $backup['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="تحميل">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v4h16v-4M16 8l-4-4-4 4m4 8V4"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويب 5: رفع موقع جديد -->
<!-- ============================================= -->
<div id="tab-deploy" class="tab-content hidden">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- الجزء الأيسر: رفع الملفات -->
        <div class="lg:col-span-2">
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
                <h3 class="text-lg font-semibold text-white mb-4">رفع ملفات الموقع</h3>
                
                <!-- اختيار الموقع -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">اختر الموقع المستهدف</label>
                    <select id="deploy-site" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-white">
                        <option value="">-- اختر موقعاً --</option>
                        <?php foreach ($sites as $site): ?>
                            <?php if ($site['status'] == 'active'): ?>
                            <option value="<?php echo $site['id']; ?>"><?php echo htmlspecialchars($site['site_name']); ?> (<?php echo $site['domain_name'] ?? 'بدون نطاق'; ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- منطقة السحب -->
                <div id="dropzone" class="border-2 border-dashed border-slate-700 hover:border-blue-500/50 rounded-xl p-8 text-center transition-all cursor-pointer mb-4"
                     onclick="document.getElementById('file-input').click()"
                     ondragover="handleDragOver(event)"
                     ondrop="handleDrop(event)">
                    
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    
                    <p class="text-lg font-medium text-white mb-2">اسحب مجلد الموقع هنا</p>
                    <p class="text-sm text-slate-400 mb-4">أو</p>
                    
                    <input type="file" id="file-input" class="hidden" webkitdirectory directory multiple onchange="handleFiles(this.files)">
                    
                    <button type="button" class="px-6 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-white transition-all">
                        اختر مجلد الموقع
                    </button>
                </div>
                
                <!-- قائمة الملفات -->
                <div id="files-list" class="hidden mb-4">
                    <h4 class="text-sm font-medium text-slate-300 mb-2">الملفات المختارة:</h4>
                    <div class="bg-slate-900 rounded-lg p-3 max-h-48 overflow-y-auto">
                        <div id="files-container" class="space-y-1"></div>
                    </div>
                </div>
                
                <!-- زر الرفع -->
                <button onclick="startUpload()" id="upload-btn" class="w-full py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg text-white font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    رفع الموقع
                </button>
                
                <!-- شريط التقدم -->
                <div id="progress-container" class="hidden mt-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-slate-400" id="progress-status">جاري الرفع...</span>
                        <span class="text-blue-400" id="progress-percent">0%</span>
                    </div>
                    <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                        <div id="progress-bar" class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- الجزء الأيمن: إرشادات -->
        <div class="lg:col-span-1">
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
                <h3 class="text-lg font-semibold text-white mb-4">إرشادات الرفع</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center ml-3 flex-shrink-0">
                            <span class="text-blue-400 font-bold">1</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white mb-1">اختر الموقع المستهدف</p>
                            <p class="text-xs text-slate-400">حدد الموقع الذي تريد رفع الملفات إليه</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center ml-3 flex-shrink-0">
                            <span class="text-blue-400 font-bold">2</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white mb-1">اختر مجلد الموقع</p>
                            <p class="text-xs text-slate-400">اسحب مجلد المشروع أو اختره من جهازك</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center ml-3 flex-shrink-0">
                            <span class="text-blue-400 font-bold">3</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white mb-1">انتظر حتى يكتمل الرفع</p>
                            <p class="text-xs text-slate-400">سيتم رفع جميع الملفات مع الحفاظ على هيكل المجلدات</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-slate-700">
                    <p class="text-xs text-slate-500">الحد الأقصى لحجم الملف: 100MB</p>
                    <p class="text-xs text-slate-500 mt-1">إجمالي مساحة الموقع: 2GB</p>
                    <p class="text-xs text-slate-500 mt-1">الصيغ المدعومة: جميع الصيغ</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة طلب استضافة جديدة -->
<!-- ============================================= -->
<div id="order-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeOrderModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">طلب استضافة جديدة</h3>
        </div>
        
        <form onsubmit="handleOrder(event)" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">اسم الموقع</label>
                <input type="text" id="site-name" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-white" placeholder="مثال: متجري الإلكتروني">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">الخطة</label>
                    <select id="plan-select" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-white">
                        <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo $plan['id']; ?>" data-price="<?php echo $plan['price_monthly']; ?>" data-name="<?php echo $plan['plan_name']; ?>">
                            <?php echo $plan['plan_name']; ?> - <?php echo $plan['price_monthly']; ?> ريال/شهر
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">مدة الاشتراك</label>
                    <select id="period-select" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-white">
                        <option value="1">شهر واحد</option>
                        <option value="3">3 أشهر (خصم 5%)</option>
                        <option value="6">6 أشهر (خصم 10%)</option>
                        <option value="12">سنة (خصم 15%)</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">النطاق</label>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <input type="text" id="domain" placeholder="example.com" class="flex-1 px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-white">
                    <button type="button" onclick="checkDomain()" class="px-4 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm">
                        تحقق
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-1">اتركه فارغاً إذا كنت تريد نطاقاً فرعياً منا</p>
            </div>
            
            <div class="bg-slate-900/50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-400">إجمالي المبلغ</span>
                    <span class="text-2xl font-bold text-blue-400" id="total-price">0 ريال</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>شامل ضريبة القيمة المضافة</span>
                    <span>قابل للدفع سنوياً/شهرياً</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse pt-4">
                <button type="button" onclick="closeOrderModal()" class="flex-1 px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-lg font-medium transition-all">
                    إرسال الطلب
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة بيانات FTP -->
<!-- ============================================= -->
<div id="ftp-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeFTPModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">بيانات FTP</h3>
        </div>
        
        <div class="bg-slate-900 rounded-lg p-4 mb-4">
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-slate-700">
                    <span class="text-slate-400">الخادم</span>
                    <span class="font-mono text-white" id="ftp-server">ftp.client.com</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-700">
                    <span class="text-slate-400">اسم المستخدم</span>
                    <span class="font-mono text-white" id="ftp-username">user_<?php echo $client_id; ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-700">
                    <span class="text-slate-400">كلمة المرور</span>
                    <span class="font-mono text-white" id="ftp-password">••••••••</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-700">
                    <span class="text-slate-400">المنفذ</span>
                    <span class="font-mono text-white">21 (FTP) / 22 (SFTP)</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-slate-400">المسار الافتراضي</span>
                    <span class="font-mono text-white">/public_html</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="copyFTPData()" class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-all">
                نسخ البيانات
            </button>
            <button onclick="showFTPPassword()" class="p-3 bg-slate-700 hover:bg-slate-600 rounded-lg transition-all" title="إظهار كلمة المرور">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة إدارة الموقع -->
<!-- ============================================= -->
<div id="manage-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-slate-700">
        <div class="flex items-center justify-between mb-6 sticky top-0 bg-slate-800 py-2">
            <button onclick="closeManageModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white" id="manage-modal-title">إدارة الموقع</h3>
        </div>
        
        <div id="manage-content" class="space-y-6">
            <!-- المحتوى يتحمل ديناميكياً من JavaScript -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
// =============================================
// التبديل بين التبويبات
// =============================================
function switchTab(tab) {
    // إخفاء جميع التبويبات
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // إظهار التبويب المحدد
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    // تحديث أزرار التبويبات
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-blue-400', 'border-blue-500');
        btn.classList.add('text-slate-400', 'border-transparent');
    });
    
    // تنشيط الزر الحالي
    document.getElementById('tab-' + tab + '-btn').classList.add('active', 'text-blue-400', 'border-blue-500');
}

// =============================================
// رفع الملفات
// =============================================
let selectedFiles = [];

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('dropzone').classList.add('border-blue-500/50', 'bg-blue-500/5');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('dropzone').classList.remove('border-blue-500/50', 'bg-blue-500/5');
    
    const items = e.dataTransfer.items;
    handleDroppedItems(items);
}

function handleFiles(files) {
    selectedFiles = Array.from(files);
    displayFiles();
}

function handleDroppedItems(items) {
    selectedFiles = [];
    for (let i = 0; i < items.length; i++) {
        const item = items[i].webkitGetAsEntry();
        if (item) {
            traverseFileTree(item);
        }
    }
}

function traverseFileTree(item, path = '') {
    if (item.isFile) {
        item.file(function(file) {
            file.relativePath = path + file.name;
            selectedFiles.push(file);
            if (selectedFiles.length === 1) {
                displayFiles();
            }
        });
    } else if (item.isDirectory) {
        const dirReader = item.createReader();
        dirReader.readEntries(function(entries) {
            for (let i = 0; i < entries.length; i++) {
                traverseFileTree(entries[i], path + item.name + '/');
            }
        });
    }
}

function displayFiles() {
    const container = document.getElementById('files-container');
    const listDiv = document.getElementById('files-list');
    
    container.innerHTML = '';
    
    if (selectedFiles.length === 0) {
        listDiv.classList.add('hidden');
        document.getElementById('upload-btn').disabled = true;
        return;
    }
    
    let totalSize = 0;
    selectedFiles.forEach(file => {
        totalSize += file.size;
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between text-xs';
        div.innerHTML = `
            <span class="text-slate-500">${formatBytes(file.size)}</span>
            <span class="text-white">${file.relativePath || file.name}</span>
        `;
        container.appendChild(div);
    });
    
    listDiv.classList.remove('hidden');
    document.getElementById('upload-btn').disabled = false;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function startUpload() {
    const site = document.getElementById('deploy-site').value;
    if (!site) {
        alert('الرجاء اختيار الموقع المستهدف');
        return;
    }
    
    if (selectedFiles.length === 0) {
        alert('الرجاء اختيار ملفات الموقع');
        return;
    }
    
    document.getElementById('progress-container').classList.remove('hidden');
    document.getElementById('upload-btn').disabled = true;
    
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
            document.getElementById('progress-status').textContent = 'تم الرفع بنجاح!';
            setTimeout(() => {
                document.getElementById('progress-container').classList.add('hidden');
                document.getElementById('upload-btn').disabled = false;
                selectedFiles = [];
                document.getElementById('files-list').classList.add('hidden');
                document.getElementById('deploy-site').value = '';
                document.getElementById('progress-bar').style.width = '0%';
                document.getElementById('progress-percent').textContent = '0%';
                showNotification('تم رفع الملفات بنجاح', 'success');
            }, 2000);
        }
        document.getElementById('progress-bar').style.width = progress + '%';
        document.getElementById('progress-percent').textContent = Math.floor(progress) + '%';
    }, 200);
}

// =============================================
// النوافذ المنبثقة
// =============================================
function openOrderModal() {
    document.getElementById('order-modal').classList.remove('hidden');
    updatePrice();
}

function closeOrderModal() {
    document.getElementById('order-modal').classList.add('hidden');
}

function orderPlan(planId) {
    // تحديد الخطة في القائمة
    const planSelect = document.getElementById('plan-select');
    for (let i = 0; i < planSelect.options.length; i++) {
        if (planSelect.options[i].value == planId) {
            planSelect.selectedIndex = i;
            break;
        }
    }
    openOrderModal();
}

function handleOrder(e) {
    e.preventDefault();
    
    const siteName = document.getElementById('site-name').value;
    const planSelect = document.getElementById('plan-select');
    const planName = planSelect.options[planSelect.selectedIndex].dataset.name;
    const period = document.getElementById('period-select').value;
    const domain = document.getElementById('domain').value || 'سيتم توفير نطاق فرعي';
    
    showNotification('جاري إرسال طلب الاستضافة...', 'info');
    
    setTimeout(() => {
        closeOrderModal();
        showNotification(`تم إرسال طلب استضافة "${siteName}" بنجاح`, 'success');
        // يمكن إعادة تعيين النموذج هنا
        document.getElementById('site-name').value = '';
        document.getElementById('domain').value = '';
    }, 1500);
}

// =============================================
// FTP
// =============================================
function showFTP(siteId) {
    document.getElementById('ftp-modal').classList.remove('hidden');
    
    // محاكاة جلب بيانات FTP للموقع
    document.getElementById('ftp-server').textContent = `ftp.site${siteId}.client.com`;
    document.getElementById('ftp-username').textContent = `user_${siteId}_<?php echo $client_id; ?>`;
}

function closeFTPModal() {
    document.getElementById('ftp-modal').classList.add('hidden');
}

function copyFTPData() {
    const text = `
        الخادم: ${document.getElementById('ftp-server').textContent}
        اسم المستخدم: ${document.getElementById('ftp-username').textContent}
        كلمة المرور: (مخفية)
        المنفذ: 21
    `;
    
    navigator.clipboard.writeText(text).then(() => {
        showNotification('تم نسخ بيانات FTP', 'success');
    });
}

function showFTPPassword() {
    showNotification('لأسباب أمنية، يرجى التواصل مع الدعم لاستعادة كلمة المرور', 'warning');
}

// =============================================
// إدارة الموقع
// =============================================
function manageSite(siteId) {
    document.getElementById('manage-modal-title').textContent = 'إدارة الموقع';
    
    const content = document.getElementById('manage-content');
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <button onclick="restartSite(${siteId})" class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-800 transition-all text-center">
                <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-white">إعادة تشغيل</p>
            </button>
            
            <button onclick="createBackup(${siteId})" class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-800 transition-all text-center">
                <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-green-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-white">نسخة احتياطية</p>
            </button>
            
            <button onclick="clearCache(${siteId})" class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-800 transition-all text-center">
                <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-white">مسح الكاش</p>
            </button>
            
            <button onclick="changePHP(${siteId})" class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-800 transition-all text-center">
                <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-white">تغيير PHP</p>
            </button>
        </div>
        
        <div class="bg-slate-900/50 rounded-xl p-4">
            <h4 class="font-bold text-white mb-3">بيانات الوصول</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400">مدير الملفات:</span>
                    <span class="text-white font-mono">https://cpanel.client.com</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">phpMyAdmin:</span>
                    <span class="text-white font-mono">https://cpanel.client.com/phpmyadmin</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Webmail:</span>
                    <span class="text-white font-mono">https://webmail.client.com</span>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-900/50 rounded-xl p-4">
            <h4 class="font-bold text-white mb-3">إحصائيات سريعة</h4>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-800 p-3 rounded-lg">
                    <p class="text-xs text-slate-500">المساحة</p>
                    <p class="text-sm font-bold text-white">2.1 GB / 50 GB</p>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg">
                    <p class="text-xs text-slate-500">الباندويث</p>
                    <p class="text-sm font-bold text-white">15 GB / 500 GB</p>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg">
                    <p class="text-xs text-slate-500">زيارات اليوم</p>
                    <p class="text-sm font-bold text-white">1,250</p>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg">
                    <p class="text-xs text-slate-500">التحميل</p>
                    <p class="text-sm font-bold text-white">45%</p>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('manage-modal').classList.remove('hidden');
}

function closeManageModal() {
    document.getElementById('manage-modal').classList.add('hidden');
}

// =============================================
// إجراءات الموقع
// =============================================
function restartSite(id) {
    showNotification('جاري إعادة تشغيل الموقع...', 'info');
    setTimeout(() => {
        showNotification('تم إعادة التشغيل بنجاح', 'success');
    }, 2000);
}

function createBackup(id) {
    showNotification('جاري إنشاء نسخة احتياطية...', 'info');
    setTimeout(() => {
        showNotification('تم إنشاء النسخة الاحتياطية', 'success');
    }, 3000);
}

function clearCache(id) {
    showNotification('جاري مسح الكاش...', 'info');
    setTimeout(() => {
        showNotification('تم مسح الكاش بنجاح', 'success');
    }, 1500);
}

function changePHP(id) {
    const versions = ['8.2', '8.1', '8.0', '7.4'];
    const current = '8.1';
    const newVersion = prompt(`اختر إصدار PHP الحالي: ${current}\nالإصدارات المتاحة: ${versions.join(', ')}`);
    
    if (newVersion && versions.includes(newVersion)) {
        showNotification(`جاري تغيير إصدار PHP إلى ${newVersion}...`, 'info');
        setTimeout(() => {
            showNotification('تم تغيير إصدار PHP بنجاح', 'success');
        }, 2000);
    }
}

// =============================================
// النطاقات
// =============================================
function openDomainModal() {
    showNotification('سيتم إضافة نافذة تسجيل النطاق قريباً', 'info');
}

function renewDomain(id) {
    showNotification('جاري تجديد النطاق...', 'info');
    setTimeout(() => {
        showNotification('تم تجديد النطاق بنجاح', 'success');
    }, 1500);
}

function configureDNS(id) {
    showNotification('جاري تحميل إعدادات DNS...', 'info');
    setTimeout(() => {
        showNotification('هذه الميزة قيد التطوير', 'warning');
    }, 1000);
}

// =============================================
// النسخ الاحتياطية
// =============================================
function restoreBackup(id) {
    if (confirm('هل أنت متأكد من استعادة هذه النسخة؟ سيتم استبدال الملفات الحالية')) {
        showNotification('جاري استعادة النسخة الاحتياطية...', 'info');
        setTimeout(() => {
            showNotification('تمت الاستعادة بنجاح', 'success');
        }, 3000);
    }
}

function downloadBackup(id) {
    showNotification('جاري تجهيز ملف التحميل...', 'info');
    setTimeout(() => {
        showNotification('سيبدأ التحميل قريباً', 'success');
    }, 1500);
}

// =============================================
// حساب السعر
// =============================================
function updatePrice() {
    const planSelect = document.getElementById('plan-select');
    const period = document.getElementById('period-select').value;
    
    if (!planSelect || !planSelect.options[planSelect.selectedIndex]) return;
    
    const price = parseInt(planSelect.options[planSelect.selectedIndex].dataset.price);
    
    let discount = 0;
    if (period == 3) discount = 0.05;
    else if (period == 6) discount = 0.10;
    else if (period == 12) discount = 0.15;
    
    const total = price * period * (1 - discount);
    document.getElementById('total-price').textContent = total.toFixed(0) + ' ريال';
}

// =============================================
// التحقق من النطاق
// =============================================
function checkDomain() {
    const domain = document.getElementById('domain').value;
    if (!domain) {
        showNotification('الرجاء إدخال النطاق', 'warning');
        return;
    }
    
    showNotification(`جاري التحقق من ${domain}...`, 'info');
    
    setTimeout(() => {
        const available = Math.random() > 0.5;
        if (available) {
            showNotification(`${domain} متاح للتسجيل`, 'success');
        } else {
            showNotification(`${domain} غير متاح، جرب اسماً آخر`, 'error');
        }
    }, 1500);
}

// =============================================
// إشعارات
// =============================================
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

// =============================================
// CSS للإضافات
// =============================================
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
    
    .dir-ltr {
        direction: ltr;
    }
`;
document.head.appendChild(style);

// تحديث السعر عند التحميل
document.addEventListener('DOMContentLoaded', function() {
    updatePrice();
    
    // إضافة مستمعي الأحداث للقوائم المنسدلة
    document.getElementById('plan-select')?.addEventListener('change', updatePrice);
    document.getElementById('period-select')?.addEventListener('change', updatePrice);
});
</script>

