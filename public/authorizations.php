<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

// Ensure table exists
db_maybe_create($db);
function db_maybe_create(PDO $db): void {
	$db->exec("CREATE TABLE IF NOT EXISTS Authorizations (user_id INTEGER NOT NULL, element_key TEXT NOT NULL, is_enabled INTEGER NOT NULL DEFAULT 1, PRIMARY KEY(user_id, element_key), FOREIGN KEY(user_id) REFERENCES Users(id) ON DELETE CASCADE)");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$userId = (int)($_POST['user_id'] ?? 0);
	$elements = $_POST['elements'] ?? [];
	$db->prepare('DELETE FROM Authorizations WHERE user_id = :uid')->execute([':uid'=>$userId]);
	$ins = $db->prepare('INSERT INTO Authorizations (user_id, element_key, is_enabled) VALUES (:uid, :ek, :en)');
	foreach ($elements as $ek => $val) { $ins->execute([':uid'=>$userId, ':ek'=>$ek, ':en'=>1]); }
	header('Location: authorizations.php?user_id='.$userId); exit;
}

$userModel = new User($db);
$users = $userModel->all();
$currentUserId = (int)($_GET['user_id'] ?? ($users[0]['id'] ?? 0));
$availableElements = [
	'btn_add_item' => 'إضافة عنصر',
	'btn_delete_item' => 'حذف عنصر',
	'btn_checkout' => 'المبيع',
	'nav_settings' => 'الإعدادات',
	'nav_users' => 'المستخدمون',
	'nav_warehouse' => 'المستودعات',
	'input_edit_price' => 'مربع تعديل الأسعار الفوري',
	'input_discount' => 'مربع إضافة خصم',
];

$existing = [];
if ($currentUserId) {
	$stmt = $db->prepare('SELECT element_key FROM Authorizations WHERE user_id = :uid');
	$stmt->execute([':uid'=>$currentUserId]);
	$existing = array_column($stmt->fetchAll(), 'element_key');
}

include 'header.php';
?>
<main class="container mt-4">
  <h2>صلاحيات العناصر</h2>
  <form method="get" class="mb-3">
    <label>اختر المستخدم</label>
    <select name="user_id" class="form-select" onchange="this.form.submit()">
      <?php foreach($users as $u): ?>
      <option value="<?= $u['id'] ?>" <?= $u['id']===$currentUserId?'selected':'' ?>><?= htmlspecialchars($u['username']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <hr>
  <form method="post" class="pt-4">
    <label class="mb-3">اختر الصلاحيات المناسبة:</label>
    <input type="hidden" name="user_id" value="<?= $currentUserId ?>">
    <div class="row g-3">
      <?php foreach($availableElements as $key => $label): ?>
      <div class="col-md-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="elements[<?= $key ?>]" id="<?= $key ?>" value="1" <?= in_array($key, $existing)?'checked':'' ?>>
          <label class="form-check-label pr-3" for="<?= $key ?>"><?= htmlspecialchars($label) ?></label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3">حفظ</button>
  </form>
</main>
</body>
</html>