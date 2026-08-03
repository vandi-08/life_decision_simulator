<?php $loggedIn = isLoggedIn(); ?>
<nav class="navbar">
  <div class="navbar-inner">
    <a href="<?= BASE_URL ?>/<?= $loggedIn ? 'dashboard.php' : 'index.php' ?>" class="navbar-brand">
      <i class="fa-solid fa-compass"></i> Life Decision <span>Simulator</span>
    </a>

    <?php if ($loggedIn): ?>
      <div class="navbar-actions">
        <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
          <i class="fa-solid fa-moon"></i>
        </button>
        <div class="navbar-user">
          <span><?= e($_SESSION['user_name'] ?? 'Pengguna') ?></span>
          <a href="<?= BASE_URL ?>/logout.php" class="btn btn-ghost btn-sm">Keluar</a>
        </div>
      </div>
    <?php else: ?>
      <div class="navbar-actions">
        <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
          <i class="fa-solid fa-moon"></i>
        </button>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-ghost btn-sm">Masuk</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Mulai Simulasi</a>
      </div>
    <?php endif; ?>
  </div>
</nav>
