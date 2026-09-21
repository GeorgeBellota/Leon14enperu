<?php
/**
 * ============================================================================
 *  Portada — rediseño 2026.
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
 *  Las medidas son las del editable HOME.ai (mesa de 1440 px). La hoja
 *  assets/css/paginas/portada.css las reproduce con la unidad --u.
 *
 *  @var \Intranet\Publico\Sitio $sitio
 * @var callable $esc
 */

declare(strict_types=1);

$meta = [
    'titulo'      => 'León XIV en el Perú · Visita Apostólica 11–16 de noviembre de 2026',
    'descripcion' => 'Sitio oficial de la Visita Apostólica de Su Santidad el Papa León XIV al Perú, '
                   . 'del 11 al 16 de noviembre de 2026. Lima, Callao, Chiclayo, Santa Cruz, Cusco y Pucallpa.',
    'ruta'        => '',
    'og_imagen'   => 'assets/img/og/og-inicio.jpg',
    'og_tipo'     => 'website',
];

$paginaCms = $sitio->contenido('home');
$secciones = $paginaCms['secciones'] ?? [];

$campo   = static fn (string $s, string $c, string $r = ''): string
    => \Intranet\Publico\Sitio::campo($secciones, $s, $c, $r);
$bloques = static fn (string $s, array $r = []): array
    => \Intranet\Publico\Sitio::bloques($secciones, $s, $r);
$hay     = static fn (string $s): bool
    => \Intranet\Publico\Sitio::activa($secciones, $s);

/* La fecha del contador sale de Configuración general → Fechas del viaje. */
$objetivo = '';
try { $objetivo = $sitio->objetivoCuentaAtras(); } catch (\Throwable $e) {
    error_log('[portada] no se pudo leer la fecha del viaje: ' . $e->getMessage());
}
?>

