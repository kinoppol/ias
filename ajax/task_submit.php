<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$user = current_user();

$taskId = (int)($_POST['task_id'] ?? 0);
$content = trim($_POST['content'] ?? '') ?: null;

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND student_id = ? AND status = 'active'");
$stmt->execute([$taskId, $user['id']]);
$task = $stmt->fetch();
if (!$task) {
    $_SESSION['notif'] = ['msg' => 'ไม่พบงานหรืองานปิดแล้ว', 'type' => 'error'];
    header('Location: /ias/student/tasks.php');
    exit;
}

// Require content or at least a file/link
$hasFile = !empty($_FILES['files']['name'][0]) && $_FILES['files']['error'][0] === UPLOAD_ERR_OK;
$hasLink = !empty(array_filter(array_map('trim', $_POST['links'] ?? [])));
if (!$content && !$hasFile && !$hasLink) {
    $_SESSION['notif'] = ['msg' => 'กรุณาเพิ่มข้อความ ไฟล์ หรือลิงก์ก่อนส่งงาน', 'type' => 'error'];
    header('Location: /ias/student/task_detail.php?id=' . $taskId);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO task_threads (task_id, author_id, entry_type, content) VALUES (?, ?, 'submission', ?)");
$stmt->execute([$taskId, $user['id'], $content]);
$threadId = (int)$pdo->lastInsertId();

$files = [];
if ($hasFile) {
    foreach ($_FILES['files']['name'] as $i => $name) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $files[] = ['name' => $name, 'type' => $_FILES['files']['type'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 'error' => UPLOAD_ERR_OK, 'size' => $_FILES['files']['size'][$i]];
        }
    }
}
$links = array_filter(array_map('trim', $_POST['links'] ?? []));
save_attachments_to_db($pdo, $taskId, $threadId, $files, $links);

$_SESSION['notif'] = ['msg' => '📤 ส่งงานสำเร็จ', 'type' => 'success'];
header('Location: /ias/student/task_detail.php?id=' . $taskId);
exit;
