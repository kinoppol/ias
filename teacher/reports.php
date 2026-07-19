<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

function week_start($dateStr) {
    $d = new DateTime($dateStr);
    $dow = (int)$d->format('N');
    $d->modify('-' . ($dow - 1) . ' days');
    return $d->format('Y-m-d');
}

function count_weekdays($start, $end) {
    $d = new DateTime($start);
    $e = new DateTime($end);
    $count = 0;
    while ($d <= $e) {
        if ((int)$d->format('N') <= 5) $count++;
        $d->modify('+1 day');
    }
    return $count;
}

$period = $_GET['period'] ?? 'daily';
if (!in_array($period, ['daily', 'weekly', 'monthly', 'range'], true)) $period = 'daily';

$today = date('Y-m-d');
$rptDate  = $_GET['date']  ?? $today;
$rptWeek  = isset($_GET['week'])  ? week_start($_GET['week'])  : week_start($today);
$rptMonth = $_GET['month'] ?? date('Y-m');
$rptFrom  = $_GET['from']  ?? date('Y-m-d', strtotime('-30 days'));
$rptTo    = $_GET['to']    ?? $today;
if ($rptFrom > $rptTo) [$rptFrom, $rptTo] = [$rptTo, $rptFrom];
$rptStudent = $_GET['student'] ?? '';

$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY id")->fetchAll();

$rows = [];
$hasC5 = false;
$rangeRows = [];   // for range/detail view

