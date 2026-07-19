<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$user = current_user();

$stmt = $pdo->prepare("SELECT t.*, u.name trainer_name FROM tasks t JOIN users u ON u.id = t.trainer_id WHERE t.student_id = ? ORDER BY t.created_at DESC");
$stmt->execute([$user['id']]);
$tasks = $stmt->fetchAll();

// Count unread (active, not yet viewed)
$unread = 0;
foreach ($tasks as $t) { if ($t['status'] === 'active' && !$t['viewed_at']) $unread++; }

$activeSection = 'tasks';
$pageTitle = 'งานที่ได้รับมอบหมาย';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.stask-card { background:#fff; border-radius:13px; box-shadow:0 1px 6px rgba(0,0,0,.07); padding:16px 18px; margin-bottom:10px; border-left:4px solid #CBD5E1; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.stask-card.active   { border-left-color:#1565C0; }
.stask-card.completed{ border-left-color:#16A34A; }
.stask-card.terminated{ border-left-color:#DC2626; }
.stask-new-dot { width:10px; height:10px; background:#DC2626; border-radius:50%; display:inline-block; margin-right:4px; }
</style>
<div class="section-title">📋 งานที่ได้รับมอบหมาย <?php if ($unread): ?><span style="background:#DC2626;color:#fff;border-radius:20px;padding:2px 10px;font-size:13px;"><?= $unread ?> ใหม่</span><?php endif; ?></div>

<?php foreach ($tasks as $t): $si = task_status_info($t['status']); $isNew = $t['status'] === 'active' && !$t['viewed_at']; ?>
<div class="stask-card <?= $t['status'] ?>">
  <div style="flex:1;min-width:200px;">
    <div style="font-size:15px;font-weight:700;color:#1A237E;">
      <?php if ($isNew): ?><span class="stask-new-dot"></span><?php endif; ?>
      <?= htmlspecialchars($t['title']) ?>
    </div>
    <div style="font-size:12px;color:#94A3B8;margin-top:4px;">🧑‍💼 <?= htmlspecialchars($t['trainer_name']) ?> · 🎯 <?= (int)$t['score'] ?> คะแนน · 📅 <?= substr($t['created_at'],0,10) ?></div>
  </div>
  <div style="display:flex;align-items:center;gap:8px;">
    <span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;"><?= $si['icon'] ?> <?= $si['label'] ?></span>
    <a href="/ias/student/task_detail.php?id=<?= $t['id'] ?>" class="btn-primary" style="font-size:13px;padding:7px 14px;"><?= $isNew ? '📖 เปิดดูงาน' : 'ดูรายละเอียด' ?></a>
  </div>
</div>
<?php endforeach; ?>
<?php if (!$tasks): ?>
  <div style="text-align:center;color:#94A3B8;padding:30px;background:#F8FAFC;border-radius:14px;">ยังไม่มีงานที่ได้รับมอบหมาย</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
