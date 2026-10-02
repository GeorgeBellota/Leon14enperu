<?php
/**
 * El tablero de métricas.
 *
 * Qué costó atender cada visita, por página y por rango de fechas. Todo sale
 * de los registros que deja el Medidor, no de la base: se puede mirar aunque
 * la base esté ahogada, que es justo cuando se quiere mirar.
 *
 * ── Por qué el p95 va antes que la media ──────────────────────────────────
 *
 * Porque la media esconde lo que duele. Si de veinte visitas diecinueve
 * tardan 50 ms y una tarda ocho segundos, la media dice 450 ms y parece bien.
 * El p95 dice ocho segundos, que es lo que se está llevando a alguien.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var string $desde
 * @var string $hasta
 * @var array  $dias
 * @var array  $resumen
 * @var array  $permanencia
 * @var int    $abiertoHasta
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$t = $resumen['total'];

/** Un número grande, legible: 1 248 311 en vez de 1248311. */
$num = static fn (float $n, int $dec = 0): string => number_format($n, $dec, ',', ' ');

/** Milisegundos como los lee una persona. */
$ms = static function (float $v) use ($num): string {
    return $v >= 1000 ? $num($v / 1000, 1) . ' s' : $num($v) . ' ms';
};

$quedan = max(0, $abiertoHasta - time());
?>

<header class="encabezado">
  <h1>Métricas</h1>
  <p class="encabezado__pie">
    Qué costó atender cada visita, sacado del registro del servidor.
    <?php if ($quedan > 0): ?>
      · El tablero se cierra solo en <?= (int) ceil($quedan / 60) ?> min.
    <?php endif; ?>
  </p>
</header>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>El rango</h2>

    <form method="post" action="<?= $url('/metricas/cerrar') ?>">
      <?= $csrf->campo() ?>
      <button class="btn btn--linea btn--mini" type="submit">Cerrar el tablero</button>
    </form>
  </header>

  <form method="get" action="<?= $url('/metricas/tablero') ?>" class="metricas__filtro">
    <div class="campo">
      <label class="campo__etiqueta" for="desde">Desde</label>
      <input type="date" id="desde" name="desde" value="<?= $e($desde) ?>">
    </div>
    <div class="campo">
      <label class="campo__etiqueta" for="hasta">Hasta</label>
      <input type="date" id="hasta" name="hasta" value="<?= $e($hasta) ?>">
    </div>
    <button class="btn btn--primario" type="submit">Ver</button>
  </form>

  <?php if ($dias === []): ?>
    <p class="vacio">Todavía no hay registro. Se escribe solo, con cada visita.</p>
  <?php else: ?>
    <p class="campo__ayuda">
      Hay registro de <?= count($dias) ?>
      <?= count($dias) === 1 ? 'día' : 'días' ?>,
      del <?= $e(end($dias)) ?> al <?= $e($dias[0]) ?>.
    </p>
  <?php endif; ?>
</section>

<?php if ((int) $t['visitas'] === 0): ?>
  <section class="tarjeta">
    <p class="vacio">No hay ninguna visita en ese rango.</p>
  </section>
<?php else: ?>

<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>El resumen</h2></header>

  <ul class="cifras">
    <li class="cifra">
      <span class="cifra__numero"><?= $num((float) $t['visitas']) ?></span>
      <span class="cifra__pie">visitas atendidas</span>
      <span class="cifra__nota"><?= $num((float) $t['personas']) ?> personas · <?= $num((float) $t['bots']) ?> robots</span>
    </li>

    <?php /* El p95 primero y en grande: es el número que avisa. */ ?>
    <li class="cifra<?= $t['p95'] >= 1000 ? ' cifra--mala' : '' ?>">
      <span class="cifra__numero"><?= $e($ms((float) $t['p95'])) ?></span>
      <span class="cifra__pie">tardó el 5 % más lento</span>
      <span class="cifra__nota">la mitad en <?= $e($ms((float) $t['p50'])) ?> · media <?= $e($ms((float) $t['msMedia'])) ?></span>
    </li>

    <li class="cifra">
      <span class="cifra__numero"><?= $num((float) $t['consultas']) ?></span>
      <span class="cifra__pie">consultas a la base</span>
      <span class="cifra__nota"><?= $num($t['consultas'] / max(1, $t['visitas']), 1) ?> por visita</span>
    </li>

    <li class="cifra<?= $t['errores'] > 0 ? ' cifra--mala' : '' ?>">
      <span class="cifra__numero"><?= $num((float) $t['errores']) ?></span>
      <span class="cifra__pie">respuestas con error</span>
      <span class="cifra__nota">
        <?php /* Un 5xx es el servidor rindiéndose. Si aparecen durante una
                 prueba de carga, ahí está el techo. */ ?>
        de servidor (5xx)
      </span>
    </li>
  </ul>

  <?php /* El gasto del rango: es lo que se pidió, «cuánto se gastó». */ ?>
  <p class="campo__ayuda">
    En total, atender ese rango costó
    <strong><?= $num($t['ms'] / 1000, 1) ?> segundos</strong> de proceso,
    de los cuales <strong><?= $num($t['msBase'] / 1000, 1) ?></strong>
    se fueron dentro de la base
    (<?= $num($t['ms'] > 0 ? $t['msBase'] / $t['ms'] * 100 : 0) ?> %).
  </p>
