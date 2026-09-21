<?php
/**
 * ============================================================================
 *  Sedes — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Las medidas son las del editable SEDES.ai (mesa de 1440 x 9339). La hoja
 *  assets/css/paginas/sedes.css las reproduce con la unidad --u: por debajo de
 *  1024 px todo fluye en una columna y, a partir de ahí, cada pieza —un
 *  bloque de texto, una fotografía— va colocada en sus coordenadas del
 *  editable sobre un lienzo de 1220 px centrado.
 *
 *  ── Cómo se hace administrable un lienzo ─────────────────────────────────
 *
 *  La FORMA está aquí, en $intro y $franjas: cuántas piezas tiene cada
 *  franja, en qué orden, con qué clase de posición y de qué lado caen. Eso es
 *  diseño y no se edita desde el panel. El CONTENIDO sale de la base: cada
 *  pieza es un bloque de su sección, con su texto y su fotografía.
 *
 *  De qué tipo es cada pieza lo dice su contenido, no un campo aparte:
 *
 *    · sólo fotografía  → una figura suelta;
 *    · sólo texto       → un bloque de texto, con el «Título» como ladillo;
 *    · las dos cosas    → la fotografía con la cita sobreimpresa (Chiclayo).
 *
 *  Si desde el panel se añade o se quita una pieza, las coordenadas dejan de
 *  corresponder —colocarían un texto donde iba una foto—, así que esa franja
 *  se marca «franja-libre» y pasa al flujo normal. Se ve distinta del
 *  editable, pero se ve bien y no se pierde nada de lo escrito.
 *
 *  ── De dónde sale cada cosa ──────────────────────────────────────────────
 *
 *   · Páginas → Sedes → Cabecera de página ....... el héroe
 *   · Páginas → Sedes → El anuncio de la visita .. la introducción
 *   · Páginas → Sedes → Las sedes ................ la lista de sedes, su
 *     dirección propia (/sedes/lima/) y la ficha que se pinta en ella
 *   · Páginas → Sedes → Lima, Chiclayo, Cusco… ... la franja de cada ciudad
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. Una web sobre un viaje papal no puede quedarse
 *  muda porque falle la base.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'         => 'Sedes · Seis ciudades, una sola nación · León XIV en el Perú',
    'descripcion'    => 'Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa: las seis ciudades de la '
                      . 'Visita Apostólica del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.',
    'og_titulo'      => 'Seis ciudades, una sola nación',
    'og_descripcion' => 'La costa, el norte, los Andes y la Amazonía. Seis maneras de ser Iglesia en el mismo país.',
    'ruta'           => 'sedes/',
    'og_imagen'      => 'assets/img/og/og-sedes.jpg',
    'og_tipo'        => 'article',
    /* Las cuatro jurisdicciones, para el buscador. Se conserva del sitio
       anterior: son datos verificados y llevan meses indexados. */
    'head_extra'     => '<script type="application/ld+json" nonce="' . $esc($sitio->nonce()) . '">
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "Place", "name": "Lima", "address": { "@type": "PostalAddress", "addressLocality": "Lima", "addressCountry": "PE" }, "description": "Arquidiócesis de Lima, primada del Perú. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Callao", "address": { "@type": "PostalAddress", "addressLocality": "Callao", "addressCountry": "PE" }, "description": "Diócesis del Callao, de la que Robert Prevost fue administrador apostólico entre 2020 y 2021. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Chiclayo", "address": { "@type": "PostalAddress", "addressLocality": "Chiclayo", "addressRegion": "Lambayeque", "addressCountry": "PE" }, "description": "Diócesis de Chiclayo, de la que Robert Prevost fue obispo entre 2015 y 2023. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Cusco", "address": { "@type": "PostalAddress", "addressLocality": "Cusco", "addressCountry": "PE" }, "description": "Arquidiócesis del Cusco. Sede del viaje apostólico de León XIV, noviembre de 2026." },
    { "@type": "Place", "name": "Pucallpa", "address": { "@type": "PostalAddress", "addressLocality": "Pucallpa", "addressRegion": "Ucayali", "addressCountry": "PE" }, "description": "Vicariato Apostólico de Pucallpa, en la Amazonía peruana. Sede del viaje apostólico de León XIV, noviembre de 2026." }
  ]
}
</script>',
];

$paginaCms = $sitio->contenido('sedes');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);

/* ── Apagar una sección sí; quedarse en blanco, no ────────────────────────
   Una sección que el panel apaga no llega en $secciones y no se pinta. Pero
   si la base no responde tampoco llega NINGUNA, y entonces «apagado» y
   «caído» se confunden: la página entera desaparecería. Por eso, cuando no
   ha llegado ni una sección, se pintan todas con sus textos de reserva. */
$bdMuda = $secciones === [];
$pinta  = static fn (string $s): bool => $bdMuda || $hay($s);

/* ── El HTML con formato del panel ────────────────────────────────────────
   Se filtra antes de pintarlo: editar un texto no puede dar el poder de
   ejecutar código en el navegador de un visitante. Lo que llegue sin
   etiquetas se escapa y conserva sus saltos de línea, que es la forma de
   clavar el corte de línea del editable desde un campo de texto llano. */
