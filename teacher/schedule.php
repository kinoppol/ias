<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

$workplaces = $pdo->query('SELECT * FROM workplaces ORDER BY id')->fetchAll();

$activeSection = 'schedule';
$pageTitle = 'กำหนดตารางเวลา';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">⏰ กำหนดตารางเวลา &amp; รัศมีเช็คอิน</div>
<?php foreach ($workplaces as $wp): ?>
<div class="wp-form-card">
  <div class="wp-form-head"><span style="font-size:18px;">🏢</span><div class="wp-form-name"><?= htmlspecialchars($wp['name']) ?></div></div>
  <div class="wp-form-address">📍 <?= htmlspecialchars($wp['address']) ?></div>
  <form method="post" action="/ias/ajax/update_wp.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars($wp['id']) ?>">
    <div class="wp-form-grid">
      <div>
        <label>⏰ เวลาเข้างาน</label>
        <input type="time" name="start_time" value="<?= substr($wp['start_time'], 0, 5) ?>">
      </div>
      <div>
        <label>⏰ เวลาออกงาน</label>
        <input type="time" name="end_time" value="<?= substr($wp['end_time'], 0, 5) ?>">
      </div>
      <div>
        <label>📏 รัศมีเช็คอิน (เมตร)</label>
        <input type="number" name="radius" min="50" max="2000" value="<?= htmlspecialchars($wp['radius']) ?>">
      </div>
    </div>
    <button type="submit" class="btn-primary" style="margin-bottom:14px;">บันทึก</button>
  </form>
  <div class="wp-rule-note">⚠️ <strong>กฎการเช็คอิน:</strong> เข้าก่อนได้ไม่เกิน 60 นาที | มาสายไม่เกิน 30 นาที = บันทึก "มาสาย" | มาสายเกิน 30 นาที = "ขาดงานครึ่งวัน"</div>
</div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
