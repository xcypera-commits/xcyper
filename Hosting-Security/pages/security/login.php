<?php
// manager/config/database.php - نفس الملف القديم
class Database {
    private $host = 'localhost';
    private $db_name = 'security_monitoring_db'; // نفس قاعدة البيانات
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("خطأ في الاتصال: " . $e->getMessage());
        }
        return $this->conn;
    }
}

function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام الاستضافة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/security-styles.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-header text-center bg-primary text-white">
                        <h3>🔐 تسجيل الدخول</h3>
                        <small>نظام الاستضافة والتخزين السحابي</small>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">خطأ في اسم المستخدم أو كلمة المرور</div>
                        <?php endif; ?>
                        
                        <form id="loginForm" action="/api/security/auth.php" method="POST">
                            <input type="hidden" name="action" value="login">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       required data-validation="required">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required data-validation="password">
                                <div class="form-text">كلمة المرور يجب أن تكون 12 حرفاً على الأقل</div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">تذكرني</label>
                            </div>
                            <a href="../secuity-admin.php">
                            <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button></a>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <a href="/pages/security/password-reset.php" class="text-decoration-none">
                                نسيت كلمة المرور؟
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">جميع الحقوق محفوظة © <?= date('Y') ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/security/input-validation.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (!InputValidation.validateForm('loginForm')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>