<?php
require_once __DIR__ . '/../..//security-init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../audit/AuditTrail.php';

$db = new PDO('mysql:host=localhost;dbname=hosting_security', 'root', '');
$auditTrail = new AuditTrail($db);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_logs':
        if (!AccessControl::checkAccess($_SESSION['user_role'], 'security_monitor')) {
            echo json_encode(['error' => 'صلاحية غير كافية'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'event_type' => $_GET['event_type'] ?? null,
            'user_id' => $_GET['user_id'] ?? null
        ];
        
        $logs = $auditTrail->getAuditLogs($filters, 100);
        echo json_encode(['logs' => $logs], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'log_event':
        $event_type = $_POST['event_type'];
        $description = $_POST['description'];
        
        $auditTrail->logEvent(
            $_SESSION['user_id'],
            $event_type,
            $description,
            $_SERVER['REMOTE_ADDR']
        );
        
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        echo json_encode(['error' => 'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
}
?>