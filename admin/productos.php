<?php
// admin/productos.php
$pageTitle = 'Productos';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';
require_once __DIR__ . '/../models/ProductoColor.php';

$ctrl       = new ProductoController();
$catCtrl    = new CategoriaController();
$colorModel = new ProductoColor();
$categorias = $catCtrl->getAll(true);

/* ---- AJAX: colores de un producto ---- */
if ($_SERVER['REQUEST_METHOD']==='GET' && ($_GET['action']??'')==='get_colores') {
    header('Content-Type: application/json');
    echo json_encode($colorModel->getByProducto(intval($_GET['producto_id']??0)));
    exit;
}

/* ---- AJAX: guardar colores ---- */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='save_colores' && isAdmin()) {
    header('Content-Type: application/json');
    $pid    = intval($_POST['producto_id'] ?? 0);
    $colores = $_POST['colores'] ?? [];
    $ok = true;

    // IDs que vienen del form (para detectar eliminados)
    $idsEnviados = [];
    foreach ($colores as $c) {
        $cid = intval($c['id'] ?? 0);
        $col = trim($c['color'] ?? '');
        $hex = trim($c['hex']   ?? '#6b7280');
        $doc = max(0, intval($c['docenas']  ?? 0));
        $uni = max(0, intval($c['unidades'] ?? 0));
        if (!$col) continue;
        $newId = $colorModel->upsert($pid, $col, $hex, $doc, $uni);
        if ($newId) $idsEnviados[] = $newId;
        else $ok = false;
    }

    // Eliminar colores que no vinieron (fueron borrados en el UI)
    $existentes = $colorModel->getByProducto($pid);
    foreach ($existentes as $ex) {
        if (!in_array((int)$ex['id'], $idsEnviados)) {
            $colorModel->delete((int)$ex['id']);
        }
    }

    // Recalcular stock del producto
    (new \Producto())->updateStockDesdeColores($pid);

    echo json_encode(['success' => $ok]);
    exit;
}

/* ---- Acciones admin ---- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isAdmin()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $r = $ctrl->create($_POST, $_FILES);
        setFlash($r['success']?'success':'error', $r['message']);
        redirect(BASE_URL . '/admin/productos.php');
    } elseif ($action === 'update') {
        $r = $ctrl->update(intval($_POST['id']), $_POST, $_FILES);
        setFlash($r['success']?'success':'error', $r['message']);
        redirect(BASE_URL . '/admin/productos.php');
    } elseif ($action === 'delete') {
        $r = $ctrl->delete(intval($_POST['id']));
        setFlash($r['success']?'success':'error', $r['message']);
        redirect(BASE_URL . '/admin/productos.php');
    } elseif ($action === 'delete_imagen') {
        header('Content-Type: application/json');
        echo json_encode(['success'=>(bool)$ctrl->deleteImagen(intval($_POST['imagen_id']),intval($_POST['producto_id']))]);
        exit;
    }
}

/* ---- Actualizar stock ---- */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update_stock') {
    if (isset($_POST['stock_docenas'])) {
        $r = $ctrl->updateStockDocenas(intval($_POST['id']),intval($_POST['stock_docenas']),intval($_POST['stock_unidades']??0));
    } else {
        $r = $ctrl->updateStock(intval($_POST['id']),intval($_POST['cantidad']));
    }
    setFlash($r['success']?'success':'error', $r['message']);
    redirect(BASE_URL . '/admin/productos.php');
}

