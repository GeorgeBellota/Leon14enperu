<?php
/**
 * La página propia de una pieza: una sede, un santo, un obispo, una comisión,
 * una noticia o una nota de prensa.
 *
 * Una sola vista para las seis colecciones. Lo que cambia entre ellas es el
 * contenido, no la forma: un titular, una fotografía, un texto y el camino de
 * vuelta al listado.
 *
 * Llega aquí por el slug. Ver index.php y Models\Pagina::piezaPorSlug().
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable                $esc
 * @var array                   $pieza
 * @var array                   $padre
 */

declare(strict_types=1);

$rico = static fn (?string $v): string => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

$titulo    = (string) ($pieza['titulo'] ?? '');
$rotulo    = (string) ($pieza['rotulo'] ?? '');
$resumen   = (string) ($pieza['datos']['resumen'] ?? '');
$anios     = (string) ($pieza['datos']['anios'] ?? '');
$fecha     = (string) ($pieza['datos']['fecha'] ?? '');
$fuente    = (string) ($pieza['datos']['fuente'] ?? '');
$rutaPadre = ltrim((string) ($padre['ruta'] ?? '/'), '/');

/* Para el resumen de buscadores y redes: el resumen propio si lo hay, y si no
   las primeras líneas del texto, ya sin etiquetas. */
$descripcion = $resumen !== ''
    ? $resumen
    : mb_substr(trim(strip_tags((string) ($pieza['texto'] ?? ''))), 0, 180);

$meta = [
    'titulo'      => $titulo . ' · ' . ($padre['nombre'] ?? 'León XIV en el Perú'),
    'descripcion' => $descripcion,
    'ruta'        => $rutaPadre . $pieza['slug'] . '/',
    'og_tipo'     => 'article',
];

/* La imagen de la pieza es también la que se ve al compartir el enlace. */
if (!empty($pieza['imagen_ruta'])) {
    $meta['og_imagen'] = (string) $pieza['imagen_ruta'];
}

/* Las hermanas, para el pie. Si la base falla no se pinta ese bloque y ya. */
$hermanas = [];
try {
    $hermanas = (new \Intranet\Models\Pagina($sitio->contenedor()))
        ->piezasHermanas((int) $pieza['seccion_id'], (int) $pieza['id'], 3);
} catch (Throwable $e) {
    error_log('[detalle] no se pudieron cargar las piezas hermanas: ' . $e->getMessage());
}
?>
<main id="contenido">

<?php /* La portada de la pieza. Se edita en el panel, dentro de su
         colección: Páginas → Sedes → Las cuatro sedes → Chiclayo. */ ?>
<header class="cabecera-pagina<?= empty($pieza['imagen_ruta']) ? ' cabecera-pagina--lisa' : '' ?>">
  <?php if (!empty($pieza['imagen_ruta'])): ?>
    <div class="cabecera-pagina__media">
      <?= $sitio->imagen($pieza, '', ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>
  <?php endif; ?>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <?php if ($rotulo !== ''): ?>
        <span class="rotulo rotulo--claro"><?= $esc($rotulo) ?></span>
      <?php endif; ?>

      <h1 class="cabecera-pagina__titulo"><?= $esc($titulo) ?></h1>

      <?php if ($anios !== ''): ?>
        <p class="cabecera-pagina__dato"><?= $esc($anios) ?></p>
      <?php endif; ?>

      <?php if ($fecha !== ''): ?>
        <p class="cabecera-pagina__dato">
          <time><?= $esc($fecha) ?></time><?php if ($fuente !== ''): ?> · <?= $esc($fuente) ?><?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if ($resumen !== ''): ?>
        <p class="cabecera-pagina__bajada"><?= $esc($resumen) ?></p>
      <?php endif; ?>

      <nav class="migas" aria-label="Migas de pan">
        <ol>
          <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
          <li><a href="<?= $esc($sitio->enlace($rutaPadre)) ?>"><?= $esc($padre['nombre'] ?? '') ?></a></li>
          <li><span aria-current="page"><?= $esc($titulo) ?></span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-detalle">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">

    <?php /* La fotografía ya no va aquí: es la portada de la página, arriba. */ ?>

    <h2 class="solo-lectores" id="t-detalle"><?= $esc($titulo) ?></h2>

    <div class="texto-lectura">
      <?php if (($pieza['texto'] ?? '') !== ''): ?>
        <?= $rico($pieza['texto']) ?>
      <?php else: ?>
        <p class="vacio">El contenido de esta página se publicará en los próximos días.</p>
      <?php endif; ?>
    </div>

    <?php /* Un enlace externo propio de la pieza: la publicación original en
             la CEP, por ejemplo. Sólo se pinta si el editor lo rellenó. */ ?>
    <?php if (($pieza['enlace_url'] ?? '') !== '' && $pieza['enlace_url'] !== '#'): ?>
      <p class="sep-l">
        <a class="btn btn--primario" href="<?= $esc($pieza['enlace_url']) ?>"
           <?= preg_match('#^https?://#i', (string) $pieza['enlace_url']) ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
          <?= $esc(($pieza['enlace_texto'] ?? '') !== '' ? $pieza['enlace_texto'] : 'Leer más') ?>
        </a>
      </p>
    <?php endif; ?>

    <p class="sep-l">
      <a class="enlace-flecha" href="<?= $esc($sitio->enlace($rutaPadre)) ?>">
        Volver a <?= $esc(mb_strtolower((string) ($padre['nombre'] ?? 'el listado'))) ?>
      </a>
    </p>

  </div></div></div>
</section>

<?php if ($hermanas !== []): ?>
  <section class="seccion seccion--tinte" aria-labelledby="t-hermanas">
    <div class="contenedor">
      <header class="seccion__encabezado">
        <hr class="seccion__filete" data-reveal="line-draw">
        <h2 id="t-hermanas" data-reveal="mask-lines">
          <span class="linea"><span>Sigue leyendo</span></span>
        </h2>
      </header>

      <ul class="rejilla-fichas">
        <?php foreach ($hermanas as $h): ?>
          <li class="ficha" data-reveal="fade-rise">
            <?php if (!empty($h['imagen_ruta'])): ?>
              <div class="ficha__media">
                <?= $sitio->imagen($h, '', ['sizes' => '(min-width:768px) 33vw, 100vw']) ?>
              </div>
            <?php endif; ?>
            <div class="ficha__cuerpo">
              <?php if (($h['rotulo'] ?? '') !== ''): ?>
                <span class="ficha__rotulo"><?= $esc($h['rotulo']) ?></span>
              <?php endif; ?>
              <h3 class="ficha__titulo">
                <a href="<?= $esc($sitio->enlace($rutaPadre . $h['slug'] . '/')) ?>"><?= $esc($h['titulo']) ?></a>
              </h3>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endif; ?>

</main>
