<?php
/**
 * Una copia guardada de una sección: cómo estaba antes de un cambio.
 *
 * Se mira antes de deshacer, para no deshacer a ciegas. Es sólo lectura: no
 * hay un campo que tocar en ninguna parte, porque editar aquí daría a
 * entender que se puede corregir el pasado.
 *
 * Las imágenes se enseñan, no sus números: «imagen_id 47» no le dice nada a
 * nadie, y la pregunta que trae aquí a cualquiera es si la foto de entonces
 * era la buena.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $seccion   cómo está AHORA
 * @var array $plantilla
 * @var array $version   la copia, con `contenido` desempaquetado
 * @var array $cambios   qué ha cambiado desde entonces hasta ahora
 * @var array $medios    la biblioteca, para enseñar las fotos
 */

use Intranet\Cms\Plantillas;
use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$enSec    = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];
$entonces = $version['contenido'];

/* La biblioteca indexada por id: la copia guarda números y aquí hace falta la
   ruta. Una imagen borrada desde entonces no estará, y se dice. */
$porId = [];

foreach ($medios as $mV) {
    $porId[(int) $mV['id']] = $mV;
}

/** Enseña una imagen de la copia, o explica por qué no se puede. */
$laFoto = static function (mixed $id) use ($porId, $c, $e): void {
    $id = (int) $id;

    if ($id === 0) {
        echo '<span class="vacio">Sin imagen</span>';

        return;
    }

    if (!isset($porId[$id])) {
        echo '<span class="vacio">La imagen que tenía ya no está en la biblioteca.</span>';

        return;
    }

    $m = $porId[$id];
    printf(
        '<img class="version__foto" src="%s" alt="" loading="lazy" decoding="async" width="120" height="120"><span class="version__nombre">%s</span>',
        $e($c->urlSitio('/' . ltrim((string) $m['ruta'], '/'))),
        $e($m['nombre_archivo'])
    );
};

/** Un valor de texto, o el hueco si estaba vacío. */
$elTexto = static function (mixed $v) use ($e): void {
    $v = trim((string) $v);

    echo $v === ''
        ? '<span class="vacio">— vacío —</span>'
        : nl2br($e($v));
};
?>

<header class="encabezado">
  <p class="rotulo">
    <a href="<?= $url('/paginas') ?>">Páginas</a> ·
    <a href="<?= $url('/paginas/' . $pagina['clave']) ?>"><?= $e($pagina['nombre']) ?></a> ·
    <a href="<?= $url($enSec) ?>"><?= $e($seccion['nombre']) ?></a> ·
    <a href="<?= $url($enSec . '/historial') ?>">Historial</a>
  </p>

  <h1>Cómo estaba antes de este cambio</h1>

  <p class="encabezado__pie">
    Guardado el <?= $e(View::fecha($version['creado_en'], true)) ?>
    por <?= $e($version['usuario'] ?? 'un usuario que ya no está') ?>.
    Esta pantalla es sólo para mirar.
  </p>
</header>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Qué ha cambiado desde entonces</h2>
  </header>

  <ul class="historial__cambios">
    <?php foreach ($cambios as $q): ?>
      <li><?= $e($q) ?></li>
    <?php endforeach; ?>
  </ul>

  <?php /* Un <div>, no un <p>: dentro va un <form>, y un párrafo sólo admite
           contenido de texto. El navegador cerraba el <p> antes de abrir el
           formulario y los dos botones acababan en líneas distintas. */ ?>
  <div class="barra-guardar barra-guardar--suelta">
    <a class="btn btn--linea" href="<?= $url($enSec . '/historial') ?>">Volver al historial</a>

    <form method="post"
          action="<?= $url($enSec . '/historial/' . (int) $version['id'] . '/restaurar') ?>"
          data-confirmar="¿Dejar la sección como estaba aquí? Lo que hay ahora se guardará como una copia más, así que esto también se puede deshacer.">
      <?= $csrf->campo() ?>
      <button class="btn btn--primario" type="submit">Dejarla como estaba aquí</button>
    </form>
  </div>
