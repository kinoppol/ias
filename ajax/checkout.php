<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$lat = (float)($_POST['lat'] ?? 0);
$lng = (float)($_POST['lng'] ?? 0);
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare('UPDATE attendance SET check_out_time = ?, check_out_lat = ?, check_out_lng = ? WHERE student_id = ? AND date = ?');
$stmt->execute([$now, $lat, $lng, $user['id'], $today]);

$_SESSION['notif'] = ['msg' => '✅ เช็คเอาท์สำเร็จ! ขอบคุณสำหรับการทำงานวันนี้', 'type' => 'success'];
json_response(['ok' => true]);
