<?php
/**
 * Vista de la página «donativo».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Donativo · Viaje de León XIV al Perú',
    'descripcion' => 'Cómo colaborar con un donativo con los trabajos organizativos y pastorales del viaje apostólico del Papa León XIV al Perú.',
    'ruta'        => 'donativo/',
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
$paginaCms = $sitio->contenido('donativo');
$secciones = $paginaCms['secciones'] ?? [];

$campo = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$hay = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);
?>

<main id="contenido">

<header class="cabecera-pagina">
  <div class="cabecera-pagina__media">
    <?php /* ── La portada de esta página ──────────────────────────────
         Sale del panel: Páginas → esta página → Cabecera. Se puede
         elegir una foto para escritorio y otra para móvil.

         Lo que va aquí abajo es el RESPALDO: la fotografía que la
         página traía escrita a mano. Mientras nadie elija otra en el
         panel se sigue viendo ésta, así que pasar la portada al
         gestor no cambió el aspecto de nada el día del despliegue. */ ?>
      <?php ob_start(); ?>
      <picture>
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-donativo-640.webp 640w, ../assets/img/fotos/cab-donativo-1024.webp 1024w, ../assets/img/fotos/cab-donativo-1486.webp 1486w">
      <img src="../assets/img/fotos/cab-donativo-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-donativo-640.jpg 640w, ../assets/img/fotos/cab-donativo-1024.jpg 1024w, ../assets/img/fotos/cab-donativo-1486.jpg 1486w" width="1486" height="637" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Cómo ayudar')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Con un donativo')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Los donativos se destinan a los trabajos organizativos y pastorales de la visita, y se gestionan con responsabilidad y transparencia.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><a href="<?= $esc($sitio->enlace('voluntariado/')) ?>">Cómo ayudar</a></li>
        <li><span aria-current="page">Donativo</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" aria-labelledby="t-donativo">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-6 col-d-7">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('todavia-canal-donativos', 'rotulo', 'Estado')) ?></span>
      <h2 class="titular--mayor" id="t-donativo" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('todavia-canal-donativos', 'titulo', 'Todavía no hay canal de donativos abierto')) ?></span></span></h2>
    </header>
    <div class="estado-actual" data-reveal="fade-rise">
      <p class="estado-actual__frase">El canal oficial de donativos se anunciará en esta página.</p>
      <p class="estado-actual__meta"><span>Última actualización: 13 de agosto de 2026</span></p>
    </div>
      <div class="texto-lectura">
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
        <p>Este sitio <strong>no tiene pasarela de pago</strong> y no la tendrá hasta que la organización defina la titularidad de la cuenta y el tratamiento fiscal de los aportes. Habilitar cobros antes de eso sería irresponsable.</p>
        <p>Cuando el canal esté abierto, aparecerá aquí con la cuenta oficial, el nombre exacto del titular y el procedimiento para pedir constancia del aporte.</p>
        <p><strong>Desconfía de cualquier cuenta que circule antes de ese anuncio</strong>, aunque venga con el escudo y con fotos del Santo Padre. Si no está publicada en esta página o por la Conferencia Episcopal Peruana, no es oficial.</p>
      </div>
  </div></div></div>
</section>

<section class="seccion seccion--tinte seccion--pastel-acento" aria-labelledby="t-destino">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('destinan', 'rotulo', 'Destino')) ?></span>
      <h2 class="titular--mayor" id="t-destino" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('destinan', 'titulo', 'A qué se destinan')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Un donativo no financia el viaje del Santo Padre: financia el trabajo de acoger a quienes vienen a encontrarse con él.</p>
    </header>
    <div class="reticula">
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise"><h3 class="tarjeta__titulo">Acogida</h3><p class="tarjeta__texto">Agua, señalética, puntos de información y atención básica en los recintos.</p></article>
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise"><h3 class="tarjeta__titulo">Voluntariado</h3><p class="tarjeta__texto">Formación, indumentaria, credenciales y traslado de los voluntarios.</p></article>
      <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente" data-reveal="fade-rise"><h3 class="tarjeta__titulo">Pastoral</h3><p class="tarjeta__texto">Materiales de oración y catequesis, y su distribución a parroquias de todo el país.</p></article>
    </div>
    <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('transparencia/')) ?>">Cómo se rinden cuentas <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
  </div>
</section>

<section class="seccion" aria-labelledby="t-aviso-don">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('avisame-cuando-abra', 'rotulo', 'Aviso')) ?></span>
      <h2 id="t-aviso-don" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('avisame-cuando-abra', 'titulo', 'Avísame cuando se abra el canal')) ?></span></span></h2>
        <!-- COPY PENDIENTE DE VALIDACIÓN -->
      <p>Un solo correo, con la cuenta oficial y el procedimiento.</p>
    </header>
        <form class="pila" data-form="aviso" data-origen="donativo" action="#" method="post" novalidate>
          <div class="campo">
            <label class="campo__etiqueta" for="correo-donativo">Correo electrónico</label>
            <input type="email" id="correo-donativo" name="correo" autocomplete="email" required data-valida="requerido correo" placeholder="tunombre@correo.com">
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="consent-donativo" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>.</span>
          </label>
          <p class="trampa" aria-hidden="true"><label for="web-donativo">No rellenar</label><input type="text" id="web-donativo" name="sitio-web" tabindex="-1" autocomplete="off"></p>
          <div><button class="btn btn--secundario" type="submit">Avísame</button></div>
          <p data-mensaje role="status" aria-live="polite"></p>
        </form>
  </div></div></div>
</section>
</main>
