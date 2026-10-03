/* ============================================================================
   NOTICIAS · los vídeos: ver más, y la ventana de reproducción
   ----------------------------------------------------------------------------
   Dos cosas, y las dos MEJORAN una página que ya funciona sin ellas:

     · «Ver más vídeos» esconde los que pasan de seis. Sin JavaScript se ven
       TODOS, que es la respuesta correcta: el botón oculta, no trae. Por eso
       el botón nace con «hidden» y lo destapa este archivo; si no cargara,
       nadie vería un botón que no hace nada.

     · La ventana del vídeo. Sin JavaScript no hay ventana, pero el título y
       la portada siguen ahí.

   ── El <iframe> se crea al pulsar, no antes ──────────────────────────────

   Si se pintara en el HTML, abrir /noticias/ dispararía una petición a
   YouTube por cada vídeo —y una cookie— sin que nadie lo pida. Este sitio no
   habla con Google hasta que la persona decide, y por eso también la portada
   se sirve desde nuestra propia biblioteca en lugar de enlazarla a ytimg.
   ========================================================================== */

(function () {
  'use strict';

  /* ── Ver más vídeos ───────────────────────────────────────────────────── */

  var boton = document.querySelector('[data-mas-videos]');
  var extra = document.querySelectorAll('[data-extra]');

  if (boton && extra.length) {
    extra.forEach(function (li) { li.hidden = true; });
    boton.hidden = false;

    boton.addEventListener('click', function () {
      /* De seis en seis: con treinta vídeos, soltarlos todos de golpe mueve
         la página entera bajo los pies de quien estaba mirando. */
      var quedan = 0;
      var abiertos = 0;

      extra.forEach(function (li) {
        if (!li.hidden) return;

        if (abiertos < 6) { li.hidden = false; abiertos++; }
        else { quedan++; }
      });

      if (quedan === 0) {
        boton.hidden = true;
        return;
      }

      var num = boton.querySelector('.nt-vmas__num');
      if (num) num.textContent = quedan;
    });
  }
})();
