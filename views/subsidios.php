<?php
/**
 * ============================================================================
 *  Subsidios pastorales — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Es la página que reparte documentos: lo que tiene que funcionar sí o sí es
 *  el botón de descarga. Por eso las dos decisiones que la gobiernan son
 *  conservadoras:
 *
 *   · Todo lo que se lee de la base lleva su texto de reserva —el del
 *     editable SUBSIDIOS.ai—, así que si MySQL no responde la página se pinta
 *     igual, con las seis piezas y sus enlaces.
 *   · El botón sólo se pinta si el archivo EXISTE de verdad. Un enlace de
 *     descarga que da 404 en la página cuyo trabajo es repartir documentos es
 *     peor que la ausencia del botón.
 *
 *  La dirección antigua de esta página era /materiales/; Rutas::mudanzas() la
 *  responde con un 301 hacia aquí, así que lo que ya está compartido e
 *  indexado sigue funcionando.
 *
 *  Las medidas son las del editable (mesa de 1440 px). La hoja
 *  assets/css/paginas/subsidios.css las reproduce con la unidad --u.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Subsidios Pastorales · León XIV en el Perú',
    'descripcion' => 'Cinco subsidios pastorales de la Conferencia Episcopal Peruana para preparar '
                   . 'la visita del Papa León XIV al Perú en la parroquia, el colegio y la familia. '
                   . 'Descarga libre y uso gratuito.',
    'ruta'        => 'subsidios/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
];

$paginaCms = $sitio->contenido('subsidios');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
$rico    = static fn (?string $v): string
    => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

/* ── Qué secciones se pintan ───────────────────────────────────────────────
   Una sección apagada en el panel no se pinta. Pero con la base caída NO hay
   ninguna sección, y entonces se pintan todas con su reserva: una página muda
   es peor que una sección de más. */
$pintar = static fn (string $s): bool => $secciones === [] || $hay($s);

/* ── El peso de un archivo, medido y no escrito ────────────────────────────
   Se lee del archivo real en cada visita. Un campo de peso en el panel es un
   dato que envejece en cuanto alguien sustituye el PDF, y decir «3,7 MB» de
   uno que ahora pesa nueve es peor que no decir nada: quien lo abre con datos
   móviles decide por esa cifra.

   Devuelve cadena vacía si el archivo no está, y entonces la vista no pinta el
   botón. Ver la cabecera de este archivo. */
$peso = static function (string $relativa): string {
    $relativa = ltrim(trim($relativa), '/');

    if ($relativa === '' || str_contains($relativa, '..')) {
        return '';
    }

    $fisica = realpath(dirname(__DIR__) . '/' . $relativa);
    $raiz   = realpath(dirname(__DIR__) . '/assets');

    if ($fisica === false || $raiz === false || !str_starts_with($fisica, $raiz) || !is_file($fisica)) {
        return '';
    }

    $mb = filesize($fisica) / (1024 * 1024);

    return $mb >= 1
        ? number_format($mb, 1, ',', '.') . ' MB'
        : max(1, (int) round($mb * 1024)) . ' KB';
};

/* El nombre con el que se guarda en el ordenador de quien descarga sale del
   TÍTULO, no de la ruta: los archivos que suben desde el panel se llaman
   «doc-61d109b8.pdf» —el nombre lo pone el servidor, por seguridad— y con eso
   en la carpeta de descargas nadie sabe qué bajó. */
