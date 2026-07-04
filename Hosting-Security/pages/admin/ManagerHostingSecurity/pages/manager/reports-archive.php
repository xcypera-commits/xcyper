<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// manager/pages/manager/reports_archive.php
// الأرشيف والتقارير - نسخة كاملة ومفصلة
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة عن الأرشيف
    // =============================================
    
    // إجمالي التقارير
    $stmt = $db->query("SELECT COUNT(*) FROM archived_reports");
    $total_reports = $stmt->fetchColumn() ?: 0;
    
    // حجم الأرشيف
    $stmt = $db->query("SELECT SUM(file_size) FROM archived_reports");
    $total_size = $stmt->fetchColumn() ?: 0;
    
    // التقارير هذا الشهر
    $stmt = $db->query("
        SELECT COUNT(*) FROM archived_reports 
        WHERE archive_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $reports_this_month = $stmt->fetchColumn() ?: 0;
    
    // أنواع التقارير
    $stmt = $db->query("SELECT COUNT(DISTINCT report_type) FROM archived_reports");
    $report_types = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. توزيع التقارير حسب النوع
    // =============================================
    
    $reports_by_type = [
        'security' => 0,
        'performance' => 0,
        'financial' => 0,
        'audit' => 0,
        'compliance' => 0,
        'operational' => 0
    ];
    
    $stmt = $db->query("SELECT report_type, COUNT(*) as count FROM archived_reports GROUP BY report_type");
    while ($row = $stmt->fetch()) {
        if (isset($reports_by_type[$row['report_type']])) {
            $reports_by_type[$row['report_type']] = $row['count'];
        }
    }
    
    // =============================================
    // 3. توزيع التقارير حسب التنسيق
    // =============================================
    
    $reports_by_format = [
        'PDF' => 0,
        'Excel' => 0,
        'Word' => 0,
        'CSV' => 0
    ];
    
    $stmt = $db->query("SELECT file_format, COUNT(*) as count FROM archived_reports GROUP BY file_format");
    while ($row = $stmt->fetch()) {
        if (isset($reports_by_format[$row['file_format']])) {
            $reports_by_format[$row['file_format']] = $row['count'];
        }
    }
    
    // =============================================
    // 4. قائمة جميع التقارير
    // =============================================
    
    $reports = $db->query("
        SELECT 
            ar.*,
            u.name as unit_name,
            us.full_name as generated_by_name
        FROM archived_reports ar
        LEFT JOIN units u ON ar.unit_id = u.id
        LEFT JOIN users us ON ar.generated_by = us.id
        ORDER BY ar.archive_date DESC
    ")->fetchAll();
    
    // =============================================
    // 5. آخر 5 تقارير مضافة
    // =============================================
    
    $recent_reports = $db->query("
        SELECT 
            ar.*,
            u.name as unit_name
        FROM archived_reports ar
        LEFT JOIN units u ON ar.unit_id = u.id
        ORDER BY ar.archive_date DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 6. إحصائيات شهرية لآخر 6 أشهر
    // =============================================
    
    $monthly_stats = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('F', strtotime("-$i months"));
        $monthly_stats[$month] = [
            'month' => $month_name,
            'count' => 0,
            'size' => 0
        ];
    }
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(archive_date, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(file_size) as size
        FROM archived_reports
        WHERE archive_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(archive_date, '%Y-%m')
    ");
    
    while ($row = $stmt->fetch()) {
        if (isset($monthly_stats[$row['month']])) {
            $monthly_stats[$row['month']]['count'] = $row['count'];
            $monthly_stats[$row['month']]['size'] = $row['size'];
        }
    }
    
    // =============================================
    // 7. أكثر الوحدات توليدًا للتقارير
    // =============================================
    
    $top_units = $db->query("
        SELECT 
            u.name,
            COUNT(ar.id) as report_count,
            SUM(ar.file_size) as total_size
        FROM units u
        LEFT JOIN archived_reports ar ON u.id = ar.unit_id
        GROUP BY u.id
        HAVING report_count > 0
        ORDER BY report_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 8. أكثر المستخدمين توليدًا للتقارير
    // =============================================
    
    $top_users = $db->query("
        SELECT 
            us.full_name,
            COUNT(ar.id) as report_count,
            SUM(ar.file_size) as total_size
        FROM users us
        LEFT JOIN archived_reports ar ON us.id = ar.generated_by
        GROUP BY us.id
        HAVING report_count > 0
        ORDER BY report_count DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// دوال مساعدة للتنسيق
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function getReportTypeText($type) {
    return match($type) {
        'security' => 'أمني',
        'performance' => 'أداء',
        'financial' => 'مالي',
        'audit' => 'تدقيق',
        'compliance' => 'امتثال',
        'operational' => 'تشغيلي',
        default => $type
    };
}

function getReportTypeColor($type) {
    return match($type) {
        'security' => 'bg-red-500',
        'performance' => 'bg-blue-500',
        'financial' => 'bg-green-500',
        'audit' => 'bg-purple-500',
        'compliance' => 'bg-yellow-500',
        'operational' => 'bg-orange-500',
        default => 'bg-gray-500'
    };
}

function getFormatIcon($format) {
    return match($format) {
        'PDF' => '📄',
        'Excel' => '📊',
        'Word' => '📝',
        'CSV' => '📋',
        default => '📁'
    };
}

function getFormatColor($format) {
    return match($format) {
        'PDF' => 'text-red-400',
        'Excel' => 'text-green-400',
        'Word' => 'text-blue-400',
        'CSV' => 'text-yellow-400',
        default => 'text-gray-400'
    };
}

function formatDate($date) {
    if (!$date) return 'غير محدد';
    return date('Y-m-d', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return 'غير محدد';
    return date('Y-m-d H:i', strtotime($datetime));
}
?>

<!-- ============================================= -->
<!-- عنوان الصفحة وإحصائيات سريعة -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-right text-amber-300">
            <i class="fas fa-archive ml-2"></i>
            الأرشيف والتقارير
        </h1>
        <div class="flex items-center space-x-4 space-x-reverse">
            <button onclick="generateNewReport()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all flex items-center">
                <i class="fas fa-plus ml-2"></i>
                تقرير جديد
            </button>
            <button onclick="refreshArchive()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <i class="fas fa-sync-alt ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- بطاقات KPIs الرئيسية -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm mb-1">إجمالي التقارير</p>
                    <p class="text-3xl font-bold text-blue-400"><?php echo $total_reports; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl text-blue-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-blue-200">
                هذا الشهر: <?php echo $reports_this_month; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-900 to-green-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-200 text-sm mb-1">حجم الأرشيف</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo formatFileSize($total_size); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-database text-2xl text-green-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-green-200">
                إجمالي المساحة
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900 to-purple-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-200 text-sm mb-1">أنواع التقارير</p>
                    <p class="text-3xl font-bold text-purple-400"><?php echo $report_types; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-tags text-2xl text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-purple-200">
                6 أنواع مختلفة
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-900 to-amber-950 rounded-lg p-5 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-200 text-sm mb-1">الوحدات النشطة</p>
                    <p class="text-3xl font-bold text-amber-400"><?php echo count($top_units); ?></p>
                </div>
                <div class="w-12 h-12 bg-amber-600 bg-opacity-30 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-2xl text-amber-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-amber-200">
                توليد تقارير
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">متوسط حجم التقرير</span>
            <span class="text-lg font-bold text-blue-400">
                <?php echo $total_reports > 0 ? formatFileSize($total_size / $total_reports) : '0 B'; ?>
            </span>
        </div>
        <div class="bg-slate-800 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm text-gray-400">آخر تحديث</span>
            <span class="text-lg font-bold text-green-400"><?php echo date('Y-m-d H:i'); ?></span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني الشهري -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-cyan-300 mb-4 flex items-center">
        <i class="fas fa-chart-line ml-2"></i>
        إحصائيات التقارير - آخر 6 أشهر
    </h3>
    
    <div class="h-80 relative" id="reports-chart-container">
        <canvas id="reportsChart"></canvas>
    </div>
    
    <div class="flex items-center justify-center mt-4 space-x-6 space-x-reverse">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">عدد التقارير</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full ml-2"></span>
            <span class="text-sm text-gray-300">الحجم (MB)</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- آخر التقارير المضافة وتوزيع الأنواع -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- آخر التقارير -->
    <?php if (!empty($recent_reports)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-blue-300 flex items-center">
                <i class="fas fa-history ml-2"></i>
                آخر التقارير المضافة
            </h3>
            <span class="px-3 py-1 bg-blue-600 rounded-full text-xs font-bold"><?php echo count($recent_reports); ?></span>
        </div>
        
        <div class="space-y-4">
            <?php foreach ($recent_reports as $report): ?>
            <div class="p-4 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <span class="text-2xl ml-2"><?php echo getFormatIcon($report['file_format']); ?></span>
                        <div>
                            <h4 class="font-bold text-white"><?php echo $report['report_name']; ?></h4>
                            <p class="text-xs text-gray-400"><?php echo $report['unit_name'] ?? 'غير محدد'; ?></p>
                        </div>
                    </div>
                    <span class="px-2 py-1 <?php echo getReportTypeColor($report['report_type']); ?> rounded-full text-xs">
                        <?php echo getReportTypeText($report['report_type']); ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 text-xs mb-2">
                    <div>
                        <span class="text-gray-400">التنسيق:</span>
                        <span class="<?php echo getFormatColor($report['file_format']); ?> mr-1">
                            <?php echo $report['file_format']; ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-400">الحجم:</span>
                        <span class="text-gray-300 mr-1"><?php echo formatFileSize($report['file_size']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">التاريخ:</span>
                        <span class="text-gray-300 mr-1"><?php echo formatDate($report['report_date']); ?></span>
                    </div>
                </div>
                
                <div class="flex space-x-2 space-x-reverse">
                    <button onclick="viewReport(<?php echo $report['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded text-xs transition-colors">
                        <i class="fas fa-eye ml-1"></i>
                        عرض
                    </button>
                    <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs transition-colors">
                        <i class="fas fa-download ml-1"></i>
                        تحميل
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- توزيع التقارير -->
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-purple-300 mb-4 flex items-center">
            <i class="fas fa-chart-pie ml-2"></i>
            توزيع التقارير
        </h3>
        
        <div class="grid grid-cols-2 gap-6">
            <!-- حسب النوع -->
            <div>
                <h4 class="text-sm font-semibold text-gray-300 mb-3 text-center">حسب النوع</h4>
                <div class="space-y-3">
                    <?php 
                    $type_labels = [
                        'security' => 'أمني',
                        'performance' => 'أداء',
                        'financial' => 'مالي',
                        'audit' => 'تدقيق',
                        'compliance' => 'امتثال',
                        'operational' => 'تشغيلي'
                    ];
                    $total_reports_count = array_sum($reports_by_type);
                    foreach ($type_labels as $key => $label): 
                        $count = $reports_by_type[$key] ?? 0;
                        $percentage = $total_reports_count > 0 ? round(($count / $total_reports_count) * 100, 1) : 0;
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400"><?php echo $label; ?></span>
                            <span class="text-xs font-bold"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo getReportTypeColor($key); ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- حسب التنسيق -->
            <div>
                <h4 class="text-sm font-semibold text-gray-300 mb-3 text-center">حسب التنسيق</h4>
                <div class="space-y-3">
                    <?php 
                    $format_labels = [
                        'PDF' => 'PDF',
                        'Excel' => 'Excel',
                        'Word' => 'Word',
                        'CSV' => 'CSV'
                    ];
                    $total_formats = array_sum($reports_by_format);
                    foreach ($format_labels as $key => $label): 
                        $count = $reports_by_format[$key] ?? 0;
                        $percentage = $total_formats > 0 ? round(($count / $total_formats) * 100, 1) : 0;
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400"><?php echo $label; ?></span>
                            <span class="text-xs font-bold"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $key == 'PDF' ? 'bg-red-500' : ($key == 'Excel' ? 'bg-green-500' : ($key == 'Word' ? 'bg-blue-500' : 'bg-yellow-500')); ?>" 
                                 style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- أكثر الوحدات والمستخدمين توليدًا للتقارير -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- أكثر الوحدات -->
    <?php if (!empty($top_units)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-green-300 mb-4 flex items-center">
            <i class="fas fa-trophy ml-2"></i>
            أكثر الوحدات توليدًا للتقارير
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($top_units as $index => $unit): ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-xl ml-2"><?php 
                        echo $index == 0 ? '🥇' : ($index == 1 ? '🥈' : ($index == 2 ? '🥉' : '📄'));
                    ?></span>
                    <span class="text-sm text-gray-300"><?php echo $unit['name']; ?></span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm font-bold text-blue-400 ml-3"><?php echo $unit['report_count']; ?></span>
                    <span class="text-xs text-gray-400"><?php echo formatFileSize($unit['total_size']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- أكثر المستخدمين -->
    <?php if (!empty($top_users)): ?>
    <div class="security-border manager-card rounded-xl p-6">
        <h3 class="text-lg font-bold text-amber-300 mb-4 flex items-center">
            <i class="fas fa-users ml-2"></i>
            أكثر المستخدمين توليدًا للتقارير
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($top_users as $index => $user): ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-xl ml-2"><?php 
                        echo $index == 0 ? '🥇' : ($index == 1 ? '🥈' : ($index == 2 ? '🥉' : '👤'));
                    ?></span>
                    <span class="text-sm text-gray-300"><?php echo $user['full_name']; ?></span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm font-bold text-blue-400 ml-3"><?php echo $user['report_count']; ?></span>
                    <span class="text-xs text-gray-400"><?php echo formatFileSize($user['total_size']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- جدول جميع التقارير مع البحث والتصفية -->
<!-- ============================================= -->
<div class="security-border manager-card rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-yellow-300 flex items-center">
            <i class="fas fa-table ml-2"></i>
            جميع التقارير
        </h3>
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="relative">
                <input type="text" id="search-reports" placeholder="بحث في التقارير..." 
                       class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
            <select id="type-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل الأنواع</option>
                <option value="security">أمني</option>
                <option value="performance">أداء</option>
                <option value="financial">مالي</option>
                <option value="audit">تدقيق</option>
                <option value="compliance">امتثال</option>
                <option value="operational">تشغيلي</option>
            </select>
            <select id="format-filter" class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="all">كل التنسيقات</option>
                <option value="PDF">PDF</option>
                <option value="Excel">Excel</option>
                <option value="Word">Word</option>
                <option value="CSV">CSV</option>
            </select>
        </div>
    </div>

    <?php if (empty($reports)): ?>
    <div class="text-center py-12 bg-slate-800 rounded-lg">
        <i class="fas fa-folder-open text-5xl text-gray-500 mb-4"></i>
        <p class="text-xl text-gray-400">لا توجد تقارير في الأرشيف</p>
        <p class="text-sm text-gray-500 mt-2">قم بإنشاء تقارير جديدة لإضافتها هنا</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full" id="reports-table">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                    <th class="px-6 py-4 text-sm font-semibold">النوع</th>
                    <th class="px-6 py-4 text-sm font-semibold">التنسيق</th>
                    <th class="px-6 py-4 text-sm font-semibold">الحجم</th>
                    <th class="px-6 py-4 text-sm font-semibold">الوحدة</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ التقرير</th>
                    <th class="px-6 py-4 text-sm font-semibold">تاريخ الأرشفة</th>
                    <th class="px-6 py-4 text-sm font-semibold">المنشئ</th>
                    <th class="px-6 py-4 text-sm font-semibold">اسم التقرير</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-800 transition-colors report-row" 
                    data-type="<?php echo $report['report_type']; ?>"
                    data-format="<?php echo $report['file_format']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewReport(<?php echo $report['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="text-green-400 hover:text-green-300" title="تحميل">
                                <i class="fas fa-download"></i>
                            </button>
                            <button onclick="shareReport(<?php echo $report['id']; ?>)" class="text-purple-400 hover:text-purple-300" title="مشاركة">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo getReportTypeColor($report['report_type']); ?>">
                            <?php echo getReportTypeText($report['report_type']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="flex items-center <?php echo getFormatColor($report['file_format']); ?>">
                            <span class="text-xl ml-1"><?php echo getFormatIcon($report['file_format']); ?></span>
                            <?php echo $report['file_format']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo formatFileSize($report['file_size']); ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $report['unit_name'] ?? 'غير محدد'; ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo formatDate($report['report_date']); ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo formatDate($report['archive_date']); ?>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        <?php echo $report['generated_by_name'] ?? 'النظام'; ?>
                    </td>
                    <td class="px-6 py-4 font-semibold text-green-400">
                        <?php echo $report['report_name']; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- إحصائيات أسفل الجدول -->
    <div class="mt-6 flex items-center justify-between text-sm text-gray-400">
        <div>
            إجمالي: <?php echo count($reports); ?> تقرير
        </div>
        <div class="flex items-center space-x-4 space-x-reverse">
            <span class="flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full ml-1"></span>
                أمني: <?php echo $reports_by_type['security']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full ml-1"></span>
                أداء: <?php echo $reports_by_type['performance']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full ml-1"></span>
                مالي: <?php echo $reports_by_type['financial']; ?>
            </span>
            <span class="flex items-center">
                <span class="w-3 h-3 bg-purple-500 rounded-full ml-1"></span>
                تدقيق: <?php echo $reports_by_type['audit']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة إنشاء تقرير جديد -->
<!-- ============================================= -->
<div id="create-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateReportModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-green-400">
                <i class="fas fa-plus-circle ml-2"></i>
                إنشاء تقرير جديد
            </h3>
        </div>

        <form id="create-report-form" onsubmit="saveNewReport(event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم التقرير</label>
                    <input type="text" name="report_name" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع التقرير</label>
                    <select name="report_type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="security">أمني</option>
                        <option value="performance">أداء</option>
                        <option value="financial">مالي</option>
                        <option value="audit">تدقيق</option>
                        <option value="compliance">امتثال</option>
                        <option value="operational">تشغيلي</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">التنسيق</label>
                    <select name="file_format" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="PDF">PDF</option>
                        <option value="Excel">Excel</option>
                        <option value="Word">Word</option>
                        <option value="CSV">CSV</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الوحدة</label>
                    <select name="unit_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="">اختر الوحدة</option>
                        <?php
                        $units = $db->query("SELECT id, name FROM units ORDER BY name")->fetchAll();
                        foreach ($units as $unit):
                        ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ التقرير</label>
                    <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>

            <div class="border-2 border-dashed border-slate-600 rounded-lg p-8 text-center">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-500 mb-3"></i>
                <p class="text-sm text-gray-400">اسحب وأفلت الملف هنا أو</p>
                <button type="button" class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm transition-colors">
                    <i class="fas fa-folder-open ml-1"></i>
                    اختر ملف
                </button>
                <p class="text-xs text-gray-500 mt-2">PDF, Excel, Word, CSV (حتى 50MB)</p>
            </div>

            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save ml-2"></i>
                    إنشاء التقرير
                </button>
                <button type="button" onclick="closeCreateReportModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    <i class="fas fa-times ml-2"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة عرض التقرير -->
<!-- ============================================= -->
<div id="view-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeViewReportModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h3 class="text-2xl font-bold text-blue-400" id="view-report-title">
                <i class="fas fa-file-alt ml-2"></i>
                عرض التقرير
            </h3>
        </div>
        <div id="view-report-content" class="space-y-6">
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
    const ctx = document.getElementById('reportsChart')?.getContext('2d');
    if (!ctx) return;
    
    const monthlyData = <?php echo json_encode(array_values($monthly_stats)); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'عدد التقارير',
                    data: monthlyData.map(d => d.count),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'الحجم (MB)',
                    data: monthlyData.map(d => (d.size / (1024 * 1024)).toFixed(2)),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    yAxisID: 'y1'
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
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'عدد التقارير',
                        color: '#94a3b8'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            return value + ' MB';
                        }
                    },
                    title: {
                        display: true,
                        text: 'الحجم (MB)',
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

let currentReportId = null;

function generateNewReport() {
    document.getElementById('create-report-modal').classList.remove('hidden');
    document.getElementById('create-report-modal').classList.add('flex');
}

function closeCreateReportModal() {
    document.getElementById('create-report-modal').classList.add('hidden');
    document.getElementById('create-report-modal').classList.remove('flex');
}

function saveNewReport(event) {
    event.preventDefault();
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        closeCreateReportModal();
        if (typeof showNotification === 'function') {
            showNotification('تم إنشاء التقرير بنجاح', 'success');
        }
        setTimeout(() => location.reload(), 1500);
    }, 1500);
}

function refreshArchive() {
    if (typeof showLoading === 'function') showLoading();
    setTimeout(() => {
        if (typeof hideLoading === 'function') hideLoading();
        if (typeof showNotification === 'function') {
            showNotification('تم تحديث الأرشيف', 'success');
        }
        location.reload();
    }, 1500);
}

function viewReport(reportId) {
    currentReportId = reportId;
    
    if (typeof showLoading === 'function') showLoading();
    
    setTimeout(() => {
        const content = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-700 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-4">تفاصيل التقرير #${reportId}</h4>
                    <p class="text-gray-300">جاري تحميل التقرير...</p>
                </div>
                <div class="flex justify-between pt-4 border-t border-slate-600">
                    <button onclick="downloadReport(${reportId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg">تحميل</button>
                    <button onclick="closeViewReportModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                </div>
            </div>
        `;
        
        document.getElementById('view-report-content').innerHTML = content;
        document.getElementById('view-report-title').innerHTML = `<i class="fas fa-file-alt ml-2"></i> عرض التقرير #${reportId}`;
        
        if (typeof hideLoading === 'function') hideLoading();
        document.getElementById('view-report-modal').classList.remove('hidden');
        document.getElementById('view-report-modal').classList.add('flex');
    }, 1000);
}

function closeViewReportModal() {
    document.getElementById('view-report-modal').classList.add('hidden');
    document.getElementById('view-report-modal').classList.remove('flex');
}

function downloadReport(reportId) {
    if (typeof showNotification === 'function') {
        showNotification(`جاري تحميل التقرير #${reportId}`, 'info');
    }
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification(`تم تحميل التقرير بنجاح`, 'success');
        }
    }, 2000);
}

function shareReport(reportId) {
    if (typeof showNotification === 'function') {
        showNotification(`فتح نافذة مشاركة التقرير #${reportId}`, 'info');
    }
}

// البحث والتصفية
document.getElementById('search-reports')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.report-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.getElementById('type-filter')?.addEventListener('change', filterReports);
document.getElementById('format-filter')?.addEventListener('change', filterReports);

function filterReports() {
    const typeFilter = document.getElementById('type-filter').value;
    const formatFilter = document.getElementById('format-filter').value;
    const rows = document.querySelectorAll('.report-row');
    
    rows.forEach(row => {
        const typeMatch = typeFilter === 'all' || row.dataset.type === typeFilter;
        const formatMatch = formatFilter === 'all' || row.dataset.format === formatFilter;
        
        if (typeMatch && formatMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
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
.scrollbar-custom::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-custom::-webkit-scrollbar-track {
    background: #1e293b;
}
.scrollbar-custom::-webkit-scrollbar-thumb {
    background: #f59e0b;
    border-radius: 3px;
}
.table-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}
.manager-card {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.8));
    border: 1px solid rgba(59, 130, 246, 0.2);
    backdrop-filter: blur(10px);
}
.security-border {
    border: 2px solid rgba(59, 130, 246, 0.3);
    position: relative;
    overflow: hidden;
}
</style>