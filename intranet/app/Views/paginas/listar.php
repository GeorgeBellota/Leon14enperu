<?php
/**
 * La lista de páginas del sitio, en dos niveles.
 *
 * ── Por qué dos y no una tabla de veinticuatro filas ─────────────────────
 *
 * Porque no todas son lo mismo. «Contacto» es una página. «Sedes» no: es una
 * colección que contiene cuatro sedes, y cada una tiene su propia dirección
 * —/sedes/chiclayo/— y su propio contenido. Puestas en la misma lista, las
 * cuatro sedes no aparecían por ningún lado y no había forma de adivinar
 * dónde se editaban.
 *
 * Arriba, las colecciones con sus piezas colgando. Abajo, las páginas
 * sueltas. El mismo sitio sigue teniendo veinticuatro entradas: lo que cambia
 * es que ahora se ve cuál contiene a cuál.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $paginas
 * @var array $colecciones  piezas con página propia, por clave de página
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/* Se reparten las páginas en los dos grupos. Una página es colección si
   alguna de sus piezas tiene dirección propia; eso lo decide la base, no una
   lista escrita aquí, así que una colección nueva aparece sola. */
$deColeccion = [];
$sueltas     = [];

foreach ($paginas as $p) {
    if (isset($colecciones[$p['clave']])) {
        $deColeccion[] = $p;
    } else {
        $sueltas[] = $p;
    }
}

/* El interruptor de publicar/ocultar, que se repite en los dos grupos. */
$publicar = static function (array $p) use ($auth, $csrf, $url, $e): void {
    if (!$auth->puede('paginas.publicar')) {
        return;
    }
    ?>
    <form method="post" class="en-linea"
          action="<?= $url('/paginas/' . $p['clave'] . '/publicar') ?>"
          data-confirmar="<?= (int) $p['activa'] === 1
              ? '¿Ocultar esta página? Dejará de verse en la web y responderá «no encontrada».'
              : '¿Publicar esta página? Quedará visible para cualquiera.' ?>">
      <?= $csrf->campo() ?>
      <button class="btn btn--plano" type="submit"><?= (int) $p['activa'] === 1 ? 'Ocultar' : 'Publicar' ?></button>
    </form>
    <?php
};

$estado = static function (array $p) use ($e): void {
    ?>
    <?php if ((int) $p['activa'] === 1): ?>
      <span class="pildora pildora--acreditado">Publicada</span>
    <?php else: ?>
      <span class="pildora pildora--baja">Oculta</span>
    <?php endif; ?>
    <?php
};
?>

<header class="encabezado">
  <p class="rotulo">Contenidos</p>
  <h1>Páginas</h1>
  <p class="encabezado__pie">
    <?= count($paginas) ?> páginas, <?= count($deColeccion) ?> de ellas colecciones.
    Publicar una página la hace visible; oculta responde «no encontrada» aunque
    alguien tenga la dirección.
  </p>
</header>

