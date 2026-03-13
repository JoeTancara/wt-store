<?php ?>
  </div><!-- /.admin-content -->
</div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
// Sidebar toggle
(function(){
  var toggleBtn = document.getElementById('sidebarToggle');
  var sidebar   = document.getElementById('mainSidebar');
  var overlay   = document.getElementById('sidebarOverlay');
  if(toggleBtn && sidebar){
    toggleBtn.addEventListener('click', function(){
      sidebar.classList.toggle('open');
      if(overlay) overlay.classList.toggle('show');
    });
  }
  if(overlay){
    overlay.addEventListener('click', function(){
      if(sidebar) sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }
})();
</script>
</body>
</html>
