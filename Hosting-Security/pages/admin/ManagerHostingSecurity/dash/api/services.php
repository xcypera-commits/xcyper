<?php
// api/services.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    
    case 'get_all':
        $stmt = $pdo->query("
            SELECT s.*, c.name as category_name, c.color as category_color 
            FROM services s 
            LEFT JOIN categories c ON s.category_id = c.id 
            ORDER BY s.id DESC
        ");
        $services = $stmt->fetchAll();
        
        // تحويل features من JSON إلى array
        foreach ($services as &$service) {
            $service['features'] = json_decode($service['features'], true);
        }
        
        echo json_encode(['success' => true, 'data' => $services]);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        
        if ($service) {
            $service['features'] = json_decode($service['features'], true);
            echo json_encode(['success' => true, 'data' => $service]);
        } else {
            echo json_encode(['success' => false, 'message' => 'الخدمة غير موجودة']);
        }
        break;
        
    case 'add':
        $data = [
            'name' => $_POST['name'],
            'category_id' => $_POST['category_id'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'setup_time' => $_POST['setup_time'] ?? '',
            'features' => json_encode(explode(',', $_POST['features'])),
            'sla' => $_POST['sla'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'popular' => isset($_POST['popular']) ? 1 : 0,
            'icon' => $_POST['icon'] ?? 'fa-server'
        ];
        
        $sql = "INSERT INTO services (name, category_id, description, price, setup_time, features, sla, status, popular, icon) 
                VALUES (:name, :category_id, :description, :price, :setup_time, :features, :sla, :status, :popular, :icon)";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($data)) {
            echo json_encode(['success' => true, 'message' => 'تمت إضافة الخدمة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الإضافة']);
        }
        break;
        
    case 'update':
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'category_id' => $_POST['category_id'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'setup_time' => $_POST['setup_time'] ?? '',
            'features' => json_encode(explode(',', $_POST['features'])),
            'sla' => $_POST['sla'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'popular' => isset($_POST['popular']) ? 1 : 0,
            'icon' => $_POST['icon'] ?? 'fa-server',
            'id' => $id
        ];
        
        $sql = "UPDATE services SET 
                name = :name, 
                category_id = :category_id, 
                description = :description, 
                price = :price, 
                setup_time = :setup_time, 
                features = :features, 
                sla = :sla, 
                status = :status, 
                popular = :popular, 
                icon = :icon 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($data)) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث الخدمة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التحديث']);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'تم حذف الخدمة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
}
?>