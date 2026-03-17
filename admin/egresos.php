<?php
// admin/egresos.php
$pageTitle = 'Egresos';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../controllers/EgresoController.php';

$ctrl = new EgresoController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;
    if ($action === 'create')     $result = $ctrl->create(currentUser()['id'], $_POST);
    elseif ($action === 'update') $result = $ctrl->update(intval($_POST['id']), $_POST);
    elseif ($action === 'delete') $result = $ctrl->delete(intval($_POST['id']));
    if ($result) setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/egresos.php');
}

$egresos = $ctrl->getAll();
$stats   = $ctrl->getStats();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-wallet2"></i></div>
      <div style="min-width:0;">
        <div class="stat-value">Bs. <?= number_format($stats['total_hoy'], 2) ?></div>
        <div class="stat-label">Egresos Hoy</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-calendar-month"></i></div>
      <div style="min-width:0;">
        <div class="stat-value">Bs. <?= number_format($stats['total_mes'], 2) ?></div>
        <div class="stat-label">Este Mes</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-list-check"></i></div>
      <div style="min-width:0;">
        <div class="stat-value"><?= count($egresos) ?></div>
        <div class="stat-label">Total Registros</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-wallet2"></i> Egresos</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:180px;padding-left:2.2rem;">
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="abrirNuevoEgreso()">
        <i class="bi bi-plus-lg"></i> Nuevo
      </button>
    </div>
  </div>

  <?php if (empty($egresos)): ?>
    <div class="empty-state"><i class="bi bi-wallet2"></i><p>No hay egresos registrados</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaEgresos">
      <thead>
        <tr>
          <th>Concepto</th>
          <th class="table-hide-mobile">Registrado por</th>
          <th>Monto</th>
          <th class="table-hide-mobile">Fecha</th>
          <th style="width:90px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($egresos as $e):
          $eJson = htmlspecialchars(json_encode([
            'id'      => $e['id'],
            'concepto'=> $e['concepto'],
            'monto'   => $e['monto'],
            'fecha'   => substr($e['fecha'], 0, 10),
          ]), ENT_QUOTES);
        ?>
        <tr>
          <td>
            <strong style="font-size:.88rem;"><?= htmlspecialchars($e['concepto']) ?></strong>
            <div class="d-block d-md-none" style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">
              <?= date('d/m/Y', strtotime($e['fecha'])) ?>
              · <?= htmlspecialchars($e['usuario_nombre'] ?? '') ?>
            </div>
          </td>
          <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);">
            <?= htmlspecialchars($e['usuario_nombre'] ?? '—') ?>
          </td>
          <td class="text-money fw-bold" style="color:var(--danger);">
            Bs. <?= number_format($e['monto'], 2) ?>
          </td>
          <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);">
            <?= date('d/m/Y', strtotime($e['fecha'])) ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary"
                      onclick="editEgreso(<?= $eJson ?>)" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      onclick="eliminarEgreso(<?= (int)$e['id'] ?>, '<?= addslashes(htmlspecialchars($e['concepto'])) ?>')"
                      title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
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
<form method="POST" id="formDelEgr" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="fDelEgrId">
</form>

