<?php
// login.php

//session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - X Cyber</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #0a0a1a;
            background-image: radial-gradient(circle at 10% 20%, rgba(0, 102, 204, 0.1) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(255, 51, 51, 0.1) 0%, transparent 40%);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-[rgba(20,30,48,0.9)] rounded-2xl p-8 border border-[#0066cc]/20 backdrop-blur-lg">
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-[#0066cc] to-[#9933ff] rounded-2xl flex items-center justify-center">
                <i class="fas fa-crown text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold bg-gradient-to-l from-[#0066cc] to-[#9933ff] text-transparent bg-clip-text">
                X Cyber
            </h2>
            <p class="text-gray-400 mt-2">لوحة التحكم - تسجيل الدخول</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-500 p-4 rounded-xl mb-6 text-center">
                <i class="fas fa-exclamation-circle ml-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block mb-2 text-gray-300">اسم المستخدم أو البريد</label>
                <input type="text" name="username" required 
                       class="w-full bg-[#1a1f2e] border border-[#0066cc]/20 rounded-xl p-3 focus:outline-none focus:border-[#0066cc] text-white">
            </div>
            
            <div>
                <label class="block mb-2 text-gray-300">كلمة المرور</label>
                <input type="password" name="password" required 
                       class="w-full bg-[#1a1f2e] border border-[#0066cc]/20 rounded-xl p-3 focus:outline-none focus:border-[#0066cc] text-white">
            </div>
            
            <button type="submit" class="w-full py-3 bg-gradient-to-l from-[#0066cc] to-[#9933ff] rounded-xl font-bold hover:shadow-lg transition-all">
                <i class="fas fa-sign-in-alt ml-2"></i>
                تسجيل الدخول
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>للاختبار: admin / admin123</p>
        </div>
    </div>
</body>
</html>