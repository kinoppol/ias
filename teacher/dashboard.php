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

$sws = [];
$tsPresent = $tsLate = $tsAbsent = 0;
foreach ($students as $st) {
    $att = $attByStudent[$st['id']] ?? null;
    $si = status_info($att['status'] ?? null);
    $label = $att ? $si['label'] : 'ยังไม่เช็ค';
    $sws[] = [
        'id' => $st['id'], 'name' => $st['name'], 'dept' => $st['dept'] ?: '-', 'grade' => $st['grade'] ?: '-',
        'wpName' => short_wp_name($st['wp_name']),
        'ci' => $att ? fmt_time($att['check_in_time']) : '-',
        'co' => $att ? fmt_time($att['check_out_time']) : '-',
        'label' => $label,
        'color' => $att ? $si['color'] : '#64748B',
        'bg' => $att ? $si['bg'] : '#F1F5F9',
    ];
    if ($label === 'มาตรงเวลา') $tsPresent++;
    elseif ($label === 'มาสาย') $tsLate++;
    else $tsAbsent++;
}

$activeSection = 'dashboard';
$pageTitle = 'ภาพรวมวันนี้';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">📊 ภาพรวมวันนี้ — <span class="live-date"></span></div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:11px;margin-bottom:18px;">
  <div class="card" style="padding:16px;text-align:center;"><div style="font-size:30px;font-weight:700;color:#0D47A1;"><?= count($sws) ?></div><div style="font-size:12px;color:#64748B;margin-top:3px;">ทั้งหมด</div></div>
  <div class="card" style="padding:16px;text-align:center;background:#F0FDF4;"><div style="font-size:30px;font-weight:700;color:#16A34A;"><?= $tsPresent ?></div><div style="font-size:12px;color:#64748B;margin-top:3px;">มาตรงเวลา</div></div>
  <div class="card" style="padding:16px;text-align:center;background:#FEF3C7;"><div style="font-size:30px;font-weight:700;color:#D97706;"><?= $tsLate ?></div><div style="font-size:12px;color:#64748B;margin-top:3px;">มาสาย</div></div>
  <div class="card" style="padding:16px;text-align:center;background:#FEE2E2;"><div style="font-size:30px;font-weight:700;color:#DC2626;"><?= $tsAbsent ?></div><div style="font-size:12px;color:#64748B;margin-top:3px;">ขาด/ไม่เช็ค</div></div>
</div>

<div class="table-card">
  <div class="table-card-header">สถานะนักศึกษาวันนี้</div>
  <div class="table-scroll">
    <table class="data-table" style="min-width:480px;">
      <thead><tr>
        <th>ชื่อ-สกุล</th><th>แผนก</th><th class="center">เข้างาน</th><th class="center">ออกงาน</th><th class="center">สถานะ</th>
      </tr></thead>
      <tbody>
        <?php foreach ($sws as $st): ?>
        <tr>
          <td><div style="font-weight:600;color:#1E293B;"><?= htmlspecialchars($st['name']) ?></div><div style="font-size:11px;color:#94A3B8;"><?= htmlspecialchars($st['id']) ?></div></td>
          <td style="color:#64748B;"><?= htmlspecialchars($st['dept']) ?></td>
          <td class="center" style="font-weight:700;color:#0D47A1;"><?= htmlspecialchars($st['ci']) ?></td>
          <td class="center" style="color:#374151;"><?= htmlspecialchars($st['co']) ?></td>
          <td class="center"><span class="badge" style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;"><?= htmlspecialchars($st['label']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
