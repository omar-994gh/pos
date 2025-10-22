<?php
// public/settings_handler.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// 1. جلب المدخلات
$name       = trim($_POST['restaurant_name'] ?? '');
$taxNo      = trim($_POST['tax_number'] ?? '');
$address    = trim($_POST['address'] ?? '');
$taxRate    = floatval($_POST['tax_rate'] ?? 0);
$printWidth = intval($_POST['print_width_mm'] ?? 80);
$currency   = in_array($_POST['currency'] ?? '', ['SYP','USD','TRY'])
                ? $_POST['currency']
                : 'SYP';
$fontTitle  = intval($_POST['font_size_title'] ?? 22);
$fontItem   = intval($_POST['font_size_item'] ?? 16);
$fontTotal  = intval($_POST['font_size_total'] ?? 18);
$fsReportTitle = intval($_POST['font_size_report_title'] ?? 20);
$fsReportItem  = intval($_POST['font_size_report_item'] ?? 14);
$fsReportTotal = intval($_POST['font_size_report_total'] ?? 16);

// Exchange rate settings
$exchangeRateEnabled = !empty($_POST['exchange_rate_enabled']) ? 1 : 0;
$baseCurrency = in_array($_POST['base_currency'] ?? '', ['SYP','USD','TRY'])
                ? $_POST['base_currency']
                : 'SYP';
$usdToSypRate = floatval($_POST['usd_to_syp_rate'] ?? 15000.0);
$tryToSypRate = floatval($_POST['try_to_syp_rate'] ?? 500.0);

// 2. معالجة الشعار
$imagesDir   = __DIR__ . '/images';
if (!is_dir($imagesDir)) mkdir($imagesDir, 0777, true);
$logoFilename = 'logo.png';
$targetPath   = "$imagesDir/$logoFilename";
$logoPathRel  = "images/$logoFilename";
if (!empty($_FILES['logo_file']['tmp_name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
    if (file_exists($targetPath)) unlink($targetPath);
    if (! move_uploaded_file($_FILES['logo_file']['tmp_name'], $targetPath)) {
        die('❌ فشل في رفع الشعار.');
    }
}

// 3. حقول الطباعة
$printFields = [
    'field_item_name_ar',
    'field_item_name_en',
    'field_tax_number',
    'field_username',
    'field_restaurant_name',
    'field_restaurant_logo'
];

// بناء مصفوفة القيم 0/1 لهذه الحقول
$printValues = [];
foreach ($printFields as $f) {
    $printValues[$f] = !empty($_POST[$f]) ? 1 : 0;
}

// 4. تحضير جملة SQL ديناميكيًا
$sql = "
    INSERT INTO System_Settings
    (restaurant_name, logo_path, tax_number, address, tax_rate, print_width_mm, currency, font_size_title, font_size_item, font_size_total, font_size_report_title, font_size_report_item, font_size_report_total, exchange_rate_enabled, base_currency, usd_to_syp_rate, try_to_syp_rate, "
    . implode(', ', $printFields) . "
    )
    VALUES
    (:name, :logo, :tax_no, :addr, :rate, :width, :curr, :fs_title, :fs_item, :fs_total, :fsr_title, :fsr_item, :fsr_total, :ex_enabled, :base_curr, :usd_rate, :try_rate, :"
    . implode(', :', $printFields) . ")
";
$stmt = $db->prepare($sql);

// 5. تجميع معاملات الـ PDO
$params = [
    ':name'   => $name,
    ':logo'   => $logoPathRel,
    ':tax_no' => $taxNo,
    ':addr'   => $address,
    ':rate'   => $taxRate,
    ':width'  => $printWidth,
    ':curr'   => $currency,
    ':fs_title' => $fontTitle,
    ':fs_item'  => $fontItem,
    ':fs_total' => $fontTotal,
    ':fsr_title'=> $fsReportTitle,
    ':fsr_item' => $fsReportItem,
    ':fsr_total'=> $fsReportTotal,
    ':ex_enabled' => $exchangeRateEnabled,
    ':base_curr' => $baseCurrency,
    ':usd_rate' => $usdToSypRate,
    ':try_rate' => $tryToSypRate,
];
foreach ($printFields as $f) {
    $params[":$f"] = $printValues[$f];
}

// 6. التنفيذ
// Update exchange rates in the Exchange_Rates table
if ($exchangeRateEnabled) {
    require_once __DIR__ . '/../src/ExchangeRate.php';
    $exchangeRateManager = new ExchangeRate($db);
    $exchangeRateManager->updateSystemSettings($exchangeRateEnabled, $baseCurrency, $usdToSypRate, $tryToSypRate);
}

if ($stmt->execute($params)) {
    header('Location: settings.php');
    exit;
} else {
    $err = $stmt->errorInfo();
    die('❌ خطأ أثناء حفظ الإعدادات: ' . htmlspecialchars($err[2] ?? 'Unknown'));
}

