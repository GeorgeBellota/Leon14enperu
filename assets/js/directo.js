/* ============================================================================
   TRANSMISIÓN EN DIRECTO · el reproductor se crea al pulsar
   ----------------------------------------------------------------------------
   Hasta que alguien pulsa, en la página no hay ningún <iframe> y el navegador
   no ha hablado con Google ni una vez: ni cookie, ni IP, ni petición.

   Pulsar «reproducir» es el consentimiento. Es explícito y sin lugar a dudas:
   nadie pulsa play sin querer ver el vídeo. Y no se pierde ninguna
   visualización, porque a partir de ese clic el reproductor es el de verdad y
   YouTube la cuenta entera.

   La dirección la compone el servidor a partir de un identificador validado.
   Aquí sólo se comprueba que empiece por el dominio esperado: entre el panel y
   un <iframe> no puede haber un solo carácter sin revisar.
   ========================================================================== */

(function () {
  'use strict';

  var caja = document.querySelector('[data-directo]');
  if (!caja) return;

  var boton = caja.querySelector('[data-directo-abrir]');
  if (!boton) return;

  var fuente = caja.getAttribute('data-fuente') || '';

  /* Última comprobación antes de crear el marco. Si la dirección no es la que
     esperamos, no se abre nada: mejor sin directo que con un iframe que no
     sabemos a dónde apunta. */
  if (fuente.indexOf('https://www.youtube-nocookie.com/embed/') !== 0) return;

  boton.addEventListener('click', function () {
    var marco = document.createElement('iframe');

    marco.src = fuente;
    marco.title = boton.getAttribute('aria-label') || 'Transmisión en directo';
    marco.loading = 'lazy';
    marco.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen';
    marco.allowFullscreen = true;
    /* El navegador no le cuenta a YouTube de qué página exacta viene: le basta
       el dominio. */
    marco.referrerPolicy = 'strict-origin-when-cross-origin';
    marco.className = 'directo__marco';

    caja.innerHTML = '';
    caja.appendChild(marco);
    caja.classList.add('directo--abierto');

    /* El foco pasa al reproductor. Quien navega con teclado acaba de pulsar un
       botón que ha desaparecido: sin esto el foco se iría al principio de la
       página y habría que volver a bajar. */
    marco.setAttribute('tabindex', '-1');
    marco.focus({ preventScroll: true });
  });
})();
