<?php
/**
 * Vista de la página «noticias».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Noticias del viaje apostólico de León XIV al Perú',
    'descripcion' => 'Todo lo publicado sobre el viaje apostólico del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.',
    'ruta'        => 'noticias/',
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
$paginaCms = $sitio->contenido('noticias');
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/sedes-banner2.jpg 640w, ../assets/img/sedes-banner2.jpg 1024w, ../assets/img/sedes-banner2.jpg 1600w, ../assets/img/sedes-banner2.jpg 1920w">
      <img src="../assets/img/sedes-banner2.jpg" sizes="100vw" srcset="../assets/img/sedes-banner2.jpg 640w, ../assets/img/sedes-banner2.jpg 1024w, ../assets/img/sedes-banner2.jpg 1600w, ../assets/img/sedes-banner2.jpg 1920w" width="1920" height="823" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Actualizado el 13 de agosto de 2026')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Noticias')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Lo que se ha hecho público sobre el viaje, en orden. Aquí no se publica nada que no proceda de una fuente oficial.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Noticias</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-noticias">
  <div class="contenedor">
    <div class="reticula">
      <article class="noticia-destacada col-m-4 col-t-6 col-d-7" data-reveal="wipe-up">
        <figure class="figura ar-16-9">
          <picture>
            <source type="image/webp" sizes="(min-width:1024px) 55vw, 100vw" srcset="../assets/img/fotos/noticia-anuncio-640.webp 640w, ../assets/img/fotos/noticia-anuncio-1024.webp 1024w, ../assets/img/fotos/noticia-anuncio-1600.webp 1600w">
            <img src="../assets/img/fotos/noticia-anuncio-1024.jpg" sizes="(min-width:1024px) 55vw, 100vw" srcset="../assets/img/fotos/noticia-anuncio-640.jpg 640w, ../assets/img/fotos/noticia-anuncio-1024.jpg 1024w, ../assets/img/fotos/noticia-anuncio-1600.jpg 1600w" width="1600" height="900" alt="El Papa León XIV bendice desde la logia de la basílica de San Pedro" loading="lazy" decoding="async">
          </picture>
        </figure>
        <p class="noticia-fecha"><time datetime="2026-08-05">5 de agosto de 2026</time></p>
        <h2 id="t-noticias">La Santa Sede anuncia el viaje apostólico de León XIV al Perú</h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p class="tarjeta__texto">El Santo Padre estará en el país del 11 al 16 de noviembre, en la tercera etapa de su primera gira sudamericana, después de Uruguay y Argentina. El anuncio no incluyó el programa detallado, que se dará a conocer a su debido tiempo.</p>
      </article>

      <div class="col-m-4 col-t-6 col-d-5">
        <ul class="noticias-lista">
          <li data-reveal="fade-rise">
            <p class="noticia-fecha">Fecha por confirmar</p>
            <h3>Lima, Chiclayo, Cusco y Pucallpa serán las sedes de la visita</h3>
            <p class="tarjeta__texto">Cuatro ciudades que representan la costa, la sierra y la selva, y la diócesis que el Santo Padre pastoreó durante ocho años.</p>
            <p class="sep-m"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/')) ?>">Conoce las cuatro sedes <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
          </li>
          <li data-reveal="fade-rise">
            <p class="noticia-fecha">Fecha por confirmar</p>
            <h3>Abierta la convocatoria de voluntariado «Los amigos de León»</h3>
            <p class="tarjeta__texto">Seis servicios y tres fases de selección para quienes quieran acompañar la visita desde dentro.</p>
            <p class="sep-m"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('voluntariado/')) ?>">Inscríbete como voluntario <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
          </li>
          <li data-reveal="fade-rise">
            <p class="noticia-fecha">Fecha por confirmar</p>
            <h3>El programa del viaje se publicará más adelante</h3>
            <p class="tarjeta__texto">La Oficina de Prensa de la Santa Sede dará a conocer el programa detallado a su debido tiempo.</p>
            <p class="sep-m"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('agenda/')) ?>">Ver el estado de la agenda <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="seccion seccion--tinte seccion--pastel" aria-labelledby="t-avisos">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('enterate-cuanto-haya', 'rotulo', 'Aviso')) ?></span>
      <h2 id="t-avisos" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('enterate-cuanto-haya', 'titulo', 'Entérate en cuanto haya novedades')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Durante los días de la visita esta página se actualizará varias veces al día. Si prefieres que te avisemos, déjanos tu correo.</p>
    </header>
        <form class="pila" data-form="aviso" data-origen="noticias" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="correo-noticias">Correo electrónico</label>
            <input type="email" id="correo-noticias" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="consent-noticias" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>. Solo para avisos del viaje apostólico.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="web-noticias">No rellenar</label><input type="text" id="web-noticias" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div><button class="btn btn--secundario" type="submit">Avísame</button></div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
      </div>
      <div class="col-m-4 col-t-2 col-d-5">
        <div class="aviso sep-l">
          <p class="aviso__titulo">Cómo distinguir una fuente fiable</p>
          <p>El programa oficial solo existe cuando lo publica la Oficina de Prensa de la Santa Sede. Todo lo demás —horarios que circulan por redes, listas de inscripción, entradas a la venta— es de segunda mano hasta que lo confirme la Conferencia Episcopal Peruana o tu diócesis.</p>
        </div>
      </div>
    </div>
  </div>
</section>
</main>
