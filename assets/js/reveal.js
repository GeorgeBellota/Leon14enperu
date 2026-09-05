/* ============================================================================
   reveal.js — animaciones de barrido
   Vocabulario cerrado de cuatro movimientos: wipe-up, mask-lines, line-draw y
   fade-rise. No hay más. Solo se animan transform, opacity y clip-path.

   ── REGLA DE LA CASA ──────────────────────────────────────────────────────
   Una animación de entrada NUNCA puede dejar texto invisible. Si hay dudas,
   el texto se ve.

   Aquí eso no era teoría. La versión anterior disparaba con ScrollTrigger y
   escondía los titulares tras un translateY que en tres escenarios reales no
   se deshacía nunca:
     · abrir la página en un ancla dejaba OCHO de los once titulares de la
       home invisibles para siempre, porque el navegador salta por encima del
       punto de disparo y ScrollTrigger solo reacciona al entrar, no al haber
       pasado ya de largo;
     · bajar y volver a subir los escondía otra vez (toggleActions «reverse»);
     · el último titular de la página no llegaba a dispararse.

   Por eso el disparo es ahora un IntersectionObserver, que sí avisa de lo que
   ya está en pantalla al observarlo, más un barrido que recoge lo que quedó
   por encima. Los cuatro escenarios se comprueban con imgtool/reveal-test.js.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var movil = window.matchMedia('(max-width: 767px)').matches;
  var k = movil ? 0.6 : 1;   /* en móvil, 40% menos de recorrido y duración */

  /* Deja el elemento en su estado final. Hay que limpiar los estilos en línea
     de GSAP además de poner la clase: un transform en línea gana siempre a la
     regla .is-revealed del CSS. */
  function revelar(el) {
    el.classList.add('is-revealed');
    if (!window.gsap) return;
    var lineas = el.querySelectorAll('.linea > span');
    if (lineas.length) window.gsap.set(lineas, { clearProps: 'transform' });
    window.gsap.set(el, { clearProps: 'clipPath,opacity,transform' });
  }

  function todoVisible() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-reveal]'), revelar);
  }

  function animar(el) {
    var tipo = el.getAttribute('data-reveal');
    var delay = parseFloat(el.getAttribute('data-reveal-delay') || 0);
    var base = { delay: delay, ease: 'power3.out', onStart: function () { el.classList.add('is-revealed'); } };

    if (tipo === 'wipe-up') {
      gsap.fromTo(el, { clipPath: 'inset(100% 0 0 0)' },
        Object.assign({ clipPath: 'inset(0% 0 0 0)', duration: 0.9 * k }, base));
    } else if (tipo === 'fade-rise') {
      gsap.fromTo(el, { opacity: 0, y: 24 * k },
        Object.assign({ opacity: 1, y: 0, duration: 0.6 * k }, base));
    } else if (tipo === 'line-draw') {
      gsap.fromTo(el, { scaleX: 0, transformOrigin: 'left center' },
        Object.assign({ scaleX: 1, duration: 0.7 * k }, base));
    } else if (tipo === 'mask-lines') {
      var lineas = el.querySelectorAll('.linea > span');
      if (!lineas.length) { revelar(el); return; }
      gsap.fromTo(lineas, { yPercent: 105 },
        Object.assign({ yPercent: 0, duration: 0.9 * k, stagger: 0.07 }, base));
    } else {
      revelar(el);
    }
  }

  function init() {
    if (reduce || !window.gsap || !window.IntersectionObserver) { todoVisible(); return; }

    var pendientes = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));

    var io = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (e) {
        if (!e.isIntersecting) return;
        io.unobserve(e.target);
        animar(e.target);
      });
    }, {
      /* Equivale al antiguo «top 82%»: el elemento entra cuando su borde
         superior sube por encima del 82 % del alto de la ventana. */
      rootMargin: '0px 0px -18% 0px',
      threshold: 0
    });

    pendientes.forEach(function (el) { io.observe(el); });

    /* BARRIDO. Un IntersectionObserver no avisa de lo que quedó POR ENCIMA de
       la ventana: si el navegador salta a un ancla, todo lo anterior nunca
       intersecta y se quedaría escondido. Aquí se recoge, sin animación,
       porque animar algo que el usuario ya ha pasado no tiene sentido. */
    function barrer() {
      for (var i = pendientes.length - 1; i >= 0; i--) {
        var el = pendientes[i];
        if (el.classList.contains('is-revealed')) { pendientes.splice(i, 1); continue; }
        if (el.getBoundingClientRect().bottom <= 0) { io.unobserve(el); revelar(el); pendientes.splice(i, 1); }
      }
    }

    var pedido = false;
    function barridoSuave() {
      if (pedido) return;
      pedido = true;
      requestAnimationFrame(function () { pedido = false; barrer(); });
    }

    barrer();
    window.addEventListener('scroll', barridoSuave, { passive: true });
    window.addEventListener('resize', barridoSuave, { passive: true });
    /* Al terminar de cargar, las imágenes ya ocupan su sitio y la página ha
       cambiado de alto: hay que volver a mirar. */
    window.addEventListener('load', function () { barrer(); setTimeout(barrer, 400); });

    /* RED DE SEGURIDAD. Si algo sigue escondido con la página quieta, se
       muestra. Cubre el CDN lento, el CDN bloqueado y lo que no hayamos
       previsto: ningún texto de este sitio depende de que llegue un script. */
    setTimeout(function () {
      pendientes.slice().forEach(function (el) {
        if (el.classList.contains('is-revealed')) return;
        if (el.getBoundingClientRect().top < window.innerHeight * 1.2) revelar(el);
      });
    }, 3000);
  }

  L14.reveal = { init: init };
})(window.L14);
