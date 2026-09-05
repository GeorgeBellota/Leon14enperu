<?php
/**
 * Vista de la página «home».
 *
 * ── De dónde sale lo que se ve ───────────────────────────────────────────
 *
 * De la base, sección a sección, y se edita desde el panel. Cada lectura
 * lleva su texto de reserva: si la base no responde, o si alguien vacía un
 * campo por error, la página se pinta con lo que decía antes. Una web sobre
 * un viaje papal no puede quedarse muda porque falle MySQL.
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

use Intranet\Core\HtmlSeguro;
use Intranet\Publico\Sitio;

$meta = [
    'titulo'      => 'León XIV en el Perú · Viaje apostólico, 11–16 de noviembre de 2026',
    'descripcion' => 'El Papa León XIV vuelve al Perú del 11 al 16 de noviembre de 2026. Lima, Chiclayo, Cusco y Pucallpa. Agenda, sedes y voluntariado del viaje apostólico.',
    'og_titulo'      => 'León XIV en el Perú · Viaje apostólico 2026',
    'og_descripcion' => 'Del 11 al 16 de noviembre de 2026, en Lima, Chiclayo, Cusco y Pucallpa.',
    'ruta'        => '',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'website',
    'scripts'     => ['assets/vendor/swiper-bundle.min.js', 'assets/js/hero.js', 'assets/js/hitos.js', 'assets/js/cta-modal.js', 'assets/js/colecta.js'],
    'css'         => ['assets/vendor/swiper-bundle.min.css'],
    'head_extra'  => '<script type="application/ld+json" nonce="' . $esc($sitio->nonce()) . '">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://leon14enperu.com/#sitio",
      "url": "https://leon14enperu.com/",
      "name": "León XIV en el Perú",
      "inLanguage": "es",
      "description": "Sitio informativo del viaje apostólico del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026."
    },
    {
      "@type": "Organization",
      "@id": "https://leon14enperu.com/#organizacion",
      "name": "Conferencia Episcopal Peruana",
      "url": "https://leon14enperu.com/",
      "logo": "https://leon14enperu.com/assets/img/escudo-leon-xiv.svg",
      "areaServed": "PE"
    }
  ]
}
</script>',
];
?>
<?php
$paginaCms = $sitio->contenido('home');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string => Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array              => Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool                              => Sitio::activa($secciones, $s);

/* El HTML con formato del panel se filtra: editar textos no puede dar el
   poder de ejecutar código en el navegador de un visitante. */
$rico = static fn (?string $v): string => HtmlSeguro::limpiar((string) ($v ?? ''));

/* Los destinos del panel se escriben relativos —«sedes/lima/»— y aquí se
   convierten en enlaces del sitio. Lo que ya es una dirección completa, un
   correo o un ancla se deja intacto: si alguien enlaza a la web de la CEP, no
   se le puede colgar el dominio propio por delante. */
$destino = static function (mixed $u) use ($sitio): string {
    $u = trim((string) $u);
    if ($u === '') { return ''; }

    return preg_match('~^(https?:)?//|^(mailto|tel):|^#~i', $u) === 1
        ? $u
        : $sitio->enlace(ltrim($u, '/'));
};

/* Un <picture> escrito a mano, para cuando la pieza todavía no tiene imagen
   elegida en la biblioteca. En cuanto alguien elija una desde el panel, ésta
   deja de usarse: es el respaldo, no el contenido. */
