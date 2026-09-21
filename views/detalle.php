<?php
/**
 * ============================================================================
 *  FICHA — la página propia de una pieza. Rediseño 2026.
 * ============================================================================
 *
 *  Una sede, un santo, un obispo, una comisión, una noticia o una nota de
 *  prensa. Una sola vista para las seis colecciones: lo que cambia entre ellas
 *  es el contenido, no la forma —un titular, una fotografía, un texto y el
 *  camino de vuelta al listado—.
 *
 *  ── De dónde salen los datos ─────────────────────────────────────────────
 *
 *  Esta vista NO llama a $sitio->contenido(): no es una página de la tabla
 *  `paginas`, es UNA FILA de `bloques` con dirección propia. Se llega por el
 *  slug y el reparto lo hace index.php, que resuelve la pieza con
 *  Models\Pagina::piezaPorSlug() y entrega:
 *
 *      $pieza   la fila del bloque, con su `datos` ya decodificado
 *      $padre   la ficha de la página a la que pertenece (sedes, noticias…)
 *
 *  Ese contrato se conserva tal cual. Lo que cambia con el rediseño es el
 *  aspecto: la maqueta es la del editable SEDES INDIVIDUALES.ai —héroe de
 *  582 px con el titular grande sobre la fotografía, cuerpo gris con los
 *  titulares en granate bajo un filete y tarjetas blancas en carrusel—,
 *  portada en assets/css/paginas/detalle.css.
 *
 *  ── Lo que el editable no podía prever ───────────────────────────────────
 *
 *  El editable compone «Callao». Aquí el texto llega del panel y la misma
 *  maqueta tiene que aguantar «Mons. Edinson Edgardo Farfán Córdova, OSA» o el
 *  titular de una noticia. Por eso el cuerpo del titular se elige contando
 *  caracteres y los altos fijos del editable son mínimos en la hoja.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable                $esc
 *  @var array                   $pieza
 *  @var array                   $padre
 *  @var string                  $activa
 */

declare(strict_types=1);

$rico = static fn (?string $v): string => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

/* ── El marcador «@bd» ────────────────────────────────────────────────────
   Los archivos de intranet/database/contenido usan «@bd» para decirle a la
   migración «deja lo que ya hay en la base». Es una marca del proceso de
   carga, no un texto: si alguna llega hasta aquí —hoy la tienen en su `datos`
   las cuatro sedes— se trata como campo vacío.

   Una ficha no puede llevar valores de reserva escritos en la vista como las
   demás páginas: su contenido ES la fila. Lo único que se puede hacer por
   ella es no publicar lo que no es texto. */
$limpio = static function (mixed $valor): string {
    $valor = trim((string) ($valor ?? ''));

    return $valor === '@bd' ? '' : $valor;
};

$titulo    = $limpio($pieza['titulo'] ?? '');
$rotulo    = $limpio($pieza['rotulo'] ?? '');
$cuerpo    = $limpio($pieza['texto'] ?? '');
$resumen   = $limpio($pieza['datos']['resumen'] ?? '');
$anios     = $limpio($pieza['datos']['anios'] ?? '');
$fecha     = $limpio($pieza['datos']['fecha'] ?? '');
$fuente    = $limpio($pieza['datos']['fuente'] ?? '');
$rutaPadre = ltrim((string) ($padre['ruta'] ?? '/'), '/');
$nombrePadre = (string) ($padre['nombre'] ?? '');

/* Para el resumen de buscadores y redes: el resumen propio si lo hay, y si no
   las primeras líneas del texto, ya sin etiquetas. */
$descripcion = $resumen !== ''
    ? $resumen
    : mb_substr(trim(strip_tags($cuerpo)), 0, 180);

$meta = [
    'titulo'      => $titulo . ' · ' . ($nombrePadre !== '' ? $nombrePadre : 'León XIV en el Perú'),
    'descripcion' => $descripcion,
    'ruta'        => $rutaPadre . $pieza['slug'] . '/',
    'og_tipo'     => 'article',
];

/* La imagen de la pieza es también la que se ve al compartir el enlace. */
if (!empty($pieza['imagen_ruta'])) {
    $meta['og_imagen'] = (string) $pieza['imagen_ruta'];
}

/* ── Las fichas hermanas, para el carrusel del pie ────────────────────────
   Si la base falla no se pinta ese bloque y ya: la ficha se sirve igual.
   El editable enseña cinco tarjetas y el carrusel corre, así que se piden
   seis en lugar de las tres de la maqueta anterior, que era una rejilla. */
