/* ============================================================================
   main.js — orquestador
   Se carga el último. Arranca los módulos que existan en la página y resuelve
   los comportamientos pequeños que no merecen archivo propio.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  /* La carpeta de librerías, deducida de la ruta de este mismo archivo.
     document.currentScript vale durante la ejecución inicial —también en los
     scripts con defer—, así que se lee aquí arriba y se guarda.

     Se hace así y no escribiendo '/assets/vendor/' a pelo porque el sitio no
     siempre cuelga de la raíz del dominio: en local vive bajo /leon14peru/. */
  var RUTA_VENDOR = (function () {
    var yo = document.currentScript && document.currentScript.src;
    if (!yo) { return 'assets/vendor/'; }
    return yo.split('?')[0].replace(/assets\/js\/[^/]*$/, 'assets/vendor/');
  })();

  /* ── Enlaces reservados: el clic no hace nada, pero se ven a propósito ── */
  function pendientes() {
    document.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('[data-pending="true"]') : null;
      if (!a) return;
      e.preventDefault();
    });
  }

  /* ── Acordeones (preguntas frecuentes) ──────────────────────────────── */
  function acordeones() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-acordeon]'), function (raiz) {
      Array.prototype.forEach.call(raiz.querySelectorAll('.acordeon__boton'), function (boton) {
        boton.addEventListener('click', function () {
          var item = boton.closest('.acordeon__item');
          var abierto = item.classList.toggle('esta-abierto');
          boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
      });
    });
  }

  /* ── Carta de la CEP: «Leer más» ────────────────────────────────────── */
  function carta() {
    var boton = document.querySelector('[data-carta-mas]');
    if (!boton) return;
    var bloque = boton.closest('.carta');
    boton.addEventListener('click', function () {
      var abierto = bloque.classList.toggle('esta-desplegada');
      boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      boton.querySelector('span').textContent = abierto ? 'Leer menos' : 'Leer más';
    });
  }

  /* ── Diagrama de sedes: nodo → ficha lateral ────────────────────────── */
  function sedes() {
    var diagrama = document.querySelector('[data-diagrama]');
    if (!diagrama) return;
    var nodos = Array.prototype.slice.call(diagrama.querySelectorAll('[data-sede]'));
    var fichas = Array.prototype.slice.call(document.querySelectorAll('[data-ficha]'));
    if (!nodos.length || !fichas.length) return;

    function activar(clave) {
      nodos.forEach(function (n) { n.setAttribute('aria-current', n.getAttribute('data-sede') === clave ? 'true' : 'false'); });
      fichas.forEach(function (f) { f.hidden = f.getAttribute('data-ficha') !== clave; });
    }

    nodos.forEach(function (n) {
      var clave = n.getAttribute('data-sede');
      n.addEventListener('mouseenter', function () { activar(clave); });
      n.addEventListener('focus', function () { activar(clave); });
      n.addEventListener('click', function () { activar(clave); });
    });

    activar(nodos[0].getAttribute('data-sede'));
  }

  /* ── Fachada de vídeo: el iframe solo se carga al pulsar ────────────── */
  function fachadas() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-fachada]'), function (boton) {
      boton.addEventListener('click', function () {
        var src = boton.getAttribute('data-video');
        if (!src) return;
        var marco = document.createElement('iframe');
        marco.src = src;
        marco.title = boton.getAttribute('data-titulo') || 'Vídeo';
        marco.loading = 'lazy';
        marco.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture';
        marco.allowFullscreen = true;
        marco.style.cssText = 'width:100%;aspect-ratio:16/9;border:0;display:block';
        boton.replaceWith(marco);
      });
    });
  }

  /* ── Scroll suave: SOLO escritorio (§3) ─────────────────────────────
     En Android de gama media Lenis degrada el rendimiento, así que en móvil
     ni se descarga. Tampoco con prefers-reduced-motion. */
  function scrollSuave() {
    if (!window.matchMedia('(min-width: 1024px)').matches) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (location.protocol === 'file:') return;  /* evita el fallo de red al abrir el archivo suelto */

    var s = document.createElement('script');
    /* Desde el propio dominio. La política de contenido del sitio no admite
       scripts de terceros, y un CDN ajeno ejecutando código en la página del
       formulario es justo lo que se cerró en el despliegue del 03/09. */
    s.src = RUTA_VENDOR + 'lenis.min.js';
    s.onload = function () {
      if (typeof window.Lenis === 'undefined') return;
      var lenis = new window.Lenis({ duration: 1.05, smoothWheel: true });

      if (window.gsap && window.ScrollTrigger) {
        lenis.on('scroll', window.ScrollTrigger.update);
        window.gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
        window.gsap.ticker.lagSmoothing(0);
      } else {
        var bucle = function (t) { lenis.raf(t); requestAnimationFrame(bucle); };
        requestAnimationFrame(bucle);
      }
      L14.lenis = lenis;
    };
    document.head.appendChild(s);
  }

  function arrancar() {
    pendientes();
    acordeones();
    carta();
    sedes();
    fachadas();

    if (L14.nav) L14.nav.init();
    if (L14.anclaForm) L14.anclaForm.init();
    if (L14.ctaModal) L14.ctaModal.init();
    if (L14.hero) L14.hero.init();
    if (L14.hitos) L14.hitos.init();
    if (L14.contador) L14.contador.init();
    if (L14.form) L14.form.init();
    if (L14.reveal) L14.reveal.init();

    scrollSuave();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', arrancar);
  } else {
    arrancar();
  }
})(window.L14);
