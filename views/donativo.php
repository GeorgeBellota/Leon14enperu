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
    /* Los botones de copiar de las cuentas. El módulo no pinta nada si el
       navegador no da portapapeles, así que cargarlo nunca estorba. */
    'scripts'     => ['assets/js/colecta.js'],
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

/* El HTML con formato del panel se filtra antes de pintarlo: editar un texto
   no puede dar el poder de ejecutar código en el navegador de un visitante. */
$rico = static fn (?string $v): string
    => \Intranet\Core\HtmlSeguro::limpiar((string) ($v ?? ''));

/* ── Las cuentas salen de la portada, no de esta página ────────────────────
   La sección «colecta» vive en «home» y aquí se lee de ahí. No hay una segunda
   copia en la base, y por eso no puede haber dos versiones distintas de un
   número de cuenta según por dónde entres.

   Si la base no responde, esto queda vacío y el parcial no pinta nada: unas
   cuentas bancarias escritas a mano en el código serían imposibles de corregir
   sin un despliegue, y una cuenta vieja publicada es peor que ninguna. */
$colectaSecciones = $sitio->contenido('home')['secciones'] ?? [];
?>

<main id="contenido">

<header class="cabecera-pagina cabecera-pagina--colecta">
  <div class="cabecera-pagina__media">
    <?php /* ── La portada de esta página ──────────────────────────────
         Sale del panel: Páginas → esta página → Cabecera. Se puede
         elegir una foto para escritorio y otra para móvil.

         Lo que va aquí abajo es el RESPALDO: la fotografía que la
         página traía escrita a mano. Mientras nadie elija otra en el
         panel se sigue viendo ésta, así que pasar la portada al
         gestor no cambió el aspecto de nada el día del despliegue. */ ?>
      <?php /* La imagen de la Colecta Nacional que envió la Conferencia
               Episcopal. Llegó como PNG de 1664×640 y 2,1 MB, que en la
               cabecera de una página es una barbaridad: en un teléfono con
               datos móviles son varios segundos mirando un hueco gris. Se
               generó de ella la familia habitual del sitio —640, 1024 y 1486,
               en webp y jpg— y ahí el peor caso baja a 143 KB y el que se sirve
               a un móvil, a 32.

               El original se queda en assets/img/leon14enperu-colecta-3.png
               como fuente, por si hay que rehacer los recortes.

               Va sin texto alternativo a propósito: el titular y la bajada que
               se pintan encima ya dicen lo que hay que saber, y describir la
               composición otra vez sólo añadiría ruido a un lector de pantalla.
               Es el mismo criterio que traía la cabecera anterior. */ ?>
      <?php ob_start(); ?>
      <picture>
      <source type="image/webp" sizes="100vw" srcset="../assets/img/fotos/cab-colecta-640.webp 640w, ../assets/img/fotos/cab-colecta-1024.webp 1024w, ../assets/img/fotos/cab-colecta-1486.webp 1486w">
      <img src="../assets/img/fotos/cab-colecta-1024.jpg" sizes="100vw" srcset="../assets/img/fotos/cab-colecta-640.jpg 640w, ../assets/img/fotos/cab-colecta-1024.jpg 1024w, ../assets/img/fotos/cab-colecta-1486.jpg 1486w" width="1486" height="572" alt="" fetchpriority="high" decoding="async">
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
      <?php /* El recuadro de estado que había aquí decía «El canal oficial de
               donativos se anunciará en esta página» y llevaba una fecha de
               última actualización escrita a mano. Las dos cosas dejaron de ser
               ciertas el día que la Conferencia Episcopal abrió la colecta, y
               una fecha en el código es una fecha que nadie actualiza. Ahora el
               estado lo dicen el titular y el texto, y los dos se editan desde
               el panel.

               El texto ya no está escrito aquí: sale de la base, como el resto
               de la página. Lo de abajo es sólo el respaldo por si no responde.

               La frase que avisa de las cuentas falsas se conserva casi palabra
               por palabra: era buena antes y sigue siéndolo ahora, sólo que
               antes decía «cualquier cuenta» y ahora dice «cualquier otra»,
               porque ya hay unas oficiales publicadas. */ ?>
      <div class="texto-lectura" data-reveal="fade-rise">
        <?= $rico($campo('todavia-canal-donativos', 'texto',
            '<p>Este sitio <strong>no tiene pasarela de pago</strong>: el aporte se hace por depósito o transferencia a las cuentas oficiales de la Conferencia Episcopal Peruana, que están publicadas aquí abajo.</p>'
          . '<p><strong>Desconfía de cualquier otra cuenta que circule</strong>, aunque venga con el escudo y con fotos del Santo Padre. Si no está publicada en esta página o por la Conferencia Episcopal Peruana, no es oficial.</p>')) ?>
      </div>
  </div></div></div>
</section>

<?php /* Las cuentas. Mismo parcial que la portada y misma sección de la base:
         un solo sitio donde corregir un dígito.

         Aquí van SIN titular ni texto de presentación —$colectaSoloCuentas—
         porque la sección de arriba acaba de presentar la colecta, y repetirlo
         sería decir dos veces lo mismo con dos titulares seguidos. */ ?>
