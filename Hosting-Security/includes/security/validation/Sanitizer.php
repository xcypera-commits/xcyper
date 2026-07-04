<?php
class Sanitizer {
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    public static function sanitizeFilename($filename) {
        $filename = preg_replace('/[^\w\.\-]/', '_', $filename);
        $filename = basename($filename); // Remove path
        return $filename;
    }
    
    public static function sanitizeHTML($html) {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}

?>




<?php
namespace HostingSystem\Security\Validation;

class Sanitizer {
    /**
     * تنظيف النص من الترميزات الخطرة
     */
    public static function sanitizeString(string $input, bool $allowHTML = false): string {
        if ($allowHTML) {
            // تنظيف HTML مع السماح ببعض الوسوم الآمنة
            $allowedTags = '<p><br><strong><em><u><ol><ul><li><a><img>';
            $cleaned = strip_tags($input, $allowedTags);
        } else {
            // إزالة جميع الوسوم
            $cleaned = strip_tags($input);
        }
        
        // إزالة الترميز الخاص
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // إزالة المسافات الزائدة
        $cleaned = trim($cleaned);
        
        // إزالة الأحرف غير المرئية
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $cleaned);
        
        return $cleaned;
    }
    
    /**
     * تنظيف المدخلات من مصفوفة
     */
    public static function sanitizeArray(array $data, array $rules = []): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $rule = $rules[$key] ?? 'string';
                $sanitized[$key] = self::sanitizeByType($value, $rule);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * تنظيف حسب النوع
     */
    private static function sanitizeByType($value, string $type) {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
                
            case 'int':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'string':
                return self::sanitizeString($value);
                
            case 'html':
                return self::sanitizeString($value, true);
                
            case 'sql':
                // تنظيف خاص للاستعلامات SQL
                return self::sanitizeForSQL($value);
                
            default:
                return self::sanitizeString($value);
        }
    }
    
    /**
     * تنظيف نص للاستخدام في SQL
     */
    private static function sanitizeForSQL(string $input): string {
        // إزالة الأحرف الخطرة في SQL
        $dangerous = ["'", '"', ';', '--', '/*', '*/', 'xp_', 'sp_'];
        $cleaned = str_replace($dangerous, '', $input);
        
        // إزالة المسافات المتعددة
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        return $cleaned;
    }
    
    /**
     * تنظيف ملفات الرفع
     */
    public static function sanitizeUploadedFile(array $file): array {
        $sanitized = $file;
        
        // تنظيف اسم الملف
        $sanitized['name'] = self::sanitizeFilename($file['name']);
        
        // التحقق من MIME type
        $sanitized['mime'] = mime_content_type($file['tmp_name']);
        
        // إزالة metadata الخطرة من الصور
        if (strpos($sanitized['mime'], 'image/') === 0) {
            $sanitized = self::sanitizeImage($sanitized);
        }
        
        return $sanitized;
    }
    
    /**
     * تنظيف اسم الملف
     */
    private static function sanitizeFilename(string $filename): string {
        // إزالة المسارات
        $filename = basename($filename);
        
        // إزالة الأحرار الخطرة
        $filename = preg_replace('/[^\w\-\. ]/', '_', $filename);
        
        // تقصير إذا كان طويلاً جداً
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = substr($name, 0, 255 - strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }
}