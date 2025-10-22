<?php
// migrate_sqlite.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_db'], $_POST['new_db'])) {
    $oldDbPath = trim($_POST['old_db']);
    $newDbPath = trim($_POST['new_db']);
    $tables = $_POST['tables'] ?? [];

    if (!file_exists($oldDbPath)) {
        $error = "قاعدة البيانات القديمة غير موجودة.";
    } elseif (!file_exists($newDbPath)) {
        $error = "قاعدة البيانات الجديدة غير موجودة.";
    } elseif (empty($tables)) {
        $error = "الرجاء اختيار جدول واحد على الأقل.";
    } else {
        try {
            $oldDb = new PDO("sqlite:" . $oldDbPath);
            $newDb = new PDO("sqlite:" . $newDbPath);

            $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach ($tables as $table) {
                // جلب جميع الصفوف من الجدول القديم
                $rows = $oldDb->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);

                if ($rows) {
                    // جلب أعمدة الجدول القديم والجديد
                    $oldCols = $oldDb->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
                    $newCols = $newDb->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);

                    // التقاطع بين الأعمدة
                    $commonCols = array_intersect($oldCols, $newCols);

                    if (empty($commonCols)) {
                        echo "<div class='alert alert-warning'>⚠ لا يوجد أعمدة مشتركة في جدول $table</div>";
                        continue;
                    }

                    $colList = implode(",", $commonCols);
                    $placeholders = ":" . implode(",:", $commonCols);

                    $stmt = $newDb->prepare("INSERT OR IGNORE INTO $table ($colList) VALUES ($placeholders)");

                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($commonCols as $col) {
                            $data[":$col"] = $row[$col] ?? null;
                        }
                        $stmt->execute($data);
                    }
                }
            }

            $success = "تم نسخ البيانات بنجاح.";
        } catch (Exception $e) {
            $error = "حدث خطأ: " . $e->getMessage();
        }
    }
}

// جلب قائمة الجداول من قاعدة البيانات القديمة عند تحديد المسار
$availableTables = [];
if (isset($_POST['load_tables']) && !empty($_POST['old_db']) && file_exists($_POST['old_db'])) {
    try {
        $tempDb = new PDO("sqlite:" . $_POST['old_db']);
        $res = $tempDb->query("SELECT name FROM sqlite_master WHERE type='table'");
        $availableTables = $res->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $error = "تعذر قراءة الجداول: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>نسخ بيانات بين قواعد SQLite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="mt-5 bg-light">
<div class="container">
  <h2 class="mb-4">أداة نسخ بيانات النُسخ الاحتياطية</h2>
  <h6 style="color: red;" class="">⚠ احذر استخدام هذه الأداة إلا بعد استشارة المبرمج ولا تستخدمها أبداً</h6>
  <h6 style="color: red;" class="mb-4">استخدامها بشكل غير صحيح قد يؤدي لضياع باناتك بشكل دائم غير قابل للاسترداد</h6>

  <hr class="mb-3">

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm mb-3">
    <div class="mb-3">
      <label class="form-label">مسار قاعدة البيانات القديمة:</label>
      <input type="text" name="old_db" class="form-control" value="<?= htmlspecialchars($_POST['old_db'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">مسار قاعدة البيانات الجديدة:</label>
      <input type="text" name="new_db" class="form-control" value="<?= htmlspecialchars($_POST['new_db'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <button type="submit" name="load_tables" value="1" class="btn btn-secondary">تحميل الجداول</button>
    </div>

    <?php if ($availableTables): ?>
      <h6 style="color: red;" class="">إذا كُنت تعتقد أن البيانات المتوفرة في النسخة الاحتياطية ضخمة، قم بنسخ الجداول بالتسلسل</h6>
      <h6 style="color: red;" class="">مثلاً: انسخ جدول items, orders ثم orders_items, warehouse وهكذا..</h6>
      <h6 style="color: red;" class="mb-4">تأكد من إفراغ قاعدة البيانات الحالية (الوجهة)</h6>
      <div class="mb-3" dir="ltr">
        <label style="text-align: right;" class="form-label">اختر الجداول المراد نسخها:</label>
        <?php foreach ($availableTables as $table): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="tables[]" value="<?= htmlspecialchars($table) ?>" id="t<?= $table ?>">
            <label class="form-check-label" for="t<?= $table ?>"><?= htmlspecialchars($table) ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary">بدء النسخ</button>
    <?php endif; ?>
  </form>
</div>
</body>
</html>
