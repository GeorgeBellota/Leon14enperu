<?php
/**
 * Vista de la página «privacidad».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Política de privacidad · leon14enperu.com',
    'descripcion' => 'Qué datos personales recoge este sitio, para qué, durante cuánto tiempo y cómo ejercer tus derechos.',
    'ruta'        => 'privacidad/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
    'body_attr'   => 'data-phase="pre"',
];
?>
<?php
/* El contenido de esta página sale de la base y se edita desde el panel.
   Cada lectura lleva su texto de reserva: si la base no responde, o si
   alguien vacía un campo, la página se pinta con lo que decía antes.
   Una web sobre un viaje papal no puede quedarse muda porque falle MySQL. */
$paginaCms = $sitio->contenido('privacidad');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);

/* El HTML con formato del panel se filtra: editar un texto no puede dar el
   poder de ejecutar código en el navegador de un visitante. Mismo patrón que
   usa views/coleccion.php para el resto de páginas cuyo cuerpo entero vive
   en el CMS. */
$rico = static fn (?string $v): string => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));
?>

<main id="contenido">

<?php /* ── La portada de esta página ──────────────────────────────────────
         Sin foto, la franja es una banda roja lisa y compacta. En cuanto
         alguien elija una en el panel —Páginas → esta página → Cabecera—,
         la clase «--lisa» desaparece y la franja crece para mostrarla.

         Se quita la clase en lugar de dejarla siempre porque «--lisa» lleva
         min-height:0: con una fotografía dentro, la franja se colapsaría y
         la imagen no se vería. */ ?>
<?php $portada = $secciones['cabecera'] ?? []; ?>
<header class="cabecera-pagina<?= empty($portada['imagen_ruta']) ? ' cabecera-pagina--lisa' : '' ?>">
  <?php if (!empty($portada['imagen_ruta'])): ?>
    <div class="cabecera-pagina__media">
      <?= $sitio->imagen($portada, '', ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>
  <?php endif; ?>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Legal')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Política de privacidad')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Qué datos pedimos, para qué, cuánto tiempo los guardamos y cómo ejercer tus derechos.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Privacidad</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<?php /* ── El cuerpo de la página, entero desde el gestor ───────────────────
         Ni una palabra fija aquí: se pinta cada sección que exista para esta
         página, en el orden en que estén en Páginas → Privacidad, con el
         título y el texto que haya en cada una. Lo que no se ponga en el
         gestor, no sale en la página; lo que se ponga, sale tal cual.

         Es el mismo patrón que usa views/coleccion.php para las páginas cuyo
         cuerpo entero vive en el CMS. Sólo «cabecera» se trata aparte,
         porque es la franja de arriba, no un bloque de texto. */ ?>
<?php foreach ($secciones as $clave => $s): ?>
  <?php if ($clave === 'cabecera') { continue; } ?>
  <?php if (($s['titulo'] ?? '') === '' && ($s['texto'] ?? '') === '') { continue; } ?>

  <section class="seccion" id="<?= $esc($clave) ?>" aria-labelledby="t-<?= $esc($clave) ?>">
    <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">

      <?php if (($s['titulo'] ?? '') !== ''): ?>
        <header class="seccion__encabezado seccion__encabezado--mayor">
          <hr class="seccion__filete" data-reveal="line-draw">
          <?php if (($s['rotulo'] ?? '') !== ''): ?>
            <span class="rotulo"><?= $esc($s['rotulo']) ?></span>
          <?php endif; ?>
          <h2 class="titular--mayor" id="t-<?= $esc($clave) ?>" data-reveal="mask-lines">
            <span class="linea"><span><?= $esc($s['titulo']) ?></span></span>
          </h2>
        </header>
      <?php endif; ?>

      <?php if (($s['texto'] ?? '') !== ''): ?>
        <div class="texto-lectura"><?= $rico($s['texto']) ?></div>
      <?php endif; ?>

    </div></div></div>
  </section>
<?php endforeach; ?>
</main>
