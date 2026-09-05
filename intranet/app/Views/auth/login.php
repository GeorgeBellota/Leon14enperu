<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 */

use Intranet\Core\View;

$e = static fn ($v) => View::e($v);
?>

<h1 class="portada__titulo">Entrar</h1>
<p class="portada__bajada">Acceso restringido al equipo de la organización.</p>

<?php if (!empty($aviso)): ?>
  <p class="destello destello--aviso"><?= $e($aviso) ?></p>
<?php endif; ?>

<form class="formulario" method="post" action="<?= $e($c->url('/login')) ?>" novalidate>
  <?= $csrf->campo() ?>

  <div class="campo">
    <label class="campo__etiqueta" for="correo">Correo electrónico</label>
    <input type="email" id="correo" name="correo" autocomplete="username"
           required autofocus inputmode="email">
  </div>

  <div class="campo">
    <label class="campo__etiqueta" for="clave">Contraseña</label>
    <input type="password" id="clave" name="clave" autocomplete="current-password" required>
  </div>

  <button class="btn btn--primario btn--bloque" type="submit">Entrar</button>
</form>

<p class="portada__nota">
  Si no recuerdas tu contraseña, pídesela a la persona que administra la intranet:
  por seguridad no hay recuperación automática por correo.
</p>
