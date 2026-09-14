<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $secciones
 * @var array $fichas      piezas con página propia de esta página
 * @var array $plantillas
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/**
 * La primera línea de lo que la sección dice de verdad.
 *
 * El listado enseñaba el nombre de la sección y su titular. Cuando los dos
 * son parecidos —«Después de enviar» y «Y entonces, ¿qué pasa?»— la fila no
 * dice de qué va. El texto sí, y es lo que se reconoce al buscar «la sección
 * donde pone lo del correo».
 *
 * Llega como HTML del editor: se le quitan las etiquetas y se recorta.
 */
$extracto = static function (array $s): string {
    $crudo = trim(strip_tags((string) ($s['texto'] ?? '')));

    if ($crudo === '') {
        return '';
    }

    $limpio = trim(preg_replace('/\s+/u', ' ', html_entity_decode($crudo, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

    return $limpio === '' ? '' : View::recortar($limpio, 120);
};
?>

<header class="encabezado">
  <p class="rotulo"><a href="<?= $url('/paginas') ?>">Páginas</a></p>
  <h1><?= $e($pagina['nombre']) ?></h1>
  <p class="encabezado__pie">
    <a class="enlace-flecha" href="<?= $e($c->urlSitio($pagina['ruta'])) ?>" target="_blank" rel="noopener">Ver la página en la web ↗</a>
    <?php if ($auth->puede('paginas.editar')): ?>
      · <a href="<?= $url('/paginas/' . $pagina['clave'] . '/seo') ?>">Datos para buscadores</a>
    <?php endif; ?>
  </p>
</header>

<?php if ($secciones === []): ?>
  <section class="tarjeta">
    <p class="vacio">Esta página todavía no tiene secciones administrables.</p>
  </section>
<?php else: ?>

  <?php $puedeOrdenar = $auth->puede('paginas.editar'); ?>

  <form method="post" action="<?= $url('/paginas/' . $pagina['clave'] . '/orden') ?>"
        data-ordenar-secciones>
    <?= $csrf->campo() ?>

  <div class="secciones-lista__intro">
    <p>
      <?= count($secciones) ?> secciones, en el mismo orden en que salen en la página.
      <?php if ($puedeOrdenar): ?>
        <span data-con-js hidden>Arrastra para cambiarlo.</span>
      <?php endif; ?>
    </p>
    <?php if ($puedeOrdenar): ?>
      <?php /* Sólo aparece cuando algo se ha movido: un botón de guardar
               siempre visible invita a pulsarlo sin haber cambiado nada. */ ?>
      <button type="submit" class="btn btn--primario" data-guardar-orden hidden>Guardar el orden</button>
    <?php endif; ?>
  </div>

  <div class="secciones-lista" data-lista-secciones>
    <?php foreach ($secciones as $indice => $s): ?>
      <?php
        $plantilla = $plantillas[$s['plantilla']] ?? $plantillas['generica'];
        $resumen   = $extracto($s);
        $titular   = trim((string) ($s['titulo'] ?? ''));
      ?>
      <?php /* La sección y sus fichas viajan juntas. Iban sueltas como
               hermanas, y al reordenar arrastrando las fichas se quedaban
               colgando de la sección equivocada. */ ?>
      <div class="seccion-grupo" data-seccion="<?= $e($s['clave']) ?>">

        <?php if ($puedeOrdenar): ?>
          <input type="hidden" name="orden[]" value="<?= $e($s['clave']) ?>">
        <?php endif; ?>

      <article class="seccion-fila<?= $s['activa'] ? '' : ' seccion-fila--apagada' ?>">

        <?php /* La columna de la izquierda dice dos cosas sin gastar palabras:
                 en qué puesto de la página va la sección, y qué forma tiene.
                 El nombre de la plantilla se queda en el title, para quien lo
                 busque: en la fila ocupaba el sitio de honor y no distinguía
                 nada, porque 47 de las 107 secciones del sitio son «texto de
                 lectura». */ ?>
        <div class="seccion-fila__marca" title="<?= $e($plantilla['nombre']) ?>">
          <span class="seccion-fila__orden" data-numero><?= $indice + 1 ?></span>
          <?php $plantillaEsquema = (string) $s['plantilla']; ?>
          <?php require __DIR__ . '/_esquema.php'; ?>
          <span class="visualmente-oculto"><?= $e($plantilla['nombre']) ?></span>

          <?php if ($puedeOrdenar): ?>
            <?php /* Las flechas son botones de verdad dentro de un formulario
                     de verdad: mueven la sección aunque no haya JavaScript, y
                     son la única forma de reordenar con el teclado. Con
                     JavaScript mueven la fila sin recargar. */ ?>
            <span class="seccion-fila__flechas">
              <button type="submit" name="subir" value="<?= $e($s['clave']) ?>"
                      class="flecha" data-subir-seccion
                      aria-label="Subir «<?= $e($s['nombre']) ?>»"
                      <?= $indice === 0 ? 'disabled' : '' ?>>↑</button>
              <button type="submit" name="bajar" value="<?= $e($s['clave']) ?>"
                      class="flecha" data-bajar-seccion
                      aria-label="Bajar «<?= $e($s['nombre']) ?>»"
                      <?= $indice === count($secciones) - 1 ? 'disabled' : '' ?>>↓</button>
            </span>
          <?php endif; ?>
        </div>

        <div class="seccion-fila__cuerpo">
          <h2 class="seccion-fila__titulo"><?= $e($s['nombre']) ?></h2>

          <?php if ($titular !== ''): ?>
            <p class="seccion-fila__texto"><?= $e(View::recortar($titular, 90)) ?></p>
          <?php endif; ?>

          <?php if ($resumen !== ''): ?>
            <p class="seccion-fila__extracto"><?= $e($resumen) ?></p>
          <?php endif; ?>

          <p class="seccion-fila__pie">
            <?php if (!$s['activa']): ?>
              <span class="pildora pildora--baja">Oculta en la web</span>
            <?php endif; ?>
            <span><?= $e($plantilla['nombre']) ?></span>
            <?php if ((int) $s['bloques'] > 0): ?>
              · <?= (int) $s['bloques'] ?> <?= (int) $s['bloques'] === 1 ? 'bloque' : 'bloques' ?>
            <?php endif; ?>
            <?php if ($s['editor']): ?>
              · Editada por <?= $e($s['editor']) ?>, <?= $e(View::fecha($s['actualizado_en'], true)) ?>
            <?php else: ?>
              · Sin cambios desde la instalación
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
      </div><!-- .seccion-grupo -->
    <?php endforeach; ?>
  </div>

  </form>

<?php endif; ?>
