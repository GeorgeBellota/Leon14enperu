<?php
/**
 * Vista de la página «transparencia».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Transparencia · Viaje de León XIV al Perú',
    'descripcion' => 'Compromiso de rendición de cuentas de los aportes recibidos para el viaje apostólico del Papa León XIV al Perú.',
    'ruta'        => 'transparencia/',
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
$paginaCms = $sitio->contenido('transparencia');
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
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Rendición de cuentas')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Transparencia')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Quien da algo tiene derecho a saber en qué se usó. Este es el compromiso, escrito antes de recibir el primer aporte.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Transparencia</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-transp">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-8">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('publicara-cuando', 'rotulo', 'Compromiso')) ?></span>
      <h2 class="titular--mayor" id="t-transp" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('publicara-cuando', 'titulo', 'Qué se publicará y cuándo')) ?></span></span></h2>
    </header>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Todavía no hay nada que rendir: el canal de donativos no está abierto y no se ha recibido ningún aporte a través de este sitio. Lo que sí puede escribirse desde ya es el compromiso.</p>
      </div>
    <ul class="actos sep-l">
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Un informe económico del viaje</h3><p class="acto__texto">Con el total recibido y el desglose por partidas: acogida, voluntariado, pastoral y logística. Se publicará en esta página.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Relación de patrocinadores</h3><p class="acto__texto">Con el tipo de aporte de cada uno. Los donativos de personas particulares no se publican con nombre.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Destino del remanente</h3><p class="acto__texto">Si sobra dinero, se dirá cuánto y a qué obra de la Iglesia en el Perú se destinó.</p></li>
      <li class="acto" data-reveal="fade-rise"><h3 class="acto__titulo">Plazo</h3><p class="acto__texto"><strong>[PLAZO POR CONFIRMAR]</strong> después de la conclusión del viaje.</p></li>
    </ul>
    <div class="aviso sep-l"><p class="aviso__titulo">Responsable</p><p>La entidad responsable de la gestión y de la rendición de cuentas es <strong>[RESPONSABLE POR CONFIRMAR]</strong>. Este dato se publicará antes de abrir cualquier canal de aportes.</p></div>
  </div></div></div>
</section>
</main>
