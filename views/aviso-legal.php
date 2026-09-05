<?php
/**
 * Vista de la página «aviso-legal».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Aviso legal · leon14enperu.com',
    'descripcion' => 'Titularidad, condiciones de uso y propiedad intelectual del sitio informativo del viaje apostólico de León XIV al Perú.',
    'ruta'        => 'aviso-legal/',
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
$paginaCms = $sitio->contenido('aviso-legal');
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
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Aviso legal')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Titularidad del sitio, condiciones de uso y propiedad intelectual de los contenidos.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Aviso legal</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-label="Texto legal">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Los datos de titularidad de este apartado los debe completar la organización antes de la publicación del sitio. Aparecen entre corchetes.</p>
      <h2 class="legal__titulo">Titular del sitio</h2>
      <p>Denominación: <strong>[RAZÓN SOCIAL POR CONFIRMAR]</strong>. Domicilio: <strong>[DOMICILIO POR CONFIRMAR]</strong>. Registro: <strong>[DATOS REGISTRALES POR CONFIRMAR]</strong>. Correo de contacto: <strong>[CORREO POR CONFIRMAR]</strong>.</p>
      <h2 class="legal__titulo">Objeto</h2>
      <p>Este sitio tiene una finalidad exclusivamente informativa y pastoral: dar a conocer el viaje apostólico de Su Santidad el Papa León XIV al Perú, previsto del 11 al 16 de noviembre de 2026, y canalizar la participación de los fieles como voluntarios o colaboradores. No es un sitio de venta ni de intermediación comercial.</p>
      <h2 class="legal__titulo">Información no oficial hasta su confirmación</h2>
      <p>El programa del viaje lo publica la Oficina de Prensa de la Santa Sede. Mientras no exista publicación oficial, los contenidos de este sitio referidos a horarios, recintos, aforos o condiciones de acceso se presentan expresamente como no confirmados. El titular no responde de las decisiones que se tomen a partir de información marcada como pendiente.</p>
      <h2 class="legal__titulo">Propiedad intelectual</h2>
      <p>El retrato pontificio y el escudo de Su Santidad son propiedad de la Santa Sede y se reproducen aquí con finalidad informativa. Las fotografías tienen el crédito indicado en cada pie. Los textos, el diseño y el código de este sitio pertenecen a su titular o a quien los haya cedido. Su reproducción total o parcial con fines comerciales requiere autorización previa por escrito.</p>
      <h2 class="legal__titulo">Enlaces a terceros</h2>
      <p>Este sitio enlaza a la web de la Santa Sede con finalidad informativa. El titular no controla ni responde de los contenidos de sitios ajenos.</p>
      <h2 class="legal__titulo">Ley aplicable</h2>
      <p>Este aviso se rige por la legislación de la República del Perú. <strong>[FUERO POR CONFIRMAR]</strong>.</p>
    </div>
  </div></div></div>
</section>
</main>
