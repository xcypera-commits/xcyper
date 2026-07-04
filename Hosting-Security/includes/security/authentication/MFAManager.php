<?php
/**
 * مدير المصادقة متعددة العوامل
 * Multi-Factor Authentication Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class MFAManager {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
    }
    
    /**
     * تفعيل MFA لمستخدم
     */
    public function enableMFA($userId, $method = 'google_authenticator') {
        $secret = $this->generateSecret();
        $qrCode = $this->generateQRCode($userId, $secret);
        
        // حفظ السر في قاعدة البيانات
        $_SESSION['mfa_setup'][$userId] = [
            'secret' => $secret,
            'method' => $method,
            'pending' => true
        ];
        
        $this->logger->log('mfa', "MFA setup initiated for user $userId");
        
        return [
            'secret' => $secret,
            'qr_code' => $qrCode,
            'method' => $method
        ];
    }
    
    /**
     * توليد سر MFA
     */
    private function generateSecret() {
        $bytes = random_bytes(20);
        return $this->base32Encode($bytes);
    }
    
    /**
     * تشفير Base32
     */
    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        
        for ($i = 0; $i < strlen($data); $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        $encoded = '';
        for ($i = 0; $i < strlen($binary); $i += 5) {
            $chunk = substr($binary, $i, 5);
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $index = bindec($chunk);
            $encoded .= $alphabet[$index];
        }
        
        return $encoded;
    }
    
    /**
     * توليد رمز QR
     */
    private function generateQRCode($userId, $secret) {
        $issuer = urlencode('Hosting Security');
        $account = urlencode("user_$userId");
        $otpUrl = "otpauth://totp/$issuer:$account?secret=$secret&issuer=$issuer&algorithm=SHA1&digits=6&period=30";
        
        // استخدام خدمة QR code (مثال: Google Charts)
        $qrCode = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($otpUrl);
        
        return $qrCode;
    }
    
    /**
     * التحقق من رمز MFA
     */
    public function verifyCode($secret, $code) {
        // التحقق من رمز Google Authenticator (TOTP)
        $timeSlice = floor(time() / 30);
        
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->generateTOTP($secret, $timeSlice + $i);
            if ($this->timingSafeCompare($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * توليد رمز TOTP
     */
    private function generateTOTP($secret, $timeSlice) {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        $hash = hash_hmac('sha1', $time, $key, true);
        
        $offset = ord(substr($hash, -1)) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, 6);
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * فك تشفير Base32
     */
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $binary = '';
        
        for ($i = 0; $i < strlen($data); $i++) {
            $pos = strpos($alphabet, $data[$i]);
            if ($pos !== false) {
                $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }
        
        $decoded = '';
        for ($i = 0; $i < strlen($binary); $i += 8) {
            $chunk = substr($binary, $i, 8);
            if (strlen($chunk) == 8) {
                $decoded .= chr(bindec($chunk));
            }
        }
        
        return $decoded;
    }
    
    /**
     * مقارنة آمنة لمنع هجمات التوقيت
     */
    private function timingSafeCompare($known, $user) {
        if (function_exists('hash_equals')) {
            return hash_equals($known, $user);
        }
        
        $knownLen = strlen($known);
        $userLen = strlen($user);
        $result = $knownLen ^ $userLen;
        
        for ($i = 0; $i < $userLen; $i++) {
            $result |= ord($known[$i % $knownLen]) ^ ord($user[$i]);
        }
        
        return $result === 0;
    }
    
    /**
     * إرسال رمز عبر البريد الإلكتروني
     */
    public function sendEmailCode($email) {
        $code = sprintf("%06d", random_int(0, 999999));
        
        $_SESSION['email_mfa_code'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['email_mfa_expires'] = time() + 300; // 5 دقائق
        
        $subject = "رمز التحقق الخاص بك";
        $message = "رمز التحقق الخاص بك هو: $code\n";
        $message .= "هذا الرمز صالح لمدة 5 دقائق.";
        
        mail($email, $subject, $message);
        
        $this->logger->log('mfa', "Email MFA code sent to $email");
        
        return true;
    }
    
    /**
     * إرسال رمز عبر SMS
     */
    public function sendSMSCode($phone) {
        $code = sprintf("%06d", random_int(0, 999999));
        
        $_SESSION['sms_mfa_code'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['sms_mfa_expires'] = time() + 300; // 5 دقائق
        
        // محاكاة إرسال SMS
        // استخدم خدمة SMS حقيقية هنا
        
        $this->logger->log('mfa', "SMS MFA code sent to $phone");
        
        return true;
    }
    
    /**
     * تعطيل MFA
     */
    public function disableMFA($userId) {
        // حذف من قاعدة البيانات
        unset($_SESSION['mfa_setup'][$userId]);
        
        $this->logger->log('mfa', "MFA disabled for user $userId");
        
        return true;
    }
    
    /**
     * الحصول على طرق MFA المتاحة
     */
    public function getAvailableMethods() {
        return [
            'google_authenticator' => 'Google Authenticator',
            'email' => 'البريد الإلكتروني',
            'sms' => 'الرسائل النصية'
        ];
    }
    
    /**
     * التحقق من رمز البريد
     */
    public function verifyEmailCode($code) {
        if (!isset($_SESSION['email_mfa_code'])) {
            return false;
        }
        
        if (time() > $_SESSION['email_mfa_expires']) {
            unset($_SESSION['email_mfa_code']);
            return false;
        }
        
        if (password_verify($code, $_SESSION['email_mfa_code'])) {
            unset($_SESSION['email_mfa_code']);
            return true;
        }
        
        return false;
    }
    
    /**
     * التحقق من رمز SMS
     */
    public function verifySMSCode($code) {
        if (!isset($_SESSION['sms_mfa_code'])) {
            return false;
        }
        
        if (time() > $_SESSION['sms_mfa_expires']) {
            unset($_SESSION['sms_mfa_code']);
            return false;
        }
        
        if (password_verify($code, $_SESSION['sms_mfa_code'])) {
            unset($_SESSION['sms_mfa_code']);
            return true;
        }
        
        return false;
    }
}
?>