<?php
// Manage creation and update of receipt printers (network or USB)
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Printer.php';

Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

$printerModel = new Printer($db);
$isEdit       = isset($_GET['id']);
$printer      = $isEdit ? $printerModel->find((int)$_GET['id']) : null;
?>
<?php include 'header.php'; ?>

<h2><?= $isEdit ? 'تعديل طابعة' : 'إضافة طابعة جديدة' ?></h2>
<form action="printer_save.php" method="post">
  <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $printer['id'] ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label>اسم الطابعة</label>
    <input type="text" name="name" class="form-control" required value="<?= $isEdit ? htmlspecialchars($printer['name']) : '' ?>">
  </div>

  <div class="mb-3">
    <label>العنوان (IP أو USB path)</label>
    <input type="text" name="address" class="form-control" required value="<?= $isEdit ? htmlspecialchars($printer['address']) : '' ?>">
  </div>
  
  <label>النوع</label>
  <select name="printer_type" class="form-control" required>
    <option value="wifi" <?= $isEdit && $printer['type']==='wifi'?'selected':'' ?>>شبكة</option>
    <option value="usb"  <?= $isEdit && $printer['type']==='usb' ?'selected':'' ?>>كبل USB</option>
  </select>

  <button type="submit" class="btn btn-primary mt-3">
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?>
  </button>
  <a href="printers.php" class="btn btn-secondary mt-3">إلغاء</a>
</form>

<?php include 'footer.php'; ?>
</main>
<script src="../assets/bootstrap.min.js"></script>
</body>
</html>
