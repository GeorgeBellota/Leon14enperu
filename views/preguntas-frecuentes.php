<?php
/**
 * ============================================================================
 *  Preguntas frecuentes — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. Una web sobre un viaje papal no puede quedarse
 *  muda porque falle la base.
 *
 *  Las medidas son las del editable Preguntas-frecuentes.ai (mesa de
 *  1440 x 3317). La hoja assets/css/paginas/preguntas-frecuentes.css las
 *  reproduce con la unidad --u. El editable de las subpáginas no dibuja la
 *  cabecera del sitio, así que todo su contenido cae 76 px más abajo que en
 *  la mesa de trabajo.
 *
 *  ── Cómo se administra ───────────────────────────────────────────────────
 *
 *  Cada grupo temático —«La visita», «Actividades»…— es una sección de
 *  plantilla «Texto con apartados», y cada pregunta es un apartado suyo:
 *  el titular del apartado es la pregunta y su texto, la respuesta. Así el
 *  editor añade, quita y reordena preguntas desde Páginas → Preguntas
 *  frecuentes sin tocar una línea de código. El límite de la plantilla son
 *  veinte apartados por grupo.
 *
 *  El acordeón es el componente .accordion de sistema.css: un
 *  button[aria-expanded] por pregunta y un panel que rediseno.js abre y
 *  cierra cambiando ese atributo. Sin JavaScript las respuestas quedan
 *  plegadas pero siguen en el HTML, así que el buscador —y el JSON-LD de
 *  aquí abajo— las ven igual.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$paginaCms = $sitio->contenido('preguntas-frecuentes');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);

/* ── Apagar un grupo sí; quedarse en blanco, no ───────────────────────────
   Un grupo que el panel apaga no llega en $secciones y no se pinta. Pero si
   la base entera no responde tampoco llega ninguno, y entonces «no llega»
   no quiere decir «apagado»: quiere decir que hay que salir con los textos
   de reserva. Se distingue por lo evidente: si no ha llegado NADA, es que
   falló la consulta, no que alguien apagó las cinco secciones. */
$sinBase = $secciones === [];
$pinta   = static fn (string $s): bool => $sinBase || $hay($s);

/* ── Los cuatro grupos del editable ───────────────────────────────────────
   En el mismo orden en que se ven. Cada uno lleva su clave en la base, el
   titular de reserva y las preguntas de reserva —que son las dieciséis del
   editable con la respuesta que hoy está publicada—. Si la migración aún no
   ha pasado, o si la base se cae, la página sale entera igualmente.

   Las claves son las que YA existían («visita», «asistir-actos»,
   «voluntariado», «este-sitio»): se conservan para no perder el historial
   ni el SEO de esas secciones, aunque dos de ellas cambien de titular. */
