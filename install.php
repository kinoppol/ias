<?php
// ตัวติดตั้งระบบบันทึกการเข้างานฝึกงาน (OVEC)
// ลบไฟล์นี้ทิ้งหลังติดตั้งเสร็จเพื่อความปลอดภัย

date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/includes/logo.php';

$configPath = __DIR__ . '/config/db.php';
$schemaPath = __DIR__ . '/sql/schema.sql';

function already_installed($host, $name, $user, $pass) {
    try {
        $mysqli = @new mysqli($host, $user, $pass, $name);
        if ($mysqli->connect_errno) return false;
        $res = $mysqli->query("SHOW TABLES LIKE 'users'");
        $installed = $res && $res->num_rows > 0;
        $mysqli->close();
        return $installed;
    } catch (Throwable $e) {
        return false;
    }
}

$existingHost = $existingName = $existingUser = $existingPass = '';
if (file_exists($configPath)) {
    $src = file_get_contents($configPath);
    if (preg_match("/\\\$DB_HOST\s*=\s*'([^']*)'/", $src, $m)) $existingHost = $m[1];
    if (preg_match("/\\\$DB_NAME\s*=\s*'([^']*)'/", $src, $m)) $existingName = $m[1];
    if (preg_match("/\\\$DB_USER\s*=\s*'([^']*)'/", $src, $m)) $existingUser = $m[1];
    if (preg_match("/\\\$DB_PASS\s*=\s*'([^']*)'/", $src, $m)) $existingPass = $m[1];
}

