<?php
/**
 * functions.php
 * دوال مساعدة عامة لوحدة الاستضافة السحابي
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    exit('لا يمكن الوصول المباشر');
}

/**
 * =============================================
 * دوال التنسيق والعرض
 * =============================================
 */

/**
 * تنسيق التاريخ بشكل عربي
 * @param string $date التاريخ
 * @param string $format صيغة العرض
 * @return string
 */
function formatDate($date, $format = 'Y/m/d') {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    
    $timestamp = strtotime($date);
    $months = [
        '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس',
        '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو',
        '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر',
        '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
    ];
    
    $ar_date = date('Y/m/d', $timestamp);
    foreach ($months as $num => $name) {
        $ar_date = str_replace("/{$num}/", "/{$name}/", $ar_date);
    }
    
    return $ar_date;
}

/**
 * تنسيق الوقت النسبي (منذ...)
 * @param string $datetime التاريخ والوقت
 * @return string
 */
function timeAgo($datetime) {
    if (!$datetime) return '-';
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'منذ لحظات';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "منذ {$mins} " . ($mins == 1 ? 'دقيقة' : 'دقائق');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "منذ {$hours} " . ($hours == 1 ? 'ساعة' : 'ساعات');
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "منذ {$days} " . ($days == 1 ? 'يوم' : 'أيام');
    } elseif ($diff < 31104000) {
        $months = floor($diff / 2592000);
        return "منذ {$months} " . ($months == 1 ? 'شهر' : 'أشهر');
    } else {
        $years = floor($diff / 31104000);
        return "منذ {$years} " . ($years == 1 ? 'سنة' : 'سنوات');
    }
}

/**
 * تنسيق حجم الملف
 * @param int $bytes الحجم بالبايت
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes === null || $bytes === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * تنسيق النسبة المئوية
 * @param float $value القيمة
 * @param int $decimals عدد المنازل العشرية
 * @return string
 */
function formatPercent($value, $decimals = 1) {
    return number_format($value, $decimals) . '%';
}

/**
 * =============================================
 * دوال التحقق والتنظيف
 * =============================================
 */

/**
 * تنظيف مدخلات النصوص
 * @param string $data النص المدخل
 * @return string
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * التحقق من البريد الإلكتروني
 * @param string $email البريد
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من عنوان IP
 * @param string $ip عنوان IP
 * @return bool
 */
function validateIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * التحقق من اسم النطاق
 * @param string $domain اسم النطاق
 * @return bool
 */
function validateDomain($domain) {
    return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain);
}

/**
 * توليد رمز عشوائي
 * @param int $length الطول
 * @return string
 */
function generateRandomCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

/**
 * =============================================
 * دوال قواعد البيانات
 * =============================================
 */

/**
 * تنفيذ استعلام وإرجاع النتائج
 * @param string $sql الاستعلام
 * @param array $params المعاملات
 * @return array
 */
function dbQuery($sql, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'stmt' => $stmt,
            'lastId' => $db->lastInsertId()
        ];
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'حدث خطأ في قاعدة البيانات'
        ];
    }
}

/**
 * جلب سجل واحد
 * @param string $sql الاستعلام
 * @param array $params المعاملات
 * @return array|null
 */
