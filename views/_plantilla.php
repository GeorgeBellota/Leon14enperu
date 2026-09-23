<?php
/**
 * ============================================================================
 *  Plantilla común de las páginas públicas — rediseño 2026.
 * ============================================================================
 *
 *  La vista sólo escribe su <main> y declara su $meta. Aquí van el <head>, el
 *  sprite, la cabecera, el pie y los scripts. Una corrección en este archivo
 *  llega a todo el sitio.
 *
 *  ── Qué cambió con el rediseño ───────────────────────────────────────────
 *
 *   · Las tipografías ya no vienen de Google Fonts: Neulis, Poppins, Pathway
 *     Gothic One y Freestyle Script se sirven desde assets/fonts en WOFF2.
 *     Una petición menos a un tercero y ningún dato del visitante fuera.
 *   · Las seis hojas de estilo antiguas se sustituyen por sistema.css (el
 *     sistema de diseño) + heredado.css (formulario y piezas que siguen
 *     vivas) + la hoja propia de cada página, que se carga sola si existe.
 *   · GSAP, ScrollTrigger y Lenis se retiran: el movimiento del diseño nuevo
 *     lo resuelven el CSS y un IntersectionObserver. Son 130 KB menos en
 *     cada página. flatpickr se queda, porque lo usa el formulario.
 *
 *  Variables que recibe:
 *    $sitio      Publico\Sitio
 *    $esc        escapador
 *    $activa     clave de la página, para marcar el menú
 *    $contenido  el HTML que produjo la vista
 *    $meta       titulo, descripcion, ruta, og_imagen, css, scripts, body_attr
 *
 *  @var \Intranet\Publico\Sitio $sitio
 */

declare(strict_types=1);

$raiz = $sitio->enlace('');   // prefijo de TODOS los enlaces de cabecera y pie

/* ── El SEO que venga del panel ───────────────────────────────────────────
   Manda lo que se haya escrito en Páginas → Datos para buscadores, y sólo
   eso: un campo que se deje vacío no vuelve aquí, y la página conserva el
   texto que trae escrito abajo. Si la base no responde, seo() devuelve vacío
   y esto no llega a notarse. */
$seoPanel = $sitio->seo((string) ($activa ?? ''));

$titulo      = (string) ($seoPanel['titulo']      ?? $meta['titulo']      ?? 'León XIV en el Perú');
$descripcion = (string) ($seoPanel['descripcion'] ?? $meta['descripcion'] ?? '');
$rutaPagina  = ltrim((string) ($meta['ruta'] ?? ''), '/');
$ogImagen    = (string) ($seoPanel['og_imagen']   ?? $meta['og_imagen']   ?? 'assets/img/og/og-inicio.jpg');
$ogTipo      = (string) ($meta['og_tipo'] ?? 'article');

/* ── La fase del sitio ────────────────────────────────────────────────────
   «pre» antes del viaje, «live» durante los días de visita y «post» después.
   Varias piezas se encienden y se apagan con esto desde el CSS. Sale de
   Sitio::fase(), que lo calcula con las fechas del panel: el 11 de noviembre
   el sitio cambia solo, sin desplegar nada. */
$bodyAttr = (string) ($meta['body_attr'] ?? '');
$bodyAttr = trim('data-phase="' . $esc($sitio->fase()) . '" ' . $bodyAttr);

$extras   = (array)  ($meta['scripts'] ?? []);
$ogTitulo = (string) ($meta['og_titulo']      ?? $titulo);
$ogDesc   = (string) ($meta['og_descripcion'] ?? $descripcion);
$canonica = $sitio->url($rutaPagina);

/* ── La hoja propia de la página ──────────────────────────────────────────
   Cada página tiene la suya en assets/css/paginas/, con el mismo nombre que
   su vista. Se carga sola si el archivo existe: la vista no tiene que
   declararla y no hay forma de olvidarse. */
$hojasPagina = [];
/* $__vista lo deja index.php antes de ejecutar la vista, precisamente para no
   depender aquí de $destino: la vista corre en este mismo ámbito y puede
   haberlo pisado sin querer. */
$vistaActual = (string) ($__vista ?? '');

if ($vistaActual !== '') {
    $hoja = 'assets/css/paginas/' . $vistaActual . '.css';

    if (is_file(dirname(__DIR__) . '/' . $hoja)) {
        $hojasPagina[] = $hoja;
    }
}
?>
<!doctype html>
<html lang="es-PE">
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
<meta name="theme-color" content="#6E0B14">

<?php /* ── El icono de la pestaña ──────────────────────────────────────────
         Se puede cambiar desde Configuración → Icono de la pestaña. Mientras
         no haya ninguno subido se sirve el favicon.svg de la raíz, que dibuja
         una cruz latina sobre campo rojo; ese archivo lleva dentro por qué no
         lleva el escudo pontificio.

         Se pregunta al DISCO, no a un ajuste guardado: si alguien borra el
         archivo por FTP, la web vuelve sola al de la raíz en lugar de pintar
         un icono roto en todas las páginas.

         El «apple-touch-icon» no admite SVG —iOS lo ignora y pone una captura
         de la página en su lugar—, así que sólo se declara cuando el icono
         subido es de píxeles. */ ?>
