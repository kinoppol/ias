<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$date = trim($_POST['date'] ?? '');
$name = trim($_POST['name'] ?? '') ?: null;

if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $_SESSION['notif'] = ['msg' => 'กรุณาระบุวันที่ให้ถูกต้อง', 'type' => 'error'];
    header('Location: /ias/admin/holidays.php');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO holidays (date, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
$stmt->execute([$date, $name]);
$_SESSION['notif'] = ['msg' => '✅ บันทึกวันหยุดแล้ว', 'type' => 'success'];
header('Location: /ias/admin/holidays.php');
exit;
