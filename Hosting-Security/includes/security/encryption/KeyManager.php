<?php
/**
 * مدير المفاتيح
 * Key Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class KeyManager {
    
    private $logger;
    private $config;
    private $keysPath;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require SECURITY_PATH . 'config/encryption-config.php';
        $this->keysPath = ROOT_PATH . 'keys/';
        
        $this->ensureDirectories();
    }
    
    /**
     * التأكد من وجود المجلدات
     */
    private function ensureDirectories() {
        $dirs = [
            $this->keysPath,
            $this->keysPath . 'private/',
            $this->keysPath . 'public/',
            $this->keysPath . 'certificates/',
            $this->keysPath . 'secrets/'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
        }
    }
    
    /**
     * إنشاء زوج مفاتيح RSA
     */
    public function generateRSAKeyPair($bits = 2048) {
        $config = [
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $resource = openssl_pkey_new($config);
        
        if (!$resource) {
            throw new Exception('Failed to generate RSA key pair');
        }
        
        // استخراج المفتاح الخاص
        openssl_pkey_export($resource, $privateKey);
        
        // استخراج المفتاح العام
        $keyDetails = openssl_pkey_get_details($resource);
        $publicKey = $keyDetails['key'];
        
        return [
            'private' => $privateKey,
            'public' => $publicKey,
            'bits' => $bits
        ];
    }
    
    /**
     * حفظ زوج مفاتيح
     */
    public function saveKeyPair($name, $keyPair, $passphrase = null) {
        $timestamp = date('Ymd_His');
        
        // حفظ المفتاح الخاص
        $privatePath = $this->keysPath . 'private/' . $name . '_' . $timestamp . '.key';
        if ($passphrase) {
            // تشفير المفتاح الخاص
            $encrypted = openssl_encrypt(
                $keyPair['private'],
                'aes-256-cbc',
                $passphrase,
                0,
                str_repeat('0', 16)
            );
            file_put_contents($privatePath, $encrypted);
        } else {
            file_put_contents($privatePath, $keyPair['private']);
        }
        chmod($privatePath, 0600);
        
        // حفظ المفتاح العام
        $publicPath = $this->keysPath . 'public/' . $name . '_' . $timestamp . '.pub';
        file_put_contents($publicPath, $keyPair['public']);
        chmod($publicPath, 0644);
        
        $this->logger->log('key', "Key pair saved: $name");
        
        return [
            'private' => $privatePath,
            'public' => $publicPath
        ];
    }
    
    /**
     * تحميل مفتاح خاص
     */
    public function loadPrivateKey($path, $passphrase = null) {
        if (!file_exists($path)) {
            throw new Exception('Private key not found');
        }
        
        $keyData = file_get_contents($path);
        
        if ($passphrase) {
            // فك تشفير المفتاح الخاص
            $keyData = openssl_decrypt(
                $keyData,
                'aes-256-cbc',
                $passphrase,
                0,
                str_repeat('0', 16)
            );
        }
        
        return openssl_pkey_get_private($keyData);
    }
    
    /**
     * تحميل مفتاح عام
     */
    public function loadPublicKey($path) {
        if (!file_exists($path)) {
            throw new Exception('Public key not found');
        }
        
        $keyData = file_get_contents($path);
        return openssl_pkey_get_public($keyData);
    }
    
    /**
     * إنشاء مفتاح سري عشوائي
     */
    public function generateSecretKey($bytes = 32) {
        return bin2hex(random_bytes($bytes));
    }
    
    /**
     * حفظ مفتاح سري
     */
    public function saveSecret($name, $secret) {
        $path = $this->keysPath . 'secrets/' . $name . '.secret';
        file_put_contents($path, $this->encryptSecret($secret));
        chmod($path, 0600);
        
        $this->logger->log('key', "Secret saved: $name");
        
        return $path;
    }
    
    /**
     * تحميل مفتاح سري
     */
    public function loadSecret($name) {
        $path = $this->keysPath . 'secrets/' . $name . '.secret';
        
        if (!file_exists($path)) {
            return null;
        }
        
        $encrypted = file_get_contents($path);
        return $this->decryptSecret($encrypted);
    }
    
    /**
     * تشفير السر (للتخزين)
     */
    private function encryptSecret($secret) {
        $key = $this->config['keys']['master_key'] ?? 'default_key';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($secret, 'aes-256-cbc', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * فك تشفير السر
     */
    private function decryptSecret($encrypted) {
        $key = $this->config['keys']['master_key'] ?? 'default_key';
        $data = base64_decode($encrypted);
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
    
    /**
     * إنشاء شهادة SSL ذاتية التوقيع
     */
    public function generateSelfSignedCertificate($dn, $days = 365) {
        $keyPair = $this->generateRSAKeyPair(2048);
        
        $csr = openssl_csr_new($dn, $keyPair['private']);
        $cert = openssl_csr_sign($csr, null, $keyPair['private'], $days);
        
        openssl_x509_export($cert, $certOutput);
        
        return [
            'certificate' => $certOutput,
            'private_key' => $keyPair['private']
        ];
    }
    
    /**
     * تدوير المفاتيح
     */
    public function rotateKeys($keyName) {
        // إنشاء مفتاح جديد
        $newKey = $this->generateSecretKey();
        
        // حفظ المفتاح الجديد
        $this->saveSecret($keyName . '_new', $newKey);
        
        // تسجيل عملية التدوير
        $this->logger->log('key', "Key rotation initiated for: $keyName");
        
        return true;
    }
    
    /**
     * حذف مفتاح قديم
     */
    public function deleteKey($name) {
        $paths = [
            $this->keysPath . 'private/' . $name . '*.key',
            $this->keysPath . 'public/' . $name . '*.pub',
            $this->keysPath . 'secrets/' . $name . '*.secret'
        ];
        
        foreach ($paths as $pattern) {
            foreach (glob($pattern) as $file) {
                unlink($file);
                $this->logger->log('key', "Key deleted: " . basename($file));
            }
        }
        
        return true;
    }
}
?>