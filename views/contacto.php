<?php
/**
 * ============================================================================
 *  Contacto — rediseño 2026.
 * ============================================================================
 *
 *  Sólo el contenido. El <head>, la cabecera, el pie y los scripts los pone
 *  views/_plantilla.php; el enrutado, index.php con Publico\Rutas.
 *
 *  Todo lo que se lee de la base lleva su texto de reserva: si MySQL no
 *  responde, o si alguien vacía un campo en el panel, la página se pinta con
 *  lo que dice el editable. La página de contacto es la puerta de entrada de
 *  quien necesita algo del viaje: no puede quedarse muda porque falle la base.
 *
 *  Las medidas son las del editable CONTACTO.ai (mesa de 1440 px). La hoja
 *  assets/css/paginas/contacto.css las reproduce con la unidad --u.
 *
 *  ⚠ EL FORMULARIO NO TIENE ENVÍO EN SERVIDOR. Véase el comentario grande
 *    junto al <form>: hoy sólo valida en el navegador.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 *  @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'Contacto · Viaje de León XIV al Perú',
    'descripcion' => 'Escríbenos y te respondemos. Formulario de contacto y canales directos del '
                   . 'Viaje Apostólico de Su Santidad el Papa León XIV al Perú, '
                   . '11–16 de noviembre de 2026.',
    'ruta'        => 'contacto/',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'website',
];

$paginaCms = $sitio->contenido('contacto');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);

/* ── Destinos escritos en el panel ────────────────────────────────────────
   Un canal directo puede apuntar a un correo (mailto:), a un teléfono, a un
   perfil de otra web o a una página de este sitio. Sólo las rutas internas
   necesitan la base delante; lo demás se deja tal cual, porque anteponerle
   la base a «mailto:…» produce un enlace roto.

   Ojo con el nombre: index.php ya tiene un $destino —la entrada del mapa de
   rutas— y _plantilla.php lo lee para cargar la hoja de esta página. Pisarlo
   desde aquí deja el sitio en blanco con un 500 sin rastro en el registro. */
$enlaceCanal = static function (string $url) use ($sitio): string {
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    return preg_match('~^(?:https?:|mailto:|tel:|//|#)~i', $url) === 1
        ? $url
        : $sitio->enlace(ltrim($url, '/'));
};

/* ── Los iconos de las redes ──────────────────────────────────────────────
   Van dibujados aquí, en línea, y no en el sprite del sitio: son marcas de
   terceros que sólo aparecen en esta página. El panel elige cuál se pinta
   escribiendo su nombre en el rótulo del bloque; un nombre que no esté en
   esta lista no rompe nada, simplemente deja el canal sin icono. */
