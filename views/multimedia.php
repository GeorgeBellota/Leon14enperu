<?php
/**
 * ============================================================================
 *  Multimedia — la galería de imágenes.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  ── De dónde sale lo que se ve ──────────────────────────────────────────
 *
 *  Del panel, en Contenidos → Multimedia, y NO de las secciones del gestor de
 *  páginas: una galería tiene tres niveles —actividad, fecha, fotografías— y
 *  las secciones sólo dan dos. Lo único que se edita en Páginas → Multimedia
 *  es la cabecera.
 *
 *  Una actividad sin fotografías no se pinta: sería un titular con un hueco
 *  debajo. Y si no hay ninguna, la página lo dice en vez de quedarse en
 *  blanco.
 *
 *  ── El original ─────────────────────────────────────────────────────────
 *
 *  La cuadrícula y la ventana grande sirven las VARIANTES —640, 1024, 1600—,
 *  que es lo que hace que esto cargue en un teléfono. El botón «Descargar»
 *  entrega el archivo ORIGINAL, sin recortar ni recomprimir, que es lo que
 *  necesita un medio que vaya a publicarla.
 *
 *  Las fotografías subidas antes de octubre de 2026 no tienen original: se
 *  borraba al generar las variantes. De ésas se ofrece la mayor que haya.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Multimedia · Viaje de León XIV al Perú',
    'descripcion' => 'Galería de imágenes de la Visita Apostólica del Papa León XIV al Perú, '
                   . 'agrupadas por actividad y fecha.',
    'ruta'        => 'multimedia/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
    'scripts'     => ['assets/js/multimedia.js'],
];

$paginaCms = $sitio->contenido('multimedia');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);

/* La galería no vive en `secciones`: tiene tablas propias. Se pide aquí, una
   vez, ya agrupada por actividad y fecha. */
$actividades = (new \Intranet\Models\Galeria($sitio->contenedor()))->paraLaWeb();

