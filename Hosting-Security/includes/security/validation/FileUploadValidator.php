<?php
/**
 * فحص الملفات المرفوعة
 * File Upload Validator
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class FileUploadValidator {
    
    private $errors = [];
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/security-config.php';
    }
    
    /**
     * فحص الملف المرفوع
     */
    public function validate($file, $options = []) {
        $this->errors = [];
        
        // التحقق من وجود الملف
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[] = 'لم يتم رفع أي ملف';
            return false;
        }
        
        // التحقق من أخطاء الرفع
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // التحقق من الحجم
        $maxSize = $options['max_size'] ?? $this->config['uploads']['max_file_size'];
        if ($file['size'] > $maxSize) {
            $this->errors[] = 'حجم الملف أكبر من المسموح به';
            return false;
        }
        
        // التحقق من الامتداد
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $options['allowed'] ?? $this->config['uploads']['allowed_extensions'];
        $blocked = $options['blocked'] ?? $this->config['uploads']['blocked_extensions'];
        
        if (!in_array($extension, $allowed)) {
            $this->errors[] = 'نوع الملف غير مسموح به';
            return false;
        }
        
        if (in_array($extension, $blocked)) {
            $this->errors[] = 'نوع الملف ممنوع';
            return false;
        }
        
        // التحقق من MIME type الحقيقي
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = $this->getAllowedMimeTypes($allowed);
        if (!in_array($mime, $allowedMimes)) {
            $this->errors[] = 'نوع الملف لا يتطابق مع الامتداد';
            return false;
        }
        
        // فحص الفيروسات إذا كان مفعلاً
        if ($this->config['uploads']['virus_scan']) {
            if (!$this->scanForViruses($file['tmp_name'])) {
                $this->errors[] = 'الملف يحتوي على فيروس أو برمجية خبيثة';
                return false;
            }
        }
        
        // فحص محتوى الملفات النصية
        if (in_array($extension, ['php', 'php3', 'php4', 'php5', 'phtml', 'js', 'html', 'htm'])) {
            if ($this->containsMaliciousCode($file['tmp_name'])) {
                $this->errors[] = 'الملف يحتوي على كود خبيث';
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * الحصول على رسالة خطأ الرفع
     */
    private function getUploadErrorMessage($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم الملف أكبر من المسموح به';
            case UPLOAD_ERR_PARTIAL:
                return 'تم رفع جزء فقط من الملف';
            case UPLOAD_ERR_NO_FILE:
                return 'لم يتم رفع أي ملف';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'مجلد مؤقت غير موجود';
            case UPLOAD_ERR_CANT_WRITE:
                return 'فشل في كتابة الملف';
            case UPLOAD_ERR_EXTENSION:
                return 'امتداد الملف غير مسموح به';
            default:
                return 'خطأ غير معروف في رفع الملف';
        }
    }
    
    /**
     * الحصول على MIME types المسموحة
     */
    private function getAllowedMimeTypes($extensions) {
        $mimes = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip'],
        ];
        
        $allowed = [];
        foreach ($extensions as $ext) {
            if (isset($mimes[$ext])) {
                $allowed = array_merge($allowed, $mimes[$ext]);
            }
        }
        
        return array_unique($allowed);
    }
    
    /**
     * فحص الفيروسات (باستخدام ClamAV أو بسيط)
     */
    private function scanForViruses($filepath) {
        // محاولة استخدام ClamAV إذا كان مثبتاً
        if (function_exists('exec')) {
            $output = [];
            $returnVar = 0;
            exec("clamscan --no-summary " . escapeshellarg($filepath) . " 2>&1", $output, $returnVar);
            
            // إذا كان ClamAV مثبتاً واستجاب
            if ($returnVar === 0 && strpos(implode(' ', $output), 'OK') !== false) {
                return true;
            }
            if ($returnVar === 1) {
                return false; // فيروس مكتشف
            }
        }
        
        // فحص أساسي للملفات المشبوهة
        $content = file_get_contents($filepath);
        $suspicious = [
            'eval(',
            'base64_decode(',
            'system(',
            'exec(',
            'shell_exec(',
            'passthru(',
            'popen(',
            'proc_open(',
            'phpinfo(',
            'chmod(',
            'chown(',
            'unlink(',
            'rmdir(',
            'fopen(',
            'fwrite(',
            'file_put_contents(',
            'assert(',
            'create_function(',
            'preg_replace.*\/e',
        ];
        
        foreach ($suspicious as $pattern) {
            if (stripos($content, $pattern) !== false) {
                SecurityLogger::logThreat('Suspicious code detected in uploaded file', 'malware', [
                    'file' => basename($filepath),
                    'pattern' => $pattern
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * فحص وجود كود خبيث في الملفات النصية
     */
    private function containsMaliciousCode($filepath) {
        $content = file_get_contents($filepath);
        
        $malicious = [
            '/\<\?php/i',
            '/\<\?=/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/popen\s*\(/i',
            '/proc_open\s*\(/i',
            '/curl_exec/i',
            '/file_get_contents\s*\(.*http/i',
            '/fopen\s*\(.*http/i',
            '/allow_url_fopen/i',
            '/allow_url_include/i',
            '/create_function/i',
            '/assert\s*\(/i',
            '/preg_replace.*\/e/i',
            '/GLOBALS\[/i',
            '/_REQUEST\[/i',
            '/_GET\[/i',
            '/_POST\[/i',
            '/_COOKIE\[/i',
            '/_FILES\[/i',
            '/_SERVER\[/i',
            '/_ENV\[/i',
            '/`.*`/',
        ];
        
        foreach ($malicious as $pattern) {
            if (preg_match($pattern, $content)) {
                SecurityLogger::logThreat('Malicious code detected in file', 'malware', [
                    'file' => basename($filepath),
                    'pattern' => $pattern
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * الحصول على الأخطاء
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * حفظ الملف في مكان آمن
     */
    public function saveUploadedFile($file, $destination, $quarantine = false) {
        if ($quarantine) {
            $destination = __DIR__ . '/../../../uploads/quarantine/' . $destination;
        } else {
            $destination = __DIR__ . '/../../../uploads/clean/' . $destination;
        }
        
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            chmod($destination, 0644);
            SecurityLogger::logUpload($file['name'], 'saved', [
                'destination' => $destination,
                'quarantine' => $quarantine
            ]);
            return $destination;
        }
        
        SecurityLogger::logUpload($file['name'], 'failed', ['error' => 'Failed to move file']);
        return false;
    }
}
?>