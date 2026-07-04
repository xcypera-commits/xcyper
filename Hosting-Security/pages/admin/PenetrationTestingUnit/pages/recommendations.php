<?php
// =============================================
// pentest-unit/pages/recommendations.php
// صفحة التوصيات الأمنية - بيانات حقيقية من قاعدة البيانات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// معالجة التصفية
$priority_filter = $_GET['priority'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

try {
    // =============================================
    // 1. إحصائيات التوصيات - من قاعدة البيانات
    // =============================================
    
    // إجمالي التوصيات
    $stmt = $db->query("SELECT COUNT(*) FROM security_recommendations");
    $total_recommendations = $stmt->fetchColumn() ?: 0;
    
    // التوصيات حسب الأولوية
    $priority_counts = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    $stmt = $db->query("SELECT priority, COUNT(*) as count FROM security_recommendations GROUP BY priority");
    while ($row = $stmt->fetch()) {
        $priority_counts[$row['priority']] = $row['count'];
    }
    
    // التوصيات حسب الحالة
    $status_counts = [
        'pending' => 0,
        'in-progress' => 0,
        'implemented' => 0,
        'scheduled' => 0,
        'cancelled' => 0
    ];
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM security_recommendations GROUP BY status");
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // التوصيات المنفذة
    $implemented_count = $status_counts['implemented'] ?? 0;
    
    // التوصيات المتأخرة (الموعد النهائي مضى)
    $stmt = $db->query("
        SELECT COUNT(*) FROM security_recommendations 
        WHERE due_date < CURDATE() 
        AND status IN ('pending', 'in-progress', 'scheduled')
    ");
    $overdue_count = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. جميع التوصيات مع التفاصيل - من قاعدة البيانات
    // =============================================
    
    $sql = "SELECT 
                r.*,
                p.project_name,
                p.project_code,
                u.full_name as assigned_to_name,
                v.name as vulnerability_name,
                v.severity as vulnerability_severity,
                creator.full_name as created_by_name
            FROM security_recommendations r
            LEFT JOIN pentest_projects p ON r.project_id = p.id
            LEFT JOIN users u ON r.assigned_to = u.id
            LEFT JOIN users creator ON r.created_by = creator.id
            LEFT JOIN vulnerabilities v ON r.vulnerability_id = v.id
            WHERE 1=1";
    
    if ($priority_filter !== 'all') {
        $sql .= " AND r.priority = '$priority_filter'";
    }
    
    if ($status_filter !== 'all') {
        $sql .= " AND r.status = '$status_filter'";
    }
    
    $sql .= " ORDER BY 
                CASE r.priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                CASE r.status
                    WHEN 'pending' THEN 1
                    WHEN 'scheduled' THEN 2
                    WHEN 'in-progress' THEN 3
                    WHEN 'implemented' THEN 4
                    WHEN 'cancelled' THEN 5
                END,
                r.due_date ASC";
    
    $recommendations = $db->query($sql)->fetchAll();
    
    // =============================================
    // 3. التوصيات العاجلة (أولوية حرجة/عالية) - من قاعدة البيانات
    // =============================================
    
    $urgent_recommendations = $db->query("
        SELECT r.*, p.project_name, u.full_name as assigned_to_name
        FROM security_recommendations r
        LEFT JOIN pentest_projects p ON r.project_id = p.id
        LEFT JOIN users u ON r.assigned_to = u.id
        WHERE r.priority IN ('critical', 'high') 
        AND r.status IN ('pending', 'in-progress', 'scheduled')
        ORDER BY 
            CASE r.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
            END,
            r.due_date ASC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 4. إحصائيات التوصيات حسب الفئة
    // =============================================
    
    $category_stats = $db->query("
        SELECT category, COUNT(*) as count,
               SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented
        FROM security_recommendations
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll();
    
    // =============================================
    // 5. توزيع التوصيات حسب الشهر - للرسم البياني
    // =============================================
    
    $monthly_stats = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('F', strtotime("-$i months"));
        $monthly_stats[$month] = [
            'month' => $month_name,
            'created' => 0,
            'implemented' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as created,
            SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented
        FROM security_recommendations
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($monthly_stats[$row['month']])) {
            $monthly_stats[$row['month']]['created'] = $row['created'];
            $monthly_stats[$row['month']]['implemented'] = $row['implemented'];
        }
    }
    
    // =============================================
    // 6. المستخدمين المتاحين للتكليف
    // =============================================
    
    $users = $db->query("
        SELECT id, full_name 
        FROM users 
        WHERE is_active = 1 
        ORDER BY full_name
    ")->fetchAll();
    
    // =============================================
    // 7. المشاريع النشطة
    // =============================================
    
    $projects = $db->query("
        SELECT id, project_name 
        FROM pentest_projects 
        WHERE status IN ('in-progress', 'pending')
        ORDER BY project_name
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة
function getPriorityBadge($priority) {
    return match($priority) {
        'critical' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-600">حرج</span>',
        'high' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500">عالي</span>',
        'medium' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">متوسط</span>',
        'low' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">منخفض</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getStatusBadge($status) {
    return match($status) {
        'pending' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500">في الانتظار</span>',
        'in-progress' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500">قيد التنفيذ</span>',
        'implemented' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500">منفذة</span>',
        'scheduled' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-500">مجدولة</span>',
        'cancelled' => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">ملغاة</span>',
        default => '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500">غير معروف</span>'
    };
}

function getCategoryText($category) {
    return match($category) {
        'network' => 'الشبكة',
        'application' => 'التطبيقات',
        'data' => 'البيانات',
        'compliance' => 'الامتثال',
        'training' => 'التدريب',
        default => $category
    };
}

function getCategoryColor($category) {
    return match($category) {
        'network' => 'bg-purple-500',
        'application' => 'bg-blue-500',
        'data' => 'bg-green-500',
        'compliance' => 'bg-yellow-500',
        'training' => 'bg-orange-500',
        default => 'bg-gray-500'
    };
}

function getDaysUntil($date) {
    if (!$date) return 'غير محدد';
    
    $now = new DateTime();
    $due = new DateTime($date);
    $interval = $now->diff($due);
    
    if ($due < $now) {
        return '<span class="text-red-400">متأخر ' . $interval->days . ' يوم</span>';
    } elseif ($interval->days == 0) {
        return '<span class="text-orange-400">اليوم</span>';
    } else {
        return '<span class="text-green-400">' . $interval->days . ' يوم متبقي</span>';
    }
}

function formatDate($date) {
    if (!$date) return 'غير محدد';
    return date('Y-m-d', strtotime($date));
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-indigo-300">
            <i class="fas fa-clipboard-check ml-2"></i>
            التوصيات الأمنية
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="addNewRecommendation()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center cyber-glow">
                <i class="fas fa-plus ml-2"></i>
                توصية جديدة
            </button>
            <button onclick="refreshRecommendations()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
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
                    <p class="text-red-200 text-sm mb-1">توصيات حرجة</p>
                    <p class="text-3xl font-bold text-red-400"><?php echo $priority_counts['critical']; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-red-200">
                تحتاج تنفيذ فوري
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-900 to-orange-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-200 text-sm mb-1">توصيات عالية</p>
                    <p class="text-3xl font-bold text-orange-400"><?php echo $priority_counts['high']; ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-2xl text-orange-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-orange-200">
                أولوية عالية
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">منفذة</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $implemented_count; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                من إجمالي <?php echo $total_recommendations; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900 to-yellow-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-200 text-sm mb-1">متأخرة</p>
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $overdue_count; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-yellow-200">
                تجاوزت الموعد النهائي
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">قيد الانتظار</span>
            <span class="text-lg font-bold text-yellow-400"><?php echo $status_counts['pending']; ?></span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">قيد التنفيذ</span>
            <span class="text-lg font-bold text-blue-400"><?php echo $status_counts['in-progress']; ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- التوصيات العاجلة -->
<!-- ============================================= -->
<?php if (!empty($urgent_recommendations)): ?>
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-red-300 flex items-center">
            <i class="fas fa-clock ml-2"></i>
            توصيات عاجلة - تحتاج تنفيذ فوري
        </h3>
        <span class="px-3 py-1 bg-red-600 rounded-full text-xs font-bold"><?php echo count($urgent_recommendations); ?></span>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($urgent_recommendations as $rec): ?>
        <div class="bg-slate-900 rounded-lg p-4 border-r-4 <?php echo $rec['priority'] == 'critical' ? 'border-red-500' : 'border-orange-500'; ?> card-hover">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-bold text-white"><?php echo $rec['title']; ?></h4>
                <?php echo getPriorityBadge($rec['priority']); ?>
            </div>
            
            <p class="text-xs text-gray-400 mb-2 line-clamp-2"><?php echo $rec['description']; ?></p>
            
            <div class="space-y-1 text-xs mb-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">المشروع:</span>
                    <span class="text-blue-400"><?php echo $rec['project_name'] ?? 'عام'; ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">المسؤول:</span>
                    <span class="text-green-400"><?php echo $rec['assigned_to_name'] ?? 'غير معين'; ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">تاريخ التسليم:</span>
                    <span><?php echo getDaysUntil($rec['due_date']); ?></span>
                </div>
            </div>
            
            <div class="flex space-x-2 space-x-reverse">
                <button onclick="implementRecommendation(<?php echo $rec['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                    <i class="fas fa-check ml-1"></i>
                    تنفيذ
                </button>
                <button onclick="viewRecommendationDetails(<?php echo $rec['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                    <i class="fas fa-eye ml-1"></i>
                    تفاصيل
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- الرسم البياني للتوصيات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        إحصائيات التوصيات - آخر 6 أشهر
    </h3>
    
    <div class="h-64 relative" id="recommendations-chart-container">
        <canvas id="recommendationsChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تم إنشاؤها</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">تم تنفيذها</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات التوصيات حسب الفئة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- توزيع حسب الفئة -->
    <?php if (!empty($category_stats)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع التوصيات حسب الفئة
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($category_stats as $stat): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo getCategoryText($stat['category']); ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $stat['count']; ?></span>
                        <span class="text-xs text-gray-400">(منفذ: <?php echo $stat['implemented']; ?>)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <?php $percentage = $stat['count'] > 0 ? round(($stat['implemented'] / $stat['count']) * 100, 1) : 0; ?>
                    <div class="progress-fill <?php echo getCategoryColor($stat['category']); ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- توزيع حسب الأولوية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-chart-bar ml-2"></i>
            توزيع التوصيات حسب الأولوية
        </h3>
        
        <div class="space-y-4">
            <?php 
            $priorities = [
                'critical' => ['name' => 'حرجة', 'color' => 'bg-red-500'],
                'high' => ['name' => 'عالية', 'color' => 'bg-orange-500'],
                'medium' => ['name' => 'متوسطة', 'color' => 'bg-yellow-500'],
                'low' => ['name' => 'منخفضة', 'color' => 'bg-blue-500']
            ];
            $total = array_sum($priority_counts);
            foreach ($priorities as $key => $info): 
                $count = $priority_counts[$key] ?? 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300"><?php echo $info['name']; ?></span>
                    <div class="flex items-center">
                        <span class="text-sm font-bold ml-2"><?php echo $count; ?></span>
                        <span class="text-xs text-gray-400">(<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $info['color']; ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة جميع التوصيات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-yellow-300 flex items-center">
            <i class="fas fa-list ml-2"></i>
            جميع التوصيات
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-recommendations" placeholder="بحث في التوصيات..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-indigo-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="priority-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
                <option value="all" <?php echo $priority_filter == 'all' ? 'selected' : ''; ?>>كل الأولويات</option>
                <option value="critical" <?php echo $priority_filter == 'critical' ? 'selected' : ''; ?>>حرجة</option>
                <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>عالية</option>
                <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>متوسطة</option>
                <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>منخفضة</option>
            </select>
            <select id="status-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>كل الحالات</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>في الانتظار</option>
                <option value="in-progress" <?php echo $status_filter == 'in-progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>مجدولة</option>
                <option value="implemented" <?php echo $status_filter == 'implemented' ? 'selected' : ''; ?>>منفذة</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>ملغاة</option>
            </select>
        </div>
    </div>

    <?php if (empty($recommendations)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-clipboard-check text-5xl text-gray-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد توصيات</p>
        <p class="text-sm text-gray-500 mt-2">قم بإضافة توصيات جديدة لتحسين الأمان</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="recommendations-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">الأولوية</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                    <th class="px-6 py-4 text-sm font-semibold">الفئة</th>
                    <th class="px-6 py-4 text-sm font-semibold">المشروع</th>
                    <th class="px-6 py-4 text-sm font-semibold">المسؤول</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ التسليم</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوصف</th>
                    <th class="px-6 py-4 text-sm font-semibold">عنوان التوصية</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recommendations as $rec): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors rec-row" 
                    data-priority="<?php echo $rec['priority']; ?>"
                    data-status="<?php echo $rec['status']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <?php if ($rec['status'] != 'implemented'): ?>
                            <button onclick="implementRecommendation(<?php echo $rec['id']; ?>)" class="text-green-400 hover:text-green-300" title="تنفيذ">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <button onclick="editRecommendation(<?php echo $rec['id']; ?>)" class="text-yellow-400 hover:text-yellow-300" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="viewRecommendationDetails(<?php echo $rec['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?php echo getPriorityBadge($rec['priority']); ?></td>
                    <td class="px-6 py-4"><?php echo getStatusBadge($rec['status']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo getCategoryColor($rec['category']); ?>">
                            <?php echo getCategoryText($rec['category']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $rec['project_name'] ?? 'عام'; ?></td>
                    <td class="px-6 py-4 text-gray-300"><?php echo $rec['assigned_to_name'] ?? 'غير معين'; ?></td>
                    <td class="px-6 py-4"><?php echo getDaysUntil($rec['due_date']); ?></td>
                    <td class="px-6 py-4 text-gray-300 max-w-xs truncate"><?php echo $rec['description']; ?></td>
                    <td class="px-6 py-4 font-semibold text-green-400"><?php echo $rec['title']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($recommendations); ?> توصية
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-600 rounded-full ml-1"></span>
                حرجة: <?php echo $priority_counts['critical']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-orange-500 rounded-full ml-1"></span>
                عالية: <?php echo $priority_counts['high']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-1"></span>
                متوسطة: <?php echo $priority_counts['medium']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                منخفضة: <?php echo $priority_counts['low']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إضافة توصية جديدة -->
<!-- ============================================= -->
<div id="add-recommendation-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                إضافة توصية جديدة
            </h3>
        </div>

        <form id="add-recommendation-form" onsubmit="saveNewRecommendation(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">عنوان التوصية</label>
                    <input type="text" name="title" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الأولوية</label>
                    <select name="priority" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                        <option value="critical">حرجة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الفئة</label>
                    <select name="category" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="network">الشبكة</option>
                        <option value="application">التطبيقات</option>
                        <option value="data">البيانات</option>
                        <option value="compliance">الامتثال</option>
                        <option value="training">التدريب</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                    <select name="project_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">عام</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المسؤول</label>
                    <select name="assigned_to" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">غير معين</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ التسليم</label>
                    <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">وقت التنفيذ (ساعات)</label>
                    <input type="number" name="effort_hours" value="8" min="1" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    حفظ التوصية
                </button>
                <button type="button" onclick="closeAddModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تفاصيل التوصية -->
<!-- ============================================= -->
<div id="recommendation-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-indigo-400" id="rec-details-title">
                <i class="fas fa-info-circle ml-2"></i>
                تفاصيل التوصية
            </h3>
        </div>
        <div id="rec-details-content" class="space-y-6">
            <!-- يتم تعبئتها ديناميكياً -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة تنفيذ توصية -->
<!-- ============================================= -->
<div id="implement-recommendation-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeImplementModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-check-circle ml-2"></i>
                تأكيد تنفيذ التوصية
            </h3>
        </div>

        <form id="implement-recommendation-form" onsubmit="confirmImplementation(event)" class="space-y-4">
            <input type="hidden" id="implement-rec-id" name="rec_id">
            
            <div class="text-center py-4">
                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                <p class="text-gray-300 mb-2">هل أنت متأكد من تنفيذ هذه التوصية؟</p>
                <p class="text-sm text-gray-400">سيتم تحديث حالتها إلى "منفذة"</p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">ملاحظات التنفيذ</label>
                <textarea id="implement-notes" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right" placeholder="أضف ملاحظاتك هنا..."></textarea>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-check ml-2"></i>
                    تأكيد التنفيذ
                </button>
                <button type="button" onclick="closeImplementModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
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
    const ctx = document.getElementById('recommendationsChart')?.getContext('2d');
    if (!ctx) return;
    
    const monthlyData = <?php echo json_encode(array_values($monthly_stats)); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'تم إنشاؤها',
                    data: monthlyData.map(d => d.created),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'تم تنفيذها',
                    data: monthlyData.map(d => d.implemented),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
let currentRecId = null;

// =============================================
// دوال التصفية
// =============================================
function filterRecommendations() {
    const priority = document.getElementById('priority-filter').value;
    const status = document.getElementById('status-filter').value;
    window.location.href = `?page=recommendations&priority=${priority}&status=${status}`;
}

document.getElementById('priority-filter')?.addEventListener('change', filterRecommendations);
document.getElementById('status-filter')?.addEventListener('change', filterRecommendations);

// =============================================
// دوال التوصيات
// =============================================
function addNewRecommendation() {
    document.getElementById('add-recommendation-modal').classList.remove('hidden');
    document.getElementById('add-recommendation-modal').classList.add('flex');
}

function closeAddModal() {
    document.getElementById('add-recommendation-modal').classList.add('hidden');
    document.getElementById('add-recommendation-modal').classList.remove('flex');
}

function saveNewRecommendation(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeAddModal();
        if (typeof showNotification === 'function') {
            showNotification('✅ تم إضافة التوصية الجديدة', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshRecommendations() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('🔄 تم تحديث التوصيات', 'success');
        }
        location.reload();
    }, 1500);
}

function implementRecommendation(recId) {
    currentRecId = recId;
    document.getElementById('implement-rec-id').value = recId;
    document.getElementById('implement-recommendation-modal').classList.remove('hidden');
    document.getElementById('implement-recommendation-modal').classList.add('flex');
}

function closeImplementModal() {
    document.getElementById('implement-recommendation-modal').classList.add('hidden');
    document.getElementById('implement-recommendation-modal').classList.remove('flex');
}

function confirmImplementation(event) {
    event.preventDefault();
    
    const notes = document.getElementById('implement-notes').value;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeImplementModal();
        if (typeof showNotification === 'function') {
            showNotification('✅ تم تنفيذ التوصية بنجاح', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function viewRecommendationDetails(recId) {
    currentRecId = recId;
    
    if (typeof showLoading === 'function') showLoading();
    
    // محاكاة جلب تفاصيل التوصية
    setTimeout(() => {
        const details = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل التوصية #${recId}</h4>
                    <p class="text-gray-300">جاري تحميل التفاصيل الكاملة...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="implementRecommendation(${recId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تنفيذ</button>
                    <button onclick="editRecommendation(${recId})" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg">تعديل</button>
                    <button onclick="closeDetailsModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('rec-details-content').innerHTML = details;
        document.getElementById('rec-details-title').innerHTML = `<i class="fas fa-info-circle ml-2"></i> تفاصيل التوصية #${recId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('recommendation-details-modal').classList.remove('hidden');
        document.getElementById('recommendation-details-modal').classList.add('flex');
    }, 1000);
}

function closeDetailsModal() {
    document.getElementById('recommendation-details-modal').classList.add('hidden');
    document.getElementById('recommendation-details-modal').classList.remove('flex');
}

function editRecommendation(recId) {
    if (typeof showNotification === 'function') {
        showNotification(`✏️ تعديل التوصية #${recId}`, 'info');
    }
    closeDetailsModal();
}

// البحث المباشر
document.getElementById('search-recommendations')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.rec-row');
    
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
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>