<?php
/**
 * Vista genérica de una página de colección.
 *
 * La usan las páginas cuyo contenido entero vive en el CMS y no tienen
 * maquetación propia: Tierra de santos, los obispos, las comisiones,
 * Participa y Multimedia.
 *
 * No hay un archivo por página: se pinta lo que traiga la base, sección a
 * sección, según la plantilla de cada una. Añadir una página de este tipo es
 * una línea en Publico\Rutas y unas filas en la base.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable                $esc
 * @var array                   $destino
 */

declare(strict_types=1);

use Intranet\Publico\Sitio;

$paginaCms = $sitio->contenido($destino['clave']);
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string => Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s): array                            => Sitio::bloques($secciones, $s);
$hay     = static fn (string $s): bool                             => Sitio::activa($secciones, $s);

/* El HTML con formato del panel se filtra: editar textos no puede dar el
   poder de ejecutar código en el navegador de un visitante. */
$rico = static fn (?string $v): string => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

$titulo = $campo('cabecera', 'titulo', (string) ($paginaCms['nombre'] ?? 'León XIV en el Perú'));
$bajada = $campo('cabecera', 'texto', '');

$meta = [
    'titulo'      => $titulo . ' · Viaje de León XIV al Perú',
    'descripcion' => mb_substr($bajada, 0, 180),
    'ruta'        => ltrim((string) ($paginaCms['ruta'] ?? ''), '/'),
];
?>
<main id="contenido">

<?php /* ── La portada de esta colección ────────────────────────────────
         Sale del panel: Páginas → esta página → Cabecera, con una foto para
         escritorio y otra para móvil. Sin foto queda la banda roja lisa.

         La clase «--lisa» se quita cuando hay imagen porque lleva
         min-height:0: con una fotografía dentro, la franja se colapsaría. */ ?>
<?php $portada = $secciones['cabecera'] ?? []; ?>
<header class="cabecera-pagina<?= empty($portada['imagen_ruta']) ? ' cabecera-pagina--lisa' : '' ?>">
  <?php if (!empty($portada['imagen_ruta'])): ?>
    <div class="cabecera-pagina__media">
      <?= $sitio->imagen($portada, '', ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>
  <?php endif; ?>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <?php if ($campo('cabecera', 'rotulo') !== ''): ?>
        <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo')) ?></span>
      <?php endif; ?>
      <h1 class="cabecera-pagina__titulo"><?= $esc($titulo) ?></h1>
      <?php if ($bajada !== ''): ?>
        <p class="cabecera-pagina__bajada"><?= $esc($bajada) ?></p>
      <?php endif; ?>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
          <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
          <li><span aria-current="page"><?= $esc($titulo) ?></span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<?php foreach ($secciones as $clave => $s): ?>
  <?php if ($clave === 'cabecera') { continue; } ?>

  <section class="seccion" id="<?= $esc($clave) ?>" aria-labelledby="t-<?= $esc($clave) ?>">
    <div class="contenedor">
      <header class="seccion__encabezado seccion__encabezado--mayor">
        <hr class="seccion__filete" data-reveal="line-draw">
        <?php if (($s['rotulo'] ?? '') !== ''): ?>
          <span class="rotulo"><?= $esc($s['rotulo']) ?></span>
        <?php endif; ?>
        <h2 class="titular--mayor" id="t-<?= $esc($clave) ?>" data-reveal="mask-lines">
          <span class="linea"><span><?= $esc($s['titulo'] ?? '') ?></span></span>
        </h2>
      </header>

      <?php if (($s['texto'] ?? '') !== ''): ?>
        <div class="texto-lectura"><?= $rico($s['texto']) ?></div>
      <?php endif; ?>

      <?php $piezas = $bloques($clave); ?>
      <?php if ($piezas !== []): ?>
        <?php /* Cada pieza es una tarjeta. Si tiene slug, además es un enlace
                 a su propia página; si no, se queda como tarjeta muda. */ ?>
        <ul class="rejilla-fichas sep-l">
          <?php foreach ($piezas as $b): ?>
            <?php
            $destinoPieza = ($b['slug'] ?? '') !== ''
                ? $sitio->enlace(ltrim((string) $paginaCms['ruta'], '/') . $b['slug'] . '/')
                : (string) ($b['enlace_url'] ?? '');
            $resumen = $b['datos']['resumen'] ?? $b['texto'] ?? '';
            ?>
            <li class="ficha" data-reveal="fade-rise">
              <?php if (!empty($b['imagen_ruta'])): ?>
                <div class="ficha__media">
                  <?= $sitio->imagen($b, '', ['sizes' => '(min-width:768px) 33vw, 100vw']) ?>
                </div>
              <?php endif; ?>
              <div class="ficha__cuerpo">
                <?php if (($b['rotulo'] ?? '') !== ''): ?>
                  <span class="ficha__rotulo"><?= $esc($b['rotulo']) ?></span>
                <?php endif; ?>
                <h3 class="ficha__titulo"><?= $esc($b['titulo'] ?? '') ?></h3>
                <?php if ($resumen !== ''): ?>
                  <p class="ficha__texto"><?= $esc(mb_substr(strip_tags((string) $resumen), 0, 190)) ?></p>
                <?php endif; ?>
                <?php if ($destinoPieza !== '' && $destinoPieza !== '#'): ?>
                  <a class="enlace-flecha" href="<?= $esc($destinoPieza) ?>">
                    <?= $esc(($b['enlace_texto'] ?? '') !== '' ? $b['enlace_texto'] : 'Conoce más') ?>
                  </a>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php if ($secciones === []): ?>
  <section class="seccion">
    <div class="contenedor">
      <p class="vacio">Esta página todavía no tiene contenido publicado.</p>
    </div>
  </section>
<?php endif; ?>

</main>
