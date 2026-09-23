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

/* ── El texto de cada trámite ─────────────────────────────────────────────
   El campo del panel es texto plano y así se queda: lo que llega de un
   formulario no se imprime sin escapar. Se escapa PRIMERO y después se
   destapan sólo las marcas que el editable necesita, ya escapadas, así que
   del panel no puede salir ninguna otra etiqueta.

     **negrita**         lo que el editable pone en semibold
     [texto](destino)    un enlace, con la misma regla de destinos que los
                         botones: «contacto/» cuelga de la raíz del sitio y
                         lo de fuera se abre en otra pestaña
     - al principio      una línea de la lista con guion
     línea en blanco     un párrafo nuevo

   Y los correos sueltos se enlazan solos. En esta página hay uno —el de la
   Cancillería, que es a donde se manda la acreditación— y es justo lo que
   el lector va a querer pulsar desde el teléfono. */
$rico = static function (string $linea) use ($esc, $sitio): string {
    $html = $esc($linea);

    /* Primero los enlaces escritos, que son los únicos que traen destino. */
    $html = (string) preg_replace_callback(
        '/\[([^\]]+)\]\(([^)\s]+)\)/u',
        static function (array $trozo) use ($sitio): string {
            $adonde = $sitio->enlaceDelPanel(
                html_entity_decode($trozo[2], ENT_QUOTES, 'UTF-8')
            );

            /* Sin destino no hay enlace, pero el texto no se pierde: se pinta
               tal cual. Un enlace que no lleva a ninguna parte es peor que
               una frase corriente. */
            if ($adonde === '') {
                return $trozo[1];
            }

            return '<a href="' . htmlspecialchars($adonde, ENT_QUOTES, 'UTF-8') . '"'
                 . ($sitio->esExterno($adonde) ? ' target="_blank" rel="noopener noreferrer"' : '')
                 . '>' . $trozo[1] . '</a>';
        },
        $html
    ) ?: $html;

    /* Después los correos sueltos. Van detrás de los enlaces escritos para no
       volver a enlazar los que acaban de quedar dentro de un href o de un
       rótulo: por eso se descartan los precedidos de «:» y de «>». */
    $html = (string) preg_replace(
        '/(?<![:>])\b([\w.+-]+@[\w-]+(?:\.[\w-]+)+)\b/u',
        '<a href="mailto:$1">$1</a>',
        $html
    ) ?: $html;

    /* Y la negrita al final, para que pueda envolver un enlace ya hecho: el
       correo de la Cancillería va en semibold en el editable. */
    return (string) preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $html) ?: $html;
};

$cuerpo = static function (string $texto) use ($rico): string {
    $texto = trim(str_replace(["\r\n", "\r"], "\n", $texto));

    if ($texto === '') {
        return '';
    }

    $html    = '';
    $parrafo = [];
    $lista   = [];

    $volcar = static function (array &$parrafo, array &$lista, string &$html) use ($rico): void {
        if ($parrafo !== []) {
            $html   .= '<p>' . implode('<br>', array_map($rico, $parrafo)) . '</p>';
            $parrafo = [];
        }

        if ($lista !== []) {
            $html .= '<ul class="pr-tramite__items"><li>'
                   . implode('</li><li>', array_map($rico, $lista))
                   . '</li></ul>';
            $lista = [];
        }
    };

    foreach (explode("\n", $texto) as $linea) {
        $linea = trim($linea);

        if ($linea === '') {
            $volcar($parrafo, $lista, $html);

            continue;
        }

        /* Guion al principio —del teclado o tipográfico, que es lo que pega
           un copiar y pegar desde Word— y la línea entra en la lista. */
        if (preg_match('/^[-\x{2013}\x{2014}]\s+(.*)$/u', $linea, $trozo) === 1) {
            if ($parrafo !== []) {
                $volcar($parrafo, $lista, $html);
            }

            $lista[] = $trozo[1];

            continue;
        }

        if ($lista !== []) {
            $volcar($parrafo, $lista, $html);
        }

        $parrafo[] = $linea;
    }

    $volcar($parrafo, $lista, $html);

    return $html;
};

