<?php
// admin/reportes.php
$pageTitle = 'Reportes';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireAdmin();
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../controllers/EgresoController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';

$ventaCtrl  = new VentaController();
$egresoCtrl = new EgresoController();
$prodCtrl   = new ProductoController();
$db         = Database::getInstance();

$hoy   = date('Y-m-d');
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? $hoy;
$vendId = intval($_GET['vendedor'] ?? 0);

// Datos del período
$ventas      = $ventaCtrl->getVentasRango($desde, $hasta, $vendId ?: null);
$topProductos= $prodCtrl->getTopVendidos(10, $desde, $hasta);
$inventario  = $prodCtrl->getInventarioValorizado();
$ganProd     = $prodCtrl->getGananciasPorProducto($desde, $hasta);
$resumenMes  = $ventaCtrl->getResumenMensual(6);
$arqueomeses = $ventaCtrl->getArqueoMensual(6);
$egresos     = $egresoCtrl->getAll(null, $desde, $hasta);
$ganRango    = $ventaCtrl->getGananciasRango($desde, $hasta);

// KPIs período
$totalIngresos = array_sum(array_map(fn($v)=>$v['estado']?floatval($v['total']):0, $ventas));
$totalAnuladas = count(array_filter($ventas, fn($v)=>!$v['estado']));
$totalVentas   = count(array_filter($ventas, fn($v)=> $v['estado']));
$totalEgresos  = array_sum(array_map(fn($e)=>floatval($e['monto']), $egresos ?? []));
$balance       = $totalIngresos - $totalEgresos;
$ganancia      = floatval($ganRango['ganancia'] ?? 0);
$costos        = floatval($ganRango['costos']   ?? 0);
$margen        = $totalIngresos > 0 ? round($ganancia/$totalIngresos*100,1) : 0;

// Inventario
$valorCompraTotal  = array_sum(array_map(fn($p)=>floatval($p['valor_compra']),  $inventario));
$valorVentaTotal   = array_sum(array_map(fn($p)=>floatval($p['valor_venta']),   $inventario));
$ganPotencial      = array_sum(array_map(fn($p)=>floatval($p['ganancia_potencial']), $inventario));
$stockCritico      = array_filter($inventario, fn($p)=>(int)$p['stock']>0&&(int)$p['stock']<=5&&(int)$p['estado']);
$sinStock          = array_filter($inventario, fn($p)=>(int)$p['stock']===0&&(int)$p['estado']);

// Gráficos
$ventasPorDia = [];
foreach ($ventas as $v) {
    if (!$v['estado']) continue;
    $dia = date('d/m', strtotime($v['fecha']));
    $ventasPorDia[$dia] = ($ventasPorDia[$dia] ?? 0) + floatval($v['total']);
}
$mesesLabels=[]; $mesesIngresos=[]; $mesesGanancia=[];
foreach ($arqueomeses as $r) {
    $mesesLabels[]  = date('M Y', strtotime($r['mes'].'-01'));
    $mesesIngresos[] = floatval($r['ingresos']);
    $mesesGanancia[] = floatval($r['ganancia']);
}
$efeCount = array_sum(array_map(fn($v)=>($v['estado']&&$v['tipo_pago']==='efectivo')?1:0, $ventas));
$qrCount  = array_sum(array_map(fn($v)=>($v['estado']&&$v['tipo_pago']==='qr')?1:0, $ventas));

$vRows = $db->query("SELECT id,nombre FROM usuarios WHERE estado=1 ORDER BY nombre");
$vendedores = $vRows ? $vRows->fetch_all(MYSQLI_ASSOC) : [];

include __DIR__ . '/../views/partials/header_admin.php';
?>

