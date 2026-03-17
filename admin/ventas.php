<?php
// admin/ventas.php
$pageTitle = 'Ventas';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../models/Producto.php';

$ctrl = new VentaController();

// AJAX: crear venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'create') {
    header('Content-Type: application/json');
    $tipoPago  = $_POST['tipo_pago']  ?? 'efectivo';
    $descuento = floatval($_POST['descuento'] ?? 0);
    $items     = [];
    foreach ($_POST['items'] ?? [] as $item) {
        $items[] = [
            'producto_id' => intval($item['producto_id']),
            'cantidad'    => intval($item['cantidad']),
            'precio'      => floatval($item['precio']),
        ];
    }
    // Pasar descuento al controlador
    $result = $ctrl->createConDescuento(currentUser()['id'], $tipoPago, $items, $descuento);
    echo json_encode($result);
    exit;
}

// AJAX: anular venta (solo admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'anular') {
    header('Content-Type: application/json');
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos para anular ventas']);
        exit;
    }
    echo json_encode($ctrl->anular(intval($_POST['id'] ?? 0), trim($_POST['motivo'] ?? '')));
    exit;
}

$uid       = isAdmin() ? null : currentUser()['id'];
$ventas    = $ctrl->getAll(null, $uid);
$stats     = $ctrl->getStats($uid);
$productos = (new Producto())->getAll(true);

include __DIR__ . '/../views/partials/header_admin.php';
?>

