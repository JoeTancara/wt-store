<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/ProductoController.php';

$id   = intval($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/index.php'); }

$ctrl = new ProductoController();
$prod = $ctrl->findById($id);

if (!$prod || !$prod['estado']) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = $prod['nombre'];
$imagenes  = $prod['imagenes'] ?? [];

include __DIR__ . '/views/partials/header_public.php';
?>

<div class="container py-5">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb" style="font-size:.85rem;">
      <li class="breadcrumb-item">
        <a href="<?= BASE_URL ?>/index.php">Inicio</a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?= BASE_URL ?>/index.php?categoria=<?= (int)$prod['categoria_id'] ?>">
          <?= htmlspecialchars($prod['categoria_nombre'] ?? 'Categoría') ?>
        </a>
      </li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($prod['nombre']) ?></li>
    </ol>
  </nav>

  <div class="row g-5">

    <!-- Galería de imágenes -->
    <div class="col-lg-5">
      <?php if (!empty($imagenes)): ?>
        <div class="gallery-main mb-2" id="galleryMain">
          <img src="<?= UPLOAD_URL . htmlspecialchars(basename($imagenes[0]['ruta_imagen'])) ?>"
               id="mainImg"
               alt="<?= htmlspecialchars($prod['nombre']) ?>">
        </div>
        <?php if (count($imagenes) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($imagenes as $i => $img): ?>
          <img src="<?= UPLOAD_URL . htmlspecialchars(basename($img['ruta_imagen'])) ?>"
               class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
               alt="imagen <?= $i+1 ?>"
               onclick="setMainImg(this, '<?= UPLOAD_URL . htmlspecialchars(basename($img['ruta_imagen'])) ?>')">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php else: ?>
        <div style="width:100%;height:320px;background:var(--bg-primary);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:5rem;border:1px solid var(--border-color);">
          <i class="bi bi-image"></i>
        </div>
      <?php endif; ?>
    </div>

    <!-- Detalle del producto -->
    <div class="col-lg-7">
      <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--accent);margin-bottom:.5rem;">
        <?= htmlspecialchars($prod['categoria_nombre'] ?? '') ?>
      </div>
      <h1 style="font-weight:800;font-size:2rem;line-height:1.2;margin-bottom:1rem;">
        <?= htmlspecialchars($prod['nombre']) ?>
      </h1>

      <?php if ($prod['descripcion']): ?>
      <p style="color:var(--text-secondary);line-height:1.7;margin-bottom:1.5rem;font-size:.97rem;">
        <?= nl2br(htmlspecialchars($prod['descripcion'])) ?>
      </p>
      <?php endif; ?>

      <!-- Precio -->
      <div style="font-size:2.5rem;font-weight:800;color:var(--accent);font-family:var(--font-mono);margin-bottom:1rem;">
        Bs <?= number_format($prod['precio'], 2) ?>
      </div>

      <!-- Stock -->
      <?php
        $sc = (int)$prod['stock'] > 10 ? 'stock-high'
            : ((int)$prod['stock'] > 3  ? 'stock-medium'
            : ((int)$prod['stock'] > 0  ? 'stock-low' : 'stock-out'));
      ?>
      <div class="stock-indicator <?= $sc ?> mb-4" style="font-size:.95rem;">
        <span class="stock-dot"></span>
        <?php if ((int)$prod['stock'] > 0): ?>
          Disponible — <?= (int)$prod['stock'] ?> unidades
        <?php else: ?>
          Sin stock disponible
        <?php endif; ?>
      </div>

      <!-- Acciones -->
      <?php if ((int)$prod['stock'] > 0): ?>
        <?php if (isLoggedIn()): ?>
          <div class="d-flex gap-3 align-items-center flex-wrap">
            <!--<div class="d-flex align-items-center gap-2"
                 style="background:var(--bg-primary);border:1.5px solid var(--border-color);border-radius:var(--radius-sm);padding:.4rem .75rem;">
              <button type="button" class="btn btn-sm p-0 lh-1" style="background:none;border:none;color:var(--text-secondary);"
                      onclick="changeQty(-1)">
                <i class="bi bi-dash-lg"></i>
              </button>
              
              <input type="number" id="qtyInput" value="1" min="1" max="<?= (int)$prod['stock'] ?>"
                     style="width:50px;text-align:center;background:none;border:none;font-weight:700;color:var(--text-primary);font-family:var(--font-mono);font-size:1rem;"
                     class="form-control p-0">
              <button type="button" class="btn btn-sm p-0 lh-1" style="background:none;border:none;color:var(--text-secondary);"
                      onclick="changeQty(1)">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
            <button class="btn btn-primary btn-lg px-4 fw-bold" onclick="addToCart()">
              <i class="bi bi-cart-plus"></i> Agregar al carrito
            </button>
          </div>-->
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-lg px-4 fw-bold">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión para comprar
          </a>
        <?php endif; ?>
      <?php else: ?>
        <button class="btn btn-secondary btn-lg px-4" disabled>
          <i class="bi bi-x-circle"></i> Sin stock
        </button>
      <?php endif; ?>

      <!-- Info extra -->
      <div class="mt-4 pt-3" style="border-top:1px solid var(--border-color);">
        <div class="d-flex gap-4 flex-wrap" style="font-size:.85rem;color:var(--text-muted);">
          <span><i class="bi bi-tag me-1"></i> <?= htmlspecialchars($prod['categoria_nombre'] ?? '—') ?></span>
          <span><i class="bi bi-upc me-1"></i> ID: <?= (int)$prod['id'] ?></span>
        </div>
      </div>
    </div>

  </div>

  <!-- Más productos -->
  <div class="mt-5 pt-3" style="border-top:1px solid var(--border-color);">
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-primary">
      <i class="bi bi-arrow-left"></i> Ver más productos
    </a>
  </div>

</div>

<script>
function setMainImg(thumb, src) {
  document.getElementById('mainImg').src = src;
  document.querySelectorAll('.gallery-thumb').forEach(function(t) { t.classList.remove('active'); });
  thumb.classList.add('active');
}

function changeQty(delta) {
  var inp = document.getElementById('qtyInput');
  var val = parseInt(inp.value) + delta;
  var max = parseInt(inp.max);
  if (val < 1)   val = 1;
  if (val > max) val = max;
  inp.value = val;
}

function addToCart() {
  var qty = parseInt(document.getElementById('qtyInput').value) || 1;
  var product = {
    id:     <?= (int)$prod['id'] ?>,
    nombre: <?= json_encode($prod['nombre']) ?>,
    precio: "<?= $prod['precio'] ?>",
    stock:  <?= (int)$prod['stock'] ?>
  };
  // Add multiple times based on qty
  Cart.load();
  // Find existing
  var existing = null;
  for (var i = 0; i < Cart.items.length; i++) {
    if (Cart.items[i].id == product.id) { existing = Cart.items[i]; break; }
  }
  if (existing) {
    var newQty = existing.cantidad + qty;
    if (newQty > product.stock) {
      showToast('Stock insuficiente', 'warning');
      return;
    }
    existing.cantidad = newQty;
  } else {
    if (qty > product.stock) { showToast('Stock insuficiente', 'warning'); return; }
    Cart.items.push({ id: product.id, nombre: product.nombre, precio: product.precio, stock: product.stock, cantidad: qty });
  }
  Cart.save();
  Cart.render();
  showToast(product.nombre + ' agregado al carrito (' + qty + ' uds)', 'success');
}
</script>

<?php include __DIR__ . '/views/partials/footer_public.php'; ?>
