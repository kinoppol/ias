<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Force re-run migrations when this page is visited
unset($_SESSION['_migrated_v'], $_SESSION['_migrate_error']);

$results = [];
$errors  = [];

try {
    $results = run_migrations($pdo);
    $_SESSION['_migrated_v'] = MIGRATE_VERSION;
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

// Extra DB info
$dbVersion  = $pdo->query("SELECT VERSION()")->fetchColumn();
$dbName     = $pdo->query("SELECT DATABASE()")->fetchColumn();
$tableList  = array_column($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM), 0);
$roleCol    = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();

$activeSection = 'migrate';
$pageTitle = 'Migration';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.mig-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07); padding:20px 22px; margin-bottom:16px; }
.mig-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #F1F5F9; gap:10px; }
.mig-row:last-child { border-bottom:none; }
.mig-name { font-size:14px; font-weight:600; color:#1E293B; font-family:monospace; }
.mig-ok    { color:#16A34A; font-weight:700; font-size:13px; }
.mig-applied { color:#D97706; font-weight:700; font-size:13px; }
.mig-error { color:#DC2626; font-weight:700; font-size:13px; }
.db-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:600px){ .db-info-grid { grid-template-columns:1fr; } }
.info-chip { background:#F8FAFC; border:1.5px solid #E2E8F0; border-radius:10px; padding:12px 16px; }
.info-chip .label { font-size:11.5px; color:#94A3B8; font-weight:600; margin-bottom:4px; }
.info-chip .val { font-size:14px; font-weight:700; color:#1E293B; font-family:monospace; word-break:break-all; }
.table-chip { display:inline-block; background:#EFF6FF; color:#1565C0; border-radius:6px; padding:3px 10px; font-size:12px; font-family:monospace; margin:3px; }
.error-box { background:#FEF2F2; border:1.5px solid #FECACA; border-radius:10px; padding:14px 16px; color:#B91C1C; font-size:13.5px; margin-bottom:14px; }
</style>

<div class="flex-between" style="margin-bottom:16px;">
  <div class="section-title" style="margin-bottom:0;">🔧 Database Migration</div>
  <form method="get">
    <button type="submit" class="btn-primary">🔄 รันอีกครั้ง</button>
  </form>
</div>

<?php if ($errors): ?>
  <?php foreach ($errors as $e): ?>
    <div class="error-box">❌ ข้อผิดพลาด: <?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Migration results -->
<div class="mig-card">
  <div style="font-size:15px;font-weight:700;color:#1A237E;margin-bottom:14px;">📋 สถานะ Migration (v<?= MIGRATE_VERSION ?>)</div>
  <?php if ($results): ?>
    <?php foreach ($results as $r): ?>
      <div class="mig-row">
        <div class="mig-name"><?= htmlspecialchars($r['name']) ?></div>
        <?php if ($r['status'] === 'ok'): ?>
          <span class="mig-ok">✅ พร้อมใช้งาน</span>
        <?php elseif ($r['status'] === 'applied'): ?>
          <span class="mig-applied">⚡ เพิ่งดำเนินการ</span>
        <?php else: ?>
          <span class="mig-error">❌ <?= htmlspecialchars($r['status']) ?></span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div style="color:#94A3B8;padding:10px 0;">ไม่มีข้อมูล migration</div>
  <?php endif; ?>
</div>

<!-- DB Info -->
<div class="mig-card">
  <div style="font-size:15px;font-weight:700;color:#1A237E;margin-bottom:14px;">🗄 ข้อมูลฐานข้อมูล</div>
  <div class="db-info-grid">
    <div class="info-chip"><div class="label">MySQL Version</div><div class="val"><?= htmlspecialchars($dbVersion) ?></div></div>
    <div class="info-chip"><div class="label">Database</div><div class="val"><?= htmlspecialchars($dbName) ?></div></div>
    <div class="info-chip"><div class="label">users.role ENUM</div><div class="val"><?= htmlspecialchars($roleCol['Type'] ?? 'ไม่พบ') ?></div></div>
    <div class="info-chip"><div class="label">Migration Session Version</div><div class="val"><?= (int)($_SESSION['_migrated_v'] ?? 0) ?> / <?= MIGRATE_VERSION ?></div></div>
  </div>
</div>

<!-- Tables -->
<div class="mig-card">
  <div style="font-size:15px;font-weight:700;color:#1A237E;margin-bottom:12px;">📦 ตารางในฐานข้อมูล (<?= count($tableList) ?> ตาราง)</div>
  <?php
  $required = ['workplaces','users','attendance','leaves','settings','holidays','tasks','task_attachments','task_threads','task_thread_attachments'];
  foreach ($required as $t): $exists = in_array($t, $tableList); ?>
    <span style="display:inline-flex;align-items:center;gap:4px;background:<?= $exists ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $exists ? '#166534' : '#DC2626' ?>;border-radius:8px;padding:4px 12px;font-size:13px;font-family:monospace;margin:3px;">
      <?= $exists ? '✅' : '❌' ?> <?= htmlspecialchars($t) ?>
    </span>
  <?php endforeach; ?>
  <div style="margin-top:10px;border-top:1px solid #F1F5F9;padding-top:10px;font-size:12px;color:#94A3B8;">
    ตารางอื่นในฐานข้อมูล:
    <?php foreach (array_diff($tableList, $required) as $t): ?>
      <span class="table-chip"><?= htmlspecialchars($t) ?></span>
    <?php endforeach; ?>
  </div>
</div>

<!-- Manual SQL (for emergencies) -->
<div class="mig-card" style="border:1.5px solid #FDE68A;">
  <div style="font-size:14px;font-weight:700;color:#92400E;margin-bottom:8px;">⚠️ หาก Migration ยังไม่สำเร็จ</div>
  <p style="font-size:13px;color:#64748B;margin-bottom:12px;">รัน SQL นี้ด้วย phpMyAdmin หรือ MySQL client โดยตรง:</p>
  <pre style="background:#1E293B;color:#94A3B8;border-radius:10px;padding:16px;font-size:12px;overflow-x:auto;line-height:1.6;">ALTER TABLE workplaces
  ADD COLUMN IF NOT EXISTS work_days VARCHAR(7) NOT NULL DEFAULT '1111100';

ALTER TABLE users
  MODIFY COLUMN role ENUM('student','teacher','admin','trainer') NOT NULL;

CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL UNIQUE,
  name VARCHAR(200) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(500) NOT NULL,
  description TEXT,
  score INT NOT NULL DEFAULT 10,
  trainer_id VARCHAR(20) NOT NULL,
  student_id VARCHAR(20) NOT NULL,
  status ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
  close_note TEXT NULL,
  viewed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS task_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  att_type ENUM('file','link') NOT NULL DEFAULT 'file',
  original_name VARCHAR(500) NULL,
  stored_name VARCHAR(500) NULL,
  link_url VARCHAR(1000) NULL,
  mime_type VARCHAR(100) NULL,
  file_size INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS task_threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  author_id VARCHAR(20) NOT NULL,
  entry_type ENUM('submission','comment') NOT NULL,
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_task(task_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS task_thread_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_id INT NOT NULL,
  att_type ENUM('file','link') NOT NULL DEFAULT 'file',
  original_name VARCHAR(500) NULL,
  stored_name VARCHAR(500) NULL,
  link_url VARCHAR(1000) NULL,
  mime_type VARCHAR(100) NULL,
  file_size INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES task_thread_attachments(id) ON DELETE CASCADE
) ENGINE=InnoDB;</pre>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