<!-- Filtros -->
<div class="table-card mb-4">
  <div class="table-card-header">
    <span class="table-card-title"><i class="bi bi-funnel"></i> Filtros</span>
    <div class="d-flex gap-1 flex-wrap">
      <?php $r0="?desde=$hoy&hasta=$hoy"; $r1="?desde=".date('Y-m-d',strtotime('-1 day'))."&hasta=".date('Y-m-d',strtotime('-1 day')); $r7="?desde=".date('Y-m-d',strtotime('-6 days'))."&hasta=$hoy"; $rm="?desde=".date('Y-m-01')."&hasta=$hoy"; ?>
      <a href="<?=$r0?>" class="btn btn-sm btn-outline-secondary <?=($desde==$hoy&&$hasta==$hoy)?'active':''?>">Hoy</a>
      <a href="<?=$r1?>" class="btn btn-sm btn-outline-secondary">Ayer</a>
      <a href="<?=$r7?>" class="btn btn-sm btn-outline-secondary">7 días</a>
      <a href="<?=$rm?>" class="btn btn-sm btn-outline-secondary">Este mes</a>
    </div>
  </div>
  <div class="p-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-6 col-md-3"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="<?=htmlspecialchars($desde)?>"></div>
      <div class="col-6 col-md-3"><label class="form-label">Hasta</label><input type="date" name="hasta" class="form-control" value="<?=htmlspecialchars($hasta)?>"></div>
      <div class="col-12 col-md-3">
        <label class="form-label">Vendedor</label>
        <select name="vendedor" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($vendedores as $vend): ?><option value="<?=$vend['id']?>" <?=$vendId==$vend['id']?'selected':''?>><?=htmlspecialchars($vend['nombre'])?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filtrar</button>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()" title="Imprimir"><i class="bi bi-printer"></i></button>
      </div>
    </form>
  </div>
</div>

<!-- KPIs principales -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
      <div><div class="stat-value">Bs.<?=number_format($totalIngresos,0)?></div><div class="stat-label">Ingresos período</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon <?=$ganancia>=0?'purple':'red'?>"><i class="bi bi-graph-up-arrow"></i></div>
      <div><div class="stat-value" style="color:var(--<?=$ganancia>=0?'success':'danger'?>);">Bs.<?=number_format($ganancia,0)?></div><div class="stat-label">Ganancia neta</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-graph-down-arrow"></i></div>
      <div><div class="stat-value">Bs.<?=number_format($totalEgresos,0)?></div><div class="stat-label">Egresos período</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon <?=$balance>=0?'blue':'red'?>"><i class="bi bi-<?=$balance>=0?'trending-up':'trending-down'?>"></i></div>
      <div><div class="stat-value" style="color:var(--<?=$balance>=0?'success':'danger'?>);"><?=$balance<0?'−':''?>Bs.<?=number_format(abs($balance),0)?></div><div class="stat-label">Balance (ventas-gastos)</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
      <div><div class="stat-value"><?=$totalVentas?><?=$totalAnuladas?" <small style='font-size:.65rem;color:var(--danger);'>(<?=$totalAnuladas?> anul.)</small>":''?></div><div class="stat-label">Ventas activas</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-percent"></i></div>
      <div><div class="stat-value"><?=$margen?>%</div><div class="stat-label">Margen de ganancia</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-boxes"></i></div>
      <div><div class="stat-value" style="font-size:.95rem;">Bs.<?=number_format($valorVentaTotal,0)?></div><div class="stat-label">Valor inventario</div></div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-cash-coin"></i></div>
      <div><div class="stat-value" style="font-size:.95rem;">Bs.<?=number_format($ganPotencial,0)?></div><div class="stat-label">Gan. potencial inv.</div></div></div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rVentas"    type="button"><i class="bi bi-receipt"></i> Ventas</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#rGanancias" type="button"><i class="bi bi-graph-up-arrow"></i> Ganancias</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#rArqueo"    type="button"><i class="bi bi-calculator"></i> Arqueo</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#rProductos" type="button"><i class="bi bi-trophy"></i> Top Productos</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#rInventario" type="button"><i class="bi bi-boxes"></i> Inventario</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#rGraficos"  type="button"><i class="bi bi-bar-chart-line"></i> Gráficos</button></li>
</ul>

<div class="tab-content">

