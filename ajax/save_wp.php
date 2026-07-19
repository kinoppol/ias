<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$id = trim($_POST['id'] ?? '');
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';
$radius = (int)($_POST['radius'] ?? 200);
$startTime = $_POST['start_time'] ?? '08:00';
$endTime = $_POST['end_time'] ?? '17:00';
$teacherId = trim($_POST['teacher_id'] ?? '') ?: null;
$allowOt = isset($_POST['allow_ot']) ? 1 : 0;
$workDaysArr = $_POST['work_days'] ?? [];
$workDays = '';
for ($i = 0; $i < 7; $i++) {
    $workDays .= in_array((string)$i, $workDaysArr, true) ? '1' : '0';
}
if (strlen($workDays) !== 7) $workDays = '1111100';

if ($name === '' || $address === '' || $lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
    $_SESSION['notif'] = ['msg' => 'กรุณากรอกชื่อ ที่อยู่ และพิกัด (latitude/longitude) ให้ถูกต้อง', 'type' => 'error'];
    header('Location: /ias/admin/workplaces.php');
    exit;
}

if ($id) {
    // Edit existing
    $stmt = $pdo->prepare('UPDATE workplaces SET name=?, address=?, lat=?, lng=?, radius=?, start_time=?, end_time=?, teacher_id=?, allow_ot=?, work_days=? WHERE id=?');
    $stmt->execute([$name, $address, $lat, $lng, $radius, $startTime, $endTime, $teacherId, $allowOt, $workDays, $id]);
    $_SESSION['notif'] = ['msg' => '💾 บันทึกข้อมูลสถานประกอบการแล้ว', 'type' => 'success'];
} else {
    // Create new — generate next WPxxx id
    $maxRow = $pdo->query("SELECT id FROM workplaces WHERE id REGEXP '^WP[0-9]+$' ORDER BY CAST(SUBSTRING(id,3) AS UNSIGNED) DESC LIMIT 1")->fetch();
    $next = 1;
    if ($maxRow && preg_match('/^WP(\d+)$/', $maxRow['id'], $m)) {
        $next = (int)$m[1] + 1;
    }
    $newId = 'WP' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare('INSERT INTO workplaces (id, name, address, lat, lng, radius, start_time, end_time, teacher_id, allow_ot, active, work_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
    $stmt->execute([$newId, $name, $address, $lat, $lng, $radius, $startTime, $endTime, $teacherId, $allowOt, $workDays]);
    $_SESSION['notif'] = ['msg' => '✅ เพิ่มสถานประกอบการแล้ว', 'type' => 'success'];
}

header('Location: /ias/admin/workplaces.php');
exit;
