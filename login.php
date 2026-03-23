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
  <title>Iniciar Sesión</title>
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
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
      background: linear-gradient(135deg, var(--accent) 0%, #7c3aed 100%);
    }
    [data-theme="dark"] body {
      background: linear-gradient(135deg, #1e1b4b 0%, #0f1117 100%);
    }
    .login-wrap {
      width: 100%;
      max-width: 420px;
    }
    .login-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      padding: 2.25rem 2rem;
    }
    .login-logo-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 1.25rem;
    }
    .login-logo-img {
      width: 200px;
      height: 200px;
      object-fit: contain;
      border-radius: 50%;
      filter: drop-shadow(0 4px 12px rgba(0,0,0,0.12));
    }
    .login-sub {
      color: var(--text-muted);
      font-size: 0.88rem;
      margin-top: 0.2rem;
    }
    .input-group-text {
      background: var(--bg-primary) !important;
      border-color: var(--border-color) !important;
      color: var(--text-muted) !important;
    }
    /* Responsive: mantener tamaño razonable en movil */
    @media (max-width: 480px) {
      .login-card { padding: 1.75rem 1.25rem; }
      .login-logo-img { width: 160px; height: 160px; }
    }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">

    <!-- Logo -->
    <div class="login-logo-wrap">
      <img src="<?= BASE_URL ?>/logo.png"
           alt="Logo WT Store"
           class="login-logo-img"
           onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
      <div id="logoFallback" style="display:none;font-size:2rem;font-weight:800;color:var(--accent);">
        <i class="bi bi-shop"></i> WT Store
      </div>
    </div>

    <div class="text-center mb-4">
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
      <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
      </button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>/index.php"
         style="font-size:0.83rem;color:var(--text-muted);text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Volver al catálogo
      </a>
    </div>

    <hr class="divider">
    <div class="text-center" style="font-size:0.76rem;color:var(--text-muted);">
      <div class="mt-2">
        <button class="theme-toggle" type="button">
          <i class="bi bi-moon-fill"></i> Modo Oscuro
        </button>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
function togglePass() {
  var i = document.getElementById('passwordInput');
  var e = document.getElementById('eyeIcon');
  if (i.type === 'password') { i.type = 'text';     e.className = 'bi bi-eye-slash'; }
  else                       { i.type = 'password'; e.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
