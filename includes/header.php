<?php
// Expects: $activeSection (string key), $pageTitle (optional)
require_once __DIR__ . '/logo.php';
$user = current_user();
$role = $user['role'];

$navByRole = [
    'student' => [
        ['key' => 'dashboard', 'icon' => '🏠', 'label' => 'เช็คอิน/ออก', 'href' => '/ias/student/dashboard.php'],
        ['key' => 'history',   'icon' => '📋', 'label' => 'ประวัติ',     'href' => '/ias/student/history.php'],
        ['key' => 'tasks',     'icon' => '📌', 'label' => 'งาน',         'href' => '/ias/student/tasks.php'],
        ['key' => 'leave',     'icon' => '📝', 'label' => 'การลา',      'href' => '/ias/student/leave.php'],
    ],
    'teacher' => [
        ['key' => 'dashboard', 'icon' => '📊', 'label' => 'ภาพรวม',     'href' => '/ias/teacher/dashboard.php'],
        ['key' => 'students',  'icon' => '👥', 'label' => 'นักศึกษา',    'href' => '/ias/teacher/students.php'],
        ['key' => 'schedule',  'icon' => '⏰', 'label' => 'ตารางเวลา',  'href' => '/ias/teacher/schedule.php'],
        ['key' => 'reports',   'icon' => '📈', 'label' => 'รายงาน',     'href' => '/ias/teacher/reports.php'],
        ['key' => 'leaves',      'icon' => '🗓', 'label' => 'การลา',       'href' => '/ias/teacher/leaves.php'],
        ['key' => 'task_report', 'icon' => '📌', 'label' => 'ติดตามงาน',  'href' => '/ias/teacher/task_report.php'],
    ],
    'trainer' => [
        ['key' => 'dashboard', 'icon' => '📊', 'label' => 'ภาพรวม',         'href' => '/ias/trainer/dashboard.php'],
        ['key' => 'tasks',     'icon' => '📋', 'label' => 'งานที่มอบหมาย',   'href' => '/ias/trainer/tasks.php'],
        ['key' => 'students',  'icon' => '👥', 'label' => 'นักศึกษา',        'href' => '/ias/trainer/students.php'],
    ],
    'admin' => [
        ['key' => 'users',      'icon' => '👤',  'label' => 'ผู้ใช้งาน',      'href' => '/ias/admin/users.php'],
        ['key' => 'workplaces', 'icon' => '🏢',  'label' => 'สถานประกอบการ', 'href' => '/ias/admin/workplaces.php'],
        ['key' => 'holidays',   'icon' => '🗓',  'label' => 'วันหยุด',        'href' => '/ias/admin/holidays.php'],
        ['key' => 'settings',   'icon' => '⚙️',  'label' => 'ตั้งค่า',        'href' => '/ias/admin/settings.php'],
        ['key' => 'migrate',    'icon' => '🔧',  'label' => 'Migration',      'href' => '/ias/admin/migrate.php'],
    ],
];
$navItems = $navByRole[$role] ?? [];

// Count unread tasks for student nav badge (may already be set by page)
if (!isset($_navUnreadTasks)) {
    $_navUnreadTasks = 0;
    if ($role === 'student') {
        try {
            $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE student_id = ? AND status = 'active' AND viewed_at IS NULL");
            $s->execute([$user['id']]);
            $_navUnreadTasks = (int)$s->fetchColumn();
        } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'ระบบบันทึกการเข้างานฝึกงาน') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/ias/assets/css/style.css">
</head>
<body>
<div class="app-shell">
<?php if (isset($_SESSION['original_admin'])): ?>
<div class="impersonate-bar no-print">
  <span>🎭 กำลังสวมสิทธิ์เป็น <strong><?= htmlspecialchars($user['name']) ?></strong> (<?= htmlspecialchars(role_label($role)) ?>) — Admin: <?= htmlspecialchars($_SESSION['original_admin']['name']) ?></span>
  <form method="post" action="/ias/ajax/stop_impersonate.php" style="display:inline;margin:0;">
    <button type="submit" class="impersonate-exit-btn">⬅ คืนสิทธิ์ Admin</button>
  </form>
</div>
<?php endif; ?>
  <header class="app-header no-print">
    <div class="app-header-left">
      <?= ovec_logo(32, 13) ?>
      <div class="app-header-titles">
        <div class="app-title">ระบบบันทึกการเข้างานฝึกงาน</div>
        <div class="app-clock desktop-only" id="headerClock"></div>
      </div>
    </div>
    <div class="app-header-right">
      <div class="app-user-info desktop-only">
        <div class="app-user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="app-user-role"><?= htmlspecialchars(role_label($role)) ?></div>
      </div>
      <div class="app-user-avatar"><?= role_avatar($role) ?></div>
      <a href="/ias/logout.php" class="btn-logout">ออกจากระบบ</a>
    </div>
  </header>

  <div class="app-body">
    <nav class="app-sidebar desktop-only no-print">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['href'] ?>" class="nav-btn <?= $activeSection === $item['key'] ? 'active' : '' ?>" style="justify-content:space-between;">
          <span style="display:flex;align-items:center;gap:9px;">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <span><?= htmlspecialchars($item['label']) ?></span>
          </span>
          <?php if ($item['key'] === 'tasks' && $_navUnreadTasks > 0): ?>
            <span style="background:#DC2626;color:#fff;border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;line-height:1.7;min-width:18px;text-align:center;"><?= $_navUnreadTasks ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
      <div class="sidebar-clock">
        <div class="sidebar-clock-time" id="sidebarClockTime"></div>
        <div class="sidebar-clock-date" id="sidebarClockDate"></div>
      </div>
    </nav>

    <main class="app-main" id="mc">
