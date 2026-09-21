<?php
/**
 * ============================================================================
 *  Logo y lema — «Abramos el corazón» — rediseño 2026.
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
 *  Las medidas son las del editable «PÁG LOGO Y LEMA.ai» de septiembre de 2026
 *  (mesa de 1440 × 4732 px). La hoja assets/css/paginas/logo-y-lema.css las
 *  reproduce con la unidad --u.
 *
 *  ── Qué NO sale del panel, y por qué ─────────────────────────────────────
 *
 *  El collage de «Su diseño» y el logotipo recortado de «El lema» no son
 *  imágenes sueltas: son máscaras CSS sobre el archivo de marca
 *  —brand/logo-lockup-hero.png—, con el encuadre calibrado sobre ese dibujo
 *  concreto. Poner otro archivo detrás no daría otro collage, daría seis
 *  recortes sin sentido. Por eso viven en la hoja de estilos y no en la base.
 *  La única excepción es el emblema entero del collage, que tiene máscara
 *  propia porque en el archivo de marca el logotipo tipográfico le cae encima.
 *
 *  Lo editable aquí son los textos, la marca de la banda superior y las
 *  versiones del logotipo de la última sección, que sí son archivos enteros.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Logo y lema · «Abramos el corazón» · León XIV en el Perú',
    'descripcion' => 'El logotipo y el lema «Abramos el corazón» de la Visita Apostólica del Papa '
                   . 'León XIV al Perú: qué significa, cómo se diseñó, su color y sus versiones.',
    'ruta'        => 'logo-y-lema/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
];

$paginaCms = $sitio->contenido('logo-y-lema');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);

/* El lema se escribe una sola vez: rotula la banda de arriba, da nombre
   accesible al logotipo recortado de «El lema» y sirve de texto alternativo
   si alguien sube otra marca sin describirla. */
$lema = $campo('marca', 'titulo', 'Abramos el corazón');

/* El texto alternativo largo del archivo del editable. Sólo se usa mientras
   nadie elija imagen en el panel: en cuanto la hay, manda la descripción que
   lleve la foto en la biblioteca. */
