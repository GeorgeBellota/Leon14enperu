/* ============================================================================
   LA VENTANA DEL VÍDEO
   ----------------------------------------------------------------------------
   Compartida por el listado de noticias y por la página interna de cada una.

   Vivía dentro de noticias.js, que sólo carga en /noticias/. Cuando los vídeos
   pasaron a poder ir también dentro del cuerpo de una nota hacía falta aquí
   también, y duplicarla habría dejado dos sitios donde arreglar el mismo
   fallo.

   ── El <iframe> se crea al pulsar, no antes ───────────────────────────────

   Mientras nadie pulse, la página no ha hablado con YouTube: lo que se ve es
   una portada que vive en nuestra propia biblioteca. El <iframe> aparece con
   el clic y se destruye al cerrar —si sólo se escondiera, el vídeo seguiría
   sonando detrás de la página—.
   ========================================================================== */

(function () {
  'use strict';

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
