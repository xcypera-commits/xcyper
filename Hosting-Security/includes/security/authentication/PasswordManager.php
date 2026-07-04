<?php
/**
 * مدير كلمات المرور
 * Password Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class PasswordManager {
    
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/security-config.php';
    }
    
    /**
     * تغيير كلمة المرور
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // التحقق من كلمة المرور القديمة
        if (!$this->verifyCurrentPassword($userId, $oldPassword)) {
            return [
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ];
        }
        
        // التحقق من قوة كلمة المرور الجديدة
        $strength = $this->checkPasswordStrength($newPassword);
        if ($strength['score'] < 5) {
            return [
                'success' => false,
                'message' => 'كلمة المرور الجديدة ضعيفة جداً',
                'feedback' => $strength['feedback']
            ];
        }
        
        // التحقق من عدم تكرار كلمة المرور
        if ($this->isPasswordReused($userId, $newPassword)) {
            return [
                'success' => false,
                'message' => 'لا يمكن استخدام كلمة مرور مستخدمة سابقاً'
            ];
        }
        
        // تحديث كلمة المرور
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, [
            'cost' => $this->config['encryption']['bcrypt_cost'] ?? 12
        ]);
        
        // حفظ في قاعدة البيانات
        $this->savePasswordHistory($userId, $hashed);
        
        $this->logger->log('password', "Password changed for user $userId");
        
        // إرسال إشعار
        $this->sendPasswordChangeNotification($userId);
        
        return [
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ];
    }
    
    /**
     * التحقق من كلمة المرور الحالية
     */
    private function verifyCurrentPassword($userId, $password) {
        // محاكاة - استعلام قاعدة البيانات
        $currentHash = '$2y$12$' . str_repeat('x', 53); // محاكاة
        return password_verify($password, $currentHash);
    }
    
    /**
     * فحص قوة كلمة المرور
     */
    public function checkPasswordStrength($password) {
        $score = 0;
        $feedback = [];
        
        // الطول
        if (strlen($password) >= 8) {
            $score += 1;
        } else {
            $feedback[] = 'يجب أن تكون كلمة المرور 8 أحرف على الأقل';
        }
        
        if (strlen($password) >= 12) {
            $score += 1;
        }
        
        // الأحرف الكبيرة
        if (preg_match('/[A-Z]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'يجب أن تحتوي على حرف كبير واحد على الأقل';
        }
        
        // الأحرف الصغيرة
        if (preg_match('/[a-z]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'يجب أن تحتوي على حرف صغير واحد على الأقل';
        }
        
        // الأرقام
        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'يجب أن تحتوي على رقم واحد على الأقل';
        }
        
        // الرموز الخاصة
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'يجب أن تحتوي على رمز خاص واحد على الأقل';
        }
        
        // عدم وجود أنماط متكررة
        if (!preg_match('/(.)\1{2,}/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'يجب ألا تحتوي على أحرف متكررة';
        }
        
        // عدم وجود أنماط متسلسلة
        $sequences = ['123', '234', '345', '456', '567', '678', '789', 
                     'abc', 'bcd', 'cde', 'def', 'efg', 'fgh', 'ghi',
                     'qwe', 'wer', 'ert', 'rty', 'tyu', 'yui', 'uio'];
        
        $hasSequence = false;
        foreach ($sequences as $seq) {
            if (stripos($password, $seq) !== false) {
                $hasSequence = true;
                break;
            }
        }
        
        if (!$hasSequence) {
            $score += 1;
        } else {
            $feedback[] = 'يجب ألا تحتوي على أنماط متسلسلة';
        }
        
        // تقييم القوة
        if ($score <= 3) {
            $strength = 'weak';
        } elseif ($score <= 5) {
            $strength = 'medium';
        } elseif ($score <= 7) {
            $strength = 'strong';
        } else {
            $strength = 'very_strong';
        }
        
        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback
        ];
    }
    
    /**
     * التحقق من تكرار كلمة المرور
     */
    private function isPasswordReused($userId, $newPassword) {
        // محاكاة - استعلام قاعدة البيانات لآخر 5 كلمات مرور
        $history = []; // قائمة بالـ hashes السابقة
        
        foreach ($history as $oldHash) {
            if (password_verify($newPassword, $oldHash)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * حفظ تاريخ كلمات المرور
     */
    private function savePasswordHistory($userId, $hashed) {
        // حفظ في قاعدة البيانات مع الحد الأقصى
        $maxHistory = $this->config['auth']['password_history'] ?? 5;
        
        // محاكاة حفظ التاريخ
        $this->logger->log('password', "Password history updated for user $userId");
    }
    
    /**
     * إرسال إشعار تغيير كلمة المرور
     */
    private function sendPasswordChangeNotification($userId) {
        // إرسال بريد إلكتروني
        $email = 'user@example.com'; // من قاعدة البيانات
        
        $subject = "تم تغيير كلمة المرور";
        $message = "تم تغيير كلمة المرور الخاصة بك بنجاح.\n";
        $message .= "إذا لم تقم بهذا التغيير، يرجى التواصل مع الدعم فوراً.";
        
        mail($email, $subject, $message);
        
        $this->logger->log('notification', "Password change notification sent to user $userId");
    }
    
    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword($email) {
        // التحقق من وجود البريد
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'البريد الإلكتروني غير مسجل'
            ];
        }
        
        // توليد رمز إعادة التعيين
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // ساعة واحدة
        
        // حفظ الرمز
        $_SESSION['reset_token'][$user['id']] = [
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'expires' => $expires
        ];
        
        // إرسال رابط إعادة التعيين
        $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=$token&user=" . $user['id'];
        
        $subject = "إعادة تعيين كلمة المرور";
        $message = "لإعادة تعيين كلمة المرور، يرجى النقر على الرابط التالي:\n";
        $message .= $resetLink . "\n\n";
        $message .= "هذا الرابط صالح لمدة ساعة واحدة.";
        
        mail($email, $subject, $message);
        
        $this->logger->log('password', "Password reset requested for user {$user['id']}");
        
        return [
            'success' => true,
            'message' => 'تم إرسال رابط إعادة التعيين إلى بريدك الإلكتروني'
        ];
    }
    
    /**
     * الحصول على مستخدم بالبريد
     */
    private function getUserByEmail($email) {
        // محاكاة
        if ($email === 'admin@example.com') {
            return ['id' => 1, 'email' => $email];
        }
        return null;
    }
    
    /**
     * تأكيد إعادة تعيين كلمة المرور
     */
    public function confirmReset($userId, $token, $newPassword) {
        // التحقق من الرمز
        if (!isset($_SESSION['reset_token'][$userId])) {
            return [
                'success' => false,
                'message' => 'طلب إعادة تعيين غير صالح'
            ];
        }
        
        $reset = $_SESSION['reset_token'][$userId];
        
        if (strtotime($reset['expires']) < time()) {
            unset($_SESSION['reset_token'][$userId]);
            return [
                'success' => false,
                'message' => 'انتهت صلاحية الرابط'
            ];
        }
        
        if (!password_verify($token, $reset['token'])) {
            return [
                'success' => false,
                'message' => 'الرمز غير صحيح'
            ];
        }
        
        // التحقق من قوة كلمة المرور
        $strength = $this->checkPasswordStrength($newPassword);
        if ($strength['score'] < 5) {
            return [
                'success' => false,
                'message' => 'كلمة المرور ضعيفة جداً'
            ];
        }
        
        // تحديث كلمة المرور
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // حفظ في قاعدة البيانات
        $this->savePasswordHistory($userId, $hashed);
        
        // تنظيف الرمز
        unset($_SESSION['reset_token'][$userId]);
        
        $this->logger->log('password', "Password reset completed for user $userId");
        
        return [
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
        ];
    }
}
?>