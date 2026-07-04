<?php
/**
 * ملف الإعدادات الأمنية الرئيسي
 * Security Main Configuration File
 */

// منع الوصول المباشر
if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

return [
    // =============================================
    // الإعدادات العامة
    // =============================================
    'general' => [
        'app_name' => 'Hosting Security System',
        'version' => '1.0.0',
        'environment' => 'production', // production, development, testing
        'debug_mode' => false,
        'timezone' => 'Asia/Riyadh',
        'session_lifetime' => 7200, // 2 hours
        'session_name' => 'secure_hosting_session',
    ],

    // =============================================
    // إعدادات المصادقة
    // =============================================
    'auth' => [
        'mfa_required' => true,
        'mfa_methods' => ['google_authenticator', 'email'],
        'password_min_length' => 12,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => true,
        'password_history' => 5,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'session_regenerate' => true,
        'remember_me_days' => 30,
    ],

    // =============================================
    // إعدادات العزل (الأهم لمشروعك)
    // =============================================
    'isolation' => [
        'enabled' => true,
        'container_driver' => 'docker', // docker, podman, lxc
        'default_memory_limit' => '512M',
        'default_cpu_limit' => 0.5,
        'max_storage_per_client' => '10G',
        'max_files_per_client' => 10000,
        'network_isolation' => true,
        'enable_seccomp' => true,
        'enable_apparmor' => true,
        'readonly_rootfs' => true,
        'max_processes' => 100,
        'max_open_files' => 1024,
    ],

    // =============================================
    // إعدادات رفع الملفات
    // =============================================
    'uploads' => [
        'max_file_size' => 100 * 1024 * 1024, // 100MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'],
        'blocked_extensions' => ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bash', 'js', 'html', 'htm'],
        'scan_all_files' => true,
        'quarantine_infected' => true,
        'virus_scan' => true,
    ],

    // =============================================
    // إعدادات المراقبة
    // =============================================
    'monitoring' => [
        'log_all_activities' => true,
        'log_security_events' => true,
        'alert_on_suspicious' => true,
        'alert_on_brute_force' => true,
        'alert_on_malware' => true,
        'alert_on_container_escape' => true,
        'notification_channels' => ['email', 'database'],
        'admin_email' => 'admin@yourdomain.com',
        'real_time_monitoring' => true,
    ],

    // =============================================
    // إعدادات الحماية
    // =============================================
    'protection' => [
        'csrf_protection' => true,
        'xss_protection' => true,
        'sql_injection_protection' => true,
        'rate_limiting' => true,
        'requests_per_minute' => 60,
        'block_suspicious_ips' => true,
        'enable_waf' => true, // Web Application Firewall
    ],

    // =============================================
    // إعدادات التشفير
    // =============================================
    'encryption' => [
        'algorithm' => 'AES-256-GCM',
        'hash_algorithm' => 'sha256',
        'bcrypt_cost' => 12,
        'key_rotation_days' => 90,
        'encrypt_sensitive_data' => true,
    ],

    // =============================================
    // إعدادات النسخ الاحتياطي
    // =============================================
    'backup' => [
        'enabled' => true,
        'schedule' => 'daily', // daily, hourly
        'retention_days' => 30,
        'compress' => true,
        'encrypt_backups' => true,
        'backup_path' => __DIR__ . '/../../../backups/',
        'verify_backup' => true,
    ],

    // =============================================
    // قوائم المنع
    // =============================================
    'blacklist' => [
        'ips' => [],
        'countries' => [], // 'KP', 'IR' etc
        'user_agents' => [],
        'referers' => [],
    ],

    // =============================================
    // قوائم السماح
    // =============================================
    'whitelist' => [
        'ips' => [],
        'countries' => [],
    ],
];
?>