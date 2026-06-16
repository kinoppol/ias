<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$lat = (float)($_POST['lat'] ?? 0);
$lng = (float)($_POST['lng'] ?? 0);
$today = date('Y-m-d');
$now = new DateTime();

$stmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? AND date = ?');
$stmt->execute([$user['id'], $today]);
$todayAtt = $stmt->fetch();

if (!$todayAtt || !$todayAtt['check_in_time']) {
    $_SESSION['notif'] = ['msg' => 'กรุณาเช็คอินก่อนเช็คเอาท์', 'type' => 'error'];
    json_response(['ok' => false]);
}
if ($todayAtt['check_out_time']) {
    $_SESSION['notif'] = ['msg' => 'คุณเช็คเอาท์ไปแล้ววันนี้', 'type' => 'error'];
    json_response(['ok' => false]);
}

$stmt = $pdo->prepare('SELECT * FROM workplaces WHERE id = ?');
$stmt->execute([$user['workplace_id']]);
$wp = $stmt->fetch();

if ($wp) {
    $endSched = new DateTime($today . ' ' . $wp['end_time']);
    if ($now < $endSched) {
        $_SESSION['notif'] = ['msg' => 'ยังไม่ถึงเวลาเลิกงาน (' . substr($wp['end_time'], 0, 5) . ' น.) — ไม่สามารถเช็คเอาท์ได้', 'type' => 'error'];
        json_response(['ok' => false]);
    }
}

$nowStr = $now->format('Y-m-d H:i:s');
$stmt = $pdo->prepare('UPDATE attendance SET check_out_time = ?, check_out_lat = ?, check_out_lng = ? WHERE student_id = ? AND date = ?');
$stmt->execute([$nowStr, $lat, $lng, $user['id'], $today]);

$_SESSION['notif'] = ['msg' => '✅ เช็คเอาท์สำเร็จ! ขอบคุณสำหรับการทำงานวันนี้', 'type' => 'success'];
json_response(['ok' => true]);