<!-- Modal Egreso -->
<div class="modal fade" id="modalEgreso" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="formEgreso">
        <input type="hidden" name="action" id="eAction" value="create">
        <input type="hidden" name="id"     id="eId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloModalEgr">
            <i class="bi bi-plus-lg"></i> Nuevo Egreso
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Concepto *</label>
            <input type="text" name="concepto" id="eConcepto" class="form-control"
                   required maxlength="200" placeholder="Ej: Pago proveedor, servicios...">
            <div class="invalid-feedback">El concepto es obligatorio</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Monto *</label>
            <div class="input-group">
              <span class="input-group-text">Bs.</span>
              <input type="number" name="monto" id="eMonto" class="form-control"
                     step="0.01" min="0.01" required placeholder="0.00">
            </div>
            <div class="invalid-feedback">Ingresa un monto mayor a 0</div>
          </div>
          <div class="mb-2">
            <label class="form-label">Fecha *</label>
            <input type="date" name="fecha" id="eFecha" class="form-control"
                   required value="<?= date('Y-m-d') ?>">
            <div class="invalid-feedback">La fecha es obligatoria</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarEgr">
            <i class="bi bi-save"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('searchInput', 'tablaEgresos', [0, 1]);

  // Limpiar errores al escribir
  ['eConcepto','eMonto','eFecha'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { this.classList.remove('is-invalid'); });
  });

  document.getElementById('formEgreso').addEventListener('submit', function(e) {
    var ok = true;
    var concepto = document.getElementById('eConcepto');
    var monto    = document.getElementById('eMonto');
    var fecha    = document.getElementById('eFecha');
    [concepto, monto, fecha].forEach(function(el) { el.classList.remove('is-invalid'); });

    if (!concepto.value.trim()) { concepto.classList.add('is-invalid'); ok = false; }
    if (!monto.value || parseFloat(monto.value) <= 0) { monto.classList.add('is-invalid'); ok = false; }
    if (!fecha.value) { fecha.classList.add('is-invalid'); ok = false; }

    if (!ok) {
      e.preventDefault();
      var first = document.querySelector('#formEgreso .is-invalid');
      if (first) { first.focus(); first.scrollIntoView({behavior:'smooth',block:'center'}); }
      return;
    }
    document.getElementById('btnGuardarEgr').disabled = true;
    document.getElementById('btnGuardarEgr').innerHTML =
      '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });

  document.getElementById('modalEgreso').addEventListener('hidden.bs.modal', function() {
    document.getElementById('formEgreso').reset();
    document.getElementById('eAction').value = 'create';
    document.getElementById('eId').value = '';
    document.getElementById('tituloModalEgr').innerHTML = '<i class="bi bi-plus-lg"></i> Nuevo Egreso';
    document.getElementById('eFecha').value = '<?= date('Y-m-d') ?>';
    document.getElementById('btnGuardarEgr').disabled = false;
    document.getElementById('btnGuardarEgr').innerHTML = '<i class="bi bi-save"></i> Guardar';
    document.querySelectorAll('#formEgreso .is-invalid').forEach(function(el) {
      el.classList.remove('is-invalid');
    });
  });
});

function abrirNuevoEgreso() {
  document.getElementById('formEgreso').reset();
  document.getElementById('eAction').value = 'create';
  document.getElementById('eId').value = '';
  document.getElementById('tituloModalEgr').innerHTML = '<i class="bi bi-plus-lg"></i> Nuevo Egreso';
  document.getElementById('eFecha').value = '<?= date('Y-m-d') ?>';
  document.getElementById('btnGuardarEgr').disabled = false;
  document.getElementById('btnGuardarEgr').innerHTML = '<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formEgreso .is-invalid').forEach(function(el) {
    el.classList.remove('is-invalid');
  });
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEgreso')).show();
}

function editEgreso(e) {
  document.getElementById('eAction').value   = 'update';
  document.getElementById('eId').value       = e.id;
  document.getElementById('eConcepto').value = e.concepto || '';
  document.getElementById('eMonto').value    = e.monto    || '';
  document.getElementById('eFecha').value    = e.fecha    || '';
  document.getElementById('tituloModalEgr').innerHTML = '<i class="bi bi-pencil"></i> Editar Egreso';
  document.getElementById('btnGuardarEgr').disabled = false;
  document.getElementById('btnGuardarEgr').innerHTML = '<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formEgreso .is-invalid').forEach(function(el) {
    el.classList.remove('is-invalid');
  });
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEgreso')).show();
}

function eliminarEgreso(id, concepto) {
  confirmar('¿Eliminar el egreso <strong>' + concepto + '</strong>?', 'danger').then(function(ok) {
    if (!ok) return;
    document.getElementById('fDelEgrId').value = id;
    document.getElementById('formDelEgr').submit();
  });
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
