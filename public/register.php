<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = Auth::register($db, $username, $password);
    if ($result === true) {
        header('Location: login.php');
        exit;
    } else {
        $error = $result; // رسالة الخطأ
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل مستخدم جديد</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="p-5">
    <div class="container" style="max-width:400px;">
        <h2 class="mb-4 text-center">تسجيل جديد</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="username" class="form-label">اسم المستخدم</label>
                <input type="text" name="username" id="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">تسجيل</button>
        </form>
        <p class="mt-3 text-center">
            لديك حساب؟ <a href="login.php">تسجيل الدخول</a>
        </p>
    </div>
</body>
</html>