<!-- ===== VENTAS ===== -->
<div class="tab-pane fade show active" id="rVentas" role="tabpanel">
  <div class="table-card">
    <div class="table-card-header">
      <span class="table-card-title"><i class="bi bi-receipt"></i> Ventas del período <span style="font-size:.78rem;color:var(--text-muted);">(<?=count($ventas)?> registros)</span></span>
      <div class="search-bar"><i class="bi bi-search search-icon"></i><input type="text" id="srchV" class="form-control form-control-sm" placeholder="Buscar..." style="width:180px;padding-left:2.2rem;"></div>
    </div>
    <?php if (empty($ventas)): ?><div class="empty-state"><i class="bi bi-receipt"></i><p>Sin ventas en este período</p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table" id="tablaRV">
        <thead><tr><th>#</th><th class="table-hide-mobile">Vendedor</th><th>Pago</th><th>Total</th><th>Estado</th><th class="table-hide-tablet">Fecha</th><th style="width:50px;"></th></tr></thead>
        <tbody>
        <?php foreach ($ventas as $v): ?>
          <tr class="<?=!$v['estado']?'opacity-50':''?>">
            <td style="color:var(--text-muted);font-weight:600;">#<?=(int)$v['id']?></td>
            <td class="table-hide-mobile" style="font-size:.85rem;"><?=htmlspecialchars($v['vendedor_nombre'])?></td>
            <td><span class="badge-status <?=$v['tipo_pago']==='qr'?'badge-qr':'badge-efectivo'?>"><i class="bi bi-<?=$v['tipo_pago']==='qr'?'qr-code':'cash'?>"></i> <?=ucfirst($v['tipo_pago'])?></span></td>
            <td class="text-money fw-bold" style="color:var(--<?=$v['estado']?'success':'text-muted'?>);<?=!$v['estado']?'text-decoration:line-through;':''?>">Bs.<?=number_format($v['total'],2)?></td>
            <td><span class="badge-status <?=$v['estado']?'badge-active':'badge-inactive'?>"><?=$v['estado']?'Activa':'Anulada'?></span></td>
            <td class="table-hide-tablet" style="font-size:.82rem;color:var(--text-muted);"><?=date('d/m/Y H:i',strtotime($v['fecha']))?></td>
            <td><button class="btn btn-sm btn-outline-info" onclick="verDetalleR(<?=(int)$v['id']?>)"><i class="bi bi-eye"></i></button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr style="background:var(--bg-primary);">
          <td colspan="3" class="fw-bold text-end table-hide-tablet" style="font-size:.82rem;">TOTAL:</td>
          <td colspan="4"><span class="text-money fw-bold" style="color:var(--success);font-size:1rem;">Bs.<?=number_format($totalIngresos,2)?></span></td>
        </tr></tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ===== GANANCIAS POR PRODUCTO ===== -->
<div class="tab-pane fade" id="rGanancias" role="tabpanel">
  <div class="table-card">
    <div class="table-card-header">
      <span class="table-card-title"><i class="bi bi-graph-up-arrow"></i> Ganancia por producto</span>
      <div class="search-bar"><i class="bi bi-search search-icon"></i><input type="text" id="srchGan" class="form-control form-control-sm" placeholder="Buscar..." style="width:170px;padding-left:2.2rem;"></div>
    </div>
    <?php if (empty($ganProd)): ?><div class="empty-state"><i class="bi bi-graph-up-arrow"></i><p>Sin datos en este período</p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table" id="tablaGan">
        <thead>
          <tr>
            <th>Producto</th>
            <th class="table-hide-mobile">Categoría</th>
            <th class="table-hide-tablet">Uni. vendidas</th>
            <th class="table-hide-tablet">Ingresos</th>
            <th class="table-hide-tablet">Costo</th>
            <th>Ganancia</th>
            <th class="table-hide-tablet">Margen</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $totalGanProd = array_sum(array_column($ganProd,'ganancia'));
          foreach ($ganProd as $gp):
            $margenProd = floatval($gp['ingresos'])>0 ? round(floatval($gp['ganancia'])/floatval($gp['ingresos'])*100,1) : 0;
            $ganPositiva = floatval($gp['ganancia']) >= 0;
        ?>
          <tr>
            <td><strong style="font-size:.87rem;"><?=htmlspecialchars($gp['nombre'])?></strong></td>
            <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);"><?=htmlspecialchars($gp['categoria']??'—')?></td>
            <td class="table-hide-tablet text-center"><span class="badge-status badge-active"><?=number_format($gp['unidades_vendidas'])?></span></td>
            <td class="table-hide-tablet text-money" style="font-size:.85rem;">Bs.<?=number_format($gp['ingresos'],2)?></td>
            <td class="table-hide-tablet text-money" style="font-size:.85rem;color:var(--danger);">Bs.<?=number_format($gp['costo'],2)?></td>
            <td class="text-money fw-bold" style="color:var(--<?=$ganPositiva?'success':'danger'?>);font-size:.9rem;">
              <?=$ganPositiva?'':'−'?>Bs.<?=number_format(abs($gp['ganancia']),2)?>
            </td>
            <td class="table-hide-tablet">
              <div style="display:flex;align-items:center;gap:.4rem;">
                <div style="flex:1;background:var(--bg-primary);border-radius:4px;height:6px;overflow:hidden;">
                  <div style="width:<?=min(100,$margenProd>0?$margenProd:0)?>%;background:var(--<?=$ganPositiva?'success':'danger'?>);height:100%;border-radius:4px;"></div>
                </div>
                <span style="font-size:.72rem;min-width:38px;"><?=$margenProd?>%</span>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--bg-primary);font-weight:700;">
            <td colspan="5" class="text-end" style="font-size:.82rem;">TOTAL GANANCIA:</td>
            <td class="text-money" style="color:var(--<?=$totalGanProd>=0?'success':'danger'?>);font-size:.95rem;"><?=$totalGanProd<0?'−':''?>Bs.<?=number_format(abs($totalGanProd),2)?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ===== ARQUEO ===== -->