$rico = static function (string $v) use ($esc): string {
    $v = trim($v);

    if ($v === '') {
        return '';
    }

    if (!str_contains($v, '<')) {
        return '<p>' . nl2br($esc($v)) . '</p>';
    }

    /* HtmlSeguro deja pasar <h3> pero se lleva por delante sus atributos:
       el panel no puede escribir clases. Los ladillos que vengan dentro de
       un texto se marcan aquí para que lleven el estilo del editable. */
    return str_replace(
        '<h3>',
        '<h3 class="sede__h3 sede__h3--sig">',
        \Intranet\Core\HtmlSeguro::limpiar($v)
    );
};

/* ── Destinos que vienen del panel ────────────────────────────────────────
   En el panel se escriben cortos («agenda/»). Desde /sedes/ un enlace
   relativo apuntaría a /sedes/agenda/, que no existe, así que se cuelgan de
   la raíz del sitio. Lo que ya venga absoluto, con esquema o como ancla, se
   respeta tal cual. */
$aRaiz = static function (string $url) use ($sitio): string {
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    return preg_match('~^(?:https?:|mailto:|tel:|/|#)~i', $url) === 1
        ? $url
        : $sitio->enlace($url);
};

/* ── De qué tipo es una pieza ─────────────────────────────────────────────
   Lo dice su contenido. Un bloque con fotografía y sin texto es una figura;
   con texto y sin fotografía, un bloque de texto; con las dos cosas, la
   fotografía con la cita sobreimpresa. */
$tipoPieza = static function (array $p): string {
    $conFoto  = !empty($p['imagen_ruta']) || !empty($p['img']);
    $conTexto = trim((string) ($p['texto'] ?? '')) !== ''
             || trim((string) ($p['titulo'] ?? '')) !== ''
             || trim((string) ($p['rotulo'] ?? '')) !== '';

    if ($conFoto) {
        return $conTexto ? 'cita' : 'foto';
    }

    return 'texto';
};

/* El <picture> de una pieza: el del panel si hay imagen elegida y, si no, el
   de reserva con la fotografía del editable. */
$fotoPieza = static function (array $p, string $sizes) use ($sitio, $esc): string {
    ob_start();

    if (!empty($p['img'])):
        $base = 'assets/img/rediseno/sedes/' . $p['img'];
        ?>
        <picture>
          <source srcset="<?= $esc($sitio->asset($base . '.webp')) ?>" type="image/webp">
          <img src="<?= $esc($sitio->asset($base . '.jpg')) ?>"
               alt="<?= $esc((string) ($p['alt'] ?? '')) ?>"
               width="<?= (int) ($p['w'] ?? 0) ?>" height="<?= (int) ($p['h'] ?? 0) ?>"
               loading="lazy" decoding="async">
        </picture>
        <?php
    endif;

    $respaldo = (string) ob_get_clean();

    return $sitio->imagen($p, $respaldo, ['sizes' => $sizes]);
};

/* Las fotografías ocupan entre el 34 % y el 44 % del ancho en escritorio, y
   la hoja las limita a 540 px en tableta. */
$sizesFoto = '(min-width:1024px) 44vw, (min-width:600px) 540px, 100vw';

/* ══════════════════════════════════════════════════════════════════════════
   LA COMPOSICIÓN DEL EDITABLE
   ══════════════════════════════════════════════════════════════════════════
   Cada entrada es una pieza, en el orden en que se lee en móvil. «clase» es
   su posición en el lienzo de escritorio y «lado» de qué borde cuelga el
   texto. Lo demás es el contenido de reserva: lo que dice el editable. */

$intro = [
    'titulo' => 'El anuncio de la visita',
    'piezas' => [
        [
            'clase' => 'b-int-1', 'lado' => 'izq',
            'texto' => '<p>El 5 de agosto de 2026, <strong>la Santa Sede anunció <br>oficialmente que el Papa León XIV visitará Lima</strong>, <br>Chiclayo, Cusco y Pucallpa del 11 al 16 de noviembre <br>de 2026. El anuncio confirmó las seis ciudades, pero <br>dejó pendiente la publicación del programa <br>detallado del viaje.</p>'
                     . '<p>La elección de estas sedes permite recorrer distintos <br>rostros de la Iglesia peruana: Lima - y la provincia <br>del Callao - sede de la Iglesia metropolitana y <br>centro histórico de la evangelización del país; <br>Chiclayo - y el pueblo de Santa Cruz - la diócesis <br>que León XIV pastoreó como obispo; Cusco, con su <br>profunda tradición de fe y evangelización en los <br>Andes; y Pucallpa, en el corazón de la Amazonía, <br>donde la Iglesia vive su misión junto a comunidades <br>y pueblos diversos.</p>',
        ],
        [
            'clase' => 'b-int-2', 'img' => 'p01', 'w' => 847, 'h' => 774,
            'alt'   => 'El Papa León XIV eleva el evangeliario durante una celebración',
        ],
        [
            'clase' => 'b-int-3', 'img' => 'p02', 'w' => 956, 'h' => 640,
            'alt'   => 'El Papa León XIV inciensa el altar rodeado de rosas blancas',
        ],
        [
            'clase' => 'b-int-4', 'lado' => 'der',
            'texto' => '<p>Pero este recorrido también tiene un <br>significado personal para el Santo Padre. <br><strong>León XIV llegó al Perú como misionero en <br>1985</strong> y desarrolló aquí buena parte de su <br>vida pastoral: primero en Chulucanas y luego <br>en Trujillo, antes de ser nombrado obispo de <br>Chiclayo en 2015. En 2015 obtuvo además la <br>nacionalidad peruana por naturalización.</p>'
                     . '<p>Costa, sierra y selva. Seis ciudades, seis <br>realidades eclesiales y una misma nación <br>que se prepara para recibir a un Papa que <br>conoce de cerca la vida, fe y misión de la <br>Iglesia en el Perú.</p>',
        ],
    ],
];

