<?php

$pageTitle = 'Productos';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';

$ctrl       = new ProductoController();
$catCtrl    = new CategoriaController();
$categorias = $catCtrl->getAll(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $ctrl->create($_POST, $_FILES);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);

    } elseif ($action === 'update') {
        $result = $ctrl->update(intval($_POST['id']), $_POST, $_FILES);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);

    } elseif ($action === 'delete') {
        $result = $ctrl->delete(intval($_POST['id']));
        setFlash($result['success'] ? 'success' : 'error', $result['message']);

    } elseif ($action === 'delete_imagen') {
        // AJAX
        header('Content-Type: application/json');
        $ok = $ctrl->deleteImagen(intval($_POST['imagen_id']), intval($_POST['producto_id']));
        echo json_encode(['success' => (bool)$ok]);
        exit;

    } elseif ($action === 'update_stock') {
        $result = $ctrl->updateStock(intval($_POST['id']), intval($_POST['cantidad']));
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
    }

    redirect(BASE_URL . '/admin/productos.php');
}

$productos = $ctrl->getAll();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-box-seam"></i> Gestión de Productos</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar producto..." style="width:220px;padding-left:2.2rem;">
      </div>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalProducto">
        <i class="bi bi-plus-lg"></i> Nuevo Producto
      </button>
    </div>
  </div>

  <?php if (empty($productos)): ?>
    <div class="empty-state">
      <i class="bi bi-box-seam"></i>
      <p>No hay productos registrados</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaProductos">
      <thead>
        <tr>
          <th style="width:60px;">Img</th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Estado</th>
          <th style="width:130px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p):
          $sc = (int)$p['stock'] > 10 ? 'stock-high'
              : ((int)$p['stock'] > 3  ? 'stock-medium'
              : ((int)$p['stock'] > 0  ? 'stock-low' : 'stock-out'));
        ?>
        <tr>
          <td>
            <?php if ($p['imagen_principal']): ?>
              <img src="<?= UPLOAD_URL . htmlspecialchars(basename($p['imagen_principal'])) ?>"
                   style="width:46px;height:46px;object-fit:cover;border-radius:8px;border:1px solid var(--border-color);display:block;"
                   alt="">
            <?php else: ?>
              <div style="width:46px;height:46px;border-radius:8px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:1.2rem;">
                <i class="bi bi-image"></i>
              </div>
            <?php endif; ?>
          </td>
          <td><strong style="font-size:.9rem;"><?= htmlspecialchars($p['nombre']) ?></strong></td>
          <td style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></td>
          <td class="text-money fw-bold" style="color:var(--accent);">Bs <?= number_format($p['precio'], 2) ?></td>
          <td>
            <span class="stock-indicator <?= $sc ?>">
              <span class="stock-dot"></span> <?= $p['stock'] ?>
            </span>
          </td>
          <td>
            <span class="badge-status <?= $p['estado'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $p['estado'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-primary"
                      onclick="editProducto(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"
                      title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-outline-secondary"
                      onclick="openStockModal(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['nombre']), ENT_QUOTES) ?>, <?= (int)$p['stock'] ?>)"
                      title="Stock">
                <i class="bi bi-arrow-repeat"></i>
              </button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este producto y sus imágenes?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$p['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ===== Modal Crear/Editar ===== -->
<div class="modal fade" id="modalProducto" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data" id="formProducto">
        <input type="hidden" name="action" id="productoAction" value="create">
        <input type="hidden" name="id"     id="productoId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="modalProductoTitle"><i class="bi bi-plus-lg"></i> Nuevo Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nombre del producto *</label>
              <input type="text" name="nombre" id="prodNombre" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-4">
              <label class="form-label">Categoría *</label>
              <select name="categoria_id" id="prodCategoria" class="form-select" required>
                <option value="">Seleccionar...</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Precio *</label>
              <div class="input-group">
                <span class="input-group-text">Bs</span>
                <input type="number" name="precio" id="prodPrecio" class="form-control"
                       step="0.01" min="0.01" required>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Stock</label>
              <input type="number" name="stock" id="prodStock" class="form-control" min="0" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Estado</label>
              <select name="estado" id="prodEstado" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea name="descripcion" id="prodDescripcion" class="form-control" rows="3" maxlength="1000"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">
                Imágenes
                <small class="text-muted fw-normal">(máx. <?= MAX_IMAGES ?> · jpg, png, webp, gif)</small>
              </label>
              <input type="file" name="imagenes[]" id="imagenInput" class="form-control"
                     multiple accept="image/jpeg,image/png,image/gif,image/webp">
              <div class="img-preview-container" id="existingImages"></div>
              <div class="img-preview-container" id="newImagePreview"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== Modal Stock ===== -->
<div class="modal fade" id="modalStock" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="update_stock">
        <input type="hidden" name="id" id="stockProductoId">
        <div class="modal-header">
          <h5 class="modal-title">Actualizar Stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p id="stockProductoNombre" class="fw-bold mb-1" style="font-size:.9rem;"></p>
          <p class="mb-3" style="font-size:.85rem;color:var(--text-muted);">
            Stock actual: <strong id="stockActual"></strong>
          </p>
          <label class="form-label">Cantidad a agregar / descontar</label>
          <input type="number" name="cantidad" class="form-control" required
                 placeholder="Ej: 10 agregar · -5 descontar">
          <small class="text-muted">Valor negativo para descontar stock</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-save"></i> Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  initTableSearch('searchInput', 'tablaProductos', [1, 2]);

  // Preview de nuevas imágenes
  var imagenInput = document.getElementById('imagenInput');
  if (imagenInput) {
    imagenInput.addEventListener('change', function(){
      var container = document.getElementById('newImagePreview');
      container.innerHTML = '';
      Array.from(this.files).forEach(function(file){
        var reader = new FileReader();
        reader.onload = function(e){
          var div = document.createElement('div');
          div.className = 'img-preview-item';
          div.innerHTML = '<img src="' + e.target.result + '" alt="preview">';
          container.appendChild(div);
        };
        reader.readAsDataURL(file);
      });
    });
  }

  // Reset modal al cerrar
  document.getElementById('modalProducto').addEventListener('hidden.bs.modal', function(){
    document.getElementById('formProducto').reset();
    document.getElementById('productoAction').value = 'create';
    document.getElementById('productoId').value     = '';
    document.getElementById('modalProductoTitle').innerHTML = '<i class="bi bi-plus-lg"></i> Nuevo Producto';
    document.getElementById('existingImages').innerHTML  = '';
    document.getElementById('newImagePreview').innerHTML = '';
  });
});

