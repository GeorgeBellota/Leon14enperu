<?php
/**
 * Layout del panel: barra lateral + contenido.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var string                    $contenido
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/** Marca el enlace activo del menú. */
$activo = static function (string $prefijo) use ($rutaActual): string {
    if ($prefijo === '/') {
        return in_array($rutaActual, ['/', '/inicio'], true) ? ' aria-current="page"' : '';
    }

    return str_starts_with($rutaActual, $prefijo) ? ' aria-current="page"' : '';
};
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

<?php /* Los tokens salen del sitio público: una sola fuente de color y tipografía. */ ?>
<link rel="stylesheet" href="<?= $e($c->urlSitio('assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= $e($c->urlAsset('assets/css/panel.css')) ?>">
<link rel="icon" href="<?= $e($c->urlSitio('favicon.svg')) ?>" type="image/svg+xml">
</head>

<body>
<a class="salto" href="#principal">Saltar al contenido</a>

<div class="armazon">

  <aside class="lateral">
    <a class="marca" href="<?= $url('/') ?>">
      <span class="marca__nombre">León XIV</span>
      <span class="marca__lugar">Intranet</span>
    </a>

    <nav class="menu" aria-label="Secciones del panel">
      <a class="menu__enlace" href="<?= $url('/') ?>"<?= $activo('/') ?>>Escritorio</a>

      <?php if ($auth->puede('voluntarios.ver')): ?>
        <p class="menu__grupo">Voluntariado</p>
        <a class="menu__enlace" href="<?= $url('/voluntarios') ?>"<?= $activo('/voluntarios') ?>>Inscripciones</a>
      <?php endif; ?>

      <?php if ($auth->puedeAlguno('paginas.ver', 'medios.ver', 'comunicados.ver')): ?>
        <p class="menu__grupo">Contenidos</p>
        <?php if ($auth->puede('paginas.ver')): ?>
          <a class="menu__enlace" href="<?= $url('/paginas') ?>"<?= $activo('/paginas') ?>>Páginas</a>
        <?php endif; ?>
        <?php if ($auth->puede('medios.ver')): ?>
          <a class="menu__enlace" href="<?= $url('/medios') ?>"<?= $activo('/medios') ?>>Imágenes</a>
        <?php endif; ?>
        <?php if ($auth->puede('comunicados.ver')): ?>
          <a class="menu__enlace" href="<?= $url('/comunicados') ?>"<?= $activo('/comunicados') ?>>Comunicados</a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($auth->puedeAlguno("ajustes.general", "mantenimiento.gestionar")): ?>
        <p class="menu__grupo">Administración</p>
        <?php if ($auth->puede("ajustes.general")): ?>
          <a class="menu__enlace" href="<?= $url("/configuracion") ?>"<?= $activo("/configuracion") ?>>Configuración</a>
        <?php endif; ?>
        <?php if ($auth->puede("mantenimiento.gestionar")): ?>
          <a class="menu__enlace" href="<?= $url("/mantenimiento") ?>"<?= $activo("/mantenimiento") ?>>Mantenimiento</a>
        <?php endif; ?>
      <?php endif; ?>

      <?php /* Catálogos, biblioteca, usuarios, actividad y ajustes tienen sus
               permisos creados y sus rutas escritas, pero todavía no tienen
               controlador. No se enseñan en el menú hasta que existan: un
               enlace que lleva a un 404 es peor que un menú corto. */ ?>
    </nav>

    <div class="lateral__pie">
      <p class="lateral__usuario">
        <strong><?= $e($auth->nombre()) ?></strong>
        <span><?= $e($usuarioActual['rol_nombre'] ?? '') ?></span>
      </p>
      <a class="lateral__ver" href="<?= $e($c->urlSitio('/')) ?>" target="_blank" rel="noopener">Ver el sitio ↗</a>
      <form method="post" action="<?= $url('/salir') ?>">
        <?= $csrf->campo() ?>
        <button class="btn btn--linea btn--bloque" type="submit">Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <main class="principal" id="principal">

    <?php if ($destellos !== []): ?>
      <div class="destellos" role="status" aria-live="polite">
        <?php foreach ($destellos as $d): ?>
          <p class="destello destello--<?= $e($d['tipo']) ?>"><?= $e($d['mensaje']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?= $contenido ?>

  </main>
</div>

<script src="<?= $e($c->urlAsset('assets/js/panel.js')) ?>" defer></script>
<?php /* El calendario de los filtros. Sólo hace algo en las pantallas que
         tienen campos de fecha; en las demás no cuesta nada. */ ?>
<script src="<?= $e($c->urlAsset('assets/js/fecha.js')) ?>" defer></script>
</body>
</html>
