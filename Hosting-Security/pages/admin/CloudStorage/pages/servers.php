<?php
// =============================================
// cloud-unit/pages/servers.php
// صفحة إعدادات وإدارة الخوادم
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'create_server':
                // إنشاء خادم جديد
                $server_code = generateServerCode($db, $_POST['server_type']);
                
                $sql = "INSERT INTO cloud_servers (
                    server_name, server_code, server_type, ip_address, hostname,
                    os, cpu_cores, ram_gb, storage_gb, location, provider,
                    monthly_cost, purchase_date, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['server_name'],
                    $server_code,
                    $_POST['server_type'],
                    $_POST['ip_address'] ?? null,
                    $_POST['hostname'] ?? null,
                    $_POST['os'] ?? 'Ubuntu 22.04',
                    $_POST['cpu_cores'] ?? 2,
                    $_POST['ram_gb'] ?? 4,
                    $_POST['storage_gb'] ?? 100,
                    $_POST['location'] ?? null,
                    $_POST['provider'] ?? null,
                    $_POST['monthly_cost'] ?? 0,
                    $_POST['purchase_date'] ?? date('Y-m-d'),
                    $_POST['notes'] ?? null,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $server_id = $db->lastInsertId();
                
                logActivity($db, 'create', 'server', $server_id, 'إنشاء خادم جديد: ' . $_POST['server_name']);
                
                $response['success'] = true;
                $response['message'] = 'تم إنشاء الخادم بنجاح';
                $response['server_id'] = $server_id;
                break;
                
            case 'update_server':
                // تحديث معلومات الخادم
                $sql = "UPDATE cloud_servers SET
                    server_name = ?,
                    server_type = ?,
                    ip_address = ?,
                    hostname = ?,
                    os = ?,
                    cpu_cores = ?,
                    ram_gb = ?,
                    storage_gb = ?,
                    location = ?,
                    provider = ?,
                    monthly_cost = ?,
                    notes = ?,
                    status = ?
                    WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['server_name'],
                    $_POST['server_type'],
                    $_POST['ip_address'],
                    $_POST['hostname'],
                    $_POST['os'],
                    $_POST['cpu_cores'],
                    $_POST['ram_gb'],
                    $_POST['storage_gb'],
                    $_POST['location'],
                    $_POST['provider'],
                    $_POST['monthly_cost'],
                    $_POST['notes'],
                    $_POST['status'],
                    $_POST['server_id']
                ]);
                
                logActivity($db, 'update', 'server', $_POST['server_id'], 'تحديث معلومات الخادم');
                
                $response['success'] = true;
                $response['message'] = 'تم تحديث معلومات الخادم';
                break;
                
            case 'update_status':
                // تحديث حالة الخادم
                $sql = "UPDATE cloud_servers SET status = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['status'], $_POST['server_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم تحديث حالة الخادم';
                break;
                
            case 'update_storage':
                // تحديث استخدام التخزين
                $sql = "UPDATE cloud_servers SET storage_used_gb = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['storage_used'], $_POST['server_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم تحديث استخدام التخزين';
                break;
                
            case 'delete_server':
                // حذف خادم
                // التحقق من وجود مشاريع مرتبطة
                $check = $db->prepare("SELECT COUNT(*) FROM cloud_projects WHERE server_id = ?");
                $check->execute([$_POST['server_id']]);
                
                if ($check->fetchColumn() > 0) {
                    $response['message'] = 'لا يمكن حذف الخادم لوجود مشاريع مرتبطة به';
                    break;
                }
                
                $db->prepare("DELETE FROM cloud_servers WHERE id = ?")->execute([$_POST['server_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم حذف الخادم';
                break;
                
            case 'restart_server':
                // إعادة تشغيل الخادم
                $sql = "UPDATE cloud_servers SET last_reboot = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['server_id']]);
                
                logActivity($db, 'restart', 'server', $_POST['server_id'], 'إعادة تشغيل الخادم');
                
                $response['success'] = true;
                $response['message'] = 'تم إعادة تشغيل الخادم';
                break;
                
            case 'update_service':
                // تحديث حالة خدمة
                $sql = "UPDATE cloud_server_services SET status = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['service_status'], $_POST['service_id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم تحديث حالة الخدمة';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $type_filter = $_GET['type'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // جلب الخوادم
    $sql = "
        SELECT s.*, 
               COUNT(DISTINCT p.id) as projects_count,
               (s.storage_gb - s.storage_used_gb) as free_storage
        FROM cloud_servers s
        LEFT JOIN cloud_projects p ON s.id = p.server_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($type_filter) {
        $sql .= " AND s.server_type = ?";
        $params[] = $type_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND s.status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $sql .= " AND (s.server_name LIKE ? OR s.ip_address LIKE ? OR s.hostname LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " GROUP BY s.id ORDER BY s.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $servers = $stmt->fetchAll();
    
    // إحصائيات الخوادم
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online,
            SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning,
            SUM(cpu_cores) as total_cpu,
            SUM(ram_gb) as total_ram,
            SUM(storage_gb) as total_storage,
            SUM(storage_used_gb) as used_storage
        FROM cloud_servers
    ")->fetch();
    
    // جلب خدمات الخوادم
    $services = $db->query("
        SELECT sv.*, s.server_name
        FROM cloud_server_services sv
        LEFT JOIN cloud_servers s ON sv.server_id = s.id
        ORDER BY sv.server_id, sv.service_name
    ")->fetchAll();
    
    // آخر 5 خوادم مضافة
    $recent_servers = $db->query("
        SELECT server_name, server_type, status, created_at
        FROM cloud_servers
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // أنواع الخوادم
    $server_types = $db->query("
        SELECT server_type, COUNT(*) as count
        FROM cloud_servers
        GROUP BY server_type
        ORDER BY count DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    $servers = [];
    $services = [];
    $recent_servers = [];
    $server_types = [];
    $stats = [
        'total' => 0,
        'online' => 0,
        'offline' => 0,
        'maintenance' => 0,
        'warning' => 0,
        'total_cpu' => 0,
        'total_ram' => 0,
        'total_storage' => 0,
        'used_storage' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateServerCode($db, $type) {
    $prefixes = [
        'web' => 'SRV-WEB',
        'database' => 'SRV-DB',
        'backup' => 'SRV-BAK',
        'storage' => 'SRV-STR',
        'mail' => 'SRV-MAIL',
        'dns' => 'SRV-DNS'
    ];
    
    $prefix = $prefixes[$type] ?? 'SRV';
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cloud_servers WHERE server_code LIKE ?");
    $stmt->execute(["{$prefix}-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$number}";
}

function getServerStatusBadge($status) {
    $classes = [
        'online' => 'bg-green-600 bg-opacity-20 text-green-400',
        'offline' => 'bg-red-600 bg-opacity-20 text-red-400',
        'maintenance' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'warning' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'provisioning' => 'bg-blue-600 bg-opacity-20 text-blue-400'
    ];
    
    $texts = [
        'online' => 'نشط',
        'offline' => 'متوقف',
        'maintenance' => 'صيانة',
        'warning' => 'تحذير',
        'provisioning' => 'تجهيز'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getServerTypeBadge($type) {
    $classes = [
        'web' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'database' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'backup' => 'bg-green-600 bg-opacity-20 text-green-400',
        'storage' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'mail' => 'bg-red-600 bg-opacity-20 text-red-400',
        'dns' => 'bg-indigo-600 bg-opacity-20 text-indigo-400'
    ];
    
    $texts = [
        'web' => 'ويب',
        'database' => 'قاعدة بيانات',
        'backup' => 'نسخ احتياطي',
        'storage' => 'تخزين',
        'mail' => 'بريد',
        'dns' => 'DNS'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getServiceStatusBadge($status) {
    $classes = [
        'running' => 'bg-green-600 bg-opacity-20 text-green-400',
        'stopped' => 'bg-red-600 bg-opacity-20 text-red-400',
        'failed' => 'bg-red-600 bg-opacity-20 text-red-400',
        'starting' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'stopping' => 'bg-orange-600 bg-opacity-20 text-orange-400'
    ];
    
    $texts = [
        'running' => 'يعمل',
        'stopped' => 'متوقف',
        'failed' => 'فشل',
        'starting' => 'يبدأ',
        'stopping' => 'يتوقف'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function formatStorage($gb) {
    if ($gb < 1024) return $gb . ' GB';
    return round($gb / 1024, 1) . ' TB';
}

function logActivity($db, $type, $target, $target_id, $description) {
    $sql = "INSERT INTO cloud_activity_log (user_id, activity_type, target_type, target_id, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id'] ?? 1, $type, $target, $target_id, $description]);
}
?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري التحميل...</p>
    </div>
</div>

<!-- ============================================= -->
<!-- رأس الصفحة مع الإحصائيات -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-2xl p-8 mb-8 cyber-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">⚙️</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">إعدادات الخوادم</h1>
                <p class="text-gray-400 mt-1">إدارة وتكوين الخوادم والخدمات</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="openCreateServerModal()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">+</span>
                خادم جديد
            </button>
            <button onclick="refreshPage()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-all">
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر والبحث -->
    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-slate-700">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="بحث في الخوادم..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                   value="<?php echo htmlspecialchars($search); ?>">
            <button onclick="searchServers()" class="absolute left-2 top-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <select id="filter-type" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الأنواع</option>
            <option value="web">ويب</option>
            <option value="database">قاعدة بيانات</option>
            <option value="backup">نسخ احتياطي</option>
            <option value="storage">تخزين</option>
            <option value="mail">بريد</option>
        </select>
        
        <select id="filter-status" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
            <option value="">جميع الحالات</option>
            <option value="online">نشط</option>
            <option value="offline">متوقف</option>
            <option value="maintenance">صيانة</option>
            <option value="warning">تحذير</option>
        </select>
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي الخوادم</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total']; ?></p>
        <div class="flex items-center mt-2 text-xs">
            <span class="text-green-400 ml-2">نشط: <?php echo $stats['online']; ?></span>
            <span class="text-red-400">متوقف: <?php echo $stats['offline']; ?></span>
        </div>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">المعالجات (CPU)</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo $stats['total_cpu']; ?> نواة</p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">الذاكرة (RAM)</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['total_ram']; ?> GB</p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">سعة التخزين</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo formatStorage($stats['total_storage']); ?></p>
        <div class="progress-bar mt-2">
            <?php $storage_percent = $stats['total_storage'] > 0 ? round(($stats['used_storage'] / $stats['total_storage']) * 100) : 0; ?>
            <div class="progress-fill" style="width: <?php echo $storage_percent; ?>%"></div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة الخوادم -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">📋 قائمة الخوادم</h3>
        <span class="text-sm text-gray-400">إجمالي <?php echo count($servers); ?> خادم</span>
    </div>
    
    <?php if (empty($servers)): ?>
        <div class="text-center py-12">
            <div class="text-5xl text-gray-600 mb-4">⚙️</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد خوادم</h3>
            <p class="text-gray-500">قم بإضافة أول خادم الآن</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">المشاريع</th>
                        <th class="px-4 py-3">التخزين</th>
                        <th class="px-4 py-3">الذاكرة</th>
                        <th class="px-4 py-3">المعالج</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-4 py-3">الموقع</th>
                        <th class="px-4 py-3">اسم الخادم</th>
                        <th class="px-4 py-3">الكود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $server): 
                        $storage_percent = $server['storage_gb'] > 0 ? round(($server['storage_used_gb'] / $server['storage_gb']) * 100) : 0;
                    ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button onclick="editServer(<?php echo $server['id']; ?>)" class="text-blue-400 hover:text-blue-300 text-sm" title="تعديل">
                                    تعديل
                                </button>
                                <button onclick="restartServer(<?php echo $server['id']; ?>)" class="text-yellow-400 hover:text-yellow-300 text-sm" title="إعادة تشغيل">
                                    إعادة تشغيل
                                </button>
                                <button onclick="deleteServer(<?php echo $server['id']; ?>)" class="text-red-400 hover:text-red-300 text-sm" title="حذف">
                                    حذف
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?php echo getServerStatusBadge($server['status']); ?></td>
                        <td class="px-4 py-3"><?php echo getServerTypeBadge($server['server_type']); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo $server['projects_count']; ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <span class="text-sm ml-2"><?php echo $storage_percent; ?>%</span>
                                <div class="progress-bar w-16">
                                    <div class="progress-fill" style="width: <?php echo $storage_percent; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo $server['ram_gb']; ?> GB</td>
                        <td class="px-4 py-3 text-sm"><?php echo $server['cpu_cores']; ?> نوى</td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $server['ip_address']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $server['location'] ?? '-'; ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $server['server_name']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $server['server_code']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- خدمات الخوادم -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">🔧 خدمات الخوادم</h3>
    
    <?php if (empty($services)): ?>
        <p class="text-gray-400 text-center py-4">لا توجد خدمات</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">المنفذ</th>
                        <th class="px-4 py-3">PID</th>
                        <th class="px-4 py-3">الذاكرة</th>
                        <th class="px-4 py-3">وحدة المعالجة</th>
                        <th class="px-4 py-3">الخادم</th>
                        <th class="px-4 py-3">الخدمة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <select onchange="updateServiceStatus(<?php echo $service['id']; ?>, this.value)" 
                                    class="bg-slate-700 border border-slate-600 rounded text-xs p-1">
                                <option value="running" <?php echo $service['status'] == 'running' ? 'selected' : ''; ?>>يعمل</option>
                                <option value="stopped" <?php echo $service['status'] == 'stopped' ? 'selected' : ''; ?>>متوقف</option>
                                <option value="restarting" <?php echo $service['status'] == 'restarting' ? 'selected' : ''; ?>>إعادة تشغيل</option>
                            </select>
                        </td>
                        <td class="px-4 py-3"><?php echo getServiceStatusBadge($service['status']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $service['port'] ?? '-'; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $service['pid'] ?? '-'; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $service['memory_usage_mb'] ?? '-'; ?> MB</td>
                        <td class="px-4 py-3 text-sm"><?php echo $service['cpu_usage'] ?? '-'; ?>%</td>
                        <td class="px-4 py-3 text-sm"><?php echo $service['server_name']; ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $service['display_name'] ?? $service['service_name']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- آخر الخوادم المضافة وأنواع الخوادم -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- آخر الخوادم المضافة -->
    <?php if (!empty($recent_servers)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">آخر الخوادم المضافة</h3>
        
        <div class="space-y-3">
            <?php foreach ($recent_servers as $server): ?>
            <div class="flex items-center justify-between p-3 bg-slate-700 rounded-lg">
                <div>
                    <p class="font-semibold"><?php echo $server['server_name']; ?></p>
                    <p class="text-xs text-gray-400"><?php echo timeAgo($server['created_at']); ?></p>
                </div>
                <div class="flex items-center">
                    <?php echo getServerTypeBadge($server['server_type']); ?>
                    <span class="mr-2"><?php echo getServerStatusBadge($server['status']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- توزيع أنواع الخوادم -->
    <?php if (!empty($server_types)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توزيع أنواع الخوادم</h3>
        
        <div class="space-y-4">
            <?php foreach ($server_types as $type): ?>
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400"><?php echo getServerTypeBadge($type['server_type']); ?></span>
                    <span class="text-blue-400"><?php echo $type['count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $percent = $stats['total'] > 0 ? round(($type['count'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء خادم جديد -->
<!-- ============================================= -->
<div id="create-server-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateServerModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">إضافة خادم جديد</h3>
        </div>
        
        <form id="create-server-form" onsubmit="handleCreateServer(event)">
            <input type="hidden" name="action" value="create_server">
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">اسم الخادم</label>
                        <input type="text" name="server_name" required 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                               placeholder="مثال: سيرفر ويب رئيسي">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نوع الخادم</label>
                        <select name="server_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="web">ويب</option>
                            <option value="database">قاعدة بيانات</option>
                            <option value="backup">نسخ احتياطي</option>
                            <option value="storage">تخزين</option>
                            <option value="mail">بريد</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">عنوان IP</label>
                        <input type="text" name="ip_address" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"
                               placeholder="192.168.1.100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">اسم المضيف</label>
                        <input type="text" name="hostname" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"
                               placeholder="web01.example.com">
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">CPU (نوى)</label>
                        <input type="number" name="cpu_cores" value="2" min="1" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">RAM (GB)</label>
                        <input type="number" name="ram_gb" value="4" min="1" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">التخزين (GB)</label>
                        <input type="number" name="storage_gb" value="100" min="1" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نظام التشغيل</label>
                        <input type="text" name="os" value="Ubuntu 22.04" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الموقع</label>
                        <input type="text" name="location" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"
                               placeholder="الرياض">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">المزود</label>
                        <input type="text" name="provider" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"
                               placeholder="Local DC">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">التكلفة الشهرية</label>
                        <input type="number" name="monthly_cost" step="0.01" value="0" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                    <textarea name="notes" rows="2" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"></textarea>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateServerModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    إضافة الخادم
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تعديل خادم -->
<!-- ============================================= -->
<div id="edit-server-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeEditServerModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-right">تعديل بيانات الخادم</h3>
        </div>
        
        <form id="edit-server-form" onsubmit="handleEditServer(event)">
            <input type="hidden" name="action" value="update_server">
            <input type="hidden" name="server_id" id="edit-server-id">
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">اسم الخادم</label>
                        <input type="text" name="server_name" id="edit-server-name" required 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نوع الخادم</label>
                        <select name="server_type" id="edit-server-type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="web">ويب</option>
                            <option value="database">قاعدة بيانات</option>
                            <option value="backup">نسخ احتياطي</option>
                            <option value="storage">تخزين</option>
                            <option value="mail">بريد</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">عنوان IP</label>
                        <input type="text" name="ip_address" id="edit-server-ip" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">اسم المضيف</label>
                        <input type="text" name="hostname" id="edit-server-hostname" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">CPU</label>
                        <input type="number" name="cpu_cores" id="edit-server-cpu" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">RAM</label>
                        <input type="number" name="ram_gb" id="edit-server-ram" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">التخزين</label>
                        <input type="number" name="storage_gb" id="edit-server-storage" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نظام التشغيل</label>
                        <input type="text" name="os" id="edit-server-os" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الحالة</label>
                        <select name="status" id="edit-server-status" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="online">نشط</option>
                            <option value="offline">متوقف</option>
                            <option value="maintenance">صيانة</option>
                            <option value="warning">تحذير</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                    <textarea name="notes" id="edit-server-notes" rows="2" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg"></textarea>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeEditServerModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
// =============================================
// دوال إنشاء خادم جديد
// =============================================
function openCreateServerModal() {
    document.getElementById('create-server-modal').classList.remove('hidden');
}

function closeCreateServerModal() {
    document.getElementById('create-server-modal').classList.add('hidden');
    document.getElementById('create-server-form').reset();
}

function handleCreateServer(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-server-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeCreateServerModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ', 'error');
        console.error(error);
    });
}

// =============================================
// دوال تعديل الخادم
// =============================================
function editServer(id) {
    // جلب بيانات الخادم من الصف
    const row = event.target.closest('tr');
    if (row) {
        const cells = row.querySelectorAll('td');
        document.getElementById('edit-server-id').value = id;
        document.getElementById('edit-server-name').value = cells[9]?.textContent?.trim() || '';
        document.getElementById('edit-server-ip').value = cells[7]?.textContent?.trim() || '';
        document.getElementById('edit-server-cpu').value = cells[6]?.textContent?.trim()?.replace(' نوى', '') || '2';
        document.getElementById('edit-server-ram').value = cells[5]?.textContent?.trim()?.replace(' GB', '') || '4';
        
        // فتح النافذة
        document.getElementById('edit-server-modal').classList.remove('hidden');
    }
}

function closeEditServerModal() {
    document.getElementById('edit-server-modal').classList.add('hidden');
}

function handleEditServer(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('edit-server-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeEditServerModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    });
}

// =============================================
// دوال إدارة الخوادم
// =============================================
function restartServer(id) {
    if (!confirm('إعادة تشغيل هذا الخادم؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'restart_server');
    formData.append('server_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function deleteServer(id) {
    if (!confirm('⚠️ هل أنت متأكد من حذف هذا الخادم؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_server');
    formData.append('server_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    });
}

function updateServiceStatus(serviceId, status) {
    const formData = new FormData();
    formData.append('action', 'update_service');
    formData.append('service_id', serviceId);
    formData.append('service_status', status);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

// =============================================
// دوال الفلاتر والبحث
// =============================================
function applyFilters() {
    const type = document.getElementById('filter-type').value;
    const status = document.getElementById('filter-status').value;
    const search = document.getElementById('search-input').value;
    
    let url = '?page=servers';
    if (type) url += '&type=' + type;
    if (status) url += '&status=' + status;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

function searchServers() {
    applyFilters();
}

function resetFilters() {
    window.location.href = '?page=servers';
}

function refreshPage() {
    location.reload();
}

// =============================================
// دوال مساعدة
// =============================================
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg text-sm`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}

// إغلاق النوافذ بالـ ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateServerModal();
        closeEditServerModal();
    }
});
</script>