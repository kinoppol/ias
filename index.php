<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/logo.php';

if (current_user()) {
    header('Location: /ias/' . current_user()['role'] . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = strtoupper(trim($_POST['loginId'] ?? ''));
    $pass = $_POST['loginPass'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND password = ?');
    $stmt->execute([$id, $pass]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
        header('Location: /ias/' . $user['role'] . '/dashboard.php');
        exit;
    } else {
        $error = 'รหัสผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ — ระบบบันทึกการเข้างานฝึกงาน</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/ias/assets/css/style.css">
</head>
<body class="login-body">
  <div class="login-wrap">
    <div class="login-banner">
      <div class="login-logo"><?= ovec_logo_full(84) ?></div>
      <div class="login-banner-title">สำนักงานคณะกรรมการการอาชีวศึกษา</div>
      <div class="login-banner-sub">OFFICE OF THE VOCATIONAL EDUCATION COMMISSION</div>
    </div>

    <div class="login-card">
      <div class="login-title">ระบบบันทึกการเข้างานฝึกงาน</div>
      <div class="login-subtitle">Internship Attendance Management System</div>

      <form method="post">
        <div class="form-group">
          <label>รหัสผู้ใช้งาน / User ID</label>
          <input type="text" name="loginId" placeholder="รหัสผู้ใช้งาน" autocomplete="username" required>
        </div>
        <div class="form-group">
          <label>รหัสผ่าน / Password</label>
          <input type="password" name="loginPass" placeholder="รหัสผ่าน" autocomplete="current-password" required>
        </div>

        <?php if ($error): ?>
          <div class="login-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <button type="submit" class="btn-login">เข้าสู่ระบบ / Login</button>
      </form>
    </div>
  </div>
</body>
</html>