/** «14 de noviembre de 2026» se lee; «2026-11-14» se descifra. */
$dia = static function (string $iso): string {
    if ($iso === '') {
        return '';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    [$a, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' de ' . ($meses[$m] ?? '') . ' de ' . $a;
};

/** El día en corto, para las pestañas estrechas del móvil. */
$diaCorto = static function (string $iso): string {
    if ($iso === '') {
        return 'Sin fecha';
    }

    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun',
              'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    [, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' ' . ($meses[$m] ?? '');
};

/**
 * La dirección del archivo que se descarga.
 *
 * El original si existe; si no —las anteriores a octubre de 2026—, la mayor
 * variante que haya. Nunca se deja sin descarga: una galería de prensa sin
 * botón de bajar no sirve de nada.
 */
$descarga = static function (array $foto) use ($sitio): string {
    $original = trim((string) ($foto['original'] ?? ''));

    if ($original !== '') {
        return $sitio->enlace(ltrim($original, '/'));
    }

    $variantes = \Intranet\Models\Medio::decodificar($foto['variantes'] ?? null);
    $base      = (string) ($variantes['base'] ?? '');
    $anchos    = (array) ($variantes['anchos'] ?? []);
    $formatos  = (array) ($variantes['formatos'] ?? []);

    if ($base !== '' && $anchos !== []) {
        // El formato de respaldo —jpg o png—, no el webp: lo que se descarga
        // tiene que abrirse en cualquier programa, no sólo en un navegador.
        $formato = in_array('jpg', $formatos, true) ? 'jpg' : (string) (end($formatos) ?: 'jpg');

        return $sitio->enlace($base . '-' . max($anchos) . '.' . $formato);
    }

    return $sitio->enlace(ltrim((string) $foto['ruta'], '/'));
};

/** Un nombre de archivo decente para la descarga, no «img-3abdfcb0.jpg». */
$nombreDescarga = static function (array $foto, string $actividad): string {
    $base = trim((string) ($foto['descripcion'] ?? '')) !== ''
        ? (string) $foto['descripcion']
        : $actividad;

    $limpio = \Intranet\Cms\Slug::normalizar(mb_substr($base, 0, 60));
    $ext    = strtolower((string) pathinfo((string) ($foto['original'] ?? $foto['ruta']), PATHINFO_EXTENSION));

    return ($limpio !== '' ? $limpio : 'fotografia') . '.' . ($ext !== '' ? $ext : 'jpg');
};
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       Banda con la fotografía en duotono. Se edita en Páginas → Multimedia →
       Cabecera de página. */ ?>
  <section class="hero hero--page mm-hero">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/prensa/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/prensa/hero.jpg')) ?>"
             alt="Fotógrafos y cámaras de televisión cubriendo un acto del Santo Padre"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="hero__inner">
      <?php /* La bajada sale del campo «Texto» de la cabecera, como en las
               demás páginas. El editable dice «Galería de imágenes», que es
               lo que se pinta mientras nadie escriba otra cosa en el panel. */ ?>
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Multimedia')) ?></h1>
      <p class="hero__sub"><?= $esc($campo('cabecera', 'texto', 'Galería de imágenes')) ?></p>
    </div>
  </section>

  <div class="mm-cuerpo">

    <?php if ($actividades === []): ?>
      <?php /* Sin fotografías todavía. Se dice, en lugar de dejar la página en
               blanco y que parezca rota. */ ?>
      <section class="mm-vacio">
        <div class="mm-wrap">
          <h2 class="mm-h2">Todavía no hay fotografías</h2>
          <p class="mm-vacio__texto">
            <?= $esc($campo('cabecera', 'texto',
                'Las imágenes de la Visita Apostólica se publicarán aquí, agrupadas por actividad y por fecha.')) ?>
          </p>
        </div>
      </section>
    <?php else: ?>

      <?php foreach ($actividades as $actividad): ?>
        <?php
        $idAct    = (int) $actividad['id'];
        $porFecha = (array) $actividad['por_fecha'];

        /* Las fechas que esta actividad tiene de verdad. Se arman de lo que
           hay: una fecha sin fotografías no existe y no deja pestaña. */
        $fechas = array_keys($porFecha);

        /* Con una sola fecha el filtro sobra: sería un desplegable de un
           elemento. Se pinta la fecha como rótulo y punto. */
        $conFiltro = count($fechas) > 1;
        ?>
        <section class="mm-actividad" aria-labelledby="t-act-<?= $idAct ?>" data-actividad>
          <div class="mm-wrap">

            <header class="mm-actividad__cab">
              <?php if (trim((string) $actividad['rotulo']) !== ''): ?>
                <p class="mm-actividad__rotulo"><?= $esc($actividad['rotulo']) ?></p>
              <?php endif; ?>

              <h2 class="mm-h2" id="t-act-<?= $idAct ?>"><?= $esc($actividad['nombre']) ?></h2>
            </header>

            <?php if ($conFiltro): ?>
              <?php /* El «Fecha ▼» del diseño. Son botones y no un <select>
                       porque hay que poder ver todas a la vez, y porque en el
                       móvil una fila que se desliza se maneja mejor que un
                       desplegable del sistema.

                       Sin JavaScript se ven TODAS las fotografías, que es la
                       respuesta correcta: el filtro quita, no añade. */ ?>
              <div class="mm-fechas" role="group" aria-label="Filtrar por fecha">
                <button class="mm-fecha is-activa" type="button" data-fecha="" aria-pressed="true">
                  Todas
                </button>

                <?php foreach ($fechas as $fecha): ?>
                  <button class="mm-fecha" type="button"
                          data-fecha="<?= $esc((string) $fecha) ?>" aria-pressed="false">
                    <span class="mm-fecha__largo"><?= $esc($dia((string) $fecha) ?: 'Sin fecha') ?></span>
                    <span class="mm-fecha__corto"><?= $esc($diaCorto((string) $fecha)) ?></span>
                    <span class="mm-fecha__num"><?= count($porFecha[$fecha]) ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php elseif (($fechas[0] ?? '') !== ''): ?>
              <p class="mm-actividad__fecha"><?= $esc($dia((string) $fechas[0])) ?></p>
            <?php endif; ?>

            <ul class="mm-rejilla">
              <?php foreach ($porFecha as $fecha => $fotos): ?>
                <?php foreach ($fotos as $foto): ?>
                  <?php
                  $pie     = trim((string) ($foto['descripcion'] ?? ''));
                  $alt     = trim((string) ($foto['alt'] ?? '')) ?: $pie;
                  $grande  = $sitio->enlace(ltrim((string) $foto['ruta'], '/'));
                  $archivo = $descarga($foto);
                  ?>
                  <li class="mm-pieza" data-fecha="<?= $esc((string) $fecha) ?>">
                    <?php /* Un <button> y no un <a>: lo que hace es abrir una
                             ventana en esta misma página, no ir a otra. Con un
                             enlace, el tabulador prometería una navegación que
                             no ocurre. */ ?>
                    <button class="mm-pieza__abrir" type="button"
                            data-ver
                            data-grande="<?= $esc($grande) ?>"
                            data-descarga="<?= $esc($archivo) ?>"
                            data-nombre="<?= $esc($nombreDescarga($foto, (string) $actividad['nombre'])) ?>"
                            data-pie="<?= $esc($pie) ?>"
                            data-original="<?= ($foto['original'] ?? null) !== null ? '1' : '0' ?>"
                            aria-label="Ver más grande<?= $pie !== '' ? ': ' . $esc($pie) : '' ?>">
                      <?= $sitio->imagen($foto, '', ['sizes' => '(min-width:1024px) 31vw, (min-width:768px) 46vw, 92vw']) ?>
                      <span class="mm-pieza__lupa" aria-hidden="true"></span>
                    </button>

                    <?php if ($pie !== ''): ?>
                      <p class="mm-pieza__pie"><?= $esc($pie) ?></p>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </ul>

            <?php /* Cuando un filtro deja la rejilla vacía. Lo enseña el JS. */ ?>
            <p class="mm-sinresultados" hidden>No hay fotografías de esa fecha.</p>

          </div>
        </section>
      <?php endforeach; ?>

    <?php endif; ?>

  </div><!-- /.mm-cuerpo -->

  <?php /* ═════════════════════════════════════════════════════ LA VENTANA ══
       Una sola para toda la página: el contenido lo pone el JavaScript al
       pulsar. Pintar una por fotografía metería cien <dialog> en el HTML.

       Va <dialog> de verdad y no un <div>: el navegador se encarga del foco,
       de Escape y de dejar fuera el resto de la página, que es lo que un div
       con position:fixed no hace sin bastante JavaScript. */ ?>
  <dialog class="mm-visor" data-visor aria-label="Fotografía ampliada">
    <div class="mm-visor__caja">
      <button class="mm-visor__cerrar" type="button" data-cerrar aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>

      <figure class="mm-visor__fig">
        <img class="mm-visor__img" data-visor-img src="" alt="">
        <figcaption class="mm-visor__pie" data-visor-pie></figcaption>
      </figure>

      <div class="mm-visor__mandos">
        <a class="btn mm-visor__bajar" data-visor-bajar href="" download>
          <svg class="ico-baja" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M12 1.2v16.6M7.4 13.2 12 17.8l4.6-4.6" fill="none" stroke="currentColor"
                  stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M1.2 11.7v11.1h21.6V11.7" fill="none" stroke="currentColor"
                  stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Descargar</span>
        </a>
        <span class="mm-visor__nota" data-visor-nota></span>
      </div>
    </div>
  </dialog>

</main>
