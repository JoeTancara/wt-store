<?php
$pageTitle = 'Egresos';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../controllers/EgresoController.php';

$ctrl = new EgresoController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;
    if ($action === 'create') {
        $result = $ctrl->create(currentUser()['id'], $_POST);
    } elseif ($action === 'update') {
        $result = $ctrl->update(intval($_POST['id']), $_POST);
    } elseif ($action === 'delete') {
        $result = $ctrl->delete(intval($_POST['id']));
    }
    if ($result) setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/egresos.php');
}

$egresos = $ctrl->getAll();
$stats   = $ctrl->getStats();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-wallet2"></i></div>
      <div>
        <div class="stat-value">Bs <?= number_format($stats['total_hoy'], 2) ?></div>
        <div class="stat-label">Egresos Hoy</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-calendar-month"></i></div>
      <div>
        <div class="stat-value">Bs <?= number_format($stats['total_mes'], 2) ?></div>
        <div class="stat-label">Este Mes</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-list-check"></i></div>
      <div>
        <div class="stat-value"><?= count($egresos) ?></div>
        <div class="stat-label">Total Registros</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-wallet2"></i> Registro de Egresos</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:200px;padding-left:2.2rem;">
      </div>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEgreso">
        <i class="bi bi-plus-lg"></i> Nuevo Egreso
      </button>
    </div>
  </div>

  <?php if (empty($egresos)): ?>
    <div class="empty-state">
      <i class="bi bi-wallet2"></i>
      <p>No hay egresos registrados</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaEgresos">
      <thead>
        <tr>
          <th>#</th>
          <th>Concepto</th>
          <th>Registrado por</th>
          <th>Monto</th>
          <th>Fecha</th>
          <th style="width:90px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($egresos as $e): ?>
        <tr>
          <td style="color:var(--text-muted);"><?= (int)$e['id'] ?></td>
          <td><strong style="font-size:.9rem;"><?= htmlspecialchars($e['concepto']) ?></strong></td>
          <td style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($e['usuario_nombre'] ?? '—') ?></td>
          <td class="text-money fw-bold" style="color:var(--danger);">Bs <?= number_format($e['monto'], 2) ?></td>
          <td style="font-size:.85rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($e['fecha'])) ?></td>
          <td>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-primary"
                      onclick="editEgreso(<?= htmlspecialchars(json_encode($e), ENT_QUOTES) ?>)"
                      title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este egreso?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$e['id'] ?>">
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

<!-- Modal Egreso -->
<div class="modal fade" id="modalEgreso" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formEgreso">
        <input type="hidden" name="action" id="egresoAction" value="create">
        <input type="hidden" name="id"     id="egresoId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEgresoTitle"><i class="bi bi-plus-lg"></i> Nuevo Egreso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Concepto *</label>
            <input type="text" name="concepto" id="egresoConcepto" class="form-control" required maxlength="200">
          </div>
          <div class="mb-3">
            <label class="form-label">Monto *</label>
            <div class="input-group">
              <span class="input-group-text">Bs</span>
              <input type="number" name="monto" id="egresoMonto" class="form-control"
                     step="0.01" min="0.01" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Fecha *</label>
            <input type="date" name="fecha" id="egresoFecha" class="form-control"
                   value="<?= date('Y-m-d') ?>" required>
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

<script>
document.addEventListener('DOMContentLoaded', function(){
  initTableSearch('searchInput', 'tablaEgresos', [1, 2]);

  document.getElementById('modalEgreso').addEventListener('hidden.bs.modal', function(){
    document.getElementById('formEgreso').reset();
    document.getElementById('egresoAction').value = 'create';
    document.getElementById('egresoId').value     = '';
    document.getElementById('egresoFecha').value  = '<?= date('Y-m-d') ?>';
    document.getElementById('modalEgresoTitle').innerHTML = '<i class="bi bi-plus-lg"></i> Nuevo Egreso';
  });
});

function editEgreso(e) {
  document.getElementById('egresoAction').value   = 'update';
  document.getElementById('egresoId').value       = e.id;
  document.getElementById('egresoConcepto').value = e.concepto || '';
  document.getElementById('egresoMonto').value    = e.monto    || '';
  document.getElementById('egresoFecha').value    = e.fecha ? e.fecha.substring(0, 10) : '';
  document.getElementById('modalEgresoTitle').innerHTML = '<i class="bi bi-pencil"></i> Editar Egreso';
  new bootstrap.Modal(document.getElementById('modalEgreso')).show();
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