$alreadyInstalled = $existingName && already_installed($existingHost, $existingName, $existingUser, $existingPass);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? 'ovec_attendance');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $forceReinstall = isset($_POST['force_reinstall']);

    if (!$host || !$name || !$user) {
        $errors[] = 'กรุณากรอกข้อมูลการเชื่อมต่อฐานข้อมูลให้ครบถ้วน';
    }

    if (!$errors && !file_exists($schemaPath)) {
        $errors[] = 'ไม่พบไฟล์ sql/schema.sql';
    }

    if (!$errors) {
        $isInstalled = already_installed($host, $name, $user, $pass);
        if ($isInstalled && !$forceReinstall) {
            $errors[] = 'ระบบติดตั้งไว้แล้ว (พบตาราง users ในฐานข้อมูล) — ติ๊ก "ติดตั้งใหม่ทับของเดิม" หากต้องการเขียนทับข้อมูลทั้งหมด';
        }
    }

    if (!$errors) {
        try {
            $mysqli = new mysqli($host, $user, $pass);
        } catch (Throwable $e) {
            $mysqli = null;
        }
        if (!$mysqli || $mysqli->connect_errno) {
            $errors[] = 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . ($mysqli ? $mysqli->connect_error : 'unknown error');
        } else {
            if ($forceReinstall) {
                $mysqli->query("DROP DATABASE IF EXISTS `$name`");
            }
            $mysqli->set_charset('utf8mb4');
            $sql = file_get_contents($schemaPath);
            // Replace the hardcoded database name in schema.sql with the chosen one
            $sql = preg_replace('/ovec_attendance/', $name, $sql);

            if (!$mysqli->multi_query($sql)) {
                $errors[] = 'เกิดข้อผิดพลาดขณะติดตั้งฐานข้อมูล: ' . $mysqli->error;
            } else {
                do {
                    if ($res = $mysqli->store_result()) $res->free();
                    if (!$mysqli->more_results()) break;
                    if (!$mysqli->next_result()) {
                        $errors[] = 'เกิดข้อผิดพลาดขณะติดตั้งฐานข้อมูล: ' . $mysqli->error;
                        break;
                    }
                } while (true);
            }
            $mysqli->close();
        }
    }

    if (!$errors) {
        $configContent = "<?php\n"
            . "\$DB_HOST = '" . addslashes($host) . "';\n"
            . "\$DB_NAME = '" . addslashes($name) . "';\n"
            . "\$DB_USER = '" . addslashes($user) . "';\n"
            . "\$DB_PASS = '" . addslashes($pass) . "';\n\n"
            . "try {\n"
            . "    \$pdo = new PDO(\n"
            . "        \"mysql:host=\$DB_HOST;dbname=\$DB_NAME;charset=utf8mb4\",\n"
            . "        \$DB_USER,\n"
            . "        \$DB_PASS,\n"
            . "        [\n"
            . "            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n"
            . "            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
            . "        ]\n"
            . "    );\n"
            . "    \$pdo->exec(\"SET time_zone = '+07:00'\");\n"
            . "} catch (PDOException \$e) {\n"
            . "    die('Database connection failed: ' . \$e->getMessage());\n"
            . "}\n";

        if (@file_put_contents($configPath, $configContent) === false) {
            $errors[] = 'เขียนไฟล์ config/db.php ไม่สำเร็จ — กรุณาตรวจสอบสิทธิ์การเขียนไฟล์ของโฟลเดอร์ config/';
        } else {
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ติดตั้งระบบ — ระบบบันทึกการเข้างานฝึกงาน</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/ias/assets/css/style.css">
<style>
  .install-body { min-height:100vh; background:linear-gradient(145deg,#0A2761 0%,#1565C0 55%,#0288D1 100%); display:flex; align-items:center; justify-content:center; padding:20px; }
  .install-wrap { width:100%; max-width:520px; }
  .install-card { background:white; border-radius:20px; padding:30px 32px; box-shadow:0 24px 64px rgba(0,0,0,0.22); }
  .install-title { font-size:19px; font-weight:700; color:#1A237E; text-align:center; margin-bottom:2px; }
  .install-subtitle { font-size:12.5px; color:#78909C; text-align:center; margin-bottom:24px; }
  .install-error { background:#FEF2F2; border:1.5px solid #FECACA; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:13.5px; color:#B91C1C; }
  .install-success { background:#F0FDF4; border:1.5px solid #BBF7D0; border-radius:10px; padding:14px 16px; margin-bottom:16px; font-size:13.5px; color:#166534; line-height:1.7; }
  .install-warning { background:#FEF9EC; border:1.5px solid #FDE68A; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:13px; color:#92400E; }
  .checkbox-row { display:flex; align-items:center; gap:8px; margin-bottom:16px; font-size:13.5px; color:#92400E; }
  .btn-link { display:block; text-align:center; width:100%; padding:13px; background:linear-gradient(135deg,#0D47A1,#1976D2); color:white; border-radius:12px; font-size:16px; font-weight:700; margin-top:8px; }
</style>
</head>
<body class="install-body">
  <div class="install-wrap">
    <div class="login-banner" style="margin-bottom:14px;">
      <div class="login-logo"><?= ovec_logo_full(72) ?></div>
      <div class="login-banner-title">ติดตั้งระบบบันทึกการเข้างานฝึกงาน</div>
      <div class="login-banner-sub">OVEC INTERNSHIP ATTENDANCE SYSTEM — INSTALLER</div>
    </div>

    <div class="install-card">
      <?php if ($success): ?>
        <div class="install-title">ติดตั้งสำเร็จ! 🎉</div>
        <div class="install-success">
          ระบบถูกติดตั้งเรียบร้อยแล้ว<br><br>
          <strong>บัญชีผู้ดูแลระบบเริ่มต้น:</strong><br>
          ADMIN / รหัสผ่าน password<br><br>
          กรุณาเข้าสู่ระบบและเปลี่ยนรหัสผ่าน แล้วเพิ่มผู้ใช้งาน (ครูนิเทศ/นักศึกษา) และสถานประกอบการ ผ่านเมนูผู้ดูแลระบบ
        </div>
        <div class="install-warning">⚠️ เพื่อความปลอดภัย กรุณาลบไฟล์ <code>install.php</code> ออกจากเซิร์ฟเวอร์หลังติดตั้งเสร็จ</div>
        <a href="/ias/index.php" class="btn-link">ไปที่หน้าเข้าสู่ระบบ →</a>
      <?php else: ?>
        <div class="install-title">ตั้งค่าการเชื่อมต่อฐานข้อมูล</div>
        <div class="install-subtitle">Database Connection Setup</div>

        <?php foreach ($errors as $e): ?>
          <div class="install-error">⚠️ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <?php if ($alreadyInstalled && !$errors): ?>
          <div class="install-warning">⚠️ ตรวจพบว่าระบบติดตั้งไว้แล้ว การติดตั้งซ้ำจะ<strong>ลบข้อมูลทั้งหมด</strong>ในฐานข้อมูลเดิม</div>
        <?php endif; ?>

        <form method="post">
          <div class="form-group">
            <label>Database Host</label>
            <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? $existingHost ?: 'localhost') ?>" required>
          </div>
          <div class="form-group">
            <label>ชื่อฐานข้อมูล (Database Name)</label>
            <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? $existingName ?: 'ovec_attendance') ?>" required>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? $existingUser ?: 'root') ?>" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="db_pass" value="">
          </div>

          <label class="checkbox-row">
            <input type="checkbox" name="force_reinstall" value="1" <?= isset($_POST['force_reinstall']) ? 'checked' : '' ?>>
            ติดตั้งใหม่ทับของเดิม (ลบฐานข้อมูลเดิมทั้งหมดและสร้างใหม่)
          </label>

          <button type="submit" class="btn-login">ติดตั้งระบบ / Install</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