<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPOS" type="button" role="tab">
      <i class="bi bi-cart3"></i> Nueva Venta
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHistorial" type="button" role="tab">
      <i class="bi bi-clock-history"></i> Historial
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ===== POS ===== -->
  <div class="tab-pane fade show active" id="tabPOS" role="tabpanel">
    <div class="row g-4">

      <!-- Lista productos -->
      <div class="col-12 col-lg-7">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title">Productos disponibles</span>
            <div class="search-bar">
              <i class="bi bi-search search-icon"></i>
              <input type="text" id="posSearch" class="form-control form-control-sm"
                     placeholder="Buscar producto..." style="width:190px;padding-left:2.2rem;">
            </div>
          </div>
          <div style="max-height:500px;overflow-y:auto;">
            <table class="table" id="posTable">
              <thead>
                <tr>
                  <th style="width:52px;"></th>
                  <th>Producto</th>
                  <th>Precio</th>
                  <th class="table-hide-mobile">Stock</th>
                  <th style="width:95px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($productos)): ?>
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">
                  No hay productos activos
                </td></tr>
                <?php else: foreach ($productos as $p):
                  $sc = (int)$p['stock'] > 10 ? 'stock-high'
                      : ((int)$p['stock'] > 3  ? 'stock-medium'
                      : ((int)$p['stock'] > 0  ? 'stock-low' : 'stock-out'));
                  $pv = floatval($p['precio_venta'] ?? $p['precio'] ?? 0);
                  $pJs = htmlspecialchars(json_encode([
                    'id'              => (int)$p['id'],
                    'nombre'          => $p['nombre'],
                    'precio'          => $pv,
                    'stock'           => (int)$p['stock'],
                    'imagen_principal'=> $p['imagen_principal'] ?? '',
                  ]), ENT_QUOTES);
                ?>
                <tr>
                  <td>
                    <?php if ($p['imagen_principal']): ?>
                      <img src="<?= UPLOAD_URL . htmlspecialchars(basename($p['imagen_principal'])) ?>"
                           style="width:44px;height:44px;object-fit:cover;border-radius:7px;border:1px solid var(--border-color);display:block;">
                    <?php else: ?>
                      <div style="width:44px;height:44px;border-radius:7px;background:var(--bg-primary);
                                  display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
                        <i class="bi bi-image"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong style="font-size:.88rem;"><?= htmlspecialchars($p['nombre']) ?></strong>
                    <div style="font-size:.74rem;color:var(--text-muted);">
                      <?= htmlspecialchars($p['categoria_nombre'] ?? '') ?>
                    </div>
                  </td>
                  <td class="text-money fw-bold" style="color:var(--accent);white-space:nowrap;">
                    Bs. <?= number_format($pv, 2) ?>
                  </td>
                  <td class="table-hide-mobile">
                    <span class="stock-indicator <?= $sc ?>">
                      <span class="stock-dot"></span><?= (int)$p['stock'] ?>
                    </span>
                  </td>
                  <td>
                    <?php if ((int)$p['stock'] > 0): ?>
                    <button type="button" class="btn btn-sm btn-primary"
                            onclick="Cart.add(<?= $pJs ?>)">
                      <i class="bi bi-plus"></i> Agregar
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm btn-secondary" disabled>Sin stock</button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Carrito -->
      <div class="col-12 col-lg-5">
        <div class="table-card" style="position:sticky;top:70px;">
          <div class="table-card-header">
            <span class="table-card-title"><i class="bi bi-cart3"></i> Carrito</span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="Cart.clear()">
              <i class="bi bi-trash"></i> Limpiar
            </button>
          </div>

          <div id="cartEmpty" class="text-center py-4" style="color:var(--text-muted);">
            <i class="bi bi-cart-x" style="font-size:2.5rem;display:block;margin-bottom:.6rem;"></i>
            <p class="mb-1 fw-bold">Carrito vacio</p>
            <small>Agrega productos desde la lista</small>
          </div>

          <div id="cartItems"></div>

          <div id="cartCheckout" style="display:none;">
            <div class="px-3 pb-3">
              <hr class="divider mt-0">

              <!-- Subtotal -->
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:.88rem;color:var(--text-secondary);">Subtotal:</span>
                <span id="cartSubtotal" class="text-money" style="color:var(--text-secondary);">Bs. 0.00</span>
              </div>

              <!-- Descuento -->
              <div class="d-flex align-items-center gap-2 mb-2">
                <label style="font-size:.88rem;color:var(--text-secondary);white-space:nowrap;margin:0;">
                  Descuento:
                </label>
                <div class="input-group input-group-sm flex-fill">
                  <input type="number" id="descuentoInput" class="form-control form-control-sm"
                         min="0" max="100" step="0.01" value="0" placeholder="0"
                         oninput="actualizarTotalConDescuento()"
                         style="max-width:70px;">
                  <select id="tipoDescuento" class="form-select form-select-sm"
                          onchange="actualizarTotalConDescuento()" style="max-width:60px;">
                    <option value="pct">%</option>
                    <option value="monto">Bs.</option>
                  </select>
                </div>
                <span id="descuentoMonto" class="text-money"
                      style="color:var(--danger);font-size:.85rem;white-space:nowrap;">- Bs. 0.00</span>
              </div>

              <!-- Total final -->
              <div class="d-flex justify-content-between align-items-center mb-3"
                   style="border-top:1px solid var(--border-color);padding-top:.75rem;">
                <span class="fw-bold fs-6">Total:</span>
                <span class="cart-total" id="cartTotal">Bs. 0.00</span>
              </div>

              <!-- Tipo de pago -->
              <div class="mb-3">
                <label class="form-label">Tipo de pago</label>
                <div class="d-flex gap-2">
                  <input type="radio" name="tipoPago" id="rEfectivo" value="efectivo" class="d-none" checked>
                  <label for="rEfectivo" id="lblEfectivo"
                         class="btn btn-outline-success flex-fill active"
                         onclick="seleccionarPago('efectivo')">
                    <i class="bi bi-cash"></i> Efectivo
                  </label>
                  <input type="radio" name="tipoPago" id="rQR" value="qr" class="d-none">
                  <label for="rQR" id="lblQR"
                         class="btn btn-outline-info flex-fill"
                         onclick="seleccionarPago('qr')">
                    <i class="bi bi-qr-code"></i> QR
                  </label>
                </div>
              </div>

              <button type="button" id="btnConfirmarVenta" class="btn btn-primary w-100 fw-bold py-2"
                      onclick="procesarVenta()">
                <i class="bi bi-check-lg"></i> Confirmar Venta
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Historial ===== -->
  <div class="tab-pane fade" id="tabHistorial" role="tabpanel">
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-cash"></i></div>
          <div><div class="stat-value">Bs. <?= number_format($stats['total_hoy'],2) ?></div>
          <div class="stat-label">Ingresos Hoy</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-calendar-month"></i></div>
          <div><div class="stat-value">Bs. <?= number_format($stats['total_mes'],2) ?></div>
          <div class="stat-label">Este Mes</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
          <div><div class="stat-value"><?= $stats['count_hoy'] ?></div>
          <div class="stat-label">Ventas Hoy</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="bi bi-list-ol"></i></div>
          <div>
            <div class="stat-value"><?= count(array_filter($ventas, fn($v) => $v['estado'])) ?></div>
            <div class="stat-label">Ventas Activas</div>
          </div>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title">
          <i class="bi bi-clock-history"></i>
          <?= isAdmin() ? 'Todas las Ventas' : 'Mis Ventas' ?>
        </span>
        <div class="search-bar">
          <i class="bi bi-search search-icon"></i>
          <input type="text" id="searchVentas" class="form-control form-control-sm"
                 placeholder="Buscar..." style="width:180px;padding-left:2.2rem;">
        </div>
      </div>

      <?php if (empty($ventas)): ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>Sin ventas registradas</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table" id="tablaVentas">
          <thead>
            <tr>
              <th>#</th>
              <?php if (isAdmin()): ?><th class="table-hide-mobile">Vendedor</th><?php endif; ?>
              <th>Pago</th>
              <th>Total</th>
              <th>Estado</th>
              <th class="table-hide-mobile">Fecha</th>
              <th style="width:<?= isAdmin() ? '90' : '60' ?>px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ventas as $v): ?>
            <tr>
              <td style="color:var(--text-muted);font-weight:600;">#<?= (int)$v['id'] ?></td>
              <?php if (isAdmin()): ?>
              <td class="table-hide-mobile" style="font-size:.85rem;">
                <?= htmlspecialchars($v['vendedor_nombre']) ?>
              </td>
              <?php endif; ?>
              <td>
                <span class="badge-status <?= $v['tipo_pago']==='qr' ? 'badge-qr' : 'badge-efectivo' ?>">
                  <i class="bi bi-<?= $v['tipo_pago']==='qr' ? 'qr-code' : 'cash' ?>"></i>
                  <?= ucfirst($v['tipo_pago']) ?>
                </span>
              </td>
              <td class="text-money fw-bold"
                  style="color:var(--<?= $v['estado'] ? 'success' : 'text-muted' ?>);
                         <?= !$v['estado'] ? 'text-decoration:line-through;opacity:.6;' : '' ?>">
                Bs. <?= number_format($v['total'],2) ?>
              </td>
              <td>
                <?php if ($v['estado']): ?>
                  <span class="badge-status badge-active">Activa</span>
                <?php else: ?>
                  <span class="badge-status badge-inactive"
                        title="<?= htmlspecialchars($v['motivo_anulacion'] ?? '') ?>">Anulada</span>
                <?php endif; ?>
              </td>
              <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);">
                <?= date('d/m/Y H:i', strtotime($v['fecha'])) ?>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <button type="button" class="btn btn-sm btn-outline-info"
                          onclick="verDetalle(<?= (int)$v['id'] ?>)" title="Ver detalle">
                    <i class="bi bi-eye"></i>
                  </button>
                  <?php if (isAdmin() && $v['estado']): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger"
                          onclick="pedirAnulacion(<?= (int)$v['id'] ?>, <?= number_format($v['total'],2,'.','') ?>)"
                          title="Anular">
                    <i class="bi bi-x-circle"></i>
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
  </div>
