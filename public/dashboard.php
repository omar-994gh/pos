<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::requireLogin();

// جلب إعدادات النظام الأخيرة
$stmt = $db->query("SELECT restaurant_name, logo_path, address FROM System_Settings ORDER BY id DESC LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$restaurantName = $settings['restaurant_name'] ?? 'اسم المطعم';
$logoPath       = $settings['logo_path']     ?? 'images/logo.png';
$address        = $settings['address']       ?? 'عنوان المطعم';
$username       = htmlspecialchars($_SESSION['username']);
$role           = htmlspecialchars($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة التحكم</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <style>
    /* Hero section */
    .hero {
      background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
      color: #fff;
      border-radius: .5rem;
      padding: 2rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    .hero::after {
      content: '';
      position: absolute;
      top: -50px; right: -50px;
      width: 200px; height: 200px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
      0%,100% { transform: translate(0,0); }
      50% { transform: translate(-20px,20px); }
    }
    .hero img {
      max-width: 120px;
      border-radius: .25rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
      gap: 1.5rem;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      transition: transform .3s;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
  </style>
</head>
<body class="bg-light">
  <?php include 'header.php';?>
  <main class="container py-4">
    <section class="hero d-flex align-items-center justify-content-between">
      <div>
        <h1 class="display-5">مرحبًا، <?= $username ?>!</h1>
        <p class="lead mb-0"><?= $restaurantName ?></p>
        <small><?= $address ?></small>
      </div>
      <img src="<?= $logoPath ?>" alt="Logo">
    </section>

    <?php if (Auth::isAdmin()): ?>
    <h2 class="mb-4">لوحة المشرف</h2>
    <div class="cards-grid">
      <a href="users.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-people-fill" style="font-size:2rem;"></i></div>
        <h5>المستخدمون</h5>
      </a>
      <a href="items.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-box-seam" style="font-size:2rem;"></i></div>
        <h5>الأصناف</h5>
      </a>
      <a href="groups.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-tags-fill" style="font-size:2rem;"></i></div>
        <h5>المجموعات</h5>
      </a>
      <a href="printers.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-printer-fill" style="font-size:2rem;"></i></div>
        <h5>الطابعات</h5>
      </a>
      <a href="warehouses.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-archive-fill" style="font-size:2rem;"></i></div>
        <h5>المستودع</h5>
      </a>
      <a href="sales_log.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-receipt-cutoff" style="font-size:2rem;"></i></div>
        <h5>سجل المبيعات</h5>
      </a>
      <a href="summary_groups.php" class="card card-hover text-center p-3">
        <div class="mb-2"><i class="bi bi-collection" style="font-size:2rem;"></i></div>
        <h5>ملخص المجموعات</h5>
      </a>
    </div>
    <?php else: ?>
    <h2 class="mb-4">لوحة الكاشير</h2>
    <div class="text-center">
      <a href="pos.php" class="btn btn-primary btn-lg">ابدأ البيع الآن</a>
    </div>
    <?php endif; ?>
  <?php include 'footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.js"></script>
</body>
</html>