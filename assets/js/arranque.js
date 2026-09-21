/* ============================================================================
   arranque.js — orquestador de los módulos heredados.

   Sustituye al antiguo main.js. Se carga el ÚLTIMO, porque su trabajo es
   llamar al init() de los módulos que se registran en window.L14, y para
   eso tienen que haberse registrado antes.

   Lo que arranca:
     · form        el formulario de voluntariado (assets/js/form.js)
     · colecta     el widget de aportaciones
     · ctaModal    la ventana de llamada a la acción
     · anclaForm   el salto al formulario desde otras páginas

   Lo que YA NO arranca, porque lo resuelve rediseno.js con el diseño nuevo:
     nav, hero, hitos, contador y reveal.

   Y lo que se retiró del sitio: el desplazamiento suave con Lenis y las
   animaciones con GSAP/ScrollTrigger. El movimiento del rediseño lo hacen el
   CSS y un IntersectionObserver, así que esas tres librerías —unos 130 KB en
   cada página— dejaron de cargarse.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  /* ── Enlaces reservados: el clic no hace nada, pero se ven a propósito ── */
  function pendientes() {
    document.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('[data-pending="true"]') : null;
      if (!a) return;
      e.preventDefault();
    });
  }

  /* ── Bloque de texto plegable («Leer más» de la carta de la CEP) ─────── */
  function carta() {
    var boton = document.querySelector('[data-carta-mas]');
    if (!boton) return;

    var bloque = boton.closest('.carta');
    if (!bloque) return;

    boton.addEventListener('click', function () {
      var abierto = bloque.classList.toggle('esta-desplegada');
      boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      var etiqueta = boton.querySelector('span');
      if (etiqueta) etiqueta.textContent = abierto ? 'Leer menos' : 'Leer más';
    });
  }

  /* ── Diagrama de sedes: cada nodo enciende su ficha ──────────────────── */
  function sedes() {
    var diagrama = document.querySelector('[data-diagrama]');
    if (!diagrama) return;

    var nodos  = Array.prototype.slice.call(diagrama.querySelectorAll('[data-sede]'));
    var fichas = Array.prototype.slice.call(document.querySelectorAll('[data-ficha]'));
    if (!nodos.length || !fichas.length) return;

    function activar(clave) {
      nodos.forEach(function (n) {
        n.setAttribute('aria-current', n.getAttribute('data-sede') === clave ? 'true' : 'false');
      });
      fichas.forEach(function (f) { f.hidden = f.getAttribute('data-ficha') !== clave; });
    }

    nodos.forEach(function (n) {
      var clave = n.getAttribute('data-sede');
      ['mouseenter', 'focus', 'click'].forEach(function (ev) {
        n.addEventListener(ev, function () { activar(clave); });
      });
    });

    activar(nodos[0].getAttribute('data-sede'));
  }

  /* ── Fachada de vídeo antigua: el iframe sólo se carga al pulsar ──────
     rediseno.js resuelve las tarjetas nuevas con [data-video]; esto atiende
     a las páginas que todavía usan [data-fachada]. */
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

  /* ── Columnas del pie: en móvil se pliegan ───────────────────────────── */
  function pie() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-pie-grupo]'), function (grupo) {
      var boton = grupo.querySelector('.pie-columnas__titulo');
      if (!boton) return;

      boton.addEventListener('click', function () {
        var abierto = grupo.classList.toggle('esta-abierto');
        boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      });
    });
  }

  function arrancar() {
    pendientes();
    carta();
    sedes();
    fachadas();
    pie();

    if (L14.anclaForm) L14.anclaForm.init();
    if (L14.ctaModal)  L14.ctaModal.init();
    if (L14.colecta)   L14.colecta.init();
    if (L14.form)      L14.form.init();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', arrancar);
  } else {
    arrancar();
  }
})(window.L14);
