<?php
// pages/servers.php - مراقبة الخوادم
$db = getDB();

// =============================================
// 1. إحصائيات الخوادم
// =============================================
$servers_stats = $db->query("
    SELECT 
        COUNT(*) as total_servers,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_servers,
        SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_servers,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_servers,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_servers,
        ROUND(AVG(cpu_usage), 1) as avg_cpu,
        ROUND(AVG(memory_usage), 1) as avg_memory,
        ROUND(AVG(storage_usage), 1) as avg_storage,
        ROUND(AVG(uptime) / 86400, 1) as avg_uptime_days
    FROM servers
")->fetch();

// =============================================
// 2. جميع الخوادم مع تفاصيلها
// =============================================
$servers = $db->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM alerts WHERE server_id = s.id AND status != 'resolved') as active_alerts,
           (SELECT COUNT(*) FROM threats WHERE target_server_id = s.id AND status = 'active') as active_threats
    FROM servers s
    ORDER BY 
        CASE s.status
            WHEN 'warning' THEN 1
            WHEN 'online' THEN 2
            WHEN 'offline' THEN 3
            WHEN 'maintenance' THEN 4
        END,
        s.cpu_usage DESC
")->fetchAll();

// =============================================
// 3. إحصائيات الأداء لكل خادم (آخر 24 ساعة)
// =============================================
$server_performance = [];
foreach ($servers as $server) {
    $perf = $db->prepare("
        SELECT 
            COUNT(*) as alert_count,
            SUM(CASE WHEN type = 'critical' THEN 1 ELSE 0 END) as critical_alerts
        FROM alerts
        WHERE server_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $perf->execute([$server['id']]);
    $server_performance[$server['id']] = $perf->fetch();
}

// =============================================
// 4. أكثر الخوادم تحميلاً (Top 5 CPU)
// =============================================
$top_cpu_servers = $db->query("
    SELECT name, cpu_usage, status
    FROM servers
    WHERE status != 'offline'
    ORDER BY cpu_usage DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 5. أكثر الخوادم استهلاكاً للذاكرة
// =============================================
$top_memory_servers = $db->query("
    SELECT name, memory_usage, status
    FROM servers
    WHERE status != 'offline'
    ORDER BY memory_usage DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 6. أكثر الخوادم استهلاكاً للتخزين
// =============================================
$top_storage_servers = $db->query("
    SELECT name, storage_usage, status
    FROM servers
    WHERE status != 'offline'
    ORDER BY storage_usage DESC
    LIMIT 5
")->fetchAll();

// =============================================
// 7. آخر فحص للخوادم
// =============================================
$last_scan = $db->query("SELECT MAX(last_check) as last_scan FROM servers")->fetchColumn();

// دوال مساعدة
function getServerStatusBadge($status) {
    $colors = [
        'online' => 'bg-green-500',
        'warning' => 'bg-yellow-500',
        'offline' => 'bg-red-500',
        'maintenance' => 'bg-blue-500'
    ];
    $texts = [
        'online' => 'نشط',
        'warning' => 'تحذير',
        'offline' => 'غير نشط',
        'maintenance' => 'صيانة'
    ];
    $color = $colors[$status] ?? 'bg-gray-500';
    $text = $texts[$status] ?? $status;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getServerStatusColor($status) {
    return match($status) {
        'online' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'offline' => 'text-red-400',
        'maintenance' => 'text-blue-400',
        default => 'text-gray-400'
    };
}

function getServerStatusIndicator($status) {
    return match($status) {
        'online' => 'bg-green-500',
        'warning' => 'bg-yellow-500',
        'offline' => 'bg-red-500',
        'maintenance' => 'bg-blue-500',
        default => 'bg-gray-500'
    };
}

function getUsageColor($value, $threshold_warning = 70, $threshold_critical = 85) {
    if ($value >= $threshold_critical) return 'text-red-400';
    if ($value >= $threshold_warning) return 'text-yellow-400';
    return 'text-green-400';
}

function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    
    if ($days > 0) {
        return "$days يوم $hours ساعة";
    }
    return "$hours ساعة";
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="refreshServerStatus()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    تحديث الحالة
                </button>
                <button onclick="restartAllServers()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    إعادة تشغيل الكل
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">🖥️</span> مراقبة الخوادم
            </h1>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gradient-to-br from-green-600 to-green-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm mb-1">الخوادم النشطة</p>
                        <p class="text-3xl font-bold"><?php echo $servers_stats['online_servers']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-green-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-green-200">
                    من أصل <?php echo $servers_stats['total_servers']; ?> خادم
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-200 text-sm mb-1">تحذير</p>
                        <p class="text-3xl font-bold"><?php echo $servers_stats['warning_servers']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-yellow-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-yellow-200">
                    تحتاج للمتابعة
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-200 text-sm mb-1">غير نشط</p>
                        <p class="text-3xl font-bold"><?php echo $servers_stats['offline_servers']; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-red-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-red-200">
                    بحاجة للفحص
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm mb-1">متوسط CPU</p>
                        <p class="text-3xl font-bold"><?php echo $servers_stats['avg_cpu']; ?>%</p>
                    </div>
                    <svg class="w-12 h-12 text-blue-300 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <div class="mt-2 text-sm text-blue-200">
                    الذاكرة: <?php echo $servers_stats['avg_memory']; ?>% | تخزين: <?php echo $servers_stats['avg_storage']; ?>%
                </div>
            </div>
        </div>

        <!-- آخر فحص -->
        <div class="text-sm text-gray-400 text-left">
            آخر تحديث: <?php echo $last_scan ? date('Y-m-d H:i:s', strtotime($last_scan)) : 'غير متاح'; ?>
        </div>
    </div>

    <!-- إحصائيات الأداء -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-red-400 mb-4 text-right">🔥 أعلى استخدام CPU</h3>
            <div class="space-y-4">
                <?php foreach ($top_cpu_servers as $server): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-300"><?php echo $server['name']; ?></span>
                    <div class="flex items-center">
                        <span class="ml-2 <?php echo getUsageColor($server['cpu_usage']); ?> font-bold"><?php echo $server['cpu_usage']; ?>%</span>
                        <span class="status-indicator <?php echo getServerStatusIndicator($server['status']); ?>"></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $server['cpu_usage']; ?>%"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-blue-400 mb-4 text-right">💾 أعلى استخدام RAM</h3>
            <div class="space-y-4">
                <?php foreach ($top_memory_servers as $server): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-300"><?php echo $server['name']; ?></span>
                    <div class="flex items-center">
                        <span class="ml-2 <?php echo getUsageColor($server['memory_usage']); ?> font-bold"><?php echo $server['memory_usage']; ?>%</span>
                        <span class="status-indicator <?php echo getServerStatusIndicator($server['status']); ?>"></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $server['memory_usage']; ?>%"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-purple-400 mb-4 text-right">📀 أعلى استخدام تخزين</h3>
            <div class="space-y-4">
                <?php foreach ($top_storage_servers as $server): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-300"><?php echo $server['name']; ?></span>
                    <div class="flex items-center">
                        <span class="ml-2 <?php echo getUsageColor($server['storage_usage'], 80, 90); ?> font-bold"><?php echo $server['storage_usage']; ?>%</span>
                        <span class="status-indicator <?php echo getServerStatusIndicator($server['status']); ?>"></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $server['storage_usage']; ?>%"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- شبكة الخوادم -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-green-400">🖧 حالة الخوادم المباشرة</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($servers as $server): ?>
            <div class="bg-slate-900 rounded-lg p-5 border-r-4 border-<?php echo $server['status'] == 'online' ? 'green' : ($server['status'] == 'warning' ? 'yellow' : ($server['status'] == 'offline' ? 'red' : 'blue')); ?>-500 server-card">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-lg <?php echo getServerStatusColor($server['status']); ?>"><?php echo $server['name']; ?></h3>
                    <?php echo getServerStatusBadge($server['status']); ?>
                </div>
                
                <div class="text-sm text-gray-400 mb-3">
                    <span class="ml-2"><?php echo $server['type']; ?></span> | 
                    <span><?php echo $server['ip_address']; ?></span> | 
                    <span><?php echo $server['location']; ?></span>
                </div>

                <div class="space-y-3 mb-4">
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400">CPU</span>
                            <span class="<?php echo getUsageColor($server['cpu_usage']); ?>"><?php echo $server['cpu_usage']; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $server['cpu_usage']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400">الذاكرة</span>
                            <span class="<?php echo getUsageColor($server['memory_usage']); ?>"><?php echo $server['memory_usage']; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $server['memory_usage']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400">التخزين</span>
                            <span class="<?php echo getUsageColor($server['storage_usage'], 80, 90); ?>"><?php echo $server['storage_usage']; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $server['storage_usage']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                    <span>🕒 <?php echo formatUptime($server['uptime']); ?></span>
                    <span>📅 <?php echo date('H:i', strtotime($server['last_check'])); ?></span>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex space-x-2 space-x-reverse">
                        <?php if ($server['active_alerts'] > 0): ?>
                        <span class="px-2 py-1 bg-red-500 rounded-full text-xs" title="تنبيهات نشطة">
                            🔴 <?php echo $server['active_alerts']; ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($server['active_threats'] > 0): ?>
                        <span class="px-2 py-1 bg-orange-500 rounded-full text-xs" title="تهديدات نشطة">
                            ⚠️ <?php echo $server['active_threats']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button onclick="viewServerDetails(<?php echo $server['id']; ?>)" 
                                class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="restartServer(<?php echo $server['id']; ?>)" 
                                class="text-green-400 hover:text-green-300" title="إعادة تشغيل">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- جدول الخوادم التفصيلي -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-blue-400">📋 سجل الخوادم التفصيلي</h2>

        <!-- شريط البحث والتصفية -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="relative">
                    <input type="text" id="search-servers" placeholder="بحث في الخوادم..." 
                           class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="status-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الحالات</option>
                    <option value="online">نشط</option>
                    <option value="warning">تحذير</option>
                    <option value="offline">غير نشط</option>
                    <option value="maintenance">صيانة</option>
                </select>
                <select id="type-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الأنواع</option>
                    <option value="web">ويب</option>
                    <option value="database">قاعدة بيانات</option>
                    <option value="application">تطبيق</option>
                    <option value="cache">كاش</option>
                    <option value="loadbalancer">موازن تحميل</option>
                </select>
            </div>
            <div class="text-sm text-gray-400">
                إجمالي: <?php echo count($servers); ?> خادم
            </div>
        </div>

        <!-- الجدول -->
        <div class="overflow-x-auto">
            <table class="w-full" id="servers-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                        <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                        <th class="px-6 py-4 text-sm font-semibold">الخادم</th>
                        <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                        <th class="px-6 py-4 text-sm font-semibold">IP</th>
                        <th class="px-6 py-4 text-sm font-semibold">الموقع</th>
                        <th class="px-6 py-4 text-sm font-semibold">CPU</th>
                        <th class="px-6 py-4 text-sm font-semibold">الذاكرة</th>
                        <th class="px-6 py-4 text-sm font-semibold">التخزين</th>
                        <th class="px-6 py-4 text-sm font-semibold">وقت التشغيل</th>
                        <th class="px-6 py-4 text-sm font-semibold">آخر فحص</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $server): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors server-row" 
                        data-status="<?php echo $server['status']; ?>"
                        data-type="<?php echo $server['type']; ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button onclick="viewServerDetails(<?php echo $server['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300" title="تفاصيل">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <button onclick="restartServer(<?php echo $server['id']; ?>)" 
                                        class="text-green-400 hover:text-green-300" title="إعادة تشغيل">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo getServerStatusBadge($server['status']); ?>
                        </td>
                        <td class="px-6 py-4 font-semibold text-green-400"><?php echo $server['name']; ?></td>
                        <td class="px-6 py-4 text-gray-300"><?php echo $server['type']; ?></td>
                        <td class="px-6 py-4 text-gray-300"><?php echo $server['ip_address']; ?></td>
                        <td class="px-6 py-4 text-gray-300"><?php echo $server['location']; ?></td>
                        <td class="px-6 py-4">
                            <span class="<?php echo getUsageColor($server['cpu_usage']); ?>"><?php echo $server['cpu_usage']; ?>%</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="<?php echo getUsageColor($server['memory_usage']); ?>"><?php echo $server['memory_usage']; ?>%</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="<?php echo getUsageColor($server['storage_usage'], 80, 90); ?>"><?php echo $server['storage_usage']; ?>%</span>
                        </td>
                        <td class="px-6 py-4 text-gray-300"><?php echo formatUptime($server['uptime']); ?></td>
                        <td class="px-6 py-4 text-gray-300"><?php echo date('Y-m-d H:i', strtotime($server['last_check'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- نافذة تفاصيل الخادم -->
<div id="server-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeServerModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400">🔍 تفاصيل الخادم</h3>
        </div>
        
        <div id="server-details-content" class="space-y-6">
            <!-- محتوى الخادم يتم تحميله هنا -->
        </div>
    </div>
</div>

<style>
.server-card {
    transition: all 0.3s ease;
}
.server-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.3);
}
.progress-bar {
    height: 6px;
    background: #1e293b;
    border-radius: 3px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}
</style>

<script>
let currentServerId = null;

function refreshServerStatus() {
    showLoading();
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function restartAllServers() {
    if (confirm('هل أنت متأكد من إعادة تشغيل جميع الخوادم؟')) {
        showLoading();
        setTimeout(() => {
            hideLoading();
            showNotification('جاري إعادة تشغيل جميع الخوادم...', 'warning');
            setTimeout(() => {
                showNotification('تم إعادة تشغيل الخوادم بنجاح', 'success');
                location.reload();
            }, 3000);
        }, 1000);
    }
}

function viewServerDetails(id) {
    currentServerId = id;
    showLoading();
    
    // جلب تفاصيل الخادم (يمكن تطويرها لاحقاً)
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-900 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل الخادم #${id}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                    
                    <div class="flex justify-between mt-4 pt-4 border-t border-slate-700">
                        <button onclick="restartServer(${id})" class="px-4 py-2 bg-green-600 rounded-lg">إعادة تشغيل</button>
                        <button onclick="closeServerModal()" class="px-4 py-2 bg-gray-600 rounded-lg">إغلاق</button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('server-details-content').innerHTML = details;
        hideLoading();
        document.getElementById('server-details-modal').classList.remove('hidden');
        document.getElementById('server-details-modal').classList.add('flex');
    }, 1000);
}

function closeServerModal() {
    document.getElementById('server-details-modal').classList.add('hidden');
    document.getElementById('server-details-modal').classList.remove('flex');
}

function restartServer(id) {
    if (confirm('هل أنت متأكد من إعادة تشغيل هذا الخادم؟')) {
        showNotification(`جاري إعادة تشغيل الخادم #${id}...`, 'info');
        setTimeout(() => {
            showNotification('تم إعادة تشغيل الخادم بنجاح', 'success');
            closeServerModal();
            setTimeout(() => location.reload(), 1500);
        }, 2000);
    }
}

// البحث المباشر
document.getElementById('search-servers')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.server-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// تصفية الحالة
document.getElementById('status-filter')?.addEventListener('change', filterTable);
document.getElementById('type-filter')?.addEventListener('change', filterTable);

function filterTable() {
    const status = document.getElementById('status-filter').value;
    const type = document.getElementById('type-filter').value;
    const rows = document.querySelectorAll('.server-row');
    
    rows.forEach(row => {
        const statusMatch = status === 'all' || row.dataset.status === status;
        const typeMatch = type === 'all' || row.dataset.type === type;
        row.style.display = statusMatch && typeMatch ? '' : 'none';
    });
}

// دوال مساعدة من index.php
function showLoading() {
    if (typeof window.showLoading === 'function') {
        window.showLoading();
    }
}

function hideLoading() {
    if (typeof window.hideLoading === 'function') {
        window.hideLoading();
    }
}

function showNotification(message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}
</script>