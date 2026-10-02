<?php
/**
 * Noticias · los vídeos de la página.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $videos
 * @var int   $tope
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
$src = static fn (?string $r) => $r === null || $r === '' ? '' : View::e($c->urlSitio('/' . ltrim($r, '/')));

$puedeEditar = $auth->puede('paginas.editar');
$activos     = count(array_filter($videos, static fn (array $v): bool => (int) $v['activo'] === 1));
?>

<header class="encabezado">
  <p class="encabezado__migas"><a href="<?= $url('/noticias') ?>">Noticias</a></p>
  <h1>Vídeos</h1>
  <p class="encabezado__pie">
    La cuadrícula que va debajo de las noticias en
    <a href="<?= $e($c->urlSitio('/noticias/')) ?>" target="_blank" rel="noopener">/noticias/</a>.
    Se ven <?= (int) $tope ?> de entrada y el resto con «Ver más».
  </p>
</header>

<?php if ($puedeEditar): ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Añadir un vídeo</h2>

    <form method="post" action="<?= $url('/noticias/videos') ?>">
      <?= $csrf->campo() ?>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="url">Enlace de YouTube</label>
        <input type="url" id="url" name="url" required
               placeholder="https://www.youtube.com/watch?v=…">
        <p class="campo__ayuda">
          Pega el enlace y ya está: el servidor trae <strong>el título y la
          portada</strong> de YouTube. No hay que subir ninguna imagen.
        </p>
        <p class="campo__ayuda">
          Valen las direcciones normales, las cortas de <code>youtu.be</code>,
          las de Shorts y las de directo.
        </p>
      </div>

      <div class="campo sep-m">
        <label class="campo__etiqueta" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3"></textarea>
        <p class="campo__ayuda">Opcional. Se lee bajo el título, en la ventana del vídeo.</p>
      </div>

      <p><button class="btn btn--primario" type="submit">Añadir</button></p>
    </form>

    <?php /* Se dice aquí porque es la pregunta que viene después: «¿y por qué
             tarda un segundo?». Porque sale a YouTube. */ ?>
    <p class="campo__ayuda">
      Al guardar, el servidor pide el título a YouTube y <strong>descarga la
      portada</strong> a la biblioteca. Tarda un segundo. Se hace así, y no
      enlazando a YouTube, para que el navegador de quien visita la web no
      hable con Google hasta que pulse el vídeo.
    </p>
  </section>
<?php endif; ?>

<section class="tarjeta">
  <header class="tarjeta__cabecera">
    <h2>En la página</h2>
    <span class="tarjeta__nota"><?= $activos ?> visible<?= $activos === 1 ? '' : 's' ?> de <?= count($videos) ?></span>
  </header>

  <?php if ($videos === []): ?>
    <p class="vacio">
      Todavía no hay vídeos.
      <?= $puedeEditar ? 'Pega el primer enlace arriba.' : '' ?>
    </p>
  <?php else: ?>
    <?php foreach ($videos as $i => $v): ?>
      <article class="video-fila<?= (int) $v['activo'] === 0 ? ' is-oculta' : '' ?>">

        <?php if (($v['imagen_ruta'] ?? null) !== null): ?>
          <img class="video-fila__mini" src="<?= $src($v['imagen_ruta']) ?>" alt="" loading="lazy" width="160" height="90">
        <?php else: ?>
          <div class="video-fila__mini video-fila__mini--vacia" aria-hidden="true">sin portada</div>
        <?php endif; ?>

        <div class="video-fila__datos">
          <?php if ($puedeEditar): ?>
            <form method="post" action="<?= $url('/noticias/videos/' . (int) $v['id']) ?>" class="pila-compacta">
              <?= $csrf->campo() ?>

              <input type="text" name="titulo" value="<?= $e($v['titulo'] ?? '') ?>" maxlength="255"
                     placeholder="Título del vídeo" aria-label="Título">

              <textarea name="descripcion" rows="2" placeholder="Descripción"
                        aria-label="Descripción"><?= $e($v['descripcion'] ?? '') ?></textarea>

              <div class="video-fila__mandos">
                <label class="casilla">
                  <input type="checkbox" name="activo" value="1" <?= (int) $v['activo'] === 1 ? 'checked' : '' ?>>
                  <span>Visible</span>
                </label>
                <button class="btn btn--mini" type="submit">Guardar</button>
              </div>
            </form>
          <?php else: ?>
            <p><strong><?= $e($v['titulo'] ?? '(sin título)') ?></strong></p>
            <p class="campo__ayuda"><?= $e($v['descripcion'] ?? '') ?></p>
          <?php endif; ?>

          <p class="campo__ayuda">
            <?php if (($v['autor'] ?? null) !== null): ?>
              <?= $e($v['autor']) ?> ·
            <?php endif; ?>
            <a href="<?= $e($v['url']) ?>" target="_blank" rel="noopener noreferrer">ver en YouTube ↗</a>
          </p>
        </div>

        <?php if ($puedeEditar): ?>
          <div class="video-fila__acciones">
            <?php foreach ([['subir', '↑'], ['bajar', '↓']] as [$dir, $icono]): ?>
              <?php if (($dir === 'subir' && $i > 0) || ($dir === 'bajar' && $i < count($videos) - 1)): ?>
                <form method="post" action="<?= $url('/noticias/videos/' . (int) $v['id'] . '/orden') ?>" class="en-linea">
                  <?= $csrf->campo() ?>
                  <input type="hidden" name="direccion" value="<?= $dir ?>">
                  <button class="mando-mini" type="submit" aria-label="<?= $dir === 'subir' ? 'Subir' : 'Bajar' ?>"><?= $icono ?></button>
                </form>
              <?php endif; ?>
            <?php endforeach; ?>

            <?php /* Para cuando el autor le cambia el título en YouTube, o
                     cuando la portada no se pudo traer la primera vez. */ ?>
            <form method="post" action="<?= $url('/noticias/videos/' . (int) $v['id'] . '/refrescar') ?>" class="en-linea">
              <?= $csrf->campo() ?>
              <button class="mando-mini" type="submit" title="Volver a pedir el título y la portada a YouTube">↻</button>
            </form>

            <form method="post" action="<?= $url('/noticias/videos/' . (int) $v['id'] . '/borrar') ?>" class="en-linea"
                  data-confirmar="¿Quitar este vídeo de la página?">
              <?= $csrf->campo() ?>
              <button class="mando-mini mando-mini--peligro" type="submit">Quitar</button>
            </form>
          </div>
        <?php endif; ?>

      </article>
    <?php endforeach; ?>

    <?php if ($activos > $tope): ?>
      <p class="campo__ayuda sep-m">
        Hay <?= $activos ?> vídeos visibles. En la web se ven los
        <?= (int) $tope ?> primeros y los demás aparecen al pulsar «Ver más».
      </p>
    <?php endif; ?>
  <?php endif; ?>
</section>
