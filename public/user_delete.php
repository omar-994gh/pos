<?php
// public/user_delete.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';

Auth::requireLogin();
if (!Auth::isAdmin()) { header('Location: dashboard.php'); exit; }

$acceptsJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($acceptsJson) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }
    header('Location: users.php'); exit;
}

$id = $_GET['id'] ?? null;
if ($id) { (new User($db))->delete((int)$id); }

if ($acceptsJson) { header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit; }
header('Location: users.php');
exit;
