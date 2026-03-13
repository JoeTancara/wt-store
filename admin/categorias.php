<?php

$pageTitle = 'Categorías';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../controllers/CategoriaController.php';

$ctrl = new CategoriaController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;
    if ($action === 'create') {
        $result = $ctrl->create($_POST);
    } elseif ($action === 'update') {
        $result = $ctrl->update(intval($_POST['id']), $_POST);
    } elseif ($action === 'delete') {
        $result = $ctrl->delete(intval($_POST['id']));
    }
    if ($result) setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/categorias.php');
}

$categorias = $ctrl->getWithCount();
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-tags"></i> Gestión de Categorías</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:200px;padding-left:2.2rem;">
      </div>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCategoria">
        <i class="bi bi-plus-lg"></i> Nueva Categoría
      </button>
    </div>
  </div>

  <?php if (empty($categorias)): ?>
    <div class="empty-state">
      <i class="bi bi-tags"></i>
      <p>No hay categorías registradas</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaCategorias">
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Productos</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th style="width:100px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $c): ?>
        <tr>
          <td style="color:var(--text-muted);"><?= (int)$c['id'] ?></td>
          <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
          <td style="color:var(--text-muted);font-size:.85rem;max-width:220px;">
            <?php if ($c['descripcion']): ?>
              <?= htmlspecialchars(mb_substr($c['descripcion'], 0, 60)) ?><?= mb_strlen($c['descripcion']) > 60 ? '…' : '' ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <span style="background:var(--accent-light);color:var(--accent);border-radius:50px;padding:.25rem .75rem;font-size:.78rem;font-weight:700;">
              <?= (int)$c['total_productos'] ?>
            </span>
          </td>
          <td>
            <span class="badge-status <?= $c['estado'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $c['estado'] ? 'Activa' : 'Inactiva' ?>
            </span>
          </td>
          <td style="font-size:.82rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($c['fecha_creacion'])) ?></td>
          <td>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-primary"
                      onclick="editCategoria(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)"
                      title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" style="display:inline;"
                    onsubmit="return confirm('¿Eliminar? Solo es posible si no tiene productos.')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$c['id'] ?>">
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

<!-- Modal Categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formCategoria">
        <input type="hidden" name="action" id="catAction" value="create">
        <input type="hidden" name="id"     id="catId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCatTitle"><i class="bi bi-plus-lg"></i> Nueva Categoría</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" id="catNombre" class="form-control" required maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" id="catDescripcion" class="form-control" rows="3" maxlength="500"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado" id="catEstado" class="form-select">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
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
  initTableSearch('searchInput', 'tablaCategorias', [1, 2]);

  document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function(){
    document.getElementById('formCategoria').reset();
    document.getElementById('catAction').value = 'create';
    document.getElementById('catId').value     = '';
    document.getElementById('modalCatTitle').innerHTML = '<i class="bi bi-plus-lg"></i> Nueva Categoría';
  });
});

function editCategoria(c) {
  document.getElementById('catAction').value      = 'update';
  document.getElementById('catId').value          = c.id;
  document.getElementById('catNombre').value      = c.nombre || '';
  document.getElementById('catDescripcion').value = c.descripcion || '';
  document.getElementById('catEstado').value      = c.estado !== undefined ? c.estado : 1;
  document.getElementById('modalCatTitle').innerHTML = '<i class="bi bi-pencil"></i> Editar Categoría';
  new bootstrap.Modal(document.getElementById('modalCategoria')).show();
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
