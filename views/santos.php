<?php
/**
 * ============================================================================
 *  Santos del Perú — «Cinco Santos, un mismo corazón» (rediseño 2026).
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  ── De «tierra-de-santos» a «santos» ─────────────────────────────────────
 *
 *  La página existía con la clave «tierra-de-santos» y dos secciones:
 *  «cabecera» y «los-cinco-santos». Las dos se conservan con su clave —así no
 *  se pierden ni su contenido ni su historial— y la dirección antigua responde
 *  con un 301 desde Rutas::mudanzas().
 *
 *  ── Por qué se pintan cinco fichas y no una ──────────────────────────────
 *
 *  El editable SANTOS.ai dibuja el héroe y UNA sola ficha, la de Santo Toribio
 *  de Mogrovejo, aunque el titular anuncie cinco santos. La base sí tiene la
 *  colección completa en la sección «los-cinco-santos» (plantilla «personas»),
 *  así que la lista sale de ahí y cada santo se pinta con la forma que el
 *  editable dibujó para la ficha: filete y nombre, la línea de virtudes, la
 *  banda arena y el cuerpo de texto a la izquierda; el retrato, a la derecha.
 *
 *  Cada ficha lleva el `slug` como ancla, que es el mismo con el que
 *  index.php resuelve /santos/<slug>/ contra views/detalle.php.
 *
 *  ── Reservas ─────────────────────────────────────────────────────────────
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. Una web sobre un viaje papal no puede quedarse
 *  muda porque falle la base.
 *
 *  Las medidas son las del editable SANTOS.ai (mesa de 1440 px). La hoja
 *  assets/css/paginas/santos.css las reproduce con la unidad --u.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Cinco Santos, un mismo corazón · Santos del Perú · León XIV en el Perú',
    'descripcion' => 'Los santos del Perú: cinco caminos distintos y una misma llamada, vivir la fe y '
                   . 'hacerla visible en la vida cotidiana. Santa Rosa de Lima, San Martín de Porres, '
                   . 'San Juan Macías, San Francisco Solano y Santo Toribio de Mogrovejo.',
    'ruta'        => 'santos/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
];

$paginaCms = $sitio->contenido('santos');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       Banda duotono de 582 px con la fotografía ya compuesta. La foto se
       cambia en Páginas → Santos del Perú → Cabecera de página. */ ?>
  <section class="hero hero--page santos-hero">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/santos/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/santos/hero.jpg')) ?>"
             alt="Bendición del tapiz con las imágenes de los santos del Perú durante una celebración de la Conferencia Episcopal Peruana"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="container hero__inner">
      <?php
      /* El titular va en dos líneas de cuerpo y peso distintos. Del panel
         llega una sola cadena: se parte por la coma, que es donde el editable
         lo rompe. Es el mismo criterio que en la portada. */
      $tituloHero = $campo('cabecera', 'titulo', 'Cinco Santos, un mismo corazón');
      $partesHero = preg_split('/,\s*/u', $tituloHero, 2) ?: [$tituloHero];
      ?>
      <h1 class="hero__title santos-hero__titulo">
        <span class="santos-hero__t1"><?= $esc($partesHero[0] . (isset($partesHero[1]) ? ',' : '')) ?></span>
        <?php if (isset($partesHero[1])): ?>
          <span class="santos-hero__t2"><?= $esc($partesHero[1]) ?></span>
        <?php endif; ?>
      </h1>

      <?php
      /* La bajada del editable enuncia y remata: la primera mitad en fino y
         la segunda en seminegrita. El corte está en los dos puntos, así que
         se parte por ahí. Si el panel escribe una frase sin dos puntos, sale
         entera en fino y no pasa nada. */
      $bajadaHero = $campo(
          'cabecera',
          'texto',
          'Son cinco caminos distintos, pero una misma llamada: vivir la fe y hacerla visible en la vida cotidiana.'
      );
      $partesBajada = preg_split('/:\s+/u', $bajadaHero, 2) ?: [$bajadaHero];
      ?>
      <p class="hero__sub santos-hero__sub">
        <?= $esc($partesBajada[0] . (isset($partesBajada[1]) ? ': ' : '')) ?>
        <?php if (isset($partesBajada[1])): ?><strong><?= $esc($partesBajada[1]) ?></strong><?php endif; ?>
      </p>
    </div>
  </section>

  <?php /* ══════════════════════════════════════════════ LOS CINCO SANTOS ══
       Cada bloque de la sección «los-cinco-santos» es una ficha. Se editan
       en Páginas → Santos del Perú → Los cinco santos.

       La sección no se condiciona a Sitio::activa(): esa comprobación también
       da «no» cuando la base no responde, y entonces la página se quedaría en
       un héroe suelto. Con la reserva de abajo siempre hay cinco fichas. */ ?>
  <?php
  /* ── La reserva ──────────────────────────────────────────────────────
     Los cinco santos con lo que el editable y la base tienen escrito hoy.
     Hace dos trabajos: es la lista que se pinta si la base no responde, y
     es de donde sale el retrato de cada santo mientras nadie elija otro en
     el panel. Por eso está indexada por `slug`: los bloques que llegan de
     la base traen el suyo y encuentran aquí su fotografía.

     Va entera y no a medias a propósito. Con las fichas vacías la página se
     quedaba en cinco nombres sueltos al lado de cinco retratos de 800 px, y
     un fallo de MySQL no puede convertir la página de los santos del Perú en
     una galería sin texto.

     Santo Toribio es el único que lleva aquí el texto del EDITABLE, que es
     distinto del de la base: el editable lo presenta con la cita de León XIV
     y la base, con los 300 años de su canonización. Manda el de la base —va
     marcado «@bd» en el JSON de contenido—, y el del editable queda como
     reserva para no perderlo.

     El editable sólo dibujó el retrato de Santo Toribio (santos/p01); los
     otros cuatro reutilizan los que ya usa la portada. */
  $reservaSantos = [
      'santa-rosa-de-lima' => [
          'slug'   => 'santa-rosa-de-lima',
          'titulo' => 'Santa Rosa de Lima',
          'rotulo' => 'Oración · Entrega · Servicio',
          'texto'  => '<p>Isabel Flores de Oliva, conocida como Santa Rosa de Lima, nació en Lima en 1586 y '
                    . 'dedicó su vida a Dios desde una profunda experiencia de oración y entrega. Vivió como laica '
                    . 'consagrada y perteneció a la Tercera Orden de Santo Domingo.</p>'
                    . '<p>En medio de una vida sencilla, hizo del servicio a los pobres y enfermos una expresión '
                    . 'concreta de su amor a Dios. Atendía a personas necesitadas y convirtió parte de su propia '
                    . 'casa en un espacio de acogida para quienes sufrían.</p>'
                    . '<p>Murió en Lima en 1617, a los 31 años. Fue canonizada por el papa Clemente X el 12 de '
                    . 'abril de 1671, convirtiéndose en la primera santa de América. Es patrona del Perú, de '
                    . 'América y de Filipinas.</p>',
          'datos'  => ['anios' => '1586 – 1617', 'resumen' => 'Una vida entregada a Dios y al servicio de los más necesitados.'],
          'carpeta' => 'index', 'img' => 'p02', 'w' => 491, 'h' => 761,
          'alt'     => 'Retrato de Santa Rosa de Lima',
      ],
      'san-martin-de-porres' => [
          'slug'   => 'san-martin-de-porres',
          'titulo' => 'San Martín de Porres',
          'rotulo' => 'Caridad · Humildad · Fraternidad',
          'texto'  => '<p>Martín de Porres nació en Lima en 1579. Creció en una sociedad marcada por profundas '
                    . 'diferencias sociales y raciales, y desde joven mostró una especial sensibilidad hacia los '
                    . 'pobres y enfermos.</p>'
                    . '<p>Ingresó al Convento del Santísimo Rosario de los dominicos de Lima, donde desarrolló '
                    . 'diferentes labores de servicio antes de convertirse en fraile. Su servicio no hizo '
                    . 'distinciones: atendía a personas de todas las condiciones y manifestó un especial amor por '
                    . 'los animales y por toda la creación.</p>'
                    . '<p>La tradición lo recuerda como el «Santo de la escoba», imagen que expresa su humildad. '
                    . 'Fue canonizado por el papa Juan XXIII el 6 de mayo de 1962.</p>',
          'datos'  => ['anios' => '1579 – 1639', 'resumen' => 'Hizo del servicio humilde un camino de santidad.'],
          'carpeta' => 'index', 'img' => 'p03', 'w' => 484, 'h' => 750,
          'alt'     => 'Retrato de San Martín de Porres',
      ],
      'san-juan-macias' => [
          'slug'   => 'san-juan-macias',
          'titulo' => 'San Juan Macías',
          'rotulo' => 'Misericordia · Servicio · Solidaridad',
          'texto'  => '<p>Juan Macías nació en Ribera del Fresno, España, en 1585. Huérfano desde muy joven, '
                    . 'trabajó como pastor antes de emigrar a América. Llegó al Perú y se estableció en Lima, '
                    . 'donde ingresó a la Orden de Predicadores.</p>'
                    . '<p>Durante más de dos décadas fue hermano portero del convento dominico de La Magdalena. '
                    . 'Desde ese lugar desarrolló una intensa labor de ayuda a los pobres: su servicio comenzaba '
                    . 'en la puerta del convento y se extendía mediante la distribución de alimentos y limosnas.</p>'
                    . '<p>Su amistad con San Martín de Porres es otro elemento importante de la historia de la '
                    . 'santidad limeña. Fue canonizado por Pablo VI el 28 de septiembre de 1975.</p>',
          'datos'  => ['anios' => '1585 – 1645', 'resumen' => 'Desde la sencillez, hizo de la misericordia una forma de vida.'],
          'carpeta' => 'index', 'img' => 'p04', 'w' => 491, 'h' => 734,
          'alt'     => 'Retrato de San Juan Macías',
      ],
      'san-francisco-solano' => [
          'slug'   => 'san-francisco-solano',
          'titulo' => 'San Francisco Solano',
          'rotulo' => 'Misión · Encuentro · Evangelización',
          'texto'  => '<p>Francisco Solano nació en Montilla, España, en 1549. Ingresó a la Orden Franciscana y '
                    . 'fue ordenado sacerdote en 1576. Llegó a América en 1589 y desembarcó en Paita, Piura.</p>'
                    . '<p>Desde allí emprendió un largo recorrido por territorios del actual Perú y otros países '
                    . 'de Sudamérica. Aprendió lenguas indígenas para comunicarse con los pueblos a los que servía '
                    . 'y utilizó también la música como instrumento de evangelización.</p>'
                    . '<p>Su vida se vincula especialmente con la idea de una Iglesia en salida: caminar, '
                    . 'encontrarse con las personas y llevar el Evangelio allí donde se encuentran. Murió en Lima '
                    . 'el 14 de julio de 1610.</p>',
          'datos'  => ['anios' => '1549 – 1610', 'resumen' => 'Caminó grandes distancias para llevar el Evangelio al encuentro de los pueblos.'],
          'carpeta' => 'index', 'img' => 'p05', 'w' => 352, 'h' => 546,
          'alt'     => 'Retrato de San Francisco Solano',
      ],
      'santo-toribio-de-mogrovejo' => [
          'slug'   => 'santo-toribio-de-mogrovejo',
          'titulo' => 'Santo Toribio de Mogrovejo',
          'rotulo' => 'Pastor · Misión · Defensa de los pueblos',
          /* El único cuerpo de texto que el editable llegó a componer. */
          'texto'  => '<p>Nacido en Mayorga, España, en 1538, Toribio de Mogrovejo llegó al Perú en 1581 como '
                    . 'segundo arzobispo de Lima. Concibió su ministerio como una misión al servicio de todos, '
                    . 'especialmente de los más alejados y vulnerables. Aprendió quechua para acercarse a las '
                    . 'comunidades y recorrió durante años la enorme extensión de su arquidiócesis.</p>'
                    . '<p>Su acción fue decisiva para la organización y consolidación de la Iglesia en el Perú. '
                    . 'Convocó sínodos y concilios, impulsó la formación del clero y promovió una evangelización '
                    . 'que buscaba llegar realmente a las personas y a sus culturas. Durante 25 años de episcopado '
                    . 'recorrió buena parte del territorio, llevando personalmente la Palabra y los sacramentos.</p>'
                    . '<p>León XIV lo ha presentado como <strong>“símbolo episcopal de la auténtica sinodalidad y '
                    . 'del Evangelio ofrecido en las periferias”</strong>. Para el actual Pontífice, la fuerza de '
                    . 'Toribio no estuvo solamente en su extraordinaria actividad pastoral, sino en la '
                    . '<strong>intensa oración y unión con Dios</strong> que la sostenía.</p>',
          'datos'  => ['anios' => '1538 – 1606', 'resumen' => 'Un pastor en salida que recorrió el Perú para anunciar el Evangelio y acompañar a su pueblo.'],
          'carpeta' => 'santos', 'img' => 'p01', 'w' => 1010, 'h' => 1594,
          'alt'     => 'Retrato al óleo de Santo Toribio de Mogrovejo arrodillado en oración, con muceta roja, ante un altar',
      ],
  ];

  $fichas = $bloques('los-cinco-santos', array_values($reservaSantos));

  /* El titular de la sección es el que el rediseño promovió al héroe
     («Cinco santos, un mismo corazón»). No se repinta aquí —sería decir dos
     veces lo mismo en media pantalla—, pero sigue siendo el nombre de la
     sección: se deja para el lector de pantalla y para el índice del panel. */
  $tituloSeccion = $campo('los-cinco-santos', 'titulo', 'Cinco santos, un mismo corazón');
  $rotuloSeccion = $campo('los-cinco-santos', 'rotulo', 'Siglos XVI y XVII');
  $entradilla    = $campo(
      'los-cinco-santos',
      'texto',
      '<p>En los siglos XVI y XVII, cinco figuras dejaron una huella profunda en la vida de la Iglesia. '
      . 'Sus vidas estuvieron marcadas por la oración, la misión, la caridad, la defensa de los más '
      . 'vulnerables y el encuentro con distintas realidades del Perú.</p>'
  );

  /* La misma regla que Sitio::campo(), pero para un bloque: un campo vacío en
     el panel no borra nada, cae en lo que la página trae escrito. */
  $deFicha = static function (array $ficha, array $base, string $campo): string {
      $valor = trim((string) ($ficha[$campo] ?? ''));

      return $valor !== '' ? $valor : trim((string) ($base[$campo] ?? ''));
  };
  ?>
  <section class="santos-pagina" aria-labelledby="t-santos">
    <h2 class="visually-hidden" id="t-santos"><?= $esc($tituloSeccion) ?></h2>

    <?php /* La entradilla no está en el editable, que sólo presentaba a un
             santo. Con cinco fichas hace falta una línea que abra la serie, y
             el texto ya estaba escrito en la base: se pinta con la misma caja
             y la misma tipografía que el cuerpo de las fichas. */ ?>
    <?php if ($rotuloSeccion !== '' || $entradilla !== ''): ?>
      <div class="santos-intro">
        <div class="santos-wrap">
          <div class="santos-intro__caja">
            <?php if ($rotuloSeccion !== ''): ?>
              <p class="santos-kicker"><?= $esc($rotuloSeccion) ?></p>
            <?php endif; ?>
            <?php if ($entradilla !== ''): ?>
              <div class="santos-texto"><?= $entradilla ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php foreach ($fichas as $ficha): ?>
      <?php
      $slug   = trim((string) ($ficha['slug'] ?? ''));
      $base   = $reservaSantos[$slug] ?? [];
      $nombre = $deFicha($ficha, $base, 'titulo');
      $virtud = $deFicha($ficha, $base, 'rotulo');
      $cuerpo = $deFicha($ficha, $base, 'texto');

      /* Los dos valores de la columna `datos` del bloque: los declara la
         plantilla «personas» y se editan con el resto de la ficha. */
      $datos   = (array) ($ficha['datos'] ?? []);
      $resumen = $deFicha($datos, (array) ($base['datos'] ?? []), 'resumen');
      $anios   = $deFicha($datos, (array) ($base['datos'] ?? []), 'anios');

      /* El retrato: el que se haya elegido en el panel y, si no hay ninguno,
         el que la página trae escrito para ese santo. */
      ob_start();
      if (!empty($base['img'])): ?>
        <picture>
          <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/' . $base['carpeta'] . '/' . $base['img'] . '.webp')) ?>" type="image/webp">
          <img src="<?= $esc($sitio->asset('assets/img/rediseno/' . $base['carpeta'] . '/' . $base['img'] . '.jpg')) ?>"
               alt="<?= $esc((string) ($base['alt'] ?? ('Retrato de ' . $nombre))) ?>"
               width="<?= (int) ($base['w'] ?? 1010) ?>" height="<?= (int) ($base['h'] ?? 1594) ?>"
               loading="lazy" decoding="async">
        </picture>
      <?php endif;
      $respaldoRetrato = (string) ob_get_clean();
      $retrato = $sitio->imagen($ficha, $respaldoRetrato, ['sizes' => '(min-width:1024px) 35vw, 100vw']);
      ?>
      <article class="santos-toribio"<?= $slug !== '' ? ' id="' . $esc($slug) . '"' : '' ?>>
        <div class="santos-wrap santos-toribio__grid<?= $retrato === '' ? ' santos-toribio__grid--solo' : '' ?>">

          <div class="santos-toribio__col">
            <h3 class="santos-h"><?= $esc($nombre) ?></h3>
            <?php if ($virtud !== ''): ?>
              <p class="santos-kicker"><?= $esc($virtud) ?></p>
            <?php endif; ?>

            <?php /* La banda arena del editable. Allí llevaba una cita de
                     León XIV sobre Santo Toribio; la plantilla «personas» no
                     tiene campo de cita, así que recoge lo que sí es de cada
                     santo y está en la base: su resumen y sus años. */ ?>
            <?php if ($resumen !== '' || $anios !== ''): ?>
              <div class="santos-cita">
                <?php if ($resumen !== ''): ?>
                  <p class="santos-cita__txt"><?= $esc($resumen) ?></p>
                <?php endif; ?>
                <?php if ($anios !== ''): ?>
                  <p class="santos-cita__fuente"><?= $esc($anios) ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($cuerpo !== ''): ?>
              <div class="santos-texto"><?= $cuerpo ?></div>
            <?php endif; ?>
          </div>

          <?php if ($retrato !== ''): ?>
            <figure class="santos-retrato"><?= $retrato ?></figure>
          <?php endif; ?>

        </div>
      </article>
    <?php endforeach; ?>
  </section>

</main>