$foto = static function (string $base, int $ancho, int $alto, string $alt, array $anchos, string $sizes, bool $primera = false) use ($esc): string {
    $webp = $jpg = [];
    foreach ($anchos as $w) {
        $webp[] = "assets/img/{$base}-{$w}.webp {$w}w";
        $jpg[]  = "assets/img/{$base}-{$w}.jpg {$w}w";
    }
    $medio = $anchos[min(1, count($anchos) - 1)];

    return '<picture>'
         . '<source type="image/webp" sizes="' . $esc($sizes) . '" srcset="' . $esc(implode(', ', $webp)) . '">'
         . '<img src="' . $esc("assets/img/{$base}-{$medio}.jpg") . '"'
         . ' sizes="' . $esc($sizes) . '" srcset="' . $esc(implode(', ', $jpg)) . '"'
         . ' width="' . $ancho . '" height="' . $alto . '"'
         . ' alt="' . $esc($alt) . '"'
         . ($primera ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async"></picture>';
};
?>

<main id="contenido">

<!-- ══════════════════════════════════════════════════════════════════════
     1. CARRUSEL DE PORTADA
     Las láminas salen del gestor (sección «Carrusel de portada»). Si la base
     no responde se pinta la lista de reserva, que es lo que la portada decía
     antes de ser administrable.
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$fotosHero = [
    $foto('pontifice/retrato-oficial', 1131, 1600, 'Su Santidad el Papa León XIV', [640, 1024, 1131], '(min-width:900px) 42vw, 100vw', true),
    $foto('fotos/hero-multitud', 1600, 851, 'Vista aérea de una explanada llena de fieles durante una celebración papal', [640, 1024, 1600], '(min-width:900px) 42vw, 100vw'),
    $foto('ciudades/lima-g', 720, 540, 'Catedral de Lima iluminada en la plaza Mayor', [480, 720], '(min-width:900px) 42vw, 100vw'),
    /* ── Cuarta y quinta lámina: por qué esta fotografía y no la otra ───────
       La cuarta es la de la Colecta y va A SANGRE, con el bloque de texto
       encima. Eso obliga a elegir una fotografía donde el Santo Padre NO esté
       en la mitad izquierda, porque ahí es donde se apoya el bloque y un panel
       opaco sobre su cara es tan inaceptable como un velo.

       Se probó con «hito-anuncio», que es la del cartel de la Conferencia
       Episcopal: el Santo Padre está centrado y la imagen es 4:3, así que al
       recortarla a la franja del hero se recorta por arriba y por abajo, nunca
       por los lados. La cara se quedaba justo debajo del bloque. Con
       «papamovil» está a la derecha, que además es la composición del propio
       cartel: texto a la izquierda, Santo Padre a la derecha.

       Y «hito-anuncio» se va a la quinta, que es partida: allí la imagen tiene
       su columna entera y nada se le pone encima. */
    $foto('fotos/hero-papamovil', 1080, 720, 'El Santo Padre saluda desde el papamóvil a los fieles congregados', [640, 1024, 1080], '100vw'),
    $foto('hitos/hito-anuncio', 1440, 1080, 'El Santo Padre saluda con los brazos en alto desde la logia', [640, 960, 1440], '(min-width:900px) 42vw, 100vw'),
];

$laminas = $bloques('hero', [
    ['rotulo' => 'Viaje apostólico', 'titulo' => 'Abramos el corazón',
     'texto'  => 'Visita Apostólica del Papa León XIV al Perú.',
     'enlace_texto' => 'Conoce la visita', 'enlace_url' => 'el-papa/'],
    /* A sangre: la fotografía es una toma aérea de una explanada llena de
       fieles, sin ningún rostro en primer plano que el velo pudiera invadir. */
    ['rotulo' => 'Los amigos de León', 'titulo' => 'Sirve en la visita',
     'texto'  => 'Hay un lugar para cada talento y cada corazón.',
     'enlace_texto' => 'Quiero ser voluntario', 'enlace_url' => 'voluntariado/',
     'datos'  => ['diseno' => 'fondo']],
    ['rotulo' => 'Cuatro ciudades', 'titulo' => 'Lima · Chiclayo · Cusco · Pucallpa',
     'texto'  => 'El recorrido del Santo Padre por la costa, la sierra y la selva.',
     'enlace_texto' => 'Ver las sedes', 'enlace_url' => 'sedes/'],
    /* La Colecta Nacional. Los textos son los del cartel de la Conferencia
       Episcopal, palabra por palabra; aquí no se ha redactado nada.

       Los números de cuenta NO van en la lámina: no se leen de un vistazo en un
       carrusel que pasa cada siete segundos, y un dígito mal copiado en una
       cuenta bancaria es un error caro. Viven en /donativo/, que es donde
       alguien los puede leer con calma, copiar y comprobar. */
    ['rotulo' => 'Colecta Nacional', 'titulo' => 'Súmate con tu donación',
     'texto'  => 'Con tu aporte ayudamos a preparar este gran encuentro de fe, unidad y esperanza.',
     'enlace_texto' => 'Cómo donar', 'enlace_url' => '#colecta',
     'datos'  => ['diseno' => 'fondo']],
    /* Quinta lámina, a sangre. Los textos son los de la sección «Tierra de
       santos» que ya está publicada, y la frase de la bajada es la del
       documento de la Conferencia Episcopal. Nada redactado aquí. */
    ['rotulo' => 'Cinco caminos de santidad', 'titulo' => 'Cinco santos, un mismo corazón',
     'texto'  => 'Cinco santos nos muestran distintos caminos para vivir la fe y servir a los demás.',
     'enlace_texto' => 'Conoce sus historias', 'enlace_url' => 'tierra-de-santos/'],
]);

/* ── El orden de los diseños ──────────────────────────────────────────────
   Las láminas alternan: partida, a sangre, partida, a sangre, partida. Es lo
   que da variedad al carrusel sin que dos composiciones iguales se sigan.

   La primera es SIEMPRE partida, y eso no es alternancia sino regla: lleva el
   retrato oficial del Santo Padre, y sobre ese retrato no va ningún velo ni
   degradado. Por eso su texto vive en el panel de color de al lado.

   Lo que hay aquí es la reserva. El orden real lo manda el panel, lámina a
   lámina, en el campo «Diseño de la lámina». */

$laminas = array_values($laminas);
$total   = count($laminas);
?>
<section class="hero" id="inicio" data-hero aria-roledescription="carrusel" aria-label="Presentación del viaje apostólico">
  <div class="swiper">
    <div class="swiper-wrapper">
      <?php foreach ($laminas as $i => $l): ?>
        <?php
        $titular = (string) ($l['titulo'] ?? '');
        $enlaceT = (string) ($l['enlace_texto'] ?? '');
        $enlaceU = $destino($l['enlace_url'] ?? '');

        /* ── El diseño de cada lámina se elige en el panel ─────────────────
           Dos composiciones, y la decide quien edita, lámina a lámina, desde
           «Diseño de la lámina» (viaja en la columna JSON `datos`, así que no
           hizo falta ni una columna nueva):

             · vacío   → composición PARTIDA: el texto en el panel de color y la
                         imagen en su propia columna al lado. Es la de siempre,
                         y la única que admite el retrato del Santo Padre, que
                         no puede llevar velo ni degradado encima.
             · «fondo» → fotografía A SANGRE con el texto sobre ella, dentro de
                         un bloque opaco. Para fotos de multitud o de ambiente,
                         donde no hay un rostro que proteger en primer plano.

           Se normaliza a minúsculas y sin espacios: cualquier otra cosa cae en
           la partida. Una errata en el panel no rompe la portada. */
        $aSangre = mb_strtolower(trim((string) ($l['datos']['diseno'] ?? ''))) === 'fondo';

        /* El contenido es EL MISMO en los dos diseños. Va en un cierre para no
           tenerlo escrito dos veces: dos copias del mismo bloque acaban
           descuadrándose en cuanto alguien toca una y olvida la otra. */
        $pintarLamina = static function () use ($l, $i, $titular, $enlaceT, $enlaceU, $esc, $sitio): void {
            ?>
            <?php if (($l['rotulo'] ?? '') !== ''): ?>
              <span class="rotulo rotulo--claro"><?= $esc($l['rotulo']) ?></span>
            <?php endif; ?>

            <?php /* Sólo la primera lámina es el <h1>. Un documento con cinco
                     encabezados de nivel 1 no tiene título: tiene cinco, y
                     ninguno manda. */ ?>
            <?php if ($i === 0): ?>
              <h1 class="hero__titulo"><?= $esc($titular) ?></h1>
            <?php else: ?>
              <p class="hero__titulo" role="heading" aria-level="2"><?= $esc($titular) ?></p>
            <?php endif; ?>

            <?php if (($l['texto'] ?? '') !== ''): ?>
              <p class="hero__bajada"><?= $esc(strip_tags((string) $l['texto'])) ?></p>
            <?php endif; ?>

            <p class="hero__dato">
              <span>11–16 noviembre 2026</span><span>Lima</span><span>Chiclayo</span><span>Cusco</span><span>Pucallpa</span>
            </p>

            <?php /* El segundo botón lleva SIEMPRE al voluntariado: es la única
                     acción abierta hoy y no puede depender de qué lámina esté a
                     la vista. Salvo cuando la lámina ya va allí —entonces
                     saldrían dos veces el mismo botón—. */ ?>
            <?php $voluntariado = $sitio->enlace('voluntariado/'); ?>
            <div class="hero__acciones">
              <?php if ($enlaceT !== '' && $enlaceU !== ''): ?>
                <a class="btn btn--claro" href="<?= $esc($enlaceU) ?>"><?= $esc($enlaceT) ?></a>
              <?php endif; ?>
              <?php if ($enlaceU !== $voluntariado): ?>
                <a class="btn btn--contorno-claro" href="<?= $esc($voluntariado) ?>">Sé voluntario</a>
              <?php endif; ?>
            </div>
            <?php
        };
        ?>
        <div class="swiper-slide" role="group" aria-roledescription="diapositiva"
             aria-label="<?= $i + 1 ?> de <?= $total ?>">

          <?php if ($aSangre): ?>
            <?php /* La fotografía llena la diapositiva y el texto va encima.
                     El velo es --hero: un foco elíptico anclado fuera del
                     encuadre, por la esquina inferior izquierda, que cubre la
                     zona del texto y se apaga antes de llegar a ningún rostro.
                     Sus porcentajes están medidos; no se tocan al reutilizarlo. */ ?>
            <div class="hero__media">
              <?= $sitio->imagen($l, $fotosHero[$i % count($fotosHero)], [
                  'sizes'     => '100vw',
                  'prioridad' => $i === 0,
              ]) ?>
            </div>
            <div class="capa velo velo--hero" aria-hidden="true"></div>
            <div class="contenedor hero__contenido">
              <div class="hero__bloque"><?php $pintarLamina(); ?></div>
            </div>

          <?php else: ?>
            <?php /* El panel va PRIMERO en el documento: en escritorio esto es
                     una rejilla, y en una rejilla el orden del código es el
                     orden en pantalla. Texto a la izquierda, imagen a la
                     derecha, que es la composición que pidió el cliente. */ ?>
            <div class="hero__doble hero__doble--texto-izq">
              <div class="hero__panel">
                <div class="hero__panel-interior"><?php $pintarLamina(); ?></div>
              </div>

              <div class="hero__retrato retrato">
                <?= $sitio->imagen($l, $fotosHero[$i % count($fotosHero)], [
                    'sizes'     => '(min-width:900px) 42vw, 100vw',
                    'prioridad' => $i === 0,
                ]) ?>
              </div>
            </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($total > 1): ?>
    <div class="hero__controles">
      <button class="hero__pausa" type="button" data-hero-pausa aria-label="Pausar el paso automático de diapositivas">Pausa</button>
      <div class="hero__paginacion" role="group" aria-label="Ir a una diapositiva">
        <?php foreach ($laminas as $i => $l): ?>
          <button class="hero__punto" type="button" data-indice="<?= $i ?>"
                  aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                  aria-label="Diapositiva <?= $i + 1 ?>: <?= $esc($l['titulo'] ?? '') ?>">
            <span><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <span class="hero__barra"><i></i></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  <p class="solo-lectores" data-hero-vivo aria-live="polite"></p>
</section>

<?php /* Aquí estaba la tira de la cuenta atrás. Se ha movido a la cabecera
         (assets/parciales/cabecera.php), donde sale en las veinticuatro
         páginas y no sólo en ésta. La fecha sigue saliendo del panel.
         Para recuperarla aquí: git show HEAD:views/portada.php. */ ?>

<!-- ══════════════════════════════════════════════════════════════════════
     3. ABRAMOS EL CORAZÓN — el lema, explicado
     ══════════════════════════════════════════════════════════════════════ -->
<?php /* La decoración de esta sección es TODA de vista: ni un campo nuevo, ni una
         consulta nueva. Se leen exactamente los mismos cuatro valores de antes.

         Por qué hacía falta: el titular del hero y éste son la misma frase, con
         el mismo cuerpo, a 467 px de distancia, y esto iba sobre papel liso
         entre dos bandas de color. Se leía como el hueco entre dos secciones y
         no como la sección que explica el lema.

         Tres cosas lo arreglan, y las tres salen del sistema que ya existe:
         suelo propio (la banda de tinte cálido), el corazón atravesado del
         escudo agustino del Santo Padre como grafismo, y un filete de oro. El
         corazón grande del fondo es ornamento: va con aria-hidden y no aporta
         significado que se pierda si no se ve. */ ?>
<?php if ($hay('abramos-el-corazon')): ?>
<section class="seccion lema-bloque seccion--tinte seccion--pastel-acento" id="abramos-el-corazon" aria-labelledby="t-lema">
  <svg class="lema-bloque__fondo" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#i-corazon"/></svg>
  <div class="contenedor">
    <div class="lema-bloque__interior" data-reveal="fade-rise">
      <svg class="ornamento lema-bloque__signo" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#i-corazon"/></svg>
      <span class="rotulo"><?= $esc($campo('abramos-el-corazon', 'rotulo', 'El lema')) ?></span>
      <h2 class="lema-bloque__titulo" id="t-lema">
        <?= $esc($campo('abramos-el-corazon', 'titulo', 'Abramos el corazón')) ?>
      </h2>
      <hr class="filete filete--corto lema-bloque__filete">
      <div class="lema-bloque__texto">
        <?= $rico($campo('abramos-el-corazon', 'texto',
            '<p>Una invitación a recibir al Santo Padre, encontrarnos como Iglesia y renovar nuestra esperanza.</p>')) ?>
      </div>
      <?php if ($campo('abramos-el-corazon', 'cta_texto') !== ''): ?>
        <p class="lema-bloque__pie">
          <a class="enlace-flecha" href="<?= $esc($destino($campo('abramos-el-corazon', 'cta_url', 'el-papa/'))) ?>">
            <?= $esc($campo('abramos-el-corazon', 'cta_texto')) ?>
            <svg aria-hidden="true"><use href="#i-flecha"/></svg>
          </a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     4. EL PAPA LEÓN XIV LLEGA AL PERÚ
     El bloque de presentación, con la tira de ciudades debajo. Las cuatro no
     son adorno: cada una lleva directamente a su sede, para quien sólo quiere
     saber si el Santo Padre pasa por la suya.
     ══════════════════════════════════════════════════════════════════════ -->
<?php if ($hay('llega-al-peru')): ?>
<section class="seccion seccion--tinte llegada" id="la-visita" aria-labelledby="t-llegada">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">

        <header class="seccion__encabezado seccion__encabezado--mayor">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('llega-al-peru', 'rotulo', 'La visita')) ?></span>
          <h2 class="titular--mayor" id="t-llegada" data-reveal="mask-lines">
            <span class="linea"><span><?= $esc($campo('llega-al-peru', 'titulo', 'El Papa León XIV llega al Perú')) ?></span></span>
          </h2>
        </header>

        <div class="texto-lectura" data-reveal="fade-rise">
          <?= $rico($campo('llega-al-peru', 'texto',
              '<p>Del 11 al 16 de noviembre de 2026, el Santo Padre León XIV realizará su Visita Apostólica al Perú, recorriendo Lima, Chiclayo, Cusco y Pucallpa.</p>')) ?>
        </div>

        <p class="llegada__accion">
          <a class="btn btn--primario" href="<?= $esc($destino($campo('llega-al-peru', 'cta_url', 'el-papa/'))) ?>">
            <?= $esc($campo('llega-al-peru', 'cta_texto', 'Conoce la visita')) ?>
          </a>
        </p>

      </div>
    </div>

    <?php $ciudades = $bloques('el-recorrido'); ?>
    <?php if ($ciudades !== []): ?>
      <ul class="tira-ciudades" data-reveal="fade-rise">
        <?php foreach ($ciudades as $c): ?>
          <?php $u = $destino($c['enlace_url'] ?? ''); ?>
          <li>
            <?php if ($u !== ''): ?>
              <a href="<?= $esc($u) ?>"><?= $esc($c['titulo'] ?? '') ?></a>
            <?php else: ?>
              <span><?= $esc($c['titulo'] ?? '') ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     5. EL RECORRIDO — una tarjeta por sede
     Cada tarjeta lleva a la PÁGINA de su sede, no a un ancla dentro del
     listado. La sede es donde vivirán el programa, los lugares de encuentro,
     la información para peregrinos y las noticias de esa ciudad.
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$fotosSede = [
    'lima'     => $foto('ciudades/lima-g', 720, 540, 'Catedral de Lima iluminada en la plaza Mayor', [480, 720], '(min-width:1024px) 25vw, (min-width:600px) 50vw, 100vw'),
    'chiclayo' => $foto('ciudades/chiclayo-g', 720, 540, 'Catedral de Santa María y plaza de armas de Chiclayo al anochecer', [480, 720], '(min-width:1024px) 25vw, (min-width:600px) 50vw, 100vw'),
    'cusco'    => $foto('ciudades/cusco-g', 720, 540, 'Catedral del Cusco desde la plaza de armas al atardecer', [480, 720, 1080], '(min-width:1024px) 25vw, (min-width:600px) 50vw, 100vw'),
    'pucallpa' => $foto('ciudades/pucallpa-g', 720, 540, 'Catedral de Pucallpa y plaza de armas iluminadas de noche', [480, 720], '(min-width:1024px) 25vw, (min-width:600px) 50vw, 100vw'),
];
$sedes = array_values($bloques('el-recorrido'));
?>
<?php if ($sedes !== []): ?>
<section class="seccion seccion--tinte seccion--pastel-acento" id="sedes" aria-labelledby="t-sedes">
  <div class="contenedor">

    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('el-recorrido', 'rotulo', 'El recorrido')) ?></span>
      <h2 class="titular--mayor" id="t-sedes" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('el-recorrido', 'titulo', 'Lima · Chiclayo · Cusco · Pucallpa')) ?></span></span>
      </h2>
      <div class="sedes__intro"><?= $rico($campo('el-recorrido', 'texto')) ?></div>
    </header>

    <ol class="sedes-grilla">
      <?php foreach ($sedes as $i => $s): ?>
        <?php
        $clave = strtolower(trim((string) ($s['titulo'] ?? '')));
        $u     = $destino($s['enlace_url'] ?? '');
        ?>
        <li class="sede-caja" data-reveal="fade-rise" data-reveal-delay="<?= number_format($i * 0.07, 2, '.', '') ?>">
          <a class="sede-caja__enlace" href="<?= $esc($u !== '' ? $u : $sitio->enlace('sedes/')) ?>">
            <span class="sede-caja__media">
              <?= $sitio->imagen($s, $fotosSede[$clave] ?? reset($fotosSede), [
                  'sizes' => '(min-width:1024px) 25vw, (min-width:600px) 50vw, 100vw',
              ]) ?>
            </span>
            <span class="sede-caja__cuerpo">
              <span class="sede-caja__num indice"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <span class="sede-caja__nombre"><?= $esc($s['titulo'] ?? '') ?></span>
              <?php if (($s['rotulo'] ?? '') !== ''): ?>
                <span class="sede-caja__meta"><?= $esc($s['rotulo']) ?></span>
              <?php endif; ?>
              <?php if (($s['texto'] ?? '') !== ''): ?>
                <span class="sede-caja__linea"><?= $esc(mb_strimwidth(strip_tags((string) $s['texto']), 0, 148, '…')) ?></span>
              <?php endif; ?>
              <span class="sede-caja__ir">
                <?= $esc(($s['enlace_texto'] ?? '') !== '' ? $s['enlace_texto'] : 'Conoce esta sede') ?>
                <svg aria-hidden="true"><use href="#i-flecha"/></svg>
              </span>
            </span>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>

    <p class="seccion__pie nota">El reparto de actos por ciudad lo fijará el programa oficial.</p>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     6. EL ITINERARIO — jornada a jornada

     ⚠ PROGRAMA REFERENCIAL. Lo dice la propia sección y lo repite el aviso en
     pantalla: mientras la Santa Sede no publique el programa oficial, estas
     fechas y actividades son una propuesta de diseño, no información
     confirmada. El aviso NO se quita hasta que el programa sea oficial.
     ══════════════════════════════════════════════════════════════════════ -->
