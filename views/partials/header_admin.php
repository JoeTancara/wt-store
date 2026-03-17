<?php
// views/partials/header_admin.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Panel') ?> - WT Store Admin</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script>
    (function(){
      var t = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
    var BASE_URL = '<?= BASE_URL ?>';
  </script>
</head>
<body>
<div class="admin-wrapper">

<?php include __DIR__ . '/sidebar_admin.php'; ?>

<div class="admin-main">
  <!-- Topbar -->
  <header class="admin-topbar">
    <button type="button" id="sidebarToggle"
            style="background:none;border:none;color:var(--text-secondary);cursor:pointer;padding:0.4rem;display:flex;align-items:center;">
      <i class="bi bi-list" style="font-size:1.4rem;"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Panel') ?></div>
    <div class="d-flex align-items-center gap-2">
      <button class="theme-toggle" type="button">
        <i class="bi bi-moon-fill"></i> Modo Oscuro
      </button>
    </div>
  </header>

  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="flash-message alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> d-flex align-items-center gap-2 m-3 mb-0 py-2" role="alert">
    <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <div class="admin-content">
