<?php
/**
 * Una fila de bloque dentro del editor de secciones.
 *
 * Se incluye dos veces: una por cada bloque existente y otra dentro del
 * <template> del que salen los nuevos. Por eso el índice viene de fuera y
 * puede ser el marcador «__i__».
 *
 * @var array      $def  definición de bloques de la plantilla
 * @var array      $b    el bloque
 * @var int|string $i    índice, o «__i__» en el molde
 */

use Intranet\Cms\Plantillas;
use Intranet\Core\View;

$e = static fn ($v) => View::e($v);
?>
<?php /* El ancla con la que la lista de páginas entra directamente a esta
         ficha. Sin ella, «editar Chiclayo» dejaba a alguien delante de un
         formulario con trece fichas iguales, buscando la suya. En el molde del
         <template> no hay slug todavía, así que ahí no se pinta ningún id
         —dos elementos con el mismo id romperían el ancla—. */ ?>
<article class="bloque" data-bloque<?= ($b['slug'] ?? '') !== '' ? ' id="pieza-' . $e($b['slug']) . '"' : '' ?>>
  <header class="bloque__cabecera">
    <span class="bloque__num" data-bloque-num><?= is_int($i) ? $i + 1 : '' ?></span>
    <div class="bloque__mandos">
      <button class="mando-mini" type="button" data-subir aria-label="Subir este bloque">↑</button>
      <button class="mando-mini" type="button" data-bajar aria-label="Bajar este bloque">↓</button>
      <button class="mando-mini mando-mini--peligro" type="button" data-quitar aria-label="Quitar este bloque">Quitar</button>
    </div>
  </header>

  <label class="interruptor">
    <input type="checkbox" name="bloques[<?= $e($i) ?>][activo]" value="1"<?= !empty($b['activo']) ? ' checked' : '' ?>>
    <span>Visible</span>
  </label>

  <?php /* La dirección propia de la pieza. Sólo en las secciones cuyas piezas
           tienen página; en las demás no se pinta y no se guarda. */ ?>
  <?php if (!empty($seccion['datos']['detalle'])): ?>
    <div class="campo">
      <label class="campo__etiqueta">Dirección de su página</label>
      <div class="campo-slug">
        <span class="campo-slug__base"><?= $e(rtrim((string) ($pagina['ruta'] ?? '/'), '/')) ?>/</span>
        <input type="text" name="bloques[<?= $e($i) ?>][slug]"
               value="<?= $e($b['slug'] ?? '') ?>"
               pattern="[a-z0-9]+(-[a-z0-9]+)*"
               placeholder="se calcula del título">
        <span class="campo-slug__base">/</span>
      </div>
      <p class="campo__ayuda campo__ayuda--aviso">
        Si lo dejas vacío se calcula del título. <strong>Cambiarlo rompe los
        enlaces que ya se hayan compartido</strong> de esta página: sólo hazlo
        si aún no se ha publicado.
      </p>
    </div>
  <?php endif; ?>

  <?php foreach ($def['campos'] as $campo): ?>
    <div class="campo">
      <label class="campo__etiqueta"><?= $e(Plantillas::campoBloque($campo)) ?></label>
      <?php if (Plantillas::campo($campo)['tipo'] === 'imagen'): ?>
        <?php
        // El selector guarda el id de la biblioteca, no una ruta. En el molde
        // del <template> el índice es «__i__» y el name sale igual de bien.
        //
        // La columna se pregunta a la plantilla porque ya hay dos campos de
        // imagen —la de escritorio y la de móvil— y cada uno va a la suya.
        $columna = Plantillas::columna($campo);
        $nombre  = 'bloques[' . $i . '][' . $columna . ']';
        $idCampo = 'b-' . $i . '-' . $campo;
        $elegida = $b[$columna] ?? null;
        require __DIR__ . '/_selector-imagen.php';
        ?>
      <?php elseif ($campo === 'texto'): ?>
        <textarea name="bloques[<?= $e($i) ?>][<?= $e($campo) ?>]" rows="3"><?= $e($b[$campo] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="bloques[<?= $e($i) ?>][<?= $e($campo) ?>]" value="<?= $e($b[$campo] ?? '') ?>">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php
  /* ── Lo que esta pantalla no enseña, pero existe ──────────────────────
     En `datos` puede haber claves que la plantilla no declara: las pone una
     migración y ninguna pantalla las muestra. Viajan aquí, en un campo
     oculto, y vuelven tal cual al guardar.

     Sin esto se perdían en el primer «Guardar», porque el controlador rehace
     `datos` desde cero con sólo lo que declara la plantilla. Y viajan CON su
     pieza —no se leen de la base al guardar— porque las piezas se borran y se
     recrean: emparejarlas por posición haría que reordenar dos fichas
     cambiara de sitio lo que arrastran. */
  $declaradas = array_keys($def['datos'] ?? []);
  $sobrantes  = array_diff_key((array) ($b['datos'] ?? []), array_flip($declaradas));
  ?>
  <?php if ($sobrantes !== []): ?>
    <input type="hidden" name="bloques[<?= $e($i) ?>][datos_extra]"
           value="<?= $e(json_encode($sobrantes, JSON_UNESCAPED_UNICODE)) ?>">
  <?php endif; ?>

  <?php foreach ($def['datos'] ?? [] as $clave => $defDato): ?>
    <div class="campo">
      <label class="campo__etiqueta"><?= $e($defDato['etiqueta']) ?></label>
      <textarea name="bloques[<?= $e($i) ?>][datos][<?= $e($clave) ?>]" rows="4"><?php
        /* El valor por defecto era un array vacío para todos los campos, no
           sólo para las listas. En un campo de texto sin rellenar, ese array
           se convertía a cadena: PHP avisaba —«Array to string conversion»— y
           el aviso se imprimía DENTRO del textarea. Al guardar, esa frase se
           quedaba escrita en la base como si fuera contenido. */
        $valor = $b['datos'][$clave] ?? null;

        echo $e($defDato['tipo'] === 'lista'
            ? implode("\n", (array) ($valor ?? []))
            : (is_scalar($valor) ? (string) $valor : ''));
      ?></textarea>
      <?php if ($defDato['tipo'] === 'lista'): ?>
        <p class="campo__ayuda">Un elemento por línea.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</article>
