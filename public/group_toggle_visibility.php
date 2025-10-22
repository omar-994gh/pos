<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Group.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: groups.php');
    exit;
}

$groupModel = new Group($db);
$id = (int)$_GET['id'];

if ($groupModel->toggleVisibility($id)) {
    header('Location: groups.php?msg=visibility_toggled');
} else {
    header('Location: groups.php?error=toggle_failed');
}
exit;
