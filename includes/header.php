<?php
/** Shared <head> + opening layout. Expects optional $pageTitle. */
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script>
  // Apply saved theme before paint to avoid flash
  (function() {
    var theme = localStorage.getItem('ldsi_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
  })();
</script>
</head>
<body>
<?php $flashes = getFlashes(); ?>
<?php if ($flashes): ?>
  <div class="flash-stack" id="flashStack">
    <?php foreach ($flashes as $flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>">
        <span><?= e($flash['message']) ?></span>
        <button type="button" class="flash-close" aria-label="Tutup">&times;</button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
