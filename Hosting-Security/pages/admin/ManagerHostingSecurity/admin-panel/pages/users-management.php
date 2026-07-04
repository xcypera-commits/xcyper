<?php
/**
 * إدارة المستخدمين
 * Users Management Page
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
            case 'add_user':
                $userData = [
                    'username' => sanitize_input($_POST['username']),
                    'email' => sanitize_input($_POST['email']),
                    'full_name' => sanitize_input($_POST['full_name']),
                    'user_type' => sanitize_input($_POST['user_type']),
                    'role_id' => sanitize_input($_POST['role_id']),
                    'status' => sanitize_input($_POST['status']),
                    'password' => $_POST['password'] ?? 'Default@123'
                ];
                
                $result = add_user($userData);
                if ($result['success']) {
                    set_success($result['message']);
                    log_activity($_SESSION['user_id'], 'user_added', ['username' => $userData['username']]);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'edit_user':
                $id = (int)$_POST['user_id'];
                $userData = [
                    'full_name' => sanitize_input($_POST['full_name']),
                    'email' => sanitize_input($_POST['email']),
                    'user_type' => sanitize_input($_POST['user_type']),
                    'role_id' => sanitize_input($_POST['role_id']),
                    'status' => sanitize_input($_POST['status'])
                ];
                
                if (!empty($_POST['password'])) {
                    $userData['password'] = $_POST['password'];
                }
                
                $result = update_user($id, $userData);
                if ($result['success']) {
                    set_success($result['message']);
                    log_activity($_SESSION['user_id'], 'user_updated', ['user_id' => $id]);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'delete_user':
                $id = (int)$_POST['user_id'];
                $result = delete_user($id);
                
                if ($result['success']) {
                    set_success($result['message']);
                    log_activity($_SESSION['user_id'], 'user_deleted', ['user_id' => $id]);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'bulk_action':
                $action = sanitize_input($_POST['bulk_action']);
                $userIds = $_POST['user_ids'] ?? [];
                
                if (empty($userIds)) {
                    set_error('الرجاء تحديد مستخدمين على الأقل');
                } else {
                    $count = 0;
                    foreach ($userIds as $id) {
                        if ($action === 'delete' && $id != 1) {
                            delete_user($id);
                            $count++;
                        } elseif ($action === 'activate') {
                            update_user($id, ['status' => 'active']);
                            $count++;
                        } elseif ($action === 'deactivate') {
                            update_user($id, ['status' => 'inactive']);
                            $count++;
                        }
                    }
                    set_success("تم تنفيذ الإجراء على $count مستخدم/مستخدمين");
                    log_activity($_SESSION['user_id'], 'bulk_action', ['action' => $action, 'count' => $count]);
                }
                break;
        }
    } catch (Exception $e) {
        set_error('حدث خطأ: ' . $e->getMessage());
    }
    
    redirect('users-management.php');
}

// الحصول على معاملات الفلتر
$filters = [
    'user_type' => $_GET['type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// جلب المستخدمين
$users = get_users($filters);
$userStats = get_user_stats();

// جلب الأدوار
try {
    $stmt = $db->query("SHOW COLUMNS FROM users_all WHERE Field = 'user_type'");
    $typeRow = $stmt->fetch();
    preg_match("/^enum\((.*)\)$/", $typeRow['Type'], $matches);
    $userTypes = str_getcsv($matches[1], ',', "'");
} catch (Exception $e) {
    $userTypes = ['admin', 'manager', 'client'];
}

// الحصول على المستخدم الحالي
$currentUser = current_user();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
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
        
        .sidebar-header h4 {
            margin: 15px 0 5px;
            font-size: 1.2rem;
        }
        
        .sidebar-header small {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            margin: 5px 0;
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
            font-size: 1.1rem;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: #ffd700;
            padding-right: 35px;
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
        
        /* بطاقات الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        /* بطاقة المحتوى */
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
        
        .card-title i {
            color: var(--primary-color);
        }
        
        /* شارات الحالة */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-suspended {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-locked {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-pending_verification {
            background: #cce5ff;
            color: #004085;
        }
        
        /* شارات النوع */
        .type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .type-admin {
            background: #dc3545;
            color: white;
        }
        
        .type-manager {
            background: #ffc107;
            color: black;
        }
        
        .type-client {
            background: #17a2b8;
            color: white;
        }
        
        .type-staff {
            background: #6c757d;
            color: white;
        }
        
        /* صورة المستخدم */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* أزرار الإجراءات */
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        /* شريط البحث والفلترة */
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
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
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }
        
        .menu-toggle {
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
        
        /* تخصيص DataTables */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 5px 10px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 6px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: none;
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
            <h4><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></h4>
            <small>
                <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i>
                متصل
            </small>
        </div>
        
        <div class="nav-menu">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    لوحة التحكم
                </a>
            </div>
            
            <div class="nav-item">
                <a href="users-management.php" class="nav-link active">
                    <i class="fas fa-users"></i>
                    إدارة المستخدمين
                </a>
            </div>
            
            <div class="nav-item">
                <a href="roles-permissions.php" class="nav-link">
                    <i class="fas fa-key"></i>
                    الأدوار والصلاحيات
                </a>
            </div>
            
            <div class="nav-item">
                <a href="audit-logs.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    سجلات التدقيق
                </a>
            </div>
            
            <div class="nav-item">
                <a href="security-settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    إعدادات الأمان
                </a>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
            
            <div class="nav-item">
                <a href="../../index.php" class="nav-link">
                    <i class="fas fa-globe"></i>
                    الموقع الرئيسي
                </a>
            </div>
            
            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل خروج
                </a>
            </div>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-users-cog text-primary me-2"></i>
                إدارة المستخدمين
            </h2>
            <div>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus-circle me-1"></i>
                    إضافة مستخدم جديد
                </button>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">إجمالي المستخدمين</div>
                <div class="stat-value"><?php echo $userStats['by_type'] ? array_sum(array_column($userStats['by_type'], 'count')) : 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">مديرين</div>
                <div class="stat-value"><?php 
                    $admins = array_filter($userStats['by_type'] ?? [], fn($t) => $t['user_type'] === 'admin');
                    echo $admins ? reset($admins)['count'] : 0;
                ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">موظفين</div>
                <div class="stat-value"><?php 
                    $staff = array_filter($userStats['by_type'] ?? [], fn($t) => strpos($t['user_type'], 'staff') !== false);
                    echo array_sum(array_column($staff, 'count'));
                ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">عملاء</div>
                <div class="stat-value"><?php 
                    $clients = array_filter($userStats['by_type'] ?? [], fn($t) => $t['user_type'] === 'client');
                    echo $clients ? reset($clients)['count'] : 0;
                ?></div>
            </div>
        </div>

        <!-- قسم الفلترة والبحث -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">نوع المستخدم</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        <?php foreach ($userTypes as $type): ?>
                        <option value="<?php echo trim($type, "'"); ?>" 
                            <?php echo $filters['user_type'] === trim($type, "'") ? 'selected' : ''; ?>>
                            <?php echo trim($type, "'"); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                        <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>موقوف</option>
                        <option value="locked" <?php echo $filters['status'] === 'locked' ? 'selected' : ''; ?>>مقفل</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">بحث</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="اسم المستخدم - البريد - الاسم" 
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="users-management.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- جدول المستخدمين -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-list"></i>
                    قائمة المستخدمين
                </h5>
                <div>
                    <button class="btn btn-sm btn-success" onclick="selectAll()">
                        <i class="fas fa-check-double"></i> تحديد الكل
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('activate')">
                        <i class="fas fa-play"></i> تفعيل
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="bulkAction('deactivate')">
                        <i class="fas fa-pause"></i> تعطيل
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </div>
            </div>
            
            <form id="bulkForm" method="POST">
                <input type="hidden" name="action" value="bulk_action">
                <input type="hidden" name="bulk_action" id="bulkAction">
                
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                                </th>
                                <th>#</th>
                                <th>المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>نوع المستخدم</th>
                                <th>الدور</th>
                                <th>المصدر</th>
                                <th>الحالة</th>
                                <th>آخر دخول</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" 
                                           class="user-checkbox" <?php echo $user['id'] == 1 ? 'disabled' : ''; ?>>
                                </td>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo mb_substr($user['full_name'] ?? $user['username'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="type-badge type-<?php 
                                        echo match($user['user_type']) {
                                            'admin' => 'admin',
                                            'manager' => 'manager',
                                            'client' => 'client',
                                            default => 'staff'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($user['user_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['role_id'] ?? '-'); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars($user['user_source']); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php 
                                        echo match($user['status']) {
                                            'active' => 'نشط',
                                            'inactive' => 'غير نشط',
                                            'suspended' => 'موقوف',
                                            'locked' => 'مقفل',
                                            'pending_verification' => 'بإنتظار التحقق',
                                            'deleted' => 'محذوف',
                                            default => $user['status']
                                        }; 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '-'; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-action" title="عرض"
                                            onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btn-action" title="تعديل"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != 1): ?>
                                    <button class="btn btn-sm btn-danger btn-action" title="حذف"
                                            onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-secondary btn-action" title="إعادة تعيين كلمة المرور"
                                            onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fas fa-users-slash fa-3x mb-3"></i>
                                    <br>
                                    لا يوجد مستخدمين لعرضهم
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- مودال إضافة مستخدم -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        إضافة مستخدم جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required 
                                       placeholder="ahmed.ali">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required 
                                       placeholder="user@example.com">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required 
                                       placeholder="أحمد علي">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="text" name="password" class="form-control" 
                                       value="Default@123">
                                <small class="text-muted">اتركها فارغة لاستخدام القيمة الافتراضية</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع المستخدم <span class="text-danger">*</span></label>
                                <select name="user_type" class="form-select" required>
                                    <option value="">اختر النوع</option>
                                    <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo trim($type, "'"); ?>">
                                        <?php echo trim($type, "'"); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الدور</label>
                                <select name="role_id" class="form-select">
                                    <option value="">اختر الدور</option>
                                    <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo trim($type, "'"); ?>">
                                        <?php echo trim($type, "'"); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                    <option value="suspended">موقوف</option>
                                    <option value="pending_verification">بإنتظار التحقق</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            كلمة المرور الافتراضية: <strong>Default@123</strong>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> إضافة المستخدم
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال تعديل مستخدم -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>
                        تعديل بيانات المستخدم
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع المستخدم</label>
                                <select name="user_type" id="edit_user_type" class="form-select" required>
                                    <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo trim($type, "'"); ?>">
                                        <?php echo trim($type, "'"); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الدور</label>
                                <select name="role_id" id="edit_role_id" class="form-select">
                                    <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo trim($type, "'"); ?>">
                                        <?php echo trim($type, "'"); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                    <option value="suspended">موقوف</option>
                                    <option value="locked">مقفل</option>
                                    <option value="pending_verification">بإنتظار التحقق</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة مرور جديدة</label>
                                <input type="text" name="password" class="form-control" 
                                       placeholder="اتركها فارغة إذا لم ترد التغيير">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> تحديث البيانات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال عرض المستخدم -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-circle me-2"></i>
                        تفاصيل المستخدم
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetails">
                    <!-- تملأ بالجافاسكريبت -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال حذف مستخدم -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
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
                    <p>هل أنت متأكد من حذف المستخدم <strong id="deleteUsername"></strong>؟</p>
                    <p class="text-danger">لا يمكن التراجع عن هذا الإجراء!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> حذف المستخدم
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال إعادة تعيين كلمة المرور -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        إعادة تعيين كلمة المرور
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من إعادة تعيين كلمة المرور للمستخدم <strong id="resetUsername"></strong>؟</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        كلمة المرور الجديدة: <strong>Default@123</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="resetUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> إعادة التعيين
                        </button>
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
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
                },
                pageLength: 25,
                order: [[1, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [0, 9] }
                ]
            });
            
            // تهيئة Select2
            $('.form-select').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#addUserModal, #editUserModal')
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
        
        // فتح مودال الإضافة
        function openAddModal() {
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        }
        
        // عرض المستخدم
        function viewUser(user) {
            const details = `
                <div class="text-center mb-4">
                    <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                        ${(user.full_name || user.username).charAt(0)}
                    </div>
                    <h5 class="mt-2">${user.full_name || user.username}</h5>
                    <span class="type-badge type-${user.user_type}">${user.user_type}</span>
                    <span class="status-badge status-${user.status}">${user.status}</span>
                </div>
                
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">معرف المستخدم</th>
                        <td>${user.id}</td>
                    </tr>
                    <tr>
                        <th>اسم المستخدم</th>
                        <td>@${user.username}</td>
                    </tr>
                    <tr>
                        <th>البريد الإلكتروني</th>
                        <td>${user.email}</td>
                    </tr>
                    <tr>
                        <th>نوع المستخدم</th>
                        <td>${user.user_type}</td>
                    </tr>
                    <tr>
                        <th>الدور</th>
                        <td>${user.role_id || '-'}</td>
                    </tr>
                    <tr>
                        <th>المصدر</th>
                        <td>${user.user_source}</td>
                    </tr>
                    <tr>
                        <th>تاريخ الإنشاء</th>
                        <td>${user.created_at || '-'}</td>
                    </tr>
                    <tr>
                        <th>آخر دخول</th>
                        <td>${user.last_login || '-'}</td>
                    </tr>
                    <tr>
                        <th>آخر IP</th>
                        <td>${user.last_login_ip || '-'}</td>
                    </tr>
                    <tr>
                        <th>محاولات الدخول</th>
                        <td>${user.login_attempts || 0}</td>
                    </tr>
                    <tr>
                        <th>MFA مفعل</th>
                        <td>${user.mfa_enabled ? 'نعم' : 'لا'}</td>
                    </tr>
                </table>
            `;
            
            document.getElementById('userDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('viewUserModal')).show();
        }
        
        // تعديل المستخدم
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name || '';
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_user_type').value = user.user_type;
            document.getElementById('edit_role_id').value = user.role_id || '';
            document.getElementById('edit_status').value = user.status || 'active';
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        // حذف المستخدم
        function deleteUser(id, username) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUsername').innerText = username;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
        
        // إعادة تعيين كلمة المرور
        function resetPassword(id, username) {
            document.getElementById('resetUserId').value = id;
            document.getElementById('resetUsername').innerText = username;
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
        
        // تحديد الكل
        function selectAll() {
            const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = true);
        }
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        // تنفيذ إجراء جماعي
        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('الرجاء تحديد مستخدمين على الأقل');
                return;
            }
            
            document.getElementById('bulkAction').value = action;
            document.getElementById('bulkForm').submit();
        }
        
        // تحديث البيانات كل 5 دقائق
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>