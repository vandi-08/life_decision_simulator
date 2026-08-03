<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();

$stmt = $pdo->prepare('SELECT * FROM financial_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);
$fp = $stmt->fetch() ?: ['monthly_income' => 0, 'savings_balance' => 0, 'emergency_fund' => 0, 'total_debt' => 0, 'monthly_debt_payment' => 0];

$pageTitle = 'Simulasi Baru';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="app-main" style="max-width:760px;">
    <h2>Simulasi Keputusan Baru</h2>
    <p>Isi kondisimu, dua pilihan yang sedang kamu pertimbangkan, lalu atur prioritas pribadimu.</p>

    <form method="post" action="<?= BASE_URL ?>/simulator/process.php" id="simulatorForm">
      <?= csrfField() ?>

      <!-- STEP INDICATOR -->
      <div class="flex gap-8 mt-16" id="stepIndicator">
        <span class="badge" data-step-dot="1">1. Kategori</span>
        <span class="badge" data-step-dot="2" style="opacity:.5;">2. Kondisi Saat Ini</span>
        <span class="badge" data-step-dot="3" style="opacity:.5;">3. Pilihan A</span>
        <span class="badge" data-step-dot="4" style="opacity:.5;">4. Pilihan B</span>
        <span class="badge" data-step-dot="5" style="opacity:.5;">5. Prioritas</span>
      </div>

      <!-- STEP 1 -->
      <div class="card mt-16 sim-step" data-step="1">
        <h3>Keputusan apa yang sedang kamu pikirkan?</h3>
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select class="form-control" name="category" required>
            <?php foreach (DECISION_CATEGORIES as $key => $label): ?>
              <option value="<?= e($key) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Judul Keputusan</label>
          <input class="form-control" type="text" name="title" placeholder="mis. Pindah ke Jakarta" required>
        </div>
        <div class="form-group">
          <label class="form-label">Apa yang ingin kamu putuskan? (opsional)</label>
          <input class="form-control" type="text" name="question" placeholder="mis. Apakah saya sebaiknya menerima pekerjaan di Jakarta?">
        </div>
      </div>

      <!-- STEP 2: current condition (used as scoring context, not persisted as an option) -->
      <div class="card mt-16 sim-step" data-step="2" style="display:none;">
        <h3>Kondisi Keuanganmu Saat Ini</h3>
        <p class="form-hint">Diambil dari profil keuanganmu — sesuaikan jika ada perubahan.</p>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Gaji Saat Ini</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="current_income" value="<?= e(number_format((float) $fp['monthly_income'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="current_income" id="current_income" value="<?= e($fp['monthly_income']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Dana Darurat</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="emergency_fund" value="<?= e(number_format((float) $fp['emergency_fund'], 0, ',', '.')) ?>"></div>
            <input type="hidden" name="emergency_fund" id="emergency_fund" value="<?= e($fp['emergency_fund']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Cicilan / Utang Bulanan Saat Ini</label>
          <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="monthly_debt_payment" value="<?= e(number_format((float) $fp['monthly_debt_payment'], 0, ',', '.')) ?>"></div>
          <input type="hidden" name="monthly_debt_payment" id="monthly_debt_payment" value="<?= e($fp['monthly_debt_payment']) ?>">
        </div>
      </div>

      <!-- STEP 3 & 4: options A and B share the same field layout -->
      <?php foreach (['A' => 'Pilihan A (mis. Tetap di kota sekarang)', 'B' => 'Pilihan B (mis. Pindah ke kota baru)'] as $letter => $heading): ?>
      <div class="card mt-16 sim-step" data-step="<?= $letter === 'A' ? 3 : 4 ?>" style="display:none;">
        <h3><?= e($heading) ?></h3>
        <div class="form-group">
          <label class="form-label">Label Pilihan</label>
          <input class="form-control" type="text" name="option_<?= $letter ?>_label" placeholder="mis. Tetap di Bandung" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Gaji Bulanan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_income" placeholder="5.000.000"></div>
            <input type="hidden" name="option_<?= $letter ?>_income" id="opt<?= $letter ?>_income">
          </div>
          <div class="form-group">
            <label class="form-label">Kos / Sewa</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_housing" placeholder="0"></div>
            <input type="hidden" name="option_<?= $letter ?>_housing" id="opt<?= $letter ?>_housing">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Makan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_food" placeholder="1.000.000"></div>
            <input type="hidden" name="option_<?= $letter ?>_food" id="opt<?= $letter ?>_food">
          </div>
          <div class="form-group">
            <label class="form-label">Transportasi</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_transport" placeholder="500.000"></div>
            <input type="hidden" name="option_<?= $letter ?>_transport" id="opt<?= $letter ?>_transport">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Internet</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_internet" placeholder="300.000"></div>
            <input type="hidden" name="option_<?= $letter ?>_internet" id="opt<?= $letter ?>_internet">
          </div>
          <div class="form-group">
            <label class="form-label">Hiburan</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_entertainment" placeholder="300.000"></div>
            <input type="hidden" name="option_<?= $letter ?>_entertainment" id="opt<?= $letter ?>_entertainment">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Belanja</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_shopping" placeholder="300.000"></div>
            <input type="hidden" name="option_<?= $letter ?>_shopping" id="opt<?= $letter ?>_shopping">
          </div>
          <div class="form-group">
            <label class="form-label">Pengeluaran Lain</label>
            <div class="input-rupiah"><span>Rp</span><input class="form-control" type="text" data-rupiah-input data-rupiah-target="opt<?= $letter ?>_other" placeholder="0"></div>
            <input type="hidden" name="option_<?= $letter ?>_other" id="opt<?= $letter ?>_other">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Pertumbuhan Karier</label>
            <select class="form-control" name="option_<?= $letter ?>_career_growth">
              <option value="rendah">Rendah</option><option value="sedang" selected>Sedang</option><option value="tinggi">Tinggi</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Stabilitas Pekerjaan</label>
            <select class="form-control" name="option_<?= $letter ?>_job_stability">
              <option value="rendah">Rendah</option><option value="sedang" selected>Sedang</option><option value="tinggi">Tinggi</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Jam Kerja / Minggu</label>
            <input class="form-control" type="number" name="option_<?= $letter ?>_work_hours" value="40" min="1" max="100">
          </div>
          <div class="form-group">
            <label class="form-label">Waktu Perjalanan (menit)</label>
            <input class="form-control" type="number" name="option_<?= $letter ?>_commute" value="30" min="0" max="240">
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- STEP 5: priority sliders -->
      <div class="card mt-16 sim-step" data-step="5" style="display:none;">
        <h3>Prioritas Pribadi</h3>
        <p class="form-hint">Total harus selalu 100%.</p>
        <?php foreach (DEFAULT_PRIORITY_WEIGHTS as $key => $default):
            $labels = ['financial' => 'Stabilitas Keuangan', 'career' => 'Pertumbuhan Karier', 'lifestyle' => 'Kualitas Hidup', 'family' => 'Dekat Keluarga', 'freetime' => 'Waktu Luang', 'growth' => 'Pengembangan Diri'];
        ?>
          <div class="priority-slider">
            <div class="priority-slider-head"><span><?= e($labels[$key]) ?></span><span id="weight_<?= $key ?>_value"><?= $default ?>%</span></div>
            <input type="range" id="weight_<?= $key ?>" name="weight_<?= $key ?>" min="0" max="100" value="<?= $default ?>">
          </div>
        <?php endforeach; ?>
        <div id="priorityTotal" class="priority-total">Total: 100%</div>
      </div>

      <div class="flex gap-12 mt-24">
        <button type="button" class="btn btn-secondary" id="simPrevBtn" style="display:none;">Sebelumnya</button>
        <button type="button" class="btn btn-primary" id="simNextBtn">Selanjutnya</button>
        <button type="submit" class="btn btn-primary" id="simulatorSubmit" style="display:none;">Sedang menganalisis kondisimu... <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </form>
  </main>
</div>

<script>
(function () {
  var steps = document.querySelectorAll('.sim-step');
  var dots = document.querySelectorAll('[data-step-dot]');
  var current = 1;
  var total = steps.length;

  function show(step) {
    steps.forEach(function (s) { s.style.display = (parseInt(s.dataset.step, 10) === step) ? 'block' : 'none'; });
    dots.forEach(function (d) { d.style.opacity = (parseInt(d.dataset.stepDot, 10) === step) ? '1' : '.5'; });
    document.getElementById('simPrevBtn').style.display = step === 1 ? 'none' : 'inline-flex';
    document.getElementById('simNextBtn').style.display = step === total ? 'none' : 'inline-flex';
    document.getElementById('simulatorSubmit').style.display = step === total ? 'inline-flex' : 'none';
  }

  document.getElementById('simNextBtn').addEventListener('click', function () {
    if (current < total) { current++; show(current); window.scrollTo({top: 0, behavior: 'smooth'}); }
  });
  document.getElementById('simPrevBtn').addEventListener('click', function () {
    if (current > 1) { current--; show(current); window.scrollTo({top: 0, behavior: 'smooth'}); }
  });

  document.getElementById('simulatorForm').addEventListener('submit', function () {
    var btn = document.getElementById('simulatorSubmit');
    btn.disabled = true;
    btn.innerHTML = 'Sedang menganalisis kondisimu... <i class="fa-solid fa-spinner fa-spin"></i>';
  });

  show(current);
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
