<?php
// =============================================
// cloud-unit/pages/dashboard.php
// لوحة التحكم الرئيسية - وحدة الاستضافة والتخزين السحابي
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
    
    // إحصائيات الخوادم
    $servers_stats = $db->query("
        SELECT 
            COUNT(*) as total_servers,
            SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_servers,
            SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_servers,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_servers,
            SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_servers,
            COALESCE(SUM(storage_gb), 0) as total_storage,
            COALESCE(SUM(storage_used_gb), 0) as used_storage
        FROM cloud_servers
    ")->fetch();
    
    // إحصائيات المشاريع
    $projects_stats = $db->query("
        SELECT 
            COUNT(*) as total_projects,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_projects,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_projects,
            SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_projects,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_projects
        FROM cloud_projects
    ")->fetch();
    
    // إحصائيات الملفات
    $files_stats = $db->query("
        SELECT 
            COUNT(*) as total_files,
            SUM(CASE WHEN is_folder = 1 THEN 1 ELSE 0 END) as total_folders,
            COALESCE(SUM(file_size), 0) as total_size,
            COUNT(DISTINCT file_type) as file_types_count
        FROM cloud_files
    ")->fetch();
    
    // إحصائيات النسخ الاحتياطي
    $backup_stats = $db->query("
        SELECT 
            COUNT(*) as total_backups,
            COALESCE(SUM(size_mb), 0) as total_backup_size,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_backups,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_backups
        FROM cloud_backups
    ")->fetch();
    
    // إحصائيات عمليات النشر
    $deployment_stats = $db->query("
        SELECT 
            COUNT(*) as total_deployments,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_deployments,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_deployments,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_deployments
        FROM cloud_deployments
    ")->fetch();
    
    // إحصائيات التحديثات الأمنية
    $security_stats = $db->query("
        SELECT 
            COUNT(*) as total_updates,
            SUM(CASE WHEN severity = 'critical' AND status = 'pending' THEN 1 ELSE 0 END) as critical_pending,
            SUM(CASE WHEN severity = 'high' AND status = 'pending' THEN 1 ELSE 0 END) as high_pending,
            SUM(CASE WHEN severity = 'medium' AND status = 'pending' THEN 1 ELSE 0 END) as medium_pending
        FROM cloud_security_updates
    ")->fetch();
    
    // =============================================
    // 2. آخر الخوادم النشطة
    // =============================================
    
    $recent_servers = $db->query("
        SELECT 
            id, server_name, server_code, server_type, ip_address, 
            status, cpu_cores, ram_gb, storage_used_gb, storage_gb
        FROM cloud_servers
        ORDER BY 
            CASE status
                WHEN 'online' THEN 1
                WHEN 'warning' THEN 2
                WHEN 'maintenance' THEN 3
                WHEN 'offline' THEN 4
                ELSE 5
            END,
            updated_at DESC
        LIMIT 4
    ")->fetchAll();
    
    // =============================================
    // 3. آخر المشاريع النشطة
    // =============================================
    
    $recent_projects = $db->query("
        SELECT p.*, s.server_name
        FROM cloud_projects p
        LEFT JOIN cloud_servers s ON p.server_id = s.id
        WHERE p.status = 'active'
        ORDER BY 
            CASE p.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END,
            p.updated_at DESC
        LIMIT 4
    ")->fetchAll();
    
    // =============================================
    // 4. آخر الملفات المضافة
    // =============================================
    
    $recent_files = $db->query("
        SELECT f.*, p.project_name
        FROM cloud_files f
        LEFT JOIN cloud_projects p ON f.project_id = p.id
        WHERE f.is_folder = 0
        ORDER BY f.created_at DESC
        LIMIT 8
    ")->fetchAll();
    
    // =============================================
    // 5. آخر عمليات النشر
    // =============================================
    
    $recent_deployments = $db->query("
        SELECT d.*, p.project_name
        FROM cloud_deployments d
        LEFT JOIN cloud_projects p ON d.project_id = p.id
        ORDER BY d.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 6. آخر النسخ الاحتياطية
    // =============================================
    
    $recent_backups = $db->query("
        SELECT b.*, p.project_name, s.server_name
        FROM cloud_backups b
        LEFT JOIN cloud_projects p ON b.project_id = p.id
        LEFT JOIN cloud_servers s ON b.server_id = s.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 7. آخر التنبيهات الأمنية
    // =============================================
    
    $recent_alerts = $db->query("
        SELECT a.*, s.server_name
        FROM cloud_storage_alerts a
        LEFT JOIN cloud_servers s ON a.server_id = s.id
        WHERE a.is_resolved = 0
        ORDER BY 
            CASE a.severity
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END,
            a.created_at DESC
        LIMIT 3
    ")->fetchAll();
    
    // =============================================
    // 8. إحصائيات استخدام التخزين اليومية
    // =============================================
    
    $daily_stats = $db->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as files_count,
            COALESCE(SUM(file_size), 0) as total_size
        FROM cloud_files
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ")->fetchAll();
    
    // =============================================
    // 9. أنواع الملفات (للدونات)
    // =============================================
    
    $file_types_distribution = $db->query("
        SELECT 
            file_type,
            COUNT(*) as count,
            COALESCE(SUM(file_size), 0) as total_size
        FROM cloud_files
        WHERE is_folder = 0
        GROUP BY file_type
        ORDER BY count DESC
        LIMIT 6
    ")->fetchAll();
    
    // =============================================
    // 10. المشاريع حسب الأولوية
    // =============================================
    
    $projects_by_priority = $db->query("
        SELECT 
            priority,
            COUNT(*) as count
        FROM cloud_projects
        WHERE status = 'active'
        GROUP BY priority
        ORDER BY 
            CASE priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END
    ")->fetchAll();
    
    // =============================================
    // حساب النسب المئوية
    // =============================================
    
    $storage_percent = $servers_stats['total_storage'] > 0 
        ? round(($servers_stats['used_storage'] / $servers_stats['total_storage']) * 100, 1) 
        : 0;
    
    $backup_success_rate = $backup_stats['total_backups'] > 0 
        ? round(($backup_stats['completed_backups'] / $backup_stats['total_backups']) * 100, 1) 
        : 0;
    
    $deployment_success_rate = $deployment_stats['total_deployments'] > 0 
        ? round(($deployment_stats['successful_deployments'] / $deployment_stats['total_deployments']) * 100, 1) 
        : 0;
    
} catch (Exception $e) {
    error_log("خطأ في جلب البيانات: " . $e->getMessage());
    
    // قيم افتراضية
    $servers_stats = ['total_servers' => 0, 'online_servers' => 0, 'offline_servers' => 0, 'maintenance_servers' => 0, 'warning_servers' => 0, 'total_storage' => 0, 'used_storage' => 0];
    $projects_stats = ['total_projects' => 0, 'active_projects' => 0, 'inactive_projects' => 0, 'suspended_projects' => 0, 'critical_projects' => 0, 'high_priority_projects' => 0];
    $files_stats = ['total_files' => 0, 'total_folders' => 0, 'total_size' => 0, 'file_types_count' => 0];
    $backup_stats = ['total_backups' => 0, 'total_backup_size' => 0, 'completed_backups' => 0, 'failed_backups' => 0];
    $deployment_stats = ['total_deployments' => 0, 'successful_deployments' => 0, 'failed_deployments' => 0, 'today_deployments' => 0];
    $security_stats = ['total_updates' => 0, 'critical_pending' => 0, 'high_pending' => 0, 'medium_pending' => 0];
    $recent_servers = [];
    $recent_projects = [];
    $recent_files = [];
    $recent_deployments = [];
    $recent_backups = [];
    $recent_alerts = [];
    $daily_stats = [];
    $file_types_distribution = [];
    $projects_by_priority = [];
    $storage_percent = 0;
    $backup_success_rate = 0;
    $deployment_success_rate = 0;
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}
?>

<!-- ============================================= -->
<!-- إحصائيات سريعة - 4 بطاقات رئيسية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- بطاقة التخزين -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">إجمالي التخزين</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo round($servers_stats['total_storage'] / 1000, 1); ?> TB</p>
            </div>
            <div class="w-12 h-12 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $storage_percent; ?>%"></div>
            </div>
            <div class="flex items-center justify-between text-sm mt-2">
                <span class="text-gray-400">مستخدم</span>
                <span class="text-blue-400"><?php echo round($servers_stats['used_storage'] / 1000, 1); ?> TB</span>
            </div>
        </div>
    </div>

    <!-- بطاقة الخوادم -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">الخوادم النشطة</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $servers_stats['online_servers']; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm">
            <div class="flex items-center">
                <span class="status-indicator bg-green-500"></span>
                <span class="text-green-400 mr-2"><?php echo $servers_stats['online_servers']; ?> نشط</span>
            </div>
            <div class="flex items-center">
                <span class="status-indicator bg-yellow-500"></span>
                <span class="text-yellow-400 mr-2"><?php echo $servers_stats['warning_servers']; ?> تحذير</span>
            </div>
        </div>
    </div>

    <!-- بطاقة المشاريع -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">المشاريع النشطة</p>
                <p class="text-3xl font-bold text-purple-400"><?php echo $projects_stats['active_projects']; ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm">
            <span class="text-gray-400">إجمالي</span>
            <span class="text-purple-400"><?php echo $projects_stats['total_projects']; ?> مشروع</span>
        </div>
        <?php if ($projects_stats['critical_projects'] > 0): ?>
        <div class="mt-2 flex items-center">
            <span class="status-indicator bg-red-500"></span>
            <span class="text-red-400 mr-2 text-sm"><?php echo $projects_stats['critical_projects']; ?> مشروع حرج</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- بطاقة الملفات -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">إجمالي الملفات</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo number_format($files_stats['total_files']); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm">
            <span class="text-gray-400">الحجم الكلي</span>
            <span class="text-yellow-400"><?php echo round($files_stats['total_size'] / (1024 * 1024 * 1024), 2); ?> GB</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الإجراءات السريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-xl font-bold mb-6 text-right">الإجراءات السريعة</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <button onclick="navigateTo('files')" class="card-hover file-card bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <span class="text-sm font-semibold">رفع ملفات</span>
        </button>

        <button onclick="navigateTo('servers')" class="card-hover server-card bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            <span class="text-sm font-semibold">إضافة خادم</span>
        </button>

        <button onclick="navigateTo('deployment')" class="card-hover monitor-card bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-yellow-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span class="text-sm font-semibold">نشر تطبيق</span>
        </button>

        <button onclick="navigateTo('backup')" class="card-hover backup-card bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-purple-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span class="text-sm font-semibold">نسخ احتياطي</span>
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- الصف الثاني: الخوادم النشطة والتنبيهات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- الخوادم النشطة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">الخوادم النشطة</h3>
            <a href="?page=servers" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_servers)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد خوادم</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_servers as $server): ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                    <div class="flex items-center">
                        <span class="w-2 h-2 rounded-full ml-3 <?php 
                            echo $server['status'] == 'online' ? 'bg-green-500' : 
                                ($server['status'] == 'warning' ? 'bg-yellow-500' : 
                                ($server['status'] == 'maintenance' ? 'bg-blue-500' : 'bg-red-500')); 
                        ?>"></span>
                        <div>
                            <h4 class="font-semibold text-sm"><?php echo $server['server_name']; ?></h4>
                            <p class="text-xs text-gray-400"><?php echo $server['ip_address']; ?></p>
                        </div>
                    </div>
                    <div class="text-left">
                        <p class="text-xs font-mono text-blue-400"><?php echo $server['cpu_cores']; ?> نوى</p>
                        <p class="text-xs text-gray-400"><?php echo $server['ram_gb']; ?> GB</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- التنبيهات الأمنية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">التنبيهات</h3>
            <span class="text-xs bg-red-600 bg-opacity-20 text-red-400 px-2 py-1 rounded-full">
                <?php echo count($recent_alerts); ?> نشطة
            </span>
        </div>
        
        <?php if (empty($recent_alerts)): ?>
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-green-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-400">لا توجد تنبيهات</p>
                <p class="text-xs text-gray-500 mt-2">جميع الأنظمة تعمل بكفاءة</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_alerts as $alert): ?>
                <div class="p-3 rounded-lg <?php 
                    echo $alert['severity'] == 'high' ? 'bg-red-900 bg-opacity-20 border border-red-800' : 
                        ($alert['severity'] == 'medium' ? 'bg-yellow-900 bg-opacity-20 border border-yellow-800' : 
                        'bg-blue-900 bg-opacity-20 border border-blue-800'); 
                ?>">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs <?php 
                            echo $alert['severity'] == 'high' ? 'text-red-400' : 
                                ($alert['severity'] == 'medium' ? 'text-yellow-400' : 'text-blue-400'); 
                        ?>">
                            <?php echo $alert['server_name']; ?>
                        </span>
                        <span class="text-xs text-gray-400"><?php echo timeAgo($alert['created_at']); ?></span>
                    </div>
                    <p class="text-sm text-gray-300"><?php echo $alert['message']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-4 text-right">إحصائيات سريعة</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">مشاريع نشطة</span>
                    <span class="text-green-400"><?php echo $projects_stats['active_projects']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-green-500" style="width: <?php echo $projects_stats['total_projects'] > 0 ? round(($projects_stats['active_projects'] / $projects_stats['total_projects']) * 100) : 0; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">نجاح النسخ الاحتياطي</span>
                    <span class="text-blue-400"><?php echo $backup_success_rate; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $backup_success_rate; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">نجاح النشر</span>
                    <span class="text-purple-400"><?php echo $deployment_success_rate; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $deployment_success_rate; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mt-6">
            <div class="bg-slate-900 p-3 rounded-lg text-center">
                <p class="text-2xl font-bold text-yellow-400"><?php echo $security_stats['critical_pending']; ?></p>
                <p class="text-xs text-gray-400">تحديثات حرجة</p>
            </div>
            <div class="bg-slate-900 p-3 rounded-lg text-center">
                <p class="text-2xl font-bold text-orange-400"><?php echo $deployment_stats['today_deployments']; ?></p>
                <p class="text-xs text-gray-400">نشر اليوم</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الصف الثالث: المشاريع النشطة والملفات الأخيرة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- المشاريع النشطة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">المشاريع النشطة</h3>
            <a href="?page=projects" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_projects)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد مشاريع نشطة</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_projects as $project): ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm"><?php echo $project['project_name']; ?></h4>
                        <p class="text-xs text-gray-400"><?php echo $project['server_name'] ?? 'غير مرتبط'; ?></p>
                    </div>
                    <div class="flex items-center">
                        <span class="px-2 py-1 text-xs rounded-full <?php 
                            echo $project['priority'] == 'critical' ? 'bg-red-600 bg-opacity-20 text-red-400' : 
                                ($project['priority'] == 'high' ? 'bg-orange-600 bg-opacity-20 text-orange-400' : 
                                ($project['priority'] == 'medium' ? 'bg-blue-600 bg-opacity-20 text-blue-400' : 
                                'bg-green-600 bg-opacity-20 text-green-400')); 
                        ?>">
                            <?php echo $project['priority']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- آخر الملفات المضافة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">آخر الملفات المضافة</h3>
            <a href="?page=files" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_files)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد ملفات مضافة</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach (array_slice($recent_files, 0, 5) as $file): ?>
                <div class="flex items-center justify-between p-2 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                    <div class="flex items-center">
                        <?php
                        $ext = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                        $icon = match(strtolower($ext)) {
                            'pdf' => '📄',
                            'jpg', 'jpeg', 'png', 'gif' => '🖼️',
                            'mp4', 'avi', 'mov' => '🎥',
                            'mp3', 'wav' => '🎵',
                            'zip', 'rar', 'tar', 'gz' => '📦',
                            default => '📄'
                        };
                        ?>
                        <span class="text-lg ml-2"><?php echo $icon; ?></span>
                        <div>
                            <p class="text-sm font-semibold"><?php echo $file['file_name']; ?></p>
                            <p class="text-xs text-gray-400"><?php echo $file['project_name'] ?? 'عام'; ?></p>
                        </div>
                    </div>
                    <span class="text-xs text-gray-400"><?php echo formatFileSize($file['file_size']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- الصف الرابع: عمليات النشر والنسخ الاحتياطي -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- آخر عمليات النشر -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">آخر عمليات النشر</h3>
            <a href="?page=deployment" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_deployments)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد عمليات نشر</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_deployments as $deploy): ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                    <div>
                        <h4 class="font-semibold text-sm"><?php echo $deploy['project_name']; ?></h4>
                        <p class="text-xs text-gray-400">الإصدار <?php echo $deploy['version']; ?></p>
                    </div>
                    <div class="text-left">
                        <span class="text-xs px-2 py-1 rounded-full <?php 
                            echo $deploy['status'] == 'success' ? 'bg-green-600 bg-opacity-20 text-green-400' : 
                                ($deploy['status'] == 'failed' ? 'bg-red-600 bg-opacity-20 text-red-400' : 
                                'bg-yellow-600 bg-opacity-20 text-yellow-400'); 
                        ?>">
                            <?php echo $deploy['status']; ?>
                        </span>
                        <p class="text-xs text-gray-400 mt-1"><?php echo timeAgo($deploy['created_at']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- آخر النسخ الاحتياطية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">آخر النسخ الاحتياطية</h3>
            <a href="?page=backup" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_backups)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد نسخ احتياطية</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_backups as $backup): ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                    <div>
                        <h4 class="font-semibold text-sm"><?php echo $backup['backup_name']; ?></h4>
                        <p class="text-xs text-gray-400"><?php echo $backup['project_name'] ?? $backup['server_name']; ?></p>
                    </div>
                    <div class="text-left">
                        <span class="text-xs <?php echo $backup['status'] == 'completed' ? 'text-green-400' : 'text-yellow-400'; ?>">
                            <?php echo $backup['status']; ?>
                        </span>
                        <p class="text-xs text-gray-400 mt-1"><?php echo round($backup['size_mb'] / 1024, 2); ?> GB</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- الصف الخامس: إحصائيات إضافية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- توزيع أنواع الملفات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-4 text-right">توزيع أنواع الملفات</h3>
        
        <?php if (empty($file_types_distribution)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد بيانات</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($file_types_distribution as $type): ?>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-400"><?php echo strtoupper($type['file_type'] ?: 'أخرى'); ?></span>
                        <span class="text-blue-400"><?php echo round($type['total_size'] / (1024 * 1024 * 1024), 2); ?> GB</span>
                    </div>
                    <div class="progress-bar">
                        <?php $percent = $files_stats['total_size'] > 0 ? round(($type['total_size'] / $files_stats['total_size']) * 100, 1) : 0; ?>
                        <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1"><?php echo $type['count']; ?> ملف • <?php echo $percent; ?>%</p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- المشاريع حسب الأولوية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-4 text-right">المشاريع حسب الأولوية</h3>
        
        <?php if (empty($projects_by_priority)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد مشاريع</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php 
                $colors = [
                    'critical' => 'red',
                    'high' => 'orange',
                    'medium' => 'blue',
                    'low' => 'green'
                ];
                
                foreach ($projects_by_priority as $item): 
                    $color = $colors[$item['priority']] ?? 'gray';
                ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-400"><?php echo ucfirst($item['priority']); ?></span>
                    <div class="flex-1 mx-4">
                        <div class="progress-bar">
                            <?php $percent = $projects_stats['total_projects'] > 0 ? round(($item['count'] / $projects_stats['total_projects']) * 100) : 0; ?>
                            <div class="progress-fill bg-<?php echo $color; ?>-500" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-<?php echo $color; ?>-400"><?php echo $item['count']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>