/* ============================================================================
   HIMNO OFICIAL · el audio se crea al pulsar
   ----------------------------------------------------------------------------
   El enlace de la franja dorada apunta al MP3 que se subió desde el panel
   (Contenidos → Documentos) y se pegó en «Enlace» de la sección «Himno
   oficial». Este script lo convierte en un reproductor: el himno suena sin
   salir de la portada y el botón pasa a pausa.

   ── Por qué el <audio> no está en el HTML ─────────────────────────────────

   Porque un himno son varios megas y ésta es la página más visitada del
   sitio. Aunque `preload="none"` evita la descarga, el elemento ya obliga al
   navegador a resolver la fuente. Creándolo en el primer clic, quien entra y
   se va no gasta ni una petición. Es la misma regla que sigue directo.js con
   el vídeo.

   ── Por qué se parte de un enlace y no de un botón ────────────────────────

   Para que funcione sin JavaScript. Si este archivo no llega a ejecutarse
   —bloqueado, error de red, un navegador viejo—, en la página queda un <a>
   que apunta al MP3: se pulsa y el navegador lo abre y lo reproduce. Un
   <button> sin script no hace absolutamente nada.

   A partir de aquí el enlace ya no navega, así que se anuncia como botón:
   role="button" y aria-pressed, que es lo que un lector de pantalla necesita
   para decir si está sonando o no.
   ========================================================================== */

(function () {
  'use strict';

  var enlace = document.querySelector('[data-himno]');
  if (!enlace) return;

  var fuente = enlace.getAttribute('href') || '';
  if (!fuente) return;

  var tituloEl = enlace.querySelector('.hymn__title');
  var titulo = tituloEl ? tituloEl.textContent.trim() : 'el himno oficial';

  var audio = null;

  /* El estado, en un solo sitio: la clase mueve el icono y los atributos se
     lo cuentan a quien no lo ve. */
  function pintar(sonando) {
    enlace.classList.toggle('suena', sonando);
    enlace.setAttribute('aria-pressed', sonando ? 'true' : 'false');
    enlace.setAttribute('aria-label', (sonando ? 'Pausar ' : 'Escuchar ') + titulo);
  }

  enlace.setAttribute('role', 'button');
  pintar(false);

  /* Un enlace se activa con Intro; un botón, también con la barra. Como aquí
     se anuncia como botón, hay que cumplir lo que eso promete: sin esto, la
     barra haría lo de siempre en un enlace —bajar la página— y quien navega
     con teclado se quedaría sin poder pararlo. */
  enlace.addEventListener('keydown', function (evento) {
    if (evento.key === ' ' || evento.key === 'Spacebar') {
      evento.preventDefault();
      enlace.click();
    }
  });

  enlace.addEventListener('click', function (evento) {
    evento.preventDefault();

    if (audio === null) {
      audio = document.createElement('audio');
      audio.src = fuente;
      audio.preload = 'none';

      /* Estos tres mantienen el botón sincronizado pase lo que pase: también
         si el audio se para solo al acabar, o si alguien lo maneja desde los
         controles del sistema o desde otra pestaña. */
      audio.addEventListener('play', function () { pintar(true); });
      audio.addEventListener('pause', function () { pintar(false); });
      audio.addEventListener('ended', function () { pintar(false); });

      /* Si el archivo no se puede reproducir —se borró del panel, se subió
         algo que no era audio, la red falla— el enlace vuelve a ser un
         enlace. Pulsar otra vez abre el archivo y el navegador explica qué
         pasa mejor que un mensaje inventado aquí. */
      audio.addEventListener('error', function () {
        pintar(false);
        enlace.removeAttribute('data-himno');
        enlace.removeAttribute('role');
        enlace.removeAttribute('aria-pressed');
        enlace.removeAttribute('aria-label');
      });

      enlace.parentNode.insertBefore(audio, enlace.nextSibling);
    }

    if (!audio.paused) {
      audio.pause();
      return;
    }

    var intento = audio.play();

    /* play() devuelve una promesa que el navegador puede rechazar. Viniendo
       de un clic no debería, pero si lo hace y no se recoge, la consola se
       llena de errores no capturados. */
    if (intento && typeof intento.catch === 'function') {
      intento.catch(function () { pintar(false); });
    }
  });
})();
