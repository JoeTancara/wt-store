<?php // views/partials/footer_admin.php ?>
  </div><!-- /.admin-content -->
</div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
// Sidebar toggle responsivo
(function(){
  var toggleBtn = document.getElementById('sidebarToggle');
  var sidebar   = document.getElementById('mainSidebar');
  var overlay   = document.getElementById('sidebarOverlay');
  var adminMain = document.querySelector('.admin-main');

  function isMobileView() {
    return window.innerWidth <= 991;
  }

  function closeMobileSidebar() {
    if(sidebar) sidebar.classList.remove('open');
    if(overlay) overlay.classList.remove('show');
  }

  function openMobileSidebar() {
    if(sidebar) sidebar.classList.add('open');
    if(overlay) overlay.classList.add('show');
  }

  function toggleSidebar() {
    if(!sidebar) return;

    if (isMobileView()) {
      if (sidebar.classList.contains('open')) {
        closeMobileSidebar();
      } else {
        openMobileSidebar();
      }
      // en móvil no se usa collapsed
      if(adminMain) adminMain.classList.remove('collapsed');
      sidebar.classList.remove('collapsed');
    } else {
      // escritorio: colapsa/expande lateral sin overlay
      sidebar.classList.remove('open');
      if(overlay) overlay.classList.remove('show');
      sidebar.classList.toggle('collapsed');
      if(adminMain) adminMain.classList.toggle('collapsed');
    }
  }

  function handleResize() {
    if (isMobileView()) {
      // Forzar cerrado en mobile cuando se reescala desde desktop
      closeMobileSidebar();
    } else {
      // Restaurar sidebar visible en desktop
      if(sidebar) sidebar.classList.remove('open');
      if(overlay) overlay.classList.remove('show');
      if(sidebar) sidebar.classList.remove('collapsed');
      if(adminMain) adminMain.classList.remove('collapsed');
    }
  }

  if(toggleBtn){
    toggleBtn.addEventListener('click', toggleSidebar);
  }

  if(overlay){
    overlay.addEventListener('click', function(){
      closeMobileSidebar();
    });
  }

  window.addEventListener('resize', handleResize);
  document.addEventListener('DOMContentLoaded', handleResize);
})();
</script>
</body>
</html>
