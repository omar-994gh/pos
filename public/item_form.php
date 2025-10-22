<?php
// public/item_form.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Group.php';
require_once __DIR__ . '/../src/Item.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$groupModel = new Group($db);
$itemModel  = new Item($db);

$groups = $groupModel->all();
$isEdit = isset($_GET['id']);
$item   = $isEdit ? $itemModel->find((int)$_GET['id']) : null;
$groupId = $isEdit
    ? (int)$item['group_id']
    : (int)($_GET['group_id'] ?? 0);
?>
<?php include 'header.php'; ?>

<h2><?= $isEdit ? 'تعديل صنف' : 'إضافة صنف جديد' ?></h2>
<form action="item_save.php" method="post">
  <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $item['id'] ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label>المجموعة</label>
    <select name="group_id" class="form-control" required>
      <option value="">— اختر —</option>
      <?php foreach ($groups as $g): ?>
        <option value="<?= $g['id'] ?>"
          <?= $g['id']===$groupId ? 'selected' : '' ?>>
          <?= htmlspecialchars($g['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label>الاسم (عربي)</label>
    <input type="text" name="name_ar" class="form-control" required
           value="<?= $isEdit ? htmlspecialchars($item['name_ar']) : '' ?>">
  </div>

  <div class="mb-3">
    <label>الاسم (إنجليزي)</label>
    <input type="text" name="name_en" class="form-control"
           value="<?= $isEdit ? htmlspecialchars($item['name_en']) : '' ?>">
  </div>

  <div class="mb-3">
    <label>باركود</label>
    <div class="input-group">
      <input type="text" id="barcodeField" name="barcode" class="form-control"
            value="<?= $isEdit ? htmlspecialchars($item['barcode']) : '' ?>">
      <button type="button" id="generateBarcode" class="btn btn-outline-secondary">توليد باركود</button>
    </div>
  </div>

  <div class="mb-3">
    <label>السعر</label>
    <input type="number" step="0.01" name="price" class="form-control" required
           value="<?= $isEdit ? htmlspecialchars($item['price']) : '' ?>">
  </div>

  <div class="mb-3">
    <label>العملة</label>
    <select name="currency" class="form-select" required>
      <option value="SYP" <?= ($isEdit && $item['currency'] === 'SYP') ? 'selected' : '' ?>>ليرة سورية (SYP)</option>
      <option value="USD" <?= ($isEdit && $item['currency'] === 'USD') ? 'selected' : '' ?>>دولار أمريكي (USD)</option>
      <option value="TRY" <?= ($isEdit && $item['currency'] === 'TRY') ? 'selected' : '' ?>>ليرة تركية (TRY)</option>
    </select>
  </div>

  <div class="mb-3">
    <label>الكمية الابتدائية</label>
    <input type="number" step="0.01" name="stock" class="form-control" required
           value="<?= $isEdit ? htmlspecialchars($item['stock']) : '0' ?>">
  </div>

  <div class="mb-3">
    <label>الوحدة</label>
    <input type="text" name="unit" class="form-control"
           value="<?= $isEdit ? htmlspecialchars($item['unit']) : '' ?>">
  </div>

  <button type="submit" class="btn btn-primary" data-auth="btn_add_item">
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?>
  </button>
  <a href="items.php?group_id=<?= $groupId ?>" class="btn btn-secondary">إلغاء</a>
</form>

</main>
<script src="../assets/bootstrap.min.js"></script>
<script src="js/add_item.js"></script>
</body>
</html>
