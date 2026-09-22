<?php
/**
 * ============================================================================
 *  Agenda — «Días de encuentro». Rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta
 *  igual. Una web sobre un viaje papal no puede quedarse muda porque falle
 *  la base. La reserva es la del editable salvo en el cronograma, donde el
 *  editable trae relleno y se usa el programa referencial de producción.
 *
 *  ── Cómo se organiza el cronograma ───────────────────────────────────────
 *
 *  El editable agrupa las jornadas POR SEDE: cada ciudad con su titular, su
 *  fotografía y debajo los días que le tocan. La base, en cambio, guarda una
 *  lista plana de jornadas (sección «itinerario», plantilla «jornadas»), y
 *  cada jornada dice a qué ciudad pertenece en su título: «Lima · Llegada y
 *  bienvenida oficial».
 *
 *  Así que la vista hace de costurera: coge las sedes de «cuatro-ventanas»
 *  —nombre, fotografía y orden— y reparte entre ellas las jornadas por la
 *  ciudad con la que empieza cada título. Una jornada cuya ciudad no figure
 *  entre las sedes NO se pierde: se le abre su propio apartado al final.
 *
 *  ── Los horarios ─────────────────────────────────────────────────────────
 *
 *  El editable dibuja «XX:00 Hrs.» y «Lorem ipsum»: es relleno. Lo real son
 *  las actividades que ya hay en la base, que todavía no tienen hora porque
 *  la Santa Sede no ha publicado el programa. Por eso cada actividad se lee
 *  entera y sólo se pinta la hora en granate si el texto empieza por una:
 *  «12:00 Hrs. Llegada al Aeropuerto del Callao». El día que llegue el
 *  programa oficial basta con escribir la hora delante en el panel.
 *
 *  Las medidas son las del editable AGENDA.ai (mesa de 1440 px). La hoja
 *  assets/css/paginas/agenda.css las reproduce con la unidad --u.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'         => 'Agenda · León XIV en el Perú · 11–16 de noviembre de 2026',
    'descripcion'    => 'Días de encuentro: cronograma del viaje apostólico del Papa León XIV al Perú '
                      . 'en Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa, del 11 al 16 de '
                      . 'noviembre de 2026.',
    'og_titulo'      => 'Agenda · León XIV en el Perú',
    'og_descripcion' => 'El Papa León XIV estará en el Perú del 11 al 16 de noviembre de 2026.',
    'ruta'           => 'agenda/',
    'og_imagen'      => 'assets/img/og/og-agenda.jpg',
    'og_tipo'        => 'article',
];

$paginaCms = $sitio->contenido('agenda');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);

/* ── Realce del texto del héroe ───────────────────────────────────────────
   El editable parte la bajada antes de la fecha y pone «11 al 16 de
   noviembre» en semibold. El campo del panel es texto plano —la plantilla
   «cabecera_pagina» no admite HTML— y no vamos a imprimir sin escapar lo que
   salga de un formulario. La convención es la de toda la vida: un salto de
   línea donde se quiera partir y **asteriscos** para la negrita. Se escapa
   PRIMERO y se marcan después las dos únicas cosas admitidas, así que del
   panel no puede salir ninguna otra etiqueta. */
$realce = static function (string $texto) use ($esc): string {
    $html = $esc($texto);
    $html = (string) preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $html);

    return nl2br($html, false);
};

/* ── Rótulos con interletraje del editable ───────────────────────────────
   «Lima - Callao» se dibuja «L I M A   -   C A L L A O»: el espacio entre
   letras es parte del texto, no un letter-spacing, porque el editable ajusta
   además el interletraje en negativo. Separando cada carácter con un espacio
   sale exactamente eso —el espacio entre palabras queda triple, como en el
   editable— y las hojas de esta página lo conservan con «white-space:pre-wrap».

   Si en el panel ya lo escriben espaciado, se deja tal cual: espaciarlo dos
   veces lo dejaría el doble de ancho. */
