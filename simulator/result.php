<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$decisionId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM decisions WHERE id = :id AND user_id = :uid LIMIT 1');
$stmt->execute(['id' => $decisionId, 'uid' => $userId]);
$decision = $stmt->fetch();

if (!$decision) {
    setFlash('error', 'Simulasi tidak ditemukan.');
    redirect('/simulator/history.php');
}

$stmt = $pdo->prepare('
    SELECT o.*, r.financial_score, r.career_score, r.lifestyle_score, r.risk_score, r.overall_score,
           r.monthly_surplus, r.saving_rate, r.status_label, r.is_recommended
    FROM decision_options o
    JOIN decision_results r ON r.option_id = o.id
    WHERE o.decision_id = :did
    ORDER BY o.sort_order ASC
');
$stmt->execute(['did' => $decisionId]);
$options = $stmt->fetchAll();

if (count($options) < 2) {
    setFlash('error', 'Data simulasi tidak lengkap.');
    redirect('/simulator/history.php');
}

$optA = $options[0];
$optB = $options[1];
$winner = $optA['is_recommended'] ? $optA : $optB;
$loser = $optA['is_recommended'] ? $optB : $optA;
$winnerBand = scoreBand((float) $winner['overall_score']);

$pageTitle = $decision['title'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="app-main">
    <h2><?= e($decision['title']) ?></h2>
    <?php if ($decision['question']): ?><p><?= e($decision['question']) ?></p><?php endif; ?>

    <div class="card mt-16" style="text-align:center;">
      <p class="text-muted mb-0">Decision Score — <?= e($winner['label']) ?></p>
      <div class="score-ring-value"><?= e(round((float) $winner['overall_score'])) ?>/100</div>
      <span class="score-pill <?= e($winnerBand['class']) ?>"><?= e($winner['status_label']) ?></span>
    </div>

    <div class="card mt-16">
      <h3>Perbandingan Pilihan</h3>
      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Faktor</th><th><?= e($optA['label']) ?></th><th><?= e($optB['label']) ?></th></tr></thead>
        <tbody>
          <tr><td>Gaji</td><td><?= formatRupiah($optA['monthly_income']) ?></td><td><?= formatRupiah($optB['monthly_income']) ?></td></tr>
          <tr><td>Sisa / Bulan</td><td><?= formatRupiah($optA['monthly_surplus']) ?></td><td><?= formatRupiah($optB['monthly_surplus']) ?></td></tr>
          <tr><td>Saving Rate</td><td><?= formatPercent($optA['saving_rate']) ?></td><td><?= formatPercent($optB['saving_rate']) ?></td></tr>
          <tr><td>Financial Score</td><td><?= e(round((float) $optA['financial_score'])) ?></td><td><?= e(round((float) $optB['financial_score'])) ?></td></tr>
          <tr><td>Career Score</td><td><?= e(round((float) $optA['career_score'])) ?></td><td><?= e(round((float) $optB['career_score'])) ?></td></tr>
          <tr><td>Lifestyle Score</td><td><?= e(round((float) $optA['lifestyle_score'])) ?></td><td><?= e(round((float) $optB['lifestyle_score'])) ?></td></tr>
          <tr><td>Risk Score</td><td><?= e(round((float) $optA['risk_score'])) ?></td><td><?= e(round((float) $optB['risk_score'])) ?></td></tr>
          <tr><th>Overall</th><th><?= e(round((float) $optA['overall_score'])) ?></th><th><?= e(round((float) $optB['overall_score'])) ?></th></tr>
        </tbody>
      </table>
      </div>
      <canvas id="compareChart" height="90" class="mt-24"></canvas>
    </div>

    <div class="card mt-16">
      <h3>Mengapa "<?= e($winner['label']) ?>" Lebih Unggul?</h3>

      <?php
        $reasons = [];
        if ($winner['monthly_surplus'] > $loser['monthly_surplus']) $reasons[] = 'Potensi tabungan bulanan lebih tinggi';
        if ($winner['career_score'] > $loser['career_score']) $reasons[] = 'Pertumbuhan karier dinilai lebih baik';
        if ($winner['monthly_income'] > $loser['monthly_income']) $reasons[] = 'Gaji lebih besar';
        if ($winner['risk_score'] > $loser['risk_score']) $reasons[] = 'Risiko finansial lebih rendah';
        if ($winner['lifestyle_score'] > $loser['lifestyle_score']) $reasons[] = 'Kualitas hidup / commute lebih baik';

        $risks = [];
        if ($winner['monthly_surplus'] < 0) $risks[] = 'Pengeluaran pilihan ini melebihi pendapatan bulanan';
        if ((float) $winner['risk_score'] < 60) $risks[] = 'Skor risiko masih di bawah level aman';
        if ((float) $winner['saving_rate'] < (float) getSetting('healthy_saving_rate_min')) $risks[] = 'Saving rate belum mencapai target sehat (' . e(getSetting('healthy_saving_rate_min')) . '%)';
        if ($winner['commute_minutes'] > 45) $risks[] = 'Waktu perjalanan cukup panjang (' . (int) $winner['commute_minutes'] . ' menit)';
      ?>

      <h4 class="mt-16">Kelebihan</h4>
      <ul>
        <?php foreach ($reasons as $r): ?><li>✓ <?= e($r) ?></li><?php endforeach; ?>
        <?php if (!$reasons): ?><li>Kedua pilihan cukup seimbang secara data yang kamu masukkan.</li><?php endif; ?>
      </ul>

      <h4>Risiko yang Perlu Dipertimbangkan</h4>
      <ul>
        <?php foreach ($risks as $r): ?><li>⚠ <?= e($r) ?></li><?php endforeach; ?>
        <?php if (!$risks): ?><li>Tidak ada risiko besar yang terdeteksi dari data ini.</li><?php endif; ?>
      </ul>

      <h4>Kesimpulan</h4>
      <p>Berdasarkan data yang kamu masukkan, <strong><?= e($winner['label']) ?></strong> terlihat lebih kuat secara keseluruhan (<?= e(round((float) $winner['overall_score'])) ?>/100, <?= e($winner['status_label']) ?>). Gunakan hasil ini sebagai bahan pertimbangan, bukan keputusan mutlak — pertimbangkan juga faktor non-finansial yang penting bagimu.</p>
    </div>

    <div class="flex gap-12 mt-16">
      <a href="<?= BASE_URL ?>/simulator/new.php" class="btn btn-primary">Simulasi Lain</a>
      <a href="<?= BASE_URL ?>/simulator/history.php" class="btn btn-secondary">Lihat Riwayat</a>
    </div>
  </main>
</div>

<script>
new Chart(document.getElementById('compareChart'), {
  type: 'bar',
  data: {
    labels: ['Financial', 'Career', 'Lifestyle', 'Risk', 'Overall'],
    datasets: [
      { label: <?= json_encode($optA['label']) ?>, data: [<?= (float) $optA['financial_score'] ?>, <?= (float) $optA['career_score'] ?>, <?= (float) $optA['lifestyle_score'] ?>, <?= (float) $optA['risk_score'] ?>, <?= (float) $optA['overall_score'] ?>], backgroundColor: '#818CF8' },
      { label: <?= json_encode($optB['label']) ?>, data: [<?= (float) $optB['financial_score'] ?>, <?= (float) $optB['career_score'] ?>, <?= (float) $optB['lifestyle_score'] ?>, <?= (float) $optB['risk_score'] ?>, <?= (float) $optB['overall_score'] ?>], backgroundColor: '#06B6D4' }
    ]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
