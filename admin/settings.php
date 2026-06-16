<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $early = (int)($_POST['early_checkin_minutes'] ?? 60);
    $grace = (int)($_POST['late_grace_minutes'] ?? 30);
    $inst = trim($_POST['institution_name'] ?? '');
    $pdo->prepare('UPDATE settings SET v = ? WHERE k = ?')->execute([$early, 'early_checkin_minutes']);
    $pdo->prepare('UPDATE settings SET v = ? WHERE k = ?')->execute([$grace, 'late_grace_minutes']);
    $pdo->prepare('UPDATE settings SET v = ? WHERE k = ?')->execute([$inst, 'institution_name']);
    $_SESSION['notif'] = ['msg' => '💾 บันทึกการตั้งค่าแล้ว', 'type' => 'success'];
    header('Location: /ias/admin/settings.php');
    exit;
}

$settings = [];
foreach ($pdo->query('SELECT * FROM settings')->fetchAll() as $r) $settings[$r['k']] = $r['v'];

$totalUsers = (int)$pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
$totalWPs = (int)$pdo->query('SELECT COUNT(*) c FROM workplaces')->fetch()['c'];
$totalAtt = (int)$pdo->query('SELECT COUNT(*) c FROM attendance')->fetch()['c'];

$activeSection = 'settings';
$pageTitle = 'ตั้งค่าระบบ';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-600">
  <div class="section-title">⚙️ ตั้งค่าระบบ</div>
  <div class="settings-card">
    <div class="settings-card-title">กฎการเช็คอิน</div>
    <form method="post" class="settings-grid">
      <div>
        <label>เช็คอินก่อนเวลาได้ (นาที)</label>
        <input type="number" name="early_checkin_minutes" value="<?= htmlspecialchars($settings['early_checkin_minutes'] ?? 60) ?>">
        <div class="settings-hint">นักศึกษาเช็คอินได้ก่อนเวลาสูงสุด <?= htmlspecialchars($settings['early_checkin_minutes'] ?? 60) ?> นาที</div>
      </div>
      <div>
        <label>ระยะเวลาผ่อนผันการสาย (นาที)</label>
        <input type="number" name="late_grace_minutes" value="<?= htmlspecialchars($settings['late_grace_minutes'] ?? 30) ?>">
        <div class="settings-hint">สายเกิน <?= htmlspecialchars($settings['late_grace_minutes'] ?? 30) ?> นาที = ขาดงานครึ่งวัน</div>
      </div>
      <div>
        <label>ชื่อสถาบันการศึกษา</label>
        <input type="text" name="institution_name" placeholder="วิทยาลัย..." value="<?= htmlspecialchars($settings['institution_name'] ?? '') ?>">
      </div>
      <button type="submit" class="btn-save">บันทึกการตั้งค่า</button>
    </form>
  </div>
  <div class="settings-card">
    <div class="settings-card-title">ℹ️ ข้อมูลระบบ</div>
    <div style="display:grid;gap:7px;">
      <div class="info-row"><span>ระบบ</span><span>Internship Attendance v1.0</span></div>
      <div class="info-row"><span>ผู้พัฒนา</span><span>OVEC</span></div>
      <div class="info-row"><span>จำนวนผู้ใช้</span><span><?= $totalUsers ?> คน</span></div>
      <div class="info-row"><span>สถานประกอบการ</span><span><?= $totalWPs ?> แห่ง</span></div>
      <div class="info-row"><span>บันทึกการเข้างาน</span><span><?= $totalAtt ?> รายการ</span></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
