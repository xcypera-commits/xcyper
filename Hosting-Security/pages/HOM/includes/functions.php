<?php
// includes/functions.php

// دالة لجلب جميع الخدمات
function getAllServices() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT s.*, c.name as category_name, c.category_key, c.color 
        FROM services s 
        LEFT JOIN categories c ON s.category_id = c.id 
        WHERE s.status = 'active' 
        ORDER BY s.popular DESC, s.id DESC
    ");
    return $stmt->fetchAll();
}

// دالة لجلب جميع الفئات
function getAllCategories() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT c.*, COUNT(s.id) as services_count 
        FROM categories c 
        LEFT JOIN services s ON c.id = s.category_id AND s.status = 'active'
        GROUP BY c.id
        ORDER BY c.id
    ");
    return $stmt->fetchAll();
}

// دالة لجلب الإحصائيات
function getStats() {
    global $pdo;
    
    $stats = [];
    
    // إجمالي الخدمات
    $stmt = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'active'");
    $stats['total_services'] = $stmt->fetchColumn();
    
    // إجمالي الطلبات
    $stmt = $pdo->query("SELECT COUNT(*) FROM client_requests");
    $stats['total_requests'] = $stmt->fetchColumn();
    
    // إجمالي الفئات
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $stats['total_categories'] = $stmt->fetchColumn();
    
    // الخدمات النشطة
    $stmt = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'active'");
    $stats['active_services'] = $stmt->fetchColumn();
    
    return $stats;
}

// دالة لجلب خدمة محددة
function getServiceById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
?>