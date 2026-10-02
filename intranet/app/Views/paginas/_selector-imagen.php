<?php
/**
 * Selector de una imagen de la biblioteca.
 *
 * Se usa en el editor de secciones y en cada pieza. Guarda el id, no la ruta,
 * para que cambiar una foto en la biblioteca la cambie en todas las páginas
 * donde aparece.
 *
 * ── Por qué ya no trae la biblioteca dentro ───────────────────────────────
 *
 * Porque la traía ENTERA, y una vez por campo. Medido en octubre de 2026 en
 * Páginas → Inicio → Itinerario:
 *
 *     7 piezas × 2 campos de imagen = 14 selectores
 *     14 selectores × 96 fotos      = 1 344 miniaturas
 *     → 998 KB de HTML en una sola pantalla
 *
 * Y empeoraba solo: cada foto subida engordaba TODAS las pantallas de
 * edición. Con 300 fotos serían más de 4 000 miniaturas.
 *
 * Ahora cada selector trae sólo la foto elegida, y la rejilla para escoger
 * vive en UNA ventana compartida por toda la pantalla
 * (_biblioteca-ventana.php). Las miniaturas de esa ventana son «lazy», así
 * que no se piden hasta que alguien la abre.
 *
 * ── Las tres capas siguen ─────────────────────────────────────────────────
 *
 *   1. Un <select> normal. Es el que guarda el valor y el que se envía. Sin
 *      JavaScript el campo funciona exactamente igual que siempre.
 *   2. Con JavaScript, el botón «Elegir» abre la ventana y escribe en el
 *      <select>.
 *   3. La subida sin salir, que ya era sólo con JavaScript.
 *
 * ── Por qué la subida no envía el formulario de la sección ────────────────
 *
 * Porque no es «multipart». Si se convirtiera y una subida superara
 * post_max_size, PHP entregaría un $_POST VACÍO: se guardaría la pantalla
 * entera en blanco, y sin un solo mensaje de error.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var array       $medios  la biblioteca (para el <select>, sin miniaturas)
 * @var string      $nombre  name del campo
 * @var string      $idCampo id del <select>
 * @var int|null    $elegida id de la imagen actual
 */

use Intranet\Core\View;

$eSel   = static fn ($v) => View::e($v);
$actual = null;

foreach ($medios as $m) {
    if ((int) $m['id'] === (int) ($elegida ?? 0)) {
        $actual = $m;
        break;
    }
}
?>
<div class="selector-imagen" data-selector-imagen data-medios-url="<?= $eSel($c->url('/medios')) ?>">

  <div class="selector-imagen__cabecera">
    <div class="selector-imagen__vista">
      <?php if ($actual !== null): ?>
        <img src="<?= $eSel($c->urlSitio('/' . ltrim((string) $actual['ruta'], '/'))) ?>"
             alt="<?= $eSel($actual['alt'] ?? '') ?>" loading="lazy" decoding="async"
             data-vista-imagen>
      <?php else: ?>
        <img src="" alt="" hidden data-vista-imagen>
        <span class="selector-imagen__vacia" data-vista-vacia>Sin imagen</span>
      <?php endif; ?>
    </div>

    <div class="selector-imagen__mandos">
      <?php /* El nombre de lo elegido, para saber qué hay sin mirar la
               miniatura: a 90 px, dos fotos parecidas son la misma. */ ?>
      <p class="selector-imagen__nombre" data-vista-nombre>
        <?= $actual !== null ? $eSel($actual['nombre_archivo']) : 'Sin imagen' ?>
      </p>

      <?php /* El <select> es el campo de verdad. Con JavaScript se esconde
               —se maneja desde la ventana— pero sigue siendo lo que viaja en
               el formulario. Sin JavaScript, es todo lo que hay y basta. */ ?>
      <select id="<?= $eSel($idCampo) ?>" name="<?= $eSel($nombre) ?>" data-elegir-imagen>
        <option value="">— Sin imagen —</option>
        <?php foreach ($medios as $m): $esta = (int) $m['id'] === (int) ($elegida ?? 0); ?>
          <?php /* Los data-* van SOLO en la opción elegida. El JS los lee de la
                   opción seleccionada para pintar la vista previa, y cuando se
                   elige otra imagen en la ventana los escribe él mismo. Ponerlos
                   en las 96 eran 1 344 copias y 341 KB en esta sola pantalla. */ ?>
          <option value="<?= (int) $m['id'] ?>"<?php if ($esta): ?>
                  data-src="<?= $eSel($c->urlSitio('/' . ltrim((string) $m['ruta'], '/'))) ?>"
                  data-alt="<?= $eSel($m['alt'] ?? '') ?>"
                  data-nombre="<?= $eSel($m['nombre_archivo']) ?>"
                  selected<?php endif; ?>>
            <?= $eSel($m['nombre_archivo']) ?><?php
              if (!empty($m['ancho'])) {
                  echo ' (' . (int) $m['ancho'] . '×' . (int) $m['alto'] . ')';
              }
            ?>
          </option>
        <?php endforeach; ?>
      </select>

      <?php /* Sin JavaScript esto es lo único que hay, y es lo que había
               antes: el desplegable de arriba y un enlace a la biblioteca. */ ?>
      <p class="selector-imagen__salida" data-sin-js>
        <a href="<?= $eSel($c->url('/medios')) ?>" target="_blank" rel="noopener">Ver o subir imágenes ↗</a>
      </p>

      <div class="selector-imagen__acciones" data-con-js hidden>
        <button type="button" class="btn btn--mini" data-abrir-biblioteca>Elegir imagen</button>
        <button type="button" class="btn btn--mini btn--linea" data-abrir-subida>Subir una</button>
        <button type="button" class="btn btn--mini btn--linea" data-quitar-imagen
                <?= $actual === null ? 'hidden' : '' ?>>Quitar</button>
      </div>
    </div>
  </div>

  <?php /* La zona de subida. Va vacía en el HTML y JavaScript la rellena sólo
           cuando se pulsa «Subir una»: son inputs sueltos, no un formulario,
           porque estamos dentro del formulario de la sección. */ ?>
  <div class="subida" data-subida hidden></div>
</div>
