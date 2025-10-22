<?php
// public/warehouse_entry_form.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Warehouse.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$type = in_array($_GET['type'] ?? '', ['IN','OUT']) ? $_GET['type'] : 'IN';
$whModel = new Warehouse($db);
$warehouses = $whModel->allWh();
?>
<?php include 'header.php'; ?>

<main class="container mt-4">
  <h2>فاتورة <?= $type === 'IN' ? 'إدخال' : 'إخراج' ?></h2>
  <form id="entryForm" action="warehouse_entry_save.php" method="post">
    <input type="hidden" name="entry_type" value="<?= $type ?>">
    <div class="row mb-3">
      <div class="col-md-4">
        <label>المخزن</label>
        <select name="warehouse_id" class="form-control" required>
          <option value="">— اختر —</option>
          <?php foreach ($warehouses as $w): ?>
            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label>المورد</label>
        <input type="text" name="supplier" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label>التاريخ والوقت</label>
        <input type="datetime-local" name="date" class="form-control"
               value="<?= date('Y-m-d\TH:i') ?>" required>
      </div>
    </div>

    <h5>تفاصيل الأصناف</h5>
    <table class="table" id="itemsTable">
      <thead>
        <tr>
          <th>الصنف</th>
          <th>باركود</th>
          <th>سعر الوحدة</th>
          <th>العدد</th>
          <th>الوحدة</th>
          <th>الإجمالي</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr class="item-row">
          <td style="position:relative">
            <input name="item_name[]" class="form-control item-search" placeholder="إبدأ الكتابة..." required>
            <input name="item_id[]" class="form-control item-id" hidden required>
            <div class="suggestions list-group position-absolute w-100" style="z-index:1000;"></div>
          </td>
          <td><input name="barcode[]" class="form-control barcode-input"></td>
          <td><input name="unit_price[]" type="number" step="0.01" class="form-control unit-price"></td>
          <td><input name="quantity[]" type="number" step="0.01" class="form-control qty-input"></td>
          <td><input name="unit[]" class="form-control unit"></td>
          <td><input name="total_price[]" class="form-control total-price" readonly></td>
          <td><button type="button" class="btn btn-sm btn-danger remove-row">–</button></td>
        </tr>
      </tbody>
    </table>
    <button type="button" id="addRow" class="btn btn-secondary mb-3">+ إضافة صنف</button>
    <br>
    <button type="submit" class="btn btn-primary">حفظ الفاتورة</button>
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector('#itemsTable tbody');

  function bindRow(row) {
    const itemInput    = row.querySelector('.item-search');
    const itemId       = row.querySelector('.item-id');
    const suggestions  = row.querySelector('.suggestions');
    const barcodeInput = row.querySelector('.barcode-input');
    const unitPrice    = row.querySelector('.unit-price');
    const qtyInput     = row.querySelector('.qty-input');
    const unitInput    = row.querySelector('.unit');
    const totalPrice   = row.querySelector('.total-price');
    const removeBtn    = row.querySelector('.remove-row');

    // بحث فوري بالاسم
    itemInput.addEventListener('input', async () => {
      const q = itemInput.value.trim();
      suggestions.innerHTML = '';
      if (q.length < 2) return;
      const res = await fetch(`items_search.php?q=${encodeURIComponent(q)}`);
      const items = await res.json();
      items.forEach(it => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = it.name_ar + ' — ' + it.name_en;
        a.addEventListener('click', e => {
          e.preventDefault();
          selectItem(it);
        });
        suggestions.appendChild(a);
      });
    });

    // بحث بالباركود
    barcodeInput.addEventListener('change', async () => {
      const bc = barcodeInput.value.trim();
      if (!bc) return;
      const res = await fetch(`items_by_barcode.php?barcode=${encodeURIComponent(bc)}`);
      const it = await res.json();
      if (it.id) selectItem(it);
    });

    function selectItem(it) {
      itemId.value       = it.id
      itemInput.value    = it.name_ar;
      barcodeInput.value = it.barcode;
      unitPrice.value    = it.price;
      unitInput.value    = it.unit;
      qtyInput.value     = 1;
      updateTotal();
      suggestions.innerHTML = '';
    }

    // حساب الإجمالي
    function updateTotal() {
      const qty = parseFloat(qtyInput.value) || 0;
      const up  = parseFloat(unitPrice.value) || 0;
      totalPrice.value = (qty * up).toFixed(2);
    }
    qtyInput.addEventListener('input', updateTotal);
    unitPrice.addEventListener('input', updateTotal);

    // إزالة الصف
    removeBtn.addEventListener('click', () => {
      if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
      }
    });
  }

  // ربط الصف الأول
  bindRow(tbody.querySelector('tr'));

  // إضافة صف جديد
  document.getElementById('addRow').addEventListener('click', () => {
    const first = tbody.querySelector('tr');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(clone);
    bindRow(clone);
  });
});
</script>
