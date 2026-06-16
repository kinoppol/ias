<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$rmap = [
    'student' => ['label' => 'นักศึกษาฝึกงาน', 'color' => '#1E40AF', 'bg' => '#DBEAFE'],
    'teacher' => ['label' => 'ครูนิเทศ', 'color' => '#166534', 'bg' => '#DCFCE7'],
    'admin'   => ['label' => 'ผู้ดูแลระบบ', 'color' => '#92400E', 'bg' => '#FEF3C7'],
];

$stmt = $pdo->query('SELECT u.*, w.name AS wp_name FROM users u LEFT JOIN workplaces w ON w.id = u.workplace_id ORDER BY u.role, u.id');
$users = $stmt->fetchAll();

$activeSection = 'users';
$pageTitle = 'จัดการผู้ใช้งาน';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="section-title">👤 จัดการผู้ใช้งาน</div>
<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:500px;">
      <thead><tr>
        <th>รหัส</th><th>ชื่อ-สกุล</th><th>บทบาท</th><th>สถานประกอบการ</th><th class="center">จัดการ</th>
      </tr></thead>
      <tbody>
        <?php foreach ($users as $u): $ri = $rmap[$u['role']] ?? ['label' => $u['role'], 'color' => '#374151', 'bg' => '#F1F5F9']; ?>
        <tr>
          <td style="font-weight:700;color:#0D47A1;"><?= htmlspecialchars($u['id']) ?></td>
          <td style="color:#1E293B;"><?= htmlspecialchars($u['name']) ?></td>
          <td><span class="badge" style="color:<?= $ri['color'] ?>;background:<?= $ri['bg'] ?>;"><?= htmlspecialchars($ri['label']) ?></span></td>
          <td style="color:#64748B;"><?= htmlspecialchars(short_wp_name($u['wp_name'])) ?></td>
          <td class="center">
            <form method="post" action="/ias/ajax/delete_user.php" onsubmit="return confirm('ยืนยันการลบผู้ใช้?');" style="display:inline;">
              <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
              <button type="submit" class="btn-delete-sm">ลบ</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
