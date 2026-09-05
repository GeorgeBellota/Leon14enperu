/* ============================================================================
   comunicado.js — el aviso que aparece sobre la página.

   Viene de cta-modal.js, el modal escrito a mano que vivía en la portada. Dos
   diferencias:

     · La configuración ya no está aquí. Cuántas veces sale, cuánto tarda y
       cuándo se cierra solo lo decide quien publica el aviso desde el panel, y
       llega en atributos data- del propio <dialog>. Antes había que editar
       este archivo y volver a subirlo para cambiar un número.

     · Cuenta. Al abrirse avisa al servidor, y al pulsar el botón también. Sin
       eso no habría forma de saber si un comunicado sirvió para algo.

   Se apoya en <dialog> y showModal() en lugar de un div con overlay: el
   navegador da gratis el atrapado de foco, el fondo inerte, la pila de capas y
   el cierre con Escape. Reimplementar eso a mano es de donde salen los modales
   que se pueden tabular por detrás.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  /* Cuánto duran las animaciones. Es lo único que sigue aquí porque tiene que
     coincidir con la transición del CSS: partirlo en dos sitios que hay que
     mantener iguales sería peor que dejarlo. */
  var ANIMACION_MS = 320;

  var reduce = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)')
    : { matches: false };

  /* ── Cuenta de apariciones ──────────────────────────────────────────────
     Por comunicado y no global: al publicar uno nuevo, quien ya había visto el
     anterior tres veces tiene que volver a ver éste. Con una sola clave, el
     aviso nuevo nacería agotado para media audiencia. */
  function clave(id) { return 'l14-com-' + id; }

  function leerVistas(id) {
    try { return parseInt(sessionStorage.getItem(clave(id)), 10) || 0; }
    catch (e) { return 0; }          /* modo privado: se trata como 0 */
  }

  function anotarVista(id, n) {
    try { sessionStorage.setItem(clave(id), String(n)); } catch (e) { /* se ignora */ }
  }

  /* ── Avisar al servidor ─────────────────────────────────────────────────
     sendBeacon y no fetch: el navegador la entrega aunque la página se esté
     cerrando, que es justo lo que ocurre al pulsar un botón que abre otra
     pestaña o descarga un archivo. Con fetch, esas peticiones se cancelan a
     medias y el clic no llega a contarse. */
  function avisar(url, id, accion) {
    if (!url) return;

    var destino = url + '?id=' + encodeURIComponent(id) + '&a=' + accion;

    try {
      if (navigator.sendBeacon && navigator.sendBeacon(destino)) return;
    } catch (e) { /* se prueba con fetch */ }

    try {
      fetch(destino, { method: 'GET', keepalive: true, credentials: 'same-origin' });
    } catch (e) { /* si tampoco, se pierde la cuenta y nada más */ }
  }

  /* ── Apertura y cierre ──────────────────────────────────────────────── */
  function init() {
    var modal = document.querySelector('[data-comunicado]');
    if (!modal) return;

    /* Sin soporte de <dialog> no hay aviso. No se pierde nada: es un extra
       sobre la página, no parte de ella. */
    if (typeof modal.showModal !== 'function') { modal.remove(); return; }

    var id        = modal.getAttribute('data-id');
    var urlAviso  = modal.getAttribute('data-aviso');
    var veces     = parseInt(modal.getAttribute('data-veces'), 10) || 0;
    var retraso   = parseInt(modal.getAttribute('data-retraso'), 10) || 0;
    var autoCierre = parseInt(modal.getAttribute('data-autocierre'), 10) || 0;

    if (veces && leerVistas(id) >= veces) { modal.remove(); return; }

    var temporizadorCierre = null;
    var temporizadorSalida = null;
    var focoPrevio = null;

    function cancelarAutoCierre() {
      if (temporizadorCierre) { clearTimeout(temporizadorCierre); temporizadorCierre = null; }
    }

    function abrir() {
      focoPrevio = document.activeElement;
      modal.showModal();
      document.body.classList.add('sin-scroll');

      /* Un fotograma entre showModal() y la clase: sin esa espera el navegador
         pinta ya con el estado final y no hay transición que ver. */
      if (reduce.matches) {
        modal.classList.add('esta-abierto');
      } else {
        requestAnimationFrame(function () {
          requestAnimationFrame(function () { modal.classList.add('esta-abierto'); });
        });
      }

      anotarVista(id, leerVistas(id) + 1);
      avisar(urlAviso, id, 'vista');

      if (autoCierre) {
        temporizadorCierre = setTimeout(cerrar, autoCierre);
      }
    }

    function cerrar() {
      cancelarAutoCierre();
      if (temporizadorSalida) return;          /* ya se está cerrando */

      modal.classList.remove('esta-abierto');

      var espera = reduce.matches ? 0 : ANIMACION_MS;
      temporizadorSalida = setTimeout(function () {
        temporizadorSalida = null;
        modal.close();
      }, espera);
    }

    /* close() dispara este evento tanto si cerramos nosotros como si el
       navegador cierra por su cuenta: es el único sitio fiable para limpiar. */
    modal.addEventListener('close', function () {
      cancelarAutoCierre();
      document.body.classList.remove('sin-scroll');
      modal.classList.remove('esta-abierto');
      if (focoPrevio && typeof focoPrevio.focus === 'function') {
        focoPrevio.focus({ preventScroll: true });
      }
    });

    /* Escape. Se intercepta para que la salida se vea animada en vez de
       desaparecer de golpe, que es lo que hace el cierre nativo. */
    modal.addEventListener('cancel', function (e) {
      e.preventDefault();
      cerrar();
    });

    /* Clic fuera. En un <dialog> modal el fondo forma parte del propio
       elemento, así que un clic en él llega con target === modal; los clics en
       el contenido llegan con target dentro y no cierran. */
    modal.addEventListener('click', function (e) {
      if (e.target === modal) cerrar();
    });

    /* Botones de cierre del contenido. */
    Array.prototype.forEach.call(modal.querySelectorAll('[data-cta-cerrar]'), function (b) {
      b.addEventListener('click', cerrar);
    });

    /* ── El botón que se cuenta ────────────────────────────────────────────
       Sólo para los de tipo enlace. En las descargas, el conteo lo hace el
       propio servidor al servir el archivo: contarlo también aquí lo sumaría
       dos veces por cada descarga. */
    Array.prototype.forEach.call(modal.querySelectorAll('[data-comunicado-clic]'), function (a) {
      if (a.hasAttribute('download')) return;

      a.addEventListener('click', function () {
        avisar(urlAviso, id, 'clic');
      });
    });

    /* Si lo está leyendo o usando, no se le quita de delante. */
    ['pointerdown', 'keydown', 'focusin'].forEach(function (evt) {
      modal.addEventListener(evt, cancelarAutoCierre);
    });

    setTimeout(abrir, retraso);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  L14.comunicado = { init: init };
})(window.L14);
