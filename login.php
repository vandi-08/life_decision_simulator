<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = null;
$emailOld = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $emailOld = cleanString($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!v_required($emailOld) || !v_required($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $result = attemptLogin($emailOld, $password);
        if ($result['success']) {
            redirect('/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Masuk';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrap">
  <div class="card">
    <h2>Masuk ke Akunmu</h2>
    <p>Lanjutkan simulasi keputusan hidupmu.</p>

    <?php if ($error): ?>
      <div class="flash flash-error" style="position:static; margin-bottom:16px; display:block;"><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/login.php" novalidate>
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($emailOld) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Masuk</button>
    </form>

    <p class="mt-16" style="text-align:center;">Belum punya akun? <a href="<?= BASE_URL ?>/register.php" style="color:var(--color-primary); font-weight:600;">Daftar</a></p>
    <p style="text-align:center; font-size:13px;" class="text-muted">Demo: demo@lifedecision.id / demo1234 (setelah menjalankan seed_accounts.php)</p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
