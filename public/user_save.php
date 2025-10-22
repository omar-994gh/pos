<?php
// public/user_save.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$userModel = new User($db);

$id       = $_POST['id'] ?? null;
$username = trim($_POST['username'] ?? '');
$role     = $_POST['role'] ?? '';

if ($id) {
    // تحديث (لا نغيّر كلمة المرور هنا)
    $success = $userModel->update((int)$id, $username, $role);
    if (!$success) {
        die('فشل في تحديث بيانات المستخدم');
    }
} else {
    // إضافة مستخدم جديد: نستعمل Auth::register
    $password = $_POST['password'] ?? '';
    $result = Auth::register($db, $username, $password, $role);
    if ($result !== true) {
        die("خطأ: $result");
    }
}

header('Location: users.php');
exit;
