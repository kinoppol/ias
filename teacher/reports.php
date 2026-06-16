<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

function week_start($dateStr) {
    $d = new DateTime($dateStr);
    $dow = (int)$d->format('N'); // 1=Mon..7=Sun
    $d->modify('-' . ($dow - 1) . ' days');
    return $d->format('Y-m-d');
}

$period = $_GET['period'] ?? 'daily';
if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) $period = 'daily';

$rptDate = $_GET['date'] ?? date('Y-m-d');
$rptWeek = isset($_GET['week']) ? week_start($_GET['week']) : week_start(date('Y-m-d'));
$rptMonth = $_GET['month'] ?? date('Y-m');

$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY id")->fetchAll();

$rows = [];
if ($period === 'daily') {
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE date = ?');
    $stmt->execute([$rptDate]);
    $attMap = [];
    foreach ($stmt->fetchAll() as $a) $attMap[$a['student_id']] = $a;
    foreach ($students as $s) {
        $att = $attMap[$s['id']] ?? null;
        $si = status_info($att['status'] ?? 'absent');
        $rows[] = [
            'id' => $s['id'], 'name' => $s['name'], 'dept' => $s['dept'] ?: '-',
            'v1' => $att && $att['check_in_time'] ? fmt_time($att['check_in_time']) : '-',
            'v2' => $att && $att['check_out_time'] ? fmt_time($att['check_out_time']) : '-',
            'v3' => $si['label'], 'v4' => '-', 'sc' => $si['color'], 'sb' => $si['bg'], 'pc' => '#94A3B8',
        ];
    }
    $title = 'รายงานวันที่ ' . $rptDate;
    $c1 = 'เข้างาน'; $c2 = 'ออกงาน'; $c3 = 'สถานะ'; $c4 = 'หมายเหตุ';
} else {
    if ($period === 'weekly') {
        $start = $rptWeek;
        $end = (new DateTime($rptWeek))->modify('+4 days')->format('Y-m-d');
        $title = "รายงานสัปดาห์ ($rptWeek)";
    } else {
        $start = $rptMonth . '-01';
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        $title = "รายงานเดือน $rptMonth";
    }
    $stmt = $pdo->prepare('SELECT student_id, status, COUNT(*) c FROM attendance WHERE date BETWEEN ? AND ? GROUP BY student_id, status');
    $stmt->execute([$start, $end]);
    $byStudent = [];
    foreach ($stmt->fetchAll() as $r) {
        $byStudent[$r['student_id']][$r['status']] = (int)$r['c'];
    }
    foreach ($students as $s) {
        $c = $byStudent[$s['id']] ?? [];
        $present = $c['present'] ?? 0;
        $late = $c['late'] ?? 0;
        $half = $c['half-day'] ?? 0;
        $total = $present + $late + $half;
        $pct = $total ? round(($present + $late) / $total * 100) : 0;
        $pc = $pct >= 90 ? '#16A34A' : ($pct >= 75 ? '#D97706' : '#DC2626');
        $rows[] = [
            'id' => $s['id'], 'name' => $s['name'], 'dept' => $s['dept'] ?: '-',
            'v1' => (string)$present, 'v2' => (string)$late, 'v3' => (string)$half, 'v4' => $pct . '%',
            'sc' => '#64748B', 'sb' => '#F1F5F9', 'pc' => $pc,
        ];
    }
    $c1 = 'มาตรงเวลา (วัน)'; $c2 = 'มาสาย (วัน)'; $c3 = 'ขาดงาน (วัน)'; $c4 = 'เปอร์เซ็นต์';
}

$activeSection = 'reports';
$pageTitle = 'รายงานการเข้างาน';
require_once __DIR__ . '/../includes/header.php';
?>
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
</div>

<div class="report-title-bar"><?= htmlspecialchars($title) ?></div>
<div class="report-table-wrap">
  <div class="table-scroll">
    <table class="data-table" style="min-width:500px;">
      <thead><tr>
        <th>รหัส</th><th>ชื่อ-สกุล</th><th class="center"><?= htmlspecialchars($c1) ?></th><th class="center"><?= htmlspecialchars($c2) ?></th><th class="center"><?= htmlspecialchars($c3) ?></th><th class="center"><?= htmlspecialchars($c4) ?></th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td style="font-weight:600;color:#0D47A1;"><?= htmlspecialchars($r['id']) ?></td>
          <td><div style="font-weight:600;color:#1E293B;"><?= htmlspecialchars($r['name']) ?></div><div style="font-size:11px;color:#94A3B8;"><?= htmlspecialchars($r['dept']) ?></div></td>
          <td class="center" style="font-weight:600;color:#0D47A1;"><?= htmlspecialchars($r['v1']) ?></td>
          <td class="center" style="color:#374151;"><?= htmlspecialchars($r['v2']) ?></td>
          <td class="center"><span class="badge" style="color:<?= $r['sc'] ?>;background:<?= $r['sb'] ?>;"><?= htmlspecialchars($r['v3']) ?></span></td>
          <td class="center" style="font-weight:600;color:<?= $r['pc'] ?>;"><?= htmlspecialchars($r['v4']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
