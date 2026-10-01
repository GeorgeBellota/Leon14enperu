/* ============================================================================
   BIBLIOTECA DE IMÁGENES · la rejilla, la ficha y la selección
   ----------------------------------------------------------------------------
   Todo lo de aquí son MEJORAS sobre una pantalla que ya funciona sin ellas:

     · La rejilla se ve igual. Cada miniatura es un <a> de verdad que, sin
       JavaScript, lleva a su ficha en JSON. No es bonito, pero existe.
     · Las casillas de selección nacen ocultas y las destapa este archivo:
       un botón «Borrar las marcadas» que no puede marcar nada sólo confunde.
     · El campo de archivo sigue siendo un campo de archivo; lo de arrastrar
       se añade encima.

   ── Por qué la ficha va en una ventana y no en otra página ────────────────

   Con cien imágenes, abrir una ficha, volver, y aparecer otra vez al
   principio de la rejilla es insufrible. La ventana deja la rejilla donde
   estaba, con su desplazamiento intacto.
   ========================================================================== */

(function () {
  'use strict';

  var rejilla = document.querySelector('[data-biblioteca]');
  if (!rejilla) return;

  var base = (document.querySelector('[data-biblioteca]').getAttribute('data-base') || '')
           || location.pathname.replace(/\/medios.*$/, '/medios');

  /* ── Arrastrar para subir ─────────────────────────────────────────────── */

  var zona  = document.querySelector('[data-soltar]');
  var campo = document.querySelector('[data-soltar-campo]');
  var rotulo = document.querySelector('[data-soltar-texto]');

  if (zona && campo) {
    ['dragenter', 'dragover'].forEach(function (ev) {
      zona.addEventListener(ev, function (e) {
        e.preventDefault();
        zona.classList.add('is-encima');
      });
    });

    ['dragleave', 'drop'].forEach(function (ev) {
      zona.addEventListener(ev, function (e) {
        e.preventDefault();
        zona.classList.remove('is-encima');
      });
    });

    zona.addEventListener('drop', function (e) {
      if (!e.dataTransfer || !e.dataTransfer.files.length) return;

      /* Se asigna al campo de verdad: así el formulario se envía como
         siempre, con su CSRF y su validación del servidor. No hay un camino
         de subida paralelo que mantener. */
      campo.files = e.dataTransfer.files;
      campo.dispatchEvent(new Event('change', { bubbles: true }));
    });

    campo.addEventListener('change', function () {
      if (rotulo && campo.files && campo.files.length) {
        rotulo.textContent = campo.files[0].name;
      }
    });
  }

  /* ── Marcar varias ────────────────────────────────────────────────────── */

  var lote    = document.querySelector('[data-lote]');
  var marcas  = rejilla.querySelectorAll('[data-marca]');
  var cajas   = rejilla.querySelectorAll('[data-marca-caja]');

  if (lote && marcas.length) {
    lote.hidden = false;
    cajas.forEach(function (c) { c.hidden = false; });

    var aviso = lote.querySelector('[data-marcadas]');
    var todas = lote.querySelector('[data-marcar-todas]');

    function contar() {
      var n = 0;
      marcas.forEach(function (m) { if (m.checked) n++; });

      if (aviso) {
        aviso.textContent = n === 0 ? 'Ninguna seleccionada'
          : (n === 1 ? '1 seleccionada' : n + ' seleccionadas');
      }

      lote.classList.toggle('is-vacio', n === 0);
    }

    marcas.forEach(function (m) {
      m.addEventListener('change', function () {
        m.closest('[data-pieza]').classList.toggle('is-marcada', m.checked);
        contar();
      });
    });

    if (todas) {
      todas.addEventListener('click', function () {
        var encender = Array.prototype.some.call(marcas, function (m) { return !m.checked; });

        marcas.forEach(function (m) {
          m.checked = encender;
          m.closest('[data-pieza]').classList.toggle('is-marcada', encender);
        });

        todas.textContent = encender ? 'Desmarcar todas' : 'Marcar todas';
        contar();
      });
    }

    /* Las casillas van FUERA del formulario —están en la rejilla— así que al
       enviar se copian dentro. Meter la rejilla en el <form> la ataría a una
       sola acción. */
    lote.addEventListener('submit', function () {
      lote.querySelectorAll('[data-copia]').forEach(function (e) { e.remove(); });

      marcas.forEach(function (m) {
        if (!m.checked) return;

        var oculto = document.createElement('input');
        oculto.type = 'hidden';
        oculto.name = 'medios[]';
        oculto.value = m.value;
        oculto.setAttribute('data-copia', '');
        lote.appendChild(oculto);
      });
    });

    contar();
  }

  /* ── La ficha ─────────────────────────────────────────────────────────── */

  var ventana = document.querySelector('[data-ficha-ventana]');
  if (!ventana || typeof ventana.showModal !== 'function') return;

  var el = {
    img:     ventana.querySelector('[data-f-img]'),
    nombre:  ventana.querySelector('[data-f-nombre]'),
    meta:    ventana.querySelector('[data-f-meta]'),
    form:    ventana.querySelector('[data-f-form]'),
    alt:     ventana.querySelector('[data-f-alt]'),
    altSolo: ventana.querySelector('[data-f-alt-solo]'),
    dec:     ventana.querySelector('[data-f-dec]'),
    resumen: ventana.querySelector('[data-f-usos-resumen]'),
    lista:   ventana.querySelector('[data-f-usos-lista]'),
    url:     ventana.querySelector('[data-f-url]'),
    copiar:  ventana.querySelector('[data-f-copiar]'),
    copiado: ventana.querySelector('[data-f-copiado]'),
    borrar:  ventana.querySelector('[data-f-borrar]'),
    borrarB: ventana.querySelector('[data-f-borrar-btn]'),
    borrarN: ventana.querySelector('[data-f-borrar-nota]')
  };

  var origen = null;

  function peso(b) {
    if (!b) return '—';
    return b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
  }

  rejilla.addEventListener('click', function (ev) {
    var enlace = ev.target.closest('[data-ficha]');
    if (!enlace) return;

    ev.preventDefault();
    origen = enlace;

    fetch(enlace.getAttribute('href'), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (f) {
        if (f.error) { window.alert(f.error); return; }

        el.img.src = f.ruta;
        el.img.alt = f.alt || '';

        el.nombre.textContent = f.nombre;

        var trozos = [f.ancho + '×' + f.alto + ' px', peso(f.peso)];
        if (f.original) trozos.push('original ' + peso(f.peso_original));
        else trozos.push('sin original');
        if (f.autor) trozos.push('subida por ' + f.autor);
        el.meta.textContent = trozos.join(' · ');

        if (el.alt) el.alt.value = f.alt || '';
        if (el.dec) el.dec.checked = !!f.decorativa;
        if (el.altSolo) el.altSolo.textContent = f.alt || 'Sin descripción';
        if (el.form) el.form.action = base + '/' + f.id;

        /* ── Dónde se usa ─────────────────────────────────────────────
           Y si está en uso, el botón de borrar se desactiva AQUÍ, no al
           pulsarlo: enterarse después de haber decidido es tarde. */
        el.lista.replaceChildren();

        if (f.usos === 0) {
          el.resumen.textContent = 'En ninguna parte. Se puede borrar.';
        } else {
          el.resumen.textContent = f.usos === 1
            ? 'En 1 sitio:' : 'En ' + f.usos + ' sitios:';

          f.donde.forEach(function (d) {
            var li = document.createElement('li');
            li.textContent = d;
            el.lista.appendChild(li);
          });
        }

        if (el.borrar) {
          el.borrar.action = base + '/' + f.id + '/borrar';
          el.borrarB.disabled = f.usos > 0;
          el.borrarN.textContent = f.usos > 0
            ? 'No se puede: está en uso. Cámbiala primero donde aparece.'
            : '';
        }

        el.url.value = f.url;
        if (el.copiado) el.copiado.hidden = true;

        ventana.showModal();
      })
      .catch(function () { window.alert('No se pudo cargar la ficha.'); });
  });

  if (el.copiar) {
    el.copiar.addEventListener('click', function () {
      el.url.select();

      var hecho = function () { if (el.copiado) el.copiado.hidden = false; };

      if (navigator.clipboard) {
        navigator.clipboard.writeText(el.url.value).then(hecho, function () {
          // Sin permiso para el portapapeles queda seleccionada para Ctrl+C.
        });
      } else {
        try { document.execCommand('copy'); hecho(); } catch (e) { /* seleccionada */ }
      }
    });
  }

  ventana.addEventListener('click', function (ev) {
    if (ev.target === ventana || ev.target.closest('[data-cerrar]')) ventana.close();
  });

  ventana.addEventListener('close', function () {
    el.img.src = '';
    if (origen) { origen.focus(); origen = null; }
  });
})();
