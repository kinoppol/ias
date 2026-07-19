<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('trainer');
$user = current_user();

$students = [];
$wp = null;
if ($user['workplace_id']) {
    $stmt = $pdo->prepare('SELECT * FROM workplaces WHERE id = ?');
    $stmt->execute([$user['workplace_id']]);
    $wp = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='student' AND workplace_id = ? ORDER BY name");
    $stmt->execute([$user['workplace_id']]);
    $students = $stmt->fetchAll();
}

$activeSection = 'students';
$pageTitle = 'นักศึกษาฝึกงาน';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">👥 นักศึกษาฝึกงาน<?= $wp ? ' — ' . htmlspecialchars($wp['name']) : '' ?></div>
<?php if (!$students): ?>
  <div style="text-align:center;color:#94A3B8;padding:30px;">ไม่มีนักศึกษาในสถานประกอบการนี้</div>
<?php else: ?>
<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:500px;">
      <thead><tr><th>รหัส</th><th>ชื่อ-สกุล</th><th>แผนก</th><th class="center">งานทั้งหมด</th><th class="center">เสร็จ</th><th class="center">กำลังดำเนินการ</th><th class="center">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($students as $s):
          $stmt2 = $pdo->prepare("SELECT status, COUNT(*) c FROM tasks WHERE trainer_id = ? AND student_id = ? GROUP BY status");
          $stmt2->execute([$user['id'], $s['id']]);
          $sc = ['active' => 0, 'completed' => 0, 'terminated' => 0];
          foreach ($stmt2->fetchAll() as $r) $sc[$r['status']] = (int)$r['c'];
          $total = array_sum($sc);
      ?>
        <tr>
          <td style="font-weight:700;color:#0D47A1;"><?= htmlspecialchars($s['id']) ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></td>
          <td style="color:#64748B;"><?= htmlspecialchars($s['dept'] ?: '—') ?></td>
          <td class="center"><?= $total ?></td>
          <td class="center" style="color:#16A34A;font-weight:700;"><?= $sc['completed'] ?></td>
          <td class="center" style="color:#1565C0;font-weight:700;"><?= $sc['active'] ?></td>
          <td class="center">
            <a href="/ias/trainer/tasks.php?new=1&student=<?= htmlspecialchars($s['id']) ?>" class="btn-toggle" style="background:#EDE9FE;color:#6D28D9;">+ มอบหมายงาน</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
