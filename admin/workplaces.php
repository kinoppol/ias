<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$workplaces = $pdo->query('SELECT * FROM workplaces ORDER BY id')->fetchAll();
$studentCounts = [];
foreach ($pdo->query("SELECT workplace_id, COUNT(*) c FROM users WHERE role='student' GROUP BY workplace_id")->fetchAll() as $r) {
    $studentCounts[$r['workplace_id']] = (int)$r['c'];
}

$activeSection = 'workplaces';
$pageTitle = 'สถานประกอบการ';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">🏢 สถานประกอบการ</div>
<?php foreach ($workplaces as $wp): $sc = $studentCounts[$wp['id']] ?? 0; ?>
<div class="wp-admin-card">
  <div class="wp-admin-row">
    <div style="flex:1;min-width:240px;">
      <div style="font-size:15px;font-weight:700;color:#1E293B;"><?= htmlspecialchars($wp['name']) ?></div>
      <div style="font-size:12.5px;color:#94A3B8;margin-top:3px;">📍 <?= htmlspecialchars($wp['address']) ?></div>
      <div class="wp-admin-meta">
        <span>⏰ <?= substr($wp['start_time'], 0, 5) ?>–<?= substr($wp['end_time'], 0, 5) ?></span>
        <span>📏 รัศมี <?= htmlspecialchars($wp['radius']) ?> ม.</span>
        <span>👥 <?= $sc ?> คน</span>
        <span>OT: <?= $wp['allow_ot'] ? 'เปิด' : 'ปิด' ?></span>
      </div>
    </div>
    <div class="wp-admin-actions">
      <span class="badge" style="color:<?= $wp['active'] ? '#16A34A' : '#DC2626' ?>;background:<?= $wp['active'] ? '#DCFCE7' : '#FEE2E2' ?>;"><?= $wp['active'] ? 'ใช้งาน' : 'ปิดใช้งาน' ?></span>
      <form method="post" action="/ias/ajax/toggle_wp.php" style="display:inline;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($wp['id']) ?>">
        <button type="submit" class="btn-toggle">เปิด/ปิด</button>
      </form>
      <form method="post" action="/ias/ajax/delete_wp.php" onsubmit="return confirm('ยืนยันการลบสถานประกอบการ?');" style="display:inline;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($wp['id']) ?>">
        <button type="submit" class="btn-delete">ลบ</button>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
