<?php
/**
 * مدير حصص الموارد
 * Quota Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class QuotaManager {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
    }
    
    /**
     * تطبيق حصة تخزينية على عميل
     */
    public function applyStorageQuota($clientId, $limit) {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId;
        
        if (!is_dir($clientPath)) {
            return false;
        }
        
        $current = $this->getCurrentStorageUsage($clientId);
        $limitBytes = $this->parseSize($limit);
        
        $quota = [
            'client_id' => $clientId,
            'limit' => $limit,
            'limit_bytes' => $limitBytes,
            'current' => $current,
            'usage_percent' => ($current / $limitBytes) * 100,
            'applied_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($clientPath . '/config/quota.json', json_encode($quota, JSON_PRETTY_PRINT));
        
        $this->logger->log('quota', "Storage quota applied for client $clientId", $quota);
        
        return $quota;
    }
    
    /**
     * الحصول على استخدام التخزين الحالي
     */
    public function getCurrentStorageUsage($clientId) {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId . '/www';
        
        if (!is_dir($clientPath)) {
            return 0;
        }
        
        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($clientPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }
    
    /**
     * التحقق من الحصة التخزينية
     */
    public function checkStorageQuota($clientId) {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId;
        $quotaFile = $clientPath . '/config/quota.json';
        
        if (!file_exists($quotaFile)) {
            return ['within_limit' => true];
        }
        
        $quota = json_decode(file_get_contents($quotaFile), true);
        $current = $this->getCurrentStorageUsage($clientId);
        
        $quota['current'] = $current;
        $quota['usage_percent'] = ($current / $quota['limit_bytes']) * 100;
        
        file_put_contents($quotaFile, json_encode($quota, JSON_PRETTY_PRINT));
        
        $result = [
            'within_limit' => $current <= $quota['limit_bytes'],
            'current' => $this->formatBytes($current),
            'limit' => $quota['limit'],
            'usage_percent' => round($quota['usage_percent'], 2),
            'free' => $this->formatBytes($quota['limit_bytes'] - $current)
        ];
        
        // تنبيه إذا اقترب من الحد
        if ($quota['usage_percent'] > 90) {
            $this->logger->log('quota', "Client $clientId approaching storage limit", $result);
        }
        
        return $result;
    }
    
    /**
     * تطبيق حصة عدد الملفات
     */
    public function applyFilesQuota($clientId, $maxFiles) {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId;
        
        $current = $this->getCurrentFileCount($clientId);
        
        $quota = [
            'client_id' => $clientId,
            'max_files' => $maxFiles,
            'current_files' => $current,
            'usage_percent' => ($current / $maxFiles) * 100,
            'applied_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($clientPath . '/config/files_quota.json', json_encode($quota, JSON_PRETTY_PRINT));
        
        return $quota;
    }
    
    /**
     * الحصول على عدد الملفات الحالي
     */
    public function getCurrentFileCount($clientId) {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId . '/www';
        
        if (!is_dir($clientPath)) {
            return 0;
        }
        
        $count = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($clientPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            $count++;
        }
        
        return $count;
    }
    
    /**
     * تطبيق حصة النطاق الترددي
     */
    public function applyBandwidthQuota($clientId, $monthlyLimit) {
        $quotaFile = HOSTING_PATH . 'clients/' . $clientId . '/config/bandwidth.json';
        
        $quota = [
            'client_id' => $clientId,
            'monthly_limit' => $monthlyLimit,
            'used' => 0,
            'month' => date('Y-m'),
            'reset_date' => date('Y-m-01', strtotime('+1 month')),
            'applied_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($quotaFile, json_encode($quota, JSON_PRETTY_PRINT));
        
        return $quota;
    }
    
    /**
     * تسجيل استخدام النطاق الترددي
     */
    public function trackBandwidthUsage($clientId, $bytes) {
        $quotaFile = HOSTING_PATH . 'clients/' . $clientId . '/config/bandwidth.json';
        
        if (!file_exists($quotaFile)) {
            return false;
        }
        
        $quota = json_decode(file_get_contents($quotaFile), true);
        
        // إعادة تعيين إذا كان شهر جديد
        if ($quota['month'] != date('Y-m')) {
            $quota['used'] = 0;
            $quota['month'] = date('Y-m');
            $quota['reset_date'] = date('Y-m-01', strtotime('+1 month'));
        }
        
        $quota['used'] += $bytes;
        
        file_put_contents($quotaFile, json_encode($quota, JSON_PRETTY_PRINT));
        
        // تنبيه إذا اقترب من الحد
        $limitBytes = $this->parseSize($quota['monthly_limit']);
        $usagePercent = ($quota['used'] / $limitBytes) * 100;
        
        if ($usagePercent > 80) {
            $this->logger->log('quota', "Client $clientId bandwidth usage at " . round($usagePercent) . "%", [
                'used' => $this->formatBytes($quota['used']),
                'limit' => $quota['monthly_limit']
            ]);
        }
        
        return $quota;
    }
    
    /**
     * الحصول على جميع الحصص لعميل
     */
    public function getAllQuotas($clientId) {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId;
        
        return [
            'storage' => $this->checkStorageQuota($clientId),
            'files' => $this->getFilesQuota($clientId),
            'bandwidth' => $this->getBandwidthQuota($clientId)
        ];
    }
    
    /**
     * الحصول على حصة الملفات
     */
    private function getFilesQuota($clientId) {
        $file = HOSTING_PATH . 'clients/' . $clientId . '/config/files_quota.json';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $quota = json_decode(file_get_contents($file), true);
        $current = $this->getCurrentFileCount($clientId);
        
        $quota['current_files'] = $current;
        $quota['usage_percent'] = ($current / $quota['max_files']) * 100;
        
        return $quota;
    }
    
    /**
     * الحصول على حصة النطاق
     */
    private function getBandwidthQuota($clientId) {
        $file = HOSTING_PATH . 'clients/' . $clientId . '/config/bandwidth.json';
        
        if (!file_exists($file)) {
            return null;
        }
        
        return json_decode(file_get_contents($file), true);
    }
    
    /**
     * تحويل حجم النصي إلى بايت
     */
    private function parseSize($size) {
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return $value;
        }
    }
    
    /**
     * تنسيق البايتات
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
?>