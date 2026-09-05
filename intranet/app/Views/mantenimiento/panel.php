<?php
/**
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var bool   $activo
 * @var array  $textos
 * @var array  $ips
 * @var string $miIp
 * @var bool   $miIpVale
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
?>

<header class="encabezado">
  <p class="rotulo">Administración</p>
  <h1>Mantenimiento del sitio</h1>
  <p class="encabezado__pie">
    Cierra la web pública mientras se prepara. La intranet nunca se cierra:
    si lo hiciera, quien apagó el sitio no tendría por dónde volver a encenderlo.
  </p>
</header>

<!-- ── Estado actual, bien visible ── -->
<section class="estado-sitio<?= $activo ? ' estado-sitio--cerrado' : '' ?>">
  <p class="estado-sitio__rotulo">Ahora mismo</p>
  <p class="estado-sitio__valor"><?= $activo ? 'Sitio CERRADO al público' : 'Sitio abierto' ?></p>
  <p class="estado-sitio__nota">
    <?php if ($activo): ?>
      Sólo lo ven las <?= count(array_filter($ips, static fn ($i) => $i['activa'])) ?>
      dirección(es) autorizadas de abajo. Todos los demás reciben la página de aviso.
    <?php else: ?>
      Cualquiera puede ver la web con normalidad.
    <?php endif; ?>
  </p>
</section>

<?php /* Aviso antes de que sea tarde: si cierra el sitio y su IP no está en la
         lista, dejará de ver la web pública al instante. */ ?>
<?php if (!$miIpVale): ?>
  <p class="destello destello--aviso">
    Tu dirección actual es <strong><?= $e($miIp) ?></strong> y <strong>no está autorizada</strong>.
    Si cierras el sitio sin añadirla, dejarás de ver la web pública (el panel seguirá funcionando).
  </p>
<?php else: ?>
  <p class="destello destello--exito">
    Tu dirección actual (<strong><?= $e($miIp) ?></strong>) está autorizada: seguirás viendo la web
    aunque el sitio esté cerrado.
  </p>
<?php endif; ?>

<!-- ── Interruptor y textos ── -->
<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Estado y mensaje</h2></header>

  <form class="formulario" method="post" action="<?= $url('/mantenimiento') ?>"
        data-confirmar="<?= $activo ? '' : '¿Cerrar la web al público?' ?>">
    <?= $csrf->campo() ?>

    <label class="interruptor">
      <input type="checkbox" name="activo" value="1"<?= $activo ? ' checked' : '' ?>>
      <span>Cerrar el sitio público (modo mantenimiento)</span>
    </label>

    <div class="campo">
      <label class="campo__etiqueta" for="titulo">Titular del aviso</label>
      <input type="text" id="titulo" name="titulo" value="<?= $e($textos['titulo']) ?>">
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="mensaje">Mensaje</label>
      <textarea id="mensaje" name="mensaje" rows="3"><?= $e($textos['mensaje']) ?></textarea>
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="vuelve">Cuándo volvemos (opcional)</label>
      <input type="text" id="vuelve" name="vuelve" value="<?= $e($textos['vuelve']) ?>"
             placeholder="Volvemos hoy a las 6 de la tarde" aria-describedby="vuelve-ayuda">
      <p class="campo__ayuda" id="vuelve-ayuda">Si lo dejas vacío, no se muestra nada.</p>
    </div>

    <div class="fila">
      <button class="btn btn--primario" type="submit">Guardar</button>
      <a class="enlace-flecha" href="<?= $e($c->urlSitio('/')) ?>" target="_blank" rel="noopener">Ver la web ↗</a>
    </div>
  </form>
</section>

