<?php
// admin/productos.php
$pageTitle = 'Productos';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';

$ctrl       = new ProductoController();
$catCtrl    = new CategoriaController();
$categorias = $catCtrl->getAll(true);

// Solo admin puede crear/editar/eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $r = $ctrl->create($_POST, $_FILES);
        setFlash($r['success'] ? 'success' : 'error', $r['message']);
        redirect(BASE_URL . '/admin/productos.php');
    } elseif ($action === 'update') {
        $r = $ctrl->update(intval($_POST['id']), $_POST, $_FILES);
        setFlash($r['success'] ? 'success' : 'error', $r['message']);
        redirect(BASE_URL . '/admin/productos.php');
    } elseif ($action === 'delete') {
        $r = $ctrl->delete(intval($_POST['id']));
        setFlash($r['success'] ? 'success' : 'error', $r['message']);
        redirect(BASE_URL . '/admin/productos.php');
    } elseif ($action === 'delete_imagen') {
        header('Content-Type: application/json');
        echo json_encode(['success' => (bool)$ctrl->deleteImagen(
            intval($_POST['imagen_id']),
            intval($_POST['producto_id'])
        )]);
        exit;
    }
}

// Ambos roles pueden actualizar stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_stock') {
    $r = $ctrl->updateStock(intval($_POST['id']), intval($_POST['cantidad']));
    setFlash($r['success'] ? 'success' : 'error', $r['message']);
    redirect(BASE_URL . '/admin/productos.php');
}

$productos = $ctrl->getAll();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">
      <i class="bi bi-box-seam"></i> Productos
      <span style="font-size:.78rem;color:var(--text-muted);font-weight:500;">(<?= count($productos) ?>)</span>
    </span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:190px;padding-left:2.2rem;">
      </div>
      <?php if (isAdmin()): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="abrirNuevo()">
        <i class="bi bi-plus-lg"></i> Nuevo Producto
      </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($productos)): ?>
    <div class="empty-state">
      <i class="bi bi-box-seam"></i>
      <p>No hay productos registrados</p>
      <?php if (isAdmin()): ?>
      <button type="button" class="btn btn-primary mt-3" onclick="abrirNuevo()">
        <i class="bi bi-plus-lg"></i> Crear primer producto
      </button>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaProductos">
      <thead>
        <tr>
          <th style="width:46px;"></th>
          <th>Nombre</th>
          <th class="table-hide-mobile">Categoria</th>
          <?php if (isAdmin()): ?>
          <th class="table-hide-tablet">P. Compra</th>
          <?php endif; ?>
          <th>P. Venta</th>
          <th>Stock</th>
          <th class="table-hide-mobile">Estado</th>
          <th style="width:<?= isAdmin() ? '130' : '90' ?>px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p):
          $sc  = (int)$p['stock'] > 10 ? 'stock-high'
               : ((int)$p['stock'] > 3  ? 'stock-medium'
               : ((int)$p['stock'] > 0  ? 'stock-low' : 'stock-out'));
          $pv  = floatval($p['precio_venta'] ?? $p['precio'] ?? 0);
          $pc  = floatval($p['precio_compra'] ?? 0);
          // pJson incluye imagen_principal y categoria_nombre para el modal Ver
          $pJson = htmlspecialchars(json_encode([
            'id'              => (int)$p['id'],
            'nombre'          => $p['nombre'],
            'descripcion'     => $p['descripcion'] ?? '',
            'categoria_id'    => (int)$p['categoria_id'],
            'categoria_nombre'=> $p['categoria_nombre'] ?? '',
            'imagen_principal' => $p['imagen_principal'] ?? '',
            'precio_compra'   => $pc,
            'precio_venta'    => $pv,
            'stock'           => (int)$p['stock'],
            'estado'          => (int)$p['estado'],
          ]), ENT_QUOTES);
        ?>
        <tr>
          <td>
            <?php if ($p['imagen_principal']): ?>
              <img src="<?= UPLOAD_URL . htmlspecialchars(basename($p['imagen_principal'])) ?>"
                   style="width:40px;height:40px;object-fit:cover;border-radius:7px;border:1px solid var(--border-color);display:block;">
            <?php else: ?>
              <div style="width:40px;height:40px;border-radius:7px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
                <i class="bi bi-image"></i>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <strong style="font-size:.88rem;"><?= htmlspecialchars($p['nombre']) ?></strong>
            <div class="d-block d-md-none" style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">
              <?= htmlspecialchars($p['categoria_nombre'] ?? '') ?>
            </div>
          </td>
          <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);">
            <?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?>
          </td>
          <?php if (isAdmin()): ?>
          <td class="table-hide-tablet" style="font-size:.85rem;color:var(--text-secondary);">
            Bs. <?= number_format($pc, 2) ?>
          </td>
          <?php endif; ?>
          <td class="text-money fw-bold" style="color:var(--accent);">
            Bs. <?= number_format($pv, 2) ?>
          </td>
          <td>
            <span class="stock-indicator <?= $sc ?>">
              <span class="stock-dot"></span><?= (int)$p['stock'] ?>
            </span>
          </td>
          <td class="table-hide-mobile">
            <span class="badge-status <?= $p['estado'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $p['estado'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <!-- VER — todos los roles -->
              <button type="button" class="btn btn-sm btn-outline-info"
                      onclick="verProducto(<?= $pJson ?>)" title="Ver detalle">
                <i class="bi bi-eye"></i>
              </button>
              <!-- STOCK — todos los roles -->
              <button type="button" class="btn btn-sm btn-outline-secondary"
                      onclick="abrirStock(<?= (int)$p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nombre'])) ?>', <?= (int)$p['stock'] ?>)"
                      title="Actualizar stock">
                <i class="bi bi-arrow-repeat"></i>
              </button>
              <?php if (isAdmin()): ?>
              <!-- EDITAR — solo admin -->
              <button type="button" class="btn btn-sm btn-outline-primary"
                      onclick="editProducto(<?= $pJson ?>)" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <!-- ELIMINAR — solo admin -->
              <button type="button" class="btn btn-sm btn-outline-danger"
                      onclick="eliminarProducto(<?= (int)$p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nombre'])) ?>')"
                      title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Form eliminar oculto -->
