<?php
/**
 * Vista de la página «cookies».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Cookies · leon14enperu.com',
    'descripcion' => 'Este sitio no usa cookies. Solo almacenamiento del navegador para el borrador del formulario y el aviso de la portada.',
    'ruta'        => 'cookies/',
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
$paginaCms = $sitio->contenido('cookies');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
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
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Cookies')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'La respuesta corta: este sitio no usa cookies. La larga explica qué guarda tu navegador y por qué.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Cookies</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-label="Texto legal">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <h2 class="legal__titulo">Este sitio no instala cookies</h2>
      <p>Ni propias ni de terceros. No hay analítica, no hay píxeles de seguimiento, no hay publicidad y no hay perfilado. Por eso no verás un banner de consentimiento: no habría nada que consentir.</p>
      <h2 class="legal__titulo">Lo que sí guarda tu navegador</h2>
      <p>El sitio usa el <strong>almacenamiento del navegador</strong> para dos cosas, ambas técnicas y ambas dentro de tu propio equipo. Este almacenamiento no se envía al servidor en cada petición, a diferencia de una cookie.</p>
      <p><strong>1. El borrador del formulario de voluntariado.</strong> Clave <code>l14-inscripcion-borrador</code>, en <em>localStorage</em>. Guarda lo que hayas escrito en el formulario para que no lo pierdas si te interrumpen. Solo existe en tu navegador, no se envía a ningún servidor, y el formulario muestra un aviso visible cuando hay un borrador guardado, con un botón para borrarlo.</p>
      <p><strong>2. La cuenta del aviso de la portada.</strong> Clave <code>l14-cta-vistas</code>, en <em>sessionStorage</em>. Contiene un número: cuántas veces se te ha mostrado el aviso de voluntariado de la página de inicio, para no repetírtelo indefinidamente. No identifica a nadie y se borra sola al cerrar la pestaña.</p>
      <h2 class="legal__titulo">Cómo eliminarlo</h2>
      <p>Borrar los datos de navegación de tu navegador para este sitio elimina ambas claves. La cuenta del aviso desaparece además al cerrar la pestaña, sin hacer nada. También puedes borrar el borrador del formulario con el botón «Borrar el borrador» que aparece sobre él.</p>
      <h2 class="legal__titulo">Recursos externos</h2>
      <p>El sitio carga tipografías desde Google Fonts y dos librerías de animación desde jsDelivr. Esos servicios reciben tu dirección IP por el hecho de servir el archivo, como cualquier recurso de internet, pero este sitio no les envía ningún dato tuyo ni instala cookies suyas.</p>
      <h2 class="legal__titulo">Si esto cambia</h2>
      <p>Si en el futuro se añade analítica o cualquier tecnología que sí requiera consentimiento, se implantará el aviso correspondiente y esta página se actualizará antes de activarla.</p>
    </div>
  </div></div></div>
</section>
</main>