</div>

<!-- Modal: Detalle venta -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-receipt"></i> Detalle de Venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalleBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php if (isAdmin()): ?>
<!-- Modal: Anular venta (solo admin) -->
<div class="modal fade" id="modalAnular" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom-color:var(--danger);">
        <h5 class="modal-title" style="color:var(--danger);">
          <i class="bi bi-x-circle"></i> Anular Venta
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="anularLabel" class="fw-bold mb-2" style="font-size:.9rem;"></p>
        <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem;">
          El stock sera restituido automaticamente.
        </p>
        <label class="form-label">Motivo (opcional)</label>
        <input type="text" id="anularMotivo" class="form-control"
               placeholder="Ej: Error en cobro, devolucion..." maxlength="200">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger fw-bold" id="btnAnular" onclick="ejecutarAnulacion()">
          <i class="bi bi-x-circle"></i> Confirmar Anulacion
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
var _ventaAnularId = null;

document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('posSearch',    'posTable',    [1]);
  initTableSearch('searchVentas', 'tablaVentas', [0, 1]);
  Cart.render();
  actualizarTotalConDescuento();
});

// ---- Descuento ----
function actualizarTotalConDescuento() {
  var subtotal  = Cart.getTotal();
  var descVal   = parseFloat(document.getElementById('descuentoInput').value) || 0;
  var tipo      = document.getElementById('tipoDescuento') ? document.getElementById('tipoDescuento').value : 'pct';
  var descMonto = tipo === 'pct' ? subtotal * (descVal / 100) : Math.min(descVal, subtotal);
  var total     = Math.max(0, subtotal - descMonto);

  var subEl  = document.getElementById('cartSubtotal');
  var dscEl  = document.getElementById('descuentoMonto');
  var totEl  = document.getElementById('cartTotal');

  if (subEl) subEl.textContent  = 'Bs. ' + subtotal.toFixed(2);
  if (dscEl) dscEl.textContent  = '- Bs. ' + descMonto.toFixed(2);
  if (totEl) totEl.textContent  = 'Bs. ' + total.toFixed(2);
}

// Sobreescribir Cart.render para actualizar descuento despues
var _origRender = Cart.render.bind(Cart);
Cart.render = function() {
  _origRender();
  actualizarTotalConDescuento();
};

// ---- Tipo pago ----
function seleccionarPago(tipo) {
  document.getElementById('rEfectivo').checked = (tipo === 'efectivo');
  document.getElementById('rQR').checked       = (tipo === 'qr');
  document.getElementById('lblEfectivo').classList.toggle('active', tipo === 'efectivo');
  document.getElementById('lblQR').classList.toggle('active',       tipo === 'qr');
}

