<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

$today = date('Y-m-d');
$stmt = $pdo->query("SELECT u.*, w.name AS wp_name FROM users u LEFT JOIN workplaces w ON w.id = u.workplace_id WHERE u.role = 'student' ORDER BY u.id");
$students = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM attendance WHERE date = ?');
$stmt->execute([$today]);
$attByStudent = [];
foreach ($stmt->fetchAll() as $a) { $attByStudent[$a['student_id']] = $a; }

$activeSection = 'students';
$pageTitle = 'รายชื่อนักศึกษาฝึกงาน';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">👥 รายชื่อนักศึกษาฝึกงานทั้งหมด</div>
<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:560px;">
      <thead><tr>
        <th>รหัส</th><th>ชื่อ-สกุล</th><th>ระดับ</th><th>แผนก</th><th>สถานที่</th><th class="center">วันนี้</th>
      </tr></thead>
      <tbody>
        <?php foreach ($students as $st):
          $att = $attByStudent[$st['id']] ?? null;
          $si = status_info($att['status'] ?? null);
          $label = $att ? $si['label'] : 'ยังไม่เช็ค';
        ?>
        <tr>
          <td style="font-weight:600;color:#0D47A1;"><?= htmlspecialchars($st['id']) ?></td>
          <td style="font-weight:600;color:#1E293B;"><?= htmlspecialchars($st['name']) ?></td>
          <td style="color:#64748B;"><?= htmlspecialchars($st['grade'] ?: '-') ?></td>
          <td style="color:#64748B;"><?= htmlspecialchars($st['dept'] ?: '-') ?></td>
          <td style="color:#64748B;font-size:12px;"><?= htmlspecialchars(short_wp_name($st['wp_name'])) ?></td>
          <td class="center"><span class="badge" style="color:<?= $att ? $si['color'] : '#64748B' ?>;background:<?= $att ? $si['bg'] : '#F1F5F9' ?>;"><?= htmlspecialchars($label) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