$productos = $ctrl->getAll();
$stats     = $ctrl->getStats();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-sm-4 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-boxes"></i></div>
      <div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Productos</div></div>
    </div>
  </div>
  <div class="col-6 col-sm-4 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
      <div><div class="stat-value"><?= $stats['activos'] ?></div><div class="stat-label">Activos</div></div>
    </div>
  </div>
  <div class="col-6 col-sm-4 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
      <div><div class="stat-value"><?= $stats['sinStock'] ?></div><div class="stat-label">Sin stock</div></div>
    </div>
  </div>
  <div class="col-6 col-sm-4 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-exclamation-triangle"></i></div>
      <div><div class="stat-value"><?= $stats['critico'] ?></div><div class="stat-label">Stock crítico</div></div>
    </div>
  </div>
  <?php if (isAdmin()): ?>
  <div class="col-6 col-sm-4 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-cash-coin"></i></div>
      <div><div class="stat-value" style="font-size:.95rem;">Bs.<?= number_format($stats['valVenta'],0) ?></div><div class="stat-label">Val. inventario</div></div>
    </div>
  </div>
  <div class="col-6 col-sm-4 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
      <div><div class="stat-value" style="font-size:.95rem;">Bs.<?= number_format($stats['ganMes'],0) ?></div><div class="stat-label">Gan. este mes</div></div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ===== TABLA PRODUCTOS ===== -->
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
      <i class="bi bi-box-seam"></i><p>No hay productos registrados</p>
      <?php if (isAdmin()): ?>
      <button class="btn btn-primary mt-3" onclick="abrirNuevo()"><i class="bi bi-plus-lg"></i> Crear producto</button>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaProductos">
      <thead>
        <tr>
          <th style="width:44px;"></th>
          <th>Nombre</th>
          <th class="table-hide-mobile">Categoría</th>
          <?php if (isAdmin()): ?>
          <th class="table-hide-tablet">P. Compra</th>
          <th class="table-hide-tablet">P. Doc.</th>
          <?php endif; ?>
          <th>P. Venta</th>
          <th>Docenas</th>
          <th>Unidades</th>
          <th class="table-hide-mobile">Colores</th>
          <th class="table-hide-mobile">Estado</th>
          <th style="width:<?= isAdmin()?'160':'110' ?>px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($productos as $p):
        $doc  = (int)($p['stock_docenas']  ?? 0);
        $uni  = (int)($p['stock_unidades'] ?? 0);
        $upd  = max(1,(int)($p['unidades_por_docena'] ?? 12));
        $tot  = ($doc*$upd)+$uni;
        $sc   = $tot>10?'stock-high':($tot>3?'stock-medium':($tot>0?'stock-low':'stock-out'));
        $pv   = floatval($p['precio_venta']  ?? 0);
        $pc   = floatval($p['precio_compra'] ?? 0);
        $pd   = floatval($p['precio_docena'] ?? 0);
        $colores = $colorModel->getActivosByProducto((int)$p['id']);
        $pJson = htmlspecialchars(json_encode([
          'id'               => (int)$p['id'],
          'nombre'           => $p['nombre'],
          'descripcion'      => $p['descripcion'] ?? '',
          'categoria_id'     => (int)$p['categoria_id'],
          'categoria_nombre' => $p['categoria_nombre'] ?? '',
          'imagen_principal' => $p['imagen_principal'] ?? '',
          'precio_compra'    => $pc,
          'precio_venta'     => $pv,
          'precio_docena'    => $pd,
          'stock'            => (int)$p['stock'],
          'stock_docenas'    => $doc,
          'stock_unidades'   => $uni,
          'unidades_por_docena' => $upd,
          'estado'           => (int)$p['estado'],
        ]), ENT_QUOTES);
      ?>
      <tr>
        <td>
          <?php if ($p['imagen_principal']): ?>
            <img src="<?= UPLOAD_URL.htmlspecialchars(basename($p['imagen_principal'])) ?>"
                 style="width:38px;height:38px;object-fit:cover;border-radius:7px;border:1px solid var(--border-color);">
          <?php else: ?>
            <div style="width:38px;height:38px;border-radius:7px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);"><i class="bi bi-image"></i></div>
          <?php endif; ?>
        </td>
        <td>
          <strong style="font-size:.88rem;"><?= htmlspecialchars($p['nombre']) ?></strong>
          <div class="d-md-none" style="font-size:.70rem;color:var(--text-muted);">
            <?= htmlspecialchars($p['categoria_nombre']??'') ?>
          </div>
        </td>
        <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($p['categoria_nombre']??'—') ?></td>
        <?php if (isAdmin()): ?>
        <td class="table-hide-tablet" style="font-size:.83rem;color:var(--text-secondary);">Bs.<?= number_format($pc,2) ?></td>
        <td class="table-hide-tablet" style="font-size:.83rem;color:var(--text-secondary);"><?= $pd>0?'Bs.'.number_format($pd,2):'—' ?></td>
        <?php endif; ?>
        <td class="text-money fw-bold" style="color:var(--accent);font-size:.88rem;">Bs.<?= number_format($pv,2) ?></td>
        <td>
          <span style="font-weight:700;color:var(--accent);"><?= $doc ?></span>
          <small style="color:var(--text-muted);font-size:.68rem;"> doc</small>
        </td>
        <td>
          <span class="stock-indicator <?= $sc ?>"><span class="stock-dot"></span><?= $uni ?><small style="color:var(--text-muted);font-size:.68rem;"> uni</small></span>
        </td>
        <td class="table-hide-mobile">
          <?php if (empty($colores)): ?>
            <span style="color:var(--text-muted);font-size:.75rem;">—</span>
          <?php else: ?>
            <div style="display:flex;gap:3px;flex-wrap:wrap;align-items:center;">
              <?php foreach (array_slice($colores,0,5) as $col): ?>
                <span title="<?= htmlspecialchars($col['color']) ?> (<?= $col['docenas'] ?>d+<?= $col['unidades'] ?>u)"
                      style="display:inline-block;width:16px;height:16px;border-radius:50%;background:<?= htmlspecialchars($col['hex_code']) ?>;border:1.5px solid rgba(0,0,0,.15);flex-shrink:0;"></span>
              <?php endforeach; ?>
              <?php if (count($colores)>5): ?>
                <small style="color:var(--text-muted);font-size:.68rem;">+<?= count($colores)-5 ?></small>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </td>
        <td class="table-hide-mobile">
          <span class="badge-status <?= $p['estado']?'badge-active':'badge-inactive' ?>">
            <?= $p['estado']?'Activo':'Inactivo' ?>
          </span>
        </td>
        <td>
          <div class="d-flex gap-1 flex-wrap">
            <button class="btn btn-sm btn-outline-info"     onclick="verProducto(<?= $pJson ?>)" title="Ver"><i class="bi bi-eye"></i></button>
            <button class="btn btn-sm btn-outline-secondary" onclick="abrirStock(<?= (int)$p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nombre'])) ?>',<?= $doc ?>,<?= $uni ?>,<?= $upd ?>)" title="Stock"><i class="bi bi-arrow-repeat"></i></button>
            <?php if (isAdmin()): ?>
            <button class="btn btn-sm btn-outline-warning"  onclick="abrirColores(<?= (int)$p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nombre'])) ?>')" title="Colores"><i class="bi bi-palette"></i></button>
            <button class="btn btn-sm btn-outline-primary"  onclick="editProducto(<?= $pJson ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger"   onclick="eliminarProducto(<?= (int)$p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nombre'])) ?>')" title="Eliminar"><i class="bi bi-trash"></i></button>
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

