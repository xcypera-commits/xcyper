<?php
require_once __DIR__ . '/../../includes/security/security-init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../includes/security/monitoring/ActivityMonitor.php';
require_once __DIR__ . '/../../includes/security/monitoring/ThreatDetector.php';

$db = new PDO('mysql:host=localhost;dbname=hosting_security', 'root', '');
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_activity':
        if (!AccessControl::checkAccess($_SESSION['user_role'], 'security_monitor')) {
            echo json_encode(['error' => 'صلاحية غير كافية'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $activityMonitor = new ActivityMonitor();
        $activities = $activityMonitor->getRecentActivities(50);
        
        echo json_encode(['activities' => $activities], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'get_threats':
        if (!AccessControl::checkAccess($_SESSION['user_role'], 'security_monitor')) {
            echo json_encode(['error' => 'صلاحية غير كافية'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $threatDetector = new ThreatDetector($db);
        
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'severity' => $_GET['severity'] ?? null,
            'status' => $_GET['status'] ?? 'new'
        ];
        
        $threats = $threatDetector->getThreats($filters, 100);
        echo json_encode(['threats' => $threats], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'log_event':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $activityMonitor = new ActivityMonitor();
        $activityMonitor->logActivity(
            $_SESSION['user_id'],
            $data['type'] ?? 'unknown',
            json_encode($data['details'] ?? [])
        );
        
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'get_stats':
        $stats = [
            'today' => [
                'logins' => rand(10, 50),
                'failed_logins' => rand(1, 5),
                'threats' => rand(0, 3),
                'alerts' => rand(0, 2)
            ],
            'week' => [
                'logins' => rand(100, 300),
                'failed_logins' => rand(10, 30),
                'threats' => rand(5, 20),
                'alerts' => rand(2, 10)
            ]
        ];
        
        echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        echo json_encode(['error' => 'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
}
?>