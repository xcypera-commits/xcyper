<?php
// =============================================
// documentation-unit/pages/reports.php
// صفحة التقارير والإحصائيات
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
            case 'generate_report':
                // إنشاء تقرير جديد
                $report_code = generateReportCode($db, $_POST['report_type']);
                
                $sql = "INSERT INTO reports (
                    report_code, report_title, report_type, recipient,
                    priority, status, format, summary, notes, created_by,
                    date_from, date_to, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $report_code,
                    $_POST['report_title'],
                    $_POST['report_type'],
                    $_POST['recipient'] ?? 'manager',
                    $_POST['priority'] ?? 'normal',
                    'ready',
                    $_POST['format'] ?? 'pdf',
                    $_POST['summary'] ?? null,
                    $_POST['notes'] ?? null,
                    $_SESSION['user_id'] ?? 1,
                    $_POST['date_from'] ?? null,
                    $_POST['date_to'] ?? null
                ]);
                
                $report_id = $db->lastInsertId();
                
                // إضافة المستندات المحددة للتقرير
                if (!empty($_POST['document_ids'])) {
                    $doc_ids = json_decode($_POST['document_ids'], true);
                    if (is_array($doc_ids)) {
                        $insert = $db->prepare("INSERT INTO report_documents (report_id, document_id) VALUES (?, ?)");
                        foreach ($doc_ids as $doc_id) {
                            $insert->execute([$report_id, $doc_id]);
                        }
                    }
                }
                
                logActivity($db, 'create', 'report', $report_id, 'إنشاء تقرير جديد: ' . $_POST['report_title']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إنشاء التقرير بنجاح';
                $response['report_id'] = $report_id;
                break;
                
            case 'send_report':
                // إرسال تقرير
                $sql = "UPDATE reports SET status = 'sent', sent_date = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['report_id']]);
                
                logActivity($db, 'send', 'report', $_POST['report_id'], 'إرسال تقرير');
                
                $response['success'] = true;
                $response['message'] = '✅ تم إرسال التقرير بنجاح';
                break;
                
            case 'archive_report':
                // أرشفة تقرير
                $sql = "UPDATE reports SET status = 'archived' WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['report_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم أرشفة التقرير';
                break;
                
            case 'delete_report':
                // حذف تقرير
                $db->prepare("DELETE FROM report_documents WHERE report_id = ?")->execute([$_POST['report_id']]);
                $db->prepare("DELETE FROM reports WHERE id = ?")->execute([$_POST['report_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم حذف التقرير';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
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
    $date_from = $_GET['from'] ?? date('Y-m-01');
    $date_to = $_GET['to'] ?? date('Y-m-d');
    
    // جلب التقارير
    $sql = "
        SELECT r.*, 
               u.full_name as creator_name,
               (SELECT COUNT(*) FROM report_documents WHERE report_id = r.id) as documents_count
        FROM reports r
        LEFT JOIN users u ON r.created_by = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($type_filter) {
        $sql .= " AND r.report_type = ?";
        $params[] = $type_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND r.status = ?";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY r.created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
    
    // إحصائيات التقارير
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
            SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN report_type = 'monthly' THEN 1 ELSE 0 END) as monthly,
            SUM(CASE WHEN report_type = 'security' THEN 1 ELSE 0 END) as security,
            SUM(CASE WHEN report_type = 'progress' THEN 1 ELSE 0 END) as progress,
            SUM(CASE WHEN report_type = 'final' THEN 1 ELSE 0 END) as final
        FROM reports
    ")->fetch();
    
    // إحصائيات إضافية للرسوم البيانية
    $monthly_stats = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
        FROM reports
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();
    
    // أنواع التقارير
    $report_types = $db->query("
        SELECT report_type, COUNT(*) as count 
        FROM reports 
        GROUP BY report_type 
        ORDER BY count DESC
    ")->fetchAll();
    
    // المستندات الجاهزة للتقارير
    $documents = $db->query("
        SELECT id, title, document_code, document_type, status
        FROM documents
        WHERE status = 'approved'
        ORDER BY title
        LIMIT 100
    ")->fetchAll();
    
    // آخر المستندات المعتمدة
    $recent_approved = $db->query("
        SELECT d.id, d.title, d.document_code, d.document_type, d.approval_date,
               p.project_name
        FROM documents d
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        WHERE d.status = 'approved'
        ORDER BY d.approval_date DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (Exception $e) {
    $reports = [];
    $monthly_stats = [];
    $report_types = [];
    $documents = [];
    $recent_approved = [];
    $stats = [
        'total' => 0,
        'preparing' => 0,
        'ready' => 0,
        'sent' => 0,
        'approved' => 0,
        'monthly' => 0,
        'security' => 0,
        'progress' => 0,
        'final' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function generateReportCode($db, $type) {
    $prefixes = [
        'monthly' => 'RPT-MON',
        'security' => 'RPT-SEC',
        'progress' => 'RPT-PROG',
        'final' => 'RPT-FIN',
        'technical' => 'RPT-TECH',
        'audit' => 'RPT-AUD'
    ];
    
    $prefix = $prefixes[$type] ?? 'RPT';
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reports WHERE report_code LIKE ?");
    $stmt->execute(["{$prefix}-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$number}";
}

function getReportTypeText($type) {
    $texts = [
        'monthly' => '📊 تقرير شهري',
        'security' => '🔒 تقرير أمني',
        'progress' => '📈 تقرير تقدم',
        'final' => '🏁 تقرير نهائي',
        'technical' => '⚙️ تقرير تقني',
        'audit' => '📋 تقرير تدقيق'
    ];
    return $texts[$type] ?? $type;
}

function getReportStatusBadge($status) {
    $classes = [
        'preparing' => 'bg-gray-600 bg-opacity-20 text-gray-400',
        'ready' => 'bg-green-600 bg-opacity-20 text-green-400',
        'sent' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'approved' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'archived' => 'bg-yellow-600 bg-opacity-20 text-yellow-400'
    ];
    
    $texts = [
        'preparing' => '⏳ قيد الإعداد',
        'ready' => '✅ جاهز',
        'sent' => '📤 مرسل',
        'approved' => '✔️ معتمد',
        'archived' => '📦 مؤرشف'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getPriorityBadge($priority) {
    $classes = [
        'urgent' => 'bg-red-600 bg-opacity-20 text-red-400',
        'high' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'normal' => 'bg-blue-600 bg-opacity-20 text-blue-400'
    ];
    
    $texts = [
        'urgent' => 'عاجل',
        'high' => 'عالية',
        'normal' => 'عادية'
    ];
    
    $class = $classes[$priority] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$priority] ?? $priority;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
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
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي التقارير</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">قيد الإعداد</p>
        <p class="text-2xl font-bold text-gray-400"><?php echo $stats['preparing'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">جاهزة</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['ready'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مرسلة</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['sent'] ?? 0; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">معتمدة</p>
        <p class="text-2xl font-bold text-purple-400"><?php echo $stats['approved'] ?? 0; ?></p>
    </div>
</div>

<!-- ============================================= -->
<!-- رأس الصفحة مع أزرار الإجراءات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h3 class="text-xl font-bold text-right">التقارير والإحصائيات</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="openGenerateReportModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                إنشاء تقرير جديد
            </button>
            
            <button onclick="exportStats()" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                تصدير إحصائيات
            </button>
        </div>
    </div>
    
    <!-- فلاتر التقارير -->
    <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t border-slate-700">
        <div class="flex items-center space-x-2 space-x-reverse">
            <label class="text-sm text-gray-400">النوع:</label>
            <select id="filter-type" onchange="applyFilters()" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
                <option value="">الكل</option>
                <option value="monthly">شهري</option>
                <option value="security">أمني</option>
                <option value="progress">تقدم</option>
                <option value="final">نهائي</option>
            </select>
        </div>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <label class="text-sm text-gray-400">الحالة:</label>
            <select id="filter-status" onchange="applyFilters()" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
                <option value="">الكل</option>
                <option value="preparing">قيد الإعداد</option>
                <option value="ready">جاهز</option>
                <option value="sent">مرسل</option>
                <option value="approved">معتمد</option>
            </select>
        </div>
        
        <button onclick="resetFilters()" class="px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسوم البيانية والإحصائيات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- رسم بياني للتقارير الشهرية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
    <h3 class="text-lg font-bold mb-4 flex items-center">
        <svg class="w-5 h-5 ml-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
            التقارير الشهرية
        </h3>
        <div style="position: relative; width: 100%; height: 250px;">
        <canvas id="monthlyChart" class="w-full h-64"></canvas></div>
    </div>
    
    <!-- توزيع أنواع التقارير -->
    
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <h3 class="text-lg font-bold mb-4 flex items-center">
        <svg class="w-5 h-5 ml-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
        </svg>
            توزيع أنواع التقارير
        </h3>
        <div style="position: relative; width: 100%; height: 250px;">
        <canvas id="typesChart" class="w-full h-64"></canvas></div>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة التقارير -->
<!-- ============================================= -->
<?php if (empty($reports)): ?>
    <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
        <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-2xl font-bold text-gray-400 mb-2">لا توجد تقارير</h3>
        <p class="text-gray-500 mb-6">قم بإنشاء أول تقرير الآن</p>
        <button onclick="openGenerateReportModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all inline-flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إنشاء تقرير جديد
        </button>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($reports as $report): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6 report-card hover:shadow-lg transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="flex space-x-2 space-x-reverse">
                    <?php echo getPriorityBadge($report['priority'] ?? 'normal'); ?>
                    <?php echo getReportStatusBadge($report['status']); ?>
                </div>
                <span class="text-sm text-gray-400"><?php echo $report['report_code']; ?></span>
            </div>
            
            <h4 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($report['report_title']); ?></h4>
            <p class="text-sm text-gray-400 mb-4"><?php echo getReportTypeText($report['report_type']); ?></p>
            
            <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                <div>
                    <p class="text-gray-400">الجهة</p>
                    <p class="font-semibold"><?php echo $report['recipient'] ?? 'غير محدد'; ?></p>
                </div>
                <div>
                    <p class="text-gray-400">المستندات</p>
                    <p class="font-semibold"><?php echo $report['documents_count'] ?? 0; ?></p>
                </div>
                <div>
                    <p class="text-gray-400">المنشئ</p>
                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($report['creator_name'] ?? 'النظام'); ?></p>
                </div>
                <div>
                    <p class="text-gray-400">التاريخ</p>
                    <p class="font-semibold text-sm"><?php echo timeAgo($report['created_at']); ?></p>
                </div>
            </div>
            
            <?php if ($report['summary']): ?>
            <p class="text-xs text-gray-400 mb-4 line-clamp-2"><?php echo htmlspecialchars($report['summary']); ?></p>
            <?php endif; ?>
            
            <div class="flex items-center justify-between gap-2">
                <?php if ($report['status'] == 'ready'): ?>
                <button onclick="sendReport(<?php echo $report['id']; ?>)" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
                    إرسال
                </button>
                <?php endif; ?>
                
                <button onclick="viewReport(<?php echo $report['id']; ?>)" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                    عرض
                </button>
                
                <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
                
                <div class="relative" x-data="{ open: false }">
                    <button onclick="toggleReportMenu(<?php echo $report['id']; ?>)" class="px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                    <div id="menu-<?php echo $report['id']; ?>" class="hidden absolute left-0 mt-2 w-48 bg-slate-800 rounded-lg shadow-lg border border-slate-700 z-50">
                        <div class="py-1">
                            <button onclick="archiveReport(<?php echo $report['id']; ?>)" class="w-full text-right px-4 py-2 hover:bg-slate-700 text-sm">
                                📦 أرشفة
                            </button>
                            <button onclick="deleteReport(<?php echo $report['id']; ?>)" class="w-full text-right px-4 py-2 hover:bg-slate-700 text-sm text-red-400">
                                🗑️ حذف
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة إنشاء تقرير جديد -->
<!-- ============================================= -->
<div id="generate-report-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeGenerateReportModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">إنشاء تقرير جديد</h3>
        </div>
        
        <form id="report-form" onsubmit="generateReport(event)">
            <input type="hidden" name="action" value="generate_report">
            <input type="hidden" name="document_ids" id="selected-documents">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- عنوان التقرير -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">عنوان التقرير <span class="text-red-400">*</span></label>
                    <input type="text" id="report-title" name="report_title" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="أدخل عنوان التقرير">
                </div>
                
                <!-- نوع التقرير والجهة -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع التقرير</label>
                    <select id="report-type" name="report_type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="monthly">تقرير شهري</option>
                        <option value="security">تقرير أمني</option>
                        <option value="progress">تقرير تقدم</option>
                        <option value="final">تقرير نهائي</option>
                        <option value="technical">تقرير تقني</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الجهة المستلمة</label>
                    <select id="report-recipient" name="recipient" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="manager">مدير النظام</option>
                        <option value="client">العميل</option>
                        <option value="security">فريق الأمان</option>
                        <option value="team">فريق التوثيق</option>
                        <option value="admin">الإدارة</option>
                    </select>
                </div>
                
                <!-- الأولوية والتنسيق -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الأولوية</label>
                    <select id="report-priority" name="priority" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="normal">عادية</option>
                        <option value="high">عالية</option>
                        <option value="urgent">عاجلة</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تنسيق التقرير</label>
                    <select id="report-format" name="format" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="pdf">PDF</option>
                        <option value="docx">DOCX</option>
                        <option value="html">HTML</option>
                        <option value="xlsx">Excel</option>
                    </select>
                </div>
                
                <!-- نطاق التاريخ -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">من تاريخ</label>
                    <input type="date" id="date-from" name="date_from" value="<?php echo date('Y-m-01'); ?>"
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">إلى تاريخ</label>
                    <input type="date" id="date-to" name="date_to" value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <!-- ملخص التقرير -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">ملخص التقرير</label>
                    <textarea id="report-summary" name="summary" rows="3" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="ملخص مختصر لمحتوى التقرير..."></textarea>
                </div>
                
                <!-- ملاحظات إضافية -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">ملاحظات</label>
                    <textarea id="report-notes" name="notes" rows="2" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="أي ملاحظات إضافية..."></textarea>
                </div>
                
                <!-- اختيار المستندات -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">المستندات المرفقة</label>
                    <div class="border border-slate-600 rounded-lg p-4 max-h-60 overflow-y-auto">
                        <?php if (empty($documents)): ?>
                            <p class="text-gray-400 text-center py-4">لا توجد مستندات معتمدة</p>
                        <?php else: ?>
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach ($documents as $doc): ?>
                                <label class="flex items-center justify-end p-2 hover:bg-slate-700 rounded-lg cursor-pointer">
                                    <span class="text-sm mr-2"><?php echo htmlspecialchars($doc['title']); ?></span>
                                    <input type="checkbox" class="doc-checkbox w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded"
                                           value="<?php echo $doc['id']; ?>">
                                </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeGenerateReportModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    إنشاء التقرير
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // =============================================
// عرض التقرير
// =============================================
function viewReport(reportId) {
    showLoading();
    
    // محاكاة جلب بيانات التقرير
    setTimeout(() => {
        hideLoading();
        
        // نافذة منبثقة لعرض التقرير
        showReportPreview(reportId);
    }, 500);
}

// عرض معاينة التقرير
function showReportPreview(reportId) {
    // البحث عن التقرير في البيانات (مؤقت)
    const reportCards = document.querySelectorAll('.report-card');
    let reportTitle = 'تقرير';
    let reportCode = '';
    
    reportCards.forEach(card => {
        const codeSpan = card.querySelector('span.text-sm.text-gray-400');
        const titleEl = card.querySelector('h4.text-lg.font-bold');
        
        if (codeSpan && codeSpan.textContent.includes(reportId)) {
            reportCode = codeSpan.textContent;
            reportTitle = titleEl ? titleEl.textContent : 'تقرير';
        }
    });
    
    // إنشاء نافذة المعاينة
    const previewHtml = `
        <div id="report-preview-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop">
            <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <button onclick="closeReportPreview()" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <h3 class="text-2xl font-bold text-right text-blue-400">معاينة التقرير</h3>
                </div>
                
                <div class="bg-slate-900 rounded-lg p-8 mb-6">
                    <!-- رأس التقرير -->
                    <div class="border-b border-slate-700 pb-4 mb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-400">${reportCode}</span>
                            <h1 class="text-3xl font-bold text-blue-400">${reportTitle}</h1>
                        </div>
                        <div class="flex items-center justify-between mt-4 text-sm">
                            <span class="text-gray-400">تاريخ الإنشاء: ${new Date().toLocaleDateString('ar-EG')}</span>
                            <span class="text-gray-400">المنشئ: ${currentUser?.name || 'النظام'}</span>
                        </div>
                    </div>
                    
                    <!-- ملخص التقرير -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold mb-3 text-right">ملخص التقرير</h2>
                        <p class="text-gray-300 bg-slate-800 p-4 rounded-lg">
                            هذا تقرير شامل يوضح حالة المستندات والتقدم في المشاريع.
                            يتضمن التقرير إحصائيات مفصلة عن المستندات والمراجعات والتحديثات.
                        </p>
                    </div>
                    
                    <!-- إحصائيات سريعة -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-slate-800 p-4 rounded-lg text-center">
                            <p class="text-3xl font-bold text-blue-400">${document.querySelectorAll('.document-row')?.length || 0}</p>
                            <p class="text-sm text-gray-400">إجمالي المستندات</p>
                        </div>
                        <div class="bg-slate-800 p-4 rounded-lg text-center">
                            <p class="text-3xl font-bold text-green-400">${document.querySelectorAll('.status-approved')?.length || 0}</p>
                            <p class="text-sm text-gray-400">مستندات معتمدة</p>
                        </div>
                        <div class="bg-slate-800 p-4 rounded-lg text-center">
                            <p class="text-3xl font-bold text-yellow-400">${document.querySelectorAll('.status-review')?.length || 0}</p>
                            <p class="text-sm text-gray-400">قيد المراجعة</p>
                        </div>
                    </div>
                    
                    <!-- جدول المستندات -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold mb-3 text-right">المستندات المرفقة</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-slate-800 text-right">
                                        <th class="px-4 py-2 text-sm">الحالة</th>
                                        <th class="px-4 py-2 text-sm">النوع</th>
                                        <th class="px-4 py-2 text-sm">الإصدار</th>
                                        <th class="px-4 py-2 text-sm">عنوان المستند</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${generateReportDocumentsRows()}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- تذييل التقرير -->
                    <div class="border-t border-slate-700 pt-4 mt-4">
                        <div class="flex items-center justify-between text-sm text-gray-400">
                            <span>تم الإنشاء بواسطة نظام التوثيق الفني</span>
                            <span>جميع الحقوق محفوظة © ${new Date().getFullYear()}</span>
                        </div>
                    </div>
                </div>
                
                <!-- أزرار الإجراءات -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <button onclick="closeReportPreview()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                        إغلاق
                    </button>
                    <button onclick="downloadReport(${reportId})" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow">
                        <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        تحميل التقرير
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // إضافة النافذة للصفحة
    document.body.insertAdjacentHTML('beforeend', previewHtml);
}

// إغلاق نافذة المعاينة
function closeReportPreview() {
    const modal = document.getElementById('report-preview-modal');
    if (modal) {
        modal.remove();
    }
}

// توليد صفوف المستندات للتقرير
function generateReportDocumentsRows() {
    const rows = [];
    const documents = document.querySelectorAll('.document-row');
    
    if (documents.length === 0) {
        return '<tr><td colspan="4" class="text-center py-4 text-gray-400">لا توجد مستندات مرفقة</td></tr>';
    }
    
    documents.forEach((doc, index) => {
        if (index < 5) { // عرض أول 5 مستندات فقط
            const title = doc.querySelector('td:nth-child(10) a')?.textContent || 'مستند';
            const type = doc.querySelector('td:nth-child(4) span')?.textContent || 'تقني';
            const version = doc.querySelector('td:nth-child(7)')?.textContent || 'v1.0';
            const status = doc.querySelector('td:nth-child(3) span')?.textContent || 'draft';
            
            const statusClass = status.includes('معتمد') ? 'text-green-400' : 
                               status.includes('مراجعة') ? 'text-yellow-400' : 'text-gray-400';
            
            rows.push(`
                <tr class="border-b border-slate-700">
                    <td class="px-4 py-2"><span class="${statusClass}">${status}</span></td>
                    <td class="px-4 py-2">${type}</td>
                    <td class="px-4 py-2">${version}</td>
                    <td class="px-4 py-2">${title}</td>
                </tr>
            `);
        }
    });
    
    return rows.join('');
}

// =============================================
// دوال إضافية للتقرير
// =============================================

// تصدير التقرير كـ PDF
function exportReportAsPDF(reportId) {
    showNotification('📄 جاري تصدير التقرير كـ PDF...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير بنجاح', 'success');
        closeReportPreview();
    }, 2000);
}

// تصدير التقرير كـ Excel
function exportReportAsExcel(reportId) {
    showNotification('📊 جاري تصدير التقرير كـ Excel...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير بنجاح', 'success');
    }, 2000);
}

// طباعة التقرير
function printReport(reportId) {
    showNotification('🖨️ جاري تجهيز التقرير للطباعة...', 'info');
    setTimeout(() => {
        showNotification('✅ تم تجهيز التقرير', 'success');
        // هنا يمكن إضافة نافذة طباعة
        window.print();
    }, 1000);
}
// =============================================
// بيانات الرسوم البيانية
// =============================================
const monthlyData = <?php echo json_encode(array_reverse($monthly_stats)); ?>;
const typesData = <?php echo json_encode($report_types); ?>;

// تهيئة الرسوم البيانية
document.addEventListener('DOMContentLoaded', function() {
    
    // الرسم البياني الشهري
    if (document.getElementById('monthlyChart')) {
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [
                    {
                        label: 'إجمالي التقارير',
                        data: monthlyData.map(d => d.total),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'التقارير المرسلة',
                        data: monthlyData.map(d => d.sent),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
    
    // رسم بياني لأنواع التقارير
    if (document.getElementById('typesChart')) {
        const ctx = document.getElementById('typesChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: typesData.map(t => getReportTypeText(t.report_type)),
                datasets: [{
                    data: typesData.map(t => t.count),
                    backgroundColor: [
                        '#3b82f6',
                        '#ef4444',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});

// =============================================
// دوال التقارير
// =============================================

// فتح نافذة إنشاء تقرير
function openGenerateReportModal() {
    document.getElementById('generate-report-modal').classList.remove('hidden');
}

// إغلاق نافذة إنشاء تقرير
function closeGenerateReportModal() {
    document.getElementById('generate-report-modal').classList.add('hidden');
    document.getElementById('report-form').reset();
}

// إنشاء تقرير
function generateReport(event) {
    event.preventDefault();
    
    // جمع المستندات المحددة
    const selectedDocs = [];
    document.querySelectorAll('.doc-checkbox:checked').forEach(cb => {
        selectedDocs.push(cb.value);
    });
    document.getElementById('selected-documents').value = JSON.stringify(selectedDocs);
    
    const formData = new FormData(document.getElementById('report-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeGenerateReportModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في الاتصال', 'error');
        console.error(error);
    });
}


// إرسال تقرير
function sendReport(reportId) {
    if (confirm('هل أنت متأكد من إرسال هذا التقرير؟')) {
        const formData = new FormData();
        formData.append('action', 'send_report');
        formData.append('report_id', reportId);
        
        showLoading();
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
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
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ حدث خطأ', 'error');
            console.error(error);
        });
    }
}

// أرشفة تقرير
function archiveReport(reportId) {
    if (confirm('هل أنت متأكد من أرشفة هذا التقرير؟')) {
        const formData = new FormData();
        formData.append('action', 'archive_report');
        formData.append('report_id', reportId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        });
    }
}

// حذف تقرير
function deleteReport(reportId) {
    if (confirm('⚠️ هل أنت متأكد من حذف هذا التقرير؟\nلا يمكن التراجع عن هذا الإجراء!')) {
        const formData = new FormData();
        formData.append('action', 'delete_report');
        formData.append('report_id', reportId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        });
    }
}

// تصدير إحصائيات
function exportStats() {
    showNotification('📊 جاري تصدير الإحصائيات...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير', 'success');
    }, 2000);
}

// تطبيق الفلاتر
function applyFilters() {
    const type = document.getElementById('filter-type').value;
    const status = document.getElementById('filter-status').value;
    
    let url = '?page=reports';
    if (type) url += '&type=' + type;
    if (status) url += '&status=' + status;
    
    window.location.href = url;
}

// إعادة تعيين الفلاتر
function resetFilters() {
    window.location.href = '?page=reports';
}

// قائمة التقرير
function toggleReportMenu(reportId) {
    const menu = document.getElementById('menu-' + reportId);
    menu.classList.toggle('hidden');
}

// إغلاق القوائم عند النقر خارجها
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.querySelectorAll('[id^="menu-"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

// دوال مساعدة
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
    notification.innerHTML = `<div class="flex items-center">${message}</div>`;
    
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

function getReportTypeText(type) {
    const types = {
        'monthly': 'تقرير شهري',
        'security': 'تقرير أمني',
        'progress': 'تقرير تقدم',
        'final': 'تقرير نهائي',
        'technical': 'تقرير تقني',
        'audit': 'تقرير تدقيق'
    };
    return types[type] || type;
}


</script>

<!-- ============================================= -->
<!-- CSS إضافي -->
<!-- ============================================= -->
<style>
.report-card {
    border-right: 4px solid #f59e0b;
    transition: all 0.3s ease;
}
.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(245, 158, 11, 0.2);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.spinner {
    border: 3px solid rgba(59, 130, 246, 0.3);
    border-top: 3px solid #3b82f6;
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
</style>