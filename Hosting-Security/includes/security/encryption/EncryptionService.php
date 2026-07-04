<?php
/**
 * خدمة التشفير
 * Encryption Service
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class EncryptionService {
    
    private $key;
    private $cipher;
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/encryption-config.php';
        
        $this->key = $this->config['keys']['master_key'] ?? 'default_key_change_this';
        $this->cipher = $this->config['ciphers']['aes_256_gcm'];
    }
    
    /**
     * تشفير بيانات
     */
    public function encrypt($data, $key = null) {
        if (empty($data)) {
            return $data;
        }
        
        $key = $key ?? $this->key;
        $method = $this->cipher['method'];
        $iv = random_bytes($this->cipher['iv_length']);
        
        // تشفير مع GCM
        $tag = '';
        $encrypted = openssl_encrypt(
            $data,
            $method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            $this->cipher['tag_length']
        );
        
        if ($encrypted === false) {
            $this->logger->log('encryption', 'Encryption failed');
            throw new Exception('Encryption failed');
        }
        
        // دمج IV + TAG + البيانات المشفرة
        $result = base64_encode($iv . $tag . $encrypted);
        
        return $result;
    }
    
    /**
     * فك تشفير بيانات
     */
    public function decrypt($encryptedData, $key = null) {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        $key = $key ?? $this->key;
        $method = $this->cipher['method'];
        
        $decoded = base64_decode($encryptedData);
        
        // استخراج IV و TAG والبيانات
        $iv = substr($decoded, 0, $this->cipher['iv_length']);
        $tag = substr($decoded, $this->cipher['iv_length'], $this->cipher['tag_length']);
        $encrypted = substr($decoded, $this->cipher['iv_length'] + $this->cipher['tag_length']);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            $this->logger->log('encryption', 'Decryption failed');
            throw new Exception('Decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * تشفير باستخدام مفتاح عام
     */
    public function encryptWithPublicKey($data, $publicKeyPath) {
        $publicKey = file_get_contents($publicKeyPath);
        
        $encrypted = '';
        if (!openssl_public_encrypt($data, $encrypted, $publicKey)) {
            throw new Exception('Public key encryption failed');
        }
        
        return base64_encode($encrypted);
    }
    
    /**
     * فك تشفير باستخدام مفتاح خاص
     */
    public function decryptWithPrivateKey($encryptedData, $privateKeyPath, $passphrase = null) {
        $privateKey = file_get_contents($privateKeyPath);
        
        if ($passphrase) {
            $privateKey = openssl_pkey_get_private($privateKey, $passphrase);
        }
        
        $decoded = base64_decode($encryptedData);
        $decrypted = '';
        
        if (!openssl_private_decrypt($decoded, $decrypted, $privateKey)) {
            throw new Exception('Private key decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * تشفير ملف
     */
    public function encryptFile($sourcePath, $destPath = null) {
        if (!file_exists($sourcePath)) {
            throw new Exception('Source file not found');
        }
        
        $destPath = $destPath ?? $sourcePath . '.enc';
        $data = file_get_contents($sourcePath);
        
        $encrypted = $this->encrypt($data);
        file_put_contents($destPath, $encrypted);
        
        $this->logger->log('encryption', "File encrypted: " . basename($sourcePath));
        
        return $destPath;
    }
    
    /**
     * فك تشفير ملف
     */
    public function decryptFile($sourcePath, $destPath = null) {
        if (!file_exists($sourcePath)) {
            throw new Exception('Source file not found');
        }
        
        $destPath = $destPath ?? preg_replace('/\.enc$/', '', $sourcePath);
        $data = file_get_contents($sourcePath);
        
        $decrypted = $this->decrypt($data);
        file_put_contents($destPath, $decrypted);
        
        $this->logger->log('encryption', "File decrypted: " . basename($sourcePath));
        
        return $destPath;
    }
    
    /**
     * تشفير بيانات حساسة (للتخزين في قاعدة البيانات)
     */
    public function encryptSensitive($data) {
        return $this->encrypt($data);
    }
    
    /**
     * فك تشفير بيانات حساسة
     */
    public function decryptSensitive($data) {
        return $this->decrypt($data);
    }
    
    /**
     * تغيير مفتاح التشفير
     */
    public function reencrypt($data, $oldKey, $newKey) {
        $decrypted = $this->decrypt($data, $oldKey);
        return $this->encrypt($decrypted, $newKey);
    }
}
?>