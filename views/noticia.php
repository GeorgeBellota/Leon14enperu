<?php
/**
 * ============================================================================
 *  Noticia — la página de una noticia.
 * ============================================================================
 *
 *  La «interna» a la que se llega al pulsar «Ver más» en el listado. Es lo
 *  que el cliente pidió: «permitiendo al usuario acceder a la nota completa
 *  al seleccionar la opción correspondiente».
 *
 *  La resuelve index.php: parte /noticias/{slug}/ por la última barra, busca
 *  el slug en la tabla `noticias` y manda aquí con la fila ya cargada en
 *  $noticia. Sólo encuentra las PUBLICADAS; un borrador da 404.
 *
 *  ── El cuerpo se imprime sin escapar ────────────────────────────────────
 *
 *  Y es seguro, porque no se guarda sin filtrar: HtmlSeguro lo limpia en el
 *  servidor ANTES de llegar a la base. Lo que hay en `cuerpo` ya pasó por la
 *  lista blanca —<p>, <strong>, <a>, <img>, <h3>, listas— y no puede traer
 *  <script>, atributos «on…» ni enlaces «javascript:».
 *
 *  Si alguna vez se escribe otro camino que guarde en esa columna, tiene que
 *  pasar por el mismo filtro. Es la única regla que sostiene esta línea.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 *  @var array    $noticia   la fila, puesta por index.php
 */

declare(strict_types=1);

/* Sin noticia no se pinta nada. No debería pasar —index.php sólo manda aquí
   cuando la encontró—, pero una vista que confía ciegamente en una variable
   del armazón revienta con un error ilegible el día que algo cambie. */
if (!isset($noticia) || !is_array($noticia)) {
    http_response_code(404);
    echo '<main id="contenido"><p>Esa noticia no existe.</p></main>';

    return;
}