/* ── Destinos que vienen del panel ────────────────────────────────────────
   En el panel los destinos se escriben cortos («contacto/»). Desde /prensa/
   un enlace relativo apuntaría a /prensa/contacto/, que no existe, así que
   se cuelgan de la raíz del sitio. De eso se encarga $sitio->enlaceDelPanel(),
   que es la misma regla que usan los apartados y las tarjetas.

   Ojo con los nombres: la vista se ejecuta en el mismo ámbito que index.php
   y que _plantilla.php, así que una variable llamada $destino o $pieza
   pisaría las suyas —la ruta resuelta y la pieza del detalle— y rompería la
   página entera. Todo lo de aquí lleva nombre propio. */

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

    <?php /* La fotografía que dibuja el editable para el primer trámite. Se
             prepara aquí, fuera del bucle, porque dentro sólo la usa el
             primero: los otros dos esperan a que alguien las suba. */ ?>
    <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/prensa/p01.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/prensa/p01.jpg')) ?>"
             alt="Una periodista consulta en su teléfono la página oficial de la visita del Papa León XIV"
             width="1550" height="946" loading="lazy" decoding="async">
      </picture>
    <?php $respaldoFoto = (string) ob_get_clean(); ?>

    <?php /* ═════════════════════════════════════════════ ACREDITACIÓN ════
         El editable de septiembre de 2026 parte lo que era UN texto en
         varios trámites, uno debajo de otro y separados por una línea. Cada
         uno lleva su estado en el rótulo, su titular, su explicación y su
         fotografía, y es un bloque del panel: se añaden, se quitan, se
         reordenan y se apagan sin tocar esta página.

         Y falta hace, porque los estados caducan con el calendario. Lo que
         hoy dice «proceso no habilitado» lo dirá habilitado en octubre, el
         plazo del correo vence el 28 de septiembre y después del viaje
         sobrarán los tres. Nada de esto debería necesitar un despliegue.

         El respaldo de abajo no es adorno: si MySQL no responde no llega
         ninguna sección, y la página tiene que salir igualmente con lo que
         dibuja el editable. */ ?>
    <?php if ($pinta('como-acreditarse')): ?>
    <?php
    $tramites = $bloques('como-acreditarse', [
        [
            'rotulo' => 'ACREDITACIÓN / PROCESO HABILITADO',
            'titulo' => "Dirigido a la Prensa para la\nVisita del Santo Padre al Perú",
            'datos'  => ['subtitulo' => 'Medios de Comunicación Nacionales e Internacionales'],
            'texto'  => "El **Ministerio de Relaciones Exteriores** informa que ya se encuentra abierta la acreditación de medios de comunicación para la Visita Apostólica de Su Santidad el papa León XIV al Perú, la cual es válida para todo el territorio nacional.\n\nLos medios de comunicación interesados deberán enviar, **hasta el lunes 28 de septiembre a las 23:59 horas**, un correo electrónico a **prensa@rree.gob.pe** indicando:\n\n- Nombre completo\n- Tipo y número de documento de identidad\n- Teléfono celular\n- Dirección de correo electrónico de su coordinador de enlace",
        ],
        [
            'rotulo' => 'ACREDITACIÓN / PROCESO HABILITADO',
            'titulo' => 'Vuelo Papal',
            'datos'  => ['foto' => 'abajo'],
            'texto'  => "A los periodistas que deseen acreditarse **para hacer todo el recorrido del Viaje Apostólico de Su Santidad el Papa León XIV a Uruguay, Argentina y Perú**.\n\nEsta acreditación se solicita a la Oficina de Prensa correspondiente, a través de un sistema de acreditación online.\n\nPara más información [inscríbete aquí](#).",
        ],
        [
            'rotulo' => 'ACREDITACIÓN / PROCESO NO HABILITADO',
            'titulo' => "Señal oficial para\nmedios de comunicación",
            'texto'  => "El **Instituto Nacional de Radio y Televisión del Perú (IRTP)** acreditará a los medios de comunicación que deseen acceder a la señal de transmisión de la visita del Santo Padre, del 11 al 16 de noviembre. La señal se proporcionará limpia, sin logotipos, banners, cintillos ni otros elementos gráficos.\n\nPara acceder a ella, cada medio deberá acreditarse y completar el formulario correspondiente, indicando las especificaciones técnicas que requiera. El enlace al formulario **estará disponible en la página del IRTP del 19 al 31 de octubre**.",
        ],
    ]);
    ?>
    <section class="pr-acred" aria-label="<?= $esc($campo('como-acreditarse', 'titulo', 'Acreditación')) ?>">
      <div class="pr-wrap">
        <?php /* El editable no dibuja entradilla para esta banda, pero la
                 plantilla del panel ofrece el campo. Si alguien escribe ahí,
                 se pinta; sin texto no ocupa nada. Lo que NO puede pasar es
                 que se escriba y desaparezca sin avisar. */ ?>
        <?php $entradaAcred = $campo('como-acreditarse', 'texto', ''); ?>
        <?php if ($entradaAcred !== ''): ?>
          <div class="pr-acred__lead"><?= $entradaAcred ?></div>
        <?php endif; ?>

        <?php foreach ($tramites as $n => $tramite): ?>
          <?php
          /* `datos` llega como texto JSON desde MySQL y ya como arreglo
             cuando es el respaldo de aquí arriba. */
          $suyos = $tramite['datos'] ?? null;
          $suyos = is_string($suyos) ? json_decode($suyos, true) : $suyos;
          $suyos = is_array($suyos) ? $suyos : [];

          $abajo     = strcasecmp(trim((string) ($suyos['foto'] ?? '')), 'abajo') === 0;
          $subtitulo = trim((string) ($suyos['subtitulo'] ?? ''));

          /* El rótulo trae dos cosas separadas por una barra: la palabra fija
             en dorado y el estado del trámite en gris. Si alguien escribe uno
             sin barra, se pinta entero en dorado y ya está. */
          $piezas = preg_split('~\s*/\s*~u', trim((string) ($tramite['rotulo'] ?? '')), 2);
          $piezas = $piezas ?: [''];

          /* La fotografía es opcional: la del editable sólo existe para el
             primer trámite, y los otros dos esperan a que alguien las suba
             desde el panel. Sin foto, el texto se queda con todo el ancho en
             lugar de dejar un hueco gris. */
          $foto = $sitio->imagen($tramite, $n === 0 ? $respaldoFoto : '', [
              'sizes' => $abajo ? '(min-width:1024px) 85vw, 100vw' : '(min-width:1024px) 42vw, 100vw',
          ]);
          ?>
          <article class="pr-tramite<?= $abajo ? ' pr-tramite--abajo' : '' ?><?= $foto === '' ? ' pr-tramite--sinfoto' : '' ?>">

            <div class="pr-tramite__texto">
              <?php if ($piezas[0] !== ''): ?>
                <p class="pr-tramite__kicker">
                  <b><?= $esc($piezas[0]) ?></b><?php if (isset($piezas[1]) && $piezas[1] !== ''): ?> / <span><?= $esc($piezas[1]) ?></span><?php endif; ?>
                </p>
              <?php endif; ?>

              <h2 class="pr-tramite__h"><?= nl2br($esc((string) ($tramite['titulo'] ?? ''))) ?></h2>

              <?php if ($subtitulo !== ''): ?>
                <p class="pr-tramite__sub"><?= $esc($subtitulo) ?></p>
              <?php endif; ?>

              <div class="pr-tramite__copy"><?= $cuerpo((string) ($tramite['texto'] ?? '')) ?></div>
            </div>

            <?php if ($foto !== ''): ?>
              <figure class="pr-tramite__foto"><?= $foto ?></figure>
            <?php endif; ?>

          </article>
        <?php endforeach; ?>
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

    <?php /* ═════════════════════════════════════════════════ BOTONERA ══
         El editable de septiembre de 2026 cambia aquí: donde había un botón
         «Contacto» centrado ahora hay una fila con dos, «Programa oficial»
         pegado a la izquierda y «Contacto» a la derecha, en la misma línea
         y dentro de la caja de 1218 px.

         Cada botón es un bloque de la sección —plantilla «botonera»—, así
         que en Páginas → Prensa → Botón de contacto se añaden, se quitan y
         se reordenan. El día que la Santa Sede publique el programa, basta
         con cambiar el destino del primero.

         El respaldo de abajo no es adorno: si MySQL no responde no llega
         ninguna sección, y la página tiene que salir igualmente con la fila
         que dibuja el editable. */ ?>
    <?php if ($pinta('contacto-prensa')): ?>
    <?php
    $botones = $bloques('contacto-prensa', []);

    if ($botones === []) {
        /* Sin bloques: o la base no respondió, o es una instalación que aún
           no pasó la migración de la botonera. En el segundo caso la sección
           todavía guarda su botón en «cta_texto/cta_url», y sería una pena
           perderlo por estrenar plantilla. */
        $botones = [
            ['titulo' => 'Programa oficial', 'enlace_url' => 'agenda/',
             'datos'  => ['icono' => 'descarga']],
            ['titulo' => $campo('contacto-prensa', 'cta_texto', 'Contacto'),
             'enlace_url' => $campo('contacto-prensa', 'cta_url', 'contacto/')],
        ];
    }
    ?>
    <section class="pr-cta" aria-label="<?= $esc($campo('contacto-prensa', 'titulo', 'Contacto de prensa')) ?>">
      <div class="pr-wrap pr-cta__fila">
        <?php foreach ($botones as $boton): ?>
          <?php
          $rotulo  = trim((string) ($boton['titulo'] ?? ''));
          $destino = $sitio->enlaceDelPanel((string) ($boton['enlace_url'] ?? ''));

          /* Un botón sin rótulo o sin destino no se pinta: mejor una fila más
             corta que un botón que no lleva a ninguna parte. */
          if ($rotulo === '' || $destino === '') {
              continue;
          }

          /* `datos` llega como texto JSON desde MySQL y ya como arreglo cuando
             es el respaldo de aquí arriba. */
          $datosBoton = $boton['datos'] ?? null;
          $datosBoton = is_string($datosBoton) ? json_decode($datosBoton, true) : $datosBoton;
          $conIcono   = is_array($datosBoton)
              && strcasecmp(trim((string) ($datosBoton['icono'] ?? '')), 'descarga') === 0;
          ?>
          <a class="btn pr-cta__btn<?= $conIcono ? ' pr-cta__btn--descarga' : '' ?>" href="<?= $esc($destino) ?>"<?= $sitio->esExterno($destino) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
            <?php if ($conIcono): ?>
              <svg class="ico-baja" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 1.2v16.6M7.4 13.2 12 17.8l4.6-4.6" fill="none" stroke="currentColor"
                      stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M1.2 11.7v11.1h21.6V11.7" fill="none" stroke="currentColor"
                      stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            <?php endif; ?>
            <span><?= $esc($rotulo) ?></span>
          </a>
        <?php endforeach; ?>
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
