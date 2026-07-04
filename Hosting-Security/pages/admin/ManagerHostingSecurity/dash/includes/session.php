<?php
// includes/session.php
/*
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// جلب معلومات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if (!$current_user) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>