if ($period === 'range') {
    // --- Range detail report for one student ---
    $selStudent = null;
    foreach ($students as $s) { if ($s['id'] === $rptStudent) { $selStudent = $s; break; } }

    if ($selStudent) {
        $stmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC');
        $stmt->execute([$selStudent['id'], $rptFrom, $rptTo]);
        $attMap = [];
        foreach ($stmt->fetchAll() as $a) $attMap[$a['date']] = $a;

        // Walk every calendar day in range
        $d = new DateTime($rptFrom);
        $eDate = new DateTime($rptTo);
        while ($d <= $eDate) {
            $dateStr = $d->format('Y-m-d');
            $dow = (int)$d->format('N');
            $isWeekend = $dow >= 6;
            $att = $attMap[$dateStr] ?? null;
            $si = status_info($att['status'] ?? ($isWeekend ? null : 'absent'));
            $noCheckout = $att && $att['check_in_time'] && !$att['check_out_time'];
            $rangeRows[] = [
                'date'      => $dateStr,
                'dateTh'    => fmt_date_th($dateStr),
                'isWeekend' => $isWeekend,
                'checkIn'   => $att && $att['check_in_time']  ? fmt_time($att['check_in_time'])  : null,
                'checkOut'  => $att && $att['check_out_time'] ? fmt_time($att['check_out_time']) : null,
                'noCheckout'=> $noCheckout,
                'status'    => $att['status'] ?? ($isWeekend ? 'weekend' : 'absent'),
                'si'        => $si,
            ];
            $d->modify('+1 day');
        }

        // Summary counts (weekdays only)
        $sumPresent = $sumLate = $sumHalf = $sumAbsent = $sumNoOut = 0;
        foreach ($rangeRows as $rr) {
            if ($rr['isWeekend']) continue;
            if ($rr['status'] === 'present')  $sumPresent++;
            elseif ($rr['status'] === 'late') $sumLate++;
            elseif ($rr['status'] === 'half-day') $sumHalf++;
            else $sumAbsent++;
            if ($rr['noCheckout']) $sumNoOut++;
        }
    }
    $title = $selStudent
        ? 'รายงานช่วงวันที่ ' . $rptFrom . ' – ' . $rptTo . ' | ' . $selStudent['name']
        : 'รายงานช่วงวันที่ ' . $rptFrom . ' – ' . $rptTo;

} elseif ($period === 'daily') {
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE date = ?');
    $stmt->execute([$rptDate]);
    $attMap = [];
    foreach ($stmt->fetchAll() as $a) $attMap[$a['student_id']] = $a;
    foreach ($students as $s) {
        $att = $attMap[$s['id']] ?? null;
        $si = status_info($att['status'] ?? 'absent');
        $noCheckout = $att && $att['check_in_time'] && !$att['check_out_time'];
        $rows[] = [
            'id' => $s['id'], 'name' => $s['name'], 'dept' => $s['dept'] ?: '-',
            'v1' => $att && $att['check_in_time']  ? fmt_time($att['check_in_time'])  : '-',
            'v2' => $att && $att['check_out_time'] ? fmt_time($att['check_out_time']) : '-',
            'v3' => $si['label'],
            'v4' => $noCheckout ? '⚠️ ไม่ได้ลงชื่อออก' : '-',
            'v4Color' => $noCheckout ? '#DC2626' : '#94A3B8',
            'sc' => $si['color'], 'sb' => $si['bg'], 'pc' => '#94A3B8',
        ];
    }
    $title = 'รายงานวันที่ ' . $rptDate;
    $c1 = 'เข้างาน'; $c2 = 'ออกงาน'; $c3 = 'สถานะ'; $c4 = 'หมายเหตุ';
} else {
    $hasC5 = true;
    if ($period === 'weekly') {
        $start = $rptWeek;
        $end   = (new DateTime($rptWeek))->modify('+4 days')->format('Y-m-d');
        $title = "รายงานสัปดาห์ ($rptWeek)";
    } else {
        $start = $rptMonth . '-01';
        $end   = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        $title = "รายงานเดือน $rptMonth";
    }
    $effectiveEnd = $end < $today ? $end : $today;
    $workingDays  = $start <= $effectiveEnd ? count_weekdays($start, $effectiveEnd) : 0;

    $stmt = $pdo->prepare('SELECT student_id, status, COUNT(*) c FROM attendance WHERE date BETWEEN ? AND ? GROUP BY student_id, status');
    $stmt->execute([$start, $end]);
    $byStudent = [];
    foreach ($stmt->fetchAll() as $r) $byStudent[$r['student_id']][$r['status']] = (int)$r['c'];

    $stmt = $pdo->prepare('SELECT student_id, COUNT(*) c FROM attendance WHERE date BETWEEN ? AND ? AND check_in_time IS NOT NULL AND check_out_time IS NULL GROUP BY student_id');
    $stmt->execute([$start, $end]);
    $noCheckoutByStudent = [];
    foreach ($stmt->fetchAll() as $r) $noCheckoutByStudent[$r['student_id']] = (int)$r['c'];

    foreach ($students as $s) {
        $c = $byStudent[$s['id']] ?? [];
        $present  = $c['present']  ?? 0;
        $late     = $c['late']     ?? 0;
        $half     = $c['half-day'] ?? 0;
        $attended = $present + $late + $half;
        $absent   = max(0, $workingDays - $attended);
        $pct = $attended ? round(($present + $late) / $attended * 100) : 0;
        $pc  = $pct >= 90 ? '#16A34A' : ($pct >= 75 ? '#D97706' : '#DC2626');
        $noCheckout = $noCheckoutByStudent[$s['id']] ?? 0;
        $rows[] = [
            'id' => $s['id'], 'name' => $s['name'], 'dept' => $s['dept'] ?: '-',
            'v1' => (string)$present, 'v2' => (string)$late, 'v3' => (string)$absent, 'v4' => $pct . '%',
            'v3Color' => $absent > 0 ? '#DC2626' : '#94A3B8',
            'v5' => (string)$noCheckout, 'v5Color' => $noCheckout > 0 ? '#DC2626' : '#94A3B8',
            'sc' => '#64748B', 'sb' => '#F1F5F9', 'pc' => $pc,
        ];
    }
    $c1 = 'มาตรงเวลา (วัน)'; $c2 = 'มาสาย (วัน)'; $c3 = 'ขาดงาน (วัน)'; $c4 = 'เปอร์เซ็นต์'; $c5 = 'ไม่ลงชื่อออก (วัน)';
}

