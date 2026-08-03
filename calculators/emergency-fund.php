<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$stmt = $pdo->prepare('SELECT * FROM financial_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);
$fp = $stmt->fetch() ?: ['monthly_expenses' => 0, 'emergency_fund' => 0];

$targetMonths = (float) getSetting('emergency_fund_target_months');
$currentFund = (float) $fp['emergency_fund'];
$mandatoryExpenses = (float) $fp['monthly_expenses'];
$coverage = calculateEmergencyCoverage($currentFund, $mandatoryExpenses);
$targetAmount = $mandatoryExpenses * $targetMonths;
$shortfall = max(0, $targetAmount - $currentFund);

$monthsToTarget = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $monthlyContribution = parseRupiah($_POST['monthly_contribution'] ?? '0');
    if ($monthlyContribution > 0 && $shortfall > 0) {
        $monthsToTarget = ceil($shortfall / $monthlyContribution);
    }
}

$pageTitle = 'Dana Darurat';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="app-main" style="max-width:640px;">
    <h2>Kalkulator Dana Darurat</h2>
    <p>Berdasarkan pengeluaran wajib bulanan dan dana darurat yang tercatat di profilmu.</p>

    <div class="card mt-16">
      <div class="card-grid">
        <div><p class="text-muted mb-0">Pengeluaran Wajib</p><p style="font-weight:700; font-size:18px;"><?= formatRupiah($mandatoryExpenses) ?>/bulan</p></div>
        <div><p class="text-muted mb-0">Dana Darurat Saat Ini</p><p style="font-weight:700; font-size:18px;"><?= formatRupiah($currentFund) ?></p></div>
        <div><p class="text-muted mb-0">Coverage</p><p style="font-weight:700; font-size:18px;"><?= formatMonths($coverage) ?></p></div>
        <div><p class="text-muted mb-0">Target</p><p style="font-weight:700; font-size:18px;"><?= formatMonths($targetMonths) ?></p></div>
      </div>

      <div class="mt-24">
        <div class="progress-bar"><div class="progress-bar-fill" style="width:<?= min(100, $targetAmount > 0 ? ($currentFund / $targetAmount) * 100 : 0) ?>%;"></div></div>
        <p class="mt-16">Kekurangan menuju target: <strong><?= formatRupiah($shortfall) ?></strong></p>
      </div>

      <form method="post" class="mt-16">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Kontribusi Bulanan yang Kamu Rencanakan</label>
          <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_contribution" placeholder="500.000"></div>
          <input type="hidden" name="monthly_contribution" id="monthly_contribution">
        </div>
        <button type="submit" class="btn btn-primary">Hitung Estimasi Waktu</button>
      </form>

      <?php if ($monthsToTarget !== null): ?>
        <p class="mt-16">Estimasi waktu mencapai target: <strong><?= formatMonths($monthsToTarget) ?></strong> (sekitar <?= e(date('F Y', strtotime('+' . (int) $monthsToTarget . ' months'))) ?>).</p>
      <?php elseif ($shortfall <= 0): ?>
        <p class="mt-16" style="color:var(--color-success); font-weight:600;">✓ Dana daruratmu sudah mencapai target!</p>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
