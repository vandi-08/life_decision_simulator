<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'age' => '', 'city' => '', 'occupation' => '', 'monthly_salary' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $old['full_name'] = cleanString($_POST['full_name'] ?? '');
    $old['email'] = cleanString($_POST['email'] ?? '');
    $old['age'] = cleanString($_POST['age'] ?? '');
    $old['city'] = cleanString($_POST['city'] ?? '');
    $old['occupation'] = cleanString($_POST['occupation'] ?? '');
    $old['monthly_salary'] = $_POST['monthly_salary'] ?? '';

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!v_required($old['full_name'])) $errors[] = 'Nama lengkap wajib diisi.';
    if (!v_required($old['email']) || !v_email($old['email'])) $errors[] = 'Email tidak valid.';
    if (!v_min_length($password, 8)) $errors[] = 'Password minimal 8 karakter.';
    if ($password !== $passwordConfirm) $errors[] = 'Konfirmasi password tidak cocok.';
    if ($old['age'] !== '' && !v_int_between($old['age'], 15, 100)) $errors[] = 'Usia tidak valid.';

    if (!$errors) {
        $result = registerUser([
            'full_name'      => $old['full_name'],
            'email'          => $old['email'],
            'password'       => $password,
            'age'            => $old['age'] !== '' ? (int) $old['age'] : null,
            'city'           => $old['city'],
            'occupation'     => $old['occupation'],
            'monthly_salary' => parseRupiah($old['monthly_salary']),
        ]);

        if ($result['success']) {
            setFlash('success', 'Akun berhasil dibuat. Yuk lengkapi profilmu.');
            redirect('/onboarding.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Daftar Akun';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrap">
  <div class="card">
    <h2>Buat Akun Baru</h2>
    <p>Mulai simulasikan keputusan hidupmu dalam beberapa menit.</p>

    <?php if ($errors): ?>
      <div class="flash flash-error" style="position:static; margin-bottom:16px; display:block;">
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/register.php" novalidate>
      <?= csrfField() ?>

      <div class="form-group">
        <label class="form-label" for="full_name">Nama Lengkap</label>
        <input class="form-control" type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="password_confirm">Konfirmasi Password</label>
          <input class="form-control" type="password" id="password_confirm" name="password_confirm" minlength="8" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="age">Usia</label>
          <input class="form-control" type="number" id="age" name="age" min="15" max="100" value="<?= e($old['age']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="city">Kota</label>
          <input class="form-control" type="text" id="city" name="city" placeholder="mis. Bandung" value="<?= e($old['city']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="occupation">Pekerjaan</label>
          <input class="form-control" type="text" id="occupation" name="occupation" placeholder="mis. Software Developer" value="<?= e($old['occupation']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="monthly_salary_display">Gaji Bulanan</label>
          <div class="input-rupiah">
            <span>Rp</span>
            <input class="form-control" type="text" id="monthly_salary_display" data-rupiah-input data-rupiah-target="monthly_salary" placeholder="5.000.000">
          </div>
          <input type="hidden" name="monthly_salary" id="monthly_salary" value="<?= e($old['monthly_salary']) ?>">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Daftar</button>
    </form>

    <p class="mt-16" style="text-align:center;">Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" style="color:var(--color-primary); font-weight:600;">Masuk</a></p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