$altMarca = 'Abramos el corazón — Papa León XIV, Visita Apostólica al Perú, '
          . '11–16 de noviembre de 2026';
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════ BANDA DORADA CON LA MARCA ════
       Esta página no lleva foto de héroe: la banda superior es dorada con
       degradado radial y la marca grande centrada, tal cual el editable.
       Se edita en Páginas → Logo y lema → Banda de la marca. */ ?>
  <section class="marca-banda" aria-label="<?= $esc($lema) ?>">
    <?php ob_start(); ?>
    <picture>
      <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/brand/logo-lockup-hero.webp')) ?>" type="image/webp">
      <img class="marca-banda__logo" src="<?= $esc($sitio->asset('assets/img/rediseno/brand/logo-lockup-hero.png')) ?>"
           alt="<?= $esc($altMarca) ?>" width="1600" height="1477" fetchpriority="high" decoding="async">
    </picture>
    <?php $respaldoBanda = (string) ob_get_clean(); ?>
    <?= $sitio->imagen(
        $secciones['marca'] ?? [],
        $respaldoBanda,
        ['clase' => 'marca-banda__logo', 'sizes' => '(min-width:1024px) 55vw, 88vw', 'prioridad' => true]
    ) ?>
  </section>

  <?php /* ═════════════════════════════════════════════════════════ CUERPO ══
       De aquí abajo, un lienzo de 1440 px con las piezas en las coordenadas
       del editable. Por debajo de 1024 px el lienzo se deshace y todo pasa a
       flujo normal; de eso se encarga la hoja de la página. */ ?>
  <div class="marca-cuerpo">
    <div class="marca-lienzo">

      <?php /* ───────────────────────────────────────── ¿QUÉ SIGNIFICA? ─── */ ?>
      <section class="marca-sig">
        <h1 class="marca-sig__h"><?= $esc($campo('que-significa', 'titulo', '¿Qué significa?')) ?></h1>
        <hr class="marca-regla marca-regla--1">
      </section>

      <?php /* ────────────────────────────────────────────────── EL LEMA ───
           El rótulo va en Freestyle Script (.font-script) y la flecha
           manuscrita es un SVG dibujado sobre el trazo del editable: no es
           una fuente ni una imagen, así que no hay nada que cargar. */ ?>
      <section class="marca-lema" aria-labelledby="t-lema">
        <p class="marca-lema__cab">
          <svg class="marca-lema__flecha" viewBox="0 0 107 101" width="107" height="101"
               aria-hidden="true" focusable="false">
            <g fill="none" stroke="#6E0B14" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M34 98C22 93 3 82 2.5 63 2 46 16 34 36 27c20-7 42-11 59-12"/>
              <path d="M82.7 2.6 104.2 17.6 75.8 29.1"/>
            </g>
          </svg>
          <span class="marca-lema__kicker font-script" id="t-lema"><?= $esc($campo('que-significa', 'rotulo', 'El lema')) ?></span>
        </p>

        <?php /* El logotipo tipográfico, recortado de la marca con una
                 máscara. Es una imagen a todos los efectos: lleva su nombre
                 accesible y ningún texto dentro que un buscador pueda leer
                 dos veces. */ ?>
        <p class="marca-lema__marca" role="img" aria-label="<?= $esc($lema) ?>"></p>

        <?php $textoLema = $campo('que-significa', 'texto', ''); ?>
        <?php if ($textoLema !== ''): ?>
          <div class="marca-lema__txt"><?= $textoLema ?></div>
        <?php else: ?>
          <p class="marca-lema__txt"><strong>Invita a abrirse a Dios para renovar la fe</strong>, al prójimo para <br>construir fraternidad y a la misión para anunciar con alegría <br>el Evangelio, fortalecer la unidad y fomentar la paz.</p>
        <?php endif; ?>

        <hr class="marca-regla marca-regla--2">
      </section>

      <?php /* ──────────────────────────────────────────────── SU DISEÑO ───
           El editable de septiembre de 2026 da la vuelta a esta sección: el
           titular y su entradilla suben al centro, el collage pasa a ocupar
           todo el ancho y debajo aparecen dos notas, una a cada lado, que lo
           señalan con una flecha manuscrita.

           Cinco de las seis piezas del collage siguen siendo recortes del
           archivo de marca hechos con máscara CSS —las mismas de siempre,
           bajadas 273 px—. La sexta, el emblema entero, no se puede sacar de
           ahí: en el archivo de marca el logotipo tipográfico va ENCIMA de los
           santos y cualquier máscara se lo llevaría puesto. Por eso lleva su
           propia máscara, «emblema-mascara.png», sacada del editable.

           El texto alternativo describe la pieza entera: por separado los
           recortes no significan nada. */ ?>
      <section class="marca-diseno" aria-labelledby="t-diseno">
        <h2 class="marca-diseno__h" id="t-diseno"><?= $esc($campo('su-diseno', 'titulo', 'Su diseño')) ?></h2>

        <?php $textoDiseno = $campo('su-diseno', 'texto', ''); ?>
        <?php if ($textoDiseno !== ''): ?>
          <div class="marca-diseno__txt"><?= $textoDiseno ?></div>
        <?php else: ?>
          <p class="marca-diseno__txt">El conjunto evoca <strong>una Iglesia guiada por el Espíritu Santo</strong> <br>y enriquecida por el testimonio de sus santos.</p>
        <?php endif; ?>

        <div class="marca-collage" role="img"
             aria-label="Detalles del logotipo: el Papa León XIV, Santo Toribio de Mogrovejo y San Martín de Porres, la Virgen, San Francisco Solano y San Juan Macías, la paloma del Espíritu Santo y el emblema completo.">
          <span class="marca-tile marca-tile--6"></span>
          <span class="marca-tile marca-tile--1"></span>
          <span class="marca-tile marca-tile--3"></span>
          <span class="marca-tile marca-tile--2"></span>
          <span class="marca-tile marca-tile--4"></span>
          <span class="marca-tile marca-tile--5"></span>
        </div>

        <?php /* Las dos notas. Son bloques del panel: se pueden quitar, dejar
                 una sola o cambiarlas de orden. La flecha es un SVG dibujado
                 sobre el trazo del editable, no una fuente ni una imagen. */ ?>
        <?php
        $notas = $bloques('su-diseno', [
            ['texto' => 'En el centro figura León XIV, rodeado por santa Rosa de Lima, santo Toribio '
                      . 'de Mogrovejo, san Martín de Porres, san Francisco Solano y san Juan Macías, '
                      . 'principales santos de la tradición peruana.'],
            ['texto' => 'La paloma del Espíritu Santo, colocada sobre el territorio nacional, recuerda '
                      . 'la presencia de Dios que guía a la Iglesia, inspira la reconciliación y '
                      . 'renueva la esperanza del pueblo peruano.'],
        ]);
        ?>
        <ul class="marca-notas">
          <?php foreach (array_slice($notas, 0, 2) as $i => $nota): ?>
            <?php $textoNota = trim((string) ($nota['texto'] ?? '')); ?>
            <?php if ($textoNota === '') { continue; } ?>
            <li class="marca-notas__i marca-notas__i--<?= $i + 1 ?>">
              <?php if ($i === 0): ?>
                <svg class="marca-notas__flecha" viewBox="0 0 52.5 158" aria-hidden="true" focusable="false">
                  <g fill="none" stroke="#6E0B14" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M50.5 2C28 16 5 44 2.5 78.5 .5 108 20 132 40 143"/>
                    <path d="M30.6 128.6 51 143.6 28.7 156"/>
                  </g>
                </svg>
              <?php else: ?>
                <svg class="marca-notas__flecha" viewBox="0 0 63 116" aria-hidden="true" focusable="false">
                  <g fill="none" stroke="#6E0B14" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2c22 11 44 33 47.5 58.5C63.5 83 47 101 28 110"/>
                    <path d="M35.4 95.9 25.6 111.8 45 116.5"/>
                  </g>
                </svg>
              <?php endif; ?>
              <p><?= nl2br($esc($textoNota)) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>

        <hr class="marca-regla marca-regla--3">
      </section>

      <?php /* ─────────────────────────────────── SU COLOR Y SUS VERSIONES ──
           El editable nuevo las pone en FILAS: cada versión con su logotipo
           centrado y su explicación debajo, una tras otra. Antes iban las tres
           en hilera compartiendo un único texto de cierre.

           Siguen siendo bloques del panel —cada una con su archivo, su texto
           alternativo y ahora su explicación—, así que añadir una cuarta o
           quitar una recompone la lista sin tocar código. */ ?>
      <?php
      $versiones = $bloques('su-color', [
          ['titulo' => 'Versión principal del logotipo, en rojo', 'img' => 'logo-lockup-rojo-alt', 'w' => 1552,
           'texto' => '**El rojo** representa la identidad y el espíritu del Perú.'],
          ['titulo' => 'Versión del logotipo en dorado',          'img' => 'logo-lockup-dorado',   'w' => 1552,
           'texto' => '**El dorado** transmite la dimensión espiritual, solemne y luminosa de la visita.'],
          ['titulo' => 'Versión del logotipo en negro',           'img' => 'logo-lockup-negro',    'w' => 1556,
           'texto' => '**El gris** garantiza versatilidad y legibilidad en aplicaciones donde se requiere una reproducción más sobria.'],
      ]);

      /* El campo de la ficha es de texto llano, no de HTML: para que la primera
         palabra pueda ir en negrita —«El rojo», «El dorado», «El gris»— se
         acepta el subrayado de los dos asteriscos y se convierte aquí, después
         de escapar. Cualquier otra cosa que se escriba sale tal cual. */
      $negritas = static function (string $t) use ($esc): string {
          return preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', nl2br($esc($t))) ?? $esc($t);
      };
      ?>
      <section class="marca-color" aria-labelledby="t-color">
        <?php /* El titular va en dos renglones en el editable. Del panel llega
                 una sola cadena: el salto de línea que se escriba en el campo
                 es el que se respeta aquí. */ ?>
        <h2 class="marca-color__h" id="t-color"><?= nl2br($esc($campo('su-color', 'titulo', "Su color\ny sus versiones"))) ?></h2>

        <ul class="marca-versiones">
          <?php foreach ($versiones as $i => $version): ?>
            <li class="marca-versiones__i marca-versiones__i--<?= $i + 1 ?>">
              <?php
              ob_start();
              if (!empty($version['img'])): ?>
                <picture>
                  <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/brand/' . $version['img'] . '.webp')) ?>" type="image/webp">
                  <img class="marca-version" src="<?= $esc($sitio->asset('assets/img/rediseno/brand/' . $version['img'] . '.png')) ?>"
                       alt="<?= $esc((string) ($version['titulo'] ?? '')) ?>"
                       width="<?= (int) ($version['w'] ?? 1552) ?>" height="1268" loading="lazy" decoding="async">
                </picture>
              <?php endif;
              $respaldoVersion = (string) ob_get_clean(); ?>
              <?= $sitio->imagen(
                  $version,
                  $respaldoVersion,
                  ['clase' => 'marca-version', 'sizes' => '(min-width:1024px) 27vw, (min-width:768px) 40vw, 78vw']
              ) ?>

              <?php $textoVersion = trim((string) ($version['texto'] ?? '')); ?>
              <?php if ($textoVersion !== ''): ?>
                <p class="marca-versiones__txt"><?= $negritas($textoVersion) ?></p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php /* El editable nuevo ya no cierra con un párrafo común: cada
                 versión lleva el suyo. Si alguien escribe uno en el panel se
                 sigue pintando aquí en lugar de perderse. */ ?>
        <?php $textoColor = $campo('su-color', 'texto', ''); ?>
        <?php if ($textoColor !== ''): ?>
          <div class="marca-color__txt"><?= $textoColor ?></div>
        <?php endif; ?>
      </section>

    </div>
  </div>

</main>
