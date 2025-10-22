<?php
// public/header.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

// جلب آخر إعدادات النظام
$stmt = $db->query("SELECT restaurant_name, logo_path FROM System_Settings ORDER BY id DESC LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// قيم افتراضية
$restaurantName = $settings['restaurant_name'] ?? 'اسم المطعم';
$logoPath       = $settings['logo_path'] ?? 'images/logo.png';

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

$authMap = [];
try {
	$stmtA = $db->prepare('SELECT element_key FROM Authorizations WHERE user_id = :uid');
	$stmtA->execute([':uid'=>(int)$_SESSION['user_id']]);
	$authMap = array_flip(array_column($stmtA->fetchAll(PDO::FETCH_ASSOC), 'element_key'));
} catch (Exception $e) {
	$authMap = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($restaurantName) ?></title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <!-- <link rel="stylesheet" href="../assets/tailwind.min.css"> -->
  <link rel="stylesheet" href="css/style.css">
  <script src="../assets/jquery.min.js"></script>
  <script src="../assets/popper.min.js"></script>
  <script src="../assets/bootstrap.bundle.min.js"></script>
  <script src="js/exchange_rates.js"></script>
  <!-- <script src="../assets/tailwindcss.css"></script> -->
</head>
<body>
  <header class="p-3 bg-light d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <img src="<?= htmlspecialchars($logoPath) ?>" alt="شعار" height="50">
      <span class="h5 mx-3"><?= htmlspecialchars($restaurantName) ?></span>
    </div>
    <nav class="no-print">
      <a href="dashboard.php" class="btn btn-sm btn-outline-primary mx-1">الرئيسية</a>
      <a href="pos.php" class="btn btn-sm btn-outline-warning mx-1" data-auth="btn_checkout">المبيع</a>
      <?php if (Auth::isAdmin()): ?>
        <a href="settings.php" class="btn btn-sm btn-outline-secondary mx-1" data-auth="nav_settings">الإعدادات</a>
      <?php endif; ?>
      <?php if (Auth::isAdmin()): ?>
        <a href="stock_report.php" class="btn btn-sm btn-outline-danger mx-1">جرد المخزن</a>
      <?php endif; ?>
      <?php if (Auth::isAdmin()): ?>
        <a href="warehouse_entries.php" class="btn btn-sm btn-outline-info mx-1" data-auth="nav_warehouse">فواتير إدخال/إخراج</a>
      <?php endif; ?>
      <?php if (Auth::isAdmin()): ?>
        <a href="sales_log.php" class="btn btn-sm btn-outline-dark mx-1">سجل المبيعات</a>
      <?php endif; ?>
      <?php if (Auth::isAdmin()): ?>
        <a href="refund.php" class="btn btn-sm btn-outline-warning mx-1">استرداد المبيعات</a>
      <?php endif; ?>
      <a href="logout.php" class="btn btn-sm btn-danger mx-1">خروج (<?= htmlspecialchars($username) ?>)</a>
    </nav>
  </header>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const auth = <?= json_encode(array_keys($authMap)) ?>;
      document.querySelectorAll('[data-auth]').forEach(el => {
        const key = el.getAttribute('data-auth');
        if (key && auth.indexOf(key) === -1) {
          if (el.tagName === 'A') {
            el.classList.add('disabled'); el.setAttribute('aria-disabled','true'); el.addEventListener('click', e => e.preventDefault());
          } else if (el.tagName === 'INPUT' || el.tagName === 'BUTTON' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            el.disabled = true;
          }
        }
      });
    });
  </script>
  <main class="container mt-4">
  <div id="toast" class="toast"></div>
  <script>
    function showToast(msg, ms = 3000) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(()=> t.classList.remove('show'), ms);
    }
  </script>
