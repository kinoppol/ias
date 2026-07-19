<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('trainer');
$user = current_user();

$taskId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, u.name student_name, u.dept student_dept FROM tasks t JOIN users u ON u.id = t.student_id WHERE t.id = ? AND t.trainer_id = ?");
$stmt->execute([$taskId, $user['id']]);
$task = $stmt->fetch();
if (!$task) { header('Location: /ias/trainer/tasks.php'); exit; }

// Task attachments
$stmt = $pdo->prepare('SELECT * FROM task_attachments WHERE task_id = ? ORDER BY id');
$stmt->execute([$taskId]);
$taskAtts = $stmt->fetchAll();

// Thread
$stmt = $pdo->prepare("SELECT tt.*, u.name author_name, u.role author_role FROM task_threads tt JOIN users u ON u.id = tt.author_id WHERE tt.task_id = ? ORDER BY tt.created_at ASC");
$stmt->execute([$taskId]);
$threads = $stmt->fetchAll();

// Thread attachments map
$threadIds = array_column($threads, 'id');
$threadAtts = [];
if ($threadIds) {
    $placeholders = implode(',', array_fill(0, count($threadIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM task_thread_attachments WHERE thread_id IN ($placeholders) ORDER BY id");
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
.task-header-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07); padding:20px 22px; margin-bottom:16px; }
.thread-wrap { display:flex; flex-direction:column; gap:12px; margin-bottom:20px; }
.thread-bubble { max-width:85%; padding:13px 16px; border-radius:14px; position:relative; }
.thread-bubble.submission { background:#EFF6FF; border:1.5px solid #BFDBFE; align-self:flex-start; border-bottom-left-radius:4px; }
.thread-bubble.comment { background:#F0FDF4; border:1.5px solid #BBF7D0; align-self:flex-end; border-bottom-right-radius:4px; }
.thread-meta { font-size:11.5px; color:#94A3B8; margin-bottom:6px; }
.thread-content { font-size:14px; color:#1E293B; white-space:pre-wrap; word-break:break-word; }
.thread-atts { margin-top:8px; display:flex; flex-wrap:wrap; gap:6px; }
.att-chip { display:inline-flex; align-items:center; gap:5px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:4px 10px; font-size:12.5px; color:#374151; text-decoration:none; }
.att-chip:hover { background:#EFF6FF; }
.comment-box { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07); padding:20px; }
.comment-box textarea { width:100%; min-height:100px; padding:10px 13px; border:1.5px solid #CBD5E1; border-radius:10px; font-size:14px; font-family:inherit; resize:vertical; box-sizing:border-box; }
.close-form { background:#FFF5F5; border:1.5px solid #FECACA; border-radius:12px; padding:16px; margin-top:12px; }
</style>

<div style="margin-bottom:10px;"><a href="/ias/trainer/tasks.php" style="color:#64748B;font-size:13px;">← กลับ</a></div>

<div class="task-header-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <div>
      <div style="font-size:18px;font-weight:800;color:#1A237E;margin-bottom:4px;"><?= htmlspecialchars($task['title']) ?></div>
      <div style="font-size:13px;color:#64748B;">
        👤 <?= htmlspecialchars($task['student_name']) ?><?= $task['student_dept'] ? ' · ' . htmlspecialchars($task['student_dept']) : '' ?> · 🎯 <?= (int)$task['score'] ?> คะแนน · 📅 มอบหมาย: <?= substr($task['created_at'],0,10) ?>
        <?php if ($task['due_date']): $overdue = $task['status']==='active' && $task['due_date'] < date('Y-m-d H:i:s'); ?>
        · <span style="font-weight:700;color:<?= $overdue ? '#DC2626' : '#D97706' ?>;"><?= $overdue ? '⚠️ เกินกำหนด!' : '⏰' ?> กำหนดส่ง: <?= substr($task['due_date'],0,16) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <span class="badge" style="color:<?= $si['color'] ?>;background:<?= $si['bg'] ?>;font-size:13px;padding:6px 14px;"><?= $si['icon'] ?> <?= $si['label'] ?></span>
  </div>
  <?php if ($task['description']): ?>
  <div style="margin-top:12px;font-size:14px;color:#374151;white-space:pre-wrap;background:#F8FAFC;border-radius:10px;padding:12px;"><?= htmlspecialchars($task['description']) ?></div>
  <?php endif; ?>
  <?php if ($task['viewed_at']): ?>
  <div style="margin-top:8px;font-size:12px;color:#16A34A;">👁 นักศึกษาเปิดดูงานแล้ว: <?= $task['viewed_at'] ?></div>
  <?php else: ?>
  <div style="margin-top:8px;font-size:12px;color:#D97706;">⏳ นักศึกษายังไม่ได้เปิดดูงาน</div>
  <?php endif; ?>
  <?php if ($taskAtts): ?>
  <div style="margin-top:12px;">
    <div style="font-size:12px;font-weight:700;color:#64748B;margin-bottom:6px;">📎 ไฟล์/ลิงก์ที่แนบมากับงาน</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
      <?php foreach ($taskAtts as $a): ?>
        <?php if ($a['att_type'] === 'link'): ?>
          <a href="<?= htmlspecialchars($a['link_url']) ?>" target="_blank" class="att-chip">🔗 <?= htmlspecialchars(parse_url($a['link_url'], PHP_URL_HOST) ?: $a['link_url']) ?></a>
        <?php else: ?>
          <a href="<?= $uploadBase . htmlspecialchars($a['stored_name']) ?>" target="_blank" class="att-chip"><?= attachment_icon($a['mime_type'] ?? '', 'file') ?> <?= htmlspecialchars($a['original_name']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Thread -->
<div style="font-size:14px;font-weight:700;color:#1A237E;margin-bottom:10px;">💬 การสนทนา / ประวัติการส่งงาน</div>
<div class="thread-wrap">
<?php if (!$threads): ?>
  <div style="text-align:center;color:#94A3B8;padding:20px;background:#F8FAFC;border-radius:12px;">ยังไม่มีการส่งงานหรือความเห็น</div>
<?php endif; ?>
<?php foreach ($threads as $th): $atts = $threadAtts[$th['id']] ?? []; ?>
  <div class="thread-bubble <?= $th['entry_type'] ?>">
    <div class="thread-meta"><?= $th['entry_type'] === 'submission' ? '📤 ส่งงาน' : '💬 ความเห็นครูฝึก' ?> — <?= htmlspecialchars($th['author_name']) ?> · <?= $th['created_at'] ?></div>
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
<!-- Trainer comment box -->
<div class="comment-box">
  <div style="font-size:14px;font-weight:700;color:#1A237E;margin-bottom:12px;">💬 ให้ความเห็น / ตอบกลับ</div>
  <form method="post" action="/ias/ajax/task_comment.php" enctype="multipart/form-data">
    <input type="hidden" name="task_id" value="<?= $taskId ?>">
    <textarea name="content" placeholder="พิมพ์ความเห็น คำแนะนำ หรือคำถาม..." style="margin-bottom:10px;"></textarea>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">📎 แนบไฟล์</label>
        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">🔗 ลิงก์</label>
        <input type="text" name="links[]" placeholder="https://..." style="width:100%;padding:7px 10px;border:1.5px solid #CBD5E1;border-radius:8px;font-size:13px;box-sizing:border-box;">
      </div>
    </div>
    <button type="submit" class="btn-submit">📨 ส่งความเห็น</button>
  </form>
</div>

<!-- Close task -->
<div class="close-form">
  <div style="font-size:14px;font-weight:700;color:#1A237E;margin-bottom:12px;">🔒 สิ้นสุดงาน</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
    <form method="post" action="/ias/ajax/task_close.php">
      <input type="hidden" name="task_id" value="<?= $taskId ?>">
      <input type="hidden" name="action" value="completed">
      <button type="submit" class="btn-submit" style="width:100%;background:#16A34A;" onclick="return confirm('ทำเครื่องหมายว่างานเสร็จสิ้น?')">✅ งานเสร็จสิ้น</button>
    </form>
    <form method="post" action="/ias/ajax/task_close.php" id="terminateForm">
      <input type="hidden" name="task_id" value="<?= $taskId ?>">
      <input type="hidden" name="action" value="terminated">
      <textarea name="close_note" placeholder="ระบุสาเหตุที่สิ้นสุดโดยไม่เสร็จสิ้น *" style="width:100%;padding:8px;border:1.5px solid #FECACA;border-radius:8px;font-size:13px;min-height:60px;margin-bottom:6px;box-sizing:border-box;font-family:inherit;"></textarea>
      <button type="submit" class="btn-delete" style="width:100%;" onclick="return confirm('ยืนยันสิ้นสุดงานโดยไม่เสร็จสิ้น?')">🔴 สิ้นสุด (ไม่เสร็จ)</button>
    </form>
  </div>
</div>
<?php elseif ($task['close_note']): ?>
<div style="background:#FFF5F5;border:1.5px solid #FECACA;border-radius:12px;padding:14px;margin-top:12px;">
  <div style="font-size:13px;font-weight:700;color:#DC2626;margin-bottom:4px;">🔴 สิ้นสุดโดยไม่เสร็จสิ้น</div>
  <div style="font-size:13.5px;color:#374151;"><?= htmlspecialchars($task['close_note']) ?></div>
  <?php if ($task['closed_at']): ?><div style="font-size:12px;color:#94A3B8;margin-top:4px;"><?= $task['closed_at'] ?></div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
