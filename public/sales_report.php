<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تقرير المبيعات</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <style>
    body.printing {
      background-image: url('../assets/watermark.png');
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      -webkit-print-color-adjust: exact;
    }

    @media print {
      body {
        background-image: url('../assets/watermark.png') !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        -webkit-print-color-adjust: exact;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>
  <div class="container mt-4">
    <div class="no-print mb-3 d-flex justify-content-between align-items-center">
      <h4>تقرير المواد المباعة</h4>
      <div>
        <label>من: <input type="date" id="fromDate" class="form-control d-inline w-auto"></label>
        <label>إلى: <input type="date" id="toDate" class="form-control d-inline w-auto"></label>
        <button class="btn btn-primary" onclick="filterSales()">فلترة</button>
        <button class="btn btn-success" onclick="window.print()">طباعة</button>
      </div>
    </div>

    <table class="table table-bordered table-striped" id="salesTable">
      <thead class="table-dark">
        <tr>
          <th>رقم الطلب</th>
          <th>تاريخ الطلب</th>
          <th>اسم المستخدم</th>
          <th>المادة</th>
          <th>الكمية</th>
          <th>سعر الوحدة</th>
          <th>الإجمالي</th>
        </tr>
      </thead>
      <tbody id="salesBody">
        <!-- بيانات الطلبات هنا -->
      </tbody>
      <tfoot>
        <tr class="table-secondary">
          <td colspan="6" class="text-end fw-bold">الإجمالي الكلي:</td>
          <td id="totalSum" class="fw-bold text-success"></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <script>
    async function fetchSales(fromDate = '', toDate = '') {
      const url = new URL('../api/get_sales.php', window.location.origin);
      if (fromDate) url.searchParams.append('from', fromDate);
      if (toDate) url.searchParams.append('to', toDate);

      const res = await fetch(url);
      const data = await res.json();

      const salesBody = document.getElementById('salesBody');
      const totalSum = document.getElementById('totalSum');
      salesBody.innerHTML = '';

      let total = 0;
      data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${row.order_id}</td>
          <td>${row.date}</td>
          <td>${row.username}</td>
          <td>${row.item_name}</td>
          <td>${row.quantity}</td>
          <td>${row.unit_price.toFixed(2)}</td>
          <td>${(row.quantity * row.unit_price).toFixed(2)}</td>
        `;
        salesBody.appendChild(tr);
        total += row.quantity * row.unit_price;
      });
      totalSum.textContent = total.toFixed(2);
    }

    function filterSales() {
      const fromDate = document.getElementById('fromDate').value;
      const toDate = document.getElementById('toDate').value;
      fetchSales(fromDate, toDate);
    }

    // تحميل البيانات مبدئياً
    window.onload = () => fetchSales();
  </script>
</body>
</html>
