<?php
require_once __DIR__ . '/../includes/auth.php';

// Only real admin (not already impersonating another admin) can impersonate
$realUser = $_SESSION['original_admin'] ?? current_user();
if (!$realUser || $realUser['role'] !== 'admin') {
    header('Location: /ias/index.php');
    exit;
}

$targetId = $_POST['user_id'] ?? '';
if (!$targetId) {
    header('Location: /ias/admin/users.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$target = $stmt->fetch();

if (!$target) {
    $_SESSION['notif'] = ['msg' => 'ไม่พบผู้ใช้งาน', 'type' => 'error'];
    header('Location: /ias/admin/users.php');
    exit;
}

if ($target['id'] === $realUser['id']) {
    $_SESSION['notif'] = ['msg' => 'ไม่สามารถสวมสิทธิ์ตัวเองได้', 'type' => 'error'];
    header('Location: /ias/admin/users.php');
    exit;
}

// Save original admin session (only if not already impersonating)
if (!isset($_SESSION['original_admin'])) {
    $_SESSION['original_admin'] = $_SESSION['user'];
}
$_SESSION['user'] = $target;
$_SESSION['notif'] = ['msg' => '🎭 กำลังสวมสิทธิ์เป็น ' . $target['name'] . ' (' . $target['role'] . ')', 'type' => 'info'];

// Redirect to appropriate home page
$redirectMap = [
    'student' => '/ias/student/dashboard.php',
    'teacher' => '/ias/teacher/dashboard.php',
    'admin'   => '/ias/admin/users.php',
];
header('Location: ' . ($redirectMap[$target['role']] ?? '/ias/index.php'));
exit;
