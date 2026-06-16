<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

$stmt = $pdo->query("SELECT l.*, u.name AS s_name FROM leaves l JOIN users u ON u.id = l.student_id ORDER BY l.created_at DESC");
$allLeaves = $stmt->fetchAll();
$pendingLeaves = array_values(array_filter($allLeaves, fn($l) => $l['status'] === 'pending'));

$activeSection = 'leaves';
$pageTitle = 'จัดการการลา';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">🗓 จัดการการลา (Leave Management)</div>

<?php if ($pendingLeaves): ?>
<div class="pending-box">
  <div class="pending-title">⏳ รอการอนุมัติ (<?= count($pendingLeaves) ?> รายการ)</div>
  <?php foreach ($pendingLeaves as $lv): ?>
  <div class="pending-item">
    <div style="flex:1;min-width:200px;">
      <div style="font-weight:700;color:#1E293B;"><?= htmlspecialchars($lv['s_name']) ?></div>
      <div style="font-size:12.5px;color:#64748B;margin-top:2px;"><?= htmlspecialchars(leave_type_label($lv['type'])) ?> — <?= htmlspecialchars($lv['date']) ?></div>
      <div style="font-size:12.5px;color:#94A3B8;margin-top:2px;">เหตุผล: <?= htmlspecialchars($lv['reason']) ?></div>
    </div>
    <div style="display:flex;gap:7px;">
      <form method="post" action="/ias/ajax/approve_leave.php" style="display:inline;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($lv['id']) ?>">
        <input type="hidden" name="status" value="approved">
        <button type="submit" class="btn-approve">✓ อนุมัติ</button>
      </form>
      <form method="post" action="/ias/ajax/approve_leave.php" style="display:inline;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($lv['id']) ?>">
        <input type="hidden" name="status" value="rejected">
        <button type="submit" class="btn-reject">✗ ไม่อนุมัติ</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="table-card">
  <div class="table-card-header">ประวัติการลาทั้งหมด</div>
  <?php foreach ($allLeaves as $lv): $si = leave_status_info($lv['status']); ?>
    <div class="leave-row">
      <div class="leave-row-main">
        <div class="leave-row-title" style="font-weight:700;"><?= htmlspecialchars($lv['s_name']) ?></div>
        <div class="leave-row-sub"><?= htmlspecialchars(leave_type_label($lv['type'])) ?> — <?= htmlspecialchars($lv['date']) ?></div>
      </div>
      <span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;"><?= htmlspecialchars($si['label']) ?></span>
    </div>
  <?php endforeach; ?>
  <?php if (!$allLeaves): ?>
    <div style="padding:20px;text-align:center;color:#94A3B8;">ยังไม่มีข้อมูลการลา</div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
