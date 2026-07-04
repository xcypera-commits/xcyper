<?php
/**
 * نظام التنبيهات
 * Alert System
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class AlertSystem {
    
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require __DIR__ . '/../config/security-config.php';
    }
    
    /**
     * إرسال تنبيه
     */
    public function sendAlert($type, $message, $level = 'warning', $data = []) {
        $alert = [
            'id' => uniqid('alert_'),
            'type' => $type,
            'level' => $level,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        // حفظ التنبيه
        $this->saveAlert($alert);
        
        // إرسال حسب القنوات المحددة
        $channels = $this->config['monitoring']['notification_channels'];
        
        foreach ($channels as $channel) {
            $this->sendViaChannel($channel, $alert);
        }
        
        // تسجيل التنبيه
        $this->logger->log('alert', $message, $alert);
        
        return $alert['id'];
    }
    
    /**
     * حفظ التنبيه
     */
    private function saveAlert($alert) {
        $alertsFile = LOGS_PATH . 'alerts.json';
        
        $alerts = [];
        if (file_exists($alertsFile)) {
            $alerts = json_decode(file_get_contents($alertsFile), true) ?: [];
        }
        
        array_unshift($alerts, $alert); // إضافة في البداية
        
        // الاحتفاظ بآخر 1000 تنبيه فقط
        $alerts = array_slice($alerts, 0, 1000);
        
        file_put_contents($alertsFile, json_encode($alerts, JSON_PRETTY_PRINT));
    }
    
    /**
     * إرسال عبر قناة محددة
     */
    private function sendViaChannel($channel, $alert) {
        switch ($channel) {
            case 'email':
                $this->sendEmailAlert($alert);
                break;
                
            case 'database':
                $this->saveToDatabase($alert);
                break;
                
            case 'slack':
                $this->sendSlackAlert($alert);
                break;
                
            case 'sms':
                $this->sendSMSAlert($alert);
                break;
        }
    }
    
    /**
     * إرسال تنبيه بريد إلكتروني
     */
    private function sendEmailAlert($alert) {
        $to = $this->config['monitoring']['admin_email'];
        $subject = "[{$alert['level']}] Security Alert: {$alert['type']}";
        
        $message = "Alert ID: {$alert['id']}\n";
        $message .= "Type: {$alert['type']}\n";
        $message .= "Level: {$alert['level']}\n";
        $message .= "Message: {$alert['message']}\n";
        $message .= "Time: {$alert['timestamp']}\n";
        $message .= "IP: {$alert['ip']}\n";
        
        if (!empty($alert['data'])) {
            $message .= "\nDetails:\n" . print_r($alert['data'], true);
        }
        
        // محاولة إرسال البريد
        if (function_exists('mail')) {
            mail($to, $subject, $message);
        }
    }
    
    /**
     * حفظ في قاعدة البيانات
     */
    private function saveToDatabase($alert) {
        // يمكن تنفيذها لاحقاً حسب هيكل قاعدة البيانات
    }
    
    /**
     * إرسال تنبيه Slack
     */
    private function sendSlackAlert($alert) {
        $webhook = $this->config['monitoring']['slack_webhook'] ?? null;
        
        if (!$webhook) {
            return;
        }
        
        $color = $this->getAlertColor($alert['level']);
        
        $payload = [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "[{$alert['level']}] {$alert['type']}",
                    'text' => $alert['message'],
                    'fields' => [
                        [
                            'title' => 'Alert ID',
                            'value' => $alert['id'],
                            'short' => true
                        ],
                        [
                            'title' => 'Time',
                            'value' => $alert['timestamp'],
                            'short' => true
                        ],
                        [
                            'title' => 'IP Address',
                            'value' => $alert['ip'],
                            'short' => true
                        ]
                    ],
                    'footer' => 'Security Alert System',
                    'ts' => time()
                ]
            ]
        ];
        
        // إرسال الطلب
        if (function_exists('curl_init')) {
            $ch = curl_init($webhook);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    
    /**
     * إرسال تنبيه SMS
     */
    private function sendSMSAlert($alert) {
        // يمكن تنفيذها لاحقاً مع خدمة SMS
    }
    
    /**
     * الحصول على لون التنبيه
     */
    private function getAlertColor($level) {
        switch ($level) {
            case 'critical':
                return 'danger';
            case 'warning':
                return 'warning';
            case 'info':
                return 'info';
            default:
                return 'good';
        }
    }
    
    /**
     * الحصول على التنبيهات
     */
    public function getAlerts($limit = 50, $level = null) {
        $alertsFile = LOGS_PATH . 'alerts.json';
        
        if (!file_exists($alertsFile)) {
            return [];
        }
        
        $alerts = json_decode(file_get_contents($alertsFile), true) ?: [];
        
        if ($level) {
            $alerts = array_filter($alerts, function($alert) use ($level) {
                return $alert['level'] === $level;
            });
        }
        
        return array_slice($alerts, 0, $limit);
    }
    
    /**
     * مسح التنبيهات القديمة
     */
    public function cleanOldAlerts($days = 30) {
        $alertsFile = LOGS_PATH . 'alerts.json';
        
        if (!file_exists($alertsFile)) {
            return;
        }
        
        $alerts = json_decode(file_get_contents($alertsFile), true) ?: [];
        $cutoff = strtotime("-$days days");
        
        $alerts = array_filter($alerts, function($alert) use ($cutoff) {
            return strtotime($alert['timestamp']) > $cutoff;
        });
        
        file_put_contents($alertsFile, json_encode(array_values($alerts), JSON_PRETTY_PRINT));
    }
}
?>