<?php
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['original_admin'])) {
    header('Location: /ias/index.php');
    exit;
}

$_SESSION['user'] = $_SESSION['original_admin'];
unset($_SESSION['original_admin']);
$_SESSION['notif'] = ['msg' => '✅ คืนสิทธิ์ผู้ดูแลระบบแล้ว', 'type' => 'success'];
header('Location: /ias/admin/users.php');
exit;