<?php $jornadas = $bloques('itinerario'); ?>
<?php if ($jornadas !== []): ?>
<?php /* Banda propia. Medido antes de ponerla: el itinerario, los destacados y
         las crónicas sumaban 2.863 px seguidos sobre exactamente el mismo papel
         —y eran 4.091 px antes de compactar esta sección—, tres bloques que el
         ojo no distingue como secciones distintas porque nada cambia entre
         ellos. El tinte es uno de los tres que ya usa el sitio, con su
         contraste comprobado; no entra ningún color nuevo. */ ?>
<section class="seccion itinerario seccion--tinte" id="itinerario" aria-labelledby="t-itinerario">
  <div class="contenedor">

    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('itinerario', 'rotulo', 'Del 11 al 16 de noviembre de 2026')) ?></span>
      <h2 class="titular--mayor" id="t-itinerario" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('itinerario', 'titulo', 'El recorrido del Santo Padre')) ?></span></span>
      </h2>
      <div class="texto-lectura"><?= $rico($campo('itinerario', 'texto')) ?></div>
    </header>

    <p class="aviso-referencial" role="note">
      <strong>Programa referencial.</strong> Las fechas, los lugares y las actividades
      se sustituirán por el programa oficial cuando la Santa Sede lo publique.
    </p>

    <?php /* ── El formato ─────────────────────────────────────────────────────
             Antes: seis filas a todo el ancho, la fecha en una columna estrecha
             y el cuerpo al lado. 2.445 px, la sección más alta de la portada, y
             las seis jornadas idénticas entre sí: el día que llega el Santo
             Padre y el día que se va se veían igual.

             Ahora: una jornada por tarjeta, en dos o tres columnas según el
             ancho, cada una abierta por un filete con su punto de oro. Se lee
             como un recorrido con paradas y no como una lista larga.

             Lo que NO cambia, porque es lo que sostiene la sección:
              · sigue siendo <ol> con <li>, que es lo que hace que un lector de
                pantalla diga «elemento 3 de 6»;
              · un <h3> por jornada, dentro del esquema de encabezados;
              · la fecha va ANTES del titular en el documento, como iba;
              · las actividades siguen en <ul> de verdad;
              · el aviso de programa referencial sigue arriba y a la vista;
              · ni un texto se acorta, se resume ni se esconde. Se pintan los
                mismos campos, con el mismo escapado y el mismo $destino().

             El numeral es un índice de secuencia, como el 01–04 de las sedes:
             va con aria-hidden porque no añade información, sólo orienta. */ ?>
    <ol class="ruta">
      <?php foreach ($jornadas as $i => $j): ?>
        <?php
        $u    = $destino($j['enlace_url'] ?? '');
        $acts = (array) ($j['datos']['actividades'] ?? []);
        ?>
        <li class="ruta__dia" data-reveal="fade-rise" data-reveal-delay="<?= $esc(number_format(($i % 3) * 0.07, 2, '.', '')) ?>">
          <p class="ruta__fecha">
            <span class="indice ruta__num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <?= $esc($j['rotulo'] ?? '') ?>
          </p>
          <div class="ruta__cuerpo">
            <h3 class="ruta__titulo"><?= $esc($j['titulo'] ?? '') ?></h3>
            <?php if (($j['texto'] ?? '') !== ''): ?>
              <p class="ruta__texto"><?= $esc(strip_tags((string) $j['texto'])) ?></p>
            <?php endif; ?>
            <?php if ($acts !== []): ?>
              <ul class="ruta__actos">
                <?php foreach ($acts as $a): ?>
                  <li><?= $esc((string) $a) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if ($u !== ''): ?>
              <p class="ruta__pie">
                <a class="enlace-flecha" href="<?= $esc($u) ?>">
                  <?= $esc(($j['enlace_texto'] ?? '') !== '' ? $j['enlace_texto'] : 'Conoce la sede') ?>
                  <svg aria-hidden="true"><use href="#i-flecha"/></svg>
                </a>
              </p>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>

    <p class="seccion__pie">
      <a class="enlace-flecha" href="<?= $esc($sitio->enlace('agenda/')) ?>">
        Ver el estado de la agenda <svg aria-hidden="true"><use href="#i-flecha"/></svg>
      </a>
    </p>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     7. DESTACADOS — por dónde empezar
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$fotosDestacado = [
    $foto('fotos/destacado-guia', 1120, 1400, 'Jóvenes acompañan la cruz peregrina en una procesión', [640, 1024, 1400], '(min-width:1024px) 30vw, 76vw'),
    $foto('fotos/destacado-agenda', 576, 720, 'El Papa León XIV saluda a los fieles congregados en la plaza', [640, 720], '(min-width:1024px) 30vw, 76vw'),
    $foto('fotos/destacado-materiales', 1120, 1400, 'El Papa León XIV durante una celebración eucarística', [640, 1024, 1400], '(min-width:1024px) 30vw, 76vw'),
];
$destacados = array_values($bloques('todo-necesitas-antes'));
?>
<?php if ($destacados !== []): ?>
<section class="seccion destacados" id="destacados" aria-labelledby="t-destacados">
  <div class="contenedor">

    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('todo-necesitas-antes', 'rotulo', 'Por dónde empezar')) ?></span>
      <h2 id="t-destacados" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('todo-necesitas-antes', 'titulo', 'Todo lo que necesitas antes del viaje')) ?></span></span>
      </h2>
    </header>

    <div class="reticula destacados__pista">
      <?php foreach ($destacados as $i => $d): ?>
        <?php $u = $destino($d['enlace_url'] ?? ''); ?>
        <article class="tarjeta destacado col-m-4 col-t-2 col-d-4" data-reveal="wipe-up"
                 data-reveal-delay="<?= number_format($i * 0.1, 1, '.', '') ?>">
          <figure class="figura ar-4-5 figura--arco">
            <?= $sitio->imagen($d, $fotosDestacado[$i % count($fotosDestacado)], [
                'sizes' => '(min-width:1024px) 30vw, 76vw',
            ]) ?>
          </figure>
          <div class="tarjeta__cuerpo">
            <?php if (($d['rotulo'] ?? '') !== ''): ?>
              <span class="rotulo"><?= $esc($d['rotulo']) ?></span>
            <?php endif; ?>
            <h3 class="tarjeta__titulo">
              <?php if ($u !== ''): ?>
                <a href="<?= $esc($u) ?>"><?= $esc($d['titulo'] ?? '') ?></a>
              <?php else: ?>
                <?= $esc($d['titulo'] ?? '') ?>
              <?php endif; ?>
            </h3>
            <p class="tarjeta__texto"><?= $esc(strip_tags((string) ($d['texto'] ?? ''))) ?></p>
          </div>
          <?php if ($u !== ''): ?>
            <p class="tarjeta__pie">
              <a class="enlace-flecha" href="<?= $esc($u) ?>">
                <?= $esc(($d['enlace_texto'] ?? '') !== '' ? $d['enlace_texto'] : 'Ver más') ?>
                <svg aria-hidden="true"><use href="#i-flecha"/></svg>
              </a>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     8. CRÓNICAS DE LA VISITA — los hitos del camino

     Las fotos están recortadas para que ningún rostro quede bajo el velo: la
     regla dura del encargo prohíbe cualquier degradado sobre el rostro del
     Santo Padre. Si alguien cambia una foto desde el panel hay que volver a
     comprobarlo: el recorte no se rehace solo.
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$fotosHito = [
    $foto('hitos/hito-anuncio', 1440, 1080, 'El Santo Padre saluda con los brazos en alto desde la logia', [640, 960, 1440], '(min-width:1024px) 38rem, 86vw'),
    $foto('hitos/hito-programa', 960, 720, 'Concelebración en la Capilla Sixtina', [640, 960], '(min-width:1024px) 38rem, 86vw'),
    $foto('hitos/hito-llegada', 1111, 833, 'Bendición a una mujer mayor durante una celebración parroquial', [640, 960], '(min-width:1024px) 38rem, 86vw'),
    $foto('hitos/hito-sedes', 1440, 1080, 'El Santo Padre bendice durante una celebración solemne', [640, 960, 1440], '(min-width:1024px) 38rem, 86vw'),
    $foto('hitos/hito-regreso', 1137, 853, 'El Santo Padre se dirige a los presentes durante una audiencia', [640, 960], '(min-width:1024px) 38rem, 86vw'),
];
$hitos = array_values($bloques('cronicas-visita'));

