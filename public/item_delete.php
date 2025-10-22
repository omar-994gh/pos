<?php
// public/item_delete.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Item.php';

Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

$acceptsJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($acceptsJson) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }
    header('Location: items.php'); exit;
}

$id      = $_GET['id'] ?? null;
$groupId = $_GET['group_id'] ?? null;
if ($id) { (new Item($db))->delete((int)$id); }

if ($acceptsJson) { header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit; }
header("Location: items.php?group_id=$groupId");
exit;
