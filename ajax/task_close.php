<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('trainer');
$user = current_user();

$taskId = (int)($_POST['task_id'] ?? 0);
$action = $_POST['action'] ?? '';
$closeNote = trim($_POST['close_note'] ?? '') ?: null;

if (!in_array($action, ['completed', 'terminated'], true)) {
    header('Location: /ias/trainer/tasks.php');
    exit;
}
if ($action === 'terminated' && !$closeNote) {
    $_SESSION['notif'] = ['msg' => 'กรุณาระบุสาเหตุที่สิ้นสุดงานโดยไม่เสร็จสิ้น', 'type' => 'error'];
    header('Location: /ias/trainer/task_detail.php?id=' . $taskId);
    exit;
}

$stmt = $pdo->prepare("UPDATE tasks SET status = ?, close_note = ?, closed_at = NOW() WHERE id = ? AND trainer_id = ? AND status = 'active'");
$stmt->execute([$action, $closeNote, $taskId, $user['id']]);

$msg = $action === 'completed' ? '✅ ทำเครื่องหมายงานเสร็จสิ้นแล้ว' : '🔴 สิ้นสุดงานแล้ว';
$_SESSION['notif'] = ['msg' => $msg, 'type' => 'success'];
header('Location: /ias/trainer/task_detail.php?id=' . $taskId);
exit;
