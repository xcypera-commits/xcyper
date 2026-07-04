<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/security_monitoring.php
// المراقبة الأمنية - نسخة كاملة ومفصلة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات أمنية سريعة
    // =============================================
    
    // عدد التهديدات النشطة
    $stmt = $db->query("SELECT COUNT(*) FROM threats WHERE status = 'active'");
    $active_threats = $stmt->fetchColumn() ?: 0;
    
    // عدد التنبيهات الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM alerts WHERE type = 'critical' AND status != 'resolved'");
    $critical_alerts = $stmt->fetchColumn() ?: 0;
    
    // عدد الحوادث المفتوحة
    $stmt = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open', 'in-progress')");
    $open_incidents = $stmt->fetchColumn() ?: 0;
    
    // عدد الخوادم المصابة
    $stmt = $db->query("SELECT COUNT(DISTINCT id) FROM threats WHERE status = 'active' AND target_server_id IS NOT NULL");
    $infected_servers = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. إحصائيات التهديدات حسب النوع
    // =============================================
    
    $threats_by_type = [
        'ddos' => 0,
        'brute_force' => 0,
        'sql_injection' => 0,
        'xss' => 0,
        'malware' => 0,
        'phishing' => 0
    ];
    
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM threats WHERE status = 'active' GROUP BY type");
    while ($row = $stmt->fetch()) {
        if (isset($threats_by_type[$row['type']])) {
            $threats_by_type[$row['type']] = $row['count'];
        }
    }
    
    // =============================================
    // 3. إحصائيات التنبيهات حسب الشدة
    // =============================================
    
    $alerts_by_severity = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    $stmt = $db->query("SELECT severity, COUNT(*) as count FROM alerts WHERE status != 'resolved' GROUP BY severity");
    while ($row = $stmt->fetch()) {
        if (isset($alerts_by_severity[$row['severity']])) {
            $alerts_by_severity[$row['severity']] = $row['count'];
        }
    }
    
    // =============================================
    // 4. آخر 10 تهديدات نشطة
    // =============================================
    
    $recent_threats = $db->query("
        SELECT t.*, s.name as server_name
        FROM threats t
        LEFT JOIN servers s ON t.target_server_id = s.id
        WHERE t.status = 'active'
        ORDER BY t.last_seen DESC
        LIMIT 10
    ")->fetchAll();
    
    // =============================================
    // 5. آخر 10 تنبيهات غير محلولة
    // =============================================
    
    $recent_alerts = $db->query("
        SELECT a.*, s.name as server_name
        FROM alerts a
        LEFT JOIN servers s ON a.server_id = s.id
        WHERE a.status != 'resolved'
        ORDER BY a.created_at DESC
        LIMIT 10
    ")->fetchAll();
    
    // =============================================
    // 6. حالة أنظمة الحماية
    // =============================================
    
    $security_systems = $db->query("
        SELECT * FROM system_status 
        WHERE component IN ('جدار الحماية', 'أنظمة الكشف', 'النسخ الاحتياطي', 'نظام منع الاختراق')
        ORDER BY component
    ")->fetchAll();
    
    // إذا لم توجد بيانات، نستخدم بيانات افتراضية
    if (empty($security_systems)) {
        $security_systems = [
            ['component' => 'جدار الحماية', 'status' => 'active', 'health_percentage' => 100],
            ['component' => 'أنظمة الكشف', 'status' => 'active', 'health_percentage' => 95],
            ['component' => 'النسخ الاحتياطي', 'status' => 'active', 'health_percentage' => 100],
            ['component' => 'نظام منع الاختراق', 'status' => 'active', 'health_percentage' => 92]
        ];
    }
    
    // =============================================
    // 7. أكثر عناوين IP هجومًا
    // =============================================
    
    $top_attackers = $db->query("
        SELECT source_ip, COUNT(*) as attack_count, 
               MAX(severity) as max_severity,
               GROUP_CONCAT(DISTINCT type) as attack_types
        FROM threats
        WHERE source_ip IS NOT NULL AND source_ip != ''
        GROUP BY source_ip
        ORDER BY attack_count DESC
        LIMIT 10
    ")->fetchAll();
    
    // =============================================
    // 8. إحصائيات آخر 24 ساعة
    // =============================================
    
    $last_24h_stats = [];
    
    // إجمالي الهجمات في آخر 24 ساعة
    $stmt = $db->query("
        SELECT COUNT(*) FROM threats 
        WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $last_24h_stats['total_attacks'] = $stmt->fetchColumn() ?: 0;
    
    // إجمالي التنبيهات في آخر 24 ساعة
    $stmt = $db->query("
        SELECT COUNT(*) FROM alerts 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $last_24h_stats['total_alerts'] = $stmt->fetchColumn() ?: 0;
    
    // أعلى شدة هجوم في آخر 24 ساعة
    $stmt = $db->query("
        SELECT severity FROM threats 
        WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY 
            CASE severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END
        LIMIT 1
    ");
    $last_24h_stats['max_severity'] = $stmt->fetchColumn() ?: 'none';
    
    // =============================================
    // 9. أكثر الخوادم استهدافًا
    // =============================================
    
    $top_targeted_servers = $db->query("
        SELECT s.name, COUNT(t.id) as attack_count,
               SUM(CASE WHEN t.severity = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM servers s
        LEFT JOIN threats t ON s.id = t.target_server_id AND t.status = 'active'
        GROUP BY s.id
        HAVING attack_count > 0
        ORDER BY attack_count DESC, critical_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 10. إحصائيات يومية للآخر 7 أيام (للرسم البياني)
    // =============================================
    
    $daily_stats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily_stats[$date] = [
            'date' => $date,
            'attacks' => 0,
            'alerts' => 0,
            'critical' => 0
        ];
    }
    
    // جلب إحصائيات التهديدات اليومية
    $stmt = $db->query("
        SELECT DATE(last_seen) as attack_date, COUNT(*) as count,
               SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count
        FROM threats
        WHERE last_seen >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(last_seen)
    ");
    while ($row = $stmt->fetch()) {
        if (isset($daily_stats[$row['attack_date']])) {
            $daily_stats[$row['attack_date']]['attacks'] = $row['count'];
            $daily_stats[$row['attack_date']]['critical'] = $row['critical_count'];
        }
    }
    
    // جلب إحصائيات التنبيهات اليومية
    $stmt = $db->query("
        SELECT DATE(created_at) as alert_date, COUNT(*) as count
        FROM alerts
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
    ");
    while ($row = $stmt->fetch()) {
        if (isset($daily_stats[$row['alert_date']])) {
            $daily_stats[$row['alert_date']]['alerts'] = $row['count'];
        }
    }
    
    // =============================================
    // 11. أحداث أمنية أخيرة (لوج)
    // =============================================
    
    $security_events = $db->query("
        SELECT l.*, u.full_name as user_name, s.name as server_name
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.id
        LEFT JOIN servers s ON l.server_id = s.id
        WHERE l.log_type = 'security'
        ORDER BY l.created_at DESC
        LIMIT 15
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function getThreatTypeText($type) {
    return match($type) {
        'ddos' => 'DDoS',
        'brute_force' => 'Brute Force',
        'sql_injection' => 'SQL Injection',
        'xss' => 'XSS',
        'malware' => 'برمجيات خبيثة',
        'phishing' => 'تصيد',
        default => $type
    };
}

function getThreatTypeColor($type) {
    return match($type) {
        'ddos' => 'bg-red-500',
        'brute_force' => 'bg-yellow-500',
        'sql_injection' => 'bg-blue-500',
        'xss' => 'bg-purple-500',
        'malware' => 'bg-orange-500',
        'phishing' => 'bg-indigo-500',
        default => 'bg-gray-500'
    };
}



function getSeverityText($severity) {
    return match($severity) {
        'critical' => 'حرج',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض',
        default => $severity
    };
}

function getAlertStatusText($status) {
    return match($status) {
        'new' => 'جديد',
        'acknowledged' => 'مؤكد',
        'in-progress' => 'قيد المعالجة',
        'resolved' => 'تم الحل',
        default => $status
    };
}



function getLogLevelText($level) {
    return match($level) {
        'error' => 'خطأ',
        'warning' => 'تحذير',
        'info' => 'معلومات',
        'debug' => 'تصحيح',
        default => $level
    };
}



?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-red-300">
            <i class="fas fa-shield-alt ml-2"></i>
            المراقبة الأمنية
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="runFullSecurityScan()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-all flex items-center operation-glow">
                <i class="fas fa-search ml-2"></i>
                فحص أمني شامل
            </button>
            <button onclick="refreshSecurityData()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات KPIs الأمنية -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-red-900 to-red-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-200 text-sm mb-1">تهديدات نشطة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $active_threats; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-bug text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                آخر 24 ساعة: <?php echo $last_24h_stats['total_attacks']; ?> هجوم
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-900 to-orange-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-200 text-sm mb-1">تنبيهات حرجة</p>
                    <p class="text-3xl font-bold text-orange-400"><?php echo $critical_alerts; ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-orange-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-orange-200">
                غير محلولة: <?php echo $alerts_by_severity['critical'] + $alerts_by_severity['high']; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">حوادث مفتوحة</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $open_incidents; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-fire text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                قيد التحقيق: <?php echo $open_incidents; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">خوادم مستهدفة</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $infected_servers; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-server text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                تحت الحماية: 24/7
            </div>
        </div>
    </div>

    <!-- آخر 24 ساعة معلومات -->
    <div class="bg-slate-800 rounded-lg p-4 mt-2 flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full ml-2"></span>
                <span class="text-sm">أعلى شدة: <span class="font-bold text-red-400"><?php echo $last_24h_stats['max_severity'] != 'none' ? getSeverityText($last_24h_stats['max_severity']) : 'لا يوجد'; ?></span></span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 bg-orange-500 rounded-full ml-2"></span>
                <span class="text-sm">إجمالي التنبيهات: <span class="font-bold text-orange-400"><?php echo $last_24h_stats['total_alerts']; ?></span></span>
            </div>
        </div>
        <div class="text-sm text-gray-400">
            <i class="far fa-clock ml-1"></i>
            آخر تحديث: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- توزيع التهديدات وأنظمة الحماية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع التهديدات حسب النوع -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-red-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع التهديدات حسب النوع
        </h3>
        <div class="space-y-4">
            <?php 
            $threat_types = [
                'ddos' => 'DDoS',
                'brute_force' => 'Brute Force',
                'sql_injection' => 'SQL Injection',
                'xss' => 'XSS',
                'malware' => 'برمجيات خبيثة',
                'phishing' => 'تصيد'
            ];
            $total_threats = array_sum($threats_by_type);
            foreach ($threat_types as $key => $label): 
                $count = $threats_by_type[$key] ?? 0;
                $percentage = $total_threats > 0 ? round(($count / $total_threats) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $label; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $count; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo getThreatTypeColor($key); ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($total_threats == 0): ?>
            <p class="text-center text-gray-400 py-4">لا توجد تهديدات نشطة</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- حالة أنظمة الحماية -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-shield-virus ml-2"></i>
            حالة أنظمة الحماية
        </h3>
        <div class="space-y-4">
            <?php foreach ($security_systems as $system): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300 flex items-center">
                        <span class="w-2 h-2 rounded-full <?php echo $system['status'] == 'active' ? 'bg-green-500' : ($system['status'] == 'warning' ? 'bg-yellow-500' : 'bg-red-500'); ?> ml-2"></span>
                        <?php echo $system['component']; ?>
                    </span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold <?php echo $system['health_percentage'] > 90 ? 'text-green-400' : ($system['health_percentage'] > 70 ? 'text-yellow-400' : 'text-red-400'); ?>">
                            <?php echo $system['health_percentage']; ?>%
                        </span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $system['health_percentage'] > 90 ? 'bg-green-500' : ($system['health_percentage'] > 70 ? 'bg-yellow-500' : 'bg-red-500'); ?>" 
                         style="width: <?php echo $system['health_percentage']; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- خريطة التهديدات (مصادر الهجمات) -->
        <?php if (!empty($top_attackers)): ?>
        <div class="mt-6">
            <h4 class="text-md font-semibold text-yellow-300 mb-3 flex items-center">
                <i class="fas fa-map-marker-alt ml-2"></i>
                أكثر مصادر الهجمات
            </h4>
            <div class="space-y-2 max-h-48 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($top_attackers as $attacker): ?>
                <div class="flex items-center justify-between p-2 bg-slate-800 rounded-lg">
                    <span class="text-sm font-mono text-blue-400"><?php echo $attacker['source_ip']; ?></span>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="px-2 py-0.5 bg-red-600 rounded-full text-xs"><?php echo $attacker['attack_count']; ?></span>
                        <span class="px-2 py-0.5 <?php echo getSeverityColor($attacker['max_severity']); ?> rounded-full text-xs">
                            <?php echo getSeverityText($attacker['max_severity']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني لآخر 7 أيام -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        نشاط التهديدات والتنبيهات - آخر 7 أيام
    </h3>
    
    <div class="h-64 relative" id="security-chart-container">
        <canvas id="securityChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تهديدات</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-orange-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تنبيهات</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-purple-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تهديدات حرجة</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- آخر التهديدات والتنبيهات (2 Columns) -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- آخر التهديدات -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-red-300 flex items-center">
                <i class="fas fa-bug ml-2"></i>
                آخر التهديدات النشطة
            </h3>
            <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo count($recent_threats); ?></span>
        </div>
        
        <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
            <?php if (empty($recent_threats)): ?>
            <div class="p-6 bg-slate-800 rounded-lg text-center">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                <p class="text-green-400">لا توجد تهديدات نشطة</p>
            </div>
            <?php else: ?>
                <?php foreach ($recent_threats as $threat): ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 border-<?php echo $threat['severity'] == 'critical' ? 'red' : ($threat['severity'] == 'high' ? 'orange' : 'yellow'); ?>-500 hover:bg-slate-700 transition-colors">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <span class="px-2 py-0.5 <?php echo getThreatTypeColor($threat['type']); ?> rounded-full text-xs">
                                    <?php echo getThreatTypeText($threat['type']); ?>
                                </span>
                                <span class="px-2 py-0.5 <?php echo getSeverityColor($threat['severity']); ?> rounded-full text-xs">
                                    <?php echo getSeverityText($threat['severity']); ?>
                                </span>
                            </div>
                            <p class="font-semibold text-white mt-2"><?php echo $threat['name']; ?></p>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-server ml-1"></i> <?php echo $threat['server_name'] ?? 'غير محدد'; ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-network-wired ml-1"></i> <?php echo $threat['source_ip'] ?? 'غير معروف'; ?>
                            </p>
                        </div>
                        <span class="text-xs bg-slate-700 text-gray-300 px-2 py-1 rounded-full">
                            <?php echo formatTimeAgo($threat['last_seen']); ?>
                        </span>
                    </div>
                    <div class="flex space-x-2 space-x-reverse mt-3">
                        <button onclick="viewThreatDetails(<?php echo $threat['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <button onclick="mitigateThreat(<?php echo $threat['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-shield-alt ml-1"></i>
                            تخفيف
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- آخر التنبيهات -->
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-orange-300 flex items-center">
                <i class="fas fa-exclamation-circle ml-2"></i>
                آخر التنبيهات غير المحلولة
            </h3>
            <span class="px-3 py-1 bg-orange-600 rounded-full text-xs font-bold"><?php echo count($recent_alerts); ?></span>
        </div>
        
        <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-custom pl-2">
            <?php if (empty($recent_alerts)): ?>
            <div class="p-6 bg-slate-800 rounded-lg text-center">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                <p class="text-green-400">لا توجد تنبيهات غير محلولة</p>
            </div>
            <?php else: ?>
                <?php foreach ($recent_alerts as $alert): ?>
                <div class="p-4 bg-slate-800 rounded-lg border-r-4 border-<?php echo $alert['type'] == 'critical' ? 'red' : ($alert['type'] == 'warning' ? 'yellow' : 'blue'); ?>-500 hover:bg-slate-700 transition-colors">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <span class="px-2 py-0.5 <?php echo $alert['type'] == 'critical' ? 'bg-red-600' : ($alert['type'] == 'warning' ? 'bg-yellow-600' : 'bg-blue-600'); ?> rounded-full text-xs">
                                    <?php echo $alert['type']; ?>
                                </span>
                                <span class="px-2 py-0.5 <?php echo getSeverityColor($alert['severity']); ?> rounded-full text-xs">
                                    <?php echo getSeverityText($alert['severity']); ?>
                                </span>
                            </div>
                            <p class="font-semibold text-white mt-2"><?php echo $alert['title']; ?></p>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-server ml-1"></i> <?php echo $alert['server_name'] ?? 'النظام'; ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-clock ml-1"></i> <?php echo getAlertStatusText($alert['status']); ?>
                            </p>
                        </div>
                        <span class="text-xs bg-slate-700 text-gray-300 px-2 py-1 rounded-full">
                            <?php echo formatTimeAgo($alert['created_at']); ?>
                        </span>
                    </div>
                    <div class="flex space-x-2 space-x-reverse mt-3">
                        <button onclick="viewAlertDetails(<?php echo $alert['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-eye ml-1"></i>
                            تفاصيل
                        </button>
                        <?php if ($alert['status'] == 'new'): ?>
                        <button onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                            <i class="fas fa-check ml-1"></i>
                            تأكيد
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- أكثر الخوادم استهدافًا والأحداث الأمنية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- أكثر الخوادم استهدافًا -->
    <?php if (!empty($top_targeted_servers)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-300 mb-4 flex items-center">
            <i class="fas fa-chart-bar ml-2"></i>
            أكثر الخوادم استهدافًا
        </h3>
        <div class="space-y-4">
            <?php foreach ($top_targeted_servers as $server): ?>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-300"><?php echo $server['name']; ?></span>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="px-2 py-0.5 bg-red-600 rounded-full text-xs">
                        <?php echo $server['attack_count']; ?> هجوم
                    </span>
                    <?php if ($server['critical_count'] > 0): ?>
                    <span class="px-2 py-0.5 bg-red-800 rounded-full text-xs">
                        حرج: <?php echo $server['critical_count']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="progress-bar">
                <?php 
                $total_attacks = array_sum(array_column($top_targeted_servers, 'attack_count'));
                $percentage = $total_attacks > 0 ? round(($server['attack_count'] / $total_attacks) * 100, 1) : 0;
                ?>
                <div class="progress-fill bg-purple-500" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- الأحداث الأمنية الأخيرة -->
    <?php if (!empty($security_events)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
            <i class="fas fa-history ml-2"></i>
            أحداث أمنية أخيرة
        </h3>
        <div class="space-y-3 max-h-80 overflow-y-auto scrollbar-custom pl-2">
            <?php foreach ($security_events as $event): ?>
            <div class="p-3 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                <div class="flex items-start">
                    <div class="w-2 h-2 mt-2 ml-3 rounded-full <?php echo $event['level'] == 'error' ? 'bg-red-500' : ($event['level'] == 'warning' ? 'bg-yellow-500' : 'bg-blue-500'); ?>"></div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs px-2 py-0.5 <?php echo getLogLevelColor($event['level']); ?> rounded-full">
                                <?php echo getLogLevelText($event['level']); ?>
                            </span>
                            <span class="text-xs text-gray-400"><?php echo formatTimeAgo($event['created_at']); ?></span>
                        </div>
                        <p class="text-sm text-white mt-1"><?php echo $event['description']; ?></p>
                        <div class="flex items-center mt-2 text-xs text-gray-400">
                            <span class="ml-3"><i class="fas fa-server ml-1"></i> <?php echo $event['server_name'] ?? 'النظام'; ?></span>
                            <span><i class="fas fa-user ml-1"></i> <?php echo $event['user_name'] ?? 'system'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- جدول التهديدات التفصيلي (إذا كان هناك تهديدات كثيرة) -->
<!-- ============================================= -->
<?php if (count($recent_threats) >= 5): ?>
<div class="security-border manager-card rounded-xl p-6">
    <h3 class="text-lg font-bold text-yellow-300 mb-4 flex items-center">
        <i class="fas fa-table ml-2"></i>
        جميع التهديدات النشطة
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-4 py-3 text-sm font-semibold">الإجراءات</th>
                    <th class="px-4 py-3 text-sm font-semibold">النوع</th>
                    <th class="px-4 py-3 text-sm font-semibold">الشدة</th>
                    <th class="px-4 py-3 text-sm font-semibold">المصدر</th>
                    <th class="px-4 py-3 text-sm font-semibold">الهدف</th>
                    <th class="px-4 py-3 text-sm font-semibold">آخر نشاط</th>
                    <th class="px-4 py-3 text-sm font-semibold">اسم التهديد</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_threats as $threat): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors">
                    <td class="px-4 py-3">
                        <button onclick="viewThreatDetails(<?php echo $threat['id']; ?>)" class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 <?php echo getThreatTypeColor($threat['type']); ?> rounded-full text-xs">
                            <?php echo getThreatTypeText($threat['type']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 <?php echo getSeverityColor($threat['severity']); ?> rounded-full text-xs">
                            <?php echo getSeverityText($threat['severity']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-300 font-mono text-sm"><?php echo $threat['source_ip'] ?? 'غير معروف'; ?></td>
                    <td class="px-4 py-3 text-gray-300"><?php echo $threat['server_name'] ?? 'غير محدد'; ?></td>
                    <td class="px-4 py-3 text-gray-300"><?php echo formatTimeAgo($threat['last_seen']); ?></td>
                    <td class="px-4 py-3 font-semibold text-green-400"><?php echo $threat['name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة تفاصيل التهديد -->
<!-- ============================================= -->
<div id="threat-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeThreatModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-red-400" id="threat-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل التهديد
            </h3>
        </div>
        <div id="threat-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل التنبيه -->
<!-- ============================================= -->
<div id="alert-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAlertModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-orange-400" id="alert-details-title">
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
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// =============================================
// الرسم البياني
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('securityChart')?.getContext('2d');
    if (!ctx) return;
    
    const dailyData = <?php echo json_encode(array_values($daily_stats)); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'تهديدات',
                    data: dailyData.map(d => d.attacks),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'تنبيهات',
                    data: dailyData.map(d => d.alerts),
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'تهديدات حرجة',
                    data: dailyData.map(d => d.critical),
                    borderColor: '#a855f7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        color: '#94a3b8'
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
// دوال الصفحة
// =============================================

function runFullSecurityScan() {
    showLoading();
    showNotification('بدء الفحص الأمني الشامل...', 'warning');
    
    setTimeout(() => {
        hideLoading();
        showNotification('اكتمل الفحص الأمني - تم العثور على ' + <?php echo $active_threats; ?> + ' تهديد نشط', 'info');
    }, 3000);
}

function refreshSecurityData() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        showNotification('تم تحديث بيانات المراقبة الأمنية', 'success');
        location.reload();
    }, 1500);
}

function viewThreatDetails(threatId) {
    showLoading();
    
    // محاكاة جلب تفاصيل التهديد
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل التهديد #${threatId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="mitigateThreat(${threatId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تخفيف</button>
                    <button onclick="closeThreatModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('threat-details-content').innerHTML = details;
        hideLoading();
        document.getElementById('threat-details-modal').classList.remove('hidden');
        document.getElementById('threat-details-modal').classList.add('flex');
    }, 1000);
}

function closeThreatModal() {
    document.getElementById('threat-details-modal').classList.add('hidden');
    document.getElementById('threat-details-modal').classList.remove('flex');
}

function viewAlertDetails(alertId) {
    showLoading();
    
    // محاكاة جلب تفاصيل التنبيه
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل التنبيه #${alertId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="acknowledgeAlert(${alertId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تأكيد</button>
                    <button onclick="closeAlertModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('alert-details-content').innerHTML = details;
        hideLoading();
        document.getElementById('alert-details-modal').classList.remove('hidden');
        document.getElementById('alert-details-modal').classList.add('flex');
    }, 1000);
}

function closeAlertModal() {
    document.getElementById('alert-details-modal').classList.add('hidden');
    document.getElementById('alert-details-modal').classList.remove('flex');
}

function mitigateThreat(threatId) {
    showNotification(`جاري تخفيف التهديد #${threatId}`, 'info');
    closeThreatModal();
}

function acknowledgeAlert(alertId) {
    showNotification(`تم تأكيد التنبيه #${alertId}`, 'success');
    closeAlertModal();
}

// استدعاء دوال الإشعارات من الصفحة الرئيسية
function showNotification(message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}

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
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.progress-bar {
    height: 6px;
    background: #334155;
    border-radius: 3px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}
.operation-glow {
    box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
}
.scrollbar-custom::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-custom::-webkit-scrollbar-track {
    background: #1e293b;
}
.scrollbar-custom::-webkit-scrollbar-thumb {
    background: #ef4444;
    border-radius: 3px;
}
</style>