<?php
$colectaSoloCuentas = true;
$colectaConAncla    = true;
/* «Las cuentas oficiales» no es una frase inventada para rellenar: es
   exactamente lo que el párrafo de arriba acaba de decir —que sólo son
   oficiales las publicadas en esta página o por la Conferencia Episcopal— y le
   pone nombre a lo que sigue. Sin encabezado, dos tarjetas con números
   quedaban flotando entre dos titulares de sección. */
$colectaTitulo = 'Las cuentas oficiales';
require dirname(__DIR__) . '/assets/parciales/colecta.php';
?>

<section class="seccion seccion--tinte seccion--pastel-acento" aria-labelledby="t-destino">
  <div class="contenedor">
    <header class="seccion__encabezado seccion__encabezado--mayor">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('destinan', 'rotulo', 'Destino')) ?></span>
      <h2 class="titular--mayor" id="t-destino" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('destinan', 'titulo', 'A qué se destinan')) ?></span></span></h2>
      <?php /* El párrafo estaba escrito aquí con una nota de «copy pendiente de
               validación». Ahora sale de la base y se corrige desde el panel sin
               desplegar; lo de abajo es el mismo texto que había, de respaldo. */ ?>
      <div class="texto-lectura">
        <?= $rico($campo('destinan', 'texto',
            '<p>Un donativo no financia el viaje del Santo Padre: financia el trabajo de acoger a quienes vienen a encontrarse con él.</p>')) ?>
      </div>
    </header>
    <?php /* Las tres tarjetas salen ahora de la base y se editan desde el panel.
             Antes estaban escritas aquí y no había forma de tocarlas sin
             desplegar. Lo que queda escrito es el respaldo: exactamente las tres
             que había, palabra por palabra.

             El icono es ornamento —va con aria-hidden y no añade información que
             se pierda si no se ve— pero convierte tres párrafos bajo un filete
             en tres piezas con la forma del resto del sitio.

             Se comprueba contra una lista blanca: el panel guarda el nombre del
             símbolo como texto libre, y un <use href="#loquesea"> con un nombre
             que no existe deja un hueco. Con la lista, una errata cae en el
             corazón y la tarjeta sigue entera. */ ?>
    <?php
    $iconosDestino = ['i-acogida', 'i-manos', 'i-alianza', 'i-ofrenda', 'i-corazon', 'i-lirio', 'i-resguardo', 'i-comunicacion', 'i-logistica', 'i-auxilios'];

    $destinos = \Intranet\Publico\Sitio::bloques($secciones, 'destinan', [
        ['icono' => 'i-acogida', 'titulo' => 'Acogida',      'texto' => 'Agua, señalética, puntos de información y atención básica en los recintos.'],
        ['icono' => 'i-manos',   'titulo' => 'Voluntariado',  'texto' => 'Formación, indumentaria, credenciales y traslado de los voluntarios.'],
        ['icono' => 'i-ofrenda', 'titulo' => 'Pastoral',      'texto' => 'Materiales de oración y catequesis, y su distribución a parroquias de todo el país.'],
    ]);
    ?>
    <div class="reticula">
      <?php foreach ($destinos as $i => $d): ?>
        <?php
        $icono = trim((string) ($d['icono'] ?? ''));
        if (!in_array($icono, $iconosDestino, true)) { $icono = 'i-corazon'; }
        ?>
        <article class="col-m-4 col-t-2 col-d-4 tarjeta--pendiente destino-aporte"
                 data-reveal="fade-rise" data-reveal-delay="<?= $esc(number_format($i * 0.07, 2, '.', '')) ?>">
          <svg class="destino-aporte__icono" aria-hidden="true" focusable="false"><use href="#<?= $esc($icono) ?>"/></svg>
          <h3 class="tarjeta__titulo"><?= $esc($d['titulo'] ?? '') ?></h3>
          <p class="tarjeta__texto"><?= $esc(strip_tags((string) ($d['texto'] ?? ''))) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="seccion__pie"><a class="enlace-flecha" href="<?= $esc($sitio->enlace('transparencia/')) ?>">Cómo se rinden cuentas <svg aria-hidden="true"><use href="#i-flecha"/></svg></a></p>
  </div>
</section>

<section class="seccion" aria-labelledby="t-aviso-don">
  <div class="contenedor"><div class="reticula"><div class="col-m-4 col-t-4 col-d-6">
    <header class="seccion__encabezado">
      <hr class="seccion__filete" data-reveal="line-draw">
      <span class="rotulo"><?= $esc($campo('avisame-cuando-abra', 'rotulo', 'Aviso')) ?></span>
      <h2 id="t-aviso-don" data-reveal="mask-lines"><span class="linea"><span><?= $esc($campo('avisame-cuando-abra', 'titulo', 'Avísame de las novedades de la colecta')) ?></span></span></h2>
      <?php /* El formulario se queda, con otro encargo. Prometía avisar «cuando
               se abra el canal», y el canal ya está abierto: dejarlo así era
               pedirle a la gente que se apunte a un aviso que nunca llegará.
               Lo que sigue pendiente es el procedimiento para pedir constancia
               del aporte, y eso sí merece un correo cuando lo publiquen. */ ?>
      <div class="texto-lectura">
        <?= $rico($campo('avisame-cuando-abra', 'texto',
            '<p>Un solo correo cuando haya novedades, como el procedimiento para pedir constancia del aporte.</p>')) ?>
      </div>
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
