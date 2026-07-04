<?php
/**
 * مدير الجدار الناري الرئيسي
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class FirewallManager {
    private $db;
    private $logger;
    private $ipFilter;
    private $rateLimiter;
    
    public function __construct($db = null) {
        $this->db = $db;
        $this->logger = new SecurityLogger();
        $this->ipFilter = new IPFilter();
        $this->rateLimiter = new RateLimiter();
    }
    
    /**
     * فحص الطلب الحالي
     */
    public function inspectRequest() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        // 1. فحص IP في القائمة السوداء
        if ($this->ipFilter->isBlacklisted($ip)) {
            $this->blockRequest('IP محظور: ' . $ip);
            return false;
        }
        
        // 2. فحص معدل الطلبات
        if (!$this->rateLimiter->check($ip)) {
            $this->blockRequest('تجاوز معدل الطلبات: ' . $ip);
            return false;
        }
        
        // 3. فحص الـ User Agent
        if ($this->isBadUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->blockRequest('User Agent محظور');
            return false;
        }
        
        // 4. فحص محاولات الاختراق
        if ($this->detectAttack($uri, $_REQUEST)) {
            $this->blockRequest('محاولة اختراق detected');
            return false;
        }
        
        return true;
    }
    
    /**
     * حظر الطلب
     */
    private function blockRequest($reason) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $this->logger->logThreat('طلب محظور', 'firewall', [
            'ip' => $ip,
            'reason' => $reason,
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD']
        ]);
        
        // إضافة IP إلى القائمة السوداء المؤقتة
        $this->ipFilter->tempBan($ip, 3600); // ساعة
        
        http_response_code(403);
        die(json_encode(['error' => 'Access denied', 'reason' => $reason]));
    }
    
    /**
     * كشف محاولات الاختراق
     */
    private function detectAttack($uri, $data) {
        $patterns = [
            '/\.\.\/|\.\.\\\\/' => 'Path Traversal',
            '/<script.*>.*<\/script>/i' => 'XSS',
            '/union.*select/i' => 'SQL Injection',
            '/exec.*\(/i' => 'Command Injection',
            '/eval.*\(/i' => 'Code Injection',
            '/base64_decode.*\(/i' => 'Base64 Injection',
            '/wp-config|config\.php/i' => 'Config Access'
        ];
        
        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $uri . ' ' . json_encode($data))) {
                $this->logger->logThreat('هجوم ' . $type, 'attack_detected', [
                    'pattern' => $pattern,
                    'uri' => $uri
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * فحص User Agent
     */
    private function isBadUserAgent($ua) {
        $badAgents = [
            'sqlmap',
            'nmap',
            'nikto',
            'wpscan',
            'hydra',
            'medusa',
            'openvas',
            'nessus',
            'burp',
            'zap',
            'python-requests',
            'go-http-client',
            'scrapy'
        ];
        
        foreach ($badAgents as $bad) {
            if (stripos($ua, $bad) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * تطبيق قواعد WAF
     */
    public function applyWAFRules() {
        $waf = new WAF();
        return $waf->process();
    }
}
?>