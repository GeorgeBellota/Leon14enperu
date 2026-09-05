<?php
/**
 * Biblioteca de imágenes: subir, describir y borrar.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $listado
 * @var array $filtros
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/** La dirección pública de una imagen, para la miniatura. */
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
        : round($b / 1024 / 1024, 1) . ' MB';
};

/** Los anchos generados, para que se vea que no es un solo archivo. */
$tamanos = static function (array $m): string {
    $v = \Intranet\Models\Medio::decodificar($m['variantes'] ?? null);
    $anchos = $v['anchos'] ?? [];

    if ($anchos === []) {
        return 'vector';
    }

    return implode(' · ', array_map(static fn ($a) => $a . 'px', $anchos));
};

/** La URL de esta pantalla conservando la búsqueda. */
$conFiltros = static function (array $cambios) use ($c, $filtros): string {
    $q = array_filter(array_merge(['buscar' => $filtros['buscar'] ?? ''], $cambios), static fn ($v) => $v !== '' && $v !== null);

    return View::e($c->url('/medios' . ($q === [] ? '' : '?' . http_build_query($q))));
};
?>

<header class="encabezado">
  <p class="rotulo">Contenidos</p>
  <h1>Imágenes</h1>
  <p class="encabezado__pie">
    Las fotos que se pueden elegir desde cualquier página. Se sube una sola vez
    y sirve para todas: si cambias la descripción, cambia en todos los sitios donde
    aparece.
  </p>
</header>

<?php if ($puedeEditar): ?>
  <?php /* ── Subir ────────────────────────────────────────────────────────────
           Se sube UNA imagen, la más grande que se tenga. El servidor genera
           los tamaños pequeños y la versión WebP: no hay que preparar nada
           antes ni subir la misma foto tres veces. */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Subir una imagen</h2>

    <form method="post" action="<?= $url('/medios') ?>" enctype="multipart/form-data">
      <?= $csrf->campo() ?>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="imagen">Archivo</label>
        <input type="file" id="imagen" name="imagen" required
               accept="image/jpeg,image/png,image/webp,image/svg+xml">
        <p class="campo__ayuda">
          Sube la versión más grande que tengas, en JPG, PNG, WEBP o SVG.
          El servidor genera solo los tamaños de <?= implode(', ', \Intranet\Core\Imagen::ANCHOS) ?> px
          y su versión WebP, que es lo que hace que la web cargue rápido en el móvil.
          Máximo 20 MB.
        </p>
      </div>

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
          Márcala sólo si la imagen es un adorno —una textura, un filete— y
          describirla sería ruido. En ese caso no hace falta descripción.
        </p>
      </div>

      <p><button class="btn btn--primario" type="submit">Subir imagen</button></p>
    </form>
  </section>
<?php endif; ?>

<?php /* ── La biblioteca ──────────────────────────────────────────────────── */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Biblioteca</h2>
    <span class="tarjeta__nota"><?= (int) $listado['total'] ?> imagen<?= (int) $listado['total'] === 1 ? '' : 'es' ?></span>
  </header>

  <form method="get" action="<?= $url('/medios') ?>" class="filtros sep-m">
    <div class="campo">
      <label class="campo__etiqueta" for="buscar">Buscar</label>
      <input type="search" id="buscar" name="buscar" value="<?= $e($filtros['buscar'] ?? '') ?>"
             placeholder="Nombre o descripción">
    </div>
    <p><button class="btn" type="submit">Buscar</button>
      <?php if (($filtros['buscar'] ?? '') !== ''): ?>
        <a class="btn btn--plano" href="<?= $url('/medios') ?>">Quitar el filtro</a>
      <?php endif; ?>
    </p>
  </form>

  <?php if ($listado['filas'] === []): ?>
    <p class="vacio">
      <?= ($filtros['buscar'] ?? '') !== ''
            ? 'Ninguna imagen coincide con esa búsqueda.'
            : 'Todavía no hay ninguna imagen subida. Sube la primera desde el formulario de arriba.' ?>
    </p>
  <?php else: ?>
    <ul class="medios">
      <?php foreach ($listado['filas'] as $m): ?>
        <li class="medio">
          <figure class="medio__vista">
            <img src="<?= $src($m) ?>" alt="<?= $e($m['alt'] ?? '') ?>" loading="lazy" decoding="async">
          </figure>

          <div class="medio__cuerpo">
            <p class="medio__nombre"><?= $e($m['nombre_archivo']) ?></p>
            <p class="medio__meta">
              <?= (int) $m['ancho'] ?>×<?= (int) $m['alto'] ?> px · <?= $e($peso($m['peso'])) ?>
              · <?= $e($tamanos($m)) ?>
            </p>

            <?php if ($puedeEditar): ?>
              <form method="post" action="<?= $url('/medios/' . (int) $m['id']) ?>" class="medio__form">
                <?= $csrf->campo() ?>
                <label class="campo__etiqueta" for="alt-<?= (int) $m['id'] ?>">Descripción</label>
                <input type="text" id="alt-<?= (int) $m['id'] ?>" name="alt" maxlength="255"
                       value="<?= $e($m['alt'] ?? '') ?>"
                       <?= (int) $m['decorativa'] === 1 ? 'disabled' : '' ?>>
                <label class="medio__casilla">
                  <input type="checkbox" name="decorativa" value="1"
                         <?= (int) $m['decorativa'] === 1 ? 'checked' : '' ?>>
                  Decorativa
                </label>
                <button class="btn btn--plano" type="submit">Guardar</button>
              </form>

              <?php /* data-confirmar y no onsubmit: la CSP del panel no
                       permite JavaScript incrustado en los atributos. */ ?>
              <form method="post" action="<?= $url('/medios/' . (int) $m['id'] . '/borrar') ?>"
                    data-confirmar="¿Borrar esta imagen? Se borran también todos sus tamaños.">
                <?= $csrf->campo() ?>
                <button class="btn btn--peligro btn--plano" type="submit">Borrar</button>
              </form>
            <?php else: ?>
              <p class="medio__meta"><?= $e($m['alt'] ?: 'Decorativa') ?></p>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ((int) $listado['paginas'] > 1): ?>
      <nav class="paginacion" aria-label="Páginas de la biblioteca">
        <span>Página <?= (int) $listado['pagina'] ?> de <?= (int) $listado['paginas'] ?></span>
        <span class="paginacion__paginas">
          <?php if ((int) $listado['pagina'] > 1): ?>
            <a href="<?= $conFiltros(['pagina' => (int) $listado['pagina'] - 1]) ?>" rel="prev">Anterior</a>
          <?php endif; ?>
          <?php if ((int) $listado['pagina'] < (int) $listado['paginas']): ?>
            <a href="<?= $conFiltros(['pagina' => (int) $listado['pagina'] + 1]) ?>" rel="next">Siguiente</a>
          <?php endif; ?>
        </span>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