<?php /* ══ COLECCIONES ══════════════════════════════════════════════════ */ ?>
<?php if ($deColeccion !== []): ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Colecciones</h2>
    </header>

    <p class="vacio">
      Cada una tiene su propia página —el listado— y, dentro, fichas con
      dirección propia. Editar la ficha de Chiclayo no cambia el listado de
      sedes, y al revés.
    </p>

    <div class="colecciones">
      <?php foreach ($deColeccion as $p): ?>
        <?php
        $piezas = $colecciones[$p['clave']];
        $cuenta = count($piezas);
        ?>
        <article class="coleccion">

          <?php /* La cabecera: a la izquierda quién es, a la derecha qué se
                   puede hacer. En dos columnas de verdad, no en una fila que
                   se parte: con «Editar el listado · Ver ↗ · Ocultar» seguidos
                   en línea, el botón caía solo a la línea de abajo y la ficha
                   se leía como tres cosas sueltas. */ ?>
          <header class="coleccion__cabecera">
            <div class="coleccion__quien">
              <h3 class="coleccion__titulo">
                <a href="<?= $url('/paginas/' . $p['clave']) ?>"><?= $e($p['nombre']) ?></a>
                <?php $estado($p); ?>
              </h3>
              <p class="coleccion__meta">
                <span class="tabla__mono"><?= $e($p['ruta']) ?></span>
                <span class="coleccion__punto">·</span>
                <?= (int) $p['secciones'] ?> <?= (int) $p['secciones'] === 1 ? 'sección' : 'secciones' ?>
              </p>
            </div>

            <div class="coleccion__mandos">
              <a class="btn btn--linea" href="<?= $url('/paginas/' . $p['clave']) ?>">Editar</a>
              <a class="btn btn--plano" href="<?= $e($c->urlSitio($p['ruta'])) ?>"
                 target="_blank" rel="noopener">Ver ↗</a>
              <?php $publicar($p); ?>
            </div>
          </header>

          <?php /* Las colecciones cortas se ven de golpe; las largas empiezan
                   plegadas. Con las trece comisiones desplegadas, esta pantalla
                   medía tres pantallazos y volvía a ser el problema que venía a
                   resolver. Seis es donde deja de leerse de una ojeada. */ ?>
          <details class="fichas"<?= $cuenta <= 6 ? ' open' : '' ?>>
            <summary class="fichas__resumen">
              <span class="fichas__flecha" aria-hidden="true"></span>
              <span class="fichas__cuenta"><?= $cuenta ?></span>
              <?= $cuenta === 1 ? 'ficha con página propia' : 'fichas con página propia' ?>
            </summary>

            <ul class="fichas__lista">
              <?php foreach ($piezas as $f): ?>
                <?php
                /* La ficha se edita dentro del formulario de su sección; el
                   ancla lleva directamente a su bloque en lugar de dejar a
                   nadie buscando entre trece formularios iguales. */
                $editar = '/paginas/' . $p['clave'] . '/' . $f['seccion'] . '#pieza-' . $f['slug'];
                $ver    = rtrim((string) $p['ruta'], '/') . '/' . $f['slug'] . '/';
                ?>
                <li class="ficha<?= (int) $f['activo'] === 1 ? '' : ' ficha--apagada' ?>">
                  <a class="ficha__principal" href="<?= $url($editar) ?>">
                    <span class="ficha__nombre"><?= $e($f['titulo']) ?></span>
                    <?php if (($f['rotulo'] ?? '') !== ''): ?>
                      <span class="ficha__rotulo"><?= $e(View::recortar((string) $f['rotulo'], 56)) ?></span>
                    <?php endif; ?>
                    <span class="ficha__ruta tabla__mono"><?= $e($ver) ?></span>
                  </a>
                  <span class="ficha__mandos">
                    <?php if ((int) $f['activo'] !== 1): ?>
                      <span class="pildora pildora--baja">Oculta</span>
                    <?php endif; ?>
                    <a class="btn btn--plano" href="<?= $e($c->urlSitio($ver)) ?>"
                       target="_blank" rel="noopener"
                       aria-label="Ver «<?= $e($f['titulo']) ?>» en la web">Ver ↗</a>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          </details>

        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php /* ══ PÁGINAS SUELTAS ══════════════════════════════════════════════ */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Páginas</h2></header>

  <div class="tabla-envoltorio">
    <table class="tabla">
      <thead>
        <tr>
          <th scope="col">Página</th>
          <th scope="col">Estado</th>
          <th scope="col">Ruta</th>
          <th scope="col">Secciones</th>
          <th scope="col">Último cambio</th>
          <th scope="col"><span class="solo-lectores">Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sueltas as $p): ?>
          <tr>
            <td><a href="<?= $url('/paginas/' . $p['clave']) ?>"><strong><?= $e($p['nombre']) ?></strong></a></td>
            <td><?php $estado($p); ?></td>
            <td class="tabla__mono"><?= $e($p['ruta']) ?></td>
            <td><?= (int) $p['secciones'] ?></td>
            <td>
              <?= $e(View::fecha($p['actualizado_en'], true)) ?>
              <?php if ($p['editor']): ?><br><span class="mascara"><?= $e($p['editor']) ?></span><?php endif; ?>
            </td>
            <td>
              <a class="enlace-flecha" href="<?= $url('/paginas/' . $p['clave']) ?>">Editar</a>
              &nbsp;·&nbsp;
              <a class="enlace-flecha" href="<?= $e($c->urlSitio($p['ruta'])) ?>" target="_blank" rel="noopener">Ver ↗</a>
              <?php if ($auth->puede('paginas.publicar')): ?>
                &nbsp;·&nbsp;<?php $publicar($p); ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Cómo funciona</h2></header>
  <p>Cada página se divide en <strong>secciones</strong>, y cada sección puede tener
     <strong>fichas</strong> repetibles: los pasos, las tarjetas, las sedes. Al guardar se
     conserva una copia del estado anterior, así que un error de edición no obliga a
     reescribir el texto de memoria.</p>
  <p>En una <strong>colección</strong>, cada ficha tiene además su propia dirección. La
     dirección se calcula del título la primera vez y luego no vuelve a cambiar sola:
     si se cambiara, los enlaces que ya se hayan compartido dejarían de funcionar.</p>
  <p class="vacio">Los seis servicios no se editan como fichas: viven en el catálogo de
     servicios, que es también el que llena el desplegable del formulario de inscripción.
     Así la tarjeta y la opción del desplegable no pueden acabar diciendo cosas distintas.
     Su pantalla de edición está pendiente.</p>
</section>
