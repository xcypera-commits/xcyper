<?php
// api/categories.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    
    case 'get_all':
        $stmt = $pdo->query("
            SELECT c.*, COUNT(s.id) as services_count 
            FROM categories c 
            LEFT JOIN services s ON c.id = s.category_id 
            GROUP BY c.id 
            ORDER BY c.id
        ");
        $categories = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $categories]);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        
        if ($category) {
            echo json_encode(['success' => true, 'data' => $category]);
        } else {
            echo json_encode(['success' => false, 'message' => 'الفئة غير موجودة']);
        }
        break;
        
    case 'add':
        $data = [
            'name' => $_POST['name'],
            'category_key' => $_POST['key'],
            'color' => $_POST['color']
        ];
        
        $sql = "INSERT INTO categories (name, category_key, color) VALUES (:name, :category_key, :color)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($data)) {
            echo json_encode(['success' => true, 'message' => 'تمت إضافة الفئة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الإضافة']);
        }
        break;
        
    case 'update':
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'category_key' => $_POST['key'],
            'color' => $_POST['color'],
            'id' => $id
        ];
        
        $sql = "UPDATE categories SET name = :name, category_key = :category_key, color = :color WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($data)) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث الفئة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التحديث']);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'تم حذف الفئة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
}
?>