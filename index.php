<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Categoria.php';

$pageTitle      = 'Catálogo';
$productoModel  = new Producto();
$categoriaModel = new Categoria();
$categorias     = $categoriaModel->getAll(true);

// Búsqueda o filtro por categoría
$busqueda   = trim($_GET['q'] ?? '');
$categoriaId= intval($_GET['categoria'] ?? 0);

if ($busqueda) {
    $productos = $productoModel->search($busqueda);
} else {
    $productos = $productoModel->getAll(true, $categoriaId ?: null);
}

include __DIR__ . '/views/partials/header_public.php';
?>

<!-- Hero -->
<section class="hero-section">
  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1>Descubre nuestros<br>productos destacados</h1>
        <p class="mt-3 mb-4">Explora nuestro catálogo completo con los mejores productos y precios.</p>
        <form method="GET" class="d-flex gap-2" style="max-width:480px;">
          <div class="search-bar flex-grow-1">
            <i class="bi bi-search search-icon" style="color:rgba(255,255,255,0.6);"></i>
            <input type="text" name="q" class="form-control form-control-lg"
                   style="padding-left:2.5rem;background:rgba(255,255,255,0.18);border-color:rgba(255,255,255,0.35);color:white;"
                   placeholder="Buscar productos..."
                   value="<?= htmlspecialchars($busqueda) ?>">
          </div>
          <button type="submit" class="btn btn-light btn-lg fw-bold px-4">Buscar</button>
        </form>
      </div>
      <div class="col-lg-5 text-center d-none d-lg-block" style="position:relative;z-index:2;">
        <i class="bi bi-shop" style="font-size:9rem;opacity:0.12;color:#fff;"></i>
      </div>
    </div>
  </div>
</section>

<!-- Categorías -->
<section class="py-5" id="categorias">
  <div class="container">
    <h2 class="section-title mb-4">Categorías</h2>
    <?php if (empty($categorias)): ?>
      <p style="color:var(--text-muted);">No hay categorías disponibles.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($categorias as $cat): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="?categoria=<?= $cat['id'] ?>" class="text-decoration-none d-block">
          <div class="stat-card" style="cursor:pointer;flex-direction:column;align-items:flex-start;gap:.5rem;<?= $categoriaId == $cat['id'] ? 'border-color:var(--accent);' : '' ?>">
            <div class="stat-icon purple"><i class="bi bi-tag"></i></div>
            <div>
              <div style="font-weight:700;color:var(--text-primary);font-size:.95rem;"><?= htmlspecialchars($cat['nombre']) ?></div>
              <?php if ($cat['descripcion']): ?>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                <?= htmlspecialchars(mb_substr($cat['descripcion'], 0, 45)) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Productos -->
<section class="pb-5">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <h2 class="section-title mb-0">
        <?php if ($busqueda): ?>
          Resultados para "<em><?= htmlspecialchars($busqueda) ?></em>"
        <?php elseif ($categoriaId && !empty($categorias)): ?>
          <?php foreach ($categorias as $c): if ($c['id'] == $categoriaId): ?>
            <?= htmlspecialchars($c['nombre']) ?>
          <?php endif; endforeach; ?>
        <?php else: ?>
          Todos los Productos
        <?php endif; ?>
        <span style="font-size:1rem;font-weight:500;color:var(--text-muted);margin-left:.5rem;">(<?= count($productos) ?>)</span>
      </h2>
      <?php if ($busqueda || $categoriaId): ?>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-x-circle"></i> Ver todos
        </a>
      <?php endif; ?>
    </div>

    <!-- Filter bar (client-side) -->
    <div class="category-filter mb-4">
      <button class="category-btn <?= !$categoriaId ? 'active' : '' ?>" data-category="all"
              onclick="window.location='<?= BASE_URL ?>/index.php'">Todos</button>
      <?php foreach ($categorias as $cat): ?>
        <button class="category-btn <?= $categoriaId == $cat['id'] ? 'active' : '' ?>"
                data-category="<?= $cat['id'] ?>"
                onclick="window.location='<?= BASE_URL ?>/index.php?categoria=<?= $cat['id'] ?>'">
          <?= htmlspecialchars($cat['nombre']) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if (empty($productos)): ?>
      <div class="empty-state">
        <i class="bi bi-box-seam"></i>
        <p>No se encontraron productos<?= $busqueda ? " para &ldquo;$busqueda&rdquo;" : '' ?>.</p>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary mt-3">Ver todos los productos</a>
      </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($productos as $prod): ?>
      <div class="col-12 col-sm-6 col-lg-4 col-xl-3 product-card-wrap" data-category="<?= $prod['categoria_id'] ?>">
        <div class="product-card">
          <?php if ($prod['imagen_principal']): ?>
            <img src="<?= UPLOAD_URL . htmlspecialchars(basename($prod['imagen_principal'])) ?>"
                 class="product-card-img"
                 alt="<?= htmlspecialchars($prod['nombre']) ?>"
                 loading="lazy">
          <?php else: ?>
            <div class="product-card-img-placeholder">
              <i class="bi bi-image"></i>
            </div>
          <?php endif; ?>
          <div class="product-card-body">
            <div class="product-card-category"><?= htmlspecialchars($prod['categoria_nombre'] ?? '') ?></div>
            <div class="product-card-title"><?= htmlspecialchars($prod['nombre']) ?></div>
            <?php if ($prod['descripcion']): ?>
              <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:.5rem;line-height:1.4;flex:1;">
                <?= htmlspecialchars(mb_substr($prod['descripcion'], 0, 80)) ?><?= mb_strlen($prod['descripcion']) > 80 ? '…' : '' ?>
              </p>
            <?php endif; ?>
            <div class="d-flex align-items-end justify-content-between mt-auto pt-2">
              <div>
                <div class="product-card-price">Bs <?= number_format($prod['precio'], 2) ?></div>
                <?php
                  $stockClass = (int)$prod['stock'] > 10 ? 'stock-high'
                              : ((int)$prod['stock'] > 3  ? 'stock-medium'
                              : ((int)$prod['stock'] > 0  ? 'stock-low' : 'stock-out'));
                  $stockText  = (int)$prod['stock'] > 0 ? "Stock: {$prod['stock']}" : 'Sin stock';
                ?>
                <div class="stock-indicator <?= $stockClass ?>">
                  <span class="stock-dot"></span> <?= $stockText ?>
                </div>
              </div>
              <a href="<?= BASE_URL ?>/producto-detalle.php?id=<?= $prod['id'] ?>"
                 class="btn btn-primary btn-sm">
                Ver más
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/views/partials/footer_public.php'; ?>
