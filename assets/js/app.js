
// ---- Dark Mode ----
var ThemeManager = {
  init: function() {
    var saved = localStorage.getItem('theme') || 'dark';
    this.apply(saved);
  },
  apply: function(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    document.querySelectorAll('.theme-toggle').forEach(function(btn) {
      btn.innerHTML = theme === 'dark'
        ? '<i class="bi bi-sun-fill"></i> Modo Claro'
        : '<i class="bi bi-moon-fill"></i> Modo Oscuro';
    });
  },
  toggle: function() {
    var current = localStorage.getItem('theme') || 'dark';
    this.apply(current === 'dark' ? 'light' : 'dark');
  }
};

// Apply theme on script load (before DOMContentLoaded)
ThemeManager.init();

document.addEventListener('click', function(e) {
  if (e.target.closest && e.target.closest('.theme-toggle')) {
    ThemeManager.toggle();
  }
});

// ---- Flash message auto-dismiss ----
document.addEventListener('DOMContentLoaded', function() {
  var flash = document.querySelector('.flash-message');
  if (flash) {
    setTimeout(function() {
      flash.style.transition = 'opacity 0.5s';
      flash.style.opacity = '0';
      setTimeout(function() { if(flash.parentNode) flash.parentNode.removeChild(flash); }, 500);
    }, 4000);
  }
});

// ---- Toast notifications ----
function showToast(message, type) {
  type = type || 'success';
  var container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;min-width:280px;max-width:360px;';
    document.body.appendChild(container);
  }
  var icons = {success:'check-circle-fill', danger:'x-circle-fill', warning:'exclamation-triangle-fill', info:'info-circle-fill'};
  var toast = document.createElement('div');
  toast.className = 'toast align-items-center text-bg-' + type + ' border-0 show';
  toast.setAttribute('role', 'alert');
  toast.innerHTML = '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2">'
    + '<i class="bi bi-' + (icons[type] || 'info-circle-fill') + '"></i>'
    + message
    + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest(\'.toast\').remove()"></button></div>';
  container.appendChild(toast);
  setTimeout(function() { if(toast.parentNode) toast.parentNode.removeChild(toast); }, 4000);
}

// ---- Table search (client-side) ----
function initTableSearch(inputId, tableId, cols) {
  var input = document.getElementById(inputId);
  var table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', function() {
    var term = this.value.toLowerCase().trim();
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
      var text;
      if (cols && cols.length) {
        text = cols.map(function(c) { return (row.cells[c] ? row.cells[c].textContent : ''); }).join(' ').toLowerCase();
      } else {
        text = row.textContent.toLowerCase();
      }
      row.style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
    });
  });
}

// ---- Category filter (public catalog) ----
function initCategoryFilter() {
  document.querySelectorAll('.category-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.category-btn').forEach(function(b) { b.classList.remove('active'); });
      this.classList.add('active');
      var cat = this.dataset.category;
      document.querySelectorAll('.product-card-wrap').forEach(function(card) {
        card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
      });
    });
  });
}

