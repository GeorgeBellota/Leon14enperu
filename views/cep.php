<?php
/**
 * Vista de la página «cep».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'La Iglesia en el Perú · Viaje de León XIV',
    'descripcion' => 'La Conferencia Episcopal Peruana y las cuatro jurisdicciones que reciben al Papa León XIV: Lima, Chiclayo, Cusco y Pucallpa.',
    'ruta'        => 'cep/',
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
$paginaCms = $sitio->contenido('cep');
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/apostolico.webp 640w, ../assets/img/apostolico.webp 1024w, ../assets/img/apostolico.webp 1600w, ../assets/img/apostolico.webp 2200w">
      <img src="../assets/img/apostolico.webp" sizes="100vw" srcset="../assets/img/apostolico.webp 640w, ../assets/img/apostolico.webp 1024w, ../assets/img/apostolico.webp 1600w, ../assets/img/apostolico.webp 2200w" width="2200" height="943" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Quién recibe la visita')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'La Iglesia en el Perú')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'La Conferencia Episcopal Peruana y las cuatro jurisdicciones eclesiásticas que acogen el viaje apostólico.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">La Iglesia en el Perú</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-cep">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('quien-organiza-visita', 'rotulo', 'La Conferencia')) ?></span>
      <h2 class="titular--mayor" id="t-cep" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('quien-organiza-visita', 'titulo', 'Quién organiza la visita')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>La <strong>Conferencia Episcopal Peruana</strong> reúne a los obispos de las diócesis, arquidiócesis, prelaturas y vicariatos apostólicos del país. Es el organismo que coordina la preparación del viaje en el Perú, difunde y adapta lo que publica la Santa Sede, y articula el trabajo de las cuatro jurisdicciones que reciben al Santo Padre.</p>
        <p>León XIV la conoce por dentro. Fue su <strong>segundo vicepresidente</strong> desde marzo de 2018, miembro de su Consejo Económico y presidente de la Comisión Episcopal de Cultura y Educación. En 2023 la propia Conferencia le concedió la Medalla de Oro de Santo Toribio de Mogrovejo.</p>
        <p>Los datos de contacto institucional de la Conferencia se publicarán en la página de contacto.</p>
      </div>
  </div></div></div>
</section>

<section class="seccion seccion--tinte seccion--pastel" aria-labelledby="t-jurisdicciones">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('quien-acoge-cada', 'rotulo', 'Cuatro jurisdicciones')) ?></span>
      <h2 class="titular--mayor" id="t-jurisdicciones" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('quien-acoge-cada', 'titulo', 'Quién acoge cada sede')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Una arquidiócesis primada, una arquidiócesis andina, una diócesis y un vicariato apostólico de territorio de misión.</p>
    </header>
    <ol class="indice-sedes">
      <li class="sede-fila" data-reveal="fade-rise">
        <p class="sede-fila__num indice">01</p><h3 class="sede-fila__nombre">Lima</h3>
        <p class="sede-fila__meta">Arquidiócesis de Lima · Primada del Perú</p>
        <p class="sede-fila__linea">Creada en 1546 por el papa Paulo III. Confirmada como arquidiócesis primada en 1943. Abarca a más de nueve millones de habitantes.</p>
        <p class="sede-fila__ir"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/#lima')) ?>">Ver la ficha <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </li>
      <li class="sede-fila" data-reveal="fade-rise">
        <p class="sede-fila__num indice">02</p><h3 class="sede-fila__nombre">Chiclayo</h3>
        <p class="sede-fila__meta">Diócesis de Chiclayo · Lambayeque y Santa Cruz</p>
        <p class="sede-fila__linea">Erigida el 17 de diciembre de 1956 con territorio de la arquidiócesis de Trujillo y de la diócesis de Cajamarca. Robert Prevost fue su obispo entre 2015 y 2023.</p>
        <p class="sede-fila__ir"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/#chiclayo')) ?>">Ver la ficha <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </li>
      <li class="sede-fila" data-reveal="fade-rise">
        <p class="sede-fila__num indice">03</p><h3 class="sede-fila__nombre">Cusco</h3>
        <p class="sede-fila__meta">Arquidiócesis del Cusco</p>
        <p class="sede-fila__linea">Una de las Iglesias más antiguas del continente, elevada al rango de arquidiócesis en 1943. El Señor de los Temblores es su Patrón Jurado.</p>
        <p class="sede-fila__ir"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/#cusco')) ?>">Ver la ficha <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </li>
      <li class="sede-fila" data-reveal="fade-rise">
        <p class="sede-fila__num indice">04</p><h3 class="sede-fila__nombre">Pucallpa</h3>
        <p class="sede-fila__meta">Vicariato Apostólico de Pucallpa · Ucayali</p>
        <p class="sede-fila__linea">Erigido el 2 de marzo de 1956 al dividirse el vicariato de Ucayali. Depende directamente de la Santa Sede y cubre más de 52.000 km² de Amazonía.</p>
        <p class="sede-fila__ir"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/#pucallpa')) ?>">Ver la ficha <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </li>
    </ol>
  </div>
</section>
</main>
