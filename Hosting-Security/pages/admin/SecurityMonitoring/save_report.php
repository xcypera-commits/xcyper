<?php
// save_report.php - حفظ التقارير مع PDF
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$db = getDB();

// استقبال البيانات
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'بيانات غير صالحة']);
    exit;
}

try {
    // بدء transaction
    $db->beginTransaction();

    // إدخال التقرير في جدول daily_reports
    $stmt = $db->prepare("
        INSERT INTO daily_reports (report_date, report_type, title, summary, statistics, generated_by, status)
        VALUES (?, ?, ?, ?, ?, ?, 'published')
    ");
    
    $statistics_json = json_encode($input['statistics']);
    $user_id = $_SESSION['user_id'] ?? 1;
    
    $stmt->execute([
        $input['date'],
        $input['type'],
        $input['title'],
        $input['summary'],
        $statistics_json,
        $user_id
    ]);
    
    $report_id = $db->lastInsertId();
    
    // إدخال الإحصائيات في جدول report_statistics
    $stats = $input['statistics'];
    $stmt2 = $db->prepare("
        INSERT INTO report_statistics (report_id, total_alerts, total_threats, critical_events, uptime_percentage)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt2->execute([
        $report_id,
        $stats['total_alerts'] ?? 0,
        $stats['threats'] ?? 0,
        $stats['critical'] ?? 0,
        $stats['uptime'] ?? 99.98
    ]);
    
    $db->commit();
    
    // توليد PDF بعد حفظ البيانات
    $pdf_url = "generate_report_pdf.php?id=" . $report_id;
    
    echo json_encode([
        'success' => true,
        'report_id' => $report_id,
        'pdf_url' => $pdf_url,
        'message' => 'تم حفظ التقرير وإنشاء PDF بنجاح'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>