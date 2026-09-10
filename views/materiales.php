<?php
/**
 * Vista de la página «materiales».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Materiales de pastoral · Viaje de León XIV al Perú',
    'descripcion' => 'Subsidios de oración, catequesis y animación para preparar la visita del Papa León XIV en tu parroquia, tu colegio o tu casa.',
    'ruta'        => 'materiales/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre"',
];
?>
<?php
/* El contenido de esta página sale de la base y se edita desde el panel.
   Cada lectura lleva su texto de reserva: si la base no responde, o si
   alguien vacía un campo, la página se pinta con lo que decía antes.
   Una web sobre un viaje papal no puede quedarse muda porque falle MySQL. */
$paginaCms = $sitio->contenido('materiales');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$rico = static fn (?string $v): string
    => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

/* ── El peso de un archivo, medido y no escrito ────────────────────────────
   Se lee del archivo real en cada visita. Un campo de peso en el panel es un
   dato que envejece en cuanto alguien sustituye el PDF, y decir «3,7 MB» de
   uno que ahora pesa nueve es peor que no decir nada: quien lo abre con datos
   móviles decide por esa cifra.

   Devuelve cadena vacía si el archivo no está, y entonces la vista no pinta el
   botón. Es deliberado: un botón de descarga que lleva a un 404 es peor que la
   ausencia del botón, sobre todo en una página cuyo trabajo es repartir
   documentos. */
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
?>

<main id="contenido">

<header class="cabecera-pagina">
  <div class="cabecera-pagina__media">
    <?php /* ── La portada de esta página ──────────────────────────────
         Sale del panel: Páginas → esta página → Cabecera. Se puede
         elegir una foto para escritorio y otra para móvil.

         Lo que va aquí abajo es el RESPALDO: la fotografía que la
         página traía escrita a mano. Mientras nadie elija otra en el
         panel se sigue viendo ésta, así que pasar la portada al
         gestor no cambió el aspecto de nada el día del despliegue. */ ?>
      <?php ob_start(); ?>
      <picture>
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-materiales-640.webp 640w, ../assets/img/fotos/cab-materiales-1024.webp 1024w, ../assets/img/fotos/cab-materiales-1600.webp 1600w, ../assets/img/fotos/cab-materiales-1900.webp 1900w">
      <img src="../assets/img/fotos/cab-materiales-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-materiales-640.jpg 640w, ../assets/img/fotos/cab-materiales-1024.jpg 1024w, ../assets/img/fotos/cab-materiales-1600.jpg 1600w, ../assets/img/fotos/cab-materiales-1900.jpg 1900w" width="1900" height="814" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Para parroquias y colegios')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Materiales de pastoral')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Lo que necesitas para preparar la visita en comunidad. Se irá publicando conforme la Conferencia Episcopal lo apruebe.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Materiales</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<?php
/* ══════════════════════════════════════════════════════════════════════════
   LOS SUBSIDIOS · lo que ya se puede descargar

   Es el punto 07.a de la solicitud del cliente. Hasta hoy esta página decía
   que no había nada publicado; ahora abre con las seis piezas aprobadas.

   Cada una es un bloque del panel: portada de la biblioteca de imágenes y PDF
   por su ruta. Lo que va escrito aquí abajo es la RESERVA, con las mismas seis
   piezas, para que la página no se quede muda si la base no responde.

   La primera va DESTACADA —a lo ancho y con la portada al lado— porque no es
   un subsidio más: es el documento que presenta el conjunto, y su portada es
   apaisada mientras que las otras cinco son A4. Meterla en la misma rejilla
   dejaba una tarjeta con una forma distinta a las demás sin explicar por qué.
   Se marca con «destacada» en el panel, no por su posición: reordenar las
   piezas no puede cambiar cuál manda.
   ══════════════════════════════════════════════════════════════════════════ */
