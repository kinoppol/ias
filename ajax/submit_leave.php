<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$type = $_POST['type'] ?? 'sick';
$date = $_POST['date'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!$date || !$reason) {
    $_SESSION['notif'] = ['msg' => 'กรุณากรอกวันที่และเหตุผล', 'type' => 'error'];
    header('Location: /ias/student/leave.php');
    exit;
}

$id = 'LV' . time() . rand(100, 999);
$stmt = $pdo->prepare('INSERT INTO leaves (id, student_id, date, type, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$id, $user['id'], $date, $type, $reason, 'pending']);

$_SESSION['notif'] = ['msg' => '📤 ส่งคำขอลาแล้ว รอครูนิเทศอนุมัติ', 'type' => 'success'];
header('Location: /ias/student/leave.php');
exit;
