<?php
/**
 * Selector de una imagen de la biblioteca.
 *
 * Se usa en el editor de secciones y en cada bloque. No sube nada: elige entre
 * lo que ya está en Imágenes. Guarda el id, no la ruta, para que cambiar una
 * foto en la biblioteca la cambie en todas las páginas donde aparece.
 *
 * Es un <select> normal: sin JavaScript sigue funcionando, y la vista previa
 * es un añadido que se activa sola si el navegador puede.
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
<div class="selector-imagen" data-selector-imagen>
  <?php if ($medios === []): ?>
    <p class="campo__ayuda">
      Todavía no hay imágenes en la biblioteca.
      <a href="<?= $eSel($c->url('/medios')) ?>">Sube la primera</a> y vuelve aquí.
    </p>
    <input type="hidden" name="<?= $eSel($nombre) ?>" value="">
  <?php else: ?>
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
  <?php endif; ?>
</div>
