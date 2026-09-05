/* ============================================================================
   ancla-form.js — panel flotante de acceso al formulario de voluntariado

   El formulario de inscripción vive al final de una página larga (seis
   servicios y tres fases antes de llegar a él). Este panel lo mantiene a un
   clic desde cualquier punto del recorrido.

   Sólo actúa por debajo de 1280px. Desde ese ancho el formulario entero
   acompaña el scroll en su propio carril (pages.css, «El formulario fijo») y
   el CSS oculta esta franja, porque llevaría a donde el lector ya está mirando.

   La regla que da forma al resto: no debe pisarse con el formulario. Cuando
   #inscripcion entra en pantalla la franja se retira, porque a partir de ahí
   duplica el mensaje. El CSS reserva su alto abajo en el <body>, así que
   tampoco tapa texto en ningún momento.

   Se usan scripts clásicos y el espacio de nombres window.L14, igual que el
   resto del sitio: la regla del encargo es que funcione al abrir el HTML
   directamente, sin servidor.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  function init() {
    var panel = document.querySelector('[data-ancla-form]');
    if (!panel) return;

    var destino = document.querySelector('#inscripcion');
    if (!destino) { panel.remove(); return; }

    /* Sin IntersectionObserver el panel se queda visible y funcionando: es un
       enlace corriente. Se pierde el ocultado automático, nada más. */
    if (!('IntersectionObserver' in window)) {
      panel.removeAttribute('hidden');
      return;
    }

    panel.removeAttribute('hidden');

    /* rootMargin negativo por abajo: el panel no se retira en cuanto asoma el
       borde superior de la sección, sino cuando ya hay formulario de verdad en
       pantalla. Sin esto parpadea al entrar y salir. */
    var observador = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (e) {
        panel.classList.toggle('esta-oculto', e.isIntersecting);
      });
    }, { rootMargin: '-25% 0px -20% 0px' });

    observador.observe(destino);

    /* El salto lo hace el navegador con el href; aquí solo se mueve el foco al
       primer campo, que es lo que un enlace ancla no hace por sí solo. */
    var enlace = panel.querySelector('a[href="#inscripcion"]');
    if (enlace) {
      enlace.addEventListener('click', function () {
        var campo = destino.querySelector('input, select, textarea');
        if (!campo) return;
        setTimeout(function () { campo.focus({ preventScroll: true }); }, 600);
      });
    }
  }

  L14.anclaForm = { init: init };
})(window.L14);
