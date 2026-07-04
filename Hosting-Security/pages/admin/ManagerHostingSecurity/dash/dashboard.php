<?php
// dashboard.php - لوحة التحكم الإدارية
require_once '../../../../security-init.php';
// الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'security_monitoring_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// جلب الإحصائيات
$stats = [];

// إجمالي الخدمات
$stmt = $pdo->query("SELECT COUNT(*) FROM services");
$stats['total_services'] = $stmt->fetchColumn();

// إجمالي الفئات
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// إجمالي الطلبات
$stmt = $pdo->query("SELECT COUNT(*) FROM client_requests");
$stats['total_requests'] = $stmt->fetchColumn();

// إجمالي المستخدمين
$stmt = $pdo->query("SELECT COUNT(*) FROM users_login");
$stats['total_users'] = $stmt->fetchColumn();

// جلب آخر الخدمات
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, c.color as category_color 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    ORDER BY s.id DESC 
    LIMIT 5
");
$recent_services = $stmt->fetchAll();

// جلب آخر الطلبات
$stmt = $pdo->query("
    SELECT * FROM client_requests 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_requests = $stmt->fetchAll();

// جلب الفئات مع عدد الخدمات
$stmt = $pdo->query("
    SELECT c.*, COUNT(s.id) as services_count 
    FROM categories c 
    LEFT JOIN services s ON c.id = s.category_id 
    GROUP BY c.id
");
$categories_stats = $stmt->fetchAll();

// جلب جميع الخدمات للجدول
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, c.color as category_color 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    ORDER BY s.id DESC
");
$services = $stmt->fetchAll();

// جلب جميع الفئات
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id");
$categories = $stmt->fetchAll();

// جلب جميع الطلبات
$stmt = $pdo->query("
    SELECT * FROM client_requests 
    ORDER BY created_at DESC
");
$requests = $stmt->fetchAll();

// جلب جميع المستخدمين
$stmt = $pdo->query("
    SELECT id, username, full_name, email, role, created_at 
    FROM users_login 
    ORDER BY id DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X Cyber - لوحة التحكم الإدارية</title>
    <!-- منع التخزين المؤقت -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- CSS and Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #0066cc;
            --primary-red: #ff3333;
            --primary-green: #00cc66;
            --primary-purple: #9933ff;
            --dark-bg: #0a0a1a;
            --card-bg: rgba(20, 30, 48, 0.9);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: var(--dark-bg);
            color: white;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 102, 204, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 51, 51, 0.1) 0%, transparent 40%);
        }
        
        /* تدرجات الألوان */
        .gradient-text-blue {
            background: linear-gradient(135deg, var(--primary-blue), #3399ff);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .gradient-text-red {
            background: linear-gradient(135deg, var(--primary-red), #ff6666);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .gradient-text-green {
            background: linear-gradient(135deg, var(--primary-green), #66ff99);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .gradient-text-purple {
            background: linear-gradient(135deg, var(--primary-purple), #cc99ff);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* بطاقات الخدمات */
        .service-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(0, 102, 204, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .service-card:hover::before {
            transform: translateX(100%);
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-blue);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 60px rgba(0, 102, 204, 0.2);
        }
        
        /* أزرار التفاعل */
        .primary-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .primary-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 102, 204, 0.3);
        }
        
        /* أزرار الإجراءات */
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .edit-btn {
            background: rgba(0, 102, 204, 0.2);
            color: var(--primary-blue);
            border: 1px solid rgba(0, 102, 204, 0.3);
        }
        
        .edit-btn:hover {
            background: var(--primary-blue);
            color: white;
        }
        
        .delete-btn {
            background: rgba(255, 51, 51, 0.2);
            color: var(--primary-red);
            border: 1px solid rgba(255, 51, 51, 0.3);
        }
        
        .delete-btn:hover {
            background: var(--primary-red);
            color: white;
        }
        
        .view-btn {
            background: rgba(0, 204, 102, 0.2);
            color: var(--primary-green);
            border: 1px solid rgba(0, 204, 102, 0.3);
        }
        
        .view-btn:hover {
            background: var(--primary-green);
            color: white;
        }
        
        /* شريط التنقل الجانبي */
        .sidebar {
            background: rgba(10, 15, 25, 0.95);
            border-left: 1px solid rgba(0, 102, 204, 0.2);
            backdrop-filter: blur(10px);
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            z-index: 50;
        }
        
        .sidebar-link {
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #9ca3af;
            margin: 4px 0;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(0, 102, 204, 0.1);
            color: var(--primary-blue);
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* النوافذ المنبثقة */
        .modal {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--card-bg);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
        }
        
        /* حقول الإدخال */
        .form-input {
            background: rgba(30, 40, 60, 0.5);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            width: 100%;
            color: white;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        
        .form-input::placeholder {
            color: #6b7280;
        }
        
        /* البادجات */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-blue {
            background: rgba(0, 102, 204, 0.2);
            color: #3399ff;
            border: 1px solid rgba(0, 102, 204, 0.3);
        }
        
        .badge-red {
            background: rgba(255, 51, 51, 0.2);
            color: #ff6666;
            border: 1px solid rgba(255, 51, 51, 0.3);
        }
        
        .badge-green {
            background: rgba(0, 204, 102, 0.2);
            color: #66ff99;
            border: 1px solid rgba(0, 204, 102, 0.3);
        }
        
        .badge-purple {
            background: rgba(153, 51, 255, 0.2);
            color: #cc99ff;
            border: 1px solid rgba(153, 51, 255, 0.3);
        }
        
        /* الجداول */
        .table-container {
            overflow-x: auto;
            border-radius: 20px;
            background: var(--card-bg);
            border: 1px solid rgba(0, 102, 204, 0.2);
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(0, 102, 204, 0.1);
            padding: 16px;
            font-weight: 600;
            color: var(--primary-blue);
            text-align: right;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background: rgba(0, 102, 204, 0.05);
        }
        
        /* تخصيص التمرير */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0f1a2d;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border-radius: 5px;
        }
        
        /* بطاقات الإحصائيات */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 20px;
            padding: 24px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-blue);
        }
        
        /* الأقسام */
        .dashboard-section {
            display: none;
        }
        
        .dashboard-section.active {
            display: block;
        }
        
        /* مؤشر التحميل */
        .spinner {
            border: 3px solid rgba(0, 102, 204, 0.3);
            border-top: 3px solid var(--primary-blue);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- الشريط الجانبي -->
    <div class="sidebar p-6">
        <!-- الشعار -->
        <div class="flex items-center gap-3 mb-8">
            <div class="w-12 h-12 bg-gradient-to-br from-primary-blue to-primary-purple rounded-xl flex items-center justify-center">
                <i class="fas fa-crown text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold gradient-text-blue">X Cyber</h2>
                <p class="text-xs text-gray-400">لوحة التحكم</p>
            </div>
        </div>
        
        <!-- روابط القائمة -->
        <div class="space-y-2 mt-8">
            <a href="#" onclick="showSection('main')" class="sidebar-link active" id="link-main">
                <i class="fas fa-chart-pie w-6"></i>
                <span>الرئيسية</span>
            </a>
            <a href="#" onclick="showSection('services')" class="sidebar-link" id="link-services">
                <i class="fas fa-server w-6"></i>
                <span>الخدمات</span>
            </a>
            <a href="#" onclick="showSection('categories')" class="sidebar-link" id="link-categories">
                <i class="fas fa-tags w-6"></i>
                <span>الفئات</span>
            </a>
            <a href="#" onclick="showSection('requests')" class="sidebar-link" id="link-requests">
                <i class="fas fa-file-alt w-6"></i>
                <span>طلبات العملاء</span>
            </a>
            <a href="#" onclick="showSection('users')" class="sidebar-link" id="link-users">
                <i class="fas fa-users w-6"></i>
                <span>المستخدمين</span>
            </a>
            <a href="#" onclick="showSection('settings')" class="sidebar-link" id="link-settings">
                <i class="fas fa-cog w-6"></i>
                <span>الإعدادات</span>
            </a>
        </div>
        
        <!-- معلومات المدير -->
        <div class="absolute bottom-6 right-6 left-6">
            <div class="p-4 bg-primary-blue/10 rounded-xl border border-primary-blue/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-purple/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-primary-purple"></i>
                    </div>
                    <div>
                        <p class="font-bold">مدير النظام</p>
                        <p class="text-xs text-gray-400">admin@xcyber.com</p>
                    </div>
                    <a href="index.html" class="mr-auto text-gray-400 hover:text-primary-red" title="العودة للموقع">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- الهيدر -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold mb-2" id="page-title">لوحة التحكم الرئيسية</h1>
                <p class="text-gray-400" id="page-subtitle">نظرة عامة على النظام وإحصائيات الخدمات</p>
            </div>
            
            <div class="flex gap-4">
                <button onclick="openAddModal('service')" class="primary-btn" id="add-btn" style="display: none;">
                    <i class="fas fa-plus"></i>
                    <span>إضافة جديد</span>
                </button>
                <a href="index.html" class="px-4 py-3 border border-primary-blue/30 rounded-xl hover:border-primary-blue transition-all flex items-center gap-2">
                    <i class="fas fa-arrow-right"></i>
                    <span>العودة للموقع</span>
                </a>
            </div>
        </div>

        <!-- قسم الرئيسية -->
        <div id="main-section" class="dashboard-section active">
            <!-- بطاقات الإحصائيات -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-primary-blue/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-server text-primary-blue text-xl"></i>
                        </div>
                        <span class="badge badge-blue">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-blue"><?php echo $stats['total_services']; ?></h3>
                    <p class="text-gray-400">إجمالي الخدمات</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-primary-green/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-tags text-primary-green text-xl"></i>
                        </div>
                        <span class="badge badge-green">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-green"><?php echo $stats['total_categories']; ?></h3>
                    <p class="text-gray-400">الفئات</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-primary-purple/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-file-alt text-primary-purple text-xl"></i>
                        </div>
                        <span class="badge badge-purple">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-purple"><?php echo $stats['total_requests']; ?></h3>
                    <p class="text-gray-400">طلبات العملاء</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-primary-red/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-primary-red text-xl"></i>
                        </div>
                        <span class="badge badge-red">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-red"><?php echo $stats['total_users']; ?></h3>
                    <p class="text-gray-400">المستخدمين</p>
                </div>
            </div>
            
            <!-- آخر الخدمات -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="service-card p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text-blue">آخر الخدمات المضافة</h3>
                    <div id="recent-services" class="space-y-4">
                        <?php if (empty($recent_services)): ?>
                            <p class="text-gray-400 text-center py-4">لا توجد خدمات</p>
                        <?php else: ?>
                            <?php foreach ($recent_services as $service): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-xl">
                                <div>
                                    <p class="font-bold"><?php echo htmlspecialchars($service['name']); ?></p>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($service['price']); ?></p>
                                </div>
                                <span class="badge badge-<?php echo $service['category_color']; ?>">
                                    <?php echo htmlspecialchars($service['category_name']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="service-card p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text-green">آخر الطلبات</h3>
                    <div id="recent-requests-list" class="space-y-4">
                        <?php if (empty($recent_requests)): ?>
                            <p class="text-gray-400 text-center py-4">لا توجد طلبات</p>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $request): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-xl">
                                <div>
                                    <p class="font-bold"><?php echo htmlspecialchars($request['full_name']); ?></p>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($request['service_type']); ?></p>
                                </div>
                                <span class="badge badge-<?php echo $request['status'] == 'new' ? 'green' : 'blue'; ?>">
                                    <?php 
                                    $status_text = [
                                        'new' => 'جديد',
                                        'reviewing' => 'قيد المراجعة',
                                        'accepted' => 'مقبول',
                                        'rejected' => 'مرفوض',
                                        'completed' => 'مكتمل'
                                    ];
                                    echo $status_text[$request['status']] ?? $request['status'];
                                    ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- توزيع الفئات -->
            <div class="service-card p-6">
                <h3 class="text-xl font-bold mb-4 gradient-text-purple">توزيع الخدمات حسب الفئة</h3>
                <div id="categories-chart" class="space-y-4">
                    <?php if (empty($categories_stats)): ?>
                        <p class="text-gray-400 text-center py-4">لا توجد فئات</p>
                    <?php else: ?>
                        <?php foreach ($categories_stats as $category): 
                            $percentage = $stats['total_services'] > 0 ? round(($category['services_count'] / $stats['total_services']) * 100) : 0;
                        ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                <span class="text-<?php echo $category['color']; ?>-400">
                                    <?php echo $category['services_count']; ?> خدمة (<?php echo $percentage; ?>%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="bg-<?php echo $category['color']; ?>-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- قسم الخدمات -->
        <div id="services-section" class="dashboard-section">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold gradient-text-blue">إدارة الخدمات</h2>
                <button onclick="openAddModal('service')" class="primary-btn">
                    <i class="fas fa-plus"></i>
                    <span>إضافة خدمة جديدة</span>
                </button>
            </div>
            
            <!-- فلاتر البحث -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-4 text-gray-500"></i>
                    <input type="text" id="search-service" placeholder="بحث عن خدمة..." 
                           class="form-input pr-12" onkeyup="filterServices()">
                </div>
                
                <select id="filter-category" class="form-input" onchange="filterServices()">
                    <option value="">جميع الفئات</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="filter-status" class="form-input" onchange="filterServices()">
                    <option value="">جميع الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            
            <!-- جدول الخدمات -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الخدمة</th>
                            <th>الفئة</th>
                            <th>السعر</th>
                            <th>الحالة</th>
                            <th>مميز</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="services-table">
                        <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-8">
                                لا توجد خدمات
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($services as $index => $service): ?>
                            <tr data-id="<?php echo $service['id']; ?>" 
                                data-name="<?php echo htmlspecialchars($service['name']); ?>"
                                data-category="<?php echo $service['category_id']; ?>"
                                data-price="<?php echo htmlspecialchars($service['price']); ?>"
                                data-description="<?php echo htmlspecialchars($service['description']); ?>"
                                data-status="<?php echo $service['status']; ?>"
                                data-popular="<?php echo $service['popular']; ?>"
                                data-features='<?php echo $service['features']; ?>'>
                                <td><?php echo $index + 1; ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($service['name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $service['category_color']; ?>">
                                        <?php echo htmlspecialchars($service['category_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($service['price']); ?></td>
                                <td>
                                    <span class="badge <?php echo $service['status'] == 'active' ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo $service['status'] == 'active' ? 'نشط' : 'غير نشط'; ?>
                                    </span>
                                </td>
                                <td><?php echo $service['popular'] ? '⭐' : '-'; ?></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="editService(<?php echo $service['id']; ?>)" class="action-btn edit-btn" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteService(<?php echo $service['id']; ?>)" class="action-btn delete-btn" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- قسم الفئات -->
        <div id="categories-section" class="dashboard-section">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold gradient-text-purple">إدارة الفئات</h2>
                <button onclick="openAddModal('category')" class="primary-btn">
                    <i class="fas fa-plus"></i>
                    <span>إضافة فئة جديدة</span>
                </button>
            </div>
            
            <!-- جدول الفئات -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الفئة</th>
                            <th>المفتاح</th>
                            <th>اللون</th>
                            <th>عدد الخدمات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table">
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-8">
                                لا توجد فئات
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $index => $category): 
                                $services_count = 0;
                                foreach ($categories_stats as $stat) {
                                    if ($stat['id'] == $category['id']) {
                                        $services_count = $stat['services_count'];
                                        break;
                                    }
                                }
                            ?>
                            <tr data-id="<?php echo $category['id']; ?>"
                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                data-key="<?php echo htmlspecialchars($category['category_key']); ?>"
                                data-color="<?php echo $category['color']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><span class="badge badge-<?php echo $category['color']; ?>"><?php echo htmlspecialchars($category['category_key']); ?></span></td>
                                <td>
                                    <div class="w-6 h-6 rounded-full bg-<?php echo $category['color']; ?>-500"></div>
                                </td>
                                <td><?php echo $services_count; ?></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="editCategory(<?php echo $category['id']; ?>)" class="action-btn edit-btn" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="action-btn delete-btn" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- قسم طلبات العملاء -->
        <div id="requests-section" class="dashboard-section">
            <h2 class="text-2xl font-bold gradient-text-green mb-6">طلبات العملاء</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>البريد</th>
                            <th>الهاتف</th>
                            <th>الخدمة</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="requests-table">
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-gray-400 py-8">
                                لا توجد طلبات
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $index => $request): ?>
                            <tr data-id="<?php echo $request['id']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td><?php echo htmlspecialchars($request['phone']); ?></td>
                                <td><?php echo htmlspecialchars($request['service_type']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $status_colors = [
                                        'new' => 'badge-green',
                                        'reviewing' => 'badge-blue',
                                        'accepted' => 'badge-purple',
                                        'rejected' => 'badge-red',
                                        'completed' => 'badge-green'
                                    ];
                                    $status_texts = [
                                        'new' => 'جديد',
                                        'reviewing' => 'قيد المراجعة',
                                        'accepted' => 'مقبول',
                                        'rejected' => 'مرفوض',
                                        'completed' => 'مكتمل'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_colors[$request['status']] ?? 'badge-blue'; ?>">
                                        <?php echo $status_texts[$request['status']] ?? $request['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="updateRequestStatus(<?php echo $request['id']; ?>)" class="action-btn edit-btn" title="تحديث الحالة">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- قسم المستخدمين -->
        <div id="users-section" class="dashboard-section">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold gradient-text-red">إدارة المستخدمين</h2>
                <button onclick="openAddModal('user')" class="primary-btn">
                    <i class="fas fa-plus"></i>
                    <span>إضافة مستخدم</span>
                </button>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المستخدم</th>
                            <th>الاسم الكامل</th>
                            <th>البريد</th>
                            <th>الدور</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="users-table">
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-8">
                                لا توجد مستخدمين
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $index => $user): ?>
                            <tr data-id="<?php echo $user['id']; ?>"
                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                data-role="<?php echo $user['role']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'red' : 'blue'; ?>">
                                        <?php 
                                        $role_names = [
                                            'admin' => 'مدير عام',
                                            'manager' => 'مدير',
                                            'editor' => 'محرر'
                                        ];
                                        echo $role_names[$user['role']] ?? $user['role']; 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="editUser(<?php echo $user['id']; ?>)" class="action-btn edit-btn" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="action-btn delete-btn" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- قسم الإعدادات -->
        <div id="settings-section" class="dashboard-section">
            <h2 class="text-2xl font-bold gradient-text-blue mb-6">إعدادات النظام</h2>
            
            <div class="service-card p-8 max-w-2xl">
                <form id="settings-form" class="space-y-6">
                    <div>
                        <label class="block mb-2 text-gray-300">اسم الشركة</label>
                        <input type="text" id="company-name" class="form-input" value="X Cyber Hosting">
                    </div>
                    
                    <div>
                        <label class="block mb-2 text-gray-300">البريد الإلكتروني</label>
                        <input type="email" id="company-email" class="form-input" value="info@xcyber.com">
                    </div>
                    
                    <div>
                        <label class="block mb-2 text-gray-300">رقم الهاتف</label>
                        <input type="text" id="company-phone" class="form-input" value="+966 12 345 6789">
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="maintenance-mode" class="w-5 h-5">
                            <span>وضع الصيانة</span>
                        </label>
                        
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="show-prices" class="w-5 h-5" checked>
                            <span>عرض الأسعار</span>
                        </label>
                    </div>
                    
                    <button type="button" onclick="saveSettings()" class="primary-btn">
                        <i class="fas fa-save ml-2"></i>
                        حفظ الإعدادات
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل الخدمة -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-start mb-6">
                <h3 class="text-2xl font-bold gradient-text-blue" id="modal-title">إضافة خدمة جديدة</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="modal-form" method="POST" class="space-y-4">
                <input type="hidden" id="modal-action" name="action" value="add">
                <input type="hidden" id="modal-id" name="id">
                
                <div>
                    <label class="block mb-2">اسم الخدمة</label>
                    <input type="text" id="service-name" name="name" required class="form-input">
                </div>
                
                <div>
                    <label class="block mb-2">الفئة</label>
                    <select id="service-category" name="category_id" required class="form-input">
                        <option value="">اختر الفئة</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2">السعر</label>
                    <input type="text" id="service-price" name="price" required class="form-input" placeholder="مثال: 49 ريال/شهر">
                </div>
                
                <div>
                    <label class="block mb-2">الوصف</label>
                    <textarea id="service-description" name="description" required rows="3" class="form-input"></textarea>
                </div>
                
                <div>
                    <label class="block mb-2">المميزات (مفصولة بفاصلة)</label>
                    <input type="text" id="service-features" name="features" class="form-input" placeholder="مساحة 10GB, دعم PHP, قاعدة بيانات">
                </div>
                
                <div>
                    <label class="block mb-2">مدة التنفيذ</label>
                    <input type="text" id="service-setup" name="setup_time" class="form-input" placeholder="مثال: 24 ساعة">
                </div>
                
                <div>
                    <label class="block mb-2">ضمان التوافر</label>
                    <input type="text" id="service-sla" name="sla" class="form-input" placeholder="مثال: 99.9%">
                </div>
                
                <div>
                    <label class="block mb-2">الأيقونة</label>
                    <select id="service-icon" name="icon" class="form-input">
                        <option value="fa-server">خادم</option>
                        <option value="fa-globe">موقع</option>
                        <option value="fa-shield-alt">حماية</option>
                        <option value="fa-cloud">سحابة</option>
                        <option value="fa-database">قاعدة بيانات</option>
                        <option value="fa-code">كود</option>
                    </select>
                </div>
                
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="service-status" name="status" class="w-5 h-5" value="active" checked>
                        <span>نشط</span>
                    </label>
                    
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="service-popular" name="popular" class="w-5 h-5" value="1">
                        <span>مميز</span>
                    </label>
                </div>
                
                <div class="flex gap-4 pt-4">
                    <button type="submit" class="primary-btn flex-1">
                        <i class="fas fa-save ml-2"></i>
                        حفظ
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 bg-gray-700 rounded-xl hover:bg-gray-600">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- نافذة تأكيد الحذف -->
    <div id="delete-modal" class="modal">
        <div class="modal-content max-w-md text-center">
            <div class="w-20 h-20 mx-auto mb-4 bg-red-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
            </div>
            
            <h3 class="text-2xl font-bold mb-2">تأكيد الحذف</h3>
            <p class="text-gray-400 mb-6" id="delete-message">هل أنت متأكد من الحذف؟</p>
            
            <input type="hidden" id="delete-id">
            <input type="hidden" id="delete-type">
            
            <div class="flex gap-4">
                <button onclick="confirmDelete()" class="flex-1 px-6 py-3 bg-red-600 rounded-xl hover:bg-red-700">
                    حذف
                </button>
                <button onclick="closeDeleteModal()" class="flex-1 px-6 py-3 bg-gray-700 rounded-xl hover:bg-gray-600">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
    <!-- نافذة إضافة/تعديل الخدمة -->
<div id="serviceModal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-start mb-6">
            <h3 class="text-2xl font-bold gradient-text-blue" id="serviceModalTitle">إضافة خدمة جديدة</h3>
            <button onclick="closeServiceModal()" class="text-gray-400 hover:text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="serviceForm" method="POST" class="space-y-4">
            <input type="hidden" id="serviceId" name="id">
            
            <div>
                <label class="block mb-2">اسم الخدمة</label>
                <input type="text" id="serviceName" name="name" required class="form-input">
            </div>
            
            <div>
                <label class="block mb-2">الفئة</label>
                <select id="serviceCategory" name="category_id" required class="form-input">
                    <option value="">اختر الفئة</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2">السعر</label>
                <input type="text" id="servicePrice" name="price" required class="form-input" placeholder="مثال: 49 ريال/شهر">
            </div>
            
            <div>
                <label class="block mb-2">الوصف</label>
                <textarea id="serviceDescription" name="description" required rows="3" class="form-input"></textarea>
            </div>
            
            <div>
                <label class="block mb-2">المميزات (مفصولة بفاصلة)</label>
                <input type="text" id="serviceFeatures" name="features" class="form-input" placeholder="مساحة 10GB, دعم PHP, قاعدة بيانات">
            </div>
            
            <div>
                <label class="block mb-2">مدة التنفيذ</label>
                <input type="text" id="serviceSetup" name="setup_time" class="form-input" placeholder="مثال: 24 ساعة">
            </div>
            
            <div>
                <label class="block mb-2">ضمان التوافر</label>
                <input type="text" id="serviceSla" name="sla" class="form-input" placeholder="مثال: 99.9%">
            </div>
            
            <div>
                <label class="block mb-2">الأيقونة</label>
                <select id="serviceIcon" name="icon" class="form-input">
                    <option value="fa-server">خادم</option>
                    <option value="fa-globe">موقع</option>
                    <option value="fa-shield-alt">حماية</option>
                    <option value="fa-cloud">سحابة</option>
                    <option value="fa-database">قاعدة بيانات</option>
                    <option value="fa-code">كود</option>
                </select>
            </div>
            
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="serviceStatus" name="status" class="w-5 h-5" value="active" checked>
                    <span>نشط</span>
                </label>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="servicePopular" name="popular" class="w-5 h-5" value="1">
                    <span>مميز</span>
                </label>
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="saveService()" class="primary-btn flex-1">
                    <i class="fas fa-save ml-2"></i>
                    حفظ
                </button>
                <button type="button" onclick="closeServiceModal()" class="flex-1 px-6 py-3 bg-gray-700 rounded-xl hover:bg-gray-600">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة إضافة/تعديل الفئة -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-start mb-6">
            <h3 class="text-2xl font-bold gradient-text-purple" id="categoryModalTitle">إضافة فئة جديدة</h3>
            <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="categoryForm" class="space-y-4">
            <input type="hidden" id="categoryId">
            
            <div>
                <label class="block mb-2">اسم الفئة</label>
                <input type="text" id="categoryName" required class="form-input">
            </div>
            
            <div>
                <label class="block mb-2">المفتاح</label>
                <input type="text" id="categoryKey" required class="form-input" placeholder="مثال: hosting">
                <p class="text-xs text-gray-400 mt-1">يستخدم في الروابط (بالانجليزي)</p>
            </div>
            
            <div>
                <label class="block mb-2">اللون</label>
                <select id="categoryColor" class="form-input">
                    <option value="blue">أزرق</option>
                    <option value="red">أحمر</option>
                    <option value="green">أخضر</option>
                    <option value="purple">بنفسجي</option>
                    <option value="yellow">أصفر</option>
                    <option value="orange">برتقالي</option>
                </select>
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="saveCategory()" class="primary-btn flex-1">
                    <i class="fas fa-save ml-2"></i>
                    حفظ
                </button>
                <button type="button" onclick="closeCategoryModal()" class="flex-1 px-6 py-3 bg-gray-700 rounded-xl hover:bg-gray-600">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة إضافة/تعديل المستخدم -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-start mb-6">
            <h3 class="text-2xl font-bold gradient-text-red" id="userModalTitle">إضافة مستخدم جديد</h3>
            <button onclick="closeUserModal()" class="text-gray-400 hover:text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="userForm" class="space-y-4">
            <input type="hidden" id="userId">
            
            <div>
                <label class="block mb-2">اسم المستخدم</label>
                <input type="text" id="userUsername" required class="form-input">
            </div>
            
            <div>
                <label class="block mb-2">الاسم الكامل</label>
                <input type="text" id="userFullname" required class="form-input">
            </div>
            
            <div>
                <label class="block mb-2">البريد الإلكتروني</label>
                <input type="email" id="userEmail" required class="form-input">
            </div>
            
            <div>
                <label class="block mb-2">كلمة المرور</label>
                <input type="password" id="userPassword" class="form-input" placeholder="اترك فارغاً إذا لم ترد التغيير">
            </div>
            
            <div>
                <label class="block mb-2">الدور</label>
                <select id="userRole" class="form-input">
                    <option value="editor">محرر</option>
                    <option value="manager">مدير</option>
                    <option value="admin">مدير عام</option>
                </select>
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="saveUser()" class="primary-btn flex-1">
                    <i class="fas fa-save ml-2"></i>
                    حفظ
                </button>
                <button type="button" onclick="closeUserModal()" class="flex-1 px-6 py-3 bg-gray-700 rounded-xl hover:bg-gray-600">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        // =====================================================
        // التنقل بين الأقسام
        // =====================================================
        function showSection(section) {
            // إخفاء كل الأقسام
            document.querySelectorAll('.dashboard-section').forEach(el => {
                el.classList.remove('active');
            });
            
            // إظهار القسم المطلوب
            document.getElementById(section + '-section').classList.add('active');
            
            // تحديث الرابط النشط
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('active');
            });
            document.getElementById('link-' + section).classList.add('active');
            
            // تحديث عنوان الصفحة
            const titles = {
                'main': { title: 'لوحة التحكم الرئيسية', subtitle: 'نظرة عامة على النظام وإحصائيات الخدمات' },
                'services': { title: 'إدارة الخدمات', subtitle: 'إضافة وتعديل وحذف الخدمات' },
                'categories': { title: 'إدارة الفئات', subtitle: 'تنظيم فئات الخدمات' },
                'requests': { title: 'طلبات العملاء', subtitle: 'إدارة طلبات الخدمات الواردة' },
                'users': { title: 'إدارة المستخدمين', subtitle: 'إدارة حسابات المستخدمين والصلاحيات' },
                'settings': { title: 'الإعدادات', subtitle: 'تخصيص إعدادات النظام' }
            };
            
            document.getElementById('page-title').textContent = titles[section].title;
            document.getElementById('page-subtitle').textContent = titles[section].subtitle;
            
            // تحديث زر الإضافة
            const addBtn = document.getElementById('add-btn');
            if (section === 'services' || section === 'categories' || section === 'users') {
                addBtn.style.display = 'flex';
                if (section === 'services') addBtn.onclick = () => openAddModal('service');
                if (section === 'categories') addBtn.onclick = () => openAddModal('category');
                if (section === 'users') addBtn.onclick = () => openAddModal('user');
            } else {
                addBtn.style.display = 'none';
            }
        }

        // =====================================================
        // نوافذ الإضافة والتعديل
        // =====================================================
        function openAddModal(type) {
            document.getElementById('modal-title').textContent = type === 'service' ? 'إضافة خدمة جديدة' : 
                                                                 type === 'category' ? 'إضافة فئة جديدة' : 
                                                                 'إضافة مستخدم جديد';
            
            let fields = '';
            
            if (type === 'service') {
                fields = `
                    <div>
                        <label class="block mb-2">اسم الخدمة</label>
                        <input type="text" name="name" required class="form-input" id="modal-service-name">
                    </div>
                    <div>
                        <label class="block mb-2">الفئة</label>
                        <select name="category_id" required class="form-input" id="modal-service-category">
                            <option value="">اختر الفئة</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2">السعر</label>
                        <input type="text" name="price" required class="form-input" id="modal-service-price">
                    </div>
                    <div>
                        <label class="block mb-2">الوصف</label>
                        <textarea name="description" required rows="3" class="form-input" id="modal-service-description"></textarea>
                    </div>
                    <div>
                        <label class="block mb-2">المميزات</label>
                        <input type="text" name="features" class="form-input" id="modal-service-features">
                    </div>
                    <div>
                        <label class="block mb-2">مدة التنفيذ</label>
                        <input type="text" name="setup_time" class="form-input" id="modal-service-setup">
                    </div>
                    <div>
                        <label class="block mb-2">ضمان التوافر</label>
                        <input type="text" name="sla" class="form-input" id="modal-service-sla">
                    </div>
                    <div>
                        <label class="block mb-2">الأيقونة</label>
                        <select name="icon" class="form-input" id="modal-service-icon">
                            <option value="fa-server">خادم</option>
                            <option value="fa-globe">موقع</option>
                            <option value="fa-shield-alt">حماية</option>
                            <option value="fa-cloud">سحابة</option>
                            <option value="fa-database">قاعدة بيانات</option>
                            <option value="fa-code">كود</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="status" value="active" class="w-5 h-5" id="modal-service-status" checked>
                            <span>نشط</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="popular" value="1" class="w-5 h-5" id="modal-service-popular">
                            <span>مميز</span>
                        </label>
                    </div>
                `;
            } else if (type === 'category') {
                fields = `
                    <div>
                        <label class="block mb-2">اسم الفئة</label>
                        <input type="text" name="name" required class="form-input" id="modal-category-name">
                    </div>
                    <div>
                        <label class="block mb-2">المفتاح</label>
                        <input type="text" name="key" required class="form-input" id="modal-category-key" placeholder="مثال: hosting">
                    </div>
                    <div>
                        <label class="block mb-2">اللون</label>
                        <select name="color" class="form-input" id="modal-category-color">
                            <option value="blue">أزرق</option>
                            <option value="red">أحمر</option>
                            <option value="green">أخضر</option>
                            <option value="purple">بنفسجي</option>
                        </select>
                    </div>
                `;
            } else if (type === 'user') {
                fields = `
                    <div>
                        <label class="block mb-2">اسم المستخدم</label>
                        <input type="text" name="username" required class="form-input" id="modal-user-username">
                    </div>
                    <div>
                        <label class="block mb-2">الاسم الكامل</label>
                        <input type="text" name="full_name" required class="form-input" id="modal-user-fullname">
                    </div>
                    <div>
                        <label class="block mb-2">البريد الإلكتروني</label>
                        <input type="email" name="email" required class="form-input" id="modal-user-email">
                    </div>
                    <div>
                        <label class="block mb-2">كلمة المرور</label>
                        <input type="password" name="password" class="form-input" id="modal-user-password">
                    </div>
                    <div>
                        <label class="block mb-2">الدور</label>
                        <select name="role" class="form-input" id="modal-user-role">
                            <option value="editor">محرر</option>
                            <option value="manager">مدير</option>
                            <option value="admin">مدير عام</option>
                        </select>
                    </div>
                `;
            }
            
            document.getElementById('modal-fields').innerHTML = fields;
            document.getElementById('modal').classList.add('active');
        }

        // =====================================================
        // فلترة الخدمات
        // =====================================================
        function filterServices() {
            const searchTerm = document.getElementById('search-service').value.toLowerCase();
            const categoryId = document.getElementById('filter-category').value;
            const status = document.getElementById('filter-status').value;
            
            const rows = document.querySelectorAll('#services-table tr');
            
            rows.forEach(row => {
                if (row.querySelector('td[colspan]')) return;
                
                const name = row.dataset.name?.toLowerCase() || '';
                const rowCategory = row.dataset.category || '';
                const rowStatus = row.dataset.status || '';
                
                const matchesSearch = name.includes(searchTerm);
                const matchesCategory = !categoryId || rowCategory === categoryId;
                const matchesStatus = !status || rowStatus === status;
                
                row.style.display = matchesSearch && matchesCategory && matchesStatus ? '' : 'none';
            });
        }

        // =====================================================
        // إجراءات الخدمات
        // =====================================================
        function editService(id) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) return;
            
            openAddModal('service');
            document.getElementById('modal-title').textContent = 'تعديل الخدمة';
            document.getElementById('modal-id').value = id;
            document.getElementById('modal-service-name').value = row.dataset.name;
            document.getElementById('modal-service-category').value = row.dataset.category;
            document.getElementById('modal-service-price').value = row.dataset.price;
            document.getElementById('modal-service-description').value = row.dataset.description;
            document.getElementById('modal-service-features').value = row.dataset.features ? JSON.parse(row.dataset.features).join(', ') : '';
            document.getElementById('modal-service-status').checked = row.dataset.status === 'active';
            document.getElementById('modal-service-popular').checked = row.dataset.popular == 1;
            
            // تغيير action إلى update
            document.getElementById('modal-form').action = 'api/services.php';
            document.getElementById('modal-action').value = 'update';
        }

        function deleteService(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-type').value = 'service';
            document.getElementById('delete-message').textContent = 'هل أنت متأكد من حذف هذه الخدمة؟';
            document.getElementById('delete-modal').classList.add('active');
        }

        // =====================================================
        // إجراءات الفئات
        // =====================================================
        function editCategory(id) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) return;
            
            openAddModal('category');
            document.getElementById('modal-title').textContent = 'تعديل الفئة';
            document.getElementById('modal-id').value = id;
            document.getElementById('modal-category-name').value = row.dataset.name;
            document.getElementById('modal-category-key').value = row.dataset.key;
            document.getElementById('modal-category-color').value = row.dataset.color;
        }

        function deleteCategory(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-type').value = 'category';
            document.getElementById('delete-message').textContent = 'هل أنت متأكد من حذف هذه الفئة؟';
            document.getElementById('delete-modal').classList.add('active');
        }

        // =====================================================
        // إجراءات المستخدمين
        // =====================================================
        function editUser(id) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) return;
            
            openAddModal('user');
            document.getElementById('modal-title').textContent = 'تعديل المستخدم';
            document.getElementById('modal-id').value = id;
            document.getElementById('modal-user-username').value = row.dataset.username;
            document.getElementById('modal-user-fullname').value = row.dataset.fullname;
            document.getElementById('modal-user-email').value = row.dataset.email;
            document.getElementById('modal-user-role').value = row.dataset.role;
            document.getElementById('modal-user-password').required = false;
        }

        function deleteUser(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-type').value = 'user';
            document.getElementById('delete-message').textContent = 'هل أنت متأكد من حذف هذا المستخدم؟';
            document.getElementById('delete-modal').classList.add('active');
        }

        // =====================================================
        // تحديث حالة الطلب
        // =====================================================
        function updateRequestStatus(id) {
            window.location.href = `api/update_request_status.php?id=${id}`;
        }

        // =====================================================
        // حفظ الإعدادات
        // =====================================================
        function saveSettings() {
            alert('تم حفظ الإعدادات بنجاح');
        }

        // =====================================================
        // إرسال النموذج
        // =====================================================
        document.getElementById('modal-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = document.getElementById('modal-action').value;
            
            formData.append('action', action);
            
            fetch('api/services.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('خطأ: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        });

        // =====================================================
        // تأكيد الحذف
        // =====================================================
        function confirmDelete() {
            const id = document.getElementById('delete-id').value;
            const type = document.getElementById('delete-type').value;
            
            let url = '';
            if (type === 'service') url = `api/services.php`;
            else if (type === 'category') url = `api/categories.php`;
            else if (type === 'user') url = `api/users.php`;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('خطأ: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
            
            closeDeleteModal();
        }

        // =====================================================
        // إغلاق النوافذ
        // =====================================================
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('active');
        }

        // إغلاق النوافذ عند النقر خارجها
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
                closeDeleteModal();
            }
        }
        // =====================================================
