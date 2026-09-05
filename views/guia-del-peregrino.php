<?php
/**
 * Vista de la página «guia-del-peregrino».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Guía del peregrino · Viaje de León XIV al Perú',
    'descripcion' => 'Qué llevar, cómo prepararte y qué esperar de las celebraciones del viaje apostólico. Los detalles de acceso se publicarán con el programa oficial.',
    'ruta'        => 'guia-del-peregrino/',
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
$paginaCms = $sitio->contenido('guia-del-peregrino');
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-peregrino-640.webp 640w, ../assets/img/fotos/cab-peregrino-1024.webp 1024w, ../assets/img/fotos/cab-peregrino-1600.webp 1600w, ../assets/img/fotos/cab-peregrino-1920.webp 1920w">
      <img src="../assets/img/fotos/cab-peregrino-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-peregrino-640.jpg 640w, ../assets/img/fotos/cab-peregrino-1024.jpg 1024w, ../assets/img/fotos/cab-peregrino-1600.jpg 1600w, ../assets/img/fotos/cab-peregrino-1920.jpg 1920w" width="1920" height="823" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Para quien va a ir')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Guía del peregrino')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Lo que sí puedes preparar desde ya, y lo que habrá que esperar. Ninguna de estas indicaciones sustituye a las de tu diócesis.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Guía del peregrino</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-estado-guia">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('esta-confirmado', 'rotulo', 'Antes de nada')) ?></span>
      <h2 class="titular--mayor" id="t-estado-guia" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('esta-confirmado', 'titulo', 'Qué está confirmado y qué no')) ?></span></span></h2>
    </header>
        <div class="estado-actual" data-reveal="fade-rise">
          <p class="estado-actual__frase">Los recintos, los horarios y las condiciones de acceso no se han publicado.</p>
          <p class="estado-actual__meta"><span>Última actualización: 13 de agosto de 2026</span></p>
        </div>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Esta guía no inventa logística. Lo que encontrarás aquí son las cosas que puedes preparar sin saber todavía dónde ni a qué hora será cada acto: el equipaje, la salud, los niños, la accesibilidad y, sobre todo, la preparación interior.</p>
        <p>En cuanto la Oficina de Prensa de la Santa Sede publique el programa y la Conferencia Episcopal Peruana difunda las indicaciones de acceso, esta página se completará con los recintos, los accesos por puerta, los horarios de apertura y el transporte.</p>
      </div>
      </div>
    </div>
  </div>
</section>

<section class="seccion seccion--tinte" aria-labelledby="t-llevar" id="que-llevar">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('llevar', 'rotulo', 'Equipaje')) ?></span>
      <h2 class="titular--mayor" id="t-llevar" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('llevar', 'titulo', 'Qué llevar')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Las celebraciones multitudinarias son largas y casi siempre al aire libre. Lo que llevas encima marca la diferencia entre un buen día y un mal recuerdo.</p>
    </header>
    <ul class="actos">
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Agua y algo de comer</h3><p class="acto__texto">Botella reutilizable y comida ligera. Las esperas empiezan mucho antes de la hora del acto.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Protección del sol</h3><p class="acto__texto">Gorra o sombrero, bloqueador y una prenda ligera de manga larga. En Lima y Chiclayo el sol de noviembre pega, aunque el cielo esté cubierto.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Abrigo para el Cusco</h3><p class="acto__texto">A tres mil cuatrocientos metros la temperatura cae en cuanto se va el sol. Una casaca no sobra.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Calzado cerrado y cómodo</h3><p class="acto__texto">Vas a caminar y a estar de pie muchas horas. No es el día de estrenar zapatos.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Documento de identidad</h3><p class="acto__texto">Llévalo siempre. Si finalmente hacen falta pases o acreditaciones, te los pedirán con el DNI.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Tus medicinas</h3><p class="acto__texto">Si tomas algo a diario, llévalo contigo en cantidad suficiente para todo el día.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Batería y punto de encuentro</h3><p class="acto__texto">La cobertura se satura en las concentraciones grandes. Acuerda con los tuyos un punto físico de reunión antes de entrar.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Poco equipaje</h3><p class="acto__texto">Mochilas grandes, sombrillas y sillas suelen estar restringidas en este tipo de actos. <span class="estado">Por confirmar</span></p></li>
    </ul>
  </div>
</section>

<section class="seccion mancha mancha--sup-der" aria-labelledby="t-corazon">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('preparar-corazon', 'rotulo', 'Lo importante')) ?></span>
      <h2 class="titular--mayor" id="t-corazon" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('preparar-corazon', 'titulo', 'Preparar el corazón')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>No se peregrina solo con los pies. Las semanas previas son parte de la visita, y lo que ocurra en ellas se nota el día del encuentro.</p>
        <p><strong>Vuelve a la oración en familia.</strong> Un rato corto y a la misma hora vale más que un propósito largo que no se sostiene.</p>
        <p><strong>Acércate al sacramento de la reconciliación.</strong> Tu parroquia tendrá horarios reforzados conforme se acerquen las fechas.</p>
        <p><strong>Reconcíliate con quien tengas pendiente.</strong> Es lo más difícil de la lista y lo único que no se puede improvisar el día antes.</p>
        <p><strong>Visita a quien está solo.</strong> Un enfermo, un vecino mayor, alguien de la comunidad que ya no puede salir. Muchos de ellos no podrán ir; llévales tú el encuentro.</p>
      </div>
        <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('materiales/')) ?>">Materiales de oración para preparar la visita <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </div>
    </div>
  </div>
</section>

<section class="seccion seccion--tinte seccion--pastel-acento" aria-labelledby="t-cuidados" id="accesibilidad">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('mayores-ninos-accesibilidad', 'rotulo', 'Cuidados')) ?></span>
      <h2 class="titular--mayor" id="t-cuidados" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('mayores-ninos-accesibilidad', 'titulo', 'Mayores, niños y accesibilidad')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Tres situaciones que conviene pensar antes y no sobre la marcha.</p>
    </header>
    <div class="reticula">
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise">
        <h3 class="tarjeta__titulo">Si vas con niños</h3>
        <p class="tarjeta__texto">Escríbeles tu número de teléfono en una pulsera o en el brazo. Acuerda con ellos qué hacer si se pierden: quedarse quietos y buscar a un voluntario. Los voluntarios llevarán indumentaria identificable.</p>
      </article>
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise">
        <h3 class="tarjeta__titulo">Si vas con una persona mayor</h3>
        <p class="tarjeta__texto">Calcula el doble de tiempo para todo. Lleva agua, una silla plegable si estuviera permitida y sus medicinas. Pregunta en tu parroquia si organiza traslado en grupo.</p>
      </article>
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise">
        <h3 class="tarjeta__titulo">Accesibilidad</h3>
        <p class="tarjeta__texto">En los viajes apostólicos suele reservarse una zona para personas con discapacidad y sus acompañantes, con acceso propio. <span class="estado">Por confirmar</span></p>
      </article>
    </div>
  </div>
</section>

<section class="seccion" aria-labelledby="t-llegar" id="como-llegar">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('como-llegar-tu', 'rotulo', 'Transporte')) ?></span>
      <h2 class="titular--mayor" id="t-llegar" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('como-llegar-tu', 'titulo', 'Cómo llegar a tu sede')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Las indicaciones de acceso, transporte y puntos de encuentro dependen de los recintos, y los recintos todavía no se han anunciado. En cuanto se publiquen, aquí encontrarás para cada sede el punto de acceso, los cortes de tránsito previstos y las rutas recomendadas.</p>
        <p>Mientras tanto, lo más útil que puedes hacer es <strong>preguntar en tu parroquia</strong>. Muchas organizan traslado en grupo, y ese suele ser el modo más ordenado de llegar y de volver.</p>
      </div>
        <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/')) ?>">Ver las cuatro sedes <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
      </div>
    </div>
  </div>
</section>

<section class="seccion seccion--tinte" aria-labelledby="t-aviso-guia">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('te-avisamos-cuando', 'rotulo', 'Aviso')) ?></span>
      <h2 id="t-aviso-guia" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('te-avisamos-cuando', 'titulo', 'Te avisamos cuando esté la guía completa')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>En cuanto se publiquen recintos y accesos, completamos esta página y te escribimos.</p>
    </header>
        <form class="pila" data-form="aviso" data-origen="guia" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="correo-guia">Correo electrónico</label>
            <input type="email" id="correo-guia" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="consent-guia" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="web-guia">No rellenar</label><input type="text" id="web-guia" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div><button class="btn btn--secundario" type="submit">Avísame</button></div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
      </div>
    </div>
  </div>
</section>
</main>