<form method="POST" id="formDel" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="fDelId">
</form>

<!-- ===== MODAL VER ===== -->
<div class="modal fade" id="modalVer" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye"></i> Detalle del Producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="verBody"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-outline-secondary" id="btnVerStock"><i class="bi bi-arrow-repeat"></i> Actualizar Stock</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL STOCK ===== -->
<div class="modal fade" id="modalStock" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="formStock">
        <input type="hidden" name="action" value="update_stock">
        <input type="hidden" name="id" id="sId">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Actualizar Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p id="sNombre" class="fw-bold mb-3" style="font-size:.95rem;"></p>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-bold"><i class="bi bi-collection" style="color:var(--accent);"></i> Docenas</label>
              <input type="number" name="stock_docenas" id="sDocenas" class="form-control form-control-lg" min="0" placeholder="0">
              <small style="color:var(--text-muted);font-size:.74rem;" id="sPorDocena"></small>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold"><i class="bi bi-box" style="color:var(--success);"></i> Unidades sueltas</label>
              <input type="number" name="stock_unidades" id="sUnidades" class="form-control form-control-lg" min="0" placeholder="0">
            </div>
          </div>
          <div class="mt-3 p-2" style="background:var(--bg-primary);border-radius:var(--radius-sm);font-size:.84rem;">
            Actual: <strong id="sActualDoc" style="color:var(--accent);"></strong> doc +
            <strong id="sActualUni" style="color:var(--success);"></strong> uni =
            <strong id="sActualTotal"></strong> unidades
          </div>
          <div class="mt-2 p-2" style="background:var(--warning-light);border-radius:var(--radius-sm);font-size:.78rem;color:var(--warning);">
            <i class="bi bi-info-circle me-1"></i> Si el producto tiene colores, usa el botón <strong>Colores</strong> para ajustar el stock por color.
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarStock"><i class="bi bi-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== MODAL COLORES ===== -->
<div class="modal fade" id="modalColores" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom-color:var(--accent);">
        <h5 class="modal-title"><i class="bi bi-palette"></i> Colores — <span id="colorProdNombre"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="colorProdId">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <p style="color:var(--text-muted);font-size:.84rem;margin:0;">
            Registra cuántas docenas y unidades hay de cada color. El stock total del producto se calculará automáticamente.
          </p>
          <button type="button" class="btn btn-sm btn-primary" onclick="agregarFilaColor()">
            <i class="bi bi-plus-lg"></i> Agregar color
          </button>
        </div>

        <div id="coloresTotalesInfo" class="mb-3" style="display:none;">
          <div class="p-2" style="background:var(--accent-light);border-radius:var(--radius-sm);font-size:.84rem;">
            Total calculado: <strong id="colorTotalDoc" style="color:var(--accent);"></strong> doc +
            <strong id="colorTotalUni" style="color:var(--success);"></strong> uni =
            <strong id="colorTotalGeneral"></strong> unidades
          </div>
        </div>

        <div id="coloresContainer">
          <!-- Filas de colores se insertan aquí -->
        </div>

        <div id="coloresEmpty" class="text-center py-4" style="color:var(--text-muted);">
          <i class="bi bi-palette" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
          <p>Agrega colores para desglosar el stock de esta docena</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary fw-bold" id="btnGuardarColores" onclick="guardarColores()">
          <i class="bi bi-save"></i> Guardar Colores
        </button>
      </div>
    </div>
  </div>
