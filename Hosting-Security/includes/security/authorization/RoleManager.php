<?php
/**
 * مدير الأدوار
 * Role Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class RoleManager {
    
    private $logger;
    private $roles = [];
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->loadRoles();
    }
    
    /**
     * تحميل الأدوار
     */
    private function loadRoles() {
        // الأدوار الافتراضية بناءً على هيكل النظام الخاص بك
        $this->roles = [
            'admin' => [
                'name' => 'مدير النظام',
                'description' => 'صلاحيات كاملة على النظام',
                'permissions' => ['*'] // كل الصلاحيات
            ],
            'manager' => [
                'name' => 'مدير',
                'description' => 'مدير نظام الاستضافة والحماية',
                'permissions' => [
                    'view_all_projects',
                    'manage_tasks',
                    'review_reports',
                    'approve_delivery',
                    'manage_users',
                    'view_financial',
                    'manage_security'
                ]
            ],
            'documentation_staff' => [
                'name' => 'موظف توثيق',
                'description' => 'وحدة التوثيق',
                'permissions' => [
                    'create_documentation',
                    'edit_documentation',
                    'view_projects',
                    'send_reports',
                    'manage_docs_repo'
                ]
            ],
            'cloud_storage_staff' => [
                'name' => 'موظف تخزين سحابي',
                'description' => 'وحدة التخزين السحابي',
                'permissions' => [
                    'manage_files',
                    'manage_servers',
                    'perform_backup',
                    'apply_updates',
                    'monitor_storage'
                ]
            ],
            'pentest_staff' => [
                'name' => 'مختبر اختراق',
                'description' => 'وحدة اختبار الاختراق',
                'permissions' => [
                    'run_security_tests',
                    'view_documentation',
                    'create_vulnerability_reports',
                    'recommend_fixes'
                ]
            ],
            'monitoring_staff' => [
                'name' => 'موظف مراقبة',
                'description' => 'وحدة الحماية والمراقبة',
                'permissions' => [
                    'view_monitoring',
                    'receive_alerts',
                    'create_reports',
                    'apply_security_policies',
                    'analyze_threats'
                ]
            ],
            'pms_staff' => [
                'name' => 'مدير مشاريع',
                'description' => 'إدارة المشاريع',
                'permissions' => [
                    'create_projects',
                    'manage_contracts',
                    'schedule_tasks',
                    'coordinate_teams',
                    'track_progress'
                ]
            ],
            'finance_staff' => [
                'name' => 'موظف مالي',
                'description' => 'نظام الحسابات والفوترة',
                'permissions' => [
                    'manage_invoices',
                    'process_payments',
                    'view_financial_reports',
                    'close_projects_financially'
                ]
            ],
            'client' => [
                'name' => 'عميل',
                'description' => 'عميل عادي',
                'permissions' => [
                    'view_own_projects',
                    'upload_files',
                    'view_own_contracts',
                    'pay_invoices',
                    'view_own_reports',
                    'send_feedback'
                ]
            ]
        ];
    }
    
    /**
     * الحصول على دور
     */
    public function getRole($roleId) {
        return $this->roles[$roleId] ?? null;
    }
    
    /**
     * الحصول على جميع الأدوار
     */
    public function getAllRoles() {
        return $this->roles;
    }
    
    /**
     * إنشاء دور جديد
     */
    public function createRole($roleId, $name, $description, $permissions = []) {
        if (isset($this->roles[$roleId])) {
            return false;
        }
        
        $this->roles[$roleId] = [
            'name' => $name,
            'description' => $description,
            'permissions' => $permissions
        ];
        
        $this->saveRoles();
        $this->logger->log('role', "Role created: $roleId");
        
        return true;
    }
    
    /**
     * تحديث دور
     */
    public function updateRole($roleId, $data) {
        if (!isset($this->roles[$roleId])) {
            return false;
        }
        
        foreach ($data as $key => $value) {
            if (isset($this->roles[$roleId][$key])) {
                $this->roles[$roleId][$key] = $value;
            }
        }
        
        $this->saveRoles();
        $this->logger->log('role', "Role updated: $roleId");
        
        return true;
    }
    
    /**
     * حذف دور
     */
    public function deleteRole($roleId) {
        if (!isset($this->roles[$roleId])) {
            return false;
        }
        
        unset($this->roles[$roleId]);
        $this->saveRoles();
        $this->logger->log('role', "Role deleted: $roleId");
        
        return true;
    }
    
    /**
     * إضافة صلاحية لدور
     */
    public function addPermission($roleId, $permission) {
        if (!isset($this->roles[$roleId])) {
            return false;
        }
        
        if (!in_array($permission, $this->roles[$roleId]['permissions'])) {
            $this->roles[$roleId]['permissions'][] = $permission;
            $this->saveRoles();
            $this->logger->log('role', "Permission $permission added to role $roleId");
        }
        
        return true;
    }
    
    /**
     * إزالة صلاحية من دور
     */
    public function removePermission($roleId, $permission) {
        if (!isset($this->roles[$roleId])) {
            return false;
        }
        
        $key = array_search($permission, $this->roles[$roleId]['permissions']);
        if ($key !== false) {
            array_splice($this->roles[$roleId]['permissions'], $key, 1);
            $this->saveRoles();
            $this->logger->log('role', "Permission $permission removed from role $roleId");
        }
        
        return true;
    }
    
    /**
     * حفظ الأدوار (محاكاة)
     */
    private function saveRoles() {
        // في الإنتاج، احفظ في قاعدة البيانات
        $_SESSION['roles'] = $this->roles;
    }
    
    /**
     * الحصول على صلاحيات دور
     */
    public function getRolePermissions($roleId) {
        if ($roleId === 'admin') {
            return ['*']; // كل الصلاحيات
        }
        
        return $this->roles[$roleId]['permissions'] ?? [];
    }
}
?>