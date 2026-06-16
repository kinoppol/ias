<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

$id = $_POST['id'] ?? '';
$startTime = $_POST['start_time'] ?? null;
$endTime = $_POST['end_time'] ?? null;
$radius = isset($_POST['radius']) ? (int)$_POST['radius'] : null;

if ($startTime) {
    $pdo->prepare('UPDATE workplaces SET start_time = ? WHERE id = ?')->execute([$startTime, $id]);
}
if ($endTime) {
    $pdo->prepare('UPDATE workplaces SET end_time = ? WHERE id = ?')->execute([$endTime, $id]);
}
if ($radius !== null && $radius > 0) {
    $pdo->prepare('UPDATE workplaces SET radius = ? WHERE id = ?')->execute([$radius, $id]);
}

$_SESSION['notif'] = ['msg' => '💾 บันทึกข้อมูลแล้ว', 'type' => 'success'];
header('Location: /ias/teacher/schedule.php');
exit;
