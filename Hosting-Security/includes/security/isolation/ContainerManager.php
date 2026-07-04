<?php
/**
 * مدير حاويات العملاء
 * Container Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class ContainerManager {
    
    private $config;
    private $logger;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/security-config.php';
        $this->logger = new SecurityLogger();
    }
    
    /**
     * إنشاء حاوية جديدة لعميل
     */
    public function createContainer($clientId, $resources = []) {
        $containerId = $this->generateContainerId($clientId);
        $containerPath = HOSTING_PATH . 'clients/' . $clientId;
        
        // إنشاء هيكل المجلدات
        $this->createContainerDirectories($containerPath);
        
        // تطبيق حدود الموارد
        $limits = $this->applyResourceLimits($containerPath, $resources);
        
        // إنشاء ملف تعريف الحاوية
        $config = $this->createContainerConfig($clientId, $containerId, $limits);
        
        // تسجيل الحاوية
        $this->registerContainer($clientId, $containerId, $config);
        
        $this->logger->log('container', "Container created for client $clientId", [
            'container_id' => $containerId,
            'limits' => $limits
        ]);
        
        return [
            'container_id' => $containerId,
            'path' => $containerPath,
            'limits' => $limits,
            'config' => $config
        ];
    }
    
    /**
     * إنشاء معرف فريد للحاوية
     */
    private function generateContainerId($clientId) {
        return 'container_' . $clientId . '_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }
    
    /**
     * إنشاء مجلدات الحاوية
     */
    private function createContainerDirectories($path) {
        $dirs = [
            $path . '/www',
            $path . '/databases',
            $path . '/emails',
            $path . '/logs',
            $path . '/temp',
            $path . '/backups',
            $path . '/config',
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // إنشاء ملفات .htaccess للحماية
        $this->createProtectionFiles($path);
    }
    
    /**
     * إنشاء ملفات حماية للمجلدات
     */
    private function createProtectionFiles($path) {
        $htaccess = "Order Deny,Allow\nDeny from all";
        
        // حماية المجلدات الحساسة
        $protected = ['databases', 'emails', 'logs', 'temp', 'backups', 'config'];
        
        foreach ($protected as $dir) {
            $file = $path . '/' . $dir . '/.htaccess';
            if (!file_exists($file)) {
                file_put_contents($file, $htaccess);
            }
        }
        
        // ملف php.ini محلي
        $phpini = $path . '/config/php.ini';
        if (!file_exists($phpini)) {
            $ini = "; Security restrictions\n";
            $ini .= "open_basedir = " . $path . "/www\n";
            $ini .= "disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source\n";
            $ini .= "max_execution_time = 30\n";
            $ini .= "memory_limit = " . $this->config['isolation']['default_memory_limit'] . "\n";
            $ini .= "post_max_size = 10M\n";
            $ini .= "upload_max_filesize = 10M\n";
            $ini .= "allow_url_fopen = Off\n";
            $ini .= "allow_url_include = Off\n";
            file_put_contents($phpini, $ini);
        }
    }
    
    /**
     * تطبيق حدود الموارد
     */
    private function applyResourceLimits($path, $resources) {
        $limits = [
            'memory' => $resources['memory'] ?? $this->config['isolation']['default_memory_limit'],
            'cpu' => $resources['cpu'] ?? $this->config['isolation']['default_cpu_limit'],
            'storage' => $resources['storage'] ?? $this->config['isolation']['max_storage_per_client'],
            'processes' => $resources['processes'] ?? 100,
            'files' => $resources['files'] ?? 10000,
        ];
        
        // حفظ حدود الموارد
        file_put_contents($path . '/config/limits.json', json_encode($limits, JSON_PRETTY_PRINT));
        
        return $limits;
    }
    
    /**
     * إنشاء ملف تعريف الحاوية
     */
    private function createContainerConfig($clientId, $containerId, $limits) {
        $config = [
            'client_id' => $clientId,
            'container_id' => $containerId,
            'created_at' => date('Y-m-d H:i:s'),
            'limits' => $limits,
            'status' => 'active',
            'network' => [
                'isolated' => true,
                'allowed_ports' => [80, 443, 21, 22],
                'blocked_ports' => [25, 465, 587, 3306, 5432],
            ],
            'security' => [
                'seccomp_enabled' => $this->config['isolation']['enable_seccomp'],
                'apparmor_enabled' => $this->config['isolation']['enable_apparmor'],
                'readonly_rootfs' => $this->config['isolation']['readonly_rootfs'],
            ]
        ];
        
        file_put_contents(HOSTING_PATH . 'clients/' . $clientId . '/config/container.json', 
            json_encode($config, JSON_PRETTY_PRINT));
        
        return $config;
    }
    
    /**
     * تسجيل الحاوية في النظام
     */
    private function registerContainer($clientId, $containerId, $config) {
        $containers = [];
        $registryFile = HOSTING_PATH . 'containers/registry.json';
        
        if (file_exists($registryFile)) {
            $containers = json_decode(file_get_contents($registryFile), true) ?: [];
        }
        
        $containers[$containerId] = [
            'client_id' => $clientId,
            'created_at' => $config['created_at'],
            'status' => 'active'
        ];
        
        file_put_contents($registryFile, json_encode($containers, JSON_PRETTY_PRINT));
    }
    
    /**
     * عزل حاوية (في حالة اكتشاف تهديد)
     */
    public function isolateContainer($containerId, $reason = 'security_threat') {
        $registryFile = HOSTING_PATH . 'containers/registry.json';
        
        if (!file_exists($registryFile)) {
            return false;
        }
        
        $containers = json_decode(file_get_contents($registryFile), true);
        
        if (!isset($containers[$containerId])) {
            return false;
        }
        
        $clientId = $containers[$containerId]['client_id'];
        $containerPath = HOSTING_PATH . 'clients/' . $clientId;
        
        // تغيير الحالة
        $containers[$containerId]['status'] = 'isolated';
        $containers[$containerId]['isolated_at'] = date('Y-m-d H:i:s');
        $containers[$containerId]['isolation_reason'] = $reason;
        
        file_put_contents($registryFile, json_encode($containers, JSON_PRETTY_PRINT));
        
        // قطع الاتصال بالشبكة
        $this->disconnectNetwork($containerPath);
        
        // نقل الملفات المشبوهة للحجر الصحي
        $this->quarantineSuspiciousFiles($containerPath);
        
        // تسجيل الحدث
        $this->logger->logThreat("Container $containerId isolated", $reason, [
            'client_id' => $clientId,
            'reason' => $reason
        ]);
        
        return true;
    }
    
    /**
     * قطع اتصال الشبكة عن الحاوية
     */
    private function disconnectNetwork($containerPath) {
        // إنشاء ملف لقطع الشبكة
        file_put_contents($containerPath . '/config/network_block', date('Y-m-d H:i:s'));
        
        // محاولة تعديل iptables إذا كان متاحاً
        if (function_exists('exec')) {
            exec("iptables -A INPUT -s " . $this->getContainerIP($containerPath) . " -j DROP 2>/dev/null");
        }
    }
    
    /**
     * الحصول على IP الحاوية
     */
    private function getContainerIP($containerPath) {
        // محاولة قراءة IP من ملف الإعدادات
        $configFile = $containerPath . '/config/container.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            return $config['ip'] ?? '0.0.0.0';
        }
        return '0.0.0.0';
    }
    
    /**
     * نقل الملفات المشبوهة للحجر الصحي
     */
    private function quarantineSuspiciousFiles($containerPath) {
        $quarantinePath = __DIR__ . '/../../../quarantine/';
        
        if (!is_dir($quarantinePath)) {
            mkdir($quarantinePath, 0755, true);
        }
        
        // البحث عن ملفات مشبوهة
        $suspicious = glob($containerPath . '/www/*.{php,php3,php4,php5,phtml,exe}', GLOB_BRACE);
        
        foreach ($suspicious as $file) {
            $dest = $quarantinePath . basename($file) . '.' . uniqid() . '.quarantine';
            rename($file, $dest);
            
            $this->logger->log('quarantine', "File quarantined: " . basename($file), [
                'source' => $file,
                'destination' => $dest
            ]);
        }
    }
    
    /**
     * إلغاء عزل الحاوية
     */
    public function releaseContainer($containerId) {
        $registryFile = HOSTING_PATH . 'containers/registry.json';
        
        if (!file_exists($registryFile)) {
            return false;
        }
        
        $containers = json_decode(file_get_contents($registryFile), true);
        
        if (!isset($containers[$containerId])) {
            return false;
        }
        
        $containers[$containerId]['status'] = 'active';
        $containers[$containerId]['released_at'] = date('Y-m-d H:i:s');
        
        file_put_contents($registryFile, json_encode($containers, JSON_PRETTY_PRINT));
        
        $this->logger->log('container', "Container $containerId released");
        
        return true;
    }
    
    /**
     * حذف الحاوية
     */
    public function deleteContainer($containerId) {
        $registryFile = HOSTING_PATH . 'containers/registry.json';
        
        if (!file_exists($registryFile)) {
            return false;
        }
        
        $containers = json_decode(file_get_contents($registryFile), true);
        
        if (!isset($containers[$containerId])) {
            return false;
        }
        
        $clientId = $containers[$containerId]['client_id'];
        
        // أخذ نسخة احتياطية قبل الحذف
        $this->backupBeforeDelete($clientId);
        
        // حذف الحاوية من السجل
        unset($containers[$containerId]);
        file_put_contents($registryFile, json_encode($containers, JSON_PRETTY_PRINT));
        
        $this->logger->log('container', "Container $containerId deleted");
        
        return true;
    }
    
    /**
     * نسخ احتياطي قبل الحذف
     */
    private function backupBeforeDelete($clientId) {
        $backupManager = new BackupManager();
        $backupManager->createClientBackup($clientId, 'pre_deletion');
    }
    
    /**
     * الحصول على حالة الحاوية
     */
    public function getContainerStatus($containerId) {
        $registryFile = HOSTING_PATH . 'containers/registry.json';
        
        if (!file_exists($registryFile)) {
            return null;
        }
        
        $containers = json_decode(file_get_contents($registryFile), true);
        
        return $containers[$containerId] ?? null;
    }
    
    /**
     * الحصول على جميع حاويات عميل
     */
    public function getClientContainers($clientId) {
        $registryFile = HOSTING_PATH . 'containers/registry.json';
        
        if (!file_exists($registryFile)) {
            return [];
        }
        
        $containers = json_decode(file_get_contents($registryFile), true);
        $clientContainers = [];
        
        foreach ($containers as $id => $info) {
            if ($info['client_id'] == $clientId) {
                $clientContainers[$id] = $info;
            }
        }
        
        return $clientContainers;
    }
    
    /**
     * فحص سلامة الحاوية
     */
    public function scanContainer($containerId) {
        $status = $this->getContainerStatus($containerId);
        
        if (!$status) {
            return ['error' => 'Container not found'];
        }
        
        $clientId = $status['client_id'];
        $containerPath = HOSTING_PATH . 'clients/' . $clientId;
        
        $issues = [];
        
        // فحص الملفات المشبوهة
        $scanner = new MalwareDetector();
        $malware = $scanner->scanDirectory($containerPath . '/www');
        
        if (!empty($malware)) {
            $issues['malware'] = $malware;
        }
        
        // فحص استخدام الموارد
        $usage = $this->checkResourceUsage($containerPath);
        if ($usage['over_limits']) {
            $issues['resources'] = $usage;
        }
        
        // فحص صلاحيات الملفات
        $permissions = $this->checkFilePermissions($containerPath);
        if (!empty($permissions)) {
            $issues['permissions'] = $permissions;
        }
        
        $result = [
            'container_id' => $containerId,
            'status' => $status['status'],
            'issues' => $issues,
            'scan_time' => date('Y-m-d H:i:s')
        ];
        
        $this->logger->log('scan', "Container $containerId scanned", $result);
        
        return $result;
    }
    
    /**
     * فحص استخدام الموارد
     */
    private function checkResourceUsage($path) {
        $limitsFile = $path . '/config/limits.json';
        
        if (!file_exists($limitsFile)) {
            return ['over_limits' => false];
        }
        
        $limits = json_decode(file_get_contents($limitsFile), true);
        
        // فحص حجم التخزين
        $storage = $this->getDirectorySize($path . '/www');
        $storageLimit = $this->parseSize($limits['storage']);
        
        $overLimits = [];
        
        if ($storage > $storageLimit) {
            $overLimits[] = 'storage';
        }
        
        return [
            'over_limits' => !empty($overLimits),
            'limits' => $limits,
            'current' => [
                'storage' => $this->formatBytes($storage),
            ]
        ];
    }
    
    /**
     * فحص صلاحيات الملفات
     */
    private function checkFilePermissions($path) {
        $issues = [];
        $wwwPath = $path . '/www';
        
        if (!is_dir($wwwPath)) {
            return $issues;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($wwwPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            $perms = substr(sprintf('%o', fileperms($file)), -4);
            
            // ملفات قابلة للكتابة بشكل خطر
            if ($perms == '0777' || $perms == '0666') {
                $issues[] = [
                    'file' => str_replace($path, '', $file),
                    'permissions' => $perms,
                    'issue' => 'world_writable'
                ];
            }
            
            // ملفات PHP بصلاحيات تنفيذ خطرة
            if ($file->getExtension() == 'php' && ($perms == '0755' || $perms == '0777')) {
                $issues[] = [
                    'file' => str_replace($path, '', $file),
                    'permissions' => $perms,
                    'issue' => 'executable_php'
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * حساب حجم المجلد
     */
    private function getDirectorySize($path) {
        $size = 0;
        
        if (!is_dir($path)) {
            return 0;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        return $size;
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