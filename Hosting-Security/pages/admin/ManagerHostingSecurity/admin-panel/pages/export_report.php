<?php
/**
 * تصدير التقارير
 * Export Reports Page
 */

// تعريف ثابت للوصول
define('ADMIN_ACCESS', true);
require_once '../../../../../security-init.php';
// تضمين الملفات الأساسية
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/admin_functions.php';

// طلب تسجيل الدخول وصلاحية المدير


// تحديد نوع التصدير والفترة
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// تحديد الفترة الزمنية
switch ($period) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d');
        $periodText = 'اليوم';
        break;
    case 'yesterday':
        $dateFrom = date('Y-m-d', strtotime('-1 day'));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        $periodText = 'أمس';
        break;
    case 'week':
        $dateFrom = date('Y-m-d', strtotime('-7 days'));
        $dateTo = date('Y-m-d');
        $periodText = 'آخر 7 أيام';
        break;
    case 'month':
        $dateFrom = date('Y-m-d', strtotime('-30 days'));
        $dateTo = date('Y-m-d');
        $periodText = 'آخر 30 يوم';
        break;
    case 'year':
        $dateFrom = date('Y-m-d', strtotime('-365 days'));
        $dateTo = date('Y-m-d');
        $periodText = 'آخر سنة';
        break;
    case 'custom':
        $periodText = "من $dateFrom إلى $dateTo";
        break;
}

