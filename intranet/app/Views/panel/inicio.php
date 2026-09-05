<?php
/**
 * Escritorio.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var array<string,int>         $resumen
 * @var array                     $porEstado
 * @var array                     $ultimos
 * @var string                    $dia              día que se está mirando (AAAA-MM-DD)
 * @var bool                      $esHoy
 * @var string                    $diaAnterior
 * @var string|null               $diaSiguiente     null cuando ya estamos en hoy
 * @var int                       $delDia
 * @var array<int,int>            $porHora          las 24 horas, incluidas las vacías
 * @var array                     $porDepartamento
 * @var array                     $porJurisdiccion
 * @var array                     $ultimos7
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$etiquetaEstado = [
    'nuevo'         => 'Sin revisar',
    'en_validacion' => 'En validación',
    'validado'      => 'Validado',
    'acreditado'    => 'Acreditado',
    'rechazado'     => 'Rechazado',
    'baja'          => 'Baja',
];
?>

<header class="encabezado">
  <p class="rotulo">Escritorio</p>
  <h1>Hola, <?= $e(explode(' ', $auth->nombre())[0]) ?></h1>
</header>

<?php if ($auth->puede('voluntarios.ver')): ?>

  <section class="cifras" aria-label="Resumen de inscripciones">
    <article class="cifra">
      <p class="cifra__numero"><?= (int) $resumen['total'] ?></p>
      <p class="cifra__pie">Inscripciones</p>
    </article>
    <article class="cifra">
      <p class="cifra__numero"><?= (int) $resumen['nuevos'] ?></p>
      <p class="cifra__pie">Sin revisar</p>
    </article>
    <article class="cifra">
      <p class="cifra__numero"><?= (int) $resumen['semana'] ?></p>
      <p class="cifra__pie">Últimos 7 días</p>
    </article>
    <article class="cifra">
      <p class="cifra__numero"><?= (int) $resumen['hoy'] ?></p>
      <p class="cifra__pie">Hoy</p>
    </article>
  </section>

  <?php /* ══════════════════════════════════════════════════════════════════
           EL RITMO DE LOS ÚLTIMOS SIETE DÍAS

           Un número suelto —«131 inscripciones»— no dice si la cosa sube o
           baja. Siete barras sí: se ve de un vistazo si la convocatoria está
           cogiendo fuerza, si se apagó el fin de semana o si una publicación
           movió algo.

           Se dibuja con divs y porcentajes, sin librería de gráficos: son
           siete valores, y traer un archivo de cien kilobytes para pintar
           siete barras sería desproporcionado.
           ══════════════════════════════════════════════════════════════════ */ ?>
  <?php $topeSemana = max(1, max(array_column($ultimos7, 'total'))); ?>

  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Últimos 7 días</h2>
      <span class="tarjeta__nota"><?= array_sum(array_column($ultimos7, 'total')) ?> en total</span>
    </header>

    <ul class="barras">
      <?php foreach ($ultimos7 as $d): ?>
        <li class="barras__item<?= $d['esHoy'] ? ' es-hoy' : '' ?><?= $d['dia'] === $dia ? ' es-elegido' : '' ?>">
          <a class="barras__enlace" href="<?= $url('/?dia=' . $d['dia']) ?>"
             title="Ver el detalle del <?= $e($d['etiqueta']) ?>">
            <span class="barras__cifra"><?= (int) $d['total'] ?></span>
            <span class="barras__vara" style="height: <?= max(2, (int) round($d['total'] / $topeSemana * 100)) ?>%"></span>
            <span class="barras__pie"><?= $e($d['etiqueta']) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="campo__ayuda">Pulsa un día para ver su detalle por hora, departamento y jurisdicción.</p>
  </section>

  <?php /* ══════════════════════════════════════════════════════════════════
           EL DÍA ELEGIDO

           Todo lo que hay debajo —las horas, los departamentos, las
           jurisdicciones— se refiere a este día. El selector se repite arriba
           para que no haga falta recordar cuál se está mirando.
           ══════════════════════════════════════════════════════════════════ */ ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2><?= $esHoy ? 'Hoy' : 'El ' . $e(View::fecha($dia)) ?></h2>
      <?php /* «inscripción» pierde la tilde en plural: inscripciones. Pegarle
               una «es» al singular daba «inscripciónes», que además se colaba
               en cualquier búsqueda de texto. */ ?>
      <span class="tarjeta__nota"><strong><?= (int) $delDia ?></strong> <?= (int) $delDia === 1 ? 'inscripción' : 'inscripciones' ?></span>
    </header>

    <form class="filtro-dia" method="get" action="<?= $url('/') ?>">
      <a class="btn btn--linea btn--menor" href="<?= $url('/?dia=' . $diaAnterior) ?>" aria-label="Día anterior">←</a>

      <label class="solo-lectores" for="dia">Ver otro día</label>
      <input type="date" id="dia" name="dia" value="<?= $e($dia) ?>"
             max="<?= $e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>">

      <button class="btn btn--primario btn--menor" type="submit">Ver</button>

      <?php /* El botón de «siguiente» no existe cuando ya se está en hoy: no
               hay inscripciones del futuro, y un botón que no lleva a ninguna
               parte sólo sirve para que alguien lo pulse y no pase nada. */ ?>
      <?php if ($diaSiguiente !== null): ?>
        <a class="btn btn--linea btn--menor" href="<?= $url('/?dia=' . $diaSiguiente) ?>" aria-label="Día siguiente">→</a>
      <?php endif; ?>

      <?php if (!$esHoy): ?>
        <a class="enlace-flecha" href="<?= $url('/') ?>">Volver a hoy</a>
      <?php endif; ?>
    </form>

    <?php /* ── Por hora ──────────────────────────────────────────────────
             Las 24 horas siempre, incluidas las vacías. Enseñar sólo las que
             tuvieron actividad juntaría las 8 de la mañana con las 8 de la
             tarde y parecería un goteo constante donde hubo dos picos. */ ?>
    <h3 class="subtitulo">Por hora</h3>

    <?php $topeHora = max(1, max($porHora)); ?>
    <ul class="horas" aria-label="Inscripciones por hora">
      <?php foreach ($porHora as $h => $n): ?>
        <li class="horas__item<?= $n > 0 ? ' con-datos' : '' ?>"
            title="<?= sprintf('%02d:00 – %02d:59 · %d inscripción%s', $h, $h, $n, $n === 1 ? '' : 'es') ?>">
          <span class="horas__vara" style="height: <?= $n > 0 ? max(4, (int) round($n / $topeHora * 100)) : 0 ?>%"></span>
          <?php /* Sólo se rotulan las horas en punto de tres en tres: con las
                   24 escritas no se lee ninguna. */ ?>
          <span class="horas__pie"><?= $h % 3 === 0 ? sprintf('%02d', $h) : '' ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if (array_sum($porHora) === 0): ?>
      <p class="vacio">Ninguna inscripción este día.</p>
    <?php endif; ?>
  </section>

  <?php /* ══════════════════════════════════════════════════════════════════
           DÓNDE Y CON QUIÉN

           Dos columnas de lo mismo: cuántas de este día y cuántas en total.

           Se ordenan por el TOTAL, no por las del día, para que la lista no
           se reordene sola cada vez que entra alguien. Una tabla que cambia
           de orden obliga a buscar otra vez dónde estaba cada zona.
           ══════════════════════════════════════════════════════════════════ */ ?>
  <div class="rejilla-dos">
    <section class="tarjeta">
      <header class="tarjeta__cabecera">
        <h2>Por departamento</h2>
        <span class="tarjeta__nota"><?= count($porDepartamento) ?> con inscripciones</span>
      </header>

      <?php if ($porDepartamento === []): ?>
        <p class="vacio">Todavía no hay inscripciones.</p>
      <?php else: ?>
        <div class="tabla-envoltorio tabla-envoltorio--alta">
          <table class="tabla tabla--compacta">
            <thead>
              <tr>
                <th scope="col">Departamento</th>
                <th scope="col" class="num">Del día</th>
                <th scope="col" class="num">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($porDepartamento as $f): ?>
                <tr<?= (int) $f['del_dia'] > 0 ? ' class="es-activa"' : '' ?>>
                  <td><?= $e($f['nombre']) ?></td>
                  <td class="num"><?= (int) $f['del_dia'] > 0 ? '<strong>' . (int) $f['del_dia'] . '</strong>' : '—' ?></td>
                  <td class="num"><?= (int) $f['total'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="tarjeta">
      <header class="tarjeta__cabecera">
        <h2>Por jurisdicción</h2>
        <span class="tarjeta__nota"><?= count($porJurisdiccion) ?> con inscripciones</span>
      </header>

      <?php if ($porJurisdiccion === []): ?>
        <p class="vacio">Todavía no hay inscripciones.</p>
      <?php else: ?>
        <div class="tabla-envoltorio tabla-envoltorio--alta">
          <table class="tabla tabla--compacta">
            <thead>
              <tr>
                <th scope="col">Jurisdicción</th>
                <th scope="col" class="num">Del día</th>
                <th scope="col" class="num">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($porJurisdiccion as $f): ?>
                <tr<?= (int) $f['del_dia'] > 0 ? ' class="es-activa"' : '' ?>>
                  <td><?= $e($f['nombre']) ?></td>
                  <td class="num"><?= (int) $f['del_dia'] > 0 ? '<strong>' . (int) $f['del_dia'] . '</strong>' : '—' ?></td>
                  <td class="num"><?= (int) $f['total'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Últimas inscripciones</h2>
      <a class="enlace-flecha" href="<?= $url('/voluntarios') ?>">Ver todas →</a>
    </header>

    <?php if ($ultimos === []): ?>
      <p class="vacio">
        Todavía no hay inscripciones. Llegarán aquí en cuanto el formulario de
        <a href="<?= $e($c->urlSitio('/voluntariado/')) ?>" target="_blank" rel="noopener">voluntariado</a>
        esté conectado a la base de datos.
      </p>
    <?php else: ?>
      <div class="tabla-envoltorio">
        <table class="tabla">
          <thead>
            <tr>
              <th scope="col">Código</th>
              <th scope="col">Nombre</th>
              <th scope="col">Jurisdicción</th>
              <th scope="col">Servicio</th>
              <th scope="col">Estado</th>
              <th scope="col">Recibida</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimos as $v): ?>
              <tr>
                <td class="tabla__mono"><?= $e($v['codigo']) ?></td>
                <td><a href="<?= $url('/voluntarios/' . (int) $v['id']) ?>"><?= $e($v['nombres']) ?></a></td>
                <td><?= $e($v['jurisdiccion']) ?></td>
                <td><?= $e(View::recortar($v['servicio'], 28)) ?></td>
                <td><span class="pildora pildora--<?= $e($v['estado']) ?>"><?= $e($etiquetaEstado[$v['estado']] ?? $v['estado']) ?></span></td>
                <td><?= $e(View::fecha($v['creado_en'], true)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($porEstado !== []): ?>
    <section class="tarjeta">
      <header class="tarjeta__cabecera"><h2>Por estado</h2></header>
      <ul class="lista-conteo">
        <?php foreach ($porEstado as $fila): ?>
          <li>
            <span><?= $e($etiquetaEstado[$fila['estado']] ?? $fila['estado']) ?></span>
            <strong><?= (int) $fila['total'] ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

<?php endif; ?>

<?php /* ══════════════════════════════════════════════════════════════════════
         COMUNICADOS

         Cuántas veces se mostró cada aviso y cuántas se pulsó su botón. Los
         retirados siguen aquí: es lo que permite comparar uno con otro y
         decidir qué comunicar la próxima vez.
         ══════════════════════════════════════════════════════════════════════ */ ?>
<?php if (!empty($comunicados)): ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Comunicados</h2>
      <a class="enlace-flecha" href="<?= $url('/comunicados') ?>">Gestionarlos →</a>
    </header>

    <div class="tabla-envoltorio">
      <table class="tabla tabla--compacta">
        <thead>
          <tr>
            <th scope="col">Comunicado</th>
            <th scope="col">Estado</th>
            <th scope="col" class="num">Vistas</th>
            <th scope="col" class="num">Clics</th>
            <th scope="col" class="num">Tasa</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($comunicados as $cm): ?>
            <?php
              $v = (int) $cm['vistas'];
              $cl = (int) $cm['clics'];
            ?>
            <tr<?= (int) $cm['vigente'] === 1 ? ' class="es-activa"' : '' ?>>
              <td><?= $e($cm['nombre']) ?></td>
              <td>
                <?php if ((int) $cm['vigente'] === 1): ?>
                  <span class="pildora pildora--nuevo">En la web</span>
                <?php else: ?>
                  <span class="mascara">Retirado</span>
                <?php endif; ?>
              </td>
              <td class="num"><?= number_format($v, 0, ',', '.') ?></td>
              <td class="num"><strong><?= number_format($cl, 0, ',', '.') ?></strong></td>
              <?php /* La tasa es lo que da sentido a las otras dos: 40 clics de
                       900 vistas y 40 de 60 son cosas muy distintas. */ ?>
              <td class="num"><?= $v === 0 ? '—' : round($cl / $v * 100, 1) . ' %' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php /* Este bloque decía que el módulo de voluntarios, el CMS y la conexión
         del formulario estaban «pendientes» cuando llevaban semanas
         funcionando —de hecho, las 105 inscripciones de arriba entraron por
         ahí—. Un panel que informa mal de sí mismo no es un panel: es una
         nota vieja pegada en la pared. */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Estado de la instalación</h2></header>
  <ul class="lista-estado">
    <li><span class="ok">✓</span> Núcleo MVC, conexión PDO y enrutador operativos.</li>
    <li><span class="ok">✓</span> Autenticación, roles y permisos activos.</li>
    <li><span class="ok">✓</span> Módulo de voluntarios, con filtros y ficha.</li>
    <li><span class="ok">✓</span> Formulario público conectado a la base de datos.</li>
    <li><span class="ok">✓</span> CMS de páginas: voluntariado editable desde «Páginas».</li>
    <li><span class="pendiente">·</span> Las demás páginas del sitio, todavía fuera del gestor.</li>
    <li><span class="pendiente">·</span> Texto legal del consentimiento, pendiente de confirmar.</li>
  </ul>
</section>
