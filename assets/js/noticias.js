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

  /* ── La ventana del vídeo ─────────────────────────────────────────────── */

  var visor = document.querySelector('[data-visor-video]');
  if (!visor || typeof visor.showModal !== 'function') return;

  var marco   = visor.querySelector('[data-visor-marco]');
  var titulo  = visor.querySelector('[data-visor-titulo]');
  var descrip = visor.querySelector('[data-visor-desc]');

  // De dónde se abrió, para devolverle el foco al cerrar.
  var origen = null;

  document.addEventListener('click', function (ev) {
    var abrir = ev.target.closest('[data-ver-video]');
    if (!abrir) return;

    var id = abrir.getAttribute('data-id');
    if (!id) return;

    origen = abrir;

    var t = abrir.getAttribute('data-titulo') || '';
    var d = abrir.getAttribute('data-desc') || '';

    titulo.textContent = t;
    titulo.hidden = t === '';
    descrip.textContent = d;
    descrip.hidden = d === '';

    /* «youtube-nocookie» y no «youtube»: es el dominio que la CSP del sitio
       admite en frame-src, y el que no deja cookies de seguimiento hasta que
       se reproduce. El identificador ya viene validado del servidor, pero se
       vuelve a comprobar aquí: lo que se mete en un src no se da por bueno
       porque venga de nuestro propio HTML. */
    if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;

    var marcoIframe = document.createElement('iframe');
    marcoIframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
    marcoIframe.title = t || 'Vídeo';
    marcoIframe.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture';
    marcoIframe.allowFullscreen = true;
    marcoIframe.referrerPolicy = 'strict-origin-when-cross-origin';

    marco.replaceChildren(marcoIframe);
    visor.showModal();
  });

  visor.addEventListener('click', function (ev) {
    if (ev.target === visor || ev.target.closest('[data-cerrar]')) visor.close();
  });

  visor.addEventListener('close', function () {
    /* Se tira el <iframe>: si sólo se escondiera, el vídeo seguiría sonando
       detrás de la página con la ventana cerrada. */
    marco.replaceChildren();

    if (origen) { origen.focus(); origen = null; }
  });
})();
