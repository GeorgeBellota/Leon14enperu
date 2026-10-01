<?php
/**
 * ============================================================================
 *  Noticias — el listado.
 * ============================================================================
 *
 *  Responde a lo que escribió el cliente:
 *
 *    «deben mostrarse en orden cronológico descendente, desde la más
 *     reciente hasta la más antigua»
 *        Lo hace la consulta, porque la fecha ya es una DATE. Antes era texto
 *        libre y el orden lo decidían las flechas del panel.
 *
 *    «cada publicación debe presentar únicamente un extracto del contenido,
 *     permitiendo al usuario acceder a la nota completa»
 *        Extracto y «Ver más» a su página propia, /noticias/{slug}/.
 *
 *    «no se muestran los títulos ni las portadas de los videos»
 *        Los trae el servidor de YouTube al pegar el enlace, y la portada se
 *        guarda en la biblioteca. Aquí sólo se pintan.
 *
 *  ── La paginación ───────────────────────────────────────────────────────
 *
 *  Con enlaces de verdad, no con JavaScript. Cada página tiene su dirección,
 *  se puede compartir, Google la indexa y el botón «atrás» funciona. Es lo
 *  que se espera de un listado de noticias.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$paginaCms = $sitio->contenido('noticias');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);

$modelo = new \Intranet\Models\Noticia($sitio->contenedor());

/* La página pedida. Se valida aquí: lo que llega por la URL no entra en una
   consulta sin pasar por (int), y el modelo ya recorta a la última página si
   alguien escribe un número de más. */
$nPagina = max(1, (int) ($_GET['pagina'] ?? 1));
$listado = $modelo->publicadas($nPagina);
$videos  = $modelo->videos();

$meta = [
    'titulo'      => $nPagina > 1
        ? 'Noticias · página ' . $nPagina . ' · Viaje de León XIV al Perú'
        : 'Noticias · Viaje de León XIV al Perú',
    'descripcion' => 'Actualidad y comunicaciones oficiales sobre la Visita Apostólica '
                   . 'del Papa León XIV al Perú.',
    'ruta'        => 'noticias/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'website',
    'scripts'     => ['assets/js/noticias.js'],
];

