<?php
/**
 * Vista de la página «agenda».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Agenda del viaje apostólico de León XIV al Perú · Noviembre 2026',
    'descripcion' => 'El programa detallado del viaje apostólico aún no es público. Aquí está el estado actual, las cuatro sedes, qué suele incluir un viaje apostólico y cómo se anunciará.',
    'og_titulo'      => 'Agenda del viaje apostólico de León XIV al Perú',
    'og_descripcion' => 'Estado actual del programa, las cuatro sedes y cómo se anunciará el itinerario oficial.',
    'ruta'        => 'agenda/',
    'og_imagen'   => 'assets/img/og/og-agenda.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre"',
    'head_extra'  => '<script type="application/ld+json" nonce="' . $esc($sitio->nonce()) . '">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "inLanguage": "es",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Cuándo se sabrá el programa del viaje apostólico al Perú?",
      "acceptedAnswer": { "@type": "Answer", "text": "No hay fecha anunciada. El programa detallado de un viaje apostólico lo publica la Oficina de Prensa de la Santa Sede, habitualmente algunas semanas antes del viaje, y la Conferencia Episcopal Peruana lo difunde en el país. En cuanto sea oficial se publicará en esta página." }
    },
    {
      "@type": "Question",
      "name": "¿Habrá que inscribirse para asistir a los actos?",
      "acceptedAnswer": { "@type": "Answer", "text": "Por confirmar. Las condiciones de acceso a cada acto forman parte del programa oficial y todavía no se han publicado. No des por válida ninguna inscripción que no proceda de la Conferencia Episcopal Peruana o de tu diócesis." }
    },
    {
      "@type": "Question",
      "name": "¿Las Misas del Papa León XIV tendrán entrada?",
      "acceptedAnswer": { "@type": "Answer", "text": "Por confirmar. En otros viajes apostólicos algunas celebraciones han sido de acceso libre y otras han requerido pase por razones de aforo y seguridad. La decisión corresponde a los organizadores y se anunciará con el programa." }
    },
    {
      "@type": "Question",
      "name": "¿Se transmitirán los actos del viaje apostólico?",
      "acceptedAnswer": { "@type": "Answer", "text": "Los viajes apostólicos se transmiten habitualmente por los medios del Vaticano y por las emisoras y canales de la Iglesia en el país. Los enlaces concretos se publicarán en la sección Desde donde estés cuando existan." }
    },
    {
      "@type": "Question",
      "name": "¿Cómo llego a mi sede?",
      "acceptedAnswer": { "@type": "Answer", "text": "Las indicaciones de acceso, transporte y puntos de encuentro dependen de los recintos, que aún no se han anunciado. Se publicarán junto con el programa y con la guía del peregrino." }
    }
  ]
}
</script>',
];
?>
<?php
/* El contenido de esta página sale de la base y se edita desde el panel.
   Cada lectura lleva su texto de reserva: si la base no responde, o si
   alguien vacía un campo, la página se pinta con lo que decía antes.
   Una web sobre un viaje papal no puede quedarse muda porque falle MySQL. */
$paginaCms = $sitio->contenido('agenda');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
?>

<main id="contenido">

<!-- ══════════════ CABECERA DE PÁGINA ══════════════ -->
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/img-7.jpg 640w, ../assets/img/img-7.jpg 1024w, ../assets/img/img-7.jpg 1600w, ../assets/img/img-7.jpg 2200w">
      <img src="../assets/img/img-7.jpg" sizes="100vw" srcset="../assets/img/img-7.jpg 640w, ../assets/img/img-7.jpg 1024w, ../assets/img/img-7.jpg 1600w, ../assets/img/img-7.jpg 2200w" width="2200" height="943" alt="Celebración solemne presidida por el Papa León XIV" loading="lazy" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
    <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Agenda')) ?></span>
    <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Los días del encuentro')) ?></h1>
    <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'El Papa León XIV estará en el Perú del 11 al 16 de noviembre de 2026. El programa detallado todavía no es público: aquí está todo lo que sí se sabe, y nada de lo que no.')) ?></p>
    <nav class="migas" aria-label="Migas de pan">
      <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Agenda</span></li>
      </ol>
    </nav>
    </div>
  </div>
</header>

<!-- ══════════════ 1. ESTADO ACTUAL ══════════════ -->
<section class="seccion" aria-labelledby="t-estado">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-7">
        <header class="seccion__encabezado seccion__encabezado--mayor">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('sabe-hoy', 'rotulo', 'Actualizado el 13 de agosto de 2026')) ?></span>
          <h2 class="titular--mayor" id="t-estado" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('sabe-hoy', 'titulo', 'Qué se sabe hoy')) ?></span></span></h2>
        </header>

        <div class="estado-actual" data-reveal="fade-rise">
          <p class="estado-actual__frase">La agenda de la visita del Papa León XIV al Perú estará disponible próximamente.</p>
          <p class="estado-actual__meta">
            <span>Última actualización: 13 de agosto de 2026</span>
            <span>Fuente: Conferencia Episcopal Peruana</span>
          </p>
        </div>

        <div class="texto-lectura sep-m">
          <!-- COPY PENDIENTE DE VALIDACIÓN -->
          <p>Lo confirmado es el marco: seis días, cuatro sedes y un anuncio de la Santa Sede fechado el 5 de agosto de 2026. El viaje al Perú es la tercera etapa de la primera gira sudamericana del Santo Padre, después de Uruguay y Argentina.</p>
          <p>Todo lo demás —horarios, recintos, aforos y actos concretos— sigue sin publicarse. En esta página no encontrarás ninguna hora que la Santa Sede no haya hecho oficial. Si ves circular un programa por redes sociales sin fuente, desconfía.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ 2. LAS CUATRO VENTANAS ══════════════ -->
