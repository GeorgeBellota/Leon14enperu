/* ============================================================================
   cta-modal.js — llamada a la acción en modal (SOLO PORTADA)

   Se apoya en <dialog> y showModal() en lugar de un div con overlay: el
   navegador da gratis el atrapado de foco, el fondo inerte, la pila de capas y
   el cierre con Escape. Reimplementar eso a mano es de donde salen los modales
   que se pueden tabular por detrás.

   La animación no usa @starting-style: se abre el diálogo, se deja pasar un
   fotograma y se añade la clase, que es lo que dispara la transición. Al cerrar
   se hace al revés y el close() real espera a que termine. Así funciona en
   cualquier navegador con <dialog>, sin depender de soporte reciente.

   Scripts clásicos y espacio de nombres window.L14, como el resto del sitio:
   la regla del encargo es que funcione al abrir el HTML directamente.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  /* ══════════════════════════════════════════════════════════════════════
     AJUSTES. Es lo único que hay que tocar para cambiar el comportamiento.
     ══════════════════════════════════════════════════════════════════════ */
  var CONFIG = {
    /* Cuántas veces puede aparecer el modal. La cuenta se guarda por sesión de
       pestaña, así que recargar la página consume una. Con 0 no hay límite. */
    vecesMaximas: 3,

    /* Cuánto tarda en aparecer desde que la página está lista, en ms.
       Aparecer de golpe al cargar es lo que hace que se cierre sin leer. */
    retrasoMs: 3000,

    /* Cuánto tarda en cerrarse solo, en ms. Con 0 no se cierra solo.
       El contador se cancela en cuanto el visitante toca el modal: si lo está
       leyendo, quitárselo de delante es peor que dejarlo. */
    autoCierreMs: 12000,

    /* Duración de las animaciones de entrada y salida, en ms. Tiene que
       coincidir con la del CSS (.cta-modal, propiedad transition). */
    animacionMs: 320,

    /* Clave de sessionStorage donde vive la cuenta. Si se cambia a
       localStorage, el tope pasa a ser permanente en vez de por sesión — y hay
       que actualizar la página de cookies, que declara qué se guarda. */
    clave: 'l14-cta-vistas'
  };

  var reduce = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)')
    : { matches: false };

  /* ── Cuenta de apariciones ──────────────────────────────────────────── */
  function leerVistas() {
    try { return parseInt(sessionStorage.getItem(CONFIG.clave), 10) || 0; }
    catch (e) { return 0; }          /* modo privado: se trata como 0 */
  }

  function anotarVista(n) {
    try { sessionStorage.setItem(CONFIG.clave, String(n)); } catch (e) { /* se ignora */ }
  }

  function puedeAparecer() {
    if (!CONFIG.vecesMaximas) return true;
    return leerVistas() < CONFIG.vecesMaximas;
  }

  /* ── Apertura y cierre ──────────────────────────────────────────────── */
  function init() {
    var modal = document.querySelector('[data-cta-modal]');
    if (!modal) return;

    /* Sin soporte de <dialog> no hay modal: el aviso ya está en la página como
       sección, así que no se pierde nada. */
    if (typeof modal.showModal !== 'function') { modal.remove(); return; }
    if (!puedeAparecer()) { modal.remove(); return; }

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

      anotarVista(leerVistas() + 1);

      if (CONFIG.autoCierreMs) {
        temporizadorCierre = setTimeout(cerrar, CONFIG.autoCierreMs);
      }
    }

    function cerrar() {
      cancelarAutoCierre();
      if (temporizadorSalida) return;          /* ya se está cerrando */

      modal.classList.remove('esta-abierto');

      var espera = reduce.matches ? 0 : CONFIG.animacionMs;
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

    /* Si lo está leyendo o usando, no se le quita de delante. */
    ['pointerdown', 'keydown', 'focusin'].forEach(function (evt) {
      modal.addEventListener(evt, cancelarAutoCierre);
    });

    setTimeout(abrir, CONFIG.retrasoMs);
  }

  L14.ctaModal = { init: init, CONFIG: CONFIG };
})(window.L14);
