<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$originalId = trim($_POST['original_id'] ?? '');
$id = strtoupper(trim($_POST['id'] ?? ''));
$name = trim($_POST['name'] ?? '');
$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';
$grade = trim($_POST['grade'] ?? '') ?: null;
$dept = trim($_POST['dept'] ?? '') ?: null;
$workplaceId = trim($_POST['workplace_id'] ?? '') ?: null;

if (!in_array($role, ['student', 'teacher', 'admin'], true)) {
    $_SESSION['notif'] = ['msg' => 'บทบาทไม่ถูกต้อง', 'type' => 'error'];
    header('Location: /ias/admin/users.php');
    exit;
}
if ($role !== 'student') {
    $grade = null;
    $workplaceId = null;
}

if ($id === '' || $name === '') {
    $_SESSION['notif'] = ['msg' => 'กรุณากรอกรหัสผู้ใช้และชื่อ-สกุล', 'type' => 'error'];
    header('Location: /ias/admin/users.php');
    exit;
}

if ($originalId) {
    // Edit existing — id (primary key) is not changed
    if ($password !== '') {
        $stmt = $pdo->prepare('UPDATE users SET name=?, role=?, password=?, grade=?, dept=?, workplace_id=? WHERE id=?');
        $stmt->execute([$name, $role, $password, $grade, $dept, $workplaceId, $originalId]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET name=?, role=?, grade=?, dept=?, workplace_id=? WHERE id=?');
        $stmt->execute([$name, $role, $grade, $dept, $workplaceId, $originalId]);
    }
    $_SESSION['notif'] = ['msg' => '💾 บันทึกข้อมูลผู้ใช้แล้ว', 'type' => 'success'];
} else {
    if ($password === '') {
        $_SESSION['notif'] = ['msg' => 'กรุณากำหนดรหัสผ่านสำหรับผู้ใช้ใหม่', 'type' => 'error'];
        header('Location: /ias/admin/users.php');
        exit;
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        $_SESSION['notif'] = ['msg' => "รหัสผู้ใช้ $id มีอยู่แล้ว", 'type' => 'error'];
        header('Location: /ias/admin/users.php');
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO users (id, name, role, password, grade, dept, workplace_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $name, $role, $password, $grade, $dept, $workplaceId]);
    $_SESSION['notif'] = ['msg' => '✅ เพิ่มผู้ใช้แล้ว', 'type' => 'success'];
}

header('Location: /ias/admin/users.php');
exit;
