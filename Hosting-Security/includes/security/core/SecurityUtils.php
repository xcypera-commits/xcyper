<?php
/**
 * دوال الأمان المساعدة
 * Security Utilities
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityUtils {
    
    /**
     * الحصول على IP حقيقي للعميل
     */
    public static function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // التحقق من وجود proxy
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                break;
            }
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * إنشاء رمز عشوائي آمن
     */
    public static function generateRandomString($length = 32, $type = 'alnum') {
        switch ($type) {
            case 'alnum':
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numeric':
                $characters = '0123456789';
                break;
            case 'hex':
                $characters = '0123456789abcdef';
                break;
            default:
                $characters = $type;
        }
        
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * تشفير بيانات حساسة
     */
    public static function encryptData($data, $key = null) {
        if ($key === null) {
            $config = require __DIR__ . '/../config/encryption-config.php';
            $key = $config['keys']['master_key'];
        }
        
        $method = 'aes-256-gcm';
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * فك تشفير بيانات
     */
    public static function decryptData($encryptedData, $key = null) {
        if ($key === null) {
            $config = require __DIR__ . '/../config/encryption-config.php';
            $key = $config['keys']['master_key'];
        }
        
        $data = base64_decode($encryptedData);
        
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * حساب بصمة الملف
     */
    public static function calculateFileHash($filepath, $algorithm = 'sha256') {
        if (!file_exists($filepath)) {
            return false;
        }
        
        return hash_file($algorithm, $filepath);
    }
    
    /**
     * التحقق من نوع الملف الحقيقي
     */
    public static function getRealMimeType($filepath) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        return $mime;
    }
    
    /**
     * الحصول على معلومات النظام
     */
    public static function getSystemInfo() {
        return [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disabled_functions' => explode(',', ini_get('disable_functions')),
        ];
    }
    
    /**
     * التحقق من وجود ثغرات أمنية في PHP
     */
    public static function checkPHPVulnerabilities() {
        $issues = [];
        
        // التحقق من إصدار PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $issues[] = 'PHP version is outdated. Please upgrade to 7.4 or higher.';
        }
        
        // التحقق من الإعدادات الخطرة
        $dangerous_settings = [
            'allow_url_fopen' => '1',
            'allow_url_include' => '1',
            'display_errors' => '1',
            'expose_php' => '1',
        ];
        
        foreach ($dangerous_settings as $setting => $value) {
            if (ini_get($setting) == $value) {
                $issues[] = "Dangerous setting enabled: $setting";
            }
        }
        
        return $issues;
    }
    
    /**
     * تسريع وتأمين الدوال
     */
    public static function secureShellExec($command, $timeout = 30) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        
        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return false;
        }
        
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        
        $output = '';
        $errors = '';
        $startTime = time();
        
        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;
            
            if (stream_select($read, $write, $except, 1) > 0) {
                foreach ($read as $stream) {
                    if ($stream === $pipes[1]) {
                        $output .= stream_get_contents($stream);
                    } elseif ($stream === $pipes[2]) {
                        $errors .= stream_get_contents($stream);
                    }
                }
            }
            
            if (time() - $startTime > $timeout) {
                proc_terminate($process);
                return ['error' => 'Timeout', 'output' => $output];
            }
            
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
        }
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
        return [
            'output' => $output,
            'errors' => $errors,
            'exit_code' => $status['exitcode'] ?? -1,
        ];
    }
}
?>