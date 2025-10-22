<?php
// public/logout.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';

// هذه الدالة ستمسح الجلسة وتعيد توجيه المستخدم إلى صفحة تسجيل الدخول
Auth::logout();
?>