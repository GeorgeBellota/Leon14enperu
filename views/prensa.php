<?php
/**
 * Vista de la página «prensa».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Prensa · Viaje de León XIV al Perú',
    'descripcion' => 'Acreditación, contacto y condiciones de uso de material gráfico para medios que cubran el viaje apostólico del Papa León XIV al Perú.',
    'ruta'        => 'prensa/',
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
$paginaCms = $sitio->contenido('prensa');
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-prensa-640.webp 640w, ../assets/img/fotos/cab-prensa-1024.webp 1024w, ../assets/img/fotos/cab-prensa-1280.webp 1280w">
      <img src="../assets/img/fotos/cab-prensa-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-prensa-640.jpg 640w, ../assets/img/fotos/cab-prensa-1024.jpg 1024w, ../assets/img/fotos/cab-prensa-1280.jpg 1280w" width="1280" height="549" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Para medios')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Prensa')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Acreditación, contacto y condiciones de uso del material gráfico.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Prensa</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-prensa">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('como-acreditarse', 'rotulo', 'Acreditación')) ?></span>
      <h2 class="titular--mayor" id="t-prensa" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('como-acreditarse', 'titulo', 'Cómo acreditarse')) ?></span></span></h2>
    </header>
    <div class="estado-actual" data-reveal="fade-rise">
      <p class="estado-actual__frase">El proceso de acreditación no está abierto.</p>
      <p class="estado-actual__meta"><span>Última actualización: 13 de agosto de 2026</span></p>
    </div>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>En los viajes apostólicos la acreditación de medios la gestiona la <strong>Oficina de Prensa de la Santa Sede</strong> para el vuelo papal y los actos internacionales, y la conferencia episcopal del país para los actos locales. Los plazos y los formularios se publican con algunas semanas de antelación.</p>
        <p>En cuanto se abra el proceso, aquí encontrarás el formulario, el plazo de solicitud, los requisitos y los puntos de recogida de credenciales en cada sede.</p>
      </div>
  </div></div></div>
</section>

<section class="seccion seccion--tinte seccion--pastel-acento" aria-labelledby="t-material">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('uso-imagenes-escudo', 'rotulo', 'Material')) ?></span>
      <h2 class="titular--mayor" id="t-material" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('uso-imagenes-escudo', 'titulo', 'Uso de imágenes y del escudo')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Tres reglas que no dependen de que el programa esté publicado.</p>
    </header>
    <ul class="actos">
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Retrato pontificio</h3><p class="acto__texto">El retrato oficial se publica sin recortes sobre el rostro, sin filtros de color y sin texto superpuesto. Crédito: Santa Sede.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Escudo pontificio</h3><p class="acto__texto">Conserva siempre sus esmaltes propios. No se recorta, no se deforma y no se usa como elemento decorativo repetido.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Citas</h3><p class="acto__texto">Las palabras del Santo Padre se citan por su fuente oficial. Este sitio no publica ninguna frase suya que no conste en un documento de la Santa Sede.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Kit de prensa</h3><p class="acto__texto">Dosier, logotipos y fotografías en alta resolución. <span class="estado">En preparación</span></p></li>
    </ul>
    <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('contacto/')) ?>">Contacto para medios <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
  </div>
</section>
</main>
