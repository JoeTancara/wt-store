// ============================================================
// TEMA
// ============================================================
var ThemeManager = {
  init: function() { this.apply(localStorage.getItem('theme') || 'dark'); },
  apply: function(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    document.querySelectorAll('.theme-toggle').forEach(function(btn) {
      btn.innerHTML = theme === 'dark'
        ? '<i class="bi bi-sun-fill"></i> Modo Claro'
        : '<i class="bi bi-moon-fill"></i> Modo Oscuro';
    });
  },
  toggle: function() { this.apply(localStorage.getItem('theme')==='dark'?'light':'dark'); }
};
ThemeManager.init();
document.addEventListener('click', function(e) {
  if (e.target.closest && e.target.closest('.theme-toggle')) ThemeManager.toggle();
});

// Bootstrap polyfill
document.addEventListener('DOMContentLoaded', function() {
  if (typeof bootstrap !== 'undefined' && bootstrap.Modal && !bootstrap.Modal.getOrCreateInstance) {
    bootstrap.Modal.getOrCreateInstance = function(el, opts) {
      return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el, opts||{});
    };
  }
});

// Flash auto-dismiss
document.addEventListener('DOMContentLoaded', function() {
  var flash = document.querySelector('.flash-message');
  if (flash) {
    setTimeout(function() {
      flash.style.transition = 'opacity 0.5s';
      flash.style.opacity = '0';
      setTimeout(function() { if (flash.parentNode) flash.remove(); }, 500);
    }, 4000);
  }
});

// ============================================================
// TOAST
// ============================================================
function showToast(message, type, duration) {
  type = type||'success'; duration = duration||4000;
  var cont = document.getElementById('toastContainer');
  if (!cont) { cont=document.createElement('div'); cont.id='toastContainer'; document.body.appendChild(cont); }
  var icons = {success:'check-circle-fill',danger:'x-circle-fill',warning:'exclamation-triangle-fill',info:'info-circle-fill'};
  var t = document.createElement('div');
  t.className = 'toast align-items-center text-bg-'+type+' border-0 show';
  t.setAttribute('role','alert');
  t.style.cssText='min-width:240px;max-width:100%;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.2);';
  t.innerHTML='<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2" style="font-size:.85rem;font-weight:600;">'
    +'<i class="bi bi-'+(icons[type]||'info-circle-fill')+'" style="flex-shrink:0;"></i><span>'+message+'</span></div>'
    +'<button type="button" class="btn-close btn-close-white me-2 m-auto" style="flex-shrink:0;" onclick="this.closest(\'.toast\').remove()"></button></div>';
  cont.appendChild(t);
  setTimeout(function(){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(function(){if(t.parentNode)t.remove();},400);},duration);
}

// ============================================================
// MODAL CONFIRMACION
// ============================================================
function confirmar(mensaje, tipo) {
  tipo = tipo||'danger';
  return new Promise(function(resolve) {
    var mid='modalConfirmApp';
    var ex=document.getElementById(mid); if(ex)ex.remove();
    var iconos={danger:'trash3',warning:'exclamation-triangle',info:'question-circle'};
    var colores={danger:'var(--danger)',warning:'var(--warning)',info:'var(--info)'};
    document.body.insertAdjacentHTML('beforeend',
      '<div class="modal fade" id="'+mid+'" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered">'
      +'<div class="modal-content"><div class="modal-body text-center p-4">'
      +'<i class="bi bi-'+(iconos[tipo]||'question-circle')+'" style="font-size:2.5rem;color:'+(colores[tipo]||colores.danger)+';display:block;margin-bottom:.75rem;"></i>'
      +'<p style="font-size:.92rem;font-weight:600;color:var(--text-primary);margin:0 0 1.25rem;">'+mensaje+'</p>'
      +'<div class="d-flex gap-2">'
      +'<button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>'
      +'<button type="button" class="btn btn-'+tipo+' flex-fill fw-bold" id="'+mid+'Confirm">Confirmar</button>'
      +'</div></div></div></div></div>'
    );
    var modalEl=document.getElementById(mid);
    var modal=new bootstrap.Modal(modalEl,{backdrop:'static'});
    var resolved=false;
    document.getElementById(mid+'Confirm').addEventListener('click',function(){resolved=true;modal.hide();});
    modalEl.addEventListener('hidden.bs.modal',function(){modalEl.remove();resolve(resolved);});
    modal.show();
  });
}

