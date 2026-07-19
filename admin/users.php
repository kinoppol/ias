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
$workplaces = $pdo->query('SELECT id, name FROM workplaces ORDER BY name')->fetchAll();

$showAddForm = isset($_GET['add']);
$editId = $_GET['edit'] ?? null;
$editUser = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

function user_form_fields($u, $workplaces, $isEdit) {
    $id = htmlspecialchars($u['id'] ?? '');
    $name = htmlspecialchars($u['name'] ?? '');
    $role = $u['role'] ?? 'student';
    $grade = htmlspecialchars($u['grade'] ?? '');
    $dept = htmlspecialchars($u['dept'] ?? '');
    $workplaceId = $u['workplace_id'] ?? '';
    ?>
    <div class="wp-form-grid">
      <div>
        <label>รหัสผู้ใช้งาน (User ID)</label>
        <input type="text" name="id" value="<?= $id ?>" placeholder="STD006 / TEACHER02" <?= $isEdit ? 'readonly style="background:#F1F5F9;"' : 'required' ?>>
      </div>
      <div>
        <label>ชื่อ-สกุล</label>
        <input type="text" name="name" value="<?= $name ?>" required>
      </div>
      <div>
        <label>บทบาท</label>
        <select name="role" onchange="document.getElementById('studentFields').style.display = this.value==='student' ? '' : 'none';" id="roleSelect">
          <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>นักศึกษาฝึกงาน</option>
          <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>ครูนิเทศ</option>
          <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
        </select>
      </div>
      <div>
        <label>รหัสผ่าน<?= $isEdit ? ' (เว้นว่างถ้าไม่เปลี่ยน)' : '' ?></label>
        <input type="password" name="password" placeholder="<?= $isEdit ? '••••••••' : 'กำหนดรหัสผ่าน' ?>">
      </div>
    </div>
    <div id="studentFields" style="<?= $role === 'student' ? '' : 'display:none;' ?>">
      <div class="wp-form-grid">
        <div>
          <label>ระดับชั้น</label>
          <input type="text" name="grade" value="<?= $grade ?>" placeholder="ปวส.1 / ปวส.2">
        </div>
        <div>
          <label>แผนก</label>
          <input type="text" name="dept" value="<?= $dept ?>" placeholder="ช่างไฟฟ้า">
        </div>
        <div>
          <label>สถานประกอบการ</label>
          <select name="workplace_id">
            <option value="">— ไม่ระบุ —</option>
            <?php foreach ($workplaces as $wp): ?>
              <option value="<?= htmlspecialchars($wp['id']) ?>" <?= $workplaceId === $wp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($wp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <?php
}

$activeSection = 'users';
$pageTitle = 'จัดการผู้ใช้งาน';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between">
  <div class="section-title" style="margin-bottom:0;">👤 จัดการผู้ใช้งาน</div>
  <a href="<?= $showAddForm ? '?' : '?add=1' ?>" class="btn-primary"><?= $showAddForm ? 'ปิดฟอร์ม' : '+ เพิ่มผู้ใช้งาน' ?></a>
</div>

<?php if ($showAddForm): ?>
<div class="wp-form-card" style="border:2px solid #DBEAFE;">
  <div class="wp-form-name" style="margin-bottom:16px;">เพิ่มผู้ใช้งานใหม่</div>
  <form method="post" action="/ias/ajax/save_user.php">
    <?php user_form_fields(null, $workplaces, false); ?>
    <button type="submit" class="btn-submit" style="width:100%;margin-top:14px;">บันทึก</button>
  </form>
</div>
<?php endif; ?>

<?php if ($editUser): ?>
<div class="wp-form-card" style="border:2px solid #DBEAFE;">
  <div class="wp-form-name" style="margin-bottom:16px;">แก้ไขผู้ใช้งาน: <?= htmlspecialchars($editUser['name']) ?></div>
  <form method="post" action="/ias/ajax/save_user.php">
    <input type="hidden" name="original_id" value="<?= htmlspecialchars($editUser['id']) ?>">
    <?php user_form_fields($editUser, $workplaces, true); ?>
    <div class="leave-form-actions" style="margin-top:14px;">
      <button type="submit" class="btn-submit">บันทึก</button>
      <a href="?" class="btn-cancel">ยกเลิก</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:560px;">
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
          <td class="center" style="white-space:nowrap;">
            <?php if ($u['id'] !== current_user()['id']): ?>
            <form method="post" action="/ias/ajax/impersonate.php" style="display:inline;margin-right:4px;">
              <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
              <button type="submit" class="btn-toggle" style="background:#EDE9FE;color:#6D28D9;">🎭 สวมสิทธิ์</button>
            </form>
            <?php endif; ?>
            <a href="?edit=<?= htmlspecialchars($u['id']) ?>" class="btn-toggle" style="margin-right:4px;">แก้ไข</a>
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
