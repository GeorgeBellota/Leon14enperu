<?php
/**
 * El historial de una sección.
 *
 * Cada «Guardar» deja en `secciones_versiones` una copia de cómo estaba la
 * sección ANTES del cambio, y se conservan las diez últimas. Eso pasaba desde
 * el primer día; lo que no había era forma de verlo ni de volver atrás.
 *
 * ── La cuenta, que es lo único que puede confundir ───────────────────────
 *
 * La copia fechada el martes no es «lo que se guardó el martes»: es «cómo
 * estaba justo antes de guardar el martes». Por eso cada fila se lee como
 * «fulano cambió esto», y el botón dice «Deshacer este cambio» y no
 * «Restaurar esta versión»: lo segundo invita a pensar que recupera lo que se
 * guardó ese día, que es exactamente lo contrario.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $seccion
 * @var array $plantilla
 * @var array $versiones  de la más reciente a la más antigua, con sus cambios
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$enSec = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];
?>

<header class="encabezado">
  <p class="rotulo">
    <a href="<?= $url('/paginas') ?>">Páginas</a> ·
    <a href="<?= $url('/paginas/' . $pagina['clave']) ?>"><?= $e($pagina['nombre']) ?></a> ·
    <a href="<?= $url($enSec) ?>"><?= $e($seccion['nombre']) ?></a>
  </p>

  <h1>Historial</h1>

  <p class="encabezado__pie">
    Se guarda una copia en cada «Guardar» y se conservan las diez últimas.
    Deshacer un cambio también se puede deshacer.
  </p>
</header>

<?php if ($versiones === []): ?>
  <section class="tarjeta">
    <p class="vacio">
      Esta sección no se ha editado todavía desde que se instaló, así que no
      hay nada que deshacer.
    </p>
    <p><a class="btn btn--linea" href="<?= $url($enSec) ?>">Volver a la sección</a></p>
  </section>
<?php else: ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2><?= count($versiones) === 1 ? '1 cambio guardado' : count($versiones) . ' cambios guardados' ?></h2>
      <a class="btn btn--linea" href="<?= $url($enSec) ?>">Volver a la sección</a>
    </header>

    <ol class="historial">
      <?php foreach ($versiones as $n => $v): ?>
        <li class="historial__fila">
          <span class="historial__hito" aria-hidden="true"></span>

          <div class="historial__cuerpo">
            <p class="historial__cuando">
              <strong><?= $e(View::fecha($v['creado_en'], true)) ?></strong>
              <?php /* Un usuario borrado deja su clave ajena a null: la copia
                       sigue valiendo, sólo que no se sabe de quién fue. */ ?>
              · <?= $e($v['usuario'] ?? 'un usuario que ya no está') ?>
              <?php if ($n === 0): ?>
                <span class="pildora">el último</span>
              <?php endif; ?>
            </p>

            <ul class="historial__cambios">
              <?php foreach ($v['cambios'] as $q): ?>
                <li><?= $e($q) ?></li>
              <?php endforeach; ?>
            </ul>

            <p class="historial__pie">
              <?= (int) $v['piezas'] === 1 ? '1 ficha' : (int) $v['piezas'] . ' fichas' ?>
              antes de este cambio
            </p>
          </div>

          <div class="historial__mandos">
            <a class="btn btn--mini btn--linea" href="<?= $url($enSec . '/historial/' . (int) $v['id']) ?>">
              Ver cómo estaba
            </a>

            <?php /* Formulario propio y confirmación: deshacer reescribe la
                     sección entera y lo que hay ahora se va de la pantalla,
                     aunque quede guardado. */ ?>
            <form method="post"
                  action="<?= $url($enSec . '/historial/' . (int) $v['id'] . '/restaurar') ?>"
                  data-confirmar="¿Dejar la sección como estaba antes de este cambio? Lo que hay ahora se guardará como una copia más, así que esto también se puede deshacer.">
              <?= $csrf->campo() ?>
              <button class="btn btn--mini" type="submit">Deshacer este cambio</button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
<?php endif; ?>