$hermanas = [];
try {
    $hermanas = (new \Intranet\Models\Pagina($sitio->contenedor()))
        ->piezasHermanas((int) $pieza['seccion_id'], (int) $pieza['id'], 6);
} catch (Throwable $e) {
    error_log('[detalle] no se pudieron cargar las piezas hermanas: ' . $e->getMessage());
}

/* ── A qué colección pertenece la ficha ───────────────────────────────────
   Se mira la clave de la PÁGINA padre, que es la que decide el respaldo de la
   portada. Los santos se aceptan por sus dos claves: en la base la página se
   llama todavía «tierra-de-santos» y el mapa de rutas la publica como
   «santos», y el día que la migración la renombre esto tiene que seguir
   reconociéndola. */
$coleccion = (string) ($padre['clave'] ?? ($activa ?? ''));
$esSanto   = in_array($coleccion, ['tierra-de-santos', 'santos'], true);
$esSede    = $coleccion === 'sedes';

/* ── La portada de la ficha ───────────────────────────────────────────────
   Manda siempre el panel: si alguien elige una imagen para esta ficha, esa
   gana. Lo de aquí abajo es el respaldo por colección, y sólo para las dos
   que tienen una pieza pensada para eso.

   · SANTOS. Las fichas de los cinco santos no tienen fotografía propia —y no
     la van a tener: poner la cara de un santo en el sitio de otro es peor que
     no poner ninguna, que es la razón por la que en la portada llevan un
     medallón con su inicial—. Traen de respaldo la pieza que envió el
     cliente, la misma para las cinco, y AQUÍ SÍ es un fondo y no una pieza
     que preservar: se recorta como cualquier portada.

   · SEDES. El mismo caso: la vista de la sede del editable se compuso sobre
     una plaza peruana y esa fotografía ya está portada. Va como fondo
     decorativo —alt vacío— mientras cada sede no tenga la suya, que es lo que
     debería acabar habiendo.

   El resto de colecciones —obispos, comisiones, noticias, notas de prensa—
   se quedan con la banda granate lisa si no eligen foto: una fotografía
   prestada en la ficha de una persona o de una noticia diría algo que no es. */
$anchosPortada = static function (string $base, array $anchos) use ($sitio, $esc): array {
    $webp = $jpg = [];

    foreach ($anchos as $a) {
        $webp[] = $esc($sitio->asset("{$base}-{$a}.webp")) . " {$a}w";
        $jpg[]  = $esc($sitio->asset("{$base}-{$a}.jpg"))  . " {$a}w";
    }

    return [implode(', ', $webp), implode(', ', $jpg)];
};

$portadaRespaldo = '';

if ($esSanto) {
    [$webp, $jpg] = $anchosPortada('assets/img/banners/cabecera-santos', [640, 1024, 1600, 1950]);

    $portadaRespaldo = '<picture>'
      . '<source type="image/webp" sizes="100vw" srcset="' . $webp . '">'
      . '<img src="' . $esc($sitio->asset('assets/img/banners/cabecera-santos-1600.jpg')) . '"'
      . ' sizes="100vw" srcset="' . $jpg . '"'
      . ' width="1950" height="624" alt=""'
      . ' fetchpriority="high" decoding="async"></picture>';
} elseif ($esSede) {
    [$webp, $jpg] = $anchosPortada('assets/img/rediseno/sede/hero', [480, 768, 1024, 1440, 1920, 2560]);

    $portadaRespaldo = '<picture>'
      . '<source type="image/webp" sizes="100vw" srcset="' . $webp . '">'
      . '<img src="' . $esc($sitio->asset('assets/img/rediseno/sede/hero-1440.jpg')) . '"'
      . ' sizes="100vw" srcset="' . $jpg . '"'
      . ' width="2880" height="1164" alt=""'
      . ' fetchpriority="high" decoding="async"></picture>';
}

$conPortada = !empty($pieza['imagen_ruta']) || $portadaRespaldo !== '';

/* ── El cuerpo del titular ────────────────────────────────────────────────
   Los 114 px del editable son para «Callao». Un titular de noticia a ese
   cuerpo ocuparía media página, así que se elige por longitud; el CSS no sabe
   contar caracteres y esto es lo que cuesta: cuatro tramos. */
$largoTitulo = mb_strlen($titulo);
$claseTitulo = $largoTitulo <= 14 ? ''
    : ($largoTitulo <= 30 ? ' hero--sede--medio'
    : ($largoTitulo <= 64 ? ' hero--sede--largo' : ' hero--sede--xl'));

/* ── La bajada del héroe ──────────────────────────────────────────────────
   El editable escribe una sola línea con tres voces: el tipo de jurisdicción
   en amarillo cursiva, un inciso en negrita y el resto en fina. Aquí se
   componen con lo que traiga la ficha —el rótulo, el dato propio de su
   colección (los años de un santo o la fecha y la fuente de una noticia) y el
   resumen—, y se pinta sólo lo que exista. */
