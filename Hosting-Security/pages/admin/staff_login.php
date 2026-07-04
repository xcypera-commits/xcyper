<?php
// بدء جلسة العمل
session_start();
require_once '../../security-init.php';
// الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'security_monitoring_db'; // اسم قاعدة البيانات
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}


// معالجة تسجيل الدخول
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $department = $_POST['department'];
    
    if (empty($email) || empty($password) || empty($department)) {
        $login_error = 'جميع الحقول مطلوبة';
    } else {
        // البحث عن المستخدم في جدول users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND department = ? AND is_active = 1");
        $stmt->execute([$email, $department]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // تسجيل الدخول ناجح
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_department'] = $user['department'];
            $_SESSION['can_manage'] = $user['can_manage'];
            
            // تحديث آخر تسجيل دخول
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // التوجيه إلى لوحة التحكم حسب الدور
            switch($user['role']) {
                case 'hosting':
                case 'storage':
                    header('Location: CloudStorage/index.php');
                    break;
                case 'security':
                    header('Location: SecurityMonitoring/index.php');
                    break;
                case 'pentest':
                    header('Location: PenetrationTestingUnit/index.php');
                    break;
                case 'documentation':
                case 'technical_writer':
                    header('Location: Documentationhaid/index.php');
                    break;
                case 'management':
                case 'admin':
                case 'manager':
                    header('Location: ManagerHostingSecurity/ExecutiveDashboard.php');
                    break;
                default:
                    header('Location: HOMCustomer/index.php');
            }
            exit();
        } else {
            $login_error = 'البريد الإلكتروني أو كلمة المرور أو القسم غير صحيح';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xcyper - بوابة الموظفين</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Fira+Code:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            box-sizing: border-box;
        }
        
        * {
            font-family: 'Cairo', sans-serif;
        }
        
        .code-font {
            font-family: 'Fira Code', monospace;
        }
        
        .gradient-bg-staff {
            background: linear-gradient(135deg, #0a0f1e 0%, #1a1f35 50%, #0a0f1e 100%);
        }
        
        /* ألوان الأقسام المختلفة */
        .gradient-hosting {
            background: linear-gradient(135deg, #065f46 0%, #059669 50%, #065f46 100%);
        }
        
        .gradient-storage {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #1e40af 100%);
        }
        
        .gradient-security {
            background: linear-gradient(135deg, #b45309 0%, #d97706 50%, #b45309 100%);
        }
        
        .gradient-pentest {
            background: linear-gradient(135deg, #7e22ce 0%, #9333ea 50%, #7e22ce 100%);
        }
        
        .gradient-documentation {
            background: linear-gradient(135deg, #831843 0%, #be185d 50%, #831843 100%);
        }
        
        .gradient-management {
            background: linear-gradient(135deg, #6b21a8 0%, #7e22ce 50%, #6b21a8 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.3);
        }
        
        .cyber-border-staff {
            border: 2px solid rgba(139, 92, 246, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .cyber-border-staff::before {
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
        
        .cyber-glow-staff {
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
        }
        
        .dept-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .dept-card:hover {
            transform: translateY(-5px);
        }
        
        .dept-card.active {
            border: 2px solid #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
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
        
        .notification {
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse-ring {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
                transform: scale(1.05);
            }
        }
        
        .department-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-hosting { background: rgba(5, 150, 105, 0.2); color: #10b981; }
        .badge-storage { background: rgba(37, 99, 235, 0.2); color: #3b82f6; }
        .badge-security { background: rgba(217, 119, 6, 0.2); color: #f59e0b; }
        .badge-pentest { background: rgba(147, 51, 234, 0.2); color: #a855f7; }
        .badge-documentation { background: rgba(190, 24, 93, 0.2); color: #ec4899; }
        .badge-management { background: rgba(124, 58, 237, 0.2); color: #8b5cf6; }
    </style>
</head>
<body class="h-full gradient-bg-staff">
    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

    <div id="app" class="h-full w-full overflow-auto scrollbar-custom">
        <!-- Staff Login Page -->
        <div id="staff-login-page" class="min-h-full flex items-center justify-center p-8">
            <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                
                <!-- Left Side - Staff Branding -->
                <div class="text-center lg:text-right space-y-8 floating">
                    <div class="flex items-center justify-center lg:justify-end">
                        <svg class="w-20 h-20 ml-4" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 17L12 22L22 17" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 12L12 17L22 12" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div>
                            <h1 class="text-5xl font-bold text-purple-400">Xcyper</h1>
                            <p class="text-xl text-gray-300">Staff Portal</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h2 class="text-4xl font-bold text-white">مرحباً بك في بوابة الموظفين</h2>
                        <p class="text-xl text-gray-300">نظام إدارة الاستضافة والحماية المتقدم</p>
                    </div>
                    
                    <!-- Department Stats -->
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <div class="cyber-border-staff bg-slate-800 bg-opacity-50 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-green-400 mb-1">استضافة</div>
                            <div class="text-sm text-gray-400">Hosting</div>
                        </div>
                        <div class="cyber-border-staff bg-slate-800 bg-opacity-50 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-blue-400 mb-1">تخزين</div>
                            <div class="text-sm text-gray-400">Storage</div>
                        </div>
                        <div class="cyber-border-staff bg-slate-800 bg-opacity-50 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-yellow-400 mb-1">حماية</div>
                            <div class="text-sm text-gray-400">Security</div>
                        </div>
                        <div class="cyber-border-staff bg-slate-800 bg-opacity-50 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-purple-400 mb-1">اختبار</div>
                            <div class="text-sm text-gray-400">Pentest</div>
                        </div>
                        <div class="cyber-border-staff bg-slate-800 bg-opacity-50 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-pink-400 mb-1">توثيق</div>
                            <div class="text-sm text-gray-400">Documentation</div>
                        </div>
                        <div class="cyber-border-staff bg-slate-800 bg-opacity-50 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-indigo-400 mb-1">إدارة</div>
                            <div class="text-sm text-gray-400">Management</div>
                        </div>
                    </div>
                    
                    <!-- Back to Client Portal Button -->
                    <div class="pt-4">
                        <a href="login.php" class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-lg font-bold text-lg transition-all cyber-glow inline-block text-center">
                            ← العودة لبوابة العملاء
                        </a>
                    </div>
                </div>

                <!-- Right Side - Staff Login Form -->
                <div class="cyber-border-staff bg-slate-800 rounded-2xl p-8 cyber-glow-staff">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-white mb-2">دخول الموظفين</h2>
                        <p class="text-gray-400">اختر قسمك وسجل دخولك</p>
                    </div>

                    <!-- Department Selection Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                        <!-- Hosting Department -->
                        <div class="dept-card bg-slate-900 rounded-xl p-4 text-center hover:bg-green-900 hover:bg-opacity-20 transition-all" data-dept="hosting">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-green-600 bg-opacity-20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-green-400">الاستضافة</h3>
                            <span class="department-badge badge-hosting mt-1 inline-block">hosting</span>
                        </div>

                        <!-- Cloud Storage Department -->
                        <div class="dept-card bg-slate-900 rounded-xl p-4 text-center hover:bg-blue-900 hover:bg-opacity-20 transition-all" data-dept="storage">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-blue-600 bg-opacity-20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-blue-400">التخزين</h3>
                            <span class="department-badge badge-storage mt-1 inline-block">storage</span>
                        </div>

                        <!-- Security Department -->
                        <div class="dept-card bg-slate-900 rounded-xl p-4 text-center hover:bg-yellow-900 hover:bg-opacity-20 transition-all" data-dept="security">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-yellow-600 bg-opacity-20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-yellow-400">الحماية</h3>
                            <span class="department-badge badge-security mt-1 inline-block">security</span>
                        </div>

                        <!-- Pentesting Department -->
                        <div class="dept-card bg-slate-900 rounded-xl p-4 text-center hover:bg-purple-900 hover:bg-opacity-20 transition-all" data-dept="pentest">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-purple-600 bg-opacity-20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-purple-400">اختبار الاختراق</h3>
                            <span class="department-badge badge-pentest mt-1 inline-block">pentest</span>
                        </div>

                        <!-- Documentation Department -->
                        <div class="dept-card bg-slate-900 rounded-xl p-4 text-center hover:bg-pink-900 hover:bg-opacity-20 transition-all" data-dept="documentation">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-pink-600 bg-opacity-20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-pink-400">التوثيق</h3>
                            <span class="department-badge badge-documentation mt-1 inline-block">documentation</span>
                        </div>

                        <!-- General Management -->
                        <div class="dept-card bg-slate-900 rounded-xl p-4 text-center hover:bg-indigo-900 hover:bg-opacity-20 transition-all" data-dept="management">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-indigo-600 bg-opacity-20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-indigo-400">الإدارة العامة</h3>
                            <span class="department-badge badge-management mt-1 inline-block">management</span>
                        </div>
                    </div>

                    <!-- Information Message -->
                    <div class="mb-6 p-4 bg-purple-900 bg-opacity-30 border border-purple-700 rounded-lg">
                        <p class="text-center text-purple-300 font-semibold">
                            ⓘ بوابة الموظفين الداخلية
                        </p>
                        <p class="text-center text-gray-400 text-sm mt-1">
                            هذه البوابة مخصصة لموظفي Xcyper فقط
                        </p>
                    </div>

                    <!-- Login Form -->
                    <form id="staff-login-form" method="POST" action="" class="space-y-6">
                        <input type="hidden" name="staff_login" value="1">
                        <input type="hidden" name="department" id="selected-department" value="">
                        
                        <div class="text-center mb-4">
                            <div id="selected-dept-display" class="text-lg font-bold text-purple-400 mb-2">
                                الرجاء اختيار القسم أولاً
                            </div>
                        </div>

                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">البريد الإلكتروني الوظيفي</label>
                            <input type="email" name="email" id="staff-email" required 
                                   class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500 text-right"
                                   placeholder="staff@xcyper.com">
                        </div>

                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">كلمة المرور</label>
                            <input type="password" name="password" id="staff-password" required 
                                   class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-purple-500 text-right"
                                   placeholder="••••••••">
                        </div>

                        <button type="submit" id="login-submit" disabled
                                class="w-full px-6 py-4 bg-gray-600 cursor-not-allowed rounded-lg font-bold text-lg transition-all">
                            دخول
                        </button>
                    </form>

                    <!-- Department Features -->
                    <div class="mt-8 p-4 bg-slate-900 rounded-lg">
                        <h4 class="text-lg font-bold text-white mb-3 text-center">الأدوار والصلاحيات</h4>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-400">
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full ml-2"></span>
                                <span>hosting: إدارة السيرفرات</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-blue-400 rounded-full ml-2"></span>
                                <span>storage: إدارة المساحات</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-yellow-400 rounded-full ml-2"></span>
                                <span>security: جدران نارية</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-purple-400 rounded-full ml-2"></span>
                                <span>pentest: تحليل الثغرات</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-pink-400 rounded-full ml-2"></span>
                                <span>documentation: توثيق</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-indigo-400 rounded-full ml-2"></span>
                                <span>management: إدارة</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-orange-400 rounded-full ml-2"></span>
                                <span>admin: صلاحيات كاملة</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-teal-400 rounded-full ml-2"></span>
                                <span>analyst: تحليل</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Department selection handling
        const deptCards = document.querySelectorAll('.dept-card');
        const selectedDeptInput = document.getElementById('selected-department');
        const selectedDeptDisplay = document.getElementById('selected-dept-display');
        const loginSubmit = document.getElementById('login-submit');
        let selectedDept = '';

        // Department names in Arabic
        const deptNames = {
            'hosting': 'قسم الاستضافة',
            'storage': 'قسم التخزين السحابي',
            'security': 'قسم الحماية والمراقبة',
            'pentest': 'قسم اختبار الاختراق',
            'documentation': 'قسم التوثيق',
            'management': 'الإدارة العامة'
        };

        // Department colors
        const deptColors = {
            'hosting': 'text-green-400',
            'storage': 'text-blue-400',
            'security': 'text-yellow-400',
            'pentest': 'text-purple-400',
            'documentation': 'text-pink-400',
            'management': 'text-indigo-400'
        };

        deptCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove active class from all cards
                deptCards.forEach(c => {
                    c.classList.remove('active', 'border-2', 'border-purple-500');
                });
                
                // Add active class to selected card
                this.classList.add('active', 'border-2', 'border-purple-500');
                
                // Get department
                selectedDept = this.dataset.dept;
                selectedDeptInput.value = selectedDept;
                
                // Update display
                selectedDeptDisplay.innerHTML = `القسم المختار: <span class="${deptColors[selectedDept]}">${deptNames[selectedDept]}</span>`;
                
                // Enable login button
                loginSubmit.disabled = false;
                loginSubmit.classList.remove('bg-gray-600', 'cursor-not-allowed');
                loginSubmit.classList.add('bg-purple-600', 'hover:bg-purple-700', 'cursor-pointer');
            });
        });

        // Form validation
        document.getElementById('staff-login-form').addEventListener('submit', function(e) {
            if (!selectedDept) {
                e.preventDefault();
                showNotification('الرجاء اختيار القسم أولاً', 'error');
            }
        });

        // Show notification function
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            
            const colors = {
                'success': 'bg-green-600',
                'error': 'bg-red-600',
                'info': 'bg-blue-600'
            };
            
            notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
            notification.textContent = message;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Show PHP error message if exists
        <?php if ($login_error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('<?php echo $login_error; ?>', 'error');
        });
        <?php endif; ?>
    </script>
</body>
</html>