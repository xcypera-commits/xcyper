<?php
// staff_management.php - بدون session
error_reporting(E_ALL);
ini_set('display_errors', 1);

// الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'security_monitoring_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// جلب جميع المستخدمين
$users = [];
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة العمليات (CRUD)
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // =============================================
    // إضافة مستخدم جديد
    // =============================================
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $department = $_POST['department'];
        $can_manage = isset($_POST['can_manage']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // التحقق من عدم تكرار اسم المستخدم أو البريد
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->rowCount() > 0) {
            $message = "اسم المستخدم أو البريد الإلكتروني موجود مسبقاً";
            $message_type = "error";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, role, department, can_manage, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $password_hash, $full_name, $phone, $role, $department, $can_manage, $is_active])) {
                $message = "تم إضافة المستخدم بنجاح";
                $message_type = "success";
                // إعادة التوجيه لتجنب إعادة الإرسال
                header("Location: staff_management.php?success=added");
                exit();
            }
        }
    }
    
    // =============================================
    // تعديل بيانات المستخدم
    // =============================================
    elseif (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $department = $_POST['department'];
        $can_manage = isset($_POST['can_manage']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, role = ?, department = ?, can_manage = ?, is_active = ? WHERE id = ?");
        
        if ($stmt->execute([$full_name, $phone, $role, $department, $can_manage, $is_active, $id])) {
            $message = "تم تحديث بيانات المستخدم بنجاح";
            $message_type = "success";
            header("Location: staff_management.php?success=updated");
            exit();
        }
    }
    
    // =============================================
    // تغيير كلمة المرور
    // =============================================
    elseif (isset($_POST['change_password'])) {
        $id = $_POST['id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $message = "كلمات المرور غير متطابقة";
            $message_type = "error";
        } elseif (strlen($new_password) < 6) {
            $message = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
            $message_type = "error";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            
            if ($stmt->execute([$password_hash, $id])) {
                $message = "تم تغيير كلمة المرور بنجاح";
                $message_type = "success";
                header("Location: staff_management.php?success=password");
                exit();
            }
        }
    }
    
    // =============================================
    // حذف مستخدم
    // =============================================
    elseif (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "تم حذف المستخدم بنجاح";
            $message_type = "success";
            header("Location: staff_management.php?success=deleted");
            exit();
        }
    }
    
    // =============================================
    // حذف مجموعة مستخدمين
    // =============================================
    elseif (isset($_POST['bulk_delete'])) {
        $ids = $_POST['user_ids'] ?? [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            if ($stmt->execute($ids)) {
                $message = "تم حذف " . count($ids) . " مستخدم بنجاح";
                $message_type = "success";
                header("Location: staff_management.php?success=bulk_deleted");
                exit();
            }
        }
    }
}