<div class="tab-pane fade" id="rArqueo" role="tabpanel">
  <div class="row g-4">
    <div class="col-12 col-lg-7">
      <div class="table-card">
        <div class="table-card-header"><span class="table-card-title"><i class="bi bi-calculator"></i> Arqueo mensual (6 meses)</span></div>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Mes</th><th>Ingresos</th><th class="table-hide-mobile">Costos</th><th>Ganancia</th><th class="table-hide-tablet">Ventas</th></tr></thead>
            <tbody>
            <?php foreach ($arqueomeses as $am):
              $ganMes = floatval($am['ganancia']);
              $marMes = floatval($am['ingresos'])>0 ? round($ganMes/floatval($am['ingresos'])*100,1) : 0;
            ?>
              <tr>
                <td style="font-weight:600;"><?=date('M Y',strtotime($am['mes'].'-01'))?></td>
                <td class="text-money" style="color:var(--success);">Bs.<?=number_format($am['ingresos'],2)?></td>
                <td class="table-hide-mobile text-money" style="color:var(--danger);">Bs.<?=number_format($am['costos'],2)?></td>
                <td class="text-money fw-bold" style="color:var(--<?=$ganMes>=0?'success':'danger'?>);"><?=$ganMes<0?'−':''?>Bs.<?=number_format(abs($ganMes),2)?> <small style="font-weight:400;color:var(--text-muted);font-size:.72rem;">(<?=$marMes?>%)</small></td>
                <td class="table-hide-tablet text-center"><span class="badge-status badge-active"><?=(int)$am['num_ventas']?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-5">
      <div class="table-card h-100">
        <div class="table-card-header"><span class="table-card-title"><i class="bi bi-receipt-cutoff"></i> Arqueo del período</span></div>
        <div class="p-3">
          <?php
            $filas = [
              ['Ventas activas','',              $totalVentas,        'text'],
              ['Ingresos brutos','Bs.',          $totalIngresos,      'money-success'],
              ['Costo de mercancía','Bs.',       $costos,             'money-danger'],
              ['Ganancia bruta','Bs.',           $ganancia,           $ganancia>=0?'money-success':'money-danger'],
              ['Margen de ganancia','',          $margen.'%',         'text'],
              ['─────────','','','sep'],
              ['Egresos registrados','Bs.',      $totalEgresos,       'money-danger'],
              ['Balance neto','Bs.',             $balance,            $balance>=0?'money-success':'money-danger'],
            ];
          ?>
          <table class="table table-sm" style="font-size:.87rem;">
            <tbody>
            <?php foreach ($filas as $f): ?>
              <?php if ($f[3]==='sep'): ?>
                <tr><td colspan="2" style="padding:.25rem 0;color:var(--border-color);"><?=$f[0]?></td></tr>
              <?php elseif ($f[3]==='text'): ?>
                <tr><td style="color:var(--text-muted);"><?=$f[0]?></td><td class="text-end fw-bold"><?=$f[1]?><?=$f[2]?></td></tr>
              <?php else:
                $color = strpos($f[3],'success')!==false ? 'var(--success)' : 'var(--danger)';
                $val   = is_numeric($f[2]) ? (floatval($f[2])<0?'−':'').number_format(abs($f[2]),2) : $f[2];
              ?>
                <tr><td style="color:var(--text-muted);"><?=$f[0]?></td><td class="text-end fw-bold text-money" style="color:<?=$color?>;"><?=$f[1]?><?=$val?></td></tr>
              <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== TOP PRODUCTOS ===== -->
