<?php
require_once '../src/init.php';
require_once '../src/Auth.php';
require_once '../src/warehouse_management.php';
Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: warehouses.php'); exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $model = new Warehouse($db);
    $model->delete($id);
}
header('Location: warehouses.php');
exit;
?>