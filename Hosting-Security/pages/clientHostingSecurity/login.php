<?php
// بدء جلسة العمل

// تشغيل عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// بدء الجلسة في البداية (الأهم)
session_start();

// المسار الأساسي
define('BASE_PATH', __DIR__);
define('BASE_URL', '/client-unit');
require_once '../../security-init.php';
// تحميل الملفات الأساسية
require_once BASE_PATH . '/config/database.php';

$db = getDB();
$pdo = $db;

// متغيرات للأخطاء والرسائل
$login_error = '';
$reset_success = '';
$reset_error = '';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // التحقق من الحقول الفارغة
    if (empty($email) || empty($password)) {
        $login_error = 'البريد الإلكتروني وكلمة المرور مطلوبان';
    } else {
        // البحث عن المستخدم في قاعدة البيانات
        $stmt = $pdo->prepare("SELECT * FROM client_clients WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // ✅ أولاً: حفظ بيانات المستخدم في الجلسة
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['client_id'] = $user['id']; // الأهم لصفحات العميل
            $_SESSION['client_code'] = $user['client_code'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['company_name'] = $user['company_name'];
            $_SESSION['balance'] = $user['balance'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // تحديث آخر تسجيل دخول
            $updateStmt = $pdo->prepare("UPDATE client_clients SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // تسجيل عملية الدخول
            error_log("تسجيل دخول ناجح للمستخدم: " . $email . " - " . date('Y-m-d H:i:s'));
            
            // ✅ ثانياً: التوجيه إلى لوحة التحكم (بعد حفظ الجلسة)
            header('Location: dashboard/index.php');
            exit(); // مهم جداً
        
        } else {
            $login_error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            error_log("محاولة تسجيل دخول فاشلة للبريد: " . $email . " - " . date('Y-m-d H:i:s'));
        }
    }
}

// معالجة تغيير كلمة المرور (نفس الكود)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // ... باقي كود تغيير كلمة المرور
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xcyper - بوابة العملاء</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            box-sizing: border-box;
        }
        
        * {
            font-family: 'Cairo', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.3);
        }
        
        .cyber-border {
            border: 2px solid rgba(59, 130, 246, 0.3);
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
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .cyber-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
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
            background: #3b82f6;
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
        
        .password-strength {
            height: 4px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #ef4444; width: 25%; }
        .strength-fair { background-color: #f59e0b; width: 50%; }
        .strength-good { background-color: #10b981; width: 75%; }
        .strength-strong { background-color: #059669; width: 100%; }
    </style>
</head>
<body class="h-full gradient-bg">
    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

    <div id="app" class="h-full w-full overflow-auto scrollbar-custom">
        <!-- Login Page -->
        <div id="login-page" class="min-h-full flex items-center justify-center p-8">
            <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <!-- Left Side - Branding -->
                <div class="text-center lg:text-right space-y-8 floating">
                    <div class="flex items-center justify-center lg:justify-end">
                        <svg class="w-20 h-20 ml-4" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 17L12 22L22 17" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 12L12 17L22 12" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div>
                            <h1 class="text-5xl font-bold text-blue-400" id="login-company-name">Xcyper</h1>
                            <p class="text-xl text-gray-300">Network Security</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h2 class="text-4xl font-bold text-white" id="login-welcome-title">مرحباً بك في بوابة العملاء</h2>
                        <p class="text-xl text-gray-300">حلول أمن الشبكات والحوسبة السحابية</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-8">
                        <div class="cyber-border bg-slate-800 bg-opacity-50 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-blue-400 mb-2">500+</div>
                            <div class="text-sm text-gray-400">عميل راضٍ</div>
                        </div>
                        <div class="cyber-border bg-slate-800 bg-opacity-50 rounded-lg p-6 text-center">
                            <div class="text-3xl font-bold text-cyan-400 mb-2">24/7</div>
                            <div class="text-sm text-gray-400">دعم فني</div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Login/Change Password Forms -->
                <div class="cyber-border bg-slate-800 rounded-2xl p-8 cyber-glow">
                    <div class="flex items-center justify-center mb-8 space-x-4 space-x-reverse">
                        <button id="show-login" class="flex-1 px-6 py-3 <?php echo !isset($_POST['reset_password']) ? 'bg-blue-600' : 'bg-slate-700'; ?> rounded-lg font-bold transition-all hover:bg-blue-700">تسجيل الدخول</button>
                        <button id="show-reset" class="flex-1 px-6 py-3 <?php echo isset($_POST['reset_password']) ? 'bg-green-600' : 'bg-slate-700'; ?> hover:bg-green-700 rounded-lg font-bold transition-all">تغيير كلمة السر</button>
                    </div>

                    <!-- Information Message -->
                    <div id="info-message" class="mb-6 p-4 bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg">
                        <p class="text-center text-blue-300 font-semibold">
                            ⓘ الحسابات تنشأ من قبل إدارة النظام فقط
                        </p>
                        <p class="text-center text-gray-400 text-sm mt-1">
                            إذا كنت عميلاً ولديك حساب، يمكنك تسجيل الدخول أو تغيير كلمة المرور
                        </p>
                    </div>

                    <!-- Login Form -->
                    <form id="login-form" method="POST" action="" class="space-y-6 <?php echo isset($_POST['reset_password']) ? 'hidden' : ''; ?>">
                        <input type="hidden" name="login" value="1">
                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-white mb-2">تسجيل الدخول</h3>
                            <p class="text-gray-400">ادخل بياناتك للوصول إلى حسابك</p>
                        </div>
                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">البريد الإلكتروني</label>
                            <input type="email" name="email" id="login-email" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="example@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">كلمة المرور</label>
                            <input type="password" name="password" id="login-password" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="••••••••">
                        </div>
                        <button type="submit" class="w-full px-6 py-4 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold text-lg transition-all cyber-glow">دخول</button>
                    </form>

                    <!-- Reset Password Form -->
                    <form id="reset-password-form" method="POST" action="" class="space-y-6 <?php echo isset($_POST['reset_password']) ? '' : 'hidden'; ?>">
                        <input type="hidden" name="reset_password" value="1">
                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-white mb-2">تغيير كلمة المرور</h3>
                            <p class="text-gray-400">يجب أن تحتوي كلمة المرور على شروط أمان قوية</p>
                        </div>
                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">البريد الإلكتروني</label>
                            <input type="email" name="reset_email" id="reset-email" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="example@email.com" value="<?php echo isset($_POST['reset_email']) ? htmlspecialchars($_POST['reset_email']) : ''; ?>">
                        </div>
                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">كلمة المرور القديمة</label>
                            <input type="password" name="old_password" id="reset-old-password" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="••••••••">
                        </div>
                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">كلمة المرور الجديدة</label>
                            <input type="password" name="new_password" id="reset-new-password" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="••••••••">
                            <div class="mt-2 space-y-2">
                                <div id="password-strength" class="password-strength rounded-full"></div>
                                <div class="text-xs text-gray-400 space-y-1">
                                    <p id="length-check" class="flex items-center">
                                        <span class="text-red-400 mr-1">✗</span> 8 أحرف على الأقل
                                    </p>
                                    <p id="uppercase-check" class="flex items-center">
                                        <span class="text-red-400 mr-1">✗</span> حرف كبير واحد على الأقل
                                    </p>
                                    <p id="lowercase-check" class="flex items-center">
                                        <span class="text-red-400 mr-1">✗</span> حرف صغير واحد على الأقل
                                    </p>
                                    <p id="number-check" class="flex items-center">
                                        <span class="text-red-400 mr-1">✗</span> رقم واحد على الأقل
                                    </p>
                                    <p id="special-check" class="flex items-center">
                                        <span class="text-red-400 mr-1">✗</span> رمز خاص واحد على الأقل (!@#$%^&*)
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <label class="block text-sm font-semibold mb-2 text-gray-300">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" name="confirm_password" id="reset-confirm-password" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="••••••••">
                            <div id="password-match" class="hidden text-xs text-red-400 mt-1">كلمات المرور غير متطابقة</div>
                        </div>
                        <button type="button" id="back-to-login" class="w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all mb-2">← العودة لتسجيل الدخول</button>
                        <button type="submit" class="w-full px-6 py-4 bg-green-600 hover:bg-green-700 rounded-lg font-bold text-lg transition-all cyber-glow">تغيير كلمة المرور</button>
                    </form>

                    <!-- Security Policies Info -->
                    <div class="mt-8 p-4 bg-slate-900 rounded-lg">
                        <h4 class="text-lg font-bold text-white mb-2 text-center">سياسات الأمان</h4>
                        <ul class="text-sm text-gray-400 space-y-1 text-right">
                            <li class="flex items-center justify-end">
                                <span>تسجيل الدخول الآمن (HTTPS)</span>
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewbox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </li>
                            <li class="flex items-center justify-end">
                                <span>تشفير كلمات المرور</span>
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewbox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </li>
                            <li class="flex items-center justify-end">
                                <span>مصادقة متعددة العوامل</span>
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewbox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </li>
                            <li class="flex items-center justify-end">
                                <span>مراقبة أنشطة الدخول</span>
                                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewbox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // دالة إظهار الإشعارات
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

        // التحقق من قوة كلمة المرور
        function validatePassword(password) {
            const checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*]/.test(password)
            };

            let strength = Object.values(checks).filter(Boolean).length;

            let strengthClass = 'strength-weak';
            if (strength >= 4) strengthClass = 'strength-good';
            else if (strength >= 3) strengthClass = 'strength-fair';
            else if (strength === 5) strengthClass = 'strength-strong';

            return { checks, strengthClass };
        }

        // تحديث مؤشر قوة كلمة المرور
        function updatePasswordStrength(password) {
            const result = validatePassword(password);
            const strengthBar = document.getElementById('password-strength');
            
            strengthBar.className = 'password-strength rounded-full ' + result.strengthClass;
            
            // تحديث علامات التحقق
            const checks = ['length', 'uppercase', 'lowercase', 'number', 'special'];
            checks.forEach(check => {
                const element = document.getElementById(`${check}-check`);
                if (element) {
                    const span = element.firstElementChild;
                    span.textContent = result.checks[check] ? '✓' : '✗';
                    span.className = result.checks[check] ? 'text-green-400 mr-1' : 'text-red-400 mr-1';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // تبديل التبويبات
            document.getElementById('show-login').addEventListener('click', () => {
                document.getElementById('login-form').classList.remove('hidden');
                document.getElementById('reset-password-form').classList.add('hidden');
                document.getElementById('show-login').classList.add('bg-blue-600');
                document.getElementById('show-login').classList.remove('bg-slate-700');
                document.getElementById('show-reset').classList.remove('bg-green-600');
                document.getElementById('show-reset').classList.add('bg-slate-700');
            });

            document.getElementById('show-reset').addEventListener('click', () => {
                document.getElementById('login-form').classList.add('hidden');
                document.getElementById('reset-password-form').classList.remove('hidden');
                document.getElementById('show-reset').classList.add('bg-green-600');
                document.getElementById('show-reset').classList.remove('bg-slate-700');
                document.getElementById('show-login').classList.remove('bg-blue-600');
                document.getElementById('show-login').classList.add('bg-slate-700');
            });

            document.getElementById('back-to-login').addEventListener('click', () => {
                document.getElementById('show-login').click();
                document.getElementById('reset-password-form').reset();
                
                // إعادة تعيين مؤشر قوة كلمة المرور
                const strengthBar = document.getElementById('password-strength');
                strengthBar.className = 'password-strength rounded-full';
                
                // إعادة تعيين علامات التحقق
                const checks = document.querySelectorAll('[id$="-check"] span');
                checks.forEach(span => {
                    span.textContent = '✗';
                    span.className = 'text-red-400 mr-1';
                });
            });

            // مراقبة قوة كلمة المرور
            document.getElementById('reset-new-password').addEventListener('input', function(e) {
                updatePasswordStrength(e.target.value);
                
                // التحقق من تطابق كلمات المرور
                const confirmPassword = document.getElementById('reset-confirm-password').value;
                const matchError = document.getElementById('password-match');
                
                if (confirmPassword && e.target.value !== confirmPassword) {
                    matchError.classList.remove('hidden');
                } else {
                    matchError.classList.add('hidden');
                }
            });

            document.getElementById('reset-confirm-password').addEventListener('input', function(e) {
                const newPassword = document.getElementById('reset-new-password').value;
                const matchError = document.getElementById('password-match');
                
                if (newPassword && e.target.value !== newPassword) {
                    matchError.classList.remove('hidden');
                } else {
                    matchError.classList.add('hidden');
                }
            });

            // عرض رسائل الخطأ/النجاح
            <?php if ($login_error): ?>
            showNotification('<?php echo $login_error; ?>', 'error');
            <?php endif; ?>
            
            <?php if ($reset_success): ?>
            showNotification('<?php echo $reset_success; ?>', 'success');
            setTimeout(() => {
                document.getElementById('show-login').click();
            }, 2000);
            <?php endif; ?>
            
            <?php if ($reset_error): ?>
            showNotification('<?php echo $reset_error; ?>', 'error');
            <?php endif; ?>
        });
    </script>
</body>
</html>