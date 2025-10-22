<?php
// public/warehouse_entry_view.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/WarehouseInvoice.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: warehouse_entries.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$model = new WarehouseInvoice($db);

// 1) إذا وردت معلمة delete_item في العنوان، نحذف البند ثم نعيد التوجيه لنفس الصفحة بدون المعامل
if (!empty($_GET['delete_item'])) {
    $itemId = (int)$_GET['delete_item'];
    // نستدعي دالة الحذف
    $model->deleteItem($id, $itemId);
    // إعادة التوجيه لإزالة query string
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: {$base}?id={$id}");
    exit;
}

// جلب بيانات الفاتورة
$stmt = $db->prepare("SELECT wi.*, w.name AS warehouse
  FROM Warehouse_Invoices wi
  JOIN Warehouses w ON wi.warehouse_id = w.id
  WHERE wi.id = :id");
$stmt->execute([':id'=>$id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$inv) {
    die('فاتورة غير موجودة');
}

// جلب البنود
$items = $model->findItems($id);
?>
<?php include 'header.php'; ?>

<main class="container mt-4">
  <h2>عرض فاتورة #<?= $inv['id'] ?></h2>
  <p><strong>المخزن:</strong> <?= htmlspecialchars($inv['warehouse']) ?>
     &nbsp;|&nbsp; <strong>المورد:</strong> <?= htmlspecialchars($inv['supplier']) ?>
     &nbsp;|&nbsp; <strong>التاريخ:</strong> <?= htmlspecialchars($inv['date']) ?>
     &nbsp;|&nbsp; <strong>النوع:</strong> <?= $inv['entry_type']==='IN'?'إدخال':'إخراج' ?></p>

  <table class="table">
    <thead>
      <tr>
        <th>الصنف (عربي/إنجليزي)</th>
        <th>العدد</th>
        <th>سعر الوحدة</th>
        <th>الإجمالي</th>
        <th>الوحدة</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['name_ar']) ?><br><small><?= htmlspecialchars($it['name_en']) ?></small></td>
        <td><?= $it['quantity'] ?></td>
        <td><?= number_format($it['unit_price'],2) ?></td>
        <td><?= number_format($it['total_price'],2) ?></td>
        <td><?= htmlspecialchars($it['unit']) ?></td>
        <td class="no-print">
          <a href="?id=<?= $id ?>&delete_item=<?= $it['item_id'] ?>"
             class="btn btn-sm btn-danger"
             data-auth="btn_delete_item"
             onclick="return confirm('هل تريد حذف هذا البند؟');">
            حذف
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <button class="btn btn-primary" id="printBtn">طباعة</button>
  <a href="warehouse_entries.php" class="btn btn-secondary">عودة</a>
</main>
<script>
    document.getElementById('printBtn').addEventListener('click', () => {
    document.body.classList.add('print-mode');
    window.print();
    document.body.classList.remove('print-mode');
    });
</script>