$partesBajada = [];

if ($rotulo !== '') {
    $partesBajada[] = ['html' => '<em>' . $esc($rotulo) . '</em>', 'plano' => $rotulo];
}

if ($anios !== '') {
    $partesBajada[] = ['html' => '<strong>' . $esc($anios) . '</strong>', 'plano' => $anios];
} elseif ($fecha !== '') {
    $partesBajada[] = [
        'html'  => '<strong><time>' . $esc($fecha) . '</time>'
                 . ($fuente !== '' ? ' · ' . $esc($fuente) : '') . '</strong>',
        'plano' => $fuente !== '' ? $fuente : $fecha,
    ];
}

if ($resumen !== '') {
    $partesBajada[] = ['html' => $esc($resumen), 'plano' => $resumen];
}

$bajada = '';
$previo = '';

foreach ($partesBajada as $parte) {
    if ($bajada !== '') {
        // Si el trozo anterior ya termina en signo, no se le añade un punto.
        $bajada .= preg_match('/[.!?…·:]$/u', $previo) === 1 ? ' ' : '. ';
    }

    $bajada .= $parte['html'];
    $previo  = $parte['plano'];
}

/* ── La inicial de una ficha hermana sin fotografía ───────────────────────
   El editable pone «FOTO» dentro del recuadro negro: es un marcador de
   maqueta, no algo que publicar. En su lugar va la inicial de la ficha, como
   el medallón de los santos de la portada.

   Se salta el tratamiento o el artículo del principio: cinco santos que
   empiezan todos por «S» —o trece obispos por «Mons.»— no distinguen nada. */
$inicialDe = static function (string $nombre): string {
    $saltar   = ['mons.', 'mons', 'san', 'santa', 'santo', 'sta.', 'sto.', 'el', 'la', 'los', 'las'];
    $palabras = preg_split('/\s+/u', trim($nombre)) ?: [];

    foreach ($palabras as $palabra) {
        if ($palabra === '' || in_array(mb_strtolower($palabra), $saltar, true)) {
            continue;
        }

        return mb_strtoupper(mb_substr($palabra, 0, 1));
    }

    return mb_strtoupper(mb_substr(trim($nombre), 0, 1));
};

/* ── La vuelta al listado ─────────────────────────────────────────────────
   «Volver a sedes», «Volver a los obispos del Perú». Se baja SÓLO la primera
   letra del nombre de la página: pasarlo entero a minúsculas dejaba «volver a
   los obispos del perú». */
$nombreVuelta = $nombrePadre !== '' ? $nombrePadre : 'el listado';
$nombreVuelta = mb_strtolower(mb_substr($nombreVuelta, 0, 1)) . mb_substr($nombreVuelta, 1);

/* ── El enlace propio de la pieza ─────────────────────────────────────────
   La publicación original en la CEP, por ejemplo. Dos cautelas:

   · No se pinta si apunta a ESTA misma página. En las sedes, «enlace_url» es
     lo que usa el listado para venir hasta aquí —«sedes/lima/»—, y un botón
     que lleva a la página en la que ya estás no es un botón.
   · Las direcciones internas pasan por $sitio->enlace(). Escritas a pelo eran
     relativas, y desde /sedes/lima/ «sedes/lima/» resolvía a
     /sedes/lima/sedes/lima/: un enlace roto. */
