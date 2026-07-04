<?php
session_start();

// تدمير الجلسة بالكامل
$_SESSION = [];
session_unset();
session_destroy();

// حذف كوكي الجلسة من المتصفح
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// التوجيه لصفحة login
header('Location: login.php');
exit();
?>