<!-- ── Lista blanca de direcciones ── -->
<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>Direcciones autorizadas</h2>
  </header>

  <p class="vacio">
    Estas direcciones siguen viendo la web con el sitio cerrado. Se admite una dirección suelta
    (<code>203.0.113.7</code>) o un rango completo (<code>203.0.113.0/24</code>), útil para una
    oficina entera.
  </p>

  <form class="filtros" method="post" action="<?= $url('/mantenimiento/ip') ?>">
    <?= $csrf->campo() ?>
    <div class="campo">
      <label class="campo__etiqueta" for="ip">Dirección IP o rango</label>
      <input type="text" id="ip" name="ip" placeholder="203.0.113.7" required>
    </div>
    <div class="campo">
      <label class="campo__etiqueta" for="etiqueta">¿De quién es?</label>
      <input type="text" id="etiqueta" name="etiqueta" placeholder="Oficina de la CEP" required>
    </div>
    <div class="filtros__acciones">
      <button class="btn btn--primario" type="submit">Autorizar</button>
    </div>
  </form>

  <?php if (!$miIpVale): ?>
    <form method="post" action="<?= $url('/mantenimiento/ip') ?>" class="sep-m">
      <?= $csrf->campo() ?>
      <input type="hidden" name="ip" value="<?= $e($miIp) ?>">
      <input type="hidden" name="etiqueta" value="Mi conexión actual">
      <button class="btn btn--linea" type="submit">Autorizar mi dirección actual (<?= $e($miIp) ?>)</button>
    </form>
  <?php endif; ?>

  <?php if ($ips === []): ?>
    <p class="vacio sep-m">Todavía no hay ninguna dirección autorizada.</p>
  <?php else: ?>
    <div class="tabla-envoltorio sep-m">
      <table class="tabla">
        <thead>
          <tr>
            <th scope="col">Dirección</th>
            <th scope="col">Etiqueta</th>
            <th scope="col">Estado</th>
            <th scope="col">Último uso</th>
            <th scope="col"><span class="solo-lectores">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ips as $i): ?>
            <tr>
              <td class="tabla__mono">
                <?= $e($i['ip']) ?>
                <?php if (\Intranet\Publico\Mantenimiento::coincide($miIp, (string) $i['ip'])): ?>
                  <span class="pildora pildora--validado">la tuya</span>
                <?php endif; ?>
              </td>
              <td><?= $e($i['etiqueta']) ?></td>
              <td>
                <span class="pildora pildora--<?= $i['activa'] ? 'validado' : 'baja' ?>">
                  <?= $i['activa'] ? 'Activa' : 'Desactivada' ?>
                </span>
              </td>
              <td><?= $e(View::fecha($i['ultimo_uso'], true)) ?></td>
              <td>
                <div class="fila">
                  <form method="post" action="<?= $url('/mantenimiento/ip/' . (int) $i['id'] . '/alternar') ?>">
                    <?= $csrf->campo() ?>
                    <button class="mando-mini" type="submit"><?= $i['activa'] ? 'Desactivar' : 'Activar' ?></button>
                  </form>
                  <form method="post" action="<?= $url('/mantenimiento/ip/' . (int) $i['id'] . '/borrar') ?>"
                        data-confirmar="¿Quitar <?= $e($i['ip']) ?> de la lista?">
                    <?= $csrf->campo() ?>
                    <button class="mando-mini mando-mini--peligro" type="submit">Quitar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="tarjeta">
  <header class="tarjeta__cabecera"><h2>Qué conviene saber</h2></header>
  <ul class="lista-estado">
    <li><span class="ok">·</span> La página de aviso responde <strong>503</strong>, no 200: los buscadores
        entienden que es temporal y no la indexan en lugar de la web.</li>
    <li><span class="ok">·</span> <strong>La intranet nunca se cierra</strong>, estés donde estés.</li>
    <li><span class="ok">·</span> Si la base de datos falla, el sitio se queda <strong>abierto</strong>:
        un error de MySQL no puede tumbar la web entera.</li>
    <li><span class="ok">·</span> En una conexión doméstica la IP suele cambiar sola cada pocos días.
        Si dejas de ver la web, vuelve aquí y autoriza la nueva.</li>
  </ul>
</section>