$dia = static function (?string $iso): string {
    if ($iso === null || $iso === '') {
        return '';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    [$a, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' de ' . ($meses[$m] ?? '') . ' de ' . $a;
};

$resumen = \Intranet\Models\Noticia::extracto($noticia, 200);

/* El SEO propio de la noticia, que es lo que se pidió. Vacío, se usan el
   titular y el extracto: así no hay que rellenarlo dos veces en cada nota y
   se puede cambiar cuando el titular bueno para la web no es el de Google. */
/* ── Los vídeos del cuerpo ────────────────────────────────────────────────
   Lo guardado es una marca que escribió el servidor al guardar la noticia:

       <figure data-youtube="eoLVnA036Lw" data-portada="47"></figure>

   Nunca un <iframe>. Aquí se convierte en la portada con su botón de play, y
   el <iframe> lo crea visor-video.js cuando alguien pulsa. Mientras tanto la
   página no ha hablado con YouTube: la portada vive en nuestra biblioteca.

   El identificador se vuelve a comprobar aunque venga de nuestra propia base:
   lo que acaba dentro de un atributo no se da por bueno por su procedencia. */
$conVideos = static function (string $html) use ($sitio, $esc): string {
    if (!str_contains($html, 'data-youtube')) {
        return $html;
    }

    return (string) preg_replace_callback(
        '~<figure([^>]*)data-youtube="([^"]+)"([^>]*)></figure>~i',
        static function (array $m) use ($sitio, $esc): string {
            $yt = $m[2];

            if (preg_match('~^[A-Za-z0-9_-]{11}$~', $yt) !== 1) {
                return '';
            }

            $foto = null;

            if (preg_match('~data-portada="(\d+)"~', $m[1] . $m[3], $d) === 1) {
                $foto = $sitio->medio((int) $d[1]);
            }

            ob_start(); ?>
<figure class="na-video">
  <button class="na-video__play" type="button"
          data-ver-video data-id="<?= $esc($yt) ?>"
          aria-label="Reproducir el vídeo · se conectará con YouTube">
    <?php if ($foto !== null): ?>
      <?= $sitio->imagen($foto, '', ['sizes' => '(min-width:768px) 70vw, 94vw']) ?>
    <?php else: ?>
      <span class="na-video__sinportada" aria-hidden="true"></span>
    <?php endif; ?>
    <span class="na-video__icono" aria-hidden="true">
      <svg viewBox="0 0 68 48" focusable="false">
        <path d="M66.5 7.7a8.6 8.6 0 0 0-6-6C55.2 0 34 0 34 0S12.8 0 7.5 1.6a8.6 8.6 0 0 0-6 6A90 90 0 0 0 0 24a90 90 0 0 0 1.5 16.3 8.6 8.6 0 0 0 6 6C12.8 48 34 48 34 48s21.2 0 26.5-1.6a8.6 8.6 0 0 0 6-6A90 90 0 0 0 68 24a90 90 0 0 0-1.5-16.3z" fill="currentColor"/>
        <path d="M27 34V14l18 10z" fill="#fff"/>
      </svg>
    </span>
  </button>
</figure>
<?php
            return (string) ob_get_clean();
        },
        $html
    );
};

$meta = [
    /* La ventana del vídeo, para los que lleve el cuerpo. */
    'scripts' => ['assets/js/visor-video.js'],
    'titulo'      => trim((string) ($noticia['seo_titulo'] ?? '')) !== ''
        ? (string) $noticia['seo_titulo']
        : $noticia['titulo'] . ' · León XIV en el Perú',
    'descripcion' => trim((string) ($noticia['seo_descripcion'] ?? '')) !== ''
        ? (string) $noticia['seo_descripcion']
        : $resumen,
    'ruta'        => 'noticias/' . $noticia['slug'] . '/',
    'og_tipo'     => 'article',
];

/* La imagen al compartir: la elegida para eso, si no la portada, si no la
   del sitio. El enlace en WhatsApp sin imagen se ve pobre. */
$ogRuta = trim((string) ($noticia['og_ruta'] ?? '')) !== ''
    ? (string) $noticia['og_ruta']
    : (string) ($noticia['imagen_ruta'] ?? '');

$meta['og_imagen'] = $ogRuta !== '' ? ltrim($ogRuta, '/') : 'assets/img/og/og-inicio.jpg';
?>

<main id="contenido">

  <article class="na">

    <?php /* ── La portada ───────────────────────────────────────────────── */ ?>
    <?php if (($noticia['imagen_ruta'] ?? null) !== null): ?>
      <div class="na-portada">
        <?= $sitio->imagen($noticia, '', ['sizes' => '100vw', 'prioridad' => true]) ?>
      </div>
    <?php endif; ?>

    <div class="na-wrap">

      <nav class="na-migas" aria-label="Migas de pan">
        <ol>
          <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
          <li><a href="<?= $esc($sitio->enlace('noticias/')) ?>">Noticias</a></li>
          <li><span aria-current="page"><?= $esc($noticia['titulo']) ?></span></li>
        </ol>
      </nav>

      <header class="na-cab">
        <?php /* La fecha en <time> y no en un <p> suelto: es un dato, y así
                 Google y los lectores de pantalla saben que lo es. */ ?>
        <p class="na-fecha">
          <time datetime="<?= $esc((string) $noticia['fecha']) ?>"><?= $esc($dia($noticia['fecha'])) ?></time>
          <?php if (trim((string) ($noticia['fuente'] ?? '')) !== ''): ?>
            <span class="na-fuente"><?= $esc($noticia['fuente']) ?></span>
          <?php endif; ?>
        </p>

        <h1 class="na-h1"><?= $esc($noticia['titulo']) ?></h1>

        <?php if (trim((string) ($noticia['resumen'] ?? '')) !== ''): ?>
          <p class="na-entradilla"><?= $esc($noticia['resumen']) ?></p>
        <?php endif; ?>
      </header>

      <?php /* El cuerpo, ya filtrado por HtmlSeguro al guardarse. Lo único
               que se hace aquí es convertir las marcas de vídeo en su
               portada: el HTML no se vuelve a tocar. */ ?>
      <div class="na-cuerpo">
        <?= $conVideos((string) ($noticia['cuerpo'] ?? '')) ?>
      </div>

      <footer class="na-pie">
        <a class="btn btn--linea na-volver" href="<?= $esc($sitio->enlace('noticias/')) ?>">‹ Todas las noticias</a>
      </footer>

    </div>
  </article>

  <?php /* La ventana donde se reproduce un vídeo del cuerpo. */ ?>
  <?php require __DIR__ . '/_visor-video.php'; ?>

</main>