/* El estado se escribe en el panel con una palabra. Se normaliza aquí para
   que «Cumplido», «cumplido» y «CUMPLIDO» pinten el mismo color, y para que
   cualquier otra cosa se muestre tal cual en lugar de inventarse una clase
   CSS que no existe. */
$claseEstado = static function (mixed $e): string {
    $e = mb_strtolower(trim((string) $e));

    return in_array($e, ['cumplido', 'previsto'], true) ? ' estado--' . $e : '';
};
?>
<?php if ($hitos !== []): ?>
<section class="seccion" id="cronicas" aria-labelledby="t-cronicas" data-hitos>
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-11">
        <header class="seccion__encabezado hitos-carril__encabezado">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('cronicas-visita', 'rotulo', 'Hitos verificables')) ?></span>
          <h2 id="t-cronicas" data-reveal="mask-lines">
            <span class="linea"><span><?= $esc($campo('cronicas-visita', 'titulo', 'Crónicas de la visita')) ?></span></span>
          </h2>
          <div class="texto-lectura"><?= $rico($campo('cronicas-visita', 'texto')) ?></div>

          <div class="hitos-carril__mandos">
            <button class="mando" type="button" data-hitos-prev aria-label="Hito anterior">
              <svg aria-hidden="true"><use href="#i-flecha"/></svg>
            </button>
            <button class="mando" type="button" data-hitos-next aria-label="Hito siguiente">
              <svg aria-hidden="true"><use href="#i-flecha"/></svg>
            </button>
          </div>
        </header>
      </div>
    </div>
  </div>

  <p class="solo-lectores" data-hitos-vivo role="status" aria-live="polite"></p>
  <div class="hitos-carril">
    <div class="swiper">
      <ol class="swiper-wrapper">
        <?php foreach ($hitos as $i => $h): ?>
          <li class="swiper-slide hito-tarjeta">
            <?= $sitio->imagen($h, $fotosHito[$i % count($fotosHito)], [
                'sizes' => '(min-width:1024px) 38rem, 86vw',
            ]) ?>
            <div class="hito-tarjeta__cuerpo">
              <p class="hito-tarjeta__fecha"><?= $esc($h['rotulo'] ?? '') ?></p>
              <h3 class="hito-tarjeta__titulo"><?= $esc($h['titulo'] ?? '') ?></h3>
              <p class="hito-tarjeta__texto"><?= $esc(strip_tags((string) ($h['texto'] ?? ''))) ?></p>
              <?php $estado = trim((string) ($h['datos']['estado'] ?? '')); ?>
              <?php if ($estado !== ''): ?>
                <p class="estado<?= $claseEstado($estado) ?>"><?= $esc(mb_strtoupper(mb_substr($estado, 0, 1)) . mb_substr($estado, 1)) ?></p>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     9. TIERRA DE SANTOS
     Cinco retratos, cinco palabras, cinco caminos. Cada uno lleva a su
     biografía completa.

     Mientras una pieza no tenga fotografía elegida en la biblioteca se pinta
     un medallón con su inicial. Es preferible a una imagen prestada: poner la
     cara de un santo en el sitio de otro es peor que no poner ninguna.
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$santos = array_values($bloques('tierra-de-santos'));

