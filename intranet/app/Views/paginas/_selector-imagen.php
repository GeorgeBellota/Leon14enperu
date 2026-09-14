<?php
/**
 * Selector de una imagen de la biblioteca.
 *
 * Se usa en el editor de secciones y en cada bloque. Guarda el id, no la ruta,
 * para que cambiar una foto en la biblioteca la cambie en todas las páginas
 * donde aparece.
 *
 * ── Qué hay aquí y por qué ─────────────────────────────────────────────────
 *
 * Tres capas, de menos a más, y cada una funciona sin la siguiente:
 *
 *   1. Un <select> normal. Es el que guarda el valor y el que se envía. Sin
 *      JavaScript el campo funciona exactamente igual que siempre.
 *   2. Una rejilla de miniaturas. Se pinta en PHP, así que se ve aunque no
 *      haya JavaScript; con él, pulsar una miniatura mueve el <select>.
 *   3. Una zona de subida. Sólo aparece si hay JavaScript, porque sin él no
 *      hay forma de subir sin salir: el editor de sección ya es un <form> y
 *      no se pueden anidar formularios.
 *
 * ── Por qué la subida no envía el formulario de la sección ─────────────────
 *
 * Porque ese formulario borra y recrea las piezas al guardar, y no es
 * «multipart». Si se convirtiera y una subida superara post_max_size, PHP
 * entregaría un $_POST VACÍO y la sección se reconstruiría con nada: se
 * perdería entera y sin un solo mensaje de error.
 *
 * Así que la imagen viaja por su cuenta a /medios con fetch, y lo único que
 * vuelve es un id que se mete en el <select>. El formulario de la sección ni
 * se entera, y lo que el usuario llevaba escrito sigue donde estaba.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var array       $medios  la biblioteca
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
      <select id="<?= $eSel($idCampo) ?>" name="<?= $eSel($nombre) ?>" data-elegir-imagen>
        <option value="">— Sin imagen —</option>
        <?php foreach ($medios as $m): ?>
          <option value="<?= (int) $m['id'] ?>"
                  data-src="<?= $eSel($c->urlSitio('/' . ltrim((string) $m['ruta'], '/'))) ?>"
                  data-alt="<?= $eSel($m['alt'] ?? '') ?>"
                  <?= (int) $m['id'] === (int) ($elegida ?? 0) ? 'selected' : '' ?>>
            <?= $eSel($m['nombre_archivo']) ?><?php
              if (!empty($m['ancho'])) {
                  echo ' (' . (int) $m['ancho'] . '×' . (int) $m['alto'] . ')';
              }
            ?>
          </option>
        <?php endforeach; ?>
      </select>

      <?php /* Sin JavaScript esto es lo único que hay, y es lo que había antes:
               un enlace a la biblioteca. Con JavaScript se esconde y en su
               lugar aparece el botón de subir, que no obliga a salir. */ ?>
      <p class="selector-imagen__salida" data-sin-js>
        <a href="<?= $eSel($c->url('/medios')) ?>" target="_blank" rel="noopener">Subir una imagen nueva ↗</a>
      </p>

      <div class="selector-imagen__acciones" data-con-js hidden>
        <button type="button" class="btn btn--plano" data-abrir-subida>Subir una imagen</button>
      </div>
    </div>
  </div>

  <?php /* La rejilla. Se pinta siempre: incluso sin JavaScript sirve para ver
           qué hay en la biblioteca sin abrir otra pestaña. Con JavaScript,
           además, elige. */ ?>
  <?php if ($medios !== []): ?>
    <ul class="biblioteca" data-biblioteca>
      <?php foreach ($medios as $m): ?>
        <li>
          <button type="button"
                  class="biblioteca__pieza<?= (int) $m['id'] === (int) ($elegida ?? 0) ? ' es-elegida' : '' ?>"
                  data-pieza="<?= (int) $m['id'] ?>"
                  aria-pressed="<?= (int) $m['id'] === (int) ($elegida ?? 0) ? 'true' : 'false' ?>"
                  title="<?= $eSel($m['nombre_archivo']) ?>">
            <img src="<?= $eSel($c->urlSitio('/' . ltrim((string) $m['ruta'], '/'))) ?>"
                 alt="<?= $eSel($m['alt'] ?? '') ?>" loading="lazy" decoding="async">
          </button>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="campo__ayuda" data-biblioteca-vacia>
      Todavía no hay imágenes en la biblioteca.
    </p>
  <?php endif; ?>

  <?php /* La zona de subida. Va vacía en el HTML y JavaScript la rellena sólo
           cuando se pulsa «Subir una imagen»: son inputs sueltos, no un
           formulario, porque estamos dentro del formulario de la sección. */ ?>
  <div class="subida" data-subida hidden></div>
</div>
