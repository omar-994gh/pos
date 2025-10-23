<?php
require_once __DIR__.'/../src/init.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/warehouse_management.php';
Auth::requireLogin(); if (!Auth::isAdmin()) exit;

$model = new Warehouse($db);
$isEdit = !empty($_GET['id']);
$data   = $isEdit ? $model->find((int)$_GET['id']) : ['name'=>''];
include 'header.php';
?>
<main class="container mt-4">
  <h2><?= $isEdit?'تعديل مخزن':'إضافة مخزن جديد' ?></h2>
  <form method="post" action="warehouse_save.php">
    <?php if($isEdit): ?>
      <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <?php endif; ?>
    <div class="mb-3">
      <label>اسم المخزن</label>
      <input name="name" class="form-control" required value="<?= htmlspecialchars($data['name']) ?>">
    </div>
    <button class="btn btn-success"><?= $isEdit?'حفظ':'إضافة' ?></button>
    <a href="warehouses.php" class="btn btn-secondary">إلغاء</a>
  </form>
<?php include 'footer.php'; ?>
</main>
