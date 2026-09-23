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
      <?php else: ?>
        <?php
        /* ── Se pregunta por el TIPO, no por el nombre ────────────────────
           Antes decía «if ($campo === 'texto')», así que cualquier otro campo
           salía como <input> de una línea aunque la plantilla lo declarara
           como área. Y un <input> no puede contener un salto de línea: el
           navegador los quita del valor al enviarlo.

           Eso se llevó por delante los titulares de las tarjetas de Subsidios
           de la portada, que van a dos renglones a propósito. Bastó con abrir
           la sección y guardar: «UNIDOS EN CRISTO,\nSEMBRADORES DE\nPAZ» se
           guardó como «UNIDOS EN CRISTO,SEMBRADORES DEPAZ», sin salto y sin
           espacio, y con nowrap eso se desbordaba encima de las tarjetas de
           al lado. Nada avisó.

           La columna también se pregunta a la plantilla, igual que en las
           imágenes: así un campo puede llamarse de una forma en el panel y
           guardar en la columna de siempre. */
        $columna = Plantillas::columna($campo);
        $nombre  = 'bloques[' . $i . '][' . $columna . ']';
        ?>
        <?php if (Plantillas::campo($campo)['tipo'] === 'area'): ?>
          <textarea name="<?= $e($nombre) ?>" rows="3"><?= $e($b[$columna] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= $e($nombre) ?>" value="<?= $e($b[$columna] ?? '') ?>">
        <?php endif; ?>
      <?php endif; ?>
      <?php if (($ayuda = Plantillas::campo($campo)['ayuda'] ?? '') !== ''): ?>
        <p class="campo__ayuda"><?= $ayuda /* la ayuda es texto nuestro, no del usuario */ ?></p>
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
      <?php /* Una caja de cuatro renglones para un campo de una línea —dónde va
               la fotografía, un subtítulo— invita a escribir un párrafo donde
               cabe una palabra. La altura la dice el tipo. */ ?>
      <textarea name="bloques[<?= $e($i) ?>][datos][<?= $e($clave) ?>]"
                rows="<?= $defDato['tipo'] === 'texto' ? 2 : 4 ?>"><?php
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
      <?php elseif (($defDato['ayuda'] ?? '') !== ''): ?>
        <?php /* La plantilla puede explicar para qué sirve el campo, igual que
                 hace con los de la sección. Se declaraba y no se pintaba en
                 ninguna parte, así que quien abría la ficha tenía que adivinar
                 qué se escribe en «Dónde va la fotografía». */ ?>
        <p class="campo__ayuda"><?= $defDato['ayuda'] /* la ayuda es texto nuestro, no del usuario */ ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</article>
