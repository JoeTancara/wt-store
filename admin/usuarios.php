<?php
// admin/usuarios.php
$pageTitle = 'Usuarios';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
require_once __DIR__ . '/../controllers/UsuarioController.php';

$ctrl = new UsuarioController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;
    if ($action === 'create')     $result = $ctrl->create($_POST);
    elseif ($action === 'update') $result = $ctrl->update(intval($_POST['id']), $_POST);
    elseif ($action === 'delete') $result = $ctrl->delete(intval($_POST['id']));
    if ($result) setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/usuarios.php');
}

$usuarios = $ctrl->getAll();
$meId     = currentUser()['id'];
include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">
      <i class="bi bi-people"></i> Usuarios
      <span style="font-size:.78rem;color:var(--text-muted);font-weight:500;">(<?= count($usuarios) ?>)</span>
    </span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Buscar..." style="width:180px;padding-left:2.2rem;">
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="abrirNuevoUsuario()">
        <i class="bi bi-person-plus"></i> Nuevo
      </button>
    </div>
  </div>

  <?php if (empty($usuarios)): ?>
    <div class="empty-state"><i class="bi bi-people"></i><p>No hay usuarios</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table" id="tablaUsuarios">
      <thead>
        <tr>
          <th>Usuario</th>
          <th class="table-hide-mobile">Email</th>
          <th>Rol</th>
          <th class="table-hide-mobile">Estado</th>
          <th class="table-hide-tablet">Creado</th>
          <th style="width:90px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u):
          $uJson = htmlspecialchars(json_encode([
            'id'     => $u['id'],
            'nombre' => $u['nombre'],
            'email'  => $u['email'],
            'rol'    => $u['rol'],
            'estado' => $u['estado'],
          ]), ENT_QUOTES);
        ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                <?= strtoupper(mb_substr($u['nombre'], 0, 1)) ?>
              </div>
              <div style="min-width:0;">
                <strong style="font-size:.88rem;display:block;"><?= htmlspecialchars($u['nombre']) ?></strong>
                <?php if ($u['id'] == $meId): ?>
                  <span style="font-size:.65rem;background:var(--success-light);color:var(--success);border-radius:50px;padding:1px 7px;font-weight:700;">Tú</span>
                <?php endif; ?>
                <div class="d-block d-md-none" style="font-size:.72rem;color:var(--text-muted);">
                  <?= htmlspecialchars($u['email']) ?>
                </div>
              </div>
            </div>
          </td>
          <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);">
            <?= htmlspecialchars($u['email']) ?>
          </td>
          <td>
            <span class="badge-status <?= $u['rol'] === 'admin' ? 'badge-admin' : 'badge-vendor' ?>">
              <i class="bi bi-<?= $u['rol'] === 'admin' ? 'shield-check' : 'person' ?>"></i>
              <?= $u['rol'] === 'admin' ? 'Admin' : 'Vendedor' ?>
            </span>
          </td>
          <td class="table-hide-mobile">
            <span class="badge-status <?= $u['estado'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $u['estado'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td class="table-hide-tablet" style="font-size:.78rem;color:var(--text-muted);">
            <?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary"
                      onclick="editUsuario(<?= $uJson ?>)" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <?php if ($u['id'] != $meId): ?>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      onclick="eliminarUsuario(<?= (int)$u['id'] ?>, '<?= addslashes(htmlspecialchars($u['nombre'])) ?>')"
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
<form method="POST" id="formDelUsr" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="fDelUsrId">
</form>

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="formUsuario">
        <input type="hidden" name="action" id="uAction" value="create">
        <input type="hidden" name="id"     id="uId"     value="">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloModalUsr">
            <i class="bi bi-person-plus"></i> Nuevo Usuario
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" id="uNombre" class="form-control"
                   required maxlength="100" placeholder="Nombre completo">
            <div class="invalid-feedback">El nombre es obligatorio</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="uEmail" class="form-control"
                   required placeholder="correo@ejemplo.com">
            <div class="invalid-feedback">Ingresa un email válido</div>
          </div>
          <div class="mb-3">
            <label class="form-label" id="uPassLabel">Contraseña *</label>
            <div class="input-group">
              <input type="password" name="password" id="uPassword" class="form-control"
                     placeholder="Mínimo 6 caracteres">
              <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
            <div class="invalid-feedback" id="uPassFeedback">La contraseña es obligatoria (mín. 6 caracteres)</div>
            <small id="uPassHint" style="display:none;color:var(--text-muted);font-size:.74rem;">
              Dejar vacío para no cambiar la contraseña
            </small>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Rol</label>
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
          <button type="submit" class="btn btn-primary" id="btnGuardarUsr">
            <i class="bi bi-save"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var _editandoUsuario = false;

