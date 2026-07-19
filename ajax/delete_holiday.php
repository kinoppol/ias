<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('DELETE FROM holidays WHERE id = ?')->execute([$id]);
    $_SESSION['notif'] = ['msg' => 'ลบวันหยุดแล้ว', 'type' => 'success'];
}
header('Location: /ias/admin/holidays.php');
exit;
