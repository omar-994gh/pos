<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

$stmt = $db->prepare("SELECT * FROM System_Settings ORDER BY id DESC LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$restaurantName   = $settings['restaurant_name']   ?? '';
$logoPath         = $settings['logo_path']         ?? '';
$taxNumber        = $settings['tax_number']        ?? '';
$address          = $settings['address']           ?? '';
$taxRate          = $settings['tax_rate']          ?? 0;
$printWidthMm     = $settings['print_width_mm']    ?? 80;
$currency         = $settings['currency']          ?? 'SYP';
$fontSizeTitle    = (int)($settings['font_size_title'] ?? 22);
$fontSizeItem     = (int)($settings['font_size_item'] ?? 16);
$fontSizeTotal    = (int)($settings['font_size_total'] ?? 18);
$fontSizeReportTitle = (int)($settings['font_size_report_title'] ?? 20);
$fontSizeReportItem  = (int)($settings['font_size_report_item'] ?? 14);
$fontSizeReportTotal = (int)($settings['font_size_report_total'] ?? 16);

// Exchange rate settings
$exchangeRateEnabled = (int)($settings['exchange_rate_enabled'] ?? 0);
$baseCurrency        = $settings['base_currency'] ?? 'SYP';
$usdToSypRate        = (float)($settings['usd_to_syp_rate'] ?? 15000.0);
$tryToSypRate        = (float)($settings['try_to_syp_rate'] ?? 500.0);

$printFields = [
  'field_item_name_ar',
  'field_item_name_en',
  'field_tax_number',
  'field_username',
  'field_restaurant_name',
  'field_restaurant_logo',
];
$fieldValues = [];
foreach ($printFields as $f) { $fieldValues[$f] = (!empty($settings[$f]) && $settings[$f] == 1) ? 1 : 0; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إعدادات النظام</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include 'header.php'; ?>
  <main class="container mt-4 mb-4">
    <div class="d-flex justify-content-between align-content-left mt-4 mb-4">
      <div>
        <h2>إعدادات النظام</h2>
      </div>
      <div>
        <a href="db_backup.php" class="btn btn-sm btn-outline-danger mx-1">النسخ الاحتياطي</a>
        <a href="authorizations.php" class="btn btn-sm btn-outline-dark mx-1">إدارة الصلاحيات</a>
        <a href="reset_data.php" class="btn btn-sm btn-outline-danger mx-1">تفريغ البيانات…</a>
        <a href="users.php" class="btn btn-sm btn-outline-secondary mx-1">المستخدمين</a>
        <a href="authorizations.php" class="btn btn-sm btn-outline-info mx-1">الصلاحيات</a>
        <a href="../helpers/migrate_sqlite.php" class="btn btn-sm btn-outline-warning mx-1">استرداد البيانات</a>
      </div>
    </div>
    <hr>
    <form action="settings_handler.php" method="post" enctype="multipart/form-data" class="mt-4">
      <div class="mb-3">
        <label>اسم المنظّمة/المطعم</label>
        <input type="text" name="restaurant_name" class="form-control" value="<?= htmlspecialchars($restaurantName) ?>" required>
      </div>

      <input type="hidden" name="existing_logo" value="<?= htmlspecialchars($logoPath) ?>">
      <div class="mb-3">
        <label>الشعار (تحميل صورة)</label>
        <input type="file" name="logo_file" class="form-control">
        <?php if ($logoPath): ?>
          <img src="<?= htmlspecialchars($logoPath) ?>" alt="الشعار" height="80" class="mt-2">
        <?php endif; ?>
      </div>

      <hr>

      <div class="mb-3">
        <label>الرقم الضريبي</label>
        <input type="text" name="tax_number" class="form-control" value="<?= htmlspecialchars($taxNumber) ?>">
      </div>

      <div class="mb-3">
        <label>العنوان</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($address) ?>">
      </div>

      <div class="mb-3">
        <label>نسبة الضريبة (%)</label>
        <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?= htmlspecialchars($taxRate) ?>">
      </div>

      <div class="mb-3">
        <label>عرض الفاتورة (مم)</label>
        <input type="number" name="print_width_mm" class="form-control" value="<?= htmlspecialchars($printWidthMm) ?>">
      </div>

      <div class="mb-3">
        <label>العملة الافتراضية</label>
        <select name="currency" class="form-control">
          <option value="SYP" <?= $currency==='SYP'?'selected':'' ?>>ليرة سورية (SYP)</option>
          <option value="USD" <?= $currency==='USD'?'selected':'' ?>>دولار أمريكي (USD)</option>
          <option value="TRY" <?= $currency==='TRY'?'selected':'' ?>>ليرة تركية (TRY)</option>
        </select>
      </div>

      <hr>

      <h4 class="mt-4">إعدادات صرف العملات</h4>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="exchange_rate_enabled" id="exchange_rate_enabled" value="1" <?= $exchangeRateEnabled ? 'checked' : '' ?>>
          <label class="form-check-label mr-3" for="exchange_rate_enabled">
            حساب الأسعار باستخدام سعر الصرف
          </label>
        </div>
        <small class="form-text text-muted">عند تفعيل هذا الخيار، ستظهر جميع الأسعار محولة إلى العملة المحددة أدناه</small>
      </div>

      <div class="mb-3">
        <label>العملة الأساسية للتحويل</label>
        <select name="base_currency" class="form-control">
          <option value="SYP" <?= $baseCurrency==='SYP'?'selected':'' ?>>ليرة سورية (SYP)</option>
          <option value="USD" <?= $baseCurrency==='USD'?'selected':'' ?>>دولار أمريكي (USD)</option>
          <option value="TRY" <?= $baseCurrency==='TRY'?'selected':'' ?>>ليرة تركية (TRY)</option>
        </select>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label>سعر صرف الدولار إلى الليرة السورية</label>
          <input type="number" step="0.01" name="usd_to_syp_rate" class="form-control" value="<?= htmlspecialchars($usdToSypRate) ?>" placeholder="15000">
          <small class="form-text text-muted">مثال: 1 USD = 15000 SYP</small>
        </div>
        <div class="col-md-6">
          <label>سعر صرف الليرة التركية إلى الليرة السورية</label>
          <input type="number" step="0.01" name="try_to_syp_rate" class="form-control" value="<?= htmlspecialchars($tryToSypRate) ?>" placeholder="500">
          <small class="form-text text-muted">مثال: 1 TRY = 500 SYP</small>
        </div>
      </div>

      <hr>

      <h4 class="mt-4">حجم الخط في الفاتورة</h4>
      <div class="row g-3">
        <div class="col-md-4">
          <label>حجم عنوان الفاتورة</label>
          <input type="number" name="font_size_title" class="form-control" value="<?= htmlspecialchars($fontSizeTitle) ?>">
        </div>
        <div class="col-md-4">
          <label>حجم خط العناصر</label>
          <input type="number" name="font_size_item" class="form-control" value="<?= htmlspecialchars($fontSizeItem) ?>">
        </div>
        <div class="col-md-4">
          <label>حجم خط المجموع</label>
          <input type="number" name="font_size_total" class="form-control" value="<?= htmlspecialchars($fontSizeTotal) ?>">
        </div>
      </div>

      <hr>

      <h4 class="mt-4">حجم الخط في تقارير الطباعة</h4>
      <div class="row g-3">
        <div class="col-md-4">
          <label>حجم عنوان التقرير</label>
          <input type="number" name="font_size_report_title" class="form-control" value="<?= htmlspecialchars($fontSizeReportTitle) ?>">
        </div>
        <div class="col-md-4">
          <label>حجم عناصر التقرير</label>
          <input type="number" name="font_size_report_item" class="form-control" value="<?= htmlspecialchars($fontSizeReportItem) ?>">
        </div>
        <div class="col-md-4">
          <label>حجم المجموع في التقرير</label>
          <input type="number" name="font_size_report_total" class="form-control" value="<?= htmlspecialchars($fontSizeReportTotal) ?>">
        </div>
      </div>

      <hr>

      <!-- Grid container for the checkboxes -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4 rounded-lg bg-gray-100">
          <?php
          $labels = [
              'field_item_name_ar'    => 'اسم المادة بالعربي',
              'field_item_name_en'    => 'اسم المادة بالإنكليزي',
              'field_tax_number'      => 'الرقم الضريبي',
              'field_username'        => 'اسم المستخدم',
              'field_restaurant_name' => 'اسم المطعم/المنشأة',
              'field_restaurant_logo' => 'شعار المنشأة',
          ];
          // This is a placeholder for your existing PHP logic to get $fieldValues
          $fieldValues = isset($fieldValues) ? $fieldValues : [];
          
          foreach ($labels as $key => $label): ?>
          
          <!-- Each form-check div becomes a grid item -->
          <div class="flex items-center p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition duration-300">
              <input class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500 transition duration-150 ease-in-out" 
                    type="checkbox" 
                    name="<?= $key ?>" 
                    id="<?= $key ?>" 
                    value="1" 
                    <?= isset($fieldValues[$key]) && $fieldValues[$key] ? 'checked' : '' ?>>
              <label class="mr-3 text-gray-700 cursor-pointer" for="<?= $key ?>"><?= $label ?></label>
          </div>
          
          <?php endforeach; ?>
      </div>

      <button type="submit" class="btn btn-success mt-3">حفظ الإعدادات</button>
    </form>
  </main>
</body>
</html>
