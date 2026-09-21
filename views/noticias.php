<?php
/**
 * ============================================================================
 *  Noticias — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. Una web sobre un viaje papal no puede quedarse
 *  muda porque falle la base.
 *
 *  ── Cómo se reparten las noticias ────────────────────────────────────────
 *
 *  El editable dibuja UNA noticia destacada —foto grande, titular a 48 px— y
 *  a su derecha una lista de noticias separadas por filetes. En el panel eso
 *  es una sola sección, «Últimas noticias»: la PRIMERA de la lista es la
 *  destacada y las demás van a la columna de la derecha. Así el editor
 *  decide cuál manda simplemente subiéndola con las flechas, sin tener que
 *  entender que hay dos sitios distintos donde escribir una noticia.
 *
 *  Cada noticia tiene su propia página: la abre views/detalle.php a partir
 *  del `slug` del bloque, porque la sección está marcada con «detalle» en su
 *  columna `datos`. El botón «Ver más» lleva ahí.
 *
 *  Las medidas son las del editable NOTICIAS.ai (mesa de 1440 px). La hoja
 *  assets/css/paginas/noticias.css las reproduce con la unidad --u.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Noticias · León XIV en el Perú',
    'descripcion' => 'Actualidad y comunicaciones oficiales rumbo a la Visita Apostólica del '
                   . 'Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.',
    'ruta'        => 'noticias/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'website',
];

$paginaCms = $sitio->contenido('noticias');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);

/* El texto de un sumario llega del panel como texto llano. Se escapa y se
   respetan los saltos de línea que haya escrito el editor: es lo único que
   tiene para separar ideas en un campo sin formato. */
$parrafo = static fn (string $t): string => nl2br($esc($t), false);

/**
 * El destino de un enlace escrito en el panel.
 *
 * Si es una dirección completa («https://…»), un ancla o un correo, se
 * respeta tal cual. Si es un camino del propio sitio —«sedes/»— se pasa por
 * Sitio::enlace(), porque el sitio no cuelga siempre de la raíz del dominio
 * y un enlace relativo se rompería según desde qué página se pinte.
 *
 * ── Ojo con los nombres de variable ──────────────────────────────────────
 * La vista se ejecuta en el mismo ámbito que index.php y que la plantilla
 * común, así que aquí NO se puede usar cualquier nombre: `$destino`, por
 * ejemplo, es la ruta resuelta que después lee _plantilla.php para saber qué
 * hoja de estilos cargar. Pisarlo deja la página en blanco con un error.
 */
$aEnlace = static function (string $url) use ($sitio): string {
    $url = trim($url);

    if ($url === '' || $url === '#') {
        return '';
    }

    return preg_match('~^(?:[a-z][a-z0-9+.-]*:|//|/|#)~i', $url) === 1
        ? $url
        : $sitio->enlace($url);
};

/**
 * A dónde lleva el «Ver más» de una noticia.
 *
 * Primero su página propia, la que abre views/detalle.php con el slug. Si la
 * pieza no tiene slug —todavía no se le ha escrito el cuerpo— se cae al
 * enlace que traiga el bloque. Si no hay ni una cosa ni la otra, devuelve
 * cadena vacía y el botón no se pinta: mejor sin botón que con un botón que
 * no va a ninguna parte.
 */
$destinoNoticia = static function (array $n) use ($sitio, $aEnlace): string {
    $slug = trim((string) ($n['slug'] ?? ''));

    if ($slug !== '') {
        return $sitio->enlace('noticias/' . $slug . '/');
    }

    return $aEnlace((string) ($n['enlace_url'] ?? ''));
};

/**
 * El identificador de YouTube que hay dentro de lo que se pegue en el panel.
 *
 * Se guarda el IDENTIFICADOR, nunca el <iframe>: es la misma regla que sigue
 * Sitio::directo() para la transmisión en directo. Aquí se admite además el
 * enlace entero —que es lo que copia cualquiera desde el navegador— y se le
 * saca el identificador. Si no cuadra con el formato, se devuelve vacío y la
 * tarjeta se queda como el marco negro del editable, sin reproductor.
 */
