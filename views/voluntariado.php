<?php
/**
 * Vista de la página «voluntariado».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
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
$jurisdicciones = $espejo->recordar('jurisdicciones', fn () => $catalogo->jurisdicciones());

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
    'scripts'     => ['assets/vendor/flatpickr.min.js', 'assets/vendor/flatpickr-es.js', 'assets/js/ancla-form.js'],
    'css'         => ['assets/vendor/flatpickr.min.css'],
];
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-voluntariado-640.webp 640w, ../assets/img/fotos/cab-voluntariado-1024.webp 1024w, ../assets/img/fotos/cab-voluntariado-1600.webp 1600w, ../assets/img/fotos/cab-voluntariado-1920.webp 1920w">
      <?php /* Es la imagen más grande y visible al cargar: eager y prioridad
               alta. Con loading="lazy" el navegador la pedía tarde y la
               cabecera aparecía en blanco durante el primer segundo. */ ?>
      <img src="../assets/img/fotos/cab-voluntariado-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-voluntariado-640.jpg 640w, ../assets/img/fotos/cab-voluntariado-1024.jpg 1024w, ../assets/img/fotos/cab-voluntariado-1600.jpg 1600w, ../assets/img/fotos/cab-voluntariado-1920.jpg 1920w" width="1920" height="823" alt="Jóvenes acompañan la cruz peregrina en una procesión" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Voluntariado')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Los amigos de León')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'La visita del Santo Padre al Perú será una experiencia inolvidable, y queremos vivirla sirviendo, acogiendo y haciendo comunidad.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
          <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
          <li><span aria-current="page">Voluntariado</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<?php if ($hay('resumen')): ?>