<div class="tab-pane fade" id="rProductos" role="tabpanel">
  <div class="table-card">
    <div class="table-card-header"><span class="table-card-title"><i class="bi bi-trophy"></i> Productos más vendidos</span><span style="font-size:.8rem;color:var(--text-muted);"><?=htmlspecialchars($desde)?> — <?=htmlspecialchars($hasta)?></span></div>
    <?php if (empty($topProductos)): ?><div class="empty-state"><i class="bi bi-trophy"></i><p>Sin datos en este período</p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th style="width:30px;">#</th><th>Producto</th><th class="table-hide-mobile">Cat.</th><th>Unidades</th><th>Ingresos</th><th>Ganancia</th><th class="table-hide-tablet" style="width:120px;">Participación</th></tr></thead>
        <tbody>
        <?php
          $totalIng2 = array_sum(array_column($topProductos,'total_ingresos')) ?: 1;
          foreach ($topProductos as $i => $p):
            $pct = round($p['total_ingresos']/$totalIng2*100);
            $ganP = floatval($p['ganancia_total'] ?? 0);
        ?>
          <tr>
            <td style="font-weight:700;color:var(--text-muted);"><?=$i+1?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($p['imagen_principal']): ?><img src="<?=UPLOAD_URL.htmlspecialchars(basename($p['imagen_principal']))?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px;flex-shrink:0;"><?php else: ?><div style="width:32px;height:32px;border-radius:6px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;"><i class="bi bi-image" style="color:var(--text-muted);"></i></div><?php endif; ?>
                <strong style="font-size:.87rem;"><?=htmlspecialchars($p['nombre'])?></strong>
              </div>
            </td>
            <td class="table-hide-mobile" style="font-size:.82rem;color:var(--text-muted);"><?=htmlspecialchars($p['categoria']??'—')?></td>
            <td><span class="badge-status badge-active"><?=number_format($p['total_unidades'])?></span></td>
            <td class="text-money fw-bold" style="color:var(--success);">Bs.<?=number_format($p['total_ingresos'],2)?></td>
            <td class="text-money fw-bold" style="color:var(--<?=$ganP>=0?'success':'danger'?>);font-size:.85rem;"><?=$ganP<0?'−':''?>Bs.<?=number_format(abs($ganP),2)?></td>
            <td class="table-hide-tablet">
              <div style="display:flex;align-items:center;gap:.4rem;">
                <div style="flex:1;background:var(--bg-primary);border-radius:4px;height:6px;overflow:hidden;"><div style="width:<?=$pct?>%;background:var(--accent);height:100%;border-radius:4px;"></div></div>
                <span style="font-size:.72rem;min-width:30px;"><?=$pct?>%</span>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ===== INVENTARIO ===== -->