$grupos = [
    [
        'clave'  => 'visita',
        'titulo' => 'La visita',
        'reserva' => [
            ['titulo' => '¿Cuándo viene el Papa León XIV al Perú?', 'texto' => 'Del 11 al 16 de noviembre de 2026. Es la tercera etapa de su primera gira sudamericana, después de Uruguay y Argentina. La Santa Sede lo anunció el 5 de agosto de 2026.'],
            ['titulo' => '¿Qué ciudades visitará?',                 'texto' => 'Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa. El orden del viaje y los días que corresponden a cada sede no se han publicado.'],
            ['titulo' => '¿Por qué esas seis?',                      'texto' => 'Son la capital y su arquidiócesis primada, con la provincia del Callao; la diócesis que el Santo Padre pastoreó entre 2015 y 2023, con el pueblo de Santa Cruz; la Iglesia andina más antigua del país y un vicariato apostólico amazónico. Costa, sierra y selva.'],
            ['titulo' => '¿Cuándo se sabrá el programa?',            'texto' => 'No hay fecha anunciada. El programa detallado lo publica la Oficina de Prensa de la Santa Sede, habitualmente algunas semanas antes del viaje, y la Conferencia Episcopal Peruana lo difunde en el país.'],
            ['titulo' => '¿Se transmitirán los actos?',              'texto' => 'Los viajes apostólicos se transmiten habitualmente por los medios de la Santa Sede y por las emisoras y canales de la Iglesia en el país. Los enlaces concretos se publicarán cuando existan.'],
            ['titulo' => '¿Hay cuentas oficiales en redes sociales?', 'texto' => 'Todavía no. Cuando existan se anunciarán en este sitio. Cualquier cuenta que hoy diga representar la visita no es oficial.'],
            ['titulo' => '¿Dónde consigo materiales para mi parroquia?', 'texto' => 'En la página de materiales de pastoral. Guía de oración, subsidio de catequesis, cantoral y banners, todo de descarga libre, conforme se vayan aprobando.'],
        ],
    ],
    [
        'clave'  => 'asistir-actos',
        'titulo' => 'Actividades',
        'reserva' => [
            ['titulo' => '¿Habrá que inscribirse para asistir?', 'texto' => 'Por confirmar. Las condiciones de acceso forman parte del programa oficial y todavía no se han publicado. No des por válida ninguna inscripción que no proceda de la Conferencia Episcopal Peruana o de tu diócesis.'],
            ['titulo' => '¿Las misas tendrán entradas?',          'texto' => 'Por confirmar. En otros viajes apostólicos algunas celebraciones han sido de acceso libre y otras han requerido pase por razones de aforo y seguridad.'],
            ['titulo' => '¿Los encuentros son gratuitos?',        'texto' => 'Sí. Todos los actos de la Visita Apostólica son gratuitos. Si alguien te cobra por una entrada, por una inscripción o por un pase, no es oficial.'],
            ['titulo' => '¿Qué debo llevar?',                     'texto' => 'Agua, protección para el sol, calzado cómodo, tu documento de identidad y tus medicinas. La guía del peregrino lo detalla.'],
        ],
    ],
    [
        'clave'  => 'voluntariado',
        'titulo' => 'Voluntariado',
        'reserva' => [
            ['titulo' => '¿Quién puede ser voluntario?',  'texto' => 'Hay seis servicios y un lugar para cada talento: resguardo y orden, acogida y hospitalidad, comunicación, logística, primeros auxilios y traducción e interpretación. La inscripción es la Fase 01 y se hace por internet.'],
            ['titulo' => '¿Qué documentos me pedirán?',   'texto' => 'En la Fase 02 se solicitan carta de recomendación de un sacerdote, religioso o religiosa u obispo, declaración o certificado de antecedentes judiciales y penales, entrevista personal según necesidad y evaluación psicológica cuando sea posible. Nada de eso se sube a este sitio: la organización indicará el canal.'],
            ['titulo' => '¿Puedo elegir el servicio?',    'texto' => 'Indicas tu preferencia al inscribirte. La asignación definitiva se comunica en la Fase 03, junto con la credencial.'],
            ['titulo' => '¿Qué hacen con mis datos?',     'texto' => 'Se usan únicamente para el fin por el que los diste: avisarte de una publicación, responder tu consulta o gestionar tu inscripción como voluntario. La política de privacidad lo detalla.'],
        ],
    ],
    [
        'clave'  => 'este-sitio',
        'titulo' => 'Sobre la web',
        'reserva' => [
            ['titulo' => '¿Usa cookies?', 'texto' => 'No. Este sitio no instala cookies ni tiene analítica ni scripts de seguimiento. Solo usa almacenamiento del navegador para guardar el borrador del formulario de voluntariado y para no repetirte el aviso de la portada.'],
        ],
    ],
];

/* Lo que pedía el cliente: que una pregunta nunca se quede sin nada que
   enseñar. Si alguien crea el apartado en el panel y todavía no ha escrito
   la respuesta, el panel se abre con esta frase en lugar de vacío. */
$respuestaPendiente = 'Estamos preparando esta respuesta. En cuanto haya información confirmada, se publicará aquí.';

/* ── Se resuelve el contenido antes de pintar ─────────────────────────────
   Se hace en dos pasadas porque el JSON-LD del <head> tiene que decir
   exactamente lo mismo que el acordeón: si se generase aparte, un cambio en
   el panel dejaría a Google con las respuestas viejas. */
$gruposPintados = [];

foreach ($grupos as $grupo) {
    if (!$pinta($grupo['clave'])) {
        continue;
    }

    $apartados = [];

    foreach ($bloques($grupo['clave'], $grupo['reserva']) as $apartado) {
        $pregunta = trim((string) ($apartado['titulo'] ?? ''));

        // Un apartado sin pregunta no tiene botón que pintar.
        if ($pregunta === '') {
            continue;
        }

        $respuesta = trim((string) ($apartado['texto'] ?? ''));

        $apartados[] = [
            'pregunta'  => $pregunta,
            'respuesta' => $respuesta === '' ? $respuestaPendiente : $respuesta,
        ];
    }

    if ($apartados === []) {
        continue;
    }

    $gruposPintados[] = [
        'titulo'    => $campo($grupo['clave'], 'titulo', $grupo['titulo']),
        'apartados' => $apartados,
    ];
}

/* ── El JSON-LD de preguntas frecuentes ───────────────────────────────────
   Sale del mismo contenido que se acaba de resolver, así que no hay forma de
   que se desincronice con lo que ve el visitante. Se escribe con json_encode
   y con las etiquetas en hexadecimal: una respuesta que llevara «</script>»
   escrito no podría cerrar el bloque. */
$fichaFaq = '';

