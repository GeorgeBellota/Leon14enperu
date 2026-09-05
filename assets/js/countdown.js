/* ============================================================================
   countdown.js — la cuenta atrás hasta el inicio del viaje apostólico.

   ── De dónde sale la fecha ────────────────────────────────────────────────

   Del panel: Configuración general → Fechas del viaje. El servidor la escribe
   en el atributo «data-objetivo» del bloque, y aquí se lee. Antes estaba
   escrita a mano en este archivo, con dos consecuencias: cambiarla exigía un
   despliegue, y el contador podía decir una cosa mientras la base decía otra.

   Si el atributo falta o no se entiende —base caída, alguien borra el valor—
   se cae al 11 de noviembre de 2026, que es la fecha anunciada por la Santa
   Sede. Una web sobre un viaje papal no puede quedarse con el contador en
   blanco porque falle una consulta.

   ── Por qué el desplazamiento es fijo ─────────────────────────────────────

   La fecha del panel lleva su huso escrito («…-05:00»). América/Lima no tiene
   horario de verano, así que ese desplazamiento no cambia nunca y alguien en
   Madrid ve exactamente la misma cuenta que alguien en Lima.

   ── Por qué acepta varios contadores ──────────────────────────────────────

   La portada tiene uno dentro de la cabecera y puede tener otro más abajo.
   Con querySelector sólo se animaba el primero y el segundo se quedaba con
   los guiones puestos.
   ========================================================================== */
window.L14 = window.L14 || {};

(function (L14) {
  'use strict';

  /* 11 nov 2026, 00:00 en Lima  ==  11 nov 2026, 05:00 UTC */
  var RESERVA = Date.UTC(2026, 10, 11, 5, 0, 0);

  function dosDigitos(n) { return n < 10 ? '0' + n : String(n); }

  /** La fecha declarada en el HTML, o la de reserva si no hay o no vale. */
  function objetivoDe(raiz) {
    var texto = raiz.getAttribute('data-objetivo');
    if (!texto) return RESERVA;

    var t = Date.parse(texto);
    return isNaN(t) ? RESERVA : t;
  }

  function arrancar(raiz) {
    var objetivo = objetivoDe(raiz);

    var salidas = {
      dias: raiz.querySelector('[data-unidad="dias"]'),
      horas: raiz.querySelector('[data-unidad="horas"]'),
      minutos: raiz.querySelector('[data-unidad="minutos"]'),
      segundos: raiz.querySelector('[data-unidad="segundos"]')
    };
    if (!salidas.dias) return null;

    var etiqueta = raiz.querySelector('[data-contador-lectura]');
    var ultimo = '';

    function pintar() {
      var restante = objetivo - Date.now();
      if (restante < 0) restante = 0;

      var totalSeg = Math.floor(restante / 1000);
      var dias = Math.floor(totalSeg / 86400);
      var horas = Math.floor((totalSeg % 86400) / 3600);
      var minutos = Math.floor((totalSeg % 3600) / 60);
      var segundos = totalSeg % 60;

      var firma = dias + '|' + horas + '|' + minutos + '|' + segundos;
      if (firma === ultimo) return;
      ultimo = firma;

      salidas.dias.textContent = String(dias);
      if (salidas.horas) salidas.horas.textContent = dosDigitos(horas);
      if (salidas.minutos) salidas.minutos.textContent = dosDigitos(minutos);
      if (salidas.segundos) salidas.segundos.textContent = dosDigitos(segundos);

      /* El texto para lectores de pantalla NO incluye los segundos: se
         reescribe cada segundo y un lector de pantalla lo leería sin parar. */
      if (etiqueta) {
        etiqueta.textContent = 'Faltan ' + dias + ' días, ' + horas + ' horas y ' +
          minutos + ' minutos para el inicio del viaje apostólico.';
      }
    }

    pintar();
    return setInterval(pintar, 1000);
  }

  function init() {
    var nodos = document.querySelectorAll('[data-contador]');
    for (var i = 0; i < nodos.length; i++) arrancar(nodos[i]);
  }

  L14.contador = { init: init, RESERVA: RESERVA };
})(window.L14);
