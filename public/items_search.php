<?php
// public/items_search.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Item.php';
Auth::requireLogin();

$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    $stmt = $db->prepare(
        'SELECT id, name_ar, name_en, barcode, unit, price
         FROM Items 
         WHERE name_ar LIKE :q OR name_en LIKE :q 
         LIMIT 10'
    );
    $stmt->execute([':q' => "%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode($results);
