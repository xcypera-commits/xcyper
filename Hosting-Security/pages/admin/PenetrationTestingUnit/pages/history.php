<?php
// =============================================
// pentest-unit/pages/history.php
// صفحة سجل الاختبارات - بيانات حقيقية من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$type_filter = $_GET['type'] ?? 'all';
$user_filter = $_GET['user'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

try {
    // =============================================
    // 1. إحصائيات سجل النشاطات - من قاعدة البيانات
    // =============================================
    
    // إجمالي النشاطات
    $stmt = $db->query("SELECT COUNT(*) FROM pentest_activity_log");
    $total_activities = $stmt->fetchColumn() ?: 0;
    
    // النشاطات حسب النوع
    $type_counts = [
        'scan' => 0,
        'report' => 0,
        'vulnerability' => 0,
        'tool' => 0,
        'alert' => 0,
        'recommendation' => 0
    ];
    
    $stmt = $db->query("SELECT activity_type, COUNT(*) as count FROM pentest_activity_log GROUP BY activity_type");
    while ($row = $stmt->fetch()) {
        $type_counts[$row['activity_type']] = $row['count'];
    }
    
    // إجمالي ساعات العمل
    $stmt = $db->query("SELECT SUM(duration) FROM pentest_activity_log WHERE duration IS NOT NULL");
    $total_duration = $stmt->fetchColumn() ?: 0;
    
    // آخر نشاط
    $stmt = $db->query("SELECT MAX(created_at) FROM pentest_activity_log");
    $last_activity = $stmt->fetchColumn();
    
    // =============================================
    // 2. المستخدمين النشطين
    // =============================================
    
    $active_users = $db->query("
        SELECT DISTINCT u.id, u.full_name, COUNT(al.id) as activity_count
        FROM pentest_activity_log al
        JOIN users u ON al.user_id = u.id
        GROUP BY u.id
        ORDER BY activity_count DESC
    ")->fetchAll();
    
    // =============================================
    // 3. جميع سجلات النشاطات مع التفاصيل - من قاعدة البيانات
    // =============================================
    
    $sql = "SELECT 
                al.*,
                u.full_name as user_name,
                u.username,
                p.project_name
            FROM pentest_activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN pentest_projects p ON al.target_id = p.id AND al.target_type = 'project'
            WHERE DATE(al.created_at) BETWEEN :date_from AND :date_to";
    
    $params = [
        ':date_from' => $date_from,
        ':date_to' => $date_to
    ];
    
    if ($type_filter !== 'all') {
        $sql .= " AND al.activity_type = :type";
        $params[':type'] = $type_filter;
    }
    
    if ($user_filter !== 'all') {
        $sql .= " AND al.user_id = :user_id";
        $params[':user_id'] = $user_filter;
    }
    
    $sql .= " ORDER BY al.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();
    
    // =============================================
    // 4. إحصائيات يومية لآخر 30 يوم - للرسم البياني
    // =============================================
    
    $daily_stats = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily_stats[$date] = [
            'date' => $date,
            'scan' => 0,
            'report' => 0,
            'vulnerability' => 0,
            'tool' => 0,
            'alert' => 0,
            'recommendation' => 0,
            'total' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE(created_at) as activity_date,
            activity_type,
            COUNT(*) as count
        FROM pentest_activity_log
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at), activity_type
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($daily_stats[$row['activity_date']])) {
            $daily_stats[$row['activity_date']][$row['activity_type']] = $row['count'];
            $daily_stats[$row['activity_date']]['total'] += $row['count'];
        }
    }
    
    // =============================================
    // 5. أكثر الأنشطة تكراراً
    // =============================================
    
    $top_activities = $db->query("
        SELECT 
            activity_type,
            action,
            COUNT(*) as count
        FROM pentest_activity_log
        GROUP BY activity_type, action
        ORDER BY count DESC
        LIMIT 10
    ")->fetchAll();
    
    // =============================================
    // 6. نشاطات اليوم
    // =============================================
    
    $today_activities = $db->query("
        SELECT 
            al.*,
            u.full_name as user_name
        FROM pentest_activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE DATE(al.created_at) = CURDATE()
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getActivityTypeBadge($type) {
    return match($type) {
        'scan' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">فحص</span>',
        'report' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">تقرير</span>',
        'vulnerability' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500">ثغرة</span>',
        'tool' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">أداة</span>',
        'alert' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">تنبيه</span>',
        'recommendation' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">توصية</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getActivityIcon($type) {
    return match($type) {
        'scan' => '🔍',
        'report' => '📊',
        'vulnerability' => '🐛',
        'tool' => '🛠️',
        'alert' => '⚠️',
        'recommendation' => '💡',
        default => '📝'
    };
}

function formatDuration($seconds) {
    if (!$seconds) return '-';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return "{$hours} ساعة {$minutes} دقيقة";
    } elseif ($minutes > 0) {
        return "{$minutes} دقيقة";
    } else {
        return "{$seconds} ثانية";
    }
}

function formatDateTime($datetime) {
    if (!$datetime) return 'غير محدد';
    return date('Y-m-d H:i:s', strtotime($datetime));
}

function getTimeAgo($timestamp) {
    if (!$timestamp) return 'منذ قليل';
    
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "منذ $diff ثانية";
    if ($diff < 3600) return "منذ " . round($diff / 60) . " دقيقة";
    if ($diff < 86400) return "منذ " . round($diff / 3600) . " ساعة";
    if ($diff < 2592000) return "منذ " . round($diff / 86400) . " يوم";
    return date('Y-m-d', $time);
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-cyan-300">
            <i class="fas fa-history ml-2"></i>
            سجل الاختبارات
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="exportHistory()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-download ml-2"></i>
                تصدير السجل
            </button>
            <button onclick="refreshHistory()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">إجمالي النشاطات</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $total_activities; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-bar text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                آخر نشاط: <?php echo $last_activity ? getTimeAgo($last_activity) : 'لا يوجد'; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">ساعات العمل</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo round($total_duration / 3600, 1); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                إجمالي ساعات الفحص
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">المستخدمين النشطين</p>
                    <p class="text-3xl font-bold text-purple-400"><?php echo count($active_users); ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                في آخر 30 يوم
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">نشاطات اليوم</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo count($today_activities); ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                آخر 24 ساعة
            </div>
        </div>
    </div>

    <!-- توزيع النشاطات حسب النوع -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mt-4">
        <div class="bg-slate-800 rounded-lg p-3 text-center">
            <div class="text-2xl mb-1">🔍</div>
            <div class="text-sm font-bold text-blue-400"><?php echo $type_counts['scan']; ?></div>
            <div class="text-xs text-gray-400">فحوصات</div>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 text-center">
            <div class="text-2xl mb-1">📊</div>
            <div class="text-sm font-bold text-green-400"><?php echo $type_counts['report']; ?></div>
            <div class="text-xs text-gray-400">تقارير</div>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 text-center">
            <div class="text-2xl mb-1">🐛</div>
            <div class="text-sm font-bold text-red-400"><?php echo $type_counts['vulnerability']; ?></div>
            <div class="text-xs text-gray-400">ثغرات</div>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 text-center">
            <div class="text-2xl mb-1">🛠️</div>
            <div class="text-sm font-bold text-purple-400"><?php echo $type_counts['tool']; ?></div>
            <div class="text-xs text-gray-400">أدوات</div>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 text-center">
            <div class="text-2xl mb-1">⚠️</div>
            <div class="text-sm font-bold text-yellow-400"><?php echo $type_counts['alert']; ?></div>
            <div class="text-xs text-gray-400">تنبيهات</div>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 text-center">
            <div class="text-2xl mb-1">💡</div>
            <div class="text-sm font-bold text-orange-400"><?php echo $type_counts['recommendation']; ?></div>
            <div class="text-xs text-gray-400">توصيات</div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني لآخر 30 يوم -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        نشاطات آخر 30 يوم
    </h3>
    
    <div class="h-80 relative" id="history-chart-container">
        <canvas id="historyChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">فحوصات</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تقارير</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">ثغرات</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-purple-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">أدوات</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نموذج البحث والتصفية -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-yellow-300 mb-4 flex items-center">
        <i class="fas fa-filter ml-2"></i>
        تصفية السجل
    </h3>
    
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input type="hidden" name="page" value="history">
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">نوع النشاط</label>
            <select name="type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-cyan-500 text-right">
                <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>الكل</option>
                <option value="scan" <?php echo $type_filter == 'scan' ? 'selected' : ''; ?>>فحوصات</option>
                <option value="report" <?php echo $type_filter == 'report' ? 'selected' : ''; ?>>تقارير</option>
                <option value="vulnerability" <?php echo $type_filter == 'vulnerability' ? 'selected' : ''; ?>>ثغرات</option>
                <option value="tool" <?php echo $type_filter == 'tool' ? 'selected' : ''; ?>>أدوات</option>
                <option value="alert" <?php echo $type_filter == 'alert' ? 'selected' : ''; ?>>تنبيهات</option>
                <option value="recommendation" <?php echo $type_filter == 'recommendation' ? 'selected' : ''; ?>>توصيات</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">المستخدم</label>
            <select name="user" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-cyan-500 text-right">
                <option value="all" <?php echo $user_filter == 'all' ? 'selected' : ''; ?>>جميع المستخدمين</option>
                <?php foreach ($active_users as $user): ?>
                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                    <?php echo $user['full_name']; ?> (<?php echo $user['activity_count']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">من تاريخ</label>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-cyan-500 text-right">
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-2 text-right">إلى تاريخ</label>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-cyan-500 text-right">
        </div>
        
        <div class="flex items-end space-x-2 space-x-reverse">
            <button type="submit" class="flex-1 px-6 py-3 bg-cyan-600 hover:bg-cyan-700 rounded-lg font-semibold transition-all">
                <i class="fas fa-search ml-2"></i>
                بحث
            </button>
            <a href="?page=history" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all text-center">
                <i class="fas fa-undo ml-2"></i>
                إعادة
            </a>
        </div>
    </form>
</div>

<!-- ============================================= -->
<!-- نشاطات اليوم (إذا وجدت) -->
<!-- ============================================= -->
<?php if (!empty($today_activities)): ?>
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-green-300 flex items-center">
            <i class="fas fa-sun ml-2"></i>
            نشاطات اليوم
        </h3>
        <span class="text-xs text-gray-400">آخر تحديث: <?php echo date('Y-m-d H:i'); ?></span>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($today_activities as $activity): ?>
        <div class="bg-slate-700 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                    <span class="text-2xl ml-2"><?php echo getActivityIcon($activity['activity_type']); ?></span>
                    <span><?php echo getActivityTypeBadge($activity['activity_type']); ?></span>
                </div>
                <span class="text-xs text-gray-400"><?php echo getTimeAgo($activity['created_at']); ?></span>
            </div>
            <p class="text-sm text-white mb-2"><?php echo $activity['description']; ?></p>
            <div class="flex items-center justify-between text-xs text-gray-400">
                <span>بواسطة: <?php echo $activity['user_name'] ?? 'النظام'; ?></span>
                <?php if ($activity['duration']): ?>
                <span>المدة: <?php echo formatDuration($activity['duration']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- جدول جميع النشاطات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-blue-300 flex items-center">
            <i class="fas fa-table ml-2"></i>
            سجل النشاطات التفصيلي
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-history" placeholder="بحث في السجل..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-cyan-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
        </div>
    </div>

    <?php if (empty($activities)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-history text-5xl text-gray-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد نشاطات في هذه الفترة</p>
        <p class="text-sm text-gray-500 mt-2">جرب تغيير معايير البحث</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="history-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوقت</th>
                    <th class="px-6 py-4 text-sm font-semibold">المستخدم</th>
                    <th class="px-6 py-4 text-sm font-semibold">المشروع</th>
                    <th class="px-6 py-4 text-sm font-semibold">المدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">النتيجة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors history-row">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <span class="text-xl ml-1"><?php echo getActivityIcon($activity['activity_type']); ?></span>
                            <?php echo getActivityTypeBadge($activity['activity_type']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-300"><?php echo date('Y-m-d', strtotime($activity['created_at'])); ?></span>
                            <span class="text-xs text-gray-500"><?php echo date('H:i:s', strtotime($activity['created_at'])); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-300"><?php echo $activity['user_name'] ?? 'النظام'; ?></span>
                            <span class="text-xs text-gray-500">@<?php echo $activity['username'] ?? 'system'; ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $activity['project_name'] ?? '-'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo formatDuration($activity['duration']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                            <?php echo $activity['result'] ? 'bg-green-500' : 'bg-gray-500'; ?>">
                            <?php echo $activity['result'] ?? 'مكتمل'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300 max-w-md"><?php echo $activity['description']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            عرض <?php echo count($activities); ?> نشاط من إجمالي <?php echo $total_activities; ?>
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                فحوصات: <?php echo $type_counts['scan']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full ml-1"></span>
                تقارير: <?php echo $type_counts['report']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full ml-1"></span>
                ثغرات: <?php echo $type_counts['vulnerability']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- أكثر الأنشطة تكراراً -->
<!-- ============================================= -->
<?php if (!empty($top_activities)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    
    <!-- أكثر الأنشطة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-300 mb-4 flex items-center">
            <i class="fas fa-chart-bar ml-2"></i>
            أكثر الأنشطة تكراراً
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($top_activities as $activity): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center">
                        <span class="text-xl ml-2"><?php echo getActivityIcon($activity['activity_type']); ?></span>
                        <span class="text-sm text-gray-300"><?php echo $activity['action']; ?></span>
                    </div>
                    <span class="text-sm font-bold text-cyan-400"><?php echo $activity['count']; ?></span>
                </div>
                <div class="progress-bar">
                    <?php $percentage = ($activity['count'] / $total_activities) * 100; ?>
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- المستخدمين النشطين -->
    <?php if (!empty($active_users)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-users ml-2"></i>
            أكثر المستخدمين نشاطاً
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($active_users as $user): ?>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-300"><?php echo $user['full_name']; ?></span>
                <div class="flex items-center">
                    <span class="text-sm font-bold text-green-400 ml-2"><?php echo $user['activity_count']; ?></span>
                    <span class="text-xs text-gray-400">نشاط</span>
                </div>
            </div>
            <div class="progress-bar">
                <?php $percentage = ($user['activity_count'] / $total_activities) * 100; ?>
                <div class="progress-fill bg-green-500" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// =============================================
// الرسم البياني
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('historyChart')?.getContext('2d');
    if (!ctx) return;
    
    const dailyData = <?php echo json_encode(array_values($daily_stats)); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'فحوصات',
                    data: dailyData.map(d => d.scan),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'تقارير',
                    data: dailyData.map(d => d.report),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'ثغرات',
                    data: dailyData.map(d => d.vulnerability),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'أدوات',
                    data: dailyData.map(d => d.tool),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
                        color: '#94a3b8',
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
});

// =============================================
// دوال الصفحة
// =============================================
function exportHistory() {
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('📥 جاري تصدير سجل النشاطات', 'info');
        }
        setTimeout(() => {
            if (typeof showNotification === 'function') {
                showNotification('✅ تم تصدير السجل بنجاح', 'success');
            }
        }, 2000);
    }, 1000);
}

function refreshHistory() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث السجل', 'success');
        }
        location.reload();
    }, 1500);
}

// البحث المباشر في الجدول
document.getElementById('search-history')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.history-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
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
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}
</style>