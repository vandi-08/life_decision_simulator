<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$user = getCurrentUser();

if (!$user['onboarding_completed']) {
    redirect('/onboarding.php');
}

$stmt = $pdo->prepare('SELECT * FROM financial_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);
$fp = $stmt->fetch() ?: [
    'monthly_income' => 0, 'monthly_expenses' => 0, 'savings_balance' => 0,
    'emergency_fund' => 0, 'total_debt' => 0, 'monthly_debt_payment' => 0,
];

$surplus = (float) $fp['monthly_income'] - (float) $fp['monthly_expenses'];
$savingRate = calculateSavingsRate((float) $fp['monthly_income'], $surplus);
$coverage = calculateEmergencyCoverage((float) $fp['emergency_fund'], (float) $fp['monthly_expenses']);
$debtRatio = calculateDebtRatio((float) $fp['monthly_debt_payment'], (float) $fp['monthly_income']);

// Reuse the option-scoring engine on the user's "current state" as a pseudo-option
// to produce a single Financial Health score for the dashboard.
$pseudoOption = [
    'monthly_income' => $fp['monthly_income'],
    'housing_cost' => 0, 'food_cost' => 0, 'transport_cost' => 0, 'internet_cost' => 0,
    'entertainment_cost' => 0, 'shopping_cost' => 0, 'other_cost' => $fp['monthly_expenses'],
    'career_growth' => 'sedang', 'work_hours_per_week' => 40, 'commute_minutes' => 30, 'job_stability' => 'sedang',
];
$financial = calculateFinancialScore($pseudoOption, (float) $fp['emergency_fund'], (float) $fp['monthly_debt_payment']);
$career = calculateCareerScore($pseudoOption, (float) $fp['monthly_income']);
$lifestyle = calculateLifestyleScore($pseudoOption);

$stmt = $pdo->prepare('
    SELECT d.id, d.title, d.category, d.created_at, MAX(r.overall_score) AS best_score
    FROM decisions d
    LEFT JOIN decision_results r ON r.decision_id = d.id
    WHERE d.user_id = :uid AND d.status = "completed"
    GROUP BY d.id
    ORDER BY d.created_at DESC
    LIMIT 5
');
$stmt->execute(['uid' => $userId]);
$recentDecisions = $stmt->fetchAll();

$hour = (int) date('H');
$greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <main class="app-main">
    <h2><?= e($greeting) ?>, <?= e(explode(' ', $user['full_name'])[0]) ?>.</h2>
    <p>Ringkasan kondisimu hari ini.</p>

    <div class="card-grid mt-16">
      <div class="card stat-card">
        <div class="stat-label">Kesehatan Finansial</div>
        <div class="stat-value"><?= e(round($financial['score'])) ?>/100</div>
        <div class="stat-sub">Saving rate <?= formatPercent($savingRate) ?></div>
      </div>
      <div class="card stat-card">
        <div class="stat-label">Kesiapan Karier</div>
        <div class="stat-value"><?= e(round($career['score'])) ?>/100</div>
        <div class="stat-sub">Berdasarkan stabilitas &amp; beban kerja</div>
      </div>
      <div class="card stat-card">
        <div class="stat-label">Stabilitas Hidup</div>
        <div class="stat-value"><?= e(round($lifestyle['score'])) ?>/100</div>
        <div class="stat-sub">Rasio pengeluaran terhadap pendapatan</div>
      </div>
      <div class="card stat-card">
        <div class="stat-label">Dana Darurat</div>
        <div class="stat-value"><?= formatMonths($coverage) ?></div>
        <div class="stat-sub">Target <?= e(getSetting('emergency_fund_target_months')) ?> bulan</div>
      </div>
    </div>

    <div class="card mt-24">
      <div class="flex" style="justify-content:space-between; align-items:center;">
        <h3 class="mb-0">Ringkasan Keuangan Bulanan</h3>
        <a href="<?= BASE_URL ?>/finance/index.php" class="btn btn-ghost btn-sm">Kelola Keuangan</a>
      </div>
      <div class="card-grid mt-16">
        <div>
          <p class="text-muted mb-0">Pendapatan</p>
          <p style="font-size:20px; font-weight:700; color:var(--color-text);"><?= formatRupiah($fp['monthly_income']) ?></p>
        </div>
        <div>
          <p class="text-muted mb-0">Pengeluaran</p>
          <p style="font-size:20px; font-weight:700; color:var(--color-text);"><?= formatRupiah($fp['monthly_expenses']) ?></p>
        </div>
        <div>
          <p class="text-muted mb-0">Sisa / Bulan</p>
          <p style="font-size:20px; font-weight:700; color:<?= $surplus >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;"><?= formatRupiah($surplus) ?></p>
        </div>
        <div>
          <p class="text-muted mb-0">Debt-to-Income</p>
          <p style="font-size:20px; font-weight:700; color:var(--color-text);"><?= formatPercent($debtRatio) ?></p>
        </div>
      </div>
    </div>

    <div class="card mt-24">
      <div class="flex" style="justify-content:space-between; align-items:center;">
        <h3 class="mb-0">Keputusan Terbaru</h3>
        <a href="<?= BASE_URL ?>/simulator/new.php" class="btn btn-primary btn-sm">Simulasi Baru</a>
      </div>

      <?php if (!$recentDecisions): ?>
        <div class="empty-state">
          <i class="fa-regular fa-lightbulb"></i>
          <h4>Belum ada simulasi keputusan.</h4>
          <p>Coba simulasikan keputusan pertamamu untuk melihat dampaknya.</p>
          <a href="<?= BASE_URL ?>/simulator/new.php" class="btn btn-primary">Mulai Simulasi</a>
        </div>
      <?php else: ?>
        <table class="data-table mt-16">
          <thead><tr><th>Keputusan</th><th>Kategori</th><th>Score</th><th>Tanggal</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($recentDecisions as $d): $band = scoreBand((float) ($d['best_score'] ?? 0)); ?>
            <tr>
              <td><?= e($d['title']) ?></td>
              <td><?= e(DECISION_CATEGORIES[$d['category']] ?? $d['category']) ?></td>
              <td><span class="score-pill <?= e($band['class']) ?>"><?= e(round((float) ($d['best_score'] ?? 0))) ?>/100</span></td>
              <td class="text-muted"><?= e(date('d M Y', strtotime($d['created_at']))) ?></td>
              <td><a href="<?= BASE_URL ?>/simulator/result.php?id=<?= (int) $d['id'] ?>" class="btn btn-ghost btn-sm">Lihat</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
