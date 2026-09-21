<?php
/**
 * ============================================================================
 *  Papa León XIV — «El regreso de nuestro Pastor». Rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Es la página más larga del sitio: 9.099 px de mesa de trabajo. La hoja
 *  assets/css/paginas/papa-leon-xiv.css reproduce esas medidas con la unidad
 *  --u, así que aquí sólo se escribe el marcado exacto que esa hoja espera.
 *
 *  ── De dónde sale el texto ───────────────────────────────────────────────
 *
 *  De la base, con la clave «papa-leon-xiv». La página se llamaba «el-papa» y
 *  la migración renombra la clave; las secciones conservan las suyas para no
 *  perder su contenido ni su historial.
 *
 *  Toda lectura lleva su texto de reserva —el del editable—. Si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta
 *  igual. Una web sobre un viaje papal no puede quedarse muda porque falle la
 *  base.
 *
 *  ── Las cuatro etapas ────────────────────────────────────────────────────
 *
 *  1955–1984, 1985–1999, 1999–2023 y 2023–hoy tienen la misma estructura:
 *  año, titular y una serie de pasajes de texto y fotografía que se van
 *  alternando de lado. Se pintan con un solo bucle y una tabla de ranuras,
 *  porque escribir cuatro veces el mismo marcado es cuatro sitios donde
 *  corregir la misma errata.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Papa León XIV · El regreso de nuestro Pastor',
    'descripcion' => 'Quién es el Papa León XIV: Robert Francis Prevost, primer Papa de la Orden de '
                   . 'San Agustín y primer Papa nacido en Estados Unidos con nacionalidad peruana. '
                   . 'Su historia, su escudo y su magisterio.',
    'ruta'        => 'papa-leon-xiv/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
];

$paginaCms = $sitio->contenido('papa-leon-xiv');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);

/* ── ¿Se pinta esta sección? ──────────────────────────────────────────────
   Una sección apagada en el panel no se pinta. Pero si la base entera no
   respondió —$secciones vacío— se pintan todas con sus reservas: preferimos
   una página completa con el texto del editable a una página en blanco. */
$pinta = static fn (string $s): bool
    => $secciones === [] || \Intranet\Publico\Sitio::activa($secciones, $s);

/* ── Texto largo del panel → párrafos ─────────────────────────────────────
   Los campos «Texto» del panel admiten marcado (<p>, <strong>, enlaces), que
   es lo que necesitan estos pasajes: casi todos llevan nombres y cargos en
   negrita. Si lo que llega es texto llano se convierte en párrafos, para que
   quien escriba sin saber HTML tampoco vea su texto amontonado en una línea. */