$franjas = [

    /* ─────────────────────────────────────────────────── LIMA — CALLAO ── */
    'lima' => [
        'titulo'    => 'Lima - Callao',
        'subtitulo' => "Arquidiócesis de Lima · Primada del Perú\nDiócesis del Callao",
        'fecha'     => '11, 12 y 16 de noviembre',
        'cta'       => ['texto' => 'Ver Agenda', 'url' => 'agenda/', 'clase' => 'b-lima-10', 'tam' => 'sm'],
        'piezas'    => [
            [
                'clase' => 'b-lima-1', 'lado' => 'izq', 'titulo' => '¿Por qué esta ciudad?',
                'texto' => '<p>Lima y Callao la puerta de entrada del Santo Padre <br>al Perú. La Diócesis del Callao, es una jurisdicción <br>eclesiástica con 59 años de creación. Por su parte, <br>Lima es la sede de la Arquidiócesis Primada del Perú <br>y fue creada el 12 de febrero de 1546 por el papa <br>Paulo III y, desde sus primeros años, se convirtió en <br>un importante centro desde el que se extendió la <br>misión evangelizadora por amplios territorios de <br>América del Sur. En 1572 recibió el título de Primada <br>del Perú, posteriormente confirmado en 1834 y <br>ratificado en 1943.</p>',
            ],
            [
                'clase' => 'b-lima-2', 'img' => 'p03', 'w' => 841, 'h' => 765,
                'alt'   => 'El Papa León XIV sostiene la imagen de un santo obispo durante una audiencia',
            ],
            [
                'clase' => 'b-lima-3', 'img' => 'p14', 'w' => 1194, 'h' => 568,
                'alt'   => 'Basílica Catedral de Lima en la Plaza Mayor al atardecer',
            ],
            [
                'clase' => 'b-lima-4', 'lado' => 'der',
                'texto' => '<p>Esta historia tiene en <strong>Santo Toribio de <br>Mogrovejo </strong>una de sus figuras centrales. <br>Segundo arzobispo de Lima, recorrió <br>extensamente su territorio, impulsó la formación <br>del clero y promovió la evangelización en las <br>lenguas de los pueblos originarios. La Santa <br>Sede lo reconoce como una figura fundamental <br>de la evangelización de América y patrono del <br>Episcopado Latinoamericano.</p>',
            ],
            [
                'clase' => 'b-lima-5', 'img' => 'p05', 'w' => 1010, 'h' => 556,
                'alt'   => 'Retrato del arzobispo de Lima con alzacuellos y cruz pectoral',
            ],
            [
                'clase' => 'b-lima-6', 'lado' => 'izq', 'titulo' => 'Su relación con León XIV',
                'texto' => '<p>El vínculo de León XIV con Lima está unido a su <br>servicio a la Iglesia del Perú. Como obispo de <br>Chiclayo, fue elegido en marzo de 2018 segundo <br>vicepresidente de la Conferencia Episcopal Peruana, <br>además de integrar su Consejo Económico y presidir <br>la Comisión Episcopal de Cultura y Educación. En <br>abril de 2020 fue nombrado también administrador <br>apostólico de la diócesis del Callao.</p>'
                         . '<p>De 2020 hasta 2021, el Papa León XIV sirvió como <br>administrador apostólico de la Diócesis del Callao. En <br>plena pandemia de 2020 llevó las riendas de la <br>Iglesia del Callao y, en medio de las dificultades <br>propias de ese tiempo, se entregó completamente a <br>la tarea de velar por el cuidado de esta Diócesis.</p>',
            ],
            [
                'clase' => 'b-lima-7', 'img' => 'p04', 'w' => 1010, 'h' => 556,
                'alt'   => 'El obispo administra el sacramento de la confirmación con mascarillas durante la pandemia',
            ],
            [
                'clase' => 'b-lima-8', 'lado' => 'izq', 'titulo' => '¿Qué encontraremos?',
                'texto' => '<p>Lima reúne algunas de las expresiones más <br>significativas de la fe del pueblo peruano: la Basílica <br>Catedral, la memoria de santo Toribio de Mogrovejo, la <br>devoción a Santa Rosa de Lima y la profunda tradición <br>de piedad popular expresada en el Señor de los <br>Milagros.</p>'
                         . '<p>La procesión del Cristo de Pachacamilla convoca cada <br>octubre a miles de fieles en las calles de la capital. El <br>propio León XIV se hizo cercano a esta tradición <br>cuando, en octubre de 2025, saludó desde Roma a la <br>Hermandad del Señor de los Milagros con ocasión de <br>su tradicional procesión.</p>',
            ],
            [
                'clase' => 'b-lima-9', 'img' => 'p06', 'w' => 1010, 'h' => 578,
                'alt'   => 'Celebración ante la imagen del Señor de los Milagros adornada con flores moradas',
            ],
        ],
    ],

    /* ────────────────────────────────────────── CHICLAYO — SANTA CRUZ ── */
    'chiclayo' => [
        'titulo'     => 'Chiclayo - Santa Cruz',
        /* El editable pone la segunda mitad del titular en cuerpo menor y sin
           negrita. Se parte por el último « - ». */
        'titulo_alt' => true,
        'subtitulo'  => 'Diócesis de Chiclayo · Lambayeque y provincia de Santa Cruz, Cajamarca',
        'fecha'      => '13 - 14 de noviembre',
        'cta'        => ['texto' => 'Ver Agenda', 'url' => 'agenda/', 'clase' => 'b-chi-7', 'tam' => 'sm'],
        'piezas'     => [
            [
                'clase' => 'b-chi-1', 'lado' => 'izq', 'titulo' => '¿Por qué esta ciudad?',
                'texto' => '<p>Hay lugares que forman parte de una historia y otros <br>que se convierten en parte de la vida. Para León XIV, <br>Chiclayo es ambas cosas. Es la diócesis que lo <br>recibió como administrador apostólico en 2014, <br>donde fue ordenado obispo y donde ejerció su <br>ministerio pastoral durante más de ocho años.</p>'
                         . '<p>La Diócesis de Chiclayo fue creada el 17 de diciembre <br>de 1956 por el papa Pío XII, mediante la bula Sicut <br>materfamilias. Su jurisdicción comprende todo el <br>departamento de Lambayeque y la provincia <br>cajamarquina de Santa Cruz. Su sede es la iglesia <br>Santa María Catedral, cuya construcción comenzó en <br>1869 y que hoy forma parte de la memoria y de la <br>vida de la Iglesia local.</p>',
            ],
            [
                'clase' => 'b-chi-2', 'img' => 'p07', 'w' => 531, 'h' => 478,
                'alt'   => 'El obispo Robert Prevost eleva una cruz de plata rodeado de fieles',
            ],
            [
                'clase'  => 'b-chi-3', 'img' => 'p08', 'w' => 804, 'h' => 1237,
                'alt'    => 'El Papa León XIV saluda con los brazos en alto tras su proclamación',
                'rotulo' => "Palabras del proclamado\nPapa León XIV a su antigua\ndiócesis en Chiclayo:",
                'texto'  => '<p>“Mi querida diócesis de Chiclayo, en el <br>Perú, donde un pueblo fiel ha <br>acompañado a su obispo, ha compartido <br>su fe y ha dado tanto, tanto, para seguir <br>siendo Iglesia fiel de Jesucristo”.</p>',
            ],
            [
                'clase' => 'b-chi-4', 'lado' => 'der', 'titulo' => 'Su relación con León XIV',
                'texto' => '<p>El vínculo entre León XIV y Chiclayo es <br>profundamente pastoral y personal. <br>El 3 de noviembre de 2014, el papa Francisco lo <br>nombró administrador apostólico de la diócesis. El 7 <br>de noviembre tomó posesión y, el 12 de diciembre, <br>fiesta de Nuestra Señora de Guadalupe, recibió la <br>ordenación episcopal en la Catedral de Santa <br>María. El 26 de septiembre de 2015 fue nombrado <br>obispo de Chiclayo.</p>'
                         . '<p>Durante sus años en la diócesis, recorrió sus <br>comunidades, acompañó a sacerdotes y fieles y <br>participó activamente en la vida de la Iglesia local. <br>En marzo de 2018 fue elegido segundo <br>vicepresidente de la Conferencia Episcopal <br>Peruana y, desde esa responsabilidad, continuó <br>sirviendo a la Iglesia del país.</p>'
                         . '<p>En 2023, al ser llamado a Roma, la Conferencia <br>Episcopal Peruana le concedió la Medalla de Oro <br>de Santo Toribio de Mogrovejo y la Universidad <br>Católica Santo Toribio de Mogrovejo (USAT) le <br>otorgó el doctorado honoris causa en Derecho, en <br>reconocimiento a su servicio a la Iglesia en el Perú.</p>',
            ],
            [
                'clase' => 'b-chi-5', 'img' => 'p09', 'w' => 1056, 'h' => 884,
                'alt'   => 'Catedral Santa María de Chiclayo vista desde la plaza',
            ],
            [
                'clase' => 'b-chi-6', 'lado' => 'izq', 'titulo' => '¿Qué esperar?',
                'texto' => '<p>En Lambayeque, la fe se expresa en sus parroquias, <br>peregrinaciones y celebraciones, y encuentra en <br>Ciudad Eten uno de sus lugares más significativos. Allí, <br>la tradición del Milagro Eucarístico de 1649 ha dado <br>origen a una arraigada devoción y a numerosas <br>peregrinaciones de fieles. Durante sus años como <br>obispo, Robert Prevost acompañó personalmente esta <br>tradición y llevó a Roma documentación sobre su <br>historia y miles de testimonios de fe.</p>',
            ],
        ],
    ],

    /* ─────────────────────────────────────────────────────────── CUSCO ── */
    'cusco' => [
        'titulo'    => 'Cusco',
        'subtitulo' => 'Arquidiócesis del Cusco',
        'fecha'     => '15 de noviembre',
        'cta'       => ['texto' => 'Ver Agenda', 'url' => 'agenda/', 'clase' => 'b-cus-6', 'tam' => 'lg'],
        'piezas'    => [
            [
                'clase' => 'b-cus-1', 'lado' => 'izq', 'titulo' => '¿Por qué esta ciudad?',
                'texto' => '<p>En Cusco, la fe tiene raíces profundas. <strong>La Iglesia llegó <br>a los Andes en los primeros tiempos de la <br>evangelización del Perú</strong> y, desde entonces, la vida <br>cristiana fue tomando rostro propio en sus <br>comunidades, celebraciones y tradiciones. La antigua <br>sede episcopal del Cusco fue elevada a <br>arquidiócesis en 1943.</p>'
                         . '<p>Hoy, esa historia sigue viva. Se reza en castellano y en <br>quechua; se celebra en las ciudades, en los pueblos <br>y en las comunidades de altura; y la fe se expresa en <br>peregrinaciones, fiestas y devociones que forman <br>parte de la identidad del pueblo cusqueño.</p>',
            ],
            [
                'clase' => 'b-cus-2', 'img' => 'p10', 'w' => 378, 'h' => 375,
                'alt'   => 'Iglesia de la Compañía de Jesús en la Plaza de Armas del Cusco',
            ],
            [
                'clase' => 'b-cus-3', 'lado' => 'izq', 'titulo' => 'Su relación con León XIV',
                'texto' => '<p>En Trujillo, entre 1988 y 1998, dirigió la primera casa de <br>formación conjunta de los vicariatos agustinianos de <br>Chulucanas, Iquitos y Apurímac. Desde allí acompañó <br>la formación de religiosos llamados a servir en <br>territorios tan diversos como los Andes y la Amazonía.</p>'
                         . '<p>Por eso, aunque Cusco no forma parte de su historia <br>pastoral directa, sí representa una de las realidades de <br>la Iglesia peruana que León XIV conoció y valoró desde <br>su experiencia misionera y formativa.</p>',
            ],
            [
                'clase' => 'b-cus-4', 'img' => 'p11', 'w' => 674, 'h' => 864,
                'alt'   => 'Un obispo se dirige a los fieles con un micrófono durante una celebración',
            ],
            [
                'clase' => 'b-cus-5', 'lado' => 'izq', 'titulo' => '¿Qué esperar?',
                'texto' => '<p>El Señor de los Temblores, Patrón Jurado del Cusco, <br>cuya procesión del Lunes Santo detiene la ciudad <br>entera. El Corpus Christi cusqueño, con sus <br>imágenes recorriendo las calles hasta la catedral. Y <br>la Virgen de los Remedios, patrona de la <br>arquidiócesis. Cusco no necesita que le enseñen a <br>recibir: lleva siglos haciéndolo.</p>',
            ],
        ],
    ],

    /* ──────────────────────────────────────────────────────── PUCALLPA ── */
    'pucallpa' => [
        'titulo'    => 'Pucallpa',
        'subtitulo' => 'Vicariato Apostólico de Pucallpa · Ucayali',
        'fecha'     => '15 de noviembre',
        'cta'       => ['texto' => 'Ver Agenda', 'url' => 'agenda/', 'clase' => 'b-puc-5', 'tam' => 'lg'],
        'piezas'    => [
            [
                'clase' => 'b-puc-1', 'lado' => 'izq', 'titulo' => '¿Por qué esta ciudad?',
                'texto' => '<p><strong>Pucallpa abre la Visita Apostólica al corazón de la <br>Amazonía peruana.</strong> No es sede de una diócesis, sino <br>de un Vicariato Apostólico: una circunscripción <br>eclesiástica destinada a territorios de misión y <br>directamente dependiente de la Santa Sede.</p>'
                         . '<p>El Vicariato Apostólico de Pucallpa fue creado el 2 de <br>marzo de 1956 por el papa Pío XII, a partir de la división <br>del antiguo Vicariato Apostólico de Ucayali. Su territorio <br>abarca más de 52 000 kilómetros cuadrados y <br>comprende las provincias de Coronel Portillo y Padre <br>Abad, parte de Atalaya, en Ucayali, y la parte oriental <br>de Puerto Inca, en Huánuco. Su sede está en la ciudad <br>de Pucallpa, donde se encuentra la Catedral de la <br>Inmaculada Concepción.</p>'
                         . '<p>Que Pucallpa forme parte de las cinco sedes de la <br>Visita Apostólica expresa algo esencial: la Amazonía no <br>está al margen de la vida de la Iglesia en el Perú. Es <br>parte de su rostro, misión y esperanza.</p>',
            ],
            [
                'clase' => 'b-puc-2', 'img' => 'p12', 'w' => 679, 'h' => 970,
                'alt'   => 'Catedral de la Inmaculada Concepción de Pucallpa',
            ],
            [
                /* El editable junta aquí dos ladillos en la misma columna: el
                   segundo viaja dentro del texto y la vista le pone su clase. */
                'clase' => 'b-puc-4', 'lado' => 'der', 'titulo' => 'Su relación con León XIV',
                'texto' => '<p>La relación de León XIV con la Amazonía peruana se <br>remonta a sus años como misionero y formador en <br>el país.</p>'
                         . '<p>Cuando regresó al Perú en 1988, fue enviado a Trujillo <br>para dirigir la primera casa de formación conjunta <br>de los vicariatos agustinianos de Chulucanas, Iquitos <br>y Apurímac. Allí acompañó durante una década la <br>formación de religiosos llamados a servir en <br>realidades muy distintas del país, entre ellas la <br>Amazonía.</p>'
                         . '<p>Años después, ya como prefecto del Dicasterio para <br>los Obispos y presidente de la Pontificia Comisión <br>para América Latina, su servicio en la Iglesia universal <br>lo puso en contacto con numerosas Iglesias <br>particulares y territorios de misión de América Latina.</p>'
                         . '<h3>¿Qué esperar?</h3>'
                         . '<p>La misión del Vicariato se desarrolla junto a <br>poblaciones urbanas, rurales y comunidades <br>indígenas de Ucayali. Su labor pastoral comprende <br>la evangelización, la formación de agentes <br>pastorales, el acompañamiento de las comunidades <br>y el servicio a los pueblos que habitan este territorio.</p>'
                         . '<p>En esta tierra, la misión de la Iglesia también está <br>estrechamente vinculada al cuidado de la vida y de <br>la casa común. La Amazonía no es únicamente el <br>escenario de la visita: es una realidad humana, <br>cultural y espiritual que el Santo Padre viene a <br>encontrar y escuchar.</p>',
            ],
            [
                'clase' => 'b-puc-3', 'img' => 'p13', 'w' => 623, 'h' => 1015,
                'alt'   => 'El Papa León XIV con báculo sobre una vista aérea del río Ucayali',
            ],
        ],
    ],
];