$piezas = $bloques('subsidios', [
    ['titulo' => 'Subsidios para la visita del Papa León XIV al Perú',
     'texto'  => 'El documento que presenta los cinco subsidios y cómo usarlos en la parroquia, el colegio y la familia.',
     'datos'  => ['archivo' => 'assets/docs/subsidios/subsidios-papa-leon-xiv.pdf', 'destacado' => 'sí']],
    ['titulo' => 'Papa León: cercano y peruano',
     'texto'  => 'Quién es León XIV y qué lo une al Perú, para conocerlo antes de recibirlo.',
     'datos'  => ['archivo' => 'assets/docs/subsidios/1-papa-leon-cercano-peruano.pdf']],
    ['titulo' => 'Unidos en Cristo, sembradores de paz',
     'texto'  => 'El lema del Santo Padre llevado a la vida de la comunidad.',
     'datos'  => ['archivo' => 'assets/docs/subsidios/2-unidos-en-cristo.pdf']],
    ['titulo' => 'Familias que cuidan la vida',
     'texto'  => 'Material para trabajar en familia durante las semanas previas.',
     'datos'  => ['archivo' => 'assets/docs/subsidios/3-familias-que-cuidan-la-vida.pdf']],
    ['titulo' => 'Los jóvenes y la misión',
     'texto'  => 'Para grupos juveniles, colegios y pastoral universitaria.',
     'datos'  => ['archivo' => 'assets/docs/subsidios/4-los-jovenes-y-la-mision.pdf']],
    ['titulo' => 'Pastoral social: la dignidad de toda persona',
     'texto'  => 'La dimensión social de la fe, con propuestas para la comunidad.',
     'datos'  => ['archivo' => 'assets/docs/subsidios/5-pastoral-social.pdf']],
]);

/* La portada de reserva de cada pieza se empareja por el NOMBRE DEL ARCHIVO y
   no por la posición, que es la lección de la portada: apagar o reordenar una
   pieza no puede cambiarle la imagen a las demás. */
$portada = static function (array $p) use ($sitio, $esc): string {
    $ruta = (string) ($p['datos']['archivo'] ?? '');
    $base = pathinfo($ruta, PATHINFO_FILENAME);

    if ($base === '' || !is_file(dirname(__DIR__) . "/assets/img/subsidios/{$base}-420.jpg")) {
        return '';
    }

    $w = $j = [];
    foreach ([420, 840] as $ancho) {
        $w[] = $esc($sitio->asset("assets/img/subsidios/{$base}-{$ancho}.webp")) . " {$ancho}w";
        $j[] = $esc($sitio->asset("assets/img/subsidios/{$base}-{$ancho}.jpg"))  . " {$ancho}w";
    }

    return '<picture>'
         . '<source type="image/webp" sizes="(min-width:1024px) 22vw, (min-width:600px) 40vw, 80vw" srcset="' . implode(', ', $w) . '">'
         . '<img src="' . $esc($sitio->asset("assets/img/subsidios/{$base}-420.jpg")) . '"'
         . ' sizes="(min-width:1024px) 22vw, (min-width:600px) 40vw, 80vw" srcset="' . implode(', ', $j) . '"'
         . ' alt="" loading="lazy" decoding="async"></picture>';
};

