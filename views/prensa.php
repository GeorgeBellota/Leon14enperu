<?php
/**
 * ============================================================================
 *  Prensa — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. Una web sobre un viaje papal no puede quedarse
 *  muda porque falle la base.
 *
 *  Las medidas son las del editable PRENSA.ai (mesa de 1440 x 2626). La hoja
 *  assets/css/paginas/prensa.css las reproduce con la unidad --u. El editable
 *  de las subpáginas no dibuja la cabecera del sitio, así que todo su
 *  contenido cae 76 px más abajo que en la mesa de trabajo.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Prensa · Viaje de León XIV al Perú',
    'descripcion' => 'Acreditación, contacto y condiciones de uso del material gráfico para los medios '
                   . 'que cubran la Visita Apostólica del Papa León XIV al Perú.',
    'ruta'        => 'prensa/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'article',
];

$paginaCms = $sitio->contenido('prensa');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);

/* ── Apagar una sección sí; quedarse en blanco, no ────────────────────────
   Una sección que el panel apaga no llega en $secciones y no se pinta. Pero
   «no llega» no siempre significa «apagada»: si la base no responde no llega
   NINGUNA, y entonces la página tiene que salir entera con sus textos de
   reserva. Una web sobre un viaje papal no puede quedarse muda porque falle
   MySQL.

   Antes la señal eran dos secciones concretas —«contacto-prensa» y
   «multimedia»—, que sólo existen desde el rediseño y servían para saber si
   la carga de contenido ya había pasado. Cumplía su papel mientras duró esa
   ventana, pero dejaba las dos atadas entre sí: apagar LAS DOS desde el panel
   hacía creer a la página que no había contenido, y reaparecían las dos con
   sus textos y sus marcos de relleno. Apagar una funcionaba; apagar las dos,
   no. Imposible de adivinar dentro de unos meses.

   Ahora la señal es que haya llegado algo. Si llegó algo, manda el panel y
   una sección apagada se queda apagada, sea cual sea y sean cuantas sean. */
$hayContenido = $secciones !== [];
$pinta        = static fn (string $s): bool => !$hayContenido || $hay($s);

/* ── Destinos que vienen del panel ────────────────────────────────────────
   En el panel los destinos se escriben cortos («contacto/»). Desde /prensa/
   un enlace relativo apuntaría a /prensa/contacto/, que no existe, así que
   se cuelgan de la raíz del sitio. Lo que ya venga absoluto, con esquema o
   como ancla, se respeta tal cual.

   Ojo con los nombres: la vista se ejecuta en el mismo ámbito que index.php
   y que _plantilla.php, así que una variable llamada $destino o $pieza
   pisaría las suyas —la ruta resuelta y la pieza del detalle— y rompería la
   página entera. Todo lo de aquí lleva nombre propio. */
$enlacePanel = static function (string $url) use ($sitio): string {
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    return preg_match('~^(?:https?:|mailto:|tel:|/|#)~i', $url) === 1
        ? $url
        : $sitio->enlace($url);
};

/* El icono de cámara de los marcos de Multimedia. Se dibuja una sola vez y
   se repite: son tres marcos iguales y el trazado es largo. Decorativo, así
   que el lector de pantalla se lo salta. */
ob_start(); ?>
<svg class="pr-multi__ico" viewBox="0 0 110.5 115" width="110.5" height="115" aria-hidden="true" focusable="false">
  <rect x="40.7" y="1.7" width="29.1" height="28.6" rx="5" fill="none" stroke="#E9E9E9" stroke-width="3.4"/>
  <path d="M41.5 31.2h7.2v6.3h-7.2zM61.8 31.2H69v6.3h-7.2z" fill="#E9E9E9"/>
  <rect x="29" y="37.2" width="52.5" height="24" rx="7" fill="#E9E9E9"/>
  <rect x="0" y="51.2" width="110.3" height="63" rx="11" fill="#E9E9E9"/>
  <circle cx="53.8" cy="78" r="34.5" fill="none" stroke="#1C1416" stroke-width="3.5"/>
  <circle cx="53.8" cy="78" r="28.6" fill="none" stroke="#1C1416" stroke-width="2.75"/>
  <circle cx="54.2" cy="80" r="6.6" fill="#1C1416"/>
  <circle cx="67" cy="70.5" r="3.2" fill="#1C1416"/>
  <path d="M4.4 61v40M105.9 61v40" stroke="#1C1416" stroke-width="2.4" stroke-linecap="round"/>
</svg>
<?php $iconoCamara = (string) ob_get_clean(); ?>

