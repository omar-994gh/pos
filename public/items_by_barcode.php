<?php
// public/items_by_barcode.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Item.php';
Auth::requireLogin();

$bc = trim($_GET['barcode'] ?? '');
$item = null;
if ($bc !== '') {
    $stmt = $db->prepare(
        'SELECT id, name_ar, name_en, barcode, price, unit, group_id, stock, currency
         FROM Items 
         WHERE barcode = :bc'
    );
    $stmt->execute([':bc' => $bc]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode($item ?: []);