$espaciado = static function (string $texto): string {
    $texto = trim($texto);

    if ($texto === '') {
        return '';
    }

    $palabras = preg_split('/\s+/u', $texto) ?: [];
    $sueltas  = array_map(static fn (string $p): int => (int) mb_strlen($p, 'UTF-8'), $palabras);

    if (count($palabras) > 1 && max($sueltas) === 1) {
        return mb_strtoupper($texto, 'UTF-8');   // ya venía espaciado
    }

    $letras = preg_split('//u', mb_strtoupper($texto, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    return implode(' ', $letras);
};

/* La primera parte de un nombre compuesto: de «Lima · Llegada y bienvenida»
   y de «Lima - Callao» sale «Lima» en los dos casos. Es lo que empareja cada
   jornada con su sede. */
$primero = static function (string $texto): string {
    $partes = preg_split('/\s*[·•–—\-,|:]\s*/u', trim($texto), 2) ?: [];

    return trim((string) ($partes[0] ?? ''));
};

/* Esa primera parte, convertida en clave de filtro: «Lima» → «lima». Es la
   que viaja en data-key y en data-filter, y la que resuelve assets/js/rediseno.js. */
$clave = static function (string $texto) use ($primero): string {
    $base = mb_strtolower($primero($texto), 'UTF-8');
    $base = strtr($base, [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ]);

    return trim((string) preg_replace('/[^a-z0-9]+/', '-', $base), '-');
};

/* ── La fecha de la jornada ──────────────────────────────────────────────
   En la base es «11 de noviembre»; el editable la parte en un «11» grande y
   un «NOV» debajo. Si el rótulo no trae día y mes reconocibles se pinta
   entero en la línea pequeña: más vale una fecha rara que ninguna. */
$fecha = static function (string $rotulo): array {
    $rotulo = trim($rotulo);

    /* Las tres primeras letras del mes bastan: los doce se distinguen por
       ellas en español —ENE, FEB, MAR…— y así no hay tabla que mantener ni
       forma escrita que se quede fuera («setiembre» sale SET, como aquí se
       dice). */
    if (preg_match('/^(\d{1,2})\s*(?:de\s+)?(\p{L}+)?/u', $rotulo, $m) === 1) {
        $mes = isset($m[2]) ? mb_substr($m[2], 0, 3, 'UTF-8') : '';

        return ['dia' => $m[1], 'mes' => mb_strtoupper($mes, 'UTF-8')];
    }

    return ['dia' => '', 'mes' => $rotulo];
};

/* ── Un acto del día ─────────────────────────────────────────────────────
   «12:00 Hrs. Llegada al Aeropuerto del Callao» se parte en hora y acto; si
   la actividad no empieza por una hora, se pinta entera como acto y la línea
   granate no aparece. Mientras la Santa Sede no publique el programa, eso es
   lo que hay en la base y es lo honrado: ninguna hora inventada. */
$acto = static function (string $linea): array {
    $linea = trim($linea);
    $patron = '/^([0-9Xx]{1,2}\s*[:.h]\s*[0-9Xx]{2}\s*(?:hrs?|horas?|h)?\.?)\s*(?:[—–\-|·:]+\s*)?(.*)$/ui';
    $hora = '';
    $que  = $linea;

    if (preg_match($patron, $linea, $m) === 1) {
        $hora = trim($m[1]);
        $que  = trim($m[2]);
    }

    /* ── La coletilla en cursiva ──────────────────────────────────────────
       El programa oficial cierra muchas actividades diciendo qué hará el
       Papa —«Discurso del Santo Padre.», «Homilía del Santo Padre.»,
       «Ángelus.»— y el editable las dibuja en cursiva, separadas del resto.

       Se reconocen aquí, con una lista CERRADA, y no se admite HTML desde el
       panel: la cursiva la decide este código, no lo que alguien escriba en
       el gestor. Una actividad que no acabe en una de estas fórmulas se
       pinta entera como hasta ahora. */
    $nota = '';
    $formulas = 'Discurso|Homilía|Homilia|Saludo|Ángelus|Angelus|Palabras|Mensaje';

    if (preg_match('/^(.*?[^\s])[\s.]*\(?((?:' . $formulas . ')(?:\s+del\s+Santo\s+Padre)?)\)?\.?$/ui', $que, $n) === 1) {
        $resto = rtrim(trim($n[1]), '.,;');

        /* Sólo si queda actividad delante: «Ángelus.» a secas es la
           actividad entera, no la coletilla de nada. */
        if ($resto !== '') {
            $que  = $resto . '.';
            $nota = trim($n[2]) . '.';
        }
    }

    return ['hora' => $hora, 'que' => $que, 'nota' => $nota];
};

/* ── Las sedes ───────────────────────────────────────────────────────────
   Sección «cuatro-ventanas»: cada bloque es una ciudad del cronograma con su
   nombre y su fotografía. El «texto» del bloque es la segunda sede, la que el
   editable dibuja un cuerpo más pequeño («Chiclayo - Santa Cruz»).

   La reserva es la del editable, con las fotos que la maqueta traía escritas:
   mientras nadie las cambie en el panel se ven exactamente ésas. */
$sedes = $bloques('cuatro-ventanas', [
    ['titulo' => 'Lima - Callao', 'img' => 'p01', 'w' => 1084, 'h' => 626,
     'alt' => 'Plaza Mayor de Lima con la Basílica Catedral y el Palacio Arzobispal'],
    ['titulo' => 'Chiclayo', 'texto' => 'Santa Cruz', 'img' => 'p02', 'w' => 1084, 'h' => 626,
     'alt' => 'Catedral Santa María de Chiclayo vista desde el parque principal'],
    ['titulo' => 'Cusco', 'img' => 'p03', 'w' => 1084, 'h' => 626,
     'alt' => 'Catedral del Cusco en la Plaza de Armas'],
    ['titulo' => 'Pucallpa', 'img' => 'p04', 'w' => 1041, 'h' => 601,
     'alt' => 'Catedral de Pucallpa con las banderas de la plaza central'],
]);

/* ── Las jornadas ────────────────────────────────────────────────────────
   Sección «itinerario». La reserva NO es la del editable: allí las jornadas
   son «XX:00 Hrs.» y «Lorem ipsum», y eso no puede salir publicado ni
   aunque se caiga MySQL. Lo que va aquí es el programa referencial que hay
   en producción, el mismo que pinta la base. Y sin horas, porque todavía no
   las hay: la Santa Sede no ha publicado el programa oficial. */
$jornadas = $bloques('itinerario', [
    ['rotulo' => '11 de noviembre', 'titulo' => 'Lima · Llegada y bienvenida oficial',
     'texto' => 'Llegada al Perú y primer mensaje al pueblo peruano.',
     'datos' => ['actividades' => [
         'Llegada del Santo Padre',
         'Ceremonia de bienvenida',
         'Encuentro con autoridades',
         'Primer mensaje al pueblo peruano',
     ]]],
    ['rotulo' => '12 de noviembre', 'titulo' => 'Chiclayo · El reencuentro con una Iglesia que conoce',
     'texto' => 'Chiclayo tiene una relación personal y pastoral muy fuerte con León XIV: '
              . 'fue obispo de esta diócesis durante años.',
     'datos' => ['actividades' => [
         'Encuentro con la comunidad de Chiclayo',
         'Celebración eucarística',
         'Encuentro con sacerdotes, religiosos y agentes pastorales',
         'Momento de cercanía con el pueblo',
     ]]],
    ['rotulo' => '13 de noviembre', 'titulo' => 'Pucallpa · Encuentro con la Amazonía',
     'texto' => 'Encuentro con las comunidades amazónicas y los pueblos originarios.',
     'datos' => ['actividades' => [
         'Encuentro con comunidades amazónicas',
         'Encuentro con representantes de pueblos originarios',
         'Celebración o momento de oración',
         'Mensaje sobre el cuidado de la casa común',
     ]]],
    ['rotulo' => '14 de noviembre', 'titulo' => 'Cusco · La fe que nace del encuentro',
     'texto' => 'Cusco desde su identidad religiosa, cultural y andina.',
     'datos' => ['actividades' => [
         'Celebración eucarística',
         'Encuentro con la comunidad eclesial',
         'Encuentro con jóvenes y familias',
         'Visita o momento de oración en un lugar significativo',
     ]]],
    ['rotulo' => '15 de noviembre', 'titulo' => 'Lima · Un encuentro con todo el Perú',
     'texto' => 'La gran jornada del encuentro nacional.',
     'datos' => ['actividades' => [
         'Gran celebración eucarística',
         'Encuentro con familias, jóvenes y diversos sectores',
         'Mensaje del Santo Padre al pueblo peruano',
         'Momento de oración y acción de gracias',
     ]]],
    ['rotulo' => '16 de noviembre', 'titulo' => 'Lima · Hasta pronto, Perú',
     'texto' => 'Despedida y salida del Perú.',
     'datos' => ['actividades' => [
         'Encuentro de despedida',
         'Mensaje final del Santo Padre',
         'Ceremonia de despedida',
         'Salida del Perú',
     ]]],
]);

/* ── El reparto: una sede, sus jornadas ──────────────────────────────────── */
$grupos = [];

foreach ($sedes as $i => $sede) {
    $nombre = trim((string) ($sede['titulo'] ?? ''));

    if ($nombre === '') {
        continue;
    }

    $suClave = $clave($nombre) ?: 'sede-' . ($i + 1);

    /* Dos sedes que empiecen por la misma palabra darían la misma clave y la
       segunda se comería a la primera. Se le añade su posición y así ninguna
       desaparece; las jornadas de esa ciudad van a la primera, que es lo
       razonable. */
    if (isset($grupos[$suClave])) {
        $suClave .= '-' . ($i + 1);
    }

    $grupos[$suClave] = [
        'clave'    => $suClave,
        'titulo'   => $nombre,
        'segunda'  => trim((string) ($sede['texto'] ?? '')),
        'sede'     => $sede,
        'jornadas' => [],
    ];
}

foreach ($jornadas as $jornada) {
    $ciudad  = trim((string) ($jornada['titulo'] ?? ''));
    $suClave = $clave($ciudad);

    if ($suClave === '' || !isset($grupos[$suClave])) {
        /* Ciudad que no figura entre las sedes: se le abre apartado propio en
           lugar de dejar la jornada sin pintar. Sin foto, pero con su día. */
        $suClave = $suClave !== '' ? $suClave : 'otras-jornadas';
        $grupos[$suClave] ??= [
            'clave'    => $suClave,
            'titulo'   => $primero($ciudad) !== '' ? $primero($ciudad) : 'Otras jornadas',
            'segunda'  => '',
            'sede'     => [],
            'jornadas' => [],
        ];
    }

    $grupos[$suClave]['jornadas'][] = $jornada;
}
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       Banda dorada oscura de 582 px con la foto en duotono, la insignia
       «A G E N D A» sobre recuadro claro y el titular centrado. Todo sale de
       Páginas → Agenda → Cabecera de página. */ ?>
  <section class="hero hero--page agenda-hero">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/agenda/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/agenda/hero.jpg')) ?>"
             alt="El Papa León XIV saluda a una multitud de fieles que lo esperan con sus teléfonos en alto"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="hero__inner">
      <p class="hero__badge"><?= $esc($espaciado($campo('cabecera', 'rotulo', 'Agenda'))) ?></p>
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Días de encuentro')) ?></h1>
      <p class="hero__sub"><?= $realce($campo(
          'cabecera',
          'texto',
          "El Papa León XIV estará en el Perú del\n**11 al 16 de noviembre** de 2026."
      )) ?></p>
    </div>
  </section>

  <?php /* ═════════════════════════════════════════════════ CRONOGRAMA ════
       El cuerpo de la página: los filtros por ciudad y, debajo, cada sede con
       su fotografía y sus jornadas. El editable no dibuja titular para esta
       sección —lo hace el héroe—, así que el título de «itinerario» va oculto
       y sirve de nombre accesible: quien navega con lector de pantalla oye de
       qué es la lista antes de entrar en ella. */ ?>
  <section class="agenda" aria-labelledby="t-agenda">
    <div class="agenda__cont">

      <h2 class="visually-hidden" id="t-agenda"><?= $esc($campo('itinerario', 'titulo', 'El recorrido del Santo Padre')) ?></h2>

      <?php /* Filtros por ciudad: los resuelve assets/js/rediseno.js emparejando
               data-filter con data-key. Sin JavaScript se ven todas las sedes,
               que es justo lo que hace falta. */ ?>
      <div class="chips agenda__filtros" data-filter-group="ciudades" role="group"
           aria-label="<?= $esc($campo('cuatro-ventanas', 'titulo', 'Filtrar el cronograma por ciudad')) ?>">
        <button class="chip is-active" type="button" data-filter="all"><?= $esc($espaciado('Todas')) ?></button>
        <?php foreach ($grupos as $grupo): ?>
          <?php $rotuloSede = $grupo['titulo'] . ($grupo['segunda'] !== '' ? ' - ' . $grupo['segunda'] : ''); ?>
          <button class="chip chip--<?= $esc($grupo['clave']) ?>" type="button"
                  data-filter="<?= $esc($grupo['clave']) ?>"><?= $esc($espaciado($rotuloSede)) ?></button>
        <?php endforeach; ?>
      </div>

      <?php foreach ($grupos as $grupo): ?>
        <article class="ag-sede ag-sede--<?= $esc($grupo['clave']) ?>"
                 data-filter-item="ciudades" data-key="<?= $esc($grupo['clave']) ?>">

          <h3 class="ag-sede__h"><?= $esc($espaciado($grupo['titulo'])) ?><?php
            /* La segunda sede va un cuerpo más pequeño, como en el editable. */
            if ($grupo['segunda'] !== ''): ?>   -   <span class="ag-sede__h--sm"><?= $esc($espaciado($grupo['segunda'])) ?></span><?php endif; ?></h3>

          <?php
          /* La fotografía de la ciudad. Sale del panel; mientras nadie elija
             otra se ve la que traía la maqueta. */
          ob_start();
          if (!empty($grupo['sede']['img'])): ?>
            <picture>
              <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/agenda/' . $grupo['sede']['img'] . '.webp')) ?>" type="image/webp">
              <img src="<?= $esc($sitio->asset('assets/img/rediseno/agenda/' . $grupo['sede']['img'] . '.jpg')) ?>"
                   alt="<?= $esc((string) ($grupo['sede']['alt'] ?? '')) ?>"
                   width="<?= (int) ($grupo['sede']['w'] ?? 1084) ?>" height="<?= (int) ($grupo['sede']['h'] ?? 626) ?>"
                   loading="lazy" decoding="async">
            </picture>
          <?php endif;
          $respaldoSede = (string) ob_get_clean();
          $fotoSede     = $sitio->imagen($grupo['sede'], $respaldoSede, ['sizes' => '(min-width:1024px) 38vw, 100vw']);
          ?>
          <?php if ($fotoSede !== ''): ?>
            <figure class="ag-sede__foto"><?= $fotoSede ?></figure>
          <?php endif; ?>

          <?php if ($grupo['jornadas'] !== []): ?>
          <div class="ag-dias">
            <?php foreach ($grupo['jornadas'] as $jornada): ?>
              <?php
              $rotulo = trim((string) ($jornada['rotulo'] ?? ''));
              $dia    = $fecha($rotulo);

              /* Las actividades del día. El panel las guarda como lista en la
                 columna `datos`; según de dónde venga la fila puede llegar ya
                 descodificada o todavía como texto JSON. */
              $datos = $jornada['datos'] ?? [];
              if (is_string($datos)) {
                  $datos = json_decode($datos, true) ?: [];
              }

              $actividades = array_values(array_filter(array_map(
                  static fn ($v): string => trim((string) $v),
                  (array) (($datos['actividades'] ?? []) ?: [])
              ), static fn (string $v): bool => $v !== ''));

              /* Una jornada sin actividades no se queda en blanco: se pinta lo
                 que sí tenga escrito, que para eso está en el panel. */
              $cola = trim((string) preg_replace('/^[^·•–—|]*[·•–—|]\s*/u', '', (string) ($jornada['titulo'] ?? '')));
              if ($actividades === []) {
                  $unica = $cola !== '' ? $cola : trim((string) ($jornada['texto'] ?? ''));
                  $actividades = $unica !== '' ? [$unica] : [];
              }
              ?>
              <div class="timeline ag-dia">
                <?php /* El «11 NOV» solo no dice gran cosa en voz alta: este
                         titular oculto da a cada día su nombre completo. */ ?>
                <h4 class="visually-hidden"><?= $esc(trim($rotulo . ($cola !== '' ? ' · ' . $cola : ''))) ?></h4>

                <p class="timeline__day">
                  <?php if ($dia['dia'] !== ''): ?><span class="timeline__num"><?= $esc($dia['dia']) ?></span><?php endif; ?>
                  <?php if ($dia['mes'] !== ''): ?><span class="timeline__mon"><?= $esc($dia['mes']) ?></span><?php endif; ?>
                </p>

                <div class="timeline__items">
                  <?php foreach ($actividades as $actividad): ?>
                    <?php $a = $acto($actividad); ?>
                    <div class="ag-acto">
                      <?php if ($a['hora'] !== ''): ?>
                        <p class="timeline__time"><?= $esc($a['hora']) ?></p>
                      <?php endif; ?>
                      <?php if ($a['que'] !== ''): ?>
                        <p class="timeline__what"><?= $esc($a['que']) ?><?php if (($a['nota'] ?? '') !== ''): ?> <em class="ag-acto__nota"><?= $esc($a['nota']) ?></em><?php endif; ?></p>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </article>
      <?php endforeach; ?>

      <?php /* ── La letra pequeña ─────────────────────────────────────────
           El editable no la dibuja, y aun así va: el programa que se publica
           aquí es REFERENCIAL hasta que la Santa Sede apruebe el oficial, y
           esa advertencia ya estaba escrita en la base. Enseñar horas de un
           viaje papal sin decir que no son definitivas induce a error, así
           que se pinta al pie del cronograma, en cuerpo pequeño. */ ?>
      <?php
      /* La reserva importa más aquí que en ningún otro sitio: si la base no
         responde, la página sigue enseñando el cronograma —lo tiene escrito
         arriba— y no puede enseñarlo sin la advertencia. */
      $advertencia = $campo(
          'itinerario',
          'texto',
          '<p><strong>Programa referencial.</strong> Las fechas, actividades y lugares serán '
        . 'reemplazados por el programa oficial cuando la Santa Sede lo apruebe y publique.</p>'
      );
      ?>
      <?php if (trim(strip_tags($advertencia)) !== ''): ?>
        <div class="agenda__nota"><?= $advertencia ?></div>
      <?php endif; ?>

    </div>
  </section>

</main>
