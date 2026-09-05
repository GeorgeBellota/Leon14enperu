<?php
/**
 * Comunicados: los que están saliendo y el historial con sus cifras.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var array $comunicados
 * @var array $paginas
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$vigentes = array_filter($comunicados, static fn (array $x): bool => (int) $x['vigente'] === 1);
$retirados = array_filter($comunicados, static fn (array $x): bool => (int) $x['vigente'] !== 1);

/** El porcentaje de clics sobre las veces que se mostró. */
$tasa = static function (array $x): string {
    $v = (int) $x['vistas'];

    return $v === 0 ? '—' : round((int) $x['clics'] / $v * 100, 1) . ' %';
};

/** Por qué no está saliendo: retirado, caducado, o sin publicar todavía. */
$motivo = static function (array $x): string {
    if ((int) $x['vistas'] === 0 && (int) $x['clics'] === 0 && (int) $x['activo'] === 0) {
        return 'Sin publicar';
    }
    if ($x['expira_en'] !== null && strtotime((string) $x['expira_en']) < time()) {
        return 'Caducado el ' . View::fecha($x['expira_en']);
    }

    return 'Retirado';
};
?>

<header class="encabezado">
  <p class="rotulo">Contenidos</p>
  <h1>Comunicados</h1>
  <p class="encabezado__pie">
    El aviso que aparece sobre la web. Sólo se muestra uno a la vez: el más reciente
    de los que estén publicados y en fecha.
  </p>
</header>

<?php if ($auth->puede('comunicados.editar')): ?>
  <p class="sep-m">
    <a class="btn btn--primario" href="<?= $url('/comunicados/nuevo') ?>">Nuevo comunicado</a>
  </p>
<?php endif; ?>

<?php /* ── Los que están saliendo ─────────────────────────────────────────── */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Publicados</h2>
    <span class="tarjeta__nota"><?= count($vigentes) ?> en la web</span>
  </header>

  <?php if ($vigentes === []): ?>
    <p class="vacio">
      Ningún comunicado está saliendo ahora mismo. La web se ve sin ningún aviso encima.
    </p>
  <?php else: ?>
    <?php if (count($vigentes) > 1): ?>
      <p class="campo__ayuda sep-s">
        Hay <?= count($vigentes) ?> publicados, pero sólo se muestra el primero de la lista.
        Retira el que no quieras para que salga el otro.
      </p>
    <?php endif; ?>

    <?php foreach ($vigentes as $i => $x): ?>
      <?php require __DIR__ . '/_fila.php'; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<?php /* ── El historial ───────────────────────────────────────────────────────
         Los retirados NO se borran: aquí están con sus cifras. Es lo que
         permite saber si el aviso de la peregrinación funcionó mejor que el de
         las inscripciones, y eso sólo se puede comparar si los dos siguen. */ ?>
<?php if ($retirados !== []): ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Historial</h2>
      <span class="tarjeta__nota"><?= count($retirados) ?> retirado<?= count($retirados) === 1 ? '' : 's' ?></span>
    </header>

    <?php foreach ($retirados as $i => $x): ?>
      <?php require __DIR__ . '/_fila.php'; ?>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
