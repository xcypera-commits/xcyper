<?php
require_once __DIR__ . '/../../includes/security/security-init.php';

if (!isset($_SESSION['user_id']) || isset($_SESSION['mfa_verified'])) {
    header('Location: /dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق بخطوتين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header text-center bg-warning">
                        <h4>🔒 التحقق بخطوتين</h4>
                    </div>
                    <div class="card-body text-center">
                        <p>لقد قمنا بإرسال رمز تحقق إلى بريدك الإلكتروني</p>
                        
                        <form id="mfaForm" action="/api/security/auth.php" method="POST">
                            <input type="hidden" name="action" value="verify_mfa">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label for="mfa_code" class="form-label">أدخل الرمز المكون من 6 أرقام</label>
                                <input type="text" class="form-control text-center" 
                                       id="mfa_code" name="mfa_code" maxlength="6" 
                                       pattern="\d{6}" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100">تحقق</button>
                        </form>
                        
                        <div class="mt-3">
                            <a href="#" id="resendCode" class="text-decoration-none">
                                إعادة إرسال الرمز
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('mfaForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            const response = await fetch('/api/security/auth.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = result.redirect || '/dashboard.php';
            } else {
                alert(result.message || 'خطأ في التحقق');
            }
        });
        
        document.getElementById('resendCode').addEventListener('click', async (e) => {
            e.preventDefault();
            
            const response = await fetch('/api/security/auth.php?action=resend_mfa', {
                method: 'GET'
            });
            
            const result = await response.json();
            alert(result.message || 'تم إعادة إرسال الرمز');
        });
    </script>
</body>
</html>