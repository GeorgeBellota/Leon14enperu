<?php
/**
 * ============================================================================
 *  Plantilla común de las páginas públicas.
 * ============================================================================
 *
 *  Todo lo que era idéntico en las diecinueve páginas y estaba copiado en
 *  cada una: el <head> entero, el sprite, la cabecera, el pie y los scripts.
 *  Una corrección aquí llega a todo el sitio; antes había que repetirla
 *  diecinueve veces y bastaba olvidar una para que quedara descuadrada.
 *
 *  La vista sólo escribe su <main> y declara su $meta. El resto lo pone esto.
 *
 *  Variables que recibe:
 *    $sitio      Publico\Sitio
 *    $esc        escapador
 *    $activa     clave de la página, para marcar el menú
 *    $contenido  el HTML que produjo la vista
 *    $meta       titulo, descripcion, ruta, og_imagen, scripts, body_attr
 *
 *  @var \Intranet\Publico\Sitio $sitio
 */

declare(strict_types=1);

$raiz = $sitio->enlace('');   // prefijo de TODOS los enlaces de cabecera y pie

$titulo      = (string) ($meta['titulo'] ?? 'León XIV en el Perú');
$descripcion = (string) ($meta['descripcion'] ?? '');
$rutaPagina  = ltrim((string) ($meta['ruta'] ?? ''), '/');
$ogImagen    = (string) ($meta['og_imagen'] ?? 'assets/img/og/og-inicio.jpg');
$ogTipo      = (string) ($meta['og_tipo'] ?? 'article');
/* ── La fase del sitio ────────────────────────────────────────────────────
   «pre» antes del viaje, «live» durante los días de visita y «post» después.
   Varias secciones se encienden y se apagan con esto desde el CSS.

   Sale de Sitio::fase(), que lo calcula con las fechas del panel. Antes cada
   página lo llevaba escrito —data-phase="pre"—, así que el 11 de noviembre
   habría hecho falta desplegar el sitio entero para pasar a «live». */
$bodyAttr = (string) ($meta['body_attr'] ?? '');
$bodyAttr = trim('data-phase="' . $esc($sitio->fase()) . '" ' . $bodyAttr);
$extras      = (array)  ($meta['scripts'] ?? []);
$ogTitulo    = (string) ($meta['og_titulo']      ?? $titulo);
$ogDesc      = (string) ($meta['og_descripcion'] ?? $descripcion);
$canonica    = $sitio->url($rutaPagina);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $esc($titulo) ?></title>
<meta name="description" content="<?= $esc($descripcion) ?>">
<link rel="canonical" href="<?= $esc($canonica) ?>">

<meta property="og:type" content="<?= $esc($ogTipo) ?>">
<meta property="og:site_name" content="León XIV en el Perú">
<meta property="og:locale" content="es_PE">
<meta property="og:url" content="<?= $esc($canonica) ?>">
<meta property="og:title" content="<?= $esc($ogTitulo) ?>">
<meta property="og:description" content="<?= $esc($ogDesc) ?>">
<meta property="og:image" content="<?= $esc($sitio->url($ogImagen)) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $esc($ogTitulo) ?>">
<meta name="twitter:description" content="<?= $esc($ogDesc) ?>">
<meta name="twitter:image" content="<?= $esc($sitio->url($ogImagen)) ?>">

<link rel="icon" href="<?= $esc($sitio->asset('favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= $esc($sitio->asset('favicon.svg')) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Newsreader:ital,opsz,wght@0,6..72,400;1,6..72,400&family=Instrument+Sans:wght@400;600&display=swap">

<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/layout.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/components.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/sections.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/pages.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/print.css')) ?>" media="print">
<?php foreach ((array) ($meta['css'] ?? []) as $hoja): ?>
<link rel="stylesheet" href="<?= $esc($sitio->asset($hoja)) ?>">
<?php endforeach; ?>

<script nonce="<?= $esc($sitio->nonce()) ?>">
/* Marca que hay JS antes del primer pintado: de esta clase dependen las
   animaciones de entrada (.js [data-reveal]) y su alternativa sin JS. */
document.documentElement.className += ' js';
</script>
<?= $meta['head_extra'] ?? '' ?>
</head>

<body <?= $bodyAttr ?>>
<a class="salto-contenido" href="#contenido">Saltar al contenido</a>
<p class="solo-lectores" id="anuncios" role="status" aria-live="polite"></p>

<!-- Sprite de iconos. Fuente de verdad: assets/icons/sprite.svg -->
<?php require dirname(__DIR__) . '/assets/parciales/sprite.php'; ?>

<?php require dirname(__DIR__) . '/assets/parciales/cabecera.php'; ?>

<div class="pagina">

<?= $contenido ?>

<?php /* El aviso publicado desde el panel, si lo hay. No escribe nada
         cuando no hay ninguno publicado para esta página. */ ?>
<?php require dirname(__DIR__) . '/assets/parciales/comunicado.php'; ?>
<?php require dirname(__DIR__) . '/assets/parciales/pie.php'; ?>

</div><!-- /.pagina -->

<?php if (($meta['barra_fija'] ?? true) !== false): ?>
<div class="barra-fija">
  <a class="btn btn--primario btn--bloque" href="<?= $esc($sitio->enlace('voluntariado/')) ?>">Sé voluntario</a>
</div>
<?php endif; ?>

<?php /* ── El ORDEN importa ─────────────────────────────────────────────────
         Con «defer» los scripts se ejecutan en el orden en que aparecen, y
         main.js es el orquestador: hace «if (L14.hero) L14.hero.init()» para
         cada módulo. Si main.js va antes que hero.js, hitos.js o
         countdown.js, esos módulos todavía no se han registrado en L14 y
         main.js no arranca ninguno —sin dar error, simplemente no pasa nada—.

         Por eso main.js va SIEMPRE el último, después de los módulos propios
         de cada página. */ ?>
<script src="<?= $esc($sitio->asset('assets/vendor/gsap.min.js')) ?>" defer></script>
<script src="<?= $esc($sitio->asset('assets/vendor/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= $esc($sitio->asset('assets/js/nav.js')) ?>" defer></script>
<script src="<?= $esc($sitio->asset('assets/js/reveal.js')) ?>" defer></script>
<?php foreach ($extras as $script): ?>
<script src="<?= $esc($sitio->asset($script)) ?>" defer></script>
<?php endforeach; ?>
<script src="<?= $esc($sitio->asset('assets/js/form.js')) ?>" defer></script>
<script src="<?= $esc($sitio->asset('assets/js/main.js')) ?>" defer></script>
<?= $meta['pie_extra'] ?? '' ?>
</body>
</html>
