<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SalesLog.php';
require_once __DIR__ . '/../src/ExchangeRate.php';

Auth::requireLogin();
if (!Auth::isAdmin()) { 
    header('Location: dashboard.php'); 
    exit; 
}

$from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['date_to'] ?? date('Y-m-d');
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$model = new SalesLog($db);
$summary = $model->summary($from, $to, $userId);
$details = $model->details($from, $to, $userId);

$exchangeRateManager = new ExchangeRate($db);
$exchangeSettings = $exchangeRateManager->getSystemSettings();

$usersList = $db->query("SELECT id, username FROM Users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get order details if order_id is specified
$orderDetails = [];
if ($orderId) {
    $stmt = $db->prepare("
        SELECT oi.*, o.created_at, o.total as order_total, u.username, i.name_ar as item_name
        FROM Order_Items oi
        JOIN Orders o ON oi.order_id = o.id
        JOIN Users u ON o.user_id = u.id
        JOIN Items i ON oi.item_id = i.id
        WHERE o.id = ?
        ORDER BY oi.order_id
    ");
    $stmt->execute([$orderId]);
    $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<main class="container mt-4">
    <h2>استرداد المبيعات</h2>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <form method="get" class="row g-2">
            <div class="col-auto">
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($from) ?>" placeholder="من">
            </div>
            <div class="col-auto">
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($to) ?>" placeholder="إلى">
            </div>
            <div class="col-auto">
                <select name="user_id" class="form-select">
                    <option value="0">كل المستخدمين</option>
                    <?php foreach($usersList as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $userId===$u['id']?'selected':'' ?>>
                            <?= htmlspecialchars($u['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">تصفية</button>
            </div>
        </form>
        
        <div>
            <a href="sales_log.php" class="btn btn-outline-secondary">عودة لسجل المبيعات</a>
        </div>
    </div>

    <?php if ($orderId && !empty($orderDetails)): ?>
        <!-- Order Details Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>تفاصيل الطلب #<?= $orderId ?></h5>
                <p class="mb-1">المستخدم: <?= htmlspecialchars($orderDetails[0]['username']) ?></p>
                <p class="mb-1">التاريخ: <?= htmlspecialchars($orderDetails[0]['created_at']) ?></p>
                <p class="mb-0">المجموع: <?= number_format($orderDetails[0]['order_total'], 2) ?> <?= htmlspecialchars($exchangeSettings['base_currency']) ?></p>
            </div>
            <div class="card-body">
                <div class="mb-3 text-start">
                    <button type="button" class="btn btn-danger" id="refundAllBtn" data-order-id="<?= $orderId ?>">استرداد كل الأصناف</button>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>الصنف</th>
                            <th>الكمية المباعة</th>
                            <th>السعر</th>
                            <th>المجموع</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderDetails as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['unit_price'], 2) ?></td>
                                <td><?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning refund-btn" 
                                            data-item-id="<?= $item['item_id'] ?>"
                                            data-order-id="<?= $item['order_id'] ?>"
                                            data-quantity="<?= $item['quantity'] ?>"
                                            data-unit-price="<?= $item['unit_price'] ?>"
                                            data-item-name="<?= htmlspecialchars($item['item_name']) ?>">
                                        استرداد
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sales Summary -->
    <h4>ملخص المبيعات حسب المستخدم</h4>
    <table class="table table-bordered mb-5">
        <thead>
            <tr>
                <th>المستخدم</th>
                <th>عدد الفواتير</th>
                <th>إجمالي المبيعات</th>
                <th>متوسط قيمة الفاتورة</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($summary as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $row['sale_count'] ?></td>
                    <td><?= number_format($row['total_amount'], 2) ?> <?= htmlspecialchars($exchangeSettings['base_currency']) ?></td>
                    <td><?= number_format($row['avg_amount'], 2) ?> <?= htmlspecialchars($exchangeSettings['base_currency']) ?></td>
                    <td>
                        <a href="?date_from=<?= urlencode($from) ?>&date_to=<?= urlencode($to) ?>&user_id=<?= $row['user_id'] ?>" 
                           class="btn btn-sm btn-info">عرض الفواتير</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($summary)): ?>
                <tr><td colspan="5" class="text-center">لا توجد بيانات</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Sales Details -->
    <h4>تفاصيل الفواتير</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ والوقت</th>
                <th>المستخدم</th>
                <th>الإجمالي</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $o): ?>
                <tr>
                    <td><?= $o['order_id'] ?></td>
                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                    <td><?= htmlspecialchars($o['username']) ?></td>
                    <td><?= number_format($o['total'], 2) ?> <?= htmlspecialchars($exchangeSettings['base_currency']) ?></td>
                    <td>
                        <a href="?date_from=<?= urlencode($from) ?>&date_to=<?= urlencode($to) ?>&user_id=<?= $userId ?>&order_id=<?= $o['order_id'] ?>" 
                           class="btn btn-sm btn-primary">عرض التفاصيل</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($details)): ?>
                <tr><td colspan="5" class="text-center">لا توجد فواتير</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">استرداد الصنف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
            </div>
            <div class="modal-body">
                <form id="refundForm">
                    <input type="hidden" id="refundItemId" name="item_id">
                    <input type="hidden" id="refundOrderId" name="order_id">
                    
                    <div class="mb-3">
                        <label class="form-label">اسم الصنف</label>
                        <input type="text" id="refundItemName" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الكمية المباعة</label>
                        <input type="number" id="refundQuantity" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">كمية الاسترداد</label>
                        <input type="number" id="refundAmount" name="refund_amount" class="form-control" min="1" value="1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">سبب الاسترداد</label>
                        <textarea id="refundReason" name="refund_reason" class="form-control" rows="3" placeholder="أدخل سبب الاسترداد"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button> -->
                <button type="button" class="btn btn-warning" id="confirmRefund">تأكيد الاسترداد</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/jquery.min.js"></script>
<script src="../assets/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refund ALL items button
    const btnAll = document.getElementById('refundAllBtn');
    if (btnAll) {
        btnAll.addEventListener('click', async function() {
            const orderId = this.dataset.orderId;
            const reason = prompt('سبب الاسترداد لكل الأصناف:');
            if (reason === null) return;
            const finalReason = (reason || '').trim();
            if (!finalReason) { alert('الرجاء كتابة سبب الاسترداد.'); return; }

            if (!confirm('هل تريد استرداد جميع الأصناف في هذه الفاتورة؟')) return;

            const fd = new FormData();
            fd.append('action', 'refund_all');
            fd.append('order_id', orderId);
            fd.append('refund_reason', finalReason);

            try {
                const resp = await fetch('refund_handler.php', { method:'POST', body: fd });
                const res = await resp.json();
                if (res.success) {
                    alert('تم استرداد كل الأصناف بنجاح');
                    location.reload();
                } else {
                    alert('فشل في الاسترداد: ' + (res.error || 'غير معروف'));
                }
            } catch (e) {
                alert('حدث خطأ أثناء الاسترداد');
            }
        });
    }

    // Refund button click handler
    document.querySelectorAll('.refund-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const orderId = this.dataset.orderId;
            const quantity = this.dataset.quantity;
            const unitPrice = this.dataset.unitPrice;
            const itemName = this.dataset.itemName;
            
            document.getElementById('refundItemId').value = itemId;
            document.getElementById('refundOrderId').value = orderId;
            document.getElementById('refundItemName').value = itemName;
            document.getElementById('refundQuantity').value = quantity;
            document.getElementById('refundAmount').max = quantity;
            document.getElementById('refundAmount').value = 1;
            document.getElementById('refundReason').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('refundModal'));
            modal.show();
        });
    });

    // 3) تأكيد الاسترداد
    document.getElementById('confirmRefund').addEventListener('click', async function () {
        const formData = new FormData(refundForm);

        try {
            const response = await fetch('refund_handler.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // refundModal.hide();
                alert('تم الاسترداد بنجاح');
                location.reload();
            } else {
                alert('فشل في الاسترداد: ' + result.error);
            }
        } catch (error) {
            alert('حدث خطأ أثناء الاسترداد');
        }
    });

    // 4) زر الإلغاء: نعتمد على data-bs-dismiss + Fallback إخفاء يدوي
    const cancelBtn = refundModalEl.querySelector('[data-bs-dismiss="modal"]');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            // Fallback في حال تعارض يمنع Bootstrap من الالتقاط
            refundModal.hide();
        });
    }

    // 5) صفّر النموذج عند إغلاق المودال حتى يفتح نظيف في كل مرة
    refundModalEl.addEventListener('hidden.bs.modal', function () {
        refundForm.reset();
        document.getElementById('refundItemId').value = '';
        document.getElementById('refundOrderId').value = '';
        document.getElementById('refundAmount').removeAttribute('max');
    });
});
</script>

</body>
</html>