<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $fullName = cleanString($_POST['full_name'] ?? '');
        $age = cleanString($_POST['age'] ?? '');
        $city = cleanString($_POST['city'] ?? '');
        $occupation = cleanString($_POST['occupation'] ?? '');
        $riskTolerance = $_POST['risk_tolerance'] ?? 'seimbang';

        if (!v_required($fullName)) {
            $errors[] = 'Nama tidak boleh kosong.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET full_name=:name, age=:age, city=:city, occupation=:occ, risk_tolerance=:rt WHERE id=:id');
            $stmt->execute([
                'name' => $fullName, 'age' => $age !== '' ? (int) $age : null, 'city' => $city, 'occ' => $occupation,
                'rt' => in_array($riskTolerance, ['konservatif','seimbang','agresif'], true) ? $riskTolerance : 'seimbang',
                'id' => $userId,
            ]);
            $_SESSION['user_name'] = $fullName;
            setFlash('success', 'Profil berhasil diperbarui.');
            redirect('/profile/index.php');
        }
    } elseif ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors[] = 'Password saat ini salah.';
        } elseif (!v_min_length($new, 8)) {
            $errors[] = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $confirm) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $stmt->execute(['hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => $userId]);
            setFlash('success', 'Password berhasil diubah.');
            redirect('/profile/index.php');
        }
    }
}

$user = getCurrentUser();

$pageTitle = 'Profil';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="app-main" style="max-width:640px;">
    <h2>Profil</h2>

    <?php if ($errors): ?>
      <div class="flash flash-error" style="position:static; display:block; margin-bottom:16px;"><?= e($errors[0]) ?></div>
    <?php endif; ?>

    <div class="card mt-16">
      <h3>Informasi Pribadi</h3>
      <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input class="form-control" type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" value="<?= e($user['email']) ?>" disabled>
          <p class="form-hint">Email tidak dapat diubah.</p>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Usia</label>
            <input class="form-control" type="number" name="age" value="<?= e($user['age']) ?>" min="15" max="100">
          </div>
          <div class="form-group">
            <label class="form-label">Kota</label>
            <input class="form-control" type="text" name="city" value="<?= e($user['city']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Pekerjaan</label>
          <input class="form-control" type="text" name="occupation" value="<?= e($user['occupation']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Toleransi Risiko</label>
          <select class="form-control" name="risk_tolerance">
            <option value="konservatif" <?= $user['risk_tolerance'] === 'konservatif' ? 'selected' : '' ?>>Konservatif</option>
            <option value="seimbang" <?= $user['risk_tolerance'] === 'seimbang' ? 'selected' : '' ?>>Seimbang</option>
            <option value="agresif" <?= $user['risk_tolerance'] === 'agresif' ? 'selected' : '' ?>>Agresif</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Profil</button>
      </form>
    </div>

    <div class="card mt-16">
      <h3>Ubah Password</h3>
      <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label class="form-label">Password Saat Ini</label>
          <input class="form-control" type="password" name="current_password" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password Baru</label>
            <input class="form-control" type="password" name="new_password" minlength="8" required>
          </div>
          <div class="form-group">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input class="form-control" type="password" name="new_password_confirm" minlength="8" required>
          </div>
        </div>
        <button type="submit" class="btn btn-secondary">Ubah Password</button>
      </form>
    </div>
  </main>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