function editProducto(p) {
  document.getElementById('productoAction').value    = 'update';
  document.getElementById('productoId').value        = p.id;
  document.getElementById('prodNombre').value        = p.nombre || '';
  document.getElementById('prodCategoria').value     = p.categoria_id || '';
  document.getElementById('prodPrecio').value        = p.precio || '';
  document.getElementById('prodStock').value         = p.stock || 0;
  document.getElementById('prodEstado').value        = p.estado !== undefined ? p.estado : 1;
  document.getElementById('prodDescripcion').value   = p.descripcion || '';
  document.getElementById('modalProductoTitle').innerHTML = '<i class="bi bi-pencil"></i> Editar Producto';
  document.getElementById('newImagePreview').innerHTML    = '';

  var existingContainer = document.getElementById('existingImages');
  existingContainer.innerHTML = '<small style="color:var(--text-muted);font-size:.78rem;">Cargando imágenes...</small>';

  fetch(BASE_URL + '/admin/get_imagenes.php?producto_id=' + p.id)
    .then(function(r){ return r.json(); })
    .then(function(imgs){
      existingContainer.innerHTML = '';
      if (imgs.length > 0) {
        var label = document.createElement('small');
        label.style.cssText = 'color:var(--text-muted);font-size:.78rem;width:100%;display:block;margin-bottom:4px;';
        label.textContent = 'Imágenes actuales (' + imgs.length + '):';
        existingContainer.appendChild(label);
      }
      imgs.forEach(function(img){
        var div = document.createElement('div');
        div.className = 'img-preview-item';
        div.id = 'img-item-' + img.id;
        div.innerHTML = '<img src="' + BASE_URL + '/uploads/productos/' + img.ruta_imagen + '" alt="img">'
          + '<button type="button" class="img-preview-delete" onclick="deleteImagen(' + img.id + ',' + p.id + ',\'img-item-' + img.id + '\')">'
          + '<i class="bi bi-x"></i></button>';
        existingContainer.appendChild(div);
      });
    })
    .catch(function(){ existingContainer.innerHTML = ''; });

  new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

function deleteImagen(imgId, prodId, divId) {
  if (!confirm('¿Eliminar esta imagen?')) return;
  var fd = new FormData();
  fd.append('action', 'delete_imagen');
  fd.append('imagen_id', imgId);
  fd.append('producto_id', prodId);
  fetch(window.location.pathname, { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (data.success) {
        var el = document.getElementById(divId);
        if (el) el.remove();
        showToast('Imagen eliminada', 'success');
      }
    })
    .catch(function(){ showToast('Error al eliminar imagen', 'danger'); });
}

function openStockModal(id, nombre, stock) {
  document.getElementById('stockProductoId').value    = id;
  document.getElementById('stockProductoNombre').textContent = nombre;
  document.getElementById('stockActual').textContent  = stock;
  new bootstrap.Modal(document.getElementById('modalStock')).show();
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
