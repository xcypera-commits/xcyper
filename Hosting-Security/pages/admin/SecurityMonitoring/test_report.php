<?php
// test_report.php - ملف تجريبي بسيط
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

// إنشاء محتوى بسيط
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>تقرير تجريبي</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        h1 { color: green; text-align: center; }
    </style>
</head>
<body>
    <h1>📊 تقرير تجريبي</h1>
    <p>تاريخ التقرير: ' . date('Y-m-d H:i:s') . '</p>
    <p>هذا تقرير تجريبي للتحقق من عمل PDF</p>
</body>
</html>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// عرض PDF في المتصفح
$dompdf->stream("تقرير_تجريبي.pdf");
?>