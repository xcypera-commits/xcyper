<?php
// =============================================
// pentest-unit/pages/dashboard.php
// لوحة التحكم الرئيسية - وحدة اختبار الاختراق
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة
    // =============================================
    
    // المشاريع قيد الفحص
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_projects WHERE status = 'in-progress'");
    $scanning_projects = $stmt->fetchColumn() ?: 0;
    
    // الثغرات الحرجة
    $stmt = $db->query("SELECT COUNT(*) FROM vulnerabilities WHERE severity = 'critical' AND status != 'fixed'");
    $critical_vulns = $stmt->fetchColumn() ?: 0;
    
    // التقارير المكتملة هذا الشهر
    $stmt = $db->query("
        SELECT COUNT(*) FROM pentest_activity_log 
        WHERE activity_type = 'report' 
        AND action = 'generate'
        AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $completed_reports = $stmt->fetchColumn() ?: 0;
    
    // معدل الكشف (نسبة الثغرات المكتشفة من إجمالي الفحوصات)
    $stmt = $db->query("
        SELECT 
            ROUND(
                (SELECT COUNT(*) FROM vulnerabilities) * 100.0 / 
                NULLIF((SELECT COUNT(*) FROM security_scans), 0), 
                1
            ) as detection_rate
    ");
    $detection_rate = $stmt->fetchColumn() ?: 94;
    
    // =============================================
    // 2. الفحوصات الجارية حالياً
    // =============================================
    
    $active_scans = $db->query("
        SELECT 
            s.*,
            p.project_name,
            p.client_name,
            p.severity as project_severity,
            t.name as tool_name,
            u.full_name as tester_name
        FROM security_scans s
        LEFT JOIN pentest_projects p ON s.project_id = p.id
        LEFT JOIN testing_tools t ON s.tool_id = t.id
        LEFT JOIN users u ON s.performed_by = u.id
        WHERE s.status = 'in-progress'
        ORDER BY s.started_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 3. المشاريع عالية الخطورة
    // =============================================
    
    $critical_projects = $db->query("
        SELECT 
            p.*,
            u.full_name as tester_name,
            (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id AND severity = 'critical') as critical_count,
            (SELECT COUNT(*) FROM vulnerabilities WHERE project_id = p.id AND severity = 'high') as high_count
        FROM pentest_projects p
        LEFT JOIN users u ON p.tester_id = u.id
        WHERE p.severity IN ('critical', 'high') 
        AND p.status = 'in-progress'
        ORDER BY 
            CASE p.severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
            END,
            p.deadline ASC
        LIMIT 3
    ")->fetchAll();
    
    // =============================================
    // 4. آخر الثغرات المكتشفة
    // =============================================
    
    $recent_vulnerabilities = $db->query("
        SELECT 
            v.*,
            p.project_name,
            u.full_name as discoverer_name
        FROM vulnerabilities v
        LEFT JOIN pentest_projects p ON v.project_id = p.id
        LEFT JOIN users u ON v.discovered_by = u.id
        WHERE v.status != 'fixed'
        ORDER BY v.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 5. إحصائيات الفحوصات حسب النوع
    // =============================================
    
    $scan_stats = $db->query("
        SELECT 
            scan_type,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM security_scans
        GROUP BY scan_type
    ")->fetchAll();
    
    // =============================================
    // 6. آخر التنبيهات الأمنية
    // =============================================
    
    $recent_alerts = $db->query("
        SELECT *
        FROM security_alerts
        WHERE status != 'resolved'
        ORDER BY 
            CASE type
                WHEN 'critical' THEN 1
                WHEN 'warning' THEN 2
                WHEN 'info' THEN 3
            END,
            created_at DESC
        LIMIT 3
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق


function getSeverityText($severity) {
    return match($severity) {
        'critical' => 'حرج',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض',
        default => $severity
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
<!-- إحصائيات سريعة - 4 بطاقات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- المشاريع قيد الفحص -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">المشاريع قيد الفحص</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $scanning_projects; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-green-500"></span>
            <span class="text-green-400 mr-2">3 نشطة الآن</span>
        </div>
    </div>

    <!-- الثغرات الحرجة -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">الثغرات الحرجة</p>
                <p class="text-3xl font-bold text-red-400"><?php echo $critical_vulns; ?></p>
            </div>
            <div class="w-12 h-12 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-red-500"></span>
            <span class="text-red-400 mr-2">تحتاج للمعالجة الفورية</span>
        </div>
    </div>

    <!-- التقارير المكتملة -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">التقارير المكتملة</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $completed_reports; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-blue-500"></span>
            <span class="text-blue-400 mr-2">هذا الشهر</span>
        </div>
    </div>

    <!-- معدل الكشف -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">معدل الكشف</p>
                <p class="text-3xl font-bold text-cyan-400"><?php echo $detection_rate; ?>%</p>
            </div>
            <div class="w-12 h-12 bg-cyan-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $detection_rate; ?>%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-left">دقة كشاف الثغرات</p>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الفحوصات الجارية والمشاريع عالية الخطورة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- الفحوصات الجارية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">الفحوصات الجارية حالياً</h3>
            <button onclick="navigateTo('tests')" class="text-sm text-yellow-400 hover:text-yellow-300">عرض الكل</button>
        </div>
        
        <?php if (empty($active_scans)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد فحوصات جارية حالياً</p>
        </div>
        <?php else: ?>
            <?php foreach ($active_scans as $scan): ?>
            <div class="cyber-border bg-slate-900 rounded-lg p-4 mb-4 last:mb-0">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-400"><?php echo $scan['scan_name']; ?></span>
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full ml-2"></div>
                        <span class="text-xs text-green-400">قيد التنفيذ</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold"><?php echo $scan['project_name'] ?? 'فحص عام'; ?></p>
                        <p class="text-xs text-gray-400 mt-1">الهدف: <?php echo $scan['target']; ?></p>
                    </div>
                    <div class="text-left">
                        <span class="text-xs text-gray-400"><?php echo $scan['tester_name'] ?? 'غير معين'; ?></span>
                        <p class="text-xs text-yellow-400 mt-1"><?php echo formatTimeAgo($scan['started_at']); ?></p>
                    </div>
                </div>
                <div class="progress-bar mt-3">
                    <?php
                    $duration = $scan['started_at'] ? time() - strtotime($scan['started_at']) : 0;
                    $estimated = 7200; // 2 ساعات افتراضية
                    $progress = min(100, round(($duration / $estimated) * 100));
                    ?>
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- المشاريع عالية الخطورة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold text-right mb-6">المشاريع عالية الخطورة</h3>
        
        <?php if (empty($critical_projects)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد مشاريع عالية الخطورة</p>
        </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($critical_projects as $project): ?>
                <div class="p-4 <?php echo $project['severity'] == 'critical' ? 'bg-red-900 bg-opacity-20' : 'bg-yellow-900 bg-opacity-20'; ?> rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-semibold text-sm"><?php echo $project['project_name']; ?></p>
                        <span class="text-xs <?php echo $project['severity'] == 'critical' ? 'text-red-400' : 'text-yellow-400'; ?>">
                            <?php echo getSeverityText($project['severity']); ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-2">العميل: <?php echo $project['client_name']; ?></p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">الثغرات الحرجة: <span class="text-red-400"><?php echo $project['critical_count']; ?></span></span>
                        <span class="text-gray-400">تقدم: <span class="text-yellow-400"><?php echo $project['progress']; ?>%</span></span>
                    </div>
                    <div class="progress-bar mt-2">
                        <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- آخر الثغرات المكتشفة والتنبيهات الأمنية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- آخر الثغرات المكتشفة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">آخر الثغرات المكتشفة</h3>
            <button onclick="navigateTo('vulnerabilities')" class="text-sm text-yellow-400 hover:text-yellow-300">عرض الكل</button>
        </div>
        
        <?php if (empty($recent_vulnerabilities)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد ثغرات مكتشفة حديثاً</p>
        </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_vulnerabilities as $vuln): ?>
                <div class="p-3 bg-slate-900 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <span class="w-2 h-2 rounded-full <?php echo $vuln['severity'] == 'critical' ? 'bg-red-500' : ($vuln['severity'] == 'high' ? 'bg-yellow-500' : 'bg-blue-500'); ?> ml-2"></span>
                            <span class="font-semibold text-sm"><?php echo $vuln['name']; ?></span>
                        </div>
                        <span class="text-xs px-2 py-1 <?php echo getSeverityColor($vuln['severity']); ?> rounded-full">
                            <?php echo getSeverityText($vuln['severity']); ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-2"><?php echo $vuln['project_name']; ?></p>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>المكتشف: <?php echo $vuln['discoverer_name'] ?? 'النظام'; ?></span>
                        <span><?php echo formatTimeAgo($vuln['created_at']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- التنبيهات الأمنية العاجلة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">التنبيهات الأمنية العاجلة</h3>
            <button onclick="navigateTo('alerts')" class="text-sm text-yellow-400 hover:text-yellow-300">عرض الكل</button>
        </div>
        
        <?php if (empty($recent_alerts)): ?>
        <div class="text-center py-8">
            <p class="text-green-400">لا توجد تنبيهات أمنية</p>
        </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recent_alerts as $alert): ?>
                <div class="p-4 <?php echo getAlertTypeColor($alert['type']); ?> rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-2 h-2 <?php echo $alert['type'] == 'critical' ? 'bg-red-500' : ($alert['type'] == 'warning' ? 'bg-yellow-500' : 'bg-blue-500'); ?> rounded-full ml-2 blink"></div>
                            <p class="font-semibold <?php echo getAlertTextColor($alert['type']); ?>"><?php echo $alert['title']; ?></p>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo formatTimeAgo($alert['created_at']); ?></span>
                    </div>
                    <p class="text-sm text-gray-300 mb-3"><?php echo $alert['description']; ?></p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400">المصدر: <?php echo $alert['source']; ?></span>
                        <button onclick="handleAlert(<?php echo $alert['id']; ?>)" class="text-xs <?php echo getAlertTextColor($alert['type']); ?> hover:opacity-75">
                            معالجة
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- أدوات الفحص السريع وإحصائيات الفحوصات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- أدوات الفحص السريع -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-6 text-right">أدوات الفحص السريع</h3>
        <div class="grid grid-cols-2 gap-4">
            <button onclick="runPortScan()" class="tool-btn p-4 rounded-lg flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-yellow-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"/>
                </svg>
                <span class="text-sm font-semibold">فحص المنافذ</span>
            </button>
            
            <button onclick="runVulnerabilityScan()" class="tool-btn p-4 rounded-lg flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span class="text-sm font-semibold">فحص الثغرات</span>
            </button>
            
            <button onclick="runWebScan()" class="tool-btn p-4 rounded-lg flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"/>
                </svg>
                <span class="text-sm font-semibold">فحص المواقع</span>
            </button>
            
            <button onclick="runNetworkScan()" class="tool-btn p-4 rounded-lg flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
                <span class="text-sm font-semibold">فحص الشبكة</span>
            </button>
        </div>
    </div>

    <!-- إحصائيات الفحوصات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-6 text-right">إحصائيات الفحوصات</h3>
        <div class="space-y-4">
            <?php 
            $scan_types = [
                'comprehensive' => 'شامل',
                'vulnerability' => 'ثغرات',
                'port' => 'منافذ',
                'web' => 'ويب',
                'network' => 'شبكة'
            ];
            foreach ($scan_stats as $stat): 
                $percentage = $stat['count'] > 0 ? round(($stat['completed'] / $stat['count']) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-400"><?php echo $scan_types[$stat['scan_type']] ?? $stat['scan_type']; ?></span>
                    <span class="text-sm text-yellow-400"><?php echo $stat['completed']; ?>/<?php echo $stat['count']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script>
// دوال الفحص السريع
function runPortScan() {
    showNotification('🚀 جاري فحص المنافذ', 'info');
}

function runVulnerabilityScan() {
    showNotification('🔍 جاري فحص الثغرات', 'info');
}

function runWebScan() {
    showNotification('🌐 جاري فحص المواقع', 'info');
}

function runNetworkScan() {
    showNotification('📡 جاري فحص الشبكة', 'info');
}

function handleAlert(alertId) {
    showNotification(`⚡ جاري معالجة التنبيه ${alertId}`, 'info');
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
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    transition: width 0.3s ease;
}
.tool-btn {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border: 1px solid rgba(245, 158, 11, 0.3);
    transition: all 0.3s ease;
}
.tool-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
    border-color: #f59e0b;
}
.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.blink {
    animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>