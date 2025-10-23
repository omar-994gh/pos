<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Group.php';
require_once __DIR__ . '/../src/Printer.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$groupModel   = new Group($db);
$printerModel = new Printer($db);

$isEdit = isset($_GET['id']);
$group  = $isEdit ? $groupModel->find((int)$_GET['id']) : null;
$printers = $printerModel->all();
?>
<?php include 'header.php'; ?>

<h2><?= $isEdit ? 'تعديل مجموعة' : 'إضافة مجموعة جديدة' ?></h2>
<form action="group_save.php" method="post">
  <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $group['id'] ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label>اسم المجموعة</label>
    <input type="text" name="name" class="form-control" required
           value="<?= $isEdit ? htmlspecialchars($group['name']) : '' ?>">
  </div>

  <div class="mb-3">
    <label>طابعة مرتبطة</label>
    <select name="printer_id" class="form-select">
      <option value="">— لا شيء —</option>
      <?php foreach ($printers as $p): ?>
        <option value="<?= $p['id'] ?>"
          <?= $isEdit && $group['printer_id'] == $p['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['address']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?>
  </button>
  <a href="groups.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include 'footer.php'; ?>
</main>
<script src="../assets/bootstrap.min.js"></script>
</body>
</html>