if ($gruposPintados !== []) {
    $preguntas = [];

    foreach ($gruposPintados as $grupo) {
        foreach ($grupo['apartados'] as $apartado) {
            $preguntas[] = [
                '@type'          => 'Question',
                'name'           => $apartado['pregunta'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $apartado['respuesta']],
            ];
        }
    }

    $ficha = json_encode(
        [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'inLanguage' => 'es',
            'mainEntity' => $preguntas,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT
    );

    if (is_string($ficha)) {
        $fichaFaq = '<script type="application/ld+json" nonce="' . $esc($sitio->nonce()) . '">'
                  . "\n" . $ficha . "\n</script>";
    }
}

$meta = [
    'titulo'      => 'Preguntas frecuentes · Viaje de León XIV al Perú',
    'descripcion' => 'Fechas, sedes, cómo asistir, voluntariado y cómo seguir la visita del Papa '
                   . 'León XIV al Perú, del 11 al 16 de noviembre de 2026.',
    'ruta'        => 'preguntas-frecuentes/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
    'head_extra'  => $fichaFaq,
];
?>

<main id="contenido">

  <?php /* ══════════════════════════════════════════════════════ HÉROE ════
       Banda duotono de 582 px con la fotografía al fondo. La foto y el
       titular se editan en Páginas → Preguntas frecuentes → Cabecera.

       El editable NO dibuja rótulo ni bajada sobre el titular: sólo las dos
       líneas «Preguntas / frecuentes». Los dos campos siguen guardados en la
       base —y editables— pero el diseño nuevo no los pinta.

       Del panel llega un titular de una sola línea. El editable lo parte en
       dos cuerpos distintos (Neulis Bold 92,59 y Neulis Regular 81,1), así
       que se corta por el primer espacio: la primera palabra arriba y el
       resto abajo. Con un titular de una sola palabra, la segunda línea
       simplemente no se pinta. */ ?>
  <?php
  $tituloHero = $campo('cabecera', 'titulo', 'Preguntas frecuentes');
  $lineas     = preg_split('/\s+/u', $tituloHero, 2) ?: [$tituloHero];
  ?>
  <section class="hero hero--page pf-hero">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/preguntas-frecuentes/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/preguntas-frecuentes/hero.jpg')) ?>"
             alt="El Papa León XIV bendice a los obispos reunidos en audiencia en el Vaticano"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="container hero__inner">
      <h1 class="hero__title pf-hero__title">
        <span class="pf-hero__t1"><?= $esc($lineas[0]) ?></span>
        <?php if (isset($lineas[1]) && $lineas[1] !== ''): ?>
          <span class="pf-hero__t2"><?= $esc($lineas[1]) ?></span>
        <?php endif; ?>
      </h1>
    </div>
  </section>

  <?php /* ══════════════════════════════════════════════════ PREGUNTAS ════
       Un grupo por sección y una pregunta por apartado. Las clases pf-g1 a
       pf-g4 llevan la separación exacta que el editable deja entre grupos;
       se reparten por posición, no por clave, para que apagar un grupo en el
       panel no descuadre los que quedan. Si alguien crea un quinto grupo,
       hereda la separación del cuarto, que es la mayor del editable. */ ?>
  <section class="pf-contenido" aria-label="Preguntas frecuentes">
    <div class="pf-wrap">

      <?php $n = 0; /* numerador global: los identificadores del acordeón no
                       pueden repetirse entre grupos */ ?>
      <?php foreach ($gruposPintados as $i => $grupo): ?>
        <?php $clase = 'pf-g' . min($i + 1, 4); ?>
        <section class="pf-grupo <?= $esc($clase) ?>" aria-labelledby="pf-t<?= $i + 1 ?>">
          <h2 class="pf-grupo__h head-rule head-rule--wine" id="pf-t<?= $i + 1 ?>"><?= $esc($grupo['titulo']) ?></h2>

          <ul class="accordion pf-lista">
            <?php foreach ($grupo['apartados'] as $apartado): ?>
              <?php $n++; ?>
              <li class="accordion__item">
                <button class="accordion__btn" type="button" id="pf-b<?= $n ?>" aria-expanded="false" aria-controls="pf-r<?= $n ?>">
                  <span class="pf-pregunta"><?= $esc($apartado['pregunta']) ?></span>
                  <span class="accordion__icon" aria-hidden="true"></span>
                </button>
                <div class="accordion__panel" id="pf-r<?= $n ?>" role="region" aria-labelledby="pf-b<?= $n ?>">
                  <?php /* El campo «texto» del apartado es texto plano, no
                           HTML: el salto de línea que se escriba en el panel
                           se respeta con nl2br y nada más pasa. */ ?>
                  <div><p class="pf-respuesta"><?= nl2br($esc($apartado['respuesta'])) ?></p></div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>

    </div>
  </section>

</main>