function dbFetchOne($sql, $params = []) {
    $result = dbQuery($sql, $params);
    
    if ($result['success']) {
        return $result['stmt']->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

/**
 * جلب جميع السجلات
 * @param string $sql الاستعلام
 * @param array $params المعاملات
 * @return array
 */
function dbFetchAll($sql, $params = []) {
    $result = dbQuery($sql, $params);
    
    if ($result['success']) {
        return $result['stmt']->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return [];
}

/**
 * =============================================
 * دوال الجلسات والمستخدمين
 * =============================================
 */

/**
 * بدء الجلسة بشكل آمن
 */
function initSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * التحقق من تسجيل الدخول
 * @return bool
 */
function isLoggedIn() {
    initSession();
    
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * الحصول على معرف المستخدم الحالي
 * @return int|null
 */
function getCurrentUserId() {
    initSession();
    
    return $_SESSION['user_id'] ?? null;
}

/**
 * الحصول على صلاحيات المستخدم
 * @param int $user_id معرف المستخدم
 * @return array
 */
function getUserPermissions($user_id) {
    $permissions = [
        'admin' => ['*'],
        'manager' => ['view_all', 'create', 'edit', 'delete', 'deploy', 'backup'],
        'engineer' => ['view_assigned', 'deploy', 'backup', 'restart', 'monitor'],
        'viewer' => ['view_assigned']
    ];
    
    $user = dbFetchOne(
        "SELECT role FROM users WHERE id = ?",
        [$user_id]
    );
    
    return $permissions[$user['role']] ?? [];
}

/**
 * =============================================
 * دوال الإشعارات
 * =============================================
 */

/**
 * إضافة إشعار جديد
 * @param int $user_id المستخدم
 * @param string $title عنوان الإشعار
 * @param string $message نص الإشعار
 * @param string $type نوع الإشعار
 * @param string $link الرابط
 * @return bool
 */
function addNotification($user_id, $title, $message, $type = 'info', $link = '#') {
    $sql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $result = dbQuery($sql, [$user_id, $title, $message, $type, $link]);
    
    return $result['success'];
}

/**
 * عرض الإشعارات في الواجهة
 * @param array $notification بيانات الإشعار
 * @return string
 */
function renderNotification($notification) {
    $colors = [
        'success' => 'bg-green-500',
        'error' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500'
    ];
    
    $color = $colors[$notification['type']] ?? 'bg-blue-500';
    
    return "
    <div class='notification flex items-center p-4 mb-2 {$color} bg-opacity-20 rounded-lg border-r-4 border-{$notification['type']}-500'>
        <div class='flex-1'>
            <h4 class='font-semibold'>{$notification['title']}</h4>
            <p class='text-sm text-gray-300'>{$notification['message']}</p>
            <span class='text-xs text-gray-400'>" . timeAgo($notification['created_at']) . "</span>
        </div>
        <a href='{$notification['link']}' class='mr-4 text-blue-400 hover:text-blue-300'>
            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/>
            </svg>
        </a>
    </div>";
}

/**
 * =============================================
 * دوال الملفات والرفع
 * =============================================
 */

/**
 * رفع ملف بأمان
 * @param array $file بيانات الملف من $_FILES
 * @param string $target_dir المجلد المستهدف
 * @param array $allowed_types الأنواع المسموحة
 * @return array
 */
function uploadFile($file, $target_dir, $allowed_types = ['pdf', 'docx', 'xlsx', 'jpg', 'png', 'zip', 'tar', 'gz']) {
    $response = [
        'success' => false,
        'message' => '',
        'filename' => ''
    ];
    
    // التحقق من وجود الملف
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        $response['message'] = 'خطأ في رفع الملف';
        return $response;
    }
    
    // التحقق من نوع الملف
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        $response['message'] = 'نوع الملف غير مسموح به';
        return $response;
    }
    
    // التحقق من الحجم (100MB كحد أقصى للاستضافة)
    if ($file['size'] > 100 * 1024 * 1024) {
        $response['message'] = 'حجم الملف كبير جداً (الحد الأقصى 100MB)';
        return $response;
    }
    
    // إنشاء اسم فريد للملف
    $new_filename = uniqid() . '_' . date('Ymd') . '.' . $ext;
    $target_path = $target_dir . '/' . $new_filename;
    
    // التأكد من وجود المجلد
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $response['success'] = true;
        $response['message'] = 'تم رفع الملف بنجاح';
        $response['filename'] = $new_filename;
    } else {
        $response['message'] = 'فشل في حفظ الملف';
    }
    
    return $response;
}

/**
 * حذف ملف
 * @param string $file_path مسار الملف
 * @return bool
 */
function deleteFile($file_path) {
    if (file_exists($file_path) && is_file($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

/**
 * =============================================
 * دوال مساعدة للواجهة
 * =============================================
 */

/**
 * إنشاء قائمة منسدلة
 * @param array $options الخيارات
 * @param string $selected القيمة المحددة
 * @param string $name اسم الحقل
 * @return string
 */
function buildSelect($options, $selected = '', $name = '') {
    $html = "<select name='{$name}' class='w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500'>";
    
    foreach ($options as $value => $label) {
        $sel = ($value == $selected) ? 'selected' : '';
        $html .= "<option value='{$value}' {$sel}>{$label}</option>";
    }
    
    $html .= "</select>";
    
    return $html;
}

/**
 * إنشاء شارة حالة للخوادم
 * @param string $status الحالة
 * @return string
 */
function buildServerStatusBadge($status) {
    $colors = [
        'online' => 'bg-green-600',
        'offline' => 'bg-red-600',
        'maintenance' => 'bg-yellow-600',
        'warning' => 'bg-orange-600',
        'provisioning' => 'bg-blue-600'
    ];
    
    $labels = [
        'online' => 'نشط',
        'offline' => 'متوقف',
        'maintenance' => 'تحت الصيانة',
        'warning' => 'تحذير',
        'provisioning' => 'قيد التجهيز'
    ];
    
    $color = $colors[$status] ?? 'bg-gray-600';
    $label = $labels[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold {$color} bg-opacity-20 {$color} text-{$color}'>{$label}</span>";
}

/**
 * إنشاء شارة حالة المشروع
 * @param string $status الحالة
 * @return string
 */
function buildProjectStatusBadge($status) {
    $colors = [
        'active' => 'bg-green-600',
        'inactive' => 'bg-gray-600',
        'suspended' => 'bg-red-600',
        'maintenance' => 'bg-yellow-600',
        'deploying' => 'bg-blue-600'
    ];
    
    $labels = [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'suspended' => 'موقوف',
        'maintenance' => 'صيانة',
        'deploying' => 'جاري النشر'
    ];
    
    $color = $colors[$status] ?? 'bg-gray-600';
    $label = $labels[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold {$color} bg-opacity-20 {$color} text-{$color}'>{$label}</span>";
}

/**
 * إنشاء شارة الأولوية
 * @param string $priority الأولوية
 * @return string
 */
function buildPriorityBadge($priority) {
    $colors = [
        'critical' => 'bg-red-600',
        'high' => 'bg-orange-600',
        'medium' => 'bg-yellow-600',
        'low' => 'bg-green-600'
    ];
    
    $labels = [
        'critical' => 'حرج',
        'high' => 'عالية',
        'medium' => 'متوسطة',
        'low' => 'منخفضة'
    ];
    
    $color = $colors[$priority] ?? 'bg-gray-600';
    $label = $labels[$priority] ?? $priority;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold {$color} bg-opacity-20 {$color} text-{$color}'>{$label}</span>";
}

/**
 * إنشاء شارة نوع الخادم
 * @param string $type النوع
 * @return string
 */
function buildServerTypeBadge($type) {
    $colors = [
        'web' => 'bg-blue-600',
        'database' => 'bg-purple-600',
        'backup' => 'bg-green-600',
        'storage' => 'bg-yellow-600',
        'mail' => 'bg-red-600',
        'dns' => 'bg-indigo-600'
    ];
    
    $labels = [
        'web' => 'ويب',
        'database' => 'قاعدة بيانات',
        'backup' => 'نسخ احتياطي',
        'storage' => 'تخزين',
        'mail' => 'بريد',
        'dns' => 'DNS'
    ];
    
    $color = $colors[$type] ?? 'bg-gray-600';
    $label = $labels[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold {$color} bg-opacity-20 {$color} text-{$color}'>{$label}</span>";
}

/**
 * =============================================
 * دوال التصحيح والتسجيل
 * =============================================
 */

/**
 * تسجيل خطأ
 * @param string $message نص الخطأ
 * @param string $level مستوى الخطأ
 */
function logError($message, $level = 'ERROR') {
    $log_file = __DIR__ . '/../../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // إنشاء مجلد logs إذا لم يكن موجوداً
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * طباعة مصفوفة للت Debug
 * @param mixed $data البيانات
 * @param bool $die التوقف بعد الطباعة
 */
function debug($data, $die = true) {
    echo '<pre style="direction: ltr; background: #1e1e1e; color: #fff; padding: 10px; border-radius: 5px; margin: 10px;">';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * =============================================
 * دوال الأمان
 * =============================================
 */

/**
 * توليد توكن CSRF
 * @return string
 */
function generateCSRFToken() {
    initSession();
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من توكن CSRF
 * @param string $token التوكن
 * @return bool
 */
function verifyCSRFToken($token) {
    initSession();
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * تشفير كلمة المرور
 * @param string $password كلمة المرور
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * التحقق من كلمة المرور
 * @param string $password كلمة المرور المدخلة
 * @param string $hash الهاش المخزن
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * =============================================
 * نهاية الملف
 * =============================================
 */