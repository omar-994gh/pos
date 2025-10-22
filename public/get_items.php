<?php
// public/get_items.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Item.php';

Auth::requireLogin();

$groupId = intval($_GET['group_id'] ?? 0);
$itemModel = new Item($db);

if (isset($_GET['single_id'])) {
    // جلب صنف واحد
    $item = $itemModel->find(intval($_GET['single_id']));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($item);
    exit;
}

// جلب قائمة الأصناف
$items = $itemModel->allByGroup($groupId);

// إرجاع JSON
header('Content-Type: application/json; charset=utf-8');
// Ensure group_id present
$items = array_map(function($r){ if (!isset($r['group_id'])) { /* fallback: nothing */ } return $r; }, $items);
echo json_encode($items);
