<?php
/**
 * Una pieza de una sección, sola en su pantalla.
 *
 * Una jornada del itinerario, una sede, una comisión. Antes se editaban
 * todas a la vez en el formulario de la sección: trece comisiones eran 161
 * campos, y corregir una coma en la segunda obligaba a bajar por las once
 * siguientes y a volver a guardarlas todas.
 *
 * Los campos van sueltos —`titulo`, no `bloques[3][titulo]`— porque aquí sólo
 * hay una pieza. Eso también quita del medio el <template> con el marcador
 * «__i__» del que salían las piezas nuevas: ahora una pieza nueva se crea en
 * el servidor y nace con su propia dirección.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $seccion
 * @var array $plantilla
 * @var array $pieza
 * @var array $medios    la biblioteca, para los selectores de imagen
 * @var int   $posicion  el puesto que ocupa, para situarse
 * @var int   $cuantas
 */

use Intranet\Cms\Plantillas;
use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$def     = $plantilla['bloques'];
$enSec   = '/paginas/' . $pagina['clave'] . '/' . $seccion['clave'];
$conPagi = !empty($seccion['datos']['detalle']);
?>

<header class="encabezado">
  <?php /* El rastro completo. Antes la pieza no tenía pantalla y no hacía
           falta; ahora sin él no se sabe de dónde se ha venido. */ ?>
  <p class="rotulo">
    <a href="<?= $url('/paginas') ?>">Páginas</a> ·
    <a href="<?= $url('/paginas/' . $pagina['clave']) ?>"><?= $e($pagina['nombre']) ?></a> ·
    <a href="<?= $url($enSec) ?>"><?= $e($seccion['nombre']) ?></a>
  </p>

  <h1><?= $e($pieza['titulo'] ?: 'Ficha sin título') ?></h1>

  <p class="encabezado__pie">
    <?= $e($def['nombre']) ?><?php if ($posicion > 0): ?>
      <?= ' ' . $posicion . ' de ' . $cuantas ?>
    <?php endif; ?>
    <?php if (!$pieza['activo']): ?>
      · <strong>Oculta en la web</strong>
    <?php endif; ?>
  </p>
</header>

