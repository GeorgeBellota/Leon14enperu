<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var bool                      $obligado
 * @var int                       $minimo
 */

use Intranet\Core\View;

$e = static fn ($v) => View::e($v);
?>

<h1 class="portada__titulo">Cambiar la contraseña</h1>

<?php if ($obligado): ?>
  <p class="destello destello--aviso">
    Es tu primer acceso. Cambia la contraseña que te dieron antes de continuar:
    quien te creó la cuenta la conoce.
  </p>
<?php endif; ?>

<form class="formulario" method="post" action="<?= $e($c->url('/clave')) ?>" novalidate>
  <?= $csrf->campo() ?>

  <div class="campo">
    <label class="campo__etiqueta" for="actual">Contraseña actual</label>
    <input type="password" id="actual" name="actual" autocomplete="current-password" required autofocus>
  </div>

  <div class="campo">
    <label class="campo__etiqueta" for="nueva">Contraseña nueva</label>
    <input type="password" id="nueva" name="nueva" autocomplete="new-password"
           minlength="<?= (int) $minimo ?>" required aria-describedby="ayuda-clave">
    <p class="campo__ayuda" id="ayuda-clave">
      Mínimo <?= (int) $minimo ?> caracteres. Una frase larga que recuerdes es
      mejor que ocho símbolos raros que acabes apuntando en un papel.
    </p>
  </div>

  <div class="campo">
    <label class="campo__etiqueta" for="repite">Repite la contraseña nueva</label>
    <input type="password" id="repite" name="repite" autocomplete="new-password"
           minlength="<?= (int) $minimo ?>" required>
  </div>

  <button class="btn btn--primario btn--bloque" type="submit">Guardar</button>
</form>

<?php if (!$obligado): ?>
  <p class="portada__nota"><a href="<?= $e($c->url('/')) ?>">Volver al escritorio</a></p>
<?php endif; ?>