</div>

<?php if (isAdmin()): ?>
<!-- ===== MODAL CREAR/EDITAR ===== -->
<div class="modal fade" id="modalProducto" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
              <input type="text" name="nombre" id="pNombre" class="form-control" required maxlength="150" placeholder="Nombre del producto">
              <div class="invalid-feedback">El nombre es obligatorio</div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Categoría *</label>
              <select name="categoria_id" id="pCategoria" class="form-select" required>
                <option value="">Seleccionar...</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12"><hr class="divider my-1"><small class="text-section-label">Precios</small></div>
            <div class="col-12 col-sm-4">
              <label class="form-label">Precio Compra</label>
              <div class="input-group"><span class="input-group-text">Bs.</span>
              <input type="number" name="precio_compra" id="pCompra" class="form-control" step="0.01" min="0" value="0"></div>
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label">Precio Venta (unidad) *</label>
              <div class="input-group"><span class="input-group-text">Bs.</span>
              <input type="number" name="precio_venta" id="pVenta" class="form-control" step="0.01" min="0.01" required placeholder="0.00"></div>
              <div class="invalid-feedback">Precio mayor a 0</div>
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label">Precio Docena <small style="color:var(--text-muted);">(opcional)</small></label>
              <div class="input-group"><span class="input-group-text">Bs.</span>
              <input type="number" name="precio_docena" id="pDocena" class="form-control" step="0.01" min="0" value="0"></div>
            </div>

            <div class="col-12"><hr class="divider my-1"><small class="text-section-label">Stock inicial</small></div>
            <div class="col-12 col-sm-4">
              <label class="form-label">Unidades por docena</label>
              <input type="number" name="unidades_por_docena" id="pUPD" class="form-control" min="1" value="12">
            </div>
            <div class="col-6 col-sm-4">
              <label class="form-label"><i class="bi bi-collection" style="color:var(--accent);"></i> Docenas</label>
              <input type="number" name="stock_docenas" id="pDocenas" class="form-control" min="0" value="0">
            </div>
            <div class="col-6 col-sm-4">
              <label class="form-label"><i class="bi bi-box" style="color:var(--success);"></i> Unidades sueltas</label>
              <input type="number" name="stock_unidades" id="pUnidades" class="form-control" min="0" value="0">
            </div>
            <div id="wrapStockEdit" style="display:none;" class="col-12">
              <div class="p-2" style="background:var(--bg-primary);border-radius:var(--radius-sm);font-size:.83rem;">
                Stock actual: <strong id="pDocActual" style="color:var(--accent);"></strong> doc +
                <strong id="pUniActual" style="color:var(--success);"></strong> uni
              </div>
            </div>

            <div class="col-12 col-sm-4">
              <label class="form-label">Estado</label>
              <select name="estado" id="pEstado" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea name="descripcion" id="pDesc" class="form-control" rows="2" maxlength="1000" placeholder="Descripción opcional..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Imágenes <small style="color:var(--text-muted);font-weight:400;">(máx <?= MAX_IMAGES ?> · jpg,png,webp)</small></label>
              <input type="file" name="imagenes[]" id="pImgs" class="form-control" multiple accept="image/jpeg,image/png,image/gif,image/webp">
              <div id="imgExist" class="img-preview-container"></div>
              <div id="imgNew"   class="img-preview-container"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardProd"><i class="bi bi-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
.text-section-label{color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;font-size:.72rem;font-weight:700;}
.color-fila{display:grid;grid-template-columns:36px 1fr 2fr 2fr 2fr auto;gap:.4rem;align-items:center;padding:.4rem .5rem;background:var(--bg-primary);border-radius:var(--radius-sm);margin-bottom:.35rem;border:1px solid var(--border-color);}
@media(max-width:576px){
  .color-fila{grid-template-columns:28px 1fr 1fr;gap:.3rem;}
  .color-fila .hide-xs{display:none;}
}
</style>