document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('searchInput', 'tablaUsuarios', [0, 1]);

  // Limpiar errores al escribir
  ['uNombre','uEmail','uPassword'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { this.classList.remove('is-invalid'); });
  });

  document.getElementById('formUsuario').addEventListener('submit', function(e) {
    var nombre   = document.getElementById('uNombre');
    var email    = document.getElementById('uEmail');
    var password = document.getElementById('uPassword');
    var ok = true;

    [nombre, email, password].forEach(function(el) { el.classList.remove('is-invalid'); });

    if (!nombre.value.trim()) { nombre.classList.add('is-invalid'); ok = false; }

    var emailVal = email.value.trim();
    if (!emailVal || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
      email.classList.add('is-invalid'); ok = false;
    }

    // Contraseña: requerida en crear, opcional en editar (pero si se ingresa debe tener 6+)
    var passVal = password.value;
    if (!_editandoUsuario && !passVal) {
      document.getElementById('uPassFeedback').textContent = 'La contraseña es obligatoria';
      password.classList.add('is-invalid'); ok = false;
    } else if (passVal && passVal.length < 6) {
      document.getElementById('uPassFeedback').textContent = 'Mínimo 6 caracteres';
      password.classList.add('is-invalid'); ok = false;
    }

    if (!ok) {
      e.preventDefault();
      var first = document.querySelector('#formUsuario .is-invalid');
      if (first) { first.focus(); first.scrollIntoView({behavior:'smooth',block:'center'}); }
      return;
    }
    document.getElementById('btnGuardarUsr').disabled = true;
    document.getElementById('btnGuardarUsr').innerHTML =
      '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });

  document.getElementById('modalUsuario').addEventListener('hidden.bs.modal', function() {
    resetModalUsuario();
  });
});

function resetModalUsuario() {
  _editandoUsuario = false;
  document.getElementById('formUsuario').reset();
  document.getElementById('uAction').value = 'create';
  document.getElementById('uId').value = '';
  document.getElementById('tituloModalUsr').innerHTML = '<i class="bi bi-person-plus"></i> Nuevo Usuario';
  document.getElementById('uPassLabel').textContent = 'Contraseña *';
  document.getElementById('uPassHint').style.display = 'none';
  document.getElementById('btnGuardarUsr').disabled = false;
  document.getElementById('btnGuardarUsr').innerHTML = '<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formUsuario .is-invalid').forEach(function(el) {
    el.classList.remove('is-invalid');
  });
}

function abrirNuevoUsuario() {
  resetModalUsuario();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();
}

function editUsuario(u) {
  _editandoUsuario = true;
  document.getElementById('uAction').value  = 'update';
  document.getElementById('uId').value      = u.id;
  document.getElementById('uNombre').value  = u.nombre || '';
  document.getElementById('uEmail').value   = u.email  || '';
  document.getElementById('uRol').value     = u.rol    || 'vendedor';
  document.getElementById('uEstado').value  = (u.estado !== undefined) ? u.estado : 1;
  document.getElementById('uPassword').value = '';
  document.getElementById('uPassLabel').textContent = 'Nueva contraseña (opcional)';
  document.getElementById('uPassHint').style.display = '';
  document.getElementById('tituloModalUsr').innerHTML = '<i class="bi bi-pencil"></i> Editar Usuario';
  document.getElementById('btnGuardarUsr').disabled = false;
  document.getElementById('btnGuardarUsr').innerHTML = '<i class="bi bi-save"></i> Guardar';
  document.querySelectorAll('#formUsuario .is-invalid').forEach(function(el) {
    el.classList.remove('is-invalid');
  });
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();
}

function eliminarUsuario(id, nombre) {
  confirmar('¿Eliminar el usuario <strong>' + nombre + '</strong>?', 'danger').then(function(ok) {
    if (!ok) return;
    document.getElementById('fDelUsrId').value = id;
    document.getElementById('formDelUsr').submit();
  });
}

function togglePass() {
  var inp  = document.getElementById('uPassword');
  var icon = document.getElementById('eyeIcon');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  icon.className = inp.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