</section>

<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Qué consume cada página</h2></header>

  <div class="tabla-envoltorio">
    <table class="tabla">
      <thead>
        <tr>
          <th scope="col">Página</th>
          <th scope="col" class="num">Visitas</th>
          <th scope="col" class="num">Media</th>
          <th scope="col" class="num">5 % peor</th>
          <th scope="col" class="num">Consultas</th>
          <th scope="col" class="num">Gasto total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($resumen['rutas'], 0, 40) as $r): ?>
          <tr<?= $r['errores'] > 0 ? ' class="es-activa"' : '' ?>>
            <td>
              <span class="tabla__mono"><?= $e($r['ruta']) ?></span>
              <?php if ($r['origen'] !== 'web'): ?>
                <span class="pildora"><?= $e($r['origen']) ?></span>
              <?php endif; ?>
              <?php if ($r['errores'] > 0): ?>
                <span class="pildora pildora--baja"><?= (int) $r['errores'] ?> con error</span>
              <?php endif; ?>
            </td>
            <td class="num"><?= $num((float) $r['visitas']) ?></td>
            <td class="num"><?= $e($ms((float) $r['msMedia'])) ?></td>
            <td class="num"><?= $e($ms((float) $r['p95'])) ?></td>
            <td class="num"><?= $num($r['consultas'] / max(1, $r['visitas']), 1) ?></td>
            <td class="num"><?= $num($r['ms'] / 1000, 1) ?> s</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="campo__ayuda">
    «5 % peor» es el percentil 95: la mayoría va mejor que eso, y ese resto es
    el que se lleva la mala experiencia. Sale de tramos, así que es un «no más
    de», no una cifra al milisegundo.
  </p>
</section>

<?php if ($permanencia !== []): ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera"><h2>Cuánto se lee cada página</h2></header>

    <div class="tabla-envoltorio">
      <table class="tabla">
        <thead>
          <tr>
            <th scope="col">Página</th>
            <th scope="col" class="num">Lecturas</th>
            <th scope="col" class="num">Media</th>
            <th scope="col" class="num">En total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($permanencia, 0, 30) as $p): ?>
            <tr>
              <td><span class="tabla__mono"><?= $e($p['ruta']) ?></span></td>
              <td class="num"><?= $num((float) $p['lecturas']) ?></td>
              <td class="num"><?= $num((float) $p['media']) ?> s</td>
              <td class="num"><?= $num($p['segundos'] / 60, 1) ?> min</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="campo__ayuda">
      Se cuenta el tiempo que la página estuvo <strong>a la vista</strong>: una
      pestaña en segundo plano no suma. No lleva cookies ni identificador, así
      que son lecturas, no personas: no se puede saber si diez lecturas fueron
      de diez visitantes o de uno que volvió diez veces.
    </p>
  </section>
<?php endif; ?>

<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Por horas</h2></header>

  <?php
  $pico = max(1, max($resumen['horas'] ?: [1]));
  ?>
  <ul class="horas">
    <?php foreach (array_slice($resumen['horas'], -48, 48, true) as $hora => $cuantas): ?>
      <li class="hora">
        <span class="hora__rotulo"><?= $e(str_replace('T', '  ', $hora)) ?>h</span>
        <span class="hora__pista">
          <span class="hora__relleno" style="width: <?= (int) round($cuantas / $pico * 100) ?>%"></span>
        </span>
        <span class="hora__valor"><?= $num((float) $cuantas) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Cómo respondió</h2></header>

  <ul class="lista-conteo">
    <?php foreach ($resumen['codigos'] as $codigo => $cuantas): ?>
      <li>
        <span>
          <?= (int) $codigo ?>
          <?= $codigo >= 500 ? ' · el servidor se rindió'
              : ($codigo === 404 ? ' · no encontrada' : ($codigo < 300 ? ' · bien' : '')) ?>
        </span>
        <strong><?= $num((float) $cuantas) ?></strong>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<?php endif; ?>
