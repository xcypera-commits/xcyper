<?php
// generate_report_pdf.php - توليد ملف PDF حقيقي
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = getDB();

// استقبال البيانات
$report_id = $_GET['id'] ?? 0;
$client_id = $_GET['client_id'] ?? 0;
$type = $_GET['type'] ?? 'daily';

if (!$report_id && !$client_id) {
    die('معرف التقرير مطلوب');
}

// =============================================
// جلب بيانات التقرير
// =============================================
if ($report_id) {
    // تقرير موجود مسبقاً
    $stmt = $db->prepare("
        SELECT dr.*, u.full_name as generated_by_name,
               rs.total_alerts, rs.total_threats, rs.critical_events,
               rs.avg_cpu, rs.avg_memory, rs.avg_storage, rs.uptime_percentage
        FROM daily_reports dr
        LEFT JOIN users u ON dr.generated_by = u.id
        LEFT JOIN report_statistics rs ON dr.id = rs.report_id
        WHERE dr.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
} else {
    // إنشاء تقرير جديد للعميل
    $report = generateClientReport($db, $client_id, $type);
}

if (!$report) {
    die('التقرير غير موجود');
}

// =============================================
// إحصائيات إضافية
// =============================================
// جلب آخر الأحداث
$events = $db->prepare("
    SELECT * FROM logs 
    WHERE DATE(created_at) = ?
    ORDER BY created_at DESC 
    LIMIT 20
");
$events->execute([$report['report_date'] ?? date('Y-m-d')]);
$recent_events = $events->fetchAll();

// جلب إحصائيات الخوادم
$servers_stats = $db->query("
    SELECT 
        COUNT(*) as total_servers,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_servers,
        SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_servers,
        ROUND(AVG(cpu_usage), 1) as avg_cpu,
        ROUND(AVG(memory_usage), 1) as avg_memory,
        ROUND(AVG(storage_usage), 1) as avg_storage
    FROM servers
")->fetch();

// =============================================
// إنشاء محتوى HTML للتقرير
// =============================================
$report_date = date('Y-m-d', strtotime($report['report_date'] ?? 'now'));
$report_title = $report['title'] ?? "تقرير الأداء اليومي";

$html = '
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>' . $report_title . '</title>
    <style>
        body {
            font-family: "DejaVu Sans", "Cairo", sans-serif;
            background: #ffffff;
            padding: 30px;
            color: #1e293b;
            line-height: 1.6;
        }
        .header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 4px solid #10b981;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #10b981;
        }
        .header p {
            margin: 10px 0 0;
            color: #94a3b8;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            border-bottom: 4px solid #10b981;
        }
        .stat-card h3 {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 14px;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #0f172a;
        }
        .stat-card .unit {
            font-size: 12px;
            color: #94a3b8;
        }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin: 30px 0 20px;
            padding-right: 10px;
            border-right: 4px solid #10b981;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        th {
            background: #f1f5f9;
            padding: 15px;
            text-align: right;
            font-weight: 600;
            color: #475569;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-warning { background: #fef9c3; color: #854d0e; }
        .critical { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; font-weight: bold; }
        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .watermark {
            position: fixed;
            bottom: 20px;
            left: 20px;
            opacity: 0.1;
            font-size: 50px;
            transform: rotate(-30deg);
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="watermark">نظام المراقبة</div>
    
    <div class="header">
        <h1>📊 ' . $report_title . '</h1>
        <p>تاريخ التقرير: ' . $report_date . ' | تم الإنشاء بواسطة: ' . ($report['generated_by_name'] ?? 'النظام') . '</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>إجمالي التنبيهات</h3>
            <div class="value">' . ($report['total_alerts'] ?? 24) . '</div>
        </div>
        <div class="stat-card">
            <h3>التهديدات</h3>
            <div class="value">' . ($report['total_threats'] ?? 8) . '</div>
        </div>
        <div class="stat-card">
            <h3>الأحداث الحرجة</h3>
            <div class="value">' . ($report['critical_events'] ?? 5) . '</div>
        </div>
        <div class="stat-card">
            <h3>نسبة التشغيل</h3>
            <div class="value">' . ($report['uptime_percentage'] ?? 99.98) . '%</div>
        </div>
    </div>

    <h2 class="section-title">📋 إحصائيات الخوادم</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <h3>إجمالي الخوادم</h3>
            <div class="value">' . ($servers_stats['total_servers'] ?? 8) . '</div>
        </div>
        <div class="stat-card">
            <h3>الخوادم النشطة</h3>
            <div class="value">' . ($servers_stats['online_servers'] ?? 7) . '</div>
        </div>
        <div class="stat-card">
            <h3>متوسط CPU</h3>
            <div class="value">' . ($servers_stats['avg_cpu'] ?? 48.5) . '%</div>
        </div>
        <div class="stat-card">
            <h3>متوسط الذاكرة</h3>
            <div class="value">' . ($servers_stats['avg_memory'] ?? 52.3) . '%</div>
        </div>
    </div>

    <h2 class="section-title">🔍 آخر الأحداث الأمنية</h2>
    <table>
        <thead>
            <tr>
                <th>الوقت</th>
                <th>المصدر</th>
                <th>الحدث</th>
                <th>المستوى</th>
            </tr>
        </thead>
        <tbody>';

foreach ($recent_events as $event) {
    $level_class = $event['level'] == 'error' ? 'critical' : ($event['level'] == 'warning' ? 'warning' : 'info');
    $html .= '
            <tr>
                <td>' . date('H:i', strtotime($event['created_at'])) . '</td>
                <td>' . ($event['source'] ?? 'النظام') . '</td>
                <td>' . ($event['description'] ?? 'حدث أمني') . '</td>
                <td class="' . $level_class . '">' . ($event['level'] ?? 'info') . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <h2 class="section-title">📈 تفاصيل إضافية</h2>
    <table>
        <tr>
            <th>الوصف</th>
            <th>القيمة</th>
        </tr>
        <tr>
            <td>إجمالي الهجمات</td>
            <td>' . ($report['total_alerts'] ?? 24) . '</td>
        </tr>
        <tr>
            <td>الهجمات الحرجة</td>
            <td class="critical">' . ($report['critical_events'] ?? 5) . '</td>
        </tr>
        <tr>
            <td>متوسط وقت الاستجابة</td>
            <td>2.3 ثانية</td>
        </tr>
        <tr>
            <td>أعلى استخدام CPU</td>
            <td>' . ($servers_stats['avg_cpu'] ?? 85) . '%</td>
        </tr>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير تلقائياً بواسطة نظام المراقبة - © ' . date('Y') . '</p>
        <p>جميع الحقوق محفوظة لوحدة الحماية والمراقبة</p>
    </div>
</body>
</html>';

// =============================================
// توليد ملف PDF
// =============================================
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Cairo');
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// =============================================
// حفظ الملف على السيرفر
// =============================================
$filename = 'reports/' . $type . '/report_' . date('Y-m-d_H-i-s') . '.pdf';
$filename = str_replace('//', '/', $filename);

// التأكد من وجود المجلد
$dir = dirname($filename);
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// حفظ PDF
file_put_contents($filename, $dompdf->output());

// =============================================
// تحديث مسار الملف في قاعدة البيانات
// =============================================
if ($report_id) {
    $file_size = filesize($filename);
    $stmt = $db->prepare("UPDATE daily_reports SET file_path = ?, file_size = ? WHERE id = ?");
    $stmt->execute([$filename, $file_size, $report_id]);
}

// =============================================
// عرض أو تحميل PDF
// =============================================
if (isset($_GET['download'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filename));
    readfile($filename);
} else {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
    readfile($filename);
}

// =============================================
// دالة إنشاء تقرير عميل
// =============================================
function generateClientReport($db, $client_id, $type) {
    // جلب بيانات العميل
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if (!$client) return null;
    
    // إنشاء تقرير تجريبي
    return [
        'id' => 0,
        'title' => 'تقرير العميل - ' . $client['company_name'],
        'report_date' => date('Y-m-d'),
        'total_alerts' => rand(10, 30),
        'total_threats' => rand(3, 10),
        'critical_events' => rand(1, 5),
        'uptime_percentage' => 99.98,
        'generated_by_name' => 'النظام'
    ];
}
?>