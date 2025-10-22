<?php
// public/pos_barcode.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
if (!Auth::isCashier() && !Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}
$setting  = $db->query("SELECT tax_rate, currency, font_size_title, font_size_item, font_size_total FROM System_Settings ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$taxRate  = floatval($setting['tax_rate'] ?? 0);
$currency = htmlspecialchars($setting['currency'] ?? 'USD');
$fsTitle = (int)($setting['font_size_title'] ?? 22);
$fsItem  = (int)($setting['font_size_item'] ?? 16);
$fsTotal = (int)($setting['font_size_total'] ?? 18);

// Check privileges
$canEditPrice = Auth::canEditPrice($db);
$canAddDiscount = Auth::canAddDiscount($db);
?>
<?php include 'header.php'; ?>

<style>
#itemCard { display: none; animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px) } to { opacity: 1; transform: translateY(0) } }
.sticky-cart { position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto; }
.qty-input, .price-input { width: 4rem; display: inline-block; }
</style>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12">
      <div class="input-group">
        <span class="input-group-text">║▌║█║▌│</span>
        <input type="text" id="barcodeInput" class="form-control form-control-lg w-100" placeholder="أدخل باركود المادة واضغط Enter">
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="sticky-cart bg-white p-3 shadow-sm">
        <h4><i class="bi bi-cart-fill"></i> سلة المشتريات</h4>
        <table class="table table-md mb-2" id="cartTable">
          <thead>
            <tr>
              <th>صنف</th>
              <th>كمية</th>
              <th>سعر إفرادي(<?= $currency ?>)</th>
              <th>سعر إجمالي</th>
              <th>إلغاء العنصر</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <p>المجموع: <span id="subTotal">0.00</span> <?= $currency ?></p>
        <p>الضريبة (<?= $taxRate ?>%): <span id="taxAmount">0.00</span></p>
        <div class="mb-2">
          <label>الخصم (<?= $currency ?>):</label>
          <input type="number" id="discountInput" class="form-control" value="0" min="0" <?= $canAddDiscount ? '' : 'disabled' ?>>
        </div>
        <h5>الإجمالي: <span id="grandTotal">0.00</span> <?= $currency ?></h5>
        <button id="checkoutBtn" class="btn btn-success w-100" data-auth="btn_checkout" disabled>
          <i class="bi bi-check2-circle"></i> إنهاء وبيع
        </button>
      </div>
    </div>
  </div>
</div>

</main>
<script src="../assets/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cart = [];
  const taxRate = <?= $taxRate ?>;
  const currency = '<?= $currency ?>';
  const fsTitle = <?= $fsTitle ?>;
  const fsItem  = <?= $fsItem ?>;
  const fsTotal = <?= $fsTotal ?>;
  const canEditPrice = <?= $canEditPrice ? 'true' : 'false' ?>;
  const canAddDiscount = <?= $canAddDiscount ? 'true' : 'false' ?>;

  function toastOk(msg) { if (typeof showToast === 'function') showToast(msg, 2500); }
  function toastErr(msg) { if (typeof showToast === 'function') showToast(msg, 3500); }

  function renderCart() {
    const tbody = document.querySelector('#cartTable tbody');
    tbody.innerHTML = '';
    let subTotal = 0;

    cart.forEach((it, idx) => {
      const lineTotal = it.quantity * it.unit_price;
      subTotal += lineTotal;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${it.name}</td>
        <td><input type=\"number\" class=\"form-control form-control qty-input w-100\" data-idx=\"${idx}\" min=\"1\" value=\"${it.quantity}\"></td>
        <td><input type=\"number\" class=\"form-control form-control price-input w-100\" data-idx=\"${idx}\" step=\"0.01\" value=\"${it.unit_price}\" data-auth=\"input_edit_price\" ${canEditPrice ? '' : 'disabled' }></td>
        <td><span class=\"item-total\">${lineTotal.toFixed(2)}</span></td>
        <td><button class=\"btn btn-sm btn-danger remove\" data-idx=\"${idx}\">×</button></td>`;
      tbody.appendChild(tr);
      tr.querySelector('.qty-input').addEventListener('input', e => { const i = +e.target.dataset.idx; cart[i].quantity = Math.max(1, parseFloat(e.target.value)||1); recalcTotals(); });
      const priceInput = tr.querySelector('.price-input');
      if (!priceInput.disabled) priceInput.addEventListener('input', e => { const i = +e.target.dataset.idx; cart[i].unit_price = Math.max(0, parseFloat(e.target.value)||0); recalcTotals(); });
    });

    const discountEl = document.getElementById('discountInput');
    const discount = Math.max(0, parseFloat(discountEl.value) || 0);
    const taxAmount = subTotal * taxRate / 100;
    const grandTotal = subTotal + taxAmount - discount;

    document.getElementById('subTotal').textContent   = subTotal.toFixed(2);
    document.getElementById('taxAmount').textContent  = taxAmount.toFixed(2);
    document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
    document.getElementById('checkoutBtn').disabled   = cart.length === 0;
  }

  function recalcTotals() {
    let subTotal = 0;
    document.querySelectorAll('#cartTable tbody tr').forEach(tr => {
      const qty = Math.max(1, parseFloat(tr.querySelector('.qty-input').value)||1);
      const priceField = tr.querySelector('.price-input');
      const price = Math.max(0, parseFloat(priceField.value)||0);
      const lineTot = qty * price;
      subTotal += lineTot;
      tr.querySelector('.item-total').textContent = lineTot.toFixed(2);
    });
    const discount = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
    const taxAmount = subTotal * taxRate / 100;
    document.getElementById('subTotal').textContent   = subTotal.toFixed(2);
    document.getElementById('taxAmount').textContent  = taxAmount.toFixed(2);
    document.getElementById('grandTotal').textContent = (subTotal + taxAmount - discount).toFixed(2);
  }

  document.querySelector('#cartTable').addEventListener('click', e => {
    if (e.target.classList.contains('remove')) {
      const idx = +e.target.dataset.idx; cart.splice(idx, 1); renderCart();
    }
  });

  document.getElementById('barcodeInput').addEventListener('keydown', async e => {
    if (e.key !== 'Enter' || !e.target.value.trim()) return;
    e.preventDefault();
    const code = encodeURIComponent(e.target.value.trim());
    const it   = await fetch(`items_by_barcode.php?barcode=${code}`).then(r=>r.json());
    if (!it.id) { toastErr('لم أجد مادة بهذا الباركود'); e.target.select(); return; }
    if (parseFloat(it.stock) <= 0) { toastErr('نفدت الكمية'); e.target.select(); return; }
    const existing = cart.find(i => String(i.item_id) === String(it.id));
    if (existing) existing.quantity += 1;
    else cart.push({ item_id: it.id, name: it.name_ar + (it.name_en ? ` / ${it.name_en}` : ''), unit_price: parseFloat(it.price), quantity: 1, group_id: it.group_id });
    renderCart(); e.target.value = '';
    toastOk('تمت الإضافة إلى السلة');
  });

  document.getElementById('discountInput').setAttribute('data-auth','input_discount');
  document.getElementById('discountInput').addEventListener('input', renderCart);

  document.getElementById('checkoutBtn').addEventListener('click', async () => {
    if (!cart.length) return toastErr('السلة فارغة');
    const subTotal = cart.reduce((s, i) => s + i.unit_price * i.quantity, 0);
    const taxAmount = subTotal * taxRate / 100;
    const discount = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
    const total = subTotal + taxAmount - discount;

    const resp = await fetch('pos_handler.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ items: cart, total }) });
    const result = await resp.json();
    if (!result.success) return toastErr('فشل في إنشاء الطلب');
    
    // ***** تم إفراغ السلة بعد تسجيل الطلب وقبل الطباعة *****
    toastOk('تم إنشاء الطلب بنجاح');
    // *********************************************************

    const itemsByGroup = {}; cart.forEach(it => { const gid = parseInt(it.group_id || 0); if (!itemsByGroup[gid]) itemsByGroup[gid] = []; itemsByGroup[gid].push(it); });
    const groupPrinters = result.groupPrinters || {}; const unassignedPrinters = result.unassignedPrinters || [];

    const images = [];
    for (const [gidStr, items] of Object.entries(itemsByGroup)) {
      const gid = parseInt(gidStr);
      const printerId = parseInt(groupPrinters[gid] || 0);
      
      // ***** الشرط الجديد: لا يتم توليد فاتورة مفصلة إلا للمجموعات ذات الطابعات المخصصة *****
      if (printerId) {
        const img = await generateInvoiceImage(items, subTotal, taxAmount, total, result.orderSeq, fsTitle, fsItem, fsTotal);
        images.push({ image: img, printer_ids: [printerId] });
      }
    }
    if (unassignedPrinters.length) {
      const fullImg = await generateInvoiceImage(cart, subTotal, taxAmount, total, result.orderSeq, fsTitle, fsItem, fsTotal);
      images.push({ image: fullImg, printer_ids: unassignedPrinters });
    }

    const printResp = await fetch('../src/print.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ images, order_id: result.orderId }) });
    const printResult = await printResp.json();
    
    cart.length = 0;
    renderCart();
    if (printResult.success) { 
      toastOk('تم الطباعة بنجاح'); 
    } else { 
      toastErr('تم البيع ولكن فشلت الطباعة'); 
    }
  });

  // Guard add-to-cart clicks for zero stock (in case of future UI entrypoints)
  document.addEventListener('click', e => {
    const btn = e.target.closest('.add-to-cart');
    if (!btn) return;
    if (btn.hasAttribute('disabled')) {
      if (typeof showToast==='function') showToast('الكمية غير متوفرة');
      e.preventDefault();
    }
  });

  async function generateInvoiceImage(items, subTotal, taxAmount, total, orderSeq, fsTitle, fsItem, fsTotal) {
    const canvas = document.createElement('canvas'); const ctx = canvas.getContext('2d');
    const settings = await fetch('get_print_settings.php').then(r => r.json()); const cfg = {}; settings.forEach(s => cfg[s.key] = s);
    const widthMm = parseInt(cfg['print_width_mm']?.value) || 80; const pxPerMm = 7; const width = widthMm * pxPerMm;

    const infoLines = [];
    if (cfg['field_restaurant_name']?.value == 1) infoLines.push(cfg['restaurant_name']?.value || '');
    if (cfg['field_username']?.value == 1) infoLines.push('المستخدم: ' + '<?= htmlspecialchars($_SESSION["username"]) ?>');
    if (cfg['field_tax_number']?.value == 1) infoLines.push('الرقم الضريبي: ' + (cfg['tax_number']?.value || ''));
    infoLines.push(cfg['address']?.value || '');

    const rowH=45, headerH=100, infoH=infoLines.length*25, tableH=(items.length+1)*rowH, footerH=110, extra=270;
    canvas.width = width; canvas.height = headerH + infoH + tableH + footerH + extra;
    ctx.fillStyle='white'; ctx.fillRect(0, 0, width, canvas.height);

    let y = 10;
    if (cfg['field_restaurant_logo']?.value == 1 && cfg['logo_path']?.value) {
      const logo = new Image(); logo.src='images/logo.png'; await new Promise(r => logo.onload = r);
      const logoW = 200; const logoH = (logo.height * logoW) / logo.width; ctx.drawImage(logo, (width - logoW) / 2, y, logoW, logoH); y += logoH + 20;
    }

    ctx.fillStyle='black'; ctx.textAlign='center'; ctx.font = `${Math.max(16, fsTitle)}px Arial`; infoLines.forEach(line => { ctx.fillText(line, width / 2, y); y += 25; });
    ctx.font = `bold ${Math.max(16, fsTitle)}px Arial`; ctx.textAlign='right'; ctx.fillStyle='#111'; ctx.fillText(`رقم الطلب: ${orderSeq}`, width - 20, y + 40); y += 60;

    const cols = ['اسم', 'كمية', 'سعر إفرادي', 'سعر إجمالي']; const colW = [width*0.4, width*0.18, width*0.2, width*0.22];
    ctx.font = `bold ${Math.max(12, fsItem)}px Arial`; ctx.textAlign='center'; let x = width; cols.forEach((title, i) => { x -= colW[i]; ctx.strokeStyle = '#ccc'; ctx.strokeRect(x + 4, y, colW[i] - 8, rowH); ctx.fillText(title, x + colW[i] / 2, y + rowH / 2); }); y += rowH;

    ctx.font = `bold ${Math.max(12, fsItem)}px Arial`; ctx.fillStyle = '#000';
    items.forEach(item => { x = width; const nameLines = [item.name]; if (item.name_en) nameLines.push(item.name_en); const qty=item.quantity, price=item.unit_price, tot=(qty*price); const cells=[nameLines.join('\n'), qty, price.toFixed(2), tot.toFixed(2)]; cells.forEach((txt, i) => { x -= colW[i]; ctx.strokeStyle='#eee'; ctx.strokeRect(x + 4, y, colW[i]-8, rowH); if (i===0 && String(txt).includes('\n')) { String(txt).split('\n').forEach((ln, idx)=>{ ctx.fillText(ln, x + colW[i]/2, y + 18 + idx*18); }); } else { ctx.fillText(txt, x + colW[i]/2, y + rowH/2); } }); y += rowH; });

    y += 20; ctx.beginPath(); ctx.moveTo(20, y); ctx.lineTo(width-20, y); ctx.strokeStyle='#000'; ctx.lineWidth=1; ctx.stroke(); y += 30;

    ctx.font = `bold ${Math.max(12, fsTotal)}px Arial`; ctx.textAlign='right'; ctx.fillStyle='#111';
    ctx.fillText(`المجموع: ${subTotal.toFixed(2)}`, width - 4, y + 20);
    ctx.fillText(`الضريبة: ${taxAmount.toFixed(2)}`, width - 4, y + 45);
    ctx.fillText(`الإجمالي: ${total.toFixed(2)}`, width - 4, y + 70);

    ctx.textAlign='center'; ctx.font = `bold ${Math.max(12, fsItem)}px Arial`; ctx.fillText('شكراً لزيارتكم', width/2, y + 110);

    return canvas.toDataURL('image/png');
  }
});
</script>
