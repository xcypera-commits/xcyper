<?php
/**
 * إدارة الأدوار والصلاحيات
 * Roles & Permissions Management Page
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


// معالجة الإجراءات
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_role':
                $roleId = sanitize_input($_POST['role_id']);
                $roleName = sanitize_input($_POST['role_name']);
                $description = sanitize_input($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                // التحقق من عدم وجود الدور
                $check = $db->prepare("SELECT * FROM roles WHERE role_id = ?");
                $check->execute([$roleId]);
                
                if ($check->fetch()) {
                    set_error('الدور موجود بالفعل');
                } else {
                    $stmt = $db->prepare("INSERT INTO roles (role_id, name, description, permissions, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$roleId, $roleName, $description, json_encode($permissions)]);
                    
                    log_activity($_SESSION['user_id'], 'role_created', ['role_id' => $roleId, 'name' => $roleName]);
                    set_success('تم إضافة الدور بنجاح');
                }
                break;
                
            case 'update_role':
                $roleId = sanitize_input($_POST['role_id']);
                $roleName = sanitize_input($_POST['role_name']);
                $description = sanitize_input($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                $stmt = $db->prepare("UPDATE roles SET name = ?, description = ?, permissions = ? WHERE role_id = ?");
                $stmt->execute([$roleName, $description, json_encode($permissions), $roleId]);
                
                log_activity($_SESSION['user_id'], 'role_updated', ['role_id' => $roleId]);
                set_success('تم تحديث الدور بنجاح');
                break;
                
            case 'delete_role':
                $roleId = sanitize_input($_POST['role_id']);
                
                if ($roleId === 'admin') {
                    set_error('لا يمكن حذف دور المدير العام');
                } else {
                    $stmt = $db->prepare("DELETE FROM roles WHERE role_id = ?");
                    $stmt->execute([$roleId]);
                    
                    log_activity($_SESSION['user_id'], 'role_deleted', ['role_id' => $roleId]);
                    set_success('تم حذف الدور بنجاح');
                }
                break;
                
            case 'add_permission':
                $permId = sanitize_input($_POST['permission_id']);
                $permName = sanitize_input($_POST['permission_name']);
                $category = sanitize_input($_POST['category']);
                $description = sanitize_input($_POST['description']);
                
                $check = $db->prepare("SELECT * FROM permissions WHERE permission_id = ?");
                $check->execute([$permId]);
                
                if ($check->fetch()) {
                    set_error('الصلاحية موجودة بالفعل');
                } else {
                    $stmt = $db->prepare("INSERT INTO permissions (permission_id, name, category, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$permId, $permName, $category, $description]);
                    
                    log_activity($_SESSION['user_id'], 'permission_created', ['permission_id' => $permId]);
                    set_success('تم إضافة الصلاحية بنجاح');
                }
                break;
                
            case 'assign_role':
                $userId = (int)$_POST['user_id'];
                $roleId = sanitize_input($_POST['role_id']);
                
                $stmt = $db->prepare("UPDATE users_all SET role_id = ? WHERE id = ?");
                $stmt->execute([$roleId, $userId]);
                
                log_activity($_SESSION['user_id'], 'role_assigned', ['user_id' => $userId, 'role_id' => $roleId]);
                set_success('تم تعيين الدور للمستخدم بنجاح');
                break;
        }
    } catch (PDOException $e) {
        set_error('خطأ في قاعدة البيانات: ' . $e->getMessage());
    } catch (Exception $e) {
        set_error('حدث خطأ: ' . $e->getMessage());
    }
    
    redirect('roles-permissions.php');
}

// جلب الأدوار
try {
    $stmt = $db->query("SELECT * FROM roles ORDER BY role_id");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
}

// جلب الصلاحيات
try {
    $stmt = $db->query("SELECT * FROM permissions ORDER BY category, name");
    $permissions = $stmt->fetchAll();
} catch (PDOException $e) {
    $permissions = [];
}

// تجميع الصلاحيات حسب الفئة
$groupedPermissions = [];
foreach ($permissions as $perm) {
    $groupedPermissions[$perm['category']][] = $perm;
}

// جلب المستخدمين لتعيين الأدوار
try {
    $stmt = $db->query("SELECT id, username, full_name, role_id FROM users_all WHERE deleted_at IS NULL ORDER BY id");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// إحصائيات
try {
    // عدد المستخدمين لكل دور
    $stmt = $db->query("
        SELECT role_id, COUNT(*) as count 
        FROM users_all 
        WHERE deleted_at IS NULL AND role_id IS NOT NULL 
        GROUP BY role_id
    ");
    $roleStats = $stmt->fetchAll();
    $roleStats = array_column($roleStats, 'count', 'role_id');
} catch (Exception $e) {
    $roleStats = [];
}

// الحصول على المستخدم الحالي
$currentUser = current_user();

// إنشاء جدول الأدوار إذا لم يكن موجوداً
try {
    $db->query("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        permissions JSON,
        is_system BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $db->query("CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        permission_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // إضافة الأدوار الأساسية إذا لم تكن موجودة
    $checkAdmin = $db->query("SELECT * FROM roles WHERE role_id = 'admin'");
    if (!$checkAdmin->fetch()) {
        $db->prepare("INSERT INTO roles (role_id, name, description, permissions, is_system) VALUES 
            ('admin', 'مدير النظام', 'جميع الصلاحيات', '[\"*\"]', TRUE),
            ('manager', 'مدير', 'إدارة المحتوى والمستخدمين', '[]', TRUE),
            ('documentation_staff', 'موظف توثيق', 'إدارة المستندات', '[]', TRUE),
            ('cloud_storage_staff', 'موظف تخزين سحابي', 'إدارة التخزين', '[]', TRUE),
            ('pentest_staff', 'مختبر اختراق', 'اختبارات الأمان', '[]', TRUE),
            ('monitoring_staff', 'موظف مراقبة', 'مراقبة النظام', '[]', TRUE),
            ('pms_staff', 'مدير مشاريع', 'إدارة المشاريع', '[]', TRUE),
            ('finance_staff', 'موظف مالي', 'إدارة الفواتير', '[]', TRUE),
            ('ai_staff', 'موظف ذكاء اصطناعي', 'تحليل البيانات', '[]', TRUE),
            ('client', 'عميل', 'صلاحيات العميل', '[]', TRUE)
        ")->execute();
    }
    
    // إضافة الصلاحيات الأساسية
    $checkPerms = $db->query("SELECT * FROM permissions LIMIT 1");
    if (!$checkPerms->fetch()) {
        $permissionsList = [
            // صلاحيات المستخدمين
            ['view_users', 'عرض المستخدمين', 'users'],
            ['create_users', 'إنشاء مستخدمين', 'users'],
            ['edit_users', 'تعديل المستخدمين', 'users'],
            ['delete_users', 'حذف المستخدمين', 'users'],
            ['manage_roles', 'إدارة الأدوار', 'users'],
            
            // صلاحيات المشاريع
            ['view_projects', 'عرض المشاريع', 'projects'],
            ['create_projects', 'إنشاء مشاريع', 'projects'],
            ['edit_projects', 'تعديل المشاريع', 'projects'],
            ['delete_projects', 'حذف المشاريع', 'projects'],
            ['assign_projects', 'تعيين المشاريع', 'projects'],
            
            // صلاحيات الملفات
            ['view_files', 'عرض الملفات', 'files'],
            ['upload_files', 'رفع ملفات', 'files'],
            ['download_files', 'تحميل ملفات', 'files'],
            ['delete_files', 'حذف ملفات', 'files'],
            ['scan_files', 'فحص الملفات', 'files'],
            
            // صلاحيات الأمان
            ['view_security', 'عرض الأمان', 'security'],
            ['manage_security', 'إدارة الأمان', 'security'],
            ['view_logs', 'عرض السجلات', 'security'],
            ['manage_alerts', 'إدارة التنبيهات', 'security'],
            ['run_scans', 'تشغيل فحوصات', 'security'],
            
            // صلاحيات التقارير
            ['view_reports', 'عرض التقارير', 'reports'],
            ['create_reports', 'إنشاء تقارير', 'reports'],
            ['export_reports', 'تصدير التقارير', 'reports'],
            ['schedule_reports', 'جدولة تقارير', 'reports'],
            
            // صلاحيات النظام
            ['view_settings', 'عرض الإعدادات', 'system'],
            ['edit_settings', 'تعديل الإعدادات', 'system'],
            ['view_audit', 'عرض التدقيق', 'system'],
            ['manage_backups', 'إدارة النسخ', 'system']
        ];
        
        $stmt = $db->prepare("INSERT INTO permissions (permission_id, name, category) VALUES (?, ?, ?)");
        foreach ($permissionsList as $perm) {
            $stmt->execute($perm);
        }
    }
    
} catch (PDOException $e) {
    // تجاهل الأخطاء
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأدوار والصلاحيات - نظام الحماية</title>
 
  
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    
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
        
        /* بطاقات المحتوى */
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* تبويبات */
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background: none;
            font-weight: bold;
        }
        
        .nav-tabs .nav-link i {
            margin-left: 8px;
        }
        
        /* بطاقات الأدوار */
        .role-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-right: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .role-card:hover {
            transform: translateX(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .role-card.system-role {
            border-right-color: var(--primary-color);
        }
        
        .role-card.custom-role {
            border-right-color: var(--success-color);
        }
        
        .role-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .role-id {
            font-size: 0.8rem;
            color: #6c757d;
            font-family: monospace;
        }
        
        .role-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 10px 0;
        }
        
        .user-count-badge {
            background: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }
        
        /* مجموعة الصلاحيات */
        .permission-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .permission-group-title {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .permission-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .permission-item:last-child {
            border-bottom: none;
        }
        
        .permission-name {
            font-size: 0.9rem;
        }
        
        .permission-id {
            font-size: 0.7rem;
            color: #6c757d;
            font-family: monospace;
        }
        
        /* شارات */
        .badge-permission {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin: 2px;
            display: inline-block;
        }
        
        /* مصفوفة الصلاحيات */
        .matrix-table {
            font-size: 0.85rem;
        }
        
        .matrix-table th {
            background: #f8f9fa;
            text-align: center;
            vertical-align: middle;
        }
        
        .matrix-table td {
            text-align: center;
            vertical-align: middle;
        }
        
        .matrix-check {
            color: var(--success-color);
            font-size: 1.2rem;
        }
        
        .matrix-cross {
            color: var(--danger-color);
            font-size: 1.2rem;
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
            <a href="roles-permissions.php" class="nav-link active">
                <i class="fas fa-key"></i> الأدوار والصلاحيات
            </a>
            <a href="audit-logs.php" class="nav-link">
                <i class="fas fa-history"></i> سجلات التدقيق
            </a>
            <a href="security-settings.php" class="nav-link">
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
                <i class="fas fa-key text-primary me-2"></i>
                إدارة الأدوار والصلاحيات
            </h2>
            <div>
                <button class="btn btn-primary me-2" onclick="openAddRoleModal()">
                    <i class="fas fa-plus-circle me-1"></i>
                    دور جديد
                </button>
                <button class="btn btn-success" onclick="openAddPermissionModal()">
                    <i class="fas fa-plus-circle me-1"></i>
                    صلاحية جديدة
                </button>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- تبويبات التنقل -->
        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles">
                    <i class="fas fa-users-cog"></i>
                    الأدوار
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions">
                    <i class="fas fa-list-check"></i>
                    الصلاحيات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign">
                    <i class="fas fa-user-tag"></i>
                    تعيين الأدوار
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix">
                    <i class="fas fa-table"></i>
                    مصفوفة الصلاحيات
                </button>
            </li>
        </ul>

        <!-- محتوى التبويبات -->
        <div class="tab-content">
            <!-- تبويب الأدوار -->
            <div class="tab-pane fade show active" id="roles">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list"></i>
                            قائمة الأدوار
                        </h5>
                        <span class="badge bg-primary"><?php echo count($roles); ?> دور</span>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($roles as $role): 
                            $userCount = $roleStats[$role['role_id']] ?? 0;
                        ?>
                        <div class="col-md-6">
                            <div class="role-card <?php echo $role['is_system'] ? 'system-role' : 'custom-role'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="role-name">
                                            <?php echo htmlspecialchars($role['name']); ?>
                                            <?php if ($role['is_system']): ?>
                                                <span class="badge bg-secondary">نظام</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="role-id"><?php echo $role['role_id']; ?></div>
                                    </div>
                                    <span class="user-count-badge">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo $userCount; ?>
                                    </span>
                                </div>
                                
                                <div class="role-description">
                                    <?php echo htmlspecialchars($role['description'] ?? 'لا يوجد وصف'); ?>
                                </div>
                                
                                <div class="mt-2">
                                    <?php 
                                    $perms = json_decode($role['permissions'] ?? '[]', true);
                                    $displayPerms = array_slice($perms, 0, 5);
                                    foreach ($displayPerms as $perm): 
                                        if ($perm === '*') {
                                            echo '<span class="badge-permission">جميع الصلاحيات</span>';
                                        } else {
                                            echo '<span class="badge-permission">' . htmlspecialchars($perm) . '</span>';
                                        }
                                    endforeach;
                                    if (count($perms) > 5) {
                                        echo '<span class="badge-permission">+' . (count($perms) - 5) . ' أخرى</span>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="mt-3 text-left">
                                    <button class="btn btn-sm btn-info" onclick="viewRole('<?php echo $role['role_id']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$role['is_system']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRole('<?php echo $role['role_id']; ?>', '<?php echo htmlspecialchars($role['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-success" onclick="managePermissions('<?php echo $role['role_id']; ?>', <?php echo htmlspecialchars(json_encode($perms)); ?>)">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($roles)): ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <br>
                            لا توجد أدوار مضافة
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- تبويب الصلاحيات -->
            <div class="tab-pane fade" id="permissions">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list-check"></i>
                            جميع الصلاحيات
                        </h5>
                        <span class="badge bg-primary"><?php echo count($permissions); ?> صلاحية</span>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($groupedPermissions as $category => $perms): ?>
                        <div class="col-md-6 mb-3">
                            <div class="permission-group">
                                <div class="permission-group-title">
                                    <i class="fas fa-tag me-1"></i>
                                    <?php 
                                    $categoryNames = [
                                        'users' => 'المستخدمين',
                                        'projects' => 'المشاريع',
                                        'files' => 'الملفات',
                                        'security' => 'الأمان',
                                        'reports' => 'التقارير',
                                        'system' => 'النظام'
                                    ];
                                    echo $categoryNames[$category] ?? $category;
                                    ?>
                                </div>
                                
                                <?php foreach ($perms as $perm): ?>
                                <div class="permission-item">
                                    <div>
                                        <span class="permission-name"><?php echo htmlspecialchars($perm['name']); ?></span>
                                        <br>
                                        <span class="permission-id"><?php echo $perm['permission_id']; ?></span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyPermissionId('<?php echo $perm['permission_id']; ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- تبويب تعيين الأدوار -->
            <div class="tab-pane fade" id="assign">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-user-tag"></i>
                            تعيين الأدوار للمستخدمين
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الدور الحالي</th>
                                    <th>تغيير الدور</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong>
                                        <br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $currentRole = array_filter($roles, fn($r) => $r['role_id'] === $user['role_id']);
                                        $currentRole = reset($currentRole);
                                        ?>
                                        <span class="badge bg-info">
                                            <?php echo $currentRole ? htmlspecialchars($currentRole['name']) : ($user['role_id'] ?? 'غير محدد'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="action" value="assign_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role_id" class="form-select form-select-sm" style="width: 200px;">
                                                <option value="">بدون دور</option>
                                                <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo $role['role_id']; ?>" 
                                                    <?php echo $user['role_id'] === $role['role_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-save"></i> حفظ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- تبويب مصفوفة الصلاحيات -->
            <div class="tab-pane fade" id="matrix">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-table"></i>
                            مصفوفة الصلاحيات
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered matrix-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: 200px;">الصلاحية</th>
                                    <?php foreach ($roles as $role): ?>
                                    <th class="text-center">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permissions as $perm): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($perm['name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo $perm['permission_id']; ?></small>
                                    </td>
                                    <?php foreach ($roles as $role): 
                                        $perms = json_decode($role['permissions'] ?? '[]', true);
                                        $hasPerm = in_array('*', $perms) || in_array($perm['permission_id'], $perms);
                                    ?>
                                    <td class="text-center">
                                        <?php if ($hasPerm): ?>
                                            <i class="fas fa-check-circle matrix-check"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle matrix-cross"></i>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال إضافة دور -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        إضافة دور جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_role">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">معرف الدور <span class="text-danger">*</span></label>
                            <input type="text" name="role_id" class="form-control" required 
                                   placeholder="manager, editor, viewer...">
                            <small class="text-muted">أحرف إنجليزية صغيرة وشرطة سفلية فقط</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">اسم الدور <span class="text-danger">*</span></label>
                            <input type="text" name="role_name" class="form-control" required 
                                   placeholder="مدير محتوى، محرر...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الصلاحيات</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($groupedPermissions as $category => $perms): ?>
                                <div class="mb-2">
                                    <strong><?php echo $category; ?></strong>
                                    <?php foreach ($perms as $perm): ?>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox" 
                                               name="permissions[]" value="<?php echo $perm['permission_id']; ?>"
                                               id="perm_<?php echo $perm['permission_id']; ?>">
                                        <label class="form-check-label" for="perm_<?php echo $perm['permission_id']; ?>">
                                            <?php echo htmlspecialchars($perm['name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo $perm['permission_id']; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> إضافة الدور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال إضافة صلاحية -->
    <div class="modal fade" id="addPermissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        إضافة صلاحية جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_permission">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">معرف الصلاحية <span class="text-danger">*</span></label>
                            <input type="text" name="permission_id" class="form-control" required 
                                   placeholder="view_users, manage_projects...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">اسم الصلاحية <span class="text-danger">*</span></label>
                            <input type="text" name="permission_name" class="form-control" required 
                                   placeholder="عرض المستخدمين، إدارة المشاريع...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">التصنيف</label>
                            <select name="category" class="form-select">
                                <option value="users">المستخدمين</option>
                                <option value="projects">المشاريع</option>
                                <option value="files">الملفات</option>
                                <option value="security">الأمان</option>
                                <option value="reports">التقارير</option>
                                <option value="system">النظام</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> إضافة الصلاحية
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال تعديل دور -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        تعديل الدور
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم الدور</label>
                            <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الصلاحيات</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;" id="edit_permissions_container">
                                <!-- تملأ بالجافاسكريبت -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> تحديث الدور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال إدارة الصلاحيات -->
    <div class="modal fade" id="permissionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        إدارة صلاحيات الدور
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="perm_role_id">
                    
                    <div class="modal-body">
                        <h5 id="perm_role_name" class="mb-3"></h5>
                        
                        <div class="row">
                            <?php foreach ($groupedPermissions as $category => $perms): ?>
                            <div class="col-md-6 mb-3">
                                <div class="permission-group">
                                    <div class="permission-group-title">
                                        <?php echo $categoryNames[$category] ?? $category; ?>
                                    </div>
                                    <?php foreach ($perms as $perm): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" 
                                               name="permissions[]" value="<?php echo $perm['permission_id']; ?>"
                                               id="modal_perm_<?php echo $perm['permission_id']; ?>">
                                        <label class="form-check-label" for="modal_perm_<?php echo $perm['permission_id']; ?>">
                                            <?php echo htmlspecialchars($perm['name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-info" onclick="selectAllPermissions()">
                                <i class="fas fa-check-double"></i> تحديد الكل
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllPermissions()">
                                <i class="fas fa-times"></i> إلغاء الكل
                            </button>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> حفظ الصلاحيات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال عرض الدور -->
    <div class="modal fade" id="viewRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        تفاصيل الدور
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewRoleDetails">
                    <!-- تملأ بالجافاسكريبت -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال حذف دور -->
    <div class="modal fade" id="deleteRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        تأكيد الحذف
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف الدور <strong id="deleteRoleName"></strong>؟</p>
                    <p class="text-danger">لا يمكن التراجع عن هذا الإجراء!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_role">
                        <input type="hidden" name="role_id" id="deleteRoleId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">حذف الدور</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // تهيئة DataTable
        $(document).ready(function() {
            $('.table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
                },
                pageLength: 25,
                order: [[0, 'asc']]
            });
            
            // تهيئة Select2
            $('.form-select').select2({
                theme: 'bootstrap-5'
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
        
        // فتح مودال إضافة دور
        function openAddRoleModal() {
            new bootstrap.Modal(document.getElementById('addRoleModal')).show();
        }
        
        // فتح مودال إضافة صلاحية
        function openAddPermissionModal() {
            new bootstrap.Modal(document.getElementById('addPermissionModal')).show();
        }
        
        // عرض الدور
        function viewRole(roleId) {
            fetch(`get_role.php?role_id=${roleId}`)
                .then(response => response.json())
                .then(data => {
                    let html = `
                        <div class="text-center mb-3">
                            <h4>${data.name}</h4>
                            <span class="badge bg-secondary">${data.role_id}</span>
                        </div>
                        <p><strong>الوصف:</strong> ${data.description || 'لا يوجد'}</p>
                        <p><strong>عدد المستخدمين:</strong> ${data.user_count || 0}</p>
                        <h6 class="mt-3">الصلاحيات:</h6>
                        <div class="border rounded p-2">
                    `;
                    
                    if (data.permissions.includes('*')) {
                        html += '<span class="badge-permission">جميع الصلاحيات</span>';
                    } else {
                        data.permissions.forEach(perm => {
                            html += `<span class="badge-permission">${perm}</span>`;
                        });
                    }
                    
                    html += '</div>';
                    document.getElementById('viewRoleDetails').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('viewRoleModal')).show();
                });
        }
        
        // تعديل الدور
        function editRole(role) {
            document.getElementById('edit_role_id').value = role.role_id;
            document.getElementById('edit_role_name').value = role.name;
            document.getElementById('edit_description').value = role.description || '';
            
            // بناء قائمة الصلاحيات
            let perms = JSON.parse(role.permissions || '[]');
            let html = '';
            
            <?php foreach ($groupedPermissions as $category => $perms): ?>
            html += '<div class="mb-2"><strong><?php echo $categoryNames[$category] ?? $category; ?></strong>';
            <?php foreach ($perms as $perm): ?>
            html += '<div class="form-check">';
            html += '<input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm['permission_id']; ?>" id="edit_<?php echo $perm['permission_id']; ?>"';
            if (perms.includes('*') || perms.includes('<?php echo $perm['permission_id']; ?>')) {
                html += ' checked';
            }
            html += '>';
            html += '<label class="form-check-label" for="edit_<?php echo $perm['permission_id']; ?>">';
            html += '<?php echo htmlspecialchars($perm['name']); ?>';
            html += '</label></div>';
            <?php endforeach; ?>
            html += '</div>';
            <?php endforeach; ?>
            
            document.getElementById('edit_permissions_container').innerHTML = html;
            new bootstrap.Modal(document.getElementById('editRoleModal')).show();
        }
        
        // حذف الدور
        function deleteRole(roleId, roleName) {
            document.getElementById('deleteRoleId').value = roleId;
            document.getElementById('deleteRoleName').innerText = roleName;
            new bootstrap.Modal(document.getElementById('deleteRoleModal')).show();
        }
        
        // إدارة صلاحيات الدور
        function managePermissions(roleId, permissions) {
            document.getElementById('perm_role_id').value = roleId;
            
            // الحصول على اسم الدور
            fetch(`get_role.php?role_id=${roleId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('perm_role_name').innerText = data.name;
                });
            
            // تفعيل الصلاحيات
            document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
            if (permissions.includes('*')) {
                document.querySelectorAll('.perm-check').forEach(cb => cb.checked = true);
            } else {
                permissions.forEach(perm => {
                    let cb = document.getElementById(`modal_perm_${perm}`);
                    if (cb) cb.checked = true;
                });
            }
            
            new bootstrap.Modal(document.getElementById('permissionsModal')).show();
        }
        
        // تحديد جميع الصلاحيات
        function selectAllPermissions() {
            document.querySelectorAll('.perm-check').forEach(cb => cb.checked = true);
        }
        
        // إلغاء تحديد جميع الصلاحيات
        function deselectAllPermissions() {
            document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
        }
        
        // نسخ معرف الصلاحية
        function copyPermissionId(permId) {
            navigator.clipboard.writeText(permId).then(() => {
                alert('تم نسخ المعرف: ' + permId);
            });
        }
        
        // تحديث البيانات كل 5 دقائق
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>