<main id="contenido">

  <?php /* ══════════════════════════════════════════════════════ HÉROE ════
       Banda de 582 px con la fotografía en duotono. La foto y los textos se
       editan en Páginas → Prensa → Cabecera de página.

       El editable NO dibuja rótulo encima del título: el campo «Rótulo» de
       esta sección sigue guardado en la base —dice «Para medios»— pero el
       diseño nuevo no lo pinta. */ ?>
  <section class="hero hero--page pr-hero">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/prensa/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/prensa/hero.jpg')) ?>"
             alt="Periodistas y cámaras de televisión durante una conferencia de prensa"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="hero__inner">
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Prensa')) ?></h1>

      <?php
      /* El editable pone en negrita la primera palabra de la bajada, hasta la
         coma. Del panel llega una línea corrida —es un campo de texto, no de
         HTML—, así que se parte por la primera coma para respetarlo. Si el
         texto no lleva ninguna, se pinta entero y ya está. */
      $bajada = $campo('cabecera', 'texto', 'Acreditación, contacto y condiciones de uso del material gráfico.');
      $trozos = preg_split('/,\s*/u', $bajada, 2) ?: [$bajada];
      ?>
      <p class="hero__sub">
        <strong><?= $esc($trozos[0]) ?></strong><?php if (isset($trozos[1])): ?>, <?= $esc($trozos[1]) ?><?php endif; ?>
      </p>
    </div>
  </section>

  <?php /* Todo el cuerpo va sobre la banda gris #E9E9E9 del editable. */ ?>
  <div class="pr-cuerpo">

    <?php /* ═════════════════════════════════════════════ ACREDITACIÓN ════
         Texto a la izquierda y fotografía a la derecha. El rótulo es el
         estado del trámite («PROCESO AÚN NO HABILITADO»): se edita en el
         panel, que es donde habrá que cambiarlo el día que se abra. */ ?>
    <?php if ($pinta('como-acreditarse')): ?>
    <section class="pr-acred" aria-labelledby="t-acreditacion">
      <div class="pr-wrap pr-acred__grid">

        <div class="pr-acred__texto">
          <?php $estado = $campo('como-acreditarse', 'rotulo', 'PROCESO AÚN NO HABILITADO'); ?>
          <?php if ($estado !== ''): ?>
            <p class="pr-acred__kicker"><?= $esc($estado) ?></p>
          <?php endif; ?>

          <h2 class="pr-h2" id="t-acreditacion"><?= $esc($campo('como-acreditarse', 'titulo', 'Acreditación')) ?></h2>

          <?php
          /* Campo con formato: admite <p>, <strong> y <br>, ya limpiados por
             HtmlSeguro al guardarse. Los <br> del respaldo clavan el corte de
             línea del editable; por debajo de 1024 px la hoja los oculta y el
             texto vuelve a fluir. */
          $copia = $campo('como-acreditarse', 'texto', '');
          ?>
          <?php if ($copia !== ''): ?>
            <div class="pr-acred__copy"><?= $copia ?></div>
          <?php else: ?>
            <p class="pr-acred__copy">El <strong>Ministerio de Relaciones <br>Exteriores</strong> estará a cargo <br>del proceso de acreditación <br>de prensa. La fecha de inicio <br>y los requisitos se <br><strong>comunicarán <br>oportunamente a través de <br>los canales oficiales: </strong>página <br>web de la Cancillería y la <br>web oficial de la visita del <br>Papa León XIV.</p>
          <?php endif; ?>
        </div>

        <figure class="pr-acred__foto">
          <?php ob_start(); ?>
          <picture>
            <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/prensa/p01.webp')) ?>" type="image/webp">
            <img src="<?= $esc($sitio->asset('assets/img/rediseno/prensa/p01.jpg')) ?>"
                 alt="Una periodista consulta en su teléfono la página oficial de la visita del Papa León XIV"
                 width="1550" height="946" loading="lazy" decoding="async">
          </picture>
          <?php $respaldoFoto = (string) ob_get_clean(); ?>
          <?= $sitio->imagen($secciones['como-acreditarse'] ?? [], $respaldoFoto, ['sizes' => '(min-width:1024px) 54vw, 100vw']) ?>
        </figure>

      </div>
    </section>
    <?php endif; ?>

    <?php /* ═══════════════════════════════ USO DE IMÁGENES Y DEL ESCUDO ══
         Una fila por apartado: el titular en dorado a la izquierda y la
         explicación a la derecha. Los apartados son bloques, así que en el
         panel se añaden, se quitan y se reordenan; la hoja tiene medidas
         clavadas para los cuatro del editable y una altura mínima común
         para los que se añadan después.

         El salto de línea que se escriba en el panel se respeta (nl2br):
         es lo que permite clavar el corte de línea del editable sin meter
         etiquetas en un campo que no las admite. */ ?>
    <?php if ($pinta('uso-imagenes-escudo')): ?>
    <?php
    $apartados = $bloques('uso-imagenes-escudo', [
        ['titulo' => 'Retrato pontificio', 'texto' => "El retrato oficial se publica sin recortes sobre el rostro, sin filtros de color y sin\ntexto superpuesto. Crédito: Santa Sede."],
        ['titulo' => 'Escudo pontificio',  'texto' => "Conserva siempre sus esmaltes propios. No se recorta, no se deforma y no se\nusa como elemento decorativo repetido."],
        ['titulo' => 'Citas',              'texto' => "Las palabras del Santo Padre se citan por su fuente oficial. Este sitio no publica\nninguna frase suya que no conste en un documento de la Santa Sede."],
        ['titulo' => 'Kit de prensa',      'texto' => 'Dosier, logotipos y fotografías en alta resolución.'],
    ]);
    ?>
    <section class="pr-usos" aria-labelledby="t-usos">
      <div class="pr-wrap">
        <h2 class="pr-h2" id="t-usos"><?= $esc($campo('uso-imagenes-escudo', 'titulo', 'Uso de imágenes y del escudo')) ?></h2>

        <?php /* El editable no trae entradilla. Si alguien la escribe en el
                 panel se pinta aquí, en lugar de perderse sin avisar. */ ?>
        <?php $entradaUsos = $campo('uso-imagenes-escudo', 'texto', ''); ?>
        <?php if ($entradaUsos !== ''): ?>
          <div class="pr-usos__lead"><?= $entradaUsos ?></div>
        <?php endif; ?>

        <dl class="datalist pr-usos__lista">
          <?php foreach ($apartados as $apartado): ?>
            <?php /* Si el apartado lleva destino, el titular se vuelve enlace.
                     Misma regla que en Subsidios: un apartado puede apuntar a
                     un documento sin salir del panel. */ ?>
            <?php $destinoAp = $sitio->enlaceDelPanel((string) ($apartado['enlace_url'] ?? '')); ?>
            <div class="datalist__row">
              <dt class="datalist__key">
                <?php if ($destinoAp !== ''): ?>
                  <a href="<?= $esc($destinoAp) ?>"<?= $sitio->esExterno($destinoAp) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $esc((string) ($apartado['titulo'] ?? '')) ?></a>
                <?php else: ?>
                  <?= $esc((string) ($apartado['titulo'] ?? '')) ?>
                <?php endif; ?>
              </dt>
              <dd class="datalist__val"><?= nl2br($esc((string) ($apartado['texto'] ?? ''))) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
      </div>
    </section>
    <?php endif; ?>

    <?php /* ══════════════════════════════════════════════════ CONTACTO ══
         Un único botón centrado. El texto y el destino se editan en el
         panel: hoy lleva a /contacto/, y el día que haya un correo propio
         de prensa se cambia ahí sin tocar código. */ ?>
    <?php if ($pinta('contacto-prensa')): ?>
    <?php
    $textoBoton = $campo('contacto-prensa', 'cta_texto', 'Contacto');
    $urlBoton   = $enlacePanel($campo('contacto-prensa', 'cta_url', '')) ?: $sitio->enlace('contacto/');
    ?>
    <section class="pr-cta" aria-label="Contacto de prensa">
      <div class="pr-wrap">
        <a class="btn pr-cta__btn" href="<?= $esc($urlBoton) ?>"><?= $esc($textoBoton) ?></a>
      </div>
    </section>
    <?php endif; ?>

    <?php /* ════════════════════════════════════════════════ MULTIMEDIA ══
         Tres marcos negros con el icono de cámara. En el editable están
         VACÍOS a propósito: el material gráfico todavía no está cerrado y
         el diseño reserva el hueco.

         Por eso la sección se migra sin bloques y lo que se ve son estos
         tres marcos de reserva. En cuanto alguien suba una fotografía en
         Páginas → Prensa → Multimedia, los marcos los sustituyen las piezas
         reales: la imagen llena el marco y el pie lleva su crédito. */ ?>
    <?php if ($pinta('multimedia')): ?>
    <?php
    $marcos = $bloques('multimedia', [
        ['texto' => 'Autor: Nombre y apellidos'],
        ['texto' => 'Autor: Nombre y apellidos'],
        ['texto' => 'Autor: Nombre y apellidos'],
    ]);
    ?>
    <section class="pr-multimedia" aria-labelledby="t-multimedia">
      <div class="pr-wrap">
        <h2 class="pr-h2" id="t-multimedia"><?= $esc($campo('multimedia', 'titulo', 'Multimedia')) ?></h2>

        <ul class="pr-multi__grid">
          <?php foreach ($marcos as $marco): ?>
            <?php $pie = trim((string) ($marco['texto'] ?? '')); ?>
            <li>
              <figure class="pr-multi">
                <div class="pr-multi__marco">
                  <?= $sitio->imagen($marco, $iconoCamara, ['sizes' => '(min-width:1024px) 27vw, 90vw']) ?>
                </div>
                <?php if ($pie !== ''): ?>
                  <figcaption class="pr-multi__pie"><?= $esc($pie) ?></figcaption>
                <?php endif; ?>
              </figure>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
    <?php endif; ?>

  </div><!-- /.pr-cuerpo -->

</main>