<section class="seccion" aria-labelledby="t-ventanas">
  <div class="contenedor">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('cuatro-ventanas', 'rotulo', 'Cuatro jurisdicciones')) ?></span>
      <h2 id="t-ventanas" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('cuatro-ventanas', 'titulo', 'Las cuatro ventanas')) ?></span></span></h2>
      <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>El viaje ocupa seis días completos. El reparto de esos días entre las cuatro sedes no se ha publicado, así que cada ciudad figura con la ventana entera y en estado pendiente.</p>
    </header>

    <ul class="ventanas">
      <li class="ventana" data-reveal="fade-rise">
        <p class="ventana__sede">Lima</p>
        <p class="ventana__franja">11–16 de noviembre de 2026</p>
        <p><span class="estado">Programa por confirmar</span> <span class="solo-lectores">Arquidiócesis de Lima.</span></p>
      </li>
      <li class="ventana" data-reveal="fade-rise">
        <p class="ventana__sede">Chiclayo</p>
        <p class="ventana__franja">11–16 de noviembre de 2026</p>
        <p><span class="estado">Programa por confirmar</span> <span class="solo-lectores">Diócesis de Chiclayo.</span></p>
      </li>
      <li class="ventana" data-reveal="fade-rise">
        <p class="ventana__sede">Cusco</p>
        <p class="ventana__franja">11–16 de noviembre de 2026</p>
        <p><span class="estado">Programa por confirmar</span> <span class="solo-lectores">Arquidiócesis del Cusco.</span></p>
      </li>
      <li class="ventana" data-reveal="fade-rise">
        <p class="ventana__sede">Pucallpa</p>
        <p class="ventana__franja">11–16 de noviembre de 2026</p>
        <p><span class="estado">Programa por confirmar</span> <span class="solo-lectores">Vicariato Apostólico de Pucallpa.</span></p>
      </li>
    </ul>

    <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('sedes/')) ?>">Conoce las cuatro sedes <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
  </div>
</section>

<!-- ══════════════ 3. QUÉ SUELE INCLUIR UN VIAJE APOSTÓLICO ══════════════ -->
<section class="seccion seccion--tinte" aria-labelledby="t-actos">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('suele-incluir-viaje', 'rotulo', 'No es el programa peruano')) ?></span>
      <h2 class="titular--mayor" id="t-actos" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('suele-incluir-viaje', 'titulo', '¿Qué suele incluir un viaje apostólico?')) ?></span></span></h2>
      <p><strong>Esto no es el programa peruano.</strong> Es la forma que suelen tomar los viajes apostólicos y sirve para hacerse una idea de qué esperar. Ninguno de estos actos está confirmado para el Perú.</p>
    </header>

    <ul class="actos">
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Ceremonia de bienvenida</h3>
        <p class="acto__texto">Al aterrizar. Es breve, protocolaria y suele celebrarse en el propio aeropuerto.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Encuentro con las autoridades civiles y el cuerpo diplomático</h3>
        <p class="acto__texto">El Santo Padre dirige un discurso sobre la vida del país que lo recibe. Es uno de los textos más citados de cada viaje.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Misa multitudinaria</h3>
        <p class="acto__texto">El acto central. Se celebra al aire libre, en explanadas o parques capaces de acoger a mucha gente.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Encuentro con los jóvenes</h3>
        <p class="acto__texto">Un momento propio, con un tono distinto al de las celebraciones litúrgicas.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Visita a una obra de caridad</h3>
        <p class="acto__texto">Un hospital, una casa de acogida, una cárcel o una comunidad en dificultad. Suele ser el acto más discreto y el más recordado.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Encuentro con los obispos, el clero y la vida consagrada</h3>
        <p class="acto__texto">A puerta cerrada o en una catedral, con quienes sostienen el día a día de la Iglesia local.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Oración mariana</h3>
        <p class="acto__texto">Ante la advocación más querida del lugar. En el Perú hay muchas, y muy arraigadas.</p>
      </li>
      <li class="acto" data-reveal="fade-rise">
        <h3 class="acto__titulo">Ceremonia de despedida</h3>
        <p class="acto__texto">Cierra el viaje, de nuevo en el aeropuerto, antes del vuelo de regreso o del traslado a la siguiente etapa.</p>
      </li>
    </ul>
  </div>
</section>

