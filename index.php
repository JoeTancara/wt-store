<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Categoria.php';
require_once __DIR__ . '/models/Configuracion.php';

$pageTitle     = 'Catálogo';
$prodModel     = new Producto();
$catModel      = new Categoria();
$configModel   = new Configuracion();
$categorias    = $catModel->getAll(true);
$banners       = $configModel->getBanners(true);
$mostrarPrecio = $configModel->get('mostrar_precio','0') === '1';
$nombreTienda  = $configModel->get('nombre_tienda','WT Store');
$descHero      = $configModel->get('descripcion_hero','Explora nuestro catálogo con los mejores productos.');

$busqueda    = trim($_GET['q']         ?? '');
$categoriaId = intval($_GET['categoria'] ?? 0);

$productos = $busqueda
    ? $prodModel->search($busqueda)
    : $prodModel->getAll(true, $categoriaId ?: null);

$BURL = BASE_URL . '/uploads/banners/';

include __DIR__ . '/views/partials/header_public.php';
?>

<?php if (!empty($banners)): ?>
<!-- ===== CARRUSEL ===== -->
<div id="heroBannerCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">

  <!-- Indicadores -->
  <?php if (count($banners) > 1): ?>
  <div class="carousel-indicators">
    <?php foreach ($banners as $i => $b): ?>
    <button type="button" data-bs-target="#heroBannerCarousel" data-bs-slide-to="<?= $i ?>"
            <?= $i===0?'class="active" aria-current="true"':'' ?>
            aria-label="Banner <?= $i+1 ?>"></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="carousel-inner">
    <?php foreach ($banners as $i => $b): ?>
    <div class="carousel-item <?= $i===0?'active':'' ?>">
      <?php if ($b['imagen']): ?>
        <div class="carousel-bg" style="background-image:url('<?= $BURL.htmlspecialchars(basename($b['imagen'])) ?>')"></div>
      <?php else: ?>
        <div class="carousel-bg" style="background:linear-gradient(135deg,var(--accent) 0%,#10114d 100%)"></div>
      <?php endif; ?>

      <div class="carousel-overlay"></div>

      <div class="carousel-caption-custom">
        <?php if ($b['titulo']||$b['subtitulo']): ?>
        <div class="container">
          <?php if ($b['titulo']): ?>
          <h2 class="carousel-title animate__fadeInUp"><?= htmlspecialchars($b['titulo']) ?></h2>
          <?php endif; ?>
          <?php if ($b['subtitulo']): ?>
          <p class="carousel-subtitle"><?= htmlspecialchars($b['subtitulo']) ?></p>
          <?php endif; ?>
          <?php if ($b['enlace']): ?>
          <a href="<?= htmlspecialchars($b['enlace']) ?>" class="btn btn-light fw-bold px-4 mt-2">
            Ver más <i class="bi bi-arrow-right ms-1"></i>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (count($banners) > 1): ?>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroBannerCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span><span class="visually-hidden">Anterior</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroBannerCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span><span class="visually-hidden">Siguiente</span>
  </button>
  <?php endif; ?>

  <!-- Buscador flotante sobre el carrusel -->
  <div class="carousel-search-overlay">
    <div class="container">
      <form method="GET" class="carousel-search-form">
        <?php if ($categoriaId): ?>
        <input type="hidden" name="categoria" value="<?= $categoriaId ?>">
        <?php endif; ?>
        <div class="carousel-search-inner">
          <i class="bi bi-search carousel-search-icon"></i>
          <input type="text" name="q" class="carousel-search-input"
                 placeholder="Buscar productos..."
                 value="<?= htmlspecialchars($busqueda) ?>">
          <button type="submit" class="carousel-search-btn">Buscar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ===== HERO ESTÁTICO (sin banners) ===== -->
<section class="hero-section">
  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1>Descubre nuestros<br>productos destacados</h1>
        <p class="mt-3 mb-4"><?= htmlspecialchars($descHero) ?></p>
        <form method="GET" class="d-flex gap-2" style="max-width:480px;">
          <?php if ($categoriaId): ?><input type="hidden" name="categoria" value="<?= $categoriaId ?>"><?php endif; ?>
          <div class="search-bar flex-grow-1">
            <i class="bi bi-search search-icon" style="color:rgba(255,255,255,.6);"></i>
            <input type="text" name="q" class="form-control form-control-lg"
                   style="padding-left:2.5rem;background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.35);color:white;"
                   placeholder="Buscar productos..."
                   value="<?= htmlspecialchars($busqueda) ?>">
          </div>
          <button type="submit" class="btn btn-light btn-lg fw-bold px-4">Buscar</button>
        </form>
      </div>
      <div class="col-lg-5 text-center d-none d-lg-block">
        <i class="bi bi-shop" style="font-size:9rem;opacity:.12;color:#fff;"></i>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== CATEGORÍAS ===== -->