$activeSection = 'reports';
$pageTitle = 'รายงานการเข้างาน';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.range-filter-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07); padding:18px 20px; margin-bottom:18px; }
.range-filter-grid { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:12px; align-items:end; }
@media(max-width:680px){ .range-filter-grid { grid-template-columns:1fr 1fr; } }
.range-filter-grid label { font-size:12px; font-weight:600; color:#64748B; display:block; margin-bottom:4px; }
.range-filter-grid input, .range-filter-grid select { width:100%; padding:9px 11px; border:1.5px solid #CBD5E1; border-radius:9px; font-size:14px; font-family:inherit; box-sizing:border-box; }
.range-filter-grid .btn-primary { white-space:nowrap; padding:9px 20px; }
.range-day-table { width:100%; border-collapse:collapse; }
.range-day-table th { background:#1A237E; color:#fff; font-size:13px; font-weight:600; padding:10px 14px; text-align:left; }
.range-day-table th.center { text-align:center; }
.range-day-table td { padding:9px 14px; border-bottom:1px solid #F1F5F9; font-size:13.5px; }
.range-day-table tr.weekend td { background:#F8FAFC; color:#94A3B8; }
.range-day-table tr:hover td { background:#EFF6FF; }
.range-summary-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.range-sum-box { background:#fff; border-radius:10px; border:1.5px solid #E2E8F0; padding:10px 18px; text-align:center; min-width:90px; }
.range-sum-box .sv { font-size:22px; font-weight:800; }
.range-sum-box .sl { font-size:11px; color:#64748B; margin-top:2px; }
.range-no-student { background:#FEF9EC; border:1.5px solid #FDE68A; border-radius:10px; padding:14px 18px; color:#92400E; font-size:14px; }
</style>

<div class="print-header">
  <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($title) ?></div>
  <div style="font-size:13px;color:#555;margin-top:4px;">สำนักงานคณะกรรมการการอาชีวศึกษา (OVEC)</div>
</div>
<div class="flex-between no-print">
  <div class="section-title" style="margin-bottom:0;">📈 รายงานการเข้างาน</div>
  <button onclick="window.print()" class="btn-primary">🖨 พิมพ์ / Print</button>
</div>

<div class="report-tabs no-print">
  <a href="?period=daily&date=<?= htmlspecialchars($rptDate) ?>" class="report-tab <?= $period === 'daily' ? 'active' : '' ?>">รายวัน</a>
  <a href="?period=weekly&week=<?= htmlspecialchars($rptWeek) ?>" class="report-tab <?= $period === 'weekly' ? 'active' : '' ?>">รายสัปดาห์</a>
  <a href="?period=monthly&month=<?= htmlspecialchars($rptMonth) ?>" class="report-tab <?= $period === 'monthly' ? 'active' : '' ?>">รายเดือน</a>
  <a href="?period=range&from=<?= htmlspecialchars($rptFrom) ?>&to=<?= htmlspecialchars($rptTo) ?>&student=<?= htmlspecialchars($rptStudent) ?>" class="report-tab <?= $period === 'range' ? 'active' : '' ?>">ช่วงวันที่</a>
  <?php if ($period !== 'range'): ?>
  <form method="get" id="rptForm" style="display:inline;">
    <input type="hidden" name="period" value="<?= htmlspecialchars($period) ?>">
    <?php if ($period === 'daily'): ?>
      <input type="date" name="date" value="<?= htmlspecialchars($rptDate) ?>" class="report-date-input" onchange="this.form.submit()">
    <?php elseif ($period === 'weekly'): ?>
      <input type="date" name="week" value="<?= htmlspecialchars($rptWeek) ?>" class="report-date-input" title="เลือกวันใดก็ได้ในสัปดาห์" onchange="this.form.submit()">
    <?php else: ?>
      <input type="month" name="month" value="<?= htmlspecialchars($rptMonth) ?>" class="report-date-input" onchange="this.form.submit()">
    <?php endif; ?>
  </form>
  <?php endif; ?>
</div>

<?php if ($period === 'range'): ?>

<!-- Range filter form -->
<div class="range-filter-card no-print">
  <form method="get" id="rangeForm">
    <input type="hidden" name="period" value="range">
    <div class="range-filter-grid">
      <div>
        <label>วันที่เริ่มต้น</label>
        <input type="date" name="from" value="<?= htmlspecialchars($rptFrom) ?>" max="<?= htmlspecialchars($today) ?>">
      </div>
      <div>
        <label>วันที่สิ้นสุด</label>
        <input type="date" name="to" value="<?= htmlspecialchars($rptTo) ?>" max="<?= htmlspecialchars($today) ?>">
      </div>
      <div>
        <label>นักศึกษา</label>
        <select name="student">
          <option value="">— เลือกนักศึกษา —</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= htmlspecialchars($s['id']) ?>" <?= $rptStudent === $s['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['id'] . ' — ' . $s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button type="submit" class="btn-primary">ดูรายงาน</button>
      </div>
    </div>
  </form>
</div>

<?php if (!$rptStudent): ?>
  <div class="range-no-student">📋 กรุณาเลือกนักศึกษาและช่วงวันที่ แล้วกด "ดูรายงาน"</div>
<?php elseif (!$selStudent): ?>
  <div class="range-no-student">⚠️ ไม่พบข้อมูลนักศึกษา</div>
<?php else: ?>

<!-- Print header (student info) -->
<div style="margin-bottom:14px;">
  <div style="font-size:15px;font-weight:700;color:#1A237E;"><?= htmlspecialchars($selStudent['name']) ?></div>
  <div style="font-size:12px;color:#64748B;"><?= htmlspecialchars($selStudent['id']) ?><?= $selStudent['dept'] ? ' · ' . htmlspecialchars($selStudent['dept']) : '' ?> · ช่วง <?= htmlspecialchars($rptFrom) ?> ถึง <?= htmlspecialchars($rptTo) ?></div>
</div>

<!-- Summary boxes -->
<div class="range-summary-bar no-print">
  <div class="range-sum-box"><div class="sv" style="color:#1565C0;"><?= $sumPresent ?></div><div class="sl">มาตรงเวลา</div></div>
  <div class="range-sum-box"><div class="sv" style="color:#D97706;"><?= $sumLate ?></div><div class="sl">มาสาย</div></div>
  <div class="range-sum-box"><div class="sv" style="color:#9333EA;"><?= $sumHalf ?></div><div class="sl">ขาดครึ่งวัน</div></div>
  <div class="range-sum-box"><div class="sv" style="color:#DC2626;"><?= $sumAbsent ?></div><div class="sl">ขาดงาน</div></div>
  <div class="range-sum-box"><div class="sv" style="color:#DC2626;"><?= $sumNoOut ?></div><div class="sl">ไม่ลงชื่อออก</div></div>
</div>

<div class="report-title-bar"><?= htmlspecialchars($title) ?></div>
<div class="report-table-wrap">
  <div class="table-scroll">
    <table class="range-day-table">
      <thead><tr>
        <th>วันที่</th>
        <th class="center">เวลาเข้างาน</th>
        <th class="center">เวลาออกงาน</th>
        <th class="center">ผลการลงเวลา</th>
        <th class="center">หมายเหตุ</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rangeRows as $rr): ?>
        <?php
          $isWknd = $rr['isWeekend'];
          $si = $rr['si'];
          $statusLabel = $isWknd ? 'วันหยุด' : $si['label'];
          $statusColor = $isWknd ? '#94A3B8' : $si['color'];
          $statusBg    = $isWknd ? '#F1F5F9' : $si['bg'];
          $note = '';
          $noteColor = '#94A3B8';
          if ($rr['noCheckout']) { $note = '⚠️ ไม่ได้ลงชื่อออก'; $noteColor = '#DC2626'; }
        ?>
        <tr class="<?= $isWknd ? 'weekend' : '' ?>">
          <td style="font-weight:500;"><?= htmlspecialchars($rr['dateTh']) ?></td>
          <td class="center" style="font-weight:600;color:#0D47A1;"><?= $rr['checkIn'] ?? ($isWknd ? '—' : '-') ?></td>
          <td class="center" style="color:#374151;"><?= $rr['checkOut'] ?? ($isWknd ? '—' : '-') ?></td>
          <td class="center"><span class="badge" style="color:<?= $statusColor ?>;background:<?= $statusBg ?>;"><?= htmlspecialchars($statusLabel) ?></span></td>
          <td class="center" style="color:<?= $noteColor ?>;font-size:13px;"><?= htmlspecialchars($note) ?: '' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; // selStudent ?>

<?php else: // not range — original table view ?>

<div class="report-title-bar"><?= htmlspecialchars($title) ?></div>
<div class="report-table-wrap">
  <div class="table-scroll">
    <table class="data-table" style="min-width:500px;">
      <thead><tr>
        <th>รหัส</th><th>ชื่อ-สกุล</th><th class="center"><?= htmlspecialchars($c1) ?></th><th class="center"><?= htmlspecialchars($c2) ?></th><th class="center"><?= htmlspecialchars($c3) ?></th><th class="center"><?= htmlspecialchars($c4) ?></th><?php if ($hasC5): ?><th class="center"><?= htmlspecialchars($c5) ?></th><?php endif; ?>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td style="font-weight:600;color:#0D47A1;"><?= htmlspecialchars($r['id']) ?></td>
          <td><div style="font-weight:600;color:#1E293B;"><?= htmlspecialchars($r['name']) ?></div><div style="font-size:11px;color:#94A3B8;"><?= htmlspecialchars($r['dept']) ?></div></td>
          <td class="center" style="font-weight:600;color:#0D47A1;"><?= htmlspecialchars($r['v1']) ?></td>
          <td class="center" style="color:#374151;"><?= htmlspecialchars($r['v2']) ?></td>
          <?php if ($hasC5): ?>
          <td class="center" style="font-weight:600;color:<?= $r['v3Color'] ?? '#94A3B8' ?>;"><?= htmlspecialchars($r['v3']) ?></td>
          <td class="center" style="font-weight:600;color:<?= $r['pc'] ?>;"><?= htmlspecialchars($r['v4']) ?></td>
          <td class="center" style="font-weight:600;color:<?= $r['v5Color'] ?>;"><?= htmlspecialchars($r['v5']) ?></td>
          <?php else: ?>
          <td class="center"><span class="badge" style="color:<?= $r['sc'] ?>;background:<?= $r['sb'] ?>;"><?= htmlspecialchars($r['v3']) ?></span></td>
          <td class="center" style="font-weight:600;color:<?= $r['v4Color'] ?>;"><?= htmlspecialchars($r['v4']) ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
