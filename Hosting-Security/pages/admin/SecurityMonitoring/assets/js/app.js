// assets/js/app.js
// ملف JavaScript الرئيسي للتطبيق

// المتغيرات العامة
let currentSection = 'dashboard';
let isMonitoringActive = true;
let liveLogsInterval = null;
let dashboardInterval = null;
let currentUser = {
    id: 1,
    name: 'أحمد العلي',
    role: 'رئيس وحدة الحماية والمراقبة'
};

// تهيئة التطبيق
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// تهيئة التطبيق
async function initializeApp() {
    showLoading();
    
    try {
        // تحميل البيانات الأولية
        await loadDashboardData();
        
        // بدء التحديثات الحية
        startLiveUpdates();
        
        // إعداد مستمعي الأحداث
        setupEventListeners();
        
    } catch (error) {
        console.error('خطأ في تهيئة التطبيق:', error);
        showNotification('فشل تحميل البيانات', 'error');
    } finally {
        hideLoading();
    }
}

// إعداد مستمعي الأحداث
function setupEventListeners() {
    // تحديث البيانات عند تغيير القسم
    document.querySelectorAll('.nav-item').forEach(button => {
        button.addEventListener('click', function() {
            const section = this.dataset.section;
            navigateTo(section);
        });
    });
}

// بدء التحديثات الحية
function startLiveUpdates() {
    if (dashboardInterval) clearInterval(dashboardInterval);
    
    dashboardInterval = setInterval(() => {
        if (currentSection === 'dashboard') {
            updateLiveData();
        }
    }, 10000); // كل 10 ثواني
}

// تحديث البيانات الحية
async function updateLiveData() {
    try {
        const response = await fetch('/api/get_dashboard.php');
        const result = await response.json();
        
        if (result.success) {
            updateDashboardUI(result.data);
        }
    } catch (error) {
        console.error('خطأ في تحديث البيانات:', error);
    }
}

// تحديث واجهة لوحة التحكم
function updateDashboardUI(data) {
    // تحديث الإحصائيات
    document.getElementById('active-servers').textContent = data.active_servers;
    document.getElementById('active-threats').textContent = data.active_threats;
    document.getElementById('daily-alerts').textContent = data.daily_alerts;
    document.getElementById('uptime-percentage').textContent = data.uptime + '%';
    
    // تحديث رسالة التنبيهات
    const resolvedText = document.querySelector('.text-yellow-300');
    if (resolvedText) {
        resolvedText.textContent = `تم معالجة ${data.resolved_alerts} منها`;
    }
    
    // تحديث الخوادم
    updateServersUI(data.servers);
    
    // تحديث التنبيهات الحرجة
    updateCriticalAlertsUI(data.critical_alerts);
    
    // تحديث أحداث الأمان
    updateSecurityEventsUI(data.security_events);
}

// تحديث واجهة الخوادم
function updateServersUI(servers) {
    const serversContainer = document.querySelector('#section-dashboard .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4');
    if (!serversContainer) return;
    
    serversContainer.innerHTML = servers.map(server => `
        <div class="server-status-${server.status} cyber-border bg-slate-900 p-4 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm ${server.status_color}">${server.name}</span>
                <span class="status-indicator ${server.indicator_color}"></span>
            </div>
            <p class="text-xs text-gray-400">الاستخدام: ${server.cpu}%</p>
            <div class="progress-bar mt-1">
                <div class="progress-fill" style="width: ${server.cpu}%"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span>${server.ip}</span>
                <span>${server.location}</span>
            </div>
        </div>
    `).join('');
}

