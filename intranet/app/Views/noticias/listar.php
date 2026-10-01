<?php
/**
 * Noticias · el listado del gestor.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array  $listado
 * @var string $estado
 * @var int    $aproximadas
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$puedeEditar = $auth->puede('paginas.editar');

$dia = static function (?string $iso): string {
    if ($iso === null || $iso === '') {
        return '—';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    [$a, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' de ' . ($meses[$m] ?? '?') . ' de ' . $a;
};
?>

<header class="encabezado">
  <h1>Noticias</h1>
  <p class="encabezado__pie">
    Se publican en <a href="<?= $e($c->urlSitio('/noticias/')) ?>" target="_blank" rel="noopener">/noticias/</a>,
    de la más reciente a la más antigua. La fecha es la que manda el orden,
    no el sitio en que aparezcan aquí.
  </p>
</header>

<?php if ($aproximadas > 0): ?>
  <?php /* Las que se migraron de un texto sin día («Agosto de 2026»). Se
           avisa porque su fecha es inventada —el día 1— y eso decide dónde
           salen en la portada. */ ?>
  <p class="aviso aviso--ojo">
    <strong><?= (int) $aproximadas ?></strong>
    noticia<?= $aproximadas === 1 ? '' : 's' ?>
    tiene<?= $aproximadas === 1 ? '' : 'n' ?> una fecha aproximada:
    venían de un texto que sólo decía el mes, así que quedaron el día 1.
    Están marcadas abajo. Corrígelas cuando sepas el día: la fecha decide el orden.
  </p>
<?php endif; ?>

<?php if ($puedeEditar): ?>
  <p><a class="btn btn--primario" href="<?= $url('/noticias/nueva') ?>">Escribir una noticia</a></p>
<?php endif; ?>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Publicaciones</h2>
    <span class="tarjeta__nota"><?= (int) $listado['total'] ?></span>
  </header>

  <form method="get" action="<?= $url('/noticias') ?>" class="filtros sep-m">
    <label class="campo__etiqueta" for="estado">Mostrar</label>
    <select id="estado" name="estado" onchange="this.form.submit()">
      <option value=""           <?= $estado === ''           ? 'selected' : '' ?>>Todas</option>
      <option value="publicada"  <?= $estado === 'publicada'  ? 'selected' : '' ?>>Sólo publicadas</option>
      <option value="borrador"   <?= $estado === 'borrador'   ? 'selected' : '' ?>>Sólo borradores</option>
    </select>
    <noscript><button class="btn btn--mini" type="submit">Filtrar</button></noscript>
  </form>

  <?php if ($listado['filas'] === []): ?>
    <p class="vacio">
      No hay noticias<?= $estado !== '' ? ' con ese estado' : '' ?>.
      <?= $puedeEditar ? 'Escribe la primera con el botón de arriba.' : '' ?>
    </p>
  <?php else: ?>
    <table class="tabla">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Titular</th>
          <th>Estado</th>
          <th><span class="visualmente-oculto">Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($listado['filas'] as $n): ?>
          <tr>
            <td class="tabla__fecha">
              <?= $e($dia($n['fecha'])) ?>
              <?php if ((int) $n['fecha_aproximada'] === 1): ?>
                <span class="etiqueta etiqueta--aviso" title="Venía de un texto que sólo decía el mes. Quedó el día 1.">aproximada</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if ($puedeEditar): ?>
                <a href="<?= $url('/noticias/' . (int) $n['id']) ?>"><strong><?= $e($n['titulo']) ?></strong></a>
              <?php else: ?>
                <strong><?= $e($n['titulo']) ?></strong>
              <?php endif; ?>

              <?php if ((int) $n['destacada'] === 1): ?>
                <span class="etiqueta etiqueta--ok">Destacada</span>
              <?php endif; ?>

              <span class="tabla__nota">/noticias/<?= $e($n['slug']) ?>/</span>
            </td>

            <td>
              <?php if ($n['estado'] === 'publicada'): ?>
                <span class="etiqueta etiqueta--ok">Publicada</span>
              <?php else: ?>
                <span class="etiqueta">Borrador</span>
              <?php endif; ?>
            </td>

            <td class="tabla__acciones">
              <?php if ($n['estado'] === 'publicada'): ?>
                <a class="btn btn--mini" target="_blank" rel="noopener"
                   href="<?= $e($c->urlSitio('/noticias/' . $n['slug'] . '/')) ?>">Ver</a>
              <?php endif; ?>
              <?php if ($puedeEditar): ?>
                <a class="btn btn--mini" href="<?= $url('/noticias/' . (int) $n['id']) ?>">Editar</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ((int) $listado['paginas'] > 1): ?>
      <nav class="paginacion sep-m" aria-label="Páginas del listado">
        <?php for ($p = 1; $p <= (int) $listado['paginas']; $p++): ?>
          <?php $q = ['pagina' => $p] + ($estado !== '' ? ['estado' => $estado] : []); ?>
          <a class="paginacion__num<?= $p === (int) $listado['pagina'] ? ' is-activa' : '' ?>"
             href="<?= $url('/noticias?' . http_build_query($q)) ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<section class="tarjeta">
  <h2 class="tarjeta__titulo">Vídeos</h2>
  <p class="campo__ayuda">
    La cuadrícula que va debajo de las noticias. Se pega el enlace de YouTube
    y el servidor trae solo el título y la portada.
  </p>
  <p><a class="btn" href="<?= $url('/noticias/videos') ?>">Administrar los vídeos</a></p>
</section>
