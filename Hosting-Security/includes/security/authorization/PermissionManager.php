<?php
/**
 * مدير الصلاحيات
 * Permission Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class PermissionManager {
    
    private $logger;
    private $roleManager;
    private $permissions = [];
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->roleManager = new RoleManager();
        $this->loadPermissions();
    }
    
    /**
     * تحميل الصلاحيات المتاحة
     */
    private function loadPermissions() {
        $this->permissions = [
            // المشاريع
            'view_own_projects' => 'عرض مشاريعه الخاصة',
            'view_all_projects' => 'عرض جميع المشاريع',
            'create_projects' => 'إنشاء مشاريع جديدة',
            'edit_projects' => 'تعديل المشاريع',
            'delete_projects' => 'حذف المشاريع',
            
            // الملفات
            'upload_files' => 'رفع ملفات',
            'manage_files' => 'إدارة الملفات (تعديل/حذف)',
            'download_files' => 'تحميل الملفات',
            
            // العقود
            'view_own_contracts' => 'عرض عقوده الخاصة',
            'manage_contracts' => 'إدارة العقود',
            'approve_contracts' => 'الموافقة على العقود',
            
            // الفواتير
            'view_own_invoices' => 'عرض فواتيره الخاصة',
            'manage_invoices' => 'إدارة الفواتير',
            'process_payments' => 'معالجة المدفوعات',
            'view_financial_reports' => 'عرض التقارير المالية',
            
            // التقارير
            'view_own_reports' => 'عرض تقاريره الخاصة',
            'view_all_reports' => 'عرض جميع التقارير',
            'create_reports' => 'إنشاء تقارير',
            
            // المستخدمين
            'manage_users' => 'إدارة المستخدمين',
            'assign_roles' => 'تعيين الأدوار',
            
            // الأمان
            'manage_security' => 'إدارة إعدادات الأمان',
            'run_security_tests' => 'تشغيل اختبارات الأمان',
            'view_monitoring' => 'عرض المراقبة',
            'receive_alerts' => 'استقبال التنبيهات',
            'apply_security_policies' => 'تطبيق سياسات الأمان',
            'analyze_threats' => 'تحليل التهديدات',
            
            // التوثيق
            'create_documentation' => 'إنشاء توثيق',
            'edit_documentation' => 'تعديل توثيق',
            'manage_docs_repo' => 'إدارة مستودع التوثيق',
            
            // الخوادم والتخزين
            'manage_servers' => 'إدارة الخوادم',
            'monitor_storage' => 'مراقبة التخزين',
            'perform_backup' => 'تنفيذ نسخ احتياطي',
            'apply_updates' => 'تطبيق التحديثات',
            
            // المهام
            'manage_tasks' => 'إدارة المهام',
            'assign_tasks' => 'تعيين المهام',
            'track_progress' => 'تتبع التقدم',
            
            // التواصل
            'send_feedback' => 'إرسال ملاحظات',
            'coordinate_teams' => 'التنسيق بين الفرق',
            
            // إضافات
            'access_api' => 'الوصول إلى API',
            'export_data' => 'تصدير البيانات',
            'import_data' => 'استيراد البيانات'
        ];
    }
    
    /**
     * التحقق من صلاحية المستخدم
     */
    public function hasPermission($userId, $permission) {
        $userRole = $this->getUserRole($userId);
        
        if (!$userRole) {
            return false;
        }
        
        $rolePermissions = $this->roleManager->getRolePermissions($userRole);
        
        // صلاحية admin تعطي كل الصلاحيات
        if (in_array('*', $rolePermissions)) {
            return true;
        }
        
        return in_array($permission, $rolePermissions);
    }
    
    /**
     * التحقق من صلاحيات متعددة (يجب توفر الكل)
     */
    public function hasAllPermissions($userId, $permissions) {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * التحقق من صلاحيات متعددة (يكفي توفر واحدة)
     */
    public function hasAnyPermission($userId, $permissions) {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * الحصول على دور المستخدم
     */
    private function getUserRole($userId) {
        // محاكاة - استعلام قاعدة البيانات
        $users = [
            1 => 'admin',
            2 => 'client',
            3 => 'manager',
            4 => 'documentation_staff',
            5 => 'cloud_storage_staff',
            6 => 'pentest_staff',
            7 => 'monitoring_staff',
            8 => 'pms_staff',
            9 => 'finance_staff'
        ];
        
        return $users[$userId] ?? null;
    }
    
    /**
     * الحصول على جميع صلاحيات المستخدم
     */
    public function getUserPermissions($userId) {
        $userRole = $this->getUserRole($userId);
        
        if (!$userRole) {
            return [];
        }
        
        $permissions = $this->roleManager->getRolePermissions($userRole);
        
        // إذا كان admin، أرجع كل الصلاحيات
        if (in_array('*', $permissions)) {
            return array_keys($this->permissions);
        }
        
        return $permissions;
    }
    
    /**
     * الحصول على جميع الصلاحيات المتاحة
     */
    public function getAllPermissions() {
        return $this->permissions;
    }
    
    /**
     * إضافة صلاحية جديدة
     */
    public function addPermission($permissionId, $description) {
        if (isset($this->permissions[$permissionId])) {
            return false;
        }
        
        $this->permissions[$permissionId] = $description;
        $this->savePermissions();
        $this->logger->log('permission', "Permission added: $permissionId");
        
        return true;
    }
    
    /**
     * حفظ الصلاحيات
     */
    private function savePermissions() {
        // في الإنتاج، احفظ في قاعدة البيانات
        $_SESSION['permissions'] = $this->permissions;
    }
    
    /**
     * التحقق من صلاحية لعرض صفحة
     */
    public function checkPageAccess($userId, $page) {
        // قواعد الوصول للصفحات بناءً على هيكل النظام
        $pagePermissions = [
            '/pages/admin/security-dashboard.php' => ['admin', 'manager'],
            '/pages/admin/CloudStorage/' => ['admin', 'manager', 'cloud_storage_staff'],
            '/pages/admin/Documentation/' => ['admin', 'manager', 'documentation_staff'],
            '/pages/admin/ManagerHostingSecurity/' => ['admin', 'manager'],
            '/pages/admin/PenetrationTestingUnit/' => ['admin', 'manager', 'pentest_staff'],
            '/pages/admin/SecurityMonitoring/' => ['admin', 'manager', 'monitoring_staff'],
            '/pages/clientHostingSecurity/dashboard.php' => ['client'],
            '/pages/clientHostingSecurity/upload.php' => ['client'],
            '/pages/clientHostingSecurity/projects.php' => ['client'],
            '/pages/clientHostingSecurity/billing.php' => ['client', 'finance_staff'],
        ];
        
        $userRole = $this->getUserRole($userId);
        
        foreach ($pagePermissions as $pattern => $allowedRoles) {
            if (strpos($page, $pattern) === 0 || $pattern === $page) {
                return in_array($userRole, $allowedRoles);
            }
        }
        
        // الصفحات غير المحددة متاحة للجميع
        return true;
    }
}
?>