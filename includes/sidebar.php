<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$currentDir  = basename(dirname($_SERVER['SCRIPT_NAME']));

function navActive(string $match, string $currentPage, string $currentDir = ''): string
{
    return ($currentPage === $match || $currentDir === $match) ? 'active' : '';
}
?>
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-section">
    <a href="<?= BASE_URL ?>/dashboard.php" class="sidebar-link <?= navActive('dashboard.php', $currentPage) ?>">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
  </div>

  <div class="sidebar-section">
    <p class="sidebar-heading">Simulasi</p>
    <a href="<?= BASE_URL ?>/simulator/new.php" class="sidebar-link <?= navActive('new.php', $currentPage, $currentDir) ?>">
      <i class="fa-solid fa-plus"></i> Simulasi Baru
    </a>
    <a href="<?= BASE_URL ?>/simulator/history.php" class="sidebar-link <?= navActive('history.php', $currentPage, $currentDir) ?>">
      <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Keputusan
    </a>
  </div>

  <div class="sidebar-section">
    <p class="sidebar-heading">Kalkulator</p>
    <a href="<?= BASE_URL ?>/calculators/affordability.php" class="sidebar-link <?= navActive('affordability.php', $currentPage, $currentDir) ?>">
      <i class="fa-solid fa-cart-shopping"></i> Cek Kemampuan Beli
    </a>
    <a href="<?= BASE_URL ?>/calculators/emergency-fund.php" class="sidebar-link <?= navActive('emergency-fund.php', $currentPage, $currentDir) ?>">
      <i class="fa-solid fa-shield-heart"></i> Dana Darurat
    </a>
  </div>

  <div class="sidebar-section">
    <p class="sidebar-heading">Keuangan</p>
    <a href="<?= BASE_URL ?>/finance/index.php" class="sidebar-link <?= ($currentDir === 'finance') ? 'active' : '' ?>">
      <i class="fa-solid fa-wallet"></i> Keuangan Saya
    </a>
  </div>

  <div class="sidebar-section">
    <p class="sidebar-heading">Akun</p>
    <a href="<?= BASE_URL ?>/profile/index.php" class="sidebar-link <?= ($currentDir === 'profile') ? 'active' : '' ?>">
      <i class="fa-solid fa-user"></i> Profil
    </a>
  </div>
</aside>
