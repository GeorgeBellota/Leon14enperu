/* ============================================================================
   colecta.js — el botón de copiar de las cuentas de la colecta nacional.

   ── Por qué existe ─────────────────────────────────────────────────────────

   Un CCI son veinte dígitos seguidos. Copiarlos a mano, mirando la pantalla y
   tecleando en la aplicación del banco, es la forma más fácil que hay de que el
   dinero de alguien acabe en otra cuenta. Un botón que copia el número entero
   quita ese riesgo de en medio.

   ── Por qué el botón lo pone el JavaScript y no el HTML ────────────────────

   Porque un botón que no puede hacer nada es peor que no tener botón. Si no hay
   JavaScript, o si el navegador no da acceso al portapapeles —pasa en contextos
   sin HTTPS y en navegadores antiguos—, el botón no llega a existir y los
   dígitos se quedan como lo que ya eran: texto normal, seleccionable y
   copiable a mano. La página no pierde nada.

   ── Lo que NO hace ─────────────────────────────────────────────────────────

   No toca los números. Los lee del atributo data-copiar, que el servidor
   escribió con el mismo valor que está a la vista, y los entrega tal cual.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  /* Cuánto se queda el botón diciendo «copiado» antes de volver a su estado. */
  var AVISO = 2200;

  function hayPortapapeles() {
    return !!(navigator.clipboard && typeof navigator.clipboard.writeText === 'function');
  }

  function montar(cifra) {
    var valor = cifra.getAttribute('data-copiar');
    if (!valor) return;

    var boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'cuenta__copiar';

    /* El nombre accesible dice QUÉ se copia, no sólo «copiar»: en una lista de
       cuatro botones iguales, un lector de pantalla leería «copiar, copiar,
       copiar, copiar» y no habría forma de saber cuál es cuál. */
    var queEs = cifra.closest('.cuenta__linea');
    var rotulo = queEs && queEs.querySelector('dt') ? queEs.querySelector('dt').textContent.trim() : 'el número';
    var cuenta = cifra.closest('.cuenta');
    var banco = cuenta && cuenta.querySelector('.cuenta__banco') ? cuenta.querySelector('.cuenta__banco').textContent.trim() : '';

    boton.setAttribute('aria-label', 'Copiar ' + rotulo + (banco !== '' ? ' de ' + banco : ''));
    boton.textContent = 'Copiar';

    /* El aviso de «copiado» se anuncia por una región viva propia del botón.
       Sin ella, quien no ve la pantalla pulsa y no se entera de que pasó algo. */
    var vivo = document.createElement('span');
    vivo.className = 'solo-lectores';
    vivo.setAttribute('role', 'status');
    vivo.setAttribute('aria-live', 'polite');

    var temporizador = null;

    boton.addEventListener('click', function () {
      navigator.clipboard.writeText(valor).then(function () {
        boton.textContent = 'Copiado';
        boton.setAttribute('data-hecho', '');
        vivo.textContent = rotulo + ' copiado al portapapeles.';

        clearTimeout(temporizador);
        temporizador = setTimeout(function () {
          boton.textContent = 'Copiar';
          boton.removeAttribute('data-hecho');
          vivo.textContent = '';
        }, AVISO);
      }).catch(function () {
        /* El navegador puede negarse —permiso denegado, pestaña sin foco—. Se
           dice, y los dígitos siguen ahí para copiarlos a mano. */
        boton.textContent = 'Selecciónalo y cópialo';
        vivo.textContent = 'No se pudo copiar. Selecciona el número y cópialo a mano.';
      });
    });

    cifra.parentNode.appendChild(boton);
    cifra.parentNode.appendChild(vivo);
  }

  function init() {
    if (!hayPortapapeles()) return;

    var cifras = document.querySelectorAll('.colecta [data-copiar]');
    for (var i = 0; i < cifras.length; i++) montar(cifras[i]);
  }

  L14.colecta = { init: init };
})(window.L14);