// نوافذ الخدمات المنبثقة
// =====================================================

// فتح نافذة إضافة خدمة
function openAddServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'إضافة خدمة جديدة';
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceForm').reset();
    document.getElementById('serviceModal').classList.add('active');
}

// فتح نافذة تعديل خدمة
function openEditServiceModal(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;
    
    document.getElementById('serviceModalTitle').textContent = 'تعديل الخدمة';
    document.getElementById('serviceId').value = id;
    document.getElementById('serviceName').value = row.dataset.name || '';
    document.getElementById('serviceCategory').value = row.dataset.category || '';
    document.getElementById('servicePrice').value = row.dataset.price || '';
    document.getElementById('serviceDescription').value = row.dataset.description || '';
    
    // تحويل المميزات من JSON إلى نص
    if (row.dataset.features) {
        try {
            const features = JSON.parse(row.dataset.features);
            document.getElementById('serviceFeatures').value = features.join(', ');
        } catch(e) {
            document.getElementById('serviceFeatures').value = '';
        }
    }
    
    document.getElementById('serviceSetup').value = row.dataset.setup_time || '';
    document.getElementById('serviceSla').value = row.dataset.sla || '';
    document.getElementById('serviceIcon').value = row.dataset.icon || 'fa-server';
    document.getElementById('serviceStatus').checked = row.dataset.status === 'active';
    document.getElementById('servicePopular').checked = row.dataset.popular == 1;
    
    document.getElementById('serviceModal').classList.add('active');
}

