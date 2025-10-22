<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Printer.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$printerModel = new Printer($db);

$id      = $_POST['id'] ?? null;
$name    = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$type = trim($_POST['printer_type'] ?? '');

if ($id) {
    $printerModel->update((int)$id, $name, $address, $type);
} else {
    $printerModel->create($name, $address, $type);
}

header('Location: printers.php');
exit;
