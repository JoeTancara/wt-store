<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Catálogo') ?> - WT Store</title>
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
    // Apply saved theme immediately to avoid flash
    (function(){
      var t = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
    var BASE_URL = '<?= BASE_URL ?>';
  </script>
</head>
<body>

<nav class="public-navbar navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
      <i class="bi bi-shop"></i> WT Store
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav"
            aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation"
            style="border-color:var(--border-color);">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="publicNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-house"></i> Inicio
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/index.php#categorias">
            <i class="bi bi-grid"></i> Categorías
          </a>
        </li>
        <li class="nav-item ms-lg-2">
          <button class="theme-toggle" type="button">
            <i class="bi bi-moon-fill"></i> Modo Oscuro
          </button>
        </li>
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php">
            <i class="bi bi-speedometer2"></i> Panel
          </a>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="btn btn-primary btn-sm ms-lg-2 px-3" href="<?= BASE_URL ?>/login.php">
            <i class="bi bi-person"></i> Iniciar Sesión
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
