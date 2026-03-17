<?php
// admin/dashboard.php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../controllers/EgresoController.php';
require_once __DIR__ . '/../models/Producto.php';

$ventaCtrl  = new VentaController();
$egresoCtrl = new EgresoController();
$prodMdl    = new Producto();

// Vendedor solo ve sus propios datos
$uid     = isAdmin() ? null : currentUser()['id'];
$stats   = $ventaCtrl->getStats($uid);
$productos     = $prodMdl->getAll();
$stockBajo     = array_filter($productos, fn($p) => (int)$p['stock'] <= 5 && $p['estado']);
$ultimasVentas = $ventaCtrl->getAll(6, $uid);

// Grafico ingresos/egresos (admin) o solo ingresos (vendedor)
$ingEgrData   = $stats['ingresos_egresos'];
$chartLabels  = [];
$chartIngresos = [];
$chartEgresos  = [];
$diasES = ['Sun'=>'Dom','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mie','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sab'];
foreach ($ingEgrData as $row) {
    $ts = strtotime($row['dia']);
    $chartLabels[]   = ($diasES[date('D',$ts)] ?? date('D',$ts)) . ' ' . date('d',$ts);
    $chartIngresos[] = floatval($row['ingresos']);
    $chartEgresos[]  = floatval($row['egresos']);
}

include __DIR__ . '/../views/partials/header_admin.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
      <div><div class="stat-value">Bs. <?= number_format($stats['total_hoy'],2) ?></div>
      <div class="stat-label">Ingresos Hoy</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-graph-up-arrow"></i></div>
      <div><div class="stat-value">Bs. <?= number_format($stats['total_mes'],2) ?></div>
      <div class="stat-label">Ingresos del Mes</div></div>
    </div>
  </div>
  <?php if (isAdmin()):
    $egresoStats = $egresoCtrl->getStats();
    $balance     = $stats['total_mes'] - $egresoStats['total_mes'];
    $balPos      = $balance >= 0;
  ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-graph-down-arrow"></i></div>
      <div><div class="stat-value">Bs. <?= number_format($egresoStats['total_mes'],2) ?></div>
      <div class="stat-label">Egresos del Mes</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon <?= $balPos ? 'green' : 'red' ?>">
        <i class="bi bi-<?= $balPos ? 'trending-up' : 'trending-down' ?>"></i>
      </div>
      <div>
        <div class="stat-value" style="color:var(--<?= $balPos ? 'success' : 'danger' ?>);">
          <?= $balPos ? '' : '-' ?>Bs. <?= number_format(abs($balance),2) ?>
        </div>
        <div class="stat-label">Balance del Mes</div>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
      <div><div class="stat-value"><?= $stats['count_hoy'] ?></div>
      <div class="stat-label">Ventas Hoy</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-box-seam"></i></div>
      <div><div class="stat-value"><?= count($productos) ?></div>
      <div class="stat-label">Productos</div></div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Grafico + Stock -->