<script>
var _productoActual = null;

document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('searchInput','tablaProductos',[1,2]);

  document.getElementById('btnVerStock').addEventListener('click', function() {
    bootstrap.Modal.getInstance(document.getElementById('modalVer')).hide();
    if (_productoActual) setTimeout(function(){
      abrirStock(_productoActual.id,_productoActual.nombre,_productoActual.stock_docenas||0,_productoActual.stock_unidades||0,_productoActual.unidades_por_docena||12);
    }, 350);
  });

  document.getElementById('formStock').addEventListener('submit', function(e) {
    document.getElementById('btnGuardarStock').disabled=true;
    document.getElementById('btnGuardarStock').innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });

  <?php if (isAdmin()): ?>
  document.getElementById('pImgs').addEventListener('change', function() {
    var cont=document.getElementById('imgNew'); cont.innerHTML='';
    var libre=<?= MAX_IMAGES ?>-document.getElementById('imgExist').querySelectorAll('.img-preview-item').length;
    Array.from(this.files).slice(0,libre).forEach(function(f){
      var r=new FileReader(); r.onload=function(e){
        var d=document.createElement('div'); d.className='img-preview-item';
        d.innerHTML='<img src="'+e.target.result+'" alt="">'; cont.appendChild(d);
      }; r.readAsDataURL(f);
    });
  });

  document.getElementById('formProd').addEventListener('submit', function(e) {
    var nb=document.getElementById('pNombre'),ct=document.getElementById('pCategoria'),pv=document.getElementById('pVenta');
    [nb,ct,pv].forEach(function(el){el.classList.remove('is-invalid');});
    var ok=true;
    if(!nb.value.trim()){nb.classList.add('is-invalid');ok=false;}
    if(!ct.value){ct.classList.add('is-invalid');ok=false;}
    if(!pv.value||parseFloat(pv.value)<=0){pv.classList.add('is-invalid');ok=false;}
    if(!ok){e.preventDefault();return;}
    document.getElementById('btnGuardProd').disabled=true;
    document.getElementById('btnGuardProd').innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });

  document.getElementById('modalProducto').addEventListener('hidden.bs.modal', resetFormProd);
  <?php endif; ?>
});

function resetFormProd() {
  document.getElementById('formProd').reset();
  document.getElementById('pAction').value='create';
  document.getElementById('pId').value='';
  document.getElementById('tituloProd').innerHTML='<i class="bi bi-plus-lg"></i> Nuevo Producto';
  document.getElementById('imgExist').innerHTML='';
  document.getElementById('imgNew').innerHTML='';
  document.getElementById('wrapStockEdit').style.display='none';
  document.getElementById('btnGuardProd').disabled=false;
  document.getElementById('btnGuardProd').innerHTML='<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formProd .is-invalid').forEach(function(el){el.classList.remove('is-invalid');});
}