/** «28 de septiembre de 2026». */
$dia = static function (?string $iso): string {
    if ($iso === null || $iso === '') {
        return '';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    [$a, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' de ' . ($meses[$m] ?? '') . ' de ' . $a;
};

/* ── La grande de la izquierda ────────────────────────────────────────────
   Es siempre LA MÁS RECIENTE, que es la primera de la lista porque la
   consulta ya viene ordenada.

   Hubo una versión con una marca de «destacada» para fijar una a mano, y se
   quitó: el cliente pidió expresamente «orden cronológico descendente», y una
   marca manual permite volver a poner una noticia de agosto por encima de una
   de octubre, que es exactamente el desorden del que se quejaba. La columna
   sigue en la base por si algún día hace falta otra cosa, pero no decide
   nada aquí.

   Sólo en la primera página: en la segunda no hay «lo más reciente» que
   destacar, y repetirla sería enseñar dos veces la misma noticia. */
$filas     = $listado['filas'];
$destacada = null;

if ($nPagina === 1 && $filas !== []) {
    $destacada = array_shift($filas);
    $filas     = array_values($filas);
}

$tope = \Intranet\Models\Noticia::VIDEOS_VISIBLES;
?>

<main id="contenido">

  <section class="hero hero--page nt-hero">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/prensa/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/prensa/hero.jpg')) ?>"
             alt="" width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="hero__inner">
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Noticias')) ?></h1>
      <p class="hero__sub"><?= $esc($campo('cabecera', 'texto',
          'Actualidad y comunicaciones oficiales rumbo a la visita del Santo Padre.')) ?></p>
    </div>
  </section>

  <div class="nt-cuerpo">

    <?php if ($filas === [] && $destacada === null): ?>
      <section class="nt-wrap">
        <h2 class="nt-h2">Todavía no hay noticias</h2>
        <p class="nt-vacio">Aquí se publicarán las comunicaciones oficiales sobre la visita.</p>
      </section>

    <?php else: ?>
      <section class="nt-wrap" aria-label="Noticias">

        <div class="nt-reja">

          <?php if ($destacada !== null): ?>
            <?php /* La grande de la izquierda. Es un <article> con su enlace
                     al final y no una tarjeta entera pulsable: así el titular
                     se puede seleccionar y copiar, y quien navega con teclado
                     llega a UN enlace, no a tres que van al mismo sitio. */ ?>
            <article class="nt-grande">
              <?php if (($destacada['imagen_ruta'] ?? null) !== null): ?>
                <a class="nt-grande__foto" href="<?= $esc($sitio->enlace('noticias/' . $destacada['slug'] . '/')) ?>" tabindex="-1" aria-hidden="true">
                  <?= $sitio->imagen($destacada, '', ['sizes' => '(min-width:1024px) 46vw, 92vw']) ?>
                </a>
              <?php endif; ?>

              <p class="nt-fecha"><?= $esc($dia($destacada['fecha'])) ?></p>

              <h2 class="nt-grande__h">
                <a href="<?= $esc($sitio->enlace('noticias/' . $destacada['slug'] . '/')) ?>"><?= $esc($destacada['titulo']) ?></a>
              </h2>

              <p class="nt-extracto"><?= $esc(\Intranet\Models\Noticia::extracto($destacada, 200)) ?></p>

              <p class="nt-mas">
                <a class="btn nt-mas__btn" href="<?= $esc($sitio->enlace('noticias/' . $destacada['slug'] . '/')) ?>">
                  Ver más<span class="visually-hidden"> sobre <?= $esc($destacada['titulo']) ?></span>
                </a>
              </p>
            </article>
          <?php endif; ?>

          <div class="nt-columna">
            <?php foreach ($filas as $n): ?>
              <article class="nt-item">
                <p class="nt-fecha"><?= $esc($dia($n['fecha'])) ?></p>

                <h2 class="nt-item__h">
                  <a href="<?= $esc($sitio->enlace('noticias/' . $n['slug'] . '/')) ?>"><?= $esc($n['titulo']) ?></a>
                </h2>

                <p class="nt-extracto"><?= $esc(\Intranet\Models\Noticia::extracto($n, 150)) ?></p>

                <p class="nt-mas">
                  <a class="btn nt-mas__btn" href="<?= $esc($sitio->enlace('noticias/' . $n['slug'] . '/')) ?>">
                    Ver más<span class="visually-hidden"> sobre <?= $esc($n['titulo']) ?></span>
                  </a>
                </p>
              </article>
            <?php endforeach; ?>
          </div>

        </div>

        <?php /* ── Paginación ──────────────────────────────────────────────
                 Enlaces de verdad: cada página tiene su dirección, se puede
                 compartir y el botón «atrás» funciona. */ ?>
        <?php if ((int) $listado['paginas'] > 1): ?>
          <nav class="nt-paginas" aria-label="Páginas de noticias">
            <?php if ($nPagina > 1): ?>
              <a class="nt-pagina nt-pagina--flecha"
                 href="<?= $esc($sitio->enlace('noticias/' . ($nPagina - 1 > 1 ? '?pagina=' . ($nPagina - 1) : ''))) ?>"
                 rel="prev">‹ Anteriores</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= (int) $listado['paginas']; $p++): ?>
              <a class="nt-pagina<?= $p === (int) $listado['pagina'] ? ' is-activa' : '' ?>"
                 href="<?= $esc($sitio->enlace('noticias/' . ($p > 1 ? '?pagina=' . $p : ''))) ?>"
                 <?= $p === (int) $listado['pagina'] ? 'aria-current="page"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($nPagina < (int) $listado['paginas']): ?>
              <a class="nt-pagina nt-pagina--flecha"
                 href="<?= $esc($sitio->enlace('noticias/?pagina=' . ($nPagina + 1))) ?>"
                 rel="next">Siguientes ›</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

      </section>
    <?php endif; ?>

    <?php /* ═══════════════════════════════════════════════════════ VÍDEOS ══
         Cuadrícula de tres. Se ven los seis primeros y el resto aparece al
         pulsar «Ver más vídeos»: los demás ya están en el HTML pero ocultos,
         así que sin JavaScript se ven TODOS, que es la respuesta correcta
         —el botón esconde, no trae—.

         La portada y el título los trajo el servidor de YouTube al pegar el
         enlace, y la portada vive en nuestra biblioteca: el navegador del
         visitante no habla con Google hasta que pulsa el vídeo. */ ?>
    <?php if ($videos !== []): ?>
      <section class="nt-videos" aria-labelledby="t-videos" data-videos>
        <div class="nt-wrap">
          <h2 class="nt-h2" id="t-videos"><?= $esc($campo('videos', 'titulo', 'Vídeos')) ?></h2>

          <ul class="nt-vreja">
            <?php foreach ($videos as $i => $v): ?>
              <li class="nt-video<?= $i >= $tope ? ' nt-video--extra' : '' ?>" <?= $i >= $tope ? 'data-extra' : '' ?>>
                <button class="nt-video__abrir" type="button"
                        data-ver-video
                        data-id="<?= $esc($v['youtube_id']) ?>"
                        data-titulo="<?= $esc($v['titulo'] ?? '') ?>"
                        data-desc="<?= $esc($v['descripcion'] ?? '') ?>"
                        aria-label="Reproducir «<?= $esc($v['titulo'] ?? 'vídeo') ?>» · se conectará con YouTube">
                  <?php if (($v['imagen_ruta'] ?? null) !== null): ?>
                    <?= $sitio->imagen($v, '', ['sizes' => '(min-width:1024px) 31vw, (min-width:768px) 46vw, 92vw']) ?>
                  <?php else: ?>
                    <span class="nt-video__sinportada" aria-hidden="true"></span>
                  <?php endif; ?>

                  <span class="nt-video__play" aria-hidden="true">
                    <svg viewBox="0 0 68 48" width="68" height="48">
                      <path d="M66.5 7.5a8.6 8.6 0 0 0-6-6C55.2 0 34 0 34 0S12.8 0 7.5 1.4a8.6 8.6 0 0 0-6 6A90 90 0 0 0 0 24a90 90 0 0 0 1.5 16.5 8.6 8.6 0 0 0 6 6C12.8 48 34 48 34 48s21.2 0 26.5-1.4a8.6 8.6 0 0 0 6-6A90 90 0 0 0 68 24a90 90 0 0 0-1.5-16.5z" fill="currentColor"/>
                      <path d="M27 34V14l18 10z" fill="#fff"/>
                    </svg>
                  </span>
                </button>

                <h3 class="nt-video__h"><?= $esc($v['titulo'] ?? 'Vídeo') ?></h3>

                <?php if (trim((string) ($v['autor'] ?? '')) !== ''): ?>
                  <p class="nt-video__autor"><?= $esc($v['autor']) ?></p>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if (count($videos) > $tope): ?>
            <p class="nt-vmas">
              <button class="btn btn--linea" type="button" data-mas-videos hidden>
                Ver más vídeos
                <span class="nt-vmas__num"><?= count($videos) - $tope ?></span>
              </button>
            </p>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

  </div><!-- /.nt-cuerpo -->

  <?php /* La ventana del vídeo. Una sola para toda la página; el <iframe> lo
           crea el JavaScript al pulsar, no antes: así no hay una petición a
           YouTube por cada vídeo nada más abrir la página. */ ?>
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

</main>
