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
require_once __DIR__ . '/../../security-init.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center bg-info text-white">
                        <h4>🔄 إعادة تعيين كلمة المرور</h4>
                    </div>
                    <div class="card-body">
                        <div id="step1">
                            <p>أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة التعيين</p>
                            
                            <form id="resetRequestForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <button type="submit" class="btn btn-info w-100">إرسال رابط التعيين</button>
                            </form>
                        </div>
                        
                        <div id="step2" style="display: none;">
                            <p>أدخل كلمة المرور الجديدة</p>
                            
                            <form id="resetPasswordForm">
                                <input type="hidden" name="token" id="resetToken">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">يجب أن تكون 12 حرفاً على الأقل وتحتوي على أحرف كبيرة وصغيرة وأرقام</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">تعيين كلمة المرور</button>
                            </form>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="/pages/security/login.php" class="text-decoration-none">
                                العودة لتسجيل الدخول
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/security/input-validation.js"></script>
    <script>
        document.getElementById('resetRequestForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const response = await fetch('/api/security/auth.php?action=request_password_reset', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                document.getElementById('resetToken').value = result.token;
            } else {
                alert(result.message || 'حدث خطأ');
            }
        });
        
        document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                alert('كلمتا المرور غير متطابقتين');
                return;
            }
            
            const validation = SecurityUtils.validatePasswordStrength(password);
            if (!validation.length || !validation.uppercase || !validation.lowercase || !validation.number) {
                alert('كلمة المرور ضعيفة');
                return;
            }
            
            const formData = new FormData(e.target);
            const response = await fetch('/api/security/auth.php?action=reset_password', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('تم تعيين كلمة المرور بنجاح');
                window.location.href = '/pages/security/login.php';
            } else {
                alert(result.message || 'حدث خطأ');
            }
        });
    </script>
</body>
</html>