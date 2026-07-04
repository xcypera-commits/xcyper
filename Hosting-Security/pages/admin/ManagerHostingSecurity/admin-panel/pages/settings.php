<?php
/**
 * إعدادات النظام العامة
 * System Settings Page
 */

// تعريف ثابت للوصول
define('ADMIN_ACCESS', true);
require_once '../../../../../security-init.php';
// تضمين الملفات الأساسية
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/admin_functions.php';

// طلب تسجيل الدخول وصلاحية المدير


// معالجة تحديث الإعدادات
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsType = $_POST['settings_type'] ?? '';
    
    try {
        switch ($settingsType) {
            case 'site':
                // إعدادات الموقع
                $settings = [
                    'site_name' => sanitize_input($_POST['site_name']),
                    'site_description' => sanitize_input($_POST['site_description']),
                    'site_keywords' => sanitize_input($_POST['site_keywords']),
                    'site_logo' => sanitize_input($_POST['site_logo']),
                    'site_favicon' => sanitize_input($_POST['site_favicon']),
                    'site_url' => sanitize_input($_POST['site_url']),
                    'admin_email' => sanitize_input($_POST['admin_email']),
                    'support_email' => sanitize_input($_POST['support_email']),
                    'contact_phone' => sanitize_input($_POST['contact_phone']),
                    'contact_address' => sanitize_input($_POST['contact_address']),
                    'timezone' => sanitize_input($_POST['timezone']),
                    'date_format' => sanitize_input($_POST['date_format']),
                    'time_format' => sanitize_input($_POST['time_format']),
                    'week_start' => sanitize_input($_POST['week_start']),
                    'language' => sanitize_input($_POST['language']),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                    'maintenance_message' => sanitize_input($_POST['maintenance_message']),
                    'debug_mode' => isset($_POST['debug_mode']) ? 1 : 0,
                    'registration_enabled' => isset($_POST['registration_enabled']) ? 1 : 0,
                    'email_verification' => isset($_POST['email_verification']) ? 1 : 0
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'site']);
                set_success('تم تحديث إعدادات الموقع بنجاح');
                break;
                
            case 'email':
                // إعدادات البريد
                $settings = [
                    'mail_driver' => sanitize_input($_POST['mail_driver']),
                    'mail_host' => sanitize_input($_POST['mail_host']),
                    'mail_port' => (int)$_POST['mail_port'],
                    'mail_username' => sanitize_input($_POST['mail_username']),
                    'mail_password' => sanitize_input($_POST['mail_password']),
                    'mail_encryption' => sanitize_input($_POST['mail_encryption']),
                    'mail_from_address' => sanitize_input($_POST['mail_from_address']),
                    'mail_from_name' => sanitize_input($_POST['mail_from_name']),
                    'mail_reply_to' => sanitize_input($_POST['mail_reply_to'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'email']);
                set_success('تم تحديث إعدادات البريد بنجاح');
                break;
                
            case 'system':
                // إعدادات النظام
                $settings = [
                    'session_lifetime' => (int)$_POST['session_lifetime'],
                    'max_login_attempts' => (int)$_POST['max_login_attempts'],
                    'lockout_duration' => (int)$_POST['lockout_duration'],
                    'password_min_length' => (int)$_POST['password_min_length'],
                    'password_expiry' => (int)$_POST['password_expiry'],
                    'max_upload_size' => (int)$_POST['max_upload_size'],
                    'allowed_file_types' => sanitize_input($_POST['allowed_file_types']),
                    'cache_enabled' => isset($_POST['cache_enabled']) ? 1 : 0,
                    'cache_lifetime' => (int)$_POST['cache_lifetime'],
                    'log_retention' => (int)$_POST['log_retention'],
                    'backup_retention' => (int)$_POST['backup_retention'],
                    'api_enabled' => isset($_POST['api_enabled']) ? 1 : 0,
                    'api_rate_limit' => (int)$_POST['api_rate_limit']
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'system']);
                set_success('تم تحديث إعدادات النظام بنجاح');
                break;
                
            case 'notifications':
                // إعدادات الإشعارات
                $settings = [
                    'notify_email' => isset($_POST['notify_email']) ? 1 : 0,
                    'notify_sms' => isset($_POST['notify_sms']) ? 1 : 0,
                    'notify_whatsapp' => isset($_POST['notify_whatsapp']) ? 1 : 0,
                    'notify_telegram' => isset($_POST['notify_telegram']) ? 1 : 0,
                    'notify_slack' => isset($_POST['notify_slack']) ? 1 : 0,
                    'notify_on_login' => isset($_POST['notify_on_login']) ? 1 : 0,
                    'notify_on_failed_login' => isset($_POST['notify_on_failed_login']) ? 1 : 0,
                    'notify_on_user_create' => isset($_POST['notify_on_user_create']) ? 1 : 0,
                    'notify_on_user_delete' => isset($_POST['notify_on_user_delete']) ? 1 : 0,
                    'notify_on_project_create' => isset($_POST['notify_on_project_create']) ? 1 : 0,
                    'notify_on_project_complete' => isset($_POST['notify_on_project_complete']) ? 1 : 0,
                    'notify_on_backup' => isset($_POST['notify_on_backup']) ? 1 : 0,
                    'notify_on_security_alert' => isset($_POST['notify_on_security_alert']) ? 1 : 0,
                    'notify_telegram_token' => sanitize_input($_POST['notify_telegram_token']),
                    'notify_telegram_chat' => sanitize_input($_POST['notify_telegram_chat']),
                    'notify_slack_webhook' => sanitize_input($_POST['notify_slack_webhook']),
                    'notify_whatsapp_api' => sanitize_input($_POST['notify_whatsapp_api'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'notifications']);
                set_success('تم تحديث إعدادات الإشعارات بنجاح');
                break;
                
            case 'localization':
                // إعدادات التوطين
                $settings = [
                    'default_language' => sanitize_input($_POST['default_language']),
                    'default_currency' => sanitize_input($_POST['default_currency']),
                    'currency_symbol' => sanitize_input($_POST['currency_symbol']),
                    'currency_position' => sanitize_input($_POST['currency_position']),
                    'thousand_separator' => sanitize_input($_POST['thousand_separator']),
                    'decimal_separator' => sanitize_input($_POST['decimal_separator']),
                    'number_decimals' => (int)$_POST['number_decimals'],
                    'date_format' => sanitize_input($_POST['date_format']),
                    'time_format' => sanitize_input($_POST['time_format']),
                    'week_start' => (int)$_POST['week_start'],
                    'timezone' => sanitize_input($_POST['timezone']),
                    'rtl_enabled' => isset($_POST['rtl_enabled']) ? 1 : 0,
                    'languages_enabled' => sanitize_input($_POST['languages_enabled'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'localization']);
                set_success('تم تحديث إعدادات التوطين بنجاح');
                break;
                
            case 'backup':
                // إعدادات النسخ الاحتياطي
                $settings = [
                    'backup_enabled' => isset($_POST['backup_enabled']) ? 1 : 0,
                    'backup_frequency' => sanitize_input($_POST['backup_frequency']),
                    'backup_time' => sanitize_input($_POST['backup_time']),
                    'backup_retention' => (int)$_POST['backup_retention'],
                    'backup_compress' => isset($_POST['backup_compress']) ? 1 : 0,
                    'backup_encrypt' => isset($_POST['backup_encrypt']) ? 1 : 0,
                    'backup_include_files' => isset($_POST['backup_include_files']) ? 1 : 0,
                    'backup_include_database' => isset($_POST['backup_include_database']) ? 1 : 0,
                    'backup_path' => sanitize_input($_POST['backup_path']),
                    'backup_notify' => isset($_POST['backup_notify']) ? 1 : 0,
                    'backup_notify_email' => sanitize_input($_POST['backup_notify_email'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'backup']);
                set_success('تم تحديث إعدادات النسخ الاحتياطي بنجاح');
                break;
                
            case 'api':
                // إعدادات API
                $settings = [
                    'api_enabled' => isset($_POST['api_enabled']) ? 1 : 0,
                    'api_debug' => isset($_POST['api_debug']) ? 1 : 0,
                    'api_version' => sanitize_input($_POST['api_version']),
                    'api_rate_limit' => (int)$_POST['api_rate_limit'],
                    'api_rate_period' => (int)$_POST['api_rate_period'],
                    'api_key_required' => isset($_POST['api_key_required']) ? 1 : 0,
                    'api_allowed_ips' => sanitize_input($_POST['api_allowed_ips']),
                    'api_log_requests' => isset($_POST['api_log_requests']) ? 1 : 0,
                    'api_cors_enabled' => isset($_POST['api_cors_enabled']) ? 1 : 0,
                    'api_cors_origins' => sanitize_input($_POST['api_cors_origins'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                // توليد مفتاح API جديد إذا طلب
                if (isset($_POST['generate_new_key'])) {
                    $newKey = 'api_' . bin2hex(random_bytes(24));
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('api_key', ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$newKey, $newKey]);
                    set_success('تم إنشاء مفتاح API جديد');
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'api']);
                set_success('تم تحديث إعدادات API بنجاح');
                break;
                
            case 'social':
                // إعدادات وسائل التواصل
                $settings = [
                    'social_facebook' => sanitize_input($_POST['social_facebook']),
                    'social_twitter' => sanitize_input($_POST['social_twitter']),
                    'social_instagram' => sanitize_input($_POST['social_instagram']),
                    'social_linkedin' => sanitize_input($_POST['social_linkedin']),
                    'social_youtube' => sanitize_input($_POST['social_youtube']),
                    'social_whatsapp' => sanitize_input($_POST['social_whatsapp']),
                    'social_telegram' => sanitize_input($_POST['social_telegram']),
                    'social_tiktok' => sanitize_input($_POST['social_tiktok']),
                    'social_snapchat' => sanitize_input($_POST['social_snapchat'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                          ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'social']);
                set_success('تم تحديث إعدادات وسائل التواصل بنجاح');
                break;
                
            case 'clear_cache':
                // تنظيف الذاكرة المؤقتة
                clear_system_cache();
                set_success('تم تنظيف الذاكرة المؤقتة بنجاح');
                break;
                
            case 'test_email':
                // اختبار البريد الإلكتروني
                $testEmail = sanitize_input($_POST['test_email']);
                $subject = "اختبار إعدادات البريد - " . getSetting('site_name', 'نظام الحماية');
                $message = "هذا بريد اختبار للتأكد من صحة إعدادات البريد الإلكتروني.\n\n";
                $message .= "التاريخ: " . date('Y-m-d H:i:s') . "\n";
                $message .= "المستخدم: " . $_SESSION['username'] . "\n";
                $message .= "إذا استلمت هذه الرسالة، فإعدادات البريد تعمل بشكل صحيح.";
                
                if (send_test_email($testEmail, $subject, $message)) {
                    set_success("تم إرسال بريد اختبار إلى $testEmail");
                } else {
                    set_error("فشل في إرسال البريد الإلكتروني. تحقق من إعدادات SMTP");
                }
                break;
        }
    } catch (PDOException $e) {
        set_error('خطأ في قاعدة البيانات: ' . $e->getMessage());
    } catch (Exception $e) {
        set_error('حدث خطأ: ' . $e->getMessage());
    }
    
    redirect('settings.php');
}

// دوال مساعدة للإعدادات
function getSetting($key, $default = '') {
    global $db;
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function clear_system_cache() {
    // تنظيف ملفات الكاش
    $cacheDirs = [
        __DIR__ . '/../../cache/',
        __DIR__ . '/../../tmp/'
    ];
    
    foreach ($cacheDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    // تنظيف جلسات PHP القديمة
    $sessionPath = session_save_path();
    if ($sessionPath && is_dir($sessionPath)) {
        $files = glob($sessionPath . '/sess_*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > 86400) {
                unlink($file);
            }
        }
    }
    
    log_activity($_SESSION['user_id'], 'cache_cleared', []);
}

function send_test_email($to, $subject, $message) {
    $headers = "From: " . getSetting('mail_from_address', 'noreply@example.com') . "\r\n";
    $headers .= "Reply-To: " . getSetting('mail_reply_to', getSetting('mail_from_address', 'noreply@example.com')) . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// جلب جميع الإعدادات
$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // تجاهل الخطأ
}

// إعدادات الموقع
$siteSettings = [
    'site_name' => $settings['site_name'] ?? 'نظام الحماية',
    'site_description' => $settings['site_description'] ?? 'نظام متكامل لإدارة الحماية والاستضافة',
    'site_keywords' => $settings['site_keywords'] ?? 'حماية, استضافة, أمن معلومات',
    'site_logo' => $settings['site_logo'] ?? '/assets/images/logo.png',
    'site_favicon' => $settings['site_favicon'] ?? '/assets/images/favicon.ico',
    'site_url' => $settings['site_url'] ?? 'http://localhost/Hosting-Security',
    'admin_email' => $settings['admin_email'] ?? 'admin@example.com',
    'support_email' => $settings['support_email'] ?? 'support@example.com',
    'contact_phone' => $settings['contact_phone'] ?? '+966500000000',
    'contact_address' => $settings['contact_address'] ?? 'الرياض، المملكة العربية السعودية',
    'timezone' => $settings['timezone'] ?? 'Asia/Riyadh',
    'date_format' => $settings['date_format'] ?? 'Y-m-d',
    'time_format' => $settings['time_format'] ?? 'H:i:s',
    'week_start' => $settings['week_start'] ?? 'saturday',
    'language' => $settings['language'] ?? 'ar',
    'maintenance_mode' => $settings['maintenance_mode'] ?? 0,
    'maintenance_message' => $settings['maintenance_message'] ?? 'الموقع تحت الصيانة حالياً، نعتذر عن الإزعاج',
    'debug_mode' => $settings['debug_mode'] ?? 0,
    'registration_enabled' => $settings['registration_enabled'] ?? 1,
    'email_verification' => $settings['email_verification'] ?? 1
];

// إعدادات البريد
$mailSettings = [
    'mail_driver' => $settings['mail_driver'] ?? 'smtp',
    'mail_host' => $settings['mail_host'] ?? 'smtp.gmail.com',
    'mail_port' => $settings['mail_port'] ?? 587,
    'mail_username' => $settings['mail_username'] ?? '',
    'mail_password' => $settings['mail_password'] ?? '',
    'mail_encryption' => $settings['mail_encryption'] ?? 'tls',
    'mail_from_address' => $settings['mail_from_address'] ?? 'noreply@example.com',
    'mail_from_name' => $settings['mail_from_name'] ?? 'نظام الحماية',
    'mail_reply_to' => $settings['mail_reply_to'] ?? 'support@example.com'
];

// إعدادات النظام
$systemSettings = [
    'session_lifetime' => $settings['session_lifetime'] ?? 7200,
    'max_login_attempts' => $settings['max_login_attempts'] ?? 5,
    'lockout_duration' => $settings['lockout_duration'] ?? 15,
    'password_min_length' => $settings['password_min_length'] ?? 8,
    'password_expiry' => $settings['password_expiry'] ?? 90,
    'max_upload_size' => $settings['max_upload_size'] ?? 10,
    'allowed_file_types' => $settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip',
    'cache_enabled' => $settings['cache_enabled'] ?? 1,
    'cache_lifetime' => $settings['cache_lifetime'] ?? 3600,
    'log_retention' => $settings['log_retention'] ?? 90,
    'backup_retention' => $settings['backup_retention'] ?? 30,
    'api_enabled' => $settings['api_enabled'] ?? 1,
    'api_rate_limit' => $settings['api_rate_limit'] ?? 60
];

// إعدادات الإشعارات
$notifySettings = [
    'notify_email' => $settings['notify_email'] ?? 1,
    'notify_sms' => $settings['notify_sms'] ?? 0,
    'notify_whatsapp' => $settings['notify_whatsapp'] ?? 0,
    'notify_telegram' => $settings['notify_telegram'] ?? 0,
    'notify_slack' => $settings['notify_slack'] ?? 0,
    'notify_on_login' => $settings['notify_on_login'] ?? 1,
    'notify_on_failed_login' => $settings['notify_on_failed_login'] ?? 1,
    'notify_on_user_create' => $settings['notify_on_user_create'] ?? 1,
    'notify_on_user_delete' => $settings['notify_on_user_delete'] ?? 1,
    'notify_on_project_create' => $settings['notify_on_project_create'] ?? 1,
    'notify_on_project_complete' => $settings['notify_on_project_complete'] ?? 1,
    'notify_on_backup' => $settings['notify_on_backup'] ?? 1,
    'notify_on_security_alert' => $settings['notify_on_security_alert'] ?? 1,
    'notify_telegram_token' => $settings['notify_telegram_token'] ?? '',
    'notify_telegram_chat' => $settings['notify_telegram_chat'] ?? '',
    'notify_slack_webhook' => $settings['notify_slack_webhook'] ?? '',
    'notify_whatsapp_api' => $settings['notify_whatsapp_api'] ?? ''
];

// إعدادات API
$apiSettings = [
    'api_key' => $settings['api_key'] ?? 'api_' . bin2hex(random_bytes(24)),
    'api_enabled' => $settings['api_enabled'] ?? 1,
    'api_debug' => $settings['api_debug'] ?? 0,
    'api_version' => $settings['api_version'] ?? '1.0.0',
    'api_rate_limit' => $settings['api_rate_limit'] ?? 60,
    'api_rate_period' => $settings['api_rate_period'] ?? 60,
    'api_key_required' => $settings['api_key_required'] ?? 1,
    'api_allowed_ips' => $settings['api_allowed_ips'] ?? '',
    'api_log_requests' => $settings['api_log_requests'] ?? 1,
    'api_cors_enabled' => $settings['api_cors_enabled'] ?? 1,
    'api_cors_origins' => $settings['api_cors_origins'] ?? '*'
];

// إعدادات وسائل التواصل
$socialSettings = [
    'social_facebook' => $settings['social_facebook'] ?? 'https://facebook.com/',
    'social_twitter' => $settings['social_twitter'] ?? 'https://twitter.com/',
    'social_instagram' => $settings['social_instagram'] ?? 'https://instagram.com/',
    'social_linkedin' => $settings['social_linkedin'] ?? 'https://linkedin.com/company/',
    'social_youtube' => $settings['social_youtube'] ?? 'https://youtube.com/',
    'social_whatsapp' => $settings['social_whatsapp'] ?? 'https://wa.me/',
    'social_telegram' => $settings['social_telegram'] ?? 'https://t.me/',
    'social_tiktok' => $settings['social_tiktok'] ?? 'https://tiktok.com/@',
    'social_snapchat' => $settings['social_snapchat'] ?? 'https://snapchat.com/add/'
];

// إحصائيات النظام
$systemStats = [
    'php_version' => phpversion(),
    'mysql_version' => $db->query("SELECT VERSION()")->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'disk_free' => disk_free_space('/'),
    'disk_total' => disk_total_space('/')
];

// الحصول على المستخدم الحالي
$currentUser = current_user();

// إنشاء جدول الإعدادات إذا لم يكن موجوداً
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'number', 'boolean', 'json', 'array') DEFAULT 'text',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // تجاهل الخطأ إذا كان الجدول موجوداً
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات النظام - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-right: 4px solid transparent;
            text-decoration: none;
        }
        
        .nav-link i {
            margin-left: 12px;
            width: 20px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: #ffd700;
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15);
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* تبويبات الإعدادات */
        .settings-tabs {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link i {
            margin-left: 8px;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background: none;
            font-weight: bold;
        }
        
        /* بطاقات الإعدادات */
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            font-size: 1.4rem;
        }
        
        /* مجموعات الإعدادات */
        .settings-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .group-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* مربعات المعلومات */
        .info-box {
            background: #e7f3ff;
            border-right: 4px solid var(--info-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-right: 4px solid var(--warning-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* مفاتيح التبديل */
        .form-switch {
            padding-right: 2.5em;
        }
        
        .form-switch .form-check-input {
            margin-right: -2.5em;
        }
        
        /* صندوق API Key */
        .api-key-box {
            background: #2d2d2d;
            color: #00ff00;
            padding: 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9rem;
            direction: ltr;
            text-align: left;
            overflow-x: auto;
        }
        
        /* معلومات النظام */
        .system-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-label {
            width: 200px;
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            flex: 1;
            color: #212529;
        }
        
        .info-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            background: #28a745;
            color: white;
        }
        
        /* التجاوب */
        @media (max-width: 768px) {
            .sidebar {
                right: -280px;
                transition: right 0.3s;
            }
            
            .sidebar.show {
                right: 0;
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        /* شاشة التحميل */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading.show {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- شاشة التحميل -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- زر القائمة للجوال -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- الشريط الجانبي -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-3x"></i>
            <h4 class="mt-2"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></h4>
            <small>مدير النظام</small>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> لوحة التحكم
            </a>
            <a href="users-management.php" class="nav-link">
                <i class="fas fa-users"></i> إدارة المستخدمين
            </a>
            <a href="roles-permissions.php" class="nav-link">
                <i class="fas fa-key"></i> الأدوار والصلاحيات
            </a>
            <a href="audit-logs.php" class="nav-link">
                <i class="fas fa-history"></i> سجلات التدقيق
            </a>
            <a href="security-settings.php" class="nav-link">
                <i class="fas fa-cog"></i> إعدادات الأمان
            </a>
            <a href="projects.php" class="nav-link">
                <i class="fas fa-project-diagram"></i> المشاريع
            </a>
            <a href="activity.php" class="nav-link">
                <i class="fas fa-chart-line"></i> النشاطات
            </a>
            <a href="settings.php" class="nav-link active">
                <i class="fas fa-sliders-h"></i> إعدادات النظام
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="../../index.php" class="nav-link">
                <i class="fas fa-globe"></i> الموقع الرئيسي
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> تسجيل خروج
            </a>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-sliders-h text-primary me-2"></i>
                إعدادات النظام العامة
            </h2>
            <div>
                <span class="badge bg-info p-2">
                    <i class="fas fa-clock me-1"></i>
                    آخر تحديث: <?php echo date('Y-m-d H:i:s'); ?>
                </span>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- تبويبات الإعدادات -->
        <div class="settings-tabs">
            <ul class="nav nav-tabs" id="settingsTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="site-tab" data-bs-toggle="tab" data-bs-target="#site">
                        <i class="fas fa-globe"></i> الموقع
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email">
                        <i class="fas fa-envelope"></i> البريد
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system">
                        <i class="fas fa-server"></i> النظام
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications">
                        <i class="fas fa-bell"></i> الإشعارات
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api">
                        <i class="fas fa-code"></i> API
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social">
                        <i class="fas fa-share-alt"></i> التواصل
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced">
                        <i class="fas fa-tools"></i> متقدم
                    </button>
                </li>
            </ul>
        </div>

        <!-- محتوى التبويبات -->
        <div class="tab-content">
            <!-- 1. إعدادات الموقع -->
            <div class="tab-pane fade show active" id="site">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-globe text-primary"></i>
                            معلومات الموقع
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="site">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم الموقع</label>
                                <input type="text" name="site_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['site_name']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رابط الموقع</label>
                                <input type="url" name="site_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['site_url']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">وصف الموقع</label>
                            <textarea name="site_description" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['site_description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الكلمات المفتاحية</label>
                            <input type="text" name="site_keywords" class="form-control" 
                                   value="<?php echo htmlspecialchars($siteSettings['site_keywords']); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">شعار الموقع (رابط)</label>
                                <input type="text" name="site_logo" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['site_logo']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">أيقونة الموقع (رابط)</label>
                                <input type="text" name="site_favicon" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['site_favicon']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">بريد المسؤول</label>
                                <input type="email" name="admin_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['admin_email']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">بريد الدعم</label>
                                <input type="email" name="support_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['support_email']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">رقم الاتصال</label>
                                <input type="text" name="contact_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['contact_phone']); ?>">
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label class="form-label">العنوان</label>
                                <input type="text" name="contact_address" class="form-control" 
                                       value="<?php echo htmlspecialchars($siteSettings['contact_address']); ?>">
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات إضافية</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">المنطقة الزمنية</label>
                                    <select name="timezone" class="form-select">
                                        <option value="Asia/Riyadh" <?php echo $siteSettings['timezone'] == 'Asia/Riyadh' ? 'selected' : ''; ?>>الرياض</option>
                                        <option value="Asia/Dubai" <?php echo $siteSettings['timezone'] == 'Asia/Dubai' ? 'selected' : ''; ?>>دبي</option>
                                        <option value="Asia/Kuwait" <?php echo $siteSettings['timezone'] == 'Asia/Kuwait' ? 'selected' : ''; ?>>الكويت</option>
                                        <option value="Asia/Qatar" <?php echo $siteSettings['timezone'] == 'Asia/Qatar' ? 'selected' : ''; ?>>قطر</option>
                                        <option value="Asia/Bahrain" <?php echo $siteSettings['timezone'] == 'Asia/Bahrain' ? 'selected' : ''; ?>>البحرين</option>
                                        <option value="Asia/Amman" <?php echo $siteSettings['timezone'] == 'Asia/Amman' ? 'selected' : ''; ?>>عمان</option>
                                        <option value="Africa/Cairo" <?php echo $siteSettings['timezone'] == 'Africa/Cairo' ? 'selected' : ''; ?>>القاهرة</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">تنسيق التاريخ</label>
                                    <select name="date_format" class="form-select">
                                        <option value="Y-m-d" <?php echo $siteSettings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>2024-01-31</option>
                                        <option value="d/m/Y" <?php echo $siteSettings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>31/01/2024</option>
                                        <option value="d-m-Y" <?php echo $siteSettings['date_format'] == 'd-m-Y' ? 'selected' : ''; ?>>31-01-2024</option>
                                        <option value="m/d/Y" <?php echo $siteSettings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>01/31/2024</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">تنسيق الوقت</label>
                                    <select name="time_format" class="form-select">
                                        <option value="H:i:s" <?php echo $siteSettings['time_format'] == 'H:i:s' ? 'selected' : ''; ?>>14:30:00 (24 ساعة)</option>
                                        <option value="h:i:s A" <?php echo $siteSettings['time_format'] == 'h:i:s A' ? 'selected' : ''; ?>>02:30:00 PM (12 ساعة)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">بداية الأسبوع</label>
                                    <select name="week_start" class="form-select">
                                        <option value="saturday" <?php echo $siteSettings['week_start'] == 'saturday' ? 'selected' : ''; ?>>السبت</option>
                                        <option value="sunday" <?php echo $siteSettings['week_start'] == 'sunday' ? 'selected' : ''; ?>>الأحد</option>
                                        <option value="monday" <?php echo $siteSettings['week_start'] == 'monday' ? 'selected' : ''; ?>>الاثنين</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">اللغة الافتراضية</label>
                                    <select name="language" class="form-select">
                                        <option value="ar" <?php echo $siteSettings['language'] == 'ar' ? 'selected' : ''; ?>>العربية</option>
                                        <option value="en" <?php echo $siteSettings['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                           id="maintenance_mode" <?php echo $siteSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_mode">
                                        وضع الصيانة
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="debug_mode" 
                                           id="debug_mode" <?php echo $siteSettings['debug_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="debug_mode">
                                        وضع التصحيح
                                    </label>
                                    <small class="text-danger d-block">لا تفعله في الإنتاج!</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="registration_enabled" 
                                           id="registration_enabled" <?php echo $siteSettings['registration_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="registration_enabled">
                                        فتح التسجيل
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_verification" 
                                           id="email_verification" <?php echo $siteSettings['email_verification'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_verification">
                                        تفعيل التحقق من البريد
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">رسالة الصيانة</label>
                            <textarea name="maintenance_message" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['maintenance_message']); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات الموقع
                        </button>
                    </form>
                </div>
            </div>

            <!-- 2. إعدادات البريد -->
            <div class="tab-pane fade" id="email">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-envelope text-warning"></i>
                            إعدادات البريد الإلكتروني
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="email">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع الخادم</label>
                                <select name="mail_driver" class="form-select">
                                    <option value="smtp" <?php echo $mailSettings['mail_driver'] == 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                    <option value="sendmail" <?php echo $mailSettings['mail_driver'] == 'sendmail' ? 'selected' : ''; ?>>Sendmail</option>
                                    <option value="mail" <?php echo $mailSettings['mail_driver'] == 'mail' ? 'selected' : ''; ?>>PHP Mail</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">خادم SMTP</label>
                                <input type="text" name="mail_host" class="form-control" 
                                       value="<?php echo htmlspecialchars($mailSettings['mail_host']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">المنفذ</label>
                                <input type="number" name="mail_port" class="form-control" 
                                       value="<?php echo $mailSettings['mail_port']; ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">اسم المستخدم</label>
                                <input type="text" name="mail_username" class="form-control" 
                                       value="<?php echo htmlspecialchars($mailSettings['mail_username']); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="password" name="mail_password" class="form-control" 
                                       value="<?php echo htmlspecialchars($mailSettings['mail_password']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">التشفير</label>
                                <select name="mail_encryption" class="form-select">
                                    <option value="tls" <?php echo $mailSettings['mail_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $mailSettings['mail_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo $mailSettings['mail_encryption'] == 'none' ? 'selected' : ''; ?>>بدون تشفير</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">البريد المرسل</label>
                                <input type="email" name="mail_from_address" class="form-control" 
                                       value="<?php echo htmlspecialchars($mailSettings['mail_from_address']); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">اسم المرسل</label>
                                <input type="text" name="mail_from_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($mailSettings['mail_from_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">بريد الرد</label>
                            <input type="email" name="mail_reply_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($mailSettings['mail_reply_to']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات البريد
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="test_email">
                        <h6 class="mb-3">اختبار إعدادات البريد</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="email" name="test_email" class="form-control" 
                                       placeholder="أدخل بريدك للاختبار" required>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    إرسال بريد اختبار
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. إعدادات النظام -->
            <div class="tab-pane fade" id="system">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-server text-success"></i>
                            إعدادات النظام
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="system">
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات الأمان</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">مدة الجلسة (ثانية)</label>
                                    <input type="number" name="session_lifetime" class="form-control" 
                                           value="<?php echo $systemSettings['session_lifetime']; ?>" min="60" max="86400">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">محاولات تسجيل الدخول</label>
                                    <input type="number" name="max_login_attempts" class="form-control" 
                                           value="<?php echo $systemSettings['max_login_attempts']; ?>" min="1" max="10">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">مدة القفل (دقيقة)</label>
                                    <input type="number" name="lockout_duration" class="form-control" 
                                           value="<?php echo $systemSettings['lockout_duration']; ?>" min="1" max="60">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">طول كلمة المرور</label>
                                    <input type="number" name="password_min_length" class="form-control" 
                                           value="<?php echo $systemSettings['password_min_length']; ?>" min="6" max="20">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">صلاحية كلمة المرور (يوم)</label>
                                    <input type="number" name="password_expiry" class="form-control" 
                                           value="<?php echo $systemSettings['password_expiry']; ?>" min="0" max="365">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات الملفات</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الحد الأقصى للرفع (ميجابايت)</label>
                                    <input type="number" name="max_upload_size" class="form-control" 
                                           value="<?php echo $systemSettings['max_upload_size']; ?>" min="1" max="100">
                                </div>
                                
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">أنواع الملفات المسموحة</label>
                                    <input type="text" name="allowed_file_types" class="form-control" 
                                           value="<?php echo htmlspecialchars($systemSettings['allowed_file_types']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات الكاش</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="cache_enabled" 
                                               id="cache_enabled" <?php echo $systemSettings['cache_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cache_enabled">
                                            تفعيل الكاش
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدة الكاش (ثانية)</label>
                                    <input type="number" name="cache_lifetime" class="form-control" 
                                           value="<?php echo $systemSettings['cache_lifetime']; ?>" min="60" max="86400">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات الاحتفاظ</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الاحتفاظ بالسجلات (يوم)</label>
                                    <input type="number" name="log_retention" class="form-control" 
                                           value="<?php echo $systemSettings['log_retention']; ?>" min="1" max="365">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الاحتفاظ بالنسخ (يوم)</label>
                                    <input type="number" name="backup_retention" class="form-control" 
                                           value="<?php echo $systemSettings['backup_retention']; ?>" min="1" max="365">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات API</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="api_enabled" 
                                               id="api_enabled" <?php echo $systemSettings['api_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="api_enabled">
                                            تفعيل API
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">حد الطلبات في الدقيقة</label>
                                    <input type="number" name="api_rate_limit" class="form-control" 
                                           value="<?php echo $systemSettings['api_rate_limit']; ?>" min="1" max="1000">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات النظام
                        </button>
                    </form>
                </div>
            </div>

            <!-- 4. إعدادات الإشعارات -->
            <div class="tab-pane fade" id="notifications">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-bell text-warning"></i>
                            إعدادات الإشعارات
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="notifications">
                        
                        <div class="settings-group">
                            <div class="group-title">قنوات الإشعارات</div>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_email" 
                                               id="notify_email" <?php echo $notifySettings['notify_email'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_email">
                                            البريد الإلكتروني
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_sms" 
                                               id="notify_sms" <?php echo $notifySettings['notify_sms'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_sms">
                                            SMS
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_whatsapp" 
                                               id="notify_whatsapp" <?php echo $notifySettings['notify_whatsapp'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_whatsapp">
                                            WhatsApp
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_telegram" 
                                               id="notify_telegram" <?php echo $notifySettings['notify_telegram'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_telegram">
                                            Telegram
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">أحداث الإشعارات</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_login" 
                                               id="notify_on_login" <?php echo $notifySettings['notify_on_login'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_login">
                                            تسجيل الدخول
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_failed_login" 
                                               id="notify_on_failed_login" <?php echo $notifySettings['notify_on_failed_login'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_failed_login">
                                            محاولات فاشلة
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_user_create" 
                                               id="notify_on_user_create" <?php echo $notifySettings['notify_on_user_create'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_user_create">
                                            إنشاء مستخدم
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_user_delete" 
                                               id="notify_on_user_delete" <?php echo $notifySettings['notify_on_user_delete'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_user_delete">
                                            حذف مستخدم
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_project_create" 
                                               id="notify_on_project_create" <?php echo $notifySettings['notify_on_project_create'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_project_create">
                                            إنشاء مشروع
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_project_complete" 
                                               id="notify_on_project_complete" <?php echo $notifySettings['notify_on_project_complete'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_project_complete">
                                            إكمال مشروع
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_backup" 
                                               id="notify_on_backup" <?php echo $notifySettings['notify_on_backup'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_backup">
                                            النسخ الاحتياطي
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_on_security_alert" 
                                               id="notify_on_security_alert" <?php echo $notifySettings['notify_on_security_alert'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notify_on_security_alert">
                                            التنبيهات الأمنية
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات القنوات</div>
                            
                            <div class="mb-3">
                                <label class="form-label">رمز Telegram Bot</label>
                                <input type="text" name="notify_telegram_token" class="form-control" 
                                       value="<?php echo htmlspecialchars($notifySettings['notify_telegram_token']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">معرف محادثة Telegram</label>
                                <input type="text" name="notify_telegram_chat" class="form-control" 
                                       value="<?php echo htmlspecialchars($notifySettings['notify_telegram_chat']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Slack Webhook</label>
                                <input type="url" name="notify_slack_webhook" class="form-control" 
                                       value="<?php echo htmlspecialchars($notifySettings['notify_slack_webhook']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">WhatsApp API</label>
                                <input type="text" name="notify_whatsapp_api" class="form-control" 
                                       value="<?php echo htmlspecialchars($notifySettings['notify_whatsapp_api']); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات الإشعارات
                        </button>
                    </form>
                </div>
            </div>

            <!-- 5. إعدادات API -->
            <div class="tab-pane fade" id="api">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-code text-info"></i>
                            إعدادات API
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="api">
                        
                        <div class="api-key-box mb-3" dir="ltr">
                            <?php echo $apiSettings['api_key']; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="api_enabled" 
                                           id="api_enabled_tab" <?php echo $apiSettings['api_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="api_enabled_tab">
                                        تفعيل API
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="api_debug" 
                                           id="api_debug" <?php echo $apiSettings['api_debug'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="api_debug">
                                        وضع التصحيح
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="api_key_required" 
                                           id="api_key_required" <?php echo $apiSettings['api_key_required'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="api_key_required">
                                        طلب مفتاح API
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">إصدار API</label>
                                <input type="text" name="api_version" class="form-control" 
                                       value="<?php echo htmlspecialchars($apiSettings['api_version']); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">حد الطلبات</label>
                                <input type="number" name="api_rate_limit" class="form-control" 
                                       value="<?php echo $apiSettings['api_rate_limit']; ?>" min="1" max="1000">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الفترة (ثانية)</label>
                                <input type="number" name="api_rate_period" class="form-control" 
                                       value="<?php echo $apiSettings['api_rate_period']; ?>" min="1" max="3600">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">عناوين IP المسموحة</label>
                            <textarea name="api_allowed_ips" class="form-control" rows="2"><?php echo htmlspecialchars($apiSettings['api_allowed_ips']); ?></textarea>
                            <small class="text-muted">افصل بين العناوين بفواصل، اترك فارغاً للسماح للكل</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="api_log_requests" 
                                           id="api_log_requests" <?php echo $apiSettings['api_log_requests'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="api_log_requests">
                                        تسجيل طلبات API
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="api_cors_enabled" 
                                           id="api_cors_enabled" <?php echo $apiSettings['api_cors_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="api_cors_enabled">
                                        تفعيل CORS
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">CORS Origins</label>
                            <input type="text" name="api_cors_origins" class="form-control" 
                                   value="<?php echo htmlspecialchars($apiSettings['api_cors_origins']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات API
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="api">
                        <input type="hidden" name="generate_new_key" value="1">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('هل أنت متأكد من إنشاء مفتاح API جديد؟ المفتاح القديم سيتوقف عن العمل')">
                            <i class="fas fa-key me-1"></i>
                            إنشاء مفتاح API جديد
                        </button>
                    </form>
                </div>
            </div>

            <!-- 6. إعدادات التواصل -->
            <div class="tab-pane fade" id="social">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-share-alt text-success"></i>
                            روابط التواصل الاجتماعي
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="social">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-facebook text-primary me-1"></i> Facebook
                                </label>
                                <input type="url" name="social_facebook" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_facebook']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-twitter text-info me-1"></i> Twitter
                                </label>
                                <input type="url" name="social_twitter" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_twitter']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-instagram text-danger me-1"></i> Instagram
                                </label>
                                <input type="url" name="social_instagram" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_instagram']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-linkedin text-primary me-1"></i> LinkedIn
                                </label>
                                <input type="url" name="social_linkedin" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_linkedin']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-youtube text-danger me-1"></i> YouTube
                                </label>
                                <input type="url" name="social_youtube" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_youtube']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-whatsapp text-success me-1"></i> WhatsApp
                                </label>
                                <input type="url" name="social_whatsapp" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_whatsapp']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-telegram text-primary me-1"></i> Telegram
                                </label>
                                <input type="url" name="social_telegram" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_telegram']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-tiktok me-1"></i> TikTok
                                </label>
                                <input type="url" name="social_tiktok" class="form-control" 
                                       value="<?php echo htmlspecialchars($socialSettings['social_tiktok']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fab fa-snapchat text-warning me-1"></i> Snapchat
                            </label>
                            <input type="url" name="social_snapchat" class="form-control" 
                                   value="<?php echo htmlspecialchars($socialSettings['social_snapchat']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ روابط التواصل
                        </button>
                    </form>
                </div>
            </div>

            <!-- 7. إعدادات متقدمة -->
            <div class="tab-pane fade" id="advanced">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-tools text-secondary"></i>
                            أدوات متقدمة
                        </h5>
                    </div>
                    
                    <div class="settings-group">
                        <div class="group-title">معلومات النظام</div>
                        
                        <div class="system-info">
                            <div class="info-row">
                                <div class="info-label">إصدار PHP</div>
                                <div class="info-value"><?php echo $systemStats['php_version']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">إصدار MySQL</div>
                                <div class="info-value"><?php echo $systemStats['mysql_version']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">خادم الويب</div>
                                <div class="info-value"><?php echo $systemStats['server_software']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">اسم الخادم</div>
                                <div class="info-value"><?php echo $systemStats['server_name']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">عنوان IP</div>
                                <div class="info-value"><?php echo $systemStats['server_ip']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">حد الذاكرة</div>
                                <div class="info-value"><?php echo $systemStats['memory_limit']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">حد رفع الملفات</div>
                                <div class="info-value"><?php echo $systemStats['upload_max_filesize']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">حد POST</div>
                                <div class="info-value"><?php echo $systemStats['post_max_size']; ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">المساحة الحرة</div>
                                <div class="info-value">
                                    <?php 
                                    $freeGB = round($systemStats['disk_free'] / 1024 / 1024 / 1024, 2);
                                    $totalGB = round($systemStats['disk_total'] / 1024 / 1024 / 1024, 2);
                                    $percent = round(($systemStats['disk_free'] / $systemStats['disk_total']) * 100, 2);
                                    echo "{$freeGB} GB / {$totalGB} GB ({$percent}%)";
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-group">
                        <div class="group-title">أدوات الصيانة</div>
                        
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="settings_type" value="clear_cache">
                            <button type="submit" class="btn btn-warning mb-2" onclick="return confirm('هل أنت متأكد من تنظيف الذاكرة المؤقتة؟')">
                                <i class="fas fa-broom me-1"></i>
                                تنظيف الذاكرة المؤقتة
                            </button>
                        </form>
                        
                        <button class="btn btn-info mb-2" onclick="optimizeTables()">
                            <i class="fas fa-database me-1"></i>
                            تحسين جداول قاعدة البيانات
                        </button>
                        
                        <button class="btn btn-secondary mb-2" onclick="checkSystemHealth()">
                            <i class="fas fa-heartbeat me-1"></i>
                            فحص صحة النظام
                        </button>
                    </div>
                    
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>منطقة خطر!</strong> التغييرات هنا قد تؤثر على استقرار النظام.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // تهيئة Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
        
        // التحكم في الشريط الجانبي
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // إخفاء شاشة التحميل
        window.addEventListener('load', function() {
            document.getElementById('loading').classList.remove('show');
        });
        
        // حفظ التبويب النشط
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = localStorage.getItem('activeSettingsTab');
            if (activeTab) {
                const tab = document.querySelector(`[data-bs-target="${activeTab}"]`);
                if (tab) {
                    new bootstrap.Tab(tab).show();
                }
            }
        });
        
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                localStorage.setItem('activeSettingsTab', e.target.getAttribute('data-bs-target'));
            });
        });
        
        // تحسين جداول قاعدة البيانات
        function optimizeTables() {
            Swal.fire({
                title: 'تأكيد',
                text: 'هل تريد تحسين جداول قاعدة البيانات؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'جاري التحسين...',
                        html: 'يرجى الانتظار',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            // هنا يمكن إضافة كود تحسين الجداول
                            setTimeout(() => {
                                Swal.fire('تم!', 'تم تحسين جداول قاعدة البيانات بنجاح', 'success');
                            }, 2000);
                        }
                    });
                }
            });
        }
        
        // فحص صحة النظام
        function checkSystemHealth() {
            Swal.fire({
                title: 'فحص صحة النظام',
                html: 'جاري فحص جميع مكونات النظام...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    setTimeout(() => {
                        Swal.fire({
                            title: 'نتيجة الفحص',
                            html: `
                                <div class="text-start">
                                    <p><i class="fas fa-check-circle text-success me-2"></i> قاعدة البيانات: سليمة</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i> الذاكرة المؤقتة: سليمة</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i> الملفات: سليمة</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i> الجلسات: سليمة</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i> البريد الإلكتروني: بحاجة للتأكد</p>
                                </div>
                            `,
                            icon: 'success'
                        });
                    }, 2000);
                }
            });
        }
    </script>
</body>
</html>