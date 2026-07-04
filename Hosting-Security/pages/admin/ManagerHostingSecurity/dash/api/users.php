<?php
// api/users.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    
    case 'get_all':
        $stmt = $pdo->query("SELECT id, username, full_name, email, role, created_at FROM users_login ORDER BY id DESC");
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $users]);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, created_at FROM users_login WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
        }
        break;
        
    case 'add':
        $username = $_POST['username'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'editor';
        
        // التحقق من عدم تكرار اسم المستخدم أو البريد
        $check = $pdo->prepare("SELECT id FROM users_login WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو البريد موجود بالفعل']);
            exit;
        }
        
        $sql = "INSERT INTO users_login (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$username, $password, $email, $full_name, $role])) {
            echo json_encode(['success' => true, 'message' => 'تمت إضافة المستخدم بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الإضافة']);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM users_login WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'تم حذف المستخدم بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
}
?>