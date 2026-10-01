/* ============================================================================
   MULTIMEDIA · el filtro por fecha y la ventana de la fotografía
   ----------------------------------------------------------------------------
   Dos cosas, y las dos son MEJORAS sobre una página que ya funciona sin
   ellas:

     · Sin JavaScript se ven todas las fotografías de cada actividad. El
       filtro quita, no añade: que no funcione significa ver de más, no ver
       de menos.
     · Sin JavaScript cada fotografía sigue siendo visible en la cuadrícula.
       Lo que se pierde es verla grande, no verla.

   Se usa <dialog> de verdad y no un <div> con position:fixed. El navegador
   se encarga del foco, de Escape y de dejar el resto de la página fuera del
   alcance del lector de pantalla; replicarlo a mano son cien líneas y se
   hace mal.
   ========================================================================== */

(function () {
  'use strict';

  /* ── El filtro por fecha ─────────────────────────────────────────────── */

  document.querySelectorAll('[data-actividad]').forEach(function (actividad) {
    var botones = actividad.querySelectorAll('[data-fecha]');
    var piezas  = actividad.querySelectorAll('.mm-pieza');
    var vacio   = actividad.querySelector('.mm-sinresultados');

    if (!botones.length || !piezas.length) return;

    actividad.addEventListener('click', function (ev) {
      var boton = ev.target.closest('.mm-fecha');
      if (!boton || !actividad.contains(boton)) return;

      var fecha = boton.getAttribute('data-fecha') || '';
      var vistas = 0;

      piezas.forEach(function (pieza) {
        var suya = fecha === '' || pieza.getAttribute('data-fecha') === fecha;
        pieza.hidden = !suya;
        if (suya) vistas++;
      });

      actividad.querySelectorAll('.mm-fecha').forEach(function (b) {
        var activo = b === boton;
        b.classList.toggle('is-activa', activo);
        b.setAttribute('aria-pressed', activo ? 'true' : 'false');
      });

      /* No debería pasar —las pestañas salen de lo que hay—, pero si alguien
         despublica una fotografía mientras otro tiene la página abierta, el
         filtro puede quedarse sin nada que enseñar. */
      if (vacio) vacio.hidden = vistas > 0;
    });
  });

  /* ── La ventana de la fotografía ─────────────────────────────────────── */

  var visor = document.querySelector('[data-visor]');
  if (!visor || typeof visor.showModal !== 'function') return;

  var img   = visor.querySelector('[data-visor-img]');
  var pie   = visor.querySelector('[data-visor-pie]');
  var bajar = visor.querySelector('[data-visor-bajar]');
  var nota  = visor.querySelector('[data-visor-nota]');

  /* De dónde se abrió, para devolver el foco al cerrar: si se pierde, quien
     navega con teclado vuelve al principio de la página y tiene que recorrer
     la cuadrícula entera otra vez. */
  var origen = null;

  document.addEventListener('click', function (ev) {
    var boton = ev.target.closest('[data-ver]');
    if (!boton) return;

    origen = boton;

    img.src = boton.getAttribute('data-grande') || '';
    img.alt = boton.getAttribute('data-pie') || '';

    var texto = boton.getAttribute('data-pie') || '';
    pie.textContent = texto;
    pie.hidden = texto === '';

    bajar.href = boton.getAttribute('data-descarga') || '';
    bajar.setAttribute('download', boton.getAttribute('data-nombre') || '');

    /* Se dice cuándo NO es el original. Las fotografías subidas antes de
       octubre de 2026 no lo tienen —se borraba al generar las variantes— y un
       medio que se baje una creyendo que es la buena se lleva un disgusto en
       imprenta. */
    if (nota) {
      var hayOriginal = boton.getAttribute('data-original') === '1';
      nota.textContent = hayOriginal ? '' : 'De ésta no se conserva el original: se descarga la mayor disponible.';
      nota.hidden = hayOriginal;
    }

    visor.showModal();
  });

  visor.addEventListener('click', function (ev) {
    /* Cerrar al pulsar fuera. El <dialog> ocupa toda la pantalla y la caja va
       dentro, así que un clic en el propio <dialog> es un clic en el fondo. */
    if (ev.target === visor || ev.target.closest('[data-cerrar]')) {
      visor.close();
    }
  });

  visor.addEventListener('close', function () {
    // Se suelta la imagen: una de 1600 px en memoria no hace falta con la
    // ventana cerrada, y además evita verla un instante al abrir la siguiente.
    img.src = '';

    if (origen) {
      origen.focus();
      origen = null;
    }
  });
})();
