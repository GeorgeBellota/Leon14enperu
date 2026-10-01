<?php
/**
 * Multimedia · una actividad: subir, describir, mover y ordenar.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array   $actividad
 * @var array   $fechas
 * @var array   $fotos
 * @var ?string $fechaActiva
 * @var array   $actividades
 * @var array   $biblioteca
 * @var string  $carpeta
 * @var int     $tope
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
$src = static fn (string $ruta) => View::e($c->urlSitio('/' . ltrim($ruta, '/')));

$puedeEditar = $auth->puede('medios.subir');
$id          = (int) $actividad['id'];

$dia = static function (?string $iso): string {
    if ($iso === null || $iso === '') {
        return 'Sin fecha';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    [$a, $m, $d] = array_map('intval', explode('-', $iso));

    return $d . ' de ' . ($meses[$m] ?? '?') . ' de ' . $a;
};

/* La fecha que se está mirando viaja en cada formulario para volver aquí
   después de guardar, en lugar de saltar a «todas». */
$vista = $fechaActiva !== null ? '?fecha=' . rawurlencode($fechaActiva) : '';
?>

<header class="encabezado">
  <p class="encabezado__migas"><a href="<?= $url('/galeria') ?>">Multimedia</a></p>
  <h1><?= $e($actividad['nombre']) ?></h1>
  <p class="encabezado__pie">
    <?= count($fotos) ?> fotografía<?= count($fotos) === 1 ? '' : 's' ?>
    <?= $fechaActiva !== null ? 'el ' . $e($dia($fechaActiva)) : 'en total' ?>.
    Máximo <?= (int) $tope ?> por actividad.
  </p>
</header>

<?php /* ── Las fechas ───────────────────────────────────────────────────────
         Esto es el «Fecha ▼» del diseño, y aquí sirve además para otra cosa:
         ver de un vistazo si una fecha se quedó con una sola fotografía
         porque alguien tecleó mal el día. Una pestaña con 1 cuando las demás
         tienen 20 canta sola. */ ?>