// إغلاق نافذة الخدمة
function closeServiceModal() {
    document.getElementById('serviceModal').classList.remove('active');
}

// حفظ الخدمة
function saveService() {
    const id = document.getElementById('serviceId').value;
    const formData = new FormData();
    
    formData.append('action', id ? 'update' : 'add');
    if (id) formData.append('id', id);
    formData.append('name', document.getElementById('serviceName').value);
    formData.append('category_id', document.getElementById('serviceCategory').value);
    formData.append('price', document.getElementById('servicePrice').value);
    formData.append('description', document.getElementById('serviceDescription').value);
    formData.append('features', document.getElementById('serviceFeatures').value);
    formData.append('setup_time', document.getElementById('serviceSetup').value);
    formData.append('sla', document.getElementById('serviceSla').value);
    formData.append('icon', document.getElementById('serviceIcon').value);
    formData.append('status', document.getElementById('serviceStatus').checked ? 'active' : 'inactive');
    formData.append('popular', document.getElementById('servicePopular').checked ? '1' : '0');
    
    fetch('api/services.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('خطأ: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال', 'error');
    });
}

// =====================================================
// نوافذ الفئات المنبثقة
// =====================================================

// فتح نافذة إضافة فئة
function openAddCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'إضافة فئة جديدة';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryModal').classList.add('active');
}

