<?php
/**
 * Una fila del listado de comunicados. La usan las dos secciones —publicados e
 * historial— para que un retirado se lea exactamente igual que uno activo,
 * salvo por su estado.
 *
 * @var array $x        el comunicado
 * @var callable $e     escapar
 * @var callable $url   ruta del panel
 * @var callable $tasa  porcentaje de clics
 * @var callable $motivo por qué no está saliendo
 * @var \Intranet\Core\Auth $auth
 * @var \Intranet\Core\Csrf $csrf
 */

use Intranet\Core\View;

$puedeEditar = $auth->puede('comunicados.editar');
$esVigente   = (int) $x['vigente'] === 1;
?>
<article class="comunicado<?= $esVigente ? ' es-vigente' : '' ?>">

  <?php if (!empty($x['imagen'])): ?>
    <img class="comunicado__miniatura"
         src="<?= $e($c->urlSitio('/' . ltrim((string) $x['imagen'], '/'))) ?>"
         alt="" width="96" height="64" loading="lazy">
  <?php else: ?>
    <span class="comunicado__miniatura comunicado__miniatura--vacia" aria-hidden="true">—</span>
  <?php endif; ?>

  <div class="comunicado__cuerpo">
    <h3 class="comunicado__nombre">
      <?php if ($puedeEditar): ?>
        <a href="<?= $url('/comunicados/' . (int) $x['id']) ?>"><?= $e($x['nombre']) ?></a>
      <?php else: ?>
        <?= $e($x['nombre']) ?>
      <?php endif; ?>
    </h3>

    <p class="comunicado__meta">
      <?php if ($esVigente): ?>
        <span class="pildora pildora--nuevo">En la web</span>
      <?php else: ?>
        <span class="pildora pildora--baja"><?= $e($motivo($x)) ?></span>
      <?php endif; ?>

      <span class="mascara">
        Botón «<?= $e($x['boton_texto']) ?>»
        · <?= $x['boton_tipo'] === 'descarga' ? 'descarga un archivo' : 'abre un enlace' ?>
        <?php if (!empty($x['expira_en']) && $esVigente): ?>
          · caduca el <?= $e(View::fecha($x['expira_en'])) ?>
        <?php endif; ?>
      </span>
    </p>
  </div>

  <?php /* ── Las cifras ───────────────────────────────────────────────────
           Los clics al lado de las vistas, y el porcentaje debajo. Un botón
           con 40 clics no dice nada solo: con 40 de 900 sí, y con 40 de 60
           dice algo muy distinto. */ ?>
  <dl class="comunicado__cifras">
    <div>
      <dt>Vistas</dt>
      <dd><?= number_format((int) $x['vistas'], 0, ',', '.') ?></dd>
    </div>
    <div>
      <dt><?= $x['boton_tipo'] === 'descarga' ? 'Descargas' : 'Clics' ?></dt>
      <dd class="es-destacada"><?= number_format((int) $x['clics'], 0, ',', '.') ?></dd>
    </div>
    <div>
      <dt>Tasa</dt>
      <dd><?= $e($tasa($x)) ?></dd>
    </div>
  </dl>

  <?php if ($puedeEditar): ?>
    <form class="comunicado__accion" method="post"
          action="<?= $url('/comunicados/' . (int) $x['id'] . '/alternar') ?>">
      <?= $csrf->campo() ?>
      <button class="btn btn--linea btn--menor" type="submit">
        <?= (int) $x['activo'] === 1 ? 'Retirar' : 'Publicar' ?>
      </button>
    </form>
  <?php endif; ?>
</article>
