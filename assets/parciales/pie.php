<?php
/**
 * ============================================================================
 *  Pie del sitio — rediseño 2026.
 * ============================================================================
 *
 *  El editable dibuja una banda dorada de 134 px con dos líneas de crédito.
 *  Eso es el pie «simple», y es el que está puesto hoy en producción.
 *
 *  ── Cuánto pie se muestra ────────────────────────────────────────────────
 *  Lo decide el ajuste `pie.modo` desde la intranet, igual que antes:
 *
 *    simple               la banda del editable: créditos y poco más
 *    completo             encima, las cuatro columnas de enlaces y las redes
 *    simple_en_internas   completo en la portada, simple en el resto
 *
 *  Ante la duda —base caída, ajuste sin valor— se muestra el simple: es la
 *  banda que dibuja el diseño, y un pie de más nunca rompe una página.
 *  (Antes el valor de reserva era «completo»; se cambió porque el diseño
 *  nuevo tiene un pie propio y el completo es ahora la excepción.)
 *
 *  @var string $raiz    prefijo hasta la raíz del sitio
 *  @var string $activa  clave de la página actual
 */

$raiz = $raiz ?? './';
$esc  = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$modoPie = 'simple';

if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
    // Igual que en la cabecera: sin el try, una base que no responde cortaba
    // la página en seco en lugar de caer al valor de reserva.
    try {
        $modoPie = (string) $sitio->catalogo()->ajuste('pie.modo', 'simple');
    } catch (\Throwable $e) {
        error_log('[pie] no se pudo leer pie.modo: ' . $e->getMessage());
    }
}

$esPortada = ($activa ?? '') === '' || ($activa ?? '') === 'home';

$pieCompleto = $modoPie === 'completo'
    || ($modoPie === 'simple_en_internas' && $esPortada);

$columnas = [
    'Mapa del sitio' => [
        ''                => 'Inicio',
        'papa-leon-xiv/'  => 'Papa León XIV',
        'agenda/'         => 'Agenda',
        'sedes/'          => 'Sedes',
        'noticias/'       => 'Noticias',
    ],
    'Para el peregrino' => [
        'guia-del-peregrino/' => 'Guía del peregrino',
        'subsidios/'          => 'Subsidios pastorales',
        'en-directo/'         => 'En directo',
        'santos/'             => 'Santos del Perú',
    ],
    'Cómo ayudar' => [
        'voluntariado/'  => 'Voluntariado',
        'participa/'     => 'Participa',
        'patrocinios/'   => 'Patrocinios',
        'donativo/'      => 'Donativo',
        'transparencia/' => 'Transparencia',
    ],
    'Legal y contacto' => [
        'cep/'                  => 'La Iglesia en el Perú',
        'preguntas-frecuentes/' => 'Preguntas frecuentes',
        'prensa/'               => 'Prensa',
        'contacto/'             => 'Contacto',
        'aviso-legal/'          => 'Aviso legal',
        'privacidad/'           => 'Privacidad',
        'cookies/'              => 'Cookies',
    ],
];

/* ── Los enlaces sueltos de la banda ──────────────────────────────────────
   Las páginas que el diseño no pone en el menú pero a las que tiene que
   haber un camino. Si el pie va en modo completo sobran: ya están en las
   columnas.

   Cuáles son se elige en Configuración → Pie de página, del mismo catálogo
   que el menú. Estaban escritos aquí a fuego, sin forma de quitarlos ni de
   cambiarlos desde el panel.

   El ajuste distingue tres estados, y los tres hacen falta:

     null   nunca se ha configurado → los tres del diseño
     ''     se configuró a ninguno  → la banda se queda sólo con el copyright
     resto  las claves elegidas

   Sin esa distinción, «todavía no lo has tocado» y «lo has apagado» serían
   lo mismo, y no habría manera de dejar la banda limpia. */
$sueltos = [];

if (!$pieCompleto) {
    $guardado = null;

    if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
        try {
            $guardado = $sitio->catalogo()->ajuste('pie.enlaces');
        } catch (\Throwable $e) {
            error_log('[pie] no se pudo leer pie.enlaces: ' . $e->getMessage());
        }
    }

    $claves = $guardado === null
        ? \Intranet\Publico\Menu::porDefectoPie()
        : \Intranet\Publico\Menu::normalizar(explode(',', $guardado));

    $catalogoPie = \Intranet\Publico\Menu::catalogo();

    foreach ($claves as $clave) {
        $sueltos[$clave . '/'] = $catalogoPie[$clave];
    }
}
?>
<footer class="site-footer<?= $pieCompleto ? ' site-footer--completo' : '' ?>">
  <div class="container site-footer__inner">

    <?php if ($pieCompleto): ?>
      <div class="pie-columnas">
        <?php foreach ($columnas as $titulo => $enlaces): ?>
          <div class="pie-columnas__grupo" data-pie-grupo>
            <button class="pie-columnas__titulo" type="button" aria-expanded="false"><?= $esc($titulo) ?></button>
            <ul class="pie-columnas__lista">
              <?php foreach ($enlaces as $destino => $rotulo): ?>
                <li><a href="<?= $esc($raiz . $destino) ?>"><?= $esc($rotulo) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <?php /* Sin cuentas oficiales todavía. Cinco iconos enlazando a «#» son
               cinco enlaces rotos: hasta que existan, esto es un rótulo. */ ?>
      <p class="pie-redes" aria-label="Canales oficiales">
        <span class="pie-redes__iconos" aria-hidden="true">
          <svg><use href="#i-facebook"/></svg><svg><use href="#i-instagram"/></svg><svg><use href="#i-x"/></svg><svg><use href="#i-youtube"/></svg><svg><use href="#i-tiktok"/></svg>
        </span>
        <span class="pie-redes__estado">Canales oficiales · próximamente</span>
      </p>
    <?php endif; ?>

    <p class="site-footer__legal">leon14enperu.com · Viaje apostólico de Su Santidad el Papa León XIV al Perú, 11–16 de noviembre de 2026.</p>

    <?php /* Sin enlaces elegidos no se escribe ni el <nav>: un contenedor de
             navegación vacío es ruido para un lector de pantalla, que lo
             anuncia y no encuentra nada dentro. */ ?>
    <?php if ($sueltos !== []): ?>
      <nav class="site-footer__links" aria-label="Enlaces complementarios">
        <?php foreach ($sueltos as $destino => $rotulo): ?>
          <a href="<?= $esc($raiz . $destino) ?>"<?= rtrim($destino, '/') === ($activa ?? '') ? ' aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <p class="site-footer__credits">Retrato pontificio y escudo: Santa Sede. Fotografía: Santa Sede y cesiones.</p>
  </div>
</footer>
