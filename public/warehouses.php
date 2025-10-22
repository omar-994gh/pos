<?php
require_once __DIR__.'/../src/init.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/warehouse_management.php';
Auth::requireLogin(); if (!Auth::isAdmin()) exit;

$model = new Warehouse($db);
$warehouses = $model->all();
include 'header.php';
?>
<main class="container mt-4">
  <h2>إدارة المخازن</h2>
  <a href="warehouse_form.php" class="btn btn-success mb-3">+ إضافة مخزن</a>
  <table class="table">
    <thead><tr><th>#</th><th>الاسم</th><th>إجراءات</th></tr></thead>
    <tbody>
      <?php foreach($warehouses as $w): ?>
      <tr>
        <td><?= $w['id'] ?></td>
        <td><?= htmlspecialchars($w['name']) ?></td>
        <td>
          <a href="warehouse_form.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
          <a href="warehouse_delete.php?id=<?= $w['id'] ?>"
             onclick="return confirm('حذف؟')" class="btn btn-sm btn-danger">حذف</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
