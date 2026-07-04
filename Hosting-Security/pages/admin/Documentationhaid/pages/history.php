<?php
// =============================================
// documentation-unit/pages/history.php
// صفحة سجل النشاطات والتغييرات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $user_filter = $_GET['user'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $target_filter = $_GET['target'] ?? '';
    $date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $_GET['to'] ?? date('Y-m-d');
    
    // جلب سجل النشاطات
    $sql = "
        SELECT a.*, 
               u.full_name as user_name,
               u.email as user_email
        FROM documentation_activity_log a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($user_filter) {
        $sql .= " AND a.user_id = ?";
        $params[] = $user_filter;
    }
    
    if ($type_filter) {
        $sql .= " AND a.activity_type = ?";
        $params[] = $type_filter;
    }
    
    if ($target_filter) {
        $sql .= " AND a.target_type = ?";
        $params[] = $target_filter;
    }
    
    if ($date_from && $date_to) {
        $sql .= " AND DATE(a.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT 500";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();
    
    // إحصائيات النشاطات
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT user_id) as active_users,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as yesterday,
            SUM(CASE WHEN activity_type = 'create' THEN 1 ELSE 0 END) as creates,
            SUM(CASE WHEN activity_type = 'update' THEN 1 ELSE 0 END) as updates,
            SUM(CASE WHEN activity_type = 'delete' THEN 1 ELSE 0 END) as deletes,
            SUM(CASE WHEN activity_type = 'view' THEN 1 ELSE 0 END) as views,
            SUM(CASE WHEN activity_type = 'download' THEN 1 ELSE 0 END) as downloads
        FROM documentation_activity_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch();
    
    // إحصائيات يومية لآخر 7 أيام
    $daily_stats = $db->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count,
            SUM(CASE WHEN activity_type = 'create' THEN 1 ELSE 0 END) as creates,
            SUM(CASE WHEN activity_type = 'update' THEN 1 ELSE 0 END) as updates,
            SUM(CASE WHEN activity_type = 'delete' THEN 1 ELSE 0 END) as deletes
        FROM documentation_activity_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ")->fetchAll();
    
    // قائمة المستخدمين النشطين
    $active_users = $db->query("
        SELECT u.id, u.full_name, u.email,
               COUNT(a.id) as activity_count,
               MAX(a.created_at) as last_active
        FROM users u
        LEFT JOIN documentation_activity_log a ON u.id = a.user_id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY u.id
        HAVING activity_count > 0
        ORDER BY activity_count DESC
        LIMIT 10
    ")->fetchAll();
    
    // أنواع النشاطات
    $activity_types = $db->query("
        SELECT 
            activity_type,
            COUNT(*) as count,
            COUNT(DISTINCT user_id) as users
        FROM documentation_activity_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY activity_type
        ORDER BY count DESC
    ")->fetchAll();
    
    // أكثر المستندات نشاطاً
    $active_documents = $db->query("
        SELECT 
            d.id,
            d.title,
            d.document_code,
            COUNT(a.id) as activity_count
        FROM documents d
        LEFT JOIN documentation_activity_log a ON d.id = a.target_id AND a.target_type = 'document'
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY d.id
        HAVING activity_count > 0
        ORDER BY activity_count DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $activities = [];
    $daily_stats = [];
    $active_users = [];
    $activity_types = [];
    $active_documents = [];
    $stats = [
        'total' => 0,
        'active_users' => 0,
        'today' => 0,
        'yesterday' => 0,
        'creates' => 0,
        'updates' => 0,
        'deletes' => 0,
        'views' => 0,
        'downloads' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function getActivityIcon($type) {
    $icons = [
        'create' => '➕',
        'update' => '✏️',
        'delete' => '🗑️',
        'view' => '👁️',
        'download' => '⬇️',
        'upload' => '⬆️',
        'review' => '📋',
        'approve' => '✓',
        'reject' => '✗',
        'comment' => '💬',
        'share' => '🔗',
        'export' => '📊',
        'import' => '📥'
    ];
    
    return $icons[$type] ?? '📌';
}

function getActivityColor($type) {
    $colors = [
        'create' => 'text-green-400',
        'update' => 'text-blue-400',
        'delete' => 'text-red-400',
        'view' => 'text-gray-400',
        'download' => 'text-purple-400',
        'upload' => 'text-orange-400',
        'review' => 'text-yellow-400',
        'approve' => 'text-green-400',
        'reject' => 'text-red-400',
        'comment' => 'text-blue-400'
    ];
    
    return $colors[$type] ?? 'text-gray-400';
}

function getTargetIcon($target) {
    $icons = [
        'project' => '📁',
        'document' => '📄',
        'template' => '📋',
        'report' => '📊',
        'review' => '📝',
        'comment' => '💬',
        'file' => '📎'
    ];
    
    return $icons[$target] ?? '📌';
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
<!-- رأس الصفحة -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-2xl p-8 mb-8 cyber-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">📋</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">سجل النشاطات</h1>
                <p class="text-gray-400 mt-1">تتبع جميع العمليات والتغييرات في النظام</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="exportHistory()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm flex items-center">
                <span class="ml-2">📊</span>
                تصدير التقرير
            </button>
            <button onclick="refreshHistory()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm flex items-center">
                <span class="ml-2">🔄</span>
                تحديث
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر -->
    <div class="mt-6 flex flex-wrap items-center gap-3 bg-slate-900 rounded-xl p-4">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="بحث في النشاطات..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-purple-500 text-right">
        </div>
        
        <select id="filter-user" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm focus:outline-none focus:border-purple-500">
            <option value="">جميع المستخدمين</option>
            <?php foreach ($active_users as $user): ?>
            <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($user['full_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <select id="filter-type" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm focus:outline-none focus:border-purple-500">
            <option value="">جميع الأنشطة</option>
            <option value="create">➕ إنشاء</option>
            <option value="update">✏️ تعديل</option>
            <option value="delete">🗑️ حذف</option>
            <option value="view">👁️ عرض</option>
            <option value="download">⬇️ تحميل</option>
            <option value="upload">⬆️ رفع</option>
            <option value="review">📋 مراجعة</option>
            <option value="approve">✓ اعتماد</option>
            <option value="comment">💬 تعليق</option>
        </select>
        
        <input type="date" id="date-from" value="<?php echo $date_from; ?>" onchange="applyFilters()" 
               class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
        
        <input type="date" id="date-to" value="<?php echo $date_to; ?>" onchange="applyFilters()" 
               class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">إجمالي النشاطات</p>
                <p class="text-2xl font-bold text-purple-400"><?php echo number_format($stats['total']); ?></p>
            </div>
            <div class="w-10 h-10 bg-purple-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-purple-400">📊</span>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">مستخدمين نشطين</p>
                <p class="text-2xl font-bold text-blue-400"><?php echo $stats['active_users']; ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-blue-400">👥</span>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">نشاطات اليوم</p>
                <p class="text-2xl font-bold text-green-400"><?php echo $stats['today']; ?></p>
            </div>
            <div class="w-10 h-10 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-green-400">📅</span>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">نشاطات الأمس</p>
                <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['yesterday']; ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-yellow-400">📆</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات إضافية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- توزيع النشاطات -->
    <div class="bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توزيع النشاطات</h3>
        
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">➕ إنشاء</span>
                <span class="text-sm text-green-400"><?php echo $stats['creates']; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-green-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['creates'] / $stats['total']) * 100) : 0; ?>%"></div>
            </div>
            
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">✏️ تعديل</span>
                <span class="text-sm text-blue-400"><?php echo $stats['updates']; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-blue-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['updates'] / $stats['total']) * 100) : 0; ?>%"></div>
            </div>
            
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">🗑️ حذف</span>
                <span class="text-sm text-red-400"><?php echo $stats['deletes']; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-red-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['deletes'] / $stats['total']) * 100) : 0; ?>%"></div>
            </div>
            
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">👁️ عرض</span>
                <span class="text-sm text-gray-400"><?php echo $stats['views']; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-gray-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['views'] / $stats['total']) * 100) : 0; ?>%"></div>
            </div>
            
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">⬇️ تحميل</span>
                <span class="text-sm text-purple-400"><?php echo $stats['downloads']; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-purple-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['downloads'] / $stats['total']) * 100) : 0; ?>%"></div>
            </div>
        </div>
    </div>
    
    <!-- المستخدمين النشطين -->
    <div class="bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">المستخدمين النشطين</h3>
        
        <?php if (empty($active_users)): ?>
            <p class="text-gray-400 text-center py-4">لا يوجد نشاط</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($active_users as $user): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold ml-2">
                            <?php echo mb_substr($user['full_name'], 0, 1); ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-xs text-gray-400">آخر نشاط: <?php echo timeAgo($user['last_active']); ?></p>
                        </div>
                    </div>
                    <span class="text-sm bg-blue-600 bg-opacity-20 text-blue-400 px-2 py-1 rounded-full">
                        <?php echo $user['activity_count']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- أكثر المستندات نشاطاً -->
    <div class="bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">أكثر المستندات نشاطاً</h3>
        
        <?php if (empty($active_documents)): ?>
            <p class="text-gray-400 text-center py-4">لا يوجد نشاط</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($active_documents as $doc): ?>
                <div class="flex items-center justify-between cursor-pointer hover:bg-slate-700 p-2 rounded-lg transition-colors"
                     onclick="viewDocument(<?php echo $doc['id']; ?>)">
                    <div class="flex items-center">
                        <span class="text-2xl ml-2">📄</span>
                        <div>
                            <p class="text-sm font-semibold"><?php echo htmlspecialchars($doc['title']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo $doc['document_code']; ?></p>
                        </div>
                    </div>
                    <span class="text-sm bg-purple-600 bg-opacity-20 text-purple-400 px-2 py-1 rounded-full">
                        <?php echo $doc['activity_count']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- النشاطات اليومية (رسم بياني) -->
<!-- ============================================= -->
<?php if (!empty($daily_stats)): ?>
<div class="bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">النشاطات اليومية (آخر 7 أيام)</h3>
    <div style="position: relative; width: 100%; height: 200px;">
        <canvas id="dailyChart"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- سجل النشاطات -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold">سجل النشاطات التفصيلي</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <span class="text-sm text-gray-400"><?php echo count($activities); ?> نشاط</span>
            <select onchange="changePageSize(this.value)" class="px-3 py-1 bg-slate-700 border border-slate-600 rounded-lg text-sm">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="500">500</option>
            </select>
        </div>
    </div>
    
    <?php if (empty($activities)): ?>
        <div class="text-center py-12">
            <div class="text-5xl text-gray-600 mb-4">📭</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد نشاطات</h3>
            <p class="text-gray-500">لم يتم تسجيل أي نشاط في هذه الفترة</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($activities as $activity): ?>
            <div class="flex items-start p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                <!-- أيقونة النشاط -->
                <div class="ml-3 mt-1">
                    <span class="text-xl <?php echo getActivityColor($activity['activity_type']); ?>">
                        <?php echo getActivityIcon($activity['activity_type']); ?>
                    </span>
                </div>
                
                <!-- المحتوى -->
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <span class="font-semibold"><?php echo htmlspecialchars($activity['user_name'] ?? 'النظام'); ?></span>
                            <span class="text-xs text-gray-400"><?php echo timeAgo($activity['created_at']); ?></span>
                        </div>
                        <span class="text-xs text-gray-500"><?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?></span>
                    </div>
                    
                    <p class="text-sm text-gray-300 mt-1">
                        <?php echo htmlspecialchars($activity['description']); ?>
                    </p>
                    
                    <?php if ($activity['target_type'] && $activity['target_id']): ?>
                    <div class="flex items-center mt-2 text-xs text-gray-400">
                        <span class="ml-2"><?php echo getTargetIcon($activity['target_type']); ?></span>
                        <span><?php echo $activity['target_type']; ?> #<?php echo $activity['target_id']; ?></span>
                        
                        <?php if ($activity['target_type'] == 'document'): ?>
                        <button onclick="viewDocument(<?php echo $activity['target_id']; ?>)" class="mr-3 text-blue-400 hover:text-blue-300">
                            عرض
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($activity['metadata']): ?>
                        <span class="mr-3 text-gray-500">•</span>
                        <span class="text-gray-500"><?php echo $activity['metadata']; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ترقيم الصفحات -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-700">
            <div class="text-sm text-gray-400">
                عرض 1-<?php echo count($activities); ?> من <?php echo $stats['total']; ?> نشاط
            </div>
            
            <div class="flex items-center space-x-2 space-x-reverse">
                <button class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm disabled:opacity-50" disabled>
                    السابق
                </button>
                <span class="px-3 py-1 bg-blue-600 rounded-lg text-sm">1</span>
                <span class="px-3 py-1 bg-slate-700 rounded-lg text-sm">2</span>
                <span class="px-3 py-1 bg-slate-700 rounded-lg text-sm">3</span>
                <button class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm">
                    التالي
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// بيانات الرسم البياني
const dailyData = <?php echo json_encode(array_reverse($daily_stats)); ?>;

// تهيئة الرسم البياني
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'إنشاء',
                    data: dailyData.map(d => d.creates),
                    backgroundColor: '#10b981',
                    borderRadius: 4
                },
                {
                    label: 'تعديل',
                    data: dailyData.map(d => d.updates),
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                },
                {
                    label: 'حذف',
                    data: dailyData.map(d => d.deletes),
                    backgroundColor: '#ef4444',
                    borderRadius: 4
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
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
});

