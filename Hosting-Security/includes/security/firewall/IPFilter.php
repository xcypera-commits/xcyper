<?php
/**
 * فلترة عناوين IP
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class IPFilter {
    private $blacklist = [];
    private $whitelist = [];
    private $tempBans = [];
    
    public function __construct() {
        $this->loadLists();
    }
    
    /**
     * التحقق من IP في القائمة السوداء
     */
    public function isBlacklisted($ip) {
        // فحص القائمة السوداء الدائمة
        if (in_array($ip, $this->blacklist)) {
            return true;
        }
        
        // فحص الحظر المؤقت
        if (isset($this->tempBans[$ip])) {
            if ($this->tempBans[$ip] > time()) {
                return true;
            } else {
                unset($this->tempBans[$ip]);
                $this->saveLists();
            }
        }
        
        // فحص النطاقات
        foreach ($this->blacklist as $blocked) {
            if (strpos($blocked, '/') !== false) {
                if ($this->ipInRange($ip, $blocked)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * التحقق من IP في القائمة البيضاء
     */
    public function isWhitelisted($ip) {
        return in_array($ip, $this->whitelist);
    }
    
    /**
     * إضافة IP للقائمة السوداء
     */
    public function addToBlacklist($ip, $permanent = true) {
        if ($permanent) {
            $this->blacklist[] = $ip;
        } else {
            $this->tempBans[$ip] = time() + 3600; // ساعة افتراضية
        }
        
        $this->saveLists();
        $this->logAction('blacklist_add', $ip);
    }
    
    /**
     * إضافة IP للقائمة البيضاء
     */
    public function addToWhitelist($ip) {
        $this->whitelist[] = $ip;
        $this->saveLists();
        $this->logAction('whitelist_add', $ip);
    }
    
    /**
     * حظر مؤقت
     */
    public function tempBan($ip, $duration = 3600) {
        $this->tempBans[$ip] = time() + $duration;
        $this->saveLists();
        $this->logAction('temp_ban', $ip, $duration);
    }
    
    /**
     * إزالة من القائمة السوداء
     */
    public function removeFromBlacklist($ip) {
        $this->blacklist = array_diff($this->blacklist, [$ip]);
        unset($this->tempBans[$ip]);
        $this->saveLists();
        $this->logAction('blacklist_remove', $ip);
    }
    
    /**
     * إزالة من القائمة البيضاء
     */
    public function removeFromWhitelist($ip) {
        $this->whitelist = array_diff($this->whitelist, [$ip]);
        $this->saveLists();
        $this->logAction('whitelist_remove', $ip);
    }
    
    /**
     * فحص إذا كان IP في نطاق معين
     */
    private function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - $mask)) - 1);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    /**
     * تحميل القوائم
     */
    private function loadLists() {
        $file = __DIR__ . '/../../../config/ip_lists.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $this->blacklist = $data['blacklist'] ?? [];
            $this->whitelist = $data['whitelist'] ?? [];
            $this->tempBans = $data['temp_bans'] ?? [];
        }
    }
    
    /**
     * حفظ القوائم
     */
    private function saveLists() {
        $file = __DIR__ . '/../../../config/ip_lists.json';
        
        $data = [
            'blacklist' => $this->blacklist,
            'whitelist' => $this->whitelist,
            'temp_bans' => $this->tempBans,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * تسجيل الإجراء
     */
    private function logAction($action, $ip, $duration = null) {
        $logger = new SecurityLogger();
        $logger->log('firewall', $action, [
            'ip' => $ip,
            'duration' => $duration
        ]);
    }
    
    /**
     * الحصول على إحصائيات
     */
    public function getStats() {
        return [
            'blacklist_count' => count($this->blacklist),
            'whitelist_count' => count($this->whitelist),
            'temp_bans_count' => count($this->tempBans)
        ];
    }
}
?>