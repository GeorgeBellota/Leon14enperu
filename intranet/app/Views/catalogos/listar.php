<?php
/**
 * Cupos por jurisdicción.
 *
 * La pantalla enseña las tres cosas juntas —tope, inscritos y plazas libres—
 * porque un tope sin saber cuántos van es un número a ciegas: nadie puede
 * decidir si 20 000 sobra o falta sin ver que ya hay 19 800.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $jurisdicciones
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
?>

<header class="encabezado">
  <p class="rotulo">Catálogos</p>
  <h1>Cupos por jurisdicción</h1>
  <p class="encabezado__pie">
    Cuántas inscripciones admite cada una. Al llegar al tope, su opción sigue
    viéndose en el formulario con un «completado» detrás, pero no se puede
    elegir.
  </p>
</header>

<form method="post" action="<?= $url('/catalogos') ?>" class="formulario-cms" data-avisar-cambios>
  <?= $csrf->campo() ?>

  <section class="tarjeta">
    <div class="tabla-envoltorio">
      <table class="tabla">
        <thead>
          <tr>
            <th scope="col">Jurisdicción</th>
            <th scope="col">Inscritos</th>
            <th scope="col">Tope</th>
            <th scope="col">Quedan</th>
            <th scope="col">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jurisdicciones as $j): ?>
            <?php
              $limite    = $j['limite'];
              $inscritos = (int) $j['inscritos'];
              $completa  = !empty($j['completa']);
              $apagada   = (int) $j['activo'] !== 1;
            ?>
            <tr>
              <th scope="row">
                <?= $e($j['nombre']) ?>
                <?php if ($apagada): ?>
                  <span class="pildora pildora--baja">No se ofrece</span>
                <?php endif; ?>
              </th>

              <td><?= number_format($inscritos, 0, ',', ' ') ?></td>

              <td>
                <label class="visualmente-oculto" for="lim-<?= (int) $j['id'] ?>">
                  Tope de <?= $e($j['nombre']) ?>
                </label>
                <input type="text" inputmode="numeric" class="campo-tope"
                       id="lim-<?= (int) $j['id'] ?>"
                       name="limite[<?= (int) $j['id'] ?>]"
                       value="<?= $limite === null ? '' : (int) $limite ?>"
                       placeholder="sin tope" maxlength="7">
              </td>

              <td>
                <?php if ($limite === null): ?>
                  <span class="tabla__mono">—</span>
                <?php else: ?>
                  <?= number_format((int) $j['quedan'], 0, ',', ' ') ?>
                <?php endif; ?>
              </td>

              <td>
                <?php if ($limite === null): ?>
                  <span class="pildora">Abierta</span>
                <?php elseif ($completa): ?>
                  <span class="pildora pildora--rechazado">Completa</span>
                <?php else: ?>
                  <span class="pildora pildora--validado">Abierta</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="campo__ayuda sep-m">
      Deja el tope <strong>vacío</strong> para no poner ninguno. Un cero no es lo
      mismo: cerraría la jurisdicción por completo, y para eso están los
      catálogos, no el cupo.
    </p>

    <p class="campo__ayuda">
      <strong>Bajar un tope por debajo de lo ya inscrito no borra a nadie.</strong>
      Poner 5 000 donde hay 20 000 deja la jurisdicción cerrada a nuevas
      inscripciones; las que ya están siguen donde estaban.
    </p>

    <p class="campo__ayuda">
      Cuenta como plaza ocupada <strong>toda inscripción viva</strong>, sea cual
      sea su estado: nueva, en validación, validada, acreditada, rechazada o de
      baja.
    </p>
  </section>

  <div class="barra-guardar">
    <a class="btn btn--linea" href="<?= $url('/') ?>">Cancelar</a>
    <button class="btn btn--primario" type="submit">Guardar cupos</button>
  </div>
</form>
