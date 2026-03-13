<?php

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../controllers/EgresoController.php';
require_once __DIR__ . '/../models/Producto.php';

$ventaCtrl   = new VentaController();
$egresoCtrl  = new EgresoController();
$productoMdl = new Producto();

$stats         = $ventaCtrl->getStats();
$egresoStats   = $egresoCtrl->getStats();
$productos     = $productoMdl->getAll();
$stockBajo     = array_filter($productos, function($p){ return (int)$p['stock'] <= 5 && $p['estado']; });
$ultimasVentas = $ventaCtrl->getAll(6);
$totalProductos= count($productos);

// Datos para el gráfico combinado ingresos/egresos (7 días)
$ingEgrData = $stats['ingresos_egresos'];
$chartLabels  = [];
$chartIngresos= [];
$chartEgresos = [];
foreach ($ingEgrData as $row) {
    // Formato día "Lun 12"
    $ts = strtotime($row['dia']);
    $dias = ['Sun'=>'Dom','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb'];
    $label = ($dias[date('D', $ts)] ?? date('D', $ts)) . ' ' . date('d', $ts);
    $chartLabels[]   = $label;
    $chartIngresos[] = floatval($row['ingresos']);
    $chartEgresos[]  = floatval($row['egresos']);
}

include __DIR__ . '/../views/partials/header_admin.php';
?>

<!-- ===== Stat Cards ===== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
      <div>
        <div class="stat-value">Bs<?= number_format($stats['total_hoy'], 2) ?></div>
        <div class="stat-label">Ingresos Hoy</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-graph-up-arrow"></i></div>
      <div>
        <div class="stat-value">Bs<?= number_format($stats['total_mes'], 2) ?></div>
        <div class="stat-label">Ingresos del Mes</div>
      </div>
    </div>
  </div>
  <?php if (isAdmin()): ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-graph-down-arrow"></i></div>
      <div>
        <div class="stat-value">Bs<?= number_format($egresoStats['total_mes'], 2) ?></div>
        <div class="stat-label">Egresos del Mes</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <?php
        $balance = $stats['total_mes'] - $egresoStats['total_mes'];
        $balancePositivo = $balance >= 0;
      ?>
      <div class="stat-icon <?= $balancePositivo ? 'green' : 'red' ?>">
        <i class="bi bi-<?= $balancePositivo ? 'trending-up' : 'trending-down' ?>"></i>
      </div>
      <div>
        <div class="stat-value" style="color:var(--<?= $balancePositivo ? 'success' : 'danger' ?>);">
          <?= $balancePositivo ? '' : '-' ?>Bs<?= number_format(abs($balance), 2) ?>
        </div>
        <div class="stat-label">Balance del Mes</div>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
      <div>
        <div class="stat-value"><?= $stats['count_hoy'] ?></div>
        <div class="stat-label">Ventas Hoy</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-box-seam"></i></div>
      <div>
        <div class="stat-value"><?= $totalProductos ?></div>
        <div class="stat-label">Total Productos</div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ===== Gráfico + Stock ===== -->
