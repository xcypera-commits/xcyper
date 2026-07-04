<?php
/**
 * التحكم في الوصول
 * Access Control
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class AccessControl {
    
    private $permissionManager;
    private $logger;
    
    public function __construct() {
        $this->permissionManager = new PermissionManager();
        $this->logger = new SecurityLogger();
    }
    
    /**
     * التحقق من الوصول للصفحة الحالية
     */
    public function checkCurrentPageAccess() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        $currentPage = $_SERVER['REQUEST_URI'];
        
        // إزالة معلمات الاستعلام
        $currentPage = strtok($currentPage, '?');
        
        return $this->permissionManager->checkPageAccess($userId, $currentPage);
    }
    
    /**
     * تطبيق التحكم في الوصول
     */
    public function enforceAccess() {
        if (!$this->checkCurrentPageAccess()) {
            $this->logger->log('access', 'Access denied', [
                'user_id' => $_SESSION['user_id'] ?? 'guest',
                'page' => $_SERVER['REQUEST_URI'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            http_response_code(403);
            
            if (isset($_SESSION['user_id'])) {
                die('ليس لديك صلاحية للوصول إلى هذه الصفحة');
            } else {
                header('Location: /pages/clientHostingSecurity/login.php');
                exit();
            }
        }
    }
    
    /**
     * التحقق من صلاحية محددة
     */
    public function checkPermission($permission) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return $this->permissionManager->hasPermission($_SESSION['user_id'], $permission);
    }
    
    /**
     * فرض صلاحية محددة
     */
    public function requirePermission($permission) {
        if (!$this->checkPermission($permission)) {
            $this->logger->log('access', 'Permission denied', [
                'user_id' => $_SESSION['user_id'],
                'permission' => $permission,
                'page' => $_SERVER['REQUEST_URI']
            ]);
            
            http_response_code(403);
            die('ليس لديك صلاحية لتنفيذ هذا الإجراء');
        }
    }
    
    /**
     * فرض صلاحيات متعددة (الكل)
     */
    public function requireAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            $this->requirePermission($permission);
        }
    }
    
    /**
     * فرض صلاحيات متعددة (واحدة على الأقل)
     */
    public function requireAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if ($this->checkPermission($permission)) {
                return true;
            }
        }
        
        $this->logger->log('access', 'Any permission denied', [
            'user_id' => $_SESSION['user_id'],
            'required' => $permissions,
            'page' => $_SERVER['REQUEST_URI']
        ]);
        
        http_response_code(403);
        die('ليس لديك صلاحية لتنفيذ هذا الإجراء');
    }
    
    /**
     * الحصول على قائمة الصلاحيات للمستخدم الحالي
     */
    public function getCurrentUserPermissions() {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        
        return $this->permissionManager->getUserPermissions($_SESSION['user_id']);
    }
    
    /**
     * إنشاء حقل CSRF مع صلاحيات إضافية
     */
    public function getSecureFormToken($action) {
        $token = SecurityValidator::generateCSRF();
        
        // تخزين الصلاحية المطلوبة مع التوكن
        $_SESSION['form_permissions'][$token] = $action;
        
        return $token;
    }
    
    /**
     * التحقق من نموذج مع صلاحيات
     */
    public function validateSecureForm($token, $permission) {
        if (!SecurityValidator::validateCSRF($token)) {
            return false;
        }
        
        if (!isset($_SESSION['form_permissions'][$token])) {
            return false;
        }
        
        if ($_SESSION['form_permissions'][$token] !== $permission) {
            return false;
        }
        
        unset($_SESSION['form_permissions'][$token]);
        
        return $this->checkPermission($permission);
    }
}
?>