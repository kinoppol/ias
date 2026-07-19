<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$today = date('Y-m-d');

// Get workplace (for work_days)
$wp = null;
if ($user['workplace_id']) {
    $stmt = $pdo->prepare('SELECT * FROM workplaces WHERE id = ?');
    $stmt->execute([$user['workplace_id']]);
    $wp = $stmt->fetch();
}
$workDays = $wp['work_days'] ?? '1111100'; // default Mon-Fri

// Show last 60 days
$fromDate = date('Y-m-d', strtotime('-60 days'));

// Fetch attendance records in range
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC');
$stmt->execute([$user['id'], $fromDate, $today]);
$attMap = [];
foreach ($stmt->fetchAll() as $a) $attMap[$a['date']] = $a;

// Fetch holidays in range
$stmt = $pdo->prepare('SELECT date FROM holidays WHERE date BETWEEN ? AND ?');
$stmt->execute([$fromDate, $today]);
$holidaySet = [];
foreach ($stmt->fetchAll() as $h) $holidaySet[$h['date']] = true;

// Build date list (newest first)
$displayRows = [];
$d = new DateTime($today);
$start = new DateTime($fromDate);
while ($d >= $start) {
    $dateStr = $d->format('Y-m-d');
    $dow = (int)$d->format('N'); // 1=Mon..7=Sun, index=dow-1
    $isHoliday = isset($holidaySet[$dateStr]);
    $isWorkDay = ($workDays[$dow - 1] ?? '0') === '1' && !$isHoliday;
    $att = $attMap[$dateStr] ?? null;

    if ($att) {
        // Has attendance record — always show
        $displayRows[] = ['date' => $dateStr, 'att' => $att, 'type' => 'att', 'holiday' => $isHoliday];
    } elseif ($isHoliday) {
        // Holiday with no attendance — skip (clean UI)
    } elseif ($isWorkDay && $dateStr < $today) {
        // Working day, past, no record → absent
        $displayRows[] = ['date' => $dateStr, 'att' => null, 'type' => 'absent'];
    }
    // Non-work days with no record → skip

    $d->modify('-1 day');
}

$activeSection = 'history';
$pageTitle = 'ประวัติการเข้างาน';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">📋 ประวัติการเข้างาน</div>
<div class="table-card">
  <div class="history-grid-header">
    <div>วันที่</div>
    <div style="text-align:center;">เข้างาน</div>
    <div style="text-align:center;">ออกงาน</div>
    <div style="text-align:center;">สถานะ</div>
  </div>
  <?php foreach ($displayRows as $row): ?>
    <?php
      $att = $row['att'];
      if ($row['type'] === 'absent') {
          $si = status_info('absent');
          $checkIn = '-';
          $checkOut = '-';
          $checkOutColor = '#94A3B8';
          $checkOutText = '-';
          $statusBadge = '<span class="badge" style="color:' . $si['color'] . ';background:' . $si['bg'] . ';">' . htmlspecialchars($si['label']) . '</span>';
      } else {
          $si = status_info($att['status']);
          $checkIn = fmt_time($att['check_in_time']);
          $noCheckout = $att['check_in_time'] && !$att['check_out_time'];
          $checkOutColor = $noCheckout ? '#DC2626' : '#374151';
          $checkOutText = $noCheckout ? '⚠️ ไม่ได้ลงชื่อออก' : fmt_time($att['check_out_time']);
          $statusBadge = '<span class="badge" style="color:' . $si['color'] . ';background:' . $si['bg'] . ';">' . htmlspecialchars($si['label']) . '</span>';
      }
    ?>
    <div class="history-grid-row" style="<?= $row['type'] === 'absent' ? 'background:#FFF5F5;' : '' ?>">
      <div style="color:#374151;font-weight:500;"><?= htmlspecialchars(fmt_date_th($row['date'])) ?></div>
      <div style="text-align:center;color:#0D47A1;font-weight:700;"><?= $row['type'] === 'absent' ? '<span style="color:#94A3B8;">—</span>' : htmlspecialchars($checkIn) ?></div>
      <div style="text-align:center;color:<?= $checkOutColor ?? '#94A3B8' ?>;font-weight:<?= ($row['type'] === 'att' && ($att['check_in_time'] && !$att['check_out_time'])) ? '600' : '400' ?>;"><?= $row['type'] === 'absent' ? '<span style="color:#94A3B8;">—</span>' : htmlspecialchars($checkOutText) ?></div>
      <div style="text-align:center;"><?= $statusBadge ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$displayRows): ?>
    <div style="padding:20px;text-align:center;color:#94A3B8;">ยังไม่มีประวัติการเข้างาน</div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
