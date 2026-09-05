<?php
/**
 * Vista de la página «preguntas-frecuentes».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Preguntas frecuentes · Viaje de León XIV al Perú',
    'descripcion' => 'Fechas, sedes, cómo asistir, voluntariado y cómo seguir la visita del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.',
    'ruta'        => 'preguntas-frecuentes/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre"',
    'head_extra'  => '<script type="application/ld+json" nonce="' . $esc($sitio->nonce()) . '">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "inLanguage": "es",
  "mainEntity": [
    { "@type": "Question", "name": "¿Cuándo viene el Papa León XIV al Perú?", "acceptedAnswer": { "@type": "Answer", "text": "Del 11 al 16 de noviembre de 2026. Es la tercera etapa de su primera gira sudamericana, después de Uruguay y Argentina. La Santa Sede lo anunció el 5 de agosto de 2026." } },
    { "@type": "Question", "name": "¿Qué ciudades visitará?", "acceptedAnswer": { "@type": "Answer", "text": "Lima, Chiclayo, Cusco y Pucallpa. El orden del viaje y los días que corresponden a cada sede no se han publicado." } },
    { "@type": "Question", "name": "¿Por qué esas cuatro?", "acceptedAnswer": { "@type": "Answer", "text": "Son la capital y su arquidiócesis primada, la diócesis que el Santo Padre pastoreó entre 2015 y 2023, la Iglesia andina más antigua del país y un vicariato apostólico amazónico. Costa, sierra y selva." } },
    { "@type": "Question", "name": "¿Cuándo se sabrá el programa?", "acceptedAnswer": { "@type": "Answer", "text": "No hay fecha anunciada. El programa detallado lo publica la Oficina de Prensa de la Santa Sede, habitualmente algunas semanas antes del viaje, y la Conferencia Episcopal Peruana lo difunde en el país." } },
    { "@type": "Question", "name": "¿Habrá que inscribirse para asistir?", "acceptedAnswer": { "@type": "Answer", "text": "Por confirmar. Las condiciones de acceso forman parte del programa oficial y todavía no se han publicado. No des por válida ninguna inscripción que no proceda de la Conferencia Episcopal Peruana o de tu diócesis." } },
    { "@type": "Question", "name": "¿Las Misas tendrán entrada?", "acceptedAnswer": { "@type": "Answer", "text": "Por confirmar. En otros viajes apostólicos algunas celebraciones han sido de acceso libre y otras han requerido pase por razones de aforo y seguridad." } },
    { "@type": "Question", "name": "¿Cuánto cuesta asistir?", "acceptedAnswer": { "@type": "Answer", "text": "Nada. Si alguien te cobra por una entrada, por una inscripción o por un pase, no es oficial." } },
    { "@type": "Question", "name": "¿Qué llevo?", "acceptedAnswer": { "@type": "Answer", "text": "Agua, protección para el sol, calzado cómodo, tu documento de identidad y tus medicinas. La guía del peregrino lo detalla." } },
    { "@type": "Question", "name": "¿Cómo llego a mi sede?", "acceptedAnswer": { "@type": "Answer", "text": "Las indicaciones de acceso y transporte dependen de los recintos, que aún no se han anunciado. Lo más útil hoy es preguntar en tu parroquia, que en muchos casos organizará traslado en grupo." } },
    { "@type": "Question", "name": "¿Hay zona para personas con discapacidad?", "acceptedAnswer": { "@type": "Answer", "text": "En los viajes apostólicos suele reservarse una zona con acceso propio para personas con discapacidad y sus acompañantes. Por confirmar para este viaje." } },
    { "@type": "Question", "name": "¿Quién puede ser voluntario?", "acceptedAnswer": { "@type": "Answer", "text": "Hay seis servicios y un lugar para cada talento: resguardo y orden, acogida y hospitalidad, comunicación, logística, primeros auxilios y traducción e interpretación. La inscripción es la Fase 01 y se hace por internet." } },
    { "@type": "Question", "name": "¿Qué documentos me pedirán?", "acceptedAnswer": { "@type": "Answer", "text": "En la Fase 02 se solicitan carta de recomendación de un sacerdote, religioso o religiosa u obispo, declaración o certificado de antecedentes judiciales y penales, entrevista personal según necesidad y evaluación psicológica cuando sea posible. Nada de eso se sube a este sitio: la organización indicará el canal." } },
    { "@type": "Question", "name": "¿Puedo elegir el servicio?", "acceptedAnswer": { "@type": "Answer", "text": "Indicas tu preferencia al inscribirte. La asignación definitiva se comunica en la Fase 03, junto con la credencial." } },
    { "@type": "Question", "name": "¿Se transmitirán los actos?", "acceptedAnswer": { "@type": "Answer", "text": "Los viajes apostólicos se transmiten habitualmente por los medios de la Santa Sede y por las emisoras y canales de la Iglesia en el país. Los enlaces concretos se publicarán cuando existan." } },
    { "@type": "Question", "name": "¿Hay cuentas oficiales en redes sociales?", "acceptedAnswer": { "@type": "Answer", "text": "Todavía no. Cuando existan se anunciarán en este sitio. Cualquier cuenta que hoy diga representar la visita no es oficial." } },
    { "@type": "Question", "name": "¿Dónde consigo materiales para mi parroquia?", "acceptedAnswer": { "@type": "Answer", "text": "En la página de materiales de pastoral. Guía de oración, subsidio de catequesis, cantoral y banners, todo de descarga libre, conforme se vayan aprobando." } },
    { "@type": "Question", "name": "¿Quién publica esta web?", "acceptedAnswer": { "@type": "Answer", "text": "Este sitio informa del viaje apostólico y su responsable editorial es [RESPONSABLE POR CONFIRMAR]. El detalle está en el aviso legal." } },
    { "@type": "Question", "name": "¿Usa cookies?", "acceptedAnswer": { "@type": "Answer", "text": "No. Este sitio no instala cookies ni tiene analítica ni scripts de seguimiento. Solo usa almacenamiento del navegador para guardar el borrador del formulario de voluntariado y para no repetirte el aviso de la portada." } },
    { "@type": "Question", "name": "¿Qué hacen con mis datos?", "acceptedAnswer": { "@type": "Answer", "text": "Se usan únicamente para el fin por el que los diste: avisarte de una publicación, responder tu consulta o gestionar tu inscripción como voluntario. La política de privacidad lo detalla." } }
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
$paginaCms = $sitio->contenido('preguntas-frecuentes');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
?>

<main id="contenido">

<?php /* ── La portada de esta página ──────────────────────────────────────
         Sin foto, la franja es una banda roja lisa y compacta. En cuanto
         alguien elija una en el panel —Páginas → esta página → Cabecera—,
         la clase «--lisa» desaparece y la franja crece para mostrarla.

         Se quita la clase en lugar de dejarla siempre porque «--lisa» lleva
         min-height:0: con una fotografía dentro, la franja se colapsaría y
         la imagen no se vería. */ ?>
