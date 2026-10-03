<?php
/**
 * Noticias · escribir o editar una.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Auth       $auth
 * @var \Intranet\Core\Csrf       $csrf
 * @var array|null $noticia
 * @var array      $biblioteca
 * @var string     $carpeta
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));
$src = static fn (string $r) => View::e($c->urlSitio('/' . ltrim($r, '/')));

$esNueva = $noticia === null;
$accion  = $esNueva ? $url('/noticias/nueva') : $url('/noticias/' . (int) $noticia['id']);

$v = static fn (string $campo, string $porDefecto = ''): string
    => (string) ($noticia[$campo] ?? $porDefecto);
?>

<header class="encabezado">
  <p class="encabezado__migas"><a href="<?= $url('/noticias') ?>">Noticias</a></p>
  <h1><?= $esNueva ? 'Escribir una noticia' : $e($noticia['titulo']) ?></h1>
  <?php if (!$esNueva): ?>
    <p class="encabezado__pie">
      <code>/noticias/<?= $e($noticia['slug']) ?>/</code>
      <?php if ($noticia['estado'] === 'publicada'): ?>
        · <a href="<?= $e($c->urlSitio('/noticias/' . $noticia['slug'] . '/')) ?>" target="_blank" rel="noopener">verla en la web</a>
      <?php endif; ?>
    </p>
  <?php endif; ?>
</header>

<form method="post" action="<?= $accion ?>">
  <?= $csrf->campo() ?>

  <section class="tarjeta">
    <h2 class="tarjeta__titulo">La noticia</h2>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="titulo">Titular</label>
      <input type="text" id="titulo" name="titulo" maxlength="255" required value="<?= $e($v('titulo')) ?>">
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="fecha">Fecha de publicación</label>
      <input type="date" id="fecha" name="fecha" required value="<?= $e($v('fecha', date('Y-m-d'))) ?>">
      <p class="campo__ayuda">
        <strong>Decide el orden en la web</strong>: las noticias salen de la más
        reciente a la más antigua. No es la fecha en que la escribes, sino la
        del hecho que cuentas.
      </p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="resumen">Extracto</label>
      <textarea id="resumen" name="resumen" rows="3" maxlength="500"><?= $e($v('resumen')) ?></textarea>
      <p class="campo__ayuda">
        Lo que se lee en el listado, antes de pulsar «Ver más». Si lo dejas
        vacío se usan las primeras líneas del cuerpo, pero escrito queda mejor:
        un extracto no es el principio de un texto, es un resumen.
      </p>
    </div>

    <?php /* ── El cuerpo ────────────────────────────────────────────────────
             Sin JavaScript se ve el <textarea> de siempre y se puede escribir
             igual; editor.js lo esconde y pone el área con botones encima.
             Lo que se guarda pasa por HtmlSeguro en el servidor. */ ?>
    <div class="campo sep-m">
      <label class="campo__etiqueta" for="cuerpo">Cuerpo</label>

      <div class="editor__barra" data-editor-barra hidden
           data-sube="<?= $url('/noticias/imagen') ?>"
           data-csrf="<?= $e($csrf->token()) ?>">
        <button class="editor__boton" type="button" data-orden="bold" title="Negrita"><strong>B</strong></button>
        <button class="editor__boton" type="button" data-orden="italic" title="Cursiva"><em>I</em></button>
        <span class="editor__sep"></span>
        <button class="editor__boton" type="button" data-orden="formatBlock" data-valor="p" title="Párrafo">¶</button>
        <button class="editor__boton" type="button" data-orden="formatBlock" data-valor="h3" title="Subtítulo">H</button>
        <span class="editor__sep"></span>
        <button class="editor__boton" type="button" data-orden="insertUnorderedList" title="Lista">•</button>
        <button class="editor__boton" type="button" data-orden="insertOrderedList" title="Lista numerada">1.</button>
        <span class="editor__sep"></span>
        <button class="editor__boton" type="button" data-orden="enlace" title="Enlace">🔗</button>
        <button class="editor__boton" type="button" data-orden="imagen" title="Insertar imagen">🖼</button>
        <button class="editor__boton" type="button" data-orden="video" title="Insertar un vídeo de YouTube">▶</button>
        <span class="editor__sep"></span>
        <button class="editor__boton" type="button" data-orden="removeFormat" title="Quitar formato">✕</button>
      </div>

      <div class="editor__area" data-editor hidden></div>
      <input type="file" class="visualmente-oculto" data-editor-imagen accept="image/jpeg,image/png,image/webp">
      <p class="campo__ayuda editor__aviso" data-editor-aviso hidden></p>

      <textarea id="cuerpo" name="cuerpo" rows="14" data-editor-campo><?= $e($v('cuerpo')) ?></textarea>

      <p class="campo__ayuda">
        Admite párrafos, subtítulos, listas, negrita, <strong>enlaces</strong> e
        <strong>imágenes dentro del texto</strong>. Al pegar desde Word se pega
        como texto plano a propósito: el formato de Word trae tipografías y
        colores que no son los del sitio.
      </p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="fuente">Fuente</label>
      <input type="text" id="fuente" name="fuente" maxlength="160" value="<?= $e($v('fuente', 'Conferencia Episcopal Peruana')) ?>">
    </div>
  </section>

  <?php /* ── La portada ─────────────────────────────────────────────────── */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Fotografía de portada</h2>
    <p class="campo__ayuda">
      La que se ve en el listado y arriba de la noticia. Si no eliges ninguna,
      la noticia sale sólo con texto.
    </p>

    <?php
    /* El mismo selector que usan las Páginas: la elegida arriba y en
       grande, con su nombre, y la galería en una ventana con buscador.
    
       Antes esto era la biblioteca ENTERA en una rejilla de radios: con
       una foto antigua había que bajar por noventa y pico miniaturas
       buscando cuál tenía el punto encendido. */
    $medios  = $biblioteca;
    $nombre  = 'imagen_id';
    $idCampo = 'n-portada';
    $vacio   = 'Sin fotografía';
    $elegida = $v('imagen_id') !== '' ? (int) $v('imagen_id') : null;
    require __DIR__ . '/../_comunes/_selector-imagen.php';
    ?>

    <p class="campo__ayuda">
      ¿No está la que buscas? Súbela en
      <a href="<?= $url('/medios') ?>" target="_blank" rel="noopener">Imágenes</a> y vuelve.
    </p>
  </section>

  <?php /* ── SEO ──────────────────────────────────────────────────────────── */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Buscadores y redes</h2>
    <p class="campo__ayuda">
      Vacíos, se usan el titular y el extracto. Se cambian cuando el titular
      bueno para la web no es el bueno para Google.
    </p>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="seo_titulo">Título en Google</label>
      <input type="text" id="seo_titulo" name="seo_titulo" maxlength="190" value="<?= $e($v('seo_titulo')) ?>">
      <p class="campo__ayuda">Hasta unos 60 caracteres: más allá, Google lo corta.</p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="seo_descripcion">Descripción</label>
      <textarea id="seo_descripcion" name="seo_descripcion" rows="2" maxlength="255"><?= $e($v('seo_descripcion')) ?></textarea>
      <p class="campo__ayuda">Hasta unos 155 caracteres.</p>
    </div>

    <div class="campo sep-m">
      <label class="campo__etiqueta" for="og_imagen_id">Imagen al compartir</label>
      <?php
      /* Igual que la portada. Vacío sigue significando «la misma de
         portada», que es lo que dice la ayuda de debajo. */
      $medios  = $biblioteca;
      $nombre  = 'og_imagen_id';
      $idCampo = 'og_imagen_id';
      $vacio   = 'La misma de portada';
      $elegida = (int) $v('og_imagen_id') ?: null;
      require __DIR__ . '/../_comunes/_selector-imagen.php';
      ?>
      <p class="campo__ayuda">
        La que sale al pegar el enlace en WhatsApp o Facebook. Lo mejor es una
        apaisada de 1200 × 630.
      </p>
    </div>
  </section>

  <?php /* ── Publicar ───────────────────────────────────────────────────── */ ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Publicación</h2>

    <div class="campo sep-m">
      <label class="opcion">
        <input type="radio" name="estado" value="borrador"
               <?= $v('estado', 'borrador') !== 'publicada' ? 'checked' : '' ?>>
        <span><strong>Borrador</strong><span class="opcion__nota">Se guarda y no se ve en la web.</span></span>
      </label>

      <label class="opcion">
        <input type="radio" name="estado" value="publicada" <?= $v('estado') === 'publicada' ? 'checked' : '' ?>>
        <span><strong>Publicada</strong><span class="opcion__nota">Visible en /noticias/ al instante.</span></span>
      </label>
    </div>

    <?php /* Aquí había una casilla de «destacar» para fijar una noticia en el
             hueco grande. Se quitó: el cliente pidió «orden cronológico
             descendente», y fijar una a mano permite volver a poner una de
             agosto por encima de una de octubre, que es el desorden del que
             se quejaba. La grande es siempre la más reciente. */ ?>
    <p class="campo__ayuda">
      La noticia más reciente ocupa sola el hueco grande del listado. No hay
      que marcar nada: lo decide la fecha.
    </p>

    <p>
      <button class="btn btn--primario" type="submit">Guardar</button>
      <a class="btn btn--linea" href="<?= $url('/noticias') ?>">Volver sin guardar</a>
    </p>
  </section>
</form>

<?php if (!$esNueva && $auth->puede('paginas.editar')): ?>
  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Borrar</h2>
    <form method="post" action="<?= $url('/noticias/' . (int) $noticia['id'] . '/borrar') ?>"
          data-confirmar="¿Borrar «<?= $e($noticia['titulo']) ?>»? No se puede deshacer.">
      <?= $csrf->campo() ?>
      <button class="btn btn--peligro" type="submit">Borrar esta noticia</button>
      <span class="campo__ayuda">La fotografía seguirá en la biblioteca.</span>
    </form>
  </section>
<?php endif; ?>

<?php /* La ventana para elegir imagen: UNA para toda la pantalla, compartida
         por la portada y por la imagen al compartir. Sus miniaturas son
         «lazy», así que no se piden hasta que alguien la abre. */ ?>
<?php $medios = $biblioteca; ?>
<?php require __DIR__ . '/../_comunes/_biblioteca-ventana.php'; ?>
