<?php
// admin/categorias.php
$pageTitle = 'Categorías';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../controllers/CategoriaController.php';

$ctrl = new CategoriaController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;
    if ($action === 'create')     $result = $ctrl->create($_POST);
    elseif ($action === 'update') $result = $ctrl->update(intval($_POST['id']), $_POST);
    elseif ($action === 'delete') $result = $ctrl->delete(intval($_POST['id']));
    if ($result) setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/categorias.php');
}

$categorias = $ctrl->getWithCount();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">
      <i class="bi bi-tags"></i> Categorías
      <span style="font-size:.78rem;color:var(--text-muted);font-weight:500;">(<?= count($categorias) ?>)</span>
    </span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:180px;padding-left:2.2rem;">
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="abrirNuevaCategoria()">
        <i class="bi bi-plus-lg"></i> Nueva
      </button>
    </div>
  </div>

  <?php if (empty($categorias)): ?>
    <div class="empty-state"><i class="bi bi-tags"></i><p>No hay categorías</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaCategorias">
      <thead>
        <tr>
          <th>Nombre</th>
          <th class="table-hide-mobile">Descripción</th>
          <th>Productos</th>
          <th class="table-hide-mobile">Estado</th>
          <th class="table-hide-tablet">Creado</th>
          <th style="width:90px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $c):
          $cJson = htmlspecialchars(json_encode([
            'id'          => $c['id'],
            'nombre'      => $c['nombre'],
            'descripcion' => $c['descripcion'] ?? '',
            'estado'      => $c['estado'],
          ]), ENT_QUOTES);
        ?>
        <tr>
          <td>
            <strong style="font-size:.88rem;"><?= htmlspecialchars($c['nombre']) ?></strong>
            <div class="d-block d-md-none" style="margin-top:2px;">
              <span class="badge-status <?= $c['estado'] ? 'badge-active' : 'badge-inactive' ?>">
                <?= $c['estado'] ? 'Activa' : 'Inactiva' ?>
              </span>
            </div>
          </td>
          <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);max-width:200px;">
            <?php if ($c['descripcion']): ?>
              <?= htmlspecialchars(mb_substr($c['descripcion'], 0, 55)) ?><?= mb_strlen($c['descripcion']) > 55 ? '…' : '' ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <span style="background:var(--accent-light);color:var(--accent);border-radius:50px;padding:.2rem .65rem;font-size:.75rem;font-weight:700;">
              <?= (int)$c['total_productos'] ?>
            </span>
          </td>
          <td class="table-hide-mobile">
            <span class="badge-status <?= $c['estado'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $c['estado'] ? 'Activa' : 'Inactiva' ?>
            </span>
          </td>
          <td class="table-hide-tablet" style="font-size:.78rem;color:var(--text-muted);">
            <?= date('d/m/Y', strtotime($c['fecha_creacion'])) ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary"
                      onclick="editCategoria(<?= $cJson ?>)" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      onclick="eliminarCategoria(<?= (int)$c['id'] ?>, '<?= addslashes(htmlspecialchars($c['nombre'])) ?>', <?= (int)$c['total_productos'] ?>)"
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
<form method="POST" id="formDelCat" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="fDelCatId">
</form>

<!-- Modal Categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="formCategoria">
        <input type="hidden" name="action" id="cAction" value="create">
        <input type="hidden" name="id"     id="cId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloModalCat">
            <i class="bi bi-plus-lg"></i> Nueva Categoría
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" id="cNombre" class="form-control"
                   required maxlength="100" placeholder="Nombre de la categoría">
            <div class="invalid-feedback">El nombre es obligatorio</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" id="cDescripcion" class="form-control"
                      rows="3" maxlength="500" placeholder="Descripción opcional..."></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Estado</label>
            <select name="estado" id="cEstado" class="form-select">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarCat">
            <i class="bi bi-save"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('searchInput', 'tablaCategorias', [0, 1]);

  document.getElementById('cNombre').addEventListener('input', function() {
    this.classList.remove('is-invalid');
  });

  document.getElementById('formCategoria').addEventListener('submit', function(e) {
    var nombre = document.getElementById('cNombre');
    nombre.classList.remove('is-invalid');
    if (!nombre.value.trim()) {
      nombre.classList.add('is-invalid');
      nombre.focus();
      e.preventDefault();
      return;
    }
    document.getElementById('btnGuardarCat').disabled = true;
    document.getElementById('btnGuardarCat').innerHTML =
      '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });

  document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function() {
    document.getElementById('formCategoria').reset();
    document.getElementById('cAction').value = 'create';
    document.getElementById('cId').value = '';
    document.getElementById('tituloModalCat').innerHTML = '<i class="bi bi-plus-lg"></i> Nueva Categoría';
    document.getElementById('cNombre').classList.remove('is-invalid');
    document.getElementById('btnGuardarCat').disabled = false;
    document.getElementById('btnGuardarCat').innerHTML = '<i class="bi bi-save"></i> Guardar';
  });
});

function abrirNuevaCategoria() {
  document.getElementById('formCategoria').reset();
  document.getElementById('cAction').value = 'create';
  document.getElementById('cId').value = '';
  document.getElementById('tituloModalCat').innerHTML = '<i class="bi bi-plus-lg"></i> Nueva Categoría';
  document.getElementById('cNombre').classList.remove('is-invalid');
  document.getElementById('btnGuardarCat').disabled = false;
  document.getElementById('btnGuardarCat').innerHTML = '<i class="bi bi-save"></i> Guardar';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCategoria')).show();
}

function editCategoria(c) {
  document.getElementById('cAction').value      = 'update';
  document.getElementById('cId').value          = c.id;
  document.getElementById('cNombre').value      = c.nombre || '';
  document.getElementById('cDescripcion').value = c.descripcion || '';
  document.getElementById('cEstado').value      = (c.estado !== undefined) ? c.estado : 1;
  document.getElementById('tituloModalCat').innerHTML = '<i class="bi bi-pencil"></i> Editar Categoría';
  document.getElementById('cNombre').classList.remove('is-invalid');
  document.getElementById('btnGuardarCat').disabled = false;
  document.getElementById('btnGuardarCat').innerHTML = '<i class="bi bi-save"></i> Guardar';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCategoria')).show();
}

function eliminarCategoria(id, nombre, total) {
  if (total > 0) {
    showToast('No se puede eliminar: tiene ' + total + ' producto(s) asociados', 'warning', 5000);
    return;
  }
  confirmar('¿Eliminar la categoría <strong>' + nombre + '</strong>?', 'danger').then(function(ok) {
    if (!ok) return;
    document.getElementById('fDelCatId').value = id;
    document.getElementById('formDelCat').submit();
  });
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