$idVideo = static function (string $valor): string {
    $valor = trim($valor);

    if ($valor === '') {
        return '';
    }

    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $valor) === 1) {
        return $valor;
    }

    return preg_match('~(?:youtu\.be/|[?&]v=|/embed/|/shorts/|/live/)([A-Za-z0-9_-]{11})~', $valor, $m) === 1
        ? $m[1]
        : '';
};
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       La banda duotono de 582 px con el título y el sumario centrados. La
       fotografía se cambia en Páginas → Noticias → Cabecera de página; lo
       que va de respaldo es la del editable.

       El sumario son dos líneas de peso distinto. Del panel llega un solo
       texto: la PRIMERA línea va en seminegrita y el resto en fina, que es
       como lo compone el editable. */ ?>
  <?php
  $sumario = $campo('cabecera', 'texto', "Actualidad y comunicaciones oficiales\nrumbo a la visita del Santo Padre.");
  $lineas  = preg_split('/\R/u', $sumario) ?: [$sumario];
  $primera = trim((string) array_shift($lineas));
  $resto   = trim(implode(' ', array_map('trim', $lineas)));
  ?>
  <section class="hero hero--page hero--noticias">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/noticias/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/noticias/hero.jpg')) ?>"
             alt="El Papa León XIV acompañado por obispos y cardenales"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="container hero__inner">
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Noticias')) ?></h1>
      <?php if ($primera !== ''): ?>
        <p class="hero__sub">
          <strong><?= $esc($primera) ?> </strong><?php if ($resto !== ''): ?><br class="br-esc"><?= $esc($resto) ?><?php endif; ?>
        </p>
      <?php endif; ?>
    </div>
  </section>

  <?php /* ═══════════════════════════════════════ NOTICIAS Y VÍDEOS ════════
       Las dos cosas comparten la banda gris y la caja de composición de
       1219,5 px del editable, así que van dentro de la misma sección. */ ?>
  <section class="noticias" aria-label="Noticias del viaje apostólico">
    <div class="noticias__wrap">

      <?php
      /* La lista completa. La reserva es, literalmente, lo que dibuja el
         editable: una destacada y tres noticias más. */
      $todas = $bloques('ultimas-noticias', [
          [
              'titulo'     => 'Papa León XIV envía saludos al pueblo peruano',
              'enlace_url' => 'papa-leon-xiv/',
              'datos'      => ['fecha' => '15 de septiembre de 2026'],
              /* El único sumario con seminegrita dentro. Como el campo del
                 panel es texto llano, el matiz sólo se conserva mientras
                 mande la reserva; en cuanto haya texto en la base se pinta
                 escapado, sin formato. */
              'respaldo_html' => 'El Santo Padre estará en el país del <strong>11 al 16 de <br class="br-esc">noviembre</strong>, en la tercera etapa de su primera gira <br class="br-esc">sudamericana, después de Uruguay y Argentina. El <br class="br-esc">anuncio no incluyó el programa detallado, que se dará <br class="br-esc">a conocer a su debido tiempo.',
          ],
          [
              'titulo'     => 'Lima, Chiclayo, Cusco y Pucallpa serán las sedes de la visita',
              'texto'      => 'Cuatro ciudades que representan la costa, la sierra y la selva, y la diócesis que el Santo Padre pastoreó durante ocho años.',
              'enlace_url' => 'sedes/',
              'datos'      => ['fecha' => 'Fecha por confirmar'],
          ],
          [
              'titulo'     => 'Abierta la convocatoria de voluntariado «Los amigos de León»',
              'texto'      => 'Seis servicios y tres fases de selección para quienes quieran acompañar la visita desde dentro.',
              'enlace_url' => 'voluntariado/',
              'datos'      => ['fecha' => 'Fecha por confirmar'],
          ],
          [
              'titulo'     => 'El programa del viaje se publicará más adelante',
              'texto'      => 'La Oficina de Prensa de la Santa Sede dará a conocer el programa detallado a su debido tiempo.',
              'enlace_url' => 'agenda/',
              'datos'      => ['fecha' => 'Fecha por confirmar'],
          ],
      ]);

      $destacada = array_shift($todas) ?? [];
      $urlDest   = $destinoNoticia($destacada);
      $textoDest = trim((string) ($destacada['texto'] ?? ''));
      ?>

      <div class="noticias__grid">

        <?php /* ── La noticia destacada, con la foto grande a la izquierda ── */ ?>
        <article class="destacada">
          <p class="destacada__foto">
            <?php ob_start(); ?>
            <picture>
              <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/noticias/p01.webp')) ?>" type="image/webp">
              <img src="<?= $esc($sitio->asset('assets/img/rediseno/noticias/p01.jpg')) ?>"
                   alt="El Papa León XIV durante una celebración litúrgica"
                   width="1249" height="753" loading="lazy" decoding="async">
            </picture>
            <?php $respaldoFoto = (string) ob_get_clean(); ?>
            <?= $sitio->imagen($destacada, $respaldoFoto, ['sizes' => '(min-width:1024px) 48vw, 100vw']) ?>
          </p>

          <p class="destacada__fecha"><?= $esc((string) ($destacada['datos']['fecha'] ?? 'Fecha por confirmar')) ?></p>
          <h2 class="destacada__titulo"><?= $esc((string) ($destacada['titulo'] ?? '')) ?></h2>

          <p class="destacada__texto">
            <?= $textoDest !== '' ? $parrafo($textoDest) : (string) ($destacada['respaldo_html'] ?? '') ?>
          </p>

          <?php /* El botón es una pieza de tamaño fijo del editable —165 × 39,2 px—,
                   así que su texto no se edita: lo que se edita es a dónde lleva. */ ?>
          <?php if ($urlDest !== ''): ?>
            <a class="btn destacada__mas" href="<?= $esc($urlDest) ?>"<?= preg_match('~^https?://~i', $urlDest) === 1 ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>Ver más</a>
          <?php endif; ?>
        </article>

        <?php /* ── El resto, en lista, separadas por filetes ──────────────── */ ?>
        <?php if ($todas !== []): ?>
          <ol class="noticias__lista">
            <?php foreach ($todas as $n): ?>
              <?php
              $url   = $destinoNoticia($n);
              $texto = trim((string) ($n['texto'] ?? ''));
              ?>
              <li class="noticia">
                <p class="noticia__fecha"><?= $esc((string) ($n['datos']['fecha'] ?? 'Fecha por confirmar')) ?></p>
                <h2 class="noticia__titulo"><?= $esc((string) ($n['titulo'] ?? '')) ?></h2>
                <?php if ($texto !== ''): ?>
                  <p class="noticia__texto"><?= $parrafo($texto) ?></p>
                <?php endif; ?>
                <?php if ($url !== ''): ?>
                  <a class="btn noticia__mas" href="<?= $esc($url) ?>"<?= preg_match('~^https?://~i', $url) === 1 ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>Ver más</a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>

      </div>

      <hr class="noticias__filete">

      <?php /* ══════════════════════════════════════════════════ VÍDEOS ════
           El editable sólo dibuja los marcos negros con el botón de
           reproducir: no hay miniaturas ni identificadores de YouTube.
           Cuando los haya, se pegan en el panel —Páginas → Noticias →
           Vídeos— en el enlace de cada tarjeta, y de ahí sale el
           data-video que monta el reproductor rediseno.js.

           Mientras tanto data-video va vacío: la tarjeta se ve igual que en
           el editable y no hace nada al pulsarla. */ ?>
      <?php
      $videos = $bloques('videos', [[], [], []]);
      $ordinales = ['primer', 'segundo', 'tercer', 'cuarto', 'quinto', 'sexto'];
      ?>
      <h2 class="videos__h"><?= $esc($campo('videos', 'titulo', 'Videos')) ?></h2>

      <div class="rail videos__rail" role="group" aria-label="Vídeos de la visita">
        <?php foreach ($videos as $i => $v): ?>
          <?php
          $idV         = $idVideo((string) ($v['enlace_url'] ?? ''));
          $tituloVideo = trim((string) ($v['titulo'] ?? ''));
          $rotuloVideo = $tituloVideo !== ''
              ? 'Reproducir el vídeo «' . $tituloVideo . '»'
              : 'Reproducir el ' . ($ordinales[$i] ?? ($i + 1) . '.º') . ' vídeo';
          ?>
          <button class="video-card videos__card" type="button"
                  data-video="<?= $esc($idV) ?>" aria-label="<?= $esc($rotuloVideo) ?>">
            <?php /* La miniatura es opcional: sin ella queda el marco negro
                     del editable, que es como está hoy. */ ?>
            <?= $sitio->imagen($v, '', ['sizes' => '(min-width:1024px) 38vw, 80vw']) ?>
            <span class="video-card__play" aria-hidden="true"></span>
          </button>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

</main>
