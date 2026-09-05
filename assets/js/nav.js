/* ============================================================================
   nav.js — cabecera fija y menú móvil a pantalla completa (§11.1)
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  var UMBRAL = 80;

  /* ── Cabecera: transparente sobre el hero, sólida al bajar ───────────── */
  function cabecera() {
    var el = document.querySelector('[data-cabecera]');
    if (!el) return;

    var solida = null;
    function evaluar() {
      var siguiente = window.scrollY > UMBRAL;
      if (siguiente === solida) return;
      solida = siguiente;
      el.classList.toggle('es-solida', solida);
    }

    var pedido = false;
    window.addEventListener('scroll', function () {
      if (pedido) return;
      pedido = true;
      requestAnimationFrame(function () { evaluar(); pedido = false; });
    }, { passive: true });

    evaluar();
  }

  /* ── Menú móvil ─────────────────────────────────────────────────────── */
  function menu() {
    var panel = document.getElementById('menu-movil');
    var abrir = document.querySelector('[data-abrir-menu]');
    var cerrar = document.querySelector('[data-cerrar-menu]');
    if (!panel || !abrir) return;

    var previo = null;

    function enfocables() {
      return Array.prototype.filter.call(
        panel.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'),
        function (el) { return el.offsetParent !== null || el === document.activeElement; }
      );
    }

    function abrirMenu() {
      previo = document.activeElement;
      panel.classList.add('esta-abierto');
      panel.removeAttribute('inert');
      abrir.setAttribute('aria-expanded', 'true');
      document.body.classList.add('sin-scroll');
      escalonar();
      var lista = enfocables();
      if (lista.length) lista[0].focus();
      document.addEventListener('keydown', teclado, true);
    }

    function cerrarMenu() {
      panel.classList.remove('esta-abierto');
      panel.setAttribute('inert', '');
      abrir.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('sin-scroll');
      document.removeEventListener('keydown', teclado, true);
      if (previo && previo.focus) previo.focus();
    }

    /* Entrada escalonada de 30 ms por entrada */
    function escalonar() {
      var enlaces = panel.querySelectorAll('.menu-movil__lista a');
      Array.prototype.forEach.call(enlaces, function (a, i) {
        a.style.animationDelay = (i * 30) + 'ms';
      });
    }

    function teclado(e) {
      if (e.key === 'Escape') { e.preventDefault(); cerrarMenu(); return; }
      if (e.key !== 'Tab') return;
      var lista = enfocables();
      if (!lista.length) return;
      var primero = lista[0], ultimo = lista[lista.length - 1];
      if (e.shiftKey && document.activeElement === primero) { e.preventDefault(); ultimo.focus(); }
      else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primero.focus(); }
    }

    abrir.addEventListener('click', abrirMenu);
    if (cerrar) cerrar.addEventListener('click', cerrarMenu);

    /* Al navegar a un ancla de la propia página, el panel se retira */
    panel.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a') : null;
      if (a && !a.hasAttribute('data-pending')) cerrarMenu();
    });

    panel.setAttribute('inert', '');
  }

  /* ── Acordeones del pie, solo en móvil ──────────────────────────────── */
  function pie() {
    var grupos = document.querySelectorAll('[data-pie-grupo]');
    if (!grupos.length) return;
    var movil = window.matchMedia('(max-width: 767px)');

    Array.prototype.forEach.call(grupos, function (grupo) {
      var boton = grupo.querySelector('.pie__titulo');
      if (!boton || boton.tagName !== 'BUTTON') return;

      function sincronizar() {
        if (movil.matches) {
          boton.setAttribute('aria-expanded', grupo.classList.contains('esta-abierto') ? 'true' : 'false');
        } else {
          boton.removeAttribute('aria-expanded');
        }
      }

      boton.addEventListener('click', function () {
        if (!movil.matches) return;
        grupo.classList.toggle('esta-abierto');
        sincronizar();
      });

      if (movil.addEventListener) movil.addEventListener('change', sincronizar);
      sincronizar();
    });
  }

  function init() { cabecera(); menu(); pie(); }

  L14.nav = { init: init };
})(window.L14);