// ---- Confirmar venta ----
function procesarVenta() {
  var tipoPago  = document.querySelector('input[name="tipoPago"]:checked').value;
  var descVal   = parseFloat(document.getElementById('descuentoInput').value) || 0;
  var tipo      = document.getElementById('tipoDescuento').value;
  var subtotal  = Cart.getTotal();
  var descMonto = tipo === 'pct' ? subtotal * (descVal / 100) : Math.min(descVal, subtotal);
  Cart.checkoutConDescuento(tipoPago, descMonto);
}

// ---- Ver detalle ----
function verDetalle(id) {
  document.getElementById('detalleBody').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalle')).show();

  fetch(BASE_URL + '/admin/get_venta_detalle.php?id=' + id)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.venta) {
        document.getElementById('detalleBody').innerHTML =
          '<p class="text-danger text-center py-3">Error al cargar el detalle.</p>';
        return;
      }
      var v = data.venta;
      var anulada = parseInt(v.estado) === 0;
      var filas = (data.detalle || []).map(function(d) {
        return '<tr><td>' + d.producto_nombre + '</td>'
          + '<td class="text-center">' + d.cantidad + '</td>'
          + '<td class="text-money">Bs. ' + parseFloat(d.precio).toFixed(2) + '</td>'
          + '<td class="text-money fw-bold">Bs. ' + parseFloat(d.subtotal).toFixed(2) + '</td></tr>';
      }).join('');
      var estadoBadge = anulada
        ? '<span class="badge-status badge-inactive">Anulada'
          + (v.motivo_anulacion ? ': ' + v.motivo_anulacion : '') + '</span>'
        : '<span class="badge-status badge-active">Activa</span>';
      var pagoBadge = '<span class="badge-status ' + (v.tipo_pago==='qr' ? 'badge-qr' : 'badge-efectivo') + '">'
        + '<i class="bi bi-' + (v.tipo_pago==='qr' ? 'qr-code' : 'cash') + '"></i> '
        + (v.tipo_pago==='qr' ? 'QR' : 'Efectivo') + '</span>';
      var totalStyle = 'color:var(--accent);font-size:1.1rem;'
        + (anulada ? 'text-decoration:line-through;opacity:.6;' : '');

      document.getElementById('detalleBody').innerHTML =
        '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">'
        + '<div><div class="fw-bold fs-6">Venta #' + v.id + '</div>'
        + '<div style="font-size:.84rem;color:var(--text-muted);">' + v.vendedor_nombre
        + ' &middot; ' + new Date(v.fecha.replace(' ','T')).toLocaleString('es') + '</div></div>'
        + '<div class="d-flex gap-2 flex-wrap">' + pagoBadge + estadoBadge + '</div></div>'
        + '<div class="table-responsive"><table class="table table-sm">'
        + '<thead><tr><th>Producto</th><th class="text-center">Cant.</th>'
        + '<th>Precio</th><th>Subtotal</th></tr></thead>'
        + '<tbody>' + filas + '</tbody>'
        + '<tfoot><tr><td colspan="3" class="fw-bold text-end">Total:</td>'
        + '<td class="fw-bold text-money" style="' + totalStyle + '">'
        + 'Bs. ' + parseFloat(v.total).toFixed(2) + '</td></tr></tfoot>'
        + '</table></div>';
    })
    .catch(function() {
      document.getElementById('detalleBody').innerHTML =
        '<p class="text-danger text-center py-3">Error de conexion.</p>';
    });
}

<?php if (isAdmin()): ?>
var _ventaAnularId = null;

function pedirAnulacion(id, total) {
  _ventaAnularId = id;
  document.getElementById('anularLabel').textContent = 'Venta #' + id + '  —  Bs. ' + total;
  document.getElementById('anularMotivo').value = '';
  document.getElementById('btnAnular').disabled = false;
  document.getElementById('btnAnular').innerHTML = '<i class="bi bi-x-circle"></i> Confirmar Anulacion';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAnular')).show();
}

function ejecutarAnulacion() {
  if (!_ventaAnularId) return;
  var btn    = document.getElementById('btnAnular');
  var motivo = document.getElementById('anularMotivo').value.trim();
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Anulando...';
  var fd = new FormData();
  fd.append('id',     _ventaAnularId);
  fd.append('motivo', motivo);
  fetch(BASE_URL + '/admin/ventas.php?action=anular', { method:'POST', body:fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      bootstrap.Modal.getInstance(document.getElementById('modalAnular')).hide();
      if (data.success) {
        showToast(data.message, 'success', 4000);
        setTimeout(function() { location.reload(); }, 1500);
      } else {
        showToast(data.message || 'Error al anular', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Confirmar Anulacion';
      }
    })
    .catch(function() {
      showToast('Error de conexion', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-x-circle"></i> Confirmar Anulacion';
    });
}
<?php endif; ?>
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