$enlaceUrl    = $limpio($pieza['enlace_url'] ?? '');
$enlaceTexto  = $limpio($pieza['enlace_texto'] ?? '');
$rutaPropia   = trim($rutaPadre . (string) $pieza['slug'] . '/', '/');
$apuntaAquiMismo = trim(ltrim($enlaceUrl, '/'), '/') === $rutaPropia;
$esEnlaceFuera   = preg_match('#^(https?:|mailto:|tel:)#i', $enlaceUrl) === 1;
$pintarEnlace    = $enlaceUrl !== '' && $enlaceUrl !== '#' && !$apuntaAquiMismo;
$hrefEnlace      = $esEnlaceFuera ? $enlaceUrl : $sitio->enlace(ltrim($enlaceUrl, '/'));
?>
<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       La portada de la ficha se edita en el panel, dentro de su colección:
       Páginas → Sedes → Las cuatro sedes → Lima. */ ?>
  <section class="hero hero--page hero--sede<?= $claseTitulo ?><?= $conPortada ? '' : ' hero--sede--liso' ?>">
    <?php if ($conPortada): ?>
      <div class="hero__media">
        <?= $sitio->imagen($pieza, $portadaRespaldo, ['sizes' => '100vw', 'prioridad' => true]) ?>
      </div>
    <?php endif; ?>

    <div class="hero__inner sede-heroe__in">
      <h1 class="hero__title sede-heroe__h"><?= $esc($titulo) ?></h1>

      <?php if ($bajada !== ''): ?>
        <p class="hero__sub sede-heroe__sub"><?= $bajada ?></p>
      <?php endif; ?>

      <nav class="migas sede-heroe__migas" aria-label="Migas de pan">
        <ol>
          <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
          <?php if ($nombrePadre !== ''): ?>
            <li><a href="<?= $esc($sitio->enlace($rutaPadre)) ?>"><?= $esc($nombrePadre) ?></a></li>
          <?php endif; ?>
          <li><span aria-current="page"><?= $esc($titulo) ?></span></li>
        </ol>
      </nav>
    </div>
  </section>

  <div class="sede-cuerpo">

    <?php /* ═══════════════════════════════════════════ EL TEXTO DE LA FICHA ══
         El filete granate del editable abre el bloque. El titular no se
         repite en granate porque ya está, enorme, en el héroe: aquí sólo
         queda para los lectores de pantalla, que llegan a esta sección sin
         haber visto nada. */ ?>
    <section class="sede-bloque sede-bloque--ficha" aria-labelledby="t-ficha">
      <div class="sede-in">
        <p class="sede-bloque__filete" aria-hidden="true"></p>
        <h2 class="visually-hidden" id="t-ficha"><?= $esc($titulo) ?></h2>

        <?php if ($cuerpo !== ''): ?>
          <div class="sede-texto"><?= $rico($cuerpo) ?></div>
        <?php else: ?>
          <p class="sede-vacio">El contenido de esta página se publicará en los próximos días.</p>
        <?php endif; ?>

        <div class="sede-acciones">
          <?php if ($pintarEnlace): ?>
            <a class="btn" href="<?= $esc($hrefEnlace) ?>"
               <?= $esEnlaceFuera && preg_match('#^https?://#i', $enlaceUrl) === 1 ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
              <?= $esc($enlaceTexto !== '' ? $enlaceTexto : 'Leer más') ?>
            </a>
          <?php endif; ?>

          <a class="sede-volver" href="<?= $esc($sitio->enlace($rutaPadre)) ?>">
            Volver a <?= $esc($nombreVuelta) ?>
          </a>
        </div>
      </div>
    </section>

    <?php /* ═══════════════════════════════════════════ LAS FICHAS HERMANAS ══
         El carrusel de tarjetas blancas del editable. Cada tarjeta lleva su
         fotografía —o su inicial sobre el recuadro negro, si no la tiene—, el
         titular y la línea que la describe. En pantallas pequeñas la hoja lo
         convierte en rejilla para que no quede ninguna cortada. */ ?>
    <?php if ($hermanas !== []): ?>
      <section class="sede-bloque sede-bloque--hermanas" aria-labelledby="t-hermanas">
        <div class="sede-in">
          <p class="sede-bloque__filete" aria-hidden="true"></p>
          <div class="sede-bloque__cab">
            <h2 class="sede-bloque__h" id="t-hermanas">Sigue leyendo</h2>
          </div>
        </div>

        <div class="sede-carrusel" tabindex="0" role="region"
             aria-label="Más fichas de <?= $esc($nombrePadre !== '' ? $nombrePadre : 'esta sección') ?>">
          <ul class="sede-carrusel__pista">
            <?php foreach ($hermanas as $hermana): ?>
              <?php
              $tituloHermana = $limpio($hermana['titulo'] ?? '');
              $rotuloHermana = $limpio($hermana['rotulo'] ?? '');
              $inicial       = $inicialDe($tituloHermana);
              ?>
              <li class="sede-nota">
                <p class="sede-nota__foto">
                  <?php
                  $respaldoFoto = '<span class="sede-nota__inicial" aria-hidden="true">'
                                . $esc($inicial) . '</span>';
                  ?>
                  <?= $sitio->imagen($hermana, $respaldoFoto, ['sizes' => '(min-width:1024px) 18vw, 45vw']) ?>
                </p>

                <h3 class="sede-nota__tit">
                  <a href="<?= $esc($sitio->enlace($rutaPadre . (string) ($hermana['slug'] ?? '') . '/')) ?>">
                    <?= $esc($tituloHermana) ?>
                  </a>
                </h3>

                <?php if ($rotuloHermana !== ''): ?>
                  <p class="sede-nota__txt"><?= $esc($rotuloHermana) ?></p>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    <?php endif; ?>

  </div>

</main>
