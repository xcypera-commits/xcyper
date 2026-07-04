<?php
// =============================================
// pentest-unit/pages/alerts.php
// صفحة التنبيهات الأمنية - بيانات حقيقية من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$filter = $_GET['filter'] ?? 'all';

try {
    // =============================================
    // 1. إحصائيات التنبيهات - من قاعدة البيانات
    // =============================================
    
    // إجمالي التنبيهات
    $stmt = $db->query("SELECT COUNT(*) FROM security_alerts");
    $total_alerts = $stmt->fetchColumn() ?: 0;
    
    // التنبيهات حسب النوع
    $type_counts = [
        'critical' => 0,
        'warning' => 0,
        'info' => 0
    ];
    
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM security_alerts GROUP BY type");
    while ($row = $stmt->fetch()) {
        $type_counts[$row['type']] = $row['count'];
    }
    
    // التنبيهات حسب الحالة
    $status_counts = [
        'new' => 0,
        'read' => 0,
        'in-progress' => 0,
        'resolved' => 0,
        'ignored' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM security_alerts GROUP BY status");
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // التنبيهات غير المقروءة
    $stmt = $db->query("SELECT COUNT(*) FROM security_alerts WHERE is_read = false");
    $unread_count = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. جميع التنبيهات مع التفاصيل - من قاعدة البيانات
    // =============================================
    
    $sql = "SELECT 
                a.*,
                p.project_name,
                v.name as vulnerability_name,
                v.severity as vulnerability_severity,
                s.scan_name
            FROM security_alerts a
            LEFT JOIN pentest_projects p ON a.project_id = p.id
            LEFT JOIN vulnerabilities v ON a.vulnerability_id = v.id
            LEFT JOIN security_scans s ON a.scan_id = s.id
            WHERE 1=1";
    
    if ($filter !== 'all') {
        $sql .= " AND a.type = '$filter'";
    }
    
    $sql .= " ORDER BY 
                CASE a.type
                    WHEN 'critical' THEN 1
                    WHEN 'warning' THEN 2
                    WHEN 'info' THEN 3
                END,
                a.created_at DESC";
    
    $alerts = $db->query($sql)->fetchAll();
    
    // =============================================
    // 3. التنبيهات الحرجة غير المعالجة - من قاعدة البيانات
    // =============================================
    
    $critical_alerts = $db->query("
        SELECT a.*, p.project_name
        FROM security_alerts a
        LEFT JOIN pentest_projects p ON a.project_id = p.id
        WHERE a.type = 'critical' AND a.status != 'resolved'
        ORDER BY a.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 4. إحصائيات التنبيهات حسب اليوم - آخر 7 أيام
    // =============================================
    
    $daily_stats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily_stats[$date] = [
            'date' => $date,
            'critical' => 0,
            'warning' => 0,
            'info' => 0,
            'total' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE(created_at) as alert_date,
            type,
            COUNT(*) as count
        FROM security_alerts
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at), type
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($daily_stats[$row['alert_date']])) {
            $daily_stats[$row['alert_date']][$row['type']] = $row['count'];
            $daily_stats[$row['alert_date']]['total'] += $row['count'];
        }
    }
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getAlertTypeBadge($type) {
    return match($type) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'warning' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">تحذير</span>',
        'info' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">معلومات</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getAlertStatusBadge($status) {
    return match($status) {
        'new' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">جديد</span>',
        'read' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">مقروء</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">قيد المعالجة</span>',
        'resolved' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تم الحل</span>',
        'ignored' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">متجاهل</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}



function getAlertTextColor($type) {
    return match($type) {
        'critical' => 'text-red-400',
        'warning' => 'text-yellow-400',
        'info' => 'text-blue-400',
        default => 'text-gray-400'
    };
}


?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-orange-300">
            <i class="fas fa-bell ml-2"></i>
            التنبيهات الأمنية
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="markAllAsRead()" class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-check-double ml-2"></i>
                تعليم الكل مقروء
            </button>
            <button onclick="refreshAlerts()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-200 text-sm mb-1">تنبيهات حرجة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $type_counts['critical']; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                تحتاج تدخل فوري
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">تنبيهات تحذير</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $type_counts['warning']; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                تحتاج متابعة
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">تنبيهات معلومات</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $type_counts['info']; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-info-circle text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                معلومات عامة
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">غير مقروءة</p>
                    <p class="text-3xl font-bold text-purple-400"><?php echo $unread_count; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                تحتاج مراجعة
            </div>
        </div>
    </div>

    <!-- أزرار التصفية -->
    <div class="flex items-center justify-between mt-4">
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="filterAlerts('all')" class="px-4 py-2 <?php echo $filter == 'all' ? 'bg-yellow-600' : 'bg-slate-700'; ?> hover:bg-yellow-700 rounded-lg text-sm font-semibold transition-all">
                الكل
            </button>
            <button onclick="filterAlerts('critical')" class="px-4 py-2 <?php echo $filter == 'critical' ? 'bg-red-600' : 'bg-slate-700'; ?> hover:bg-red-700 rounded-lg text-sm font-semibold transition-all">
                حرجة
            </button>
            <button onclick="filterAlerts('warning')" class="px-4 py-2 <?php echo $filter == 'warning' ? 'bg-yellow-600' : 'bg-slate-700'; ?> hover:bg-yellow-700 rounded-lg text-sm font-semibold transition-all">
                تحذير
            </button>
            <button onclick="filterAlerts('info')" class="px-4 py-2 <?php echo $filter == 'info' ? 'bg-blue-600' : 'bg-slate-700'; ?> hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all">
                معلومات
            </button>
        </div>
        <div class="text-sm text-gray-400">
            إجمالي: <?php echo count($alerts); ?> تنبيه
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني لآخر 7 أيام -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        نشاط التنبيهات - آخر 7 أيام
    </h3>
    
    <div class="h-64 relative" id="alerts-chart-container">
        <canvas id="alertsChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">حرجة</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-yellow-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تحذير</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">معلومات</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- التنبيهات الحرجة غير المعالجة -->
<!-- ============================================= -->
<?php if (!empty($critical_alerts)): ?>
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-red-300 flex items-center">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            تنبيهات حرجة - تحتاج للتدخل الفوري
        </h3>
        <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo count($critical_alerts); ?></span>
    </div>
    
    <div class="space-y-4">
        <?php foreach ($critical_alerts as $alert): ?>
        <div class="p-4 bg-red-900 bg-opacity-20 rounded-lg border border-red-800 alert-critical">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full ml-2 blink"></div>
                    <p class="font-semibold text-red-400"><?php echo $alert['title']; ?></p>
                </div>
                <span class="text-xs text-gray-400"><?php echo formatTimeAgo($alert['created_at']); ?></span>
            </div>
            <p class="text-sm text-gray-300 mb-3"><?php echo $alert['description']; ?></p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">المشروع: <?php echo $alert['project_name'] ?? 'عام'; ?></span>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="handleAlert(<?php echo $alert['id']; ?>)" class="text-xs text-red-400 hover:text-red-300">
                        معالجة
                    </button>
                    <button onclick="markAsRead(<?php echo $alert['id']; ?>)" class="text-xs text-gray-400 hover:text-gray-300">
                        تجاهل
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- قائمة جميع التنبيهات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <h3 class="text-lg font-bold text-yellow-300 mb-4 flex items-center">
        <i class="fas fa-list ml-2"></i>
        جميع التنبيهات
    </h3>

    <?php if (empty($alerts)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد تنبيهات</p>
        <p class="text-sm text-gray-500 mt-2">كل الأنظمة تعمل بشكل طبيعي</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($alerts as $alert): ?>
        <div class="p-4 <?php echo getAlertTypeColor($alert['type']); ?> rounded-lg hover:bg-opacity-30 transition-colors <?php echo !$alert['is_read'] ? 'border-r-4 border-yellow-500' : ''; ?>">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <?php echo getAlertTypeBadge($alert['type']); ?>
                        <span class="text-xs text-gray-400 mr-2"><?php echo formatTimeAgo($alert['created_at']); ?></span>
                        <?php if (!$alert['is_read']): ?>
                        <span class="px-2 py-0.5 bg-yellow-600 rounded-full text-xs">جديد</span>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="font-semibold text-white mb-1"><?php echo $alert['title']; ?></h4>
                    <p class="text-sm text-gray-300 mb-3"><?php echo $alert['description']; ?></p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs mb-3">
                        <?php if ($alert['project_name']): ?>
                        <div>
                            <span class="text-gray-400">المشروع:</span>
                            <span class="text-blue-400 mr-1"><?php echo $alert['project_name']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($alert['vulnerability_name']): ?>
                        <div>
                            <span class="text-gray-400">الثغرة:</span>
                            <span class="text-red-400 mr-1"><?php echo $alert['vulnerability_name']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($alert['scan_name']): ?>
                        <div>
                            <span class="text-gray-400">الفحص:</span>
                            <span class="text-green-400 mr-1"><?php echo $alert['scan_name']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <span class="text-gray-400">المصدر:</span>
                            <span class="text-purple-400 mr-1"><?php echo $alert['source']; ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <?php echo getAlertStatusBadge($alert['status']); ?>
                        </div>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <?php if ($alert['status'] != 'resolved'): ?>
                            <button onclick="resolveAlert(<?php echo $alert['id']; ?>)" class="text-xs bg-green-600 hover:bg-green-700 px-3 py-1 rounded">
                                حل
                            </button>
                            <?php endif; ?>
                            <?php if (!$alert['is_read']): ?>
                            <button onclick="markAsRead(<?php echo $alert['id']; ?>)" class="text-xs bg-gray-600 hover:bg-gray-700 px-3 py-1 rounded">
                                تعليم كمقروء
                            </button>
                            <?php endif; ?>
                            <button onclick="viewAlertDetails(<?php echo $alert['id']; ?>)" class="text-xs bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">
                                تفاصيل
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- إحصائيات أسفل الصفحة -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($alerts); ?> تنبيه
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-600 rounded-full ml-1"></span>
                حرجة: <?php echo $type_counts['critical']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                تحذير: <?php echo $type_counts['warning']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                معلومات: <?php echo $type_counts['info']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-purple-500 rounded-full ml-1"></span>
                غير مقروء: <?php echo $unread_count; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل التنبيه -->
<!-- ============================================= -->
<div id="alert-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAlertModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-red-400" id="alert-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل التنبيه
            </h3>
        </div>
        <div id="alert-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة معالجة التنبيه -->
<!-- ============================================= -->
<div id="handle-alert-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeHandleModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-tools ml-2"></i>
                معالجة التنبيه
            </h3>
        </div>

        <form id="handle-alert-form" onsubmit="saveAlertHandling(event)" class="space-y-4">
            <input type="hidden" id="handle-alert-id" name="alert_id">
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">إجراء المعالجة</label>
                <select id="handle-action" name="action" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                    <option value="resolve">تم الحل</option>
                    <option value="in-progress">قيد المعالجة</option>
                    <option value="ignore">تجاهل</option>
                    <option value="investigate">تحقيق</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                <textarea id="handle-notes" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right" placeholder="أضف ملاحظاتك هنا..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-check ml-2"></i>
                    تأكيد
                </button>
                <button type="button" onclick="closeHandleModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// =============================================
// الرسم البياني
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('alertsChart')?.getContext('2d');
    if (!ctx) return;
    
    const dailyData = <?php echo json_encode(array_values($daily_stats)); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'حرجة',
                    data: dailyData.map(d => d.critical),
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1
                },
                {
                    label: 'تحذير',
                    data: dailyData.map(d => d.warning),
                    backgroundColor: 'rgba(245, 158, 11, 0.7)',
                    borderColor: '#f59e0b',
                    borderWidth: 1
                },
                {
                    label: 'معلومات',
                    data: dailyData.map(d => d.info),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                }
            }
        }
    });
});

