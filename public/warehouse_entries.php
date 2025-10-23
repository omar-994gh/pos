<?php
// public/warehouse_entries.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/WarehouseInvoice.php';
require_once __DIR__ . '/../src/ExchangeRate.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$model   = new WarehouseInvoice($db);
$exchangeRateManager = new ExchangeRate($db);
$exchangeSettings = $exchangeRateManager->getSystemSettings();

// إذا كان هناك طلبية حذف فاتورة (invoice_id) فننفذ الحذف
if (!empty($_GET['delete_invoice'])) {
    $toDeleteId = (int)$_GET['delete_invoice'];
    $model->deleteInvoice($toDeleteId);
    // أعِد التوجيه لنفس الصفحة بدون معلمة delete_invoice
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: {$base}");
    exit;
}

$filters = [
  'supplier'  => $_GET['supplier']  ?? '',
  'date_from' => $_GET['date_from'] ?? '',
  'date_to'   => $_GET['date_to']   ?? '',
  'item_name' => $_GET['item_name'] ?? '',
];

$entries = $model->all($filters);
?>
<?php include 'header.php'; ?>

<main class="container mt-4">
  <h2>سجل فواتير المخزن</h2>

  <!-- فلترة -->
  <form class="row gy-2 gx-3 align-items-center mb-3">
    <div class="col-auto">
      <input type="text" name="supplier" class="form-control" placeholder="المورد"
             value="<?= htmlspecialchars($filters['supplier']) ?>">
    </div>
    <div class="col-auto">
      <input type="date" name="date_from" class="form-control"
             value="<?= htmlspecialchars($filters['date_from']) ?>">
    </div>
    <div class="col-auto">
      <input type="date" name="date_to" class="form-control"
             value="<?= htmlspecialchars($filters['date_to']) ?>">
    </div>
    <div class="col-auto">
      <input type="text" name="item_name" class="form-control" placeholder="اسم المادة"
             value="<?= htmlspecialchars($filters['item_name']) ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">فلترة</button>
    </div>
  </form>
  <div>
    <a href="warehouse_entry_form.php?type=IN" class="btn btn-success mb-3">فاتورة إدخال</a>
    <a href="warehouse_entry_form.php?type=OUT" class="btn btn-danger mb-3">فاتورة إخراج</a>
  </div>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th>
        <th>المخزن</th>
        <th>المورد</th>
        <th>التاريخ</th>
        <th>عدد البنود</th>
        <th>الإجمالي</th>
        <th>النوع</th>
        <th>إجراءات</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entries as $e): ?>
      <tr>
        <td><?= $e['id'] ?></td>
        <td><?= htmlspecialchars($e['warehouse']) ?></td>
        <td><?= htmlspecialchars($e['supplier']) ?></td>
        <td><?= htmlspecialchars($e['date']) ?></td>
        <td><?= $e['items_count'] ?></td>
        <td><?= number_format($e['invoice_total'],2) ?> <?= htmlspecialchars($exchangeSettings['base_currency']) ?></td>
        <td><?= $e['entry_type']==='IN'?'إدخال':'إخراج' ?></td>
        <td>
          <a href="warehouse_entry_view.php?id=<?= $e['id'] ?>"
             class="btn btn-sm btn-info">
             عرض
          </a>
          <a href="?delete_invoice=<?= $e['id'] ?>"
             class="btn btn-sm btn-danger"
             data-auth="btn_delete_item"
             onclick="return confirm('هل أنت متأكد من حذف الفاتورة وكل بنودها؟');">
            حذف
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php include 'footer.php'; ?>
</main>
