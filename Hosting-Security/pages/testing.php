<?php
// create_password.php
$password = 'Staff@123';
$hashed = password_hash($password, PASSWORD_DEFAULT);

echo "كلمة المرور الأصلية: " . $password . "<br>";
echo "كلمة المرور المشفرة: " . $hashed . "<br>";
echo "<br>انسخ هذا الهاش واستخدمه في SQL:<br>";
echo "'" . $hashed . "'";
?>