// فتح نافذة تعديل فئة
function openEditCategoryModal(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;
    
    document.getElementById('categoryModalTitle').textContent = 'تعديل الفئة';
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = row.dataset.name || '';
    document.getElementById('categoryKey').value = row.dataset.key || '';
    document.getElementById('categoryColor').value = row.dataset.color || 'blue';
    
    document.getElementById('categoryModal').classList.add('active');
}

// إغلاق نافذة الفئة
function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

// حفظ الفئة
function saveCategory() {
    const id = document.getElementById('categoryId').value;
    const formData = new FormData();
    
    formData.append('action', id ? 'update' : 'add');
    if (id) formData.append('id', id);
    formData.append('name', document.getElementById('categoryName').value);
    formData.append('key', document.getElementById('categoryKey').value);
    formData.append('color', document.getElementById('categoryColor').value);
    
    fetch('api/categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('خطأ: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال', 'error');
    });
}

// =====================================================
// نوافذ المستخدمين المنبثقة
// =====================================================

// فتح نافذة إضافة مستخدم
function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'إضافة مستخدم جديد';
    document.getElementById('userId').value = '';
    document.getElementById('userForm').reset();
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.add('active');
}

// فتح نافذة تعديل مستخدم
function openEditUserModal(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;
    
    document.getElementById('userModalTitle').textContent = 'تعديل المستخدم';
    document.getElementById('userId').value = id;
    document.getElementById('userUsername').value = row.dataset.username || '';
    document.getElementById('userFullname').value = row.dataset.fullname || '';
    document.getElementById('userEmail').value = row.dataset.email || '';
    document.getElementById('userRole').value = row.dataset.role || 'editor';
    document.getElementById('userPassword').required = false;
    document.getElementById('userPassword').placeholder = 'اترك فارغاً إذا لم ترد التغيير';
    
    document.getElementById('userModal').classList.add('active');
}