function verProducto(p) {
  _productoActual=p;
  document.getElementById('verBody').innerHTML='<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVer')).show();
  var esAdmin=<?= isAdmin()?'true':'false' ?>;
  fetch(BASE_URL+'/admin/get_imagenes.php?producto_id='+p.id)
    .then(function(r){return r.json();})
    .then(function(imgs){
      var html='';
      if(imgs.length){
        html+='<div style="margin-bottom:1rem;">';
        if(imgs.length===1){
          html+='<img src="'+BASE_URL+'/uploads/productos/'+imgs[0].ruta_imagen+'" style="width:100%;max-height:240px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border-color);">';
        } else {
          html+='<img id="verMainImg" src="'+BASE_URL+'/uploads/productos/'+imgs[0].ruta_imagen+'" style="width:100%;max-height:220px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border-color);">';
          html+='<div style="display:flex;gap:.4rem;margin-top:.5rem;flex-wrap:wrap;">';
          imgs.forEach(function(img){
            html+='<img src="'+BASE_URL+'/uploads/productos/'+img.ruta_imagen+'" onclick="document.getElementById(\'verMainImg\').src=this.src;" style="width:56px;height:56px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid var(--border-color);">';
          });
          html+='</div>';
        }
        html+='</div>';
      }
      var upd=p.unidades_por_docena||12; var doc=p.stock_docenas||0; var uni=p.stock_unidades||0;
      var tot=(doc*upd)+uni; var sc=tot>10?'stock-high':(tot>3?'stock-medium':(tot>0?'stock-low':'stock-out'));
      html+='<table class="table table-sm" style="font-size:.87rem;">';
      html+='<tr><th style="width:40%;color:var(--text-muted);">Nombre</th><td><strong>'+p.nombre+'</strong></td></tr>';
      html+='<tr><th style="color:var(--text-muted);">Categoría</th><td>'+(p.categoria_nombre||'—')+'</td></tr>';
      if(esAdmin) html+='<tr><th style="color:var(--text-muted);">P. Compra</th><td class="text-money">Bs.'+parseFloat(p.precio_compra||0).toFixed(2)+'</td></tr>';
      html+='<tr><th style="color:var(--text-muted);">P. Venta</th><td class="text-money fw-bold" style="color:var(--accent);">Bs.'+parseFloat(p.precio_venta||0).toFixed(2)+'</td></tr>';
      if(parseFloat(p.precio_docena||0)>0) html+='<tr><th style="color:var(--text-muted);">P. Docena</th><td class="text-money fw-bold" style="color:var(--info);">Bs.'+parseFloat(p.precio_docena||0).toFixed(2)+'</td></tr>';
      if(esAdmin){var gan=parseFloat(p.precio_venta||0)-parseFloat(p.precio_compra||0);html+='<tr><th style="color:var(--text-muted);">Ganancia/uni</th><td class="text-money" style="color:var(--success);">Bs.'+gan.toFixed(2)+'</td></tr>';}
      html+='<tr><th style="color:var(--text-muted);">Stock</th><td><span class="stock-indicator '+sc+'"><span class="stock-dot"></span>'+doc+' doc + '+uni+' uni = <strong>'+tot+'</strong> uni</span></td></tr>';
      html+='</table>';

      // Colores
      fetch(BASE_URL+'/admin/productos.php?action=get_colores&producto_id='+p.id)
        .then(function(r){return r.json();})
        .then(function(cols){
          if(cols.length){
            html='<div id="verBodyColoresInner">'+html;
            html+='<hr class="divider"><strong style="font-size:.85rem;"><i class="bi bi-palette me-1" style="color:var(--accent);"></i>Colores disponibles</strong>';
            html+='<div style="display:flex;flex-direction:column;gap:.3rem;margin-top:.5rem;">';
            cols.forEach(function(c){
              var tot2=(parseInt(c.docenas)||0);var uni2=(parseInt(c.unidades)||0);
              html+='<div style="display:flex;align-items:center;gap:.5rem;padding:.3rem .5rem;background:var(--bg-primary);border-radius:var(--radius-sm);">'
                +'<span style="width:18px;height:18px;border-radius:50%;background:'+c.hex_code+';border:1.5px solid rgba(0,0,0,.15);flex-shrink:0;"></span>'
                +'<span style="font-weight:600;font-size:.86rem;flex:1;">'+c.color+'</span>'
                +'<span style="font-size:.78rem;color:var(--text-muted);">'+tot2+' doc · '+uni2+' uni</span>'
                +'</div>';
            });
            html+='</div></div>';
          }
          document.getElementById('verBody').innerHTML=html;
        })
        .catch(function(){document.getElementById('verBody').innerHTML=html;});
    });
}

function abrirStock(id,nombre,doc,uni,upd) {
  _productoActual={id:id,nombre:nombre,stock_docenas:doc,stock_unidades:uni,unidades_por_docena:upd};
  document.getElementById('sId').value=id;
  document.getElementById('sNombre').textContent=nombre;
  document.getElementById('sDocenas').value=doc;
  document.getElementById('sUnidades').value=uni;
  document.getElementById('sPorDocena').textContent='('+upd+' unidades por docena)';
  document.getElementById('sActualDoc').textContent=doc;
  document.getElementById('sActualUni').textContent=uni;
  document.getElementById('sActualTotal').textContent=(doc*upd+uni);
  document.getElementById('btnGuardarStock').disabled=false;
  document.getElementById('btnGuardarStock').innerHTML='<i class="bi bi-save"></i> Guardar';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalStock')).show();
}

/* ===== COLORES ===== */
var _colorProdId = null;

function abrirColores(pid, nombre) {
  _colorProdId = pid;
  document.getElementById('colorProdId').value   = pid;
  document.getElementById('colorProdNombre').textContent = nombre;
  document.getElementById('coloresContainer').innerHTML = '';
  document.getElementById('coloresEmpty').style.display = '';
  document.getElementById('coloresTotalesInfo').style.display = 'none';
  document.getElementById('btnGuardarColores').disabled = false;
  document.getElementById('btnGuardarColores').innerHTML = '<i class="bi bi-save"></i> Guardar Colores';

  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalColores')).show();

  fetch(BASE_URL+'/admin/productos.php?action=get_colores&producto_id='+pid)
    .then(function(r){return r.json();})
    .then(function(cols){
      document.getElementById('coloresContainer').innerHTML='';
      if(cols.length){
        document.getElementById('coloresEmpty').style.display='none';
        cols.forEach(function(c){ agregarFilaColor(c); });
        recalcularTotalesColores();
      }
    });
}

