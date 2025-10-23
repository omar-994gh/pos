<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

$tables = [
  'Items' => 'الأصناف',
  'Groups' => 'المجموعات',
  'Printers' => 'الطابعات',
  'Orders' => 'الفواتير (مع بنودها)',
  'Order_Items' => 'بنود الفواتير',
  'Warehouses' => 'المستودعات',
  'Warehouse_Invoices' => 'فواتير المخزن',
  'Warehouse_Invoice_Items' => 'بنود فواتير المخزن',
  'System_Settings' => 'إعدادات النظام',
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $selected = $_POST['tables'] ?? [];
  if (!empty($selected)) {
    try {
      $db->beginTransaction();
      foreach ($selected as $t) {
        if ($t === 'Orders') { $db->exec('DELETE FROM Order_Items'); $db->exec('DELETE FROM Orders'); }
        else if ($t === 'Warehouse_Invoices') { $db->exec('DELETE FROM Warehouse_Invoice_Items'); $db->exec('DELETE FROM Warehouse_Invoices'); }
        else { $db->exec("DELETE FROM $t"); }
      }
      $db->commit();
      $message = 'تم تفريغ البيانات المحددة بنجاح';
    } catch (Exception $e) {
      $db->rollBack();
      $message = 'فشل التفريغ: ' . $e->getMessage();
    }
  }
}

include 'header.php';
?>
<main class="container mt-4">
  <h2>تفريغ البيانات</h2>
  <hr class="mb-3">
  <?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <form method="post" onsubmit="return confirm('هل أنت متأكد من تفريغ الجداول المحددة؟ لا يمكن التراجع.');">
    <div class="row g-3">
      <?php foreach ($tables as $key => $label): ?>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="tables[]" id="t_<?= $key ?>" value="<?= $key ?>">
            <label class="form-check-label mr-3" for="t_<?= $key ?>"><?= htmlspecialchars($label) ?></label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-danger mt-3">تفريغ المحدد</button>
    <a href="settings.php" class="btn btn-secondary mt-3">عودة</a>
  </form>
<?php include 'footer.php'; ?>
</main>