<div class="tab-pane fade" id="rInventario" role="tabpanel">
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-boxes"></i></div><div><div class="stat-value"><?=count($inventario)?></div><div class="stat-label">Productos</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div><div><div class="stat-value"><?=count($stockCritico)?></div><div class="stat-label">Stock crítico</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon blue"><i class="bi bi-currency-dollar"></i></div><div><div class="stat-value" style="font-size:.9rem;">Bs.<?=number_format($valorCompraTotal,0)?></div><div class="stat-label">Valor compra</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-cash-coin"></i></div><div><div class="stat-value" style="font-size:.9rem;">Bs.<?=number_format($ganPotencial,0)?></div><div class="stat-label">Gan. potencial</div></div></div></div>
  </div>
  <div class="table-card">
    <div class="table-card-header">
      <span class="table-card-title"><i class="bi bi-boxes"></i> Inventario valorizado</span>
      <div class="d-flex gap-2 flex-wrap">
        <div class="search-bar"><i class="bi bi-search search-icon"></i><input type="text" id="srchInv" class="form-control form-control-sm" placeholder="Buscar..." style="width:155px;padding-left:2.2rem;"></div>
        <select id="filtroInv" class="form-select form-select-sm" style="width:auto;" onchange="filtrarInv()">
          <option value="todos">Todos</option><option value="critico">Stock crítico</option><option value="sinstock">Sin stock</option><option value="ok">Stock OK</option>
        </select>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table" id="tablaInv">
        <thead><tr><th style="width:36px;"></th><th>Producto</th><th class="table-hide-mobile">Cat.</th><th>Doc.</th><th>Uni.</th><th>Total</th><th class="table-hide-tablet">P.Compra</th><th>P.Venta</th><th class="table-hide-tablet">Val.Compra</th><th class="table-hide-tablet">Val.Venta</th><th class="table-hide-tablet">Gan.Pot.</th></tr></thead>
        <tbody>
        <?php foreach ($inventario as $p):
          $doc  = (int)($p['stock_docenas'] ?? 0);
          $uni  = (int)($p['stock_unidades'] ?? 0);
          $tot  = (int)$p['stock'];
          $sc   = $tot>10?'stock-high':($tot>3?'stock-medium':($tot>0?'stock-low':'stock-out'));
          $ganPot = floatval($p['ganancia_potencial'] ?? 0);
        ?>
          <tr data-stock="<?=$tot?>">
            <td><?php if ($p['imagen_principal']): ?><img src="<?=UPLOAD_URL.htmlspecialchars(basename($p['imagen_principal']))?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px;"><?php else: ?><div style="width:32px;height:32px;border-radius:6px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;"><i class="bi bi-image" style="color:var(--text-muted);font-size:.75rem;"></i></div><?php endif; ?></td>
            <td><strong style="font-size:.85rem;"><?=htmlspecialchars($p['nombre'])?></strong></td>
            <td class="table-hide-mobile" style="font-size:.8rem;color:var(--text-muted);"><?=htmlspecialchars($p['categoria_nombre']??'—')?></td>
            <td><span style="font-weight:700;color:var(--accent);"><?=$doc?></span><small style="color:var(--text-muted);font-size:.66rem;"> d</small></td>
            <td><span style="font-weight:700;"><?=$uni?></span><small style="color:var(--text-muted);font-size:.66rem;"> u</small></td>
            <td><span class="stock-indicator <?=$sc?>"><span class="stock-dot"></span><?=$tot?></span></td>
            <td class="table-hide-tablet text-money" style="font-size:.8rem;color:var(--text-secondary);">Bs.<?=number_format($p['precio_compra'],2)?></td>
            <td class="text-money fw-bold" style="color:var(--accent);font-size:.85rem;">Bs.<?=number_format($p['precio_venta'],2)?></td>
            <td class="table-hide-tablet text-money" style="font-size:.8rem;color:var(--text-secondary);">Bs.<?=number_format($p['valor_compra'],2)?></td>
            <td class="table-hide-tablet text-money fw-bold" style="font-size:.8rem;color:var(--success);">Bs.<?=number_format($p['valor_venta'],2)?></td>
            <td class="table-hide-tablet text-money fw-bold" style="font-size:.8rem;color:var(--<?=$ganPot>=0?'success':'danger'?>);"><?=$ganPot<0?'−':''?>Bs.<?=number_format(abs($ganPot),2)?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr style="background:var(--bg-primary);font-weight:700;">
          <td colspan="6" class="text-end" style="font-size:.8rem;">TOTALES:</td>
          <td class="table-hide-tablet text-money" style="color:var(--text-secondary);">Bs.<?=number_format($valorCompraTotal,2)?></td>
          <td></td>
          <td class="table-hide-tablet text-money" style="color:var(--text-secondary);">Bs.<?=number_format($valorCompraTotal,2)?></td>
          <td class="table-hide-tablet text-money" style="color:var(--success);">Bs.<?=number_format($valorVentaTotal,2)?></td>
          <td class="table-hide-tablet text-money" style="color:var(--success);">Bs.<?=number_format($ganPotencial,2)?></td>
        </tr></tfoot>
      </table>
    </div>
  </div>
</div>