<div class="row g-4 mb-4">

  <!-- Gráfico ingresos vs egresos -->
  <div class="col-lg-8">
    <div class="table-card h-100">
      <div class="table-card-header">
        <span class="table-card-title">
          <i class="bi bi-bar-chart-line"></i> Ingresos vs Egresos — Últimos 7 días
        </span>
        <div class="d-flex gap-3" style="font-size:.78rem;font-weight:600;">
          <span style="color:var(--success);"><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--success);margin-right:4px;"></span>Ingresos</span>
          <span style="color:var(--danger);"><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--danger);margin-right:4px;"></span>Egresos</span>
        </div>
      </div>
      <div class="p-3" style="position:relative;min-height:240px;">
        <canvas id="ingEgrChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Stock bajo -->
  <div class="col-lg-4">
    <div class="table-card h-100">
      <div class="table-card-header">
        <span class="table-card-title">
          <i class="bi bi-exclamation-triangle" style="color:var(--warning);"></i> Stock Bajo
        </span>
        <span class="badge bg-warning text-dark"><?= count($stockBajo) ?></span>
      </div>
      <?php if (empty($stockBajo)): ?>
        <div class="empty-state py-4">
          <i class="bi bi-check-circle" style="color:var(--success);font-size:2rem;"></i>
          <p class="mt-2 mb-0" style="font-size:.9rem;">Sin alertas de stock</p>
        </div>
      <?php else: ?>
      <div class="p-2" style="max-height:260px;overflow-y:auto;">
        <?php foreach (array_slice($stockBajo, 0, 10) as $p): ?>
        <a href="<?= BASE_URL ?>/admin/productos.php" class="text-decoration-none d-block">
          <div class="d-flex align-items-center justify-content-between p-2 mb-1"
               style="background:var(--bg-primary);border-radius:var(--radius-sm);cursor:pointer;transition:background .15s;"
               onmouseover="this.style.background='var(--accent-light)'"
               onmouseout="this.style.background='var(--bg-primary)'">
            <div style="font-size:.85rem;font-weight:600;color:var(--text-primary);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= htmlspecialchars($p['nombre']) ?>
            </div>
            <span class="badge ms-2 flex-shrink-0 <?= (int)$p['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
              <?= (int)$p['stock'] === 0 ? 'Sin stock' : $p['stock'] . ' uds' ?>
            </span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ===== Últimas Ventas ===== -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-clock-history"></i> Últimas Ventas</span>
    <a href="<?= BASE_URL ?>/admin/ventas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
  </div>
  <?php if (empty($ultimasVentas)): ?>
    <div class="empty-state py-4">
      <i class="bi bi-receipt"></i>
      <p>No hay ventas registradas aún</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Vendedor</th>
          <th>Pago</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ultimasVentas as $v): ?>
        <tr>
          <td style="color:var(--text-muted);">#<?= $v['id'] ?></td>
          <td><?= htmlspecialchars($v['vendedor_nombre']) ?></td>
          <td>
            <span class="badge-status <?= $v['tipo_pago'] === 'qr' ? 'badge-qr' : 'badge-efectivo' ?>">
              <i class="bi bi-<?= $v['tipo_pago'] === 'qr' ? 'qr-code' : 'cash' ?>"></i>
              <?= ucfirst($v['tipo_pago']) ?>
            </span>
          </td>
          <td class="text-money fw-bold"
              style="color:var(--<?= $v['estado'] ? 'success' : 'text-muted' ?>);<?= !$v['estado'] ? 'text-decoration:line-through;' : '' ?>">
            Bs<?= number_format($v['total'], 2) ?>
          </td>
          <td>
            <?php if ($v['estado']): ?>
              <span class="badge-status badge-active">Activa</span>
            <?php else: ?>
              <span class="badge-status badge-inactive">Anulada</span>
            <?php endif; ?>
          </td>
          <td style="color:var(--text-muted);font-size:.82rem;"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ===== Chart.js ===== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var labels   = <?= json_encode($chartLabels) ?>;
  var ingresos = <?= json_encode($chartIngresos) ?>;
  var egresos  = <?= json_encode($chartEgresos) ?>;

  var isDark    = localStorage.getItem('theme') === 'dark';
  var gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
  var textColor = isDark ? '#94a3b8' : '#6b7280';

  var ctx = document.getElementById('ingEgrChart');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Ingresos',
          data: ingresos,
          backgroundColor: 'rgba(16,185,129,0.75)',
          borderColor: 'rgba(16,185,129,1)',
          borderWidth: 2,
          borderRadius: 6,
          borderSkipped: false
        },
        {
          label: 'Egresos',
          data: egresos,
          backgroundColor: 'rgba(239,68,68,0.65)',
          borderColor: 'rgba(239,68,68,1)',
          borderWidth: 2,
          borderRadius: 6,
          borderSkipped: false
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx){ return ctx.dataset.label + ': Bs' + ctx.parsed.y.toFixed(2); }
          }
        }
      },
      scales: {
        x: { grid: { color: gridColor }, ticks: { color: textColor } },
        y: {
          grid: { color: gridColor },
          ticks: { color: textColor, callback: function(v){ return 'Bs ' + v; } },
          beginAtZero: true
        }
      }
    }
  });
});
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
