<?php
// admin/configuracion.php
$pageTitle = 'Configuración del Sitio';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireAdmin();
require_once __DIR__ . '/../models/Configuracion.php';

$cfg = new Configuracion();

/* -------- ACCIONES -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_config') {
        $campos = ['nombre_tienda','descripcion_hero','telefono','whatsapp','direccion','color_acento','footer_texto'];
        $pares  = [];
        foreach ($campos as $c) $pares[$c] = trim($_POST[$c] ?? '');
        $pares['mostrar_precio'] = isset($_POST['mostrar_precio']) ? '1' : '0';
        $cfg->setMultiple($pares);
        setFlash('success','Configuración guardada correctamente');
        redirect(BASE_URL . '/admin/configuracion.php');
    }

    if ($action === 'banner_create' || $action === 'banner_update') {
        $titulo    = trim($_POST['titulo']    ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $enlace    = trim($_POST['enlace']    ?? '');
        $orden     = intval($_POST['orden']   ?? 0);
        $activo    = intval($_POST['activo']  ?? 1);
        $bannerId  = intval($_POST['banner_id'] ?? 0);
        $imagen    = '';

        if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            $dir = __DIR__ . '/../uploads/banners/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (in_array($_FILES['imagen']['type'], $allowed) && $_FILES['imagen']['size'] <= 5*1024*1024) {
                $ext   = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $fname = 'banner_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dir.$fname)) {
                    $imagen = $fname;
                    if ($action==='banner_update' && $bannerId) {
                        $old = $cfg->getBannerById($bannerId);
                        if ($old && $old['imagen'] && $old['imagen'] !== $fname)
                            @unlink($dir.basename($old['imagen']));
                    }
                }
            }
        }

        if ($action === 'banner_create') {
            $cfg->createBanner($titulo,$subtitulo,$imagen,$enlace,$orden,$activo);
            setFlash('success','Banner creado correctamente');
        } else {
            $cfg->updateBanner($bannerId,$titulo,$subtitulo,$imagen ?: null,$enlace,$orden,$activo);
            setFlash('success','Banner actualizado correctamente');
        }
        redirect(BASE_URL . '/admin/configuracion.php?tab=banners');
    }

    if ($action === 'banner_delete') {
        $cfg->deleteBanner(intval($_POST['banner_id']));
        setFlash('success','Banner eliminado');
        redirect(BASE_URL . '/admin/configuracion.php?tab=banners');
    }

    if ($action === 'banner_toggle') {
        header('Content-Type: application/json');
        echo json_encode(['success'=>(bool)$cfg->toggleBanner(intval($_POST['banner_id']))]);
        exit;
    }
}

$config  = $cfg->getAll();
$banners = $cfg->getBanners();
$c       = array_map(fn($r) => $r['valor'], $config);
$BURL    = BASE_URL . '/uploads/banners/';
$activeTab = $_GET['tab'] ?? 'general';

include __DIR__ . '/../views/partials/header_admin.php';
?>

<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='general'?'active':'' ?>"
            data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">
      <i class="bi bi-sliders"></i> Ajustes Generales
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='banners'?'active':'' ?>"
            data-bs-toggle="tab" data-bs-target="#tabBanners" type="button">
      <i class="bi bi-images"></i> Carrusel / Banners
      <?php if (count($banners)): ?>
        <span style="background:var(--accent);color:#fff;border-radius:50px;padding:.1rem .4rem;font-size:.65rem;margin-left:.3rem;"><?= count($banners) ?></span>
      <?php endif; ?>
    </button>
  </li>
</ul>

<div class="tab-content">

<!-- ===== AJUSTES GENERALES ===== -->
<div class="tab-pane fade <?= $activeTab==='general'?'show active':'' ?>" id="tabGeneral" role="tabpanel">
  <form method="POST" class="row g-4">
    <input type="hidden" name="action" value="save_config">

    <div class="col-12 col-md-6">
      <div class="table-card p-3 h-100">
        <h6 class="fw-bold mb-3"><i class="bi bi-shop me-2" style="color:var(--accent);"></i>Identidad de la tienda</h6>
        <div class="mb-3">
          <label class="form-label">Nombre de la tienda</label>
          <input type="text" name="nombre_tienda" class="form-control" value="<?= htmlspecialchars($c['nombre_tienda']??'WT Store') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Descripción del hero (catálogo público)</label>
          <textarea name="descripcion_hero" class="form-control" rows="3"><?= htmlspecialchars($c['descripcion_hero']??'') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Texto del footer</label>
          <input type="text" name="footer_texto" class="form-control" value="<?= htmlspecialchars($c['footer_texto']??'') ?>">
        </div>
        <div>
          <label class="form-label">Color principal (acento)</label>
          <div class="d-flex align-items-center gap-2">
            <input type="color" name="color_acento" class="form-control" style="width:54px;height:38px;padding:2px;"
                   value="<?= htmlspecialchars($c['color_acento']??'#4f46e5') ?>">
            <small style="color:var(--text-muted);">Color que se aplica a botones, enlaces y elementos principales</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="table-card p-3 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-telephone me-2" style="color:var(--success);"></i>Información de contacto</h6>
        <div class="mb-3">
          <label class="form-label">Teléfono</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($c['telefono']??'') ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">WhatsApp</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
            <input type="text" name="whatsapp" class="form-control" placeholder="59170000000"
                   value="<?= htmlspecialchars($c['whatsapp']??'') ?>">
          </div>
          <small style="color:var(--text-muted);">Incluye código de país (ej: 591 para Bolivia)</small>
        </div>
        <div>
          <label class="form-label">Dirección</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($c['direccion']??'') ?>">
          </div>
        </div>
      </div>

      <div class="table-card p-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-toggle-on me-2" style="color:var(--info);"></i>Opciones del catálogo</h6>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="mostrar_precio" id="chkPrecio"
                 <?= ($c['mostrar_precio']??'0')==='1'?'checked':'' ?>>
          <label class="form-check-label" for="chkPrecio">
            Mostrar precio en el catálogo público
          </label>
        </div>
        <small style="color:var(--text-muted);font-size:.78rem;margin-top:.3rem;display:block;">
          Si está desactivado, los clientes solo verán el botón "Ver más"
        </small>
      </div>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary px-5">
        <i class="bi bi-save"></i> Guardar configuración
      </button>
    </div>
  </form>
</div>

<!-- ===== BANNERS ===== -->
<div class="tab-pane fade <?= $activeTab==='banners'?'show active':'' ?>" id="tabBanners" role="tabpanel">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <p style="color:var(--text-muted);font-size:.88rem;margin:0;">
      Los banners se muestran como carrusel en la portada del catálogo. El orden numérico determina la posición.
    </p>
    <button class="btn btn-primary btn-sm" onclick="abrirNuevoBanner()">
      <i class="bi bi-plus-lg"></i> Nuevo Banner
    </button>
  </div>

  <?php if (empty($banners)): ?>
    <div class="empty-state">
      <i class="bi bi-images"></i>
      <p>No hay banners. Crea el primero para activar el carrusel.</p>
      <button class="btn btn-primary mt-3" onclick="abrirNuevoBanner()">
        <i class="bi bi-plus-lg"></i> Crear primer banner
      </button>
    </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($banners as $b): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="table-card overflow-hidden"
           style="border:2px solid <?= $b['activo']?'var(--accent)':'var(--border-color)' ?>;">
        <?php if ($b['imagen']): ?>
          <img src="<?= $BURL.htmlspecialchars(basename($b['imagen'])) ?>"
               style="width:100%;height:160px;object-fit:cover;display:block;">
        <?php else: ?>
          <div style="width:100%;height:160px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
            <i class="bi bi-image" style="font-size:3rem;"></i>
          </div>
        <?php endif; ?>
        <div class="p-3">
          <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
            <div style="min-width:0;">
              <div class="fw-bold" style="font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($b['titulo']?:'(Sin título)') ?>
              </div>
              <?php if ($b['subtitulo']): ?>
              <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($b['subtitulo']) ?>
              </div>
              <?php endif; ?>
            </div>
            <span class="badge-status <?= $b['activo']?'badge-active':'badge-inactive' ?>" style="flex-shrink:0;">
              <?= $b['activo']?'Activo':'Inactivo' ?>
            </span>
          </div>
          <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:.75rem;">
            Orden: <?= (int)$b['orden'] ?><?= $b['enlace']?' · Enlace: ✓':'' ?>
          </div>
          <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-primary flex-fill"
                    onclick="editarBanner(<?= htmlspecialchars(json_encode($b),ENT_QUOTES) ?>)">
              <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn btn-sm btn-outline-<?= $b['activo']?'warning':'success' ?>"
                    onclick="toggleBanner(<?= (int)$b['id'] ?>,this)"
                    title="<?= $b['activo']?'Desactivar':'Activar' ?>">
              <i class="bi bi-<?= $b['activo']?'eye-slash':'eye' ?>"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger"
                    onclick="eliminarBanner(<?= (int)$b['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</div><!-- /tab-content -->

<!-- Modal Banner -->
<div class="modal fade" id="modalBanner" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bannerModalTitle"><i class="bi bi-images"></i> Nuevo Banner</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="formBanner">
        <div class="modal-body">
          <input type="hidden" name="action"    id="bannerAction"  value="banner_create">
          <input type="hidden" name="banner_id" id="bannerIdInput" value="">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Imagen del banner <small style="color:var(--text-muted);">JPG, PNG, WebP · máx 5 MB</small></label>
              <input type="file" name="imagen" id="bannerImgFile" class="form-control"
                     accept="image/*" onchange="previewBannerImg(this)">
              <div id="bannerPreviewBox" class="mt-2" style="display:none;">
                <img id="bannerPreviewImg" style="max-height:150px;border-radius:8px;border:1px solid var(--border-color);">
              </div>
              <div id="bannerImgActualBox" class="mt-2" style="display:none;">
                <small style="color:var(--text-muted);">Imagen actual:</small>
                <img id="bannerImgActual" style="max-height:70px;border-radius:6px;margin-left:.5rem;vertical-align:middle;">
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Título</label>
              <input type="text" name="titulo" id="bannerTitulo" class="form-control"
                     placeholder="Ej: Nueva Colección" maxlength="150">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Subtítulo</label>
              <input type="text" name="subtitulo" id="bannerSubtitulo" class="form-control"
                     placeholder="Ej: Descubre las últimas tendencias" maxlength="255">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Enlace al hacer clic <small style="color:var(--text-muted);">(opcional)</small></label>
              <input type="text" name="enlace" id="bannerEnlace" class="form-control"
                     placeholder="https://... o ?categoria=1" maxlength="255">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Orden</label>
              <input type="number" name="orden" id="bannerOrden" class="form-control" value="0" min="0">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Estado</label>
              <select name="activo" id="bannerActivo" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary fw-bold">
            <i class="bi bi-save"></i> Guardar Banner
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Form oculto para delete -->
<form id="fBannerDel" method="POST" style="display:none;">
  <input type="hidden" name="action"    value="banner_delete">
  <input type="hidden" name="banner_id" id="fBannerDelId">
</form>

<script>
function abrirNuevoBanner() {
  document.getElementById('bannerModalTitle').innerHTML='<i class="bi bi-images"></i> Nuevo Banner';
  document.getElementById('bannerAction').value='banner_create';
  document.getElementById('bannerIdInput').value='';
  document.getElementById('bannerTitulo').value='';
  document.getElementById('bannerSubtitulo').value='';
  document.getElementById('bannerEnlace').value='';
  document.getElementById('bannerOrden').value='0';
  document.getElementById('bannerActivo').value='1';
  document.getElementById('bannerImgFile').value='';
  document.getElementById('bannerPreviewBox').style.display='none';
  document.getElementById('bannerImgActualBox').style.display='none';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBanner')).show();
}

function editarBanner(b) {
  document.getElementById('bannerModalTitle').innerHTML='<i class="bi bi-pencil"></i> Editar Banner';
  document.getElementById('bannerAction').value='banner_update';
  document.getElementById('bannerIdInput').value=b.id;
  document.getElementById('bannerTitulo').value=b.titulo||'';
  document.getElementById('bannerSubtitulo').value=b.subtitulo||'';
  document.getElementById('bannerEnlace').value=b.enlace||'';
  document.getElementById('bannerOrden').value=b.orden||0;
  document.getElementById('bannerActivo').value=b.activo?'1':'0';
  document.getElementById('bannerImgFile').value='';
  document.getElementById('bannerPreviewBox').style.display='none';
  if (b.imagen) {
    document.getElementById('bannerImgActualBox').style.display='';
    document.getElementById('bannerImgActual').src=BASE_URL+'/uploads/banners/'+b.imagen;
  } else {
    document.getElementById('bannerImgActualBox').style.display='none';
  }
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBanner')).show();
}

function previewBannerImg(input) {
  if (input.files && input.files[0]) {
    var r=new FileReader();
    r.onload=function(e){document.getElementById('bannerPreviewImg').src=e.target.result;document.getElementById('bannerPreviewBox').style.display='';};
    r.readAsDataURL(input.files[0]);
  }
}

function eliminarBanner(id) {
  confirmar('¿Eliminar este banner? La imagen también será eliminada.','danger').then(function(ok){
    if(!ok)return;
    document.getElementById('fBannerDelId').value=id;
    document.getElementById('fBannerDel').submit();
  });
}

function toggleBanner(id,btn) {
  var fd=new FormData();
  fd.append('action','banner_toggle'); fd.append('banner_id',id);
  fetch(BASE_URL+'/admin/configuracion.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.success){showToast('Banner actualizado','success',2000);setTimeout(function(){location.reload();},800);}
      else showToast('Error al actualizar','danger');
    });
}
</script>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