// تحديث واجهة التنبيهات الحرجة
function updateCriticalAlertsUI(alerts) {
    const container = document.getElementById('live-alerts');
    if (!container) return;
    
    if (alerts.length === 0) {
        container.innerHTML = `
            <div class="text-center p-8">
                <svg class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-400">لا توجد تنبيهات حرجة حالياً</p>
                <p class="text-sm text-gray-400 mt-2">جميع الأنظمة تعمل بشكل طبيعي</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = alerts.map(alert => `
        <div class="critical-alert p-4 bg-red-900 bg-opacity-20 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="font-semibold text-red-400">${alert.title}</p>
                <span class="text-xs text-gray-400">${alert.time}</span>
            </div>
            <p class="text-sm text-gray-300 mb-3">${alert.description}</p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">${alert.server}</span>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="acknowledgeAlert(${alert.id})" class="text-xs text-blue-400 hover:text-blue-300">
                        تأكيد
                    </button>
                    <button onclick="viewAlertDetails(${alert.id})" class="text-xs text-green-400 hover:text-green-300">
                        تفاصيل
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// تحديث واجهة أحداث الأمان
function updateSecurityEventsUI(events) {
    const container = document.getElementById('security-events');
    if (!container) return;
    
    if (events.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-400 py-4">لا توجد أحداث أمنية حديثة</p>';
        return;
    }
    
    container.innerHTML = events.map(event => `
        <div class="log-type-security p-3 bg-slate-800 rounded-lg">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs ${event.level_color}">${event.type}</span>
                <span class="text-xs text-gray-400">${event.time}</span>
            </div>
            <p class="text-sm text-gray-300 truncate">${event.description}</p>
            <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                <span>${event.source}</span>
            </div>
        </div>
    `).join('');
}

// تحميل بيانات لوحة التحكم
async function loadDashboardData() {
    try {
        const response = await fetch('/api/get_dashboard.php');
        const result = await response.json();
        
        if (result.success) {
            updateDashboardUI(result.data);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        console.error('خطأ في تحميل البيانات:', error);
        showNotification('فشل تحميل بيانات لوحة التحكم', 'error');
    }
}

// تحميل بيانات التنبيهات
async function loadAlertsData(type = 'all') {
    showLoading();
    
    try {
        const response = await fetch(`/api/get_alerts.php?type=${type}`);
        const result = await response.json();
        
        if (result.success) {
            updateAlertsUI(result.data);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        console.error('خطأ في تحميل البيانات:', error);
        showNotification('فشل تحميل بيانات التنبيهات', 'error');
    } finally {
        hideLoading();
    }
}

// تحديث واجهة التنبيهات
function updateAlertsUI(data) {
    // تحديث الإحصائيات
    const stats = data.statistics;
    document.querySelector('#section-alerts .text-red-400').textContent = stats.critical || 0;
    document.querySelector('#section-alerts .text-yellow-400').textContent = stats.warning || 0;
    document.querySelector('#section-alerts .text-blue-400').textContent = stats.info || 0;
    document.querySelector('#section-alerts .text-green-400').textContent = stats.resolved || 0;
    
    // تحديث جدول التنبيهات
    const tbody = document.getElementById('alerts-table');
    if (!tbody) return;
    
    if (data.alerts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-400">
                    لا توجد تنبيهات حالياً
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = data.alerts.map(alert => `
        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="viewAlertDetails(${alert.id})" class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="acknowledgeAlert(${alert.id})" class="text-green-400 hover:text-green-300" title="تأكيد">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    <button onclick="resolveAlert(${alert.id})" class="text-yellow-400 hover:text-yellow-300" title="حل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${alert.status_color}">
                    ${alert.status_text}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${alert.time}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${alert.server}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${alert.type === 'critical' ? 'حرج' : alert.type === 'warning' ? 'تحذير' : 'معلومات'}
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${alert.severity_color}">
                    ${alert.severity_text}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${alert.description}
            </td>
            <td class="px-6 py-4 text-right font-semibold">
                ${alert.title}
            </td>
        </tr>
    `).join('');
}

// التنقل بين الأقسام
function navigateTo(section) {
    currentSection = section;
    
    // إخفاء جميع الأقسام
    document.querySelectorAll('.section-content').forEach(s => s.classList.add('hidden'));
    
    // إظهار القسم الحالي
    document.getElementById('section-' + section).classList.remove('hidden');
    
    // تحديث التنقل النشط
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.section === section) {
            item.classList.add('active');
        }
    });
    
    // تحديث عنوان الصفحة
    const titles = {
        'dashboard': 'لوحة المراقبة الحية',
        'alerts': 'نظام التنبيهات الفورية',
        'reports': 'تقارير الأداء اليومية',
        'policies': 'إدارة السياسات الأمنية',
        'threats': 'تحليل التهديدات',
        'statistics': 'إحصاءات الأمان',
        'servers': 'مراقبة الخوادم',
        'logs': 'سجلات الأحداث',
        'incidents': 'إدارة الحوادث'
    };
    
    document.getElementById('page-title').textContent = titles[section] || 'لوحة المراقبة';
    
    // تحميل بيانات القسم
    loadSectionData(section);
}

// تحميل بيانات القسم
function loadSectionData(section) {
    switch(section) {
        case 'alerts':
            loadAlertsData();
            break;
        case 'threats':
            loadThreatsData();
            break;
        case 'servers':
            loadServersData();
            break;
        case 'logs':
            loadLogsData();
            break;
        case 'incidents':
            loadIncidentsData();
            break;
        case 'reports':
            loadReportsData();
            break;
        case 'policies':
            loadPoliciesData();
            break;
    }
}

// دوال التحكم
function toggleMonitoring() {
    isMonitoringActive = !isMonitoringActive;
    const button = document.querySelector('#monitoring-status');
    
    if (button) {
        if (isMonitoringActive) {
            button.textContent = 'المراقبة نشطة';
            button.parentElement.classList.add('cyber-glow');
            startLiveUpdates();
            showNotification('تم تفعيل المراقبة الحية', 'success');
        } else {
            button.textContent = 'المراقبة متوقفة';
            button.parentElement.classList.remove('cyber-glow');
            if (dashboardInterval) {
                clearInterval(dashboardInterval);
                dashboardInterval = null;
            }
            showNotification('تم إيقاف المراقبة الحية', 'warning');
        }
    }
}

async function runSecurityScan() {
    showLoading();
    
    try {
        // محاكاة فحص أمني
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        showNotification('اكتمل الفحص الأمني - لا توجد ثغرات حرجة', 'success');
    } catch (error) {
        showNotification('فشل الفحص الأمني', 'error');
    } finally {
        hideLoading();
    }
}

function generateDailyReport() {
    showNotification('جاري إنشاء التقرير اليومي...', 'info');
    setTimeout(() => {
        showNotification('تم إنشاء التقرير اليومي بنجاح', 'success');
    }, 1500);
}

// دوال التنبيهات
async function acknowledgeAlert(alertId) {
    try {
        const response = await fetch('/api/update_alert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                alert_id: alertId,
                status: 'acknowledged',
                user_id: currentUser.id
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم تأكيد التنبيه', 'success');
            if (currentSection === 'alerts') {
                loadAlertsData();
            } else {
                loadDashboardData();
            }
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تأكيد التنبيه', 'error');
    }
}

async function resolveAlert(alertId) {
    try {
        const response = await fetch('/api/update_alert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                alert_id: alertId,
                status: 'resolved',
                user_id: currentUser.id
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم حل التنبيه', 'success');
            if (currentSection === 'alerts') {
                loadAlertsData();
            } else {
                loadDashboardData();
            }
            closeAlertDetails();
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل حل التنبيه', 'error');
    }
}

function acknowledgeAllAlerts() {
    showNotification('جاري تأكيد جميع التنبيهات...', 'info');
    setTimeout(() => {
        showNotification('تم تأكيد جميع التنبيهات', 'success');
        if (currentSection === 'alerts') {
            loadAlertsData();
        }
    }, 1000);
}

function filterAlerts(filter) {
    loadAlertsData(filter);
    showNotification(`تصفية التنبيهات حسب: ${filter === 'critical' ? 'الحرجة' : filter === 'warning' ? 'التحذيرية' : 'المعلومات'}`, 'info');
}

// دوال التهديدات
async function loadThreatsData(filter = 'all') {
    showLoading();
    
    try {
        const response = await fetch(`/api/get_threats.php?filter=${filter}`);
        const result = await response.json();
        
        if (result.success) {
            updateThreatsUI(result.data);
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تحميل بيانات التهديدات', 'error');
    } finally {
        hideLoading();
    }
}

function updateThreatsUI(data) {
    // تحديث إحصائيات التهديدات
    const stats = data.statistics;
    document.querySelector('#section-threats .text-red-400').textContent = stats.ddos || 0;
    document.querySelector('#section-threats .text-yellow-400').textContent = stats.brute_force || 0;
    document.querySelector('#section-threats .text-blue-400').textContent = stats.sql_injection || 0;
    document.querySelector('#section-threats .text-purple-400').textContent = stats.xss || 0;
    
    // تحديث جدول التهديدات
    const tbody = document.getElementById('threats-table');
    if (!tbody) return;
    
    if (data.threats.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-400">
                    لا توجد تهديدات حالياً
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = data.threats.map(threat => `
        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="viewThreatDetails(${threat.id})" class="text-blue-400 hover:text-blue-300" title="تحليل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </button>
                    <button onclick="mitigateThreat(${threat.id})" class="text-green-400 hover:text-green-300" title="تخفيف">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${threat.status === 'active' ? 'bg-red-500' : 'bg-green-500'}">
                    ${threat.status === 'active' ? 'نشط' : 'تم التخفيف'}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${threat.last_seen}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${threat.source_ip}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${threat.target}
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${threat.severity_color}">
                    ${threat.severity_text}
                </span>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${threat.type_color}">
                    ${threat.type_text}
                </span>
            </td>
            <td class="px-6 py-4 text-right font-semibold">
                ${threat.name}
            </td>
        </tr>
    `).join('');
}

function filterThreats(filter) {
    loadThreatsData(filter);
    showNotification(`تصفية التهديدات حسب: ${filter === 'active' ? 'النشطة' : 'التي تم التخفيف منها'}`, 'info');
}

function runThreatAnalysis() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('اكتمل تحليل التهديدات - تم اكتشاف 3 تهديدات جديدة', 'info');
    }, 2000);
}

// دوال الخوادم
async function loadServersData() {
    showLoading();
    
    try {
        const response = await fetch('/api/get_servers.php');
        const result = await response.json();
        
        if (result.success) {
            updateServersFullUI(result.data);
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تحميل بيانات الخوادم', 'error');
    } finally {
        hideLoading();
    }
}

function updateServersFullUI(data) {
    const container = document.querySelector('#section-servers .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3');
    const tbody = document.getElementById('servers-table');
    
    if (!container || !tbody) return;
    
    // تحديث شبكة الخوادم
    container.innerHTML = data.servers.map(server => {
        const statusColor = server.status === 'online' ? 'text-green-400' : 
                           server.status === 'warning' ? 'text-yellow-400' : 'text-red-400';
        
        return `
            <div class="server-status-${server.status} cyber-border bg-slate-900 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-lg ${statusColor}">${server.name}</h4>
                        <p class="text-sm text-gray-400">${server.type} - ${server.location}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400">${server.ip_address}</p>
                        <p class="text-xs text-gray-400">${date('H:i', strtotime(${server.last_check}))}</p>
                    </div>
                </div>
                
                <div class="space-y-3 mb-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-400">وحدة المعالجة المركزية</span>
                            <span class="text-sm ${server.cpu_usage > 80 ? 'text-red-400' : 'text-green-400'}">${server.cpu_usage}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${server.cpu_usage}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-400">الذاكرة</span>
                            <span class="text-sm ${server.memory_usage > 80 ? 'text-red-400' : 'text-green-400'}">${server.memory_usage}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${server.memory_usage}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-400">التخزين</span>
                            <span class="text-sm ${server.storage_usage > 90 ? 'text-red-400' : 'text-green-400'}">${server.storage_usage}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${server.storage_usage}%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <button onclick="restartServer(${server.id})" class="text-xs text-blue-400 hover:text-blue-300">
                        إعادة تشغيل
                    </button>
                    <button onclick="viewServerDetails(${server.id})" class="text-xs text-green-400 hover:text-green-300">
                        تفاصيل
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    // تحديث جدول الخوادم
    tbody.innerHTML = data.servers.map(server => {
        const statusColor = server.status === 'online' ? 'bg-green-500' : 
                           server.status === 'warning' ? 'bg-yellow-500' : 'bg-red-500';
        const statusText = server.status === 'online' ? 'نشط' : 
                          server.status === 'warning' ? 'تحذير' : 'غير نشط';
        
        return `
            <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button onclick="viewServerDetails(${server.id})" class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="restartServer(${server.id})" class="text-green-400 hover:text-green-300" title="إعادة تشغيل">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="px-6 py-4 text-right">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColor}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4 text-right text-gray-300">
                    ${new Date(server.last_check).toLocaleTimeString('ar-SA')}
                </td>
                <td class="px-6 py-4 text-right text-gray-300">
                    ${server.storage_usage}%
                </td>
                <td class="px-6 py-4 text-right text-gray-300">
                    ${server.memory_usage}%
                </td>
                <td class="px-6 py-4 text-right text-gray-300">
                    ${server.cpu_usage}%
                </td>
                <td class="px-6 py-4 text-right text-gray-300">
                    ${server.type}
                </td>
                <td class="px-6 py-4 text-right font-semibold">
                    ${server.name}
                </td>
            </tr>
        `;
    }).join('');
}

function refreshServerStatus() {
    loadServersData();
    showNotification('تم تحديث حالة الخوادم', 'success');
}

function restartServer(serverId) {
    if (confirm('هل أنت متأكد من إعادة تشغيل هذا الخادم؟')) {
        showNotification(`جاري إعادة تشغيل الخادم...`, 'info');
        setTimeout(() => {
            showNotification(`تم إعادة تشغيل الخادم بنجاح`, 'success');
            loadServersData();
        }, 2000);
    }
}

// دوال السجلات
async function loadLogsData() {
    showLoading();
    
    try {
        const type = document.getElementById('log-type')?.value || '';
        const level = document.getElementById('log-level')?.value || '';
        const from = document.getElementById('log-from')?.value || '';
        const to = document.getElementById('log-to')?.value || '';
        
        const url = `/api/get_logs.php?type=${type}&level=${level}&from=${from}&to=${to}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            updateLogsUI(result.data);
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تحميل السجلات', 'error');
    } finally {
        hideLoading();
    }
}

function updateLogsUI(logs) {
    const tbody = document.getElementById('logs-table');
    if (!tbody) return;
    
    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-8 text-gray-400">
                    لا توجد سجلات حالياً
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = logs.map(log => `
        <tr class="log-type-${log.type} border-b border-slate-700 hover:bg-slate-900 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="viewLogDetails(${log.id})" class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${log.level === 'error' ? 'bg-red-500' : log.level === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'}">
                    ${log.level_text}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${log.time}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${log.source}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${log.user}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${log.event_type}
            </td>
            <td class="px-6 py-4 text-right font-semibold">
                ${log.description}
            </td>
        </tr>
    `).join('');
}

function searchLogs() {
    loadLogsData();
    showNotification('جاري البحث في السجلات...', 'info');
}

function exportLogs() {
    showNotification('جاري تصدير السجلات...', 'info');
}

// دوال الحوادث
async function loadIncidentsData(filter = 'all') {
    showLoading();
    
    try {
        const response = await fetch(`/api/get_incidents.php?filter=${filter}`);
        const result = await response.json();
        
        if (result.success) {
            updateIncidentsUI(result.data);
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تحميل بيانات الحوادث', 'error');
    } finally {
        hideLoading();
    }
}

function updateIncidentsUI(data) {
    // تحديث إحصائيات الحوادث
    const stats = data.statistics;
    document.querySelector('#section-incidents .text-red-400').textContent = stats.open || 0;
    document.querySelector('#section-incidents .text-yellow-400').textContent = stats.in_progress || 0;
    document.querySelector('#section-incidents .text-blue-400').textContent = stats.resolved_this_month || 0;
    document.querySelector('#section-incidents .text-green-400').textContent = stats.avg_resolution_hours || 0;
    
    // تحديث جدول الحوادث
    const tbody = document.getElementById('incidents-table');
    if (!tbody) return;
    
    if (data.incidents.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-400">
                    لا توجد حوادث حالياً
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = data.incidents.map(incident => `
        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="viewIncidentDetails(${incident.id})" class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    incident.status === 'open' ? 'bg-red-500' : 
                    incident.status === 'in-progress' ? 'bg-yellow-500' : 'bg-green-500'
                }">
                    ${incident.status === 'open' ? 'مفتوح' : 
                      incident.status === 'in-progress' ? 'قيد المعالجة' : 'محلول'}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${incident.last_update}
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${incident.severity_color}">
                    ${incident.severity_text}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${incident.assigned_to}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${incident.type}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${incident.impact}
            </td>
            <td class="px-6 py-4 text-right font-semibold">
                ${incident.name}
            </td>
        </tr>
    `).join('');
}

function filterIncidents(filter) {
    loadIncidentsData(filter);
    showNotification(`تصفية الحوادث حسب: ${filter === 'open' ? 'المفتوحة' : filter === 'in-progress' ? 'قيد المعالجة' : 'المحلولة'}`, 'info');
}

function createIncident() {
    showNotification('فتح نافذة تسجيل حادث جديد', 'info');
}

// دوال التقارير
async function loadReportsData() {
    showLoading();
    
    try {
        const response = await fetch('/api/get_reports.php');
        const result = await response.json();
        
        if (result.success) {
            updateReportsUI(result.data);
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تحميل التقارير', 'error');
    } finally {
        hideLoading();
    }
}

function updateReportsUI(reports) {
    const tbody = document.getElementById('reports-table');
    if (!tbody) return;
    
    if (reports.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-400">
                    لا توجد تقارير حالياً
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = reports.map(report => `
        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="viewReport(${report.id})" class="text-blue-400 hover:text-blue-300" title="عرض">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="downloadReport(${report.id})" class="text-green-400 hover:text-green-300" title="تحميل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    report.status === 'published' ? 'bg-green-500' : 'bg-yellow-500'
                }">
                    ${report.status === 'published' ? 'منشور' : 'مسودة'}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${new Date(report.generated_at).toLocaleDateString('ar-SA')}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${report.type === 'security' ? 'أمان' : 
                  report.type === 'performance' ? 'أداء' : 
                  report.type === 'network' ? 'شبكة' : 'حوادث'}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${report.period === 'daily' ? 'يومي' : 
                  report.period === 'weekly' ? 'أسبوعي' : 'شهري'}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${report.statistics ? JSON.parse(report.statistics).total_attacks || 0 : 0}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${report.generated_by_name}
            </td>
            <td class="px-6 py-4 text-right font-semibold">
                ${report.name}
            </td>
        </tr>
    `).join('');
}

// دوال السياسات
async function loadPoliciesData() {
    showLoading();
    
    try {
        const response = await fetch('/api/get_policies.php');
        const result = await response.json();
        
        if (result.success) {
            updatePoliciesUI(result.data);
        }
    } catch (error) {
        console.error('خطأ:', error);
        showNotification('فشل تحميل السياسات', 'error');
    } finally {
        hideLoading();
    }
}

function updatePoliciesUI(data) {
    // تحديث إحصائيات السياسات
    const stats = data.statistics;
    document.querySelector('#section-policies .text-red-400').textContent = stats.active || 0;
    document.querySelector('#section-policies .text-green-400').textContent = stats.compliant || 0;
    document.querySelector('#section-policies .text-yellow-400').textContent = stats.needs_review || 0;
    
    // تحديث جدول السياسات
    const tbody = document.getElementById('policies-table');
    if (!tbody) return;
    
    if (data.policies.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-400">
                    لا توجد سياسات حالياً
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = data.policies.map(policy => `
        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="viewPolicy(${policy.id})" class="text-blue-400 hover:text-blue-300" title="عرض">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="editPolicy(${policy.id})" class="text-green-400 hover:text-green-300" title="تعديل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">
                    ${policy.status}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${new Date(policy.created_at).toLocaleDateString('ar-SA')}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${policy.compliance_percentage}%
            </td>
            <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    policy.priority === 'high' ? 'bg-red-500' : 
                    policy.priority === 'medium' ? 'bg-yellow-500' : 'bg-blue-500'
                }">
                    ${policy.priority === 'high' ? 'عالي' : 
                      policy.priority === 'medium' ? 'متوسط' : 'منخفض'}
                </span>
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${policy.scope}
            </td>
            <td class="px-6 py-4 text-right text-gray-300">
                ${policy.created_by_name}
            </td>
            <td class="px-6 py-4 text-right font-semibold">
                ${policy.name}
            </td>
        </tr>
    `).join('');
}

// دوال مساعدة
function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
    document.getElementById('loading-spinner').classList.add('flex');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
    document.getElementById('loading-spinner').classList.remove('flex');
}

function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function viewAlertDetails(alertId) {
    showNotification(`جاري عرض تفاصيل التنبيه ${alertId}`, 'info');
}

function closeAlertDetails() {
    document.getElementById('alert-details-modal')?.classList.add('hidden');
    document.getElementById('alert-details-modal')?.classList.remove('flex');
}

function viewServerDetails(serverId) {
    showNotification(`جاري عرض تفاصيل الخادم ${serverId}`, 'info');
}

function viewThreatDetails(threatId) {
    showNotification(`جاري عرض تفاصيل التهديد ${threatId}`, 'info');
}

function mitigateThreat(threatId) {
    showNotification(`جاري تخفيف التهديد ${threatId}`, 'info');
}

function viewLogDetails(logId) {
    showNotification(`جاري عرض تفاصيل السجل ${logId}`, 'info');
}

function viewIncidentDetails(incidentId) {
    showNotification(`جاري عرض تفاصيل الحادث ${incidentId}`, 'info');
}

function viewReport(reportId) {
    showNotification(`جاري عرض التقرير ${reportId}`, 'info');
}

function downloadReport(reportId) {
    showNotification(`جاري تحميل التقرير ${reportId}`, 'info');
}

function viewPolicy(policyId) {
    showNotification(`جاري عرض السياسة ${policyId}`, 'info');
}

function editPolicy(policyId) {
    showNotification(`جاري تعديل السياسة ${policyId}`, 'info');
}

function addAlertRule() {
    showNotification('فتح نافذة إضافة قاعدة تنبيه جديدة', 'info');
}

function addNewPolicy() {
    showNotification('فتح نافذة إضافة سياسة جديدة', 'info');
}

function importPolicies() {
    showNotification('جاري استيراد السياسات', 'info');
}

function generateDailySecurityReport() {
    showNotification('جاري إنشاء تقرير الأمان اليومي', 'info');
}

function exportAllReports() {
    showNotification('جاري تصدير جميع التقارير', 'info');
}

function viewSecurityReport() {
    navigateTo('reports');
    showNotification('عرض تقرير الأمان', 'info');
}

function viewPerformanceReport() {
    showNotification('عرض تقرير الأداء', 'info');
}

function viewNetworkReport() {
    showNotification('عرض تقرير الشبكة', 'info');
}

function viewIncidentReport() {
    navigateTo('incidents');
    showNotification('عرض تقرير الحوادث', 'info');
}

function generateStatisticsReport() {
    showNotification('جاري إنشاء تقرير إحصائي', 'info');
}

function filterStatistics(filter) {
    showNotification(`تصفية الإحصاءات حسب: ${filter}`, 'info');
}

function restartAllServers() {
    if (confirm('هل أنت متأكد من إعادة تشغيل جميع الخوادم؟')) {
        showNotification('جاري إعادة تشغيل الخوادم...', 'warning');
    }
}

function clearOldLogs() {
    if (confirm('هل أنت متأكد من مسح السجلات القديمة؟')) {
        showNotification('جاري مسح السجلات القديمة...', 'warning');
    }
}

function toggleLiveLogs() {
    // يمكن إضافة منطق إيقاف/تشغيل السجلات المباشرة لاحقاً
    showNotification('تم تبديل حالة السجلات المباشرة', 'info');
}