<form method="POST" id="formDel" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="fDelId">
</form>

<!-- ===== MODAL: Ver Producto (todos los roles) ===== -->
<div class="modal fade" id="modalVer" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-eye"></i> Detalle del Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="verBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-outline-secondary" id="btnVerStock">
          <i class="bi bi-arrow-repeat"></i> Actualizar Stock
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL: Actualizar Stock (ambos roles) ===== -->
<div class="modal fade" id="modalStock" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="formStock">
        <input type="hidden" name="action" value="update_stock">
        <input type="hidden" name="id" id="sId">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Actualizar Stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p id="sNombre" class="fw-bold mb-1" style="font-size:.9rem;"></p>
          <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem;">
            Stock actual: <strong id="sActual" style="color:var(--accent);"></strong>
          </p>
          <label class="form-label">Cantidad *</label>
          <input type="number" name="cantidad" id="sCant" class="form-control" required
                 placeholder="+ agregar   /   - descontar">
          <div class="invalid-feedback">Ingresa una cantidad</div>
          <small style="color:var(--text-muted);font-size:.74rem;display:block;margin-top:.3rem;">
            Ej: <code>10</code> suma &nbsp;&nbsp; <code>-5</code> resta
          </small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarStock">
            <i class="bi bi-save"></i> Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (isAdmin()): ?>
