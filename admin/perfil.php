<?php
// admin/perfil.php
$pageTitle = 'Mi Perfil';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/UsuarioController.php';

$ctrl = new UsuarioController();
$me   = $ctrl->findById(currentUser()['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    if (empty($data['password'])) unset($data['password']);
    $data['rol']    = $me['rol'];
    $data['estado'] = $me['estado'];
    $result = $ctrl->update($me['id'], $data);
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/perfil.php');
}

include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-7 col-lg-5">
    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title">
          <i class="bi bi-person-circle"></i> Mi Perfil
        </span>
        <span class="badge-status <?= $me['rol'] === 'admin' ? 'badge-admin' : 'badge-vendor' ?>">
          <i class="bi bi-<?= $me['rol'] === 'admin' ? 'shield-check' : 'person' ?>"></i>
          <?= $me['rol'] === 'admin' ? 'Administrador' : 'Vendedor' ?>
        </span>
      </div>
      <div class="p-3 p-md-4">

        <div class="text-center mb-4">
          <div style="width:70px;height:70px;border-radius:50%;background:var(--accent);display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;margin-bottom:.65rem;">
            <?= strtoupper(mb_substr($me['nombre'], 0, 1)) ?>
          </div>
          <div class="fw-bold" style="font-size:1.05rem;"><?= htmlspecialchars($me['nombre']) ?></div>
          <div style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($me['email']) ?></div>
        </div>

        <hr class="divider">

        <form method="POST" id="formPerfil">
          <div class="mb-3">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" id="pNombre" class="form-control"
                   required maxlength="100"
                   value="<?= htmlspecialchars($me['nombre']) ?>">
            <div class="invalid-feedback">El nombre es obligatorio</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo electrónico *</label>
            <input type="email" name="email" id="pEmail" class="form-control"
                   required value="<?= htmlspecialchars($me['email']) ?>">
            <div class="invalid-feedback">Ingresa un email válido</div>
          </div>
          <div class="mb-4">
            <label class="form-label">
              Nueva Contraseña
              <small class="fw-normal" style="color:var(--text-muted);">(opcional)</small>
            </label>
            <div class="input-group">
              <input type="password" name="password" id="pPass" class="form-control"
                     placeholder="Dejar vacío para no cambiar" minlength="6">
              <button type="button" class="btn btn-outline-secondary" onclick="togglePassPerfil()">
                <i class="bi bi-eye" id="eyeIconPerfil"></i>
              </button>
            </div>
            <div class="invalid-feedback" id="pPassFeedback">Mínimo 6 caracteres</div>
            <small style="color:var(--text-muted);font-size:.74rem;">
              Dejar en blanco para no modificar
            </small>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" id="btnGuardarPerfil">
            <i class="bi bi-save"></i> Guardar cambios
          </button>
        </form>

        <hr class="divider">
        <div class="d-flex gap-2 justify-content-center">
          <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
          <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  ['pNombre','pEmail','pPass'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { this.classList.remove('is-invalid'); });
  });

  document.getElementById('formPerfil').addEventListener('submit', function(e) {
    var nombre = document.getElementById('pNombre');
    var email  = document.getElementById('pEmail');
    var pass   = document.getElementById('pPass');
    var ok = true;

    [nombre, email, pass].forEach(function(el) { el.classList.remove('is-invalid'); });

    if (!nombre.value.trim()) { nombre.classList.add('is-invalid'); ok = false; }

    if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      email.classList.add('is-invalid'); ok = false;
    }

    if (pass.value && pass.value.length < 6) {
      document.getElementById('pPassFeedback').textContent = 'Mínimo 6 caracteres';
      pass.classList.add('is-invalid'); ok = false;
    }

    if (!ok) {
      e.preventDefault();
      var first = document.querySelector('#formPerfil .is-invalid');
      if (first) { first.focus(); first.scrollIntoView({behavior:'smooth',block:'center'}); }
      return;
    }
    var btn = document.getElementById('btnGuardarPerfil');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
  });
});

function togglePassPerfil() {
  var inp  = document.getElementById('pPass');
  var icon = document.getElementById('eyeIconPerfil');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  icon.className = inp.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
