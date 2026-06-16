<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'teacher']);

$id = $_GET['id'] ?? ($_POST['id'] ?? '');
if ($id) {
    $pdo->prepare('UPDATE workplaces SET active = NOT active WHERE id = ?')->execute([$id]);
}
$redirect = current_user()['role'] === 'admin' ? '/ias/admin/workplaces.php' : '/ias/teacher/schedule.php';
header('Location: ' . $redirect);
exit;