$cuerpo = static function (string $texto): string {
    $texto = trim($texto);

    if ($texto === '') {
        return '';
    }

    if (preg_match('~<(p|div|ul|ol|br|strong|b|em|i|a|cite|span)\b~i', $texto) === 1) {
        return $texto;
    }

    $html = '';

    foreach (preg_split('/\R{2,}/u', $texto) ?: [$texto] as $parrafo) {
        $parrafo = trim($parrafo);

        if ($parrafo !== '') {
            $html .= '<p>' . nl2br(htmlspecialchars($parrafo, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
    }

    return $html;
};

/* Lo mismo para una línea suelta —un dato de la ficha—: ni párrafos ni nada
   que se vea, pero respetando los saltos de línea y la cursiva del lema. */
$linea = static function (string $texto): string {
    $texto = trim($texto);

    if ($texto === '') {
        return '';
    }

    if (preg_match('~<(br|strong|b|em|i|a|cite|span)\b~i', $texto) === 1) {
        return $texto;
    }

    return nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
};

/* ── La palabra dorada del titular del héroe ──────────────────────────────
   El editable escribe «El regreso de» con «regreso» en dorado y semibold. En
   el panel eso se marca con asteriscos —«El *regreso* de»—, igual que se
   marcaría una cursiva al escribir un correo. Si no hay asteriscos, o si
   están descabalados, el titular se pinta entero y no pasa nada. */
$acento = static function (string $texto) use ($esc): string {
    if (substr_count($texto, '*') < 2 || substr_count($texto, '*') % 2 !== 0) {
        return $esc($texto);
    }

    $html = '';

    foreach (explode('*', $texto) as $i => $trozo) {
        $html .= $i % 2 === 1 ? '<em>' . $esc($trozo) . '</em>' : $esc($trozo);
    }

    return $html;
};

/* ── El <picture> de respaldo ─────────────────────────────────────────────
   El que se ve mientras nadie elija una fotografía en el panel. Todas las de
   esta página están en assets/img/rediseno/papa-leon-xiv/ y tienen su gemela
   en webp. */
$respaldoFoto = static function (
    string $base,
    int $ancho,
    int $alto,
    string $alt,
    string $formato = 'jpg',
    bool $prioritaria = false
) use ($sitio, $esc): string {
    $carpeta = 'assets/img/rediseno/papa-leon-xiv/';

    ob_start(); ?>
    <picture>
      <source srcset="<?= $esc($sitio->asset($carpeta . $base . '.webp')) ?>" type="image/webp">
      <img src="<?= $esc($sitio->asset($carpeta . $base . '.' . $formato)) ?>"
           alt="<?= $esc($alt) ?>" width="<?= $ancho ?>" height="<?= $alto ?>"
           <?= $prioritaria ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
    </picture>
    <?php

    return trim((string) ob_get_clean());
};

/* ══════════════════════════════════════════════════════════════════════════
   LAS CUATRO ETAPAS

   Cada etapa declara sus ranuras: la hoja de estilos tiene un hueco con
   coordenadas exactas para cada fotografía (a, b, c) y para cada texto, y
   «der» dice cuáles van alineados a la derecha, como en el editable.

   La lista de pasajes hace de dos cosas a la vez: es la reserva que se pinta
   si la base no responde y es el mapa de ranuras que le toca a cada bloque
   del panel. El bloque n.º 1 ocupa la primera ranura, el n.º 2 la segunda…
   Si el editor añade uno de más, la página no lo esconde: se pinta debajo de
   la etapa, en su propio flujo (ver «.pl-extras» en la hoja de la página).
   ══════════════════════════════════════════════════════════════════════════ */
$etapas = [
    [
        'clave'  => 'raices-vocacion-agustiniana',
        'n'      => 1,
        'id'     => 't-raices',
        'anio'   => '1955 - 1984',
        'titulo' => "Raíces y vocación\nagustiniana",
        'nota'   => '',
        'orden'  => 'foto-texto',
        'pasajes' => [
            [
                'rfoto' => 'a', 'rtexto' => 'a', 'der' => false,
                'img' => 'p11', 'w' => 778, 'h' => 732,
                'alt' => 'Robert Francis Prevost de niño, sobre una vista aérea de Chicago',
                'texto' => '<p><strong>Robert Francis Prevost</strong> nació en Chicago, Estados Unidos, <br>'
                         . 'el 14 de septiembre de 1955. Es el menor de los tres hijos <br>'
                         . 'de Louis Marius Prevost y Mildred Agnes Martínez. Sus <br>'
                         . 'hermanos son Louis Martín y John Joseph.</p>'
                         . '<p>Realizó sus estudios primarios en la Saint Mary School of <br>'
                         . 'the Assumption y luego estudió en la Saint Augustine <br>'
                         . 'Seminary High School, en Holland, Michigan, donde se <br>'
                         . 'graduó en 1973. Ese mismo año ingresó a la Universidad <br>'
                         . 'de Villanova, en Filadelfia, donde obtuvo en 1977 la <br>'
                         . 'licenciatura en Matemáticas y cursó estudios de Filosofía.</p>',
            ],
            [
                'rfoto' => 'b', 'rtexto' => 'b', 'der' => true,
                'img' => 'p08', 'w' => 474, 'h' => 491,
                'alt' => 'El joven Robert Prevost revestido de diácono en una celebración',
                'texto' => '<p>El 1 de septiembre de 1977 <strong>ingresó al noviciado de la Orden de <br>'
                         . 'San Agustín</strong>, en Saint Louis, perteneciente a la Provincia del <br>'
                         . 'Medio Oeste de Nuestra Madre del Buen Consejo. <strong class="pl-negro">Emitió sus <br>'
                         . 'votos temporales</strong> el 2 de septiembre de 1978 y, mientras <br>'
                         . 'continuaba su formación teológica en la Catholic Theological <br>'
                         . 'Union de Chicago, obtuvo allí la licenciatura en Teología y <br>'
                         . '<strong>realizó su profesión religiosa definitiva</strong> en 1981.</p>'
                         . '<p>En septiembre de 1981 <strong>fue enviado a Roma para estudiar <br>'
                         . 'Derecho Canónico</strong> en la Pontificia Universidad de Santo <br>'
                         . 'Tomás de Aquino (Angelicum), residiendo en el Colegio <br>'
                         . 'Internacional Santa Mónica. Fue ordenado diácono el 10 de <br>'
                         . 'septiembre de 1981, en la parroquia Santa Clara de Montefalco, <br>'
                         . 'en Grosse Pointe Park, diócesis de Detroit. El 19 de junio de 1982 <br>'
                         . 'recibió la ordenación sacerdotal en la capilla de Santa <br>'
                         . 'Mónica. En 1984 obtuvo la licenciatura en Derecho Canónico.</p>',
            ],
        ],
    ],

    [
        'clave'  => 'misionero-formador-peru',
        'n'      => 2,
        'id'     => 't-misionero',
        'anio'   => '1985 – 1999',
        'titulo' => "Misionero y\nformador en el Perú",
        'nota'   => '<strong>«Patrística»</strong>: Estudio de los Padres de la Iglesia.',
        'orden'  => 'foto-texto',
        'pasajes' => [
            [
                'rfoto' => 'a', 'rtexto' => 'a', 'der' => true,
                'img' => 'p09', 'w' => 925, 'h' => 880,
                'alt' => 'El padre Prevost junto a un seminarista durante su misión en el Perú',
                'texto' => '<p>En 1984 obtuvo la licenciatura en Derecho Canónico y, al <br>'
                         . 'año siguiente, mientras preparaba su tesis doctoral, fue <br>'
                         . 'enviado a la misión agustiniana de <strong>Chulucanas, en la <br>'
                         . 'región Piura.</strong> Allí permaneció un año, como vicepárroco <br>'
                         . 'de la Catedral de la Sagrada Familia y canciller de la <br>'
                         . 'entonces Prelatura Territorial de Chulucanas.</p>'
                         . '<p>En 1985 defendió su tesis doctoral sobre <strong>el papel del <br>'
                         . 'prior local en la Orden de San Agustín</strong>, publicada dos <br>'
                         . 'años después. En 1987 regresó a Estados Unidos, donde <br>'
                         . 'fue nombrado director de Vocaciones y director de <br>'
                         . 'Misiones de su provincia agustiniana, con residencia en <br>'
                         . 'Olympia Fields.</p>',
            ],
            [
                'rfoto' => 'b', 'rtexto' => 'b', 'der' => false,
                'img' => 'p10', 'w' => 665, 'h' => 766,
                'alt' => 'El padre Prevost con un poblador y dos jóvenes durante la misión agustiniana en el norte del Perú',
                'texto' => '<p>En 1988 volvió al Perú, esta vez a la <strong>misión agustiniana <br>'
                         . 'de Trujillo</strong>, para dirigir la primera casa de formación <br>'
                         . 'conjunta de los vicariatos agustinianos de Chulucanas, <br>'
                         . 'Iquitos y Apurímac. Allí fue prior de la comunidad <br>'
                         . '(1988–1992), director de Formación (1988–1998) y <br>'
                         . 'maestro de profesos (1993–1998).</p>'
                         . '<p>En la <strong>Arquidiócesis de Trujillo</strong> fue director de Estudios y <br>'
                         . 'rector interino del Seminario Mayor San Carlos y San <br>'
                         . 'Marcelo, donde enseñó Derecho Canónico, Teología <br>'
                         . 'Moral y <strong class="t-wine">*Patrística</strong>. También ejerció como vicario judicial <br>'
                         . 'y miembro del Colegio de Consultores de Trujillo.</p>'
                         . '<p>En el ámbito pastoral, fue párroco de <strong>Nuestra Señora <br>'
                         . 'Madre de la Iglesia</strong>, hoy parroquia Santa Rita de Casia <br>'
                         . '(1988–1999), y administrador de <strong>Nuestra Señora de <br>'
                         . 'Montserrat </strong>(1992–1999). En 1998 fue elegido prior <br>'
                         . 'provincial de la Provincia del Medio Oeste de Nuestra <br>'
                         . 'Madre del Buen Consejo y regresó a Estados Unidos el 8 <br>'
                         . 'de marzo de 1999 para iniciar su mandato.</p>',
            ],
        ],
    ],

    [
        'clave'  => 'prior-general-obispo',
        'n'      => 3,
        'id'     => 't-prior',
        'anio'   => '1999 – 2023',
        'titulo' => 'De prior general a obispo de Chiclayo',
        'nota'   => '',
        /* Aquí el texto va delante de su fotografía: es el único orden que
           deja la lectura correcta cuando la página se apila en un móvil. */
        'orden'  => 'texto-foto',
        'pasajes' => [
            [
                'rfoto' => 'a', 'rtexto' => 'a', 'der' => false,
                'img' => 'p01', 'w' => 1162, 'h' => 755,
                'alt' => 'El obispo Robert Prevost en audiencia con el papa Francisco',
                'texto' => '<p>En 1999 inició su mandato como prior provincial de <br>'
                         . 'la Provincia del Medio Oeste de Nuestra Madre del <br>'
                         . 'Buen Consejo, con sede en Chicago. En el Capítulo <br>'
                         . 'General de 2001 fue elegido <strong>prior general de la <br>'
                         . 'Orden de San Agustín</strong> y, en 2007, fue confirmado <br>'
                         . 'para un segundo mandato de seis años.</p>'
                         . '<p>Al concluir su servicio en 2013, regresó a su <br>'
                         . 'provincia en Chicago, donde fue director de <br>'
                         . 'Formación en el convento de San Agustín, <br>'
                         . 'además de primer consejero y vicario provincial. <br>'
                         . 'El 3 de noviembre de 2014, el papa Francisco lo <br>'
                         . 'nombró <strong>administrador apostólico de la diócesis <br>'
                         . 'de Chiclayo y obispo titular de Sufar</strong>. Tomó <br>'
                         . 'posesión de la diócesis el 7 de noviembre y recibió <br>'
                         . 'la ordenación episcopal el 12 de diciembre, en la <br>'
                         . 'catedral de Santa María, en la fiesta de Nuestra <br>'
                         . 'Señora de Guadalupe. El 26 de septiembre de 2015 <br>'
                         . 'fue nombrado obispo de Chiclayo.</p>'
                         . '<p>En marzo de 2018 fue elegido <strong>segundo <br>'
                         . 'vicepresidente de la Conferencia Episcopal <br>'
                         . 'Peruana</strong>, donde también integró el Consejo <br>'
                         . 'Económico y presidió la Comisión Episcopal de <br>'
                         . 'Cultura y Educación, y fue miembro de la <br>'
                         . 'Comisión de la Protección del Menor. En abril de <br>'
                         . '2020 fue nombrado como administrador <br>'
                         . 'apostólico de la diócesis del Callao.</p>',
            ],
            [
                /* Sólo fotografía: en el editable la asamblea de obispos va
                   sola, debajo de la audiencia con el papa Francisco. */
                'rfoto' => 'b', 'rtexto' => '', 'der' => false,
                'img' => 'p02', 'w' => 1164, 'h' => 778,
                'alt' => 'Asamblea de obispos de la Conferencia Episcopal Peruana',
                'texto' => '',
            ],
            [
                'rfoto' => 'c', 'rtexto' => 'b', 'der' => true,
                'img' => 'p03', 'w' => 982, 'h' => 930,
                'alt' => 'Entrega del doctorado honoris causa a Robert Prevost',
                'texto' => '<p>El 30 de enero de 2023 fue llamado a Roma por <br>'
                         . 'el papa Francisco para asumir como <strong>Prefecto <br>'
                         . 'del Dicasterio para los Obispos y Presidente <br>'
                         . 'de la Pontificia Comisión para América Latina</strong>, <br>'
                         . 'dejando así su servicio pastoral en el Perú. Ese <br>'
                         . 'mismo año, la Conferencia Episcopal Peruana <br>'
                         . 'le otorgó la <strong>Medalla de Oro de Santo Toribio de <br>'
                         . 'Mogrovejo</strong>, en reconocimiento a su servicio a la <br>'
                         . 'Iglesia en el Perú, y la Universidad Católica <br>'
                         . 'Santo Toribio de Mogrovejo (USAT), de Chiclayo, <br>'
                         . 'le concedió el <strong>doctorado honoris causa en <br>'
                         . 'Derecho</strong>. En noviembre de 2023, la Pontificia <br>'
                         . 'Universidad Católica del Perú (PUCP) entregó la <br>'
                         . 'medalla de Honor R.P. Jorge Dintilhac SS.CC. a <br>'
                         . 'Robert Prevost en reconocimiento a su <br>'
                         . 'trayectoria pastoral, su vínculo con la <br>'
                         . 'comunidad universitaria y <strong>su labor como <br>'
                         . 'miembro de la Asamblea Universitaria</strong> de la <br>'
                         . 'PUCP entre 2017 y 2023.</p>',
            ],
        ],
    ],

    [
        'clave'  => 'chiclayo-pontificado',
        'n'      => 4,
        'id'     => 't-pontificado',
        'anio'   => '2023 – Hoy',
        'titulo' => 'De Chiclayo al pontificado',
        'nota'   => '<strong>«Prefecto»</strong>: Responsable del Dicasterio de la Santa Sede.',
        'orden'  => 'foto-texto',
        'pasajes' => [
            [
                'rfoto' => 'a', 'rtexto' => 'a', 'der' => false,
                'img' => 'p04', 'w' => 784, 'h' => 841,
                'alt' => 'El cardenal Robert Francis Prevost con la vestidura cardenalicia',
                'texto' => '<p>El 30 de enero de 2023, el papa Francisco lo llamó <br>'
                         . 'a Roma como <strong class="t-wine">*Prefecto</strong><strong> del Dicasterio para los <br>'
                         . 'Obispos</strong> y Presidente de la Pontificia Comisión <br>'
                         . 'para América Latina, elevándolo a la dignidad de <br>'
                         . 'arzobispo. El 30 de septiembre de ese mismo año <br>'
                         . 'fue nombrado cardenal, con la diaconía de Santa <br>'
                         . 'Mónica, de la que tomó posesión el 28 de enero <br>'
                         . 'de 2024.</p>'
                         . '<p>El 6 de febrero de 2025, el papa Francisco lo <br>'
                         . 'promovió al <strong>orden de los cardenales obispos</strong>, <br>'
                         . 'asignándole el título de la Iglesia suburbicaria de <br>'
                         . 'Albano. Durante la última hospitalización del Papa <br>'
                         . 'Francisco, presidió el 3 de marzo el Rosario por la <br>'
                         . 'salud del Pontífice en la Plaza de San Pedro.</p>',
            ],
            [
                'rfoto' => 'b', 'rtexto' => 'b', 'der' => true,
                'img' => 'p05', 'w' => 726, 'h' => 458,
                'alt' => 'El Papa León XIV saluda a los fieles tras su elección',
                'texto' => '<p>El cónclave comenzó el 7 de mayo de 2025. Al <br>'
                         . 'día siguiente, 8 de mayo, fue elegido Papa y <br>'
                         . 'tomó el nombre de <strong>León XIV</strong>. Es <strong>el 267.º <br>'
                         . 'Pontífice</strong>, el primero procedente de los <br>'
                         . 'Estados Unidos de América y el primero <br>'
                         . 'perteneciente a la Orden de San Agustín. El 18 <br>'
                         . 'de mayo presidió la celebración eucarística <br>'
                         . 'de inicio de su ministerio petrino.</p>',
            ],
        ],
    ],
];
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       La franja granate de 582 px con el retrato del Santo Padre saludando.
       El rótulo y las dos líneas del titular se editan en
       Páginas → Papa León XIV → Cabecera de página. */ ?>
  <?php if ($pinta('cabecera')): ?>
  <?php
  $portada = $secciones['cabecera'] ?? [];

  /* El editable pone «267.º» en negrita y el resto en redonda. Del panel
     llega una sola línea: se parte por el primer espacio, que es justo donde
     cambia el grosor. Las versalitas las pone la hoja de estilos, así que en
     el panel se escribe con mayúsculas y minúsculas normales. */
  $rotuloHero = $campo('cabecera', 'rotulo', '267.º sucesor de Pedro');
  $corte      = mb_strpos($rotuloHero, ' ');
  $rotuloFuer = $corte === false ? $rotuloHero : mb_substr($rotuloHero, 0, $corte);
  $rotuloTen  = $corte === false ? ''          : mb_substr($rotuloHero, $corte + 1);
  ?>
  <section class="hero hero-papa">
    <div class="hero__media">
      <?= $sitio->imagen(
          $portada,
          $respaldoFoto(
              'hero', 2880, 1164,
              'El Papa León XIV saluda con los brazos abiertos desde el balcón de la basílica de San Pedro',
              'jpg', true
          ),
          ['sizes' => '100vw', 'prioridad' => true]
      ) ?>
    </div>

    <div class="hero-papa__inner">
      <p class="hero-papa__kicker"><b><?= $esc($rotuloFuer) ?></b> <?= $esc($rotuloTen) ?></p>
      <h1 class="hero-papa__title">
        <span class="hero-papa__l1"><?= $acento($campo('cabecera', 'titulo', 'El *regreso* de')) ?></span>
        <span class="hero-papa__l2"><?= $esc($campo('cabecera', 'texto', 'nuestro Pastor')) ?></span>
      </h1>
    </div>
  </section>
  <?php endif; ?>

  <?php /* ══════════════════════════════════════════════════════ PERFIL ════
       Retrato, entradilla y la ficha de datos. Cada fila de la ficha es un
       «apartado» del panel: el título es la etiqueta y el texto, el dato.
       Los saltos de línea del dato se respetan. */ ?>
  <?php if ($pinta('quien-leon-xiv')): ?>
  <?php
  $datosFicha = $bloques('quien-leon-xiv', [
      ['titulo' => 'NOMBRE',                 'texto' => 'Robert Francis Prevost, O.S.A.'],
      ['titulo' => 'NACIMIENTO',             'texto' => "14 de septiembre de 1955,\nChicago, Estados Unidos (Illinois)"],
      ['titulo' => 'NACIONALIDAD',           'texto' => "Estadounidense y peruano\npor naturalización"],
      ['titulo' => 'ORDEN RELIGIOSA',        'texto' => "Orden de San Agustín\nPrimera profesión: 1978\nVotos solemnes: 1981"],
      ['titulo' => 'ORDENACIÓN SACERDOTAL',  'texto' => '19 de junio de 1982, Roma'],
      ['titulo' => 'MISIÓN EN EL PERÚ',      'texto' => "Chulucanas, Trujillo, Chiclayo\ny Callao, desde 1985"],
      ['titulo' => 'OBISPO DE CHICLAYO',     'texto' => '2015 – 2023'],
      ['titulo' => 'ELECCIÓN COMO PAPA',     'texto' => '8 de mayo de 2025'],
      ['titulo' => 'NOMBRE PONTIFICIO',      'texto' => 'León XIV'],
      ['titulo' => 'LEMA EPISCOPAL',         'texto' => '<em>In Illo uno unum</em>'],
  ]);

  $entradilla = $cuerpo($campo(
      'quien-leon-xiv',
      'texto',
      '<p><strong>Primer Papa perteneciente a la Orden de San Agustín y <br>'
      . 'primer Papa nacido en Estados Unidos y con nacionalidad <br>'
      . 'peruana</strong>. Fue elegido el 8 de mayo de 2025, en la tarde del <br>'
      . 'segundo día del cónclave, y tomó el nombre de León XIV.</p>'
  ));
  ?>
  <section class="pl-sec perfil" aria-labelledby="t-quien">
    <div class="pl-wrap">

      <h2 class="perfil__h" id="t-quien"><?= nl2br($esc($campo('quien-leon-xiv', 'titulo', '¿Quién es León XIV?'))) ?></h2>
      <span class="pl-rule perfil__rule" aria-hidden="true"></span>

      <div class="pl-cuerpo perfil__lead"><?= $entradilla ?></div>

      <figure class="pl-foto perfil__foto">
        <?= $sitio->imagen(
            $secciones['quien-leon-xiv'] ?? [],
            $respaldoFoto('p07', 816, 1160, 'Retrato oficial de Su Santidad el Papa León XIV'),
            ['sizes' => '(min-width:1024px) 29vw, 100vw']
        ) ?>
        <?php /* El pie es el crédito del retrato: viaja con la fotografía, no
                 con el texto de la sección, y por eso no sale del panel. */ ?>
        <figcaption class="perfil__pie">Su Santidad el Papa León XIV.<br><span>Fotografía: Santa Sede</span></figcaption>
      </figure>

      <?php if ($datosFicha !== []): ?>
      <dl class="ficha">
        <?php foreach ($datosFicha as $fila): ?>
          <?php /* Con destino, el rótulo de la fila se vuelve enlace. Misma
                   regla que en Subsidios y en Prensa: la plantilla es la
                   misma y el campo tiene que hacer lo mismo en las tres. */ ?>
          <?php $destinoFila = $sitio->enlaceDelPanel((string) ($fila['enlace_url'] ?? '')); ?>
          <div class="ficha__fila">
            <dt>
              <?php if ($destinoFila !== ''): ?>
                <a href="<?= $esc($destinoFila) ?>"<?= $sitio->esExterno($destinoFila) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $esc((string) ($fila['titulo'] ?? '')) ?></a>
              <?php else: ?>
                <?= $esc((string) ($fila['titulo'] ?? '')) ?>
              <?php endif; ?>
            </dt>
            <dd><?= $linea((string) ($fila['texto'] ?? '')) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

  <?php /* ══════════════════════════════════════════════════════ ETAPAS ════
       Las cuatro etapas de su vida, con el mismo marcado y distintas
       coordenadas. Ver la tabla $etapas de arriba. */ ?>
  <?php foreach ($etapas as $etapa): ?>
    <?php if (!$pinta($etapa['clave'])) { continue; } ?>
    <?php
    $n        = $etapa['n'];
    $pasajes  = $bloques($etapa['clave'], $etapa['pasajes']);
    $nota     = $cuerpo($campo($etapa['clave'], 'texto', $etapa['nota']));
    $extras   = [];
    ?>
    <section class="pl-sec etapa etapa--<?= $n ?>" aria-labelledby="<?= $esc($etapa['id']) ?>">
      <div class="pl-wrap">
        <span class="etapa__halo" aria-hidden="true"></span>

        <p class="etapa__anio etapa<?= $n ?>__anio"><?= $esc($campo($etapa['clave'], 'rotulo', $etapa['anio'])) ?></p>
        <h2 class="etapa__h etapa<?= $n ?>__h" id="<?= $esc($etapa['id']) ?>"><?= nl2br($esc($campo($etapa['clave'], 'titulo', $etapa['titulo']))) ?></h2>

        <?php foreach ($pasajes as $i => $pasaje): ?>
          <?php
          /* La ranura que le toca a este bloque. Si el panel trae más
             bloques que huecos tiene el diseño, los de más se guardan para
             pintarlos debajo, en flujo normal, en lugar de amontonarlos
             encima de los otros. */
          $ranura = $etapa['pasajes'][$i] ?? null;

          if ($ranura === null) {
              $extras[] = $pasaje;
              continue;
          }

          /* La fotografía: la del panel si la hay, y si no la del editable. */
          $reserva = ($pasaje['img'] ?? '') !== ''
              ? $respaldoFoto((string) $pasaje['img'], (int) ($pasaje['w'] ?? 0), (int) ($pasaje['h'] ?? 0), (string) ($pasaje['alt'] ?? ''))
              : '';

          ob_start(); ?>
          <?php if ($ranura['rfoto'] !== ''): ?>
            <?php $imagen = $sitio->imagen($pasaje, $reserva, ['sizes' => '(min-width:1024px) 41vw, 100vw']); ?>
            <?php if ($imagen !== ''): ?>
              <figure class="pl-foto etapa<?= $n ?>__foto-<?= $ranura['rfoto'] ?>"><?= $imagen ?></figure>
            <?php endif; ?>
          <?php endif; ?>
          <?php $marcaFoto = (string) ob_get_clean(); ?>

          <?php
          ob_start();
          $textoPasaje = $cuerpo((string) ($pasaje['texto'] ?? ''));
          ?>
          <?php if ($ranura['rtexto'] !== '' && $textoPasaje !== ''): ?>
            <div class="pl-cuerpo<?= $ranura['der'] ? ' pl-der' : '' ?> etapa<?= $n ?>__txt-<?= $ranura['rtexto'] ?>"><?= $textoPasaje ?></div>
          <?php endif; ?>
          <?php $marcaTexto = (string) ob_get_clean(); ?>

          <?= $etapa['orden'] === 'texto-foto' ? $marcaTexto . $marcaFoto : $marcaFoto . $marcaTexto ?>
        <?php endforeach; ?>

        <?php if ($nota !== ''): ?>
          <div class="pl-nota etapa<?= $n ?>__nota"><?= $nota ?></div>
        <?php endif; ?>

        <?php if ($extras !== []): ?>
          <div class="pl-extras">
            <?php foreach ($extras as $extra): ?>
              <?php $imagenExtra = $sitio->imagen($extra, '', ['sizes' => '(min-width:1024px) 41vw, 100vw']); ?>
              <?php if ($imagenExtra !== ''): ?>
                <figure class="pl-foto"><?= $imagenExtra ?></figure>
              <?php endif; ?>
              <?php $textoExtra = $cuerpo((string) ($extra['texto'] ?? '')); ?>
              <?php if ($textoExtra !== ''): ?>
                <div class="pl-cuerpo"><?= $textoExtra ?></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </section>
  <?php endforeach; ?>

  <?php /* ═══════════════════════════════════════════════════ HERÁLDICA ════
       La franja rosada con el escudo y el lema. Es una sección propia, con
       su fondo y su altura: el rosado no es de las etapas. */ ?>
  <?php if ($pinta('escudo-lema')): ?>
  <?php
  $textoEscudo = $cuerpo($campo(
      'escudo-lema',
      'texto',
      '<p>El escudo está dividido diagonalmente en dos sectores. La parte <br>'
      . 'superior tiene fondo azul y presenta un lirio blanco. La inferior, <br>'
      . 'sobre fondo claro, lleva la imagen que recuerda a la Orden de <br>'
      . 'San Agustín: un libro cerrado sobre el que descansa un corazón <br>'
      . 'traspasado por una flecha.</p>'
      . '<p>Esa imagen evoca la conversión de San Agustín, que él mismo <br>'
      . 'explicó con las palabras <em>«Vulnerasti cor meum verbo tuo»</em>: has <br>'
      . 'traspasado mi corazón con tu Palabra.</p>'
      . '<p>El lema, <em class="pl-em-fuerte">«In Illo uno unum»</em>, procede de un sermón de San <br>'
      . 'Agustín, la Exposición del Salmo 127, y significa que aunque los <br>'
      . 'cristianos seamos muchos, en el único Cristo somos uno. León <br>'
      . 'XIV confirmó en lo esencial el escudo y el lema que había <br>'
      . 'elegido para su consagración episcopal en Chiclayo.</p>'
  ));
  ?>
  <section class="pl-sec heraldica" aria-labelledby="t-escudo">
    <div class="pl-wrap">

      <p class="heraldica__kicker"><?= $esc($campo('escudo-lema', 'rotulo', 'Heráldica')) ?></p>
      <h2 class="heraldica__h" id="t-escudo"><?= nl2br($esc($campo('escudo-lema', 'titulo', 'El escudo y el lema'))) ?></h2>

      <figure class="heraldica__escudo">
        <?= $sitio->imagen(
            $secciones['escudo-lema'] ?? [],
            $respaldoFoto(
                'p06', 835, 1165,
                'Escudo del Papa León XIV: lirio blanco sobre fondo azul y, sobre fondo claro, un libro '
                . 'cerrado con un corazón traspasado por una flecha, con el lema In Illo uno unum',
                'png'
            ),
            ['sizes' => '(min-width:1024px) 29vw, 100vw']
        ) ?>
      </figure>

      <div class="pl-cuerpo heraldica__txt"><?= $textoEscudo ?></div>

    </div>
  </section>
  <?php endif; ?>

  <?php /* ══════════════════════════════════════════════════ DOCUMENTOS ════
       Su magisterio. Cada documento es un bloque del panel y aparece dos
       veces: arriba en la tabla —nombre, fecha y tipo— y abajo en su
       tarjeta, con la explicación y el botón. */ ?>
  <?php if ($pinta('magisterio-hasta-hoy')): ?>
  <?php
  $documentos = $bloques('magisterio-hasta-hoy', [
      [
          'titulo' => 'Dilexi te',
          'datos'  => ['fecha' => '4 de octubre de 2025', 'fuente' => 'Exhortación Apostólica'],
          'texto'  => '<p><cite>Dilexi te</cite><strong> («Te he amado»)</strong> es su primera <br>'
                    . 'exhortación apostólica y uno de los primeros <br>'
                    . 'grandes documentos de su pontificado. En <br>'
                    . 'ella, León XIV reflexiona sobre el amor de <br>'
                    . 'Dios hacia los pobres y el compromiso de la <br>'
                    . 'Iglesia con ellos.</p>',
          'enlace_texto' => 'Texto completo',
          'enlace_url'   => 'noticias/',
      ],
      [
          'titulo' => 'Magnifica humanitas',
          'datos'  => ['fecha' => 'Firma: 15 de mayo de 2026 | Publicación: 25 de mayo de 2026',
                       'fuente' => 'Carta Encíclica'],
          'texto'  => '<p><cite>Magnifica humanitas</cite> es la primera <br>'
                    . 'encíclica de León XIV. Reflexiona sobre la <br>'
                    . 'protección de la persona y su dignidad en <br>'
                    . 'la era de la inteligencia artificial, así como <br>'
                    . 'sobre sus implicaciones para la vida social.</p>',
          'enlace_texto' => 'Texto completo',
          'enlace_url'   => 'noticias/',
      ],
  ]);

  /* La fecha de un documento puede tener dos renglones —firma y
     publicación—. En el panel se escriben en una sola línea separados por
     una barra vertical, y lo que va antes de los dos puntos se pinta en
     seminegrita, como en el editable. */
  $fechaDocumento = static function (string $fecha) use ($esc): string {
      $renglones = [];

      foreach (explode('|', $fecha) as $trozo) {
          $trozo = trim($trozo);

          if ($trozo === '') {
              continue;
          }

          $dosPuntos = mb_strpos($trozo, ':');

          $renglones[] = $dosPuntos === false
              ? $esc($trozo)
              : '<strong>' . $esc(mb_substr($trozo, 0, $dosPuntos + 1)) . '</strong> '
                . $esc(trim(mb_substr($trozo, $dosPuntos + 1)));
      }

      return implode('<br>', $renglones);
  };

  $leadDocs = $cuerpo($campo(
      'magisterio-hasta-hoy',
      'texto',
      '<p>Los documentos que marcan el inicio de su pontificado y <br>'
      . 'expresan las principales líneas de su magisterio. El contenido <br>'
      . 'completo puede consultarse en la web de la Santa Sede.</p>'
  ));
  ?>
  <section class="pl-sec docs" aria-labelledby="t-magisterio">
    <div class="pl-wrap">

      <p class="docs__kicker"><?= $esc($campo('magisterio-hasta-hoy', 'rotulo', 'Documentos')) ?></p>
      <h2 class="docs__h" id="t-magisterio"><?= nl2br($esc($campo('magisterio-hasta-hoy', 'titulo', 'Su magisterio hasta hoy'))) ?></h2>
      <span class="pl-rule docs__rule" aria-hidden="true"></span>

      <div class="pl-cuerpo docs__lead"><?= $leadDocs ?></div>

      <?php if ($documentos !== []): ?>
      <div class="docs__tabla">
        <?php foreach ($documentos as $doc): ?>
          <div class="docs__fila">
            <p class="docs__nombre"><cite><?= $esc((string) ($doc['titulo'] ?? '')) ?></cite></p>
            <p class="docs__fecha"><?= $fechaDocumento((string) ($doc['datos']['fecha'] ?? '')) ?></p>
            <p class="docs__tipo"><?= $esc((string) ($doc['datos']['fuente'] ?? '')) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="docs__items">
        <?php foreach ($documentos as $i => $doc): ?>
          <?php
          $nombreDoc = (string) ($doc['titulo'] ?? '');
          $textoDoc  = $cuerpo((string) ($doc['texto'] ?? ''));
          $urlDoc    = trim((string) ($doc['enlace_url'] ?? ''));
          $rotuloDoc = trim((string) ($doc['enlace_texto'] ?? '')) ?: 'Texto completo';
          ?>
          <div class="docs__item docs__item--<?= $i + 1 ?>">
            <?= $textoDoc !== '' ? '<div class="pl-cuerpo">' . $textoDoc . '</div>' : '' ?>
            <?php if ($urlDoc !== ''): ?>
              <a class="btn docs__btn" href="<?= $esc(preg_match('~^https?://~i', $urlDoc) === 1 ? $urlDoc : $sitio->enlace($urlDoc)) ?>">
                <?= $esc($rotuloDoc) ?><?php if ($nombreDoc !== ''): ?><span class="visually-hidden"> de <?= $esc($nombreDoc) ?></span><?php endif; ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

</main>
