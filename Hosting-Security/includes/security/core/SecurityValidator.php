<?php
/**
 * المدقق الأمني العام
 * Security Validator
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityValidator {
    
    /**
     * التحقق من الإدخال
     */
    public static function validateInput($input, $type = 'string', $options = []) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;
            
            case 'ip':
                return filter_var($input, FILTER_VALIDATE_IP) !== false;
            
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT) !== false;
            
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
            
            case 'boolean':
                return is_bool($input) || in_array($input, ['true', 'false', '1', '0', 1, 0], true);
            
            case 'username':
                return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $input);
            
            case 'password':
                $min = $options['min'] ?? 8;
                $require_upper = $options['require_upper'] ?? true;
                $require_lower = $options['require_lower'] ?? true;
                $require_number = $options['require_number'] ?? true;
                $require_special = $options['require_special'] ?? true;
                
                if (strlen($input) < $min) return false;
                if ($require_upper && !preg_match('/[A-Z]/', $input)) return false;
                if ($require_lower && !preg_match('/[a-z]/', $input)) return false;
                if ($require_number && !preg_match('/[0-9]/', $input)) return false;
                if ($require_special && !preg_match('/[^a-zA-Z0-9]/', $input)) return false;
                
                return true;
            
            case 'filename':
                return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $input) && 
                       !preg_match('/\.\./', $input) && 
                       !preg_match('/^\\./', $input);
            
            case 'path':
                return preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $input) && 
                       !preg_match('/\.\./', $input);
            
            default:
                return is_string($input) && strlen($input) <= ($options['max_length'] ?? 1000);
        }
    }
    
    /**
     * تنظيف الإدخال
     */
    public static function sanitize($input, $type = 'string') {
        if ($input === null) {
            return '';
        }
        
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            
            case 'int':
                return (int) $input;
            
            case 'float':
                return (float) $input;
            
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $input);
            
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * التحقق من CSRF token
     */
    public static function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * إنشاء CSRF token
     */
    public static function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * التحقق من صحة التاريخ
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * التحقق من صحة الوقت
     */
    public static function validateTime($time, $format = 'H:i:s') {
        $d = DateTime::createFromFormat($format, $time);
        return $d && $d->format($format) === $time;
    }
    
    /**
     * التحقق من صحة رقم الهاتف
     */
    public static function validatePhone($phone, $country = 'SA') {
        // إزالة المسافات والرموز
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        switch ($country) {
            case 'SA': // السعودية
                return preg_match('/^(00966|\+966|966|05|5)[0-9]{8,9}$/', $phone);
            
            case 'EG': // مصر
                return preg_match('/^(0020|\+20|20|01)[0-9]{9,10}$/', $phone);
            
            case 'AE': // الإمارات
                return preg_match('/^(00971|\+971|971|05|5)[0-9]{8,9}$/', $phone);
            
            default:
                return preg_match('/^[0-9+]{8,15}$/', $phone);
        }
    }
    
    /**
     * التحقق من قوة كلمة المرور
     */
    public static function checkPasswordStrength($password) {
        $score = 0;
        $feedback = [];
        
        // الطول
        if (strlen($password) >= 8) $score += 1;
        if (strlen($password) >= 12) $score += 1;
        if (strlen($password) >= 16) $score += 1;
        
        // التنوع
        if (preg_match('/[a-z]/', $password)) $score += 1;
        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/[0-9]/', $password)) $score += 1;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 1;
        
        // عدم وجود أنماط متكررة
        if (!preg_match('/(.)\1{2,}/', $password)) $score += 1;
        
        // تقييم القوة
        if ($score <= 3) {
            $strength = 'weak';
            $feedback[] = 'كلمة المرور ضعيفة جداً';
        } elseif ($score <= 5) {
            $strength = 'medium';
            $feedback[] = 'كلمة المرور متوسطة';
        } elseif ($score <= 7) {
            $strength = 'strong';
            $feedback[] = 'كلمة المرور قوية';
        } else {
            $strength = 'very_strong';
            $feedback[] = 'كلمة المرور قوية جداً';
        }
        
        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback
        ];
    }
}
?>