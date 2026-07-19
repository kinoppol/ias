<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('trainer');
$user = current_user();

$title = trim($_POST['title'] ?? '');
$studentId = trim($_POST['student_id'] ?? '');
$score = max(1, min(100, (int)($_POST['score'] ?? 10)));
$description = trim($_POST['description'] ?? '') ?: null;

if (!$title || !$studentId) {
    $_SESSION['notif'] = ['msg' => 'กรุณากรอกชื่องานและเลือกนักศึกษา', 'type' => 'error'];
    header('Location: /ias/trainer/tasks.php?new=1');
    exit;
}

// Verify student belongs to trainer's workplace
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND workplace_id = ?");
$stmt->execute([$studentId, $user['workplace_id']]);
if (!$stmt->fetch()) {
    $_SESSION['notif'] = ['msg' => 'นักศึกษาไม่อยู่ในสถานประกอบการของคุณ', 'type' => 'error'];
    header('Location: /ias/trainer/tasks.php?new=1');
    exit;
}

$stmt = $pdo->prepare("INSERT INTO tasks (title, description, score, trainer_id, student_id) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$title, $description, $score, $user['id'], $studentId]);
$taskId = (int)$pdo->lastInsertId();

// Handle attachments
$files = [];
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $i => $name) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $files[] = [
                'name' => $name,
                'type' => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error' => $_FILES['files']['error'][$i],
                'size' => $_FILES['files']['size'][$i],
            ];
        }
    }
}
$links = array_filter(array_map('trim', $_POST['links'] ?? []));
save_attachments_to_db($pdo, $taskId, null, $files, $links);

$_SESSION['notif'] = ['msg' => '✅ มอบหมายงานสำเร็จ', 'type' => 'success'];
header('Location: /ias/trainer/task_detail.php?id=' . $taskId);
exit;
