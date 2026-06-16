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
    return ['student' => 'นักศึกษาฝึกงาน', 'teacher' => 'ครูนิเทศ', 'admin' => 'ผู้ดูแลระบบ'][$role] ?? $role;
}

function role_avatar($role) {
    return ['student' => '👨‍🎓', 'teacher' => '👩‍🏫', 'admin' => '⚙️'][$role] ?? '👤';
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
