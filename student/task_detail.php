<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$user = current_user();

$taskId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, u.name trainer_name FROM tasks t JOIN users u ON u.id = t.trainer_id WHERE t.id = ? AND t.student_id = ?");
$stmt->execute([$taskId, $user['id']]);
$task = $stmt->fetch();
if (!$task) { header('Location: /ias/student/tasks.php'); exit; }

// Mark as viewed
if (!$task['viewed_at']) {
    $pdo->prepare("UPDATE tasks SET viewed_at = NOW() WHERE id = ?")->execute([$taskId]);
    $task['viewed_at'] = date('Y-m-d H:i:s');
}

// Task attachments
$stmt = $pdo->prepare('SELECT * FROM task_attachments WHERE task_id = ? ORDER BY id');
$stmt->execute([$taskId]);
$taskAtts = $stmt->fetchAll();

// Thread
$stmt = $pdo->prepare("SELECT tt.*, u.name author_name, u.role author_role FROM task_threads tt JOIN users u ON u.id = tt.author_id WHERE tt.task_id = ? ORDER BY tt.created_at ASC");
$stmt->execute([$taskId]);
$threads = $stmt->fetchAll();

$threadIds = array_column($threads, 'id');
$threadAtts = [];
if ($threadIds) {
    $ph = implode(',', array_fill(0, count($threadIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM task_thread_attachments WHERE thread_id IN ($ph) ORDER BY id");
    $stmt->execute($threadIds);
    foreach ($stmt->fetchAll() as $a) $threadAtts[$a['thread_id']][] = $a;
}

$si = task_status_info($task['status']);
$uploadBase = '/ias/uploads/tasks/' . $taskId . '/';

$activeSection = 'tasks';
$pageTitle = htmlspecialchars($task['title']);
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.task-header-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);padding:20px 22px;margin-bottom:16px;}
.thread-wrap{display:flex;flex-direction:column;gap:12px;margin-bottom:20px;}
.thread-bubble{max-width:85%;padding:13px 16px;border-radius:14px;}
.thread-bubble.submission{background:#EFF6FF;border:1.5px solid #BFDBFE;align-self:flex-end;border-bottom-right-radius:4px;}
.thread-bubble.comment{background:#F0FDF4;border:1.5px solid #BBF7D0;align-self:flex-start;border-bottom-left-radius:4px;}
.thread-meta{font-size:11.5px;color:#94A3B8;margin-bottom:6px;}
.thread-content{font-size:14px;color:#1E293B;white-space:pre-wrap;word-break:break-word;}
.thread-atts{margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;}
.att-chip{display:inline-flex;align-items:center;gap:5px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:4px 10px;font-size:12.5px;color:#374151;text-decoration:none;}
.att-chip:hover{background:#EFF6FF;}
.submit-box{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);padding:20px;}
.submit-box textarea{width:100%;min-height:100px;padding:10px 13px;border:1.5px solid #CBD5E1;border-radius:10px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box;}
</style>

<div style="margin-bottom:10px;"><a href="/ias/student/tasks.php" style="color:#64748B;font-size:13px;">← กลับ</a></div>

<div class="task-header-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <div>
      <div style="font-size:18px;font-weight:800;color:#1A237E;margin-bottom:4px;"><?= htmlspecialchars($task['title']) ?></div>
      <div style="font-size:13px;color:#64748B;">
        🧑‍💼 ครูฝึก: <?= htmlspecialchars($task['trainer_name']) ?> · 🎯 <?= (int)$task['score'] ?> คะแนน · 📅 มอบหมาย: <?= substr($task['created_at'],0,10) ?>
        <?php if ($task['due_date']): $overdue = $task['status']==='active' && $task['due_date'] < date('Y-m-d H:i:s'); ?>
        · <span style="font-weight:700;color:<?= $overdue ? '#DC2626' : '#D97706' ?>;"><?= $overdue ? '⚠️ เกินกำหนดแล้ว!' : '⏰ กำหนดส่ง:' ?> <?= substr($task['due_date'],0,16) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;font-size:13px;padding:6px 14px;"><?= $si['icon'] ?> <?= $si['label'] ?></span>
  </div>
  <?php if ($task['description']): ?>
  <div style="margin-top:12px;font-size:14px;color:#374151;white-space:pre-wrap;background:#F8FAFC;border-radius:10px;padding:12px;"><?= htmlspecialchars($task['description']) ?></div>
  <?php endif; ?>
  <?php if ($taskAtts): ?>
  <div style="margin-top:12px;">
    <div style="font-size:12px;font-weight:700;color:#64748B;margin-bottom:6px;">📎 ไฟล์/ลิงก์จากครูฝึก</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
      <?php foreach ($taskAtts as $a): ?>
        <?php if ($a['att_type'] === 'link'): ?>
          <a href="<?= htmlspecialchars($a['link_url']) ?>" target="_blank" class="att-chip">🔗 <?= htmlspecialchars($a['link_url']) ?></a>
        <?php else: ?>
          <a href="<?= $uploadBase . htmlspecialchars($a['stored_name']) ?>" target="_blank" class="att-chip"><?= attachment_icon($a['mime_type'] ?? '', 'file') ?> <?= htmlspecialchars($a['original_name']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($task['status'] === 'terminated' && $task['close_note']): ?>
  <div style="margin-top:10px;background:#FEF2F2;border-radius:10px;padding:10px 14px;font-size:13px;color:#DC2626;"><strong>สิ้นสุดโดยไม่เสร็จสิ้น:</strong> <?= htmlspecialchars($task['close_note']) ?></div>
  <?php endif; ?>
</div>

<div style="font-size:14px;font-weight:700;color:#1A237E;margin-bottom:10px;">💬 การสนทนา</div>
<div class="thread-wrap">
<?php if (!$threads): ?>
  <div style="text-align:center;color:#94A3B8;padding:20px;background:#F8FAFC;border-radius:12px;">ยังไม่มีการส่งงานหรือความเห็น — เริ่มส่งงานด้านล่าง</div>
<?php endif; ?>
<?php foreach ($threads as $th): $atts = $threadAtts[$th['id']] ?? []; ?>
  <div class="thread-bubble <?= $th['entry_type'] ?>">
    <div class="thread-meta"><?= $th['entry_type'] === 'submission' ? '📤 คุณส่งงาน' : '💬 ความเห็นจากครูฝึก: ' . htmlspecialchars($th['author_name']) ?> · <?= $th['created_at'] ?></div>
    <?php if ($th['content']): ?><div class="thread-content"><?= htmlspecialchars($th['content']) ?></div><?php endif; ?>
    <?php if ($atts): ?>
    <div class="thread-atts">
      <?php foreach ($atts as $a): ?>
        <?php if ($a['att_type'] === 'link'): ?>
          <a href="<?= htmlspecialchars($a['link_url']) ?>" target="_blank" class="att-chip">🔗 <?= htmlspecialchars($a['link_url']) ?></a>
        <?php else: ?>
          <a href="<?= $uploadBase . htmlspecialchars($a['stored_name']) ?>" target="_blank" class="att-chip"><?= attachment_icon($a['mime_type'] ?? '', 'file') ?> <?= htmlspecialchars($a['original_name']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<?php if ($task['status'] === 'active'): ?>
<div class="submit-box">
  <div style="font-size:14px;font-weight:700;color:#1A237E;margin-bottom:12px;">📤 ส่งงาน</div>
  <form method="post" action="/ias/ajax/task_submit.php" enctype="multipart/form-data">
    <input type="hidden" name="task_id" value="<?= $taskId ?>">
    <textarea name="content" placeholder="อธิบายงานที่ทำ หรือรายงานความคืบหน้า..." style="margin-bottom:10px;"></textarea>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">📎 แนบไฟล์</label>
        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">🔗 ลิงก์</label>
        <input type="text" name="links[]" placeholder="https://..." style="width:100%;padding:7px 10px;border:1.5px solid #CBD5E1;border-radius:8px;font-size:13px;box-sizing:border-box;">
      </div>
    </div>
    <button type="submit" class="btn-primary" style="width:100%;padding:12px;">📤 ส่งงาน</button>
  </form>
</div>
<?php elseif ($task['status'] === 'completed'): ?>
<div style="background:#F0FDF4;border:1.5px solid #BBF7D0;border-radius:12px;padding:16px;text-align:center;font-size:15px;font-weight:700;color:#16A34A;">✅ งานนี้เสร็จสิ้นแล้ว</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
