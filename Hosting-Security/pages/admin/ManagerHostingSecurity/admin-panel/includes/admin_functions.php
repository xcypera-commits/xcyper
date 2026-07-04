<?php
/**
 * دوال خاصة بالمدير
 * Admin Functions
 */

// منع الوصول المباشر
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}

// جلب متغير قاعدة البيانات العام
global $db;

// إذا كان $db غير موجود، حاول تضمين ملف قاعدة البيانات
if (!isset($db) || $db === null) {
    // المسار النسبي لملف قاعدة البيانات
    $databaseFile = __DIR__ . '/../../config/database.php';
    
    if (file_exists($databaseFile)) {
        require_once $databaseFile;
    } else {
        // محاولة مسار آخر
        $databaseFile = __DIR__ . '/../../../config/database.php';
        if (file_exists($databaseFile)) {
            require_once $databaseFile;
        }
    }
}

/**
 * الحصول على إحصائيات سريعة
 */
function get_dashboard_stats() {
    global $db;
    
    // التحقق من وجود اتصال قاعدة البيانات
    if (!isset($db) || $db === null) {
        error_log("Database connection not available in get_dashboard_stats()");
        return [
            'users' => 0,
            'active_users' => 0,
            'events_today' => 0,
            'critical_events' => 0,
            'projects' => 0,
            'active_projects' => 0,
            'storage_used' => 0,
            'storage_total' => 0
        ];
    }
    
    $stats = [
        'users' => 0,
        'active_users' => 0,
        'events_today' => 0,
        'critical_events' => 0,
        'projects' => 0,
        'active_projects' => 0,
        'storage_used' => 0,
        'storage_total' => 0
    ];
    
    try {
        // عدد المستخدمين
        $result = $db->query("SELECT COUNT(*) as total FROM users_all WHERE deleted_at IS NULL");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $stats['users'] = $row['total'] ?? 0;
        }
        
        // المستخدمين النشطين اليوم
        $result = $db->query("SELECT COUNT(DISTINCT user_id) as active FROM user_events WHERE DATE(created_at) = CURDATE()");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $stats['active_users'] = $row['active'] ?? 0;
        }
        
        // أحداث اليوم
        $result = $db->query("SELECT COUNT(*) as total FROM user_events WHERE DATE(created_at) = CURDATE()");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $stats['events_today'] = $row['total'] ?? 0;
        }
        
        // الأحداث الحرجة
        $result = $db->query("SELECT COUNT(*) as total FROM user_events WHERE severity = 'critical' AND DATE(created_at) = CURDATE()");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $stats['critical_events'] = $row['total'] ?? 0;
        }
        
        // المشاريع
        $result = $db->query("SELECT COUNT(*) as total FROM client_projects");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $stats['projects'] = $row['total'] ?? 0;
        }
        
        // المشاريع النشطة
        $result = $db->query("SELECT COUNT(*) as total FROM client_projects WHERE status = 'active'");
        if ($result) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $stats['active_projects'] = $row['total'] ?? 0;
        }
        
    } catch (PDOException $e) {
        error_log("Stats error in get_dashboard_stats(): " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * الحصول على آخر الأحداث
 */
function get_recent_events($limit = 10) {
    global $db;
    
    if (!isset($db) || $db === null) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT ue.*, ua.full_name, ua.username 
            FROM user_events ue 
            LEFT JOIN users_all ua ON ue.user_id = ua.id 
            ORDER BY ue.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_recent_events(): " . $e->getMessage());
        return [];
    }
}

/**
 * الحصول على المستخدمين
 */
function get_users($filters = []) {
    global $db;
    
    if (!isset($db) || $db === null) {
        return [];
    }
    
    $sql = "SELECT * FROM users_all WHERE deleted_at IS NULL";
    $params = [];
    
    if (!empty($filters['user_type'])) {
        $sql .= " AND user_type = ?";
        $params[] = $filters['user_type'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    $sql .= " ORDER BY id DESC";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_users(): " . $e->getMessage());
        return [];
    }
}

/**
 * إضافة مستخدم جديد
 */
function add_user($data) {
    global $db;
    
    if (!isset($db) || $db === null) {
        return ['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات'];
    }
    
    try {
        // التحقق من عدم التكرار
        $check = $db->prepare("SELECT id FROM users_all WHERE username = ? OR email = ?");
        $check->execute([$data['username'], $data['email']]);
        
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'اسم المستخدم أو البريد موجود بالفعل'];
        }
        
        $password = password_hash($data['password'] ?? 'Default@123', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO users_all 
            (uuid, username, email, password, full_name, user_source, source_id, user_type, role_id, status, created_at)
            VALUES (UUID(), ?, ?, ?, ?, 'admin', 0, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['username'],
            $data['email'],
            $password,
            $data['full_name'],
            $data['user_type'],
            $data['role_id'] ?? $data['user_type'],
            $data['status'] ?? 'active'
        ]);
        
        $userId = $db->lastInsertId();
        
        // تسجيل النشاط
        if (isset($_SESSION['user_id'])) {
            log_activity($_SESSION['user_id'], 'user_created', ['user_id' => $userId, 'username' => $data['username']]);
        }
        
        return ['success' => true, 'message' => 'تم إضافة المستخدم بنجاح', 'id' => $userId];
        
    } catch (PDOException $e) {
        error_log("Error in add_user(): " . $e->getMessage());
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

/**
 * تحديث مستخدم
 */
function update_user($id, $data) {
    global $db;
    
    if (!isset($db) || $db === null) {
        return ['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات'];
    }
    
    try {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'password') {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $params[] = $id;
        
        $sql = "UPDATE users_all SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if (isset($_SESSION['user_id'])) {
            log_activity($_SESSION['user_id'], 'user_updated', ['user_id' => $id]);
        }
        
        return ['success' => true, 'message' => 'تم تحديث المستخدم بنجاح'];
        
    } catch (PDOException $e) {
        error_log("Error in update_user(): " . $e->getMessage());
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

/**
 * حذف مستخدم
 */
function delete_user($id) {
    global $db;
    
    if (!isset($db) || $db === null) {
        return ['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات'];
    }
    
    try {
        // لا يمكن حذف المدير الرئيسي
        if ($id == 1) {
            return ['success' => false, 'message' => 'لا يمكن حذف المدير الرئيسي'];
        }
        
        $stmt = $db->prepare("UPDATE users_all SET deleted_at = NOW(), status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);
        
        if (isset($_SESSION['user_id'])) {
            log_activity($_SESSION['user_id'], 'user_deleted', ['user_id' => $id]);
        }
        
        return ['success' => true, 'message' => 'تم حذف المستخدم بنجاح'];
        
    } catch (PDOException $e) {
        error_log("Error in delete_user(): " . $e->getMessage());
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

/**
 * الحصول على إحصائيات المستخدمين
 */
function get_user_stats() {
    global $db;
    
    if (!isset($db) || $db === null) {
        return [
            'by_type' => [],
            'by_status' => [],
            'by_source' => [],
            'new_this_month' => 0
        ];
    }
    
    $stats = [
        'by_type' => [],
        'by_status' => [],
        'by_source' => [],
        'new_this_month' => 0
    ];
    
    try {
        // حسب النوع
        $result = $db->query("
            SELECT user_type, COUNT(*) as count 
            FROM users_all 
            WHERE deleted_at IS NULL 
            GROUP BY user_type
        ");
        $stats['by_type'] = $result->fetchAll(PDO::FETCH_ASSOC);
        
        // حسب الحالة
        $result = $db->query("
            SELECT status, COUNT(*) as count 
            FROM users_all 
            WHERE deleted_at IS NULL 
            GROUP BY status
        ");
        $stats['by_status'] = $result->fetchAll(PDO::FETCH_ASSOC);
        
        // حسب المصدر
        $result = $db->query("
            SELECT user_source, COUNT(*) as count 
            FROM users_all 
            WHERE deleted_at IS NULL 
            GROUP BY user_source
        ");
        $stats['by_source'] = $result->fetchAll(PDO::FETCH_ASSOC);
        
        // المستخدمين الجدد هذا الشهر
        $result = $db->query("
            SELECT COUNT(*) as count 
            FROM users_all 
            WHERE MONTH(created_at) = MONTH(NOW()) 
            AND YEAR(created_at) = YEAR(NOW())
        ");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $stats['new_this_month'] = $row['count'] ?? 0;
        
    } catch (PDOException $e) {
        error_log("Error in get_user_stats(): " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * البحث في سجلات التدقيق
 */
function search_audit_logs($criteria = []) {
    global $db;
    
    if (!isset($db) || $db === null) {
        return [];
    }
    
    $sql = "
        SELECT ue.*, ua.full_name, ua.username 
        FROM user_events ue 
        LEFT JOIN users_all ua ON ue.user_id = ua.id 
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($criteria['user_id'])) {
        $sql .= " AND ue.user_id = ?";
        $params[] = $criteria['user_id'];
    }
    
    if (!empty($criteria['event_type'])) {
        $sql .= " AND ue.event_type = ?";
        $params[] = $criteria['event_type'];
    }
    
    if (!empty($criteria['severity'])) {
        $sql .= " AND ue.severity = ?";
        $params[] = $criteria['severity'];
    }
    
    if (!empty($criteria['date_from'])) {
        $sql .= " AND DATE(ue.created_at) >= ?";
        $params[] = $criteria['date_from'];
    }
    
    if (!empty($criteria['date_to'])) {
        $sql .= " AND DATE(ue.created_at) <= ?";
        $params[] = $criteria['date_to'];
    }
    
    if (!empty($criteria['search'])) {
        $sql .= " AND (ue.description LIKE ? OR ue.action LIKE ?)";
        $search = "%{$criteria['search']}%";
        $params[] = $search;
        $params[] = $search;
    }
    
    $sql .= " ORDER BY ue.created_at DESC";
    
    if (!empty($criteria['limit'])) {
        $sql .= " LIMIT " . (int)$criteria['limit'];
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in search_audit_logs(): " . $e->getMessage());
        return [];
    }
}

/**
 * تسجيل نشاط
 */

?>