<!-- ===== GRÁFICOS ===== -->
<div class="tab-pane fade" id="rGraficos" role="tabpanel">
  <div class="row g-4">
    <div class="col-12 col-lg-8">
      <div class="table-card"><div class="table-card-header"><span class="table-card-title"><i class="bi bi-bar-chart-line"></i> Ventas por día (período)</span></div>
        <div class="p-3" style="height:280px;"><canvas id="chartDia"></canvas></div></div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="table-card"><div class="table-card-header"><span class="table-card-title"><i class="bi bi-pie-chart"></i> Tipo de pago</span></div>
        <div class="p-3" style="height:280px;"><canvas id="chartPago"></canvas></div></div>
    </div>
    <div class="col-12">
      <div class="table-card"><div class="table-card-header"><span class="table-card-title"><i class="bi bi-graph-up"></i> Ingresos vs Ganancia mensual</span></div>
        <div class="p-3" style="height:260px;"><canvas id="chartMes"></canvas></div></div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="table-card"><div class="table-card-header"><span class="table-card-title"><i class="bi bi-trophy"></i> Top 8 productos por ganancia</span></div>
        <div class="p-3" style="height:260px;"><canvas id="chartTopGan"></canvas></div></div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="table-card"><div class="table-card-header"><span class="table-card-title"><i class="bi bi-boxes"></i> Inventario top stock</span></div>
        <div class="p-3" style="height:260px;"><canvas id="chartInv"></canvas></div></div>
    </div>
  </div>
</div>

</div><!-- /tab-content -->

<!-- Modal detalle -->
<div class="modal fade" id="modalDetalleR" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt"></i> Detalle de Venta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="detalleBodyR"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var _labDia   = <?=json_encode(array_keys($ventasPorDia))?>;
var _datDia   = <?=json_encode(array_values($ventasPorDia))?>;
var _labMes   = <?=json_encode($mesesLabels)?>;
var _datIng   = <?=json_encode($mesesIngresos)?>;
var _datGan   = <?=json_encode($mesesGanancia)?>;
var _pagEfec  = <?=(int)$efeCount?>;
var _pagQR    = <?=(int)$qrCount?>;
var _invLab   = <?=json_encode(array_map(fn($p)=>mb_substr($p['nombre'],0,14), array_slice($inventario,0,8)))?>;
var _invDat   = <?=json_encode(array_map(fn($p)=>(int)$p['stock'], array_slice($inventario,0,8)))?>;
var _topGanLab= <?=json_encode(array_map(fn($p)=>mb_substr($p['nombre'],0,14), array_slice($ganProd,0,8)))?>;
var _topGanDat= <?=json_encode(array_map(fn($p)=>round(floatval($p['ganancia']),2), array_slice($ganProd,0,8)))?>;

var accent =(getComputedStyle(document.documentElement).getPropertyValue('--accent').trim()||'#4f46e5');
var success=(getComputedStyle(document.documentElement).getPropertyValue('--success').trim()||'#10b981');
var danger =(getComputedStyle(document.documentElement).getPropertyValue('--danger').trim()||'#ef4444');
var info   =(getComputedStyle(document.documentElement).getPropertyValue('--info').trim()||'#3b82f6');
var muted  =(getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim()||'#9ca3af');
var border =(getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim()||'#e5e7eb');
Chart.defaults.color=muted; Chart.defaults.borderColor=border;

