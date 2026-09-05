/* ============================================================================
   fecha.js — el calendario de los filtros del panel.

   Sustituye al <input type="date"> del navegador. No por capricho: el nativo
   se ve distinto en cada navegador y en cada sistema, escribe la fecha en el
   formato del ordenador —en el de aquí salía «mm/dd/yyyy», que en Perú se lee
   al revés y hace elegir el día equivocado— y no admite lo que de verdad se
   usa en un panel: «los últimos 7 días», «este mes».

   Este es propio y no depende de nada externo. Ni CDN, ni librería, ni
   descarga: si el panel abre, el calendario funciona.

   Cómo se usa desde el HTML:

       <input type="text" data-fecha name="desde" value="2026-08-24">

   El campo guarda SIEMPRE la fecha en formato ISO (2026-08-24), que es lo que
   el servidor entiende, y enseña dd/mm/aaaa, que es lo que se lee aquí. Los
   dos formatos no se mezclan nunca: uno es para la máquina y otro para las
   personas.

   Sin JavaScript el campo sigue siendo un campo de texto donde se puede
   escribir la fecha a mano. Se pierde el calendario, no el filtro.
   ========================================================================== */

(function () {
  'use strict';

  var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
               'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

  /* La semana empieza en lunes, como en el calendario de aquí. getDay()
     devuelve 0 para el domingo, así que hay que correrlo. */
  var DIAS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

  function dosCifras(n) { return (n < 10 ? '0' : '') + n; }

  function aIso(f) {
    return f.getFullYear() + '-' + dosCifras(f.getMonth() + 1) + '-' + dosCifras(f.getDate());
  }

  function aVisible(iso) {
    var p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
    return p ? p[3] + '/' + p[2] + '/' + p[1] : '';
  }

  /* Lee lo que haya escrito una persona: 24/8/2026, 24-08-2026, 2026-08-24.
     Devuelve el ISO o cadena vacía.

     Se comprueba recomponiendo la fecha y viendo si sale la misma: así un
     31 de febrero se rechaza en lugar de convertirse en 3 de marzo, que es
     lo que hace Date si se le deja. */
  function desdeTexto(txt) {
    txt = (txt || '').trim();
    if (!txt) return '';

    var m = /^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/.exec(txt);
    var a, mes, d;

    if (m) {
      d = +m[1]; mes = +m[2]; a = +m[3];
    } else {
      m = /^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/.exec(txt);
      if (!m) return '';
      a = +m[1]; mes = +m[2]; d = +m[3];
    }

    var f = new Date(a, mes - 1, d);
    if (f.getFullYear() !== a || f.getMonth() !== mes - 1 || f.getDate() !== d) return '';

    return aIso(f);
  }

  function calendario(campo) {
    /* El valor de verdad viaja en un campo oculto con el mismo nombre; el que
       se ve es sólo la cara legible. Así el servidor recibe siempre ISO,
       escriba lo que escriba quien filtra. */
    var oculto = document.createElement('input');
    oculto.type = 'hidden';
    oculto.name = campo.name;
    oculto.value = campo.value || '';
    campo.removeAttribute('name');
    campo.value = aVisible(oculto.value);
    campo.setAttribute('placeholder', 'dd/mm/aaaa');
    campo.setAttribute('autocomplete', 'off');
    campo.setAttribute('inputmode', 'numeric');
    campo.parentNode.insertBefore(oculto, campo.nextSibling);

    var caja = document.createElement('div');
    caja.className = 'calendario';
    caja.hidden = true;
    campo.parentNode.appendChild(caja);

    var mesVisible = null;   /* qué mes se está pintando */

    function fijar(iso) {
      oculto.value = iso || '';
      campo.value = aVisible(iso);
      /* Se avisa por si algo más escucha: el formulario, un contador. */
      campo.dispatchEvent(new CustomEvent('fecha:cambio', { bubbles: true }));
    }

    function pintar() {
      var elegido = oculto.value;
      var hoy = aIso(new Date());
      var base = mesVisible || (elegido ? new Date(elegido + 'T12:00:00') : new Date());
      mesVisible = new Date(base.getFullYear(), base.getMonth(), 1);

      var html = '<div class="calendario__barra">'
        + '<button type="button" class="calendario__paso" data-mover="-1" aria-label="Mes anterior">‹</button>'
        + '<strong>' + MESES[mesVisible.getMonth()] + ' ' + mesVisible.getFullYear() + '</strong>'
        + '<button type="button" class="calendario__paso" data-mover="1" aria-label="Mes siguiente">›</button>'
        + '</div><div class="calendario__rejilla">';

      DIAS.forEach(function (d) { html += '<span class="calendario__dia">' + d + '</span>'; });

      /* Cuántos huecos antes del día 1 para que caiga en su columna. */
      var primero = (mesVisible.getDay() + 6) % 7;
      for (var i = 0; i < primero; i++) { html += '<span></span>'; }

      var ultimo = new Date(mesVisible.getFullYear(), mesVisible.getMonth() + 1, 0).getDate();

      for (var d = 1; d <= ultimo; d++) {
        var iso = aIso(new Date(mesVisible.getFullYear(), mesVisible.getMonth(), d));
        var clases = 'calendario__numero';
        if (iso === elegido) clases += ' es-elegido';
        if (iso === hoy) clases += ' es-hoy';
        html += '<button type="button" class="' + clases + '" data-iso="' + iso + '">' + d + '</button>';
      }

      html += '</div><div class="calendario__pie">'
        + '<button type="button" class="calendario__atajo" data-iso="' + hoy + '">Hoy</button>'
        + '<button type="button" class="calendario__atajo" data-limpiar>Quitar</button>'
        + '</div>';

      caja.innerHTML = html;
    }

    function abrir() { pintar(); caja.hidden = false; }
    function cerrar() { caja.hidden = true; mesVisible = null; }

    campo.addEventListener('focus', abrir);
    campo.addEventListener('click', abrir);

    /* Al escribir a mano se acepta en cuanto la fecha es válida. Escribir es
       más rápido que buscar en un calendario cuando ya se sabe el día. */
    campo.addEventListener('input', function () {
      var iso = desdeTexto(campo.value);
      oculto.value = iso;
      if (iso) { mesVisible = new Date(iso + 'T12:00:00'); pintar(); }
    });

    campo.addEventListener('blur', function () {
      /* Se deja lo escrito sólo si es una fecha de verdad; si no, se borra.
         Un campo con «24/13» dentro y un filtro que no lo tiene en cuenta es
         peor que un campo vacío: parece que está filtrando y no lo está. */
      setTimeout(function () {
        campo.value = aVisible(oculto.value);
      }, 150);
    });

    campo.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { cerrar(); }
      if (e.key === 'Enter' && !caja.hidden) { cerrar(); }
    });

    caja.addEventListener('mousedown', function (e) {
      /* mousedown y no click: el click llega después de que el campo pierda el
         foco, y para entonces el calendario ya se cerró. */
      var b = e.target.closest('button');
      if (!b) return;
      e.preventDefault();

      if (b.hasAttribute('data-mover')) {
        mesVisible = new Date(mesVisible.getFullYear(), mesVisible.getMonth() + (+b.getAttribute('data-mover')), 1);
        pintar();
        return;
      }

      if (b.hasAttribute('data-limpiar')) { fijar(''); cerrar(); return; }
      if (b.hasAttribute('data-iso')) { fijar(b.getAttribute('data-iso')); cerrar(); }
    });

    document.addEventListener('mousedown', function (e) {
      if (!caja.hidden && !caja.contains(e.target) && e.target !== campo) cerrar();
    });

    return { fijar: fijar, valor: function () { return oculto.value; } };
  }

  /* ── Rangos de uso corriente ────────────────────────────────────────────
     «Los últimos 7 días» es la pregunta que se hace de verdad; «del 18 al 24
     de agosto» es la traducción que hay que hacer a mano cada vez, y en la que
     es fácil equivocarse de día. */
  function atajos(form, desde, hasta) {
    var zona = form.querySelector('[data-rangos]');
    if (!zona || !desde || !hasta) return;

    var hoy = new Date();

    function haceDias(n) {
      var f = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - n);
      return aIso(f);
    }

    var rangos = [
      ['Hoy',            aIso(hoy),      aIso(hoy)],
      ['Ayer',           haceDias(1),    haceDias(1)],
      ['Últimos 7 días', haceDias(6),    aIso(hoy)],
      ['Últimos 30 días', haceDias(29),  aIso(hoy)],
      ['Este mes',       aIso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), aIso(hoy)],
      ['Todo',           '',             '']
    ];

    rangos.forEach(function (r) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'chip';
      b.textContent = r[0];
      b.addEventListener('click', function () {
        desde.fijar(r[1]);
        hasta.fijar(r[2]);
        form.submit();
      });
      zona.appendChild(b);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var campos = document.querySelectorAll('input[data-fecha]');
    if (!campos.length) return;

    var hechos = {};
    Array.prototype.forEach.call(campos, function (campo) {
      hechos[campo.name] = calendario(campo);
    });

    var form = document.querySelector('.filtros');
    if (form) atajos(form, hechos.desde, hechos.hasta);
  });
})();
