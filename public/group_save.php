<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Group.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$groupModel = new Group($db);

$id        = $_POST['id'] ?? null;
$name      = trim($_POST['name'] ?? '');
$printerId = $_POST['printer_id'] !== '' ? (int)$_POST['printer_id'] : null;

if ($id) {
    $groupModel->update((int)$id, $name, $printerId);
} else {
    $groupModel->create($name, $printerId);
}

header('Location: groups.php');
exit;
