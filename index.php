<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (isLoggedIn()) {
    redirect('/dashboard.php');
}
$pageTitle = 'Sebelum Memutuskan, Coba Simulasikan';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<section class="hero">
  <div>
    <h1>Sebelum Memutuskan,<br>Coba Simulasikan.</h1>
    <p class="subtitle">Lihat dampak finansial, karier, dan gaya hidup dari keputusan besar sebelum kamu benar-benar mengambilnya — dibuat khusus untuk anak muda Indonesia.</p>
    <div class="hero-actions">
      <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Mulai Simulasi</a>
      <a href="#cara-kerja" class="btn btn-secondary">Lihat Cara Kerja</a>
    </div>
  </div>

  <div class="hero-visual">
    <p class="hero-visual-title">Pindah ke Jakarta?</p>
    <div class="hero-visual-row"><span class="label">Gaji Sekarang</span><span class="value" data-animate-number="5000000" data-prefix="Rp">Rp0</span></div>
    <div class="hero-visual-row"><span class="label">Gaji Baru</span><span class="value" data-animate-number="8000000" data-prefix="Rp">Rp0</span></div>
    <div class="hero-visual-row"><span class="label">Biaya Hidup</span><span class="value" data-animate-number="5600000" data-prefix="Rp">Rp0</span></div>
    <div class="hero-visual-row"><span class="label">Potensi Tabungan</span><span class="value" data-animate-number="2400000" data-prefix="Rp">Rp0</span></div>
    <div class="hero-visual-row">
      <span class="label">Decision Score</span>
      <span class="score-pill score-good"><span data-animate-number="78">0</span>/100</span>
    </div>
  </div>
</section>

<section class="section" id="cara-kerja">
  <h2 class="section-title">Cara Kerja</h2>
  <p class="section-subtitle">Enam langkah sederhana untuk mengubah keputusan besar menjadi pertimbangan yang lebih rasional.</p>
  <div class="steps-grid">
    <div class="step-card"><div class="step-num">1</div><h4>Ceritakan Kondisimu</h4><p>Masukkan penghasilan, pengeluaran, tabungan, utang, kota, dan pekerjaan saat ini.</p></div>
    <div class="step-card"><div class="step-num">2</div><h4>Tentukan Pilihan</h4><p>Masukkan dua atau tiga opsi yang sedang kamu pertimbangkan.</p></div>
    <div class="step-card"><div class="step-num">3</div><h4>Atur Prioritas</h4><p>Tentukan apa yang paling penting bagi kamu lewat slider prioritas.</p></div>
    <div class="step-card"><div class="step-num">4</div><h4>Simulasikan</h4><p>Sistem menghitung dampak setiap pilihan secara real-time.</p></div>
    <div class="step-card"><div class="step-num">5</div><h4>Bandingkan</h4><p>Lihat kelebihan, kekurangan, risiko, dan konsekuensi tiap opsi.</p></div>
    <div class="step-card"><div class="step-num">6</div><h4>Ambil Keputusan</h4><p>Gunakan hasil sebagai bahan pertimbangan, bukan keputusan mutlak.</p></div>
  </div>
</section>

<section class="section">
  <h2 class="section-title">Kategori Keputusan</h2>
  <p class="section-subtitle">Template yang relevan dengan kehidupan anak muda Indonesia.</p>
  <div class="category-grid">
    <div class="category-card"><i class="fa-solid fa-briefcase"></i><h4>Karier</h4><p class="text-muted">Kerja baru, resign, career switch</p></div>
    <div class="category-card"><i class="fa-solid fa-city"></i><h4>Pindah Kota</h4><p class="text-muted">Merantau, pindah domisili</p></div>
    <div class="category-card"><i class="fa-solid fa-house"></i><h4>Tempat Tinggal</h4><p class="text-muted">Ngekos, tinggal sendiri</p></div>
    <div class="category-card"><i class="fa-solid fa-bag-shopping"></i><h4>Pembelian</h4><p class="text-muted">Laptop, HP, motor, PC</p></div>
    <div class="category-card"><i class="fa-solid fa-graduation-cap"></i><h4>Pendidikan</h4><p class="text-muted">Kuliah, sertifikasi</p></div>
    <div class="category-card"><i class="fa-solid fa-store"></i><h4>Bisnis</h4><p class="text-muted">UMKM, online shop, jasa</p></div>
    <div class="category-card"><i class="fa-solid fa-heart"></i><h4>Masa Depan</h4><p class="text-muted">Menikah, rumah, keluarga</p></div>
    <div class="category-card"><i class="fa-solid fa-ellipsis"></i><h4>Lainnya</h4><p class="text-muted">Keputusan custom kamu sendiri</p></div>
  </div>
</section>

<section class="section" style="text-align:center;">
  <h2 class="section-title">Siap membuat keputusan yang lebih rasional?</h2>
  <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Mulai Simulasi Gratis</a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
