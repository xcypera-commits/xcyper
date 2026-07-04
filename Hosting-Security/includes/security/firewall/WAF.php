<?php
/**
 * Web Application Firewall
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class WAF {
    private $rules = [];
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->loadRules();
    }
    
    /**
     * معالجة الطلب
     */
    public function process() {
        $this->checkSQLInjection();
        $this->checkXSS();
        $this->checkPathTraversal();
        $this->checkCommandInjection();
        $this->checkFileInclusion();
        $this->checkCSRF();
    }
    
    /**
     * فحص SQL Injection
     */
    private function checkSQLInjection() {
        $patterns = [
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
            '/\w*((\%27)|(\'))\s*((\%6F)|o|(\%4F))((\%72)|r|(\%52))/ix',
            '/((\%27)|(\'))union/ix',
            '/exec(\s|\+)+(s|x)p\w+/ix'
        ];
        
        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->scanValue($val, $patterns, 'SQL Injection');
                }
            } else {
                $this->scanValue($value, $patterns, 'SQL Injection');
            }
        }
    }
    
    /**
     * فحص XSS
     */
    private function checkXSS() {
        $patterns = [
            '/(<[^>]+)on\w+\s*=[^>]*>/i',
            '/<script.*>.*<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/expression\s*\(/i',
            '/data:\s*text\/html/i',
            '/document\.(cookie|write|location)/i',
            '/alert\s*\(/i'
        ];
        
        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->scanValue($val, $patterns, 'XSS');
                }
            } else {
                $this->scanValue($value, $patterns, 'XSS');
            }
        }
    }
    
    /**
     * فحص Path Traversal
     */
    private function checkPathTraversal() {
        $patterns = [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/%2e%2e%2f/i',
            '/%2e%2e\\\/i',
            '/%252e%252e%252f/i',
            '/%c0%ae%c0%ae/i',
            '/%uff0e%uff0e%u2215/i'
        ];
        
        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->scanValue($val, $patterns, 'Path Traversal');
                }
            } else {
                $this->scanValue($value, $patterns, 'Path Traversal');
            }
        }
    }
    
    /**
     * فحص Command Injection
     */
    private function checkCommandInjection() {
        $patterns = [
            '/;\s*(ls|dir|cat|type|wget|curl|ping|nslookup)/i',
            '/\|\s*(ls|dir|cat|type|wget|curl|ping|nslookup)/i',
            '/`.*`/',
            '/\$\{.*\}/',
            '/\$\(.*\)/',
            '/&&/',
            '/\|\|/'
        ];
        
        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->scanValue($val, $patterns, 'Command Injection');
                }
            } else {
                $this->scanValue($value, $patterns, 'Command Injection');
            }
        }
    }
    
    /**
     * فحص File Inclusion
     */
    private function checkFileInclusion() {
        $patterns = [
            '/\.\.\/.*\.(php|asp|aspx|jsp|pl|cgi)/i',
            '/(http|https|ftp):\/\//i',
            '/\b(include|require|include_once|require_once)\s*\(/i'
        ];
        
        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->scanValue($val, $patterns, 'File Inclusion');
                }
            } else {
                $this->scanValue($value, $patterns, 'File Inclusion');
            }
        }
    }
    
    /**
     * فحص CSRF
     */
    private function checkCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->block('CSRF Token مطلوب أو غير صالح');
            }
        }
    }
    
    /**
     * فحص قيمة
     */
    private function scanValue($value, $patterns, $type) {
        if (empty($value) || !is_string($value)) {
            return;
        }
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $this->block("$type detected: " . substr($value, 0, 100));
            }
        }
    }
    
    /**
     * حظر الطلب
     */
    private function block($reason) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $this->logger->logThreat('WAF - ' . $reason, 'waf_block', [
            'ip' => $ip,
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD']
        ]);
        
        http_response_code(403);
        die(json_encode(['error' => 'Request blocked by WAF', 'reason' => $reason]));
    }
    
    /**
     * تحميل القواعد
     */
    private function loadRules() {
        $file = __DIR__ . '/../../../config/waf_rules.json';
        
        if (file_exists($file)) {
            $this->rules = json_decode(file_get_contents($file), true);
        }
    }
}
?>