// جلب جميع البيانات للتصدير
try {
    // إحصائيات المستخدمين
    $stmt = $db->query("SELECT COUNT(*) as total FROM users_all WHERE deleted_at IS NULL");
    $totalUsers = $stmt->fetch()['total'];
    
    $stmt = $db->query("
        SELECT user_type, COUNT(*) as count 
        FROM users_all 
        WHERE deleted_at IS NULL 
        GROUP BY user_type
    ");
    $usersByType = $stmt->fetchAll();
    
    // إحصائيات الأحداث
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $totalEvents = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("
        SELECT event_type, COUNT(*) as count 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY event_type
        ORDER BY count DESC
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $eventsByType = $stmt->fetchAll();
    
    $stmt = $db->prepare("
        SELECT severity, COUNT(*) as count 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY severity
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $eventsBySeverity = $stmt->fetchAll();
    
    $stmt = $db->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM user_events 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $dailyActivity = $stmt->fetchAll();
    
    // إحصائيات المشاريع
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $totalProjects = $stmt->fetch()['total'];
    
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM projects 
        GROUP BY status
    ");
    $projectsByStatus = $stmt->fetchAll();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM projects 
        WHERE status = 'completed' 
        AND updated_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $completedProjects = $stmt->fetch()['count'];
    
    // إحصائيات المهام
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_tasks");
    $totalTasks = $stmt->fetch()['total'];
    
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM project_tasks 
        GROUP BY status
    ");
    $tasksByStatus = $stmt->fetchAll();
    
    // إحصائيات الأمان
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM user_events 
        WHERE event_type IN ('security_alert', 'threat_detected', 'malware_found')
        AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $totalThreats = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM user_events 
        WHERE severity = 'critical'
        AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $criticalAlerts = $stmt->fetch()['total'];
    
    // آخر الأحداث
    $stmt = $db->prepare("
        SELECT ue.*, ua.full_name, ua.username 
        FROM user_events ue 
        LEFT JOIN users_all ua ON ue.user_id = ua.id 
        WHERE ue.created_at BETWEEN ? AND ?
        ORDER BY ue.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $recentEvents = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}

// تصدير حسب الصيغة المطلوبة
switch ($format) {
    case 'pdf':
        exportPDF();
        break;
    case 'excel':
        exportExcel();
        break;
    case 'csv':
        exportCSV();
        break;
    default:
        die('صيغة غير مدعومة');
}

/**
 * تصدير PDF
 */
function exportPDF() {
    global $totalUsers, $usersByType, $totalEvents, $eventsByType, $eventsBySeverity;
    global $totalProjects, $projectsByStatus, $completedProjects, $totalTasks, $tasksByStatus;
    global $totalThreats, $criticalAlerts, $recentEvents, $periodText, $dateFrom, $dateTo;
    
    // محاكاة PDF - في الإنتاج استخدم مكتبة مثل mPDF أو TCPDF
    header('Content-Type: text/html; charset=utf-8');
    
    $html = '
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>تقرير النظام الشامل</title>
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
            h1 { color: #1e3c72; text-align: center; }
            h2 { color: #2a5298; border-bottom: 2px solid #1e3c72; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background: #1e3c72; color: white; padding: 8px; text-align: right; }
            td { padding: 6px; border: 1px solid #ddd; }
            .header { text-align: center; margin-bottom: 30px; }
            .date { color: #666; margin-bottom: 20px; }
            .stat-box { display: inline-block; width: 23%; margin: 1%; background: #f0f0f0; padding: 10px; text-align: center; border-radius: 5px; }
            .stat-value { font-size: 20px; font-weight: bold; color: #1e3c72; }
            .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>تقرير النظام الشامل</h1>
            <div class="date">الفترة: ' . $periodText . ' (' . $dateFrom . ' - ' . $dateTo . ')</div>
            <div class="date">تاريخ التقرير: ' . date('Y-m-d H:i:s') . '</div>
        </div>
        
        <h2>إحصائيات سريعة</h2>
        <div style="text-align: center;">
            <div class="stat-box">
                <div>إجمالي المستخدمين</div>
                <div class="stat-value">' . number_format($totalUsers) . '</div>
            </div>
            <div class="stat-box">
                <div>إجمالي الأحداث</div>
                <div class="stat-value">' . number_format($totalEvents) . '</div>
            </div>
            <div class="stat-box">
                <div>إجمالي المشاريع</div>
                <div class="stat-value">' . number_format($totalProjects) . '</div>
            </div>
            <div class="stat-box">
                <div>إجمالي المهام</div>
                <div class="stat-value">' . number_format($totalTasks) . '</div>
            </div>
        </div>
        
        <h2>إحصائيات المستخدمين</h2>
        <table>
            <thead>
                <tr>
                    <th>نوع المستخدم</th>
                    <th>العدد</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($usersByType as $type) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($type['user_type']) . '</td>
                    <td>' . $type['count'] . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <h2>إحصائيات الأحداث</h2>
        <table>
            <thead>
                <tr>
                    <th>نوع الحدث</th>
                    <th>العدد</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($eventsByType as $event) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($event['event_type']) . '</td>
                    <td>' . $event['count'] . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <table>
            <thead>
                <tr>
                    <th>مستوى الخطورة</th>
                    <th>العدد</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($eventsBySeverity as $sev) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($sev['severity'] ?? 'غير محدد') . '</td>
                    <td>' . $sev['count'] . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <h2>إحصائيات المشاريع</h2>
        <table>
            <thead>
                <tr>
                    <th>حالة المشروع</th>
                    <th>العدد</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($projectsByStatus as $proj) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($proj['status']) . '</td>
                    <td>' . $proj['count'] . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <h2>إحصائيات المهام</h2>
        <table>
            <thead>
                <tr>
                    <th>حالة المهمة</th>
                    <th>العدد</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($tasksByStatus as $task) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($task['status']) . '</td>
                    <td>' . $task['count'] . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <h2>آخر الأحداث (100 حدث)</h2>
        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>المستخدم</th>
                    <th>الحدث</th>
                    <th>الوصف</th>
                    <th>المستوى</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($recentEvents as $event) {
        $html .= '<tr>
                    <td>' . date('Y-m-d H:i', strtotime($event['created_at'])) . '</td>
                    <td>' . htmlspecialchars($event['full_name'] ?? $event['username'] ?? 'نظام') . '</td>
                    <td>' . htmlspecialchars($event['event_type']) . '</td>
                    <td>' . htmlspecialchars(mb_substr($event['description'] ?? '', 0, 50)) . '</td>
                    <td>' . htmlspecialchars($event['severity'] ?? '-') . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="footer">
            <p>تم إنشاء هذا التقرير تلقائياً بواسطة نظام الحماية - جميع الحقوق محفوظة</p>
        </div>
    </body>
    </html>';
    
    // تعيين headers للتحميل
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: max-age=0');
    
    // في الإنتاج استخدم مكتبة PDF حقيقية
    echo $html;
    exit();
}

/**
 * تصدير Excel
 */
function exportExcel() {
    global $totalUsers, $usersByType, $totalEvents, $eventsByType, $eventsBySeverity;
    global $totalProjects, $projectsByStatus, $completedProjects, $totalTasks, $tasksByStatus;
    global $totalThreats, $criticalAlerts, $recentEvents, $periodText, $dateFrom, $dateTo;
    
    // محاكاة Excel - تصدير CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.csv"');
    
    // فتح ملف CSV
    $output = fopen('php://output', 'w');
    
    // إضافة BOM للعربية
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // عنوان التقرير
    fputcsv($output, ['تقرير النظام الشامل']);
    fputcsv($output, ['الفترة:', $periodText, $dateFrom, 'إلى', $dateTo]);
    fputcsv($output, ['تاريخ التقرير:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // إحصائيات سريعة
    fputcsv($output, ['إحصائيات سريعة']);
    fputcsv($output, ['البيان', 'القيمة']);
    fputcsv($output, ['إجمالي المستخدمين', $totalUsers]);
    fputcsv($output, ['إجمالي الأحداث', $totalEvents]);
    fputcsv($output, ['إجمالي المشاريع', $totalProjects]);
    fputcsv($output, ['إجمالي المهام', $totalTasks]);
    fputcsv($output, ['التهديدات الأمنية', $totalThreats]);
    fputcsv($output, ['التنبيهات الحرجة', $criticalAlerts]);
    fputcsv($output, []);
    
    // المستخدمين حسب النوع
    fputcsv($output, ['المستخدمين حسب النوع']);
    fputcsv($output, ['نوع المستخدم', 'العدد']);
    foreach ($usersByType as $type) {
        fputcsv($output, [$type['user_type'], $type['count']]);
    }
    fputcsv($output, []);
    
    // الأحداث حسب النوع
    fputcsv($output, ['الأحداث حسب النوع']);
    fputcsv($output, ['نوع الحدث', 'العدد']);
    foreach ($eventsByType as $event) {
        fputcsv($output, [$event['event_type'], $event['count']]);
    }
    fputcsv($output, []);
    
    // الأحداث حسب المستوى
    fputcsv($output, ['الأحداث حسب مستوى الخطورة']);
    fputcsv($output, ['مستوى الخطورة', 'العدد']);
    foreach ($eventsBySeverity as $sev) {
        fputcsv($output, [$sev['severity'] ?? 'غير محدد', $sev['count']]);
    }
    fputcsv($output, []);
    
    // المشاريع حسب الحالة
    fputcsv($output, ['المشاريع حسب الحالة']);
    fputcsv($output, ['الحالة', 'العدد']);
    foreach ($projectsByStatus as $proj) {
        fputcsv($output, [$proj['status'], $proj['count']]);
    }
    fputcsv($output, []);
    
    // المهام حسب الحالة
    fputcsv($output, ['المهام حسب الحالة']);
    fputcsv($output, ['الحالة', 'العدد']);
    foreach ($tasksByStatus as $task) {
        $statusText = match($task['status']) {
            'pending' => 'معلق',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $task['status']
        };
        fputcsv($output, [$statusText, $task['count']]);
    }
    fputcsv($output, []);
    
    // آخر الأحداث
    fputcsv($output, ['آخر الأحداث']);
    fputcsv($output, ['التاريخ', 'المستخدم', 'الحدث', 'الوصف', 'المستوى']);
    foreach ($recentEvents as $event) {
        fputcsv($output, [
            date('Y-m-d H:i', strtotime($event['created_at'])),
            $event['full_name'] ?? $event['username'] ?? 'نظام',
            $event['event_type'],
            mb_substr($event['description'] ?? '', 0, 50),
            $event['severity'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit();
}

/**
 * تصدير CSV
 */
function exportCSV() {
    // نفس وظيفة Excel لكن بامتداد CSV
    exportExcel();
}
?>