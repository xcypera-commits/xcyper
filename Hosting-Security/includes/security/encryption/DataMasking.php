<?php
/**
 * إخفاء البيانات الحساسة
 * Data Masking
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class DataMasking {
    
    private $sensitiveFields;
    
    public function __construct() {
        $config = require SECURITY_PATH . 'config/encryption-config.php';
        $this->sensitiveFields = $config['sensitive_fields'] ?? [
            'password', 'credit_card', 'ssn', 'bank_account', 'phone', 'email'
        ];
    }
    
    /**
     * إخفاء بيانات حساسة في مصفوفة
     */
    public function maskArray($data, $maskWith = '***') {
        if (!is_array($data)) {
            return $this->maskValue($data, $maskWith);
        }
        
        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $data[$key] = $this->maskValue($value, $maskWith);
            } elseif (is_array($value)) {
                $data[$key] = $this->maskArray($value, $maskWith);
            }
        }
        
        return $data;
    }
    
    /**
     * التحقق من أن الحقل حساس
     */
    private function isSensitiveField($fieldName) {
        $fieldName = strtolower($fieldName);
        
        foreach ($this->sensitiveFields as $sensitive) {
            if (strpos($fieldName, $sensitive) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * إخفاء قيمة
     */
    private function maskValue($value, $maskWith = '***') {
        if (is_null($value)) {
            return null;
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return $this->maskNumeric($value, $maskWith);
        }
        
        return $this->maskString($value, $maskWith);
    }
    
    /**
     * إخفاء نص
     */
    private function maskString($string, $maskWith = '***') {
        $length = strlen($string);
        
        if ($length <= 4) {
            return $maskWith;
        }
        
        $visible = ceil($length * 0.25); // 25% ظاهر
        $masked = $length - $visible;
        
        return substr($string, 0, $visible) . str_repeat('*', $masked);
    }
    
    /**
     * إخفاء رقم
     */
    private function maskNumeric($number, $maskWith = '***') {
        $strNum = (string)$number;
        return $this->maskString($strNum, $maskWith);
    }
    
    /**
     * إخفاء بريد إلكتروني
     */
    public function maskEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->maskString($email);
        }
        
        list($local, $domain) = explode('@', $email);
        
        $maskedLocal = $this->maskString($local, '***');
        $maskedDomain = $this->maskString($domain, '***');
        
        return $maskedLocal . '@' . $maskedDomain;
    }
    
    /**
     * إخفاء رقم هاتف
     */
    public function maskPhone($phone) {
        // إزالة الرموز غير الرقمية
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        
        if (strlen($clean) <= 4) {
            return '****';
        }
        
        $visible = 4; // آخر 4 أرقام
        $masked = strlen($clean) - $visible;
        
        return str_repeat('*', $masked) . substr($clean, -$visible);
    }
    
    /**
     * إخفاء رقم بطاقة ائتمان
     */
    public function maskCreditCard($cardNumber) {
        $clean = preg_replace('/[^0-9]/', '', $cardNumber);
        
        if (strlen($clean) <= 4) {
            return '****';
        }
        
        // إظهار آخر 4 أرقام فقط
        return '****-****-****-' . substr($clean, -4);
    }
    
    /**
     * إخفاء JSON
     */
    public function maskJson($json) {
        $data = json_decode($json, true);
        if (!$data) {
            return $json;
        }
        
        $masked = $this->maskArray($data);
        return json_encode($masked);
    }
    
    /**
     * إخفاء سجلات متعددة
     */
    public function maskCollection($collection) {
        return array_map([$this, 'maskArray'], $collection);
    }
    
    /**
     * إخفاء بيانات للـ API
     */
    public function maskForAPI($data, $userRole = 'user') {
        // للمديرين: إظهار بيانات أكثر
        if ($userRole === 'admin' || $userRole === 'manager') {
            return $data;
        }
        
        return $this->maskArray($data);
    }
    
    /**
     * إخفاء السجلات (لوج)
     */
    public function maskLogData($data) {
        return $this->maskArray($data, '[REDACTED]');
    }
    
    /**
     * إخفاء مع الحفاظ على التنسيق
     */
    public function maskPreserveFormat($value) {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }
        
        if (preg_match('/^[0-9+\-\s]+$/', $value)) {
            return $this->maskPhone($value);
        }
        
        return $this->maskValue($value);
    }
}
?>