<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$holidays = $pdo->query('SELECT * FROM holidays ORDER BY date DESC')->fetchAll();

$activeSection = 'holidays';
$pageTitle = 'วันหยุด';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.holiday-form-row { display:grid; grid-template-columns:1fr 2fr auto; gap:10px; align-items:end; margin-bottom:16px; }
@media(max-width:600px){ .holiday-form-row { grid-template-columns:1fr; } }
.holiday-form-row label { font-size:12px; font-weight:600; color:#64748B; display:block; margin-bottom:4px; }
.holiday-form-row input { width:100%; padding:9px 11px; border:1.5px solid #CBD5E1; border-radius:9px; font-size:14px; font-family:inherit; box-sizing:border-box; }
</style>
<div class="flex-between">
  <div class="section-title" style="margin-bottom:0;">🗓 วันหยุด</div>
</div>

<div class="table-card" style="margin-bottom:16px;">
  <div style="font-size:14px;font-weight:700;color:#1A237E;margin-bottom:14px;">➕ เพิ่มวันหยุด</div>
  <form method="post" action="/ias/ajax/save_holiday.php">
    <div class="holiday-form-row">
      <div>
        <label>วันที่</label>
        <input type="date" name="date" required max="2099-12-31">
      </div>
      <div>
        <label>ชื่อวันหยุด (ไม่บังคับ)</label>
        <input type="text" name="name" placeholder="เช่น วันแม่แห่งชาติ, วันสถาปนา...">
      </div>
      <div>
        <button type="submit" class="btn-primary" style="white-space:nowrap;">บันทึก</button>
      </div>
    </div>
  </form>
</div>

<div class="table-card">
  <div class="table-scroll">
    <table class="data-table" style="min-width:400px;">
      <thead><tr>
        <th>วันที่</th><th>วัน</th><th>ชื่อวันหยุด</th><th class="center">ลบ</th>
      </tr></thead>
      <tbody>
      <?php
      $thDays = ['','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์','อาทิตย์'];
      foreach ($holidays as $h):
          $dow = (int)(new DateTime($h['date']))->format('N');
          $isWeekend = $dow >= 6;
      ?>
      <tr>
        <td style="font-weight:600;color:#1A237E;"><?= htmlspecialchars(fmt_date_th($h['date'])) ?></td>
        <td><span class="badge" style="color:<?= $isWeekend ? '#DC2626' : '#374151' ?>;background:<?= $isWeekend ? '#FEE2E2' : '#F1F5F9' ?>;"><?= htmlspecialchars($thDays[$dow]) ?></span></td>
        <td style="color:#374151;"><?= htmlspecialchars($h['name'] ?: '—') ?></td>
        <td class="center">
          <form method="post" action="/ias/ajax/delete_holiday.php" onsubmit="return confirm('ลบวันหยุดนี้?');" style="display:inline;">
            <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
            <button type="submit" class="btn-delete-sm">ลบ</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$holidays): ?>
        <tr><td colspan="4" style="text-align:center;color:#94A3B8;padding:20px;">ยังไม่มีข้อมูลวันหยุด</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