$iconoCanal = static function (string $nombre): string {
    $nombre = strtolower(trim($nombre));
    /* Se admiten las grafías que un editor escribiría a mano. */
    $nombre = strtr($nombre, ['tik tok' => 'tiktok', 'tik-tok' => 'tiktok', ' ' => '']);

    return match ($nombre) {
        'youtube' => '<svg class="canal__ico canal__ico--yt" viewBox="0 0 34 24" width="34" height="24" aria-hidden="true" focusable="false">'
                   . '<rect width="34" height="24" rx="6.6" fill="currentColor"/>'
                   . '<path d="M13.7 6.6 23.2 12l-9.5 5.4z" fill="#E1E1E1"/></svg>',

        'facebook' => '<svg class="canal__ico" viewBox="0 0 34 34" width="34" height="34" aria-hidden="true" focusable="false">'
                    . '<rect width="34" height="34" rx="6.6" fill="currentColor"/>'
                    . '<path d="M21.4 34V21.3h4.2l.6-4.9h-4.8v-3.1c0-1.4.4-2.4 2.4-2.4h2.6V6.5c-.5-.1-2-.2-3.8-.2-3.7 0-6.3 2.3-6.3 6.5v3.6h-4.2v4.9h4.2V34z" fill="#E1E1E1"/></svg>',

        'instagram' => '<svg class="canal__ico" viewBox="0 0 34 34" width="34" height="34" aria-hidden="true" focusable="false">'
                     . '<rect x="1.4" y="1.4" width="31.2" height="31.2" rx="8.8" fill="none" stroke="currentColor" stroke-width="2.8"/>'
                     . '<circle cx="17" cy="17" r="7.3" fill="none" stroke="currentColor" stroke-width="2.8"/>'
                     . '<circle cx="25.9" cy="8.2" r="1.9" fill="currentColor"/></svg>',

        'tiktok' => '<svg class="canal__ico" viewBox="0 0 34 34" width="34" height="34" aria-hidden="true" focusable="false">'
                  . '<rect width="34" height="34" rx="6.6" fill="currentColor"/>'
                  . '<path d="M18.9 6.8h3.4c.2 2 1.4 3.7 3.6 4v3.5c-1.3 0-2.6-.4-3.6-1.1v6.4c0 3.4-2.7 6.1-6.1 6.1s-6.1-2.7-6.1-6.1 2.7-6.1 6.1-6.1c.3 0 .6 0 .9.1v3.6c-.3-.1-.6-.2-.9-.2-1.4 0-2.5 1.2-2.5 2.6s1.1 2.6 2.5 2.6 2.7-1.1 2.7-2.6z" fill="#E1E1E1"/></svg>',

        'whatsapp' => '<svg class="canal__ico" viewBox="0 0 34 34" width="34" height="34" aria-hidden="true" focusable="false">'
                    . '<rect width="34" height="34" rx="6.6" fill="currentColor"/>'
                    . '<path d="M17 7.2c-5.4 0-9.7 4.4-9.7 9.7 0 1.7.5 3.4 1.3 4.8l-1.4 5.1 5.2-1.4c1.4.8 2.9 1.2 4.6 1.2 5.4 0 9.7-4.4 9.7-9.7S22.4 7.2 17 7.2m0 17.6c-1.5 0-2.9-.4-4.2-1.1l-.3-.2-3.1.8.8-3-.2-.3c-.8-1.3-1.2-2.7-1.2-4.2 0-4.4 3.6-8 8-8s8 3.6 8 8-3.5 8-7.8 8m4.5-5.9c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.4-.4c.1-.2.2-.3.2-.5s0-.3-.1-.4l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 2s.8 2.3.9 2.5c.1.1 1.6 2.5 4 3.5.6.2 1 .4 1.3.5.6.2 1.1.2 1.5.1.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1z" fill="#E1E1E1"/></svg>',

        default => '',
    };
};

/* ── Si alguien envía el formulario sin JavaScript ────────────────────────
   No hay nada al otro lado (véase el comentario del <form>), así que en vez
   de recargar la página en silencio —y dejar a la persona creyendo que su
   mensaje salió— se le dice la verdad y se le ofrecen los correos de abajo.
   Cuando exista el envío de verdad, esta rama se sustituye por él. */
$envioIntentado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
?>

