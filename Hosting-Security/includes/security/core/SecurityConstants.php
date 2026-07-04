<?php
/**
 * ثوابت الأمان العامة
 * Security Constants
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityConstants {
    // مستويات الأمان
    const SECURITY_LEVEL_LOW = 1;
    const SECURITY_LEVEL_MEDIUM = 2;
    const SECURITY_LEVEL_HIGH = 3;
    const SECURITY_LEVEL_CRITICAL = 4;
    
    // أنواع الأحداث
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_UPLOAD = 'upload';
    const EVENT_DOWNLOAD = 'download';
    const EVENT_DELETE = 'delete';
    const EVENT_UPDATE = 'update';
    const EVENT_BACKUP = 'backup';
    const EVENT_RESTORE = 'restore';
    const EVENT_ISOLATE = 'isolate';
    const EVENT_QUARANTINE = 'quarantine';
    
    // أنواع التهديدات
    const THREAT_MALWARE = 'malware';
    const THREAT_BRUTE_FORCE = 'brute_force';
    const THREAT_SQL_INJECTION = 'sql_injection';
    const THREAT_XSS = 'xss';
    const THREAT_CONTAINER_ESCAPE = 'container_escape';
    const THREAT_DDOS = 'ddos';
    const THREAT_UNAUTHORIZED = 'unauthorized';
    
    // حالات العزل
    const ISOLATION_NONE = 'none';
    const ISOLATION_ACTIVE = 'active';
    const ISOLATION_QUARANTINED = 'quarantined';
    const ISOLATION_TERMINATED = 'terminated';
    
    // أنواع المستخدمين
    const USER_CLIENT = 'client';
    const USER_ADMIN = 'admin';
    const USER_STAFF = 'staff';
    const USER_SYSTEM = 'system';
    
    // صلاحيات الوصول
    const PERMISSION_READ = 'read';
    const PERMISSION_WRITE = 'write';
    const PERMISSION_EXECUTE = 'execute';
    const PERMISSION_DELETE = 'delete';
    const PERMISSION_ADMIN = 'admin';
    
    // قيم زمنية (بالثواني)
    const ONE_MINUTE = 60;
    const ONE_HOUR = 3600;
    const ONE_DAY = 86400;
    const ONE_WEEK = 604800;
    const ONE_MONTH = 2592000;
}
?>