/* El nombre del archivo descargado, a partir del título de la pieza. */
$nombreDescarga = static function (string $titulo, string $archivo): string {
    $ext  = strtolower((string) pathinfo($archivo, PATHINFO_EXTENSION)) ?: 'pdf';
    $base = mb_strtolower(trim($titulo));
    $base = strtr($base, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $base = preg_replace('~[^a-z0-9]+~', '-', $base) ?? '';
    $base = trim($base, '-');

    return ($base === '' ? 'documento' : mb_substr($base, 0, 80)) . '.' . $ext;
};

$destacada = null;
$rejilla   = [];
foreach ($piezas as $p) {
    $esDestacada = mb_strtolower(trim((string) ($p['datos']['destacado'] ?? ''))) !== '';
    if ($esDestacada && $destacada === null) { $destacada = $p; continue; }
    $rejilla[] = $p;
}
?>
<?php if ($piezas !== []): ?>
<section class="seccion subsidios" id="subsidios" aria-labelledby="t-subsidios">
  <div class="contenedor">

    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('subsidios', 'rotulo', 'Ya disponibles')) ?></span>
      <h2 class="titular--mayor" id="t-subsidios" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('subsidios', 'titulo', 'Subsidios para la visita')) ?></span></span>
      </h2>
      <div class="texto-lectura">
        <?= $rico($campo('subsidios', 'texto',
            '<p>Cinco materiales aprobados por la Conferencia Episcopal Peruana para preparar la visita en la parroquia, el colegio y la familia. Descarga libre y uso gratuito.</p>')) ?>
      </div>
    </header>

    <?php
    /* Una pieza: portada, título, texto y botón. El botón sólo existe si el
       archivo existe de verdad —$peso() devuelve vacío si no—, porque un
       enlace de descarga que da 404 en la página que reparte los documentos es
       peor que no ponerlo. */
    $pintarPieza = static function (array $p, bool $ancha) use ($esc, $sitio, $peso, $portada, $nombreDescarga): void {
        $archivo = trim((string) ($p['datos']['archivo'] ?? ''));
        $kilos   = $peso($archivo);
        $img     = $sitio->imagen($p, $portada($p), [
            'sizes' => $ancha ? '(min-width:900px) 30vw, 80vw' : '(min-width:1024px) 22vw, (min-width:600px) 40vw, 80vw',
        ]);
        ?>
        <article class="subsidio<?= $ancha ? ' subsidio--ancha' : '' ?>" data-reveal="fade-rise">
          <?php if ($img !== ''): ?>
            <div class="subsidio__portada"><?= $img ?></div>
          <?php endif; ?>
          <div class="subsidio__cuerpo">
            <h3 class="subsidio__titulo"><?= $esc($p['titulo'] ?? '') ?></h3>
            <?php if (($p['texto'] ?? '') !== ''): ?>
              <p class="subsidio__texto"><?= $esc(strip_tags((string) $p['texto'])) ?></p>
            <?php endif; ?>
            <?php if ($kilos !== ''): ?>
              <p class="subsidio__pie">
                <?php /* El nombre con el que se guarda en el ordenador de quien
                         descarga sale del TÍTULO, no de la ruta. Los archivos que
                         suben desde el panel se llaman «doc-61d109b8.pdf» —el
                         nombre lo pone el servidor, por seguridad—, y con eso en
                         la carpeta de descargas nadie sabe qué bajó. */ ?>
                <a class="btn btn--primario" href="<?= $esc($sitio->asset($archivo)) ?>"
                   download="<?= $esc($nombreDescarga($p['titulo'] ?? '', $archivo)) ?>"
                   aria-label="Descargar «<?= $esc($p['titulo'] ?? '') ?>» en PDF, <?= $esc($kilos) ?>">
                  <svg aria-hidden="true"><use href="#i-descarga"/></svg>
                  Descargar PDF
                </a>
                <span class="subsidio__peso">PDF · <?= $esc($kilos) ?></span>
              </p>
            <?php else: ?>
              <p class="estado">En preparación</p>
            <?php endif; ?>
          </div>
        </article>
        <?php
    };
    ?>

    <?php if ($destacada !== null): ?>
      <?php $pintarPieza($destacada, true); ?>
    <?php endif; ?>

    <?php if ($rejilla !== []): ?>
      <div class="subsidios-rejilla">
        <?php foreach ($rejilla as $p): ?><?php $pintarPieza($p, false); ?><?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>

