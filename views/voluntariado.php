<?php
/**
 * ============================================================================
 *  Voluntariado «Los amigos de León» — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  ── Qué cambió y qué NO ──────────────────────────────────────────────────
 *
 *  Cambia el envoltorio: el héroe duotono, los tres pasos, la rejilla de seis
 *  servicios con sus iconos, el proceso de selección y el recuadro rosado
 *  «Ten a mano». Las medidas son las del editable «PÁG VOLUNTARIADO.ai» (mesa
 *  de 1440 px) y las reproduce assets/css/paginas/voluntariado.css con la
 *  unidad --u.
 *
 *  NO cambia el formulario. Ni una clase, ni un name, ni un data-*: es el
 *  marcado que maneja assets/js/form.js y del que dependen 37.428 fichas de
 *  voluntario. Se ha copiado tal cual, con la lógica que lo rodea —el testigo
 *  firmado, los errores, lo ya tecleado y los ajustes voluntariado.abierto y
 *  voluntariado.cerrado_texto—. Lo único que se movió de sitio es el recuadro
 *  «Ten a mano», que en el diseño nuevo va DEBAJO del formulario y no encima.
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. Una web que recoge inscripciones no puede quedarse
 *  muda porque falle la base.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

use Intranet\Core\HtmlSeguro;
use Intranet\Models\Ubigeo;
use Intranet\Models\Voluntario;
use Intranet\Publico\Espejo;
use Intranet\Publico\Inscripcion;
use Intranet\Publico\Sitio;
use Intranet\Publico\Token;

$peticion = $sitio->peticion();
$catalogo = $sitio->catalogo();

/* ── Esta página NO se cachea ──────────────────────────────────────────────
   Lleva dentro un testigo firmado con fecha de caducidad. Servida desde una
   caché, el testigo que recibe el visitante puede tener horas y el envío se
   rechaza con «el formulario caducó», sin que nadie entienda por qué.

   Pasó en producción: el hosting sirvió durante dieciocho horas una copia
   guardada, y con ella un testigo que había expirado hacía dieciséis. Todas
   las inscripciones de ese rato se perdieron.

   Los tres encabezados son el mismo mensaje dicho a tres oyentes distintos:
   Cache-Control para navegadores y proxies modernos, Pragma para los
   intermediarios antiguos, y X-LiteSpeed-Cache-Control para LiteSpeed, que
   viene activado por defecto en muchos cPanel y no siempre obedece al
   primero. */
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-LiteSpeed-Cache-Control: no-cache');
}

/* Campos que el panel deja escribir con negritas y enlaces. No se escapan,
   así que hay que filtrarlos: editar textos no debe dar el poder de ejecutar
   código en el navegador de cada visitante.

   Antes esto era strip_tags() con una lista de etiquetas, y no bastaba:
   strip_tags conserva ÍNTEGROS los atributos de las etiquetas que deja pasar,
   de modo que <a href="javascript:…">, <a onmouseover="…"> y <p onload="…">
   entraban tal cual. Bloqueaba las etiquetas de script y dejaba abierto todo
   lo demás. En
   esta página, donde se teclean DNI, teléfono y dirección, eso alcanzaba para
   leer cada campo mientras se escribe.

   HtmlSeguro analiza el HTML como árbol y reconstruye sólo lo admitido,
   atributo por atributo. Ver Core\HtmlSeguro. */
$rico = static fn ($v): string => HtmlSeguro::limpiar((string) ($v ?? ''));

// ══════════════════════════════════════════════════════════════════════════
//  ENVÍO DEL FORMULARIO
// ══════════════════════════════════════════════════════════════════════════

$errores   = [];
$enviado   = null;                       // código de inscripción si todo fue bien
$anterior  = [];                         // lo escrito, para repintarlo si hubo error
$esJson    = false;

