/* ============================================================================
   Galería de Multimedia · marcar fotografías sobre la cuadrícula
   ----------------------------------------------------------------------------
   El formulario que mueve y quita las fotografías está al final de la página,
   y las fotografías están arriba, en la cuadrícula. No se pueden meter las
   casillas de la cuadrícula dentro de ese formulario: HTML no admite un
   <form> dentro de otro, y envolver la cuadrícula entera la ataría a una sola
   acción.

   Así que hay dos juegos de casillas. Las de abajo son las de verdad —van en
   el formulario, ocultas— y las de la cuadrícula son las que se pulsan. Este
   archivo las mantiene sincronizadas en los dos sentidos.

   Sin JavaScript el formulario sigue funcionando: sus casillas existen, sólo
   que no se ven. Lo que se pierde es la comodidad de marcar sobre la foto, no
   la función.

   Va en un archivo y no en la página porque el panel sirve
   «script-src 'self'»: un <script> en línea no se ejecutaría.
   ========================================================================== */

(function () {
  'use strict';

  var lote = document.getElementById('lote-mover');
  if (!lote) return;

  var marcas = document.querySelectorAll('[data-marca]');
  if (!marcas.length) return;

  function espejoDe(valor) {
    return lote.querySelector('[data-espejo="' + valor + '"]');
  }

  marcas.forEach(function (marca) {
    var espejo = espejoDe(marca.value);
    if (!espejo) return;

    // Al recargar, el navegador puede recordar lo marcado: se parte del
    // estado real del formulario, no de cero.
    marca.checked = espejo.checked;

    marca.addEventListener('change', function () {
      espejo.checked = marca.checked;
      contar();
    });
  });

  /* ── Cuántas hay marcadas ──────────────────────────────────────────────
     Los botones dicen «Mover» a secas y es fácil darles con nada
     seleccionado y no entender por qué no pasa nada. Con el número delante,
     se ve. */
  var aviso = document.querySelector('[data-marcadas]');

  function contar() {
    if (!aviso) return;

    var n = 0;
    marcas.forEach(function (m) { if (m.checked) n++; });

    aviso.textContent = n === 0
      ? 'Ninguna seleccionada'
      : (n === 1 ? '1 fotografía seleccionada' : n + ' fotografías seleccionadas');

    lote.classList.toggle('is-vacio', n === 0);
  }

  /* Marcar todas: con ochenta fotografías, hacerlo a mano no es una opción. */
  var todas = document.querySelector('[data-marcar-todas]');

  if (todas) {
    todas.addEventListener('click', function () {
      var encender = Array.prototype.some.call(marcas, function (m) { return !m.checked; });

      marcas.forEach(function (m) {
        m.checked = encender;
        var espejo = espejoDe(m.value);
        if (espejo) espejo.checked = encender;
      });

      todas.textContent = encender ? 'Desmarcar todas' : 'Marcar todas';
      contar();
    });
  }

  contar();
})();
