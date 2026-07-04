<?php
// pages/dashboard.php - لوحة المراقبة الحية
$db = getDB();

// 1. الإحصائيات السريعة
$stats = [];
$stats['active_servers'] = $db->query("SELECT COUNT(*) FROM servers WHERE status = 'online'")->fetchColumn();
$stats['active_threats'] = $db->query("SELECT COUNT(*) FROM threats WHERE status = 'active'")->fetchColumn();
$stats['daily_alerts'] = $db->query("SELECT COUNT(*) FROM alerts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$stats['resolved_alerts'] = $db->query("SELECT COUNT(*) FROM alerts WHERE DATE(created_at) = CURDATE() AND status = 'resolved'")->fetchColumn();

// 2. الخوادم النشطة
$servers = $db->query("
    SELECT id, name, status, cpu_usage, ip_address, location 
    FROM servers 
    WHERE status IN ('online', 'warning')
    ORDER BY 
        CASE status WHEN 'warning' THEN 1 WHEN 'online' THEN 2 END,
        cpu_usage DESC
    LIMIT 4
")->fetchAll();

// 3. التنبيهات الحرجة
$critical_alerts = $db->query("
    SELECT a.*, s.name as server_name 
    FROM alerts a
    LEFT JOIN servers s ON a.server_id = s.id
    WHERE a.type = 'critical' AND a.status != 'resolved'
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll();

// 4. أحداث الأمان
$security_events = $db->query("
    SELECT * FROM logs 
    WHERE log_type = 'security' 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// 5. نشاط الشبكة
$network = $db->query("
    SELECT 
        COALESCE(SUM(CASE WHEN event_type = 'inbound' THEN bandwidth_used ELSE 0 END), 0) as inbound,
        COALESCE(SUM(CASE WHEN event_type = 'outbound' THEN bandwidth_used ELSE 0 END), 0) as outbound,
        COUNT(*) as connections
    FROM network_events 
    WHERE DATE(created_at) = CURDATE()
")->fetch();
?>

<!-- الإحصائيات السريعة -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-right text-green-400">📡 لوحة المراقبة الحية</h3>
        <div class="flex items-center">
            <span class="status-indicator bg-green-500"></span>
            <span class="text-sm text-green-400 mr-2">نشط</span>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- الخوادم النشطة -->
        <div class="card-hover cyber-border bg-slate-900 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm mb-2">الخوادم النشطة</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $stats['active_servers']; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-sm text-green-300">جميع الخوادم تعمل بشكل طبيعي</div>
        </div>

        <!-- التهديدات النشطة -->
        <div class="card-hover cyber-border bg-slate-900 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm mb-2">التهديدات النشطة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $stats['active_threats']; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-sm text-red-300">تحتاج للمعالجة الفورية</div>
        </div>

        <!-- التنبيهات اليومية -->
        <div class="card-hover cyber-border bg-slate-900 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm mb-2">التنبيهات اليومية</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $stats['daily_alerts']; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-sm text-yellow-300">تم معالجة <?php echo $stats['resolved_alerts']; ?> منها</div>
        </div>

        <!-- وقت التشغيل -->
        <div class="card-hover cyber-border bg-slate-900 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm mb-2">وقت التشغيل</p>
                    <p class="text-3xl font-bold text-cyan-400">99.98%</p>
                </div>
                <div class="w-12 h-12 bg-cyan-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-sm text-cyan-300">هذا الشهر</div>
        </div>
    </div>

    <!-- حالة الخوادم المباشرة -->
    <div class="mb-6">
        <h4 class="text-lg font-bold text-blue-400 mb-4 text-right">حالة الخوادم المباشرة</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($servers as $server): ?>
            <div class="server-status-<?php echo $server['status']; ?> cyber-border bg-slate-900 p-4 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm <?php echo getStatusColor($server['status']); ?>"><?php echo $server['name']; ?></span>
                    <span class="status-indicator <?php echo getStatusIndicator($server['status']); ?>"></span>
                </div>
                <p class="text-xs text-gray-400">الاستخدام: <?php echo $server['cpu_usage']; ?>%</p>
                <div class="progress-bar mt-1">
                    <div class="progress-fill" style="width: <?php echo $server['cpu_usage']; ?>%"></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                    <span><?php echo $server['ip_address']; ?></span>
                    <span><?php echo $server['location']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- نشاط الشبكة المباشر -->
    <div class="mb-6">
        <h4 class="text-lg font-bold text-purple-400 mb-4 text-right">نشاط الشبكة المباشر</h4>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="text-sm text-green-400">الحركة الواردة: <?php echo number_format($network['inbound'], 1); ?> Gbps</span>
                    <div class="progress-bar mt-1 w-32">
                        <div class="progress-fill" style="width: <?php echo min(100, ($network['inbound'] / 5) * 100); ?>%"></div>
                    </div>
                </div>
                <div>
                    <span class="text-sm text-blue-400">الحركة الصادرة: <?php echo number_format($network['outbound'], 1); ?> Gbps</span>
                    <div class="progress-bar mt-1 w-32">
                        <div class="progress-fill" style="width: <?php echo min(100, ($network['outbound'] / 5) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 bg-purple-600 bg-opacity-20 rounded-lg">
                    <span class="status-indicator bg-green-500"></span>
                    <span class="text-sm text-green-400 mr-2">
                        إجمالي الاتصالات: <?php echo $network['connections']; ?> | الشبكة مستقرة
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- التنبيهات وأحداث الأمان -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- التنبيهات الفورية -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-right text-red-400">⚠️ التنبيهات الفورية</h3>
                <a href="?page=alerts" class="text-sm text-red-400 hover:text-red-300">عرض الكل</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($critical_alerts)): ?>
                <div class="text-center p-8">
                    <svg class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-green-400">لا توجد تنبيهات حرجة</p>
                </div>
                <?php else: ?>
                    <?php foreach ($critical_alerts as $alert): ?>
                    <div class="critical-alert p-4 bg-red-900 bg-opacity-20 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold text-red-400"><?php echo $alert['title']; ?></p>
                            <span class="text-xs text-gray-400"><?php echo formatTimeAgo($alert['created_at']); ?></span>
                        </div>
                        <p class="text-sm text-gray-300 mb-3"><?php echo $alert['description']; ?></p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400"><?php echo $alert['server_name'] ?? 'النظام'; ?></span>
                            <button onclick="showNotification('تم تأكيد التنبيه', 'success')" class="text-xs text-blue-400 hover:text-blue-300">تأكيد</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- أحداث الأمان الأخيرة -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-right text-blue-400">🔍 أحداث الأمان الأخيرة</h3>
                <a href="?page=logs" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($security_events as $event): ?>
                <div class="log-type-security p-3 bg-slate-800 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs <?php echo getLogLevelColor($event['level']); ?>">
                            <?php echo $event['event_type'] ?? 'حدث أمني'; ?>
                        </span>
                        <span class="text-xs text-gray-400"><?php echo date('H:i', strtotime($event['created_at'])); ?></span>
                    </div>
                    <p class="text-sm text-gray-300"><?php echo $event['description']; ?></p>
                    <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                        <span><?php echo $event['source']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>