<?php
/**
 * Ficha de una inscripción.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array      $v          la inscripción
 * @var array|null $revelado   datos en claro, sólo si se pidieron y hay permiso
 * @var array      $historial
 * @var array      $estados
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$puedeSensible = $auth->puede('voluntarios.datos_sensibles');
$puedeEditar   = $auth->puede('voluntarios.editar');
?>

<header class="encabezado">
  <p class="rotulo"><a href="<?= $url('/voluntarios') ?>">Inscripciones</a></p>
  <h1><?= $e($v['nombres']) ?></h1>
  <p class="encabezado__pie">
    <span class="tabla__mono"><?= $e($v['codigo']) ?></span> ·
    <span class="pildora pildora--<?= $e($v['estado']) ?>"><?= $e($estados[$v['estado']] ?? $v['estado']) ?></span> ·
    Fase <?= (int) $v['fase'] ?> de 3
    <?php if ($v['borrado_en']): ?>
      · <strong>Dada de baja el <?= $e(View::fecha($v['borrado_en'], true)) ?></strong>
    <?php endif; ?>
  </p>
</header>

<div class="ficha">
  <div>

    <section class="tarjeta">
      <header class="tarjeta__cabecera"><h2>Datos de la inscripción</h2></header>

      <dl class="datos-lista">
        <dt>Nombre</dt><dd><?= $e($v['nombres']) ?></dd>
        <dt>Nacimiento</dt><dd><?= $e(View::fecha($v['nacimiento'])) ?></dd>
        <dt>Correo</dt><dd><a href="mailto:<?= $e($v['correo']) ?>"><?= $e($v['correo']) ?></a></dd>
        <dt>Jurisdicción</dt><dd><?= $e($v['jurisdiccion']) ?></dd>
        <dt>Servicio</dt><dd><?= $e($v['servicio']) ?></dd>
        <dt>Talla de polo</dt><dd><?= $e($v['talla']) ?></dd>
        <?php if ($lugar !== null): ?>
          <?php /* Con ubigeo: los tres niveles, del más amplio al más
                   concreto, que es como se dice una dirección en voz alta. */ ?>
          <dt>Dónde vive</dt>
          <dd>
            <?= $e($lugar['departamento']) ?> ·
            <?= $e($lugar['provincia']) ?> ·
            <strong><?= $e($lugar['distrito']) ?></strong>
          </dd>
        <?php elseif (!empty($v['ubigeo_departamento_id'])): ?>
          <?php /* El departamento se eligió de la lista, pero la provincia o
                   el distrito se escribieron: las sugerencias no cargaron a
                   tiempo. Se muestra tal cual lo puso la persona y se marca,
                   para que quien repase sepa que ese texto no está contrastado
                   contra el ubigeo oficial y pueda corregirlo si hace falta. */ ?>
          <dt>Dónde vive</dt>
          <dd>
            <?= $e($departamentoNombre ?? '') ?> ·
            <?= $e($v['provincia'] ?: '—') ?> ·
            <strong><?= $e($v['distrito'] ?: '—') ?></strong>
            <span class="pildora">escrito a mano</span>
          </dd>
        <?php elseif ($v['distrito'] || $v['provincia']): ?>
          <?php /* Inscripciones anteriores al ubigeo: el distrito y la
                   provincia se dedujeron partiendo la dirección escrita a
                   mano, así que se marcan como lo que son. */ ?>
          <dt>Zona</dt>
          <dd>
            <?= $e(trim(($v['distrito'] ?? '') . ' · ' . ($v['provincia'] ?? ''), ' ·')) ?>
            <span class="pildora">deducido del texto</span>
          </dd>
        <?php endif; ?>
        <dt>Emergencia</dt><dd><?= $e($v['emergencia_nombre']) ?></dd>
        <dt>Recibida</dt><dd><?= $e(View::fecha($v['creado_en'], true)) ?></dd>
        <?php if ($v['area_asignada']): ?>
          <dt>Área asignada</dt><dd><?= $e($v['area_asignada']) ?></dd>
        <?php endif; ?>
      </dl>

      <!-- ── Datos sensibles ── -->
      <div class="sensible">
        <?php if ($revelado !== null): ?>

          <p class="sensible__aviso">
            Datos completos. Esta consulta ha quedado registrada en el histórico de actividad
            con tu nombre y la hora.
          </p>
          <dl class="datos-lista">
            <dt>DNI</dt><dd class="tabla__mono"><?= $e($revelado['dni']) ?></dd>
            <dt>Teléfono</dt><dd class="tabla__mono"><?= $e($revelado['telefono']) ?></dd>
            <dt>Dirección</dt><dd><?= $e($revelado['direccion']) ?></dd>
            <dt>Tel. emergencia</dt><dd class="tabla__mono"><?= $e($revelado['emergencia_telefono']) ?></dd>
          </dl>
          <p class="seccion__pie"><a class="enlace-flecha" href="<?= $url('/voluntarios/' . (int) $v['id']) ?>">Ocultar de nuevo</a></p>

        <?php else: ?>

          <dl class="datos-lista">
            <dt>DNI</dt><dd class="mascara"><?= $e($v['dni_mascara']) ?></dd>
            <dt>Teléfono</dt><dd class="mascara"><?= $e($v['telefono_mascara']) ?></dd>
            <dt>Dirección</dt><dd class="mascara">·············</dd>
            <dt>Tel. emergencia</dt><dd class="mascara"><?= $e($v['emergencia_telefono_mascara']) ?></dd>
          </dl>

          <?php if ($puedeSensible): ?>
            <p class="sensible__aviso">Estos datos están cifrados en la base. Verlos deja constancia en el registro de actividad.</p>
            <a class="btn btn--linea" href="<?= $url('/voluntarios/' . (int) $v['id']) ?>?ver=sensible">Ver los datos completos</a>
          <?php else: ?>
            <p class="sensible__aviso">No tienes permiso para ver estos datos completos.</p>
          <?php endif; ?>

        <?php endif; ?>
      </div>
    </section>

    <!-- ── Consentimiento ── -->
    <section class="tarjeta">
      <header class="tarjeta__cabecera"><h2>Consentimiento</h2></header>
      <dl class="datos-lista">
        <dt>Aceptado</dt><dd><?= $v['consentimiento'] ? 'Sí' : 'No' ?></dd>
        <dt>Fecha</dt><dd><?= $e(View::fecha($v['consentimiento_en'], true)) ?></dd>
        <dt>Versión del texto</dt><dd class="tabla__mono"><?= $e($v['consentimiento_version'] ?: '—') ?></dd>
      </dl>
      <?php if (str_contains((string) $v['consentimiento_version'], 'borrador')): ?>
        <p class="destello destello--aviso">
          Esta inscripción se recogió con el texto legal todavía en borrador, con los marcadores
          entre corchetes sin rellenar. Conviene resolverlo antes de abrir la convocatoria.
        </p>
      <?php endif; ?>
    </section>

  </div>

  <aside>
    <?php if ($puedeEditar && !$v['borrado_en']): ?>
      <section class="tarjeta">
        <header class="tarjeta__cabecera"><h2>Cambiar estado</h2></header>
        <form class="formulario" method="post" action="<?= $url('/voluntarios/' . (int) $v['id'] . '/estado') ?>">
          <?= $csrf->campo() ?>
          <div class="campo">
            <label class="campo__etiqueta" for="estado">Estado</label>
            <select id="estado" name="estado">
              <?php foreach ($estados as $clave => $rotulo): ?>
                <?php if ($clave === 'baja') { continue; } ?>
                <option value="<?= $e($clave) ?>"<?= $v['estado'] === $clave ? ' selected' : '' ?>><?= $e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label class="campo__etiqueta" for="nota">Nota (opcional)</label>
            <textarea id="nota" name="nota" rows="3"></textarea>
            <p class="campo__ayuda">Queda en el histórico de esta ficha.</p>
          </div>
          <button class="btn btn--primario btn--bloque" type="submit">Guardar</button>
        </form>
      </section>
    <?php endif; ?>

    <section class="tarjeta">
      <header class="tarjeta__cabecera"><h2>Histórico</h2></header>
      <?php if ($historial === []): ?>
        <p class="vacio">Sin movimientos.</p>
      <?php else: ?>
        <ul class="linea-tiempo">
          <?php foreach ($historial as $h): ?>
            <li>
              <time datetime="<?= $e($h['creado_en']) ?>"><?= $e(View::fecha($h['creado_en'], true)) ?></time>
              <strong><?= $e($estados[$h['estado_nuevo']] ?? $h['estado_nuevo']) ?></strong>
              <?php if ($h['estado_anterior']): ?>
                <span class="mascara">desde <?= $e($estados[$h['estado_anterior']] ?? $h['estado_anterior']) ?></span>
              <?php endif; ?>
              <?php if ($h['nota']): ?><p><?= $e($h['nota']) ?></p><?php endif; ?>
              <p class="mascara"><?= $e($h['usuario'] ?: 'Formulario público') ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <?php if ($auth->puede('voluntarios.borrar') && !$v['borrado_en']): ?>
      <section class="tarjeta">
        <header class="tarjeta__cabecera"><h2>Dar de baja</h2></header>
        <p class="vacio">La ficha deja de aparecer en el listado, pero los datos y el histórico se conservan.</p>
        <form class="formulario" method="post" action="<?= $url('/voluntarios/' . (int) $v['id'] . '/baja') ?>"
              data-confirmar="¿Dar de baja esta inscripción?">
          <?= $csrf->campo() ?>
          <div class="campo">
            <label class="campo__etiqueta" for="motivo">Motivo</label>
            <input type="text" id="motivo" name="motivo">
          </div>
          <button class="btn btn--linea btn--bloque" type="submit">Dar de baja</button>
        </form>
      </section>
    <?php endif; ?>
  </aside>
</div>
