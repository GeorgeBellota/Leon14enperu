<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var int                       $codigo
 * @var string                    $mensaje
 */

use Intranet\Core\View;

$e = static fn ($v) => View::e($v);
?>

<p class="portada__codigo"><?= (int) $codigo ?></p>
<h1 class="portada__titulo">
  <?php
  echo $e(match ((int) $codigo) {
      403     => 'Sin permiso',
      404     => 'Aquí no hay nada',
      405     => 'Método no permitido',
      419     => 'La sesión caducó',
      500     => 'Algo se rompió',
      default => 'Error',
  });
  ?>
</h1>

<p class="portada__bajada"><?= $e($mensaje) ?></p>

<p class="portada__nota">
  <a class="btn btn--linea" href="<?= $e($c->url('/')) ?>">Volver al escritorio</a>
</p>
