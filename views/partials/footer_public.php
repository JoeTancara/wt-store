<?php
// views/partials/footer_public.php
require_once __DIR__ . '/../../models/Configuracion.php';
$_cfg       = new Configuracion();
$_nombre    = $_cfg->get('nombre_tienda','WT Store');
$_telefono  = $_cfg->get('telefono','');
$_whatsapp  = $_cfg->get('whatsapp','');
$_direccion = $_cfg->get('direccion','');
$_footer    = $_cfg->get('footer_texto','Sistema de Catálogo y Ventas');
?>
<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-12 col-md-6 col-lg-4">
        <div class="site-footer-brand"><?= htmlspecialchars($_nombre) ?></div>
        <div class="site-footer-text"><?= htmlspecialchars($_footer) ?></div>
      </div>
      <?php if ($_telefono || $_whatsapp || $_direccion): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div style="font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:.6rem;">Contacto</div>
        <div class="site-footer-contact">
          <?php if ($_telefono): ?>
          <a href="tel:<?= htmlspecialchars($_telefono) ?>">
            <i class="bi bi-telephone"></i> <?= htmlspecialchars($_telefono) ?>
          </a>
          <?php endif; ?>
          <?php if ($_whatsapp): ?>
          <a href="https://wa.me/<?= htmlspecialchars($_whatsapp) ?>" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($_whatsapp) ?>
          </a>
          <?php endif; ?>
          <?php if ($_direccion): ?>
          <a href="#">
            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($_direccion) ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-12 col-lg-4">
        <div style="font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:.6rem;">Navegación</div>
        <div class="d-flex flex-column gap-1" style="font-size:.84rem;">
          <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-secondary);text-decoration:none;">
            <i class="bi bi-house me-1"></i> Inicio
          </a>
          <a href="<?= BASE_URL ?>/index.php#categorias" style="color:var(--text-secondary);text-decoration:none;">
            <i class="bi bi-grid me-1"></i> Categorías
          </a>
          <?php if (isLoggedIn()): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php" style="color:var(--text-secondary);text-decoration:none;">
            <i class="bi bi-speedometer2 me-1"></i> Panel Admin
          </a>
          <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" style="color:var(--text-secondary);text-decoration:none;">
            <i class="bi bi-person me-1"></i> Iniciar Sesión
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="site-footer-bottom">
      &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($_nombre) ?></strong> — Todos los derechos reservados
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
