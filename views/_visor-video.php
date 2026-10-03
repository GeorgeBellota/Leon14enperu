<?php
/**
 * La ventana donde se reproduce un vídeo.
 *
 * UNA por página, compartida por todos los vídeos que haya en ella: el
 * listado de noticias la usa para su rejilla, y la página interna de una nota
 * para los vídeos que lleve dentro del texto.
 *
 * Está vacía a propósito. El <iframe> lo crea visor-video.js al pulsar, y lo
 * destruye al cerrar: mientras nadie pulse, la página no ha hablado con
 * YouTube.
 */
?>
<dialog class="nt-visor" data-visor-video aria-label="Vídeo">
  <div class="nt-visor__caja">
    <button class="nt-visor__cerrar" type="button" data-cerrar aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>

    <div class="nt-visor__marco" data-visor-marco></div>

    <h3 class="nt-visor__h" data-visor-titulo></h3>
    <p class="nt-visor__desc" data-visor-desc></p>
    <p class="nt-visor__nota">Al reproducirlo se conecta con YouTube, que registrará la reproducción.</p>
  </div>
</dialog>
