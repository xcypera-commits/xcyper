<?php
// index.php - الصفحة الرئيسية المربوطة بقاعدة البيانات
/*
// أولاً: تعريف ثابت الوصول
define('SECURITY_ACCESS', true);

// ثانياً: تحديد المسار الصحيح
$rootPath = dirname(__DIR__, 4); // C:\xampp\htdocs\Hosting-Security

require_once __DIR__ . '/../../../security-init.php';
require_once __DIR__ . '/../../../security-functions.php';
*/
require_once '../../security-init.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
// جلب البيانات من قاعدة البيانات
$services = getAllServices();
$categories = getAllCategories();
$stats = getStats();


// جلب جميع الخدمات من قاعدة البيانات
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, c.category_key, c.color 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY c.id, s.popular DESC, s.id
");
$all_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تنظيم الخدمات حسب الفئة
$services_by_category = [];
$category_keys = [];
foreach ($all_services as $service) {
    $cat_key = $service['category_key'];
    $services_by_category[$cat_key][] = $service;
    $category_keys[$cat_key] = [
        'name' => $service['category_name'],
        'color' => $service['color'],
        'count' => isset($category_keys[$cat_key]) ? $category_keys[$cat_key]['count'] + 1 : 1
    ];
}

// إحصائيات سريعة
$total_services = count($all_services);
$hosting_count = isset($services_by_category['hosting']) ? count($services_by_category['hosting']) : 0;
$security_count = isset($services_by_category['security']) ? count($services_by_category['security']) : 0;
$storage_count = isset($services_by_category['storage']) ? count($services_by_category['storage']) : 0;
$additional_count = isset($services_by_category['additional']) ? count($services_by_category['additional']) : 0;

