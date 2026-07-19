<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('trainer');
$user = current_user();

$filter = $_GET['filter'] ?? 'all';
$showNew = isset($_GET['new']);

// Students at my workplace for assignment
$students = [];
if ($user['workplace_id']) {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role='student' AND workplace_id = ? ORDER BY name");
    $stmt->execute([$user['workplace_id']]);
    $students = $stmt->fetchAll();
}

// Task list
$where = "trainer_id = ?";
$params = [$user['id']];
if (in_array($filter, ['active','completed','terminated'])) { $where .= " AND status = ?"; $params[] = $filter; }
$stmt = $pdo->prepare("SELECT t.*, u.name student_name FROM tasks t JOIN users u ON u.id = t.student_id WHERE $where ORDER BY t.created_at DESC");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Unread submission counts (threads by student not yet replied by trainer)
$pendingMap = [];
$stmt2 = $pdo->prepare("SELECT task_id, COUNT(*) c FROM task_threads WHERE entry_type='submission' AND task_id IN (SELECT id FROM tasks WHERE trainer_id=?) GROUP BY task_id");
$stmt2->execute([$user['id']]);
foreach ($stmt2->fetchAll() as $r) $pendingMap[$r['task_id']] = (int)$r['c'];

$activeSection = 'tasks';
$pageTitle = 'งานที่มอบหมาย';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.task-card { background:#fff; border-radius:13px; box-shadow:0 1px 6px rgba(0,0,0,.07); padding:16px 18px; margin-bottom:10px; border-left:4px solid #CBD5E1; }
.task-card.active { border-left-color:#1565C0; }
.task-card.completed { border-left-color:#16A34A; }
.task-card.terminated { border-left-color:#DC2626; }
.task-card-title { font-size:15px; font-weight:700; color:#1A237E; }
.task-card-meta { font-size:12px; color:#94A3B8; margin-top:4px; }
.task-new-form { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:22px; margin-bottom:18px; border:2px solid #DBEAFE; }
.task-new-form label { font-size:12.5px; font-weight:600; color:#374151; display:block; margin-bottom:4px; }
.task-new-form input[type=text], .task-new-form input[type=number], .task-new-form textarea, .task-new-form select { width:100%; padding:9px 12px; border:1.5px solid #CBD5E1; border-radius:9px; font-size:14px; font-family:inherit; box-sizing:border-box; }
.task-new-form textarea { resize:vertical; min-height:80px; }
.link-row { display:flex; gap:6px; margin-top:6px; }
.link-row input { flex:1; }
.link-add-btn { background:#EFF6FF; color:#1565C0; border:1.5px solid #BFDBFE; border-radius:8px; padding:6px 12px; font-size:13px; cursor:pointer; white-space:nowrap; }
</style>
<div class="flex-between">
  <div class="section-title" style="margin-bottom:0;">📋 งานที่มอบหมาย</div>
  <a href="?new=<?= $showNew ? '0' : '1' ?>&filter=<?= htmlspecialchars($filter) ?>" class="btn-primary"><?= $showNew ? '✕ ปิดฟอร์ม' : '+ มอบหมายงาน' ?></a>
</div>

<?php if ($showNew): ?>
<div class="task-new-form">
  <div style="font-size:15px;font-weight:700;color:#1A237E;margin-bottom:16px;">📝 มอบหมายงานใหม่</div>
  <form method="post" action="/ias/ajax/create_task.php" enctype="multipart/form-data">
    <div class="wp-form-grid">
      <div>
        <label>ชื่องาน / หัวข้อ *</label>
        <input type="text" name="title" required placeholder="ระบุหัวข้องาน">
      </div>
      <div>
        <label>มอบหมายให้นักศึกษา *</label>
        <select name="student_id" required>
          <option value="">— เลือกนักศึกษา —</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>คะแนนเต็ม</label>
        <input type="number" name="score" value="10" min="1" max="100">
      </div>
      <div>
        <label>📅 กำหนดส่ง / วันสิ้นสุด</label>
        <input type="datetime-local" name="due_date">
      </div>
    </div>
    <div style="margin-bottom:12px;">
      <label>รายละเอียดงาน / คำอธิบาย</label>
      <textarea name="description" placeholder="อธิบายรายละเอียดงาน วัตถุประสงค์ เงื่อนไขการส่ง..."></textarea>
    </div>
    <div style="margin-bottom:12px;">
      <label>📎 แนบไฟล์ (เลือกได้หลายไฟล์)</label>
      <input type="file" name="files[]" multiple style="margin-top:4px;" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
    </div>
    <div style="margin-bottom:16px;">
      <label>🔗 ลิงก์อ้างอิง (กด + เพื่อเพิ่ม)</label>
      <div id="linkRows">
        <div class="link-row"><input type="text" name="links[]" placeholder="https://..."><button type="button" class="link-add-btn" onclick="addLink()">+ ลิงก์</button></div>
      </div>
    </div>
    <button type="submit" class="btn-submit" style="width:100%;">📤 มอบหมายงาน</button>
  </form>
</div>
<script>
function addLink() {
  const d = document.createElement('div'); d.className = 'link-row';
  d.innerHTML = '<input type="text" name="links[]" placeholder="https://..."><button type="button" class="link-add-btn" onclick="this.parentNode.remove()">✕ ลบ</button>';
  document.getElementById('linkRows').appendChild(d);
}
</script>
<?php endif; ?>

<!-- Filter tabs -->
<div class="report-tabs no-print" style="margin-bottom:14px;">
  <?php foreach (['all' => 'ทั้งหมด', 'active' => '🔵 กำลังดำเนินการ', 'completed' => '✅ เสร็จสิ้น', 'terminated' => '🔴 สิ้นสุด'] as $k => $l): ?>
    <a href="?filter=<?= $k ?><?= $showNew ? '&new=1' : '' ?>" class="report-tab <?= $filter === $k ? 'active' : '' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<?php foreach ($tasks as $t): $si = task_status_info($t['status']); $subs = $pendingMap[$t['id']] ?? 0; ?>
<div class="task-card <?= $t['status'] ?>">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
      <div class="task-card-title">
        <a href="/ias/trainer/task_detail.php?id=<?= $t['id'] ?>" style="color:#1A237E;"><?= htmlspecialchars($t['title']) ?></a>
        <?php if ($subs > 0): ?><span style="background:#FEF3C7;color:#D97706;border-radius:20px;padding:2px 10px;font-size:12px;margin-left:6px;">📬 <?= $subs ?> ส่งงาน</span><?php endif; ?>
      </div>
      <div class="task-card-meta">
        👤 <?= htmlspecialchars($t['student_name']) ?> · 📅 <?= substr($t['created_at'],0,10) ?> · 🎯 <?= (int)$t['score'] ?> คะแนน · <?= $t['viewed_at'] ? '👁 เปิดดูแล้ว' : '⏳ ยังไม่เปิด' ?>
        <?php if ($t['due_date']): $overdue = $t['status']==='active' && $t['due_date'] < date('Y-m-d H:i:s'); ?>
        · <span style="color:<?= $overdue ? '#DC2626' : '#D97706' ?>;font-weight:600;"><?= $overdue ? '⚠️ เกินกำหนด' : '⏰' ?> กำหนดส่ง: <?= substr($t['due_date'],0,16) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;"><?= $si['icon'] ?> <?= $si['label'] ?></span>
      <a href="/ias/trainer/task_detail.php?id=<?= $t['id'] ?>" class="btn-toggle">ดูรายละเอียด</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php if (!$tasks): ?>
  <div style="text-align:center;color:#94A3B8;padding:30px;">ยังไม่มีงานที่มอบหมาย</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
