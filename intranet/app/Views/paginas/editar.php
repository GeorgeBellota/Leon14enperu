<?php
/**
 * Editor de una sección.
 *
 * El formulario se dibuja a partir de la plantilla declarada en
 * Cms\Plantillas: qué campos y qué claves JSON. Añadir una sección
 * administrable no exige tocar esta vista.
 *
 * Las piezas de la sección NO se editan aquí. Cada una tiene su pantalla
 * (pieza.php) y esta sólo las lista: la sábana hacía que corregir una coma en
 * la segunda de trece comisiones obligase a bajar por las once siguientes, y
 * que cada «Guardar» reescribiera las trece.
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

  <?php /* El historial existía desde el primer día —se guarda una copia en
           cada «Guardar»— pero no se podía llegar a él desde ninguna parte. */ ?>
  <p class="encabezado__pie">
    <a href="<?= $url('/paginas/' . $pagina['clave'] . '/' . $seccion['clave'] . '/historial') ?>">
      Ver el historial de cambios
    </a>
  </p>
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
          require __DIR__ . '/../_comunes/_selector-imagen.php';
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

          <?php if ($def['tipo'] === 'opciones'): ?>
            <?php
            $defCampo    = $def;
            $nombreCampo = 'datos_' . $clave;
            $idCampo     = $id;
            $valorCampo  = $valorDato($seccion, $clave, 'texto');
            require __DIR__ . '/_campo-opciones.php';
            ?>
          <?php elseif ($def['tipo'] === 'lista'): ?>
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

<?php /* ── Las piezas de esta sección ────────────────────────────────────
         Van FUERA del formulario de arriba y no dentro, como iban antes:
         cada flecha y el botón de añadir son formularios propios, y un
         formulario dentro de otro no es HTML válido —el navegador se queda
         con el de fuera y descarta el de dentro sin decir nada—. */ ?>
<?php if ($plantilla['bloques'] !== null): ?>
  <?php $def = $plantilla['bloques']; ?>
  <?php require __DIR__ . '/_piezas-lista.php'; ?>
<?php endif; ?>

<?php /* ── La ventana para elegir imagen ───────────────────────────────────
         UNA para toda la pantalla, fuera del <form> y compartida por todos
         los campos de imagen.

         Antes la rejilla iba DENTRO de cada campo. En Páginas → Inicio →
         Itinerario eso eran 14 copias de 96 miniaturas: 1 344 imágenes y 998
         KB de HTML para editar una jornada, y creciendo con cada foto que se
         subiera a la biblioteca. */ ?>
<?php require __DIR__ . '/../_comunes/_biblioteca-ventana.php'; ?>
