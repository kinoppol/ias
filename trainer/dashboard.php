<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('trainer');
$user = current_user();

// Stats
$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM tasks WHERE trainer_id = ? GROUP BY status");
$stmt->execute([$user['id']]);
$stats = ['active' => 0, 'completed' => 0, 'terminated' => 0];
foreach ($stmt->fetchAll() as $r) $stats[$r['status']] = (int)$r['c'];
$total = array_sum($stats);

// Students at my workplace
$students = [];
if ($user['workplace_id']) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='student' AND workplace_id = ? ORDER BY name");
    $stmt->execute([$user['workplace_id']]);
    $students = $stmt->fetchAll();
}

// Recent tasks
$stmt = $pdo->prepare("SELECT t.*, u.name student_name FROM tasks t JOIN users u ON u.id = t.student_id WHERE t.trainer_id = ? ORDER BY t.created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$recentTasks = $stmt->fetchAll();

$activeSection = 'dashboard';
$pageTitle = 'ภาพรวม — ครูฝึก';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">📊 ภาพรวมครูฝึก</div>

<div class="stats-card" style="margin-bottom:16px;">
  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-box stat-blue"><div class="stat-value"><?= $total ?></div><div class="stat-label">งานทั้งหมด</div></div>
    <div class="stat-box" style="background:#EFF6FF;"><div class="stat-value" style="color:#1565C0;"><?= $stats['active'] ?></div><div class="stat-label">กำลังดำเนินการ</div></div>
    <div class="stat-box stat-green"><div class="stat-value"><?= $stats['completed'] ?></div><div class="stat-label">เสร็จสิ้น</div></div>
    <div class="stat-box stat-red"><div class="stat-value"><?= $stats['terminated'] ?></div><div class="stat-label">สิ้นสุด</div></div>
  </div>
</div>

<div class="flex-between" style="margin-bottom:10px;">
  <div style="font-size:15px;font-weight:700;color:#1A237E;">📋 งานล่าสุด</div>
  <a href="/ias/trainer/tasks.php?new=1" class="btn-primary">+ มอบหมายงาน</a>
</div>
<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:500px;">
      <thead><tr><th>ชื่องาน</th><th>นักศึกษา</th><th class="center">คะแนน</th><th class="center">สถานะ</th><th class="center">เปิดดูแล้ว</th></tr></thead>
      <tbody>
      <?php foreach ($recentTasks as $t): $si = task_status_info($t['status']); ?>
        <tr>
          <td><a href="/ias/trainer/task_detail.php?id=<?= $t['id'] ?>" style="color:#1565C0;font-weight:600;"><?= htmlspecialchars($t['title']) ?></a></td>
          <td><?= htmlspecialchars($t['student_name']) ?></td>
          <td class="center" style="font-weight:700;color:#1565C0;"><?= (int)$t['score'] ?></td>
          <td class="center"><span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;"><?= $si['icon'] ?> <?= $si['label'] ?></span></td>
          <td class="center"><?= $t['viewed_at'] ? '<span style="color:#16A34A;">✅ แล้ว</span>' : '<span style="color:#94A3B8;">ยังไม่เปิด</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recentTasks): ?>
        <tr><td colspan="5" style="text-align:center;color:#94A3B8;padding:20px;">ยังไม่มีงานที่มอบหมาย</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($students): ?>
<div style="font-size:15px;font-weight:700;color:#1A237E;margin:16px 0 8px;">👥 นักศึกษาของฉัน</div>
<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:400px;">
      <thead><tr><th>รหัส</th><th>ชื่อ-สกุล</th><th class="center">งานทั้งหมด</th><th class="center">เสร็จ</th></tr></thead>
      <tbody>
      <?php foreach ($students as $s):
          $stmt2 = $pdo->prepare("SELECT COUNT(*) c, SUM(status='completed') done FROM tasks WHERE trainer_id = ? AND student_id = ?");
          $stmt2->execute([$user['id'], $s['id']]);
          $sc = $stmt2->fetch();
      ?>
        <tr>
          <td style="font-weight:700;color:#0D47A1;"><?= htmlspecialchars($s['id']) ?></td>
          <td><?= htmlspecialchars($s['name']) ?></td>
          <td class="center"><?= (int)$sc['c'] ?></td>
          <td class="center" style="color:#16A34A;font-weight:700;"><?= (int)$sc['done'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
