<?php
// api/requests.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    
    case 'get_all':
        $stmt = $pdo->query("
            SELECT * FROM client_requests 
            ORDER BY created_at DESC
        ");
        $requests = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $requests]);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM client_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if ($request) {
            echo json_encode(['success' => true, 'data' => $request]);
        } else {
            echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
        }
        break;
        
    case 'update_status':
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE client_requests SET status = ? WHERE id = ?");
        
        if ($stmt->execute([$status, $id])) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التحديث']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
}
?>