<?php $portada = $secciones['cabecera'] ?? []; ?>
<header class="cabecera-pagina<?= empty($portada['imagen_ruta']) ? ' cabecera-pagina--lisa' : '' ?>">
  <?php if (!empty($portada['imagen_ruta'])): ?>
    <div class="cabecera-pagina__media">
      <?= $sitio->imagen($portada, '', ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>
  <?php endif; ?>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Diecinueve respuestas')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Preguntas frecuentes')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Lo que más se pregunta, respondido con lo que hay. Cuando algo no está confirmado, aquí lo dice.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Preguntas frecuentes</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-faq-0">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('visita', 'rotulo', 'Preguntas')) ?></span>
      <h2 id="t-faq-0" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('visita', 'titulo', 'La visita')) ?></span></span></h2>
    </header>
    <div class="acordeon" data-acordeon>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-1">¿Cuándo viene el Papa León XIV al Perú? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-1"><div><p>Del 11 al 16 de noviembre de 2026. Es la tercera etapa de su primera gira sudamericana, después de Uruguay y Argentina. La Santa Sede lo anunció el 5 de agosto de 2026.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-2">¿Qué ciudades visitará? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-2"><div><p>Lima, Chiclayo, Cusco y Pucallpa. El orden del viaje y los días que corresponden a cada sede no se han publicado.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-3">¿Por qué esas cuatro? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-3"><div><p>Son la capital y su arquidiócesis primada, la diócesis que el Santo Padre pastoreó entre 2015 y 2023, la Iglesia andina más antigua del país y un vicariato apostólico amazónico. Costa, sierra y selva.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-4">¿Cuándo se sabrá el programa? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-4"><div><p>No hay fecha anunciada. El programa detallado lo publica la Oficina de Prensa de la Santa Sede, habitualmente algunas semanas antes del viaje, y la Conferencia Episcopal Peruana lo difunde en el país.</p></div></div>
      </div>
    </div>
  </div></div></div>
</section>

