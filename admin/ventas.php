<?php
$pageTitle = 'Ventas';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../models/Producto.php';

$ctrl = new VentaController();

// ---- AJAX: crear venta ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'create') {
    header('Content-Type: application/json');
    $tipoPago = $_POST['tipo_pago'] ?? 'efectivo';
    $items    = [];
    foreach ($_POST['items'] ?? [] as $item) {
        $items[] = [
            'producto_id' => intval($item['producto_id']),
            'cantidad'    => intval($item['cantidad']),
            'precio'      => floatval($item['precio']),
        ];
    }
    echo json_encode($ctrl->create(currentUser()['id'], $tipoPago, $items));
    exit;
}

// ---- AJAX: anular venta (solo admin) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'anular') {
    header('Content-Type: application/json');
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    $id     = intval($_POST['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    echo json_encode($ctrl->anular($id, $motivo));
    exit;
}

$ventas    = $ctrl->getAll();
$stats     = $ctrl->getStats();
$productos = (new Producto())->getAll(true);

include __DIR__ . '/../views/partials/header_admin.php';
?>

<!-- Tabs -->
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

  <!-- ====== POS ====== -->
  <div class="tab-pane fade show active" id="tabPOS" role="tabpanel">
    <div class="row g-4">

      <!-- Lista productos -->
      <div class="col-lg-7">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title">Seleccionar Productos</span>
            <div class="search-bar">
              <i class="bi bi-search search-icon"></i>
              <input type="text" id="posSearch" class="form-control form-control-sm"
                     placeholder="Buscar producto..." style="width:210px;padding-left:2.2rem;">
            </div>
          </div>
          <div style="max-height:500px;overflow-y:auto;">
            <table class="table" id="posTable">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Precio</th>
                  <th>Stock</th>
                  <th style="width:90px;"></th>
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
                    <div class="fw-bold" style="font-size:.9rem;"><?= htmlspecialchars($p['nombre']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></div>
                  </td>
                  <td class="text-money fw-bold" style="color:var(--accent);">Bs <?= number_format($p['precio'], 2) ?></td>
                  <td>
                    <span class="stock-indicator <?= $sc ?>">
                      <span class="stock-dot"></span> <?= $p['stock'] ?>
                    </span>
                  </td>
                  <td>
                    <?php if ((int)$p['stock'] > 0): ?>
                    <button class="btn btn-sm btn-primary"
                            onclick='Cart.add({"id":<?= $p["id"] ?>,"nombre":<?= json_encode($p["nombre"]) ?>,"precio":"<?= $p["precio"] ?>","stock":<?= $p["stock"] ?>})'>
                      <i class="bi bi-plus"></i> Agregar
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-secondary" disabled>Sin stock</button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                <tr><td colspan="4" class="text-center py-3" style="color:var(--text-muted);">No hay productos disponibles</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Carrito -->
      <div class="col-lg-5">
        <div class="table-card" style="position:sticky;top:80px;">
          <div class="table-card-header">
            <span class="table-card-title"><i class="bi bi-cart3"></i> Carrito de Venta</span>
            <button class="btn btn-sm btn-outline-danger" onclick="Cart.clear()">
              <i class="bi bi-trash"></i> Limpiar
            </button>
          </div>

          <div id="cartEmpty" style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
            <i class="bi bi-cart-x" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
            <p style="margin:0;font-weight:500;">Carrito vacío</p>
            <small>Agrega productos desde la lista</small>
          </div>

          <div id="cartItems"></div>

          <div id="cartCheckout" style="display:none;">
            <div class="px-3 pb-3">
              <hr class="divider mt-0">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold">Total:</span>
                <span class="cart-total" id="cartTotal">Bs0.00</span>
              </div>
              <div class="mb-3">
                <label class="form-label">Tipo de pago</label>
                <div class="d-flex gap-2">
                  <div class="flex-fill">
                    <input type="radio" name="tipoPago" id="pagoEfectivo" value="efectivo" class="d-none" checked>
                    <label for="pagoEfectivo" id="btnEfectivo"
                           class="btn btn-outline-success w-100 active" style="cursor:pointer;"
                           onclick="selectPago('efectivo')">
                      <i class="bi bi-cash"></i> Efectivo
                    </label>
                  </div>
                  <div class="flex-fill">
                    <input type="radio" name="tipoPago" id="pagoQR" value="qr" class="d-none">
                    <label for="pagoQR" id="btnQR"
                           class="btn btn-outline-info w-100" style="cursor:pointer;"
                           onclick="selectPago('qr')">
                      <i class="bi bi-qr-code"></i> QR
                    </label>
                  </div>
                </div>
              </div>
              <button id="btnCheckout" class="btn btn-primary w-100 fw-bold py-2"
                      onclick="Cart.checkout(document.querySelector('input[name=tipoPago]:checked').value)">
                <i class="bi bi-check-lg"></i> Confirmar Venta
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ====== Historial ====== -->
  <div class="tab-pane fade" id="tabHistorial" role="tabpanel">

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-cash"></i></div>
          <div>
            <div class="stat-value">Bs <?= number_format($stats['total_hoy'], 2) ?></div>
            <div class="stat-label">Ingresos Hoy</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-calendar-month"></i></div>
          <div>
            <div class="stat-value">Bs <?= number_format($stats['total_mes'], 2) ?></div>
            <div class="stat-label">Este Mes</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
          <div>
            <div class="stat-value"><?= $stats['count_hoy'] ?></div>
            <div class="stat-label">Ventas Hoy</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="bi bi-list-ol"></i></div>
          <div>
            <div class="stat-value"><?= count(array_filter($ventas, function($v){ return $v['estado']; })) ?></div>
            <div class="stat-label">Ventas Activas</div>
          </div>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title">Historial de Ventas</span>
        <div class="search-bar">
          <i class="bi bi-search search-icon"></i>
          <input type="text" id="searchVentas" class="form-control form-control-sm"
                 placeholder="Buscar..." style="width:200px;padding-left:2.2rem;">
        </div>
      </div>

      <?php if (empty($ventas)): ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>No hay ventas registradas</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table" id="tablaVentas">
          <thead>
            <tr>
              <th>#</th>
              <th>Vendedor</th>
              <th>Pago</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th style="width:100px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ventas as $v): ?>
            <tr>
              <td style="color:var(--text-muted);">#<?= $v['id'] ?></td>
              <td><?= htmlspecialchars($v['vendedor_nombre']) ?></td>
              <td>
                <span class="badge-status <?= $v['tipo_pago'] === 'qr' ? 'badge-qr' : 'badge-efectivo' ?>">
                  <i class="bi bi-<?= $v['tipo_pago'] === 'qr' ? 'qr-code' : 'cash' ?>"></i>
                  <?= ucfirst($v['tipo_pago']) ?>
                </span>
              </td>
              <td class="text-money fw-bold"
                  style="color:var(--<?= $v['estado'] ? 'success' : 'text-muted' ?>);<?= !$v['estado'] ? 'text-decoration:line-through;opacity:.6;' : '' ?>">
                Bs <?= number_format($v['total'], 2) ?>
              </td>
              <td>
                <?php if ($v['estado']): ?>
                  <span class="badge-status badge-active">Activa</span>
                <?php else: ?>
                  <span class="badge-status badge-inactive" title="<?= htmlspecialchars($v['motivo_anulacion'] ?? '') ?>">Anulada</span>
                <?php endif; ?>
              </td>
              <td style="font-size:.82rem;color:var(--text-muted);"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <button class="btn btn-sm btn-outline-secondary"
                          onclick="verDetalle(<?= $v['id'] ?>)" title="Ver detalle">
                    <i class="bi bi-eye"></i>
                  </button>
                  <?php if (isAdmin() && $v['estado']): ?>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmarAnular(<?= $v['id'] ?>, <?= number_format($v['total'], 2, '.', '') ?>)"
                          title="Anular venta">
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

<!-- ===== Modal: Detalle Venta ===== -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalleContent">
        <div class="text-center py-3"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Anular Venta ===== -->
<div class="modal fade" id="modalAnular" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom-color:var(--danger);">
        <h5 class="modal-title" style="color:var(--danger);">
          <i class="bi bi-x-circle"></i> Anular Venta
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1" style="font-size:.9rem;">¿Anular la <strong id="anularVentaLabel">venta</strong>?</p>
        <p class="mb-3" style="font-size:.82rem;color:var(--text-muted);">
          El stock de los productos será restituido automáticamente.
        </p>
        <label class="form-label">Motivo de anulación</label>
        <input type="text" id="motivoAnulacion" class="form-control"
               placeholder="Ej: Error en cobro, devolución..." maxlength="200">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm fw-bold" id="btnConfirmarAnular"
                onclick="ejecutarAnulacion()">
          <i class="bi bi-x-circle"></i> Confirmar Anulación
        </button>
      </div>
    </div>
  </div>
</div>

<script>
var _anularId = null;

document.addEventListener('DOMContentLoaded', function(){
  initTableSearch('posSearch',    'posTable',     [0]);
  initTableSearch('searchVentas', 'tablaVentas',  [0, 1]);
  Cart.render();
});

function selectPago(tipo) {
  document.getElementById('pagoEfectivo').checked = (tipo === 'efectivo');
  document.getElementById('pagoQR').checked       = (tipo === 'qr');
  var btnEf = document.getElementById('btnEfectivo');
  var btnQR = document.getElementById('btnQR');
  if (tipo === 'efectivo') {
    btnEf.classList.add('active'); btnQR.classList.remove('active');
  } else {
    btnQR.classList.add('active'); btnEf.classList.remove('active');
  }
}

function verDetalle(ventaId) {
  document.getElementById('detalleContent').innerHTML =
    '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
  new bootstrap.Modal(document.getElementById('modalDetalle')).show();

  fetch(BASE_URL + '/admin/get_venta_detalle.php?id=' + ventaId)
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (!data.venta) {
        document.getElementById('detalleContent').innerHTML =
          '<p class="text-danger text-center">Error al cargar.</p>';
        return;
      }
      var v = data.venta;
      var anulada = parseInt(v.estado) === 0;
      var filas = (data.detalle || []).map(function(d){
        return '<tr>'
          + '<td>' + d.producto_nombre + '</td>'
          + '<td class="text-center">' + d.cantidad + '</td>'
          + '<td class="text-money">Bs' + parseFloat(d.precio).toFixed(2) + '</td>'
          + '<td class="text-money fw-bold">Bs ' + parseFloat(d.subtotal).toFixed(2) + '</td>'
          + '</tr>';
      }).join('');

      var estadoBadge = anulada
        ? '<span class="badge-status badge-inactive">Anulada' + (v.motivo_anulacion ? ' — ' + v.motivo_anulacion : '') + '</span>'
        : '<span class="badge-status badge-active">Activa</span>';

      document.getElementById('detalleContent').innerHTML =
        '<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">'
        + '<div>'
        + '<div class="fw-bold fs-6">Venta #' + v.id + '</div>'
        + '<div style="font-size:.85rem;color:var(--text-muted);">'
        + v.vendedor_nombre + ' &middot; ' + new Date(v.fecha).toLocaleString('es') + '</div>'
        + '</div>'
        + '<div class="d-flex gap-2 align-items-center">'
        + '<span class="badge-status ' + (v.tipo_pago === 'qr' ? 'badge-qr' : 'badge-efectivo') + '">'
        + (v.tipo_pago === 'qr' ? '<i class="bi bi-qr-code"></i> QR' : '<i class="bi bi-cash"></i> Efectivo') + '</span>'
        + estadoBadge
        + '</div>'
        + '</div>'
        + '<table class="table table-sm">'
        + '<thead><tr><th>Producto</th><th class="text-center">Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>'
        + '<tbody>' + filas + '</tbody>'
        + '<tfoot><tr><td colspan="3" class="fw-bold text-end">Total:</td>'
        + '<td class="fw-bold text-money" style="color:var(--accent);font-size:1.1rem;"'
        + (anulada ? ' style="text-decoration:line-through;opacity:.6;"' : '') + '>'
        + 'Bs ' + parseFloat(v.total).toFixed(2) + '</td></tr></tfoot>'
        + '</table>';
    })
    .catch(function(){
      document.getElementById('detalleContent').innerHTML =
        '<p class="text-danger text-center">Error de conexión.</p>';
    });
}

function confirmarAnular(id, total) {
  _anularId = id;
  document.getElementById('anularVentaLabel').textContent = 'Venta #' + id + ' (Bs' + total + ')';
  document.getElementById('motivoAnulacion').value = '';
  new bootstrap.Modal(document.getElementById('modalAnular')).show();
}

function ejecutarAnulacion() {
  if (!_anularId) return;
  var btn    = document.getElementById('btnConfirmarAnular');
  var motivo = document.getElementById('motivoAnulacion').value.trim();

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Anulando...';

  var fd = new FormData();
  fd.append('id',     _anularId);
  fd.append('motivo', motivo);

  fetch(BASE_URL + '/admin/ventas.php?action=anular', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(data){
      bootstrap.Modal.getInstance(document.getElementById('modalAnular')).hide();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(function(){ window.location.reload(); }, 1500);
      } else {
        showToast(data.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Confirmar Anulación';
      }
    })
    .catch(function(){
      showToast('Error de conexión', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-x-circle"></i> Confirmar Anulación';
    });
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