<main id="contenido">

  <?php /* ══════════════════════════════════════════════════════ HERO ════
       El carrusel de la portada. Cada lámina se edita en
       Páginas → Inicio → Carrusel principal.

       Si en el panel hay una lámina se ve una y las marcas no aparecen; con
       dos o más, el carrusel gira solo cada siete segundos.

       ── Cada lámina trae lo suyo ──────────────────────────────────────────
       Antes la fotografía y el botón salían siempre de la PRIMERA lámina:
       sólo cambiaba el titular. La segunda lámina del editable de 2026 es
       otra composición entera —otra foto, otro titular y un botón dorado a la
       derecha en lugar del granate a la izquierda—, así que la foto y el
       botón pasan a ser de cada lámina.

       El reparto lo decide «datos.diseno» del bloque:
         (vacío)     la composición de siempre, con el botón «En directo».
         «programa»  la lámina «Preparémonos», con el botón dorado de descarga.

       Quien no elija foto en el panel hereda la de la primera lámina, que es
       justo lo que hacían las tres antes de este cambio. */ ?>
  <?php
  $laminas = $bloques('hero', [
      ['titulo' => 'Papa León XIV,', 'texto' => 'le esperamos.'],
      ['titulo' => 'Preparémonos',   'texto' => 'para recibirlo.',
       'enlace_texto' => 'Programa Oficial', 'enlace_url' => 'agenda/',
       'datos' => ['diseno' => 'programa']],
      ['titulo' => 'Del 11 al 16',   'texto' => 'de noviembre.'],
  ]);
  $primera = $laminas[0] ?? [];

  /* El diseño de una lámina, tal como lo guarda el panel: `datos` llega como
     texto JSON desde MySQL y ya como arreglo cuando es un valor de reserva. */
  $disenoDe = static function (array $lamina): string {
      $datos = $lamina['datos'] ?? null;
      $datos = is_string($datos) ? json_decode($datos, true) : $datos;

      return is_array($datos) ? (string) ($datos['diseno'] ?? '') : '';
  };
  ?>
  <section class="hero hero--home"<?= count($laminas) > 1 ? ' data-slider' : '' ?>
           aria-roledescription="carrusel" aria-label="Destacados">
    <div class="hero__media">
      <?php foreach ($laminas as $i => $lamina): ?>
        <?php
        /* El respaldo —lo que se ve si MySQL no responde— también es propio de
           cada lámina: la de «Preparémonos» no puede caer en la fotografía del
           papamóvil, que es la que ilustra otra frase. */
        $esPrograma = $disenoDe($lamina) === 'programa';
        $archivo = $esPrograma ? 'hero-programa' : 'hero';
        $alt = $esPrograma
            ? 'Unas manos escriben en un portátil que muestra el calendario de noviembre de 2026 con la visita del Papa León XIV'
            : 'El Papa León XIV saluda desde el papamóvil rodeado de fieles con banderas del Perú';
        ob_start(); ?>
        <picture>
          <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $archivo . '.webp')) ?>" type="image/webp">
          <img src="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $archivo . '.jpg')) ?>"
               alt="<?= $esc($alt) ?>" width="2880" height="932"
               <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
        </picture>
        <?php $respaldoHero = (string) ob_get_clean(); ?>
        <?php /* Sólo la primera fotografía se anuncia. Las otras acompañan a un
                 titular que ya lo dice todo, y leerlas seguidas sería ruido. */ ?>
        <div class="hero-home__foto<?= $esPrograma ? ' hero-home__foto--programa' : '' ?><?= $i === 0 ? ' is-active' : '' ?>"
             data-slide-foto<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
          <?= $sitio->imagen(
              ($lamina['imagen_ruta'] ?? '') !== '' ? $lamina : ($esPrograma ? [] : $primera),
              $respaldoHero,
              ['sizes' => '100vw', 'prioridad' => $i === 0]
          ) ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php /* El emblema de la Conferencia Episcopal, en negativo. Decorativo:
             el lector de pantalla no tiene nada que leer aquí. */ ?>
    <img class="hero-home__escudo" src="<?= $esc($sitio->asset('assets/img/rediseno/brand/cep-simbolo-blanco.png')) ?>"
         alt="" aria-hidden="true" width="600" height="625" decoding="async">

    <div class="hero-home__inner">
      <div class="hero-home__slides" id="hero-laminas">
        <?php foreach ($laminas as $i => $lamina): ?>
          <?php
          $diseno = $disenoDe($lamina);
          $rotulo = (string) ($lamina['enlace_texto'] ?? '')
              ?: ($diseno === 'programa' ? 'Programa Oficial' : 'En directo');
          $url = (string) ($lamina['enlace_url'] ?? '')
              ?: $sitio->enlace($diseno === 'programa' ? 'agenda/' : 'en-directo/');
          $Etiqueta = $i === 0 ? 'h1' : 'p';
          ?>
          <div class="hero-home__slide<?= $i === 0 ? ' is-active' : '' ?>"
               data-slide<?= $diseno !== '' ? ' data-diseno="' . $esc($diseno) . '"' : '' ?><?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
            <<?= $Etiqueta ?> class="hero-home__title">
              <span class="hero-home__t1"><?= $esc((string) ($lamina['titulo'] ?? '')) ?></span>
              <span class="hero-home__t2"><?= $esc((string) ($lamina['texto'] ?? '')) ?></span>
            </<?= $Etiqueta ?>>

            <?php if ($diseno === 'programa'): ?>
              <a class="btn hero-home__programa" href="<?= $esc($url) ?>">
                <svg class="ico-baja" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path d="M12 1.2v16.6M7.4 13.2 12 17.8l4.6-4.6" fill="none" stroke="currentColor"
                        stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M1.2 11.7v11.1h21.6V11.7" fill="none" stroke="currentColor"
                        stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span><?= $esc($rotulo) ?></span>
              </a>
            <?php else: ?>
              <a class="btn hero-home__live" href="<?= $esc($url) ?>">
                <svg class="ico-live" viewBox="0 0 44 24" width="44" height="24" aria-hidden="true" focusable="false">
                  <path d="M8.2 3.4C5.6 6 4.1 9.4 4.1 12s1.5 6 4.1 8.6M12.4 6.5c-1.7 1.7-2.6 4-2.6 5.5s.9 3.8 2.6 5.5M35.8 3.4C38.4 6 39.9 9.4 39.9 12s-1.5 6-4.1 8.6M31.6 6.5c1.7 1.7 2.6 4 2.6 5.5s-.9 3.8-2.6 5.5"
                        fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                  <circle cx="22" cy="12" r="4.2" fill="currentColor"/>
                </svg>
                <span><?= $esc($rotulo) ?></span>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php /* Mando del carrusel: las dos flechas y las marcas de paso. La marca
               activa lleva dentro la cuenta de los siete segundos, así que el
               salto se ve venir en lugar de sorprender. */ ?>
      <?php if (count($laminas) > 1): ?>
        <div class="hero-home__nav">
          <button class="hero-home__arrow" type="button" data-slide-prev
                  aria-controls="hero-laminas" aria-label="Destacado anterior">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path d="M15 4 7 12l8 8" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <div class="hero-home__dots" role="tablist" aria-label="Elegir destacado">
            <?php foreach ($laminas as $i => $lamina): ?>
              <button class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>" type="button" data-slide-dot
                      role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                      aria-label="Destacado <?= $i + 1 ?>">
                <span class="hero-dot__track"><span class="hero-dot__fill"></span></span>
              </button>
            <?php endforeach; ?>
          </div>

          <button class="hero-home__arrow" type="button" data-slide-next
                  aria-controls="hero-laminas" aria-label="Destacado siguiente">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path d="m9 4 8 8-8 8" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php /* ═══════════════════════════════════ BANDA DE FECHAS Y CUENTA ════
       La franja dorada. Las fechas y las ciudades se editan en el panel; los
       números los pone rediseno.js con la fecha de Configuración general. */ ?>
  <section class="band" data-countdown<?= $objetivo !== '' ? ' data-objetivo="' . $esc($objetivo) . '"' : '' ?>
           aria-label="Fechas del viaje y cuenta regresiva">
    <div class="container band__inner">
      <div class="band__when">
        <p class="band__date">
          <strong><?= $esc($campo('falta-poco-encuentro', 'rotulo', '11 - 16')) ?></strong>
          <?= $esc($campo('falta-poco-encuentro', 'titulo', 'NOVIEMBRE 2026')) ?>
        </p>
        <?php
        /* Cada ciudad lleva en el editable su propio cuerpo y su propio
           interletraje, así que van en piezas separadas. Del panel llega una
           sola línea: se parte por « · » o por comas para respetarlo. */
        $ciudadesTexto = $campo('falta-poco-encuentro', 'texto', 'LIMA CALLAO · CHICLAYO · CUSCO · PUCALLPA');
        $ciudades = array_values(array_filter(array_map('trim', preg_split('/\s*[·,]\s*/u', $ciudadesTexto) ?: [])));
        ?>
        <p class="band__cities">
          <?php foreach ($ciudades as $i => $ciudad): ?>
            <span class="c<?= $i + 1 ?>"><?= $esc($ciudad) ?></span>
          <?php endforeach; ?>
        </p>
      </div>

      <div class="band__count">
        <?php foreach (['dias' => 'días', 'horas' => 'horas', 'minutos' => 'minutos', 'segundos' => 'segundos'] as $u => $rotulo): ?>
          <div class="band__unit">
            <span class="band__num" data-cd="<?= $esc($u) ?>">00</span>
            <span class="band__lab"><?= $esc($rotulo) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php /* ══════════════════════════════════════════ LEMA (fondo dorado) ══ */ ?>
  <section class="lema">
    <div class="container">

      <?php /* El himno es su propia sección para que tenga su propio enlace:
               el de aquí abajo apunta al himno y el del bloque de la marca, a
               la página del lema. Mientras no haya archivo, lleva a Noticias,
               que es donde se publicará. */ ?>
      <?php if ($hay('himno') || !$hay('abramos-el-corazon')): ?>
      <a class="hymn" href="<?= $esc($campo('himno', 'cta_url', $sitio->enlace('noticias/'))) ?>">
        <span class="hymn__play" aria-hidden="true"></span>
        <span class="hymn__txt">
          <span class="hymn__title"><?= $esc($campo('himno', 'titulo', '“León, hermano del camino”')) ?></span>
          <span class="hymn__sub"><?= $esc($campo('himno', 'rotulo', 'HIMNO OFICIAL')) ?></span>
        </span>
      </a>
      <?php endif; ?>

      <div class="lema__grid">
        <div class="lema__mark">
          <?php ob_start(); ?>
          <picture>
            <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/brand/logo-lockup-rojo.webp')) ?>" type="image/webp">
            <img src="<?= $esc($sitio->asset('assets/img/rediseno/brand/logo-lockup-rojo.png')) ?>"
                 alt="Abramos el corazón — marca de la Visita Apostólica del Papa León XIV al Perú"
                 width="1600" height="1313" decoding="async">
          </picture>
          <?php $respaldoMarca = (string) ob_get_clean(); ?>
          <?= $sitio->imagen($secciones['abramos-el-corazon'] ?? [], $respaldoMarca, ['sizes' => '(min-width:1024px) 61vw, 90vw']) ?>
        </div>

        <div class="lema__copy">
          <?php
          $textoLema = $campo('abramos-el-corazon', 'texto', '');
          ?>
          <?php if ($textoLema !== ''): ?>
            <div class="lema__text"><?= $textoLema ?></div>
          <?php else: ?>
            <p class="lema__text"><strong>Una invitación</strong> a recibir al Santo Padre, encontrarnos como Iglesia y renovar juntos la esperanza.</p>
          <?php endif; ?>
          <a class="btn lema__cta" href="<?= $esc($campo('abramos-el-corazon', 'cta_url', $sitio->enlace('logo-y-lema/'))) ?>"><?= $esc($campo('abramos-el-corazon', 'cta_texto', 'Conoce más')) ?></a>
        </div>
      </div>

    </div>
  </section>

  <?php /* ═══════════════════════════════════════════════════ SUBSIDIOS ══ */ ?>
  <?php
  $tarjetasSubsidio = $bloques('subsidios-home', [
      ['rotulo' => 'SUBSIDIO #1', 'titulo' => "PAPA LEÓN:\nCERCANO Y PERUANO",        'img' => 'p08', 'w' => 326, 'h' => 462, 'alt' => 'Portada del subsidio 1: Papa León, cercano y peruano'],
      ['rotulo' => 'SUBSIDIO #2', 'titulo' => "UNIDOS EN CRISTO,\nSEMBRADORES DE\nPAZ", 'img' => 'p09', 'w' => 331, 'h' => 469, 'alt' => 'Portada del subsidio 2: Unidos en Cristo, sembradores de paz'],
      ['rotulo' => 'SUBSIDIO #3', 'titulo' => "FAMILIAS QUE\nCUIDAN LA VIDA",         'img' => 'p10', 'w' => 343, 'h' => 483, 'alt' => 'Portada del subsidio 3: Familias que cuidan la vida'],
      ['rotulo' => 'SUBSIDIO #4', 'titulo' => "LOS JÓVENES Y LA\nMISIÓN",             'img' => 'p11', 'w' => 336, 'h' => 475, 'alt' => 'Portada del subsidio 4: Los jóvenes y la misión'],
      ['rotulo' => 'SUBSIDIO #5', 'titulo' => "PASTORAL SOCIAL:\nLA DIGNIDAD DE\nTODA PERSONA", 'img' => 'p12', 'w' => 330, 'h' => 468, 'alt' => 'Portada del subsidio 5: Pastoral social, la dignidad de toda persona'],
  ]);
  ?>
  <section class="subsidios" aria-labelledby="t-subsidios">
    <div class="container">
      <h2 class="subsidios__h t-center" id="t-subsidios">
        <span class="t-gold"><b><?= $esc($campo('subsidios-home', 'rotulo', 'Subsidios')) ?></b>
        <span class="w-med"><?= $esc($campo('subsidios-home', 'titulo', 'pastorales')) ?></span></span>
      </h2>

      <?php $leadSub = $campo('subsidios-home', 'texto', ''); ?>
      <?php if ($leadSub !== ''): ?>
        <div class="subsidios__lead t-center"><?= $leadSub ?></div>
      <?php else: ?>
        <p class="subsidios__lead t-center">Preparémos espiritualmente para la <strong>recibir al Santo Padre</strong>, encontrarnos como Iglesia y renovar nuestra esperanza.</p>
      <?php endif; ?>

      <ol class="subsidios__grid">
        <?php foreach ($tarjetasSubsidio as $t): ?>
          <li class="subsi">
            <a href="<?= $esc((string) ($t['enlace_url'] ?? '') ?: $sitio->enlace('subsidios/')) ?>">
              <span class="subsi__img">
                <?php
                ob_start();
                if (!empty($t['img'])): ?>
                  <picture>
                    <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $t['img'] . '.webp')) ?>" type="image/webp">
                    <img src="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $t['img'] . '.jpg')) ?>"
                         alt="<?= $esc((string) ($t['alt'] ?? '')) ?>" width="<?= (int) ($t['w'] ?? 326) ?>" height="<?= (int) ($t['h'] ?? 462) ?>"
                         loading="lazy" decoding="async">
                  </picture>
                <?php endif;
                $respaldoT = (string) ob_get_clean(); ?>
                <?= $sitio->imagen($t, $respaldoT, ['sizes' => '(min-width:1024px) 13vw, 45vw']) ?>
              </span>
              <span class="subsi__kicker"><?= $esc((string) ($t['rotulo'] ?? '')) ?></span>
              <span class="subsi__title"><?= nl2br($esc((string) ($t['titulo'] ?? ''))) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <?php /* ═══════════════════════════════════════════ LA VISITA (granate) ══ */ ?>
  <section class="visita" aria-labelledby="t-visita">
    <div class="visita__media" aria-hidden="true">
      <?php ob_start(); ?>
      <picture>
        <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/index/p00.webp')) ?>" type="image/webp">
        <img src="<?= $esc($sitio->asset('assets/img/rediseno/index/p00.jpg')) ?>" alt="" width="1452" height="912" loading="lazy" decoding="async">
      </picture>
      <?php $respaldoVisita = (string) ob_get_clean(); ?>
      <?= $sitio->imagen($secciones['llega-al-peru'] ?? [], $respaldoVisita, ['sizes' => '100vw']) ?>
    </div>
    <img class="visita__cut" src="<?= $esc($sitio->asset('assets/img/rediseno/index/p01.png')) ?>"
         alt="" aria-hidden="true" width="1511" height="1489" loading="lazy" decoding="async">

    <div class="container visita__inner">
      <p class="visita__eyebrow"><?= $esc($campo('llega-al-peru', 'rotulo', 'La visita')) ?></p>
      <h2 class="visita__h" id="t-visita"><?= $esc($campo('llega-al-peru', 'titulo', 'El Papa León llega al Perú')) ?></h2>

      <?php $textoVisita = $campo('llega-al-peru', 'texto', ''); ?>
      <div class="visita__copy">
        <?php if ($textoVisita !== ''): ?>
          <?= $textoVisita ?>
        <?php else: ?>
          <p><strong>Del 11 al 16 de noviembre de 2026</strong>, el Santo Padre León XIV realizará su Visita Apostólica al Perú, recorriendo Lima, Chiclayo, Cusco y Pucallpa.</p>
          <p>el Santo Padre León XIV recorrerá Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa para encontrarse con el pueblo peruano. Su llegada será una oportunidad para renovar la fe, fortalecer los vínculos que nos unen y reconocer, en medio de nuestras diferencias, aquello que compartimos como nación.</p>
        <?php endif; ?>
      </div>

      <a class="btn btn--gold visita__cta" href="<?= $esc($campo('llega-al-peru', 'cta_url', $sitio->enlace('agenda/'))) ?>"><?= $esc($campo('llega-al-peru', 'cta_texto', 'Agenda')) ?></a>
    </div>
  </section>

  <?php /* ══════════════════════════════════════════════════ EL RECORRIDO ══ */ ?>
  <?php
  $ciudadesCards = $bloques('el-recorrido', [
      ['titulo' => 'Lima',     'img' => 'p13', 'w' => 835, 'h' => 767, 'alt' => 'Basílica Catedral de Lima en la Plaza Mayor'],
      ['titulo' => 'Callao',   'img' => 'p14', 'w' => 835, 'h' => 767, 'alt' => 'Catedral del Callao'],
      ['titulo' => 'Chiclayo', 'texto' => 'Santa Cruz', 'img' => 'p15', 'w' => 835, 'h' => 767, 'alt' => 'Catedral Santa María de Chiclayo'],
      ['titulo' => 'Cusco',    'img' => 'p16', 'w' => 835, 'h' => 767, 'alt' => 'Catedral del Cusco'],
      ['titulo' => 'Pucallpa', 'img' => 'p17', 'w' => 826, 'h' => 759, 'alt' => 'Catedral de Pucallpa'],
  ]);
  ?>
  <section class="recorrido" aria-labelledby="t-recorrido">
    <div class="container">
      <p class="recorrido__eyebrow t-center"><?= $esc($campo('el-recorrido', 'rotulo', 'El recorrido')) ?></p>
      <?php
      /* El titular va en dos líneas de cuerpo distinto. Del panel llega una
         sola cadena: se parte por la coma, que es donde el editable lo rompe. */
      $tituloRec = $campo('el-recorrido', 'titulo', 'Seis ciudades, una sola nación');
      $partes    = preg_split('/,\s*/u', $tituloRec, 2) ?: [$tituloRec];
      ?>
      <h2 class="recorrido__h t-center" id="t-recorrido">
        <b><?= $esc($partes[0] . (isset($partes[1]) ? ',' : '')) ?></b>
        <?php if (isset($partes[1])): ?><span class="w-med"><?= $esc($partes[1]) ?></span><?php endif; ?>
      </h2>

      <?php $leadRec = $campo('el-recorrido', 'texto', ''); ?>
      <?php if ($leadRec !== ''): ?>
        <div class="recorrido__lead t-center"><?= $leadRec ?></div>
      <?php else: ?>
        <p class="recorrido__lead t-center">Seis ciudades que expresan la diversidad y riqueza de la Iglesia en el Perú. Cada sede será espacio de encuentro con el Santo Padre y reflejará una historia, una comunidad y una forma particular de vivir la fe.</p>
      <?php endif; ?>

      <ul class="ciudades">
        <?php foreach ($ciudadesCards as $c): ?>
          <li class="ciudad">
            <a href="<?= $esc((string) ($c['enlace_url'] ?? '') ?: $sitio->enlace('sedes/')) ?>">
              <?php
              ob_start();
              if (!empty($c['img'])): ?>
                <picture>
                  <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $c['img'] . '.webp')) ?>" type="image/webp">
                  <img src="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $c['img'] . '.jpg')) ?>"
                       alt="<?= $esc((string) ($c['alt'] ?? '')) ?>" width="<?= (int) ($c['w'] ?? 835) ?>" height="<?= (int) ($c['h'] ?? 767) ?>"
                       loading="lazy" decoding="async">
                </picture>
              <?php endif;
              $respaldoC = (string) ob_get_clean(); ?>
              <?= $sitio->imagen($c, $respaldoC, ['sizes' => '(min-width:1024px) 30vw, 100vw']) ?>
              <span class="ciudad__cap">
                <span class="ciudad__name"><?= $esc((string) ($c['titulo'] ?? '')) ?></span>
                <?php if (!empty($c['texto'])): ?><span class="ciudad__sub"><?= $esc((string) $c['texto']) ?></span><?php endif; ?>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <?php /* ═════════════════════════════════════════════════════ LOS SANTOS ══ */ ?>
  <?php
  $santos = $bloques('tierra-de-santos', [
      ['titulo' => "Santa Rosa\nde Lima",       'rotulo' => 'ORACIÓN',      'img' => 'p02', 'w' => 491, 'h' => 761, 'alt' => 'Santa Rosa de Lima'],
      ['titulo' => "San Martín\nde Porres",     'rotulo' => 'CARIDAD',      'img' => 'p03', 'w' => 484, 'h' => 750, 'alt' => 'San Martín de Porres'],
      ['titulo' => "San Juan\nMacías",          'rotulo' => 'MISERICORDIA', 'img' => 'p04', 'w' => 491, 'h' => 734, 'alt' => 'San Juan Macías'],
      ['titulo' => "San Francisco\nSolano",     'rotulo' => 'MISIÓN',       'img' => 'p05', 'w' => 352, 'h' => 546, 'alt' => 'San Francisco Solano'],
      ['titulo' => "Santo Toribio\nde Mogrovejo",'rotulo' => 'PASTOR',      'img' => 'p06', 'w' => 491, 'h' => 761, 'alt' => 'Santo Toribio de Mogrovejo'],
  ]);
  ?>
  <section class="santos-home" aria-labelledby="t-santos">
    <div class="container">
      <?php
      $tituloSan = $campo('tierra-de-santos', 'titulo', 'Cinco santos, un mismo corazón');
      $partesSan = preg_split('/,\s*/u', $tituloSan, 2) ?: [$tituloSan];
      ?>
      <h2 class="santos-home__h t-center t-gold" id="t-santos">
        <b><?= $esc($partesSan[0] . (isset($partesSan[1]) ? ',' : '')) ?></b>
        <?php if (isset($partesSan[1])): ?><span class="w-med"><?= $esc($partesSan[1]) ?></span><?php endif; ?>
      </h2>

      <?php $leadSan = $campo('tierra-de-santos', 'texto', ''); ?>
      <?php if ($leadSan !== ''): ?>
        <div class="santos-home__lead t-center"><?= $leadSan ?></div>
      <?php else: ?>
        <p class="santos-home__lead t-center">El Perú tiene una tradición de santidad que forma parte de la <strong>identidad espiritual</strong> de su pueblo.</p>
      <?php endif; ?>

      <ul class="santos-grid">
        <?php foreach ($santos as $s): ?>
          <li class="santo">
            <a href="<?= $esc((string) ($s['enlace_url'] ?? '') ?: $sitio->enlace('santos/')) ?>">
              <?php
              ob_start();
              if (!empty($s['img'])): ?>
                <picture>
                  <source srcset="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $s['img'] . '.webp')) ?>" type="image/webp">
                  <img src="<?= $esc($sitio->asset('assets/img/rediseno/index/' . $s['img'] . '.jpg')) ?>"
                       alt="<?= $esc((string) ($s['alt'] ?? '')) ?>" width="<?= (int) ($s['w'] ?? 491) ?>" height="<?= (int) ($s['h'] ?? 761) ?>"
                       loading="lazy" decoding="async">
                </picture>
              <?php endif;
              $respaldoS = (string) ob_get_clean(); ?>
              <?= $sitio->imagen($s, $respaldoS, ['sizes' => '(min-width:1024px) 18vw, 45vw']) ?>
              <span class="santo__cap">
                <span class="santo__name"><?= nl2br($esc((string) ($s['titulo'] ?? ''))) ?></span>
                <span class="santo__virtue"><?= $esc((string) ($s['rotulo'] ?? '')) ?></span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

</main>
