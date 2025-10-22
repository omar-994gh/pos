<?php
// public/orders_report.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isAdmin() && !Auth::isCashier()) { header('Location: dashboard.php'); exit; }

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

$stmt = $db->prepare("
    SELECT
    o.created_at   AS sale_date,
    o.order_seq    AS invoice_no,
    i.name_ar      AS item_name,
    oi.quantity,
    oi.unit_price,
    (oi.quantity * oi.unit_price) AS subtotal
    FROM Order_Items oi
    JOIN Orders o ON oi.order_id = o.id
    JOIN Items i  ON oi.item_id  = i.id
    WHERE DATE(o.created_at) BETWEEN :from AND :to
    ORDER BY o.created_at, o.order_seq
");
$stmt->execute([':from'=>$dateFrom,':to'=>$dateTo]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'header.php'; ?>

<style>
  body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: none; background: url('images/logo.png') center center no-repeat; background-size: 40%; opacity: 0.1; z-index: -1; pointer-events: none; }
  .on-print { display: none; }
  @media print { .no-print { display: none !important; } .on-print { display: block !important; } }
</style>
  <div style="text-align: center; margin-bottom: 30px;">
    <h3 class="on-print">فاتورة مبيعات</h3>
  </div>
  <div class="d-flex justify-content-between align-items-center no-print mb-3">
    <h3>سجل المبيعات</h3>
    <button onclick="window.print()" class="btn btn-outline-primary">🖨 طباعة</button>
  </div>

  <form method="get" class="row g-2 no-print mb-4">
    <div class="col-auto">
      <label>من</label>
      <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
    </div>
    <div class="col-auto">
      <label>إلى</label>
      <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-primary">فلترة</button>
    </div>
  </form>
  <div class="alert alert-success on-print">تمت الطباعة بواسطة نظام إدارة نقاط المبيع POS</div>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>التاريخ</th>
        <th>رقم الفاتورة</th>
        <th>الصنف</th>
        <th>الكمية</th>
        <th>سعر الوحدة</th>
        <th>المجموع</th>
      </tr>
    </thead>
    <tbody>
      <?php $grandTotal = 0; foreach ($orders as $r): $grandTotal += $r['subtotal']; ?>
      <tr>
        <td><?= $r['sale_date'] ?></td>
        <td><?= $r['invoice_no'] ?></td>
        <td><?= htmlspecialchars($r['item_name']) ?></td>
        <td><?= $r['quantity'] ?></td>
        <td><?= number_format($r['unit_price'],2) ?></td>
        <td><?= number_format($r['subtotal'],2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="5" class="text-end">الإجمالي الكلي:</th>
        <th><?= number_format($grandTotal,2) ?></th>
      </tr>
    </tfoot>
  </table>

  <div id="details" class="mt-4"></div>
</main>
<script>
async function loadDetails(orderId) {
  const resp = await fetch(`order_details.php?id=${orderId}`);
  const html = await resp.text();
  document.getElementById('details').innerHTML = html;
  window.scrollTo(0, document.getElementById('details').offsetTop);
}
</script>
