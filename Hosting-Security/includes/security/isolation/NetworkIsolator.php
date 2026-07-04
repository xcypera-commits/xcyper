<?php
/**
 * عزل الشبكات
 * Network Isolator
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class NetworkIsolator {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
    }
    
    /**
     * إنشاء شبكة معزولة لعميل
     */
    public function createIsolatedNetwork($clientId, $containerId) {
        $networkConfig = [
            'client_id' => $clientId,
            'container_id' => $containerId,
            'network_id' => 'net_' . bin2hex(random_bytes(8)),
            'ip' => $this->generatePrivateIP($clientId),
            'created_at' => date('Y-m-d H:i:s'),
            'rules' => $this->getDefaultNetworkRules()
        ];
        
        $this->saveNetworkConfig($containerId, $networkConfig);
        
        $this->logger->log('network', "Isolated network created for container $containerId", $networkConfig);
        
        return $networkConfig;
    }
    
    /**
     * توليد IP خاص
     */
    private function generatePrivateIP($clientId) {
        // استخدام نطاق 10.0.0.0/8
        $third = ($clientId % 255);
        $fourth = rand(2, 254);
        
        return "10.0.$third.$fourth";
    }
    
    /**
     * الحصول على قواعد الشبكة الافتراضية
     */
    private function getDefaultNetworkRules() {
        return [
            'allow_outgoing' => true,
            'allow_incoming' => false,
            'allowed_ports' => [80, 443, 21, 22],
            'blocked_ips' => [],
            'rate_limit' => '1000/s',
            'isolated_from_others' => true
        ];
    }
    
    /**
     * حفظ إعدادات الشبكة
     */
    private function saveNetworkConfig($containerId, $config) {
        $file = HOSTING_PATH . 'containers/networks/' . $containerId . '.json';
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * تطبيق قواعد الجدار الناري
     */
    public function applyFirewallRules($containerId) {
        $config = $this->getNetworkConfig($containerId);
        
        if (!$config) {
            return false;
        }
        
        // تطبيق القواعد على مستوى PHP (محاكاة)
        // في الإنتاج، يمكن استخدام iptables أو APIs حقيقية
        
        $this->logger->log('firewall', "Firewall rules applied for container $containerId", $config);
        
        return true;
    }
    
    /**
     * الحصول على إعدادات الشبكة
     */
    private function getNetworkConfig($containerId) {
        $file = HOSTING_PATH . 'containers/networks/' . $containerId . '.json';
        
        if (!file_exists($file)) {
            return null;
        }
        
        return json_decode(file_get_contents($file), true);
    }
    
    /**
     * قطع الشبكة عن حاوية
     */
    public function disconnectContainer($containerId) {
        $config = $this->getNetworkConfig($containerId);
        
        if (!$config) {
            return false;
        }
        
        $config['connected'] = false;
        $config['disconnected_at'] = date('Y-m-d H:i:s');
        
        $this->saveNetworkConfig($containerId, $config);
        
        $this->logger->log('network', "Container $containerId disconnected from network");
        
        return true;
    }
    
    /**
     * إعادة توصيل الشبكة
     */
    public function reconnectContainer($containerId) {
        $config = $this->getNetworkConfig($containerId);
        
        if (!$config) {
            return false;
        }
        
        $config['connected'] = true;
        $config['reconnected_at'] = date('Y-m-d H:i:s');
        
        $this->saveNetworkConfig($containerId, $config);
        
        $this->logger->log('network', "Container $containerId reconnected to network");
        
        return true;
    }
}
?>