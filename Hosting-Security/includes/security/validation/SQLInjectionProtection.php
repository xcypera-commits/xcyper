<?php
/**
 * الحماية من SQL Injection
 * SQL Injection Protection
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class SQLInjectionProtection {
    
    /**
     * تنظيف المدخلات لاستخدامها في SQL
     */
    public static function escape($input, $connection = null) {
        if (is_array($input)) {
            return array_map([self::class, 'escape'], $input);
        }
        
        if ($connection) {
            return mysqli_real_escape_string($connection, $input);
        }
        
        // fallback
        return addslashes($input);
    }
    
    /**
     * التحقق من وجود هجمات SQL Injection
     */
    public static function detectSQLInjection($input) {
        if (is_array($input)) {
            foreach ($input as $value) {
                if (self::detectSQLInjection($value)) {
                    return true;
                }
            }
            return false;
        }
        
        $patterns = [
            '/\bUNION\b.*\bSELECT\b/i',
            '/\bSELECT\b.*\bFROM\b/i',
            '/\bINSERT\b.*\bINTO\b/i',
            '/\bUPDATE\b.*\bSET\b/i',
            '/\bDELETE\b.*\bFROM\b/i',
            '/\bDROP\b.*\bTABLE\b/i',
            '/\bALTER\b.*\bTABLE\b/i',
            '/\bCREATE\b.*\bTABLE\b/i',
            '/\bEXEC\b.*\bXP_/i',
            '/\bINFORMATION_SCHEMA\b/i',
            '/\bWAITFOR\b.*\bDELAY\b/i',
            '/\bBENCHMARK\b.*\(/i',
            '/\bSLEEP\b.*\(/i',
            '/--/',
            '/;/',
            '/\bOR\b.*=.*\bOR\b/i',
            '/\bAND\b.*=.*\bAND\b/i',
            '/\'\s*OR\s*\'\s*=\s*\'\'/i',
            '/"\s*OR\s*"\s*=\s*""/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * بناء استعلام آمن باستخدام Prepared Statements
     */
    public static function prepareQuery($connection, $query, $params = []) {
        $stmt = mysqli_prepare($connection, $query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare query: ' . mysqli_error($connection));
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                $bindParams[] = $param;
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$bindParams);
        }
        
        return $stmt;
    }
    
    /**
     * تنفيذ استعلام آمن
     */
    public static function executeSecure($connection, $query, $params = []) {
        $stmt = self::prepareQuery($connection, $query, $params);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
    
    /**
     * الحصول على قيمة آمنة للإدراج في SQL
     */
    public static function getSafeValue($value, $connection = null) {
        if ($value === null) {
            return 'NULL';
        }
        
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        $escaped = self::escape($value, $connection);
        return "'$escaped'";
    }
    
    /**
     * التحقق من صحة اسم الجدول أو العمود
     */
    public static function validateIdentifier($identifier) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $identifier);
    }
}
?>