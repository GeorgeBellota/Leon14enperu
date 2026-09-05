/* ============================================================================
   hero.js — slider a pantalla completa (§11.2)
   Swiper por CDN. Si Swiper no carga, el hero se queda en la primera
   diapositiva: sigue siendo una portada completa y legible.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  var AUTOPLAY = 7000;
  var VELOCIDAD = 1200;

  function init() {
    var hero = document.querySelector('[data-hero]');
    if (!hero) return;

    var contenedor = hero.querySelector('.swiper');
    var puntos = Array.prototype.slice.call(hero.querySelectorAll('[data-indice]'));
    var pausa = hero.querySelector('[data-hero-pausa]');
    var vivo = hero.querySelector('[data-hero-vivo]');
    var total = hero.querySelectorAll('.swiper-slide').length;

    if (!contenedor || typeof window.Swiper === 'undefined') {
      hero.classList.add('sin-slider');
      return;
    }

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    hero.style.setProperty('--hero-autoplay', (AUTOPLAY / 1000) + 's');

    var swiper = new window.Swiper(contenedor, {
      effect: 'fade',
      fadeEffect: { crossFade: true },
      speed: VELOCIDAD,
      loop: true,
      autoplay: reduce ? false : { delay: AUTOPLAY, pauseOnMouseEnter: true, disableOnInteraction: false },
      keyboard: { enabled: true },
      a11y: false, /* el marcado ya trae los roles y etiquetas correctos */
      on: {
        slideChange: function () { pintar(this.realIndex); soloLaVisible(this); },
        init: function () { pintar(this.realIndex); soloLaVisible(this); }
      }
    });

    /* ── Sólo la diapositiva a la vista existe para el teclado ──────────

       El efecto es un fundido, no un desplazamiento: las otras dos siguen
       ocupando su sitio con opacidad cero. Sus botones seguían recibiendo el
       tabulador y los lectores de pantalla leían las tres seguidas, así que
       quien tabulaba desde la cabecera pasaba por tres enlaces invisibles
       antes de llegar al contenido. Medido: tres controles alcanzables dentro
       de diapositivas que nadie ve.

       «inert» las saca del foco y del árbol de accesibilidad de una vez.
       Donde no exista se recurre a tabindex=-1 control por control, que
       arregla el foco aunque no el anuncio; es lo que se puede hacer sin
       cargar una librería para esto. */
    function soloLaVisible(sw) {
      /* Se comprueba aquí dentro y no en una variable de arriba: Swiper llama
         a «init» mientras se construye, o sea ANTES de que se ejecute
         cualquier asignación posterior. Con «var» fuera, en esa primera
         llamada valdría undefined y la primera pasada tomaría el camino
         equivocado. */
      var hayInert = typeof HTMLElement !== 'undefined' && 'inert' in HTMLElement.prototype;
      var slides = sw.slides || [];
      for (var i = 0; i < slides.length; i++) {
        var lamina = slides[i];
        var oculta = !lamina.classList.contains('swiper-slide-active');

        if (hayInert) {
          if (oculta) { lamina.setAttribute('inert', ''); } else { lamina.removeAttribute('inert'); }
        } else {
          var focos = lamina.querySelectorAll('a[href], button, input, select, textarea, [tabindex]');
          for (var j = 0; j < focos.length; j++) {
            if (oculta) { focos[j].setAttribute('tabindex', '-1'); } else { focos[j].removeAttribute('tabindex'); }
          }
        }

        if (oculta) { lamina.setAttribute('aria-hidden', 'true'); } else { lamina.removeAttribute('aria-hidden'); }
      }
    }

    function pintar(indice) {
      puntos.forEach(function (b, i) {
        var activo = i === indice;
        b.setAttribute('aria-current', activo ? 'true' : 'false');
        var barra = b.querySelector('i');
        if (activo && barra) {          /* reinicia la barra de progreso */
          barra.style.animation = 'none';
          void barra.offsetWidth;
          barra.style.animation = '';
        }
      });
      if (vivo) vivo.textContent = 'Diapositiva ' + (indice + 1) + ' de ' + total;
    }

    puntos.forEach(function (b, i) {
      b.addEventListener('click', function () {
        if (swiper.slideToLoop) swiper.slideToLoop(i); else swiper.slideTo(i);
      });
    });

    /* Botón de pausa, visible al enfocar o al pasar el cursor */
    if (pausa) {
      if (reduce) { pausa.hidden = true; }
      pausa.addEventListener('click', function () {
        var enPausa = hero.classList.toggle('esta-en-pausa');
        if (enPausa) {
          if (swiper.autoplay) swiper.autoplay.stop();
          pausa.textContent = 'Reanudar';
          pausa.setAttribute('aria-label', 'Reanudar el paso automático de diapositivas');
        } else {
          if (swiper.autoplay) swiper.autoplay.start();
          pausa.textContent = 'Pausa';
          pausa.setAttribute('aria-label', 'Pausar el paso automático de diapositivas');
        }
      });
    }

    L14.heroSwiper = swiper;
  }

  L14.hero = { init: init };
})(window.L14);
