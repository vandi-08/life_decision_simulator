<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$stmt = $pdo->prepare('SELECT * FROM financial_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);
$fp = $stmt->fetch() ?: ['monthly_income' => 0, 'monthly_expenses' => 0, 'savings_balance' => 0, 'emergency_fund' => 0];

$result = null;
$itemName = '';
$price = 0.0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $itemName = cleanString($_POST['item_name'] ?? 'Barang');
    $price = parseRupiah($_POST['price'] ?? '0');
    $surplus = (float) $fp['monthly_income'] - (float) $fp['monthly_expenses'];

    if ($price <= 0) {
        setFlash('error', 'Masukkan harga barang yang valid.');
    } else {
        $result = calculateAffordabilityScore(
            $price,
            (float) $fp['savings_balance'],
            (float) $fp['emergency_fund'],
            $surplus,
            (float) $fp['monthly_expenses']
        );
    }
}

$pageTitle = 'Cek Kemampuan Beli';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="app-main" style="max-width:640px;">
    <h2>Cek Kemampuan Beli (Affordability Checker)</h2>
    <p>Cek apakah pembelian ini masuk akal berdasarkan kondisi keuanganmu saat ini.</p>

    <div class="card mt-16">
      <form method="post">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Nama Barang</label>
          <input class="form-control" type="text" name="item_name" placeholder="mis. Laptop" value="<?= e($itemName) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Harga</label>
          <div class="input-rupiah"><span>Rp</span>
            <input class="form-control" type="text" data-rupiah-input data-rupiah-target="price" placeholder="15.000.000" value="<?= $price ? e(number_format($price, 0, ',', '.')) : '' ?>">
          </div>
          <input type="hidden" name="price" id="price">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Cek Kemampuan Beli</button>
      </form>
    </div>

    <?php if ($result): ?>
      <?php $band = $result['score'] >= 75 ? 'score-excellent' : ($result['score'] >= 60 ? 'score-good' : ($result['score'] >= 45 ? 'score-consider' : ($result['score'] >= 25 ? 'score-risky' : 'score-bad'))); ?>
      <div class="card mt-16" style="text-align:center;">
        <p class="text-muted mb-0">Affordability Score</p>
        <div class="score-ring-value"><?= e(round($result['score'])) ?>/100</div>
        <span class="score-pill <?= $band ?>"><?= e($result['label']) ?></span>
        <p class="mt-16">Pembelian <strong><?= e($itemName) ?></strong> seharga <?= formatRupiah($price) ?> akan menggunakan sekitar <strong><?= e($result['portion_of_savings']) ?>%</strong> dari tabunganmu.</p>
        <p class="text-muted">
          <?= $result['emergency_fund_intact'] ? '✓ Dana daruratmu tetap aman setelah pembelian ini.' : '⚠ Dana daruratmu akan terganggu jika membeli ini dari tabungan.' ?><br>
          Alternatif: menabung khusus untuk ini akan memakan waktu sekitar <strong><?= formatMonths($result['months_to_save_instead']) ?></strong> dari sisa uang bulananmu.
        </p>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