/* ── Los retratos de los cinco santos ──────────────────────────────────────
   Los archivos los dejó el cliente en assets/img/. Se emparejan por NOMBRE y
   no por posición: si alguien reordena las fichas desde el panel, cada retrato
   sigue con su santo. Poner la cara de un santo en el sitio de otro es peor que
   no poner ninguna, y con una lista por índice eso pasa al primer arrastre.

   Los nombres de archivo van tal cual llegaron, mayúsculas incluidas.
   Producción es Linux: ahí «Santo-Toribio-Mogrovejo.jpg» y
   «santo-toribio-mogrovejo.jpg» son dos archivos distintos, y el segundo no
   existe.

   Siguen siendo el RESPALDO, no el contenido: en cuanto alguien suba un
   retrato desde el panel, Sitio::imagen() sirve el de la biblioteca con sus
   variantes y esto deja de pintarse. Y el santo que no tenga archivo aquí
   conserva su medallón con la inicial. */
$retratosSantos = [
    'santa rosa de lima'         => ['santa-rosa.webp',             768, 512, 'Retrato de Santa Rosa de Lima'],
    'san martin de porres'       => ['san-martin.avif',            1200, 1200, 'Retrato de San Martín de Porres'],
    'san juan macias'            => ['san-juan-macias.jpg',         300, 300, 'Retrato de San Juan Macías'],
    'san francisco solano'       => ['san-francisco-solano.jpg',    447, 447, 'Retrato de San Francisco Solano'],
    'santo toribio de mogrovejo' => ['Santo-Toribio-Mogrovejo.jpg', 319, 390, 'Retrato de Santo Toribio de Mogrovejo'],
];

/* Sin tildes y en minúsculas, para que «San Martín» encuentre su archivo
   aunque el panel guarde el nombre con o sin acento. */
