<?php
// تضمين الملفات الأساسية
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ExchangeRate.php';

Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

// Get exchange rate settings
$exchangeRateManager = new ExchangeRate($db);
$exchangeSettings = $exchangeRateManager->getSystemSettings();

/**
 * كلاس SalesLog لإدارة تقارير المبيعات
 * تم تعديل الدوال لقبول معايير الفلترة الجديدة
 */
class SalesLog {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function summary($from, $to, $userId = 0, $timeFrom = '', $timeTo = '') {
        $sql = "SELECT 
                    u.username, 
                    COUNT(DISTINCT o.id) AS sale_count, 
                    SUM(oi.unit_price * oi.quantity) AS total_amount, 
                    AVG(oi.unit_price * oi.quantity) AS avg_amount,
                    i.currency
                FROM Orders o
                JOIN Users u ON u.id = o.user_id
                JOIN Order_Items oi ON oi.order_id = o.id
                JOIN Items i ON i.id = oi.item_id
                WHERE DATE(o.created_at) BETWEEN :from AND :to";
        if ($userId > 0) { $sql .= " AND o.user_id = :uid"; }
        if ($timeFrom !== '' && $timeTo !== '') { $sql .= " AND TIME(o.created_at) BETWEEN :tfrom AND :tto"; }
        $sql .= " GROUP BY u.username ORDER BY u.username";