<form method="post" action="<?= $url($enSec . '/piezas/' . (int) $pieza['id']) ?>"
      class="formulario-cms" data-avisar-cambios>
  <?= $csrf->campo() ?>

  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2><?= $e($def['nombre']) ?></h2>
      <label class="interruptor">
        <input type="checkbox" name="activo" value="1"<?= $pieza['activo'] ? ' checked' : '' ?>>
        <span>Visible en la web</span>
      </label>
    </header>

    <?php /* La dirección propia. Sólo en las secciones cuyas piezas tienen
             página; en las demás no se pinta y no se guarda. */ ?>
    <?php if ($conPagi): ?>
      <div class="campo">
        <label class="campo__etiqueta" for="p-slug">Dirección de su página</label>
        <div class="campo-slug">
          <span class="campo-slug__base"><?= $e(rtrim((string) ($pagina['ruta'] ?? '/'), '/')) ?>/</span>
          <input type="text" id="p-slug" name="slug" value="<?= $e($pieza['slug'] ?? '') ?>"
                 pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="se calcula del título">
          <span class="campo-slug__base">/</span>
        </div>
        <p class="campo__ayuda campo__ayuda--aviso">
          Si lo dejas vacío se calcula del título. <strong>Cambiarlo rompe los
          enlaces que ya se hayan compartido</strong> de esta página: sólo hazlo
          si aún no se ha publicado.
        </p>
      </div>
    <?php endif; ?>

    <?php
    /* Las listas cerradas van PRIMERO, antes que los campos que gobiernan.
       «Plantilla» decide cuáles de los demás se usan —la ficha esconde los
       otros—, así que elegirla al final, que es donde caía por estar en
       `datos`, era el orden contrario al que tiene sentido. */
    $mandan = array_filter(
        $def['datos'] ?? [],
        static fn (array $d): bool => ($d['tipo'] ?? '') === 'opciones'
    );
    ?>
    <?php foreach ($mandan as $clave => $defDato): ?>
      <?php $id = 'pd-' . $clave; ?>
      <div class="campo" data-campo="datos_<?= $e($clave) ?>">
        <label class="campo__etiqueta" for="<?= $e($id) ?>"><?= $e($defDato['etiqueta']) ?></label>
        <?php
        $defCampo    = $defDato;
        $nombreCampo = 'datos_' . $clave;
        $idCampo     = $id;
        $valorCampo  = (string) ($pieza['datos'][$clave] ?? '');
        require __DIR__ . '/_campo-opciones.php';
        ?>
        <?php if (($defDato['ayuda'] ?? '') !== ''): ?>
          <p class="campo__ayuda"><?= $defDato['ayuda'] /* texto nuestro */ ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php foreach ($def['campos'] as $campo): ?>
      <?php
      $columna = Plantillas::columna($campo);
      $tipo    = Plantillas::campo($campo)['tipo'];
      $id      = 'p-' . $campo;
      ?>
      <?php /* `data-campo` es el nombre con el que viaja: así panel.js puede
               esconder la caja entera cuando la plantilla elegida no lo usa. */ ?>
      <div class="campo" data-campo="<?= $e($columna) ?>">
        <label class="campo__etiqueta" for="<?= $e($id) ?>"><?= $e(Plantillas::campoBloque($campo)) ?></label>

        <?php if ($tipo === 'imagen'): ?>
          <?php
          // El selector guarda el id de la biblioteca, no una ruta: cambiar
          // la foto en la biblioteca la cambia en todas las páginas donde sale.
          $nombre  = $columna;
          $idCampo = $id;
          $elegida = $pieza[$columna] ?? null;
          require __DIR__ . '/../_comunes/_selector-imagen.php';
          ?>
        <?php elseif ($tipo === 'area'): ?>
          <?php /* Área, no <input>: un <input> no puede contener un salto de
                   línea —el navegador lo quita al enviar— y hay titulares que
                   van a dos renglones a propósito. */ ?>
          <textarea id="<?= $e($id) ?>" name="<?= $e($columna) ?>" rows="3"><?= $e($pieza[$columna] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" id="<?= $e($id) ?>" name="<?= $e($columna) ?>" value="<?= $e($pieza[$columna] ?? '') ?>">
        <?php endif; ?>

        <?php if (($ayuda = Plantillas::campo($campo)['ayuda'] ?? '') !== ''): ?>
          <p class="campo__ayuda"><?= $ayuda /* la ayuda es texto nuestro, no del usuario */ ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <?php
  /* ── Lo que esta pantalla no enseña, pero existe ──────────────────────
     En `datos` puede haber claves que la plantilla no declara: las pone una
     migración y ninguna pantalla las muestra. Viajan aquí, en un campo
     oculto, y vuelven tal cual al guardar. Sin esto se perdían en el primer
     «Guardar», porque el controlador rehace `datos` desde cero con sólo lo
     que declara la plantilla. */
  $declaradas = array_keys($def['datos'] ?? []);
  $sobrantes  = array_diff_key((array) ($pieza['datos'] ?? []), array_flip($declaradas));
  ?>
  <?php if ($sobrantes !== []): ?>
    <input type="hidden" name="datos_extra"
           value="<?= $e(json_encode($sobrantes, JSON_UNESCAPED_UNICODE)) ?>">
  <?php endif; ?>

  <?php
  /* Lo que no sea una lista cerrada: ésas ya fueron arriba. Si no queda
     ninguna, la tarjeta no se pinta: un título con el vacío debajo sólo hace
     dudar de si falta algo por cargar. */
  $otros = array_diff_key($def['datos'] ?? [], $mandan);
  ?>
  <?php if ($otros !== []): ?>
    <section class="tarjeta" data-tarjeta-campos>
      <header class="tarjeta__cabecera"><h2>Otros textos</h2></header>

      <?php foreach ($otros as $clave => $defDato): ?>
        <?php $id = 'pd-' . $clave; ?>
        <div class="campo" data-campo="datos_<?= $e($clave) ?>">
          <label class="campo__etiqueta" for="<?= $e($id) ?>"><?= $e($defDato['etiqueta']) ?></label>

          <?php if ($defDato['tipo'] === 'opciones'): ?>
            <?php
            /* Lista cerrada. El parcial se encarga de no descartar un valor
               guardado que ya no se ofrezca: ver _campo-opciones.php. */
            $defCampo    = $defDato;
            $nombreCampo = 'datos_' . $clave;
            $idCampo     = $id;
            $valorCampo  = (string) ($pieza['datos'][$clave] ?? '');
            require __DIR__ . '/_campo-opciones.php';
            ?>
          <?php else: ?>
          <?php /* Una caja de cuatro renglones para un campo de una línea
                   —dónde va la fotografía, un subtítulo— invita a escribir un
                   párrafo donde cabe una palabra. La altura la dice el tipo. */ ?>
          <textarea id="<?= $e($id) ?>" name="datos_<?= $e($clave) ?>"
                    rows="<?= $defDato['tipo'] === 'texto' ? 2 : 4 ?>"><?php
            /* El valor por defecto no puede ser un array para todos: en un
               campo de texto sin rellenar, ese array se convertía a cadena,
               PHP avisaba —«Array to string conversion»— y el aviso se
               imprimía DENTRO del textarea. Al guardar, esa frase se quedaba
               escrita en la base como si fuera contenido. */
            $valor = $pieza['datos'][$clave] ?? null;

            echo $e($defDato['tipo'] === 'lista'
                ? implode("\n", (array) ($valor ?? []))
                : (is_scalar($valor) ? (string) $valor : ''));
          ?></textarea>
          <?php endif; ?>

          <?php if ($defDato['tipo'] === 'lista'): ?>
            <p class="campo__ayuda">Un elemento por línea.</p>
          <?php elseif (($defDato['ayuda'] ?? '') !== ''): ?>
            <p class="campo__ayuda"><?= $defDato['ayuda'] /* texto nuestro */ ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <div class="barra-guardar">
    <a class="btn btn--linea" href="<?= $url($enSec) ?>">Volver sin guardar</a>
    <button class="btn btn--primario" type="submit">Guardar</button>
  </div>
</form>

<?php /* Borrar va en su propio formulario: uno no puede ir dentro de otro, y
         además así no viaja con el resto de los campos. */ ?>
<section class="tarjeta">
  <h2 class="tarjeta__titulo">Borrar esta ficha</h2>
  <p>Desaparece de la web y de este panel. Si sólo quieres quitarla de la web
     un tiempo, desmarca «Visible» y guarda.</p>

  <form method="post" action="<?= $url($enSec . '/piezas/' . (int) $pieza['id'] . '/borrar') ?>"
        data-confirmar="¿Borrar esta ficha? No se puede deshacer.">
    <?= $csrf->campo() ?>
    <button class="btn btn--peligro" type="submit">Borrar</button>
  </form>
</section>

<?php /* La ventana para elegir imagen: UNA para toda la pantalla, compartida
         por todos los campos de imagen y fuera del formulario. */ ?>
<?php require __DIR__ . '/../_comunes/_biblioteca-ventana.php'; ?>