<?php
$iconoSubido = (new \Intranet\Core\Favicon())->actual();
$icono       = $iconoSubido ?? 'favicon.svg';
$iconoMime   = \Intranet\Core\Favicon::mime($icono);
?>
<link rel="icon" href="<?= $esc($sitio->asset($icono)) ?>" type="<?= $esc($iconoMime) ?>">
<?php if ($iconoSubido !== null && $iconoMime !== 'image/svg+xml'): ?>
  <link rel="apple-touch-icon" href="<?= $esc($sitio->asset($iconoSubido)) ?>">
<?php endif; ?>

<?php /* Las dos familias que aparecen en cada página: se precargan para que el
         titular y el texto no parpadeen. Las demás variantes las pide el CSS
         cuando hacen falta. */ ?>
<link rel="preload" as="font" type="font/woff2" href="<?= $esc($sitio->asset('assets/fonts/neulis-bold.woff2')) ?>" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="<?= $esc($sitio->asset('assets/fonts/poppins-light.woff2')) ?>" crossorigin>

<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/sistema.css')) ?>">
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/heredado.css')) ?>">
<?php foreach (array_merge($hojasPagina, (array) ($meta['css'] ?? [])) as $hoja): ?>
<link rel="stylesheet" href="<?= $esc($sitio->asset($hoja)) ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?= $esc($sitio->asset('assets/css/print.css')) ?>" media="print">

<script nonce="<?= $esc($sitio->nonce()) ?>">
/* Marca que hay JS antes del primer pintado: de esta clase dependen las
   animaciones de entrada y su alternativa sin JS. */
document.documentElement.className += ' js';
</script>
<?= $meta['head_extra'] ?? '' ?>
</head>

<body <?= $bodyAttr ?>>
<a class="visually-hidden salto-contenido" href="#contenido">Saltar al contenido</a>
<p class="visually-hidden" id="anuncios" role="status" aria-live="polite"></p>

<!-- Sprite de iconos. Fuente de verdad: assets/icons/sprite.svg -->
<?php require dirname(__DIR__) . '/assets/parciales/sprite.php'; ?>

<?php require dirname(__DIR__) . '/assets/parciales/cabecera.php'; ?>

<div class="pagina">

<?= $contenido ?>

<?php /* El aviso publicado desde el panel, si lo hay. No escribe nada cuando
         no hay ninguno publicado para esta página. */ ?>
<?php require dirname(__DIR__) . '/assets/parciales/comunicado.php'; ?>
<?php require dirname(__DIR__) . '/assets/parciales/pie.php'; ?>

</div><!-- /.pagina -->

<?php if (($meta['barra_fija'] ?? true) !== false): ?>
<?php /* La barra de captación, sólo en móvil. El editable no la dibuja, pero
         es la principal vía de inscripción y se conserva a propósito. */ ?>
<div class="barra-fija">
  <a class="btn btn--bloque" href="<?= $esc($sitio->enlace('voluntariado/')) ?>">Sé voluntario</a>
</div>
<?php endif; ?>

<?php /* El aviso de cookies. Sólo aparece si hay medición configurada en el
         panel, y no trae ni un script de seguimiento: sólo los
         identificadores, inertes, que consentimiento.js usará si se acepta. */ ?>
<?php require dirname(__DIR__) . '/assets/parciales/consentimiento.php'; ?>

<?php /* ── El ORDEN importa ─────────────────────────────────────────────────
         Con «defer» los scripts se ejecutan en el orden en que aparecen.
         rediseno.js es autónomo —se arranca solo— y resuelve el menú, la
         cuenta atrás, los acordeones, los filtros, el carrusel, los vídeos y
         la ampliación de imágenes.

         arranque.js va SIEMPRE el último: es el orquestador de los módulos
         que se registran en window.L14 (el formulario de voluntariado, la
         colecta, el modal de llamada a la acción) y llama a su init(). Si
         fuera antes que ellos, no encontraría ninguno registrado y no
         arrancaría nada, sin dar error. */ ?>
<script src="<?= $esc($sitio->asset('assets/js/rediseno.js')) ?>" defer></script>
<?php foreach ($extras as $script): ?>
<script src="<?= $esc($sitio->asset($script)) ?>" defer></script>
<?php endforeach; ?>
<?php if ($sitio->reproductorPintado()): ?>
<?php /* Sólo en la página que pintó el reproductor, no en todas las que
         tienen transmisión configurada. */ ?>
<script src="<?= $esc($sitio->asset('assets/js/directo.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($sitio->mide()): ?>
<?php /* Sólo se carga si hay algo que medir. Si el panel tiene los campos
         vacíos, este archivo ni se pide. */ ?>
<script src="<?= $esc($sitio->asset('assets/js/consentimiento.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= $esc($sitio->asset('assets/js/arranque.js')) ?>" defer></script>
<?= $meta['pie_extra'] ?? '' ?>
</body>
</html>