$claveSanto = static function (string $s): string {
    $s = mb_strtolower(trim($s));
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

    return (string) preg_replace('~\s+~u', ' ', $s);
};
?>
<?php if ($santos !== []): ?>
<section class="seccion seccion--tinte santos" id="tierra-de-santos" aria-labelledby="t-santos">
  <div class="contenedor">

    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('tierra-de-santos', 'rotulo', 'Cinco caminos de santidad')) ?></span>
      <h2 class="titular--mayor" id="t-santos" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('tierra-de-santos', 'titulo', 'Cinco santos, un mismo corazón')) ?></span></span>
      </h2>
      <div class="texto-lectura santos__intro"><?= $rico($campo('tierra-de-santos', 'texto')) ?></div>
    </header>

    <ul class="santos-tira">
      <?php foreach ($santos as $i => $s): ?>
        <?php
        $u      = $destino($s['enlace_url'] ?? '');
        $nombre = (string) ($s['titulo'] ?? '');
        // La palabra clave es la primera del rótulo: «Oración · Entrega · Servicio».
        $clave  = trim(explode('·', (string) ($s['rotulo'] ?? ''))[0]);
        // Y la inicial sale del nombre propio, no del tratamiento: de «Santa
        // Rosa de Lima» interesa la R, no la S de «Santa» —si no, tres de los
        // cinco medallones dirían lo mismo.
        $propio  = (string) preg_replace('~^(San|Santa|Santo)\s+~ui', '', $nombre);
        $inicial = mb_strtoupper(mb_substr($propio !== '' ? $propio : $nombre, 0, 1));

        /* El retrato del cliente si lo hay para este santo; si no, el medallón
           con la inicial, que es lo que había. */
        $retrato = $retratosSantos[$claveSanto($nombre)] ?? null;
        $reserva = $retrato !== null
            ? '<img src="' . $esc($sitio->asset('assets/img/' . $retrato[0])) . '"'
              . ' width="' . $retrato[1] . '" height="' . $retrato[2] . '"'
              . ' alt="' . $esc($retrato[3]) . '" loading="lazy" decoding="async">'
            : '<span class="santo__inicial" aria-hidden="true">' . $esc($inicial) . '</span>';
        ?>
        <li class="santo" data-reveal="fade-rise" data-reveal-delay="<?= number_format($i * 0.06, 2, '.', '') ?>">
          <a class="santo__enlace" href="<?= $esc($u !== '' ? $u : $sitio->enlace('tierra-de-santos/')) ?>">
            <span class="santo__medallon<?= $retrato !== null ? ' santo__medallon--foto' : '' ?>">
              <?= $sitio->imagen($s, $reserva, [
                  'sizes' => '(min-width:900px) 15vw, 40vw',
              ]) ?>
            </span>
            <span class="santo__nombre"><?= $esc($nombre) ?></span>
            <?php if ($clave !== ''): ?>
              <span class="santo__clave"><?= $esc($clave) ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <p class="seccion__pie">
      <a class="enlace-flecha" href="<?= $esc($sitio->enlace('tierra-de-santos/')) ?>">
        Conoce sus historias <svg aria-hidden="true"><use href="#i-flecha"/></svg>
      </a>
    </p>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     2 bis. LA COLECTA NACIONAL

     Aquí aterriza la lámina «Colecta Nacional» del carrusel: su botón apunta a
     #colecta, así que el clic baja a este bloque en lugar de sacar a nadie de
     la portada.

     ⚠ LAS CUENTAS. Los números salen del comunicado oficial de la Conferencia
     Episcopal y se guardan en la base, editables desde el panel. Dos avisos
     para quien los toque:

       · Un dígito cambiado manda el dinero de alguien a otra cuenta. No se
         escriben de memoria ni se copian de una captura de pantalla: se copian
         del comunicado.
       · Los dos CCI son coherentes con sus cuentas —banco 002, oficina 191, el
         número de cuenta y los dos dígitos de control—, y esa coherencia es
         comprobable. Si alguien cambia un número y el CCI deja de cuadrar, es
         que uno de los dos está mal.

     La página /donativo/ lleva un aviso que dice que sólo son oficiales las
     cuentas publicadas allí o por la Conferencia Episcopal. Estas lo son: hay
     que dejar esa página al día para que las dos no se contradigan.
     ══════════════════════════════════════════════════════════════════════ -->
<?php $cuentas = $bloques('colecta'); ?>
<?php if ($hay('colecta')): ?>
<section class="seccion colecta seccion--tinte" id="colecta" aria-labelledby="t-colecta">
  <div class="contenedor">
    <div class="colecta__interior">

      <div class="colecta__dicho" data-reveal="fade-rise">
        <span class="rotulo"><?= $esc($campo('colecta', 'rotulo', 'Colecta Nacional')) ?></span>
        <h2 class="colecta__titulo" id="t-colecta">
          <?= $esc($campo('colecta', 'titulo', 'Súmate con tu donación')) ?>
        </h2>
        <?php if ($campo('colecta', 'subtitulo') !== ''): ?>
          <p class="colecta__sub"><?= $esc($campo('colecta', 'subtitulo')) ?></p>
        <?php endif; ?>
        <div class="colecta__texto">
          <?= $rico($campo('colecta', 'texto',
              '<p>Con tu aporte ayudamos a preparar este gran encuentro de fe, unidad y esperanza.</p>')) ?>
        </div>
        <?php if ($campo('colecta', 'cta_texto') !== ''): ?>
          <p class="colecta__pie">
            <a class="btn btn--primario" href="<?= $esc($destino($campo('colecta', 'cta_url', 'donativo/'))) ?>">
              <?= $esc($campo('colecta', 'cta_texto')) ?>
            </a>
          </p>
        <?php endif; ?>
      </div>

      <?php if ($cuentas !== []): ?>
        <?php /* Una lista de descripción y no una tabla: cada cuenta es un
                 rótulo con sus dos datos, no una matriz de filas y columnas.
                 Los dígitos van en <span> con la clase que fija cifras
                 tabulares, para que se lean de un vistazo y sin que ninguna
                 fuente los junte. */ ?>
        <ul class="colecta__cuentas" data-reveal="fade-rise" data-reveal-delay="0.08">
          <?php foreach ($cuentas as $c): ?>
            <?php
            $numero = trim((string) ($c['datos']['numero'] ?? ''));
            $cci    = trim((string) ($c['datos']['cci'] ?? ''));
            ?>
            <li class="cuenta">
              <p class="cuenta__banco"><?= $esc($c['rotulo'] ?? '') ?></p>
              <?php if (($c['titulo'] ?? '') !== ''): ?>
                <p class="cuenta__titular"><?= $esc($c['titulo']) ?></p>
              <?php endif; ?>
              <dl class="cuenta__datos">
                <?php if ($numero !== ''): ?>
                  <div class="cuenta__linea">
                    <dt>Cuenta</dt>
                    <dd><span class="cifras" data-copiar="<?= $esc($numero) ?>"><?= $esc($numero) ?></span></dd>
                  </div>
                <?php endif; ?>
                <?php if ($cci !== ''): ?>
                  <div class="cuenta__linea">
                    <dt>CCI</dt>
                    <dd><span class="cifras" data-copiar="<?= $esc($cci) ?>"><?= $esc($cci) ?></span></dd>
                  </div>
                <?php endif; ?>
              </dl>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php $nota = (string) (\Intranet\Publico\Sitio::dato($secciones, 'colecta', 'nota', '') ?? ''); ?>
      <?php if (trim($nota) !== ''): ?>
        <p class="colecta__nota"><?= $esc($nota) ?></p>
      <?php endif; ?>

    </div>
  </div>
</section>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════════
     10. NOTICIAS
     Las más recientes. Cada una lleva a su propia página dentro del sitio; el
     enlace a la publicación original de la CEP va dentro de la noticia, no
     aquí: desde la portada no se echa a nadie a otro dominio.
     ══════════════════════════════════════════════════════════════════════ -->
