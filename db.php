<?php
$host = "localhost";
$user = "root";
$pass = "12345678"; // اتركه فارغاً إذا كنت تستخدم XAMPP/AppServ الافتراضي، أو اكتب كلمة السر الخاصة بك
$dbname = "glaktarbo"; // تم التحديث لاسم قاعدة البيانات الجديدة

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>