<?php
// includes/functions.php

require_once __DIR__ . '/../config/database.php';

// ============================================
// دوال الخدمات
// ============================================

function getAllServices() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT s.*, c.name as category_name, c.color as category_color 
        FROM services s 
        LEFT JOIN categories c ON s.category_id = c.id 
        ORDER BY s.id DESC
    ");
    return $stmt->fetchAll();
}

function getServiceById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addService($data) {
    global $pdo;
    $sql = "INSERT INTO services (name, category_id, description, price, setup_time, features, sla, status, popular, icon) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['name'],
        $data['category_id'],
        $data['description'],
        $data['price'],
        $data['setup_time'],
        json_encode($data['features']),
        $data['sla'],
        $data['status'],
        $data['popular'] ? 1 : 0,
        $data['icon'] ?? 'fa-server'
    ]);
}

function updateService($id, $data) {
    global $pdo;
    $sql = "UPDATE services SET 
            name = ?, category_id = ?, description = ?, price = ?, 
            setup_time = ?, features = ?, sla = ?, status = ?, popular = ?, icon = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['name'],
        $data['category_id'],
        $data['description'],
        $data['price'],
        $data['setup_time'],
        json_encode($data['features']),
        $data['sla'],
        $data['status'],
        $data['popular'] ? 1 : 0,
        $data['icon'] ?? 'fa-server',
        $id
    ]);
}

function deleteService($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// دوال الفئات
// ============================================

function getAllCategories() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT c.*, COUNT(s.id) as services_count 
        FROM categories c 
        LEFT JOIN services s ON c.id = s.category_id 
        GROUP BY c.id 
        ORDER BY c.id
    ");
    return $stmt->fetchAll();
}

function getCategoryById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addCategory($data) {
    global $pdo;
    $sql = "INSERT INTO categories (name, category_key, color) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$data['name'], $data['key'], $data['color']]);
}

function updateCategory($id, $data) {
    global $pdo;
    $sql = "UPDATE categories SET name = ?, category_key = ?, color = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$data['name'], $data['key'], $data['color'], $id]);
}

function deleteCategory($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// دوال طلبات العملاء
// ============================================

function getAllRequests() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT r.*, s.name as service_name 
        FROM client_requests r 
        LEFT JOIN services s ON r.service_id = s.id 
        ORDER BY r.created_at DESC
    ");
    return $stmt->fetchAll();
}

?>