/* ── La lista de sedes ────────────────────────────────────────────────────
   Sale de «Las sedes», que es la misma sección de la que cuelgan las páginas
   propias de cada una: /sedes/lima/ se resuelve buscando este «slug». De
   aquí salen el orden de las franjas y el enlace de cada titular a su ficha;
   el cuerpo de la franja está en la sección de la ciudad. */
$fichas = $bloques('las-cuatro-sedes', [
    ['slug' => 'lima',     'titulo' => 'Lima',     'rotulo' => 'Arquidiócesis de Lima',   'enlace_url' => 'sedes/lima/'],
    ['slug' => 'chiclayo', 'titulo' => 'Chiclayo', 'rotulo' => 'Diócesis de Chiclayo',    'enlace_url' => 'sedes/chiclayo/'],
    ['slug' => 'cusco',    'titulo' => 'Cusco',    'rotulo' => 'Arquidiócesis del Cusco', 'enlace_url' => 'sedes/cusco/'],
    ['slug' => 'pucallpa', 'titulo' => 'Pucallpa', 'rotulo' => 'Vicariato de Pucallpa',   'enlace_url' => 'sedes/pucallpa/'],
]);
?>

<main id="contenido">

  <?php /* ══════════════════════════════════════════════════════ HÉROE ════
       Banda de 582 px con la fotografía a sangre. Se edita en
       Páginas → Sedes → Cabecera de página.

       El titular va en dos cuerpos distintos y la bajada también: del panel
       llega una sola línea de cada uno, así que el titular se parte por la
       primera coma y la bajada por el final de su primera frase, que es
       donde los rompe el editable. Si no traen ni coma ni punto, se pintan
       enteros en la primera línea y no se pierde nada. */ ?>
  <?php
  $tituloHero = $campo('cabecera', 'titulo', 'Seis ciudades, una sola nación');
  $lineas     = preg_split('/,\s*/u', $tituloHero, 2) ?: [$tituloHero];

  $bajadaHero = $campo('cabecera', 'texto', 'La costa, el norte, los Andes y la Amazonía. Seis maneras de ser Iglesia en el mismo país.');
  $frases     = preg_split('/(?<=[.!?])\s+/u', $bajadaHero, 2) ?: [$bajadaHero];
  ?>
  <section class="hero hero--page hero--sedes">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/sedes/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/sedes/hero.jpg')) ?>"
             alt="El Papa León XIV, con mitra y báculo, preside una celebración junto al altar acompañado por dos ministros"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="hero__inner">
      <h1 class="hero__title">
        <span class="hero__t1"><?= $esc($lineas[0] . (isset($lineas[1]) ? ',' : '')) ?></span>
        <?php if (isset($lineas[1])): ?>
          <span class="hero__t2"><?= $esc($lineas[1]) ?></span>
        <?php endif; ?>
      </h1>
      <p class="hero__sub">
        <span class="hero__s1"><?= $esc($frases[0]) ?></span>
        <?php if (isset($frases[1])): ?>
          <span class="hero__s2"><?= $esc($frases[1]) ?></span>
        <?php endif; ?>
      </p>
    </div>
  </section>

  <?php /* ═══════════════════════════════════════════════ INTRODUCCIÓN ════
       Dos columnas de texto y dos fotografías cruzadas. El editable deja en
       blanco la franja de arriba; aquí el gris #EDEDED es continuo.

       El rediseño no dibuja titular en esta sección, así que el suyo se
       pinta sólo para los lectores de pantalla y los buscadores: la sección
       tiene que llamarse de algún modo. */ ?>
  <?php if ($pinta('estas-cuatro')): ?>
  <?php
  $piezasIntro = $bloques('estas-cuatro', $intro['piezas']);
  $exactaIntro = count($piezasIntro) === count($intro['piezas'])
              && $tipoPieza($piezasIntro[0] ?? []) === 'texto';
  $tituloIntro = $campo('estas-cuatro', 'titulo', $intro['titulo']);
  ?>
  <section class="sedes-intro<?= $exactaIntro ? '' : ' franja-libre' ?>" aria-labelledby="t-intro">
    <div class="sede__lienzo">
      <h2 class="visually-hidden" id="t-intro"><?= $esc($tituloIntro) ?></h2>

      <?php foreach ($piezasIntro as $i => $pieza): ?>
        <?php
        $forma = $intro['piezas'][$i] ?? [];
        $clase = $exactaIntro ? (string) ($forma['clase'] ?? '') : '';
        $lado  = $exactaIntro && ($forma['lado'] ?? '') !== '' ? ' blq--' . $forma['lado'] : '';
        ?>
        <?php $tipo = $tipoPieza($pieza); ?>
        <?php if ($tipo !== 'texto'): ?>
          <figure class="blq foto<?= $tipo === 'cita' ? ' foto--cita' : '' ?> <?= $esc($clase) ?>">
            <?= $fotoPieza($pieza, $sizesFoto) ?>
            <?php if ($tipo === 'cita'): ?>
              <figcaption class="cita">
                <?php if (trim((string) ($pieza['rotulo'] ?? '')) !== ''): ?>
                  <p class="cita__intro"><?= nl2br($esc((string) $pieza['rotulo'])) ?></p>
                <?php endif; ?>
                <blockquote class="cita__texto"><?= $rico((string) ($pieza['texto'] ?? '')) ?></blockquote>
              </figcaption>
            <?php endif; ?>
          </figure>
        <?php else: ?>
          <div class="blq<?= $lado ?> tx <?= $esc($clase) ?>">
            <?php if (trim((string) ($pieza['titulo'] ?? '')) !== ''): ?>
              <h3 class="sede__h3"><?= $esc((string) $pieza['titulo']) ?></h3>
            <?php endif; ?>
            <?= $rico((string) ($pieza['texto'] ?? '')) ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <hr class="sedes-intro__linea">
    </div>
  </section>
  <?php endif; ?>

  <?php /* ═══════════════════════════════════════════ FRANJAS DE SEDE ════
       Una por ciudad, alternando el fondo gris y el rosado del editable.
       El titular enlaza a la página propia de la sede; el botón, a la
       agenda. Ver la cabecera de este archivo para el reparto de campos. */ ?>
  <?php foreach ($fichas as $iFicha => $ficha): ?>
    <?php
    $slug = trim((string) ($ficha['slug'] ?? ''));

    if ($slug === '') {
        continue;   // sin dirección propia no hay franja que pintar
    }

    /* La clave de la sección de la ciudad es su slug, y de ahí sale también
       la clase de la franja. Se limpia porque acaba dentro del HTML. */
    $clave = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';

    if ($clave === '') {
        continue;
    }

    /* Aquí NO se comprueba si la sección de la ciudad está encendida: la
       lista de sedes la manda «Las sedes», y una sede que esté en esa lista
       tiene que salir aunque su sección todavía no exista —el día que se
       añada una ciudad, la franja aparece sola con lo que diga su ficha—.
       Para quitar una franja se desactiva su sede en «Las sedes». */

    $forma  = $franjas[$clave] ?? [];
    $piezas = $bloques($clave, $forma['piezas'] ?? []);

    /* Una sede sin sección propia todavía: se pinta con el resumen de su
       ficha, que es lo único escrito que hay de ella. */
    if ($piezas === [] && trim((string) ($ficha['texto'] ?? '')) !== '') {
        $piezas = [['texto' => (string) $ficha['texto']]];
    }

    if ($piezas === []) {
        continue;
    }

    /* La maqueta del editable sólo sirve si la composición sigue siendo la
       suya: mismas piezas, en el mismo orden, y la primera de texto, porque
       comparte bloque con el encabezado. */
    $exacta = isset($forma['piezas'])
           && count($piezas) === count($forma['piezas'])
           && $tipoPieza($piezas[0]) === 'texto';

    $tituloSede = $campo($clave, 'titulo',    (string) ($forma['titulo']    ?? ($ficha['titulo'] ?? '')));
    $subSede    = $campo($clave, 'subtitulo', (string) ($forma['subtitulo'] ?? ($ficha['rotulo'] ?? '')));
    $fechaSede  = $campo($clave, 'rotulo',    (string) ($forma['fecha']     ?? ''));

    $ctaTexto = $campo($clave, 'cta_texto', (string) ($forma['cta']['texto'] ?? 'Ver Agenda'));
    $ctaUrl   = $aRaiz($campo($clave, 'cta_url', (string) ($forma['cta']['url'] ?? 'agenda/')));
    $ctaTam   = (string) ($forma['cta']['tam'] ?? 'sm');
    $ctaClase = $exacta ? (string) ($forma['cta']['clase'] ?? '') : '';

    $urlFicha = $aRaiz((string) ($ficha['enlace_url'] ?? '')) ?: $sitio->enlace('sedes/' . $clave . '/');

    /* El titular de Chiclayo lleva la segunda mitad en cuerpo menor. */
    $tituloHtml = $esc($tituloSede);

    if (!empty($forma['titulo_alt'])) {
        $corte = mb_strrpos($tituloSede, ' - ');

        if ($corte !== false) {
            $tituloHtml = $esc(mb_substr($tituloSede, 0, $corte + 3))
                        . '<span class="sede__h-alt">' . $esc(mb_substr($tituloSede, $corte + 3)) . '</span>';
        }
    }

    /* Franjas impares en rosado, como el editable. La regla es de posición y
       no de ciudad: si mañana se añade una sede, sigue alternando. */
    $clases = 'sede sede--' . $clave
            . ($iFicha % 2 === 1 ? ' sede--rosa' : '')
            . ($exacta ? '' : ' franja-libre');

    $primera = $exacta ? ($piezas[0] ?? []) : null;
    $resto   = $exacta ? array_slice($piezas, 1) : $piezas;
    $desde   = $exacta ? 1 : 0;
    ?>
    <section class="<?= $esc($clases) ?>" aria-labelledby="t-<?= $esc($clave) ?>">
      <div class="sede__lienzo">

        <?php /* El encabezado comparte bloque con la primera pieza de texto:
                 en el editable el ladillo va pegado a la fecha. */ ?>
        <div class="blq<?= $exacta ? ' blq--izq' : '' ?> tx <?= $esc($exacta ? (string) ($forma['piezas'][0]['clase'] ?? '') : '') ?>">
          <h2 class="sede__h" id="t-<?= $esc($clave) ?>">
            <a href="<?= $esc($urlFicha) ?>"><?= $tituloHtml ?></a>
          </h2>

          <?php if (trim($subSede) !== ''): ?>
            <p class="sede__sub"><?= nl2br($esc($subSede)) ?></p>
          <?php endif; ?>

          <?php if (trim($fechaSede) !== ''): ?>
            <p class="sede__fecha"><?= $esc($fechaSede) ?></p>
          <?php endif; ?>

          <?php if ($primera !== null): ?>
            <?php if (trim((string) ($primera['titulo'] ?? '')) !== ''): ?>
              <h3 class="sede__h3"><?= $esc((string) $primera['titulo']) ?></h3>
            <?php endif; ?>
            <?= $rico((string) ($primera['texto'] ?? '')) ?>
          <?php endif; ?>
        </div>

        <?php foreach ($resto as $j => $pieza): ?>
          <?php
          $formaPieza = $exacta ? ($forma['piezas'][$j + $desde] ?? []) : [];
          $clasePieza = $exacta ? (string) ($formaPieza['clase'] ?? '') : '';
          $ladoPieza  = $exacta && ($formaPieza['lado'] ?? '') !== '' ? ' blq--' . $formaPieza['lado'] : '';
          $tipo       = $tipoPieza($pieza);
          ?>

          <?php if ($tipo === 'cita'): ?>
            <?php /* La fotografía con las palabras del Santo Padre encima. */ ?>
            <figure class="blq foto foto--cita <?= $esc($clasePieza) ?>">
              <?= $fotoPieza($pieza, $sizesFoto) ?>
              <figcaption class="cita">
                <?php if (trim((string) ($pieza['rotulo'] ?? '')) !== ''): ?>
                  <p class="cita__intro"><?= nl2br($esc((string) $pieza['rotulo'])) ?></p>
                <?php endif; ?>
                <blockquote class="cita__texto"><?= $rico((string) ($pieza['texto'] ?? '')) ?></blockquote>
              </figcaption>
            </figure>

          <?php elseif ($tipo === 'foto'): ?>
            <figure class="blq foto <?= $esc($clasePieza) ?>">
              <?= $fotoPieza($pieza, $sizesFoto) ?>
            </figure>

          <?php else: ?>
            <div class="blq<?= $ladoPieza ?> tx <?= $esc($clasePieza) ?>">
              <?php if (trim((string) ($pieza['titulo'] ?? '')) !== ''): ?>
                <h3 class="sede__h3"><?= $esc((string) $pieza['titulo']) ?></h3>
              <?php endif; ?>
              <?= $rico((string) ($pieza['texto'] ?? '')) ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($ctaTexto !== '' && $ctaUrl !== ''): ?>
          <a class="btn sede__cta sede__cta--<?= $esc($ctaTam) ?> blq <?= $esc($ctaClase) ?>"
             href="<?= $esc($ctaUrl) ?>"><?= $esc($ctaTexto) ?></a>
        <?php endif; ?>

      </div>
    </section>
  <?php endforeach; ?>

</main>
