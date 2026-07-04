<?php
// index.php - الصفحة الرئيسية (الهيكل فقط)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// تحديد الصفحة المطلوبة
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'alerts', 'threats', 'servers', 'logs', 'incidents', 'reports', 'client_reports', 'policies', 'statistics'];
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الاستضافة - وحدة الحماية والمراقبة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); }
        .nav-item { transition: all 0.3s ease; border-right: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: rgba(16, 185, 129, 0.1); border-right-color: #10b981; }
        .cyber-border { border: 2px solid rgba(16, 185, 129, 0.3); position: relative; overflow: hidden; }
        .cyber-border::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
            animation: scan 3s linear infinite;
        }
        @keyframes scan { 0% { left: -100%; } 100% { left: 100%; } }
        .status-indicator { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-left: 8px; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.5; } }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3); }
        .progress-bar { height: 8px; background: #1e293b; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); transition: width 0.3s ease; }
        .scrollbar-custom::-webkit-scrollbar { width: 8px; }
        .scrollbar-custom::-webkit-scrollbar-track { background: #1e293b; }
        .scrollbar-custom::-webkit-scrollbar-thumb { background: #10b981; border-radius: 4px; }
    </style>
</head>
<body class="h-full gradient-bg text-gray-100">
    <div class="h-full w-full flex overflow-hidden">
        <!-- القائمة الجانبية -->
        <aside class="w-64 bg-slate-900 border-l border-slate-700 flex flex-col">
            <!-- الشعار -->
            <div class="p-6 border-b border-slate-700">
                <div class="flex items-center justify-center">
                    <svg class="w-10 h-10 ml-3" viewBox="0 0 24 24" fill="none">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke="#10b981" stroke-width="2"/>
                    </svg>
                    <div>
                        <h1 class="text-xl font-bold text-green-400">وحدة الحماية</h1>
                        <p class="text-xs text-gray-400">مراقبة 24/7</p>
                    </div>
                </div>
            </div>
            
            <!-- روابط التنقل -->
            <nav class="flex-1 overflow-y-auto scrollbar-custom p-4">
                <div class="space-y-2">
                    <a href="?page=dashboard" class="nav-item <?php echo $page == 'dashboard' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>لوحة المراقبة الحية</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </a>
                    <a href="?page=alerts" class="nav-item <?php echo $page == 'alerts' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>نظام التنبيهات</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </a>
                    <!-- أضف هذا الرابط بعد incidents وقبل policies -->
<a href="?page=reports" class="nav-item <?php echo $page == 'reports' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
    <span>تقارير الأداء اليومية</span>
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
</a>


<a href="?page=client_reports" class="nav-item <?php echo $page == 'client_reports' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
    <span>تقارير العملاء</span>
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
</a>

<a href="?page=policies" class="nav-item <?php echo $page == 'policies' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
    <span>إدارة السياسات الأمنية</span>
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
</a>

                    <a href="?page=threats" class="nav-item <?php echo $page == 'threats' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>تحليل التهديدات</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </a>
                    <!-- أضف هذا الرابط بعد policies -->
<a href="?page=statistics" class="nav-item <?php echo $page == 'statistics' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
    <span>إحصاءات الأمان</span>
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
    </svg>
</a>
                    
                    <a href="?page=servers" class="nav-item <?php echo $page == 'servers' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>مراقبة الخوادم</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                    </a>
                    <a href="?page=logs" class="nav-item <?php echo $page == 'logs' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>سجلات الأحداث</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </a>
                    <a href="?page=incidents" class="nav-item <?php echo $page == 'incidents' ? 'active' : ''; ?> w-full text-right px-4 py-3 rounded-lg flex items-center justify-end">
                        <span>إدارة الحوادث</span>
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </a>
                </div>
            </nav>
            
            <!-- معلومات المستخدم -->
            <div class="p-4 border-t border-slate-700">
                <div class="flex items-center justify-end">
                    <div class="text-right ml-3">
                        <p class="text-sm font-semibold"><?php echo $current_user['name']; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $current_user['role']; ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.879 6.196 9 9 0 015.121 17.804zM15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- الشريط العلوي -->
            <header class="bg-slate-900 border-b border-slate-700 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <button onclick="toggleMonitoring()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold cyber-glow flex items-center">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span id="monitoring-status">المراقبة نشطة</span>
                        </button>
                        <button onclick="runSecurityScan()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            مسح أمني
                        </button>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-6 text-right">
                            <h2 class="text-2xl font-bold text-green-400" id="page-title">
                                <?php
                                $titles = [
    'dashboard' => 'لوحة المراقبة الحية',
    'alerts' => 'نظام التنبيهات',
    'threats' => 'تحليل التهديدات',
    'servers' => 'مراقبة الخوادم',
    'logs' => 'سجلات الأحداث',
    'incidents' => 'إدارة الحوادث',
    'reports' => 'تقارير الأداء اليومية',
    'client_reports' => 'تقارير العملاء',
    'policies' => 'إدارة السياسات الأمنية',
    'statistics' => 'إحصاءات الأمان'
];
                                echo $titles[$page];
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </header>

            <!-- منطقة المحتوى - يتم تضمين الصفحة المطلوبة -->
            <div class="flex-1 overflow-y-auto scrollbar-custom p-8">
                <?php include "pages/{$page}.php"; ?>
            </div>
        </main>
    </div>

    <!-- الإشعارات والمودالات -->
    <div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>
    <div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-80">
        <div class="text-center"><div class="spinner mx-auto mb-4"></div><p class="text-gray-400">جاري التحميل...</p></div>
    </div>

    <style>
        .spinner { border: 3px solid rgba(16, 185, 129, 0.3); border-top: 3px solid #10b981; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .notification { animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .cyber-glow { box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
    </style>

    <script>
        let monitoringActive = true;
        function toggleMonitoring() {
            monitoringActive = !monitoringActive;
            const status = document.getElementById('monitoring-status');
            status.textContent = monitoringActive ? 'المراقبة نشطة' : 'المراقبة متوقفة';
            status.parentElement.classList.toggle('cyber-glow', monitoringActive);
            showNotification(monitoringActive ? 'تم تفعيل المراقبة' : 'تم إيقاف المراقبة', monitoringActive ? 'success' : 'warning');
        }
        function runSecurityScan() { showLoading(); setTimeout(() => { hideLoading(); showNotification('اكتمل الفحص الأمني', 'success'); }, 2000); }
        function showLoading() { document.getElementById('loading-spinner').classList.remove('hidden'); }
        function hideLoading() { document.getElementById('loading-spinner').classList.add('hidden'); }
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-blue-600', warning: 'bg-yellow-600' };
            const notification = document.createElement('div');
            notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg`;
            notification.textContent = message;
            container.appendChild(notification);
            setTimeout(() => { notification.style.opacity = '0'; setTimeout(() => notification.remove(), 300); }, 3000);
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