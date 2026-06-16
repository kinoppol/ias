<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$today = date('Y-m-d');
$now = new DateTime();

$stmt = $pdo->prepare('SELECT * FROM workplaces WHERE id = ?');
$stmt->execute([$user['workplace_id']]);
$wp = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? AND date = ?');
$stmt->execute([$user['id'], $today]);
$todayAtt = $stmt->fetch();

$stmt = $pdo->prepare('SELECT v FROM settings WHERE k = ?');
$stmt->execute(['early_checkin_minutes']);
$earlyRow = $stmt->fetch();
$earlyMinutes = $earlyRow ? (int)$earlyRow['v'] : 60;

$canCheckIn = $canCheckOut = $isTooEarly = $isWorkDone = $isTooEarlyCheckOut = false;
$earlyMsg = '';
$checkOutWaitMsg = '';
if ($wp) {
    $sched = new DateTime($today . ' ' . $wp['start_time']);
    $early = clone $sched;
    $early->modify("-$earlyMinutes minutes");
    $endSched = new DateTime($today . ' ' . $wp['end_time']);

    if ($todayAtt && $todayAtt['check_out_time']) {
        $isWorkDone = true;
    } elseif ($todayAtt && $todayAtt['check_in_time']) {
        if ($now >= $endSched) {
            $canCheckOut = true;
        } else {
            $isTooEarlyCheckOut = true;
            $diff = round(($endSched->getTimestamp() - $now->getTimestamp()) / 60);
            $checkOutWaitMsg = "สามารถลงชื่อออกได้ตั้งแต่เวลาเลิกงาน (" . substr($wp['end_time'], 0, 5) . " น.) — อีก $diff นาที";
        }
    } elseif ($now < $early) {
        $isTooEarly = true;
        $diff = round(($early->getTimestamp() - $now->getTimestamp()) / 60);
        $earlyMsg = "สามารถเช็คอินได้อีก $diff นาที (ก่อนเวลา $earlyMinutes นาที)";
    } else {
        $canCheckIn = true;
    }
}

$todaySI = status_info($todayAtt['status'] ?? null);

// Monthly stats
$monthPrefix = date('Y-m');
$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM attendance WHERE student_id = ? AND date LIKE ? GROUP BY status");
$stmt->execute([$user['id'], $monthPrefix . '%']);
$counts = ['present' => 0, 'late' => 0, 'half-day' => 0];
$total = 0;
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int)$row['c'];
    $total += (int)$row['c'];
}

$activeSection = 'dashboard';
$pageTitle = 'เช็คอิน/ออก — ระบบบันทึกการเข้างานฝึกงาน';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-600">
  <div class="wp-banner">
    <div class="wp-banner-icon">🏢</div>
    <div style="min-width:0;">
      <div class="wp-banner-label">สถานประกอบการ</div>
      <div class="wp-banner-name"><?= htmlspecialchars($wp['name'] ?? 'ไม่ได้ระบุสถานประกอบการ') ?></div>
      <div class="wp-banner-meta">เวลาทำงาน <?= htmlspecialchars(substr($wp['start_time'] ?? '-', 0, 5)) ?> – <?= htmlspecialchars(substr($wp['end_time'] ?? '-', 0, 5)) ?> น. | รัศมี <?= htmlspecialchars($wp['radius'] ?? '-') ?> ม.</div>
    </div>
  </div>

  <div class="checkin-card">
    <div class="checkin-time live-time"></div>
    <div class="checkin-date live-date"></div>

    <?php if ($todayAtt && $todayAtt['check_in_time']): ?>
      <div class="checkin-status-pill"><?= $todaySI['icon'] ?> <?= htmlspecialchars($todaySI['label']) ?></div>
      <div class="checkin-times-row">
        <div>
          <div class="checkin-times-label">เวลาเข้างาน</div>
          <div class="checkin-times-value"><?= fmt_time($todayAtt['check_in_time']) ?></div>
        </div>
        <?php if ($todayAtt['check_out_time']): ?>
        <div>
          <div class="checkin-times-label">เวลาออกงาน</div>
          <div class="checkin-times-value"><?= fmt_time($todayAtt['check_out_time']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="checkin-btns">
      <?php if ($isTooEarly): ?>
        <div class="checkin-early">⏱ <?= htmlspecialchars($earlyMsg) ?></div>
      <?php endif; ?>
      <?php if ($canCheckIn): ?>
        <button class="btn-checkin" onclick="doCheckIn(this)">📍 เช็คอิน (Check-In)</button>
      <?php endif; ?>
      <?php if ($isTooEarlyCheckOut): ?>
        <div class="checkin-early">⏱ <?= htmlspecialchars($checkOutWaitMsg) ?></div>
      <?php endif; ?>
      <?php if ($canCheckOut): ?>
        <button class="btn-checkout" onclick="doCheckOut(this)">📍 เช็คเอาท์ (Check-Out)</button>
      <?php endif; ?>
      <?php if ($isWorkDone): ?>
        <div class="work-done">✅ เสร็จสิ้นการทำงานวันนี้แล้ว</div>
      <?php endif; ?>
    </div>
    <div class="gps-note">📡 บันทึกพิกัด GPS อัตโนมัติขณะเช็คอิน/เอาท์</div>
  </div>

  <div class="stats-card">
    <div class="stats-title">📊 สถิติเดือนนี้ (This Month)</div>
    <div class="stats-grid">
      <div class="stat-box stat-blue"><div class="stat-value"><?= $counts['present'] ?></div><div class="stat-label">มาตรงเวลา</div></div>
      <div class="stat-box stat-amber"><div class="stat-value"><?= $counts['late'] ?></div><div class="stat-label">มาสาย</div></div>
      <div class="stat-box stat-red"><div class="stat-value"><?= $counts['half-day'] ?></div><div class="stat-label">ขาดครึ่งวัน</div></div>
      <div class="stat-box stat-green"><div class="stat-value"><?= $total ?></div><div class="stat-label">วันทั้งหมด</div></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