function agregarFilaColor(data) {
  data = data || {};
  var id = Date.now() + Math.random();
  var cont = document.getElementById('coloresContainer');
  document.getElementById('coloresEmpty').style.display='none';
  document.getElementById('coloresTotalesInfo').style.display='';

  var fila = document.createElement('div');
  fila.className = 'color-fila';
  fila.dataset.rowid = id;
  fila.innerHTML =
    '<input type="color" value="'+(data.hex_code||'#6b7280')+'" '
    +'style="width:32px;height:32px;border:none;border-radius:6px;cursor:pointer;padding:1px;" '
    +'onchange="actualizarHexFila(this)" '
    +'title="Color">'
    +'<input type="hidden" class="fila-id"    value="'+(data.id||'')+'">'
    +'<input type="hidden" class="fila-hex"   value="'+(data.hex_code||'#6b7280')+'">'
    +'<input type="text"   class="form-control form-control-sm fila-color" '
    +'placeholder="Ej: Negro" value="'+(data.color||'')+'" maxlength="80" '
    +'oninput="recalcularTotalesColores()" '
    +'title="Nombre del color">'
    +'<div class="hide-xs">'
    +'<label style="font-size:.68rem;color:var(--text-muted);display:block;margin-bottom:2px;"><i class="bi bi-collection" style="color:var(--accent);"></i> Docenas</label>'
    +'<input type="number" class="form-control form-control-sm fila-docenas" min="0" value="'+(data.docenas||0)+'" '
    +'oninput="recalcularTotalesColores()" style="max-width:80px;">'
    +'</div>'
    +'<div>'
    +'<label style="font-size:.68rem;color:var(--text-muted);display:block;margin-bottom:2px;"><i class="bi bi-box" style="color:var(--success);"></i> Unidades</label>'
    +'<input type="number" class="form-control form-control-sm fila-unidades" min="0" value="'+(data.unidades||0)+'" '
    +'oninput="recalcularTotalesColores()" style="max-width:80px;">'
    +'</div>'
    +'<button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarFilaColor(this)" title="Quitar"><i class="bi bi-x"></i></button>';

  cont.appendChild(fila);
  recalcularTotalesColores();
}

function actualizarHexFila(input) {
  var fila = input.closest('.color-fila');
  fila.querySelector('.fila-hex').value = input.value;
}

function eliminarFilaColor(btn) {
  btn.closest('.color-fila').remove();
  recalcularTotalesColores();
  if(!document.querySelector('.color-fila')){
    document.getElementById('coloresEmpty').style.display='';
    document.getElementById('coloresTotalesInfo').style.display='none';
  }
}

function recalcularTotalesColores() {
  var totalDoc=0, totalUni=0;
  document.querySelectorAll('.color-fila').forEach(function(fila){
    totalDoc += parseInt(fila.querySelector('.fila-docenas').value)||0;
    totalUni += parseInt(fila.querySelector('.fila-unidades').value)||0;
  });
  var upd = <?php echo 'parseInt(document.getElementById("pUPD")?.value||12)' ?>;
  // tomar upd del producto actual
  document.getElementById('colorTotalDoc').textContent=totalDoc;
  document.getElementById('colorTotalUni').textContent=totalUni;
  document.getElementById('colorTotalGeneral').textContent=(totalDoc*12+totalUni)+' aprox.';
}