<!-- ===== MODAL: Crear / Editar (solo admin) ===== -->
<div class="modal fade" id="modalProducto" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data" id="formProd">
        <input type="hidden" name="action" id="pAction" value="create">
        <input type="hidden" name="id"     id="pId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloProd"><i class="bi bi-plus-lg"></i> Nuevo Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-8">
              <label class="form-label">Nombre *</label>
              <input type="text" name="nombre" id="pNombre" class="form-control"
                     required maxlength="150" placeholder="Nombre del producto">
              <div class="invalid-feedback">El nombre es obligatorio</div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Categoria *</label>
              <select name="categoria_id" id="pCategoria" class="form-select" required>
                <option value="">Seleccionar...</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Selecciona una categoria</div>
            </div>
            <div class="col-6">
              <label class="form-label">Precio Compra</label>
              <div class="input-group">
                <span class="input-group-text">Bs.</span>
                <input type="number" name="precio_compra" id="pCompra" class="form-control"
                       step="0.01" min="0" value="0" placeholder="0.00">
              </div>
            </div>
            <div class="col-6">
              <label class="form-label">Precio Venta *</label>
              <div class="input-group">
                <span class="input-group-text">Bs.</span>
                <input type="number" name="precio_venta" id="pVenta" class="form-control"
                       step="0.01" min="0.01" required placeholder="0.00">
              </div>
              <div class="invalid-feedback">Ingresa un precio de venta mayor a 0</div>
            </div>
            <!-- Stock: solo en creacion, en edicion es readonly -->
            <div class="col-6 col-md-4" id="wrapStockNew">
              <label class="form-label">Stock inicial</label>
              <input type="number" name="stock" id="pStock" class="form-control" min="0" value="0">
            </div>
            <div class="col-6 col-md-4" id="wrapStockEdit" style="display:none;">
              <label class="form-label">Stock actual</label>
              <input type="number" id="pStockShow" class="form-control" disabled>
              <small style="color:var(--text-muted);font-size:.73rem;">
                Usar <i class="bi bi-arrow-repeat"></i> para cambiar
              </small>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label">Estado</label>
              <select name="estado" id="pEstado" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripcion</label>
              <textarea name="descripcion" id="pDesc" class="form-control"
                        rows="3" maxlength="1000" placeholder="Descripcion opcional..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">
                Imagenes
                <small style="color:var(--text-muted);font-weight:400;">(max <?= MAX_IMAGES ?> · jpg, png, webp)</small>
              </label>
              <input type="file" name="imagenes[]" id="pImgs" class="form-control"
                     multiple accept="image/jpeg,image/png,image/gif,image/webp">
              <div id="imgExist" class="img-preview-container"></div>
              <div id="imgNew"   class="img-preview-container"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardProd">
            <i class="bi bi-save"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
var _productoActual = null;

