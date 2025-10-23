<?php
// public/db_backup_handler.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// 1) مسار المجلد الوجهة من النموذج
$destDir = trim($_POST['dest_dir'] ?? '');
if ($destDir === '') {
    die('❌ الرجاء إدخال مسار مجلد الوجهة.');
}

// 2) تحديد ملف الـ SQLite الأصلي (من DB_PATH أو db.sqlite أو pos.db)
$src = getenv('DB_PATH') ?: '';
if (!$src || !file_exists($src)) {
    $base = realpath(__DIR__ . '/../config/');
    foreach (['config.sqlite','pos.db'] as $fn) {
        if (file_exists("$base/$fn")) { $src = "$base/$fn"; break; }
    }
}
if (! $src || ! is_readable($src)) {
    die('❌ لم أتمكن من العثور على ملف قاعدة البيانات أو قراءته.');
}

// 3) إنشاء المجلد الوجهة إذا لم يكن موجودًا
if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0777, true)) {
        die('❌ فشل في إنشاء المجلد الوجهة: ' . htmlspecialchars($destDir));
    }
}

// 4) مجلد النسخ الفرعي
$backupDir = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'backup';
if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0777, true)) {
        die('❌ فشل في إنشاء المجلد الفرعي للنسخ: ' . htmlspecialchars($backupDir));
    }
}

// 5) بناء اسم الملف الهدف مع الطابع الزمني
$fileName    = pathinfo($src, PATHINFO_FILENAME);
$fileExt     = pathinfo($src, PATHINFO_EXTENSION);
$timeStamp   = date('Ymd-His');
$destFile    = "$backupDir/{$fileName}-{$timeStamp}.{$fileExt}";

// 6) النسخ
if (!@copy($src, $destFile)) {
    die('❌ فشل في نسخ قاعدة البيانات إلى: ' . htmlspecialchars($destFile));
}

// 7) عرض النجاح
?>
<?php include 'header.php'; ?>

<main class="container mt-5">
  <div class="alert alert-success text-center">
    ✅ تم إنشاء النسخة الاحتياطية بنجاح في:<br>
    <code><?= htmlspecialchars($destFile) ?></code>
  </div>
  <div class="text-center">
    <a href="db_backup.php" class="btn btn-secondary">عودة</a>
  </div>
<?php include 'footer.php'; ?>
</main>
