<?php
/**
 * Pie del sitio, común a todas las páginas.
 *
 * @var string $raiz  prefijo hasta la raíz ('../' desde una subcarpeta)
 */

$raiz = $raiz ?? './';
$esc  = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/* ── Cuánto pie se muestra ────────────────────────────────────────────────
   Lo decide el ajuste `pie.modo` desde la intranet:

     completo             las cuatro columnas de enlaces y las redes
     simple               sólo el copyright
     simple_en_internas   completo en la portada, simple en el resto

   Mientras la mayoría de las páginas no estén terminadas, un mapa del sitio
   con veinte enlaces es un mapa de páginas a medio hacer. El copyright, en
   cambio, tiene que estar siempre: es la única línea del pie que cumple una
   función legal, no de navegación.

   Ante la duda —base caída, ajuste sin valor— se muestra el pie completo: es
   el comportamiento que tenía el sitio antes de que este ajuste existiera. */
$modoPie = 'completo';

if (isset($sitio) && $sitio instanceof \Intranet\Publico\Sitio) {
    // Igual que en la cabecera: sin el try, una base que no responde cortaba
    // la página en seco en lugar de caer al pie completo, que es lo que este
    // archivo dice hacer «ante la duda».
    try {
        $modoPie = (string) $sitio->catalogo()->ajuste('pie.modo', 'completo');
    } catch (\Throwable $e) {
        error_log('[pie] no se pudo leer pie.modo: ' . $e->getMessage());
    }
}

$esPortada = ($activa ?? '') === '' || ($activa ?? '') === 'home';

$pieCompleto = $modoPie === 'completo'
    || ($modoPie === 'simple_en_internas' && $esPortada);

$columnas = [
    'Mapa del sitio' => [
        ''         => 'Inicio',
        'el-papa/' => 'El Papa',
        'agenda/'  => 'Agenda',
        'sedes/'   => 'Sedes',
        'noticias/'=> 'Noticias',
    ],
    'Para el peregrino' => [
        'guia-del-peregrino/' => 'Guía del peregrino',
        'materiales/'         => 'Materiales de pastoral',
        'en-directo/'         => 'En directo',
        '#desde-donde-estes'  => 'Desde donde estés',
    ],
    'Cómo ayudar' => [
        'voluntariado/'  => 'Voluntariado',
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
?>
<footer class="pie<?= $pieCompleto ? '' : ' pie--simple' ?>">
  <div class="contenedor">
    <?php if ($pieCompleto): ?>
      <div class="pie__columnas">
        <?php foreach ($columnas as $titulo => $enlaces): ?>
          <div class="pie__grupo" data-pie-grupo>
            <button class="pie__titulo" type="button"><?= $esc($titulo) ?> <svg aria-hidden="true"><use href="#i-mas"/></svg></button>
            <ul class="pie__lista">
              <?php foreach ($enlaces as $destino => $rotulo): ?>
                <li><a href="<?= $esc($raiz . $destino) ?>"><?= $esc($rotulo) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <?php /* Sin cuentas oficiales todavía. Cinco iconos enlazando a «#» son
               cinco enlaces rotos: hasta que existan, esto es un rótulo. */ ?>
      <div class="pie__redes pie__redes--pendiente" aria-label="Canales oficiales">
        <span class="pie__redes-iconos" aria-hidden="true">
          <svg><use href="#i-facebook"/></svg><svg><use href="#i-instagram"/></svg><svg><use href="#i-x"/></svg><svg><use href="#i-youtube"/></svg><svg><use href="#i-tiktok"/></svg>
        </span>
        <span class="estado">Canales oficiales · próximamente</span>
      </div>
    <?php endif; ?>

    <div class="pie__base">
      <p>leon14enperu.com · Viaje apostólico de Su Santidad el Papa León XIV al Perú, 11–16 de noviembre de 2026.</p>
      <p>Retrato pontificio y escudo: Santa Sede. Fotografía: Santa Sede y cesiones.</p>
    </div>
  </div>
</footer>