<?php $noticias = array_values($bloques('mas-destacado')); ?>
<?php if ($noticias !== []): ?>
<section class="seccion seccion--tinte seccion--pastel" id="noticias" aria-labelledby="t-noticias">
  <div class="contenedor">

    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('mas-destacado', 'rotulo', 'Actualidad de la visita')) ?></span>
      <h2 id="t-noticias" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('mas-destacado', 'titulo', 'Lo más destacado')) ?></span></span>
      </h2>
    </header>

    <ol class="noticias-tira">
      <?php foreach ($noticias as $i => $n): ?>
        <?php
        $u     = $destino($n['enlace_url'] ?? '');
        $fecha = (string) ($n['datos']['fecha'] ?? '');
        ?>
        <li class="noticia-t<?= $i === 0 ? ' noticia-t--destacada' : '' ?>" data-reveal="fade-rise"
            data-reveal-delay="<?= number_format($i * 0.08, 2, '.', '') ?>">
          <a class="noticia-t__enlace" href="<?= $esc($u !== '' ? $u : $sitio->enlace('noticias/')) ?>">
            <span class="noticia-t__media">
              <?= $sitio->imagen($n, $fotosDestacado[$i % count($fotosDestacado)], [
                  'sizes' => '(min-width:900px) 33vw, 100vw',
              ]) ?>
            </span>
            <span class="noticia-t__cuerpo">
              <span class="noticia-t__meta">
                <?php if (($n['datos']['fuente'] ?? '') !== ''): ?>
                  <span class="rotulo"><?= $esc($n['datos']['fuente']) ?></span>
                <?php endif; ?>
                <?php if ($fecha !== ''): ?>
                  <span class="noticia-t__fecha"><?= $esc($fecha) ?></span>
                <?php endif; ?>
              </span>
              <span class="noticia-t__titulo"><?= $esc($n['titulo'] ?? '') ?></span>
              <span class="noticia-t__texto"><?= $esc(mb_strimwidth(strip_tags((string) ($n['texto'] ?? '')), 0, 190, '…')) ?></span>
              <span class="noticia-t__mas">
                <?= $esc(($n['enlace_texto'] ?? '') !== '' ? $n['enlace_texto'] : 'Leer más') ?>
                <svg aria-hidden="true"><use href="#i-flecha"/></svg>
              </span>
            </span>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>

    <p class="seccion__pie">
      <a class="enlace-flecha" href="<?= $esc($sitio->enlace('noticias/')) ?>">
        Ver todas las noticias <svg aria-hidden="true"><use href="#i-flecha"/></svg>
      </a>
    </p>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     11. LLAMADA A LOS FIELES — banda oscura
     ══════════════════════════════════════════════════════════════════════ -->
<section class="seccion" id="preparate" aria-labelledby="t-preparate" style="padding: 0;">
  <div class="preparate banda-oscura">

    <div class="preparate__media">
      <?= $sitio->imagen($secciones['prepara-tu-corazon'] ?? [],
          $foto('fotos/preparate', 794, 992, 'El Papa León XIV bendice a una fiel durante una visita pastoral', [640, 794], '(min-width:900px) 42vw, 100vw'),
          ['sizes' => '(min-width:900px) 42vw, 100vw']) ?>
    </div>

    <div class="preparate__panel">
      <div class="preparate__contenido">
        <img class="preparate__escudo" src="<?= $esc($sitio->asset('assets/img/escudo-leon-xiv.svg')) ?>" width="440" height="545" alt="Escudo de Su Santidad el Papa León XIV" loading="lazy">
        <span class="rotulo rotulo--claro"><?= $esc($campo('prepara-tu-corazon', 'rotulo', 'Prepárate')) ?></span>
        <h2 class="preparate__titulo" id="t-preparate"><?= $esc($campo('prepara-tu-corazon', 'titulo', 'Prepara tu corazón')) ?></h2>

        <p class="preparate__lema">«In Illo uno unum»</p>
        <p class="preparate__glosa">Aunque los cristianos seamos muchos, en el único Cristo somos uno. Lema episcopal del Santo Padre, tomado de san Agustín.</p>

        <div class="preparate__invitacion">
          <?= $rico($campo('prepara-tu-corazon', 'texto',
              '<p>Escríbenos tu correo y te avisaremos de cada paso: la publicación del programa, los materiales de oración y las formas de participar desde tu parroquia.</p>')) ?>
        </div>

        <form class="preparate__form" data-form="aviso" data-origen="preparate" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="preparate-correo">Correo electrónico</label>
            <input type="email" id="preparate-correo" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="preparate-consentimiento" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="sitio-web-preparate">No rellenar</label><input type="text" id="sitio-web-preparate" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div>
            <button class="btn btn--claro" type="submit">Quiero prepararme</button>
          </div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════
     12. LA CONFERENCIA EPISCOPAL PERUANA
     El puente institucional: quién organiza la visita, quiénes son sus
     pastores y por dónde se entra al directorio de obispos, a las trece
     comisiones y al mapa de las jurisdicciones.
     ══════════════════════════════════════════════════════════════════════ -->
