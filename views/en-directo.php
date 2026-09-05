<?php
/**
 * Vista de la página «en-directo».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'En directo · Viaje de León XIV al Perú',
    'descripcion' => 'Dónde seguir en directo las celebraciones del viaje apostólico del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.',
    'ruta'        => 'en-directo/',
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
$paginaCms = $sitio->contenido('en-directo');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-directo-640.webp 640w, ../assets/img/fotos/cab-directo-1024.webp 1024w, ../assets/img/fotos/cab-directo-1600.webp 1600w">
      <img src="../assets/img/fotos/cab-directo-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-directo-640.jpg 640w, ../assets/img/fotos/cab-directo-1024.jpg 1024w, ../assets/img/fotos/cab-directo-1600.jpg 1600w" width="1600" height="686" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Del 11 al 16 de noviembre')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'En directo')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'No hace falta estar en la explanada. Aquí estarán los enlaces de cada transmisión en cuanto existan.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">En directo</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-directo">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('todavia-enlaces', 'rotulo', 'Estado')) ?></span>
      <h2 class="titular--mayor" id="t-directo" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('todavia-enlaces', 'titulo', 'Todavía no hay enlaces')) ?></span></span></h2>
    </header>
    <div class="estado-actual" data-reveal="fade-rise">
      <p class="estado-actual__frase">Los enlaces de transmisión se publican con el programa oficial.</p>
      <p class="estado-actual__meta"><span>Última actualización: 13 de agosto de 2026</span></p>
    </div>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Los viajes apostólicos se transmiten habitualmente por los medios de comunicación de la Santa Sede y por las emisoras y los canales de la Iglesia en el país que recibe la visita. Es razonable esperar que así sea también aquí, pero <strong>ningún enlace concreto está confirmado</strong>.</p>
        <p>En cuanto se publique el programa, esta página tendrá una fila por acto, con su hora en horario de Lima y el enlace directo a la transmisión. Mientras tanto, no publicamos nada que no podamos sostener.</p>
      </div>
  </div></div></div>
</section>

<section class="seccion seccion--tinte seccion--pastel-acento" aria-labelledby="t-como-seguir">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('tres-maneras-seguirlo', 'rotulo', 'Mientras tanto')) ?></span>
      <h2 class="titular--mayor" id="t-como-seguir" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('tres-maneras-seguirlo', 'titulo', 'Tres maneras de seguirlo')) ?></span></span></h2>
    </header>
    <div class="reticula">
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise">
        <h3 class="tarjeta__titulo">En tu parroquia</h3>
        <p class="tarjeta__texto">Muchas comunidades habilitan una pantalla y acompañan la transmisión con oración. Pregunta en la tuya: es la forma menos solitaria de seguirlo.</p>
      </article>
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise">
        <h3 class="tarjeta__titulo">Desde casa</h3>
        <p class="tarjeta__texto">Los enlaces estarán aquí. Si nos dejas tu correo, te avisamos el día que se publiquen para que no tengas que estar mirando.</p>
      </article>
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise">
        <h3 class="tarjeta__titulo">Con quien no puede salir</h3>
        <p class="tarjeta__texto">Un enfermo, un vecino mayor. Llevarles la transmisión y quedarte a acompañarlos es, probablemente, la mejor manera de vivir estos días.</p>
      </article>
    </div>
  </div>
</section>

<section class="seccion" aria-labelledby="t-aviso-dir">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('avisame-cuando-haya', 'rotulo', 'Aviso')) ?></span>
      <h2 id="t-aviso-dir" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('avisame-cuando-haya', 'titulo', 'Avísame cuando haya transmisión')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Un solo correo, el día que se publiquen los enlaces.</p>
    </header>
        <form class="pila" data-form="aviso" data-origen="directo" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="correo-directo">Correo electrónico</label>
            <input type="email" id="correo-directo" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="consent-directo" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="web-directo">No rellenar</label><input type="text" id="web-directo" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div><button class="btn btn--secundario" type="submit">Avísame</button></div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
  </div></div></div>
</section>
</main>
