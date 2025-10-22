<?php
// public/db_backup.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}
?>
<?php include 'header.php'; ?>

<main class="container mt-5">
  <div class="card mx-auto" style="max-width: 600px;">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">نسخ احتياطي لقاعدة البيانات</h5>
    </div>
    <div class="card-body">
      <form action="db_backup_handler.php" method="post">
        <div class="mb-3">
          <label for="dest_dir" class="form-label">المجلد الوجهة للنسخ الاحتياطي</label>
          <input type="text" class="form-control" id="dest_dir" name="dest_dir"
                 placeholder="مثلاً: C:\backup أو /home/user/backups" required>
          <div class="form-text">أدخل مسار المجلد فقط؛ سيتم إنشاء مجلد <code>backup</code> داخله.</div>
        </div>
        <button type="submit" class="btn btn-success w-100">إنشاء النسخة الاحتياطية</button>
      </form>
    </div>
  </div>
</main>
