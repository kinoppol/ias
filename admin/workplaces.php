<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$workplaces = $pdo->query('SELECT * FROM workplaces ORDER BY id')->fetchAll();
$studentCounts = [];
foreach ($pdo->query("SELECT workplace_id, COUNT(*) c FROM users WHERE role='student' GROUP BY workplace_id")->fetchAll() as $r) {
    $studentCounts[$r['workplace_id']] = (int)$r['c'];
}
$teachers = $pdo->query("SELECT id, name FROM users WHERE role='teacher' ORDER BY name")->fetchAll();

$showAddForm = isset($_GET['add']);
$editId = $_GET['edit'] ?? null;
$editWp = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM workplaces WHERE id = ?');
    $stmt->execute([$editId]);
    $editWp = $stmt->fetch();
}

function wp_form_fields($wp, $teachers) {
    $name = htmlspecialchars($wp['name'] ?? '');
    $address = htmlspecialchars($wp['address'] ?? '');
    $lat = htmlspecialchars($wp['lat'] ?? '');
    $lng = htmlspecialchars($wp['lng'] ?? '');
    $radius = htmlspecialchars($wp['radius'] ?? 200);
    $startTime = substr($wp['start_time'] ?? '08:00:00', 0, 5);
    $endTime = substr($wp['end_time'] ?? '17:00:00', 0, 5);
    $teacherId = $wp['teacher_id'] ?? '';
    $allowOt = !empty($wp['allow_ot']);
    $workDays = $wp['work_days'] ?? '1111100';
    $dayNames = ['จ.','อ.','พ.','พฤ.','ศ.','ส.','อา.'];
    ?>
    <div class="wp-form-grid">
      <div>
        <label>ชื่อสถานประกอบการ</label>
        <input type="text" name="name" value="<?= $name ?>" required>
      </div>
      <div>
        <label>ที่อยู่</label>
        <input type="text" name="address" value="<?= $address ?>" required>
      </div>
      <div>
        <label>Latitude</label>
        <input type="text" name="lat" value="<?= $lat ?>" placeholder="13.7563" required>
      </div>
      <div>
        <label>Longitude</label>
        <input type="text" name="lng" value="<?= $lng ?>" placeholder="100.5018" required>
      </div>
      <div>
        <label>⏰ เวลาเข้างาน</label>
        <input type="time" name="start_time" value="<?= $startTime ?>" required>
      </div>
      <div>
        <label>⏰ เวลาออกงาน</label>
        <input type="time" name="end_time" value="<?= $endTime ?>" required>
      </div>
      <div>
        <label>📏 รัศมีเช็คอิน (เมตร)</label>
        <input type="number" name="radius" min="50" max="2000" value="<?= $radius ?>" required>
      </div>
      <div>
        <label>ครูนิเทศผู้ดูแล</label>
        <select name="teacher_id">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($teachers as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>" <?= $teacherId === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">📅 วันทำงาน</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach ($dayNames as $i => $d): ?>
          <label style="display:flex;flex-direction:column;align-items:center;gap:4px;cursor:pointer;">
            <input type="checkbox" name="work_days[]" value="<?= $i ?>" <?= ($workDays[$i] ?? '0') === '1' ? 'checked' : '' ?> style="width:18px;height:18px;">
            <span style="font-size:13px;font-weight:600;color:<?= $i >= 5 ? '#DC2626' : '#1E293B' ?>;"><?= $d ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;color:#374151;margin-bottom:14px;">
      <input type="checkbox" name="allow_ot" value="1" <?= $allowOt ? 'checked' : '' ?>> อนุญาตการทำงานล่วงเวลา (OT)
    </label>
    <?php
}

$activeSection = 'workplaces';
$pageTitle = 'สถานประกอบการ';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between">
  <div class="section-title" style="margin-bottom:0;">🏢 สถานประกอบการ</div>
  <a href="<?= $showAddForm ? '?' : '?add=1' ?>" class="btn-primary"><?= $showAddForm ? 'ปิดฟอร์ม' : '+ เพิ่มสถานประกอบการ' ?></a>
</div>

<?php if ($showAddForm): ?>
<div class="wp-form-card" style="border:2px solid #DBEAFE;">
  <div class="wp-form-name" style="margin-bottom:16px;">เพิ่มสถานประกอบการใหม่</div>
  <form method="post" action="/ias/ajax/save_wp.php">
    <?php wp_form_fields(null, $teachers); ?>
    <button type="submit" class="btn-submit" style="width:100%;">บันทึก</button>
  </form>
</div>
<?php endif; ?>

<?php foreach ($workplaces as $wp): $sc = $studentCounts[$wp['id']] ?? 0; ?>
  <?php if ($editWp && $editWp['id'] === $wp['id']): ?>
  <div class="wp-form-card" style="border:2px solid #DBEAFE;">
    <div class="wp-form-name" style="margin-bottom:16px;">แก้ไข: <?= htmlspecialchars($wp['name']) ?></div>
    <form method="post" action="/ias/ajax/save_wp.php">
      <input type="hidden" name="id" value="<?= htmlspecialchars($wp['id']) ?>">
      <?php wp_form_fields($wp, $teachers); ?>
      <div class="leave-form-actions">
        <button type="submit" class="btn-submit">บันทึก</button>
        <a href="?" class="btn-cancel">ยกเลิก</a>
      </div>
    </form>
  </div>
  <?php else: ?>
  <div class="wp-admin-card">
    <div class="wp-admin-row">
      <div style="flex:1;min-width:240px;">
        <div style="font-size:15px;font-weight:700;color:#1E293B;"><?= htmlspecialchars($wp['name']) ?></div>
        <div style="font-size:12.5px;color:#94A3B8;margin-top:3px;">📍 <?= htmlspecialchars($wp['address']) ?></div>
        <div class="wp-admin-meta">
          <span>⏰ <?= substr($wp['start_time'], 0, 5) ?>–<?= substr($wp['end_time'], 0, 5) ?></span>
          <span>📏 รัศมี <?= htmlspecialchars($wp['radius']) ?> ม.</span>
          <span>👥 <?= $sc ?> คน</span>
          <span>OT: <?= $wp['allow_ot'] ? 'เปิด' : 'ปิด' ?></span>
        </div>
      </div>
      <div class="wp-admin-actions">
        <span class="badge" style="color:<?= $wp['active'] ? '#16A34A' : '#DC2626' ?>;background:<?= $wp['active'] ? '#DCFCE7' : '#FEE2E2' ?>;"><?= $wp['active'] ? 'ใช้งาน' : 'ปิดใช้งาน' ?></span>
        <a href="?edit=<?= htmlspecialchars($wp['id']) ?>" class="btn-toggle">แก้ไข</a>
        <form method="post" action="/ias/ajax/toggle_wp.php" style="display:inline;">
          <input type="hidden" name="id" value="<?= htmlspecialchars($wp['id']) ?>">
          <button type="submit" class="btn-toggle">เปิด/ปิด</button>
        </form>
        <form method="post" action="/ias/ajax/delete_wp.php" onsubmit="return confirm('ยืนยันการลบสถานประกอบการ?');" style="display:inline;">
          <input type="hidden" name="id" value="<?= htmlspecialchars($wp['id']) ?>">
          <button type="submit" class="btn-delete">ลบ</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
<?php endforeach; ?>
<?php if (!$workplaces): ?>
  <div class="card" style="padding:30px;text-align:center;color:#94A3B8;">ยังไม่มีสถานประกอบการ</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