function guardarColores() {
  var pid  = document.getElementById('colorProdId').value;
  var btn  = document.getElementById('btnGuardarColores');
  var filas = document.querySelectorAll('.color-fila');
  var colores = [];
  var ok = true;

  filas.forEach(function(fila){
    var color = fila.querySelector('.fila-color').value.trim();
    if(!color){ fila.querySelector('.fila-color').classList.add('is-invalid'); ok=false; return; }
    fila.querySelector('.fila-color').classList.remove('is-invalid');
    colores.push({
      id:       fila.querySelector('.fila-id').value,
      color:    color,
      hex:      fila.querySelector('.fila-hex').value,
      docenas:  fila.querySelector('.fila-docenas').value||0,
      unidades: fila.querySelector('.fila-unidades').value||0,
    });
  });
  if(!ok){ showToast('Completa el nombre de todos los colores','warning'); return; }

  btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

  var fd = new FormData();
  fd.append('action','save_colores');
  fd.append('producto_id',pid);
  colores.forEach(function(c,i){
    fd.append('colores['+i+'][id]',       c.id);
    fd.append('colores['+i+'][color]',    c.color);
    fd.append('colores['+i+'][hex]',      c.hex);
    fd.append('colores['+i+'][docenas]',  c.docenas);
    fd.append('colores['+i+'][unidades]', c.unidades);
  });

  fetch(location.pathname, {method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.success){
        showToast('Colores guardados correctamente','success',3000);
        bootstrap.Modal.getInstance(document.getElementById('modalColores')).hide();
        setTimeout(function(){location.reload();},800);
      } else {
        showToast('Error al guardar colores','danger');
        btn.disabled=false; btn.innerHTML='<i class="bi bi-save"></i> Guardar Colores';
      }
    }).catch(function(){
      showToast('Error de conexión','danger');
      btn.disabled=false; btn.innerHTML='<i class="bi bi-save"></i> Guardar Colores';
    });
}

<?php if (isAdmin()): ?>
function abrirNuevo() {
  resetFormProd();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto')).show();
}

function editProducto(p) {
  document.getElementById('pAction').value='update';
  document.getElementById('pId').value=p.id;
  document.getElementById('pNombre').value=p.nombre||'';
  document.getElementById('pCategoria').value=p.categoria_id||'';
  document.getElementById('pCompra').value=p.precio_compra||0;
  document.getElementById('pVenta').value=p.precio_venta||0;
  document.getElementById('pDocena').value=p.precio_docena||0;
  document.getElementById('pUPD').value=p.unidades_por_docena||12;
  document.getElementById('pDocenas').value=p.stock_docenas||0;
  document.getElementById('pUnidades').value=p.stock_unidades||0;
  document.getElementById('pDocActual').textContent=p.stock_docenas||0;
  document.getElementById('pUniActual').textContent=p.stock_unidades||0;
  document.getElementById('wrapStockEdit').style.display='';
  document.getElementById('pEstado').value=(p.estado!==undefined)?p.estado:1;
  document.getElementById('pDesc').value=p.descripcion||'';
  document.getElementById('tituloProd').innerHTML='<i class="bi bi-pencil"></i> Editar Producto';
  document.getElementById('imgNew').innerHTML='';
  document.getElementById('btnGuardProd').disabled=false;
  document.getElementById('btnGuardProd').innerHTML='<i class="bi bi-save"></i> Guardar';

  var cont=document.getElementById('imgExist');
  cont.innerHTML='<small style="color:var(--text-muted);">Cargando...</small>';
  fetch(BASE_URL+'/admin/get_imagenes.php?producto_id='+p.id)
    .then(function(r){return r.json();})
    .then(function(imgs){
      cont.innerHTML='';
      if(imgs.length){
        var lbl=document.createElement('small');
        lbl.style.cssText='color:var(--text-muted);font-size:.74rem;display:block;margin-bottom:4px;';
        lbl.textContent='Imágenes ('+imgs.length+'/<?= MAX_IMAGES ?>):';
        cont.appendChild(lbl);
        imgs.forEach(function(img){
          var div=document.createElement('div'); div.className='img-preview-item'; div.id='iw'+img.id;
          div.innerHTML='<img src="'+BASE_URL+'/uploads/productos/'+img.ruta_imagen+'" alt=""><button type="button" class="img-preview-delete" onclick="borrarImg('+img.id+','+p.id+',\'iw'+img.id+'\')"><i class="bi bi-x"></i></button>';
          cont.appendChild(div);
        });
      }
    }).catch(function(){cont.innerHTML='';});

  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto')).show();
}

function borrarImg(imgId,prodId,wrapId) {
  confirmar('¿Eliminar esta imagen?','warning').then(function(ok){
    if(!ok) return;
    var fd=new FormData(); fd.append('action','delete_imagen'); fd.append('imagen_id',imgId); fd.append('producto_id',prodId);
    fetch(location.pathname,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){var el=document.getElementById(wrapId);if(el)el.remove();showToast('Imagen eliminada','success');}
      else showToast('Error al eliminar','danger');
    });
  });
}

function eliminarProducto(id,nombre) {
  confirmar('¿Eliminar <strong>'+nombre+'</strong>?<br><small style="color:var(--text-muted)">Se eliminarán sus imágenes y colores.</small>','danger')
    .then(function(ok){
      if(!ok) return;
      document.getElementById('fDelId').value=id;
      document.getElementById('formDel').submit();
    });
}
<?php endif; ?>
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
