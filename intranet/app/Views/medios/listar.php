<?php
/**
 * Biblioteca de imágenes.
 *
 * Rejilla de miniaturas, como la de WordPress: con casi cien imágenes, una
 * lista de filas obliga a desplazarse mucho para reconocer una foto, y a una
 * foto se la reconoce mirándola, no leyendo «img-6a810094».
 *
 * Al pulsar una miniatura se abre su ficha, que es donde está todo: el texto
 * alternativo, las medidas, la dirección para copiar, DÓNDE SE USA y el
 * borrado. La ficha se pide por detrás para no sacar a nadie del sitio donde
 * estaba desplazándose.
 *
 * Sin JavaScript la rejilla sigue viéndose y cada imagen lleva su enlace a la
 * ficha en JSON; lo que se pierde es la comodidad, no el acceso.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $listado
 * @var array $filtros
 * @var array $recuentos
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
$src = static fn (array $m): string => View::e($c->urlSitio('/' . ltrim((string) $m['ruta'], '/')));

$puedeEditar = $auth->puede('medios.subir');

/** El peso en algo legible: «248 KB» dice más que «253 952». */
$peso = static function (mixed $bytes): string {
    $b = (int) $bytes;

    if ($b <= 0) {
        return '—';
    }

    return $b < 1024 * 1024
        ? round($b / 1024) . ' KB'
        : round($b / 1048576, 1) . ' MB';
};

/** Conserva los filtros al cambiar de página o de pestaña. */
$conFiltros = static function (array $cambios) use ($filtros): string {
    $q = array_filter([
        'buscar' => $filtros['buscar'] ?? '',
        'estado' => $filtros['estado'] ?? '',
    ] + $cambios, static fn ($v): bool => (string) $v !== '');

    return $q === [] ? '' : '?' . http_build_query($q);
};

$pestanas = [
    ''             => ['Todas',            (int) $recuentos['total']],
    'sin-uso'      => ['Sin usar',         (int) $recuentos['sin_uso']],
    'sin-alt'      => ['Sin descripción',  (int) $recuentos['sin_alt']],
    'con-original' => ['Con original',     (int) $recuentos['con_original']],
];
?>

<header class="encabezado">
  <h1>Imágenes</h1>
  <p class="encabezado__pie">
    El almacén que comparten todas las páginas. Una misma imagen puede estar en
    el carrusel, en Prensa y en la galería de Multimedia <strong>sin ocupar el
    doble de disco</strong>: lo que se guarda aquí es el archivo; lo demás sólo
    lo señala.
  </p>
</header>

<?php if ($puedeEditar): ?>
  <?php /* ── Subir ──────────────────────────────────────────────────────────
           Se arrastra o se elige. El servidor genera los tamaños y la versión
           WebP, y desde octubre de 2026 CONSERVA el original: es lo que se
           entrega cuando alguien descarga una foto desde Multimedia. */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Subir una imagen</h2>

    <form method="post" action="<?= $url('/medios') ?>" enctype="multipart/form-data" data-subida>
      <?= $csrf->campo() ?>

      <div class="soltar" data-soltar>
        <input type="file" id="imagen" name="imagen" required
               accept="image/jpeg,image/png,image/webp,image/svg+xml" data-soltar-campo>
        <p class="soltar__texto" data-soltar-texto>
          Arrastra una imagen aquí, o <span class="soltar__enlace">elígela</span>.
        </p>
      </div>

      <p class="campo__ayuda">
        Sube la versión <strong>más grande que tengas</strong>, en JPG, PNG, WEBP o SVG.
        El servidor genera los tamaños de <?= implode(', ', \Intranet\Core\Imagen::ANCHOS) ?> px
        y su versión WebP —que es lo que hace que la web cargue rápido en el
        móvil— y guarda el original para las descargas. Máximo 20 MB.
      </p>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="alt">Qué se ve en la imagen</label>
        <input type="text" id="alt" name="alt" maxlength="255"
               placeholder="El Papa León XIV saluda a los fieles en la plaza">
        <p class="campo__ayuda">
          Es lo que oye quien navega con lector de pantalla y lo que se lee si la
          imagen no carga. Describe la escena, no repitas el título de la sección.
        </p>
      </div>

      <div class="campo casilla sep-m">
        <label>
          <input type="checkbox" name="decorativa" value="1">
          Es decorativa: no aporta información
        </label>
        <p class="campo__ayuda">
          Sólo si es un adorno —una textura, un filete—. En ese caso no hace falta
          descripción y no aparecerá en «Sin descripción».
        </p>
      </div>

      <p><button class="btn btn--primario" type="submit">Subir imagen</button></p>
    </form>
  </section>
