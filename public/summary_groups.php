<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ExchangeRate.php';

Auth::requireLogin();
if (!Auth::isAdmin() && !Auth::isCashier()) { header('Location: dashboard.php'); exit; }

// Get exchange rate settings
$exchangeRateManager = new ExchangeRate($db);
$exchangeSettings = $exchangeRateManager->getSystemSettings();

$from = $_GET['date_from'] ?? date('Y-m-01');
$to = $_GET['date_to'] ?? date('Y-m-d');
$timeFrom = $_GET['time_from'] ?? '';
$timeTo = $_GET['time_to'] ?? '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

$usersList = $db->query("SELECT id, username FROM Users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get exchange rate for currency conversion
$usdToSypRate = (float)($exchangeSettings['usd_to_syp_rate'] ?? 15000.0);
$baseCurrency = $exchangeSettings['base_currency'] ?? 'SYP';

// Build conversion expressions based on base currency
if ($baseCurrency === 'SYP') {
    $salesConvert = "CASE WHEN i.currency = 'USD' THEN oi.quantity * oi.unit_price * $usdToSypRate ELSE oi.quantity * oi.unit_price END";
    $costConvert = "CASE WHEN i.currency = 'USD' THEN oi.quantity * wh.unit_price * $usdToSypRate ELSE oi.quantity * wh.unit_price END";
} else { // USD
    $salesConvert = "CASE WHEN i.currency = 'SYP' THEN oi.quantity * oi.unit_price / $usdToSypRate ELSE oi.quantity * oi.unit_price END";
    $costConvert = "CASE WHEN i.currency = 'SYP' THEN oi.quantity * wh.unit_price / $usdToSypRate ELSE oi.quantity * wh.unit_price END";
}

// Modified SQL query to join with warehouse_invoice_items and convert currencies
$stmt = $db->prepare("SELECT
    g.name AS group_name,
    i.name_ar AS item_name,
    i.currency,
    SUM(oi.quantity) AS qty,
    SUM($salesConvert) AS total_sales,
    SUM($costConvert) AS total_cost,
    (SUM($salesConvert) - SUM($costConvert)) AS net_profit
FROM Order_Items oi
JOIN Orders o ON o.id = oi.order_id
JOIN Items i ON i.id = oi.item_id
LEFT JOIN Groups g ON g.id = i.group_id
LEFT JOIN (
    -- Subquery to get the latest unit_price for each item_id based on the highest invoice_id
    SELECT item_id, unit_price
    FROM warehouse_invoice_items
    WHERE invoice_id IN (
        SELECT MAX(invoice_id) FROM warehouse_invoice_items GROUP BY item_id
    )
) AS wh ON wh.item_id = i.id
WHERE DATE(o.created_at) BETWEEN :from AND :to
" . ($userId > 0 ? " AND o.user_id=:uid" : "") . (
    ($timeFrom !== '' && $timeTo !== '') ? " AND time(o.created_at) BETWEEN :tfrom AND :tto" : ''
) .
" GROUP BY g.name, i.name_ar, i.currency ORDER BY g.name, i.name_ar");

$params = [':from' => $from, ':to' => $to];
if ($userId > 0) $params[':uid'] = $userId;
if ($timeFrom !== '' && $timeTo !== '') { $params[':tfrom'] = $timeFrom; $params[':tto'] = $timeTo; }
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals (already converted in SQL)
$totalSales = array_sum(array_column($rows, 'total_sales'));
$totalCost = array_sum(array_column($rows, 'total_cost'));
$totalProfit = array_sum(array_column($rows, 'net_profit'));