document.addEventListener('DOMContentLoaded', function() {
  initTableSearch('srchV',   'tablaRV',  [0,1,5]);
  initTableSearch('srchGan', 'tablaGan', [0,1]);
  initTableSearch('srchInv', 'tablaInv', [1,2]);

  // Ventas por día
  var c1=document.getElementById('chartDia');
  if(c1) new Chart(c1,{type:'bar',data:{labels:_labDia.length?_labDia:['Sin datos'],datasets:[{label:'Ingresos',data:_labDia.length?_datDia:[0],backgroundColor:accent+'88',borderColor:accent,borderWidth:2,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return 'Bs.'+v;}}}}}});

  // Tipo pago
  var c2=document.getElementById('chartPago');
  if(c2) new Chart(c2,{type:'doughnut',data:{labels:['Efectivo','QR'],datasets:[{data:[_pagEfec,_pagQR],backgroundColor:[success+'cc',info+'cc'],borderColor:[success,info],borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'},tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.raw+' ventas';}}}}}});

  // Ingresos vs Ganancia mensual
  var c3=document.getElementById('chartMes');
  if(c3) new Chart(c3,{type:'bar',data:{labels:_labMes,datasets:[{label:'Ingresos',data:_datIng,backgroundColor:accent+'66',borderColor:accent,borderWidth:2,borderRadius:4},{label:'Ganancia',data:_datGan,backgroundColor:success+'66',borderColor:success,borderWidth:2,borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return 'Bs.'+v;}}}}}});

  // Top ganancia por producto
  var c4=document.getElementById('chartTopGan');
  if(c4){
    var bgColors=_topGanDat.map(function(v){return v>=0?success+'88':danger+'88';});
    var brColors=_topGanDat.map(function(v){return v>=0?success:danger;});
    new Chart(c4,{type:'bar',data:{labels:_topGanLab,datasets:[{label:'Ganancia',data:_topGanDat,backgroundColor:bgColors,borderColor:brColors,borderWidth:2,borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{callback:function(v){return 'Bs.'+v;}}}}}});
  }

  // Inventario top stock
  var c5=document.getElementById('chartInv');
  if(c5) new Chart(c5,{type:'bar',data:{labels:_invLab,datasets:[{label:'Stock',data:_invDat,backgroundColor:success+'88',borderColor:success,borderWidth:2,borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}}});
});

function filtrarInv() {
  var f=document.getElementById('filtroInv').value;
  document.querySelectorAll('#tablaInv tbody tr').forEach(function(row){
    var s=parseInt(row.dataset.stock||0);
    var show=true;
    if(f==='critico')  show=s>0&&s<=5;
    if(f==='sinstock') show=s===0;
    if(f==='ok')       show=s>5;
    row.style.display=show?'':'none';
  });
}

function verDetalleR(id) {
  document.getElementById('detalleBodyR').innerHTML='<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleR')).show();
  fetch(BASE_URL+'/admin/get_venta_detalle.php?id='+id)
    .then(function(r){return r.json();})
    .then(function(data){
      if(!data.venta){document.getElementById('detalleBodyR').innerHTML='<p class="text-danger text-center py-3">Error.</p>';return;}
      var v=data.venta,anulada=parseInt(v.estado)===0;
      var filas=(data.detalle||[]).map(function(d){
        var tB=d.tipo_unidad==='docena'?'<span class="badge-status badge-admin" style="font-size:.63rem;"><i class="bi bi-collection"></i> doc</span>':'<span class="badge-status badge-vendor" style="font-size:.63rem;"><i class="bi bi-box"></i> uni</span>';
        var cB=d.color_nombre?'<span style="background:var(--bg-primary);border-radius:4px;padding:1px 5px;font-size:.68rem;color:var(--text-muted);">'+d.color_nombre+'</span>':'';
        return '<tr><td>'+d.producto_nombre+' '+tB+' '+cB+'</td><td class="text-center">'+d.cantidad+'</td><td class="text-money">Bs.'+parseFloat(d.precio).toFixed(2)+'</td><td class="text-money fw-bold">Bs.'+parseFloat(d.subtotal).toFixed(2)+'</td></tr>';
      }).join('');
      var eB=anulada?'<span class="badge-status badge-inactive">Anulada'+(v.motivo_anulacion?': '+v.motivo_anulacion:'')+'</span>':'<span class="badge-status badge-active">Activa</span>';
      var pB='<span class="badge-status '+(v.tipo_pago==='qr'?'badge-qr':'badge-efectivo')+'"><i class="bi bi-'+(v.tipo_pago==='qr'?'qr-code':'cash')+'"></i> '+(v.tipo_pago==='qr'?'QR':'Efectivo')+'</span>';
      document.getElementById('detalleBodyR').innerHTML=
        '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">'
        +'<div><div class="fw-bold fs-6">Venta #'+v.id+'</div><div style="font-size:.84rem;color:var(--text-muted);">'+v.vendedor_nombre+' · '+new Date(v.fecha.replace(' ','T')).toLocaleString('es')+'</div></div>'
        +'<div class="d-flex gap-2">'+pB+eB+'</div></div>'
        +'<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Producto</th><th class="text-center">Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>'
        +'<tbody>'+filas+'</tbody><tfoot><tr><td colspan="3" class="fw-bold text-end">Total:</td>'
        +'<td class="fw-bold text-money" style="color:var(--accent);">Bs.'+parseFloat(v.total).toFixed(2)+'</td></tr></tfoot></table></div>';
    }).catch(function(){document.getElementById('detalleBodyR').innerHTML='<p class="text-danger text-center">Error de conexión.</p>';});
}
</script>

<style>
@media print {
  .admin-topbar,.sidebar,.sidebar-overlay,.nav-tabs,form[method="GET"],.btn-outline-secondary{display:none!important;}
  .admin-main{margin-left:0!important;}
  .tab-pane{display:block!important;opacity:1!important;}
}
</style>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
