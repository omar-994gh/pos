<?php
// public/order_details.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

// جلب المعرف
$id = intval($_GET['id'] ?? 0);
if (!$id) { echo "طلب غير صالح"; exit; }

// جلب بيانات الفاتورة
$stmt = $db->prepare("
  SELECT o.order_seq, o.created_at, o.total, u.username
    FROM Orders o
    JOIN Users u ON o.user_id=u.id
   WHERE o.id=:id
");
$stmt->execute([':id'=>$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// الأصناف
$stmt2 = $db->prepare("
  SELECT i.name_ar, oi.quantity, oi.unit_price, (oi.quantity*oi.unit_price) AS subtotal
    FROM Order_Items oi
    JOIN Items i ON oi.item_id=i.id
   WHERE oi.order_id=:id
");
$stmt2->execute([':id'=>$id]);
$items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <div class="card-header">
    <strong>تفاصيل فاتورة #<?= $order['order_seq'] ?></strong>
    <span class="float-end"><?= $order['created_at'] ?> بواسطة <?= htmlspecialchars($order['username']) ?></span>
  </div>
  <div class="card-body">
    <table class="table table-bordered">
      <thead>
        <tr><th>الصنف</th><th>كمية</th><th>سعر الوحدة</th><th>المجموع</th></tr>
      </thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['name_ar']) ?></td>
          <td><?= $it['quantity'] ?></td>
          <td><?= number_format($it['unit_price'],2) ?></td>
          <td><?= number_format($it['subtotal'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <h5 class="text-end">الإجمالي الكلي: <?= number_format($order['total'],2) ?></h5>
  </div>
</div>