include 'header.php';
?>
<main class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">ملخص المجموعات</h2>
    <form method="get" class="row g-2">
      <div class="col-auto"><input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="col-auto"><input type="time" name="time_from" class="form-control" value="<?= htmlspecialchars($timeFrom) ?>"></div>
      <div class="col-auto"><input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
      <div class="col-auto"><input type="time" name="time_to" class="form-control" value="<?= htmlspecialchars($timeTo) ?>"></div>
      <div class="col-auto">
        <select name="user_id" class="form-select">
          <option value="0">كل المستخدمين</option>
          <?php foreach ($usersList as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $userId === $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto mt-4"><button class="btn btn-primary">تصفية</button></div>
      <div class="col-auto mt-4"><button type="button" id="printSummary" class="btn btn-outline-primary">طباعة كإيصال</button></div>
      <div class="col-auto mt-4"><button type="button" id="exportPDF" class="btn btn-outline-dark">تصدير PDF</button></div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>المجموعة</th>
          <th>العنصر</th>
          <th>الكمية</th>
          <th>إجمالي سعر المبيع</th>
          <th>إجمالي سعر الكلفة</th>
          <th>صافي الربح</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): 
          // Values are already converted in SQL query
          $totalSalesConverted = (float)$r['total_sales'];
          $totalCostConverted = (float)$r['total_cost'];
          $netProfitConverted = (float)$r['net_profit'];
        ?>
          <tr>
            <td><?= htmlspecialchars($r['group_name']) ?></td>
            <td><?= htmlspecialchars($r['item_name']) ?></td>
            <td><?= number_format((float)$r['qty'], 2) ?></td>
            <td><?= number_format($totalSalesConverted, 2) ?> <?= $exchangeSettings['base_currency'] ?></td>
            <td><?= number_format($totalCostConverted, 2) ?> <?= $exchangeSettings['base_currency'] ?></td>
            <td><?= number_format($netProfitConverted, 2) ?> <?= $exchangeSettings['base_currency'] ?></td>
          </tr>
        <?php endforeach; ?>
        <tr class="table-info fw-bold">
            <td colspan="3">المجموع النهائي</td>
            <td><?= number_format($totalSales, 2) ?> <?= $exchangeSettings['base_currency'] ?></td>
            <td><?= number_format($totalCost, 2) ?> <?= $exchangeSettings['base_currency'] ?></td>
            <td><?= number_format($totalProfit, 2) ?> <?= $exchangeSettings['base_currency'] ?></td>
        </tr>
      </tbody>
    </table>
  </div>
<?php include 'footer.php'; ?>
</main>
<script>
  document.getElementById('exportPDF').addEventListener('click', () => { window.print(); });

  document.getElementById('printSummary').addEventListener('click', async () => {
    const rows = Array.from(document.querySelectorAll('table tbody tr')).map(tr => ({
      group: tr.children[0].innerText,
      item: tr.children[1].innerText,
      qty: tr.children[2].innerText,
      total_sales: tr.children[3].innerText,
      total_cost: tr.children[4].innerText,
      net_profit: tr.children[5].innerText,
    }));
    const payload = await buildSummaryImage({ from: '<?= htmlspecialchars($from) ?>', to: '<?= htmlspecialchars($to) ?>', timeFrom: '<?= htmlspecialchars($timeFrom) ?>', timeTo: '<?= htmlspecialchars($timeTo) ?>', rows });
    const resp = await fetch('../src/print.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await resp.json();
    if (data.success && typeof showToast === 'function') showToast('تم إرسال الملخص للطباعة');
  });

  async function buildSummaryImage(opts) {
    const canvas = document.createElement('canvas'); const ctx = canvas.getContext('2d');
    const width = 560; canvas.width = width; canvas.height = 1600; let y = 16;
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, width, canvas.height);

    // Header
    ctx.fillStyle = '#111'; ctx.textAlign = 'center'; ctx.font = 'bold 22px Arial';
    ctx.fillText('ملخص المجموعات', width / 2, y); y += 28;
    ctx.font = '14px Arial'; ctx.fillText(`من ${opts.from} ${opts.timeFrom || ''} إلى ${opts.to} ${opts.timeTo || ''}`, width / 2, y); y += 22;
    ctx.strokeStyle = '#000'; ctx.beginPath(); ctx.moveTo(12, y); ctx.lineTo(width - 12, y); ctx.stroke(); y += 10;

    // Columns
    const cGroup = 14, cItem = 120, cQty = 260, cSales = 350, cCost = 440, cProfit = width - 14; const rowH = 20;
    ctx.textAlign = 'left'; ctx.font = 'bold 12px Arial';
    ctx.fillText('المجموعة', cGroup, y); ctx.fillText('العنصر', cItem, y); ctx.fillText('الكمية', cQty, y);
    ctx.textAlign = 'right';
    ctx.fillText('مبيع', cSales, y); ctx.fillText('كلفة', cCost, y); ctx.fillText('ربح', cProfit, y);
    y += rowH;
    ctx.strokeStyle = '#ddd'; ctx.beginPath(); ctx.moveTo(12, y - 14); ctx.lineTo(width - 12, y - 14); ctx.stroke();

    // Rows
    ctx.font = '12px Arial';
    opts.rows.forEach(r => {
      ctx.textAlign = 'left';
      ctx.fillText(r.group, cGroup, y);
      ctx.fillText(r.item, cItem, y);
      ctx.fillText(r.qty, cQty, y);
      ctx.textAlign = 'right';
      ctx.fillText(r.total_sales, cSales, y);
      ctx.fillText(r.total_cost, cCost, y);
      ctx.fillText(r.net_profit, cProfit, y);
      y += rowH;
    });

    return { images: [{ image: canvas.toDataURL('image/png') }] };
  }
</scri