document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('searchInput', 'tablaProductos', [1, 2]);

  // Boton stock desde modal Ver
  document.getElementById('btnVerStock').addEventListener('click', function() {
    bootstrap.Modal.getInstance(document.getElementById('modalVer')).hide();
    if (_productoActual) {
      setTimeout(function() {
        abrirStock(_productoActual.id, _productoActual.nombre, _productoActual.stock);
      }, 350);
    }
  });

  // Validar formStock
  document.getElementById('formStock').addEventListener('submit', function(e) {
    var cant = document.getElementById('sCant');
    cant.classList.remove('is-invalid');
    if (!cant.value.trim() || isNaN(parseInt(cant.value))) {
      cant.classList.add('is-invalid');
      e.preventDefault();
      return;
    }
    document.getElementById('btnGuardarStock').disabled = true;
    document.getElementById('btnGuardarStock').innerHTML =
      '<span class="spinner-border spinner-border-sm me-1"></span>Actualizando...';
  });

  document.getElementById('modalStock').addEventListener('hidden.bs.modal', function() {
    document.getElementById('btnGuardarStock').disabled = false;
    document.getElementById('btnGuardarStock').innerHTML = '<i class="bi bi-save"></i> Actualizar';
    document.getElementById('sCant').classList.remove('is-invalid');
  });

  <?php if (isAdmin()): ?>
  // Preview imagenes nuevas
  document.getElementById('pImgs').addEventListener('change', function() {
    var cont = document.getElementById('imgNew');
    cont.innerHTML = '';
    var libre = <?= MAX_IMAGES ?> - document.getElementById('imgExist').querySelectorAll('.img-preview-item').length;
    Array.from(this.files).slice(0, libre).forEach(function(f) {
      var r = new FileReader();
      r.onload = function(e) {
        var d = document.createElement('div');
        d.className = 'img-preview-item';
        d.innerHTML = '<img src="' + e.target.result + '" alt="">';
        cont.appendChild(d);
      };
      r.readAsDataURL(f);
    });
    if (this.files.length > libre) showToast('Maximo <?= MAX_IMAGES ?> imagenes', 'warning');
  });

  // Validar formProd al enviar
  document.getElementById('formProd').addEventListener('submit', function(e) {
    var nb = document.getElementById('pNombre');
    var ct = document.getElementById('pCategoria');
    var pv = document.getElementById('pVenta');
    var ok = true;
    [nb, ct, pv].forEach(function(el) { el.classList.remove('is-invalid'); });
    if (!nb.value.trim())               { nb.classList.add('is-invalid'); ok = false; }
    if (!ct.value)                      { ct.classList.add('is-invalid'); ok = false; }
    if (!pv.value || parseFloat(pv.value) <= 0) { pv.classList.add('is-invalid'); ok = false; }
    if (!ok) {
      e.preventDefault();
      (document.querySelector('#formProd .is-invalid') || nb).focus();
      return;
    }
    document.getElementById('btnGuardProd').disabled = true;
    document.getElementById('btnGuardProd').innerHTML =
      '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });

  // Reset modal producto al cerrar
  document.getElementById('modalProducto').addEventListener('hidden.bs.modal', function() {
    document.getElementById('formProd').reset();
    document.getElementById('pAction').value = 'create';
    document.getElementById('pId').value     = '';
    document.getElementById('tituloProd').innerHTML = '<i class="bi bi-plus-lg"></i> Nuevo Producto';
    document.getElementById('imgExist').innerHTML = '';
    document.getElementById('imgNew').innerHTML   = '';
    document.getElementById('wrapStockNew').style.display  = '';
    document.getElementById('wrapStockEdit').style.display = 'none';
    document.getElementById('btnGuardProd').disabled = false;
    document.getElementById('btnGuardProd').innerHTML = '<i class="bi bi-save"></i> Guardar';
    document.querySelectorAll('#formProd .is-invalid').forEach(function(el) {
      el.classList.remove('is-invalid');
    });
  });
  <?php endif; ?>
});

