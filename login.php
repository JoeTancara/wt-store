<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl   = new AuthController();
    $result = $ctrl->login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - WT Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script>
    (function(){
      var t = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
    var BASE_URL = '<?= BASE_URL ?>';
  </script>
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1rem;
    }
    .login-wrap { width: 100%; max-width: 420px; }
    .login-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      padding: 2.5rem 2rem;
    }
    .login-logo {
      font-size: 2rem;
      font-weight: 800;
      color: var(--accent);
      letter-spacing: -1px;
    }
    .login-sub { color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="text-center mb-4">
      <div class="login-logo"><i class="bi bi-shop"></i> WT Store</div>
      <p class="login-sub">Ingresa tus credenciales para continuar</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label" for="email">Correo electrónico</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="correo@gmail.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label" for="passwordInput">Contraseña</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" id="passwordInput" name="password" class="form-control"
                 placeholder="••••••••" required>
          <button class="btn" type="button" onclick="togglePass()"
                  style="background:var(--bg-primary);border-color:var(--border-color);color:var(--text-muted);">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
      </button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>/index.php" style="font-size:0.85rem;color:var(--text-muted);">
        <i class="bi bi-arrow-left"></i> Volver al catálogo
      </a>
    </div>

    <hr class="divider">
    <div class="text-center" style="font-size:0.78rem;color:var(--text-muted);">
      <div class="mt-2">
        <button class="theme-toggle" type="button"><i class="bi bi-moon-fill"></i> Modo Oscuro</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
function togglePass(){
  var i = document.getElementById('passwordInput');
  var e = document.getElementById('eyeIcon');
  if(i.type === 'password'){ i.type = 'text';     e.className = 'bi bi-eye-slash'; }
  else                     { i.type = 'password'; e.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
