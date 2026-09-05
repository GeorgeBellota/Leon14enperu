<?php
/**
 * Vista de la página «contacto».
 *
 * Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 * views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Contacto · Viaje de León XIV al Perú',
    'descripcion' => 'Escribe a la organización del viaje apostólico del Papa León XIV al Perú: voluntariado, patrocinios, prensa y consultas generales.',
    'ruta'        => 'contacto/',
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
$paginaCms = $sitio->contenido('contacto');
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
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-contacto-480.webp 480w, ../assets/img/fotos/cab-contacto-596.webp 596w">
      <img src="../assets/img/fotos/cab-contacto-596.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-contacto-480.jpg 480w, ../assets/img/fotos/cab-contacto-596.jpg 596w" width="596" height="255" alt="" fetchpriority="high" decoding="async">
    </picture>
      <?php $respaldoPortada = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoPortada, ['sizes' => '100vw', 'prioridad' => true]) ?>
  </div>
  <div class="cabecera-pagina__contenido contenedor">
    <div class="cabecera-pagina__bloque">
      <span class="rotulo rotulo--claro"><?= $esc($campo('cabecera', 'rotulo', 'Organización del viaje')) ?></span>
      <h1 class="cabecera-pagina__titulo"><?= $esc($campo('cabecera', 'titulo', 'Contacto')) ?></h1>
      <p class="cabecera-pagina__bajada"><?= $esc($campo('cabecera', 'texto', 'Cuéntanos qué necesitas y te respondemos. Si tu consulta es sobre el programa, quizá ya esté resuelta en las preguntas frecuentes.')) ?></p>
      <nav class="migas" aria-label="Migas de pan">
        <ol>
        <li><a href="<?= $esc($sitio->enlace('')) ?>">Inicio</a></li>
        <li><span aria-current="page">Contacto</span></li>
        </ol>
      </nav>
    </div>
  </div>
</header>

<section class="seccion" id="canales" aria-labelledby="t-contacto">
  <div class="contenedor"><div class="reticula">
    <div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('escribenos', 'rotulo', 'Formulario')) ?></span>
      <h2 id="t-contacto" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('escribenos', 'titulo', 'Escríbenos')) ?></span></span></h2>
    </header>
      <form class="formulario" data-form="aviso" data-origen="contacto" action="#" method="post" novalidate>
        <div class="paso__rejilla">
          <div class="paso__rejilla paso__rejilla--dos">
            <div class="campo">
              <label class="campo__etiqueta" for="c-nombre">Nombre y apellidos *</label>
              <input type="text" id="c-nombre" name="nombre" autocomplete="name" required data-valida="requerido">
            </div>
            <div class="campo">
              <label class="campo__etiqueta" for="c-correo">Correo electrónico *</label>
              <input type="email" id="c-correo" name="correo" autocomplete="email" required data-valida="requerido correo">
            </div>
          </div>
          <div class="campo">
            <label class="campo__etiqueta" for="c-asunto">Motivo *</label>
            <span class="campo__selector">
              <select id="c-asunto" name="asunto" required data-valida="requerido">
                <option value="">Elige uno</option>
                <option>Voluntariado</option><option>Patrocinios</option><option>Donativo</option>
                <option>Prensa</option><option>Materiales de pastoral</option><option>Otra consulta</option>
              </select>
              <svg aria-hidden="true"><use href="#i-chevron"/></svg>
            </span>
          </div>
          <div class="campo">
            <label class="campo__etiqueta" for="c-mensaje">Tu mensaje *</label>
            <textarea id="c-mensaje" name="mensaje" rows="6" required data-valida="requerido"></textarea>
          </div>
          <label class="casilla">
            <input type="checkbox" name="consentimiento" id="c-consent" required>
            <span class="casilla__texto">He leído y acepto la <a href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>. Usaremos tus datos únicamente para responderte.</span>
          </label>
        </div>
        <p class="trampa" aria-hidden="true"><label for="c-web">No rellenar</label><input type="text" id="c-web" name="sitio-web" tabindex="-1" autocomplete="off"></p>
        <div class="form-acciones"><button class="btn btn--primario" type="submit">Enviar mensaje</button></div>
        <p data-mensaje role="status" aria-live="polite"></p>
      </form>
    </div>

    <div class="col-m-4 col-t-2 col-d-5">
      <div class="aviso sep-l">
        <p class="aviso__titulo">Canales directos</p>
        <p>Voluntariado: <strong>[CORREO POR CONFIRMAR]</strong><br>Patrocinios: <strong>[CORREO POR CONFIRMAR]</strong><br>Prensa: <strong>[CORREO POR CONFIRMAR]</strong></p>
        <p>Los canales oficiales en redes sociales se anunciarán en este sitio. Hasta entonces, no existe ninguna cuenta oficial de la visita.</p>
      </div>
      <div class="aviso sep-l">
        <p class="aviso__titulo">Antes de escribir</p>
        <p>Si preguntas por horarios, entradas o inscripciones para asistir, la respuesta está en las <a href="<?= $esc($sitio->enlace('preguntas-frecuentes/')) ?>">preguntas frecuentes</a>: el programa no se ha publicado.</p>
      </div>
    </div>
  </div></div>
</section>
</main>
