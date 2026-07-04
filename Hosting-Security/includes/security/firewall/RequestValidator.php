<?php
/**
 * مدقق الطلبات
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class RequestValidator {
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
    }
    
    /**
     * التحقق من صحة الطلب
     */
    public function validate() {
        $this->checkMethod();
        $this->checkHeaders();
        $this->checkContentType();
        $this->checkSize();
        $this->checkEncoding();
    }
    
    /**
     * فحص طريقة الطلب
     */
    private function checkMethod() {
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            $this->reject('طريقة طلب غير مسموحة: ' . $method);
        }
    }
    
    /**
     * فحص الرؤوس
     */
    private function checkHeaders() {
        $requiredHeaders = ['Host'];
        
        foreach ($requiredHeaders as $header) {
            if (!isset($_SERVER['HTTP_' . strtoupper($header)])) {
                $this->reject('الرأس مطلوب: ' . $header);
            }
        }
        
        // فحص طول الرأس
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0 && strlen($value) > 4096) {
                $this->reject('طول الرأس كبير جداً');
            }
        }
    }
    
    /**
     * فحص نوع المحتوى
     */
    private function checkContentType() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            $allowedTypes = [
                'application/x-www-form-urlencoded',
                'multipart/form-data',
                'application/json',
                'application/xml'
            ];
            
            $valid = false;
            foreach ($allowedTypes as $type) {
                if (strpos($contentType, $type) === 0) {
                    $valid = true;
                    break;
                }
            }
            
            if (!$valid && !empty($contentType)) {
                $this->reject('نوع محتوى غير مدعوم: ' . $contentType);
            }
        }
    }
    
    /**
     * فحص حجم الطلب
     */
    private function checkSize() {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($contentLength > $maxSize) {
            $this->reject('حجم الطلب كبير جداً: ' . $contentLength);
        }
    }
    
    /**
     * فحص الترميز
     */
    private function checkEncoding() {
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        $allowedEncodings = ['gzip', 'deflate', 'br'];
        
        // لا نمنع، فقط نسجل
        foreach (explode(',', $acceptEncoding) as $encoding) {
            $encoding = trim($encoding);
            if (!empty($encoding) && !in_array($encoding, $allowedEncodings)) {
                $this->logger->log('warning', 'ترميز غير معتاد: ' . $encoding);
            }
        }
    }
    
    /**
     * رفض الطلب
     */
    private function reject($reason) {
        $this->logger->logThreat('رفض طلب - ' . $reason, 'request_validation', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uri' => $_SERVER['REQUEST_URI']
        ]);
        
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request', 'reason' => $reason]));
    }
    
    /**
     * تنظيف المدخلات
     */
    public function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * التحقق من صحة البريد
     */
    public function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->reject('بريد إلكتروني غير صالح');
        }
        return $email;
    }
    
    /**
     * التحقق من صحة الرابط
     */
    public function validateURL($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->reject('رابط غير صالح');
        }
        return $url;
    }
    
    /**
     * التحقق من صحة الرقم
     */
    public function validateNumber($number, $min = null, $max = null) {
        if (!is_numeric($number)) {
            $this->reject('قيمة رقمية مطلوبة');
        }
        
        if ($min !== null && $number < $min) {
            $this->reject('القيمة أقل من الحد الأدنى');
        }
        
        if ($max !== null && $number > $max) {
            $this->reject('القيمة أكبر من الحد الأقصى');
        }
        
        return $number;
    }
}
?>