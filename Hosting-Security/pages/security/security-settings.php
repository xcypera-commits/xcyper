<?php
require_once __DIR__ . '/../../includes/security/security-init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/security/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الأمان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-arrow-left"></i> العودة
            </a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2 class="mb-4">⚙️ إعدادات الأمان</h2>
        
        <div class="row">
            <div class="col-md-4">
                <div class="list-group">
                    <a href="#password" class="list-group-item list-group-item-action active">
                        <i class="bi bi-key"></i> كلمة المرور
                    </a>
                    <a href="#mfa" class="list-group-item list-group-item-action">
                        <i class="bi bi-shield-check"></i> التحقق بخطوتين
                    </a>
                    <a href="#sessions" class="list-group-item list-group-item-action">
                        <i class="bi bi-laptop"></i> الجلسات النشطة
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action">
                        <i class="bi bi-bell"></i> التنبيهات
                    </a>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- قسم كلمة المرور -->
                <div id="password" class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-key"></i> تغيير كلمة المرور</h5>
                    </div>
                    <div class="card-body">
                        <form id="changePasswordForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text" id="passwordStrength"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">تغيير كلمة المرور</button>
                        </form>
                    </div>
                </div>
                
                <!-- قسم التحقق بخطوتين -->
                <div id="mfa" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5><i class="bi bi-shield-check"></i> التحقق بخطوتين</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> إضافة طبقة أمان إضافية لحسابك
                        </div>
                        
                        <div id="mfaStatus">
                            <p>الحالة: <span class="badge bg-danger">غير مفعل</span></p>
                            <button class="btn btn-success" onclick="enableMFA()">تفعيل التحقق بخطوتين</button>
                        </div>
                        
                        <div id="mfaSetup" style="display: none;">
                            <p>1. قم بمسح رمز QR باستخدام تطبيق Google Authenticator</p>
                            <div id="qrcode" class="text-center mb-3"></div>
                            
                            <p>2. أدخل الرمز المكون من 6 أرقام</p>
                            <input type="text" id="mfaCode" class="form-control mb-3" maxlength="6" placeholder="123456">
                            
                            <button class="btn btn-primary" onclick="verifyMFA()">تحقق</button>
                            <button class="btn btn-secondary" onclick="cancelMFA()">إلغاء</button>
                        </div>
                    </div>
                </div>
                
                <!-- قسم الجلسات النشطة -->
                <div id="sessions" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5><i class="bi bi-laptop"></i> الجلسات النشطة</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الجهاز</th>
                                    <th>عنوان IP</th>
                                    <th>آخر نشاط</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody id="sessionsTable">
                                <!-- سيتم ملؤها بالجافاسكريبت -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/security/security-main.js"></script>
    <script>
        // التحقق من قوة كلمة المرور
        document.getElementById('new_password').addEventListener('input', function() {
            const strength = SecurityUtils.validatePasswordStrength(this.value);
            const messages = [];
            
            if (!strength.length) messages.push('12 حرفاً على الأقل');
            if (!strength.uppercase) messages.push('حرف كبير');
            if (!strength.lowercase) messages.push('حرف صغير');
            if (!strength.number) messages.push('رقم');
            if (!strength.special) messages.push('رمز خاص');
            
            document.getElementById('passwordStrength').textContent = 
                messages.length > 0 ? 'مطلوب: ' + messages.join('، ') : 'قوة كلمة المرور: ممتازة';
        });
        
        // تغيير كلمة المرور
        document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const response = await fetch('/api/security/auth.php?action=change_password', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('تم تغيير كلمة المرور بنجاح');
                e.target.reset();
            } else {
                alert(result.message || 'حدث خطأ');
            }
        });
        
        // تبديل الأقسام
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // إزالة النشط من الجميع
                document.querySelectorAll('.list-group-item').forEach(i => {
                    i.classList.remove('active');
                });
                
                // إضافة النشط للعنصر المحدد
                this.classList.add('active');
                
                // إظهار القسم المناسب
                const target = this.getAttribute('href').substring(1);
                document.querySelectorAll('.card').forEach(card => {
                    card.style.display = 'none';
                });
                
                document.getElementById(target).style.display = 'block';
            });
        });
    </script>
</body>
</html>