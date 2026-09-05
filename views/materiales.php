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

<section class="seccion" aria-labelledby="t-mat">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('habra-disponible', 'rotulo', 'En preparación')) ?></span>
      <h2 class="titular--mayor" id="t-mat" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('habra-disponible', 'titulo', 'Qué habrá disponible')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Todo será de descarga libre y de uso gratuito para parroquias, colegios y movimientos. Nada de esto está publicado todavía.</p>
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
