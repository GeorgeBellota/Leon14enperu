<?php
/**
 * La transmisión en directo.
 *
 * ── Lo importante de este archivo ──────────────────────────────────────────
 *
 * AQUÍ NO HAY NINGÚN <iframe>. Lo que se pinta es una fotografía con un botón
 * de reproducir, y hasta que alguien lo pulsa el navegador NO habla con Google
 * ni una sola vez: ni cookie, ni dirección IP, ni petición.
 *
 * El reproductor lo crea assets/js/directo.js en el momento de pulsar.
 *
 * ── Por qué así y no un iframe normal ──────────────────────────────────────
 *
 * Un reproductor de YouTube incrustado a la vieja usanza contacta con Google
 * en cuanto la página carga, para TODO el que la abra: también para quien sólo
 * venía a mirar el horario. Eso son datos de esa persona viajando a Estados
 * Unidos sin que ella haya pedido ver nada.
 *
 * Pulsar «reproducir» es el consentimiento: explícito, informado y sin lugar a
 * dudas. Nadie pulsa play sin querer ver el vídeo.
 *
 * Y no se pierde ni una visualización: quien pulsa carga el reproductor de
 * verdad y YouTube la cuenta entera. Quien no pulsa tampoco habría visto el
 * directo.
 *
 * ── youtube-nocookie ───────────────────────────────────────────────────────
 *
 * El dominio de privacidad mejorada. Cuenta la visualización igual y no pone
 * cookies de publicidad. Ojo con el nombre: no significa que no hable con
 * Google, significa que no deja cookies de seguimiento. Por eso el click-to-
 * play de arriba sigue haciendo falta.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable                $esc
 */

$emision = $sitio->directo();

/* Sin transmisión configurada este archivo no pinta ni un byte, y la página
   se queda con el texto que ya tenía. */
if ($emision['id'] === '') {
    return;
}

/* La dirección la compone el servidor a partir del identificador validado.
   `rel=0` evita que al terminar aparezcan vídeos de otros canales, que en una
   transmisión de la Iglesia no es un detalle menor. */
$fuente = $emision['tipo'] === 'canal'
    ? 'https://www.youtube-nocookie.com/embed/live_stream?channel=' . rawurlencode($emision['id']) . '&autoplay=1&rel=0'
    : 'https://www.youtube-nocookie.com/embed/' . rawurlencode($emision['id']) . '?autoplay=1&rel=0';

$titulo = $emision['titulo'] !== '' ? $emision['titulo'] : 'Transmisión en directo';

/* Se avisa a la plantilla de que aquí SÍ hay reproductor, para que pida
   directo.js. Sin esto lo pedirían también las veintitrés páginas que no lo
   tienen. */
$sitio->reproductorPintado(true);
?>
<section class="seccion seccion--directo" id="transmision" aria-labelledby="t-transmision">
  <div class="contenedor">
    <h2 class="titular--mayor" id="t-transmision"><?= $esc($titulo) ?></h2>

    <div class="directo" data-directo data-fuente="<?= $esc($fuente) ?>">
      <?php /* El botón ES el reproductor hasta que se pulsa. Es un <button> de
               verdad, no un div con un icono: se alcanza con el tabulador y un
               lector de pantalla dice qué hace. */ ?>
      <button type="button" class="directo__portada" data-directo-abrir
              aria-label="Reproducir <?= $esc($titulo) ?> · se conectará con YouTube">
        <span class="directo__play" aria-hidden="true">
          <svg viewBox="0 0 68 48" width="68" height="48">
            <path d="M66.5 7.5a8.6 8.6 0 0 0-6-6C55.2 0 34 0 34 0S12.8 0 7.5 1.4a8.6 8.6 0 0 0-6 6A90 90 0 0 0 0 24a90 90 0 0 0 1.5 16.5 8.6 8.6 0 0 0 6 6C12.8 48 34 48 34 48s21.2 0 26.5-1.4a8.6 8.6 0 0 0 6-6A90 90 0 0 0 68 24a90 90 0 0 0-1.5-16.5z" fill="currentColor"/>
            <path d="M27 34V14l18 10z" fill="#fff"/>
          </svg>
        </span>
        <span class="directo__pie">
          Pulsa para ver la transmisión.
          <span class="directo__nota">Al hacerlo se conectará con YouTube, que registrará la reproducción.</span>
        </span>
      </button>
    </div>

    <?php /* Sin JavaScript no hay reproductor, así que hay que dar una salida
             que funcione igual: el enlace a YouTube. Que nadie se quede sin
             poder seguir la visita por una preferencia del navegador. */ ?>
    <noscript>
      <p class="directo__sinjs">
        <a href="https://www.youtube.com/<?= $emision['tipo'] === 'canal'
              ? 'channel/' . $esc($emision['id']) . '/live'
              : 'watch?v=' . $esc($emision['id']) ?>"
           target="_blank" rel="noopener">Ver la transmisión en YouTube ↗</a>
      </p>
    </noscript>
  </div>
</section>