<div class="row g-4 mb-4">
  <div class="col-12 col-lg-8">
    <div class="table-card h-100">
      <div class="table-card-header">
        <span class="table-card-title">
          <i class="bi bi-bar-chart-line"></i>
          <?= isAdmin() ? 'Ingresos vs Egresos' : 'Mis Ingresos' ?> — Ultimos 7 dias
        </span>
        <?php if (isAdmin()): ?>
        <div class="d-flex gap-3" style="font-size:.78rem;font-weight:600;">
          <span style="color:var(--success);">
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--success);margin-right:4px;"></span>Ingresos
          </span>
          <span style="color:var(--danger);">
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--danger);margin-right:4px;"></span>Egresos
          </span>
        </div>
        <?php endif; ?>
      </div>
      <div class="p-3" style="position:relative;min-height:220px;">
        <canvas id="ingEgrChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="table-card h-100">
      <div class="table-card-header">
        <span class="table-card-title">
          <i class="bi bi-exclamation-triangle" style="color:var(--warning);"></i> Stock Bajo
        </span>
        <span class="badge bg-warning text-dark"><?= count($stockBajo) ?></span>
      </div>
      <?php if (empty($stockBajo)): ?>
        <div class="empty-state py-3">
          <i class="bi bi-check-circle" style="color:var(--success);font-size:2rem;"></i>
          <p class="mt-2 mb-0" style="font-size:.88rem;">Sin alertas de stock</p>
        </div>
      <?php else: ?>
      <div class="p-2" style="max-height:250px;overflow-y:auto;">
        <?php foreach (array_slice($stockBajo, 0, 10) as $p): ?>
        <div class="d-flex align-items-center justify-content-between p-2 mb-1"
             style="background:var(--bg-primary);border-radius:var(--radius-sm);">
          <span style="font-size:.83rem;font-weight:600;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($p['nombre']) ?>
          </span>
          <span class="badge ms-2 flex-shrink-0 <?= (int)$p['stock']===0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
            <?= (int)$p['stock']===0 ? 'Sin stock' : $p['stock'].' uds' ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Ultimas ventas -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">
      <i class="bi bi-clock-history"></i>
      <?= isAdmin() ? 'Ultimas Ventas' : 'Mis Ultimas Ventas' ?>
    </span>
    <a href="<?= BASE_URL ?>/admin/ventas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
  </div>
  <?php if (empty($ultimasVentas)): ?>
    <div class="empty-state py-3"><i class="bi bi-receipt"></i><p>Sin ventas aun</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <?php if (isAdmin()): ?><th class="table-hide-mobile">Vendedor</th><?php endif; ?>
          <th>Pago</th>
          <th>Total</th>
          <th>Estado</th>
          <th class="table-hide-mobile">Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ultimasVentas as $v): ?>
        <tr>
          <td style="color:var(--text-muted);font-weight:600;">#<?= $v['id'] ?></td>
          <?php if (isAdmin()): ?>
          <td class="table-hide-mobile" style="font-size:.85rem;"><?= htmlspecialchars($v['vendedor_nombre']) ?></td>
          <?php endif; ?>
          <td>
            <span class="badge-status <?= $v['tipo_pago']==='qr' ? 'badge-qr' : 'badge-efectivo' ?>">
              <i class="bi bi-<?= $v['tipo_pago']==='qr' ? 'qr-code' : 'cash' ?>"></i>
              <?= ucfirst($v['tipo_pago']) ?>
            </span>
          </td>
          <td class="text-money fw-bold"
              style="color:var(--<?= $v['estado'] ? 'success' : 'text-muted' ?>);<?= !$v['estado'] ? 'text-decoration:line-through;opacity:.6;' : '' ?>">
            Bs. <?= number_format($v['total'],2) ?>
          </td>
          <td>
            <?php if ($v['estado']): ?>
              <span class="badge-status badge-active">Activa</span>
            <?php else: ?>
              <span class="badge-status badge-inactive">Anulada</span>
            <?php endif; ?>
          </td>
          <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);">
            <?= date('d/m/Y H:i', strtotime($v['fecha'])) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var labels   = <?= json_encode($chartLabels) ?>;
  var ingresos = <?= json_encode($chartIngresos) ?>;
  var egresos  = <?= json_encode($chartEgresos) ?>;
  var isDark   = localStorage.getItem('theme') === 'dark';
  var grid     = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
  var text     = isDark ? '#94a3b8' : '#6b7280';
  var ctx      = document.getElementById('ingEgrChart');
  if (!ctx) return;

  var datasets = [{
    label: 'Ingresos',
    data: ingresos,
    backgroundColor: 'rgba(16,185,129,0.75)',
    borderColor: 'rgba(16,185,129,1)',
    borderWidth: 2, borderRadius: 6, borderSkipped: false
  }];
  <?php if (isAdmin()): ?>
  datasets.push({
    label: 'Egresos',
    data: egresos,
    backgroundColor: 'rgba(239,68,68,0.65)',
    borderColor: 'rgba(239,68,68,1)',
    borderWidth: 2, borderRadius: 6, borderSkipped: false
  });
  <?php endif; ?>

  new Chart(ctx, {
    type: 'bar',
    data: { labels: labels, datasets: datasets },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: function(c){ return c.dataset.label+': Bs. '+c.parsed.y.toFixed(2); } } }
      },
      scales: {
        x: { grid:{color:grid}, ticks:{color:text} },
        y: { grid:{color:grid}, ticks:{color:text, callback:function(v){ return 'Bs. '+v; }}, beginAtZero:true }
      }
    }
  });
});
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
