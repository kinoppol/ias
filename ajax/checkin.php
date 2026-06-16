<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$lat = (float)($_POST['lat'] ?? 0);
$lng = (float)($_POST['lng'] ?? 0);
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare('SELECT * FROM workplaces WHERE id = ?');
$stmt->execute([$user['workplace_id']]);
$wp = $stmt->fetch();

$dist = $wp ? haversine_distance($lat, $lng, $wp['lat'], $wp['lng']) : 0;
$inRadius = $wp ? ($dist <= $wp['radius']) : true;
$status = compute_status($now, $wp['start_time'] ?? null);

$id = 'att_' . $today . '_' . $user['id'] . '_' . time();
$stmt = $pdo->prepare(
    'INSERT INTO attendance (id, student_id, date, check_in_time, check_in_lat, check_in_lng, check_in_dist, check_in_in_radius, status, workplace_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE check_in_time=VALUES(check_in_time), check_in_lat=VALUES(check_in_lat), check_in_lng=VALUES(check_in_lng), check_in_dist=VALUES(check_in_dist), check_in_in_radius=VALUES(check_in_in_radius), status=VALUES(status)'
);
$stmt->execute([$id, $user['id'], $today, $now, $lat, $lng, round($dist), $inRadius ? 1 : 0, $status, $user['workplace_id']]);

$statusLabel = status_info($status)['label'];
$msg = $inRadius
    ? "✅ เช็คอินสำเร็จ! สถานะ: $statusLabel"
    : '⚠️ เช็คอินสำเร็จ แต่อยู่นอกพื้นที่ (' . round($dist) . ' ม.) — บันทึกไว้แล้ว';
$_SESSION['notif'] = ['msg' => $msg, 'type' => $inRadius ? 'success' : 'warning'];

json_response(['ok' => true]);