// ---- VER Producto: carga imagenes via AJAX ----
function verProducto(p) {
  _productoActual = p;
  document.getElementById('verBody').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVer')).show();

  var esAdmin = <?= isAdmin() ? 'true' : 'false' ?>;

  // Cargar imagenes via AJAX
  fetch(BASE_URL + '/admin/get_imagenes.php?producto_id=' + p.id)
    .then(function(r) { return r.json(); })
    .then(function(imgs) {
      var html = '';

      // Galeria de imagenes
      if (imgs.length > 0) {
        html += '<div style="margin-bottom:1rem;">';
        if (imgs.length === 1) {
          html += '<img src="' + BASE_URL + '/uploads/productos/' + imgs[0].ruta_imagen + '" '
            + 'style="width:100%;max-height:260px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border-color);">';
        } else {
          html += '<img id="verMainImg" src="' + BASE_URL + '/uploads/productos/' + imgs[0].ruta_imagen + '" '
            + 'style="width:100%;max-height:240px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border-color);cursor:pointer;">';
          html += '<div style="display:flex;gap:.4rem;margin-top:.5rem;flex-wrap:wrap;">';
          imgs.forEach(function(img, i) {
            html += '<img src="' + BASE_URL + '/uploads/productos/' + img.ruta_imagen + '" '
              + 'onclick="document.getElementById(\'verMainImg\').src=this.src;" '
              + 'style="width:60px;height:60px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid '
              + (i===0 ? 'var(--accent)' : 'var(--border-color)') + ';" '
              + 'onmouseover="this.style.borderColor=\'var(--accent)\'" '
              + 'onmouseout="this.style.borderColor=\'' + (i===0?'var(--accent)':'var(--border-color)') + '\'">';
          });
          html += '</div>';
        }
        html += '</div>';
      } else {
        html += '<div style="width:100%;height:120px;background:var(--bg-primary);border-radius:var(--radius);'
          + 'display:flex;align-items:center;justify-content:center;color:var(--text-muted);'
          + 'font-size:3rem;margin-bottom:1rem;border:1px solid var(--border-color);">'
          + '<i class="bi bi-image"></i></div>';
      }

      // Datos del producto
      var sc = p.stock > 10 ? 'stock-high' : (p.stock > 3 ? 'stock-medium' : (p.stock > 0 ? 'stock-low' : 'stock-out'));
      html += '<table class="table table-sm" style="font-size:.88rem;">';
      html += '<tr><th style="width:38%;color:var(--text-muted);font-weight:600;">Nombre</th>'
        + '<td><strong>' + p.nombre + '</strong></td></tr>';
      html += '<tr><th style="color:var(--text-muted);font-weight:600;">Categoria</th>'
        + '<td>' + (p.categoria_nombre || '—') + '</td></tr>';
      if (esAdmin) {
        html += '<tr><th style="color:var(--text-muted);font-weight:600;">P. Compra</th>'
          + '<td style="font-family:var(--font-mono);">Bs. ' + parseFloat(p.precio_compra||0).toFixed(2) + '</td></tr>';
      }
      html += '<tr><th style="color:var(--text-muted);font-weight:600;">P. Venta</th>'
        + '<td class="text-money fw-bold" style="color:var(--accent);">Bs. ' + parseFloat(p.precio_venta||0).toFixed(2) + '</td></tr>';
      if (esAdmin) {
        var gan = parseFloat(p.precio_venta||0) - parseFloat(p.precio_compra||0);
        html += '<tr><th style="color:var(--text-muted);font-weight:600;">Ganancia</th>'
          + '<td class="text-money" style="color:var(--success);">Bs. ' + gan.toFixed(2) + '</td></tr>';
      }
      html += '<tr><th style="color:var(--text-muted);font-weight:600;">Stock</th><td>'
        + '<span class="stock-indicator ' + sc + '"><span class="stock-dot"></span>' + p.stock + ' unidades</span></td></tr>';
      html += '<tr><th style="color:var(--text-muted);font-weight:600;">Estado</th><td>'
        + '<span class="badge-status ' + (p.estado ? 'badge-active' : 'badge-inactive') + '">'
        + (p.estado ? 'Activo' : 'Inactivo') + '</span></td></tr>';
      if (p.descripcion) {
        html += '<tr><th style="color:var(--text-muted);font-weight:600;">Descripcion</th>'
          + '<td style="font-size:.84rem;">' + p.descripcion + '</td></tr>';
      }
      html += '</table>';
      document.getElementById('verBody').innerHTML = html;
    })
    .catch(function() {
      // Si falla AJAX igual muestra los datos del producto
      document.getElementById('verBody').innerHTML =
        '<div class="alert alert-warning">No se pudieron cargar las imagenes</div>';
    });
}

// ---- Stock ----
function abrirStock(id, nombre, stock) {
  _productoActual = { id: id, nombre: nombre, stock: stock };
  document.getElementById('sId').value            = id;
  document.getElementById('sNombre').textContent   = nombre;
  document.getElementById('sActual').textContent   = stock;
  document.getElementById('sCant').value           = '';
  document.getElementById('sCant').classList.remove('is-invalid');
  document.getElementById('btnGuardarStock').disabled = false;
  document.getElementById('btnGuardarStock').innerHTML = '<i class="bi bi-save"></i> Actualizar';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalStock')).show();
}

<?php if (isAdmin()): ?>
// ---- Nuevo producto ----
function abrirNuevo() {
  document.getElementById('formProd').reset();
  document.getElementById('pAction').value = 'create';
  document.getElementById('pId').value     = '';
  document.getElementById('tituloProd').innerHTML = '<i class="bi bi-plus-lg"></i> Nuevo Producto';
  document.getElementById('imgExist').innerHTML = '';
  document.getElementById('imgNew').innerHTML   = '';
  document.getElementById('wrapStockNew').style.display  = '';
  document.getElementById('wrapStockEdit').style.display = 'none';
  document.getElementById('btnGuardProd').disabled = false;
  document.getElementById('btnGuardProd').innerHTML = '<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formProd .is-invalid').forEach(function(el) {
    el.classList.remove('is-invalid');
  });
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto')).show();
}

