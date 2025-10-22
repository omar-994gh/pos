<?php
require_once '../src/init.php'; require_once '../src/Auth.php';
require_once '../src/warehouse_management.php';
Auth::requireLogin(); if (!Auth::isAdmin()) exit;
$model = new Warehouse($db);
$id = $_POST['id'] ?? null;
$name = trim($_POST['name']);
if($id) $model->update($id,$name);
else    $model->create($name);
header('Location: warehouses.php');
?>