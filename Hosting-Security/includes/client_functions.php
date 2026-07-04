<?php
/**
 * دوال خاصة بالعملاء
 */

// منع الوصول المباشر
if (!defined('SECURITY_ACCESS')) {
    define('SECURITY_ACCESS', true);
}

/**
 * الحصول على مشاريع العميل
 */
function get_client_projects($client_id) {
    // محاكاة - استبدلها بقاعدة البيانات
    return [
        ['id' => 1, 'name' => 'مشروع 1', 'status' => 'نشط'],
        ['id' => 2, 'name' => 'مشروع 2', 'status' => 'قيد التنفيذ']
    ];
}

/**
 * الحصول على مساحة التخزين المستخدمة
 */
function get_client_storage_used($client_id) {
    // محاكاة
    return '2.5 GB';
}

/**
 * الحصول على عدد الملفات
 */
function get_client_files_count($client_id) {
    // محاكاة
    return 150;
}
?>