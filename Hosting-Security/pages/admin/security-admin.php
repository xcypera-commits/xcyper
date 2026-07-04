<?php
//require_once __DIR__ . '/../../security-init.php';
//require_permission('manage_system');

// الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'security_monitoring_db'; // اسم قاعدة البيانات
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة إدارة الأمن</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shield-lock"></i> إدارة الأمن
            </a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#dashboard" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2"></i> لوحة التحكم
                    </a>
                    <a href="#users" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> إدارة المستخدمين
                    </a>
                    <a href="#logs" class="list-group-item list-group-item-action">
                        <i class="bi bi-journal-text"></i> سجلات النظام
                    </a>
                    <a href="#firewall" class="list-group-item list-group-item-action">
                        <i class="bi bi-fire"></i> الجدار الناري
                    </a>
                    <a href="#backup" class="list-group-item list-group-item-action">
                        <i class="bi bi-save"></i> النسخ الاحتياطي
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h4>إحصائيات الأمن</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5>المستخدمون النشطون</h5>
                                        <h2>142</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5>المحاولات الفاشلة</h5>
                                        <h2>23</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5>التنبيهات اليوم</h5>
                                        <h2>5</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h5>الهجمات المحجوبة</h5>
                                        <h2>12</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>آخر الأنشطة</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>المستخدم</th>
                                    <th>الإجراء</th>
                                    <th>الوقت</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>admin</td>
                                    <td>تسجيل دخول</td>
                                    <td>5 دقائق</td>
                                    <td>192.168.1.1</td>
                                </tr>
                                <!-- بيانات حقيقية من قاعدة البيانات -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/security/security-utils.js"></script>
</body>
</html>