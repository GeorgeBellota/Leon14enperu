<?php
/**
 * Multimedia · las actividades.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $actividades
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$puedeEditar = $auth->puede('medios.subir');

/** «14 de noviembre de 2026» se lee mejor que «2026-11-14». */
$dia = static function (?string $iso): string {
    if ($iso === null || $iso === '') {
        return 'Sin fecha';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    [$a, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' de ' . ($meses[$m] ?? '?') . ' de ' . $a;
};
?>

<header class="encabezado">
  <h1>Multimedia</h1>
  <p class="encabezado__pie">
    La galería de la página <a href="<?= $e($c->urlSitio('/multimedia/')) ?>" target="_blank" rel="noopener">/multimedia/</a>.
    Las fotografías se agrupan por <strong>actividad</strong> y, dentro de cada una, por
    <strong>fecha</strong>. Una misma actividad puede tener varios días.
  </p>
</header>

<?php if ($puedeEditar): ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Nueva actividad</h2>

    <form method="post" action="<?= $url('/galeria') ?>">
      <?= $csrf->campo() ?>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="160" required
               placeholder="Conferencia Episcopal Peruana">
        <p class="campo__ayuda">
          Es el titular que se lee en la página. Una actividad agrupa todas sus
          fotografías, de todos los días en que ocurrió.
        </p>
      </div>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="rotulo">Rótulo</label>
        <input type="text" id="rotulo" name="rotulo" maxlength="80" value="Actividades">
        <p class="campo__ayuda">La línea pequeña en dorado que va encima del titular.</p>
      </div>

      <p><button class="btn btn--primario" type="submit">Crear actividad</button></p>
    </form>
  </section>
<?php endif; ?>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Actividades</h2>
    <span class="tarjeta__nota"><?= count($actividades) ?></span>
  </header>

  <?php if ($actividades === []): ?>
    <p class="vacio">
      Todavía no hay ninguna actividad.
      <?= $puedeEditar ? 'Crea la primera arriba.' : '' ?>
    </p>
  <?php else: ?>
    <table class="tabla">
      <thead>
        <tr>
          <th>Actividad</th>
          <th>Fotografías</th>
          <th>Fechas</th>
          <th>Estado</th>
          <th><span class="visualmente-oculto">Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($actividades as $i => $a): ?>
          <tr>
            <td>
              <a href="<?= $url('/galeria/' . (int) $a['id']) ?>"><strong><?= $e($a['nombre']) ?></strong></a>
              <span class="tabla__nota"><?= $e($a['rotulo']) ?></span>
            </td>
            <td><?= (int) $a['fotos'] ?></td>
            <td><?= (int) $a['fechas'] ?: '—' ?></td>
            <td>
              <?php if ((int) $a['activa'] === 1): ?>
                <span class="etiqueta etiqueta--ok">Visible</span>
              <?php else: ?>
                <span class="etiqueta">Oculta</span>
              <?php endif; ?>
            </td>
            <td class="tabla__acciones">
              <?php if ($puedeEditar): ?>
                <?php /* Subir y bajar van en formularios porque cambian datos:
                         un enlace GET que reordena se dispara solo con que
                         alguien precargue la página. */ ?>
                <?php foreach ([['subir', '↑', 'Subir'], ['bajar', '↓', 'Bajar']] as [$dir, $icono, $titulo]): ?>
                  <?php if (($dir === 'subir' && $i > 0) || ($dir === 'bajar' && $i < count($actividades) - 1)): ?>
                    <form method="post" action="<?= $url('/galeria/' . (int) $a['id'] . '/orden') ?>" class="en-linea">
                      <?= $csrf->campo() ?>
                      <input type="hidden" name="direccion" value="<?= $dir ?>">
                      <button class="mando-mini" type="submit" aria-label="<?= $titulo ?> «<?= $e($a['nombre']) ?>»"><?= $icono ?></button>
                    </form>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endif; ?>
              <a class="btn btn--mini" href="<?= $url('/galeria/' . (int) $a['id']) ?>">Abrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php /* ── Dónde se enciende en la web ──────────────────────────────────────
         Se dice aquí porque es la pregunta que sigue a «ya tengo las fotos
         subidas, por qué no se ve». La página existe aunque no esté en el
         menú: lo que decide Configuración es si aparece el enlace. */ ?>
<section class="tarjeta">
  <h2 class="tarjeta__titulo">Para que se vea en la web</h2>
  <p class="campo__ayuda">
    La galería se publica en <a href="<?= $e($c->urlSitio('/multimedia/')) ?>" target="_blank" rel="noopener">/multimedia/</a>
    en cuanto una actividad tenga fotografías y esté visible.
  </p>
  <p class="campo__ayuda">
    Para que además salga <strong>en el menú del sitio</strong>, márcala en
    <a href="<?= $url('/configuracion') ?>">Configuración → Menú principal</a>.
    Sin marcarla, la página sigue existiendo y se puede enlazar a mano, pero no
    aparece en la navegación.
  </p>
</section>
