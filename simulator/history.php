<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    requireValidCsrf();
    $id = (int) ($_POST['decision_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM decisions WHERE id = :id AND user_id = :uid');
    $stmt->execute(['id' => $id, 'uid' => $userId]);
    setFlash('success', 'Simulasi berhasil dihapus.');
    redirect('/simulator/history.php');
}

$categoryFilter = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$sql = '
    SELECT d.id, d.title, d.category, d.created_at, MAX(r.overall_score) AS best_score
    FROM decisions d
    LEFT JOIN decision_results r ON r.decision_id = d.id
    WHERE d.user_id = :uid AND d.status = "completed"
';
$params = ['uid' => $userId];
if ($categoryFilter && array_key_exists($categoryFilter, DECISION_CATEGORIES)) {
    $sql .= ' AND d.category = :cat';
    $params['cat'] = $categoryFilter;
}
$sql .= ' GROUP BY d.id';

switch ($sort) {
    case 'oldest': $sql .= ' ORDER BY d.created_at ASC'; break;
    case 'score_high': $sql .= ' ORDER BY best_score DESC'; break;
    case 'score_low': $sql .= ' ORDER BY best_score ASC'; break;
    default: $sql .= ' ORDER BY d.created_at DESC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$decisions = $stmt->fetchAll();

$pageTitle = 'Riwayat Keputusan';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="app-shell">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="app-main">
    <h2>Riwayat Keputusan</h2>

    <form method="get" class="flex gap-12 mt-16" style="flex-wrap:wrap;">
      <select class="form-control" name="category" style="width:auto;" onchange="this.form.submit()">
        <option value="">Semua Kategori</option>
        <?php foreach (DECISION_CATEGORIES as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $categoryFilter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" name="sort" style="width:auto;" onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
        <option value="score_high" <?= $sort === 'score_high' ? 'selected' : '' ?>>Score Tertinggi</option>
        <option value="score_low" <?= $sort === 'score_low' ? 'selected' : '' ?>>Score Terendah</option>
      </select>
    </form>

    <div class="card mt-16">
      <?php if (!$decisions): ?>
        <div class="empty-state">
          <i class="fa-regular fa-folder-open"></i>
          <h4>Belum ada simulasi keputusan.</h4>
          <p>Coba simulasikan keputusan pertamamu untuk melihat dampaknya.</p>
          <a href="<?= BASE_URL ?>/simulator/new.php" class="btn btn-primary">Mulai Simulasi</a>
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Keputusan</th><th>Kategori</th><th>Score</th><th>Tanggal</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($decisions as $d): $band = scoreBand((float) ($d['best_score'] ?? 0)); ?>
            <tr>
              <td><?= e($d['title']) ?></td>
              <td><?= e(DECISION_CATEGORIES[$d['category']] ?? $d['category']) ?></td>
              <td><span class="score-pill <?= e($band['class']) ?>"><?= e(round((float) ($d['best_score'] ?? 0))) ?>/100</span></td>
              <td class="text-muted"><?= e(date('d M Y', strtotime($d['created_at']))) ?></td>
              <td class="flex gap-8">
                <a href="<?= BASE_URL ?>/simulator/result.php?id=<?= (int) $d['id'] ?>" class="btn btn-ghost btn-sm">Lihat</a>
                <form method="post" onsubmit="return confirmAction('Hapus simulasi ini?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="decision_id" value="<?= (int) $d['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger);">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
