<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();

$goals = [
    'menabung'      => 'Menabung',
    'cari_kerja'    => 'Cari Kerja',
    'pindah_kota'   => 'Pindah Kota',
    'beli_barang'   => 'Beli Barang',
    'bangun_bisnis' => 'Bangun Bisnis',
    'dana_darurat'  => 'Dana Darurat',
    'menikah'       => 'Menikah',
    'pendidikan'    => 'Pendidikan',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $employmentStatus = cleanString($_POST['employment_status'] ?? '');
    $dependents = (int) ($_POST['dependents'] ?? 0);
    $primaryGoal = cleanString($_POST['primary_goal'] ?? '');
    $riskTolerance = cleanString($_POST['risk_tolerance'] ?? 'seimbang');

    $monthlyIncome = parseRupiah($_POST['monthly_income'] ?? '0');
    $monthlyExpenses = parseRupiah($_POST['monthly_expenses'] ?? '0');
    $savingsBalance = parseRupiah($_POST['savings_balance'] ?? '0');
    $emergencyFund = parseRupiah($_POST['emergency_fund'] ?? '0');
    $totalDebt = parseRupiah($_POST['total_debt'] ?? '0');

    if (!in_array($riskTolerance, ['konservatif', 'seimbang', 'agresif'], true)) {
        $riskTolerance = 'seimbang';
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE users SET risk_tolerance = :rt, onboarding_completed = 1 WHERE id = :id');
        $stmt->execute(['rt' => $riskTolerance, 'id' => $userId]);

        $stmt = $pdo->prepare('SELECT id FROM user_profiles WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare('UPDATE user_profiles SET employment_status=:es, dependents=:dep, primary_goal=:pg WHERE user_id=:uid');
        } else {
            $stmt = $pdo->prepare('INSERT INTO user_profiles (employment_status, dependents, primary_goal, user_id) VALUES (:es, :dep, :pg, :uid)');
        }
        $stmt->execute(['es' => $employmentStatus, 'dep' => $dependents, 'pg' => $primaryGoal, 'uid' => $userId]);

        $stmt = $pdo->prepare('
            UPDATE financial_profiles
            SET monthly_income=:income, monthly_expenses=:expenses, savings_balance=:savings,
                emergency_fund=:ef, total_debt=:debt
            WHERE user_id=:uid
        ');
        $stmt->execute([
            'income' => $monthlyIncome, 'expenses' => $monthlyExpenses, 'savings' => $savingsBalance,
            'ef' => $emergencyFund, 'debt' => $totalDebt, 'uid' => $userId,
        ]);

        $pdo->commit();
        setFlash('success', 'Profil kamu sudah lengkap. Selamat datang!');
        redirect('/dashboard.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        logError('onboarding', $e);
        $errors[] = 'Terjadi masalah saat menyimpan profil. Coba lagi.';
    }
}

$pageTitle = 'Lengkapi Profil';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrap" style="max-width:560px;">
  <div class="card">
    <h2>Ceritakan Kondisimu</h2>
    <p>Data ini dipakai untuk personalisasi dashboard dan simulasi. Kamu bisa mengubahnya kapan pun lewat halaman Profil.</p>

    <?php if ($errors): ?>
      <div class="flash flash-error" style="position:static; display:block; margin-bottom:16px;"><?= e($errors[0]) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/onboarding.php">
      <?= csrfField() ?>

      <h4 class="mt-16">Tentang Kamu</h4>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Status Pekerjaan</label>
          <select class="form-control" name="employment_status">
            <option value="karyawan_tetap">Karyawan Tetap</option>
            <option value="karyawan_kontrak">Karyawan Kontrak</option>
            <option value="freelancer">Freelancer</option>
            <option value="mahasiswa">Mahasiswa</option>
            <option value="pencari_kerja">Pencari Kerja</option>
            <option value="wirausaha">Wirausaha</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Jumlah Tanggungan</label>
          <input class="form-control" type="number" name="dependents" min="0" max="20" value="0">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Pendapatan Bulanan</label>
          <div class="input-rupiah"><span>Rp</span>
            <input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_income" placeholder="5.000.000">
          </div>
          <input type="hidden" name="monthly_income" id="monthly_income">
        </div>
        <div class="form-group">
          <label class="form-label">Pengeluaran Bulanan</label>
          <div class="input-rupiah"><span>Rp</span>
            <input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_expenses" placeholder="2.500.000">
          </div>
          <input type="hidden" name="monthly_expenses" id="monthly_expenses">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Tabungan Saat Ini</label>
          <div class="input-rupiah"><span>Rp</span>
            <input class="form-control" type="text" data-rupiah-input data-rupiah-target="savings_balance" placeholder="10.000.000">
          </div>
          <input type="hidden" name="savings_balance" id="savings_balance">
        </div>
        <div class="form-group">
          <label class="form-label">Dana Darurat</label>
          <div class="input-rupiah"><span>Rp</span>
            <input class="form-control" type="text" data-rupiah-input data-rupiah-target="emergency_fund" placeholder="10.000.000">
          </div>
          <input type="hidden" name="emergency_fund" id="emergency_fund">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Total Utang Saat Ini</label>
        <div class="input-rupiah"><span>Rp</span>
          <input class="form-control" type="text" data-rupiah-input data-rupiah-target="total_debt" placeholder="0">
        </div>
        <input type="hidden" name="total_debt" id="total_debt">
      </div>

      <h4 class="mt-16">Tujuan Utama</h4>
      <div class="form-group">
        <select class="form-control" name="primary_goal">
          <?php foreach ($goals as $key => $label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <h4 class="mt-16">Toleransi Risiko</h4>
      <div class="form-group">
        <select class="form-control" name="risk_tolerance">
          <option value="konservatif">Konservatif</option>
          <option value="seimbang" selected>Seimbang</option>
          <option value="agresif">Agresif</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Simpan &amp; Lanjutkan</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
