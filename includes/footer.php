    </main>
  </div>

  <nav class="app-bottom-nav mobile-only no-print">
    <?php foreach ($navItems as $item): ?>
      <a href="<?= $item['href'] ?>" class="bottom-nav-btn <?= $activeSection === $item['key'] ? 'active' : '' ?>" style="position:relative;">
        <span class="bottom-nav-icon"><?= $item['icon'] ?></span>
        <?php if ($item['key'] === 'tasks' && $_navUnreadTasks > 0): ?>
          <span style="position:absolute;top:2px;right:calc(50% - 18px);background:#DC2626;color:#fff;border-radius:20px;padding:0 5px;font-size:10px;font-weight:700;line-height:1.6;min-width:16px;text-align:center;"><?= $_navUnreadTasks ?></span>
        <?php endif; ?>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</div>

<?php if (!empty($_SESSION['notif'])): $n = $_SESSION['notif']; unset($_SESSION['notif']); ?>
<div id="toast" class="toast toast-<?= htmlspecialchars($n['type']) ?> no-print"><?= htmlspecialchars($n['msg']) ?></div>
<script>setTimeout(()=>{const t=document.getElementById('toast'); if(t) t.remove();}, 4000);</script>
<?php endif; ?>

<script src="/ias/assets/js/app.js"></script>
</body>
</html>
