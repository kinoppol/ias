<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['teacher', 'admin']);
$user = current_user();

// Filter
$filterStudent = $_GET['student'] ?? '';
$filterStatus  = $_GET['status'] ?? '';
$filterTrainer = $_GET['trainer'] ?? '';

$students = $pdo->query("SELECT id, name FROM users WHERE role='student' ORDER BY name")->fetchAll();
$trainers = $pdo->query("SELECT id, name FROM users WHERE role='trainer' ORDER BY name")->fetchAll();

$where = ['1=1'];
$params = [];
if ($filterStudent) { $where[] = 't.student_id = ?'; $params[] = $filterStudent; }
if ($filterTrainer) { $where[] = 't.trainer_id = ?'; $params[] = $filterTrainer; }
if (in_array($filterStatus, ['active','completed','terminated'])) { $where[] = 't.status = ?'; $params[] = $filterStatus; }

$sql = "SELECT t.*, s.name student_name, s.dept student_dept, tr.name trainer_name,
        (SELECT COUNT(*) FROM task_threads tt WHERE tt.task_id = t.id AND tt.entry_type='submission') sub_count
        FROM tasks t
        JOIN users s ON s.id = t.student_id
        JOIN users tr ON tr.id = t.trainer_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Summary stats
$total = count($tasks);
$active = count(array_filter($tasks, fn($t) => $t['status'] === 'active'));
$completed = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
$terminated = count(array_filter($tasks, fn($t) => $t['status'] === 'terminated'));

$activeSection = 'task_report';
$pageTitle = 'ติดตามงาน';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.filter-bar { background:#fff; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.07); padding:14px 16px; margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
.filter-bar label { font-size:12px; font-weight:600; color:#64748B; display:block; margin-bottom:3px; }
.filter-bar select { padding:8px 10px; border:1.5px solid #CBD5E1; border-radius:8px; font-size:13.5px; font-family:inherit; }
</style>
<div class="flex-between" style="margin-bottom:12px;">
  <div class="section-title" style="margin-bottom:0;">📌 ติดตามงาน</div>
  <button onclick="window.print()" class="btn-primary no-print">🖨 พิมพ์</button>
</div>

<div class="stats-card" style="margin-bottom:16px;">
  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-box stat-blue"><div class="stat-value"><?= $total ?></div><div class="stat-label">ทั้งหมด</div></div>
    <div class="stat-box" style="background:#EFF6FF;"><div class="stat-value" style="color:#1565C0;"><?= $active ?></div><div class="stat-label">กำลังดำเนินการ</div></div>
    <div class="stat-box stat-green"><div class="stat-value"><?= $completed ?></div><div class="stat-label">เสร็จสิ้น</div></div>
    <div class="stat-box stat-red"><div class="stat-value"><?= $terminated ?></div><div class="stat-label">สิ้นสุด</div></div>
  </div>
</div>

<form method="get" class="filter-bar no-print">
  <div>
    <label>นักศึกษา</label>
    <select name="student" onchange="this.form.submit()">
      <option value="">— ทุกคน —</option>
      <?php foreach ($students as $s): ?>
        <option value="<?= htmlspecialchars($s['id']) ?>" <?= $filterStudent === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>ครูฝึก</label>
    <select name="trainer" onchange="this.form.submit()">
      <option value="">— ทุกคน —</option>
      <?php foreach ($trainers as $tr): ?>
        <option value="<?= htmlspecialchars($tr['id']) ?>" <?= $filterTrainer === $tr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tr['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>สถานะ</label>
    <select name="status" onchange="this.form.submit()">
      <option value="">— ทุกสถานะ —</option>
      <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>🔵 กำลังดำเนินการ</option>
      <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>✅ เสร็จสิ้น</option>
      <option value="terminated" <?= $filterStatus === 'terminated' ? 'selected' : '' ?>>🔴 สิ้นสุด</option>
    </select>
  </div>
  <div>
    <a href="?" class="btn-toggle">ล้างตัวกรอง</a>
  </div>
</form>

<div class="report-table-wrap">
  <div class="table-scroll">
    <table class="data-table" style="min-width:700px;">
      <thead><tr>
        <th>ชื่องาน</th><th>นักศึกษา</th><th>ครูฝึก</th>
        <th class="center">คะแนน</th><th class="center">ส่งงาน</th>
        <th class="center">เปิดดู</th><th class="center">วันที่</th><th class="center">สถานะ</th>
      </tr></thead>
      <tbody>
      <?php foreach ($tasks as $t): $si = task_status_info($t['status']); ?>
        <tr>
          <td style="font-weight:600;color:#1A237E;max-width:200px;">
            <?= htmlspecialchars($t['title']) ?>
            <?php if ($t['close_note']): ?>
              <div style="font-size:11px;color:#DC2626;margin-top:2px;">📝 <?= htmlspecialchars(mb_substr($t['close_note'], 0, 40)) ?>...</div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($t['student_name']) ?></div>
            <div style="font-size:11px;color:#94A3B8;"><?= htmlspecialchars($t['student_dept'] ?: '') ?></div>
          </td>
          <td style="color:#6D28D9;"><?= htmlspecialchars($t['trainer_name']) ?></td>
          <td class="center" style="font-weight:700;color:#1565C0;"><?= (int)$t['score'] ?></td>
          <td class="center"><?= (int)$t['sub_count'] ?> ครั้ง</td>
          <td class="center"><?= $t['viewed_at'] ? '<span style="color:#16A34A;">✅</span>' : '<span style="color:#94A3B8;">—</span>' ?></td>
          <td class="center" style="font-size:12px;color:#64748B;"><?= substr($t['created_at'],0,10) ?></td>
          <td class="center"><span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;"><?= $si['icon'] ?> <?= $si['label'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$tasks): ?>
        <tr><td colspan="8" style="text-align:center;color:#94A3B8;padding:20px;">ไม่มีข้อมูล</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
