<?php
// pages/alerts.php - نظام التنبيهات الفورية (نسخة مصححة)
$db = getDB();

// =============================================
// 1. إحصائيات التنبيهات
// =============================================
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'critical' AND status != 'resolved' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN type = 'warning' AND status != 'resolved' THEN 1 ELSE 0 END) as warning,
        SUM(CASE WHEN type = 'info' AND status != 'resolved' THEN 1 ELSE 0 END) as info,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM alerts
    WHERE DATE(created_at) = CURDATE()
")->fetch();

// 2. قواعد التنبيهات
$rules = [];
try {
    $rules = $db->query("
        SELECT * FROM alert_rules 
        WHERE is_active = 1 
        ORDER BY 
            CASE severity
                WHEN 'critical' THEN 1
                WHEN 'warning' THEN 2
                WHEN 'info' THEN 3
                ELSE 4
            END
    ")->fetchAll();
} catch (PDOException $e) {
    // جدول القواعد غير موجود، نستخدم مصفوفة فارغة
    $rules = [];
}

// 3. جميع التنبيهات مع تفاصيلها (بدون الأعمدة غير الموجودة)
$alerts = $db->query("
    SELECT a.*, 
           s.name as server_name
    FROM alerts a
    LEFT JOIN servers s ON a.server_id = s.id
    ORDER BY 
        CASE a.status
            WHEN 'new' THEN 1
            WHEN 'in-progress' THEN 2
            WHEN 'acknowledged' THEN 3
            WHEN 'resolved' THEN 4
        END,
        a.created_at DESC
")->fetchAll();

// 4. إحصائيات إضافية للأيام السابقة
$weekly_stats = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN type = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN type = 'warning' THEN 1 ELSE 0 END) as warning
    FROM alerts
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
")->fetchAll();

// 5. أكثر الخوادم تعرضاً للتنبيهات
$top_servers = $db->query("
    SELECT 
        s.name,
        s.status,
        COUNT(a.id) as alert_count,
        SUM(CASE WHEN a.type = 'critical' THEN 1 ELSE 0 END) as critical_count
    FROM servers s
    LEFT JOIN alerts a ON s.id = a.server_id AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY s.id
    HAVING alert_count > 0
    ORDER BY critical_count DESC, alert_count DESC
    LIMIT 5
")->fetchAll();

// دوال مساعدة إضافية
function getAlertTypeIcon($type) {
    return match($type) {
        'critical' => '🔴',
        'warning' => '🟡',
        'info' => '🔵',
        default => '⚪'
    };
}

function getAlertStatusBadge($status) {
    $colors = [
        'new' => 'bg-red-500',
        'acknowledged' => 'bg-yellow-500',
        'in-progress' => 'bg-blue-500',
        'resolved' => 'bg-green-500'
    ];
    $texts = [
        'new' => 'جديد',
        'acknowledged' => 'مؤكد',
        'in-progress' => 'قيد المعالجة',
        'resolved' => 'تم الحل'
    ];
    $color = $colors[$status] ?? 'bg-gray-500';
    $text = $texts[$status] ?? $status;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="acknowledgeAllAlerts()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    تأكيد جميع التنبيهات
                </button>
                <div class="flex bg-slate-900 rounded-lg p-1">
                    <button onclick="filterAlerts('all')" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all filter-btn active" data-filter="all">
                        الكل
                    </button>
                    <button onclick="filterAlerts('critical')" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all filter-btn" data-filter="critical">
                        حرجة
                    </button>
                    <button onclick="filterAlerts('warning')" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all filter-btn" data-filter="warning">
                        تحذير
                    </button>
                    <button onclick="filterAlerts('info')" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all filter-btn" data-filter="info">
                        معلومات
                    </button>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">⚠️</span> نظام التنبيهات الفورية
            </h1>
        </div>

        <!-- إحصائيات سريعة متحركة -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-slate-900 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-blue-400 mb-2"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">إجمالي اليوم</div>
            </div>
            <div class="critical-alert bg-red-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-red-400 mb-2"><?php echo $stats['critical'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">حرجة</div>
            </div>
            <div class="warning-alert bg-yellow-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-yellow-400 mb-2"><?php echo $stats['warning'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">تحذير</div>
            </div>
            <div class="info-alert bg-blue-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-blue-400 mb-2"><?php echo $stats['info'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">معلومات</div>
            </div>
            <div class="bg-green-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-green-400 mb-2"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">تم الحل</div>
            </div>
        </div>

        <!-- شريط التقدم النسبي -->
        <?php if (($stats['total'] ?? 0) > 0): ?>
        <div class="mt-6 p-4 bg-slate-900 rounded-lg">
            <div class="flex justify-between mb-2 text-sm">
                <span class="text-gray-400">توزيع التنبيهات</span>
                <span class="text-green-400"><?php echo $stats['total'] ?? 0; ?> تنبيه</span>
            </div>
            <div class="flex h-4 rounded-lg overflow-hidden">
                <?php 
                $total = max(1, $stats['total']);
                $critical_width = (($stats['critical'] ?? 0) / $total) * 100;
                $warning_width = (($stats['warning'] ?? 0) / $total) * 100;
                $info_width = (($stats['info'] ?? 0) / $total) * 100;
                ?>
                <div class="bg-red-500 h-full" style="width: <?php echo $critical_width; ?>%" title="حرجة: <?php echo $stats['critical'] ?? 0; ?>"></div>
                <div class="bg-yellow-500 h-full" style="width: <?php echo $warning_width; ?>%" title="تحذير: <?php echo $stats['warning'] ?? 0; ?>"></div>
                <div class="bg-blue-500 h-full" style="width: <?php echo $info_width; ?>%" title="معلومات: <?php echo $stats['info'] ?? 0; ?>"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- قواعد التنبيهات (تظهر فقط إذا كان الجدول موجوداً) -->
    <?php if (!empty($rules)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <button onclick="addAlertRule()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                إضافة قاعدة جديدة
            </button>
            <h2 class="text-xl font-bold text-right text-green-400">📋 قواعد التنبيهات النشطة</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($rules as $rule): ?>
            <div class="bg-slate-900 rounded-lg p-4 border-r-4 
                <?php echo $rule['severity'] == 'critical' ? 'border-red-500' : ($rule['severity'] == 'warning' ? 'border-yellow-500' : 'border-blue-500'); ?>">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold"><?php echo $rule['name']; ?></span>
                    <span class="px-2 py-1 rounded-full text-xs 
                        <?php echo $rule['severity'] == 'critical' ? 'bg-red-500' : ($rule['severity'] == 'warning' ? 'bg-yellow-500' : 'bg-blue-500'); ?>">
                        <?php echo $rule['severity']; ?>
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-2"><?php echo $rule['description']; ?></p>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>القيمة: <?php echo $rule['threshold_value']; ?></span>
                    <span>المقارنة: <?php echo $rule['comparison_operator']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- جدول التنبيهات المفصل -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-blue-400">📊 سجل التنبيهات التفصيلي</h2>

        <!-- شريط البحث والتصفية -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="relative">
                    <input type="text" id="search-alerts" placeholder="بحث في التنبيهات..." 
                           class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="status-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الحالات</option>
                    <option value="new">جديد</option>
                    <option value="acknowledged">مؤكد</option>
                    <option value="in-progress">قيد المعالجة</option>
                    <option value="resolved">تم الحل</option>
                </select>
            </div>
            <button onclick="exportAlerts()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                </svg>
                تصدير التقرير
            </button>
        </div>

        <!-- الجدول الرئيسي -->
        <div class="overflow-x-auto">
            <table class="w-full" id="alerts-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                        <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                        <th class="px-6 py-4 text-sm font-semibold">وقت الحدوث</th>
                        <th class="px-6 py-4 text-sm font-semibold">المصدر</th>
                        <th class="px-6 py-4 text-sm font-semibold">الخادم</th>
                        <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                        <th class="px-6 py-4 text-sm font-semibold">الشدة</th>
                        <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                        <th class="px-6 py-4 text-sm font-semibold">عنوان التنبيه</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $alert): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors alert-row" 
                        data-type="<?php echo $alert['type']; ?>" 
                        data-status="<?php echo $alert['status']; ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button onclick="viewAlertDetails(<?php echo $alert['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300 transition-all transform hover:scale-110" 
                                        title="عرض التفاصيل">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <?php if ($alert['status'] == 'new'): ?>
                                <button onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)" 
                                        class="text-yellow-400 hover:text-yellow-300 transition-all transform hover:scale-110" 
                                        title="تأكيد التنبيه">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php if ($alert['status'] != 'resolved'): ?>
                                <button onclick="resolveAlert(<?php echo $alert['id']; ?>)" 
                                        class="text-green-400 hover:text-green-300 transition-all transform hover:scale-110" 
                                        title="تحديد كحل">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php echo getAlertStatusBadge($alert['status']); ?>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-300">
                            <div><?php echo formatTimeAgo($alert['created_at']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo date('Y-m-d H:i', strtotime($alert['created_at'])); ?></div>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-300">
                            <?php echo $alert['source']; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($alert['server_name']): ?>
                            <span class="px-2 py-1 bg-slate-700 rounded-lg text-xs">
                                <?php echo $alert['server_name']; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="flex items-center">
                                <span class="ml-1"><?php echo getAlertTypeIcon($alert['type']); ?></span>
                                <span><?php echo $alert['type'] == 'critical' ? 'حرج' : ($alert['type'] == 'warning' ? 'تحذير' : 'معلومات'); ?></span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                <?php echo $alert['severity'] == 'high' ? 'bg-red-500' : ($alert['severity'] == 'medium' ? 'bg-yellow-500' : 'bg-blue-500'); ?>">
                                <?php echo $alert['severity'] == 'high' ? 'عالية' : ($alert['severity'] == 'medium' ? 'متوسطة' : 'منخفضة'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-300 max-w-xs truncate">
                            <?php echo $alert['description']; ?>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-green-400">
                            <?php echo $alert['title']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- إحصائيات إضافية -->
        <?php if (!empty($weekly_stats)): ?>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- رسم بياني بسيط لآخر 7 أيام -->
            <div class="bg-slate-900 rounded-lg p-6">
                <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">نشاط التنبيهات - آخر 7 أيام</h3>
                <div class="space-y-3">
                    <?php foreach ($weekly_stats as $day): ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400"><?php echo date('Y-m-d', strtotime($day['date'])); ?></span>
                            <span class="text-gray-300"><?php echo $day['total']; ?> تنبيه</span>
                        </div>
                        <div class="flex h-2 rounded-full overflow-hidden">
                            <div class="bg-red-500 h-full" style="width: <?php echo ($day['critical'] / max(1, $day['total'])) * 100; ?>%"></div>
                            <div class="bg-yellow-500 h-full" style="width: <?php echo ($day['warning'] / max(1, $day['total'])) * 100; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- أكثر الخوادم تضرراً -->
            <?php if (!empty($top_servers)): ?>
            <div class="bg-slate-900 rounded-lg p-6">
                <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">أكثر الخوادم تعرضاً للتنبيهات</h3>
                <div class="space-y-4">
                    <?php foreach ($top_servers as $server): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="status-indicator <?php echo getStatusIndicator($server['status']); ?> ml-2"></span>
                            <span class="text-sm"><?php echo $server['name']; ?></span>
                        </div>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <span class="px-2 py-1 bg-red-500 rounded-full text-xs"><?php echo $server['critical_count']; ?></span>
                            <span class="px-2 py-1 bg-slate-700 rounded-full text-xs"><?php echo $server['alert_count']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- نافذة تفاصيل التنبيه المنبثقة -->
<div id="alert-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAlertDetails()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-red-400">🔍 تفاصيل التنبيه</h3>
        </div>
        
        <div id="alert-details-content" class="space-y-6">
            <!-- سيتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<style>
/* تأثيرات إضافية */
.filter-btn {
    transition: all 0.3s ease;
}
.filter-btn.active {
    background-color: #10b981;
    color: white;
}
.filter-btn:hover:not(.active) {
    background-color: rgba(16, 185, 129, 0.2);
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.alert-row {
    transition: all 0.3s ease;
}
.alert-row:hover {
    background-color: rgba(30, 41, 59, 0.8);
}
</style>

<script>
// دوال JavaScript خاصة بصفحة التنبيهات
let currentAlertId = null;

function filterAlerts(type) {
    // تحديث الأزرار النشطة
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.filter === type) {
            btn.classList.add('active');
        }
    });

    // تصفية الصفوف
    const rows = document.querySelectorAll('.alert-row');
    rows.forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    showNotification(`تم تصفية التنبيهات: ${type === 'all' ? 'الكل' : type}`, 'info');
}

function acknowledgeAllAlerts() {
    if (confirm('هل أنت متأكد من تأكيد جميع التنبيهات الجديدة؟')) {
        showLoading();
        setTimeout(() => {
            hideLoading();
            showNotification('تم تأكيد جميع التنبيهات بنجاح', 'success');
            // هنا يمكن إضافة استدعاء API حقيقي
        }, 1500);
    }
}

function addAlertRule() {
    showNotification('جاري فتح نافذة إضافة قاعدة جديدة...', 'info');
}

function exportAlerts() {
    showNotification('جاري تحضير ملف التصدير...', 'info');
    setTimeout(() => {
        showNotification('تم تصدير التقرير بنجاح', 'success');
    }, 2000);
}

function viewAlertDetails(id) {
    currentAlertId = id;
    showLoading();
    
    // محاكاة جلب البيانات من API
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-900 rounded-lg">
                    <h4 class="text-lg font-semibold text-white mb-2">تفاصيل التنبيه #${id}</h4>
                    <p class="text-gray-300">${id === 1 ? 'ارتفاع استخدام المعالج' : 'تفاصيل التنبيه مع جميع المعلومات'}</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-900 rounded-lg">
                        <p class="text-sm text-gray-400">المصدر</p>
                        <p class="font-semibold text-blue-400">Web-Server-01</p>
                    </div>
                    <div class="p-3 bg-slate-900 rounded-lg">
                        <p class="text-sm text-gray-400">الوقت</p>
                        <p class="font-semibold text-green-400">منذ 15 دقيقة</p>
                    </div>
                    <div class="p-3 bg-slate-900 rounded-lg">
                        <p class="text-sm text-gray-400">النوع</p>
                        <p class="font-semibold text-red-400">حرج</p>
                    </div>
                    <div class="p-3 bg-slate-900 rounded-lg">
                        <p class="text-sm text-gray-400">الحالة</p>
                        <p class="font-semibold text-yellow-400">جديد</p>
                    </div>
                </div>

                <div class="p-4 bg-slate-900 rounded-lg">
                    <h5 class="text-md font-semibold text-white mb-3">الإجراءات الموصى بها</h5>
                    <ul class="space-y-2 text-right list-disc list-inside">
                        <li class="text-gray-300">فحص الخادم والتأكد من سلامته</li>
                        <li class="text-gray-300">مراجعة سجلات النظام</li>
                        <li class="text-gray-300">تطبيق التحديثات الأمنية اللازمة</li>
                        <li class="text-gray-300">مراقبة الأداء خلال الساعة القادمة</li>
                    </ul>
                </div>

                <div class="flex items-center space-x-4 space-x-reverse pt-4">
                    <button onclick="acknowledgeAlert(${id})" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all">
                        تأكيد
                    </button>
                    <button onclick="resolveAlert(${id})" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                        تم الحل
                    </button>
                    <button onclick="closeAlertDetails()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                        إغلاق
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById('alert-details-content').innerHTML = details;
        hideLoading();
        document.getElementById('alert-details-modal').classList.remove('hidden');
        document.getElementById('alert-details-modal').classList.add('flex');
    }, 1000);
}

function closeAlertDetails() {
    document.getElementById('alert-details-modal').classList.add('hidden');
    document.getElementById('alert-details-modal').classList.remove('flex');
}

function acknowledgeAlert(id) {
    showNotification(`تم تأكيد التنبيه رقم ${id}`, 'success');
    closeAlertDetails();
}

function resolveAlert(id) {
    showNotification(`تم حل التنبيه رقم ${id}`, 'success');
    closeAlertDetails();
}

// البحث المباشر في الجدول
document.getElementById('search-alerts')?.addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.alert-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// تصفية حسب الحالة
document.getElementById('status-filter')?.addEventListener('change', function(e) {
    const status = e.target.value;
    const rows = document.querySelectorAll('.alert-row');
    
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>