<!-- ══════════════ 4. CÓMO SE ANUNCIARÁ ══════════════ -->
<section class="seccion" aria-labelledby="t-anuncio">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-7">
        <header class="seccion__encabezado">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('como-cuando-anunciara', 'rotulo', 'Oficina de Prensa de la Santa Sede')) ?></span>
          <h2 id="t-anuncio" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('como-cuando-anunciara', 'titulo', 'Cómo y cuándo se anunciará el programa')) ?></span></span></h2>
        </header>

        <div class="texto-lectura">
          <!-- COPY PENDIENTE DE VALIDACIÓN -->
          <p>El programa detallado de un viaje apostólico lo publica la <strong>Oficina de Prensa de la Santa Sede</strong>. Es la fuente primaria: hasta que un acto aparece ahí, no existe oficialmente. Suele difundirse algunas semanas antes del viaje, en un documento con cada acto, su hora local y su sede.</p>
          <p>En el Perú, la <strong>Conferencia Episcopal Peruana</strong> difunde y adapta esa información: condiciones de acceso, transporte, puntos de encuentro y las indicaciones de cada diócesis. Las cuatro jurisdicciones implicadas —Lima, Chiclayo, Cusco y Pucallpa— publicarán además sus propias instrucciones.</p>
          <p>Cuando eso ocurra, esta página cambiará el mismo día: cada acto con su hora, su sede y su forma de acceso. Si quieres enterarte sin tener que volver a mirar, déjanos tu correo.</p>
        </div>

        <div class="aviso sep-l" data-reveal="fade-rise">
          <p class="aviso__titulo">Dónde mirar mientras tanto</p>
          <p>Sala de prensa de la Santa Sede · Conferencia Episcopal Peruana · esta misma página. Cualquier otro canal es de segunda mano.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ 5. AVISO POR CORREO ══════════════ -->
<section class="seccion" aria-labelledby="t-aviso">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-4 col-d-6">
        <header class="seccion__encabezado">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('avisame-cuando-publique', 'rotulo', 'Aviso')) ?></span>
          <h2 id="t-aviso">Avísame cuando se publique la agenda</h2>
        </header>

        <form class="pila" data-form="aviso" data-origen="agenda" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="aviso-correo-agenda">Correo electrónico</label>
            <input type="email" id="aviso-correo-agenda" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="aviso-consentimiento-agenda" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>"  >política de privacidad</a>. Usaremos tu correo únicamente para avisarte de la publicación del programa.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="sitio-web-agenda">No rellenar</label><input type="text" id="sitio-web-agenda" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div><button class="btn btn--secundario" type="submit">Avísame</button></div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ 6. PREGUNTAS FRECUENTES ══════════════ -->
<section class="seccion" aria-labelledby="t-faq">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">
        <header class="seccion__encabezado">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('mas-pregunta', 'rotulo', 'Cinco dudas')) ?></span>
          <h2 id="t-faq" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('mas-pregunta', 'titulo', 'Lo que más se pregunta')) ?></span></span></h2>
        </header>

        <div class="acordeon" data-acordeon>
          <div class="acordeon__item">
            <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="faq-1">¿Cuándo se sabrá el programa? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
            <div class="acordeon__panel" id="faq-1"><div>
              <p>No hay fecha anunciada. El programa lo publica la Oficina de Prensa de la Santa Sede, habitualmente algunas semanas antes del viaje, y la Conferencia Episcopal Peruana lo difunde en el país. En cuanto sea oficial, estará aquí.</p>
            </div></div>
          </div>
          <div class="acordeon__item">
            <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="faq-2">¿Habrá que inscribirse para asistir? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
            <div class="acordeon__panel" id="faq-2"><div>
              <p><strong>Por confirmar.</strong> Las condiciones de acceso a cada acto forman parte del programa oficial y todavía no se han publicado. No des por válida ninguna inscripción que no proceda de la Conferencia Episcopal Peruana o de tu diócesis.</p>
            </div></div>
          </div>
          <div class="acordeon__item">
            <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="faq-3">¿Las Misas tendrán entrada? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
            <div class="acordeon__panel" id="faq-3"><div>
              <p><strong>Por confirmar.</strong> En otros viajes apostólicos algunas celebraciones han sido de acceso libre y otras han requerido pase por razones de aforo y seguridad. La decisión corresponde a los organizadores y se anunciará con el programa.</p>
            </div></div>
          </div>
          <div class="acordeon__item">
            <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="faq-4">¿Se transmitirán los actos? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
            <div class="acordeon__panel" id="faq-4"><div>
              <p>Los viajes apostólicos se transmiten habitualmente por los medios del Vaticano y por las emisoras y canales de la Iglesia en el país. Los enlaces concretos se publicarán en <a href="<?= $esc($sitio->enlace('#desde-donde-estes')) ?>">Desde donde estés</a> cuando existan.</p>
            </div></div>
          </div>
          <div class="acordeon__item">
            <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="faq-5">¿Cómo llego a mi sede? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
            <div class="acordeon__panel" id="faq-5"><div>
              <p>Las indicaciones de acceso, transporte y puntos de encuentro dependen de los recintos, que aún no se han anunciado. Se publicarán junto con el programa y con la guía del peregrino.</p>
            </div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

</main>