<section class="py-4 py-md-5" id="categorias">
  <div class="container">
    <h2 class="section-title mb-4">Categorías</h2>
    <?php if (empty($categorias)): ?>
      <p style="color:var(--text-muted);">No hay categorías disponibles.</p>
    <?php else: ?>
    <div class="row g-2 g-md-3">
      <?php foreach ($categorias as $cat): ?>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="?categoria=<?= $cat['id'] ?>" class="text-decoration-none d-block">
          <div class="cat-chip <?= $categoriaId==$cat['id']?'active':'' ?>">
            <i class="bi bi-tag"></i>
            <span><?= htmlspecialchars($cat['nombre']) ?></span>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ===== PRODUCTOS ===== -->
<section class="pb-5">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <h2 class="section-title mb-0">
        <?php if ($busqueda): ?>
          Resultados para <em style="color:var(--accent);">"<?= htmlspecialchars($busqueda) ?>"</em>
        <?php elseif ($categoriaId && !empty($categorias)): ?>
          <?php foreach ($categorias as $c): if ($c['id']==$categoriaId): ?>
            <?= htmlspecialchars($c['nombre']) ?>
          <?php endif; endforeach; ?>
        <?php else: ?>
          Todos los Productos
        <?php endif; ?>
        <span style="font-size:1rem;font-weight:500;color:var(--text-muted);margin-left:.5rem;">
          (<?= count($productos) ?>)
        </span>
      </h2>
      <?php if ($busqueda || $categoriaId): ?>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-x-circle"></i> Ver todos
        </a>
      <?php endif; ?>
    </div>

    <!-- Filtros categoría -->
    <div class="category-filter mb-4">
      <button class="category-btn <?= !$categoriaId?'active':'' ?>"
              onclick="window.location='<?= BASE_URL ?>/index.php'">Todos</button>
      <?php foreach ($categorias as $cat): ?>
        <button class="category-btn <?= $categoriaId==$cat['id']?'active':'' ?>"
                onclick="window.location='<?= BASE_URL ?>/index.php?categoria=<?= $cat['id'] ?>'">
          <?= htmlspecialchars($cat['nombre']) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if (empty($productos)): ?>
      <div class="empty-state">
        <i class="bi bi-box-seam"></i>
        <p>No se encontraron productos<?= $busqueda?" para &ldquo;$busqueda&rdquo;":'.' ?></p>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary mt-3">Ver todos los productos</a>
      </div>
    <?php else: ?>
    <div class="row g-3 g-md-4">
      <?php foreach ($productos as $prod):
        $docenas  = (int)($prod['stock_docenas']  ?? 0);
        $unidades = (int)($prod['stock_unidades'] ?? 0);
        $upd      = max(1,(int)($prod['unidades_por_docena'] ?? 12));
        $stockTot = ($docenas*$upd)+$unidades;
        $sc = $stockTot>10?'stock-high':($stockTot>3?'stock-medium':($stockTot>0?'stock-low':'stock-out'));
      ?>
      <div class="col-6 col-sm-6 col-lg-4 col-xl-3 product-card-wrap" data-category="<?= $prod['categoria_id'] ?>">
        <div class="product-card">
          <?php if ($prod['imagen_principal']): ?>
            <img src="<?= UPLOAD_URL.htmlspecialchars(basename($prod['imagen_principal'])) ?>"
                 class="product-card-img"
                 alt="<?= htmlspecialchars($prod['nombre']) ?>"
                 loading="lazy">
          <?php else: ?>
            <div class="product-card-img-placeholder"><i class="bi bi-image"></i></div>
          <?php endif; ?>
          <div class="product-card-body">
            <div class="product-card-category"><?= htmlspecialchars($prod['categoria_nombre']??'') ?></div>
            <div class="product-card-title"><?= htmlspecialchars($prod['nombre']) ?></div>
            <?php if ($prod['descripcion']): ?>
              <p style="font-size:.80rem;color:var(--text-muted);margin-bottom:.5rem;line-height:1.4;flex:1;">
                <?= htmlspecialchars(mb_substr($prod['descripcion'],0,75)) ?><?= mb_strlen($prod['descripcion'])>75?'…':'' ?>
              </p>
            <?php endif; ?>
            <div class="d-flex align-items-end justify-content-between mt-auto pt-2 gap-1 flex-wrap">
              <div>
                <?php if ($mostrarPrecio): ?>
                  <div class="product-card-price">Bs. <?= number_format($prod['precio_venta'],2) ?></div>
                <?php endif; ?>
                <!-- Stock display: docenas + unidades -->
                <?php if ($stockTot > 0): ?>
                  <div class="stock-indicator <?= $sc ?>" style="font-size:.77rem;">
                    <span class="stock-dot"></span>
                    <?php if ($docenas > 0): ?>
                      <?= $docenas ?> doc
                      <?php if ($unidades > 0): ?> + <?= $unidades ?> uni<?php endif; ?>
                    <?php else: ?>
                      <?= $unidades ?> uni
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <div class="stock-indicator stock-out" style="font-size:.77rem;">
                    <span class="stock-dot"></span> Sin stock
                  </div>
                <?php endif; ?>
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
