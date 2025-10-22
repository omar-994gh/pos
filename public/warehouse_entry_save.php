<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/WarehouseInvoice.php';
require_once __DIR__ . '/../src/WarehouseInvoiceItem.php';

Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

$invModel = new WarehouseInvoice($db);
$invoiceId = $invModel->create([
    'warehouse_id' => (int)$_POST['warehouse_id'],
    'supplier'     => trim($_POST['supplier']),
    'date'         => $_POST['date'],
    'entry_type'   => $_POST['entry_type'],
]);

if (!$invoiceId) { die('❌ فشل عند حفظ الرأسية.'); }

$itemModel = new WarehouseInvoiceItem($db);
$rows = count($_POST['item_id'] ?? []);
for ($i = 0; $i < $rows; $i++) {
    $data = [
        'invoice_id'  => $invoiceId,
        'item_id'     => (int)$_POST['item_id'][$i],
        'quantity'    => floatval($_POST['quantity'][$i]),
        'unit_price'  => floatval($_POST['unit_price'][$i]),
        'total_price' => floatval($_POST['total_price'][$i]),
        'sale_price'  => floatval($_POST['sale_price'][$i] ?? 0),
        'unit'        => trim($_POST['unit'][$i]),
    ];
    if (!$itemModel->create($data)) { die("❌ فشل عند حفظ البند رقم " . ($i+1)); }
}

header('Location: warehouse_entries.php');
exit;
