<?php
/**
 * Aviso de cookies y consentimiento de medición.
 *
 * ── Lo importante de este archivo ──────────────────────────────────────────
 *
 * AQUÍ NO HAY NINGÚN SCRIPT DE SEGUIMIENTO. Sólo viajan los identificadores,
 * en un atributo de datos, inertes. Google Analytics y el píxel de Meta no
 * existen en la página hasta que alguien acepta, y los inyecta el JavaScript
 * de assets/js/consentimiento.js.
 *
 * Es la diferencia entre pedir permiso y avisar de lo ya hecho. Si el script
 * se carga y DESPUÉS sale el cartel, esa persona ya fue medida: el aviso no
 * vale como consentimiento, vale como notificación de algo consumado.
 *
 * ── Aceptar y rechazar pesan lo mismo ──────────────────────────────────────
 *
 * Dos botones del mismo tamaño, uno al lado del otro. Un «rechazar» escondido
 * en letra pequeña, o a dos clics de distancia, no es una elección libre.
 *
 * ── Si no hay JavaScript ───────────────────────────────────────────────────
 *
 * No se mide nada y no se ve el cartel: el bloque nace oculto y sólo el
 * JavaScript lo muestra. Sin él, nadie es seguido, que es el lado correcto en
 * el que equivocarse.
 *
 * @var \Intranet\Publico\Sitio $sitio
 * @var callable                $esc
 */

$medicion = $sitio->medicion();

/* Sin nada configurado, este archivo no pinta ni un byte. */
if ($medicion['ga4'] === '' && $medicion['pixel'] === '') {
    return;
}
?>
<div class="consentimiento" id="aviso-cookies" role="dialog" aria-modal="false"
     aria-labelledby="cookies-titulo" aria-describedby="cookies-texto"
     data-consentimiento
     data-ga4="<?= $esc($medicion['ga4']) ?>"
     data-pixel="<?= $esc($medicion['pixel']) ?>"
     hidden>
  <div class="consentimiento__caja">
    <p class="consentimiento__titulo" id="cookies-titulo">Sobre las cookies</p>

    <p class="consentimiento__texto" id="cookies-texto">
      Usamos cookies de medición para saber qué páginas se visitan y mejorar el
      sitio. No las activamos hasta que lo autorices, y puedes cambiar de
      opinión cuando quieras.
      <a href="<?= $esc($sitio->enlace('cookies/')) ?>">Más información</a>.
    </p>

    <div class="consentimiento__botones">
      <button type="button" class="btn btn--linea" data-cookies-rechazar>Rechazar</button>
      <button type="button" class="btn btn--primario" data-cookies-aceptar>Aceptar</button>
    </div>
  </div>
</div>
