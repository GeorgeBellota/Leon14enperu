<?php
/**
 * Vista de la página «patrocinios».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Patrocinios · Viaje de León XIV al Perú',
    'descripcion' => 'Espacios, señalética, indumentaria, pantallas y transporte. Cómo una empresa o institución puede apoyar la organización del viaje apostólico.',
    'ruta'        => 'patrocinios/',
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
$paginaCms = $sitio->contenido('patrocinios');
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-patrocinios-640.webp 640w, ../assets/img/fotos/cab-patrocinios-1024.webp 1024w, ../assets/img/fotos/cab-patrocinios-1280.webp 1280w">
      <img src="../assets/img/fotos/cab-patrocinios-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-patrocinios-640.jpg 640w, ../assets/img/fotos/cab-patrocinios-1024.jpg 1024w, ../assets/img/fotos/cab-patrocinios-1280.jpg 1280w" width="1280" height="549" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Empresas e instituciones')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Patrocinios')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Organizar seis días en cuatro ciudades tiene un coste. Estas son las necesidades reales y cómo se puede ayudar a cubrirlas.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><a href="<?= $esc($sitio->enlace('voluntariado/')) ?>">Cómo ayudar</a></li>
        <li><span aria-current="page">Patrocinios</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-necesidades">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('hace-falta', 'rotulo', 'Necesidades')) ?></span>
      <h2 class="titular--mayor" id="t-necesidades" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('hace-falta', 'titulo', 'Qué hace falta')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Nada de esto es simbólico: son partidas concretas del trabajo de organización.</p>
    </header>
    <ul class="actos">
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Espacios de trabajo</h3><p class="acto__texto">Sedes de coordinación en las cuatro ciudades durante las semanas previas y los días de la visita.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Señalética</h3><p class="acto__texto">Orientar a decenas de miles de personas en recintos que no conocen. Es lo que evita que un día de fiesta se convierta en un problema.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Indumentaria de voluntarios</h3><p class="acto__texto">Polos, gorras y credenciales. Un voluntario que no se distingue no puede ayudar a nadie.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Pantallas y sonido</h3><p class="acto__texto">En una explanada grande, la mayoría de los asistentes sigue la celebración por pantalla.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Transporte</h3><p class="acto__texto">Traslado de equipos, materiales y voluntarios entre sedes y recintos.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Agua y primeros auxilios</h3><p class="acto__texto">Puntos de hidratación y de atención básica repartidos por el recinto.</p></li>
    </ul>
  </div>
</section>

<section class="seccion seccion--tinte seccion--pastel" aria-labelledby="t-como-pat">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('como-proponer-patrocinio', 'rotulo', 'Cómo se hace')) ?></span>
      <h2 class="titular--mayor" id="t-como-pat" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('como-proponer-patrocinio', 'titulo', 'Cómo proponer un patrocinio')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Escribe a la organización contando <strong>qué puedes aportar</strong> —producto, servicio, horas de trabajo o aporte económico—, en qué sede y en qué fechas. No hace falta un dosier: hace falta una descripción clara.</p>
        <p>La organización responderá indicando si la necesidad sigue abierta y con qué condiciones. Cada aporte se acredita y se refleja en la rendición de cuentas del viaje.</p>
        <p>Canal de contacto para patrocinios: <strong>[CORREO POR CONFIRMAR]</strong>.</p>
      </div>
    <div class="aviso sep-l">
      <p class="aviso__titulo">Qué no incluye un patrocinio</p>
      <p>Apoyar la organización no otorga presencia de marca dentro de las celebraciones, ni acceso preferente a los actos, ni uso del escudo pontificio o de la imagen del Santo Padre con fines comerciales. Las condiciones de reconocimiento se acuerdan por escrito en cada caso.</p>
    </div>
    <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('contacto/')) ?>">Escribir a la organización <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
  </div></div></div>
</section>
</main>