<?php if ($fechas !== []): ?>
  <nav class="pestanas sep-m" aria-label="Fechas de esta actividad">
    <a class="pestana<?= $fechaActiva === null ? ' is-activa' : '' ?>"
       href="<?= $url('/galeria/' . $id) ?>">Todas</a>

    <?php foreach ($fechas as $f): ?>
      <?php $v = (string) ($f['fecha'] ?? ''); ?>
      <a class="pestana<?= $fechaActiva === $f['fecha'] ? ' is-activa' : '' ?>"
         href="<?= $url('/galeria/' . $id . ($v !== '' ? '?fecha=' . rawurlencode($v) : '')) ?>">
        <?= $e($dia($f['fecha'])) ?>
        <span class="pestana__num"><?= (int) $f['fotos'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>

<?php if ($puedeEditar): ?>
  <?php /* ── Subir ──────────────────────────────────────────────────────────
           Varias de golpe: es lo que convierte «subir las ochenta fotos del
           acto» en una operación en lugar de ochenta. La fecha se elige UNA
           vez y se aplica a toda la tanda. */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Subir fotografías</h2>

    <form method="post" action="<?= $url('/galeria/' . $id . '/subir') ?>" enctype="multipart/form-data">
      <?= $csrf->campo() ?>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="fotos">Archivos</label>
        <input type="file" id="fotos" name="fotos[]" multiple required
               accept="image/jpeg,image/png,image/webp">
        <p class="campo__ayuda">
          Puedes elegir varias a la vez. Sube la versión <strong>más grande que tengas</strong>:
          el servidor genera solo los tamaños de <?= implode(', ', \Intranet\Core\Imagen::ANCHOS) ?> px
          para la web, y <strong>conserva el original</strong>, que es lo que se
          descarga desde la galería. Máximo 20 MB por archivo.
        </p>
      </div>

      <?php /* ── La fecha ───────────────────────────────────────────────────
               Se ofrecen las que YA existen, con su recuento, y escribir una
               nueva es un paso aparte. Así, cuando se te olvidó una foto y la
               subes al día siguiente, eliges la fecha del acto con un clic en
               lugar de teclearla —que es como se crea una pestaña huérfana con
               una sola fotografía—.

               Y ojo: esta fecha es la del ACTO, no la de hoy. No se toma del
               archivo ni del reloj. */ ?>
      <fieldset class="campo sep-m">
        <legend class="campo__etiqueta">Fecha del acto</legend>

        <label class="opcion">
          <input type="radio" name="fecha_modo" value="" checked data-fecha-modo>
          <span>Sin fecha<span class="opcion__nota">Salen en la actividad, fuera de cualquier día.</span></span>
        </label>

        <?php foreach ($fechas as $f): ?>
          <?php if (($f['fecha'] ?? null) === null) { continue; } ?>
          <label class="opcion">
            <input type="radio" name="fecha_modo" value="<?= $e($f['fecha']) ?>" data-fecha-modo
                   <?= $fechaActiva === $f['fecha'] ? 'checked' : '' ?>>
            <span><?= $e($dia($f['fecha'])) ?><span class="opcion__nota"><?= (int) $f['fotos'] ?> ya</span></span>
          </label>
        <?php endforeach; ?>

        <label class="opcion">
          <input type="radio" name="fecha_modo" value="nueva" data-fecha-modo>
          <span>Otra fecha</span>
        </label>
        <input type="date" name="fecha_nueva" class="sep-s" aria-label="La fecha nueva">

        <p class="campo__ayuda">
          Es el día en que ocurrió el acto, no el día en que subes la foto. Si
          mañana subes una que se te olvidó, elige aquí su fecha de siempre y
          caerá donde las demás.
        </p>
      </fieldset>

      <p><button class="btn btn--primario" type="submit">Subir a esta actividad</button></p>
    </form>
  </section>

  <?php /* ── Traer de la biblioteca ─────────────────────────────────────── */ ?>
  <?php if ($biblioteca !== []): ?>
    <details class="tarjeta">
      <summary class="tarjeta__titulo">Traer una imagen que ya está en la biblioteca</summary>

      <p class="campo__ayuda sep-s">
        Sin volver a subirla: la misma imagen puede estar en la galería y en el
        carrusel sin ocupar el doble de disco.
      </p>

      <form method="post" action="<?= $url('/galeria/' . $id . '/biblioteca') ?>">
        <?= $csrf->campo() ?>
        <input type="hidden" name="fecha" value="<?= $e($fechaActiva ?? '') ?>">

        <div class="rejilla-medios sep-m">
          <?php foreach ($biblioteca as $m): ?>
            <label class="medio-elegible">
              <input type="checkbox" name="medios[]" value="<?= (int) $m['id'] ?>">
              <img src="<?= $src((string) $m['ruta']) ?>" alt="" loading="lazy" width="120" height="90">
              <span class="medio-elegible__pie"><?= $e($m['nombre_archivo']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <p class="campo__ayuda">
          Entran en la fecha que estés mirando ahora
          (<strong><?= $e($dia($fechaActiva)) ?></strong>).
        </p>
        <p><button class="btn" type="submit">Añadir las marcadas</button></p>
      </form>
    </details>
  <?php endif; ?>
<?php endif; ?>

<?php /* ── Las fotografías ──────────────────────────────────────────────── */ ?>
<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2><?= $fechaActiva !== null ? $e($dia($fechaActiva)) : 'Todas las fotografías' ?></h2>
    <span class="tarjeta__nota"><?= count($fotos) ?></span>
  </header>

  <?php if ($fotos === []): ?>
    <p class="vacio">
      No hay fotografías <?= $fechaActiva !== null ? 'en esta fecha' : 'todavía' ?>.
      <?= $puedeEditar ? 'Súbelas desde el formulario de arriba.' : '' ?>
    </p>
  <?php else: ?>
    <?php /* La cuadrícula NO va dentro de un formulario: las casillas de aquí
             son el mando y las de verdad están en el formulario del final,
             ocultas. Las sincroniza assets/js/galeria.js. Un <form> aquí
             tendría que anidarse con aquél, y HTML no lo admite. */ ?>
    <div class="rejilla-fotos">
        <?php foreach ($fotos as $n => $foto): ?>
          <article class="foto-galeria<?= (int) $foto['activa'] === 0 ? ' is-oculta' : '' ?>">
            <label class="foto-galeria__marca">
              <input type="checkbox" value="<?= (int) $foto['id'] ?>" data-marca>
              <span class="visualmente-oculto">Seleccionar</span>
            </label>

            <img class="foto-galeria__img" src="<?= $src((string) $foto['ruta']) ?>"
                 alt="<?= $e($foto['alt'] ?? '') ?>" loading="lazy" width="200" height="150">

            <div class="foto-galeria__datos">
              <p class="foto-galeria__meta">
                <?= $e($dia($foto['fecha'])) ?>
                <?php if (($foto['original'] ?? null) === null): ?>
                  <span class="etiqueta etiqueta--aviso" title="Se subió antes de octubre de 2026, cuando el original no se guardaba. Se descargará la versión de 1600 px.">sin original</span>
                <?php endif; ?>
              </p>
            </div>
          </article>
        <?php endforeach; ?>
    </div>

    <?php /* Las descripciones van en UN formulario por fotografía, no en uno
             común: así guardar una no reescribe las demás, y un error en una
             no pierde lo que se acaba de teclear en otras. */ ?>
    <?php if ($puedeEditar): ?>
      <h3 class="sep-l">Descripciones</h3>
      <p class="campo__ayuda">
        Es el pie que se lee en la ventana grande, al pulsar la fotografía en la
        web. No es el texto alternativo: ése vive en la biblioteca y describe la
        imagen para quien no la ve.
      </p>

      <?php foreach ($fotos as $foto): ?>
        <form method="post" action="<?= $url('/galeria/' . $id . '/foto/' . (int) $foto['id']) ?>" class="fila-descripcion">
          <?= $csrf->campo() ?>
          <input type="hidden" name="fecha_vista" value="<?= $e($fechaActiva ?? '') ?>">

          <img src="<?= $src((string) $foto['ruta']) ?>" alt="" loading="lazy" width="72" height="54">

          <input type="text" name="descripcion" maxlength="500"
                 value="<?= $e($foto['descripcion'] ?? '') ?>"
                 placeholder="El Santo Padre saluda a los fieles"
                 aria-label="Descripción de la fotografía">

          <label class="casilla">
            <input type="checkbox" name="activa" value="1" <?= (int) $foto['activa'] === 1 ? 'checked' : '' ?>>
            <span>Visible</span>
          </label>

          <button class="btn btn--mini" type="submit">Guardar</button>
        </form>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php if ($puedeEditar && $fotos !== []): ?>
  <?php /* ── Mover y quitar ─────────────────────────────────────────────────
           Mover es cambiar un dato: el archivo no se duplica ni se recomprime.
           Y «quitar» no borra: la fotografía se queda en la biblioteca, por si
           la usa el carrusel o Prensa. */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Mover o quitar las seleccionadas</h2>

    <form method="post" action="<?= $url('/galeria/' . $id . '/mover') ?>" id="lote-mover">
      <?= $csrf->campo() ?>
      <input type="hidden" name="fecha_vista" value="<?= $e($fechaActiva ?? '') ?>">

      <?php foreach ($fotos as $foto): ?>
        <input type="checkbox" name="fotos[]" value="<?= (int) $foto['id'] ?>" class="visualmente-oculto" data-espejo="<?= (int) $foto['id'] ?>">
      <?php endforeach; ?>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="actividad_destino">A otra actividad</label>
        <select id="actividad_destino" name="actividad_destino">
          <option value="0">— dejarlas en ésta —</option>
          <?php foreach ($actividades as $a): ?>
            <?php if ((int) $a['id'] === $id) { continue; } ?>
            <option value="<?= (int) $a['id'] ?>"><?= $e($a['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="campo sep-m">
        <label class="casilla">
          <input type="checkbox" name="cambiar_fecha" value="1">
          <span>Cambiarles también la fecha</span>
        </label>
        <input type="date" name="fecha_destino" class="sep-s" aria-label="La fecha de destino">
        <p class="campo__ayuda">
          Déjala vacía con la casilla marcada para quitarles la fecha. Sin marcar
          la casilla, la fecha no se toca.
        </p>
      </div>

      <p class="sep-s">
        <button class="btn btn--mini" type="button" data-marcar-todas>Marcar todas</button>
        <span class="campo__ayuda" data-marcadas>Ninguna seleccionada</span>
      </p>

      <p>
        <button class="btn btn--primario" type="submit">Mover</button>
        <button class="btn btn--peligro" type="submit"
                formaction="<?= $url('/galeria/' . $id . '/quitar') ?>">Quitar de la galería</button>
      </p>
      <p class="campo__ayuda">
        <strong>Quitar no borra el archivo.</strong> Lo saca de Multimedia y se queda
        en la biblioteca de imágenes, por si lo usa otra página.
      </p>
    </form>
  </section>

<?php endif; ?>

<?php if ($puedeEditar): ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Esta actividad</h2>

    <form method="post" action="<?= $url('/galeria/' . $id) ?>">
      <?= $csrf->campo() ?>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="160" required value="<?= $e($actividad['nombre']) ?>">
      </div>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="rotulo">Rótulo</label>
        <input type="text" id="rotulo" name="rotulo" maxlength="80" value="<?= $e($actividad['rotulo']) ?>">
      </div>

      <div class="campo casilla sep-m">
        <label>
          <input type="checkbox" name="activa" value="1" <?= (int) $actividad['activa'] === 1 ? 'checked' : '' ?>>
          Visible en la web
        </label>
        <p class="campo__ayuda">Sin marcar, la actividad y sus fotografías no se publican.</p>
      </div>

      <p><button class="btn btn--primario" type="submit">Guardar</button></p>
    </form>

    <form method="post" action="<?= $url('/galeria/' . $id . '/borrar') ?>" class="sep-l"
          onsubmit="return confirm('¿Borrar la actividad «<?= $e($actividad['nombre']) ?>»? Las fotografías seguirán en la biblioteca de imágenes.');">
      <?= $csrf->campo() ?>
      <button class="btn btn--peligro" type="submit">Borrar la actividad</button>
      <span class="campo__ayuda">Las fotografías no se borran: siguen en la biblioteca.</span>
    </form>
  </section>
<?php endif; ?>
