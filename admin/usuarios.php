<?php

$pageTitle = 'Usuarios';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../controllers/UsuarioController.php';

$ctrl = new UsuarioController();

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
    redirect(BASE_URL . '/admin/usuarios.php');
}

$usuarios = $ctrl->getAll();
$meId     = currentUser()['id'];
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-people"></i> Gestión de Usuarios</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:200px;padding-left:2.2rem;">
      </div>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
      </button>
    </div>
  </div>

  <?php if (empty($usuarios)): ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>
      <p>No hay usuarios registrados</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaUsuarios">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Creado</th>
          <th style="width:100px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;">
                <?= strtoupper(mb_substr($u['nombre'], 0, 1)) ?>
              </div>
              <strong style="font-size:.9rem;"><?= htmlspecialchars($u['nombre']) ?></strong>
              <?php if ($u['id'] == $meId): ?>
                <span style="font-size:.7rem;background:var(--success-light);color:var(--success);border-radius:50px;padding:2px 8px;font-weight:700;">Tú</span>
              <?php endif; ?>
            </div>
          </td>
          <td style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
          <td>
            <span class="badge-status <?= $u['rol'] === 'admin' ? 'badge-admin' : 'badge-vendor' ?>">
              <i class="bi bi-<?= $u['rol'] === 'admin' ? 'shield-check' : 'person' ?>"></i>
              <?= $u['rol'] === 'admin' ? 'Administrador' : 'Vendedor' ?>
            </span>
          </td>
          <td>
            <span class="badge-status <?= $u['estado'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $u['estado'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td style="font-size:.82rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></td>
          <td>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-primary"
                      onclick="editUsuario(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)"
                      title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <?php if ($u['id'] != $meId): ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$u['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
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

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formUsuario">
        <input type="hidden" name="action" id="usuarioAction" value="create">
        <input type="hidden" name="id"     id="usuarioId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="modalUsuarioTitle"><i class="bi bi-person-plus"></i> Nuevo Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" id="uNombre" class="form-control" required maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label">Correo electrónico *</label>
            <input type="email" name="email" id="uEmail" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" id="passLabel">Contraseña *</label>
            <div class="input-group">
              <input type="password" name="password" id="uPassword" class="form-control"
                     placeholder="Mínimo 6 caracteres">
              <button class="btn" type="button" onclick="togglePassModal()"
                      style="background:var(--bg-primary);border-color:var(--border-color);color:var(--text-muted);">
                <i class="bi bi-eye" id="eyeIconModal"></i>
              </button>
            </div>
            <small id="passHint" class="text-muted" style="display:none;">Dejar en blanco para no cambiar la contraseña</small>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Rol *</label>
              <select name="rol" id="uRol" class="form-select">
                <option value="vendedor">Vendedor</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Estado</label>
              <select name="estado" id="uEstado" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
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

<script>
document.addEventListener('DOMContentLoaded', function(){
  initTableSearch('searchInput', 'tablaUsuarios', [0, 1]);

  document.getElementById('modalUsuario').addEventListener('hidden.bs.modal', function(){
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioAction').value = 'create';
    document.getElementById('usuarioId').value     = '';
    document.getElementById('passLabel').textContent = 'Contraseña *';
    document.getElementById('passHint').style.display = 'none';
    document.getElementById('uPassword').required = true;
    document.getElementById('modalUsuarioTitle').innerHTML = '<i class="bi bi-person-plus"></i> Nuevo Usuario';
  });
});

function editUsuario(u) {
  document.getElementById('usuarioAction').value = 'update';
  document.getElementById('usuarioId').value     = u.id;
  document.getElementById('uNombre').value       = u.nombre  || '';
  document.getElementById('uEmail').value        = u.email   || '';
  document.getElementById('uRol').value          = u.rol     || 'vendedor';
  document.getElementById('uEstado').value       = u.estado !== undefined ? u.estado : 1;
  document.getElementById('uPassword').value     = '';
  document.getElementById('uPassword').required  = false;
  document.getElementById('passLabel').textContent = 'Nueva Contraseña';
  document.getElementById('passHint').style.display = '';
  document.getElementById('modalUsuarioTitle').innerHTML = '<i class="bi bi-pencil"></i> Editar Usuario';
  new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

function togglePassModal() {
  var inp  = document.getElementById('uPassword');
  var icon = document.getElementById('eyeIconModal');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
