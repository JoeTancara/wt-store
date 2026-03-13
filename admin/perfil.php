<?php
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
    // No puede cambiar su propio rol ni estado
    $data['rol']    = $me['rol'];
    $data['estado'] = $me['estado'];
    $result = $ctrl->update($me['id'], $data);
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/perfil.php');
}

include __DIR__ . '/../views/partials/header_admin.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">
    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title"><i class="bi bi-person-circle"></i> Mi Perfil</span>
        <span class="badge-status <?= $me['rol'] === 'admin' ? 'badge-admin' : 'badge-vendor' ?>">
          <?= $me['rol'] === 'admin' ? 'Administrador' : 'Vendedor' ?>
        </span>
      </div>
      <div class="p-4">

        <!-- Avatar grande -->
        <div class="text-center mb-4">
          <div style="width:80px;height:80px;border-radius:50%;background:var(--accent);display:inline-flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;margin-bottom:.75rem;">
            <?= strtoupper(mb_substr($me['nombre'], 0, 1)) ?>
          </div>
          <div class="fw-bold" style="font-size:1.1rem;"><?= htmlspecialchars($me['nombre']) ?></div>
          <div style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($me['email']) ?></div>
        </div>

        <hr class="divider">

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($me['nombre']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Correo electrónico *</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($me['email']) ?>">
          </div>
          <div class="mb-4">
            <label class="form-label">Nueva Contraseña</label>
            <div class="input-group">
              <input type="password" name="password" id="passInput" class="form-control"
                     placeholder="Dejar en blanco para no cambiar" minlength="6">
              <button class="btn" type="button" onclick="togglePass()"
                      style="background:var(--bg-primary);border-color:var(--border-color);color:var(--text-muted);">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
            <small class="text-muted">Mínimo 6 caracteres. Dejar en blanco para no modificar.</small>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-primary py-2 fw-bold">
              <i class="bi bi-save"></i> Guardar cambios
            </button>
          </div>
        </form>

        <hr class="divider">
        <div class="text-center">
          <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePass() {
  var inp  = document.getElementById('passInput');
  var icon = document.getElementById('eyeIcon');
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