// إغلاق نافذة المستخدم
function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

// حفظ المستخدم
function saveUser() {
    const id = document.getElementById('userId').value;
    const formData = new FormData();
    
    formData.append('action', id ? 'update' : 'add');
    if (id) formData.append('id', id);
    formData.append('username', document.getElementById('userUsername').value);
    formData.append('full_name', document.getElementById('userFullname').value);
    formData.append('email', document.getElementById('userEmail').value);
    formData.append('role', document.getElementById('userRole').value);
    
    const password = document.getElementById('userPassword').value;
    if (password) {
        formData.append('password', password);
    }
    
    fetch('api/users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('خطأ: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال', 'error');
    });
}

// =====================================================
// دوال مساعدة
// =====================================================

// عرض الإشعارات
function showNotification(message, type = 'info') {
    // إنشاء عنصر الإشعار
    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-4 z-50 px-6 py-4 rounded-lg shadow-lg notification ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        'bg-blue-600'
    } text-white flex items-center gap-3`;
    
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} text-xl"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // إخفاء الإشعار بعد 3 ثواني
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// تحديث أزرار الإضافة حسب القسم
function updateAddButton(section) {
    const addBtn = document.getElementById('add-btn');
    if (!addBtn) return;
    
    if (section === 'services') {
        addBtn.style.display = 'flex';
        addBtn.onclick = openAddServiceModal;
        addBtn.innerHTML = '<i class="fas fa-plus"></i><span>إضافة خدمة جديدة</span>';
    } else if (section === 'categories') {
        addBtn.style.display = 'flex';
        addBtn.onclick = openAddCategoryModal;
        addBtn.innerHTML = '<i class="fas fa-plus"></i><span>إضافة فئة جديدة</span>';
    } else if (section === 'users') {
        addBtn.style.display = 'flex';
        addBtn.onclick = openAddUserModal;
        addBtn.innerHTML = '<i class="fas fa-plus"></i><span>إضافة مستخدم جديد</span>';
    } else {
        addBtn.style.display = 'none';
    }
}

// تعديل دالة showSection
const originalShowSection = showSection;
showSection = function(section) {
    originalShowSection(section);
    updateAddButton(section);
};

// تعديل أزرار التعديل في الجدول
document.addEventListener('DOMContentLoaded', function() {
    // ربط أزرار تعديل الخدمات
    document.querySelectorAll('#services-table .edit-btn').forEach(btn => {
        btn.onclick = function() {
            const id = this.closest('tr').dataset.id;
            openEditServiceModal(id);
        };
    });
    
    // ربط أزرار تعديل الفئات
    document.querySelectorAll('#categories-table .edit-btn').forEach(btn => {
        btn.onclick = function() {
            const id = this.closest('tr').dataset.id;
            openEditCategoryModal(id);
        };
    });
    
    // ربط أزرار تعديل المستخدمين
    document.querySelectorAll('#users-table .edit-btn').forEach(btn => {
        btn.onclick = function() {
            const id = this.closest('tr').dataset.id;
            openEditUserModal(id);
        };
    });
    
    // ربط أزرار الحذف
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.onclick = function() {
            const id = this.closest('tr').dataset.id;
            const type = this.closest('table').id;
            
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-type').value = 
                type === 'services-table' ? 'service' : 
                type === 'categories-table' ? 'category' : 'user';
            
            document.getElementById('delete-message').textContent = 
                type === 'services-table' ? 'هل أنت متأكد من حذف هذه الخدمة؟' :
                type === 'categories-table' ? 'هل أنت متأكد من حذف هذه الفئة؟' :
                'هل أنت متأكد من حذف هذا المستخدم؟';
            
            document.getElementById('delete-modal').classList.add('active');
        };
    });
}); 
    </script>
    <style>
        /* النوافذ المنبثقة */
.modal {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    position: fixed;
    inset: 0;
    z-index: 100;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--card-bg);
    border: 1px solid rgba(0, 102, 204, 0.2);
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 30px;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* الإشعارات */
.notification {
    animation: notificationSlideIn 0.3s ease;
    z-index: 1000;
}

@keyframes notificationSlideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
    </style>
    
</body>
</html>