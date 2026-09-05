<?php
/**
 * Listado de inscripciones con filtros.
 *
 * Aquí NO se descifra ni un dato: el DNI y el teléfono se muestran con la
 * máscara que se guardó al recibir la inscripción. Ver el número completo es
 * una acción aparte, en la ficha, con su permiso y su registro.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var array $resultado
 * @var array $filtros
 * @var array $jurisdicciones
 * @var array $servicios
 * @var array $estados
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

/** Conserva los filtros al cambiar de página o exportar. */
$conFiltros = static function (array $extra = []) use ($filtros, $c): string {
    $query = array_filter(array_merge($filtros, $extra), static fn ($v) => (string) $v !== '');

    return View::e($c->url('/voluntarios') . ($query ? '?' . http_build_query($query) : ''));
};

$hayFiltro = array_filter($filtros, static fn ($v, $k) => (string) $v !== '' && !in_array($k, ['orden', 'dir'], true), ARRAY_FILTER_USE_BOTH) !== [];
?>

<header class="encabezado">
  <p class="rotulo">Voluntariado</p>
  <h1>Inscripciones</h1>
  <p class="encabezado__pie">
    <?= number_format((int) $resultado['total'], 0, ',', '.') ?>
    <?= (int) $resultado['total'] === 1 ? 'inscripción' : 'inscripciones' ?><?= $hayFiltro ? ' con los filtros aplicados' : '' ?>.
  </p>
</header>