// ---- Editar producto ----
function editProducto(p) {
  document.getElementById('pAction').value    = 'update';
  document.getElementById('pId').value        = p.id;
  document.getElementById('pNombre').value    = p.nombre       || '';
  document.getElementById('pCategoria').value = p.categoria_id || '';
  document.getElementById('pCompra').value    = p.precio_compra || 0;
  document.getElementById('pVenta').value     = p.precio_venta  || 0;
  document.getElementById('pStockShow').value = p.stock  || 0;
  document.getElementById('pEstado').value    = (p.estado !== undefined) ? p.estado : 1;
  document.getElementById('pDesc').value      = p.descripcion  || '';
  document.getElementById('tituloProd').innerHTML = '<i class="bi bi-pencil"></i> Editar Producto';
  document.getElementById('imgNew').innerHTML = '';
  document.getElementById('wrapStockNew').style.display  = 'none';
  document.getElementById('wrapStockEdit').style.display = '';
  document.getElementById('btnGuardProd').disabled = false;
  document.getElementById('btnGuardProd').innerHTML = '<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formProd .is-invalid').forEach(function(el) {
    el.classList.remove('is-invalid');
  });

  // Cargar imagenes existentes
  var cont = document.getElementById('imgExist');
  cont.innerHTML = '<small style="color:var(--text-muted);font-size:.74rem;">Cargando...</small>';
  fetch(BASE_URL + '/admin/get_imagenes.php?producto_id=' + p.id)
    .then(function(r) { return r.json(); })
    .then(function(imgs) {
      cont.innerHTML = '';
      if (imgs.length) {
        var lbl = document.createElement('small');
        lbl.style.cssText = 'color:var(--text-muted);font-size:.74rem;display:block;margin-bottom:4px;';
        lbl.textContent = 'Imagenes actuales (' + imgs.length + '/<?= MAX_IMAGES ?>):';
        cont.appendChild(lbl);
        imgs.forEach(function(img) {
          var div = document.createElement('div');
          div.className = 'img-preview-item';
          div.id = 'iw' + img.id;
          div.innerHTML =
            '<img src="' + BASE_URL + '/uploads/productos/' + img.ruta_imagen + '" alt="">'
            + '<button type="button" class="img-preview-delete" '
            + 'onclick="borrarImg(' + img.id + ',' + p.id + ',\'iw' + img.id + '\')">'
            + '<i class="bi bi-x"></i></button>';
          cont.appendChild(div);
        });
      }
    })
    .catch(function() { cont.innerHTML = ''; });

  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto')).show();
}

// ---- Borrar imagen individual ----
function borrarImg(imgId, prodId, wrapId) {
  confirmar('Eliminar esta imagen?', 'warning').then(function(ok) {
    if (!ok) return;
    var fd = new FormData();
    fd.append('action',      'delete_imagen');
    fd.append('imagen_id',   imgId);
    fd.append('producto_id', prodId);
    fetch(location.pathname, { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) {
          var el = document.getElementById(wrapId);
          if (el) el.remove();
          showToast('Imagen eliminada', 'success');
        } else {
          showToast('Error al eliminar imagen', 'danger');
        }
      });
  });
}

// ---- Eliminar producto ----
function eliminarProducto(id, nombre) {
  confirmar('Eliminar <strong>' + nombre + '</strong>?<br>'
    + '<small style="color:var(--text-muted)">Se eliminaran tambien sus imagenes.</small>', 'danger')
    .then(function(ok) {
      if (!ok) return;
      document.getElementById('fDelId').value = id;
      document.getElementById('formDel').submit();
    });
}
<?php endif; ?>
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