// معالجة إرسال النموذج
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $company = $_POST['company'] ?? '';
    $service_type = $_POST['service_type'] ?? '';
    $details = $_POST['details'] ?? '';
    
    if ($full_name && $email && $phone && $service_type && $details) {
        try {
            $sql = "INSERT INTO client_requests (full_name, email, phone, company, service_type, details) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$full_name, $email, $phone, $company, $service_type, $details]);
            
            $message = 'تم إرسال طلبك بنجاح! سنتواصل معك قريباً.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'حدث خطأ في إرسال الطلب';
            $message_type = 'error';
        }
    } else {
        $message = 'يرجى ملء جميع الحقول المطلوبة';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X Cyber - خدمات الاستضافة والحماية للعملاء</title>
    <!-- منع التخزين المؤقت للصفحة -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/assets/js/request.js"></script>
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
            transform: translateY(-10px);
            border-color: var(--primary-blue);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 60px rgba(0, 102, 204, 0.2);
        }
        
        /* أزرار التفاعل */
        .primary-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .primary-btn::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-purple), var(--primary-blue));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .primary-btn:hover::before {
            opacity: 1;
        }
        
        .primary-btn span {
            position: relative;
            z-index: 1;
        }
        
        .primary-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 102, 204, 0.3);
        }
        
        /* الأقسام */
        .section-padding {
            padding: 80px 0;
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
        
        /* علامات التبويب */
        .tab-button {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.1);
            padding: 12px 24px;
            border-radius: 50px;
            transition: all 0.3s;
            cursor: pointer;
            color: white;
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border-color: transparent;
        }
        
        /* خطوات العمل */
        .work-step {
            position: relative;
            padding-right: 60px;
        }
        
        .work-step::before {
            content: '';
            position: absolute;
            right: 20px;
            top: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-blue), transparent);
        }
        
        .step-number {
            position: absolute;
            right: 0;
            top: 0;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* خلفيات الأقسام */
        .bg-gradient-dark {
            background: linear-gradient(135deg, rgba(10, 15, 25, 0.9), rgba(20, 25, 40, 0.95));
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
        
        /* إضافة جديدة: فورمة طلب الخدمة تظهر من الأعلى */
        #request {
            scroll-margin-top: 100px;
        }
        
        .alert-success {
            background: rgba(0, 204, 102, 0.1);
            border: 1px solid rgba(0, 204, 102, 0.3);
            color: #66ff99;
        }
        
        .alert-error {
            background: rgba(255, 51, 51, 0.1);
            border: 1px solid rgba(255, 51, 51, 0.3);
            color: #ff6666;
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
                        <i class="fas fa-server text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold gradient-text-blue">X Cyber Hosting</h1>
                        <p class="text-xs text-gray-400">للعـملاء</p>
                    </div>
                </div>
                
                <!-- القائمة -->
                <div class="hidden md:flex items-center space-x-8 space-x-reverse">
                    <a href="#hero" class="text-gray-300 hover:text-white transition-all duration-300"> الرئيسية</a>
                    <a href="#system" class="text-gray-300 hover:text-white transition-all duration-300"> النظام</a>
                    <a href="#services" class="text-gray-300 hover:text-white transition-all duration-300"> الخدمات</a>
                    <a href="#workflow" class="text-gray-300 hover:text-white transition-all duration-300"> سير العمل</a>
                    <a href="#request" class="text-gray-300 hover:text-white transition-all duration-300"> طلب خدمة</a>
                </div>
                
                <!-- أزرار -->
                <a href="login.html" class="primary-btn">
                    <span>تسجيل دخول</span>
                    <i class="fas fa-user mr-2"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- القسم الرئيسي -->
    <section id="hero" class="pt-32 pb-20">
        <div class="container mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- النص الرئيسي -->
                <div class="fade-in">
                    <div class="inline-flex items-center space-x-3 space-x-reverse mb-6">
                        <span class="badge badge-blue">جديد</span>
                        <span class="text-sm text-gray-400">أكثر من <?php echo $total_services; ?> خدمة متخصصة</span>
                    </div>
                    
                    <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                        نظام <span class="gradient-text-blue">الاستضافة</span><br>
                        و <span class="gradient-text-red">الحماية</span> المتكامل
                    </h1>
                    
                    <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                        بنية تحتية رقمية محكمة تضمن توافر وأمان واستمرارية خدماتك 
                        مع فريق دعم فني متخصص يعمل على مدار الساعة
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button onclick="scrollToServices()" class="primary-btn">
                            <span>استعرض الخدمات</span>
                            <i class="fas fa-list mr-2"></i>
                        </button>
                        
                        <button onclick="showDemoModal()" class="px-8 py-4 border-2 border-primary-blue/30 rounded-xl hover:border-primary-blue transition-all duration-300">
                            <span>شاهد العرض التوضيحي</span>
                            <i class="fas fa-play ml-2"></i>
                        </button>
                    </div>
                    
                    <!-- المميزات السريعة -->
                    <div class="grid grid-cols-3 gap-4 mt-12">
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text-blue mb-2">99.99%</div>
                            <div class="text-sm text-gray-400">نسبة التوافر</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text-red mb-2">24/7</div>
                            <div class="text-sm text-gray-400">مراقبة أمنية</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text-green mb-2"><?php echo $total_services; ?>+</div>
                            <div class="text-sm text-gray-400">خدمة متخصصة</div>
                        </div>
                    </div>
                </div>
                
                <!-- الصورة المتحركة -->
                <div class="relative fade-in" style="transition-delay: 0.2s">
                    <div class="floating">
                        <div class="w-full h-96 bg-gradient-to-br from-primary-blue/20 to-primary-purple/20 rounded-3xl border border-primary-blue/30 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-primary-blue to-primary-purple rounded-full flex items-center justify-center">
                                    <i class="fas fa-shield-alt text-4xl"></i>
                                </div>
                                <p class="text-primary-blue">نظام الاستضافة والحماية</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- تعريف النظام -->
    <section id="system" class="section-padding bg-gradient-dark">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    نظام <span class="gradient-text-blue">الاستضافة والحماية</span><br>
                    <span class="text-2xl text-gray-300">نظرة شاملة</span>
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    نظام متكامل يجمع بين قوة الاستضافة وأمان الحماية في منصة واحدة
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <!-- تعريف النظام -->
                <div class="service-card p-8 fade-in">
                    <div class="w-16 h-16 mb-6 bg-primary-blue/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-info-circle text-2xl text-primary-blue"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 gradient-text-blue">تعريف النظام</h3>
                    <p class="text-gray-300 mb-6">
                        نظام الاستضافة والحماية والتخزين السحابي هو منصة بنية تحتية رقمية متكاملة 
                        تقدم خدمات استضافة المواقع، التخزين السحابي الآمن، وفحوصات الحماية المتقدمة.
                    </p>
                    <ul class="space-y-2">
                        <li class="flex items-center text-gray-400">
                            <i class="fas fa-check text-green-400 ml-2"></i>
                            مركز بيانات افتراضي محكم
                        </li>
                        <li class="flex items-center text-gray-400">
                            <i class="fas fa-check text-green-400 ml-2"></i>
                            ضمان توافر 99.99%
                        </li>
                        <li class="flex items-center text-gray-400">
                            <i class="fas fa-check text-green-400 ml-2"></i>
                            تطبيق أعلى معايير الأمان
                        </li>
                    </ul>
                </div>
                
                <!-- الهيكل التنظيمي -->
                <div class="service-card p-8 fade-in" style="transition-delay: 0.1s">
                    <div class="w-16 h-16 mb-6 bg-primary-red/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-sitemap text-2xl text-primary-red"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 gradient-text-red">الهيكل التنظيمي</h3>
                    <p class="text-gray-300 mb-6">
                        يعمل النظام عبر 4 أقسام متخصصة تحت إشراف رئاسة القسم:
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-alt text-green-400"></i>
                            </div>
                            <span class="text-sm">قسم التوثيق والتحليل</span>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-8 h-8 bg-red-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shield-alt text-red-400"></i>
                            </div>
                            <span class="text-sm">قسم الفحص الأمني</span>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-blue-400"></i>
                            </div>
                            <span class="text-sm">قسم التخزين والنشر</span>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-eye text-purple-400"></i>
                            </div>
                            <span class="text-sm">قسم الرقابة والحماية</span>
                        </div>
                    </div>
                </div>
                
                <!-- المميزات الرئيسية -->
                <div class="service-card p-8 fade-in" style="transition-delay: 0.2s">
                    <div class="w-16 h-16 mb-6 bg-primary-green/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-star text-2xl text-primary-green"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 gradient-text-green">المميزات الرئيسية</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-gray-900/50 rounded-xl">
                            <h4 class="font-bold mb-2">توافر عالي</h4>
                            <p class="text-sm text-gray-400">ضمان توافر الخدمة بنسبة 99.99% مع SLA مضمون</p>
                        </div>
                        <div class="p-4 bg-gray-900/50 rounded-xl">
                            <h4 class="font-bold mb-2">حماية متقدمة</h4>
                            <p class="text-sm text-gray-400">طبقات حماية متعددة ضد جميع أنواع الهجمات</p>
                        </div>
                        <div class="p-4 bg-gray-900/50 rounded-xl">
                            <h4 class="font-bold mb-2">دعم فني 24/7</h4>
                            <p class="text-sm text-gray-400">فريق دعم متخصص يعمل على مدار الساعة</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- الخدمات (من قاعدة البيانات) -->
    <section id="services" class="section-padding">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    خدماتنا <span class="gradient-text-blue">المتخصصة</span>
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    أكثر من <?php echo $total_services; ?> خدمة متخصصة تغطي جميع احتياجاتك الرقمية
                </p>
            </div>
            
            <!-- علامات التبويب -->
            <div class="flex flex-wrap gap-4 justify-center mb-12 fade-in">
                <button class="tab-button active" onclick="changeServiceTab('hosting')">
                    <i class="fas fa-server mr-2"></i>
                    خدمات الاستضافة (<?php echo $hosting_count; ?>)
                </button>
                <button class="tab-button" onclick="changeServiceTab('security')">
                    <i class="fas fa-shield-alt mr-2"></i>
                    خدمات الحماية (<?php echo $security_count; ?>)
                </button>
                <button class="tab-button" onclick="changeServiceTab('storage')">
                    <i class="fas fa-cloud mr-2"></i>
                    التخزين السحابي (<?php echo $storage_count; ?>)
                </button>
                <button class="tab-button" onclick="changeServiceTab('additional')">
                    <i class="fas fa-plus-circle mr-2"></i>
                    خدمات إضافية (<?php echo $additional_count; ?>)
                </button>
            </div>
            
            <!-- خدمات الاستضافة -->
            <div id="hosting-tab" class="service-tab fade-in">
                <div class="text-center mb-8">
                    <h3 class="text-3xl font-bold mb-4 gradient-text-blue">خدمات الاستضافة (<?php echo $hosting_count; ?> خدمة)</h3>
                    <p class="text-gray-300">حلول استضافة متكاملة لجميع أنواع المواقع والتطبيقات</p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (isset($services_by_category['hosting'])): ?>
                        <?php foreach ($services_by_category['hosting'] as $service): 
                            $features = json_decode($service['features'], true) ?: [];
                        ?>
                        <div class="service-card p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary-blue/20 rounded-xl flex items-center justify-center mr-4">
                                    <i class="fas <?php echo $service['icon']; ?> text-primary-blue"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold"><?php echo htmlspecialchars($service['name']); ?></h4>
                                    <?php if ($service['popular']): ?>
                                    <span class="badge badge-blue">الأكثر شيوعاً</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-gray-400 text-sm mb-6">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-blue font-bold"><?php echo htmlspecialchars($service['price']); ?></span>
                                <button class="primary-btn text-sm px-4 py-2" onclick='showServiceDetails(<?php echo json_encode($service); ?>)'>
                                    <span>تفاصيل الخدمة</span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-12">
                    <button class="primary-btn" onclick="showAllServices('hosting')">
                        <span>عرض جميع خدمات الاستضافة (<?php echo $hosting_count; ?> خدمة)</span>
                        <i class="fas fa-arrow-left mr-2"></i>
                    </button>
                </div>
            </div>
            
            <!-- خدمات الحماية -->
            <div id="security-tab" class="service-tab hidden fade-in">
                <div class="text-center mb-8">
                    <h3 class="text-3xl font-bold mb-4 gradient-text-red">خدمات الحماية (<?php echo $security_count; ?> خدمة)</h3>
                    <p class="text-gray-300">حلول أمنية متقدمة لحماية مواقعك وتطبيقاتك من التهديدات</p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (isset($services_by_category['security'])): ?>
                        <?php foreach ($services_by_category['security'] as $service): 
                            $features = json_decode($service['features'], true) ?: [];
                        ?>
                        <div class="service-card p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary-red/20 rounded-xl flex items-center justify-center mr-4">
                                    <i class="fas <?php echo $service['icon']; ?> text-primary-red"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold"><?php echo htmlspecialchars($service['name']); ?></h4>
                                    <?php if ($service['popular']): ?>
                                    <span class="badge badge-red">الأكثر طلباً</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-gray-400 text-sm mb-6">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-red font-bold"><?php echo htmlspecialchars($service['price']); ?></span>
                                <button class="primary-btn text-sm px-4 py-2" onclick='showServiceDetails(<?php echo json_encode($service); ?>)'>
                                    <span>تفاصيل الخدمة</span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-12">
                    <button class="primary-btn" onclick="showAllServices('security')">
                        <span>عرض جميع خدمات الحماية (<?php echo $security_count; ?> خدمة)</span>
                        <i class="fas fa-arrow-left mr-2"></i>
                    </button>
                </div>
            </div>
            
            <!-- التخزين السحابي -->
            <div id="storage-tab" class="service-tab hidden fade-in">
                <div class="text-center mb-8">
                    <h3 class="text-3xl font-bold mb-4 gradient-text-green">خدمات التخزين السحابي (<?php echo $storage_count; ?> خدمة)</h3>
                    <p class="text-gray-300">حلول تخزين آمنة وموثوقة لجميع أنواع البيانات والملفات</p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (isset($services_by_category['storage'])): ?>
                        <?php foreach ($services_by_category['storage'] as $service): 
                            $features = json_decode($service['features'], true) ?: [];
                        ?>
                        <div class="service-card p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary-green/20 rounded-xl flex items-center justify-center mr-4">
                                    <i class="fas <?php echo $service['icon']; ?> text-primary-green"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold"><?php echo htmlspecialchars($service['name']); ?></h4>
                                    <?php if ($service['popular']): ?>
                                    <span class="badge badge-green">الأكثر طلباً</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-gray-400 text-sm mb-6">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-green font-bold"><?php echo htmlspecialchars($service['price']); ?></span>
                                <button class="primary-btn text-sm px-4 py-2" onclick='showServiceDetails(<?php echo json_encode($service); ?>)'>
                                    <span>تفاصيل الخدمة</span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-12">
                    <button class="primary-btn" onclick="showAllServices('storage')">
                        <span>عرض جميع خدمات التخزين (<?php echo $storage_count; ?> خدمة)</span>
                        <i class="fas fa-arrow-left mr-2"></i>
                    </button>
                </div>
            </div>
            
            <!-- خدمات إضافية -->
            <div id="additional-tab" class="service-tab hidden fade-in">
                <div class="text-center mb-8">
                    <h3 class="text-3xl font-bold mb-4 gradient-text-purple">خدمات إضافية (<?php echo $additional_count; ?> خدمة)</h3>
                    <p class="text-gray-300">خدمات داعمة ومكملة لتحسين أداء وأمان أنظمتك</p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (isset($services_by_category['additional'])): ?>
                        <?php foreach ($services_by_category['additional'] as $service): 
                            $features = json_decode($service['features'], true) ?: [];
                        ?>
                        <div class="service-card p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary-purple/20 rounded-xl flex items-center justify-center mr-4">
                                    <i class="fas <?php echo $service['icon']; ?> text-primary-purple"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold"><?php echo htmlspecialchars($service['name']); ?></h4>
                                    <?php if ($service['popular']): ?>
                                    <span class="badge badge-purple">الأكثر طلباً</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-gray-400 text-sm mb-6">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary-purple font-bold"><?php echo htmlspecialchars($service['price']); ?></span>
                                <button class="primary-btn text-sm px-4 py-2" onclick='showServiceDetails(<?php echo json_encode($service); ?>)'>
                                    <span>تفاصيل الخدمة</span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-12">
                    <button class="primary-btn" onclick="showAllServices('additional')">
                        <span>عرض جميع الخدمات الإضافية (<?php echo $additional_count; ?> خدمة)</span>
                        <i class="fas fa-arrow-left mr-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- خط التقسيم -->
    <div class="divider container mx-auto px-6"></div>

    <!-- سير العمل -->
    <section id="workflow" class="section-padding">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    كيف <span class="gradient-text-blue">نعمل</span>؟
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    عملية محكمة من طلب الخدمة حتى التسليم تضمن الجودة والكفاءة
                </p>
            </div>
            
            <div class="service-card p-8 fade-in">
                <div class="work-step mb-12">
                    <div class="step-number">1</div>
                    <div class="ml-12">
                        <h3 class="text-2xl font-bold mb-4 gradient-text-blue">تقديم الطلب</h3>
                        <p class="text-gray-300 mb-6">
                            تقدم طلب الخدمة عبر موقعنا → يذهب الطلب إلى إدارة المشاريع → ثم إلى رئاسة قسم الاستضافة
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="badge badge-blue">مدة: 1-2 ساعة</span>
                            <span class="badge badge-green">المسؤول: إدارة المشاريع</span>
                        </div>
                    </div>
                </div>
                
                <div class="work-step mb-12">
                    <div class="step-number">2</div>
                    <div class="ml-12">
                        <h3 class="text-2xl font-bold mb-4 gradient-text-green">التحليل والتوثيق</h3>
                        <p class="text-gray-300 mb-6">
                            قسم التوثيق يجري دراسة أولية → يعد تقرير التحليل → يرسل إلى إدارة المشاريع لإعداد العقد
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="badge badge-blue">مدة: 1-2 يوم عمل</span>
                            <span class="badge badge-green">المسؤول: قسم التوثيق</span>
                        </div>
                    </div>
                </div>
                
                <div class="work-step mb-12">
                    <div class="step-number">3</div>
                    <div class="ml-12">
                        <h3 class="text-2xl font-bold mb-4 gradient-text-red">الفحص الأمني</h3>
                        <p class="text-gray-300 mb-6">
                            بعد موافقتك وتسديد الرسوم → قسم الفحص يجري فحصاً أمنياً شاملاً → معالجة الثغرات إن وجدت
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="badge badge-blue">مدة: 2-3 أيام عمل</span>
                            <span class="badge badge-red">المسؤول: قسم الفحص الأمني</span>
                        </div>
                    </div>
                </div>
                
                <div class="work-step">
                    <div class="step-number">4</div>
                    <div class="ml-12">
                        <h3 class="text-2xl font-bold mb-4 gradient-text-purple">النشر والمراقبة</h3>
                        <p class="text-gray-300 mb-6">
                            قسم التخزين ينشر الموقع على الخوادم → قسم الرقابة يراقب الموقع 24/7 → تقارير دورية لك
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="badge badge-blue">مدة: 1 يوم عمل + مستمر</span>
                            <span class="badge badge-purple">المسؤول: قسم الرقابة</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- مخطط سير العمل -->
            <div class="mt-16 fade-in" style="transition-delay: 0.2s">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold mb-4 gradient-text-blue">مخطط سير العمل البصري</h3>
                </div>
                
                <div class="flex flex-wrap justify-center items-center gap-8">
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-primary-blue/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-file-upload text-2xl text-primary-blue"></i>
                        </div>
                        <p class="font-bold">طلب العميل</p>
                    </div>
                    
                    <div class="text-gray-500">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-green-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-search text-2xl text-green-500"></i>
                        </div>
                        <p class="font-bold">التحليل الأولي</p>
                    </div>
                    
                    <div class="text-gray-500">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-yellow-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-file-contract text-2xl text-yellow-500"></i>
                        </div>
                        <p class="font-bold">العقد والموافقة</p>
                    </div>
                    
                    <div class="text-gray-500">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-red-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-2xl text-red-500"></i>
                        </div>
                        <p class="font-bold">الفحص الأمني</p>
                    </div>
                    
                    <div class="text-gray-500">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-purple-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-cloud-upload-alt text-2xl text-purple-500"></i>
                        </div>
                        <p class="font-bold">النشر والتسليم</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- خط التقسيم -->
    <div class="divider container mx-auto px-6"></div>

    <!-- نموذج طلب الخدمة -->
    <section id="request" class="section-padding">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    اطلب <span class="gradient-text-blue">خدمتك</span> الآن
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    املأ النموذج وسنتصل بك خلال 24 ساعة لعرض التفاصيل والبدء في تنفيذ طلبك
                </p>
            </div>
            
            <div class="max-w-3xl mx-auto fade-in" style="transition-delay: 0.2s">
                <div class="service-card p-8">
                    
                    <?php if ($message): ?>
                    <div class="p-4 rounded-xl mb-6 <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo $message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-2 text-gray-300">الاسم الكامل *</label>
                                <input type="text" name="full_name" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 focus:outline-none focus:border-primary-blue">
                            </div>
                            <div>
                                <label class="block mb-2 text-gray-300">البريد الإلكتروني *</label>
                                <input type="email" name="email" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 focus:outline-none focus:border-primary-blue">
                            </div>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-2 text-gray-300">رقم الهاتف *</label>
                                <input type="tel" name="phone" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 focus:outline-none focus:border-primary-blue">
                            </div>
                            <div>
                                <label class="block mb-2 text-gray-300">اسم المؤسسة</label>
                                <input type="text" name="company" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 focus:outline-none focus:border-primary-blue">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-gray-300">نوع الخدمة المطلوبة *</label>
                            <select name="service_type" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 focus:outline-none focus:border-primary-blue">
                                <option value="">اختر نوع الخدمة</option>
                                <optgroup label="خدمات الاستضافة">
                                    <?php if (isset($services_by_category['hosting'])): ?>
                                        <?php foreach ($services_by_category['hosting'] as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['name']); ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </optgroup>
                                <optgroup label="خدمات الحماية">
                                    <?php if (isset($services_by_category['security'])): ?>
                                        <?php foreach ($services_by_category['security'] as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['name']); ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </optgroup>
                                <optgroup label="التخزين السحابي">
                                    <?php if (isset($services_by_category['storage'])): ?>
                                        <?php foreach ($services_by_category['storage'] as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['name']); ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-gray-300">تفاصيل الطلب *</label>
                            <textarea name="details" required rows="5" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 focus:outline-none focus:border-primary-blue" placeholder="صف لنا متطلباتك وماذا تحتاج بالضبط..."></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="terms" required class="w-5 h-5 ml-3">
                            <label for="terms" class="text-gray-300 text-sm">
                                أوافق على <a href="#" class="text-primary-blue hover:underline">الشروط والأحكام</a> و 
                                <a href="#" class="text-primary-blue hover:underline">سياسة الخصوصية</a>
                            </label>
                        </div>
                        
                        <button type="submit" name="submit_request" class="primary-btn w-full text-lg py-4">
                            <span>إرسال طلب الخدمة</span>
                            <i class="fas fa-paper-plane mr-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- الفوتر -->
    <footer class="pt-12 pb-6 border-t border-gray-800">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 space-x-reverse mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-blue to-primary-purple rounded-xl"></div>
                        <div>
                            <h3 class="text-xl font-bold gradient-text-blue">X Cyber Hosting</h3>
                            <p class="text-sm text-gray-400">للعملاء</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm">
                        نظام الاستضافة والحماية المتكامل<br>
                        مع أكثر من <?php echo $total_services; ?> خدمة متخصصة
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 gradient-text-blue">روابط سريعة</h4>
                    <ul class="space-y-3">
                        <li><a href="#hero" class="text-gray-400 hover:text-white">🏠 الرئيسية</a></li>
                        <li><a href="#system" class="text-gray-400 hover:text-white">📋 النظام</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white">⚡ الخدمات</a></li>
                        <li><a href="#workflow" class="text-gray-400 hover:text-white">🔄 سير العمل</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 gradient-text-blue">خدمات شائعة</h4>
                    <ul class="space-y-3">
                        <?php 
                        $popular_services = array_filter($all_services, function($s) { return $s['popular'] == 1; });
                        $popular_services = array_slice($popular_services, 0, 4);
                        foreach ($popular_services as $service): 
                        ?>
                        <li><a href="#" onclick='showServiceDetails(<?php echo json_encode($service); ?>)' class="text-gray-400 hover:text-white">🌐 <?php echo htmlspecialchars($service['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 gradient-text-blue">التواصل</h4>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <i class="fas fa-phone text-primary-blue"></i>
                            <span class="text-gray-400">+966 12 345 6789</span>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <i class="fas fa-envelope text-primary-blue"></i>
                            <span class="text-gray-400">hosting@xcyber.com</span>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <i class="fas fa-clock text-primary-blue"></i>
                            <span class="text-gray-400">الأحد - الخميس: 8 ص - 5 م</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500">
                    © 2024 X Cyber Hosting System. جميع الحقوق محفوظة | 
                    <span class="text-primary-blue">توافر 99.99% مضمون</span>
                </p>
                <div class="mt-4 flex justify-center space-x-6 space-x-reverse text-sm text-gray-500">
                    <a href="#" class="hover:text-primary-blue">الشروط والأحكام</a>
                    <a href="#" class="hover:text-primary-blue">سياسة الخصوصية</a>
                    <a href="#" class="hover:text-primary-blue">سياسة الاستخدام</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- نافذة عرض تفاصيل الخدمة -->
    <div id="serviceModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="service-card max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-8" id="modalContent">
                <!-- سيتم ملء المحتوى ديناميكياً من JavaScript -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // التمرير السلس
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                const yOffset = -100;
                const y = element.getBoundingClientRect().top + window.pageYOffset + yOffset;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }
        
        function scrollToRequest() {
            scrollToSection('request');
        }
        
        function scrollToServices() {
            scrollToSection('services');
        }

        // تحديث زر تسجيل الدخول
        document.addEventListener('DOMContentLoaded', function() {
            updateLoginButton();
            initScrollAnimations();
        });

        function updateLoginButton() {
            const loginBtn = document.querySelector('.primary-btn');
            const isLoggedIn = sessionStorage.getItem('visitor_logged_in') === 'true';
            const visitorName = sessionStorage.getItem('visitor_name');
            
            if (loginBtn) {
                if (isLoggedIn && visitorName) {
                    loginBtn.innerHTML = `
                        <span>${visitorName}</span>
                        <i class="fas fa-user-circle mr-2"></i>
                    `;
                } else {
                    loginBtn.innerHTML = `
                        <span>تسجيل دخول</span>
                        <i class="fas fa-user mr-2"></i>
                    `;
                }
            }
        }
        
        // تغيير علامات التبويب
        function changeServiceTab(tabName) {
            document.querySelectorAll('.service-tab').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById(`${tabName}-tab`).classList.remove('hidden');
            event.target.classList.add('active');
        }
        
        // عرض تفاصيل الخدمة
        function showServiceDetails(service) {
            const modal = document.getElementById('serviceModal');
            const modalContent = document.getElementById('modalContent');
            
            let features = [];
            if (service.features) {
                try {
                    features = JSON.parse(service.features);
                } catch(e) {
                    features = [];
                }
            }
            
            const categoryColor = service.color || 'blue';
            
            modalContent.innerHTML = `
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="text-3xl font-bold mb-2 gradient-text-${categoryColor}">${service.name}</h3>
                        <p class="text-gray-400">${service.description}</p>
                    </div>
                    <button onclick="closeServiceModal()" class="text-gray-400 hover:text-white text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-xl font-bold mb-4 gradient-text-blue">المميزات</h4>
                        <div class="space-y-3">
                            ${features.map(feature => `
                                <div class="flex items-center p-3 bg-gray-900/50 rounded-lg">
                                    <i class="fas fa-check text-green-400 ml-3"></i>
                                    <span>${feature}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-xl font-bold mb-4 gradient-text-blue">تفاصيل الخدمة</h4>
                        <div class="space-y-6">
                            <div class="p-4 bg-gray-900/50 rounded-xl">
                                <p class="text-gray-400 text-sm">السعر</p>
                                <p class="text-2xl font-bold gradient-text-${categoryColor}">${service.price}</p>
                            </div>
                            
                            ${service.setup_time ? `
                            <div class="p-4 bg-gray-900/50 rounded-xl">
                                <p class="text-gray-400 text-sm">مدة التنفيذ</p>
                                <p class="text-xl font-bold">${service.setup_time}</p>
                            </div>
                            ` : ''}
                            
                            ${service.sla ? `
                            <div class="p-4 bg-gray-900/50 rounded-xl">
                                <p class="text-gray-400 text-sm">ضمان التوافر</p>
                                <p class="text-xl font-bold gradient-text-green">${service.sla}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-800 flex justify-end">
                    <button onclick="closeServiceModal()" class="px-6 py-3 bg-gray-700 rounded-lg hover:bg-gray-600 ml-4">
                        إغلاق
                    </button>
                    <button onclick="requestService('${service.name}')" class="px-6 py-3 primary-btn">
                        <span>طلب هذه الخدمة</span>
                        <i class="fas fa-shopping-cart mr-2"></i>
                    </button>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // إغلاق نافذة الخدمة
        function closeServiceModal() {
            const modal = document.getElementById('serviceModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // طلب خدمة معينة
        function requestService(serviceName) {
            closeServiceModal();
            scrollToSection('request');
            
            const serviceSelect = document.querySelector('select[name="service_type"]');
            if (serviceSelect) {
                for (let option of serviceSelect.options) {
                    if (option.text.includes(serviceName)) {
                        option.selected = true;
                        break;
                    }
                }
            }
        }
        
        // عرض جميع خدمات فئة معينة
        function showAllServices(category) {
            const categoryNames = {
                'hosting': 'الاستضافة',
                'security': 'الحماية',
                'storage': 'التخزين السحابي',
                'additional': 'الإضافية'
            };
            
            const services = <?php echo json_encode($services_by_category); ?>;
            const categoryServices = services[category] || [];
            
            let servicesHtml = '';
            categoryServices.forEach(service => {
                servicesHtml += `
                    <div class="p-4 bg-gray-900/50 rounded-xl">
                        <h4 class="font-bold mb-2">${service.name}</h4>
                        <p class="text-gray-400 text-sm mb-3">
                            ${service.description.substring(0, 100)}...
                        </p>
                        <button class="primary-btn text-sm px-3 py-1" onclick='showServiceDetails(${JSON.stringify(service)})'>
                            <span>عرض التفاصيل</span>
                        </button>
                    </div>
                `;
            });
            
            const modal = document.getElementById('serviceModal');
            const modalContent = document.getElementById('modalContent');
            
            modalContent.innerHTML = `
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="text-3xl font-bold mb-2 gradient-text-${category}">جميع خدمات ${categoryNames[category]}</h3>
                        <p class="text-gray-400">قائمة كاملة بجميع الخدمات المتاحة في هذه الفئة</p>
                    </div>
                    <button onclick="closeServiceModal()" class="text-gray-400 hover:text-white text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    ${servicesHtml}
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-800 text-center">
                    <button onclick="closeServiceModal()" class="px-8 py-3 bg-gray-700 rounded-lg hover:bg-gray-600">
                        إغلاق القائمة
                    </button>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // عرض نافذة العرض التوضيحي
        function showDemoModal() {
            const modal = document.getElementById('serviceModal');
            const modalContent = document.getElementById('modalContent');
            
            modalContent.innerHTML = `
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="text-3xl font-bold mb-2 gradient-text-blue">العرض التوضيحي</h3>
                        <p class="text-gray-400">شاهد كيف يمكن لنظام الاستضافة والحماية خدمة مشروعك</p>
                    </div>
                    <button onclick="closeServiceModal()" class="text-gray-400 hover:text-white text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div class="p-6 bg-gray-900/50 rounded-xl">
                        <h4 class="font-bold mb-4 gradient-text-blue">ماذا يشمل العرض؟</h4>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 ml-3"></i>
                                <span>جولة في لوحة التحكم</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 ml-3"></i>
                                <span>عرض خدمات الحماية</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 ml-3"></i>
                                <span>تجربة نظام المراقبة</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 ml-3"></i>
                                <span>عرض التقارير والتحليلات</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="p-6 bg-gray-900/50 rounded-xl">
                        <h4 class="font-bold mb-4 gradient-text-blue">تفاصيل الجلسة</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-gray-400 text-sm">المدة</p>
                                <p class="font-bold">45 دقيقة</p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">النوع</p>
                                <p class="font-bold">عرض مباشر عبر الإنترنت</p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">التكلفة</p>
                                <p class="font-bold gradient-text-green">مجاني تماماً</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <button onclick="closeServiceModal()" class="px-8 py-3 bg-gray-700 rounded-lg hover:bg-gray-600 ml-4">
                        إغلاق
                    </button>
                    <button onclick="scheduleDemo()" class="px-8 py-3 primary-btn">
                        <span>حجز موعد للعرض</span>
                        <i class="fas fa-calendar-alt mr-2"></i>
                    </button>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // حجز موعد للعرض التوضيحي
        function scheduleDemo() {
            closeServiceModal();
            alert('سيتم توجيهك إلى صفحة حجز المواعيد. يمكنك اختيار الوقت المناسب لك وسيتصل بك ممثلنا لتأكيد الموعد.');
        }
        
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
        
        // إغلاق النافذة عند النقر خارجها
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeServiceModal();
            }
        });
        // تحديث زر تسجيل الدخول بعد تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateLoginButton();
    
    // عرض رسالة ترحيب إذا كان المستخدم مسجل دخول جديد
    const isLoggedIn = sessionStorage.getItem('visitor_logged_in') === 'true';
    const visitorName = sessionStorage.getItem('visitor_name');
    
    if (isLoggedIn && visitorName) {
        // التحقق إذا كانت هذه أول مرة يدخل فيها بعد تسجيل الدخول
        if (!sessionStorage.getItem('welcome_shown')) {
            showWelcomeMessage(`مرحباً بك ${visitorName}!`);
            sessionStorage.setItem('welcome_shown', 'true');
        }
    }
});

// تحديث زر تسجيل الدخول
function updateLoginButton() {
    const loginBtn = document.querySelector('.primary-btn');
    const isLoggedIn = sessionStorage.getItem('visitor_logged_in') === 'true';
    const visitorName = sessionStorage.getItem('visitor_name');
    
    if (loginBtn) {
        if (isLoggedIn && visitorName) {
            loginBtn.innerHTML = `
                <span>${visitorName}</span>
                <i class="fas fa-user-circle mr-2"></i>
            `;
            // إضافة قائمة منسدلة عند النقر (اختياري)
            loginBtn.onclick = function(e) {
                e.preventDefault();
                showUserMenu();
            };
        } else {
            loginBtn.innerHTML = `
                <span>تسجيل دخول</span>
                <i class="fas fa-user mr-2"></i>
            `;
            loginBtn.onclick = function() {
                window.location.href = 'login.html';
            };
        }
    }
}

// عرض رسالة ترحيب
function showWelcomeMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 left-4 bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 notification';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle ml-3 text-xl"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// عرض قائمة المستخدم (اختياري)
function showUserMenu() {
    // يمكنك إضافة قائمة منسدلة هنا
    const visitorName = sessionStorage.getItem('visitor_name');
    const visitorEmail = sessionStorage.getItem('visitor_email');
    
    if (confirm(`مرحباً ${visitorName}\nالبريد: ${visitorEmail}\n\nهل تريد تسجيل الخروج؟`)) {
        logout();
    }
}

// تسجيل الخروج
function logout() {
    sessionStorage.clear();
    localStorage.removeItem('visitor_email');
    localStorage.removeItem('visitor_username');
    window.location.href = 'index.html';
}

// التحقق من المستخدم الجديد وعرض رسالة ترحيب
document.addEventListener('DOMContentLoaded', function() {
    // التحقق من وجود مستخدم جديد
    const isNewRegistered = sessionStorage.getItem('new_registered') === 'true';
    const visitorName = sessionStorage.getItem('visitor_name');
    
    if (isNewRegistered && visitorName) {
        // إزالة علامة المستخدم الجديد
        sessionStorage.removeItem('new_registered');
        
        // عرض رسالة ترحيب خاصة
        showWelcomeMessage(`🎉 مرحباً بك ${visitorName}! تم إنشاء حسابك بنجاح`);
    }
    
    // تحديث زر تسجيل الدخول
    updateLoginButton();
});

// دالة لعرض رسالة الترحيب
function showWelcomeMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 left-4 bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 notification';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle ml-3 text-xl"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
    </script>
    <!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 مساعد الروبوت الذكي</title>
    <style>
        /* ===== ملف CSS مدمج ===== */
        :root {
            /* ألوان داكنة متطورة */
            --primary-color: #3a86ff;
            --secondary-color: #1e4b8c;
            --accent-color: #00b4d8;
            --bot-color: #3a86ff;
            --user-color: #00b894;
            --bg-color: #1a1b2e;
            --bg-secondary: #252a41;
            --text-color: #e9ecef;
            --text-muted: #adb5bd;
            --border-color: #343a52;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --hover-color: #2c3152;
            
            /* ألوان إضافية */
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #e17055;
            --info: #74b9ff;
        }

        /* زر الروبوت العائم - تصميم إيموجي */
        .chatbot-toggle {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(58, 134, 255, 0.3);
            z-index: 9999;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.2);
            font-size: 40px;
            animation: robotFloat 3s ease-in-out infinite;
        }

        @keyframes robotFloat {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-10px) rotate(5deg);
            }
        }

        .chatbot-toggle:hover {
            transform: scale(1.15) rotate(360deg);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 15px 40px rgba(58, 134, 255, 0.5);
        }

        .chatbot-icon {
            font-size: 40px;
            line-height: 1;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
        }

        .chatbot-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: bold;
            border: 3px solid var(--bg-color);
            box-shadow: 0 4px 12px rgba(225, 112, 85, 0.4);
            animation: notificationPulse 2s infinite;
        }

        @keyframes notificationPulse {
            0%, 100% {
                transform: scale(1);
                background: var(--danger);
            }
            50% {
                transform: scale(1.15);
                background: #ff6b6b;
            }
        }

        /* نافذة الدردشة - تصميم داكن */
        .chatbot-window {
            position: fixed;
            bottom: 120px;
            left: 30px;
            width: 380px;
            max-width: 90vw;
            height: 600px;
            max-height: 80vh;
            background: var(--bg-color);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            display: none;
            flex-direction: column;
            z-index: 9998;
            overflow: hidden;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }

        .chatbot-window.active {
            display: flex;
            animation: windowSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes windowSlideIn {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes windowSlideOut {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
                display: none;
            }
        }

        .chatbot-window.closing {
            animation: windowSlideOut 0.3s ease forwards;
        }

        /* هيدر الدردشة */
        .chatbot-header {
            background: linear-gradient(135deg, var(--bg-secondary), #1e1f33);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            border-bottom: 2px solid var(--primary-color);
        }

        .chatbot-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color), var(--success));
            transform: scaleX(0);
            animation: headerGlow 3s infinite;
        }

        @keyframes headerGlow {
            0%, 100% {
                transform: scaleX(0.3);
                opacity: 0.5;
            }
            50% {
                transform: scaleX(1);
                opacity: 1;
            }
        }

        .chatbot-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(58, 134, 255, 0.3);
        }

        .chatbot-title h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chatbot-title p {
            margin: 6px 0 0;
            font-size: 12px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
        }

        .chatbot-title p::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            display: inline-block;
            animation: onlinePulse 2s infinite;
        }

        @keyframes onlinePulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(0, 184, 148, 0.5);
            }
            50% {
                box-shadow: 0 0 0 5px rgba(0, 184, 148, 0);
            }
        }

        /* زر الإغلاق */
        .chatbot-close {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chatbot-close:hover {
            background: var(--danger);
            transform: rotate(90deg) scale(1.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* منطقة الرسائل */
        .chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: var(--bg-color);
            scroll-behavior: smooth;
        }

        /* شريط التمرير */
        .chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chatbot-messages::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 3px;
        }

        .chatbot-messages::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .chatbot-messages::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        /* الرسائل */
        .message {
            display: flex;
            margin-bottom: 20px;
            gap: 12px;
            animation: messageFadeIn 0.4s ease;
        }

        @keyframes messageFadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bot-message {
            justify-content: flex-start;
        }

        .user-message {
            justify-content: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bot-color), var(--secondary-color));
            color: white;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }

        .user-message .message-avatar {
            background: linear-gradient(135deg, var(--user-color), #00a187);
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.3);
        }

        .message-content {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }

        .bot-message .message-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-top-right-radius: 5px;
            color: var(--text-color);
        }

        .user-message .message-content {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom-left-radius: 5px;
        }

        .message-content p {
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
        }

        .message-time {
            display: block;
            font-size: 10px;
            opacity: 0.7;
            margin-top: 6px;
            text-align: left;
            font-family: 'Courier New', monospace;
        }

        .user-message .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        /* الاقتراحات السريعة */
        .chatbot-suggestions {
            padding: 16px 20px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-height: 120px;
            overflow-y: auto;
        }

        .suggestion-btn {
            padding: 10px 18px;
            background: rgba(58, 134, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .suggestion-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 134, 255, 0.3);
        }

        /* منطقة الإدخال */
        .chatbot-input-area {
            padding: 16px 20px;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--bg-color);
            border-radius: 30px;
            padding: 4px 16px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
        }

        .chatbot-input {
            flex: 1;
            padding: 12px 0;
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
            color: var(--text-color);
        }

        .chatbot-input::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        .input-action-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
            opacity: 0.8;
            transition: all 0.3s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .input-action-btn:hover {
            opacity: 1;
            color: var(--primary-color);
            background: rgba(58, 134, 255, 0.1);
        }

        .send-btn {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 16px rgba(58, 134, 255, 0.3);
        }

        .send-btn:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.5);
        }

        .send-btn svg {
            width: 24px;
            height: 24px;
            fill: white;
        }

        /* الفوتر */
        .chatbot-footer {
            padding: 12px 20px;
            background: var(--bg-color);
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-footer p {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #chatbot-time {
            color: var(--accent-color);
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        /* مؤشر الكتابة */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border-radius: 18px;
            border: 1px solid var(--border-color);
            width: fit-content;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            animation: typingBounce 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typingBounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-8px);
                opacity: 1;
            }
        }

        /* رسائل الخطأ */
        .error-message {
            background: rgba(225, 112, 85, 0.1) !important;
            border-color: var(--danger) !important;
            color: var(--danger) !important;
        }

        /* رسائل النجاح */
        .success-message {
            background: rgba(0, 184, 148, 0.1) !important;
            border-color: var(--success) !important;
            color: var(--success) !important;
        }

        /* التجاوب مع الشاشات الصغيرة */
        @media (max-width: 768px) {
            .chatbot-toggle {
                bottom: 20px;
                left: 20px;
                width: 60px;
                height: 60px;
                font-size: 35px;
            }

            .chatbot-icon {
                font-size: 35px;
            }

            .chatbot-window {
                bottom: 100px;
                left: 20px;
                width: calc(100vw - 40px);
                height: 70vh;
                border-radius: 20px;
            }

            .chatbot-suggestions {
                max-height: 100px;
            }

            .message-content {
                max-width: 80%;
            }
        }

        @media (max-width: 480px) {
            .chatbot-toggle {
                width: 55px;
                height: 55px;
                font-size: 32px;
            }

            .chatbot-icon {
                font-size: 32px;
            }

            .chatbot-notification {
                width: 24px;
                height: 24px;
                font-size: 11px;
            }

            .message-avatar {
                width: 36px;
                height: 36px;
                min-width: 36px;
                font-size: 20px;
            }

            .message-content {
                padding: 12px 14px;
                font-size: 13px;
            }

            .send-btn {
                width: 48px;
                height: 48px;
            }
        }

        /* دعم الوضع الليلي */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0a0b14;
                --bg-secondary: #151824;
                --text-color: #e9ecef;
                --border-color: #2a2e3a;
            }
        }
    </style>
</head>
<body>
    <!-- زر الروبوت العائم - شكل إيموجي -->
    <div id="chatbot-toggle" class="chatbot-toggle">
        <div class="chatbot-icon">
            🤖
        </div>
        <div class="chatbot-notification" id="chatbot-notification">3</div>
    </div>

    <!-- نافذة الدردشة -->
    <div id="chatbot-window" class="chatbot-window">
        <!-- الهيدر -->
        <div class="chatbot-header">
            <div class="chatbot-avatar">
                🤖
            </div>
            <div class="chatbot-title">
                <h3>
                    مساعد الروبوت الذكي
                    <span style="font-size: 14px; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px;">v2.0</span>
                </h3>
                <p>نظام الذكاء الاصطناعي | متصل</p>
            </div>
            <button class="chatbot-close" id="chatbot-close" aria-label="إغلاق">
                ✕
            </button>
        </div>

        <!-- منطقة المحادثة -->
        <div class="chatbot-messages" id="chatbot-messages">
            <!-- الرسالة الترحيبية -->
            <div class="message bot-message">
                <div class="message-avatar">
                    🤖
                </div>
                <div class="message-content">
                    <p>مرحباً! 👋</p>
                    <p>أنا مساعد الروبوت الذكي، جاهز لمساعدتك في خدماتنا:</p>
                    <p style="margin-top: 10px;">
                        • استضافة المواقع ☁️<br>
                        • تخزين سحابي 💾<br>
                        • حماية أمنية 🔒<br>
                        • اختبار اختراق 🛡️<br>
                        • دعم فني 24/7 ⚡
                    </p>
                    <span class="message-time">الآن</span>
                </div>
            </div>
        </div>

        <!-- الاقتراحات السريعة -->
        <div class="chatbot-suggestions" id="chatbot-suggestions">
            <button class="suggestion-btn" data-question="ما هي خدمات الاستضافة لديكم؟">
                <span>☁️</span> خدمات الاستضافة
            </button>
            <button class="suggestion-btn" data-question="كيف أقدم طلب جديد؟">
                <span>📝</span> طلب جديد
            </button>
            <button class="suggestion-btn" data-question="ما هي تكاليف التخزين السحابي؟">
                <span>💾</span> التخزين السحابي
            </button>
            <button class="suggestion-btn" data-question="كيف أتابع حالة مشروعي؟">
                <span>📊</span> متابعة المشروع
            </button>
            <button class="suggestion-btn" data-question="ما هي خدمات الحماية؟">
                <span>🔒</span> خدمات الحماية
            </button>
            <button class="suggestion-btn" data-question="كيف أتواصل مع الدعم الفني؟">
                <span>🎧</span> الدعم الفني
            </button>
        </div>

        <!-- منطقة الإدخال -->
        <div class="chatbot-input-area">
            <div class="input-wrapper">
                <input type="text" 
                       id="chatbot-input" 
                       class="chatbot-input" 
                       placeholder="اكتب سؤالك هنا..."
                       autocomplete="off"
                       aria-label="مربع النص">
                <button class="input-action-btn" id="emoji-btn" aria-label="إضافة إيموجي">
                    😊
                </button>
            </div>
            <button class="send-btn" id="send-btn" aria-label="إرسال">
                <svg viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>

        <!-- الفوتر -->
        <div class="chatbot-footer">
            <p>
                <span>🤖 مساعد ذكي</span>
                <span style="color: var(--success);">● متصل</span>
            </p>
            <p>
                <span id="chatbot-time">--:--</span>
                <span>| ⚡ 100%</span>
            </p>
        </div>
    </div>

    <script>
        // ============================================
        // ✨ إعدادات التوصيل مع N8N
        // ============================================
        const ChatbotConfig = {
            n8n: {
                // 👇 غير هذا الرابط إلى الرابط الجديد من N8N
                webhookUrl: "https://xcyper.app.n8n.cloud/webhook-test/b8f8f120-01a5-4f1b-9793-68d337d77663",
                
                // 👇 لو طلب منك API Key، حطه هنا
                apiKey: "",  // اتركه فاضي إذا ما طلب
                
                // 👇 إعدادات الاتصال
                timeout: 30000,
                retryAttempts: 3,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            },
            companyPhone: "+966500000000", // رقم الدعم
            companyName: "شركة الاستضافة",
            version: "2.0"
        };

        // ============================================
        // 🤖 كامل وظائف الروبوت
        // ============================================

        class Chatbot {
            constructor() {
                this.config = ChatbotConfig;
                this.isOpen = false;
                this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                this.init();
            }

            init() {
                // تعريف العناصر
                this.toggle = document.getElementById('chatbot-toggle');
                this.window = document.getElementById('chatbot-window');
                this.closeBtn = document.getElementById('chatbot-close');
                this.messages = document.getElementById('chatbot-messages');
                this.input = document.getElementById('chatbot-input');
                this.sendBtn = document.getElementById('send-btn');
                this.suggestions = document.getElementById('chatbot-suggestions');
                this.notification = document.getElementById('chatbot-notification');
                this.timeEl = document.getElementById('chatbot-time');

                // أحداث
                this.toggle?.addEventListener('click', () => this.toggleWindow());
                this.closeBtn?.addEventListener('click', () => this.closeWindow());
                this.sendBtn?.addEventListener('click', () => this.sendMessage());
                this.input?.addEventListener('keypress', (e) => e.key === 'Enter' && this.sendMessage());

                // اقتراحات سريعة
                this.suggestions?.querySelectorAll('.suggestion-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const question = e.currentTarget.dataset.question;
                        this.addMessage(question, 'user');
                        this.sendToN8N(question);
                    });
                });

                // وقت
                this.updateTime();
                setInterval(() => this.updateTime(), 60000);
                
                console.log('🤖 Chatbot initialized with URL:', this.config.n8n.webhookUrl);
            }

            toggleWindow() {
                this.isOpen ? this.closeWindow() : this.openWindow();
            }

            openWindow() {
                this.window?.classList.add('active');
                this.isOpen = true;
                this.notification.style.display = 'none';
                this.input?.focus();
            }

            closeWindow() {
                this.window?.classList.add('closing');
                setTimeout(() => {
                    this.window?.classList.remove('active', 'closing');
                    this.isOpen = false;
                }, 300);
            }

            async sendMessage() {
                const message = this.input?.value.trim();
                if (!message) return;
                
                this.addMessage(message, 'user');
                this.input.value = '';
                await this.sendToN8N(message);
            }

            addMessage(text, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}-message`;
                
                messageDiv.innerHTML = `
                    <div class="message-avatar">${sender === 'bot' ? '🤖' : '👤'}</div>
                    <div class="message-content">
                        <p>${this.escapeHtml(text)}</p>
                        <span class="message-time">${this.getCurrentTime()}</span>
                    </div>
                `;
                
                this.messages?.appendChild(messageDiv);
                this.messages?.scrollTo(0, this.messages.scrollHeight);
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            showTyping() {
                const typingDiv = document.createElement('div');
                typingDiv.className = 'message bot-message';
                typingDiv.id = 'typing-indicator';
                typingDiv.innerHTML = `
                    <div class="message-avatar">🤖</div>
                    <div class="typing-indicator">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                `;
                this.messages?.appendChild(typingDiv);
                this.messages?.scrollTo(0, this.messages.scrollHeight);
            }

            hideTyping() {
                document.getElementById('typing-indicator')?.remove();
            }

            async sendToN8N(message) {
                this.showTyping();
                
                try {
                    console.log('📤 Sending to N8N:', message);
                    
                    const response = await fetch(this.config.n8n.webhookUrl, {
                        method: 'POST',
                        headers: this.config.n8n.headers,
                        body: JSON.stringify({
                            message: message,
                            sessionId: this.sessionId,
                            timestamp: Date.now()
                        })
                    });

                    console.log('📡 Response status:', response.status);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    let data;
                    const contentType = response.headers.get('content-type');
                    if (contentType?.includes('application/json')) {
                        data = await response.json();
                    } else {
                        data = { message: await response.text() };
                    }

                    console.log('📨 N8N response:', data);
                    
                    this.hideTyping();
                    
                    const botResponse = data.message || data.response || data.output || JSON.stringify(data);
                    this.addMessage(botResponse, 'bot');
                    
                } catch (error) {
                    console.error('❌ Error:', error);
                    this.hideTyping();
                    
                    let errorMessage = '❌ عذراً، حدث خطأ في الاتصال. ';
                    
                    if (error.message.includes('404')) {
                        errorMessage += 'الـ Webhook غير مفعل. تأكد من تفعيل الـ Workflow في N8N.';
                    } else if (error.message.includes('Failed to fetch')) {
                        errorMessage += 'لا يمكن الوصول للخادم. تأكد من الرابط.';
                    } else {
                        errorMessage += 'يرجى المحاولة مرة أخرى.';
                    }
                    
                    errorMessage += `\n\n📞 للدعم المباشر: ${this.config.companyPhone}`;
                    
                    this.addMessage(errorMessage, 'bot');
                }
            }

            updateTime() {
                if (this.timeEl) {
                    const now = new Date();
                    this.timeEl.textContent = now.toLocaleTimeString('ar-SA', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                }
            }

            getCurrentTime() {
                return new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
            }
        }

        // تشغيل الروبوت
        document.addEventListener('DOMContentLoaded', () => {
            window.Chatbot = new Chatbot();
        });
    </script>
    
</body>
</html>
</body>
</html>