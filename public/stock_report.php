<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Item.php';
require_once __DIR__ . '/../src/ExchangeRate.php';

Auth::requireLogin();

$itemModel = new Item($db);
$items = $itemModel->allWithStockAndGroup();

// Get exchange rate settings
$exchangeRateManager = new ExchangeRate($db);
$exchangeSettings = $exchangeRateManager->getSystemSettings();
?>
<?php include 'header.php'; ?>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="mb-0">تقرير المخزون</h2>
    <div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="sf_group" checked><label class="form-check-label mr-3" for="sf_group">المجموعة</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="sf_price" checked><label class="form-check-label mr-3" for="sf_price">السعر</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="sf_net" checked><label class="form-check-label mr-3" for="sf_net">السعر الإجمالي</label></div>
      <button id="printStock" class="btn btn-outline-primary mt-2">طباعة كإيصال</button>
    </div>
  </div>

  <div class="row g-3 mb-3 align-items-end">
    <div class="col-md-4">
      <label for="filterItem" class="form-label">فلترة حسب المادة:</label>
      <select id="filterItem" class="form-control">
        <option value="all">– كل المواد –</option>
        <?php foreach ($items as $item): ?>
          <option value="<?= $item['name_ar'] ?>"><?= htmlspecialchars($item['name_ar']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label for="searchInput" class="form-label">بحث مباشر:</label>
      <input type="text" id="searchInput" class="form-control" placeholder="أدخل اسم المادة...">
    </div>
    <div class="col-md-4 text-end">
      <button class="btn btn-primary mt-3" onclick="printReport()">🖨️ طباعة التقرير</button>
    </div>
  </div>

  <div class="table-responsive" id="reportArea">
    <table class="table table-bordered table-hover text-center align-middle" id="stockTable">
      <thead class="table-dark">
        <tr>
          <th>اسم المادة</th>
          <th>الاسم الإنجليزي</th>
          <th>المجموعة</th>
          <th>الكمية المتوفرة</th>
          <th>الوحدة</th>
          <th>سعر الكلفة</th>
          <th>سعر المبيع</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): 
          $net = (float)$it['stock'] * (float)($it['price'] ?? 0);
          
          // Convert prices if exchange rate is enabled
          $unitPrice = (float)($it['unit_price'] ?? 0);
          $salePrice = (float)($it['price'] ?? 0);
          
          $unitPrice = $exchangeRateManager->convertToDisplayCurrency($unitPrice, $it['currency'] ?? 'SYP');
          $salePrice = $exchangeRateManager->convertToDisplayCurrency($salePrice, $it['currency'] ?? 'SYP');
        ?>
          <tr data-name="<?= strtolower($it['name_ar']) ?>">
            <td><?= htmlspecialchars($it['name_ar']) ?></td>
            <td><?= htmlspecialchars($it['name_en']) ?></td>
            <td><?= htmlspecialchars($it['group_name']) ?></td>
            <td><?= $it['stock'] ?></td>
            <td><?= htmlspecialchars($it['unit']) ?></td>
            <td><?= number_format($unitPrice, 2) ?></td>
            <td><?= number_format($salePrice, 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const filterSelect = document.getElementById('filterItem');
  const searchInput = document.getElementById('searchInput');
  const rows = document.querySelectorAll('#stockTable tbody tr');

  const filterRows = () => {
    const selected = filterSelect.value.toLowerCase();
    const keyword = searchInput.value.toLowerCase();
    rows.forEach(row => {
      const name = row.dataset.name;
      const matchesFilter = (selected === 'all' || name.includes(selected));
      const matchesSearch = name.includes(keyword);
      row.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
    });
  };

  filterSelect.addEventListener('change', filterRows);
  searchInput.addEventListener('input', filterRows);
});

function printReport() {
  const content = document.getElementById('reportArea').innerHTML;
  const printWindow = window.open('', '', 'width=900,height=700');
  printWindow.document.write(`
    <html>
      <head>
        <title>تقرير المخزون</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
      </head>
      <body>
        <h2>تقرير المواد في المخزون</h2>
        ${content}
        <script>window.onload = () => window.print();<\/script>
      </body>
    </html>
  `);
  printWindow.document.close();
}

document.getElementById('printStock').addEventListener('click', async () => {
  const wantGroup = document.getElementById('sf_group').checked;
  const wantPrice = document.getElementById('sf_price').checked;
  const wantNet   = document.getElementById('sf_net').checked;

  const tableRows = Array.from(document.querySelectorAll('#stockTable tbody tr')).filter(r => r.style.display !== 'none');
  const rows = tableRows.map(tr => ({
    name: tr.children[0].innerText,
    en: tr.children[1].innerText,
    group: tr.children[2].innerText,
    qty: tr.children[3].innerText,
    unit: tr.children[4].innerText,
    price: tr.children[5].innerText,
    net: tr.children[6].innerText,
  }));

  const payload = await buildStockImage({ rows, wantGroup, wantPrice, wantNet });
  const resp = await fetch('../src/print.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const data = await resp.json();
  if (data.success && typeof showToast==='function') showToast('تم إرسال التقرير للطباعة');
});

async function buildStockImage(opts) {
  const canvas = document.createElement('canvas'); const ctx = canvas.getContext('2d');
  const width = 560; canvas.width = width; canvas.height = 1400; let y = 24;
  ctx.fillStyle='#fff'; ctx.fillRect(0,0,width,canvas.height); ctx.fillStyle='#000'; ctx.textAlign='center'; ctx.font='bold 20px Arial';
  ctx.fillText('تقرير المخزون', width/2, y); y+=30; ctx.textAlign='left'; ctx.font='14px Arial';
  const headers = ['اسم','إنكليزي']; if (opts.wantGroup) headers.push('مجموعة'); headers.push('كمية','وحدة'); if (opts.wantPrice) headers.push('سعر'); if (opts.wantNet) headers.push('إجمالي');
  ctx.font='bold 14px Arial'; ctx.fillText(headers.join(' | '), 10, y); y+=22; ctx.font='14px Arial';
  opts.rows.forEach(r => { const cols=[r.name,r.en]; if (opts.wantGroup) cols.push(r.group); cols.push(r.qty,r.unit); if (opts.wantPrice) cols.push(r.price); if (opts.wantNet) cols.push(r.net); ctx.fillText(cols.join(' | '), 10, y); y+=18; });
  return { images:[{ image: canvas.toDataURL('image/png') }] };
}
</script>
