<?php
/**
 * مدقق الإدخالات
 * Input Validator
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class InputValidator {
    
    private $errors = [];
    private $data = [];
    
    /**
     * التحقق من مجموعة قواعد
     */
    public function validate($data, $rules) {
        $this->data = $data;
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * تطبيق قاعدة معينة
     */
    private function applyRule($field, $value, $rule) {
        // قواعد مطلوبة
        if ($rule === 'required' && empty($value) && $value !== '0') {
            $this->errors[$field][] = 'هذا الحقل مطلوب';
            return;
        }
        
        // تجاهل التحقق إذا كان الحقل فارغاً وغير مطلوب
        if (empty($value) && $value !== '0') {
            return;
        }
        
        // قواعد النوع
        switch ($rule) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = 'البريد الإلكتروني غير صالح';
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field][] = 'الرابط غير صالح';
                }
                break;
                
            case 'ip':
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->errors[$field][] = 'عنوان IP غير صالح';
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->errors[$field][] = 'يجب أن يكون رقماً';
                }
                break;
                
            case 'int':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->errors[$field][] = 'يجب أن يكون عدداً صحيحاً';
                }
                break;
                
            case 'float':
                if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                    $this->errors[$field][] = 'يجب أن يكون عدداً عشرياً';
                }
                break;
                
            case 'boolean':
                if (!in_array($value, [true, false, 'true', 'false', 1, 0, '1', '0'], true)) {
                    $this->errors[$field][] = 'يجب أن تكون قيمة منطقية';
                }
                break;
                
            case 'alpha':
                if (!preg_match('/^[a-zA-Z]+$/', $value)) {
                    $this->errors[$field][] = 'يجب أن يحتوي على حروف فقط';
                }
                break;
                
            case 'alphanum':
                if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    $this->errors[$field][] = 'يجب أن يحتوي على حروف وأرقام فقط';
                }
                break;
        }
        
        // قواعد مخصصة
        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->errors[$field][] = "يجب أن يكون على الأقل $min حروف";
            }
        }
        
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->errors[$field][] = "يجب أن لا يتجاوز $max حرف";
            }
        }
        
        if (strpos($rule, 'min_value:') === 0) {
            $min = (int) substr($rule, 10);
            if ((float)$value < $min) {
                $this->errors[$field][] = "يجب أن تكون القيمة أكبر من أو تساوي $min";
            }
        }
        
        if (strpos($rule, 'max_value:') === 0) {
            $max = (int) substr($rule, 10);
            if ((float)$value > $max) {
                $this->errors[$field][] = "يجب أن تكون القيمة أقل من أو تساوي $max";
            }
        }
        
        if (strpos($rule, 'in:') === 0) {
            $options = explode(',', substr($rule, 3));
            if (!in_array($value, $options)) {
                $this->errors[$field][] = 'القيمة غير مقبولة';
            }
        }
        
        if (strpos($rule, 'regex:') === 0) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, $value)) {
                $this->errors[$field][] = 'القيمة لا تطابق النمط المطلوب';
            }
        }
        
        if (strpos($rule, 'equals:') === 0) {
            $otherField = substr($rule, 7);
            if ($value !== ($this->data[$otherField] ?? null)) {
                $this->errors[$field][] = 'القيم غير متطابقة';
            }
        }
    }
    
    /**
     * الحصول على الأخطاء
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * الحصول على أول خطأ
     */
    public function getFirstError($field = null) {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $errors) {
            return $errors[0] ?? null;
        }
        
        return null;
    }
    
    /**
     * التحقق من وجود أخطاء
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
?>