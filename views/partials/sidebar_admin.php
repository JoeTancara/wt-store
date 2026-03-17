<?php
// views/partials/sidebar_admin.php
$currentFile = basename($_SERVER['PHP_SELF']);
$user = currentUser();
$initial = strtoupper(substr($user['nombre'], 0, 1));
?>
<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="mainSidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <span class="sidebar-brand-text"><i class="bi bi-shop"></i> WT Store</span>
    <span class="sidebar-brand-sub">Panel Administrativo</span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <div class="sidebar-section">Principal</div>
    <a href="<?= BASE_URL ?>/admin/dashboard.php"
       class="sidebar-link <?= $currentFile === 'dashboard.php' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sidebar-section">Catálogo</div>
    <a href="<?= BASE_URL ?>/admin/productos.php"
       class="sidebar-link <?= $currentFile === 'productos.php' ? 'active' : '' ?>">
      <i class="bi bi-box-seam"></i> Productos
    </a>

    <?php if (isAdmin()): ?>
    <a href="<?= BASE_URL ?>/admin/categorias.php"
       class="sidebar-link <?= $currentFile === 'categorias.php' ? 'active' : '' ?>">
      <i class="bi bi-tags"></i> Categorías
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Ventas</div>
    <a href="<?= BASE_URL ?>/admin/ventas.php"
       class="sidebar-link <?= $currentFile === 'ventas.php' ? 'active' : '' ?>">
      <i class="bi bi-receipt"></i> Ventas
    </a>

    <?php if (isAdmin()): ?>
    <a href="<?= BASE_URL ?>/admin/egresos.php"
       class="sidebar-link <?= $currentFile === 'egresos.php' ? 'active' : '' ?>">
      <i class="bi bi-wallet2"></i> Egresos
    </a>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div class="sidebar-section">Administración</div>
    <a href="<?= BASE_URL ?>/admin/usuarios.php"
       class="sidebar-link <?= $currentFile === 'usuarios.php' ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Usuarios
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Cuenta</div>
    <a href="<?= BASE_URL ?>/admin/perfil.php"
       class="sidebar-link <?= $currentFile === 'perfil.php' ? 'active' : '' ?>">
      <i class="bi bi-person-circle"></i> Mi Perfil
    </a>
    <a href="<?= BASE_URL ?>/index.php" class="sidebar-link" target="_blank">
      <i class="bi bi-shop"></i> Ver Catálogo
    </a>
    <a href="<?= BASE_URL ?>/logout.php" class="sidebar-link" style="color:rgba(239,68,68,0.75);">
      <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
    </a>

  </nav>

  <!-- User info at bottom -->
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= $initial ?></div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= htmlspecialchars($user['nombre']) ?></div>
      <div class="sidebar-user-role"><?= $user['rol'] === 'admin' ? 'Administrador' : 'Vendedor' ?></div>
    </div>
  </div>

</aside>
