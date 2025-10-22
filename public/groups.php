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

$groups   = $groupModel->all();
?>
<?php include 'header.php'; ?>

<h2>إدارة مجموعات الأصناف</h2>
<a href="group_form.php" class="btn btn-success mb-3">+ إضافة مجموعة جديدة</a>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>اسم المجموعة</th>
      <th>طابعة مرتبطة</th>
      <th>إجراءات</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($groups as $g): ?>
      <tr>
        <td><?= htmlspecialchars($g['id']) ?></td>
        <td><?= htmlspecialchars($g['name']) ?></td>
        <td><?= htmlspecialchars($g['printer_name'] ?? '—') ?></td>
        <td>
          <a href="group_form.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
          <form action="group_delete.php?id=<?= $g['id'] ?>" method="post" style="display:inline" onsubmit="return confirm('هل تريد حذف هذه المجموعة؟');">
            <button type="submit" class="btn btn-sm btn-danger" data-auth="btn_delete_item">حذف</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</main>
<script src="../assets/bootstrap.min.js"></script>
</body>
</html>
