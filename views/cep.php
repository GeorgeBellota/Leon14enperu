<?php
/**
 * ============================================================================
 *  La Iglesia en el Perú — rediseño 2026.
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
 *  Las medidas son las del editable CEP.ai (mesa de 1440 px). La hoja
 *  assets/css/paginas/cep.css las reproduce con la unidad --u.
 *
 *  ── Las tres secciones ───────────────────────────────────────────────────
 *
 *   · «cabecera»              la franja con la foto de los obispos.
 *   · «quien-organiza-visita» el bloque de texto con la fotografía al lado.
 *   · «quien-acoge-cada»      la tabla de las cinco jurisdicciones.
 *
 *  Las tres conservan la clave que ya tenían en la base: así se mantienen su
 *  contenido, su SEO y su historial de ediciones.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'La Iglesia en el Perú · Conferencia Episcopal Peruana · León XIV en el Perú',
    'descripcion' => 'La Conferencia Episcopal Peruana y las cinco jurisdicciones eclesiásticas que '
                   . 'acogen el viaje apostólico del Papa León XIV al Perú: Lima, Callao, '
                   . 'Chiclayo y Santa Cruz, Cusco y Pucallpa.',
    'ruta'        => 'cep/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
];

$paginaCms = $sitio->contenido('cep');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);

/* ── Pintar o no pintar una sección ───────────────────────────────────────
   Una sección apagada en el panel desaparece de la página. Pero si la base
   no responde no llega ninguna sección, y entonces se pinta todo: apagar la
   página entera por un fallo de MySQL sería peor que enseñarla con el texto
   que trae escrito. */
$pintar = static fn (string $s): bool
    => $secciones === [] || \Intranet\Publico\Sitio::activa($secciones, $s);

/* ── Texto con formato ────────────────────────────────────────────────────
   Lo que el panel guarda en un campo «Texto» puede traer <p>, <strong>, <em>
   y enlaces. Se pasa por HtmlSeguro, que reconstruye el árbol y deja fuera
   todo lo que no sea formato: el visitante nunca ejecuta lo que se tecleó en
   la intranet. */
$rico = static fn (?string $v): string
    => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

/* ── …y texto sin formato que aún así son párrafos ────────────────────────
   Las secciones de esta página entraron en la base como texto plano, sin
   etiquetas, de la primera migración. Si se pintara tal cual, la hoja no
   encontraría ningún <p> que estilar y el bloque saldría en el cuerpo por
   defecto. Así que: si hay etiquetas, se limpia y se respeta; si no las hay,
   cada línea se convierte en un párrafo. */
$parrafos = static function (?string $v) use ($rico, $esc): string {
    $v = trim((string) ($v ?? ''));

    if ($v === '') {
        return '';
    }

    if (str_contains($v, '<')) {
        return $rico($v);
    }

    $salida = '';

    foreach (preg_split('/\R+/u', $v) ?: [] as $linea) {
        $linea = trim($linea);

        if ($linea !== '') {
            $salida .= '<p>' . $esc($linea) . '</p>';
        }
    }

    return $salida;
};

/* ── El respaldo de las fotografías ───────────────────────────────────────
   Mientras nadie elija otra imagen en el panel se ven éstas, las del
   editable. Se arman con las variantes que ya están en disco —las mismas que
   declara assets/img/rediseno/variantes.json— para que un teléfono no se
   descargue la pieza de 2880 px sólo porque la foto todavía no esté elegida
   en el panel o la base no responda. */