<?php $presidencia = array_values($bloques('cep-home')); ?>
<?php if ($hay('cep-home')): ?>
<section class="seccion cep-bloque" id="cep" aria-labelledby="t-cep">
  <div class="contenedor">

    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('cep-home', 'rotulo', 'La Iglesia en el Perú')) ?></span>
      <h2 class="titular--mayor" id="t-cep" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('cep-home', 'titulo', 'Conferencia Episcopal Peruana')) ?></span></span>
      </h2>
      <div class="texto-lectura cep-bloque__intro"><?= $rico($campo('cep-home', 'texto')) ?></div>
    </header>

    <?php if ($presidencia !== []): ?>
      <ul class="presidencia">
        <?php foreach ($presidencia as $i => $p): ?>
          <?php
          $nombre = (string) ($p['titulo'] ?? '');
          // «Mons. Carlos Enrique García Camader» → «C». La inicial sale del
          // nombre saltándose el tratamiento: si no, las tres fichas de la
          // presidencia mostrarían la misma M de «Mons.».
          $limpio  = trim((string) preg_replace('~^(Mons\.|Card\.|Padre|P\.)\s+~ui', '', $nombre));
          $inicial = mb_strtoupper(mb_substr($limpio !== '' ? $limpio : $nombre, 0, 1));
          $u       = $destino($p['enlace_url'] ?? '');
          ?>
          <li class="presidencia__ficha" data-reveal="fade-rise" data-reveal-delay="<?= number_format($i * 0.07, 2, '.', '') ?>">
            <span class="presidencia__retrato">
              <?= $sitio->imagen($p, '<span class="presidencia__inicial" aria-hidden="true">' . $esc($inicial) . '</span>', [
                  'sizes' => '(min-width:900px) 20vw, 45vw',
              ]) ?>
            </span>
            <?php if (($p['rotulo'] ?? '') !== ''): ?>
              <span class="presidencia__cargo"><?= $esc($p['rotulo']) ?></span>
            <?php endif; ?>
            <h3 class="presidencia__nombre">
              <?php if ($u !== ''): ?><a href="<?= $esc($u) ?>"><?= $esc($nombre) ?></a><?php else: ?><?= $esc($nombre) ?><?php endif; ?>
            </h3>
            <?php if (($p['texto'] ?? '') !== ''): ?>
              <p class="presidencia__sede"><?= $esc(strip_tags((string) $p['texto'])) ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="cep-accesos" data-reveal="fade-rise">
      <a class="cep-acceso" href="<?= $esc($sitio->enlace('cep/obispos/')) ?>">
        <span class="cep-acceso__num indice">46</span>
        <span class="cep-acceso__titulo">Los obispos del Perú</span>
        <span class="cep-acceso__texto">Arzobispos, obispos y vicarios apostólicos, jurisdicción por jurisdicción.</span>
        <span class="cep-acceso__ir">Ver el directorio <svg aria-hidden="true"><use href="#i-flecha"/></svg></span>
      </a>
      <a class="cep-acceso" href="<?= $esc($sitio->enlace('cep/comisiones/')) ?>">
        <span class="cep-acceso__num indice">13</span>
        <span class="cep-acceso__titulo">Comisiones episcopales</span>
        <span class="cep-acceso__texto">Las áreas de servicio pastoral de la Iglesia en el Perú y quién preside cada una.</span>
        <span class="cep-acceso__ir">Ver las comisiones <svg aria-hidden="true"><use href="#i-flecha"/></svg></span>
      </a>
      <a class="cep-acceso" href="<?= $esc($sitio->enlace('cep/')) ?>">
        <span class="cep-acceso__num indice">PE</span>
        <span class="cep-acceso__titulo">La Iglesia en el Perú</span>
        <span class="cep-acceso__texto">Siete arquidiócesis, veintiuna diócesis, diez prelaturas y ocho vicariatos.</span>
        <span class="cep-acceso__ir">Conoce la CEP <svg aria-hidden="true"><use href="#i-flecha"/></svg></span>
      </a>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     13. CÓMO AYUDAR
     Iconos, no fotografía: las tres imágenes anteriores eran del Santo Padre
     en el Vaticano y no decían nada de voluntariado, patrocinio ni donativo.
     Los tres símbolos están dibujados para este sitio y se asignan por orden,
     así que reordenar las tarjetas en el panel reordena también los iconos.
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$iconosAyuda = ['#i-manos', '#i-alianza', '#i-ofrenda'];
$ayudas = array_values($bloques('pon-tus-dones'));
?>
<?php if ($ayudas !== []): ?>
<section class="seccion seccion--tinte" id="como-ayudar" aria-labelledby="t-ayudar">
  <div class="contenedor">

    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('pon-tus-dones', 'rotulo', 'Tres formas')) ?></span>
      <h2 id="t-ayudar" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('pon-tus-dones', 'titulo', 'Pon tus dones al servicio')) ?></span></span>
      </h2>
    </header>

    <div class="reticula ayuda-grilla">
      <?php foreach ($ayudas as $i => $a): ?>
        <?php $u = $destino($a['enlace_url'] ?? ''); ?>
        <article class="ayuda-caja col-m-4 col-t-2 col-d-4" data-reveal="fade-rise"
                 data-reveal-delay="<?= number_format($i * 0.08, 2, '.', '') ?>">
          <span class="ayuda-caja__disco" aria-hidden="true">
            <svg><use href="<?= $esc($iconosAyuda[$i % count($iconosAyuda)]) ?>"/></svg>
          </span>
          <h3 class="ayuda-caja__titulo"><?= $esc($a['titulo'] ?? '') ?></h3>
          <?php if (($a['rotulo'] ?? '') !== ''): ?>
            <p class="ayuda-caja__lema"><?= $esc($a['rotulo']) ?></p>
          <?php endif; ?>
          <p class="ayuda-caja__texto"><?= $esc(strip_tags((string) ($a['texto'] ?? ''))) ?></p>
          <?php if ($u !== ''): ?>
            <p class="ayuda-caja__pie">
              <a class="enlace-flecha" href="<?= $esc($u) ?>">
                <?= $esc(($a['enlace_texto'] ?? '') !== '' ? $a['enlace_texto'] : 'Conoce más') ?>
                <svg aria-hidden="true"><use href="#i-flecha"/></svg>
              </a>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     14. DESDE DONDE ESTÉS
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$iconosAcceso = ['#i-pantalla', '#i-whatsapp', '#i-descarga', '#i-etiqueta'];
$accesos = array_values($bloques('acompana-cada-momento'));
?>
<?php if ($accesos !== []): ?>
<section class="seccion seccion--tinte seccion--tinte--inv seccion--pastel" id="desde-donde-estes" aria-labelledby="t-vive">
  <div class="contenedor">

    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('acompana-cada-momento', 'rotulo', 'Cuatro accesos')) ?></span>
      <h2 id="t-vive" data-reveal="mask-lines">
        <span class="linea"><span><?= $esc($campo('acompana-cada-momento', 'titulo', 'Acompaña cada momento de la visita')) ?></span></span>
      </h2>
      <div class="texto-lectura"><?= $rico($campo('acompana-cada-momento', 'texto')) ?></div>
    </header>

    <div class="accesos" data-reveal="fade-rise">
      <?php foreach ($accesos as $i => $a): ?>
        <?php $u = $destino($a['enlace_url'] ?? ''); ?>
        <a class="acceso" href="<?= $esc($u !== '' ? $u : $sitio->enlace('')) ?>">
          <svg class="acceso__icono" aria-hidden="true"><use href="<?= $esc($iconosAcceso[$i % count($iconosAcceso)]) ?>"/></svg>
          <h3 class="acceso__titulo"><?= $esc($a['titulo'] ?? '') ?></h3>
          <p class="acceso__texto"><?= $esc(strip_tags((string) ($a['texto'] ?? ''))) ?></p>
          <?php if (($a['datos']['nota'] ?? '') !== ''): ?>
            <span class="acceso__pie estado"><?= $esc($a['datos']['nota']) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     15. CARTA DE LA CONFERENCIA EPISCOPAL

     La fotografía va a sangre, desenfocada y bajo degradado. Es un
     LEVANTAMIENTO EXPRESO de la regla dura nº 6 del encargo —«el retrato del
     Santo Padre nunca lleva duotono, filtro ni degradado sobre el rostro»—,
     decidido por el cliente después de que se le planteara la contradicción.
     El desenfoque y el velo viven en el CSS (.carta-fondo__media), no aquí.

     ⚠ EL TEXTO ESTÁ PENDIENTE DE VALIDACIÓN Y FIRMA. Lo redactó el estudio en
     registro pastoral para que el bloque tuviera cuerpo real. No es un texto
     de la Conferencia Episcopal Peruana y no puede publicarse sin su revisión.
     ══════════════════════════════════════════════════════════════════════ -->
<?php
$carta = $rico($campo('iglesia-peru-te', 'texto'));

/* La carta es larga y se pliega: los tres primeros párrafos se ven y el resto
   queda tras «Leer más». El corte se hace aquí, sobre el HTML ya saneado, y
   no en el panel: quien la edita escribe una carta, no dos mitades. */
$parrafos = preg_split('~(?<=</p>)~', $carta, -1, PREG_SPLIT_NO_EMPTY) ?: [];
$visibles = implode('', array_slice($parrafos, 0, 3));
$plegado  = implode('', array_slice($parrafos, 3));
?>
<?php if ($hay('iglesia-peru-te') && trim($carta) !== ''): ?>
<section class="seccion seccion--carta carta-fondo banda-oscura" id="carta" aria-labelledby="t-carta">

  <div class="carta-fondo__media" aria-hidden="true">
    <?= $foto('fotos/carta-oracion', 447, 447, '', [447], '100vw') ?>
  </div>

  <div class="contenedor carta-fondo__contenido">
    <div class="reticula">
      <?php /* El escudo ocupa el tercio derecho de la banda. Sin él quedaba
               medio metro de rojo vacío al lado de la carta. Es ornamento, va
               marcado como decorativo y NO lleva filtro: el escudo pontificio
               conserva sus esmaltes. */ ?>
      <img class="carta__escudo oculto-movil col-d-3" src="<?= $esc($sitio->asset('assets/img/escudo-leon-xiv.svg')) ?>" width="440" height="545" alt="" loading="lazy" aria-hidden="true">

      <div class="col-m-4 col-t-6 col-d-8">
        <div class="carta">
          <header class="seccion__encabezado">
            <hr class="seccion__filete" data-reveal="line-draw">
            <span class="rotulo rotulo--claro"><?= $esc($campo('iglesia-peru-te', 'rotulo', 'Conferencia Episcopal Peruana')) ?></span>
            <h2 class="titular--mayor" id="t-carta" data-reveal="mask-lines">
              <span class="linea"><span><?= $esc($campo('iglesia-peru-te', 'titulo', 'La Iglesia en el Perú te espera')) ?></span></span>
            </h2>
          </header>

          <div class="carta__cuerpo">
            <?= $visibles ?>

            <?php if (trim($plegado) !== ''): ?>
              <div class="carta__resto" id="carta-resto">
                <div><?= $plegado ?></div>
              </div>
              <p class="carta__mas">
                <button class="btn btn--claro" type="button" data-carta-mas aria-expanded="false" aria-controls="carta-resto"><span>Leer más</span></button>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

</main>
