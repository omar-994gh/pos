<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/Item.php';

header('Content-Type: application/json');

try {
    $itemModel = new Item($db);

    // توليد باركود عشوائي (13 خانة مثلاً)
    $barcode = null;
    do {
        $barcode = strval(random_int(1000000000000, 9999999999999));
        $stmt = $db->prepare("SELECT COUNT(*) FROM Items WHERE barcode = :b");
        $stmt->execute([':b' => $barcode]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);

    echo json_encode(['success' => true, 'barcode' => $barcode]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