</section>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Los textos de la sección</h2>
    <span class="pildora<?= empty($entonces['activa']) ? ' pildora--baja' : '' ?>">
      <?= empty($entonces['activa']) ? 'Estaba oculta' : 'Estaba visible' ?>
    </span>
  </header>

  <dl class="version__campos">
    <?php foreach (['rotulo' => 'Rótulo', 'titulo' => 'Título', 'subtitulo' => 'Subtítulo',
                    'texto' => 'Texto', 'cta_texto' => 'Texto del botón',
                    'cta_url' => 'Enlace del botón'] as $columna => $comoSeLlama): ?>
      <?php if (($entonces[$columna] ?? null) === null && ($seccion[$columna] ?? null) === null) { continue; } ?>
      <div class="version__campo<?= (string) ($entonces[$columna] ?? '') !== (string) ($seccion[$columna] ?? '') ? ' es-distinto' : '' ?>">
        <dt><?= $e($comoSeLlama) ?></dt>
        <dd><?php $elTexto($entonces[$columna] ?? ''); ?></dd>
      </div>
    <?php endforeach; ?>

    <?php foreach (['imagen_id' => 'Imagen', 'imagen_movil_id' => 'Imagen para móvil'] as $columna => $comoSeLlama): ?>
      <?php if (empty($entonces[$columna]) && empty($seccion[$columna])) { continue; } ?>
      <div class="version__campo<?= (int) ($entonces[$columna] ?? 0) !== (int) ($seccion[$columna] ?? 0) ? ' es-distinto' : '' ?>">
        <dt><?= $e($comoSeLlama) ?></dt>
        <dd><?php $laFoto($entonces[$columna] ?? 0); ?></dd>
      </div>
    <?php endforeach; ?>
  </dl>

  <?php if (($entonces['datos'] ?? []) !== []): ?>
    <h3 class="version__subtitulo">Otros textos</h3>
    <dl class="version__campos">
      <?php foreach ($entonces['datos'] as $clave => $valor): ?>
        <div class="version__campo">
          <dt><?= $e($plantilla['datos'][$clave]['etiqueta'] ?? $clave) ?></dt>
          <dd><?php $elTexto(is_array($valor) ? implode("\n", $valor) : $valor); ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  <?php endif; ?>
</section>

<?php $piezas = $entonces['bloques'] ?? []; ?>
<?php if ($piezas !== []): ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2><?= count($piezas) === 1 ? '1 ficha' : count($piezas) . ' fichas' ?></h2>
    </header>

    <?php /* Las que ya no existen se marcan: son las que volverían a aparecer
             al deshacer, y suele ser justo lo que se viene a buscar. */ ?>
    <?php
    $vivas = [];

    foreach ($seccion['bloques'] as $bV) {
        $vivas[(int) $bV['id']] = true;
    }
    ?>

    <ul class="piezas">
      <?php foreach ($piezas as $nV => $bV): ?>
        <?php $idV = (int) $bV['id']; ?>
        <li class="pieza-fila<?= empty($bV['activo']) ? ' es-oculta' : '' ?>">
          <span class="pieza-fila__num"><?= $nV + 1 ?></span>

          <span class="pieza-fila__foto">
            <?php if (!empty($bV['imagen_id']) && isset($porId[(int) $bV['imagen_id']])): ?>
              <img src="<?= $e($c->urlSitio('/' . ltrim((string) $porId[(int) $bV['imagen_id']]['ruta'], '/'))) ?>"
                   alt="" loading="lazy" decoding="async" width="64" height="64">
            <?php else: ?>
              <span class="pieza-fila__sinfoto" aria-hidden="true">—</span>
            <?php endif; ?>
          </span>

          <span class="pieza-fila__texto">
            <span class="pieza-fila__titulo"><?= $e($bV['titulo'] ?: ($bV['rotulo'] ?: 'Ficha sin título')) ?></span>
            <span class="pieza-fila__pie">
              <?php if (!isset($vivas[$idV])): ?>
                <strong>Ya no existe: volvería a aparecer</strong> ·
              <?php elseif (empty($bV['activo'])): ?>
                <strong>Estaba oculta</strong> ·
              <?php endif; ?>
              <?= $e(mb_strimwidth(trim((string) ($bV['texto'] ?? '')), 0, 70, '…')) ?>
            </span>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
