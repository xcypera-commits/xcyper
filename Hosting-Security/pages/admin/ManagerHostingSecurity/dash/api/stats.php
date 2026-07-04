<?php
// api/stats.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$stats = [];

// إجمالي الخدمات
$stmt = $pdo->query("SELECT COUNT(*) FROM services");
$stats['total_services'] = $stmt->fetchColumn();

// إجمالي الفئات
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// إجمالي الطلبات
$stmt = $pdo->query("SELECT COUNT(*) FROM client_requests");
$stats['total_requests'] = $stmt->fetchColumn();

// إجمالي المستخدمين
$stmt = $pdo->query("SELECT COUNT(*) FROM users_login");
$stats['total_users'] = $stmt->fetchColumn();

// آخر الخدمات
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, c.color as category_color 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    ORDER BY s.id DESC LIMIT 5
");
$stats['recent_services'] = $stmt->fetchAll();

// آخر الطلبات
$stmt = $pdo->query("SELECT * FROM client_requests ORDER BY created_at DESC LIMIT 5");
$stats['recent_requests'] = $stmt->fetchAll();

// توزيع الفئات
$stmt = $pdo->query("
    SELECT c.*, COUNT(s.id) as services_count 
    FROM categories c 
    LEFT JOIN services s ON c.id = s.category_id 
    GROUP BY c.id
");
$stats['categories_distribution'] = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $stats]);
?>