$respaldoFoto = static function (
    string $base,
    array $anchos,
    int $ancho,
    int $alto,
    string $alt,
    string $sizes,
    bool $prioridad = false
) use ($sitio, $esc): string {
    $webp = $jpg = [];

    foreach ($anchos as $a) {
        $webp[] = $esc($sitio->asset($base . '-' . $a . '.webp')) . ' ' . $a . 'w';
        $jpg[]  = $esc($sitio->asset($base . '-' . $a . '.jpg')) . ' ' . $a . 'w';
    }

    return '<picture>'
        . '<source type="image/webp" sizes="' . $esc($sizes) . '" srcset="' . implode(', ', $webp) . '">'
        . '<img src="' . $esc($sitio->asset($base . '.jpg')) . '"'
        . ' sizes="' . $esc($sizes) . '" srcset="' . implode(', ', $jpg) . '"'
        . ' alt="' . $esc($alt) . '"'
        . ' width="' . $ancho . '" height="' . $alto . '"'
        . ($prioridad ? ' fetchpriority="high"' : ' loading="lazy"')
        . ' decoding="async"></picture>';
};
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ CABECERA ══
       La franja con la fotografía a sangre y el título encima. Se edita en
       Páginas → La Iglesia en el Perú → Cabecera de página. */ ?>
  <?php if ($pintar('cabecera')): ?>
  <?php
  $respaldoCabecera = $respaldoFoto(
      'assets/img/rediseno/cep/hero',
      [480, 768, 1024, 1440, 1920, 2560],
      2880,
      1164,
      'Los obispos de la Conferencia Episcopal Peruana reunidos con el Santo Padre en el Vaticano',
      '100vw',
      true
  );

  /* El editable escribe el nombre de la institución en semibold dentro de la
     bajada. El panel guarda esa bajada como texto plano —la plantilla de
     cabecera no admite formato—, así que el resalte se pone aquí, sobre el
     texto YA escapado, buscando el nombre tal cual. Si alguien reescribe la
     frase y el nombre desaparece, la línea se pinta entera en Light: se
     pierde un matiz tipográfico, nunca el texto. */
  $bajada     = $campo('cabecera', 'texto', 'La Conferencia Episcopal Peruana y las cinco jurisdicciones eclesiásticas que acogen el viaje apostólico.');
  $bajadaHtml = $esc($bajada);
  $nombreCep  = $esc('Conferencia Episcopal Peruana');
  $donde      = mb_strpos($bajadaHtml, $nombreCep);

  if ($donde !== false) {
      $bajadaHtml = mb_substr($bajadaHtml, 0, $donde)
                  . '<strong>' . $nombreCep . '</strong>'
                  . mb_substr($bajadaHtml, $donde + mb_strlen($nombreCep));
  }
  ?>
  <section class="hero hero--page cep-hero">
    <div class="hero__media">
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoCabecera, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="container hero__inner">
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'La Iglesia en el Perú')) ?></h1>
      <p class="hero__sub cep-hero__sub"><?= $bajadaHtml ?></p>
    </div>
  </section>
  <?php endif; ?>

  <?php /* ═══════════════════════════════════ ¿QUIÉN ORGANIZA LA VISITA? ══
       Texto a la izquierda y fotografía a la derecha. Los <br class="cep-br">
       del respaldo son los saltos de línea del editable y sólo valen en
       escritorio: la hoja los apaga por debajo de 1024 px. El texto que
       venga del panel fluye solo, que es lo que tiene que pasar cuando lo
       escribe otra persona. */ ?>
  <?php if ($pintar('quien-organiza-visita')): ?>
  <?php
  $textoOrganiza    = $parrafos($campo('quien-organiza-visita', 'texto', ''));
  $respaldoOrganiza = $respaldoFoto(
      'assets/img/rediseno/cep/p01',
      [480, 768, 1024],
      1066,
      988,
      'Un obispo peruano saluda a un cardenal durante un encuentro de la Conferencia Episcopal Peruana',
      '(min-width:1024px) 37vw, 92vw'
  );
  ?>
  <section class="cep-organiza" aria-labelledby="t-organiza">
    <div class="cep-wrap">
      <h2 class="cep-h" id="t-organiza"><?= $esc($campo('quien-organiza-visita', 'titulo', '¿Quién organiza la visita?')) ?></h2>

      <div class="cep-duo">
        <div class="cep-duo__texto">
          <?php if ($textoOrganiza !== ''): ?>
            <?= $textoOrganiza ?>
          <?php else: ?>
            <p>La <strong>Conferencia Episcopal Peruana</strong> reúne a los<br class="cep-br"> obispos de las diócesis, arquidiócesis, prelaturas y<br class="cep-br"> vicariatos apostólicos del país. Es el organismo que<br class="cep-br"> coordina la preparación del viaje en el Perú, difunde<br class="cep-br"> y adapta lo que publica la Santa Sede, y articula el<br class="cep-br"> trabajo de las cuatro jurisdicciones que reciben al<br class="cep-br"> Santo Padre.</p>
            <p>León XIV la conoce por dentro. Fue su segundo<br class="cep-br"> vicepresidente desde marzo de 2018, miembro de su<br class="cep-br"> Consejo Económico y presidente de la Comisión<br class="cep-br"> Episcopal de Cultura y Educación. En 2023 la propia<br class="cep-br"> Conferencia le concedió la Medalla de Oro de Santo<br class="cep-br"> Toribio de Mogrovejo.</p>
            <p>Los datos de contacto institucional de la<br class="cep-br"> Conferencia se publicarán en la página de contacto.</p>
          <?php endif; ?>
        </div>

        <figure class="photo cep-foto">
          <?= $sitio->imagen($secciones['quien-organiza-visita'] ?? [], $respaldoOrganiza, ['sizes' => '(min-width:1024px) 37vw, 92vw']) ?>
        </figure>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php /* ═════════════════════════════════════ ¿QUIÉN ACOGE CADA SEDE? ══
       La tabla de jurisdicciones. Cada fila es un «apartado» de la sección:
       se editan en Páginas → La Iglesia en el Perú → Quién acoge cada sede,
       y se pueden añadir, quitar o reordenar sin tocar la vista.

       El título del apartado lleva DOS LÍNEAS: la primera es el nombre en
       dorado y el resto, la jurisdicción en granate. Es la única manera de
       decir las dos cosas con la plantilla «texto con apartados», que sólo
       da título y texto a cada pieza. Si alguien escribe una sola línea, la
       fila sale con el nombre y sin el subtítulo, sin romperse. */ ?>
  <?php if ($pintar('quien-acoge-cada')): ?>
  <?php
  $jurisdicciones = $bloques('quien-acoge-cada', [
      [
          'titulo' => "Lima\nArquidiócesis de Lima · Primada del Perú",
          'texto'  => 'Creada en 1546 por el papa Paulo III. Confirmada como arquidiócesis primada '
                    . 'en 1943. Abarca a más de nueve millones de habitantes.',
      ],
      [
          'titulo' => "Callao\nDiócesis del Callao",
          'texto'  => 'Fue creada por S.S. Pablo VI, mediante el documento pontificio '
                    . '<em>Aptiorem Ecclesiarum</em> en 1967. La fe de su comunidad se expresa en la '
                    . 'profunda devoción a sus santos patrones: la Virgen del Carmen de la Legua y '
                    . 'el Señor del Mar. Se organiza en cuatro decanatos, que reúnen cerca de 60 parroquias.',
      ],
      [
          'titulo' => "Chiclayo - Santa Cruz\nDiócesis de Chiclayo · Lambayeque y Santa Cruz",
          'texto'  => 'Erigida el 17 de diciembre de 1956 con territorio de la arquidiócesis de '
                    . 'Trujillo y de la diócesis de Cajamarca. Robert Prevost fue su obispo entre 2015 y 2023.',
      ],
      [
          'titulo' => "Cusco\nArquidiócesis del Cusco",
          'texto'  => 'Una de las Iglesias más antiguas del continente, elevada al rango de '
                    . 'arquidiócesis en 1943. El Señor de los Temblores es su Patrón Jurado.',
      ],
      [
          'titulo' => "Pucallpa\nVicariato Apostólico de Pucallpa · Ucayali",
          'texto'  => 'Erigido el 2 de marzo de 1956 al dividirse el vicariato de Ucayali. Depende '
                    . 'directamente de la Santa Sede y cubre más de 52.000 km² de Amazonía.',
      ],
  ]);

  $entradaSedes = $parrafos($campo('quien-acoge-cada', 'texto', ''));
  ?>
  <section class="cep-sedes" aria-labelledby="t-sedes">
    <div class="cep-wrap">
      <h2 class="cep-h" id="t-sedes"><?= $esc($campo('quien-acoge-cada', 'titulo', '¿Quién acoge cada sede?')) ?></h2>

      <?php if ($entradaSedes !== ''): ?>
        <div class="cep-sedes__lead"><?= $entradaSedes ?></div>
      <?php else: ?>
        <p class="cep-sedes__lead">Una arquidiócesis primada, una arquidiócesis andina, dos diócesis y<br class="cep-br"> un vicariato apostólico de territorio de misión.</p>
      <?php endif; ?>

      <dl class="datalist cep-jurisdicciones">
        <?php foreach ($jurisdicciones as $j): ?>
          <?php
          /* Primera línea: el nombre. Las siguientes: la jurisdicción. */
          $lineas = preg_split('/\R+/u', trim((string) ($j['titulo'] ?? ''))) ?: [];
          $nombre = trim((string) array_shift($lineas));
          $sub    = trim(implode(' ', array_map('trim', $lineas)));

          /* «Chiclayo - Santa Cruz»: el editable pone la segunda mitad en
             redonda y un cuerpo más pequeño. Se parte por el guión largo
             rodeado de espacios, que es como se escribe en el panel. */
          $dosNombres = preg_split('/\s+-\s+/u', $nombre, 2) ?: [$nombre];
          $nombreHtml = $esc((string) ($dosNombres[0] ?? ''));

          if (isset($dosNombres[1]) && $dosNombres[1] !== '') {
              $nombreHtml .= ' - <span class="cep-jur__nombre2">' . $esc($dosNombres[1]) . '</span>';
          }

          if ($nombre === '' && $sub === '' && trim((string) ($j['texto'] ?? '')) === '') {
              continue;
          }
          ?>
          <div class="datalist__row">
            <dt class="datalist__key">
              <span class="cep-jur__nombre"><?= $nombreHtml ?></span>
              <?php if ($sub !== ''): ?>
                <span class="cep-jur__sub"><?= $esc($sub) ?></span>
              <?php endif; ?>
            </dt>
            <dd class="datalist__val"><?= $rico((string) ($j['texto'] ?? '')) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>

      <?php /* El botón de cierre. La plantilla «texto con apartados» no tiene
               campos de botón, así que su texto y su destino van aquí: la
               página de sedes es la continuación natural de esta tabla y no
               cambia. Queda anotado en el informe por si más adelante se
               quiere una plantilla con botón. */ ?>
      <p class="cep-sedes__cta">
        <a class="btn cep-btn" href="<?= $esc($sitio->enlace('sedes/')) ?>">Ir a Sedes</a>
      </p>
    </div>
  </section>
  <?php endif; ?>

</main>
