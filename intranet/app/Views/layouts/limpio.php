<?php
/**
 * Layout sin barra lateral: login, cambio de contraseña y páginas de error.
 * Se usa también cuando no hay sesión, así que no puede tocar $auth.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var string                    $contenido
 */

use Intranet\Core\View;

$e = static fn ($v) => View::e($v);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($titulo ?? 'Intranet') ?> · <?= $e($c->config('app.nombre')) ?></title>
<meta name="robots" content="noindex, nofollow">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Instrument+Sans:wght@400;500;600&display=swap">

<link rel="stylesheet" href="<?= $e($c->urlSitio('assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= $e($c->urlAsset('assets/css/panel.css')) ?>">
<link rel="icon" href="<?= $e($c->urlSitio('favicon.svg')) ?>" type="image/svg+xml">
</head>

<body class="cuerpo-limpio">

<main class="portada">
  <div class="portada__caja">

    <p class="marca marca--centrada">
      <span class="marca__nombre">León XIV</span>
      <span class="marca__lugar">En el Perú · Intranet</span>
    </p>

    <?php if (($destellos ?? []) !== []): ?>
      <div class="destellos" role="status" aria-live="polite">
        <?php foreach ($destellos as $d): ?>
          <p class="destello destello--<?= $e($d['tipo']) ?>"><?= $e($d['mensaje']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?= $contenido ?>

  </div>
</main>

</body>
</html>
