<?php
/**
 * كشف التهديدات
 * Threat Detector
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class ThreatDetector {
    
    private $logger;
    private $rules = [];
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->loadRules();
    }
    
    /**
     * تحميل قواعد الكشف
     */
    private function loadRules() {
        $rulesFile = __DIR__ . '/../config/threat_rules.json';
        
        if (file_exists($rulesFile)) {
            $this->rules = json_decode(file_get_contents($rulesFile), true);
        } else {
            $this->rules = $this->getDefaultRules();
        }
    }
    
    /**
     * القواعد الافتراضية
     */
    private function getDefaultRules() {
        return [
            'sql_injection' => [
                'patterns' => [
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
                ],
                'score' => 10
            ],
            'xss' => [
                'patterns' => [
                    '/<script.*>.*<\/script>/is',
                    '/javascript:/i',
                    '/onerror\s*=/i',
                    '/onload\s*=/i',
                    '/onclick\s*=/i',
                    '/onmouseover\s*=/i',
                    '/expression\s*\(/i',
                    '/vbscript:/i',
                    '/data:\s*text\/html/i',
                ],
                'score' => 8
            ],
            'path_traversal' => [
                'patterns' => [
                    '/\.\.\//',
                    '/\.\.\\\\/',
                    '/%2e%2e%2f/i',
                    '/%2e%2e\\\\/i',
                    '/%252e%252e%252f/i',
                ],
                'score' => 7
            ],
            'command_injection' => [
                'patterns' => [
                    '/;\s*(ls|dir|cat|type|wget|curl|ping|nslookup)/i',
                    '/\|\s*(ls|dir|cat|type|wget|curl|ping|nslookup)/i',
                    '/`.*`/',
                    '/\$\{.*\}/',
                    '/\$\(.*\)/',
                ],
                'score' => 10
            ]
        ];
    }
    
    /**
     * فحص طلب للكشف عن تهديدات
     */
    public function scanRequest() {
        $threats = [];
        $totalScore = 0;
        
        // فحص GET parameters
        foreach ($_GET as $key => $value) {
            $result = $this->scanValue($key, $value);
            if ($result['threats']) {
                $threats = array_merge($threats, $result['threats']);
                $totalScore += $result['score'];
            }
        }
        
        // فحص POST parameters
        foreach ($_POST as $key => $value) {
            $result = $this->scanValue($key, $value);
            if ($result['threats']) {
                $threats = array_merge($threats, $result['threats']);
                $totalScore += $result['score'];
            }
        }
        
        // فحص COOKIE
        foreach ($_COOKIE as $key => $value) {
            $result = $this->scanValue($key, $value);
            if ($result['threats']) {
                $threats = array_merge($threats, $result['threats']);
                $totalScore += $result['score'];
            }
        }
        
        // فحص HEADERS
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            $result = $this->scanValue($key, $value);
            if ($result['threats']) {
                $threats = array_merge($threats, $result['threats']);
                $totalScore += $result['score'];
            }
        }
        
        if (!empty($threats)) {
            $this->logger->logThreat('Request contains threats', 'multiple', [
                'threats' => $threats,
                'score' => $totalScore,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            // إذا كان التهديد خطيراً، قم بحظر IP
            if ($totalScore > 20) {
                $this->blockIP($_SERVER['REMOTE_ADDR'], 'High threat score: ' . $totalScore);
            }
        }
        
        return [
            'has_threats' => !empty($threats),
            'threats' => $threats,
            'score' => $totalScore
        ];
    }
    
    /**
     * فحص قيمة معينة
     */
    private function scanValue($key, $value) {
        $threats = [];
        $score = 0;
        
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $result = $this->scanValue($key . "[$k]", $v);
                $threats = array_merge($threats, $result['threats']);
                $score += $result['score'];
            }
            return ['threats' => $threats, 'score' => $score];
        }
        
        foreach ($this->rules as $type => $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match($pattern, $value)) {
                    $threats[] = [
                        'type' => $type,
                        'key' => $key,
                        'pattern' => $pattern,
                        'value' => substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '')
                    ];
                    $score += $rule['score'];
                    break;
                }
            }
        }
        
        return ['threats' => $threats, 'score' => $score];
    }
    
    /**
     * حظر IP
     */
    private function blockIP($ip, $reason) {
        $blocked = [];
        $blockFile = __DIR__ . '/../../../config/blocked_ips.json';
        
        if (file_exists($blockFile)) {
            $blocked = json_decode(file_get_contents($blockFile), true);
        }
        
        $blocked[$ip] = [
            'reason' => $reason,
            'blocked_at' => date('Y-m-d H:i:s'),
            'expires' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];
        
        file_put_contents($blockFile, json_encode($blocked, JSON_PRETTY_PRINT));
    }
    
    /**
     * فحص الملفات المشبوهة
     */
    public function scanFileForThreats($filepath) {
        if (!file_exists($filepath)) {
            return ['error' => 'File not found'];
        }
        
        $content = file_get_contents($filepath);
        $threats = [];
        
        foreach ($this->rules as $type => $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match($pattern, $content)) {
                    $threats[] = [
                        'type' => $type,
                        'pattern' => $pattern
                    ];
                }
            }
        }
        
        if (!empty($threats)) {
            $this->logger->logThreat('File contains threats', 'file_scan', [
                'file' => basename($filepath),
                'threats' => $threats
            ]);
        }
        
        return [
            'has_threats' => !empty($threats),
            'threats' => $threats
        ];
    }
    
    /**
     * إضافة قاعدة مخصصة
     */
    public function addRule($type, $pattern, $score) {
        if (!isset($this->rules[$type])) {
            $this->rules[$type] = [
                'patterns' => [],
                'score' => $score
            ];
        }
        
        $this->rules[$type]['patterns'][] = $pattern;
        
        // حفظ القواعد
        $rulesFile = __DIR__ . '/../config/threat_rules.json';
        file_put_contents($rulesFile, json_encode($this->rules, JSON_PRETTY_PRINT));
    }
}
?>