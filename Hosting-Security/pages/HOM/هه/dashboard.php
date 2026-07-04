<?php
// dashboard.php - لوحة التحكم (وصول مباشر بدون تسجيل دخول)

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

// جلب الإحصائيات
$stats = [];

// إجمالي الخدمات
$stmt = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'active'");
$stats['total_services'] = $stmt->fetchColumn();

// إجمالي الطلبات
$stmt = $pdo->query("SELECT COUNT(*) FROM client_requests");
$stats['total_requests'] = $stmt->fetchColumn();

// إجمالي الفئات
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// إجمالي المستخدمين
$stmt = $pdo->query("SELECT COUNT(*) FROM users_login");
$stats['total_users'] = $stmt->fetchColumn();

// جلب أحدث الخدمات
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, c.color as category_color 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY s.created_at DESC 
    LIMIT 6
");
$recent_services = $stmt->fetchAll();

// جلب أحدث الطلبات
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
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X Cyber - لوحة التحكم</title>
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
        
        /* الأقسام */
        .section-padding {
            padding: 60px 0;
        }
        
        /* تأثيرات التمرير */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
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
        
        /* خطوط التقسيم */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 102, 204, 0.5), transparent);
            margin: 40px 0;
        }
        
        /* الجداول */
        .table-container {
            overflow-x: auto;
            border-radius: 20px;
            background: var(--card-bg);
            border: 1px solid rgba(0, 102, 204, 0.2);
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
        
        /* الأنيميشن */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="fixed w-full bg-dark-bg/90 backdrop-blur-lg z-50 border-b border-primary-blue/20">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <!-- الشعار -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-blue to-primary-purple rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-pie text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold gradient-text-blue">X Cyber Dashboard</h1>
                        <p class="text-xs text-gray-400">لوحة التحكم</p>
                    </div>
                </div>
                
                <!-- القائمة -->
                <div class="hidden md:flex items-center space-x-8 space-x-reverse">
                    <a href="index.html" class="text-gray-300 hover:text-white transition-all duration-300">
                        <i class="fas fa-home ml-2"></i>
                        الرئيسية
                    </a>
                    <a href="#stats" class="text-gray-300 hover:text-white transition-all duration-300">
                        <i class="fas fa-chart-bar ml-2"></i>
                        الإحصائيات
                    </a>
                    <a href="#services" class="text-gray-300 hover:text-white transition-all duration-300">
                        <i class="fas fa-server ml-2"></i>
                        الخدمات
                    </a>
                    <a href="#requests" class="text-gray-300 hover:text-white transition-all duration-300">
                        <i class="fas fa-file-alt ml-2"></i>
                        الطلبات
                    </a>
                </div>
                
                <!-- أزرار -->
                <div class="flex items-center gap-4">
                    <a href="index.html" class="px-4 py-2 border border-primary-blue/30 rounded-xl hover:border-primary-blue transition-all">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة للموقع
                    </a>
                    <a href="login.html" class="primary-btn">
                        <span>تسجيل دخول</span>
                        <i class="fas fa-user mr-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- الهيدر -->
    <section class="pt-32 pb-12">
        <div class="container mx-auto px-6">
            <div class="text-center fade-in">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    لوحة التحكم <span class="gradient-text-blue">الرئيسية</span>
                </h1>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    نظرة عامة على النظام وإحصائيات الخدمات والطلبات
                </p>
            </div>
        </div>
    </section>

    <!-- الإحصائيات -->
    <section id="stats" class="pb-20">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- بطاقة الخدمات -->
                <div class="service-card p-6 fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-primary-blue/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-server text-2xl text-primary-blue"></i>
                        </div>
                        <span class="badge badge-blue">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-blue"><?php echo $stats['total_services']; ?></h3>
                    <p class="text-gray-400">إجمالي الخدمات</p>
                </div>
                
                <!-- بطاقة الطلبات -->
                <div class="service-card p-6 fade-in" style="transition-delay: 0.1s">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-primary-green/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-file-alt text-2xl text-primary-green"></i>
                        </div>
                        <span class="badge badge-green">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-green"><?php echo $stats['total_requests']; ?></h3>
                    <p class="text-gray-400">طلبات العملاء</p>
                </div>
                
                <!-- بطاقة الفئات -->
                <div class="service-card p-6 fade-in" style="transition-delay: 0.2s">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-primary-purple/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-tags text-2xl text-primary-purple"></i>
                        </div>
                        <span class="badge badge-purple">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-purple"><?php echo $stats['total_categories']; ?></h3>
                    <p class="text-gray-400">فئات الخدمات</p>
                </div>
                
                <!-- بطاقة المستخدمين -->
                <div class="service-card p-6 fade-in" style="transition-delay: 0.3s">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-primary-red/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-users text-2xl text-primary-red"></i>
                        </div>
                        <span class="badge badge-red">إجمالي</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1 gradient-text-red"><?php echo $stats['total_users']; ?></h3>
                    <p class="text-gray-400">المستخدمين</p>
                </div>
            </div>
        </div>
    </section>

    <!-- الفئات والخدمات -->
    <section id="services" class="pb-20">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold mb-8 text-center fade-in">
                الفئات <span class="gradient-text-blue">والخدمات</span>
            </h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 fade-in">
                <?php foreach ($categories as $category): ?>
                <div class="service-card p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-<?php echo $category['color']; ?>-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas <?php 
                                echo $category['category_key'] == 'hosting' ? 'fa-server' : 
                                    ($category['category_key'] == 'security' ? 'fa-shield-alt' : 
                                    ($category['category_key'] == 'storage' ? 'fa-cloud' : 'fa-plus-circle')); 
                            ?> text-<?php echo $category['color']; ?>-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg"><?php echo $category['name']; ?></h3>
                            <p class="text-sm text-gray-400"><?php echo $category['services_count']; ?> خدمة</p>
                        </div>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <?php 
                        $percentage = $stats['total_services'] > 0 
                            ? round(($category['services_count'] / $stats['total_services']) * 100) 
                            : 0;
                        ?>
                        <div class="bg-<?php echo $category['color']; ?>-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2"><?php echo $percentage; ?>% من إجمالي الخدمات</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- أحدث الخدمات -->
    <section class="pb-20">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold mb-8 text-center fade-in">
                أحدث <span class="gradient-text-blue">الخدمات</span>
            </h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in">
                <?php foreach ($recent_services as $service): 
                    $features = json_decode($service['features'], true) ?: [];
                ?>
                <div class="service-card p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-<?php echo $service['category_color']; ?>-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas <?php echo $service['icon']; ?> text-<?php echo $service['category_color']; ?>-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold"><?php echo $service['name']; ?></h3>
                            <span class="badge badge-<?php echo $service['category_color']; ?>"><?php echo $service['category_name']; ?></span>
                        </div>
                    </div>
                    
                    <p class="text-gray-400 text-sm mb-4">
                        <?php echo substr($service['description'], 0, 80); ?>...
                    </p>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-<?php echo $service['category_color']; ?>-500 font-bold"><?php echo $service['price']; ?></span>
                        <?php if ($service['popular']): ?>
                        <span class="badge badge-<?php echo $service['category_color']; ?>">⭐ مميز</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- أحدث الطلبات -->
    <section id="requests" class="pb-20">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold mb-8 text-center fade-in">
                أحدث <span class="gradient-text-blue">طلبات العملاء</span>
            </h2>
            
            <div class="table-container fade-in">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>البريد الإلكتروني</th>
                            <th>نوع الخدمة</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-8">
                                لا توجد طلبات حتى الآن
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $index => $request): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td class="font-bold"><?php echo $request['full_name']; ?></td>
                                <td><?php echo $request['email']; ?></td>
                                <td><?php echo $request['service_type']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $status_colors = [
                                        'new' => 'badge-blue',
                                        'reviewing' => 'badge-purple',
                                        'accepted' => 'badge-green',
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
                                    $status = $request['status'] ?? 'new';
                                    ?>
                                    <span class="badge <?php echo $status_colors[$status] ?? 'badge-blue'; ?>">
                                        <?php echo $status_texts[$status] ?? $status; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- روابط سريعة -->
    <section class="pb-20">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-6 fade-in">
                <a href="index.html" class="service-card p-8 text-center hover:scale-105 transition-all">
                    <div class="w-20 h-20 mx-auto mb-4 bg-primary-blue/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-home text-3xl text-primary-blue"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 gradient-text-blue">الصفحة الرئيسية</h3>
                    <p class="text-gray-400 text-sm">العودة للصفحة الرئيسية للموقع</p>
                </a>
                
                <a href="login.html" class="service-card p-8 text-center hover:scale-105 transition-all">
                    <div class="w-20 h-20 mx-auto mb-4 bg-primary-green/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-sign-in-alt text-3xl text-primary-green"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 gradient-text-green">تسجيل الدخول</h3>
                    <p class="text-gray-400 text-sm">الدخول كلوحة التحكم الكاملة</p>
                </a>
                
                <a href="client-register.html" class="service-card p-8 text-center hover:scale-105 transition-all">
                    <div class="w-20 h-20 mx-auto mb-4 bg-primary-purple/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-user-plus text-3xl text-primary-purple"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 gradient-text-purple">إنشاء حساب</h3>
                    <p class="text-gray-400 text-sm">تسجيل حساب جديد في النظام</p>
                </a>
            </div>
        </div>
    </section>

    <!-- الفوتر -->
    <footer class="pt-12 pb-6 border-t border-gray-800">
        <div class="container mx-auto px-6">
            <div class="text-center">
                <p class="text-gray-500">
                    © 2024 X Cyber Hosting System. جميع الحقوق محفوظة | 
                    <span class="text-primary-blue">لوحة التحكم - وصول مباشر</span>
                </p>
                <div class="mt-4 flex justify-center space-x-6 space-x-reverse text-sm text-gray-500">
                    <a href="index.html" class="hover:text-primary-blue">الصفحة الرئيسية</a>
                    <a href="login.html" class="hover:text-primary-blue">تسجيل الدخول</a>
                    <a href="client-register.html" class="hover:text-primary-blue">إنشاء حساب</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // تأثيرات الظهور عند التمرير
        function initScrollAnimations() {
            const fadeElements = document.querySelectorAll('.fade-in');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });
            
            fadeElements.forEach(element => {
                observer.observe(element);
            });
        }
        
        // تهيئة كل شيء عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            initScrollAnimations();
        });
    </script>
</body>
</html>