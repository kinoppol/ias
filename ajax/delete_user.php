<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$id = $_GET['id'] ?? ($_POST['id'] ?? '');
if ($id === current_user()['id']) {
    $_SESSION['notif'] = ['msg' => 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้', 'type' => 'error'];
} elseif ($id) {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    $_SESSION['notif'] = ['msg' => 'ลบผู้ใช้แล้ว', 'type' => 'success'];
}
header('Location: /ias/admin/users.php');
exit;