// جلب مستخدم للتحرير
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// جلب مستخدم لتغيير كلمة المرور
$password_user = null;
if (isset($_GET['password'])) {
    $stmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
    $stmt->execute([$_GET['password']]);
    $password_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xcyper - إدارة المستخدمين</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        
        .cyber-border {
            border: 2px solid rgba(139, 92, 246, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .cyber-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #8b5cf6, transparent);
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .role-admin { background: rgba(220, 38, 38, 0.2); color: #ef4444; }
        .role-manager { background: rgba(217, 119, 6, 0.2); color: #f59e0b; }
        .role-hosting { background: rgba(5, 150, 105, 0.2); color: #10b981; }
        .role-storage { background: rgba(37, 99, 235, 0.2); color: #3b82f6; }
        .role-security { background: rgba(139, 92, 246, 0.2); color: #a855f7; }
        .role-pentest { background: rgba(219, 39, 119, 0.2); color: #ec4899; }
        .role-documentation { background: rgba(6, 182, 212, 0.2); color: #06b6d4; }
        .role-analyst { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .role-viewer { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        
        .notification {
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .scrollbar-custom::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollbar-custom::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        .scrollbar-custom::-webkit-scrollbar-thumb {
            background: #8b5cf6;
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-purple-400">إدارة المستخدمين</h1>
                <p class="text-gray-400">إضافة وتعديل وحذف المستخدمين وإدارة كلمات المرور</p>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <a href="staff_login.php" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-all">
                    <i class="fas fa-arrow-right ml-2"></i>
                    العودة لتسجيل الدخول
                </a>
                <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg transition-all">
                    <i class="fas fa-plus ml-2"></i>
                    إضافة مستخدم
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="cyber-border bg-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">إجمالي المستخدمين</p>
                        <p class="text-2xl font-bold text-white"><?php echo count($users); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-purple-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-purple-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="cyber-border bg-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">المديرين</p>
                        <p class="text-2xl font-bold text-white">
                            <?php 
                            $admins = array_filter($users, function($u) { return $u['role'] == 'admin'; });
                            echo count($admins);
                            ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-crown text-red-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="cyber-border bg-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">النشطين</p>
                        <p class="text-2xl font-bold text-white">
                            <?php 
                            $active = array_filter($users, function($u) { return $u['is_active'] == 1; });
                            echo count($active);
                            ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="cyber-border bg-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">غير النشطين</p>
                        <p class="text-2xl font-bold text-white">
                            <?php 
                            $inactive = array_filter($users, function($u) { return $u['is_active'] == 0; });
                            echo count($inactive);
                            ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-gray-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-times-circle text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-white">قائمة المستخدمين</h2>
                <div class="flex space-x-2 space-x-reverse">
                    <button type="button" onclick="toggleSelectAll()" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                        <i class="fas fa-check-double ml-1"></i>
                        تحديد الكل
                    </button>
                    <button type="button" onclick="bulkDelete()" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-lg text-sm">
                        <i class="fas fa-trash ml-1"></i>
                        حذف المحدد
                    </button>
                    <div class="relative">
                        <input type="text" id="search" placeholder="بحث..." 
                               class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-1 pr-8 text-sm focus:outline-none focus:border-purple-500">
                        <i class="fas fa-search absolute left-2 top-2 text-gray-400 text-sm"></i>
                    </div>
                </div>
            </div>
            
            <form id="bulkForm" method="POST" action="">
                <input type="hidden" name="bulk_delete" value="1">
                <div class="overflow-x-auto scrollbar-custom">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 border-b border-slate-700">
                                <th class="pb-3 text-center w-10">
                                    <input type="checkbox" id="selectAll" class="rounded bg-slate-900 border-slate-700">
                                </th>
                                <th class="text-right pb-3">المستخدم</th>
                                <th class="text-right pb-3">البريد الإلكتروني</th>
                                <th class="text-right pb-3">الدور</th>
                                <th class="text-right pb-3">القسم</th>
                                <th class="text-center pb-3">مدير</th>
                                <th class="text-center pb-3">الحالة</th>
                                <th class="text-center pb-3">آخر دخول</th>
                                <th class="text-center pb-3">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b border-slate-700 hover:bg-slate-700 transition-all">
                                <td class="py-3 text-center">
                                    <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" class="user-checkbox rounded bg-slate-900 border-slate-700">
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center ml-2">
                                            <span class="text-white text-sm font-bold"><?php echo mb_substr($user['full_name'], 0, 1); ?></span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-white"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                            <p class="text-xs text-gray-400">@<?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-gray-300"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3">
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php 
                                        $role_names = [
                                            'admin' => 'مدير عام',
                                            'manager' => 'مدير',
                                            'hosting' => 'استضافة',
                                            'storage' => 'تخزين',
                                            'security' => 'حماية',
                                            'pentest' => 'اختبار اختراق',
                                            'documentation' => 'توثيق',
                                            'analyst' => 'محلل',
                                            'viewer' => 'مشاهد'
                                        ];
                                        echo $role_names[$user['role']] ?? $user['role'];
                                        ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <?php 
                                    $dept_names = [
                                        'hosting' => 'الاستضافة',
                                        'storage' => 'التخزين',
                                        'security' => 'الحماية',
                                        'pentest' => 'اختبار الاختراق',
                                        'documentation' => 'التوثيق',
                                        'management' => 'الإدارة'
                                    ];
                                    echo $dept_names[$user['department']] ?? $user['department'] ?? '-';
                                    ?>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if ($user['can_manage']): ?>
                                    <span class="text-yellow-400"><i class="fas fa-crown"></i></span>
                                    <?php else: ?>
                                    <span class="text-gray-600"><i class="fas fa-times"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if ($user['is_active']): ?>
                                    <span class="px-2 py-1 bg-green-600 bg-opacity-20 text-green-400 rounded-full text-xs">
                                        <i class="fas fa-circle text-xs ml-1"></i> نشط
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 py-1 bg-red-600 bg-opacity-20 text-red-400 rounded-full text-xs">
                                        <i class="fas fa-circle text-xs ml-1"></i> غير نشط
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center text-gray-400 text-xs">
                                    <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '-'; ?>
                                </td>
                                <td class="py-3 text-center">
                                    <button type="button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-blue-400 hover:text-blue-300 mx-1" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>')" class="text-green-400 hover:text-green-300 mx-1" title="تغيير كلمة المرور">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button type="button" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>')" class="text-red-400 hover:text-red-300 mx-1" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal إضافة مستخدم جديد -->
    <div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-purple-400">إضافة مستخدم جديد</h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="add_user" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">اسم المستخدم <span class="text-red-400">*</span></label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">الاسم الكامل <span class="text-red-400">*</span></label>
                        <input type="text" name="full_name" required 
                               class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">البريد الإلكتروني <span class="text-red-400">*</span></label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">كلمة المرور <span class="text-red-400">*</span></label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">رقم الهاتف</label>
                        <input type="text" name="phone" 
                               class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">الدور <span class="text-red-400">*</span></label>
                        <select name="role" required class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                            <option value="viewer">مشاهد</option>
                            <option value="analyst">محلل</option>
                            <option value="hosting">مهندس استضافة</option>
                            <option value="storage">مهندس تخزين</option>
                            <option value="security">مهندس أمن</option>
                            <option value="pentest">مختبر اختراق</option>
                            <option value="documentation">كاتب تقني</option>
                            <option value="manager">مدير</option>
                            <option value="admin">مدير عام</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">القسم</label>
                        <select name="department" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                            <option value="">بدون قسم</option>
                            <option value="hosting">الاستضافة</option>
                            <option value="storage">التخزين</option>
                            <option value="security">الحماية</option>
                            <option value="pentest">اختبار الاختراق</option>
                            <option value="documentation">التوثيق</option>
                            <option value="management">الإدارة</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-4 space-x-reverse mt-8">
                        <label class="flex items-center cursor-pointer ml-4">
                            <input type="checkbox" name="can_manage" class="form-checkbox h-5 w-5 text-purple-600">
                            <span class="mr-2 text-gray-300">صلاحيات إدارة</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" checked class="form-checkbox h-5 w-5 text-green-600">
                            <span class="mr-2 text-gray-300">حساب نشط</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 space-x-reverse mt-6">
                    <button type="button" onclick="closeAddModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg transition-all">
                        إلغاء
                    </button>
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 rounded-lg transition-all">
                        إضافة المستخدم
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal تعديل مستخدم -->
    <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-purple-400">تعديل بيانات المستخدم</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-300">الاسم الكامل</label>
                    <input type="text" name="full_name" id="edit_full_name" required 
                           class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">رقم الهاتف</label>
                        <input type="text" name="phone" id="edit_phone" 
                               class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">الدور</label>
                        <select name="role" id="edit_role" required class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                            <option value="viewer">مشاهد</option>
                            <option value="analyst">محلل</option>
                            <option value="hosting">مهندس استضافة</option>
                            <option value="storage">مهندس تخزين</option>
                            <option value="security">مهندس أمن</option>
                            <option value="pentest">مختبر اختراق</option>
                            <option value="documentation">كاتب تقني</option>
                            <option value="manager">مدير</option>
                            <option value="admin">مدير عام</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-300">القسم</label>
                        <select name="department" id="edit_department" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                            <option value="">بدون قسم</option>
                            <option value="hosting">الاستضافة</option>
                            <option value="storage">التخزين</option>
                            <option value="security">الحماية</option>
                            <option value="pentest">اختبار الاختراق</option>
                            <option value="documentation">التوثيق</option>
                            <option value="management">الإدارة</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-4 space-x-reverse mt-8">
                        <label class="flex items-center cursor-pointer ml-4">
                            <input type="checkbox" name="can_manage" id="edit_can_manage" class="form-checkbox h-5 w-5 text-purple-600">
                            <span class="mr-2 text-gray-300">صلاحيات إدارة</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="form-checkbox h-5 w-5 text-green-600">
                            <span class="mr-2 text-gray-300">حساب نشط</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 space-x-reverse mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg transition-all">
                        إلغاء
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition-all">
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal تغيير كلمة المرور -->
    <div id="passwordModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-purple-400">تغيير كلمة المرور</h3>
                <button type="button" onclick="closePasswordModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="passwordForm">
                <input type="hidden" name="change_password" value="1">
                <input type="hidden" name="id" id="password_id">
                
                <div class="mb-4 text-center">
                    <p class="text-white text-lg" id="password_user_name"></p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-300">كلمة المرور الجديدة</label>
                    <input type="password" name="new_password" id="new_password" required 
                           class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-300">تأكيد كلمة المرور</label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                           class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500">
                    <p id="password_error" class="text-red-400 text-xs mt-1 hidden">كلمات المرور غير متطابقة</p>
                </div>
                
                <div id="password_strength" class="mb-4">
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>قوة كلمة المرور:</span>
                        <span id="strength_text">ضعيفة</span>
                    </div>
                    <div class="h-1 bg-slate-700 rounded-full overflow-hidden">
                        <div id="strength_bar" class="h-full bg-red-500" style="width: 25%"></div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 space-x-reverse mt-6">
                    <button type="button" onclick="closePasswordModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg transition-all">
                        إلغاء
                    </button>
                    <button type="submit" id="password_submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 rounded-lg transition-all">
                        تغيير كلمة المرور
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal حذف مستخدم -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
        <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-600 bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">تأكيد الحذف</h3>
                <p class="text-gray-400 mb-6">هل أنت متأكد من حذف المستخدم: <span id="deleteUserName" class="text-white font-bold"></span>؟</p>
                
                <form method="POST" action="" id="deleteForm">
                    <input type="hidden" name="delete_user" value="1">
                    <input type="hidden" name="id" id="delete_id">
                    
                    <div class="flex justify-center space-x-4 space-x-reverse">
                        <button type="button" onclick="closeDeleteModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg transition-all">
                            إلغاء
                        </button>
                        <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-all">
                            تأكيد الحذف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // =============================================
        // الإشعارات
        // =============================================
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notification-container');
            if (!container) return;
            
            const notification = document.createElement('div');
            
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600'
            };
            
            notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm mb-2`;
            notification.innerHTML = `<div class="flex items-center">${message}</div>`;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 3000);
        }

        // =============================================
        // البحث في الجدول
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#usersTable tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchValue) ? '' : 'none';
                    });
                });
            }
        });

        // =============================================
        // تحديد الكل
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.user-checkbox');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }
        });

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.checked = !selectAll.checked;
                const checkboxes = document.querySelectorAll('.user-checkbox');
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            }
        }

        function bulkDelete() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkboxes.length === 0) {
                showNotification('الرجاء تحديد مستخدمين على الأقل', 'error');
                return;
            }
            
            if (confirm(`هل أنت متأكد من حذف ${checkboxes.length} مستخدم؟`)) {
                document.getElementById('bulkForm').submit();
            }
        }

        // =============================================
        // Modal الإضافة
        // =============================================
        function openAddModal() {
            const modal = document.getElementById('addModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeAddModal() {
            const modal = document.getElementById('addModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // =============================================
        // Modal التعديل
        // =============================================
        function openEditModal(user) {
            if (!user) return;
            
            document.getElementById('edit_id').value = user.id || '';
            document.getElementById('edit_full_name').value = user.full_name || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role || 'viewer';
            document.getElementById('edit_department').value = user.department || '';
            document.getElementById('edit_can_manage').checked = user.can_manage == 1;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // =============================================
        // Modal كلمة المرور
        // =============================================
        function openPasswordModal(id, name) {
            if (!id) return;
            
            document.getElementById('password_id').value = id;
            document.getElementById('password_user_name').textContent = name || '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('password_error').classList.add('hidden');
            
            // إعادة تعيين مؤشر القوة
            const strengthBar = document.getElementById('strength_bar');
            const strengthText = document.getElementById('strength_text');
            if (strengthBar) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'h-full bg-red-500';
            }
            if (strengthText) strengthText.textContent = 'ضعيفة';
            
            const modal = document.getElementById('passwordModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closePasswordModal() {
            const modal = document.getElementById('passwordModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // =============================================
        // التحقق من تطابق كلمة المرور
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword) {
                newPassword.addEventListener('keyup', checkPasswordMatch);
                newPassword.addEventListener('keyup', function() {
                    checkPasswordStrength(this.value);
                });
            }
            
            if (confirmPassword) {
                confirmPassword.addEventListener('keyup', checkPasswordMatch);
            }
        });

        function checkPasswordMatch() {
            const newPass = document.getElementById('new_password')?.value || '';
            const confirmPass = document.getElementById('confirm_password')?.value || '';
            const errorEl = document.getElementById('password_error');
            const submitBtn = document.getElementById('password_submit');
            
            if (!errorEl || !submitBtn) return;
            
            if (confirmPass && newPass !== confirmPass) {
                errorEl.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                errorEl.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strength_bar');
            const strengthText = document.getElementById('strength_text');
            
            if (!strengthBar || !strengthText) return;
            
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            if (password.match(/[$@#&!]+/)) strength += 25;
            
            strength = Math.min(strength, 100);
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 25) {
                strengthBar.className = 'h-full bg-red-500';
                strengthText.textContent = 'ضعيفة جداً';
            } else if (strength < 50) {
                strengthBar.className = 'h-full bg-orange-500';
                strengthText.textContent = 'ضعيفة';
            } else if (strength < 75) {
                strengthBar.className = 'h-full bg-yellow-500';
                strengthText.textContent = 'متوسطة';
            } else {
                strengthBar.className = 'h-full bg-green-500';
                strengthText.textContent = 'قوية';
            }
        }

        // =============================================
        // Modal الحذف
        // =============================================
        function deleteUser(id, name) {
            if (!id) return;
            
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteUserName').textContent = name || '';
            
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // =============================================
        // إغلاق المودال عند النقر خارجها
        // =============================================
        window.onclick = function(event) {
            // قائمة النوافذ
            const modals = [
                { id: 'addModal', closeFunc: closeAddModal },
                { id: 'editModal', closeFunc: closeEditModal },
                { id: 'passwordModal', closeFunc: closePasswordModal },
                { id: 'deleteModal', closeFunc: closeDeleteModal }
            ];
            
            modals.forEach(modal => {
                const modalElement = document.getElementById(modal.id);
                if (modalElement && event.target === modalElement) {
                    modal.closeFunc();
                }
            });
        };

        // =============================================
        // منع إغلاق النوافذ عند النقر داخل المحتوى
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const modalContents = document.querySelectorAll('.cyber-border');
            modalContents.forEach(content => {
                content.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });

        // =============================================
        // إظهار الإشعارات من PHP
        // =============================================
        <?php if (isset($_GET['success'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($_GET['success'] == 'added'): ?>
            showNotification('✅ تم إضافة المستخدم بنجاح', 'success');
            <?php elseif ($_GET['success'] == 'updated'): ?>
            showNotification('✅ تم تحديث بيانات المستخدم بنجاح', 'success');
            <?php elseif ($_GET['success'] == 'password'): ?>
            showNotification('✅ تم تغيير كلمة المرور بنجاح', 'success');
            <?php elseif ($_GET['success'] == 'deleted'): ?>
            showNotification('✅ تم حذف المستخدم بنجاح', 'success');
            <?php elseif ($_GET['success'] == 'bulk_deleted'): ?>
            showNotification('✅ تم حذف المستخدمين المحددين بنجاح', 'success');
            <?php endif; ?>
        });
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
        });
        <?php endif; ?>
    </script>
</body>
</html>