if ($peticion->esPost() && !isset($_POST["_cargar_ubigeo"])) {
    $esJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
           || $peticion->esAjax();

    $inscripcion = new Inscripcion($sitio, new Voluntario($sitio->contenedor()), $catalogo);
    $enviado     = $inscripcion->procesar($_POST);
    $errores     = $inscripcion->errores();

    if ($esJson) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code($enviado !== null ? 201 : 422);

        echo json_encode(
            $enviado !== null
                ? [
                    'ok'     => true,
                    'codigo' => $enviado,
                    // La persona ve una confirmación en los dos casos, pero no
                    // la misma: decirle «ya estás inscrito» cuando su ficha
                    // aún no está en la base sería mentirle, y se enteraría
                    // más tarde y peor.
                    'contingencia' => $inscripcion->porContingencia(),
                  ]
                : ['ok' => false, 'errores' => $errores],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // Sin JavaScript: Post-Redirect-Get. Sin el redirect, recargar la página
    // reenvía el formulario y crea una inscripción duplicada.
    if ($enviado !== null) {
        header('Location: ?inscrito=' . rawurlencode($enviado), true, 303);
        exit;
    }

    // Con errores se repinta el formulario con lo ya escrito: obligar a
    // teclearlo todo otra vez por un dígito del DNI es maltrato.
    $anterior = $_POST;
}

$confirmado = isset($_GET['inscrito']) ? (string) $_GET['inscrito'] : ($enviado ?? null);

// ══════════════════════════════════════════════════════════════════════════
//  CONTENIDO
// ══════════════════════════════════════════════════════════════════════════

$pagina    = $sitio->contenido('voluntariado');
$secciones = $pagina['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string => Sitio::campo($secciones, $s, $c, $r);
$dato    = static fn (string $s, string $c, mixed $r = null): mixed  => Sitio::dato($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array               => Sitio::bloques($secciones, $s, $r);

/* Se llama $hay y no $activa porque $activa es la variable que leen los
   parciales de cabecera y pie para marcar la página actual del menú. Al
   asignarle el nombre de la página, la función quedaba machacada y la página
   moría con «Call to undefined function voluntariado()». */
$hay = static fn (string $s): bool => Sitio::activa($secciones, $s);

/* ── Las listas del formulario, a prueba de base caída ────────────────────
   Estas cuatro consultas se hacían a pelo, y con la base sin responder
   lanzaban antes de pintar nada: la página moría con un error 500 y no se
   llegaba a ver el formulario. Se descubrió al probar la contingencia —el
   envío aguantaba, pero nadie podía llegar a enviarlo—.

   Ahora pasan por el espejo: mientras la base va bien se refresca solo; si
   falla, la página se pinta con la última copia buena. */
$espejo = new Espejo();

/* Los 25 departamentos sacados del archivo que ya se sirve al navegador para
   las sugerencias. Es el último recurso: sólo entra si la base no responde y
   además no hay copia en el espejo. */
$departamentosDeReserva = static function (): array {
    $archivo = dirname(__DIR__) . '/assets/data/ubigeo.json';
    $crudo   = is_file($archivo) ? @file_get_contents($archivo) : false;

    if ($crudo === false) {
        return [];
    }

    $datos = json_decode($crudo, true);
    $lista = [];

    foreach ($datos['d'] ?? [] as $par) {
        $lista[] = ['id' => (string) $par[0], 'nombre' => (string) $par[1]];
    }

    return $lista;
};

$servicios      = $espejo->recordar('servicios',      fn () => $catalogo->servicios());
/* Con el cupo de cada una: cuántas plazas tiene, cuántas van y si está llena.
   El espejo consulta siempre y sólo tira de su copia si la base no responde;
   si eso pasa, una copia vieja podría ofrecer una jurisdicción ya completa,
   pero el servidor la rechaza al guardar. Es el mismo trato que el resto de
   los catálogos: preferible una lista un minuto desfasada que un formulario
   que no abre. */
$jurisdicciones = $espejo->recordar('jurisdicciones', fn () => $catalogo->jurisdiccionesConCupo());

// Si no se puede saber, se da por abierta: cerrar el formulario por una
// consulta que no responde sería perder inscripciones sin motivo.
$abierto = $espejo->recordar(
    'voluntariado_abierto',
    fn () => $catalogo->ajusteBool('voluntariado.abierto', true),
    true
);

// ── Ubigeo ───────────────────────────────────────────────────────────────
// Los departamentos van siempre; provincias y distritos sólo si ya hay algo
// elegido. Eso ocurre en dos casos: cuando el formulario vuelve con un error y
// hay que repintar lo que la persona ya había escogido, y cuando NO hay
// JavaScript y la cascada se resuelve recargando la página.
$ubigeo = new Ubigeo($sitio->contenedor());

$ubigeoElegido = [
    'departamento' => trim((string) ($anterior['ubigeo_departamento_id'] ?? '')),
    'provincia'    => trim((string) ($anterior['ubigeo_provincia_id'] ?? '')),
    'distrito'     => trim((string) ($anterior['ubigeo_distrito_id'] ?? '')),

    // Provincia y distrito se escriben, así que al volver de un error hay que
    // devolver lo TECLEADO, no el código: puede que no haya código —eso es
    // legítimo— y aun así la persona tiene derecho a recuperar lo que puso.
    'provincia_nombre' => trim((string) ($anterior['provincia_nombre'] ?? $anterior['provincia'] ?? '')),
    'distrito_nombre'  => trim((string) ($anterior['distrito_nombre']  ?? $anterior['distrito']  ?? '')),
];

// Sólo los 25 departamentos: es lo único que sigue siendo un desplegable.
// Las provincias y los distritos ya no se pintan en el HTML —se escriben, y
// las sugerencias las trae el navegador cuando hacen falta—, así que la página
// se ahorra dos consultas en cada carga. En un servidor que va de 1 a 10
// segundos, eso se nota.
// Con el mismo espejo. Y si tampoco hubiera copia —una instalación recién
// puesta cuya base falla desde el primer minuto—, quedan los del archivo
// estático del ubigeo, que ya está ahí para las sugerencias.
$departamentos = $espejo->recordar(
    'departamentos',
    fn () => $ubigeo->departamentos(),
    $departamentosDeReserva()
);

// ── Fecha de nacimiento ──────────────────────────────────────────────────
// Los límites del calendario: de 15 a 95 años. Fuera de ese rango no es una
// fecha de nacimiento plausible aquí, y acotarlo evita el «1902» de quien se
// equivoca de tecla. Van en el HTML (min/max) para que el navegador los
// respete aunque flatpickr no llegue a cargar.
$fechaMax = date('Y-m-d', strtotime('-15 years'));
$fechaMin = date('Y-m-d', strtotime('-95 years'));

$tituloSeo = $pagina['titulo_seo']      ?? 'Voluntariado «Los amigos de León» · Viaje de León XIV al Perú';
$descSeo   = $pagina['descripcion_seo'] ?? 'Seis servicios y tres fases para acompañar el viaje apostólico de León XIV al Perú. Inscríbete como voluntario: hay un lugar para cada talento.';

// Testigo del formulario. Si la clave no está configurada el formulario no se
// pinta: es preferible a ofrecer un formulario que va a rechazar todo envío.
$testigo = null;
try {
    $testigo = (new Token((string) $sitio->config('app.clave', '')))->generar();
} catch (Throwable $e) {
    error_log('[voluntariado] ' . $e->getMessage());
}

/* Las dos que leen los parciales de cabecera y pie: el camino hasta la raíz
   del sitio y qué entrada del menú marcar como página actual.
   La variable se llama $activa, no $activo: con el nombre equivocado el menú
   dejaba de señalar en qué página estás, en silencio y sin romper nada. */

$meta = [
    'titulo'      => $tituloSeo,
    'descripcion' => $descSeo,
    'og_titulo'      => 'Los amigos de León · Voluntariado del viaje apostólico',
    'og_descripcion' => 'Seis servicios, una sola misión: servir con alegría. Inscríbete como voluntario.',
    'ruta'        => 'voluntariado/',
    'og_imagen'   => 'assets/img/og/og-voluntariado.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre" data-ancla data-form-fijo',
    // Esta página no lleva la barra fija de «Sé voluntario»: ya está en
    // ella, y tiene su propio panel flotante hacia el formulario.
    'barra_fija'  => false,
    'head_extra'     => '<noscript><style>[data-adelante],[data-atras],.progreso{display:none}</style></noscript>',
    /* form.js FALTABA en esta lista y sin él la página perdía todo lo que
       hace el formulario por dentro: los dos pasos, la validación al vuelo,
       las sugerencias de provincia y distrito, el calendario de flatpickr y
       el envío sin recargar. Se enviaba igual —el formulario funciona sin
       JavaScript, ésa es la red de seguridad— pero de golpe y sin avisos.
       Además, el guardián de versión del pie no encontraba window.L14.version
       y recargaba la página una vez por sesión buscándolo.
       Va antes que arranque.js, que es quien llama a su init(): la plantilla
       imprime estos scripts justo ahí. */
    'scripts'     => [
        'assets/vendor/flatpickr.min.js',
        'assets/vendor/flatpickr-es.js',
        'assets/js/form.js',
        'assets/js/ancla-form.js',
        'assets/js/ten-a-mano.js',
    ],
    'css'         => ['assets/vendor/flatpickr.min.css'],
];

/* ── Los iconos de los seis servicios ─────────────────────────────────────
   El editable dibuja un cuadrado dorado con un símbolo blanco dentro, uno
   distinto por servicio. No están en el sprite general —allí los mismos
   símbolos van en trazo fino y sin fondo—, así que se escriben aquí.

   Se buscan por la clave de icono que trae cada servicio del catálogo, no
   por su posición: si mañana se reordenan los servicios en el panel, cada
   uno conserva su dibujo. Y si alguien añade un servicio con una clave que
   no esté en esta lista, la tarjeta se pinta igual con el símbolo del sprite
   sobre el mismo cuadrado dorado. Una clave nueva no rompe la rejilla. */
$cuadroDorado = '<rect x="0" y="0" width="65" height="65" rx="8.5" fill="#E6A015"/>';

$iconosServicio = [
    'i-resguardo' => '<g fill="none" stroke="#EAEAEA" stroke-width="3.2" stroke-linejoin="round" stroke-linecap="round">'
        . '<path d="M32.5 13.4c-3 3.2-8 4.9-14.6 4.9v14.4c0 9.9 6.4 15.9 14.6 18.8 8.2-2.9 14.6-8.9 14.6-18.8V18.3c-6.6 0-11.6-1.7-14.6-4.9Z"/>'
        . '<path d="M25.2 33.6l5.1 5.1 9.5-11" stroke-width="3.6"/></g>',

    'i-acogida' => '<g fill="none" stroke="#EAEAEA" stroke-width="3.2" stroke-linecap="round">'
        . '<circle cx="32.5" cy="26.4" r="8.4"/><circle cx="16.8" cy="29.8" r="5.4"/><circle cx="48.2" cy="29.8" r="5.4"/>'
        . '<path d="M21.4 47.3a11.6 11.6 0 0 1 22.2 0"/><path d="M10.4 45.1a9.2 9.2 0 0 1 12.4-6.6"/>'
        . '<path d="M42.2 38.5a9.2 9.2 0 0 1 12.4 6.6"/></g>',

    'i-comunicacion' => '<path fill="#EAEAEA" d="M26.6 19.4c.6-1.5 2-2.5 3.6-2.5h4.6c1.6 0 3 1 3.6 2.5l.8 2h3.6c3.3 0 6 2.7 6 6v15.2c0 3.3-2.7 6-6 6H22.2c-3.3 0-6-2.7-6-6V27.4c0-3.3 2.7-6 6-6h3.6l.8-2Z"/>'
        . '<circle cx="32.5" cy="35.1" r="8.1" fill="none" stroke="#E6A015" stroke-width="3.2"/>',

    'i-logistica' => '<g fill="none" stroke="#EAEAEA" stroke-width="3.4" stroke-linejoin="round" stroke-linecap="round">'
        . '<path d="M32.5 13.6 48 22v17.1l-15.5 8.4L17 39.1V22Z"/><path d="M17 22l15.5 8.4L48 22"/>'
        . '<path d="M32.5 30.4v17.1"/></g>',

    'i-auxilios' => '<path fill="none" stroke="#EAEAEA" stroke-width="3.4" stroke-linejoin="round" d="M24.4 21.9v-4.3h16.2v4.3"/>'
        . '<rect x="15.9" y="21.9" width="33.2" height="27.2" rx="5" fill="#EAEAEA"/>'
        . '<path fill="#E6A015" d="M30 26.8h5v5.8h5.8v5H35v5.8h-5v-5.8h-5.8v-5H30Z"/>',

    'i-traduccion' => '<circle cx="32.5" cy="32.5" r="20" fill="#EAEAEA"/>'
        . '<g fill="none" stroke="#E6A015" stroke-width="3.2" stroke-linecap="round">'
        . '<ellipse cx="32.5" cy="32.5" rx="8.2" ry="19.9"/><path d="M13.2 32.5h38.6"/>'
        . '<path d="M17.1 20.9c4.4 2.6 9.7 4 15.4 4s11-1.4 15.4-4"/>'
        . '<path d="M17.1 44.1c4.4-2.6 9.7-4 15.4-4s11 1.4 15.4 4"/></g>',
];

/* El nombre del servicio se guarda en el catálogo como «Servicio de resguardo
   y orden», en minúscula, porque así se lee en el desplegable del formulario
   («Elige uno → Servicio de resguardo y orden»). En la tarjeta va sin el
   prefijo y con mayúscula inicial, como en el editable. mb_convert_case sobre
   la primera letra y no ucfirst: «Órden» o «Ámbito» empiezan por una letra de
   dos bytes y ucfirst() la partiría por la mitad. */
$tituloServicio = static function (string $nombre): string {
    $limpio = trim((string) preg_replace('/^Servicio de\s+/iu', '', $nombre));

    if ($limpio === '') {
        return '';
    }

    return mb_strtoupper(mb_substr($limpio, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($limpio, 1, null, 'UTF-8');
};
?>
<main id="contenido">

<?php /* ═══════════════════════════════════════════════════════════ HÉROE ══
     La banda duotono de 582 px con el titular encima. La fotografía sale del
     panel (Páginas → Voluntariado → Cabecera de página); el <picture> de aquí
     abajo es el RESPALDO, la que la página trae escrita. Mientras nadie elija
     otra en el panel se sigue viendo ésta.

     El rótulo («Voluntariado») no es un antetítulo suelto: va dentro de la
     bajada, en cursiva amarilla y seguido de un punto, tal como lo compone el
     editable. */ ?>
<section class="hero hero--page vol-hero">
  <div class="hero__media">
    <?php ob_start(); ?>
    <picture>
      <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/voluntariado/hero.webp')) ?>" type="image/webp">
      <img src="<?= $esc($sitio->asset('assets/img/rediseno/voluntariado/hero.jpg')) ?>"
           alt="Grupo de jóvenes voluntarios sonriendo, en duotono naranja y verde"
           width="2880" height="1164" fetchpriority="high" decoding="async">
    </picture>
    <?php $respaldoHero = (string) ob_get_clean(); ?>
    <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>

  <div class="hero__inner vol-hero__inner">
    <h1 class="vol-hero__title"><?= $esc($campo('cabecera', 'titulo', 'Los amigos de León')) ?></h1>

    <?php
    $rotuloHero = $campo('cabecera', 'rotulo', 'Voluntariado');
    $bajadaHero = $campo(
        'cabecera',
        'texto',
        '<strong>Si tienes entre 18 y 45 años</strong> participa como voluntario en la Visita del '
        . 'Papa León XIV. Una experiencia para <strong>servir, acoger y hacer comunidad</strong>.'
    );
    ?>
    <p class="vol-hero__sub"><?php
      if ($rotuloHero !== '') {
          echo '<em class="vol-hero__kicker">' . $esc($rotuloHero) . '</em>. ';
      }
      echo $rico($bajadaHero);
    ?></p>
  </div>
</section>

<div class="vol-marco">

  <?php if ($hay('resumen')): ?>
  <?php /* ══════════════════════════════════════════════ LOS TRES PASOS ══
       Tres tarjetas con borde y sombra. El número («Paso 1») lo pone el
       orden, no el panel: así reordenar los pasos no obliga a renumerarlos
       a mano, que es justo donde se quedan los «Paso 2 · Paso 2». */ ?>
  <?php
  $pasos = $bloques('resumen', [
      ['titulo' => 'Eliges tu servicio',       'texto' => 'Son seis servicios. No importa tu profesión o el tiempo que puedas dar. Todos son bienvenidos.', 'enlace_texto' => 'Ver los seis',     'enlace_url' => '#servicios'],
      ['titulo' => 'Rellenas el formulario',   'texto' => 'Es la Fase 01 y el único paso que se hace por internet. Solo cinco minutos.',                    'enlace_texto' => 'Ir al formulario', 'enlace_url' => '#inscripcion'],
      ['titulo' => 'La organización te escribe', 'texto' => 'Validación de documentos y, más adelante, acreditación y credenciales.',                        'enlace_texto' => 'Ver el proceso',   'enlace_url' => '#proceso'],
  ]);
  ?>
  <section class="vol-pasos" id="resumen" aria-labelledby="t-pasos">
    <?php $rotuloPasos = $campo('resumen', 'rotulo'); ?>
    <?php if ($rotuloPasos !== ''): ?><p class="vol-rotulo"><?= $esc($rotuloPasos) ?></p><?php endif; ?>
    <h2 class="vol-h2" id="t-pasos"><?= $esc($campo('resumen', 'titulo', '¡Quiero ser voluntario!')) ?></h2>
    <p class="vol-regla" aria-hidden="true"></p>

    <ol class="vol-pasos__grid">
      <?php foreach ($pasos as $i => $b): ?>
        <li class="vol-paso">
          <p class="vol-paso__num">Paso <?= (int) $i + 1 ?></p>
          <h3 class="vol-paso__tit"><?= $esc((string) ($b['titulo'] ?? '')) ?></h3>
          <p class="vol-paso__txt"><?= $rico($b['texto'] ?? '') ?></p>
          <?php if (!empty($b['enlace_url'])): ?>
            <a class="vol-paso__cta" href="<?= $esc((string) $b['enlace_url']) ?>"><?= $esc((string) ($b['enlace_texto'] ?? '') ?: 'Ver más') ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

  <?php if ($hay('servicios')): ?>
  <?php /* ════════════════════════════════════════════ LOS SEIS SERVICIOS ══
       Las tarjetas salen de la tabla `servicios`, que es la MISMA que llena
       el <select> del formulario. Si estuvieran duplicadas, un día la
       tarjeta diría una cosa y la opción del desplegable otra.

       De la sección del panel salen sólo el marco: el titular y, más abajo,
       las líneas de cierre y el botón. */ ?>
  <section class="vol-servicios" id="servicios" aria-labelledby="t-servicios">
    <?php $rotuloServ = $campo('servicios', 'rotulo'); ?>
    <?php if ($rotuloServ !== ''): ?><p class="vol-rotulo"><?= $esc($rotuloServ) ?></p><?php endif; ?>
    <h2 class="vol-h2" id="t-servicios"><?= $esc($campo('servicios', 'titulo', 'Un lugar para cada talento')) ?></h2>
    <p class="vol-regla" aria-hidden="true"></p>

    <?php $entradaServ = $campo('servicios', 'texto'); ?>
    <?php if ($entradaServ !== ''): ?><div class="vol-intro"><?= $rico($entradaServ) ?></div><?php endif; ?>

    <ul class="vol-serv__grid">
      <?php foreach ($servicios as $i => $s): ?>
        <?php
        $clave  = (string) ($s['icono'] ?? '');
        $clave  = $clave !== '' && !str_starts_with($clave, 'i-') ? 'i-' . $clave : $clave;
        /* El respaldo usa el símbolo del sprite (viewBox de 24) centrado y
           escalado dentro del cuadrado de 65. Los símbolos pintan con
           currentColor, así que el color se da con `style`, no con `stroke`. */
        $dibujo = $iconosServicio[$clave]
            ?? '<g transform="translate(10 10) scale(1.875)" style="color:#EAEAEA">'
             . '<use href="#' . $esc($clave !== '' ? $clave : 'i-corazon') . '" width="24" height="24"/></g>';
        ?>
        <li class="vol-serv">
          <div class="vol-serv__head">
            <svg class="vol-serv__ico" viewBox="0 0 65 65" width="65" height="65" aria-hidden="true" focusable="false"><?= $cuadroDorado . $dibujo ?></svg>
            <h3 class="vol-serv__tit"><?= $esc($tituloServicio((string) ($s['nombre'] ?? ''))) ?></h3>
          </div>
          <?php /* El editable mide un ancho distinto para el texto de cada
                   tarjeta; de la séptima en adelante se usa el de la columna
                   entera, que es lo que hay. */ ?>
          <p class="vol-serv__txt<?= $i < 6 ? ' vol-serv__txt--' . ((int) $i + 1) : '' ?>"><?= $esc((string) ($s['descripcion'] ?? '')) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php /* ═══════════════════════════════════════════════════ INVITACIÓN ══
       El cierre de la rejilla: la cita en cursiva granate, dos líneas de
       texto y el botón. Todo sale de «Los seis servicios» → datos. */ ?>
  <?php
  $cierre = (array) $dato('servicios', 'cierre', [
      'Seis servicios, una sola misión: servir con alegría.',
      'Porque encontrarnos con el Santo Padre también significa poner nuestros dones al servicio de los demás.',
      '¿Te animas a ser parte de esta experiencia?',
  ]);
  $cierre = array_values(array_filter(array_map('strval', $cierre), static fn (string $l): bool => trim($l) !== ''));
  $grito  = (string) $dato('servicios', 'grito', '');
  ?>
  <?php if ($cierre !== [] || $grito !== '' || $abierto): ?>
  <section class="vol-cita" aria-label="Invitación">
    <?php foreach ($cierre as $n => $linea): ?>
      <?php if ($n === 0): ?>
        <p class="vol-cita__quote">«<?= $esc(trim($linea, '«» ')) ?>»</p>
      <?php else: ?>
        <p class="vol-cita__txt<?= $n > 1 ? ' vol-cita__txt--2' : '' ?>"><?= $esc($linea) ?></p>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($grito !== ''): ?>
      <p class="vol-cita__txt vol-cita__grito"><?= $esc($grito) ?></p>
    <?php endif; ?>

    <?php /* Con la convocatoria cerrada no se ofrece el botón: llevaría a un
             formulario que ya no acepta a nadie. */ ?>
    <?php if ($abierto): ?>
      <p class="vol-cita__accion"><a class="vol-cta" href="<?= $esc((string) $dato('servicios', 'boton_url', '#inscripcion')) ?>"><?= $esc((string) $dato('servicios', 'boton_texto', 'Inscríbete ahora')) ?></a></p>
    <?php endif; ?>
  </section>
  <?php endif; ?>
  <?php endif; ?>

  <?php /* ═════════════════════════════════ PROCESO DE SELECCIÓN + FORMULARIO ══
       Dos columnas: a la izquierda las tres fases; a la derecha el formulario
       de verdad y, debajo, el recuadro «Ten a mano».

       El editable dibuja aquí un pantallazo del formulario. Lo que va en su
       lugar es el formulario en funcionamiento, con el mismo ancho de columna
       (558 px del editable) y sobre el mismo panel claro.

       La sección se pinta SIEMPRE, aunque «Las tres fases» esté apagada en el
       panel: si el formulario dependiera de esa casilla, apagar un texto
       cerraría la inscripción sin que nadie lo pretendiera. */ ?>
  <?php
  $fases = $bloques('proceso', [
      ['rotulo' => '1', 'titulo' => 'Inscripción', 'texto' => '<strong>Rellenas el formulario</strong> de esta página con tus datos, la jurisdicción en la que quieres servir y el servicio que prefieres. Es el único paso que se hace por internet.', 'datos' => ['vinetas' => ['Nombres y apellidos, DNI y fecha de nacimiento', 'Dirección completa, correo electrónico y número telefónico', 'Talla de polo y contacto de emergencia', 'Jurisdicción y servicio']]],
      ['rotulo' => '2', 'titulo' => 'Validación',  'texto' => 'Se solicitarán algunos documentos adicionales. <strong>Nada de esto se sube a esta web:</strong> la organización te indicará por qué canal entregarlos.', 'datos' => ['vinetas' => ['Carta de recomendación de sacerdote, religioso(a) u obispo', 'Certificado Único Laboral (documento oficial, gratuito y digital emitido por el Ministerio de Trabajo y Promoción del Empleo)', 'Entrevista personal (según necesidad)', 'Evaluación psicológica (cuando sea posible)']]],
      ['rotulo' => '3', 'titulo' => 'Acreditación', 'texto' => '<strong>El último paso</strong>, ya cerca de la visita.', 'datos' => ['vinetas' => ['Confirmación oficial', 'Asignación de área de servicio', 'Entrega de credenciales']]],
  ]);
  $conFases = $hay('proceso') && $fases !== [];
  ?>
  <section class="vol-proceso" id="proceso" aria-labelledby="<?= $conFases ? 't-proceso' : 't-inscripcion' ?>">
    <?php if ($conFases): ?>
      <?php $rotuloProc = $campo('proceso', 'rotulo'); ?>
      <?php if ($rotuloProc !== ''): ?><p class="vol-rotulo"><?= $esc($rotuloProc) ?></p><?php endif; ?>
      <h2 class="vol-h2" id="t-proceso"><?= $esc($campo('proceso', 'titulo', 'Proceso de selección')) ?></h2>
      <p class="vol-regla" aria-hidden="true"></p>

      <?php $entradaProc = $campo('proceso', 'texto'); ?>
      <?php if ($entradaProc !== ''): ?><div class="vol-intro"><?= $rico($entradaProc) ?></div><?php endif; ?>
    <?php endif; ?>

    <div class="vol-proceso__grid<?= $conFases ? '' : ' vol-proceso__grid--sola' ?>">

      <?php if ($conFases): ?>
      <ol class="vol-proceso__pasos">
        <?php $ultima = count($fases) - 1; ?>
        <?php foreach ($fases as $i => $b): ?>
          <?php $vinetas = (array) ($b['datos']['vinetas'] ?? []); ?>
          <li class="vol-fase<?= $i === 1 ? ' vol-fase--2' : ($i >= 2 ? ' vol-fase--3' : '') ?>">
            <p class="vol-fase__num" aria-hidden="true"><?= $esc((string) ($b['rotulo'] ?? '') ?: (string) ((int) $i + 1)) ?></p>
            <div class="vol-fase__cuerpo">
              <h3 class="vol-fase__tit"><?= $esc((string) ($b['titulo'] ?? '')) ?></h3>
              <p class="vol-fase__txt"><?= $rico($b['texto'] ?? '') ?></p>
              <?php if ($vinetas !== []): ?>
                <ul class="vol-lista">
                  <?php foreach ($vinetas as $v): ?>
                    <?php /* El punto va escrito, como en el editable, y oculto
                             al lector de pantalla: la lista ya se anuncia como
                             lista y «viñeta, viñeta, viñeta» sobra. */ ?>
                    <li><span aria-hidden="true">•</span> <?= $rico($v) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
            <p class="vol-sep<?= $i === $ultima ? ' vol-sep--corta' : '' ?>" aria-hidden="true"></p>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>

      <div class="vol-proceso__aside" id="inscripcion">
        <h2 class="visually-hidden" id="t-inscripcion"><?= $esc($campo('inscripcion', 'titulo', 'Inscríbete como voluntario')) ?></h2>

        <?php /* ╔═══════════════════════════════════════════════════════════╗
                 ║  A PARTIR DE AQUÍ, EL FORMULARIO. NO SE TOCA.             ║
                 ║  Copiado tal cual de la versión anterior de esta vista:   ║
                 ║  las clases, los name, los data-* y el orden de los       ║
                 ║  campos son el contrato con assets/js/form.js y con las   ║
                 ║  37.428 inscripciones que ya hay en la base. Lo único que ║
                 ║  cambia es el recuadro «Ten a mano», que ahora va debajo  ║
                 ║  del formulario y con las clases del diseño nuevo.        ║
                 ╚═══════════════════════════════════════════════════════════╝ */ ?>
        <?php if ($confirmado !== null): ?>

          <?php /* Llegada desde el redirect posterior al envío correcto. */ ?>
          <div class="confirmacion" tabindex="-1">
            <svg class="ornamento" aria-hidden="true"><use href="#i-lirio"/></svg>
            <h2>Recibimos tu inscripción</h2>
            <p>Gracias por ofrecer tu tiempo. Tu código de inscripción es <strong><?= $esc($confirmado) ?></strong>; guárdalo por si necesitas escribirnos.</p>

            <?php /* Sin JavaScript no hay respuesta JSON que consultar, pero
                     el propio código lo dice: los de contingencia empiezan por
                     CONT-. Así el aviso sale también por esta vía, que es la
                     que se usa cuando el envío se hace a la antigua. */ ?>
            <?php if (str_starts_with($confirmado, 'CONT-')): ?>
              <p class="confirmacion__nota">
                Tu inscripción quedó registrada, aunque en este momento estamos
                teniendo problemas técnicos y todavía hay que terminar de
                procesarla. <strong>No hace falta que la envíes de nuevo</strong>:
                tus datos están guardados y nos ocupamos nosotros.
              </p>
            <?php endif; ?>

            <p>Te escribiremos al correo que nos has dejado para continuar con la <strong>Fase 02</strong>, cuando corresponda.</p>
            <p>Mientras tanto, puedes seguir la preparación del viaje en la <a href="<?= $esc($sitio->enlace('agenda/')) ?>">página de agenda</a> o compartir la convocatoria con tu parroquia.</p>
          </div>

        <?php elseif (!$abierto): ?>

          <div class="aviso">
            <p class="aviso__titulo">Convocatoria cerrada</p>
            <p><?= $esc($catalogo->ajuste('voluntariado.cerrado_texto', 'La convocatoria de voluntarios no está abierta en este momento.')) ?></p>
          </div>

        <?php elseif ($testigo === null): ?>

          <div class="aviso">
            <p class="aviso__titulo">El formulario no está disponible</p>
            <p>Estamos teniendo un problema técnico. Vuelve a intentarlo dentro de un rato.</p>
          </div>

        <?php else: ?>

          <?php /* A partir de 1280px este contenedor se despega del documento y
                   acompaña el scroll en su propio carril (pages.css, «El
                   formulario fijo»). Por debajo es un div corriente. */ ?>
          <div class="form-fijo" data-form-fijo>
          <p class="form-fijo__titulo" aria-hidden="true"><?= $esc($campo('inscripcion', 'titulo', 'Inscríbete como voluntario')) ?></p>

          <?php /* La dirección del endpoint de ubigeo va en el marcado, no en el JS:
                     esta página se sirve en /voluntariado/ y también en la raíz del
                     dominio cuando es la página de inicio, y una ruta relativa
                     apuntaría a un sitio distinto en cada caso. */ ?>
          <form class="formulario sep-l" id="form-inscripcion" data-form="inscripcion"
                data-ubigeo-url="<?= $esc($sitio->enlace("ubigeo.php")) ?>"
                data-ubigeo-json="<?= $esc($sitio->url("assets/data/ubigeo.json")) ?>?v=<?= @filemtime(__DIR__ . "/../assets/data/ubigeo.json") ?>"
                autocomplete="off"
                action="#inscripcion" method="post" novalidate>

            <?php /* Testigo firmado con la clave de la aplicación. No hace falta
                     sesión ni cookie: lleva dentro su caducidad. */ ?>
            <input type="hidden" name="_testigo" value="<?= $esc($testigo) ?>">
            <input type="hidden" name="_nacido" value="<?= time() ?>">

            <?php /* «Paso 1 de 2» y el 50% son el estado inicial, para que sin
                     JavaScript el rótulo diga algo cierto. Con JavaScript los
                     recalcula form.js contando los fieldset con data-paso, así
                     que cambiar el número de pasos no obliga a tocar esto. */ ?>
            <div class="progreso">
              <p class="progreso__texto"><span data-progreso-texto>Paso 1 de 2</span> <span>Fase 01 · Inscripción</span></p>
              <p class="progreso__barra"><span data-progreso-barra style="width:50%"></span></p>
            </div>

            <?php /* Aviso de errores. Lo rellena form.js con JS; sin JS lo
                     rellena el servidor, y en los dos casos es el mismo hueco.

                     Una línea, no una lista: el detalle de cada error está
                     junto a su campo, y repetirlo aquí ocupaba tanto que
                     empujaba el botón de enviar fuera de la parte visible. */ ?>
            <div class="resumen-errores" data-resumen-errores role="alert"<?= $errores === [] ? ' hidden' : '' ?>>
              <?php if ($errores !== []): ?>
                <?php
                  $cuantos = count($errores);
                  $general = $errores['_'] ?? null;
                ?>
                <span>
                  <?php if ($general !== null): ?>
                    <?= $esc($general) ?>
                  <?php elseif ($cuantos === 1): ?>
                    Falta un dato, marcado en rojo.
                  <?php else: ?>
                    Faltan <strong><?= $cuantos ?> datos</strong>, marcados en rojo.
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </div>

            <!-- ── INSCRIPCIÓN ──
                 El DNI va PRIMERO porque es la llave: cuando se conecte la
                 consulta a RENIEC, será el campo que rellene el nombre solo. -->
            <fieldset class="paso" data-paso>
              <legend class="solo-lectores">Tus datos</legend>
              <h3 class="paso__titulo">Inscripción</h3>
              <p class="paso__pie">Tus datos tal como figuran en tu documento.</p>

              <?php /* --par: el DNI y el nombre comparten línea; la fecha ocupa la
                       siguiente entera. Ahorra una línea sin alterar el orden. */ ?>
              <div class="paso__rejilla paso__rejilla--par">

                <div class="campo campo--con-boton<?= isset($errores['dni']) ? ' campo--error' : '' ?>">
                  <label class="campo__etiqueta" for="dni">DNI *</label>
                  <span class="campo__grupo">
                    <svg class="campo__simbolo" aria-hidden="true"><use href="#i-documento"/></svg><input type="text" id="dni" name="dni" inputmode="numeric" maxlength="8"
                           required data-valida="requerido dni" autocomplete="off"
                           aria-describedby="dni-ayuda"
                           value="<?= $esc($anterior['dni'] ?? '') ?>"<?= isset($errores['dni']) ? ' aria-invalid="true"' : '' ?>>
                    <?php /* La lupa. Hoy sólo avisa de que la consulta todavía
                             no está disponible; el día que se contrate el
                             servicio, rellenará el nombre y lo dejará en sólo
                             lectura. El punto de conexión es una única función
                             en form.js, igual que se hizo con el envío. */ ?>
                    <button class="campo__lupa" type="button" data-buscar-dni
                            aria-controls="nombres"
                            title="Buscar los datos de este DNI">
                      <svg aria-hidden="true"><use href="#i-buscar"/></svg>
                      <span class="solo-lectores">Buscar los datos de este DNI</span>
                    </button>
                  </span>
                  <p class="campo__ayuda" id="dni-ayuda">Ocho dígitos, sin puntos ni guiones.</p>
                  <p class="campo__estado" data-dni-estado role="status" aria-live="polite"></p>
                  <?php if (isset($errores['dni'])): ?><p class="campo__error" role="alert"><?= $esc($errores['dni']) ?></p><?php endif; ?>
                </div>

                <div class="campo<?= isset($errores['nombres']) ? ' campo--error' : '' ?>">
                  <label class="campo__etiqueta" for="nombres">Nombre completo *</label>
                  <svg class="campo__simbolo" aria-hidden="true"><use href="#i-persona"/></svg><input type="text" id="nombres" name="nombres" autocomplete="name" required data-valida="requerido"
                         value="<?= $esc($anterior['nombres'] ?? '') ?>"<?= isset($errores['nombres']) ? ' aria-invalid="true"' : '' ?>>
                  <?php if (isset($errores['nombres'])): ?><p class="campo__error" role="alert"><?= $esc($errores['nombres']) ?></p><?php endif; ?>
                </div>

                <?php /* ── Fecha de nacimiento ──
                         Un solo campo, que flatpickr convierte en calendario.

                         Nace como <input type="date"> para que siga funcionando
                         si el CDN no responde o el JavaScript falla; en cuanto
                         flatpickr carga, lo sustituye por su calendario, que es
                         el que sí permite decidir por dónde se abre.

                         El atributo data-abre-en es la respuesta al problema
                         real: el calendario nativo empieza por el mes actual y
                         obliga a retroceder treinta y tantos años. Aquí se abre
                         directamente en enero de 1990, SIN rellenar el campo:
                         una fecha puesta de oficio que nadie revisa acaba
                         guardada como si fuera la de verdad. */ ?>
                <div class="campo<?= isset($errores['nacimiento']) ? ' campo--error' : '' ?>">
                  <label class="campo__etiqueta" for="nacimiento">Fecha de nacimiento *</label>
                  <svg class="campo__simbolo" aria-hidden="true"><use href="#i-calendario"/></svg><input type="date" id="nacimiento" name="nacimiento" autocomplete="bday"
                         required data-valida="fecha"
                         data-calendario
                         data-abre-en="1990-01-01"
                         min="<?= $esc($fechaMin) ?>" max="<?= $esc($fechaMax) ?>"
                         value="<?= $esc($anterior['nacimiento'] ?? '') ?>"<?= isset($errores['nacimiento']) ? ' aria-invalid="true"' : '' ?>>
                  <?php if (isset($errores['nacimiento'])): ?><p class="campo__error" role="alert"><?= $esc($errores['nacimiento']) ?></p><?php endif; ?>
                </div>

              </div>

            <?php /* ── UBICACIÓN ──
                     Va DENTRO del primer paso, no como paso propio. El
                     formulario pasó de cuatro pasos a dos: «tus datos» y
                     «contacto y envío». Las cuatro secciones siguen existiendo
                     como encabezados porque agrupar ayuda a leer; lo que se
                     redujo son los saltos de pantalla, que son lo que hay que
                     aprender a manejar. */ ?>
            <fieldset class="paso__seccion">
              <legend class="solo-lectores">Dónde vives</legend>
              <h3 class="paso__titulo">Ubicación</h3>
              <p class="paso__pie">Sirve para asignarte a la sede que te quede cerca.</p>

              <div class="paso__rejilla">

                <?php /* ── Ubigeo ──
                         Los tres desplegables se rellenan en cascada con JS.
                         SIN JavaScript también funcionan: al elegir uno, el
                         formulario se reenvía y el servidor devuelve el
                         siguiente ya cargado (ver el <noscript> del botón). */ ?>
                <fieldset class="campo-grupo">
                  <legend class="campo__etiqueta">Dónde vives *</legend>

                  <div class="paso__rejilla paso__rejilla--tres">
                    <div class="campo<?= isset($errores['ubigeo_departamento_id']) ? ' campo--error' : '' ?>">
                      <label class="campo__etiqueta campo__etiqueta--menor" for="ubigeo_departamento_id">Departamento</label>
                      <span class="campo__selector">
                        <select id="ubigeo_departamento_id" name="ubigeo_departamento_id" required
                                data-valida="requerido" data-ubigeo="departamento"
                                <?= $ubigeoElegido["departamento"] !== "" ? " data-conservar=\"1\"" : "" ?>
                                <?= isset($errores['ubigeo_departamento_id']) ? ' aria-invalid="true"' : '' ?>>
                          <option value="">Elige uno</option>
                          <?php foreach ($departamentos as $d): ?>
                            <option value="<?= $esc($d['id']) ?>"<?= $ubigeoElegido['departamento'] === $d['id'] ? ' selected' : '' ?>><?= $esc($d['nombre']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <svg aria-hidden="true"><use href="#i-chevron"/></svg>
                      </span>
                      <?php if (isset($errores['ubigeo_departamento_id'])): ?><p class="campo__error" role="alert"><?= $esc($errores['ubigeo_departamento_id']) ?></p><?php endif; ?>
                    </div>

                    <?php /* ── Provincia y distrito: se ESCRIBEN, no se eligen ──
                             Eran dos desplegables encadenados y ahí estaba el
                             problema: un desplegable vacío es una puerta
                             cerrada. Si la lista no llegaba —el servidor lento,
                             la consulta caída— la persona veía «No se pudo
                             cargar» y no tenía absolutamente nada que hacer.
                             Varias lo dijeron el mismo día que se abrió la
                             convocatoria.

                             Ahora son campos de texto con sugerencias. La lista
                             ayuda cuando está; cuando no está, se escribe a
                             mano y el formulario pasa igual. El dato puede
                             venir con una falta de ortografía, y eso se arregla
                             después desde el panel; una inscripción que no
                             llega no se arregla nunca.

                             El campo visible lleva el NOMBRE y viaja siempre.
                             El <input hidden> de al lado lleva el código
                             oficial y sólo se rellena si la persona tomó una
                             sugerencia. El servidor, además, intenta reconocer
                             el nombre escrito y recuperar el código él mismo. */ ?>
                    <div class="campo<?= isset($errores['ubigeo_provincia_id']) ? ' campo--error' : '' ?>">
                      <label class="campo__etiqueta campo__etiqueta--menor" for="provincia_nombre">Provincia</label>
                      <div class="sugiere" data-sugiere="provincia">
                        <svg class="campo__simbolo" aria-hidden="true"><use href="#i-mapa"/></svg><input type="text" id="provincia_nombre" name="provincia_nombre" required
                               class="campo__control"
                               data-valida="requerido" data-ubigeo="provincia"
                               autocomplete="off" autocapitalize="characters" spellcheck="false"
                               role="combobox" aria-expanded="false" aria-autocomplete="list"
                               aria-controls="provincia-sugerencias"
                               placeholder="Escribe tu provincia"
                               value="<?= $esc($ubigeoElegido['provincia_nombre']) ?>"
                               <?= isset($errores['ubigeo_provincia_id']) ? ' aria-invalid="true"' : '' ?>>
                        <input type="hidden" id="ubigeo_provincia_id" name="ubigeo_provincia_id"
                               value="<?= $esc($ubigeoElegido['provincia']) ?>">
                        <ul class="sugiere__lista" id="provincia-sugerencias" role="listbox" hidden></ul>
                      </div>
                      <?php if (isset($errores['ubigeo_provincia_id'])): ?><p class="campo__error" role="alert"><?= $esc($errores['ubigeo_provincia_id']) ?></p><?php endif; ?>
                    </div>

                    <div class="campo<?= isset($errores['ubigeo_distrito_id']) ? ' campo--error' : '' ?>">
                      <label class="campo__etiqueta campo__etiqueta--menor" for="distrito_nombre">Distrito</label>
                      <div class="sugiere" data-sugiere="distrito">
                        <svg class="campo__simbolo" aria-hidden="true"><use href="#i-mapa"/></svg><input type="text" id="distrito_nombre" name="distrito_nombre" required
                               class="campo__control"
                               data-valida="requerido" data-ubigeo="distrito"
                               autocomplete="off" autocapitalize="characters" spellcheck="false"
                               role="combobox" aria-expanded="false" aria-autocomplete="list"
                               aria-controls="distrito-sugerencias"
                               placeholder="Escribe tu distrito"
                               value="<?= $esc($ubigeoElegido['distrito_nombre']) ?>"
                               <?= isset($errores['ubigeo_distrito_id']) ? ' aria-invalid="true"' : '' ?>>
                        <input type="hidden" id="ubigeo_distrito_id" name="ubigeo_distrito_id"
                               value="<?= $esc($ubigeoElegido['distrito']) ?>">
                        <ul class="sugiere__lista" id="distrito-sugerencias" role="listbox" hidden></ul>
                      </div>
                      <?php if (isset($errores['ubigeo_distrito_id'])): ?><p class="campo__error" role="alert"><?= $esc($errores['ubigeo_distrito_id']) ?></p><?php endif; ?>
                    </div>
                  </div>

                  <?php /* Ya no hace falta el botón que recargaba la página
                           para traer la lista siguiente: provincia y distrito
                           se escriben, así que sin JavaScript el formulario
                           funciona igual, sólo que sin sugerencias. Una cosa
                           menos que puede fallar. */ ?>
                  <p class="campo__ayuda">Escribe el nombre y elige de la lista. Si no aparece, escríbelo igual y continúa.</p>
                </fieldset>

                <div class="campo<?= isset($errores['direccion']) ? ' campo--error' : '' ?>">
                  <label class="campo__etiqueta" for="direccion">Dirección *</label>
                  <svg class="campo__simbolo" aria-hidden="true"><use href="#i-mapa"/></svg><input type="text" id="direccion" name="direccion" autocomplete="street-address" required data-valida="requerido" aria-describedby="direccion-ayuda"
                         value="<?= $esc($anterior['direccion'] ?? '') ?>"<?= isset($errores['direccion']) ? ' aria-invalid="true"' : '' ?>>
                  <p class="campo__ayuda" id="direccion-ayuda">Calle, avenida o jirón, y el número. El distrito ya lo elegiste arriba.</p>
                  <?php if (isset($errores['direccion'])): ?><p class="campo__error" role="alert"><?= $esc($errores['direccion']) ?></p><?php endif; ?>
                </div>
              </div>
            </fieldset>
            </fieldset><?php /* cierra el PASO 1 (inscripción + ubicación) */ ?>

            <!-- ── CONTACTO ── -->
            <fieldset class="paso" data-paso>
              <legend class="solo-lectores">Cómo te contactamos</legend>
              <h3 class="paso__titulo">Contacto</h3>
              <p class="paso__pie">Por aquí te escribirá la organización para la Fase 02.</p>

              <div class="paso__rejilla">
                <div class="paso__rejilla paso__rejilla--dos">
                  <div class="campo<?= isset($errores['correo']) ? ' campo--error' : '' ?>">
                    <label class="campo__etiqueta" for="correo">Correo electrónico *</label>
                    <svg class="campo__simbolo" aria-hidden="true"><use href="#i-correo"/></svg><input type="email" id="correo" name="correo" autocomplete="email" required data-valida="requerido correo"
                           value="<?= $esc($anterior['correo'] ?? '') ?>"<?= isset($errores['correo']) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errores['correo'])): ?><p class="campo__error" role="alert"><?= $esc($errores['correo']) ?></p><?php endif; ?>
                  </div>
                  <div class="campo<?= isset($errores['telefono']) ? ' campo--error' : '' ?>">
                    <label class="campo__etiqueta" for="telefono">Número telefónico *</label>
                    <svg class="campo__simbolo" aria-hidden="true"><use href="#i-telefono"/></svg><input type="tel" id="telefono" name="telefono" inputmode="numeric" autocomplete="tel" required data-valida="requerido telefono"
                           value="<?= $esc($anterior['telefono'] ?? '') ?>"<?= isset($errores['telefono']) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errores['telefono'])): ?><p class="campo__error" role="alert"><?= $esc($errores['telefono']) ?></p><?php endif; ?>
                  </div>
                </div>

                <?php /* Contacto de emergencia: OPCIONAL.
                         Se pide, pero no se exige. Quien no lo tenga a mano no
                         puede quedarse sin inscribirse por eso; la organización
                         lo reclamará en la Fase 02. Sin el asterisco y con la
                         etiqueta «opcional» a la vista: un campo que no es
                         obligatorio tiene que parecerlo. */ ?>
                <div class="campo-grupo">
                  <p class="campo__etiqueta">Contacto de emergencia <span class="campo__opcional">opcional</span></p>
                  <p class="campo__ayuda">Sólo se usaría durante los días de servicio. Puedes dejarlo en blanco y darlo más adelante.</p>

                  <div class="paso__rejilla paso__rejilla--dos">
                    <div class="campo<?= isset($errores['emergencia_nombre']) ? ' campo--error' : '' ?>">
                      <label class="campo__etiqueta campo__etiqueta--menor" for="emergencia-nombre">Nombre</label>
                      <svg class="campo__simbolo" aria-hidden="true"><use href="#i-persona"/></svg><input type="text" id="emergencia-nombre" name="emergencia_nombre"
                             value="<?= $esc($anterior['emergencia_nombre'] ?? '') ?>"<?= isset($errores['emergencia_nombre']) ? ' aria-invalid="true"' : '' ?>>
                      <?php if (isset($errores['emergencia_nombre'])): ?><p class="campo__error" role="alert"><?= $esc($errores['emergencia_nombre']) ?></p><?php endif; ?>
                    </div>
                    <div class="campo<?= isset($errores['emergencia_telefono']) ? ' campo--error' : '' ?>">
                      <label class="campo__etiqueta campo__etiqueta--menor" for="emergencia-telefono">Teléfono</label>
                      <svg class="campo__simbolo" aria-hidden="true"><use href="#i-telefono"/></svg><input type="tel" id="emergencia-telefono" name="emergencia_telefono" inputmode="numeric" data-valida="telefono"
                             value="<?= $esc($anterior['emergencia_telefono'] ?? '') ?>"<?= isset($errores['emergencia_telefono']) ? ' aria-invalid="true"' : '' ?>>
                      <?php if (isset($errores['emergencia_telefono'])): ?><p class="campo__error" role="alert"><?= $esc($errores['emergencia_telefono']) ?></p><?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

            <!-- ── TU SERVICIO ── (dentro del paso 2) -->
            <fieldset class="paso__seccion">
              <legend class="solo-lectores">Tu servicio</legend>
              <h3 class="paso__titulo">Tu servicio</h3>
              <p class="paso__pie">Dónde y en qué quieres servir. Es tu preferencia: la asignación definitiva llega en la Fase 03.</p>

              <?php /* Los tres desplegables en una sola fila. Eran dos filas y
                       la de abajo era la que sacaba el paso de la pantalla.
                       Son listas cerradas y cortas de leer: caben. */ ?>
              <div class="paso__rejilla">
                <div class="paso__rejilla paso__rejilla--tres">
                  <div class="campo<?= isset($errores['jurisdiccion_id']) ? ' campo--error' : '' ?>">
                    <label class="campo__etiqueta campo__etiqueta--menor" for="jurisdiccion">Jurisdicción *</label>
                    <span class="campo__selector">
                      <select id="jurisdiccion" name="jurisdiccion_id" required data-valida="requerido"<?= isset($errores['jurisdiccion_id']) ? ' aria-invalid="true"' : '' ?>>
                        <option value="">Elige una</option>
                        <?php /* Una jurisdicción llena SE VE, con su nombre y un «completado»
                                 detrás, y no se puede elegir. Quitarla del desplegable haría
                                 pensar que la web falla o que esa sede no existe; dejarla a la
                                 vista explica por qué no está disponible.

                                 El «disabled» es sólo la pista visual. Quien decide de verdad
                                 es Inscripcion.php al guardar: aquí no hay ninguna garantía,
                                 porque el id se puede enviar a mano y porque entre abrir el
                                 formulario y enviarlo pueden agotarse las plazas. */ ?>
                        <?php foreach ($jurisdicciones as $j): ?>
                          <?php $llena = !empty($j['completa']); ?>
                          <option value="<?= (int) $j['id'] ?>"<?= $llena ? ' disabled' : '' ?><?= !$llena && (string) ($anterior['jurisdiccion_id'] ?? '') === (string) $j['id'] ? ' selected' : '' ?>><?= $esc($j['nombre']) ?><?= $llena ? ' — completado' : '' ?></option>
                        <?php endforeach; ?>
                      </select>
                      <svg aria-hidden="true"><use href="#i-chevron"/></svg>
                    </span>
                    <?php if (isset($errores['jurisdiccion_id'])): ?><p class="campo__error" role="alert"><?= $esc($errores['jurisdiccion_id']) ?></p><?php endif; ?>
                  </div>
                  <div class="campo<?= isset($errores['talla']) ? ' campo--error' : '' ?>">
                    <label class="campo__etiqueta" for="talla">Talla de polo *</label>
                    <span class="campo__selector">
                      <select id="talla" name="talla" required data-valida="requerido"<?= isset($errores['talla']) ? ' aria-invalid="true"' : '' ?>>
                        <option value="">Elige una</option>
                        <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $t): ?>
                          <option<?= (string) ($anterior['talla'] ?? '') === $t ? ' selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                      </select>
                      <svg aria-hidden="true"><use href="#i-chevron"/></svg>
                    </span>
                    <?php if (isset($errores['talla'])): ?><p class="campo__error" role="alert"><?= $esc($errores['talla']) ?></p><?php endif; ?>
                  </div>

                <div class="campo<?= isset($errores['servicio_id']) ? ' campo--error' : '' ?>">
                  <label class="campo__etiqueta campo__etiqueta--menor" for="servicio">Servicio *</label>
                  <span class="campo__selector">
                    <select id="servicio" name="servicio_id" required data-valida="requerido" aria-describedby="servicio-ayuda"<?= isset($errores['servicio_id']) ? ' aria-invalid="true"' : '' ?>>
                      <option value="">Elige uno</option>
                      <?php foreach ($servicios as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"<?= (string) ($anterior['servicio_id'] ?? '') === (string) $s['id'] ? ' selected' : '' ?>><?= $esc($s['nombre']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <svg aria-hidden="true"><use href="#i-chevron"/></svg>
                  </span>
                  <p class="campo__ayuda" id="servicio-ayuda">Es tu preferencia. La asignación definitiva se comunica en la Fase 03.</p>
                  <?php if (isset($errores['servicio_id'])): ?><p class="campo__error" role="alert"><?= $esc($errores['servicio_id']) ?></p><?php endif; ?>
                </div>
                </div><?php /* cierra la fila de tres */ ?>

                <?php /* La frase que sigue al párrafo del panel es fija a propósito: es
                         la declaración de consentimiento, no una nota informativa, y la
                         CEP pidió su redacción exacta —«autorizo el tratamiento y
                         transferencia», no sólo «he leído»—. Que quede aquí y no en el
                         campo editable evita que un cambio de contenido la debilite sin
                         que nadie se dé cuenta: «he leído la política» no es lo mismo que
                         autorizar el tratamiento y la transferencia de los datos. */ ?>
                <?php
                /* ── Dos textos, no uno ───────────────────────────────────
                   Aquí conviven cosas distintas y hasta ahora iban pegadas en
                   un único bloque de texto plano dentro de la etiqueta:

                     · la INFORMACIÓN: quién trata los datos, bajo qué ley, a
                       quién se transfieren y cuánto se conservan. Sale del
                       panel y son unas ochenta palabras.
                     · la DECLARACIÓN: la frase que la persona autoriza al
                       marcar la casilla. Veintitantas palabras.

                   Juntas formaban un muro de 750 px en una columna de 530, y
                   eso es lo que aplastaba el formulario. Separadas, la
                   información puede ir a dos columnas y en cuerpo menor
                   —sigue entera, no se recorta ni se esconde— y la
                   declaración se queda al lado de la casilla, a tamaño
                   normal, que es lo que de verdad se está aceptando.

                   Y hay una razón que no se ve: dentro de la <label>, TODO
                   ese texto era el nombre de la casilla. Un lector de
                   pantalla leía las ciento dieciséis palabras cada vez que
                   llegaba a ella. Fuera de la etiqueta y enlazada con
                   aria-describedby, la casilla se anuncia con su declaración
                   y la información se ofrece como descripción. */
                $lineasConsentimiento = preg_split(
                    '/\R+/u',
                    (string) $dato('inscripcion', 'consentimiento', ''),
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ) ?: [];
                ?>
                <div class="consent-legal<?= isset($errores['consentimiento']) ? ' campo--error' : '' ?>">
                  <?php if ($lineasConsentimiento !== []): ?>
                    <div class="consent-legal__info" id="consent-legal-info">
                      <?php foreach ($lineasConsentimiento as $linea): ?>
                        <p><?= $rico($linea) ?></p>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <label class="casilla">
                    <input type="checkbox" id="consentimiento" name="consentimiento" value="1" required<?= !empty($anterior['consentimiento']) ? ' checked' : '' ?><?= $lineasConsentimiento !== [] ? ' aria-describedby="consent-legal-info"' : '' ?>>
                    <span class="casilla__texto">
                      <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">Ver Política de Privacidad completa</a>. He leído la información proporcionada y autorizo el tratamiento y transferencia de mis datos personales para las finalidades indicadas. *
                    </span>
                  </label>
                </div>
                <?php if (isset($errores['consentimiento'])): ?><p class="campo__error" role="alert"><?= $esc($errores['consentimiento']) ?></p><?php endif; ?>
              </div>
            </fieldset>
            </fieldset><?php /* cierra el PASO 2 (contacto + tu servicio) */ ?>

            <?php /* Trampa para robots: ningún humano la ve, casi todo robot la
                     rellena. Si llega con contenido, el envío se descarta. */ ?>
            <p class="trampa" aria-hidden="true"><label for="sitio-web">No rellenar</label><input type="text" id="sitio-web" name="sitio-web" tabindex="-1" autocomplete="off"></p>

            <div class="form-acciones">
              <button class="btn btn--linea" type="button" data-atras hidden>Atrás</button>
              <button class="btn btn--primario" type="button" data-adelante>Continuar</button>
              <button class="btn btn--primario" type="submit" data-enviar>Enviar inscripción</button>
            </div>
          </form>

          <div class="confirmacion" data-confirmacion hidden>
            <svg class="ornamento" aria-hidden="true"><use href="#i-lirio"/></svg>
            <h2>Recibimos tu inscripción</h2>
            <p>Gracias por ofrecer tu tiempo. Tu código de inscripción es <strong data-codigo>—</strong>; guárdalo por si necesitas escribirnos.</p>

            <?php /* Sólo aparece cuando la inscripción no pudo entrar en la
                     base y quedó guardada aparte. Se confirma igual —el envío
                     llegó de verdad— pero sin decir «ya estás inscrito», que
                     sería falso: alguien tiene que pasarla a mano todavía.

                     Es honesto sin alarmar. La persona no tiene que hacer
                     nada, y ese es el mensaje. */ ?>
            <p class="confirmacion__nota" data-contingencia hidden>
              Tu inscripción quedó registrada, aunque en este momento estamos
              teniendo problemas técnicos y todavía hay que terminar de
              procesarla. <strong>No hace falta que la envíes de nuevo</strong>:
              tus datos están guardados y nos ocupamos nosotros.
            </p>

            <p>Te escribiremos al correo que nos has dejado para continuar con la <strong>Fase 02</strong>, cuando corresponda.</p>
            <p>Mientras tanto, puedes seguir la preparación del viaje en la <a href="<?= $esc($sitio->enlace('agenda/')) ?>">página de agenda</a> o compartir la convocatoria con tu parroquia.</p>
          </div>
          </div><!-- /.form-fijo -->

          <?php /* ══════════════ TEN A MANO ══════════════
                   El recuadro rosado con lo que hay que reunir antes de
                   empezar. Es el mismo contenido de siempre —«Formulario de
                   inscripción» → datos— con el marcado del diseño nuevo, y
                   ahora va DEBAJO del formulario, que es donde lo pone el
                   editable. Quien lo lee antes lo rellena de un tirón; quien
                   no, lo abandona a mitad buscando el DNI. */ ?>
          <?php
          /* Con su lista de reserva, como todo lo que sale de la base: si
             MySQL no responde, el recuadro sigue diciendo qué hay que reunir
             antes de empezar. Es el texto del editable. */
          $tenAMano = (array) $dato('inscripcion', 'ten_a_mano', [
              'Tu <strong>DNI</strong>, ocho dígitos',
              'Tu <strong>nombre completo</strong>',
              'Tu <strong>fecha de nacimiento</strong>',
              'Tu <strong>departamento, provincia y distrito</strong>',
              'Tu <strong>dirección</strong>: calle o avenida y número',
              'Tu <strong>correo electrónico</strong> y tu <strong>número telefónico</strong>',
              'Tu <strong>talla de polo</strong>: S, M, L, XL o XXL',
              'La <strong>jurisdicción</strong> en la que quieres servir',
              'El <strong>servicio</strong> que prefieres, de los seis',
          ]);
          $notaMano = (string) $dato(
              'inscripcion',
              'nota',
              'El contacto de emergencia es opcional: puedes darlo más adelante. '
              . 'Y si te interrumpen, lo que hayas escrito se guarda en este navegador.'
          );
          ?>
          <?php if ($tenAMano !== []): ?>
            <?php /* ── El recuadro «Ten a mano» ──────────────────────────
                     En el editable va debajo del formulario, y ahí se queda en
                     pantallas estrechas. Pero en escritorio el formulario sólo
                     enseña un paso cada vez, así que el recuadro caía justo
                     detrás de los primeros campos y partía el formulario en
                     dos: se rellenaba el DNI y aparecía una caja rosa enorme
                     antes de terminar.

                     En escritorio pasa a ser un cajón pegado al lateral, que
                     se pliega. Así acompaña sin estorbar y deja el formulario
                     entero para lo suyo. El estado se recuerda en el
                     navegador: quien lo cierra no se lo encuentra abierto en
                     cada paso. */ ?>
            <aside class="vol-mano" data-mano>
              <?php /* El botón sólo tiene sentido con JavaScript: es él quien
                       pliega. Sin JS no se pinta y el recuadro se queda como
                       estaba, debajo del formulario y siempre visible. */ ?>
              <button class="vol-mano__plegar" type="button" data-mano-plegar hidden
                      aria-expanded="true" aria-controls="ten-a-mano-cuerpo">
                <span class="vol-mano__plegar-txt">Ocultar</span>
                <span class="vol-mano__plegar-ico" aria-hidden="true"></span>
              </button>

              <p class="vol-mano__cab">
                <svg class="vol-mano__ico" viewBox="0 0 45 45" width="45" height="45" aria-hidden="true" focusable="false">
                  <g fill="none" stroke="#6E0B14" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M40.9 14.1A20.8 20.8 0 1 1 32.9 4.5"/>
                    <path d="M10.4 18.5 21.7 35.4 41.1 2.6" stroke-width="6.4"/>
                  </g>
                </svg>
                <span class="vol-mano__tit"><?= $esc((string) $dato('inscripcion', 'ten_a_mano_titulo', 'Ten a mano')) ?></span>
              </p>

              <div class="vol-mano__cuerpo" id="ten-a-mano-cuerpo">
                <ul class="vol-mano__lista">
                  <?php foreach ($tenAMano as $item): ?>
                    <li><span aria-hidden="true">•</span> <?= $rico($item) ?></li>
                  <?php endforeach; ?>
                </ul>
                <?php if ($notaMano !== ''): ?>
                  <p class="vol-mano__nota"><?= $esc($notaMano) ?></p>
                <?php endif; ?>
              </div>
            </aside>
          <?php endif; ?>

        <?php endif; ?>
        <?php /* ── Fin del bloque copiado ────────────────────────────────── */ ?>

      </div>
    </div>
  </section>

  <?php if ($hay('despues')): ?>
  <?php /* ═══════════════════════════════════════════════════ QUÉ PROCEDE ══
       El cierre de la página: a quién escribir y qué pasa después de enviar.
       El texto llega del panel como HTML, así que no puede traer clases: los
       estilos de la hoja se cuelgan de las etiquetas (h3 para el titulillo
       granate, p para los párrafos). */ ?>
  <section class="vol-procede" id="despues" aria-labelledby="t-procede">
    <h2 class="vol-procede__h" id="t-procede"><?= $esc($campo('despues', 'titulo', '¿Qué procede?')) ?></h2>
    <p class="vol-regla vol-regla--izq" aria-hidden="true"></p>

    <div class="vol-procede__cuerpo">
      <?= $rico($campo(
          'despues',
          'texto',
          '<h3>Consultas sobre el voluntariado.</h3>'
          . '<p>Enviar el formulario no es quedar seleccionado, y tampoco hace falta que hagas nada más por '
          . 'tu cuenta: a partir de aquí la organización te busca a ti.</p>'
          . '<p>Para consultas o dudas relacionadas con el voluntariado para la visita del papa León XIV al '
          . 'Perú 2026, puedes escribir a <a href="mailto:comunica.laicosyjuventud@iglesiacatolica.org.pe">'
          . 'comunica.laicosyjuventud@iglesiacatolica.org.pe</a></p>'
      )) ?>
    </div>
  </section>
  <?php endif; ?>

</div><!-- /.vol-marco -->

</main>

<?php
/* Lo que va FUERA de .pagina: el panel flotante de acceso al formulario
   y el guardián que comprueba que el navegador ejecuta el form.js de
   esta versión y no una copia guardada. La plantilla los imprime al
   final por 'pie_extra'. Se ejecutan aquí y se recoge su salida porque
   llevan PHP dentro. */

/* La versión del form.js que ESTE HTML espera. Se lee del propio archivo, y
   el guardián de abajo compara lo que ejecuta el navegador con este valor.
   Sin esto, `esperada` sale null y el guardián no detecta nada: es justo el
   caso que hay que cubrir, un navegador ejecutando un form.js cacheado. */
$rutaForm    = dirname(__DIR__) . '/assets/js/form.js';
$versionForm = '';
if (preg_match('/window\.L14\.version\s*=\s*"([^"]*)"/',
        (string) @file_get_contents($rutaForm), $mVersion)) {
    $versionForm = $mVersion[1];
}
ob_start();
?>
<?php if ($abierto && $confirmado === null && $testigo !== null): ?>
  <?php /* Panel flotante de acceso al formulario. Nace con hidden y lo destapa
           ancla-form.js: una barra que no se retira nunca estorba más de lo que
           ayuda. */ ?>
  <aside class="ancla-form" data-ancla-form aria-label="Acceso rápido a la inscripción" hidden>
    <div>
      <span class="ancla-form__rotulo"><?= $esc($dato('inscripcion', 'ancla_rotulo', 'Fase 01 · Solo por internet')) ?></span>
      <p class="ancla-form__titulo"><?= $esc($dato('inscripcion', 'ancla_titulo', 'Inscríbete como voluntario')) ?></p>
      <p class="ancla-form__dato"><?= $esc($dato('inscripcion', 'ancla_dato', 'Once datos, unos cinco minutos')) ?></p>
    </div>
    <a class="btn btn--primario" href="#inscripcion">Ir al formulario</a>
  </aside>
<?php endif; ?>
<?php if ($versionForm !== ''): ?>
<script nonce="<?= $esc($sitio->nonce()) ?>">
(function () {
  var esperada = <?= json_encode($versionForm, JSON_UNESCAPED_UNICODE) ?>;
  var marca = 'l14-recarga-version';

  window.addEventListener('load', function () {
    var recibida = (window.L14 && window.L14.version) || '(ninguna)';
    if (recibida === esperada) {
      try { sessionStorage.removeItem(marca); } catch (e) {}
      return;
    }

    var yaSeIntento = false;
    try { yaSeIntento = sessionStorage.getItem(marca) === esperada; } catch (e) {}

    if (yaSeIntento) {
      console.error('[l14] sigue llegando un form.js viejo (' + recibida +
                    ' en vez de ' + esperada + '). Hay una caché que ignora ' +
                    'la versión de la dirección.');
      return;
    }

    try { sessionStorage.setItem(marca, esperada); } catch (e) {}
    console.warn('[l14] form.js desfasado (' + recibida + '), recargando…');

    /* Se añade un parámetro distinto para que ninguna caché intermedia pueda
       responder con lo que ya tenía guardado de esta misma dirección. */
    var u = new URL(window.location.href);
    u.searchParams.set('_v', esperada);
    window.location.replace(u.toString());
  });
})();
</script>
<?php endif; ?>
<?php
$meta['pie_extra'] = ob_get_clean();
