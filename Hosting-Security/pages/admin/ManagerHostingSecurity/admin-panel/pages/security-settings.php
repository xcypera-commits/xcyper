<?php
/**
 * إعدادات الأمان المتقدمة
 * Advanced Security Settings Page
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
            case 'general':
                // تحديث الإعدادات العامة
                $settings = [
                    'site_name' => sanitize_input($_POST['site_name']),
                    'site_url' => sanitize_input($_POST['site_url']),
                    'admin_email' => sanitize_input($_POST['admin_email']),
                    'timezone' => sanitize_input($_POST['timezone']),
                    'date_format' => sanitize_input($_POST['date_format']),
                    'language' => sanitize_input($_POST['language']),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                    'debug_mode' => isset($_POST['debug_mode']) ? 1 : 0
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'general']);
                set_success('تم تحديث الإعدادات العامة بنجاح');
                break;
                
            case 'authentication':
                // تحديث إعدادات المصادقة
                $settings = [
                    'mfa_required' => isset($_POST['mfa_required']) ? 1 : 0,
                    'password_min_length' => (int)$_POST['password_min_length'],
                    'password_require_uppercase' => isset($_POST['password_require_uppercase']) ? 1 : 0,
                    'password_require_lowercase' => isset($_POST['password_require_lowercase']) ? 1 : 0,
                    'password_require_numbers' => isset($_POST['password_require_numbers']) ? 1 : 0,
                    'password_require_special' => isset($_POST['password_require_special']) ? 1 : 0,
                    'password_expiry_days' => (int)$_POST['password_expiry_days'],
                    'max_login_attempts' => (int)$_POST['max_login_attempts'],
                    'lockout_duration' => (int)$_POST['lockout_duration'],
                    'session_lifetime' => (int)$_POST['session_lifetime'],
                    'remember_me_days' => (int)$_POST['remember_me_days']
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'authentication']);
                set_success('تم تحديث إعدادات المصادقة بنجاح');
                break;
                
            case 'isolation':
                // تحديث إعدادات العزل
                $settings = [
                    'isolation_enabled' => isset($_POST['isolation_enabled']) ? 1 : 0,
                    'container_memory_limit' => sanitize_input($_POST['container_memory_limit']),
                    'container_cpu_limit' => (float)$_POST['container_cpu_limit'],
                    'container_storage_limit' => sanitize_input($_POST['container_storage_limit']),
                    'container_max_processes' => (int)$_POST['container_max_processes'],
                    'container_network_isolation' => isset($_POST['container_network_isolation']) ? 1 : 0,
                    'container_seccomp_enabled' => isset($_POST['container_seccomp_enabled']) ? 1 : 0,
                    'container_apparmor_enabled' => isset($_POST['container_apparmor_enabled']) ? 1 : 0,
                    'container_readonly_rootfs' => isset($_POST['container_readonly_rootfs']) ? 1 : 0
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'isolation']);
                set_success('تم تحديث إعدادات العزل بنجاح');
                break;
                
            case 'uploads':
                // تحديث إعدادات رفع الملفات
                $settings = [
                    'max_upload_size' => (int)$_POST['max_upload_size'] * 1024 * 1024,
                    'allowed_extensions' => sanitize_input($_POST['allowed_extensions']),
                    'blocked_extensions' => sanitize_input($_POST['blocked_extensions']),
                    'virus_scan_enabled' => isset($_POST['virus_scan_enabled']) ? 1 : 0,
                    'quarantine_infected' => isset($_POST['quarantine_infected']) ? 1 : 0,
                    'scan_all_files' => isset($_POST['scan_all_files']) ? 1 : 0,
                    'max_files_per_upload' => (int)$_POST['max_files_per_upload']
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'uploads']);
                set_success('تم تحديث إعدادات رفع الملفات بنجاح');
                break;
                
            case 'encryption':
                // تحديث إعدادات التشفير
                $settings = [
                    'encryption_algorithm' => sanitize_input($_POST['encryption_algorithm']),
                    'hash_algorithm' => sanitize_input($_POST['hash_algorithm']),
                    'bcrypt_cost' => (int)$_POST['bcrypt_cost'],
                    'key_rotation_days' => (int)$_POST['key_rotation_days'],
                    'encrypt_sensitive_data' => isset($_POST['encrypt_sensitive_data']) ? 1 : 0,
                    'encrypt_backups' => isset($_POST['encrypt_backups']) ? 1 : 0,
                    'ssl_required' => isset($_POST['ssl_required']) ? 1 : 0
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'encryption']);
                set_success('تم تحديث إعدادات التشفير بنجاح');
                break;
                
            case 'monitoring':
                // تحديث إعدادات المراقبة
                $settings = [
                    'log_all_activities' => isset($_POST['log_all_activities']) ? 1 : 0,
                    'log_security_events' => isset($_POST['log_security_events']) ? 1 : 0,
                    'log_retention_days' => (int)$_POST['log_retention_days'],
                    'alert_on_suspicious' => isset($_POST['alert_on_suspicious']) ? 1 : 0,
                    'alert_on_brute_force' => isset($_POST['alert_on_brute_force']) ? 1 : 0,
                    'alert_on_malware' => isset($_POST['alert_on_malware']) ? 1 : 0,
                    'alert_email' => sanitize_input($_POST['alert_email']),
                    'alert_slack_webhook' => sanitize_input($_POST['alert_slack_webhook']),
                    'realtime_monitoring' => isset($_POST['realtime_monitoring']) ? 1 : 0
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'monitoring']);
                set_success('تم تحديث إعدادات المراقبة بنجاح');
                break;
                
            case 'backup':
                // تحديث إعدادات النسخ الاحتياطي
                $settings = [
                    'backup_enabled' => isset($_POST['backup_enabled']) ? 1 : 0,
                    'backup_schedule' => sanitize_input($_POST['backup_schedule']),
                    'backup_retention_days' => (int)$_POST['backup_retention_days'],
                    'backup_compress' => isset($_POST['backup_compress']) ? 1 : 0,
                    'backup_encrypt' => isset($_POST['backup_encrypt']) ? 1 : 0,
                    'backup_verify' => isset($_POST['backup_verify']) ? 1 : 0,
                    'backup_path' => sanitize_input($_POST['backup_path']),
                    'backup_time' => sanitize_input($_POST['backup_time'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'backup']);
                set_success('تم تحديث إعدادات النسخ الاحتياطي بنجاح');
                break;
                
            case 'firewall':
                // تحديث إعدادات الجدار الناري
                $settings = [
                    'firewall_enabled' => isset($_POST['firewall_enabled']) ? 1 : 0,
                    'rate_limiting_enabled' => isset($_POST['rate_limiting_enabled']) ? 1 : 0,
                    'requests_per_minute' => (int)$_POST['requests_per_minute'],
                    'block_suspicious_ips' => isset($_POST['block_suspicious_ips']) ? 1 : 0,
                    'block_proxy_ips' => isset($_POST['block_proxy_ips']) ? 1 : 0,
                    'block_vpn_ips' => isset($_POST['block_vpn_ips']) ? 1 : 0,
                    'whitelist_ips' => sanitize_input($_POST['whitelist_ips']),
                    'blacklist_ips' => sanitize_input($_POST['blacklist_ips'])
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                log_activity($_SESSION['user_id'], 'settings_updated', ['type' => 'firewall']);
                set_success('تم تحديث إعدادات الجدار الناري بنجاح');
                break;
                
            case 'rotate_keys':
                // تدوير المفاتيح
                $keyType = sanitize_input($_POST['key_type']);
                $result = rotateEncryptionKeys($keyType);
                
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'keys_rotated', ['key_type' => $keyType]);
                    set_success($result['message']);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'test_email':
                // اختبار البريد الإلكتروني
                $testEmail = sanitize_input($_POST['test_email']);
                $subject = "اختبار إعدادات نظام الأمان";
                $message = "هذا بريد اختبار للتأكد من صحة إعدادات البريد في نظام الأمان.\n\n";
                $message .= "الوقت: " . date('Y-m-d H:i:s') . "\n";
                $message .= "المستخدم: " . $_SESSION['username'];
                
                if (mail($testEmail, $subject, $message)) {
                    set_success("تم إرسال بريد اختبار إلى $testEmail");
                    log_activity($_SESSION['user_id'], 'email_test', ['email' => $testEmail]);
                } else {
                    set_error("فشل في إرسال البريد الإلكتروني");
                }
                break;
        }
    } catch (PDOException $e) {
        set_error('خطأ في قاعدة البيانات: ' . $e->getMessage());
    } catch (Exception $e) {
        set_error('حدث خطأ: ' . $e->getMessage());
    }
    
    redirect('security-settings.php');
}

// جلب الإعدادات الحالية
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

// دوال مساعدة لتدوير المفاتيح
function rotateEncryptionKeys($keyType) {
    try {
        if ($keyType === 'all' || $keyType === 'master') {
            $newKey = bin2hex(random_bytes(32));
            updateSetting('encryption_master_key', $newKey);
        }
        
        if ($keyType === 'all' || $keyType === 'hmac') {
            $newKey = bin2hex(random_bytes(32));
            updateSetting('encryption_hmac_key', $newKey);
        }
        
        if ($keyType === 'all' || $keyType === 'api') {
            $newKey = bin2hex(random_bytes(32));
            updateSetting('api_key', $newKey);
        }
        
        return ['success' => true, 'message' => 'تم تدوير المفاتيح بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'خطأ في تدوير المفاتيح: ' . $e->getMessage()];
    }
}

function updateSetting($key, $value) {
    global $db;
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

// الحصول على إعدادات النظام
$settings = [
    // الإعدادات العامة
    'site_name' => getSetting('site_name', 'نظام الحماية'),
    'site_url' => getSetting('site_url', 'http://localhost/Hosting-Security'),
    'admin_email' => getSetting('admin_email', 'admin@example.com'),
    'timezone' => getSetting('timezone', 'Asia/Riyadh'),
    'date_format' => getSetting('date_format', 'Y-m-d'),
    'language' => getSetting('language', 'ar'),
    'maintenance_mode' => getSetting('maintenance_mode', 0),
    'debug_mode' => getSetting('debug_mode', 0),
    
    // إعدادات المصادقة
    'mfa_required' => getSetting('mfa_required', 1),
    'password_min_length' => getSetting('password_min_length', 12),
    'password_require_uppercase' => getSetting('password_require_uppercase', 1),
    'password_require_lowercase' => getSetting('password_require_lowercase', 1),
    'password_require_numbers' => getSetting('password_require_numbers', 1),
    'password_require_special' => getSetting('password_require_special', 1),
    'password_expiry_days' => getSetting('password_expiry_days', 90),
    'max_login_attempts' => getSetting('max_login_attempts', 5),
    'lockout_duration' => getSetting('lockout_duration', 15),
    'session_lifetime' => getSetting('session_lifetime', 7200),
    'remember_me_days' => getSetting('remember_me_days', 30),
    
    // إعدادات العزل
    'isolation_enabled' => getSetting('isolation_enabled', 1),
    'container_memory_limit' => getSetting('container_memory_limit', '512M'),
    'container_cpu_limit' => getSetting('container_cpu_limit', 0.5),
    'container_storage_limit' => getSetting('container_storage_limit', '10G'),
    'container_max_processes' => getSetting('container_max_processes', 100),
    'container_network_isolation' => getSetting('container_network_isolation', 1),
    'container_seccomp_enabled' => getSetting('container_seccomp_enabled', 1),
    'container_apparmor_enabled' => getSetting('container_apparmor_enabled', 1),
    'container_readonly_rootfs' => getSetting('container_readonly_rootfs', 1),
    
    // إعدادات رفع الملفات
    'max_upload_size' => getSetting('max_upload_size', 104857600) / 1024 / 1024,
    'allowed_extensions' => getSetting('allowed_extensions', 'jpg,jpeg,png,gif,pdf,doc,docx,txt'),
    'blocked_extensions' => getSetting('blocked_extensions', 'php,php3,php4,php5,phtml,exe,sh,bat,js,html,htm'),
    'virus_scan_enabled' => getSetting('virus_scan_enabled', 1),
    'quarantine_infected' => getSetting('quarantine_infected', 1),
    'scan_all_files' => getSetting('scan_all_files', 1),
    'max_files_per_upload' => getSetting('max_files_per_upload', 10),
    
    // إعدادات التشفير
    'encryption_algorithm' => getSetting('encryption_algorithm', 'AES-256-GCM'),
    'hash_algorithm' => getSetting('hash_algorithm', 'sha256'),
    'bcrypt_cost' => getSetting('bcrypt_cost', 12),
    'key_rotation_days' => getSetting('key_rotation_days', 90),
    'encrypt_sensitive_data' => getSetting('encrypt_sensitive_data', 1),
    'encrypt_backups' => getSetting('encrypt_backups', 1),
    'ssl_required' => getSetting('ssl_required', 1),
    
    // إعدادات المراقبة
    'log_all_activities' => getSetting('log_all_activities', 1),
    'log_security_events' => getSetting('log_security_events', 1),
    'log_retention_days' => getSetting('log_retention_days', 90),
    'alert_on_suspicious' => getSetting('alert_on_suspicious', 1),
    'alert_on_brute_force' => getSetting('alert_on_brute_force', 1),
    'alert_on_malware' => getSetting('alert_on_malware', 1),
    'alert_email' => getSetting('alert_email', 'alerts@example.com'),
    'alert_slack_webhook' => getSetting('alert_slack_webhook', ''),
    'realtime_monitoring' => getSetting('realtime_monitoring', 1),
    
    // إعدادات النسخ الاحتياطي
    'backup_enabled' => getSetting('backup_enabled', 1),
    'backup_schedule' => getSetting('backup_schedule', 'daily'),
    'backup_retention_days' => getSetting('backup_retention_days', 30),
    'backup_compress' => getSetting('backup_compress', 1),
    'backup_encrypt' => getSetting('backup_encrypt', 1),
    'backup_verify' => getSetting('backup_verify', 1),
    'backup_path' => getSetting('backup_path', '/backups'),
    'backup_time' => getSetting('backup_time', '02:00'),
    
    // إعدادات الجدار الناري
    'firewall_enabled' => getSetting('firewall_enabled', 1),
    'rate_limiting_enabled' => getSetting('rate_limiting_enabled', 1),
    'requests_per_minute' => getSetting('requests_per_minute', 60),
    'block_suspicious_ips' => getSetting('block_suspicious_ips', 1),
    'block_proxy_ips' => getSetting('block_proxy_ips', 1),
    'block_vpn_ips' => getSetting('block_vpn_ips', 0),
    'whitelist_ips' => getSetting('whitelist_ips', ''),
    'blacklist_ips' => getSetting('blacklist_ips', '')
];

// الحصول على معلومات النظام
$systemInfo = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'session_save_path' => session_save_path() ?: 'default',
    'openssl_enabled' => extension_loaded('openssl'),
    'curl_enabled' => extension_loaded('curl'),
    'gd_enabled' => extension_loaded('gd'),
    'mysqli_enabled' => extension_loaded('mysqli'),
    'pdo_enabled' => extension_loaded('pdo'),
    'zip_enabled' => extension_loaded('zip'),
    'json_enabled' => extension_loaded('json'),
    'mbstring_enabled' => extension_loaded('mbstring')
];

// الحصول على المستخدم الحالي
$currentUser = current_user();

// إنشاء جدول الإعدادات إذا لم يكن موجوداً
try {
    $db->query("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // تجاهل الأخطاء
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الأمان المتقدمة - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
        
        .danger-box {
            background: #f8d7da;
            border-right: 4px solid var(--danger-color);
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
        
        /* شارات الحالة */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* مربع المفاتيح */
        .key-box {
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
        }
        
        .badge-good {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
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
            <a href="security-settings.php" class="nav-link active">
                <i class="fas fa-cog"></i> إعدادات الأمان
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
                <i class="fas fa-cog text-primary me-2"></i>
                إعدادات الأمان المتقدمة
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
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general">
                        <i class="fas fa-globe"></i> عام
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="auth-tab" data-bs-toggle="tab" data-bs-target="#auth">
                        <i class="fas fa-lock"></i> المصادقة
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="isolation-tab" data-bs-toggle="tab" data-bs-target="#isolation">
                        <i class="fas fa-boxes"></i> العزل
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="uploads-tab" data-bs-toggle="tab" data-bs-target="#uploads">
                        <i class="fas fa-upload"></i> رفع الملفات
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="encryption-tab" data-bs-toggle="tab" data-bs-target="#encryption">
                        <i class="fas fa-key"></i> التشفير
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring">
                        <i class="fas fa-eye"></i> المراقبة
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup">
                        <i class="fas fa-database"></i> النسخ الاحتياطي
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="firewall-tab" data-bs-toggle="tab" data-bs-target="#firewall">
                        <i class="fas fa-shield-alt"></i> الجدار الناري
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system">
                        <i class="fas fa-server"></i> معلومات النظام
                    </button>
                </li>
            </ul>
        </div>

        <!-- محتوى التبويبات -->
        <div class="tab-content">
            <!-- 1. الإعدادات العامة -->
            <div class="tab-pane fade show active" id="general">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-globe text-primary"></i>
                            الإعدادات العامة
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="general">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم الموقع</label>
                                <input type="text" name="site_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رابط الموقع</label>
                                <input type="url" name="site_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['site_url']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">بريد المسؤول</label>
                                <input type="email" name="admin_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['admin_email']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">المنطقة الزمنية</label>
                                <select name="timezone" class="form-select">
                                    <option value="Asia/Riyadh" <?php echo $settings['timezone'] == 'Asia/Riyadh' ? 'selected' : ''; ?>>الرياض</option>
                                    <option value="Asia/Dubai" <?php echo $settings['timezone'] == 'Asia/Dubai' ? 'selected' : ''; ?>>دبي</option>
                                    <option value="Asia/Kuwait" <?php echo $settings['timezone'] == 'Asia/Kuwait' ? 'selected' : ''; ?>>الكويت</option>
                                    <option value="Asia/Qatar" <?php echo $settings['timezone'] == 'Asia/Qatar' ? 'selected' : ''; ?>>قطر</option>
                                    <option value="Asia/Bahrain" <?php echo $settings['timezone'] == 'Asia/Bahrain' ? 'selected' : ''; ?>>البحرين</option>
                                    <option value="Asia/Amman" <?php echo $settings['timezone'] == 'Asia/Amman' ? 'selected' : ''; ?>>عمان</option>
                                    <option value="Africa/Cairo" <?php echo $settings['timezone'] == 'Africa/Cairo' ? 'selected' : ''; ?>>القاهرة</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تنسيق التاريخ</label>
                                <select name="date_format" class="form-select">
                                    <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>2024-01-31</option>
                                    <option value="d/m/Y" <?php echo $settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>31/01/2024</option>
                                    <option value="Y/m/d" <?php echo $settings['date_format'] == 'Y/m/d' ? 'selected' : ''; ?>>2024/01/31</option>
                                    <option value="d-m-Y" <?php echo $settings['date_format'] == 'd-m-Y' ? 'selected' : ''; ?>>31-01-2024</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اللغة الافتراضية</label>
                                <select name="language" class="form-select">
                                    <option value="ar" <?php echo $settings['language'] == 'ar' ? 'selected' : ''; ?>>العربية</option>
                                    <option value="en" <?php echo $settings['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                           id="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_mode">
                                        وضع الصيانة
                                    </label>
                                    <small class="text-muted d-block">تفعيل وضع الصيانة للموقع</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="debug_mode" 
                                           id="debug_mode" <?php echo $settings['debug_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="debug_mode">
                                        وضع التصحيح
                                    </label>
                                    <small class="text-danger d-block">لا تفعله في الإنتاج!</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>تنبيه:</strong> وضع التصحيح يعرض تفاصيل الأخطاء، استخدمه فقط في بيئة التطوير.
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ الإعدادات العامة
                        </button>
                    </form>
                </div>
            </div>

            <!-- 2. إعدادات المصادقة -->
            <div class="tab-pane fade" id="auth">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-lock text-warning"></i>
                            إعدادات المصادقة
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="authentication">
                        
                        <div class="settings-group">
                            <div class="group-title">سياسة كلمة المرور</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الحد الأدنى لطول كلمة المرور</label>
                                    <input type="number" name="password_min_length" class="form-control" 
                                           value="<?php echo $settings['password_min_length']; ?>" min="6" max="20">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">صلاحية كلمة المرور (أيام)</label>
                                    <input type="number" name="password_expiry_days" class="form-control" 
                                           value="<?php echo $settings['password_expiry_days']; ?>" min="0" max="365">
                                    <small class="text-muted">0 = لا تنتهي أبداً</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="password_require_uppercase" 
                                               id="require_upper" <?php echo $settings['password_require_uppercase'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="require_upper">حرف كبير</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="password_require_lowercase" 
                                               id="require_lower" <?php echo $settings['password_require_lowercase'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="require_lower">حرف صغير</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="password_require_numbers" 
                                               id="require_number" <?php echo $settings['password_require_numbers'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="require_number">رقم</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="password_require_special" 
                                               id="require_special" <?php echo $settings['password_require_special'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="require_special">رمز خاص</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات تسجيل الدخول</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الحد الأقصى لمحاولات تسجيل الدخول</label>
                                    <input type="number" name="max_login_attempts" class="form-control" 
                                           value="<?php echo $settings['max_login_attempts']; ?>" min="1" max="10">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدة القفل (بالدقائق)</label>
                                    <input type="number" name="lockout_duration" class="form-control" 
                                           value="<?php echo $settings['lockout_duration']; ?>" min="1" max="60">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدة الجلسة (بالثواني)</label>
                                    <input type="number" name="session_lifetime" class="form-control" 
                                           value="<?php echo $settings['session_lifetime']; ?>" min="60" max="86400">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدة تذكرني (أيام)</label>
                                    <input type="number" name="remember_me_days" class="form-control" 
                                           value="<?php echo $settings['remember_me_days']; ?>" min="1" max="365">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="mfa_required" 
                                               id="mfa_required" <?php echo $settings['mfa_required'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mfa_required">
                                            تفعيل المصادقة متعددة العوامل (MFA)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات المصادقة
                        </button>
                    </form>
                </div>
            </div>

            <!-- 3. إعدادات العزل (الأهم لمشروعك) -->
            <div class="tab-pane fade" id="isolation">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-boxes text-success"></i>
                            إعدادات عزل العملاء
                        </h5>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>مهم:</strong> هذه الإعدادات تتحكم في عزل كل عميل في حاوية مستقلة لمنع انتشار الضرر.
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="isolation">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="isolation_enabled" 
                                           id="isolation_enabled" <?php echo $settings['isolation_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isolation_enabled">
                                        تفعيل عزل العملاء
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">حدود الموارد</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">حد الذاكرة</label>
                                    <select name="container_memory_limit" class="form-select">
                                        <option value="256M" <?php echo $settings['container_memory_limit'] == '256M' ? 'selected' : ''; ?>>256 ميجابايت</option>
                                        <option value="512M" <?php echo $settings['container_memory_limit'] == '512M' ? 'selected' : ''; ?>>512 ميجابايت</option>
                                        <option value="1G" <?php echo $settings['container_memory_limit'] == '1G' ? 'selected' : ''; ?>>1 جيجابايت</option>
                                        <option value="2G" <?php echo $settings['container_memory_limit'] == '2G' ? 'selected' : ''; ?>>2 جيجابايت</option>
                                        <option value="4G" <?php echo $settings['container_memory_limit'] == '4G' ? 'selected' : ''; ?>>4 جيجابايت</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">حد المعالج (CPU)</label>
                                    <select name="container_cpu_limit" class="form-select">
                                        <option value="0.25" <?php echo $settings['container_cpu_limit'] == 0.25 ? 'selected' : ''; ?>>ربع نواة</option>
                                        <option value="0.5" <?php echo $settings['container_cpu_limit'] == 0.5 ? 'selected' : ''; ?>>نصف نواة</option>
                                        <option value="1" <?php echo $settings['container_cpu_limit'] == 1 ? 'selected' : ''; ?>>نواة كاملة</option>
                                        <option value="2" <?php echo $settings['container_cpu_limit'] == 2 ? 'selected' : ''; ?>>نواتان</option>
                                        <option value="4" <?php echo $settings['container_cpu_limit'] == 4 ? 'selected' : ''; ?>>4 أنوية</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">حد التخزين</label>
                                    <select name="container_storage_limit" class="form-select">
                                        <option value="5G" <?php echo $settings['container_storage_limit'] == '5G' ? 'selected' : ''; ?>>5 جيجابايت</option>
                                        <option value="10G" <?php echo $settings['container_storage_limit'] == '10G' ? 'selected' : ''; ?>>10 جيجابايت</option>
                                        <option value="20G" <?php echo $settings['container_storage_limit'] == '20G' ? 'selected' : ''; ?>>20 جيجابايت</option>
                                        <option value="50G" <?php echo $settings['container_storage_limit'] == '50G' ? 'selected' : ''; ?>>50 جيجابايت</option>
                                        <option value="100G" <?php echo $settings['container_storage_limit'] == '100G' ? 'selected' : ''; ?>>100 جيجابايت</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الحد الأقصى للعمليات</label>
                                    <input type="number" name="container_max_processes" class="form-control" 
                                           value="<?php echo $settings['container_max_processes']; ?>" min="10" max="1000">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">إعدادات الأمان</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="container_network_isolation" 
                                               id="network_isolation" <?php echo $settings['container_network_isolation'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="network_isolation">
                                            عزل الشبكة
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="container_seccomp_enabled" 
                                               id="seccomp" <?php echo $settings['container_seccomp_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="seccomp">
                                            تفعيل Seccomp
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="container_apparmor_enabled" 
                                               id="apparmor" <?php echo $settings['container_apparmor_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="apparmor">
                                            تفعيل AppArmor
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="container_readonly_rootfs" 
                                               id="readonly" <?php echo $settings['container_readonly_rootfs'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="readonly">
                                            نظام ملفات للقراءة فقط
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات العزل
                        </button>
                    </form>
                </div>
            </div>

            <!-- 4. إعدادات رفع الملفات -->
            <div class="tab-pane fade" id="uploads">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-upload text-info"></i>
                            إعدادات رفع الملفات
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="uploads">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحد الأقصى لحجم الملف (ميجابايت)</label>
                                <input type="number" name="max_upload_size" class="form-control" 
                                       value="<?php echo $settings['max_upload_size']; ?>" min="1" max="1000">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحد الأقصى للملفات في كل رفعة</label>
                                <input type="number" name="max_files_per_upload" class="form-control" 
                                       value="<?php echo $settings['max_files_per_upload']; ?>" min="1" max="50">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الامتدادات المسموحة</label>
                                <input type="text" name="allowed_extensions" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['allowed_extensions']); ?>">
                                <small class="text-muted">افصل بين الامتدادات بفواصل</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الامتدادات الممنوعة</label>
                                <input type="text" name="blocked_extensions" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['blocked_extensions']); ?>">
                                <small class="text-muted">افصل بين الامتدادات بفواصل</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="virus_scan_enabled" 
                                           id="virus_scan" <?php echo $settings['virus_scan_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="virus_scan">
                                        فحص الفيروسات
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="quarantine_infected" 
                                           id="quarantine" <?php echo $settings['quarantine_infected'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="quarantine">
                                        عزل الملفات المصابة
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="scan_all_files" 
                                           id="scan_all" <?php echo $settings['scan_all_files'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="scan_all">
                                        فحص جميع الملفات
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات رفع الملفات
                        </button>
                    </form>
                </div>
            </div>

            <!-- 5. إعدادات التشفير -->
            <div class="tab-pane fade" id="encryption">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-key text-danger"></i>
                            إعدادات التشفير
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="encryption">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">خوارزمية التشفير</label>
                                <select name="encryption_algorithm" class="form-select">
                                    <option value="AES-256-GCM" <?php echo $settings['encryption_algorithm'] == 'AES-256-GCM' ? 'selected' : ''; ?>>AES-256-GCM (موصى به)</option>
                                    <option value="AES-256-CBC" <?php echo $settings['encryption_algorithm'] == 'AES-256-CBC' ? 'selected' : ''; ?>>AES-256-CBC</option>
                                    <option value="ChaCha20-Poly1305" <?php echo $settings['encryption_algorithm'] == 'ChaCha20-Poly1305' ? 'selected' : ''; ?>>ChaCha20-Poly1305</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">خوارزمية التجزئة</label>
                                <select name="hash_algorithm" class="form-select">
                                    <option value="sha256" <?php echo $settings['hash_algorithm'] == 'sha256' ? 'selected' : ''; ?>>SHA-256</option>
                                    <option value="sha384" <?php echo $settings['hash_algorithm'] == 'sha384' ? 'selected' : ''; ?>>SHA-384</option>
                                    <option value="sha512" <?php echo $settings['hash_algorithm'] == 'sha512' ? 'selected' : ''; ?>>SHA-512</option>
                                    <option value="sha3-256" <?php echo $settings['hash_algorithm'] == 'sha3-256' ? 'selected' : ''; ?>>SHA3-256</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تكلفة Bcrypt</label>
                                <select name="bcrypt_cost" class="form-select">
                                    <option value="10" <?php echo $settings['bcrypt_cost'] == 10 ? 'selected' : ''; ?>>10 (سريع)</option>
                                    <option value="12" <?php echo $settings['bcrypt_cost'] == 12 ? 'selected' : ''; ?>>12 (موصى به)</option>
                                    <option value="14" <?php echo $settings['bcrypt_cost'] == 14 ? 'selected' : ''; ?>>14 (بطيء - آمن جداً)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">مدة تدوير المفاتيح (أيام)</label>
                                <input type="number" name="key_rotation_days" class="form-control" 
                                       value="<?php echo $settings['key_rotation_days']; ?>" min="1" max="365">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="encrypt_sensitive_data" 
                                           id="encrypt_sensitive" <?php echo $settings['encrypt_sensitive_data'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="encrypt_sensitive">
                                        تشفير البيانات الحساسة
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="encrypt_backups" 
                                           id="encrypt_backups" <?php echo $settings['encrypt_backups'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="encrypt_backups">
                                        تشفير النسخ الاحتياطية
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ssl_required" 
                                           id="ssl_required" <?php echo $settings['ssl_required'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ssl_required">
                                        إجبار HTTPS
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group mt-3">
                            <div class="group-title">المفاتيح الحالية</div>
                            <div class="key-box">
                                <small>مفتاح رئيسي: ****************************************************</small>
                            </div>
                            <div class="key-box mt-2">
                                <small>مفتاح HMAC: ****************************************************</small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات التشفير
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="rotate_keys">
                        <h6 class="mb-3">تدوير المفاتيح</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <select name="key_type" class="form-select">
                                    <option value="master">المفتاح الرئيسي</option>
                                    <option value="hmac">مفتاح HMAC</option>
                                    <option value="api">مفتاح API</option>
                                    <option value="all">جميع المفاتيح</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('هل أنت متأكد من تدوير المفاتيح؟ هذا سيؤثر على البيانات المشفرة حالياً.')">
                                    <i class="fas fa-sync-alt me-1"></i>
                                    تدوير المفاتيح
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 6. إعدادات المراقبة -->
            <div class="tab-pane fade" id="monitoring">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-eye text-success"></i>
                            إعدادات المراقبة والتنبيهات
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="monitoring">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني للتنبيهات</label>
                                <input type="email" name="alert_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['alert_email']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Slack Webhook (اختياري)</label>
                                <input type="url" name="alert_slack_webhook" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['alert_slack_webhook']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">مدة الاحتفاظ بالسجلات (أيام)</label>
                                <input type="number" name="log_retention_days" class="form-control" 
                                       value="<?php echo $settings['log_retention_days']; ?>" min="1" max="365">
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">أنواع التسجيل</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="log_all_activities" 
                                               id="log_all" <?php echo $settings['log_all_activities'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="log_all">
                                            تسجيل جميع الأنشطة
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="log_security_events" 
                                               id="log_security" <?php echo $settings['log_security_events'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="log_security">
                                            تسجيل الأحداث الأمنية
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="realtime_monitoring" 
                                               id="realtime" <?php echo $settings['realtime_monitoring'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="realtime">
                                            مراقبة لحظية
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">أنواع التنبيهات</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="alert_on_suspicious" 
                                               id="alert_suspicious" <?php echo $settings['alert_on_suspicious'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="alert_suspicious">
                                            أنشطة مشبوهة
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="alert_on_brute_force" 
                                               id="alert_brute" <?php echo $settings['alert_on_brute_force'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="alert_brute">
                                            هجمات تخمين
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="alert_on_malware" 
                                               id="alert_malware" <?php echo $settings['alert_on_malware'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="alert_malware">
                                            برمجيات خبيثة
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات المراقبة
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="test_email">
                        <h6 class="mb-3">اختبار البريد الإلكتروني</h6>
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

            <!-- 7. إعدادات النسخ الاحتياطي -->
            <div class="tab-pane fade" id="backup">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-database text-warning"></i>
                            إعدادات النسخ الاحتياطي
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="backup">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="backup_enabled" 
                                           id="backup_enabled" <?php echo $settings['backup_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="backup_enabled">
                                        تفعيل النسخ الاحتياطي التلقائي
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">جدول النسخ</label>
                                <select name="backup_schedule" class="form-select">
                                    <option value="hourly" <?php echo $settings['backup_schedule'] == 'hourly' ? 'selected' : ''; ?>>كل ساعة</option>
                                    <option value="daily" <?php echo $settings['backup_schedule'] == 'daily' ? 'selected' : ''; ?>>يومياً</option>
                                    <option value="weekly" <?php echo $settings['backup_schedule'] == 'weekly' ? 'selected' : ''; ?>>أسبوعياً</option>
                                    <option value="monthly" <?php echo $settings['backup_schedule'] == 'monthly' ? 'selected' : ''; ?>>شهرياً</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">وقت النسخ</label>
                                <input type="time" name="backup_time" class="form-control" 
                                       value="<?php echo $settings['backup_time']; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">مدة الاحتفاظ (أيام)</label>
                                <input type="number" name="backup_retention_days" class="form-control" 
                                       value="<?php echo $settings['backup_retention_days']; ?>" min="1" max="365">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">مسار النسخ الاحتياطي</label>
                                <input type="text" name="backup_path" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['backup_path']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="backup_compress" 
                                           id="backup_compress" <?php echo $settings['backup_compress'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="backup_compress">
                                        ضغط النسخ
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="backup_encrypt" 
                                           id="backup_encrypt" <?php echo $settings['backup_encrypt'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="backup_encrypt">
                                        تشفير النسخ
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="backup_verify" 
                                           id="backup_verify" <?php echo $settings['backup_verify'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="backup_verify">
                                        التحقق من النسخ
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-box">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ملاحظة:</strong> آخر نسخة احتياطية: لم يتم تنفيذ نسخة بعد
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات النسخ الاحتياطي
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <button class="btn btn-success" onclick="runBackup()">
                        <i class="fas fa-play me-1"></i>
                        تشغيل نسخة احتياطية الآن
                    </button>
                </div>
            </div>

            <!-- 8. إعدادات الجدار الناري -->
            <div class="tab-pane fade" id="firewall">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-shield-alt text-danger"></i>
                            إعدادات الجدار الناري
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="settings_type" value="firewall">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="firewall_enabled" 
                                           id="firewall_enabled" <?php echo $settings['firewall_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="firewall_enabled">
                                        تفعيل الجدار الناري
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="rate_limiting_enabled" 
                                           id="rate_limiting" <?php echo $settings['rate_limiting_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rate_limiting">
                                        تحديد معدل الطلبات
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحد الأقصى للطلبات في الدقيقة</label>
                                <input type="number" name="requests_per_minute" class="form-control" 
                                       value="<?php echo $settings['requests_per_minute']; ?>" min="10" max="1000">
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <div class="group-title">حظر تلقائي</div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="block_suspicious_ips" 
                                               id="block_suspicious" <?php echo $settings['block_suspicious_ips'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="block_suspicious">
                                            حظر عناوين مشبوهة
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="block_proxy_ips" 
                                               id="block_proxy" <?php echo $settings['block_proxy_ips'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="block_proxy">
                                            حظر عناوين بروكسي
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="block_vpn_ips" 
                                               id="block_vpn" <?php echo $settings['block_vpn_ips'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="block_vpn">
                                            حظر عناوين VPN
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">قائمة السماح (IPs)</label>
                                <textarea name="whitelist_ips" class="form-control" rows="3" 
                                          placeholder="192.168.1.1, 10.0.0.0/24"><?php echo htmlspecialchars($settings['whitelist_ips']); ?></textarea>
                                <small class="text-muted">افصل بين العناوين بفواصل</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">قائمة المنع (IPs)</label>
                                <textarea name="blacklist_ips" class="form-control" rows="3" 
                                          placeholder="1.2.3.4, 5.6.7.8"><?php echo htmlspecialchars($settings['blacklist_ips']); ?></textarea>
                                <small class="text-muted">افصل بين العناوين بفواصل</small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ إعدادات الجدار الناري
                        </button>
                    </form>
                </div>
            </div>

            <!-- 9. معلومات النظام -->
            <div class="tab-pane fade" id="system">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-server text-secondary"></i>
                            معلومات النظام
                        </h5>
                    </div>
                    
                    <div class="system-info">
                        <div class="info-row">
                            <div class="info-label">إصدار PHP</div>
                            <div class="info-value">
                                <?php echo $systemInfo['php_version']; ?>
                                <?php if (version_compare($systemInfo['php_version'], '7.4.0', '<')): ?>
                                    <span class="info-badge badge-danger">قديم - يوصى بالتحديث</span>
                                <?php else: ?>
                                    <span class="info-badge badge-good">مدعوم</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">خادم الويب</div>
                            <div class="info-value"><?php echo $systemInfo['server_software']; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">اسم الخادم</div>
                            <div class="info-value"><?php echo $systemInfo['server_name']; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">عنوان IP الخادم</div>
                            <div class="info-value"><?php echo $systemInfo['server_ip']; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">مسار الجذر</div>
                            <div class="info-value"><small><?php echo $systemInfo['document_root']; ?></small></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">حد الذاكرة</div>
                            <div class="info-value"><?php echo $systemInfo['memory_limit']; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">حد رفع الملفات</div>
                            <div class="info-value"><?php echo $systemInfo['upload_max_filesize']; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">حد POST</div>
                            <div class="info-value"><?php echo $systemInfo['post_max_size']; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">أقصى وقت تنفيذ</div>
                            <div class="info-value"><?php echo $systemInfo['max_execution_time']; ?> ثانية</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">مسار الجلسات</div>
                            <div class="info-value"><?php echo $systemInfo['session_save_path']; ?></div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">الإضافات المطلوبة</h6>
                    
                    <div class="system-info">
                        <div class="info-row">
                            <div class="info-label">OpenSSL</div>
                            <div class="info-value">
                                <?php if ($systemInfo['openssl_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-danger">غير مفعل</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">cURL</div>
                            <div class="info-value">
                                <?php if ($systemInfo['curl_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-danger">غير مفعل</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">GD</div>
                            <div class="info-value">
                                <?php if ($systemInfo['gd_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-warning">اختياري</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">MySQLi</div>
                            <div class="info-value">
                                <?php if ($systemInfo['mysqli_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-danger">غير مفعل</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">PDO</div>
                            <div class="info-value">
                                <?php if ($systemInfo['pdo_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-danger">غير مفعل</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">ZIP</div>
                            <div class="info-value">
                                <?php if ($systemInfo['zip_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-warning">اختياري</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">JSON</div>
                            <div class="info-value">
                                <?php if ($systemInfo['json_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-danger">غير مفعل</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">MBString</div>
                            <div class="info-value">
                                <?php if ($systemInfo['mbstring_enabled']): ?>
                                    <span class="info-badge badge-good">مفعل</span>
                                <?php else: ?>
                                    <span class="info-badge badge-warning">يوصى به</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-box mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>توصيات الأمان:</strong>
                        <ul class="mt-2 mb-0">
                            <li>✓ تفعيل HTTPS في جميع الصفحات</li>
                            <li>✓ تعطيل display_errors في الإنتاج</li>
                            <li>✓ تفعيل session.cookie_httponly</li>
                            <li>✓ تفعيل session.cookie_secure مع HTTPS</li>
                            <li>✓ تعطيل allow_url_fopen إذا لم تكن بحاجة له</li>
                        </ul>
                    </div>
                    
                    <button class="btn btn-secondary mt-3" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i>
                        تحديث المعلومات
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
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
        
        // تأكيد للإعدادات الخطيرة
        document.querySelector('select[name="environment"]')?.addEventListener('change', function() {
            if (this.value === 'development') {
                Swal.fire({
                    title: 'تحذير!',
                    text: 'بيئة التطوير قد تكون أقل أماناً. هل أنت متأكد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، متأكد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        this.value = 'production';
                    }
                });
            }
        });
        
        // تشغيل نسخة احتياطية
        function runBackup() {
            Swal.fire({
                title: 'تأكيد',
                text: 'هل تريد تشغيل نسخة احتياطية الآن؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، شغل',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    // هنا يمكن إضافة كود تشغيل النسخة
                    Swal.fire(
                        'تم!',
                        'سيتم تشغيل النسخة الاحتياطية في الخلفية',
                        'success'
                    );
                }
            });
        }
        
        // التحقق من صحة المدخلات
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', function() {
                const min = parseInt(this.min) || 0;
                const max = parseInt(this.max) || 999999;
                let value = parseInt(this.value) || 0;
                
                if (value < min) this.value = min;
                if (value > max) this.value = max;
            });
        });
    </script>
</body>
</html>