$nombreDescarga = static function (string $titulo, string $archivo): string {
    $ext  = strtolower((string) pathinfo($archivo, PATHINFO_EXTENSION)) ?: 'pdf';
    $base = mb_strtolower(trim($titulo));
    $base = strtr($base, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $base = preg_replace('~[^a-z0-9]+~', '-', $base) ?? '';
    $base = trim($base, '-');

    return ($base === '' ? 'documento' : mb_substr($base, 0, 80)) . '.' . $ext;
};

/* El titular de una pieza lleva el salto de línea del editable. Para el
   aria-label y para el nombre del archivo hace falta en una sola línea. */
$enUnaLinea = static fn (string $t): string => trim((string) preg_replace('/\s+/u', ' ', $t));

/* ── Las piezas ────────────────────────────────────────────────────────────
   Cada una es un bloque de la plantilla «Descargas»: portada de la biblioteca
   de imágenes y PDF por su ruta. Lo escrito aquí abajo es la RESERVA, con las
   mismas seis piezas del editable.

   La primera va DESTACADA —a lo ancho y con la portada al lado— porque no es
   un subsidio más: es el documento que presenta el conjunto, y su portada es
   apaisada mientras que las otras cinco son A4. Se marca con «destacada» en el
   panel y NO por su posición: reordenar las piezas no puede cambiar cuál
   manda. */
$piezas = $bloques('subsidios', [
    ['titulo' => "PARA LA VISITA DEL PAPA\nLEÓN XIV AL PERÚ",
     'texto'  => "El documento que presenta los cinco \nsubsidios y cómo usarlos en la parroquia, \nel colegio y la familia.",
     'img'    => 'p06', 'w' => 1198, 'h' => 674,
     'alt'    => 'Portada del documento «Subsidios para la visita del Papa León XIV al Perú»',
     'datos'  => ['archivo' => 'assets/docs/subsidios/subsidios-papa-leon-xiv.pdf', 'destacado' => 'sí']],

    ['titulo' => "PAPA LEÓN:\nCERCANO Y PERUANO",
     'texto'  => "Quién es León XIV y qué lo une\nal Perú, para conocerlo antes\nde recibirlo.",
     'img'    => 'p01', 'w' => 397, 'h' => 563,
     'alt'    => 'Portada del subsidio 1: Papa León, cercano y peruano',
     'datos'  => ['archivo' => 'assets/docs/subsidios/1-papa-leon-cercano-peruano.pdf']],

    ['titulo' => "UNIDOS EN CRISTO,\nSEMBRADORES DE PAZ",
     'texto'  => "El lema del Santo Padre\nllevado a la vida de la\ncomunidad.",
     'img'    => 'p02', 'w' => 399, 'h' => 565,
     'alt'    => 'Portada del subsidio 2: Unidos en Cristo, sembradores de paz',
     'datos'  => ['archivo' => 'assets/docs/subsidios/2-unidos-en-cristo.pdf']],

    ['titulo' => "FAMILIAS QUE\nCUIDAN LA VIDA",
     'texto'  => "Material para trabajar en\nfamilia durante las semanas\nprevias.",
     'img'    => 'p03', 'w' => 400, 'h' => 563,
     'alt'    => 'Portada del subsidio 3: Familias que cuidan la vida',
     'datos'  => ['archivo' => 'assets/docs/subsidios/3-familias-que-cuidan-la-vida.pdf']],

    ['titulo' => "LOS JÓVENES Y\nLA MISIÓN",
     'texto'  => "Para grupos juveniles, colegios\ny pastoral universitaria.",
     'img'    => 'p04', 'w' => 398, 'h' => 563,
     'alt'    => 'Portada del subsidio 4: Los jóvenes y la misión',
     'datos'  => ['archivo' => 'assets/docs/subsidios/4-los-jovenes-y-la-mision.pdf']],

    ['titulo' => "PASTORAL SOCIAL: LA\nDIGNIDAD DE TODA PERSONA",
     'texto'  => "La dimensión social de la fe,\ncon propuestas para la\ncomunidad.",
     'img'    => 'p05', 'w' => 396, 'h' => 561,
     'alt'    => 'Portada del subsidio 5: Pastoral social, la dignidad de toda persona',
     'datos'  => ['archivo' => 'assets/docs/subsidios/5-pastoral-social.pdf']],
]);

/* La portada de reserva de cada pieza va con la pieza, no con su posición: la
   lección de la portada es que apagar o reordenar un bloque no puede cambiarle
   la imagen a los demás. Cuando la pieza viene de la base sin «img», esto
   devuelve vacío y manda la fotografía elegida en el panel. */
$portada = static function (array $p) use ($sitio, $esc): string {
    if (empty($p['img'])) {
        return '';
    }

    $base = 'assets/img/rediseno/subsidios/' . $p['img'];

    return '<picture>'
         . '<source srcset="' . $esc($sitio->asset($base . '.webp')) . '" type="image/webp">'
         . '<img src="' . $esc($sitio->asset($base . '.jpg')) . '"'
         . ' alt="' . $esc((string) ($p['alt'] ?? '')) . '"'
         . ' width="' . (int) ($p['w'] ?? 400) . '" height="' . (int) ($p['h'] ?? 563) . '"'
         . ' loading="lazy" decoding="async"></picture>';
};

/* El editable pinta una pieza ancha arriba y cinco en rejilla debajo. */
$destacada = null;
$rejilla   = [];

foreach ($piezas as $p) {
    if ($destacada === null && trim((string) ($p['datos']['destacado'] ?? '')) !== '') {
        $destacada = $p;
        continue;
    }

    $rejilla[] = $p;
}
?>

<main id="contenido">

  <?php /* ═════════════════════════════════════════════════════════ HÉROE ══
       La fotografía de la presentación de los subsidios, con el duotono
       granate del editable. Se edita en Páginas → Subsidios → Cabecera.

       El rótulo del panel sólo se pinta si tiene algo escrito: el editable de
       esta página no lo dibuja, pero el campo existe en la plantilla y en
       otras páginas —Agenda— sí se usa. */ ?>
  <section class="hero hero--page hero--subsi">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/subsidios/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/subsidios/hero.jpg')) ?>"
             alt="Agentes pastorales y obispos de la Conferencia Episcopal Peruana saludan durante la presentación de los subsidios"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="container hero__inner">
      <?php $rotuloHero = $campo('cabecera', 'rotulo'); ?>
      <?php if ($rotuloHero !== ''): ?>
        <p class="hero__badge"><?= $esc($rotuloHero) ?></p>
      <?php endif; ?>
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Subsidios')) ?></h1>
      <p class="hero__sub"><?= $esc($campo('cabecera', 'texto', 'Lo que necesitas para preparar la visita en comunidad.')) ?></p>
    </div>
  </section>

  <?php /* ═════════════════════════════════════════════════════ CONTENIDO ══
       Las dos secciones del panel —«Subsidios descargables» y «Qué habrá
       disponible»— comparten la misma caja de texto: el editable las compone
       en una sola columna de 113 a 1237 px y los márgenes entre ellas son los
       suyos. Por eso van dentro del mismo .sub-wrap y no en dos <section>
       separadas con relleno propio. */ ?>
  <section class="subsi">
    <div class="sub-wrap">

      <?php if ($pintar('subsidios')): ?>
        <span class="sub-rule" aria-hidden="true"></span>

        <?php /* El titular de esta sección no se ve: el editable lo resuelve con
                 el filete dorado y el «Subsidios» del héroe. Se conserva —es el
                 que ya estaba en la base— como nombre accesible de la sección,
                 para quien navega con lector de pantalla saltando de encabezado
                 en encabezado. */ ?>
        <h2 class="visually-hidden"><?= $esc($campo('subsidios', 'titulo', 'Subsidios para la visita')) ?></h2>

        <?php $intro = $rico($campo('subsidios', 'texto', '')); ?>
        <?php if ($intro !== ''): ?>
          <div class="subsi__intro"><?= $intro ?></div>
        <?php else: ?>
          <?php /* Los saltos van con un espacio delante para que la separación se
                   mantenga cuando la hoja los anula por debajo de 1024 px. */ ?>
          <p class="subsi__intro">Cinco materiales elaborados por la Conferencia Episcopal <br>Peruana a ser difundidos para preparar la visita en la parroquia, <br>el colegio y la familia. <strong>Descarga libre y uso gratuito</strong>.</p>
        <?php endif; ?>

        <?php
        /* ── Una pieza ─────────────────────────────────────────────────────
           Portada, titular, texto y botón. Sin archivo en su sitio no hay
           botón: en su lugar queda la línea «En preparación», que es lo que
           esta página decía de todo antes de que la Conferencia Episcopal
           aprobara los cinco subsidios. */
        $pintarPieza = static function (array $p, bool $ancha)
            use ($esc, $sitio, $peso, $portada, $nombreDescarga, $enUnaLinea): void {

            $titulo  = (string) ($p['titulo'] ?? '');
            $llano   = $enUnaLinea($titulo);
            $archivo = trim((string) ($p['datos']['archivo'] ?? ''));
            $kilos   = $peso($archivo);
            $imagen  = $sitio->imagen($p, $portada($p), [
                'sizes' => $ancha
                    ? '(min-width:1024px) 48vw, 92vw'
                    : '(min-width:1024px) 25vw, (min-width:480px) 31vw, 88vw',
            ]);
            ?>
            <?php if ($ancha): ?>
              <?php if ($imagen !== ''): ?>
                <div class="destacada__media"><?= $imagen ?></div>
              <?php endif; ?>
              <div class="destacada__copy">
                <h3 class="destacada__h"><?= nl2br($esc($titulo)) ?></h3>
                <?php if (($p['texto'] ?? '') !== ''): ?>
                  <p class="destacada__p"><?= nl2br($esc(strip_tags((string) $p['texto']))) ?></p>
                <?php endif; ?>
            <?php else: ?>
              <?php if ($imagen !== ''): ?>
                <span class="subsi-card__img"><?= $imagen ?></span>
              <?php endif; ?>
              <h3 class="subsi-card__h"><?= nl2br($esc($titulo)) ?></h3>
              <?php if (($p['texto'] ?? '') !== ''): ?>
                <p class="subsi-card__p"><?= nl2br($esc(strip_tags((string) $p['texto']))) ?></p>
              <?php endif; ?>
            <?php endif; ?>

            <?php if ($kilos !== ''): ?>
              <a class="btn btn--descarga" href="<?= $esc($sitio->asset($archivo)) ?>"
                 download="<?= $esc($nombreDescarga($llano, $archivo)) ?>"
                 aria-label="Descargar «<?= $esc($llano) ?>» en PDF, <?= $esc($kilos) ?>">Descargar</a>
            <?php else: ?>
              <p class="descarga-pendiente">En preparación</p>
            <?php endif; ?>

            <?php if ($ancha): ?>
              </div><!-- /.destacada__copy -->
            <?php endif; ?>
            <?php
        };
        ?>

        <?php /* ─────────────────────────────────── PORTADA DESTACADA ── */ ?>
        <?php if ($destacada !== null): ?>
          <div class="destacada"><?php $pintarPieza($destacada, true); ?></div>
        <?php endif; ?>

        <?php /* ───────────────────────────────── LOS CINCO SUBSIDIOS ── */ ?>
        <?php if ($rejilla !== []): ?>
          <ol class="subsi-cards">
            <?php foreach ($rejilla as $p): ?>
              <li class="subsi-card"><?php $pintarPieza($p, false); ?></li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      <?php endif; ?>

      <?php /* ───────────────────────────── ¿QUÉ HABRÁ DISPONIBLE? ──
           Lo que la Conferencia Episcopal todavía prepara. Cada línea es un
           apartado del panel: el titular en dorado y el formato al lado. */ ?>
      <?php if ($pintar('habra-disponible')): ?>
        <h2 class="subsi__h2"><?= $esc($campo('habra-disponible', 'titulo', '¿Qué habrá disponible?')) ?></h2>

        <?php $textoHabra = $rico($campo('habra-disponible', 'texto', '')); ?>
        <?php if ($textoHabra !== ''): ?>
          <div class="subsi__texto"><?= $textoHabra ?></div>
        <?php else: ?>
          <p class="subsi__texto">Además de los subsidios ya publicados, esto es lo que la <br>Conferencia Episcopal prepara. Todo será de descarga libre <br>y de uso gratuito para parroquias, colegios y movimientos.</p>
        <?php endif; ?>

        <?php
        $apartados = $bloques('habra-disponible', [
            ['titulo' => 'Guía de oración',               'texto' => 'PDF · para las semanas previas'],
            ['titulo' => 'Subsidio de catequesis',        'texto' => 'PDF · Niños, jóvenes y adultos'],
            ['titulo' => 'Himno',                         'texto' => 'Video'],
            ['titulo' => 'Fondos de pantalla y avatares', 'texto' => 'Móvil, escritorio y redes'],
        ]);
        ?>
        <?php if ($apartados !== []): ?>
          <dl class="datalist subsi-tabla">
            <?php foreach ($apartados as $a): ?>
              <?php
              /* El destino del apartado, si lo tiene. Es lo que permite colgar
                 de «Guía de oración» el PDF que vive en Drive: se pega la
                 dirección en el panel y el titular se vuelve enlace. Vacío, el
                 apartado se pinta como siempre. */
              $destinoA = $sitio->enlaceDelPanel((string) ($a['enlace_url'] ?? ''));
              $rotuloA  = (string) ($a['titulo'] ?? '');
              ?>
              <div class="datalist__row">
                <dt class="datalist__key">
                  <?php if ($destinoA !== ''): ?>
                    <a href="<?= $esc($destinoA) ?>"<?= $sitio->esExterno($destinoA) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $esc($rotuloA) ?></a>
                  <?php else: ?>
                    <?= $esc($rotuloA) ?>
                  <?php endif; ?>
                </dt>
                <dd class="datalist__val"><?= $esc(strip_tags((string) ($a['texto'] ?? ''))) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </section>

</main>