// =============================================
// متغيرات عامة
// =============================================
let currentAlertId = null;

// =============================================
// دوال الصفحة
// =============================================
function filterAlerts(type) {
    window.location.href = `?page=alerts&filter=${type}`;
}

function markAllAsRead() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('📭 تم تعليم جميع التنبيهات كمقروءة', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshAlerts() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث التنبيهات', 'success');
        }
        location.reload();
    }, 1500);
}

function handleAlert(alertId) {
    currentAlertId = alertId;
    document.getElementById('handle-alert-id').value = alertId;
    document.getElementById('handle-alert-modal').classList.remove('hidden');
    document.getElementById('handle-alert-modal').classList.add('flex');
}

function closeHandleModal() {
    document.getElementById('handle-alert-modal').classList.add('hidden');
    document.getElementById('handle-alert-modal').classList.remove('flex');
}

function saveAlertHandling(event) {
    event.preventDefault();
    
    const action = document.getElementById('handle-action').value;
    const notes = document.getElementById('handle-notes').value;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeHandleModal();
        if (typeof showNotification === 'function') {
            showNotification(`✅ تم معالجة التنبيه`, 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function markAsRead(alertId) {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification(`👁️ تم تعليم التنبيه كمقروء`, 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1000);
}

function resolveAlert(alertId) {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification(`✅ تم حل التنبيه`, 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1000);
}

function viewAlertDetails(alertId) {
    currentAlertId = alertId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل التنبيه
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل التنبيه #${alertId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="handleAlert(${alertId})" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg">معالجة</button>
                    <button onclick="closeAlertModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('alert-details-content').innerHTML = details;
        document.getElementById('alert-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل التنبيه #${alertId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('alert-details-modal').classList.remove('hidden');
        document.getElementById('alert-details-modal').classList.add('flex');
    }, 1000);
}

function closeAlertModal() {
    document.getElementById('alert-details-modal').classList.add('hidden');
    document.getElementById('alert-details-modal').classList.remove('flex');
}
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}
.blink {
    animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>