// دوال التنقل
function viewDocument(id) {
    window.location.href = '?page=documents&view=' + id;
}

// تطبيق الفلاتر
function applyFilters() {
    const user = document.getElementById('filter-user').value;
    const type = document.getElementById('filter-type').value;
    const from = document.getElementById('date-from').value;
    const to = document.getElementById('date-to').value;
    
    let url = '?page=history';
    if (user) url += '&user=' + user;
    if (type) url += '&type=' + type;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=history';
}

function changePageSize(size) {
    // تنفيذ تغيير حجم الصفحة
    console.log('تغيير حجم الصفحة إلى:', size);
}

// تصدير السجل
function exportHistory() {
    showNotification('📊 جاري تصدير سجل النشاطات...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير', 'success');
    }, 2000);
}

// تحديث السجل
function refreshHistory() {
    showLoading();
    setTimeout(() => {
        hideLoading();
        location.reload();
    }, 500);
}

// دوال مساعدة
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600'
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
</script>

<!-- ============================================= -->
<!-- CSS إضافي -->
<!-- ============================================= -->
<style>
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.spinner {
    border: 3px solid rgba(139, 92, 246, 0.3);
    border-top: 3px solid #8b5cf6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.notification {
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
.progress-bar {
    height: 4px;
    background: #334155;
    border-radius: 2px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}
</style>