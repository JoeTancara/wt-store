<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/ProductoController.php';
require_once __DIR__ . '/models/Configuracion.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: '.BASE_URL.'/index.php'); exit; }

$ctrl = new ProductoController();
$prod = $ctrl->findById($id);

if (!$prod || !$prod['estado']) { header('Location: '.BASE_URL.'/index.php'); exit; }

$mostrarPrecio = (new Configuracion())->get('mostrar_precio','0') === '1';
$pageTitle     = $prod['nombre'];
$imagenes      = $prod['imagenes'] ?? [];
$docenas       = (int)($prod['stock_docenas']  ?? 0);
$unidades      = (int)($prod['stock_unidades'] ?? 0);
$upd           = max(1,(int)($prod['unidades_por_docena'] ?? 12));
$stockTotal    = ($docenas*$upd)+$unidades;
$precioDocena  = floatval($prod['precio_docena'] ?? 0);

include __DIR__ . '/views/partials/header_public.php';
?>

<div class="container py-4 py-md-5">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb" style="font-size:.84rem;">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Inicio</a></li>
      <li class="breadcrumb-item">
        <a href="<?= BASE_URL ?>/index.php?categoria=<?= (int)$prod['categoria_id'] ?>">
          <?= htmlspecialchars($prod['categoria_nombre']??'Categoría') ?>
        </a>
      </li>
      <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($prod['nombre']) ?></li>
    </ol>
  </nav>

  <div class="row g-4 g-lg-5">

    <!-- Galería -->
    <div class="col-12 col-lg-5">
      <?php if (!empty($imagenes)): ?>
        <div class="gallery-main mb-2">
          <img src="<?= UPLOAD_URL.htmlspecialchars(basename($imagenes[0]['ruta_imagen'])) ?>"
               id="mainImg" alt="<?= htmlspecialchars($prod['nombre']) ?>">
        </div>
        <?php if (count($imagenes)>1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($imagenes as $i => $img): ?>
          <img src="<?= UPLOAD_URL.htmlspecialchars(basename($img['ruta_imagen'])) ?>"
               class="gallery-thumb <?= $i===0?'active':'' ?>"
               alt="imagen <?= $i+1 ?>"
               onclick="setMainImg(this,'<?= UPLOAD_URL.htmlspecialchars(basename($img['ruta_imagen'])) ?>')">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php else: ?>
        <div style="width:100%;height:300px;background:var(--bg-primary);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:5rem;border:1px solid var(--border-color);">
          <i class="bi bi-image"></i>
        </div>
      <?php endif; ?>
    </div>

    <!-- Detalle -->
    <div class="col-12 col-lg-7">
      <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--accent);margin-bottom:.4rem;">
        <?= htmlspecialchars($prod['categoria_nombre']??'') ?>
      </div>
      <h1 style="font-weight:800;font-size:1.85rem;line-height:1.2;margin-bottom:1rem;">
        <?= htmlspecialchars($prod['nombre']) ?>
      </h1>

      <?php if ($prod['descripcion']): ?>
      <p style="color:var(--text-secondary);line-height:1.7;margin-bottom:1.5rem;font-size:.96rem;">
        <?= nl2br(htmlspecialchars($prod['descripcion'])) ?>
      </p>
      <?php endif; ?>

      <!-- Precios si está habilitado -->
      <?php if ($mostrarPrecio): ?>
      <div class="d-flex gap-3 align-items-baseline flex-wrap mb-3">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);font-family:var(--font-mono);">
          Bs. <?= number_format($prod['precio_venta'],2) ?>
          <span style="font-size:.9rem;font-weight:500;color:var(--text-muted);">/unidad</span>
        </div>
        <?php if ($precioDocena > 0): ?>
        <div style="font-size:1.25rem;font-weight:700;color:var(--info);font-family:var(--font-mono);">
          Bs. <?= number_format($precioDocena,2) ?>
          <span style="font-size:.82rem;font-weight:500;color:var(--text-muted);">/docena</span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Stock docenas + unidades -->
      <?php
        $sc = $stockTotal>10?'stock-high':($stockTotal>3?'stock-medium':($stockTotal>0?'stock-low':'stock-out'));
      ?>
      <div class="mb-4">
        <div class="stock-indicator <?= $sc ?>" style="font-size:.95rem;margin-bottom:.4rem;">
          <span class="stock-dot"></span>
          <?php if ($stockTotal > 0): ?>
            Disponible
          <?php else: ?>
            Sin stock
          <?php endif; ?>
        </div>
        <?php if ($stockTotal > 0): ?>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.5rem;">
          <?php if ($docenas > 0): ?>
          <div style="background:var(--accent-light);border:1.5px solid var(--accent);border-radius:var(--radius-sm);padding:.4rem .85rem;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-collection" style="color:var(--accent);font-size:.9rem;"></i>
            <div>
              <div style="font-weight:800;color:var(--accent);font-size:1rem;"><?= $docenas ?></div>
              <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;">Docenas</div>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($unidades > 0): ?>
          <div style="background:var(--success-light);border:1.5px solid var(--success);border-radius:var(--radius-sm);padding:.4rem .85rem;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-box" style="color:var(--success);font-size:.9rem;"></i>
            <div>
              <div style="font-weight:800;color:var(--success);font-size:1rem;"><?= $unidades ?></div>
              <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;">Unidades sueltas</div>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Info extra -->
      <div class="pt-3" style="border-top:1px solid var(--border-color);">
        <div class="d-flex gap-3 flex-wrap" style="font-size:.83rem;color:var(--text-muted);">
          <span><i class="bi bi-tag me-1"></i> <?= htmlspecialchars($prod['categoria_nombre']??'—') ?></span>
          <span><i class="bi bi-upc me-1"></i> ID: <?= (int)$prod['id'] ?></span>
          <span><i class="bi bi-collection me-1"></i> <?= $upd ?> uni/docena</span>
        </div>
      </div>

      <div class="mt-4">
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-primary">
          <i class="bi bi-arrow-left"></i> Ver más productos
        </a>
        <?php if ($_whatsapp = (new \stdClass())): ?>
        <?php
          $_wa = (new Configuracion())->get('whatsapp','');
          if ($_wa):
        ?>
        <a href="https://wa.me/<?= htmlspecialchars($_wa) ?>?text=Hola,%20me%20interesa%20el%20producto:%20<?= urlencode($prod['nombre']) ?>"
           target="_blank" rel="noopener"
           class="btn btn-success ms-2">
          <i class="bi bi-whatsapp"></i> Consultar
        </a>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
function setMainImg(thumb,src){
  document.getElementById('mainImg').src=src;
  document.querySelectorAll('.gallery-thumb').forEach(function(t){t.classList.remove('active');});
  thumb.classList.add('active');
}
</script>

<?php include __DIR__ . '/views/partials/footer_public.php'; ?>