<?php endif; ?>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Biblioteca</h2>
    <span class="tarjeta__nota"><?= (int) $listado['total'] ?> de <?= (int) $recuentos['total'] ?></span>
  </header>

  <?php /* ── Pestañas ─────────────────────────────────────────────────────
           Con el número delante: una pestaña que dice «Sin usar» sin decir
           cuántas obliga a pulsarla para saber si hay algo. */ ?>
  <nav class="pestanas sep-s" aria-label="Filtrar la biblioteca">
    <?php foreach ($pestanas as $clave => [$titulo, $cuantas]): ?>
      <a class="pestana<?= ($filtros['estado'] ?? '') === $clave ? ' is-activa' : '' ?>"
         href="<?= $url('/medios' . $conFiltros(['estado' => $clave, 'pagina' => ''])) ?>">
        <?= $e($titulo) ?><span class="pestana__num"><?= $cuantas ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <form method="get" action="<?= $url('/medios') ?>" class="filtros sep-s">
    <?php if (($filtros['estado'] ?? '') !== ''): ?>
      <input type="hidden" name="estado" value="<?= $e($filtros['estado']) ?>">
    <?php endif; ?>
    <label class="visualmente-oculto" for="buscar">Buscar</label>
    <input type="search" id="buscar" name="buscar" value="<?= $e($filtros['buscar'] ?? '') ?>"
           placeholder="Buscar por nombre o descripción">
    <button class="btn btn--mini" type="submit">Buscar</button>
    <?php if (($filtros['buscar'] ?? '') !== ''): ?>
      <a class="btn btn--mini btn--linea" href="<?= $url('/medios' . $conFiltros(['buscar' => '', 'pagina' => ''])) ?>">Quitar</a>
    <?php endif; ?>
  </form>

  <?php if ($listado['filas'] === []): ?>
    <p class="vacio">
      <?= ($filtros['buscar'] ?? '') !== '' || ($filtros['estado'] ?? '') !== ''
          ? 'Ninguna imagen cumple ese filtro.'
          : 'Todavía no hay ninguna imagen. Sube la primera desde el formulario de arriba.' ?>
    </p>
  <?php else: ?>

    <?php if ($puedeEditar): ?>
      <?php /* ── La barra de selección ──────────────────────────────────────
               Nace oculta y la destapa el JavaScript: sin él no hay casillas
               que marcar, y un botón «Borrar las marcadas» que no puede
               marcar nada sólo confunde. */ ?>
      <form method="post" action="<?= $url('/medios/borrar') ?>" id="lote-medios"
            class="barra-lote" data-lote hidden
            data-confirmar="¿Borrar las imágenes seleccionadas? Las que estén en uso no se borrarán.">
        <?= $csrf->campo() ?>
        <button class="btn btn--mini" type="button" data-marcar-todas>Marcar todas</button>
        <span class="campo__ayuda" data-marcadas>Ninguna seleccionada</span>
        <button class="btn btn--peligro btn--mini" type="submit">Borrar las marcadas</button>
      </form>
    <?php endif; ?>

    <ul class="rejilla-biblioteca" data-biblioteca>
      <?php foreach ($listado['filas'] as $m): ?>
        <li class="pieza-med" data-pieza>
          <?php if ($puedeEditar): ?>
            <label class="pieza-med__marca" hidden data-marca-caja>
              <input type="checkbox" value="<?= (int) $m['id'] ?>" data-marca>
              <span class="visualmente-oculto">Seleccionar <?= $e($m['nombre_archivo']) ?></span>
            </label>
          <?php endif; ?>

          <?php /* Un <a> de verdad: sin JavaScript lleva a la ficha en JSON,
                   que no es bonito pero existe. Con JavaScript se intercepta
                   y se abre la ventana. */ ?>
          <a class="pieza-med__abrir" href="<?= $url('/medios/' . (int) $m['id'] . '/ficha') ?>"
             data-ficha="<?= (int) $m['id'] ?>"
             aria-label="Ver la ficha de <?= $e($m['nombre_archivo']) ?>">
            <img src="<?= $src($m) ?>" alt="" loading="lazy" width="150" height="150">

            <?php /* Las señales que importan de un vistazo, sin abrir nada. */ ?>
            <span class="pieza-med__senas">
              <?php if ((int) $m['decorativa'] === 0 && trim((string) ($m['alt'] ?? '')) === ''): ?>
                <span class="sena sena--aviso" title="Sin descripción para lectores de pantalla">sin descripción</span>
              <?php endif; ?>
              <?php if (($m['original'] ?? null) === null): ?>
                <span class="sena" title="Se subió antes de octubre de 2026: no se conserva el original">sin original</span>
              <?php endif; ?>
            </span>
          </a>

          <p class="pieza-med__nombre" title="<?= $e($m['nombre_archivo']) ?>"><?= $e($m['nombre_archivo']) ?></p>
          <p class="pieza-med__meta"><?= (int) $m['ancho'] ?>×<?= (int) $m['alto'] ?> · <?= $e($peso($m['peso'])) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ((int) $listado['paginas'] > 1): ?>
      <nav class="paginacion sep-m" aria-label="Páginas de la biblioteca">
        <?php for ($p = 1; $p <= (int) $listado['paginas']; $p++): ?>
          <a class="paginacion__num<?= $p === (int) $listado['pagina'] ? ' is-activa' : '' ?>"
             href="<?= $url('/medios' . $conFiltros(['pagina' => $p])) ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php /* ── La ficha ─────────────────────────────────────────────────────────
         Una sola para toda la pantalla; el contenido lo pone el JavaScript.
         <dialog> de verdad: el navegador se encarga del foco, de Escape y de
         dejar el resto fuera del alcance del lector de pantalla. */ ?>
