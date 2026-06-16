    </main>
  </div>

  <nav class="app-bottom-nav mobile-only no-print">
    <?php foreach ($navItems as $item): ?>
      <a href="<?= $item['href'] ?>" class="bottom-nav-btn <?= $activeSection === $item['key'] ? 'active' : '' ?>">
        <span class="bottom-nav-icon"><?= $item['icon'] ?></span>
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