<!-- ══════════════ EN TREINTA SEGUNDOS ══════════════ -->
<section class="seccion seccion--tinte seccion--pastel beforeTop" id="resumen" aria-labelledby="t-resumen">
  <div class="contenedor">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('resumen', 'rotulo', 'En treinta segundos')) ?></span>
      <h2 id="t-resumen" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('resumen', 'titulo', 'Cómo se es voluntario')) ?></span></span></h2>
    </header>

    <ol class="resumen-pasos">
      <?php foreach ($bloques('resumen') as $i => $b): ?>
        <li class="resumen-paso" data-reveal="fade-rise"<?= $i ? ' data-reveal-delay="' . number_format($i * 0.07, 2, '.', '') . '"' : '' ?>>
          <span class="resumen-paso__num indice"><?= $esc(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
          <span class="resumen-paso__titulo"><?= $esc($b['titulo']) ?></span>
          <span class="resumen-paso__texto"><?= $esc($b['texto']) ?></span>
          <?php if (!empty($b['enlace_url'])): ?>
            <a class="resumen-paso__ir" href="<?= $esc($b['enlace_url']) ?>"><?= $esc($b['enlace_texto'] ?: 'Ver más') ?> <svg aria-hidden="true"><use href="#i-flecha"/></svg></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if ($hay('servicios')): ?>
<!-- ══════════════ LOS SEIS SERVICIOS ══════════════
     Las tarjetas salen de la tabla `servicios`, que es la MISMA que llena el
     <select> del formulario. Si estuvieran duplicadas, un día la tarjeta diría
     una cosa y la opción del desplegable otra. -->
<section class="seccion" id="servicios" aria-labelledby="t-servicios">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('servicios', 'rotulo', 'Seis servicios')) ?></span>
      <h2 class="titular--mayor" id="t-servicios" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('servicios', 'titulo', 'Hay un lugar para cada talento y cada corazón')) ?></span></span></h2>
      <p><?= $esc($campo('servicios', 'texto', 'Necesitamos voluntarios en los siguientes servicios:')) ?></p>
    </header>

    <div class="reticula">
      <?php foreach ($servicios as $i => $s): ?>
        <article class="servicio col-m-4 col-t-3 col-d-4" data-reveal="fade-rise"<?= $i ? ' data-reveal-delay="' . number_format($i * 0.06, 2, '.', '') . '"' : '' ?>>
          <svg class="servicio__icono" aria-hidden="true"><use href="#<?= $esc($s['icono'] ?: 'i-corazon') ?>"/></svg>
          <h3 class="servicio__titulo"><?= $esc(preg_replace('/^Servicio de /iu', '', (string) $s['nombre'])) ?></h3>
          <p class="servicio__texto"><?= $esc($s['descripcion']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="seccion__pie cierre-servicios" data-reveal="fade-rise" style="text-align: center;">
      <svg class="ornamento" aria-hidden="true"><use href="#i-corazon"/></svg>
      <?php foreach ((array) $dato('servicios', 'cierre', []) as $linea): ?>
        <p><?= $esc($linea) ?></p>
      <?php endforeach; ?>
      <p class="cierre-servicios__grito"><?= $esc($dato('servicios', 'grito', '¡El Perú te necesita!')) ?></p>
      <?php if ($abierto): ?>
        <p class="sep-m"><a class="btn btn--primario" href="<?= $esc($dato('servicios', 'boton_url', '#inscripcion')) ?>"><?= $esc($dato('servicios', 'boton_texto', 'Inscríbete ahora')) ?></a></p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($hay('proceso')): ?>
<!-- ══════════════ LAS TRES FASES ══════════════ -->
<section class="seccion seccion--tinte" id="proceso" aria-labelledby="t-fases">
  <div class="contenedor">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('proceso', 'rotulo', 'Tres fases')) ?></span>
      <h2 id="t-fases" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('proceso', 'titulo', 'Conoce el proceso de selección')) ?></span></span></h2>
      <p><?= $esc($campo('proceso', 'texto', 'Tres fases, en este orden. La numeración de aquí abajo no es adorno: marca una secuencia real.')) ?></p>
    </header>

    <ol class="fases">
      <?php foreach ($bloques('proceso') as $i => $b): ?>
        <li class="fase" data-reveal="fade-rise">
          <p class="fase__numero"><?= $esc($b['rotulo'] ?: str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></p>
          <div>
            <h3 class="fase__titulo"><?= $esc($b['titulo']) ?></h3>
            <p class="fase__texto"><?= $rico($b['texto']) ?></p>
            <?php $vinetas = $b['datos']['vinetas'] ?? []; ?>
            <?php if ($vinetas !== []): ?>
              <ul class="fase__lista">
                <?php foreach ($vinetas as $v): ?>
                  <li><?= $rico($v) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════ FORMULARIO — FASE 01 ══════════════ -->
<section class="seccion" id="inscripcion" aria-labelledby="t-inscripcion">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-4 col-t-6 col-d-8">

        <header class="seccion__encabezado seccion__encabezado--mayor">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('inscripcion', 'rotulo', 'Solo esta fase se hace por internet')) ?></span>
          <h2 class="titular--mayor" id="t-inscripcion" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('inscripcion', 'titulo', 'Inscríbete como voluntario')) ?></span></span></h2>
        </header>

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

          <?php /* Los datos que pide la Fase 01, enumerados ANTES del formulario.
                   Quien los reúne primero lo rellena de un tirón; quien no, lo
                   abandona a mitad buscando el DNI. */ ?>
          <?php $tenAMano = (array) $dato('inscripcion', 'ten_a_mano', []); ?>
          <?php if ($tenAMano !== []): ?>
            <div class="ten-a-mano" data-reveal="fade-rise">
              <p class="ten-a-mano__titulo"><svg aria-hidden="true"><use href="#i-check"/></svg> <?= $esc($dato('inscripcion', 'ten_a_mano_titulo', 'Ten a mano')) ?></p>
              <ul class="ten-a-mano__lista">
                <?php foreach ($tenAMano as $item): ?>
                  <li><?= $rico($item) ?></li>
                <?php endforeach; ?>
              </ul>
              <?php if ($dato('inscripcion', 'nota')): ?>
                <p class="ten-a-mano__nota"><?= $esc($dato('inscripcion', 'nota')) ?></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

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
                        <?php foreach ($jurisdicciones as $j): ?>
                          <option value="<?= (int) $j['id'] ?>"<?= (string) ($anterior['jurisdiccion_id'] ?? '') === (string) $j['id'] ? ' selected' : '' ?>><?= $esc($j['nombre']) ?></option>
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

                <label class="casilla<?= isset($errores['consentimiento']) ? ' campo--error' : '' ?>">
                  <input type="checkbox" id="consentimiento" name="consentimiento" value="1" required<?= !empty($anterior['consentimiento']) ? ' checked' : '' ?>>
                  <span class="casilla__texto">
                    <?= $rico($dato('inscripcion', 'consentimiento', 'Autorizo el tratamiento de mis datos personales para gestionar mi inscripción como voluntario.')) ?>
                    He leído la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>. *
                  </span>
                </label>
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

        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php if ($hay('despues')): ?>
<!-- ══════════════ DESPUÉS DE ENVIAR ══════════════ -->
<section class="seccion seccion--tinte" id="despues" aria-labelledby="t-despues">
  <div class="contenedor">
    <div class="reticula">
      <div class="col-m-5 col-t-7 col-d-8">
        <header class="seccion__encabezado">
          <hr class="seccion__filete" data-reveal="line-draw">
          <span class="rotulo"><?= $esc($campo('despues', 'rotulo', 'Después de enviar')) ?></span>
          <h2 id="t-despues" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('despues', 'titulo', 'Y entonces, ¿qué pasa?')) ?></span></span></h2>
        </header>

        <div class="texto-lectura">
          <?= $rico($campo('despues', 'texto', '<p>Enviar el formulario no es quedar seleccionado: a partir de aquí la organización te busca a ti.</p>')) ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

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