// ---- Cart ----
var Cart = {
  items: [],

  load: function() {
    try {
      this.items = JSON.parse(sessionStorage.getItem('cart') || '[]');
    } catch(e) {
      this.items = [];
    }
  },

  save: function() {
    sessionStorage.setItem('cart', JSON.stringify(this.items));
  },

  add: function(product) {
    var existing = null;
    for (var i = 0; i < this.items.length; i++) {
      if (this.items[i].id == product.id) { existing = this.items[i]; break; }
    }
    if (existing) {
      if (existing.cantidad < parseInt(product.stock)) {
        existing.cantidad++;
      } else {
        showToast('Stock máximo alcanzado', 'warning');
        return;
      }
    } else {
      if (parseInt(product.stock) < 1) {
        showToast('Sin stock disponible', 'warning');
        return;
      }
      this.items.push({
        id:       product.id,
        nombre:   product.nombre,
        precio:   product.precio,
        stock:    product.stock,
        cantidad: 1
      });
    }
    this.save();
    this.render();
    showToast(product.nombre + ' agregado al carrito', 'success');
  },

  remove: function(id) {
    this.items = this.items.filter(function(i) { return i.id != id; });
    this.save();
    this.render();
  },

  updateQty: function(id, qty) {
    var item = null;
    for (var i = 0; i < this.items.length; i++) {
      if (this.items[i].id == id) { item = this.items[i]; break; }
    }
    if (!item) return;
    qty = parseInt(qty);
    if (qty <= 0) { this.remove(id); return; }
    if (qty > parseInt(item.stock)) { showToast('Stock insuficiente', 'warning'); return; }
    item.cantidad = qty;
    this.save();
    this.render();
  },

  clear: function() {
    this.items = [];
    this.save();
    this.render();
  },

  getTotal: function() {
    return this.items.reduce(function(s, i) {
      return s + (parseFloat(i.precio) * parseInt(i.cantidad));
    }, 0);
  },

  getCount: function() {
    return this.items.reduce(function(s, i) { return s + parseInt(i.cantidad); }, 0);
  },

  render: function() {
    var container   = document.getElementById('cartItems');
    var totalEl     = document.getElementById('cartTotal');
    var emptyEl     = document.getElementById('cartEmpty');
    var checkoutEl  = document.getElementById('cartCheckout');
    var countEls    = document.querySelectorAll('.cart-count');

    // Update badge counts
    countEls.forEach(function(el) {
      var c = Cart.getCount();
      el.textContent = c;
      el.style.display = c > 0 ? '' : 'none';
    });

    if (!container) return;

    if (this.items.length === 0) {
      container.innerHTML = '';
      if (emptyEl)    emptyEl.style.display    = '';
      if (checkoutEl) checkoutEl.style.display = 'none';
    } else {
      if (emptyEl)    emptyEl.style.display    = 'none';
      if (checkoutEl) checkoutEl.style.display = '';

      container.innerHTML = this.items.map(function(item) {
        return '<div class="cart-item">'
          + '<div class="cart-item-info">'
          + '<div class="cart-item-name">' + item.nombre + '</div>'
          + '<div class="cart-item-price">Bs ' + parseFloat(item.precio).toFixed(2) + ' c/u</div>'
          + '<div class="d-flex align-items-center gap-1 mt-1">'
          + '<button class="btn btn-sm btn-outline-secondary px-2 py-0 lh-1" onclick="Cart.updateQty(' + item.id + ',' + (item.cantidad - 1) + ')">−</button>'
          + '<span class="px-2 fw-bold">' + item.cantidad + '</span>'
          + '<button class="btn btn-sm btn-outline-secondary px-2 py-0 lh-1" onclick="Cart.updateQty(' + item.id + ',' + (item.cantidad + 1) + ')">+</button>'
          + '</div></div>'
          + '<div class="text-end">'
          + '<div class="fw-bold text-money" style="color:var(--accent);">Bs ' + (parseFloat(item.precio) * item.cantidad).toFixed(2) + '</div>'
          + '<button class="btn btn-sm btn-link text-danger p-0 mt-1" onclick="Cart.remove(' + item.id + ')"><i class="bi bi-trash"></i></button>'
          + '</div></div>';
      }).join('');
    }

    if (totalEl) totalEl.textContent = 'Bs ' + this.getTotal().toFixed(2);
  },

  toggleSidebar: function() {
    var s = document.getElementById('cartSidebar');
    if (s) s.classList.toggle('open');
  },

  checkout: function(tipoPago) {
    if (this.items.length === 0) { showToast('Carrito vacío', 'warning'); return; }
    var btn = document.getElementById('btnCheckout');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...'; }

    var formData = new FormData();
    formData.append('tipo_pago', tipoPago || 'efectivo');
    this.items.forEach(function(item, i) {
      formData.append('items[' + i + '][producto_id]', item.id);
      formData.append('items[' + i + '][cantidad]',    item.cantidad);
      formData.append('items[' + i + '][precio]',      item.precio);
    });

    var self = this;
    fetch(BASE_URL + '/admin/ventas.php?action=create', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          self.clear();
          var s = document.getElementById('cartSidebar');
          if (s) s.classList.remove('open');
          showToast('Venta #' + data.venta_id + ' registrada correctamente', 'success');
          setTimeout(function() { window.location.reload(); }, 2000);
        } else {
          showToast(data.message || 'Error al procesar venta', 'danger');
          if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirmar Venta'; }
        }
      })
      .catch(function() {
        showToast('Error de conexión', 'danger');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirmar Venta'; }
      });
  }
};

// ---- Initialize ----
document.addEventListener('DOMContentLoaded', function() {
  Cart.load();
  Cart.render();
  initCategoryFilter();
  // Show empty cart state correctly on POS page
  var emptyEl = document.getElementById('cartEmpty');
  if (emptyEl && Cart.items.length === 0) {
    emptyEl.style.display = '';
  }
});

// Expose globals
window.ThemeManager = ThemeManager;
window.Cart = Cart;
window.showToast = showToast;
window.initTableSearch = initTableSearch;