<section class="seccion seccion--tinte" aria-labelledby="t-mat">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('habra-disponible', 'rotulo', 'En preparación')) ?></span>
      <h2 class="titular--mayor" id="t-mat" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('habra-disponible', 'titulo', 'Qué habrá disponible')) ?></span></span></h2>
      <?php /* Decía «nada de esto está publicado todavía», y con los subsidios
               arriba había dejado de ser cierto. La regla nº3 del encargo —lo
               que no es oficial se dice que no lo es— vale también al revés: lo
               que ya está publicado no puede seguir anunciándose como pendiente
               dos secciones más abajo. */ ?>
      <p>Además de los subsidios ya publicados, esto es lo que la Conferencia Episcopal
         prepara. Todo será de descarga libre y de uso gratuito para parroquias,
         colegios y movimientos.</p>
    </header>
    <ul class="dias">
      <li class="dia" data-reveal="fade-rise"><h3 class="dia__sede">Guía de oración</h3><p class="dia__ventana">PDF · para las semanas previas</p><p class="estado">En preparación</p></li>
      <li class="dia" data-reveal="fade-rise"><h3 class="dia__sede">Subsidio de catequesis</h3><p class="dia__ventana">PDF · niños, jóvenes y adultos</p><p class="estado">En preparación</p></li>
      <li class="dia" data-reveal="fade-rise"><h3 class="dia__sede">Cantoral</h3><p class="dia__ventana">PDF y audio</p><p class="estado">En preparación</p></li>
      <li class="dia" data-reveal="fade-rise"><h3 class="dia__sede">Banners para parroquias</h3><p class="dia__ventana">Impresión y pantalla</p><p class="estado">En preparación</p></li>
      <li class="dia" data-reveal="fade-rise"><h3 class="dia__sede">Fondos de pantalla y avatares</h3><p class="dia__ventana">Móvil, escritorio y redes</p><p class="estado">En preparación</p></li>
    </ul>
    <p class="nota sep-l">Los archivos se publicarán en esta misma página, con su formato y su peso indicados en cada enlace.</p>
  </div>
</section>

<section class="seccion seccion--tinte seccion--pastel" id="comparte" aria-labelledby="t-comparte">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('corre-voz', 'rotulo', 'Comparte')) ?></span>
      <h2 class="titular--mayor" id="t-comparte" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('corre-voz', 'titulo', 'Corre la voz')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>La mejor difusión de esta visita no la va a hacer una campaña: la van a hacer las parroquias, los colegios y los movimientos contándolo a la gente que tienen al lado.</p>
        <p>Cuando estén listos, aquí encontrarás un kit sencillo —piezas para redes, un cartel imprimible y un texto breve para leer en las misas dominicales— pensado para que cualquier comunidad pueda usarlo sin saber de diseño.</p>
        <p>La etiqueta oficial del viaje se anunciará junto con los materiales. Hasta entonces, lo más útil es enlazar directamente a este sitio.</p>
      </div>
      </div>
      <div class="col-m-4 col-t-2 col-d-4">
        <div class="aviso sep-l"><p class="aviso__titulo">Uso de la marca y del escudo</p><p>El escudo pontificio no se recorta, no se deforma y no se usa como adorno repetido. Las condiciones de uso se publicarán con los materiales.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="seccion" aria-labelledby="t-aviso-mat">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('avisame-cuando-esten', 'rotulo', 'Aviso')) ?></span>
      <h2 id="t-aviso-mat" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('avisame-cuando-esten', 'titulo', 'Avísame cuando estén publicados')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Te escribimos una sola vez, el día que los materiales estén disponibles.</p>
    </header>
        <form class="pila" data-form="aviso" data-origen="materiales" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="correo-materiales">Correo electrónico</label>
            <input type="email" id="correo-materiales" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="consent-materiales" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="web-materiales">No rellenar</label><input type="text" id="web-materiales" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div><button class="btn btn--secundario" type="submit">Avísame</button></div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
  </div></div></div>
</section>
</main>
