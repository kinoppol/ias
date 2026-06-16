<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$user = current_user();
$showForm = isset($_GET['form']);

$stmt = $pdo->prepare('SELECT * FROM leaves WHERE student_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$leaves = $stmt->fetchAll();

$activeSection = 'leave';
$pageTitle = 'การลา';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-600">
  <div class="flex-between">
    <div class="section-title" style="margin-bottom:0;">📝 การลา (Leave)</div>
    <a href="?<?= $showForm ? '' : 'form=1' ?>" class="btn-primary"><?= $showForm ? 'ปิดฟอร์ม' : '+ ขอลา' ?></a>
  </div>

  <?php if ($showForm): ?>
  <div class="leave-form-card">
    <div class="leave-form-title">แบบฟอร์มขอลา</div>
    <form method="post" action="/ias/ajax/submit_leave.php" class="leave-form-grid">
      <div>
        <label>ประเภทการลา</label>
        <select name="type">
          <option value="sick">ลาป่วย (Sick Leave)</option>
          <option value="personal">ลากิจ (Personal Leave)</option>
        </select>
      </div>
      <div>
        <label>วันที่ลา</label>
        <input type="date" name="date" required>
      </div>
      <div>
        <label>เหตุผล</label>
        <textarea name="reason" placeholder="ระบุเหตุผลการลา..." style="min-height:75px;resize:vertical;" required></textarea>
      </div>
      <div class="leave-form-actions">
        <button type="submit" class="btn-submit">ส่งคำขอ</button>
        <a href="?" class="btn-cancel">ยกเลิก</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="table-card-header">ประวัติการลา</div>
    <?php foreach ($leaves as $lv): $si = leave_status_info($lv['status']); ?>
      <div class="leave-row">
        <div class="leave-row-main">
          <div class="leave-row-title"><?= htmlspecialchars(leave_type_label($lv['type'])) ?></div>
          <div class="leave-row-sub">วันที่: <?= htmlspecialchars($lv['date']) ?> — <?= htmlspecialchars($lv['reason']) ?></div>
        </div>
        <span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;white-space:nowrap;"><?= htmlspecialchars($si['label']) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$leaves): ?>
      <div style="padding:20px;text-align:center;color:#94A3B8;">ยังไม่มีการลา</div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
