/* ============================================================================
   hitos.js — carrusel de hitos a sangre

   Diapositivas anchas con la fotografía de fondo y el texto dentro. Se asoma
   la siguiente por el borde derecho para que se vea que hay más.

   Si Swiper no carga, el bloque NO se rompe: el CSS deja el carril con
   desplazamiento horizontal nativo y ajuste por diapositiva, así que sigue
   siendo un carrusel usable con el dedo y con la rueda.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  function init() {
    var raiz = document.querySelector('[data-hitos]');
    if (!raiz) return;

    var carril = raiz.querySelector('.swiper');
    if (!carril || typeof window.Swiper === 'undefined') {
      raiz.classList.add('sin-slider');
      return;
    }

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var total = raiz.querySelectorAll('.swiper-slide').length;
    var vivo = raiz.querySelector('[data-hitos-vivo]');

    var swiper = new window.Swiper(carril, {
      slidesPerView: 'auto',
      spaceBetween: 16,
      speed: reduce ? 0 : 520,
      grabCursor: true,
      keyboard: { enabled: true },
      a11y: false,              /* el marcado ya trae roles y etiquetas */
      navigation: {
        prevEl: raiz.querySelector('[data-hitos-prev]'),
        nextEl: raiz.querySelector('[data-hitos-next]')
      },
      breakpoints: {
        768:  { spaceBetween: 20 },
        1024: { spaceBetween: 24 }
      },
      on: {
        slideChange: function () {
          if (vivo) vivo.textContent = 'Hito ' + (this.activeIndex + 1) + ' de ' + total;
        }
      }
    });

    L14.hitosSwiper = swiper;
  }

  L14.hitos = { init: init };
})(window.L14);
