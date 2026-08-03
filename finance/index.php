<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $income = parseRupiah($_POST['monthly_income'] ?? '0');
    $expenses = parseRupiah($_POST['monthly_expenses'] ?? '0');
    $savings = parseRupiah($_POST['savings_balance'] ?? '0');
    $emergencyFund = parseRupiah($_POST['emergency_fund'] ?? '0');
    $totalDebt = parseRupiah($_POST['total_debt'] ?? '0');
    $monthlyDebtPayment = parseRupiah($_POST['monthly_debt_payment'] ?? '0');

    $stmt = $pdo->prepare('
        UPDATE financial_profiles
        SET monthly_income=:income, monthly_expenses=:expenses, savings_balance=:savings,
            emergency_fund=:ef, total_debt=:debt, monthly_debt_payment=:debt_payment
        WHERE user_id=:uid
    ');
    $stmt->execute([
        'income' => $income, 'expenses' => $expenses, 'savings' => $savings,
        'ef' => $emergencyFund, 'debt' => $totalDebt, 'debt_payment' => $monthlyDebtPayment, 'uid' => $userId,
    ]);

    setFlash('success', 'Profil keuangan berhasil diperbarui.');
    redirect('/finance/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM financial_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);
$fp = $stmt->fetch() ?: ['monthly_income' => 0, 'monthly_expenses' => 0, 'savings_balance' => 0, 'emergency_fund' => 0, 'total_debt' => 0, 'monthly_debt_payment' => 0];

$pageTitle = 'Keuangan Saya';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="app-main" style="max-width:640px;">
    <h2>Keuangan Saya</h2>
    <p>Data ini menjadi dasar perhitungan dashboard dan simulator.</p>

    <div class="card mt-16">
      <form method="post">
        <?= csrfField() ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Pendapatan Bulanan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_income" value="<?= e(number_format((float) $fp['monthly_income'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="monthly_income" id="monthly_income">
          </div>
          <div class="form-group">
            <label class="form-label">Pengeluaran Bulanan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_expenses" value="<?= e(number_format((float) $fp['monthly_expenses'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="monthly_expenses" id="monthly_expenses">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tabungan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="savings_balance" value="<?= e(number_format((float) $fp['savings_balance'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="savings_balance" id="savings_balance">
          </div>
          <div class="form-group">
            <label class="form-label">Dana Darurat</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="emergency_fund" value="<?= e(number_format((float) $fp['emergency_fund'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="emergency_fund" id="emergency_fund">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Total Utang</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="total_debt" value="<?= e(number_format((float) $fp['total_debt'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="total_debt" id="total_debt">
          </div>
          <div class="form-group">
            <label class="form-label">Cicilan Bulanan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_debt_payment" value="<?= e(number_format((float) $fp['monthly_debt_payment'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="monthly_debt_payment" id="monthly_debt_payment">
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
      </form>
    </div>
  </main>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
