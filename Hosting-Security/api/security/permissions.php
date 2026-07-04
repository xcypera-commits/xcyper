<?php
require_once __DIR__ . '/../../includes/security/security-init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../includes/security/authorization/PermissionManager.php';
require_once __DIR__ . '/../../includes/security/authorization/RoleManager.php';

$db = new PDO('mysql:host=localhost;dbname=hosting_security', 'root', '');
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_user_permissions':
        $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
        $permissionManager = new PermissionManager($db);
        
        // في الواقع، هذا سيكون من قاعدة البيانات
        $permissions = [
            'view_dashboard',
            'view_projects',
            'create_project',
            'upload_files'
        ];
        
        echo json_encode(['permissions' => $permissions], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'get_roles':
        $roleManager = new RoleManager($db);
        $roles = $roleManager->getAllRoles();
        echo json_encode(['roles' => $roles], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'update_role':
        if (!AccessControl::checkAccess($_SESSION['user_role'], 'hosting_security_manager')) {
            echo json_encode(['error' => 'صلاحية غير كافية'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $target_user_id = $_POST['user_id'];
        $new_role = $_POST['role'];
        
        $roleManager = new RoleManager($db);
        $success = $roleManager->assignRole($target_user_id, $new_role);
        
        echo json_encode(['success' => $success], JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        echo json_encode(['error' => 'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
}
?>