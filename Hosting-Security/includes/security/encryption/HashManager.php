<?php
/**
 * مدير التجزئة
 * Hash Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class HashManager {
    
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/encryption-config.php';
    }
    
    /**
     * إنشاء تجزئة لكلمة مرور
     */
    public function hashPassword($password) {
        $options = [
            'cost' => $this->config['hashing']['bcrypt']['cost'] ?? 12
        ];
        
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }
    
    /**
     * التحقق من كلمة مرور
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * التحقق من الحاجة لإعادة التجزئة
     */
    public function needsRehash($hash) {
        $options = [
            'cost' => $this->config['hashing']['bcrypt']['cost'] ?? 12
        ];
        
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $options);
    }
    
    /**
     * إنشاء تجزئة HMAC
     */
    public function hmac($data, $key = null) {
        $key = $key ?? $this->config['keys']['hmac_key'] ?? 'default_hmac_key';
        return hash_hmac($this->config['hash_algorithm'] ?? 'sha256', $data, $key);
    }
    
    /**
     * إنشاء تجزئة لملف
     */
    public function hashFile($filepath, $algorithm = null) {
        $algorithm = $algorithm ?? $this->config['hash_algorithm'] ?? 'sha256';
        
        if (!file_exists($filepath)) {
            throw new Exception('File not found');
        }
        
        return hash_file($algorithm, $filepath);
    }
    
    /**
     * إنشاء تجزئة آمنة (مع salt)
     */
    public function secureHash($data, $salt = null) {
        if ($salt === null) {
            $salt = bin2hex(random_bytes(16));
        }
        
        $hash = hash('sha256', $data . $salt);
        
        return [
            'hash' => $hash,
            'salt' => $salt
        ];
    }
    
    /**
     * التحقق من تجزئة آمنة
     */
    public function verifySecureHash($data, $hash, $salt) {
        $computed = hash('sha256', $data . $salt);
        return hash_equals($computed, $hash);
    }
    
    /**
     * إنشاء تجزئة سريعة (للمفاتيح)
     */
    public function quickHash($data) {
        return hash('sha256', $data);
    }
    
    /**
     * إنشاء UUID v4
     */
    public function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * إنشاء رمز عشوائي
     */
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * حساب بصمة البيانات
     */
    public function fingerprint($data) {
        return hash('sha256', serialize($data));
    }
    
    /**
     * مقارنة آمنة
     */
    public function secureCompare($a, $b) {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        
        // تنفيذ يدوي آمن
        $lenA = strlen($a);
        $lenB = strlen($b);
        
        if ($lenA !== $lenB) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < $lenA; $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result === 0;
    }
}
?>