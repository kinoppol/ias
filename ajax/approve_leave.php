<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

$user = current_user();
$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? '';
if (!in_array($status, ['approved', 'rejected'], true)) { exit; }

$stmt = $pdo->prepare('UPDATE leaves SET status = ?, approved_by = ? WHERE id = ?');
$stmt->execute([$status, $user['id'], $id]);

$_SESSION['notif'] = ['msg' => $status === 'approved' ? '✅ อนุมัติการลาแล้ว' : '❌ ปฏิเสธการลาแล้ว', 'type' => $status === 'approved' ? 'success' : 'error'];
header('Location: /ias/teacher/leaves.php');
exit;