<dialog class="ficha-med" data-ficha-ventana aria-label="Ficha de la imagen">
  <div class="ficha-med__caja">
    <button class="ficha-med__cerrar" type="button" data-cerrar aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>

    <div class="ficha-med__vista">
      <img data-f-img src="" alt="">
    </div>

    <div class="ficha-med__datos">
      <h2 class="ficha-med__nombre" data-f-nombre></h2>
      <p class="ficha-med__meta" data-f-meta></p>

      <?php if ($puedeEditar): ?>
        <form method="post" data-f-form class="sep-s">
          <?= $csrf->campo() ?>

          <div class="campo">
            <label class="campo__etiqueta" for="f-alt">Qué se ve en la imagen</label>
            <input type="text" id="f-alt" name="alt" maxlength="255" data-f-alt>
          </div>

          <div class="campo casilla sep-s">
            <label><input type="checkbox" name="decorativa" value="1" data-f-dec> Es decorativa</label>
          </div>

          <p><button class="btn btn--primario btn--mini" type="submit">Guardar</button></p>
        </form>
      <?php else: ?>
        <p class="campo__ayuda" data-f-alt-solo></p>
      <?php endif; ?>

      <?php /* ── Dónde se usa ────────────────────────────────────────────
               Antes esto sólo aparecía al intentar borrar, y entonces ya era
               tarde: la pregunta «¿puedo cambiar esta foto?» se hace antes. */ ?>
      <div class="ficha-med__usos">
        <h3>Dónde se usa</h3>
        <p data-f-usos-resumen></p>
        <ul data-f-usos-lista></ul>
      </div>

      <div class="campo sep-s">
        <label class="campo__etiqueta" for="f-url">Dirección</label>
        <input type="text" id="f-url" readonly data-f-url>
        <p><button class="btn btn--mini" type="button" data-f-copiar>Copiar</button>
           <span class="campo__ayuda" data-f-copiado hidden>Copiada.</span></p>
      </div>

      <?php if ($puedeEditar): ?>
        <form method="post" data-f-borrar class="sep-m"
              data-confirmar="¿Borrar esta imagen? No se puede deshacer.">
          <?= $csrf->campo() ?>
          <button class="btn btn--peligro btn--mini" type="submit" data-f-borrar-btn>Borrar</button>
          <span class="campo__ayuda" data-f-borrar-nota></span>
        </form>
      <?php endif; ?>
    </div>
  </div>
</dialog>
