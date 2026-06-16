<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 30');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

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
  <?php foreach ($rows as $r): $si = status_info($r['status']); ?>
    <div class="history-grid-row">
      <div style="color:#374151;font-weight:500;"><?= htmlspecialchars(fmt_date_th($r['date'])) ?></div>
      <div style="text-align:center;color:#0D47A1;font-weight:700;"><?= fmt_time($r['check_in_time']) ?></div>
      <div style="text-align:center;color:#374151;"><?= fmt_time($r['check_out_time']) ?></div>
      <div style="text-align:center;"><span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;"><?= htmlspecialchars($si['label']) ?></span></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?>
    <div style="padding:20px;text-align:center;color:#94A3B8;">ยังไม่มีประวัติการเข้างาน</div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