<section class="seccion seccion--tinte seccion--pastel" aria-labelledby="t-faq-4">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('asistir-actos', 'rotulo', 'Preguntas')) ?></span>
      <h2 id="t-faq-4" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('asistir-actos', 'titulo', 'Asistir a los actos')) ?></span></span></h2>
    </header>
    <div class="acordeon" data-acordeon>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-5">¿Habrá que inscribirse para asistir? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-5"><div><p>Por confirmar. Las condiciones de acceso forman parte del programa oficial y todavía no se han publicado. No des por válida ninguna inscripción que no proceda de la Conferencia Episcopal Peruana o de tu diócesis.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-6">¿Las Misas tendrán entrada? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-6"><div><p>Por confirmar. En otros viajes apostólicos algunas celebraciones han sido de acceso libre y otras han requerido pase por razones de aforo y seguridad.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-7">¿Cuánto cuesta asistir? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-7"><div><p>Nada. Si alguien te cobra por una entrada, por una inscripción o por un pase, no es oficial.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-8">¿Qué llevo? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-8"><div><p>Agua, protección para el sol, calzado cómodo, tu documento de identidad y tus medicinas. La guía del peregrino lo detalla.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-9">¿Cómo llego a mi sede? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-9"><div><p>Las indicaciones de acceso y transporte dependen de los recintos, que aún no se han anunciado. Lo más útil hoy es preguntar en tu parroquia, que en muchos casos organizará traslado en grupo.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-10">¿Hay zona para personas con discapacidad? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-10"><div><p>En los viajes apostólicos suele reservarse una zona con acceso propio para personas con discapacidad y sus acompañantes. Por confirmar para este viaje.</p></div></div>
      </div>
    </div>
  </div></div></div>
</section>

<section class="seccion" aria-labelledby="t-faq-10">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('voluntariado', 'rotulo', 'Preguntas')) ?></span>
      <h2 id="t-faq-10" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('voluntariado', 'titulo', 'Voluntariado')) ?></span></span></h2>
    </header>
    <div class="acordeon" data-acordeon>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-11">¿Quién puede ser voluntario? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-11"><div><p>Hay seis servicios y un lugar para cada talento: resguardo y orden, acogida y hospitalidad, comunicación, logística, primeros auxilios y traducción e interpretación. La inscripción es la Fase 01 y se hace por internet.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-12">¿Qué documentos me pedirán? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-12"><div><p>En la Fase 02 se solicitan carta de recomendación de un sacerdote, religioso o religiosa u obispo, declaración o certificado de antecedentes judiciales y penales, entrevista personal según necesidad y evaluación psicológica cuando sea posible. Nada de eso se sube a este sitio: la organización indicará el canal.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-13">¿Puedo elegir el servicio? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-13"><div><p>Indicas tu preferencia al inscribirte. La asignación definitiva se comunica en la Fase 03, junto con la credencial.</p></div></div>
      </div>
    </div>
  </div></div></div>
</section>

<section class="seccion seccion--tinte seccion--pastel" aria-labelledby="t-faq-13">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('seguir-visita', 'rotulo', 'Preguntas')) ?></span>
      <h2 id="t-faq-13" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('seguir-visita', 'titulo', 'Seguir la visita')) ?></span></span></h2>
    </header>
    <div class="acordeon" data-acordeon>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-14">¿Se transmitirán los actos? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-14"><div><p>Los viajes apostólicos se transmiten habitualmente por los medios de la Santa Sede y por las emisoras y canales de la Iglesia en el país. Los enlaces concretos se publicarán cuando existan.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-15">¿Hay cuentas oficiales en redes sociales? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-15"><div><p>Todavía no. Cuando existan se anunciarán en este sitio. Cualquier cuenta que hoy diga representar la visita no es oficial.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-16">¿Dónde consigo materiales para mi parroquia? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-16"><div><p>En la página de materiales de pastoral. Guía de oración, subsidio de catequesis, cantoral y banners, todo de descarga libre, conforme se vayan aprobando.</p></div></div>
      </div>
    </div>
  </div></div></div>
</section>

<section class="seccion" aria-labelledby="t-faq-16">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('este-sitio', 'rotulo', 'Preguntas')) ?></span>
      <h2 id="t-faq-16" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('este-sitio', 'titulo', 'Este sitio')) ?></span></span></h2>
    </header>
    <div class="acordeon" data-acordeon>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-17">¿Quién publica esta web? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-17"><div><p>Este sitio informa del viaje apostólico y su responsable editorial es [RESPONSABLE POR CONFIRMAR]. El detalle está en el aviso legal.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-18">¿Usa cookies? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-18"><div><p>No. Este sitio no instala cookies ni tiene analítica ni scripts de seguimiento. Solo usa almacenamiento del navegador para guardar el borrador del formulario de voluntariado y para no repetirte el aviso de la portada.</p></div></div>
      </div>
      <div class="acordeon__item">
        <h3><button class="acordeon__boton" type="button" aria-expanded="false" aria-controls="fq-19">¿Qué hacen con mis datos? <svg aria-hidden="true"><use href="#i-mas"/></svg></button></h3>
        <div class="acordeon__panel" id="fq-19"><div><p>Se usan únicamente para el fin por el que los diste: avisarte de una publicación, responder tu consulta o gestionar tu inscripción como voluntario. La política de privacidad lo detalla.</p></div></div>
      </div>
    </div>
  </div></div></div>
</section>
</main>