// ============================================================
// VALIDACION
// ============================================================
function validateForm(formEl) {
  if(!formEl)return false;
  formEl.querySelectorAll('.form-control,.form-select').forEach(function(el){el.classList.remove('is-invalid','is-valid');});
  var valid=true,first=null;
  formEl.querySelectorAll('[data-validate]').forEach(function(el){
    var rules=el.getAttribute('data-validate').split('|');
    var label=el.getAttribute('data-label')||el.name||'Campo';
    var value=el.value.trim(),error='';
    for(var i=0;i<rules.length;i++){
      var r=rules[i];
      if(r==='required'&&!value){error=label+' es obligatorio';break;}
      if(r==='email'&&value&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)){error='Email inválido';break;}
      if(r.indexOf('minlen:')===0&&value&&value.length<parseInt(r.split(':')[1])){error=label+' mínimo '+r.split(':')[1]+' caracteres';break;}
      if(r==='number'&&value&&isNaN(parseFloat(value))){error=label+' debe ser número';break;}
      if(r==='positive'&&value&&parseFloat(value)<=0){error=label+' debe ser mayor a 0';break;}
    }
    var fb=el.parentNode.querySelector('.invalid-feedback');
    if(!fb&&el.parentNode.classList&&el.parentNode.classList.contains('input-group'))fb=el.parentNode.parentNode.querySelector('.invalid-feedback');
    if(error){el.classList.add('is-invalid');if(fb)fb.textContent=error;if(!first)first=el;valid=false;}
    else if(value)el.classList.add('is-valid');
  });
  if(first){first.focus();first.scrollIntoView({behavior:'smooth',block:'center'});}
  return valid;
}

// ============================================================
// BUSCADOR TABLA
// ============================================================
function initTableSearch(inputId, tableId, cols) {
  var input=document.getElementById(inputId),table=document.getElementById(tableId);
  if(!input||!table)return;
  input.addEventListener('input',function(){
    var term=this.value.toLowerCase().trim();
    var rows=table.querySelectorAll('tbody tr:not(.no-results-row)'),count=0;
    rows.forEach(function(row){
      var text=cols&&cols.length?cols.map(function(c){return row.cells[c]?row.cells[c].textContent:'';}).join(' ').toLowerCase():row.textContent.toLowerCase();
      var match=!term||text.indexOf(term)!==-1;
      row.style.display=match?'':'none';
      if(match)count++;
    });
    var nr=table.querySelector('.no-results-row');
    if(!count&&term){
      if(!nr){var cols2=table.querySelector('thead tr')?table.querySelector('thead tr').children.length:5;var tr=document.createElement('tr');tr.className='no-results-row';tr.innerHTML='<td colspan="'+cols2+'" class="text-center py-3" style="color:var(--text-muted);font-size:.85rem;"><i class="bi bi-search me-1"></i>Sin resultados para "'+term+'"</td>';table.querySelector('tbody').appendChild(tr);}
    } else {if(nr)nr.remove();}
  });
}

// ============================================================
// FILTRO CATEGORIAS PUBLICO
// ============================================================
function initCategoryFilter() {
  document.querySelectorAll('.category-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      document.querySelectorAll('.category-btn').forEach(function(b){b.classList.remove('active');});
      this.classList.add('active');
      var cat=this.dataset.category;
      document.querySelectorAll('.product-card-wrap').forEach(function(card){
        card.style.display=(cat==='all'||card.dataset.category===cat)?'':'none';
      });
    });
  });
}