<main id="contenido">

  <?php /* ═══════════════════════════════════════════════════════ HÉROE ════
       La portada de la página. Sale del panel: Páginas → Contacto →
       Cabecera de página, donde se puede elegir una foto para escritorio y
       otra para móvil.

       Lo de aquí abajo es el RESPALDO: la fotografía que trae la maqueta.
       Mientras nadie elija otra en el panel se sigue viendo ésta, así que
       pasar la cabecera al gestor no cambió el aspecto de nada.

       El editable no dibuja rótulo sobre el titular, así que el campo
       «Rótulo» de esta sección no se pinta: se conserva en la base para no
       perderlo, pero el diseño nuevo no tiene sitio para él. */ ?>
  <section class="hero hero--page hero-contacto">
    <div class="hero__media">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/contacto/hero.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/contacto/hero.jpg')) ?>"
             alt="Manos escribiendo en el teclado de una computadora portátil"
             width="2880" height="1164" fetchpriority="high" decoding="async">
      </picture>
      <?php $respaldoHero = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['cabecera'] ?? [], $respaldoHero, ['sizes' => '100vw', 'prioridad' => true]) ?>
    </div>

    <div class="container hero__inner">
      <h1 class="hero__title"><?= $esc($campo('cabecera', 'titulo', 'Contacto')) ?></h1>
      <?php $bajada = $campo('cabecera', 'texto', 'Cuéntanos qué necesitas y te respondemos.'); ?>
      <?php if ($bajada !== ''): ?>
        <p class="hero__sub"><?= $esc($bajada) ?></p>
      <?php endif; ?>
    </div>
  </section>

  <?php /* ═══════════════════════════════════════════ CUERPO DE LA PÁGINA ══
       El editable pinta los dos apartados —el formulario y los canales—
       dentro de una sola caja gris, con el segundo titular a 98 px del
       último elemento del primero. Por eso van en una única <section>: el
       aire entre ellos es un margen, no el relleno de dos secciones. */ ?>
  <section class="contacto">
    <div class="container contacto__inner">

      <?php /* ─────────────────────────────────────────────── ESCRÍBENOS ── */ ?>
      <h2 class="contacto__h head-rule head-rule--wine"><?= $esc($campo('escribenos', 'titulo', 'Escríbenos')) ?></h2>

      <?php /* Un texto de entrada opcional, por si algún día hace falta
               avisar de algo antes del formulario. Vacío en el editable:
               mientras el campo esté vacío en el panel, no se pinta nada.
               El panel lo ofrece como texto llano, así que se escapa y sólo
               se respetan los saltos de línea. */ ?>
      <?php $entradaForm = $campo('escribenos', 'texto', ''); ?>
      <?php if ($entradaForm !== ''): ?>
        <p class="contacto__intro"><?= nl2br($esc($entradaForm)) ?></p>
      <?php endif; ?>

      <?php
      /* Los motivos del desplegable se editan en el panel, como bloques de
         la sección «Escríbenos». Si no hay ninguno —o si la base no
         responde— se usan los seis que la página tenía escritos a mano. */
      $motivos = $bloques('escribenos', [
          ['rotulo' => 'voluntariado', 'titulo' => 'Voluntariado'],
          ['rotulo' => 'patrocinios',  'titulo' => 'Patrocinios'],
          ['rotulo' => 'donativo',     'titulo' => 'Donativo'],
          ['rotulo' => 'prensa',       'titulo' => 'Prensa'],
          ['rotulo' => 'materiales',   'titulo' => 'Materiales de pastoral'],
          ['rotulo' => 'otra',         'titulo' => 'Otra consulta'],
      ]);
      ?>

      <?php /* ══════════════════════════════════════════════════════════════
           ⚠ ESTE FORMULARIO TODAVÍA NO SE ENVÍA A NINGUNA PARTE.
           ══════════════════════════════════════════════════════════════
           En el proyecto NO existe el lado servidor: no hay controlador que
           reciba este POST, ni tabla donde guardarlo, ni aviso por correo.
           Lo único que hay hoy es la validación en el navegador que activa
           `data-validate` (assets/js/rediseno.js), que además impide el
           envío y enseña un acuse en el párrafo [data-form-note].

           Queda así a propósito, para no inventarse un buzón que nadie lee.
           Para terminarlo hacen falta cuatro cosas:
             1. una ruta que atienda el POST de /contacto/;
             2. token CSRF y campo trampa contra el envío automático;
             3. guardado y/o aviso por correo al buzón que decida la CEP;
             4. sustituir el acuse de rediseno.js por el de verdad, porque
                hoy dice «Hemos recibido tu mensaje» sin haber recibido nada.
           Mientras tanto, la vía real de contacto son los correos de
           «Canales directos», justo debajo.

           El `action` apunta a la propia página para que, sin JavaScript, el
           envío no se pierda en una dirección que no existe. */ ?>
      <form class="contacto__form" data-validate method="post"
            action="<?= $esc($sitio->enlace('contacto/')) ?>">

        <div class="field">
          <label class="field__label" for="nombre">Nombre y apellidos *</label>
          <input class="field__control" type="text" id="nombre" name="nombre" autocomplete="name" required>
        </div>

        <div class="field contacto__field--2">
          <label class="field__label" for="correo">Correo electrónico *</label>
          <input class="field__control" type="email" id="correo" name="correo" autocomplete="email" required>
        </div>

        <div class="field contacto__field--3">
          <label class="field__label" for="motivo">Motivo *</label>
          <select class="field__control" id="motivo" name="motivo" required>
            <option value="" selected>Elige uno</option>
            <?php foreach ($motivos as $m): ?>
              <?php
              $etiqueta = trim((string) ($m['titulo'] ?? ''));

              if ($etiqueta === '') {
                  continue;
              }

              /* El valor que viaja en el envío: el que se haya escrito en el
                 rótulo del bloque y, si está vacío, la propia etiqueta. */
              $valor = trim((string) ($m['rotulo'] ?? '')) ?: $etiqueta;
              ?>
              <option value="<?= $esc($valor) ?>"><?= $esc($etiqueta) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field contacto__field--4">
          <label class="field__label" for="mensaje">Tu mensaje *</label>
          <textarea class="field__control" id="mensaje" name="mensaje" rows="10" required></textarea>
        </div>

        <?php /* El enlace a la política de privacidad va dentro de la
                 etiqueta y no pasa nada: según el estándar, pulsar un enlace
                 dentro de un <label> no marca la casilla. */ ?>
        <label class="check contacto__check">
          <input type="checkbox" id="privacidad" name="privacidad" required>
          <span class="check__txt">He leído y acepto la <a class="check__enlace" href="<?= $esc($sitio->enlace('privacidad/')) ?>">política de privacidad</a>. Solo para avisos del viaje apostólico.</span>
        </label>

        <button class="btn contacto__enviar" type="submit"><?= $esc($campo('escribenos', 'cta_texto', 'Enviar mensaje')) ?></button>

        <?php /* rediseno.js escribe aquí el acuse al validar. Si la página
                 llega por POST —alguien envió sin JavaScript— se pinta ya
                 visible con la verdad, que es que el envío no está montado. */ ?>
        <p class="contacto__aviso" data-form-note role="status"<?= $envioIntentado ? '' : ' hidden' ?>>
          <?= $envioIntentado
              ? 'Este formulario todavía no está conectado: tu mensaje no se ha enviado. '
                . 'Escríbenos, por favor, a uno de los correos de aquí abajo.'
              : '' ?>
        </p>
      </form>

      <?php /* ───────────────────────────────────────── CANALES DIRECTOS ── */ ?>
      <?php
      /* Cada canal es un bloque del panel:
           · Título          → la etiqueta en granate («Prensa»).
           · Texto           → lo que se lee («contacto@leon14enperu.com»).
           · Destino enlace  → opcional. Con «mailto:…» el correo se vuelve
                               pulsable; vacío, el dato se muestra sin enlace,
                               que es como está el editable para las redes.
           · Número o rótulo → el nombre del icono: youtube, facebook,
                               instagram, tiktok o whatsapp. Vacío, sin icono.
         La lista de reserva es la del editable, verificada contra la maqueta. */
      $canales = $bloques('canales', [
          ['titulo' => 'Voluntariado', 'texto' => 'comunica.laicosyjuventud@iglesiacatolica.org.pe', 'enlace_url' => 'mailto:comunica.laicosyjuventud@iglesiacatolica.org.pe'],
          ['titulo' => 'Prensa',       'texto' => 'contacto@leon14enperu.com',                       'enlace_url' => 'mailto:contacto@leon14enperu.com'],
          ['titulo' => 'Youtube',      'texto' => '@leon14enperu',            'rotulo' => 'youtube'],
          ['titulo' => 'Facebook',     'texto' => '/LeonXIVEnPeru',           'rotulo' => 'facebook'],
          ['titulo' => 'Instagram',    'texto' => 'León XIV en el Perú',      'rotulo' => 'instagram'],
          ['titulo' => 'Tik Tok',      'texto' => '@leon14enperu',            'rotulo' => 'tiktok'],
          ['titulo' => 'Canal de difusión WhatsApp', 'texto' => 'Papa León XIV en el Perú', 'rotulo' => 'whatsapp'],
      ]);
      ?>
      <h2 class="contacto__h contacto__h--canales head-rule head-rule--wine"><?= $esc($campo('canales', 'titulo', 'Canales directos')) ?></h2>

      <ul class="canales">
        <?php foreach ($canales as $c): ?>
          <?php
          $etq = trim((string) ($c['titulo'] ?? ''));
          $val = trim((string) ($c['texto']  ?? ''));

          if ($etq === '' && $val === '') {
              continue;
          }

          $svg  = $iconoCanal((string) ($c['rotulo'] ?? ''));
          $href = $enlaceCanal((string) ($c['enlace_url'] ?? ''));
          /* Los dos puntos los pone la vista: así el panel guarda el nombre
             limpio y no depende de que nadie se olvide de escribirlos. */
          $etq  = $etq === '' ? '' : rtrim($etq, ': ') . ':';
          ?>
          <li class="canal<?= $svg !== '' ? ' canal--red' : '' ?>">
            <?= $svg ?>
            <?php if ($etq !== ''): ?><b class="canal__etq"><?= $esc($etq) ?></b><?php endif; ?>
            <?php if ($href !== ''): ?>
              <a class="canal__val" href="<?= $esc($href) ?>"><?= $esc($val) ?></a>
            <?php else: ?>
              <span class="canal__val"><?= $esc($val) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

    </div>
  </section>

</main>
