<?php
/**
 * Datos para buscadores de una página.
 *
 * Hasta ahora vivían escritos dentro de cada vista, así que cambiar el título
 * que sale en Google era desplegar código. Las columnas estaban en la tabla
 * desde el principio, sin que nadie las usara.
 *
 * ── Lo que NO hace esta pantalla ───────────────────────────────────────────
 *
 * Obligar. Los tres campos son opcionales, y el que se deje vacío hace que la
 * página siga usando el texto que trae escrito. Es lo que permite afinar tres
 * páginas sin tener que rellenar las veinticuatro.
 *
 * @var \Intranet\Core\Contenedor $c
 * @var \Intranet\Core\Csrf       $csrf
 * @var array $pagina
 * @var array $medios
 */

use Intranet\Core\View;

$e   = static fn ($v) => View::e($v);
$url = static fn (string $r) => View::e($c->url($r));

$tituloSeo = (string) ($pagina['titulo_seo'] ?? '');
$descSeo   = (string) ($pagina['descripcion_seo'] ?? '');
$dominio   = preg_replace('~^https?://~', '', rtrim((string) $c->config('url.sitio', ''), '/')) ?: 'leon14enperu.com';
?>

<header class="encabezado">
  <p class="rotulo">
    <a href="<?= $url('/paginas') ?>">Páginas</a> ·
    <a href="<?= $url('/paginas/' . $pagina['clave']) ?>"><?= $e($pagina['nombre']) ?></a>
  </p>
  <h1>Datos para buscadores</h1>
  <p class="encabezado__pie">
    Lo que se ve en Google y lo que aparece al compartir el enlace por WhatsApp
    o redes. Los tres campos son opcionales.
  </p>
</header>

<form method="post" action="<?= $url('/paginas/' . $pagina['clave'] . '/seo') ?>"
      class="formulario-cms" data-avisar-cambios>
  <?= $csrf->campo() ?>

  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Título y descripción</h2>
    </header>

    <div class="campo">
      <label class="campo__etiqueta" for="s-titulo">Título</label>
      <input type="text" id="s-titulo" name="titulo_seo" maxlength="190"
             value="<?= $e($tituloSeo) ?>"
             data-contar="60" data-vista-titulo
             placeholder="<?= $e($pagina['nombre']) ?> · León XIV en el Perú">
      <p class="campo__ayuda">
        Google corta alrededor de los <strong>60 caracteres</strong>. Lo
        importante primero. Si lo dejas vacío se usa el que la página trae
        escrito.
      </p>
    </div>

    <div class="campo">
      <label class="campo__etiqueta" for="s-desc">Descripción</label>
      <textarea id="s-desc" name="descripcion_seo" rows="3" maxlength="300"
                data-contar="155" data-vista-desc
                placeholder="Una o dos frases que expliquen de qué va esta página."><?= $e($descSeo) ?></textarea>
      <p class="campo__ayuda">
        Alrededor de <strong>155 caracteres</strong>. No posiciona por sí sola,
        pero es lo que decide si alguien entra o pasa de largo.
      </p>
    </div>
  </section>

  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Imagen al compartir</h2>
    </header>

    <div class="campo">
      <label class="campo__etiqueta" for="s-og">Imagen</label>
      <?php
        $nombre  = 'og_imagen_id';
        $idCampo = 's-og';
        $elegida = $pagina['og_imagen_id'] ?? null;
        require __DIR__ . '/_selector-imagen.php';
      ?>
      <p class="campo__ayuda">
        La que sale en la tarjeta cuando alguien pega el enlace en WhatsApp,
        Facebook o X. Apaisada y de al menos 1200 píxeles de ancho. Si la dejas
        vacía se usa la imagen general del sitio.
      </p>
    </div>
  </section>

  <?php /* La vista previa. No es adorno: escribir un título a ciegas y
           descubrir en Google que se cortaba es el error que se comete una vez
           y se tarda semanas en ver. */ ?>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2>Cómo se verá</h2>
    </header>

    <div class="vista-buscador">
      <p class="vista-buscador__ruta"><?= $e($dominio . rtrim((string) $pagina['ruta'], '/')) ?>/</p>
      <p class="vista-buscador__titulo" data-eco-titulo><?= $e($tituloSeo !== '' ? $tituloSeo : $pagina['nombre'] . ' · León XIV en el Perú') ?></p>
      <p class="vista-buscador__desc" data-eco-desc><?= $e($descSeo !== '' ? $descSeo : 'Sin descripción propia: se usará la que trae escrita la página.') ?></p>
    </div>
  </section>

  <div class="barra-guardar">
    <a class="btn btn--linea" href="<?= $url('/paginas/' . $pagina['clave']) ?>">Cancelar</a>
    <button class="btn btn--primario" type="submit">Guardar</button>
  </div>
</form>
