<?php
/**
 * مدير السياسات
 * Policy Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class PolicyManager {
    
    private $logger;
    private $policies = [];
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->loadPolicies();
    }
    
    /**
     * تحميل السياسات
     */
    private function loadPolicies() {
        $this->policies = [
            'data_retention' => [
                'name' => 'سياسة الاحتفاظ بالبيانات',
                'description' => 'مدة الاحتفاظ بالبيانات المختلفة',
                'rules' => [
                    'logs' => 30, // أيام
                    'backups' => 90,
                    'user_data' => 730, // سنتان
                    'deleted_data' => 30
                ]
            ],
            'password' => [
                'name' => 'سياسة كلمة المرور',
                'description' => 'قواعد كلمات المرور',
                'rules' => [
                    'min_length' => 12,
                    'require_uppercase' => true,
                    'require_lowercase' => true,
                    'require_numbers' => true,
                    'require_special' => true,
                    'max_age' => 90, // أيام
                    'history' => 5,
                    'lockout_attempts' => 5,
                    'lockout_duration' => 15 // دقائق
                ]
            ],
            'session' => [
                'name' => 'سياسة الجلسات',
                'description' => 'قواعد إدارة الجلسات',
                'rules' => [
                    'lifetime' => 7200, // ثواني
                    'idle_timeout' => 1800,
                    'regenerate_on_login' => true,
                    'single_session' => false,
                    'remember_me_days' => 30
                ]
            ],
            'file_upload' => [
                'name' => 'سياسة رفع الملفات',
                'description' => 'قيود رفع الملفات',
                'rules' => [
                    'max_size' => 104857600, // 100MB
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'],
                    'blocked_types' => ['php', 'exe', 'sh', 'js', 'html'],
                    'scan_viruses' => true,
                    'max_files_per_upload' => 10
                ]
            ],
            'api' => [
                'name' => 'سياسة API',
                'description' => 'قيود استخدام API',
                'rules' => [
                    'rate_limit' => 1000, // طلبات في الساعة
                    'require_https' => true,
                    'token_lifetime' => 86400, // يوم
                    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE']
                ]
            ],
            'isolation' => [
                'name' => 'سياسة العزل',
                'description' => 'قواعد عزل العملاء',
                'rules' => [
                    'memory_limit' => '512M',
                    'cpu_limit' => 0.5,
                    'storage_limit' => '10G',
                    'network_isolation' => true,
                    'process_limit' => 100,
                    'file_limit' => 10000
                ]
            ]
        ];
    }
    
    /**
     * الحصول على سياسة
     */
    public function getPolicy($policyName) {
        return $this->policies[$policyName] ?? null;
    }
    
    /**
     * الحصول على قاعدة من سياسة
     */
    public function getRule($policyName, $ruleName) {
        return $this->policies[$policyName]['rules'][$ruleName] ?? null;
    }
    
    /**
     * تحديث سياسة
     */
    public function updatePolicy($policyName, $rules) {
        if (!isset($this->policies[$policyName])) {
            return false;
        }
        
        foreach ($rules as $key => $value) {
            if (isset($this->policies[$policyName]['rules'][$key])) {
                $this->policies[$policyName]['rules'][$key] = $value;
            }
        }
        
        $this->savePolicies();
        $this->logger->log('policy', "Policy updated: $policyName");
        
        return true;
    }
    
    /**
     * إنشاء سياسة جديدة
     */
    public function createPolicy($policyName, $name, $description, $rules) {
        if (isset($this->policies[$policyName])) {
            return false;
        }
        
        $this->policies[$policyName] = [
            'name' => $name,
            'description' => $description,
            'rules' => $rules
        ];
        
        $this->savePolicies();
        $this->logger->log('policy', "Policy created: $policyName");
        
        return true;
    }
    
    /**
     * حذف سياسة
     */
    public function deletePolicy($policyName) {
        if (!isset($this->policies[$policyName])) {
            return false;
        }
        
        unset($this->policies[$policyName]);
        $this->savePolicies();
        $this->logger->log('policy', "Policy deleted: $policyName");
        
        return true;
    }
    
    /**
     * حفظ السياسات
     */
    private function savePolicies() {
        // في الإنتاج، احفظ في قاعدة البيانات
        $_SESSION['policies'] = $this->policies;
    }
    
    /**
     * التحقق من الامتثال لسياسة
     */
    public function checkCompliance($policyName, $data) {
        $policy = $this->getPolicy($policyName);
        
        if (!$policy) {
            return ['compliant' => false, 'reason' => 'Policy not found'];
        }
        
        $violations = [];
        
        switch ($policyName) {
            case 'password':
                $violations = $this->checkPasswordCompliance($data, $policy['rules']);
                break;
                
            case 'file_upload':
                $violations = $this->checkFileUploadCompliance($data, $policy['rules']);
                break;
        }
        
        return [
            'compliant' => empty($violations),
            'violations' => $violations
        ];
    }
    
    /**
     * التحقق من امتثال كلمة المرور
     */
    private function checkPasswordCompliance($password, $rules) {
        $violations = [];
        
        if (strlen($password) < $rules['min_length']) {
            $violations[] = "كلمة المرور يجب أن تكون {$rules['min_length']} أحرف على الأقل";
        }
        
        if ($rules['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $violations[] = 'يجب أن تحتوي على حرف كبير';
        }
        
        if ($rules['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $violations[] = 'يجب أن تحتوي على حرف صغير';
        }
        
        if ($rules['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $violations[] = 'يجب أن تحتوي على رقم';
        }
        
        if ($rules['require_special'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $violations[] = 'يجب أن تحتوي على رمز خاص';
        }
        
        return $violations;
    }
    
    /**
     * التحقق من امتثال رفع الملف
     */
    private function checkFileUploadCompliance($file, $rules) {
        $violations = [];
        
        if ($file['size'] > $rules['max_size']) {
            $violations[] = 'حجم الملف أكبر من المسموح به';
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $rules['allowed_types'])) {
            $violations[] = 'نوع الملف غير مسموح به';
        }
        
        if (in_array($extension, $rules['blocked_types'])) {
            $violations[] = 'نوع الملف ممنوع';
        }
        
        return $violations;
    }
    
    /**
     * الحصول على جميع السياسات
     */
    public function getAllPolicies() {
        return $this->policies;
    }
}
?>