// ============================================================
// CART — con soporte tipo_unidad (docena / unidad)
// ============================================================
var Cart = {
  items: [],

  load: function() {
    try { this.items=JSON.parse(sessionStorage.getItem('cart')||'[]'); }
    catch(e){ this.items=[]; }
  },

  save: function() { sessionStorage.setItem('cart',JSON.stringify(this.items)); },

  // _key incluye color para separar ítems por color
  _key: function(id, tipoUnidad, colorId) {
    return id+'__'+(tipoUnidad||'unidad')+'__'+(colorId||0);
  },

  _find: function(id, tipoUnidad, colorId) {
    var k=this._key(id,tipoUnidad,colorId);
    for(var i=0;i<this.items.length;i++)
      if(this.items[i]._key===k) return this.items[i];
    return null;
  },

  add: function(product, tipoUnidad, colorId, colorNombre) {
    tipoUnidad   = tipoUnidad  || 'unidad';
    colorId      = colorId     || null;
    colorNombre  = colorNombre || '';
    var precio    = tipoUnidad==='docena' ? parseFloat(product.precio_docena||product.precio) : parseFloat(product.precio);
    var stockDisp = tipoUnidad==='docena' ? parseInt(product.stock_docenas||0) : parseInt(product.stock||0);

    if (stockDisp < 1) { showToast(tipoUnidad==='docena'?'Sin docenas disponibles':'Sin stock disponible','warning'); return; }

    var existing = this._find(product.id, tipoUnidad, colorId);
    if (existing) {
      if (existing.cantidad >= stockDisp) {
        showToast('Stock máximo alcanzado ('+stockDisp+(tipoUnidad==='docena'?' doc':' uni')+')','warning'); return;
      }
      existing.cantidad++;
    } else {
      var tipLabel = tipoUnidad==='docena' ? ' (docena)' : '';
      var colLabel = colorNombre ? ' · '+colorNombre : '';
      this.items.push({
        _key:           this._key(product.id, tipoUnidad, colorId),
        id:             product.id,
        nombre:         product.nombre,
        nombre_display: product.nombre + tipLabel + colLabel,
        precio:         precio,
        stock:          stockDisp,
        tipo_unidad:    tipoUnidad,
        color_id:       colorId,
        color_nombre:   colorNombre,
        imagen_principal: product.imagen_principal||'',
        cantidad:       1
      });
    }
    this.save(); this.render();
    var msg = '<strong>'+product.nombre+'</strong>';
    if(colorNombre) msg += ' · '+colorNombre;
    msg += ' ('+tipoUnidad+')';
    showToast(msg+' agregado','success',2000);
  },

  remove: function(key) {
    this.items=this.items.filter(function(i){return i._key!==key;});
    this.save(); this.render();
  },

  updateQty: function(key, qty) {
    var item=null;
    for(var i=0;i<this.items.length;i++){if(this.items[i]._key===key){item=this.items[i];break;}}
    if(!item)return;
    qty=parseInt(qty);
    if(qty<=0){this.remove(key);return;}
    if(qty>item.stock){showToast('Solo hay '+item.stock+(item.tipo_unidad==='docena'?' docenas':' unidades'),'warning');return;}
    item.cantidad=qty; this.save(); this.render();
  },

  clear: function() {
    if(this.items.length===0)return;
    confirmar('¿Limpiar todo el carrito?','warning').then(function(ok){
      if(!ok)return;
      Cart.items=[]; Cart.save(); Cart.render();
      showToast('Carrito limpiado','info',2000);
    });
  },

  getTotal: function() {
    return this.items.reduce(function(s,i){return s+parseFloat(i.precio)*parseInt(i.cantidad);},0);
  },

  getCount: function() {
    return this.items.reduce(function(s,i){return s+parseInt(i.cantidad);},0);
  },

  render: function() {
    var cont=document.getElementById('cartItems');
    var emptyEl=document.getElementById('cartEmpty');
    var checkEl=document.getElementById('cartCheckout');

    document.querySelectorAll('.cart-count').forEach(function(el){
      var c=Cart.getCount(); el.textContent=c; el.style.display=c>0?'':'none';
    });

    if(!cont)return;

    if(this.items.length===0){
      cont.innerHTML='';
      if(emptyEl)emptyEl.style.display='';
      if(checkEl)checkEl.style.display='none';
    } else {
      if(emptyEl)emptyEl.style.display='none';
      if(checkEl)checkEl.style.display='';
      cont.innerHTML=this.items.map(function(item){
        var subtotal=(parseFloat(item.precio)*parseInt(item.cantidad)).toFixed(2);
        var imgHtml=item.imagen_principal
          ?'<img src="'+BASE_URL+'/uploads/productos/'+item.imagen_principal+'" style="width:38px;height:38px;object-fit:cover;border-radius:6px;flex-shrink:0;border:1px solid var(--border-color);">'
          :'<div style="width:38px;height:38px;border-radius:6px;background:var(--bg-primary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);flex-shrink:0;"><i class="bi bi-image" style="font-size:.9rem;"></i></div>';
        var tipoBadge=item.tipo_unidad==='docena'
          ?'<span style="font-size:.62rem;background:var(--info-light);color:var(--info);border-radius:4px;padding:1px 5px;"><i class="bi bi-collection"></i> doc</span>'
          :'<span style="font-size:.62rem;background:var(--bg-primary);color:var(--text-muted);border-radius:4px;padding:1px 5px;"><i class="bi bi-box"></i> uni</span>';
        return '<div class="cart-item">'
          +imgHtml
          +'<div class="cart-item-info" style="flex:1;min-width:0;">'
          +'<div class="cart-item-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.83rem;">'+(item.nombre_display||item.nombre)+' '+tipoBadge+'</div>'
          +'<div class="cart-item-price" style="font-size:.75rem;">Bs. '+parseFloat(item.precio).toFixed(2)+' c/u</div>'
          +'<div class="d-flex align-items-center gap-1 mt-1">'
          +'<button class="btn btn-sm btn-outline-secondary px-3 py-0 lh-1" style="min-width:22px;font-size:.8rem;" onclick="Cart.updateQty(\''+item._key+'\','+(item.cantidad-1)+')">-</button>'
          +'<span style="min-width:22px;text-align:center;font-weight:700;font-size:.85rem;">'+item.cantidad+'</span>'
          +'<button class="btn btn-sm btn-outline-secondary px-3 py-0 lh-1" style="min-width:22px;font-size:.8rem;" onclick="Cart.updateQty(\''+item._key+'\','+(item.cantidad+1)+')">+</button>'
          +'</div></div>'
          +'<div class="text-end" style="flex-shrink:0;">'
          +'<div class="fw-bold text-money" style="color:var(--accent);font-size:.88rem;">Bs. '+subtotal+'</div>'
          +'<button class="btn btn-link text-danger p-0 mt-1" style="font-size:.72rem;" onclick="Cart.remove(\''+item._key+'\')"><i class="bi bi-trash"></i></button>'
          +'</div></div>';
      }).join('');
    }
    if(typeof actualizarTotalConDescuento==='function') actualizarTotalConDescuento();
    else { var te=document.getElementById('cartTotal'); if(te) te.textContent='Bs. '+this.getTotal().toFixed(2); }
  },

  checkout: function(tipoPago) { this.checkoutConDescuento(tipoPago,0); },

  checkoutConDescuento: function(tipoPago, descuentoMonto) {
    if(this.items.length===0){showToast('El carrito está vacío','warning');return;}
    var totalActual=Math.max(0,this.getTotal()-(descuentoMonto||0));
    var btn=document.getElementById('btnCheckout')||document.getElementById('btnConfirmarVenta');
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Procesando...';}
    var fd=new FormData();
    fd.append('tipo_pago',tipoPago||'efectivo');
    fd.append('descuento',(descuentoMonto||0).toFixed(2));
    this.items.forEach(function(item,i){
      fd.append('items['+i+'][producto_id]',item.id);
      fd.append('items['+i+'][cantidad]',   item.cantidad);
      fd.append('items['+i+'][precio]',     item.precio);
      fd.append('items['+i+'][tipo_unidad]',  item.tipo_unidad||'unidad');
      fd.append('items['+i+'][color_id]',    item.color_id||'');
      fd.append('items['+i+'][color_nombre]',item.color_nombre||'');
    });
    fetch(BASE_URL+'/admin/ventas.php?action=create',{method:'POST',body:fd})
      .then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
      .then(function(data){
        if(data.success){
          Cart.items=[]; Cart.save(); Cart.render();
          var di=document.getElementById('descuentoInput'); if(di)di.value='0';
          showToast('Venta #'+data.venta_id+' registrada — Bs. '+totalActual.toFixed(2),'success',5000);
          setTimeout(function(){location.reload();},2000);
        } else {
          showToast(data.message||'Error al procesar la venta','danger',5000);
          if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-check-lg"></i> Confirmar Venta';}
        }
      }).catch(function(err){
        showToast('Error de conexión: '+err.message,'danger');
        if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-check-lg"></i> Confirmar Venta';}
      });
  }
};

document.addEventListener('DOMContentLoaded',function(){
  Cart.load(); Cart.render(); initCategoryFilter();
});

window.ThemeManager=ThemeManager;window.Cart=Cart;window.showToast=showToast;
window.confirmar=confirmar;window.validateForm=validateForm;window.initTableSearch=initTableSearch;