        $stmt = $this->db->prepare($sql);
        $params = [':from' => $from, ':to' => $to];
        if ($userId > 0) { $params[':uid'] = $userId; }
        if ($timeFrom !== '' && $timeTo !== '') { $params[':tfrom'] = $timeFrom; $params[':tto'] = $timeTo; }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function details($from, $to, $userId = 0, $timeFrom = '', $timeTo = '') {
        $sql = "SELECT 
                    o.id AS order_id, 
                    o.created_at, 
                    u.username, 
                    o.total, 
                    i.currency
                FROM Orders o
                JOIN Users u ON u.id = o.user_id
                JOIN Order_Items oi ON oi.order_id = o.id
                JOIN Items i ON i.id = oi.item_id
                WHERE DATE(o.created_at) BETWEEN :from AND :to";
        if ($userId > 0) { $sql .= " AND o.user_id = :uid"; }
        if ($timeFrom !== '' && $timeTo !== '') { $sql .= " AND TIME(o.created_at) BETWEEN :tfrom AND :tto"; }
        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $params = [':from' => $from, ':to' => $to];
        if ($userId > 0) { $params[':uid'] = $userId; }
        if ($timeFrom !== '' && $timeTo !== '') { $params[':tfrom'] = $timeFrom; $params[':tto'] = $timeTo; }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$from = $_GET['date_from'] ?? date('Y-m-d');
$to = $_GET['date_to'] ?? date('Y-m-d');
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$timeFrom = $_GET['time_from'] ?? '';
$timeTo = $_GET['time_to'] ?? '';

$model = new SalesLog($db);
// تم تعديل الاستدعاء لتمرير جميع متغيرات الفلترة
$summary = $model->summary($from, $to, $userId, $timeFrom, $timeTo);
$details = $model->details($from, $to, $userId, $timeFrom, $timeTo);

$usersList = $db->query("SELECT id, username FROM Users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'header.php'; ?>

<main class="container mt-4">
  <h2>سجل المبيعات</h2>
  <div class="d-flex justify-content-between align-items-center">
    <form method="get" class="row g-2 mb-4">
      <div class="col-auto"><input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($from) ?>" placeholder="من"></div>
      <div class="col-auto"><input type="time" name="time_from" class="form-control" value="<?= htmlspecialchars($timeFrom) ?>" placeholder="من"></div>
      <div class="col-auto"><input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($to) ?>" placeholder="إلى"></div>
      <div class="col-auto"><input type="time" name="time_to" class="form-control" value="<?= htmlspecialchars($timeTo) ?>" placeholder="إلى"></div>
      <div class="col-auto">
        <select name="user_id" class="form-select">
          <option value="0">كل المستخدمين</option>
          <?php foreach($usersList as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $userId===$u['id']?'selected':'' ?>><?= htmlspecialchars($u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto mt-3"><button class="btn btn-primary">تصفية</button></div>
    </form>
    <div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="f_username" checked><label class="form-check-label pr-4" for="f_username">اسم المستخدم</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="f_total" checked><label class="form-check-label pr-4" for="f_total">القيم الإجمالية</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="f_details"><label class="form-check-label pr-4" for="f_details">تفاصيل الفواتير</label></div>
      <button id="printSales" class="btn btn-outline-primary mt-2">طباعة كإيصال</button>
      <button id="exportPDF" class="btn btn-outline-dark mt-2">تصدير PDF</button>
      <a href="refund.php" class="btn btn-outline-warning mt-2">استرداد المبيعات</a>
    </div>
  </div>

  <h4>الإحصاءات حسب المستخدم</h4>
  <table class="table table-bordered mb-5">
    <thead><tr><th>المستخدم</th><th>عدد الفواتير</th><th>إجمالي المبيعات</th><th>متوسط قيمة الفاتورة</th></tr></thead>
    <tbody>
      <?php foreach ($summary as $row): 
        // Convert prices if exchange rate is enabled
        $totalAmount = (float)$row['total_amount'];
        $avgAmount = (float)$row['avg_amount'];
        
        $totalAmount = $exchangeRateManager->convertToDisplayCurrency($totalAmount, $row['currency']);
        $avgAmount = $exchangeRateManager->convertToDisplayCurrency($avgAmount, $row['currency']);
      ?>
      <tr>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= $row['sale_count'] ?></td>
        <td><?= number_format($totalAmount, 2) ?></td>
        <td><?= number_format($avgAmount, 2) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($summary)): ?>
      <tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h4>تفاصيل الفواتير</h4>
  <table class="table table-striped">
    <thead><tr><th>#</th><th>التاريخ والوقت</th><th>المستخدم</th><th>الإجمالي</th></tr></thead>
    <tbody>
      <?php foreach ($details as $o): 
        // Convert prices if exchange rate is enabled
        $total = (float)$o['total'];
        
        $total = $exchangeRateManager->convertToDisplayCurrency($total, $o['currency']);
      ?>
      <tr>
        <td><?= $o['order_id'] ?></td>
        <td><?= htmlspecialchars($o['created_at']) ?></td>
        <td><?= htmlspecialchars($o['username']) ?></td>
        <td><?= number_format($total, 2) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($details)): ?>
      <tr><td colspan="4" class="text-center">لا توجد فواتير</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>
<script>
  document.getElementById('printSales').addEventListener('click', async () => {
    const wantUser   = document.getElementById('f_username').checked;
    const wantTotals = document.getElementById('f_total').checked;
    const wantDetails= document.getElementById('f_details').checked;

    const payload = await buildSalesImage({ from: '<?= htmlspecialchars($from) ?>', to: '<?= htmlspecialchars($to) ?>', timeFrom: '<?= htmlspecialchars($timeFrom) ?>', timeTo: '<?= htmlspecialchars($timeTo) ?>', userId: '<?= (int)$userId ?>', wantUser, wantTotals, wantDetails });
    const resp = await fetch('../src/print.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await resp.json();
    if (data.success) { if (typeof showToast==='function') showToast('تم إرسال السجل للطباعة'); }
  });

  document.getElementById('exportPDF').addEventListener('click', () => { window.print(); });

  async function buildSalesImage(opts) {
    const canvas = document.createElement('canvas'); const ctx = canvas.getContext('2d');
    const width = 560; let y = 16; canvas.width = width; canvas.height = 1600;
    ctx.fillStyle = '#fff'; ctx.fillRect(0,0,width,canvas.height);

    // Header
    ctx.fillStyle = '#111'; ctx.textAlign='center'; ctx.font = 'bold 22px Arial';
    ctx.fillText('سجل المبيعات', width/2, y); y+=28;
    ctx.font = '14px Arial';
    if (opts.from || opts.to) { ctx.fillText(`من ${opts.from||'-'} إلى ${opts.to||'-'}`, width/2, y); y+=22; }
    // Separator
    ctx.strokeStyle='#000'; ctx.beginPath(); ctx.moveTo(12,y); ctx.lineTo(width-12,y); ctx.stroke(); y+=10;

    // Totals row
    <?php $sumTotal = 0; foreach ($details as $o) { $sumTotal += (float)$o['total']; } ?>
    if (opts.wantTotals) {
      ctx.textAlign='left'; ctx.font='bold 16px Arial'; ctx.fillText('الإجمالي الكلي', 14, y);
      ctx.textAlign='right'; ctx.fillText('<?= number_format($sumTotal,2) ?>', width-14, y); y+=24;
    }

    // Summary table
    if (opts.wantUser) {
      const col1=14, col2=width-14; const rowH=22;
      ctx.textAlign='left'; ctx.font='bold 16px Arial'; ctx.fillText('حسب المستخدم', 14, y); y+=rowH;
      ctx.font='bold 14px Arial';
      ctx.fillText('المستخدم', col1, y); ctx.textAlign='right'; ctx.fillText('إجمالي - عدد', col2, y); y+=rowH;
      ctx.strokeStyle='#ddd'; ctx.beginPath(); ctx.moveTo(12,y-14); ctx.lineTo(width-12,y-14); ctx.stroke();
      ctx.font='14px Arial'; ctx.textAlign='left';
      <?php foreach ($summary as $row): $line = addslashes($row['username']); $tot = number_format($row['total_amount'],2); $cnt=(int)$row['sale_count']; ?>
        ctx.fillText('<?= $line ?>', col1, y);
        ctx.textAlign='right'; ctx.fillText('<?= $tot ?> - <?= $cnt ?>', col2, y); ctx.textAlign='left'; y+=rowH;
      <?php endforeach; ?>
      y+=6;
    }

    // Detail table
    if (opts.wantDetails) {
      const cDate=14, cUser=200, cTot=width-14; const rowH=20;
      ctx.textAlign='left'; ctx.font='bold 16px Arial'; ctx.fillText('تفاصيل الفواتير', 14, y); y+=rowH;
      ctx.font='bold 14px Arial';
      ctx.fillText('التاريخ', cDate, y); ctx.fillText('المستخدم', cUser, y); ctx.textAlign='right'; ctx.fillText('الإجمالي', cTot, y); y+=rowH;
      ctx.strokeStyle='#ddd'; ctx.beginPath(); ctx.moveTo(12,y-14); ctx.lineTo(width-12,y-14); ctx.stroke();
      ctx.font='14px Arial'; ctx.textAlign='left';
      <?php foreach ($details as $o): $d=addslashes($o['created_at']); $u=addslashes($o['username']); $t=number_format($o['total'],2); ?>
        ctx.fillText('<?= $d ?>', cDate, y); ctx.fillText('<?= $u ?>', cUser, y); ctx.textAlign='right'; ctx.fillText('<?= $t ?>', cTot, y); ctx.textAlign='left'; y+=rowH;
      <?php endforeach; ?>
    }

    // Footer separator and thank you
    y+=10; ctx.strokeStyle='#000'; ctx.beginPath(); ctx.moveTo(12,y); ctx.lineTo(width-12,y); ctx.stroke(); y+=20;
    ctx.textAlign='center'; ctx.font='bold 14px Arial'; ctx.fillText('— نهاية —', width/2, y);

    return { images:[{ image: canvas.toDataURL('image/png') }] };
  }
</script>