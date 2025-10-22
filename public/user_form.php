<?php
// public/user_form.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$userModel = new User($db);
$isEdit = isset($_GET['id']);
$user = null;
if ($isEdit) {
    $user = $userModel->find((int)$_GET['id']);
    if (!$user) {
        die('المستخدم غير موجود');
    }
}
?>
<?php include 'header.php'; ?>

<h2><?= $isEdit ? 'تعديل مستخدم' : 'إضافة مستخدم جديد' ?></h2>
<form action="user_save.php" method="post">
  <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $user['id'] ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label>اسم المستخدم</label>
    <input type="text" name="username" class="form-control" required
           value="<?= $isEdit ? htmlspecialchars($user['username']) : '' ?>">
  </div>

  <?php if (!$isEdit): // عند الإضافة فقط تطلب كلمة المرور ?>
    <div class="mb-3">
      <label>كلمة المرور</label>
      <input type="password" name="password" class="form-control" required>
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <label>الدور</label>
    <select name="role" class="form-select" required>
      <?php
        $roles = ['Admin', 'Cashier'];
        foreach ($roles as $role):
      ?>
      <option value="<?= $role ?>"
        <?= $isEdit && $user['role'] === $role ? 'selected' : '' ?>>
        <?= $role === 'Admin' ? 'مشرف' : 'كاشير' ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة المستخدم' ?>
  </button>
  <a href="users.php" class="btn btn-secondary">إلغاء</a>
</form>

</main>
<script src="../assets/bootstrap.min.js"></script>
</body>
</html>
