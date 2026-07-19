<?php
$EARLY_CHECKIN_MINUTES = 60;
$LATE_GRACE_MINUTES = 30;

function status_info($status) {
    $map = [
        'present'  => ['label' => 'มาตรงเวลา',    'color' => '#16A34A', 'bg' => '#DCFCE7', 'icon' => '✅'],
        'late'     => ['label' => 'มาสาย',         'color' => '#D97706', 'bg' => '#FEF3C7', 'icon' => '⚠️'],
        'half-day' => ['label' => 'ขาดครึ่งวัน',    'color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => '❌'],
        'absent'   => ['label' => 'ขาดงาน',        'color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => '❌'],
    ];
    return $map[$status] ?? ['label' => 'ยังไม่เช็ค', 'color' => '#64748B', 'bg' => '#F1F5F9', 'icon' => '⏱'];
}

function compute_status($checkInDateTime, $startTime, $graceMinutes = null) {
    global $pdo, $LATE_GRACE_MINUTES;
    if ($graceMinutes === null) {
        $graceMinutes = $LATE_GRACE_MINUTES;
        if (isset($pdo)) {
            $stmt = $pdo->prepare('SELECT v FROM settings WHERE k = ?');
            $stmt->execute(['late_grace_minutes']);
            $row = $stmt->fetch();
            if ($row) $graceMinutes = (int)$row['v'];
        }
    }
    if (!$checkInDateTime || !$startTime) return 'present';
    $ci = new DateTime($checkInDateTime);
    $scheduled = new DateTime($ci->format('Y-m-d') . ' ' . $startTime);
    $diffMin = ($ci->getTimestamp() - $scheduled->getTimestamp()) / 60;
    if ($diffMin > $graceMinutes) return 'half-day';
    if ($diffMin > 0) return 'late';
    return 'present';
}

function haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $R * 2 * asin(sqrt($a));
}

function fmt_time($dt) {
    if (!$dt) return '-';
    $d = new DateTime($dt);
    return $d->format('H:i');
}

function fmt_date_th($dateStr) {
    static $days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    static $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $d = new DateTime($dateStr);
    return $days[(int)$d->format('w')] . ' ' . (int)$d->format('j') . ' ' . $months[(int)$d->format('n')];
}

function role_label($role) {
    return ['student' => 'นักศึกษาฝึกงาน', 'teacher' => 'ครูนิเทศ', 'admin' => 'ผู้ดูแลระบบ', 'trainer' => 'ครูฝึก'][$role] ?? $role;
}

function role_avatar($role) {
    return ['student' => '👨‍🎓', 'teacher' => '👩‍🏫', 'admin' => '⚙️', 'trainer' => '🧑‍💼'][$role] ?? '👤';
}

function task_status_info($status) {
    return [
        'active'     => ['label' => 'กำลังดำเนินการ', 'color' => '#1565C0', 'bg' => '#DBEAFE', 'icon' => '🔵'],
        'completed'  => ['label' => 'เสร็จสิ้น',       'color' => '#16A34A', 'bg' => '#DCFCE7', 'icon' => '✅'],
        'terminated' => ['label' => 'สิ้นสุด (ไม่เสร็จ)', 'color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => '🔴'],
    ][$status] ?? ['label' => $status, 'color' => '#64748B', 'bg' => '#F1F5F9', 'icon' => '⏱'];
}

function save_attachments_to_db($pdo, $taskId = null, $threadId = null, $files = [], $links = []) {
    $table = $threadId ? 'task_thread_attachments' : 'task_attachments';
    $col   = $threadId ? 'thread_id' : 'task_id';
    $refId = $threadId ?? $taskId;

    $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','zip','txt','mp4','mov'];
    $uploadDir = __DIR__ . '/../uploads/tasks/' . ($taskId ?? 0) . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach ($files as $file) {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $stored = uniqid('f_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $stored)) continue;
        $stmt = $pdo->prepare("INSERT INTO $table ($col, att_type, original_name, stored_name, mime_type, file_size) VALUES (?, 'file', ?, ?, ?, ?)");
        $stmt->execute([$refId, $file['name'], $stored, $file['type'], $file['size']]);
    }
    foreach ($links as $url) {
        $url = trim($url);
        if (!$url) continue;
        $stmt = $pdo->prepare("INSERT INTO $table ($col, att_type, link_url) VALUES (?, 'link', ?)");
        $stmt->execute([$refId, $url]);
    }
}

function get_task_attachments($pdo, $taskId) {
    return $pdo->prepare('SELECT * FROM task_attachments WHERE task_id = ? ORDER BY id')
               ->execute([$taskId]) ? $pdo->query("SELECT * FROM task_attachments WHERE task_id = $taskId ORDER BY id")->fetchAll() : [];
}

function attachment_icon($mime, $attType) {
    if ($attType === 'link') return '🔗';
    if (strpos($mime, 'image') === 0) return '🖼';
    if ($mime === 'application/pdf') return '📄';
    if (strpos($mime, 'word') !== false || strpos($mime, 'document') !== false) return '📝';
    if (strpos($mime, 'excel') !== false || strpos($mime, 'sheet') !== false) return '📊';
    if (strpos($mime, 'zip') !== false) return '🗜';
    if (strpos($mime, 'video') === 0) return '🎬';
    return '📎';
}

function leave_type_label($type) {
    return ['sick' => 'ลาป่วย', 'personal' => 'ลากิจ', 'holiday' => 'วันหยุดนักขัตฤกษ์'][$type] ?? $type;
}

function leave_status_info($status) {
    $map = [
        'pending'  => ['label' => 'รอการอนุมัติ', 'color' => '#D97706', 'bg' => '#FEF3C7'],
        'approved' => ['label' => 'อนุมัติแล้ว',   'color' => '#16A34A', 'bg' => '#DCFCE7'],
        'rejected' => ['label' => 'ไม่อนุมัติ',     'color' => '#DC2626', 'bg' => '#FEE2E2'],
    ];
    return $map[$status] ?? ['label' => $status, 'color' => '#64748B', 'bg' => '#F1F5F9'];
}

function short_wp_name($name) {
    if (!$name) return '-';
    return str_replace([' จำกัด'], '', str_replace('บริษัท ', '', $name));
}

function json_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
