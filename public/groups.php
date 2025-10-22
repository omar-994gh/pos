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

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'visibility_toggled'): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    تم تغيير حالة الظهور بنجاح!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>اسم المجموعة</th>
      <th>طابعة مرتبطة</th>
      <th>الحالة في POS</th>
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
          <?php 
            $visible = isset($g['visible']) ? (int)$g['visible'] : 1;
            if ($visible === 1): 
          ?>
            <span class="badge bg-success">ظاهرة</span>
          <?php else: ?>
            <span class="badge bg-secondary">مخفية</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="group_form.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
          <a href="group_toggle_visibility.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-<?= $visible === 1 ? 'warning' : 'info' ?>" onclick="return confirm('هل تريد تغيير حالة ظهور هذه المجموعة في واجهة POS؟');">
            <?= $visible === 1 ? 'إخفاء' : 'إظهار' ?>
          </a>
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
