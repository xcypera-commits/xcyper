<?php
// =============================================
// cloud-unit/pages/deployment.php
// صفحة عمليات النشر والتطبيقات
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
            case 'create_deployment':
                // إنشاء نشر جديد
                $deployment_code = generateDeploymentCode($db);
                
                $sql = "INSERT INTO cloud_deployments (
                    deployment_code, project_id, deployment_type, environment,
                    status, version, commit_hash, branch, files_count, size_mb,
                    started_at, deployed_by, created_at
                ) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW(), ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $deployment_code,
                    $_POST['project_id'],
                    $_POST['deployment_type'] ?? 'full',
                    $_POST['environment'] ?? 'production',
                    $_POST['version'] ?? '1.0.0',
                    $_POST['commit_hash'] ?? null,
                    $_POST['branch'] ?? 'main',
                    $_POST['files_count'] ?? 0,
                    $_POST['size_mb'] ?? 0,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                $deployment_id = $db->lastInsertId();
                
                // تسجيل النشاط
                logActivity($db, 'create', 'deployment', $deployment_id, 'إنشاء عملية نشر جديدة');
                
                $response['success'] = true;
                $response['message'] = '✅ تم إنشاء عملية النشر بنجاح';
                $response['deployment_id'] = $deployment_id;
                break;
                
            case 'start_deployment':
                // بدء عملية نشر
                $sql = "UPDATE cloud_deployments SET 
                        status = 'in_progress', 
                        started_at = NOW() 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['deployment_id']]);
                
                logActivity($db, 'start', 'deployment', $_POST['deployment_id'], 'بدء عملية نشر');
                
                $response['success'] = true;
                $response['message'] = '✅ تم بدء عملية النشر';
                break;
                
            case 'complete_deployment':
                // إكمال عملية نشر بنجاح
                $sql = "UPDATE cloud_deployments SET 
                        status = 'success', 
                        completed_at = NOW() 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['deployment_id']]);
                
                logActivity($db, 'complete', 'deployment', $_POST['deployment_id'], 'إكمال عملية نشر بنجاح');
                
                $response['success'] = true;
                $response['message'] = '✅ تم إكمال عملية النشر بنجاح';
                break;
                
            case 'fail_deployment':
                // فشل عملية نشر
                $sql = "UPDATE cloud_deployments SET 
                        status = 'failed', 
                        completed_at = NOW(),
                        error_log = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['error_log'], $_POST['deployment_id']]);
                
                logActivity($db, 'fail', 'deployment', $_POST['deployment_id'], 'فشل عملية نشر');
                
                $response['success'] = true;
                $response['message'] = '⚠️ تم تسجيل فشل عملية النشر';
                break;
                
            case 'rollback_deployment':
                // التراجع عن نشر
                $sql = "UPDATE cloud_deployments SET 
                        status = 'rolled_back', 
                        completed_at = NOW() 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['deployment_id']]);
                
                logActivity($db, 'rollback', 'deployment', $_POST['deployment_id'], 'التراجع عن نشر');
                
                $response['success'] = true;
                $response['message'] = '✅ تم التراجع عن عملية النشر';
                break;
                
            case 'delete_deployment':
                // حذف عملية نشر
                $db->prepare("DELETE FROM cloud_deployments WHERE id = ?")->execute([$_POST['deployment_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم حذف عملية النشر';
                break;
                
            case 'save_logs':
                // حفظ سجلات النشر
                $sql = "UPDATE cloud_deployments SET logs = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['logs'], $_POST['deployment_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم حفظ السجلات';
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
    $project_filter = $_GET['project'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $environment_filter = $_GET['environment'] ?? '';
    $date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $_GET['to'] ?? date('Y-m-d');
    
    // جلب عمليات النشر
    $sql = "
        SELECT d.*, 
               p.project_name,
               p.domain,
               p.git_repo,
               p.deploy_path,
               u.full_name as deployed_by_name
        FROM cloud_deployments d
        LEFT JOIN cloud_projects p ON d.project_id = p.id
        LEFT JOIN users u ON d.deployed_by = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($project_filter) {
        $sql .= " AND d.project_id = ?";
        $params[] = $project_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND d.status = ?";
        $params[] = $status_filter;
    }
    
    if ($environment_filter) {
        $sql .= " AND d.environment = ?";
        $params[] = $environment_filter;
    }
    
    if ($date_from && $date_to) {
        $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY d.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $deployments = $stmt->fetchAll();
    
    // إحصائيات عمليات النشر
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'rolled_back' THEN 1 ELSE 0 END) as rolled_back,
            SUM(CASE WHEN environment = 'production' THEN 1 ELSE 0 END) as production,
            SUM(CASE WHEN environment = 'staging' THEN 1 ELSE 0 END) as staging,
            SUM(CASE WHEN environment = 'development' THEN 1 ELSE 0 END) as development,
            AVG(CASE WHEN status = 'success' THEN TIMESTAMPDIFF(SECOND, started_at, completed_at) ELSE NULL END) as avg_duration
        FROM cloud_deployments
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch();
    
    // إحصائيات شهرية
    $monthly_stats = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful
        FROM cloud_deployments
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();
    
    // قائمة المشاريع للفلتر
    $projects = $db->query("
        SELECT id, project_name, domain, git_repo
        FROM cloud_projects
        WHERE status = 'active'
        ORDER BY project_name
    ")->fetchAll();
    
    // آخر عمليات النشر (للعرض السريع)
    $recent_deployments = $db->query("
        SELECT d.*, p.project_name
        FROM cloud_deployments d
        LEFT JOIN cloud_projects p ON d.project_id = p.id
        ORDER BY d.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // أكثر المشاريع نشراً
    $top_projects = $db->query("
        SELECT p.id, p.project_name, COUNT(d.id) as deploy_count
        FROM cloud_projects p
        LEFT JOIN cloud_deployments d ON p.id = d.project_id
        GROUP BY p.id
        ORDER BY deploy_count DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $deployments = [];
    $recent_deployments = [];
    $top_projects = [];
    $monthly_stats = [];
    $projects = [];
    $stats = [
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
        'in_progress' => 0,
        'pending' => 0,
        'rolled_back' => 0,
        'production' => 0,
        'staging' => 0,
        'development' => 0,
        'avg_duration' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateDeploymentCode($db) {
    $year = date('Y');
    $month = date('m');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cloud_deployments WHERE deployment_code LIKE ?");
    $stmt->execute(["DEP-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "DEP-{$year}-{$number}";
}

function getDeploymentStatusBadge($status) {
    $classes = [
        'pending' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'in_progress' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'success' => 'bg-green-600 bg-opacity-20 text-green-400',
        'failed' => 'bg-red-600 bg-opacity-20 text-red-400',
        'rolled_back' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'cancelled' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'pending' => '⏳ قيد الانتظار',
        'in_progress' => '🔄 جاري النشر',
        'success' => '✅ ناجح',
        'failed' => '❌ فاشل',
        'rolled_back' => '↩️ تم التراجع',
        'cancelled' => '🚫 ملغي'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getDeploymentTypeBadge($type) {
    $classes = [
        'full' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'incremental' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'quick' => 'bg-green-600 bg-opacity-20 text-green-400',
        'rollback' => 'bg-orange-600 bg-opacity-20 text-orange-400'
    ];
    
    $texts = [
        'full' => 'نشر كامل',
        'incremental' => 'نشر تزايدي',
        'quick' => 'نشر سريع',
        'rollback' => 'تراجع'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getEnvironmentBadge($env) {
    $classes = [
        'production' => 'bg-red-600 bg-opacity-20 text-red-400',
        'staging' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'development' => 'bg-green-600 bg-opacity-20 text-green-400',
        'testing' => 'bg-blue-600 bg-opacity-20 text-blue-400'
    ];
    
    $texts = [
        'production' => 'إنتاج',
        'staging' => 'تجريبي',
        'development' => 'تطوير',
        'testing' => 'اختبار'
    ];
    
    $class = $classes[$env] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$env] ?? $env;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function formatDuration($seconds) {
    if (!$seconds) return '-';
    
    if ($seconds < 60) return $seconds . ' ثانية';
    if ($seconds < 3600) return floor($seconds / 60) . ' دقيقة';
    return floor($seconds / 3600) . ' ساعة';
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
<div class="bg-gradient-to-l from-indigo-900 via-blue-900 to-purple-900 rounded-2xl p-8 mb-8 cyber-border shadow-2xl">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg transform hover:scale-110 transition-transform">
                <span class="text-4xl">🚀</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center">
                    عمليات النشر
                    <span class="mr-3 px-3 py-1 bg-blue-600 bg-opacity-30 rounded-full text-sm text-blue-200">CI/CD</span>
                </h1>
                <p class="text-blue-200 mt-1 flex items-center">
                    <span class="ml-2">📦</span>
                    نشر وإدارة التطبيقات والمواقع المستضافة
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="openCreateDeploymentModal()" class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 rounded-xl font-semibold transition-all flex items-center shadow-lg transform hover:scale-105">
                <span class="ml-2 text-xl">🆕</span>
                نشر جديد
            </button>
            <button onclick="refreshPage()" class="px-5 py-3 bg-gray-700 hover:bg-gray-600 rounded-xl font-semibold transition-all flex items-center shadow-lg">
                <span class="ml-2">🔄</span>
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر -->
    <div class="mt-6 flex flex-wrap items-center gap-3 bg-slate-800 bg-opacity-50 rounded-xl p-4">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="🔍 بحث في عمليات النشر..." 
                   class="w-full px-6 py-3 bg-slate-700 border border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 text-right">
            <button class="absolute left-3 top-3 text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <select id="filter-project" onchange="applyFilters()" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-sm">
            <option value="">جميع المشاريع</option>
            <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
            <?php endforeach; ?>
        </select>
        
        <select id="filter-status" onchange="applyFilters()" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-sm">
            <option value="">جميع الحالات</option>
            <option value="pending">⏳ قيد الانتظار</option>
            <option value="in_progress">🔄 جاري النشر</option>
            <option value="success">✅ ناجح</option>
            <option value="failed">❌ فاشل</option>
            <option value="rolled_back">↩️ متراجع</option>
        </select>
        
        <select id="filter-environment" onchange="applyFilters()" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-sm">
            <option value="">جميع البيئات</option>
            <option value="production">🔴 إنتاج</option>
            <option value="staging">🟡 تجريبي</option>
            <option value="development">🟢 تطوير</option>
        </select>
        
        <input type="date" id="date-from" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-sm">
        <input type="date" id="date-to" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-sm">
        
        <button onclick="resetFilters()" class="px-4 py-3 bg-gray-600 hover:bg-gray-700 rounded-xl text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">إجمالي عمليات النشر</p>
                <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total']; ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-blue-400">📊</span>
            </div>
        </div>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">نشر ناجح</p>
                <p class="text-2xl font-bold text-green-400"><?php echo $stats['successful']; ?></p>
            </div>
            <div class="w-10 h-10 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-green-400">✅</span>
            </div>
        </div>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">نشر فاشل</p>
                <p class="text-2xl font-bold text-red-400"><?php echo $stats['failed']; ?></p>
            </div>
            <div class="w-10 h-10 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-red-400">❌</span>
            </div>
        </div>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">نشر قيد التنفيذ</p>
                <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['in_progress']; ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-yellow-400">🔄</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات إضافية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- توزيع البيئات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توزيع البيئات</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">🔴 إنتاج</span>
                    <span class="text-red-400"><?php echo $stats['production']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $prod_percent = $stats['total'] > 0 ? round(($stats['production'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-red-500" style="width: <?php echo $prod_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">🟡 تجريبي</span>
                    <span class="text-yellow-400"><?php echo $stats['staging']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $staging_percent = $stats['total'] > 0 ? round(($stats['staging'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-yellow-500" style="width: <?php echo $staging_percent; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">🟢 تطوير</span>
                    <span class="text-green-400"><?php echo $stats['development']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $dev_percent = $stats['total'] > 0 ? round(($stats['development'] / $stats['total']) * 100) : 0; ?>
                    <div class="progress-fill bg-green-500" style="width: <?php echo $dev_percent; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 pt-4 border-t border-slate-700">
            <div class="flex items-center justify-between">
                <span class="text-gray-400">⏱️ متوسط مدة النشر</span>
                <span class="text-blue-400"><?php echo formatDuration($stats['avg_duration']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- أكثر المشاريع نشراً -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">أكثر المشاريع نشراً</h3>
        
        <?php if (empty($top_projects)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد بيانات</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($top_projects as $project): ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-300"><?php echo $project['project_name']; ?></span>
                    <span class="text-sm bg-blue-600 bg-opacity-20 text-blue-400 px-2 py-1 rounded-full">
                        <?php echo $project['deploy_count']; ?> نشر
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-6 pt-4 border-t border-slate-700">
            <div class="flex items-center justify-between">
                <span class="text-gray-400">📊 نسبة النجاح</span>
                <span class="text-green-400">
                    <?php 
                    $success_rate = $stats['total'] > 0 
                        ? round(($stats['successful'] / $stats['total']) * 100, 1) 
                        : 0;
                    echo $success_rate; ?>%
                </span>
            </div>
            <div class="progress-bar mt-2">
                <div class="progress-fill bg-green-500" style="width: <?php echo $success_rate; ?>%"></div>
            </div>
        </div>
    </div>
    
    <!-- آخر عمليات النشر -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">آخر عمليات النشر</h3>
        
        <?php if (empty($recent_deployments)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد عمليات نشر</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_deployments as $deploy): ?>
                <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-semibold"><?php echo $deploy['project_name']; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $deploy['deployment_code']; ?></p>
                    </div>
                    <?php echo getDeploymentStatusBadge($deploy['status']); ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- معالج النشر السريع -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-lg font-bold mb-6">⚡ معالج النشر السريع</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- الخطوة 1: اختيار المشروع -->
        <div class="bg-slate-900 rounded-lg p-5">
            <div class="flex items-center mb-4">
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center ml-3">
                    <span class="text-white font-bold">1</span>
                </div>
                <h4 class="font-semibold">اختر المشروع</h4>
            </div>
            
            <select id="quick-project" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">-- اختر مشروع --</option>
                <?php foreach ($projects as $project): ?>
                <option value="<?php echo $project['id']; ?>">
                    <?php echo $project['project_name']; ?> (<?php echo $project['domain'] ?? 'بدون نطاق'; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- الخطوة 2: إعدادات النشر -->
        <div class="bg-slate-900 rounded-lg p-5">
            <div class="flex items-center mb-4">
                <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center ml-3">
                    <span class="text-white font-bold">2</span>
                </div>
                <h4 class="font-semibold">إعدادات النشر</h4>
            </div>
            
            <div class="space-y-3">
                <select id="quick-environment" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg">
                    <option value="production">🔴 إنتاج</option>
                    <option value="staging">🟡 تجريبي</option>
                    <option value="development">🟢 تطوير</option>
                </select>
                
                <select id="quick-type" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg">
                    <option value="full">📦 نشر كامل</option>
                    <option value="incremental">📈 نشر تزايدي</option>
                    <option value="quick">⚡ نشر سريع</option>
                </select>
                
                <input type="text" id="quick-version" placeholder="الإصدار (مثال: 1.0.0)" 
                       class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg"
                       value="1.0.0">
            </div>
        </div>
        
        <!-- الخطوة 3: مراجعة وتنفيذ -->
        <div class="bg-slate-900 rounded-lg p-5">
            <div class="flex items-center mb-4">
                <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center ml-3">
                    <span class="text-white font-bold">3</span>
                </div>
                <h4 class="font-semibold">مراجعة وتنفيذ</h4>
            </div>
            
            <div class="space-y-4">
                <div class="p-3 bg-slate-800 rounded-lg text-sm">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-400">المشروع:</span>
                        <span id="review-project" class="text-blue-400">غير محدد</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-400">البيئة:</span>
                        <span id="review-environment" class="text-green-400">إنتاج</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">النوع:</span>
                        <span id="review-type" class="text-purple-400">نشر كامل</span>
                    </div>
                </div>
                
                <button onclick="quickDeploy()" class="w-full py-3 bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    🚀 بدء النشر
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة عمليات النشر -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold">📋 سجل عمليات النشر</h3>
        <span class="text-sm text-gray-400">إجمالي <?php echo count($deployments); ?> عملية</span>
    </div>
    
    <?php if (empty($deployments)): ?>
        <div class="text-center py-12">
            <div class="text-6xl text-gray-600 mb-4">📭</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد عمليات نشر</h3>
            <p class="text-gray-500">قم بإنشاء أول عملية نشر الآن</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-4 py-3">الإجراءات</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">البيئة</th>
                        <th class="px-4 py-3">المدة</th>
                        <th class="px-4 py-3">التاريخ</th>
                        <th class="px-4 py-3">المنفذ</th>
                        <th class="px-4 py-3">الإصدار</th>
                        <th class="px-4 py-3">المشروع</th>
                        <th class="px-4 py-3">الكود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deployments as $deploy): 
                        $duration = null;
                        if ($deploy['started_at'] && $deploy['completed_at']) {
                            $start = strtotime($deploy['started_at']);
                            $end = strtotime($deploy['completed_at']);
                            $duration = $end - $start;
                        }
                    ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <?php if ($deploy['status'] == 'pending'): ?>
                                <button onclick="startDeployment(<?php echo $deploy['id']; ?>)" class="text-green-400 hover:text-green-300" title="بدء">
                                    ▶️
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($deploy['status'] == 'in_progress'): ?>
                                <button onclick="completeDeployment(<?php echo $deploy['id']; ?>)" class="text-green-400 hover:text-green-300" title="إكمال">
                                    ✅
                                </button>
                                <button onclick="failDeployment(<?php echo $deploy['id']; ?>)" class="text-red-400 hover:text-red-300" title="فشل">
                                    ❌
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($deploy['status'] == 'success'): ?>
                                <button onclick="rollbackDeployment(<?php echo $deploy['id']; ?>)" class="text-yellow-400 hover:text-yellow-300" title="تراجع">
                                    ↩️
                                </button>
                                <?php endif; ?>
                                
                                <button onclick="viewLogs(<?php echo $deploy['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="سجلات">
                                    📋
                                </button>
                                
                                <button onclick="deleteDeployment(<?php echo $deploy['id']; ?>)" class="text-red-400 hover:text-red-300" title="حذف">
                                    🗑️
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?php echo getDeploymentStatusBadge($deploy['status']); ?></td>
                        <td class="px-4 py-3"><?php echo getDeploymentTypeBadge($deploy['deployment_type']); ?></td>
                        <td class="px-4 py-3"><?php echo getEnvironmentBadge($deploy['environment']); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo formatDuration($duration); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo timeAgo($deploy['created_at']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $deploy['deployed_by_name'] ?? 'النظام'; ?></td>
                        <td class="px-4 py-3 text-sm text-blue-400">v<?php echo $deploy['version']; ?></td>
                        <td class="px-4 py-3 font-semibold"><?php echo $deploy['project_name']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $deploy['deployment_code']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء نشر جديد -->
<!-- ============================================= -->
<div id="create-deployment-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateDeploymentModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">إنشاء عملية نشر جديدة</h3>
        </div>
        
        <form id="create-deployment-form" onsubmit="handleCreateDeployment(event)">
            <input type="hidden" name="action" value="create_deployment">
            
            <div class="space-y-4">
                <!-- اختيار المشروع -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المشروع <span class="text-red-400">*</span></label>
                    <select name="project_id" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- اختر المشروع --</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>">
                            <?php echo $project['project_name']; ?> (<?php echo $project['domain'] ?? 'بدون نطاق'; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- نوع النشر والبيئة -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">نوع النشر</label>
                        <select name="deployment_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="full">📦 نشر كامل</option>
                            <option value="incremental">📈 نشر تزايدي</option>
                            <option value="quick">⚡ نشر سريع</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">البيئة</label>
                        <select name="environment" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="production">🔴 إنتاج</option>
                            <option value="staging">🟡 تجريبي</option>
                            <option value="development">🟢 تطوير</option>
                        </select>
                    </div>
                </div>
                
                <!-- الإصدار والفرع -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الإصدار</label>
                        <input type="text" name="version" value="1.0.0" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الفرع (Branch)</label>
                        <input type="text" name="branch" value="main" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <!-- Commit Hash -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">Commit Hash</label>
                    <input type="text" name="commit_hash" placeholder="مثال: a1b2c3d4..." 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                </div>
                
                <!-- معلومات الملفات -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">عدد الملفات</label>
                        <input type="number" name="files_count" value="0" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الحجم (MB)</label>
                        <input type="number" name="size_mb" value="0" step="0.1"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateDeploymentModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    إنشاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة عرض السجلات -->
<!-- ============================================= -->
<div id="logs-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-3xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeLogsModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-yellow-400">سجلات النشر</h3>
        </div>
        
        <div class="bg-slate-900 rounded-lg p-4 mb-4 overflow-y-auto max-h-96 font-mono text-sm text-green-400">
            <div id="logs-content">
                <p>[2024-01-15 10:30:00] بدء عملية النشر...</p>
                <p>[2024-01-15 10:30:05] جلب الكود من المستودع</p>
                <p>[2024-01-15 10:30:10] بناء التطبيق</p>
                <p>[2024-01-15 10:30:30] تشغيل الاختبارات</p>
                <p>[2024-01-15 10:30:45] نشر الملفات</p>
                <p>[2024-01-15 10:31:00] إعادة تشغيل الخدمات</p>
                <p>[2024-01-15 10:31:15] ✅ تم النشر بنجاح</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="closeLogsModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                إغلاق
            </button>
            <button onclick="copyLogs()" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                نسخ السجلات
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
// =============================================
// المتغيرات العامة
// =============================================
let currentDeploymentId = null;

// =============================================
// تحديث معاينة النشر السريع
// =============================================
document.getElementById('quick-project')?.addEventListener('change', function() {
    const project = this.options[this.selectedIndex]?.text.split(' (')[0] || 'غير محدد';
    document.getElementById('review-project').textContent = project;
});

document.getElementById('quick-environment')?.addEventListener('change', function() {
    const env = this.options[this.selectedIndex]?.text || 'إنتاج';
    document.getElementById('review-environment').textContent = env;
});

document.getElementById('quick-type')?.addEventListener('change', function() {
    const type = this.options[this.selectedIndex]?.text || 'نشر كامل';
    document.getElementById('review-type').textContent = type;
});

// =============================================
// دوال إنشاء نشر جديد
// =============================================
function openCreateDeploymentModal() {
    document.getElementById('create-deployment-modal').classList.remove('hidden');
}

function closeCreateDeploymentModal() {
    document.getElementById('create-deployment-modal').classList.add('hidden');
    document.getElementById('create-deployment-form').reset();
}

function handleCreateDeployment(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-deployment-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeCreateDeploymentModal();
        
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
// دوال النشر السريع
// =============================================
function quickDeploy() {
    const projectId = document.getElementById('quick-project').value;
    const environment = document.getElementById('quick-environment').value;
    const type = document.getElementById('quick-type').value;
    const version = document.getElementById('quick-version').value;
    
    if (!projectId) {
        showNotification('❌ الرجاء اختيار مشروع', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_deployment');
    formData.append('project_id', projectId);
    formData.append('environment', environment);
    formData.append('deployment_type', type);
    formData.append('version', version);
    
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
            showNotification('🚀 تم بدء عملية النشر', 'success');
            
            // بدء النشر تلقائياً
            setTimeout(() => {
                startDeployment(data.deployment_id);
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    });
}

// =============================================
// دوال إدارة عمليات النشر
// =============================================
function startDeployment(id) {
    if (!confirm('بدء عملية النشر؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'start_deployment');
    formData.append('deployment_id', id);
    
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

function completeDeployment(id) {
    if (!confirm('إكمال عملية النشر بنجاح؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'complete_deployment');
    formData.append('deployment_id', id);
    
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

function failDeployment(id) {
    const errorLog = prompt('أدخل سبب الفشل:');
    if (errorLog === null) return;
    
    const formData = new FormData();
    formData.append('action', 'fail_deployment');
    formData.append('deployment_id', id);
    formData.append('error_log', errorLog);
    
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
            showNotification(data.message, 'warning');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function rollbackDeployment(id) {
    if (!confirm('التراجع عن هذا النشر؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'rollback_deployment');
    formData.append('deployment_id', id);
    
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

function deleteDeployment(id) {
    if (!confirm('⚠️ هل أنت متأكد من حذف عملية النشر؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_deployment');
    formData.append('deployment_id', id);
    
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

// =============================================
// دوال السجلات
// =============================================
function viewLogs(id) {
    currentDeploymentId = id;
    document.getElementById('logs-modal').classList.remove('hidden');
    
    // هنا يمكن جلب السجلات من قاعدة البيانات
}

function closeLogsModal() {
    document.getElementById('logs-modal').classList.add('hidden');
}

function copyLogs() {
    const logs = document.getElementById('logs-content').innerText;
    navigator.clipboard.writeText(logs);
    showNotification('📋 تم نسخ السجلات', 'success');
}

// =============================================
// دوال الفلاتر
// =============================================
function applyFilters() {
    const project = document.getElementById('filter-project').value;
    const status = document.getElementById('filter-status').value;
    const env = document.getElementById('filter-environment').value;
    const from = document.getElementById('date-from').value;
    const to = document.getElementById('date-to').value;
    
    let url = '?page=deployment';
    if (project) url += '&project=' + project;
    if (status) url += '&status=' + status;
    if (env) url += '&environment=' + env;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=deployment';
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
        closeCreateDeploymentModal();
        closeLogsModal();
    }
});
</script>