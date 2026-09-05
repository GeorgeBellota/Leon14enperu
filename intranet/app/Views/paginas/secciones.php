<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var array $pagina
 * @var array $secciones
 * @var array $fichas      piezas con página propia de esta página
 * @var array $plantillas
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
?>

<header class="encabezado">
  <p class="rotulo"><a href="<?= $url('/paginas') ?>">Páginas</a></p>
  <h1><?= $e($pagina['nombre']) ?></h1>
  <p class="encabezado__pie">
    <a class="enlace-flecha" href="<?= $e($c->urlSitio($pagina['ruta'])) ?>" target="_blank" rel="noopener">Ver la página en la web ↗</a>
  </p>
</header>

<?php if ($secciones === []): ?>
  <section class="tarjeta">
    <p class="vacio">Esta página todavía no tiene secciones administrables.</p>
  </section>
<?php else: ?>

  <div class="secciones-lista">
    <?php foreach ($secciones as $s): ?>
      <?php $plantilla = $plantillas[$s['plantilla']] ?? $plantillas['generica']; ?>
      <article class="seccion-fila<?= $s['activa'] ? '' : ' seccion-fila--apagada' ?>">
        <div class="seccion-fila__cuerpo">
          <p class="seccion-fila__meta">
            <span class="pildora"><?= $e($plantilla['nombre']) ?></span>
            <?php if (!$s['activa']): ?><span class="pildora pildora--baja">Oculta en la web</span><?php endif; ?>
          </p>
          <h2 class="seccion-fila__titulo"><?= $e($s['nombre']) ?></h2>
          <?php if ($s['titulo']): ?>
            <p class="seccion-fila__texto"><?= $e(View::recortar($s['titulo'], 90)) ?></p>
          <?php endif; ?>
          <p class="seccion-fila__pie">
            <?php if ((int) $s['bloques'] > 0): ?>
              <?= (int) $s['bloques'] ?> <?= (int) $s['bloques'] === 1 ? 'bloque' : 'bloques' ?> ·
            <?php endif; ?>
            <?php if ($s['editor']): ?>
              Editada por <?= $e($s['editor']) ?>, <?= $e(View::fecha($s['actualizado_en'], true)) ?>
            <?php else: ?>
              Sin cambios desde la instalación
            <?php endif; ?>
          </p>
        </div>
        <div class="seccion-fila__accion">
          <?php if ($auth->puede('paginas.editar')): ?>
            <a class="btn btn--linea" href="<?= $url('/paginas/' . $pagina['clave'] . '/' . $s['clave']) ?>">Editar</a>
          <?php endif; ?>
          <a class="enlace-flecha" href="<?= $e($c->urlSitio($pagina['ruta'])) ?>#<?= $e($s['clave']) ?>" target="_blank" rel="noopener">Ver ↗</a>
        </div>
      </article>

      <?php /* Las fichas de esta sección, si sus piezas tienen página propia.
               Van FUERA de la fila y colgando de ella: son el segundo nivel,
               y hasta ahora no se veían en ninguna pantalla —había que abrir
               el formulario de la sección y bajar hasta encontrarlas—. */ ?>
      <?php $suyas = array_values(array_filter($fichas, static fn (array $f): bool => $f['seccion'] === $s['clave'])); ?>
      <?php if ($suyas !== []): ?>
        <ul class="fichas__lista fichas--sueltas">
          <?php foreach ($suyas as $f): ?>
            <?php $ver = rtrim((string) $pagina['ruta'], '/') . '/' . $f['slug'] . '/'; ?>
            <li class="ficha<?= (int) $f['activo'] === 1 ? '' : ' ficha--apagada' ?>">
              <?php /* Mismo marcado que en la lista de páginas: el nombre en su
                       propio span —para que tenga peso y sea lo que se subraya
                       al pasar— y la dirección empujada al final de la fila. */ ?>
              <a class="ficha__principal" href="<?= $url('/paginas/' . $pagina['clave'] . '/' . $s['clave'] . '#pieza-' . $f['slug']) ?>">
                <span class="ficha__nombre"><?= $e($f['titulo']) ?></span>
                <?php if (($f['rotulo'] ?? '') !== ''): ?>
                  <span class="ficha__rotulo"><?= $e(View::recortar((string) $f['rotulo'], 56)) ?></span>
                <?php endif; ?>
                <span class="ficha__ruta tabla__mono"><?= $e($ver) ?></span>
              </a>
              <span class="ficha__mandos">
                <a class="btn btn--plano" href="<?= $e($c->urlSitio($ver)) ?>" target="_blank" rel="noopener"
                   aria-label="Ver «<?= $e($f['titulo']) ?>» en la web">Ver ↗</a>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

<?php endif; ?>