<section class="tarjeta">
  <form class="filtros" method="get" action="<?= $url('/voluntarios') ?>">
    <div class="campo">
      <label class="campo__etiqueta" for="f-texto">Nombre, correo o código</label>
      <input type="search" id="f-texto" name="texto" value="<?= $e($filtros['texto']) ?>">
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="f-dni">DNI</label>
      <input type="text" id="f-dni" name="dni" inputmode="numeric" maxlength="8"
             value="<?= $e($filtros['dni']) ?>" aria-describedby="f-dni-ayuda">
      <p class="campo__ayuda" id="f-dni-ayuda">Los ocho dígitos exactos.</p>
    </div>

    <?php /* ── Dónde vive ──
             Sólo aparecen los departamentos de los que hay alguien inscrito, y
             con el recuento al lado. Un desplegable con los 25 del Perú
             obligaría a probarlos uno a uno para encontrar dónde hay gente. */ ?>
    <div class="campo">
      <label class="campo__etiqueta" for="f-departamento">Departamento</label>
      <select id="f-departamento" name="departamento_id" data-filtro-auto>
        <option value="">Todos</option>
        <?php foreach ($departamentos as $d): ?>
          <option value="<?= $e($d['id']) ?>"<?= (string) $filtros['departamento_id'] === (string) $d['id'] ? ' selected' : '' ?>>
            <?= $e($d['nombre']) ?> (<?= (int) $d['total'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($departamentos === []): ?>
        <p class="campo__ayuda">Ninguna inscripción tiene ubicación todavía.</p>
      <?php endif; ?>
    </div>

    <?php /* La provincia sólo tiene sentido con un departamento elegido: si no,
             el desplegable mostraría provincias de todo el país mezcladas. */ ?>
    <?php if ($provincias !== []): ?>
      <div class="campo">
        <label class="campo__etiqueta" for="f-provincia">Provincia</label>
        <select id="f-provincia" name="provincia_id" data-filtro-auto>
          <option value="">Todas</option>
          <?php foreach ($provincias as $p): ?>
            <option value="<?= $e($p['id']) ?>"<?= (string) $filtros['provincia_id'] === (string) $p['id'] ? ' selected' : '' ?>>
              <?= $e($p['nombre']) ?> (<?= (int) $p['total'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="campo">
      <label class="campo__etiqueta" for="f-jurisdiccion">Jurisdicción</label>
      <select id="f-jurisdiccion" name="jurisdiccion_id" data-filtro-auto>
        <option value="">Todas</option>
        <?php foreach ($jurisdicciones as $j): ?>
          <option value="<?= (int) $j['id'] ?>"<?= (string) $filtros['jurisdiccion_id'] === (string) $j['id'] ? ' selected' : '' ?>><?= $e($j['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="f-servicio">Servicio</label>
      <select id="f-servicio" name="servicio_id" data-filtro-auto>
        <option value="">Todos</option>
        <?php foreach ($servicios as $s): ?>
          <option value="<?= (int) $s['id'] ?>"<?= (string) $filtros['servicio_id'] === (string) $s['id'] ? ' selected' : '' ?>><?= $e($s['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="f-estado">Estado</label>
      <select id="f-estado" name="estado" data-filtro-auto>
        <option value="">Todos</option>
        <?php foreach ($estados as $clave => $rotulo): ?>
          <option value="<?= $e($clave) ?>"<?= $filtros['estado'] === $clave ? ' selected' : '' ?>><?= $e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php /* ── Fechas ──────────────────────────────────────────────────────
             Campos de texto, no <input type="date">.

             El nativo escribía la fecha en el formato del ordenador, y en éste
             salía «mm/dd/yyyy»: en Perú eso se lee al revés y hace elegir el
             día equivocado sin que nadie lo note. Además se ve distinto en
             cada navegador y no admite «los últimos 7 días», que es la
             pregunta que de verdad se hace.

             Con `type="text"` el campo sigue funcionando sin JavaScript —se
             escribe la fecha a mano— y con JavaScript se abre el calendario
             propio de fecha.js. */ ?>
    <div class="campo campo--fecha">
      <label class="campo__etiqueta" for="f-desde">Desde</label>
      <input type="text" id="f-desde" name="desde" data-fecha
             value="<?= $e($filtros['desde']) ?>" placeholder="dd/mm/aaaa">
    </div>

    <div class="campo campo--fecha">
      <label class="campo__etiqueta" for="f-hasta">Hasta</label>
      <input type="text" id="f-hasta" name="hasta" data-fecha
             value="<?= $e($filtros['hasta']) ?>" placeholder="dd/mm/aaaa">
    </div>

    <div class="filtros__acciones">
      <button class="btn btn--primario" type="submit">Filtrar</button>
      <?php if ($hayFiltro): ?>
        <a class="btn btn--linea" href="<?= $url('/voluntarios') ?>">Limpiar</a>
      <?php endif; ?>
    </div>

    <?php /* Los rangos de uso corriente. Los rellena fecha.js; sin JavaScript
             no aparecen y quedan los dos campos, que es lo que hay que
             rellenar de todas formas. */ ?>
    <div class="filtros__rangos" data-rangos aria-label="Rangos rápidos"></div>
  </form>
</section>

<?php /* ══════════════════════════════════════════════════════════════════════
         LOS RESULTADOS, EN SU PROPIA TARJETA

         Estaban dentro de la misma que los filtros, y eso le imponía a la
         tabla el ancho del bloque de filtros: nueve columnas apretadas en un
         recuadro pensado para seis campos, con barra horizontal permanente
         aunque la pantalla fuera enorme.

         Separadas, la tabla puede ensancharse hasta donde dé la pantalla y los
         filtros se quedan con el ancho de lectura, que es el que necesitan.
         ══════════════════════════════════════════════════════════════════════ */ ?>
<section class="tarjeta tarjeta--ancha">
  <header class="tarjeta__cabecera">
    <h2>
      <?= number_format((int) $resultado['total'], 0, ',', '.') ?>
      <?= (int) $resultado['total'] === 1 ? 'inscripción' : 'inscripciones' ?>
    </h2>

    <?php /* El botón de exportar, arriba y a la vista.
             Estaba al pie de la tabla, detrás de la barra de desplazamiento
             horizontal: había que bajar hasta el final para encontrarlo, y en
             una pantalla estrecha ni eso. */ ?>
    <?php if ($auth->puede('voluntarios.exportar')): ?>
      <a class="btn btn--primario btn--menor"
         href="<?= View::e($c->url('/voluntarios/exportar') . '?' . http_build_query(array_filter($filtros))) ?>">
        Descargar CSV<?= $hayFiltro ? ' de la selección' : '' ?>
      </a>
    <?php endif; ?>
  </header>

  <?php if ($resultado['filas'] === []): ?>

    <p class="vacio">
      <?php if ($hayFiltro): ?>
        Ninguna inscripción coincide con esos filtros.
      <?php else: ?>
        Todavía no hay inscripciones. Aparecerán aquí en cuanto alguien envíe el
        formulario de <a href="<?= $e($c->urlSitio('/voluntariado/')) ?>" target="_blank" rel="noopener">voluntariado</a>.
      <?php endif; ?>
    </p>

  <?php else: ?>

    <div class="tabla-envoltorio">
      <table class="tabla tabla--inscripciones">
        <thead>
          <tr>
            <th scope="col">Código</th>
            <th scope="col">Nombre</th>
            <th scope="col">DNI</th>
            <th scope="col">Contacto</th>
            <th scope="col">Dónde vive</th>
            <th scope="col">Jurisdicción</th>
            <th scope="col">Servicio</th>
            <th scope="col">Estado</th>
            <th scope="col">
              <a href="<?= $conFiltros(['orden' => 'creado_en', 'dir' => $filtros['dir'] === 'ASC' ? 'DESC' : 'ASC']) ?>">
                Recibida <?= $filtros['orden'] === 'creado_en' ? ($filtros['dir'] === 'ASC' ? '↑' : '↓') : '' ?>
              </a>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resultado['filas'] as $v): ?>
            <tr>
              <td class="tabla__mono"><?= $e($v['codigo']) ?></td>
              <td><a href="<?= $url('/voluntarios/' . (int) $v['id']) ?>"><strong><?= $e($v['nombres']) ?></strong></a></td>
              <td><span class="mascara"><?= $e($v['dni_mascara']) ?></span></td>
              <td>
                <?= $e($v['correo']) ?><br>
                <span class="mascara"><?= $e($v['telefono_mascara']) ?></span>
              </td>
              <td>
                <?php if (!empty($v['ubigeo_distrito'])): ?>
                  <?php /* El distrito primero, en negrita: es el dato que se
                           busca. El departamento debajo, para situarlo cuando
                           hay distritos que se llaman igual en dos regiones. */ ?>
                  <strong><?= $e($v['ubigeo_distrito']) ?></strong><br>
                  <span class="mascara"><?= $e($v['ubigeo_departamento']) ?> · <?= $e($v['ubigeo_provincia']) ?></span>
                <?php elseif (!empty($v['ubigeo_departamento'])): ?>
                  <?php /* Departamento sí, provincia o distrito escritos a
                           mano: las sugerencias no llegaron a cargar y la
                           persona los tecleó. Se marca para poder repasarlos
                           luego, pero la inscripción es tan válida como
                           cualquier otra: el dato de contacto está entero y
                           eso es lo que importa. */ ?>
                  <?php
                    /* Sólo se escribe lo que hay.
                       Antes se ponía un guion en el hueco del distrito y otro
                       en el de la provincia, y una inscripción con sólo el
                       departamento salía como «— / AMAZONAS · —»: tres líneas
                       para decir una palabra, y dos de ellas sin información.
                       Ahora el departamento sube al sitio del titular cuando
                       es lo único que se sabe. */
                    $detalle = array_filter([$v['provincia'] ?? '', $v['ubigeo_departamento']]);
                  ?>
                  <?php if (!empty($v['distrito'])): ?>
                    <strong><?= $e($v['distrito']) ?></strong><br>
                    <span class="mascara"><?= $e(implode(' · ', $detalle)) ?></span>
                  <?php else: ?>
                    <strong><?= $e($v['ubigeo_departamento']) ?></strong>
                    <?php if (!empty($v['provincia'])): ?>
                      <br><span class="mascara"><?= $e($v['provincia']) ?></span>
                    <?php endif; ?>
                  <?php endif; ?>
                  <br><span class="mascara">escrito a mano</span>
                <?php elseif (!empty($v['distrito']) || !empty($v['provincia'])): ?>
                  <?php /* Inscripción anterior al ubigeo: el dato se dedujo
                           partiendo la dirección escrita a mano. */ ?>
                  <?= $e(trim(($v['distrito'] ?? '') . ' · ' . ($v['provincia'] ?? ''), ' ·')) ?>
                  <br><span class="mascara">deducido</span>
                <?php else: ?>
                  <span class="mascara">—</span>
                <?php endif; ?>
              </td>
              <td><?= $e($v['jurisdiccion']) ?></td>
              <td><?= $e(View::recortar(preg_replace('/^Servicio de /iu', '', (string) $v['servicio']), 24)) ?></td>
              <td><span class="pildora pildora--<?= $e($v['estado']) ?>"><?= $e($estados[$v['estado']] ?? $v['estado']) ?></span></td>
              <td><?= $e(View::fecha($v['creado_en'], true)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="paginacion">
      <span>
        Página <?= (int) $resultado['pagina'] ?> de <?= (int) $resultado['paginas'] ?>
      </span>

      <?php if ((int) $resultado['paginas'] > 1): ?>
        <span class="paginacion__paginas">
          <?php
          $actual = (int) $resultado['pagina'];
          $total  = (int) $resultado['paginas'];
          // Ventana de cinco alrededor de la actual: con doscientas páginas,
          // pintarlas todas es una alfombra de números inservible.
          $desde  = max(1, $actual - 2);
          $hasta  = min($total, $desde + 4);
          $desde  = max(1, $hasta - 4);
          ?>
          <?php if ($actual > 1): ?>
            <a href="<?= $conFiltros(['pagina' => $actual - 1]) ?>" rel="prev">Anterior</a>
          <?php endif; ?>

          <?php for ($p = $desde; $p <= $hasta; $p++): ?>
            <?php if ($p === $actual): ?>
              <span aria-current="page"><?= $p ?></span>
            <?php else: ?>
              <a href="<?= $conFiltros(['pagina' => $p]) ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($actual < $total): ?>
            <a href="<?= $conFiltros(['pagina' => $actual + 1]) ?>" rel="next">Siguiente</a>
          <?php endif; ?>
        </span>
      <?php endif; ?>

      <?php /* El botón de exportar ya está arriba, en la cabecera de esta
               tarjeta. Aquí abajo quedaba escondido tras la barra de
               desplazamiento de la tabla. */ ?>
    </div>

  <?php endif; ?>
</section>
