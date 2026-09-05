<?php
/**
 * Editor de una sección.
 *
 * El formulario se dibuja a partir de la plantilla declarada en
 * Cms\Plantillas: qué campos, qué claves JSON y si admite bloques. Añadir una
 * sección administrable en la Fase 2 no exige tocar esta vista.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $seccion
 * @var array $plantilla
 * @var array $servicios
 */

use Intranet\Cms\Plantillas;
use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/** Un valor de la columna JSON `datos`, ya sea texto o lista de líneas. */
$valorDato = static function (array $fuente, string $clave, string $tipo): string {
    $valor = $fuente['datos'][$clave] ?? ($tipo === 'lista' ? [] : '');

    return $tipo === 'lista' ? implode("\n", (array) $valor) : (string) $valor;
};
?>

<header class="encabezado">
  <p class="rotulo">
    <a href="<?= $url('/paginas') ?>">Páginas</a> ·
    <a href="<?= $url('/paginas/' . $pagina['clave']) ?>"><?= $e($pagina['nombre']) ?></a>
  </p>
  <h1><?= $e($seccion['nombre']) ?></h1>
  <?php if (!empty($plantilla['ayuda'])): ?>
    <p class="encabezado__pie"><?= $e($plantilla['ayuda']) ?></p>
  <?php endif; ?>
</header>

<form method="post" action="<?= $url('/paginas/' . $pagina['clave'] . '/' . $seccion['clave']) ?>"
      class="formulario-cms" data-avisar-cambios>
  <?= $csrf->campo() ?>

  <!-- ── Campos propios de la sección ── -->
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Textos de la sección</h2>
      <label class="interruptor">
        <input type="checkbox" name="activa" value="1"<?= $seccion['activa'] ? ' checked' : '' ?>>
        <span>Visible en la web</span>
      </label>
    </header>

    <?php foreach ($plantilla['campos'] as $campo): ?>
      <?php
      $def     = Plantillas::campo($campo);
      $columna = Plantillas::columna($campo);
      $id      = 'c-' . $columna;
      ?>
      <div class="campo">
        <label class="campo__etiqueta" for="<?= $e($id) ?>"><?= $e($def['etiqueta']) ?></label>
        <?php if ($def['tipo'] === 'imagen'): ?>
          <?php
          $nombre  = $columna;
          $idCampo = $id;
          $elegida = $seccion[$columna] ?? null;
          require __DIR__ . '/_selector-imagen.php';
          ?>
        <?php elseif ($def['tipo'] === 'area'): ?>
          <textarea id="<?= $e($id) ?>" name="<?= $e($columna) ?>" rows="4"><?= $e($seccion[$columna] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" id="<?= $e($id) ?>" name="<?= $e($columna) ?>" value="<?= $e($seccion[$columna] ?? '') ?>">
        <?php endif; ?>
        <?php if (!empty($def['ayuda'])): ?>
          <p class="campo__ayuda"><?= $def['ayuda'] /* la ayuda es texto nuestro, no del usuario */ ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- ── Claves de la columna JSON ── -->
  <?php if ($plantilla['datos'] !== []): ?>
    <section class="tarjeta">
      <header class="tarjeta__cabecera"><h2>Otros textos</h2></header>

      <?php foreach ($plantilla['datos'] as $clave => $def): ?>
        <?php $id = 'd-' . $clave; ?>
        <div class="campo">
          <label class="campo__etiqueta" for="<?= $e($id) ?>"><?= $e($def['etiqueta']) ?></label>

          <?php if ($def['tipo'] === 'lista'): ?>
            <textarea id="<?= $e($id) ?>" name="datos_<?= $e($clave) ?>" rows="6"><?= $e($valorDato($seccion, $clave, 'lista')) ?></textarea>
            <p class="campo__ayuda">Un elemento por línea. Las líneas vacías se descartan.</p>
          <?php elseif ($def['tipo'] === 'area'): ?>
            <textarea id="<?= $e($id) ?>" name="datos_<?= $e($clave) ?>" rows="5"><?= $e($valorDato($seccion, $clave, 'texto')) ?></textarea>
          <?php else: ?>
            <input type="text" id="<?= $e($id) ?>" name="datos_<?= $e($clave) ?>" value="<?= $e($valorDato($seccion, $clave, 'texto')) ?>">
          <?php endif; ?>

          <?php if (!empty($def['ayuda'])): ?>
            <p class="campo__ayuda campo__ayuda--aviso"><?= $e($def['ayuda']) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <!-- ── Bloques repetibles ── -->
  <?php if ($plantilla['bloques'] !== null): ?>
    <?php $def = $plantilla['bloques']; ?>
    <section class="tarjeta">
      <header class="tarjeta__cabecera">
        <h2><?= $e($def['plural']) ?></h2>
        <button class="btn btn--linea" type="button" data-anadir-bloque>Añadir <?= $e(mb_strtolower($def['nombre'])) ?></button>
      </header>

      <p class="vacio">Se publican en el orden en que aparecen aquí. Usa las flechas para moverlos.</p>

      <div class="bloques" data-bloques
           data-maximo="<?= (int) ($def['maximo'] ?? 20) ?>"
           data-nombre="<?= $e($def['nombre']) ?>">
        <?php foreach ($seccion['bloques'] as $i => $b): ?>
          <?php require __DIR__ . '/_bloque.php'; ?>
        <?php endforeach; ?>
      </div>

      <?php /* Molde del que sale cada bloque nuevo. Va dentro de <template>
               para que el navegador no lo pinte ni envíe sus campos. El índice
               lleva __i__, que panel.js sustituye por el número que toque. */ ?>
      <template data-molde-bloque>
        <?php $i = '__i__'; $b = ['activo' => 1, 'datos' => []]; ?>
        <?php require __DIR__ . '/_bloque.php'; ?>
      </template>
    </section>
  <?php endif; ?>

  <!-- ── Aviso para la sección de servicios ── -->
  <?php if ($seccion['plantilla'] === 'tarjetas_icono'): ?>
    <section class="tarjeta">
      <header class="tarjeta__cabecera"><h2>Las seis tarjetas</h2></header>
      <p>No se editan aquí. Salen del catálogo de servicios, que es el mismo que llena
         el desplegable del formulario de inscripción.</p>
      <ul class="lista-conteo">
        <?php foreach ($servicios as $s): ?>
          <li>
            <span><?= $e($s['nombre']) ?></span>
            <strong><?= $s['activo'] ? 'Visible' : 'Oculto' ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="vacio">La pantalla para editarlos está pendiente; de momento se cambian
         directamente en la tabla <code>servicios</code>.</p>
    </section>
  <?php endif; ?>

  <div class="barra-guardar">
    <a class="btn btn--linea" href="<?= $url('/paginas/' . $pagina['clave']) ?>">Cancelar</a>
    <button class="btn btn--primario" type="submit">Guardar cambios</button>
  </div>
</form>
