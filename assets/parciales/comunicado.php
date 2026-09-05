<?php
/**
 * ============================================================================
 *  El comunicado: el aviso que aparece sobre la página.
 * ============================================================================
 *
 *  Se incluye desde cualquier página del sitio y se pinta solo si hay uno
 *  publicado, en fecha, y configurado para salir en ESTA página. Si no hay
 *  ninguno, este archivo no escribe nada: ni un div vacío.
 *
 *  Requiere dos variables que ya define cada página:
 *      $sitio   el arranque público
 *      $activa  la clave de la página («agenda», «voluntariado»…)
 *      $raiz    el camino hasta la raíz («../» desde una subcarpeta)
 *
 *  ── Sobre las rutas ──────────────────────────────────────────────────────
 *  Todas salen de $sitio->url(), que devuelve direcciones absolutas. Las
 *  relativas ya nos costaron dos veces —el endpoint del ubigeo y su archivo de
 *  respaldo— porque esta misma página se sirve en la raíz y dentro de una
 *  carpeta, y una ruta relativa apunta a un sitio distinto en cada caso.
 */

declare(strict_types=1);

if (!isset($sitio) || !$sitio instanceof \Intranet\Publico\Sitio) {
    return;
}

$comunicado = null;

try {
    $comunicado = (new \Intranet\Models\Comunicado($sitio->contenedor()))
        ->vigentePara((string) ($activa ?? ''));
} catch (\Throwable $e) {
    // La base no responde. La página se pinta sin aviso, que es exactamente lo
    // que se ve cuando no hay ninguno publicado: nadie nota nada.
    error_log('[comunicado] no se pudo leer: ' . $e->getMessage());
}

if ($comunicado === null) {
    return;
}

$esc = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$idComunicado = (int) $comunicado['id'];
$esDescarga   = $comunicado['boton_tipo'] === 'descarga';

/* El destino del botón.

   En las descargas NO se enlaza el archivo directamente: se enlaza un
   endpoint que cuenta la descarga y luego sirve el archivo. Enlazar el
   archivo a pelo dejaría sin contar a quien lo abre en otra pestaña o lo
   guarda con el botón derecho, que es justo como se descarga un PDF. */
$destino = $esDescarga
    ? $sitio->url('comunicado.php?id=' . $idComunicado . '&a=descargar')
    : (string) $comunicado['boton_destino'];

// Un destino interno («voluntariado/») se convierte en absoluto; uno externo
// se deja como está.
if (!$esDescarga && !preg_match('#^https?://#i', $destino)) {
    $destino = $sitio->url($destino);
}
?>
<dialog class="cta-modal" data-comunicado
        data-id="<?= $idComunicado ?>"
        data-aviso="<?= $esc($sitio->url('comunicado.php')) ?>"
        data-veces="<?= (int) $comunicado['veces_max'] ?>"
        data-retraso="<?= (int) $comunicado['retraso_ms'] ?>"
        data-autocierre="<?= (int) $comunicado['autocierre_ms'] ?>"
        aria-labelledby="comunicado-texto">

  <button class="cta-modal__aspa" type="button" data-cta-cerrar aria-label="Cerrar el aviso">
    <svg aria-hidden="true"><use href="#i-cerrar"/></svg>
  </button>

  <?php if (!empty($comunicado['imagen'])): ?>
    <span class="cta-modal__media">
      <img src="<?= $esc($sitio->url((string) $comunicado['imagen'])) ?>"
           alt="" loading="lazy" decoding="async">
    </span>
  <?php endif; ?>

  <div class="cta-modal__cuerpo">
    <?php if (trim((string) $comunicado['descripcion']) !== ''): ?>
      <p class="cta-modal__texto" id="comunicado-texto"><?= nl2br($esc($comunicado['descripcion'])) ?></p>
    <?php endif; ?>

    <div class="cta-modal__acciones">
      <?php if ($esDescarga): ?>
        <?php /* `download` además del endpoint: con los dos, el navegador
                 guarda el archivo en lugar de intentar abrirlo, y el conteo
                 sigue ocurriendo en el servidor pase lo que pase. */ ?>
        <a class="btn btn--primario" href="<?= $esc($destino) ?>"
           download="<?= $esc($comunicado['archivo_nombre'] ?: 'documento') ?>"
           data-comunicado-clic>
          <?= $esc($comunicado['boton_texto']) ?>
        </a>
      <?php else: ?>
        <?php /* `rel="noopener"` no es opcional al abrir en otra pestaña: sin
                 él, la página de destino puede manipular la nuestra desde
                 JavaScript. */ ?>
        <a class="btn btn--primario" href="<?= $esc($destino) ?>"
           target="_blank" rel="noopener noreferrer"
           data-comunicado-clic>
          <?= $esc($comunicado['boton_texto']) ?>
        </a>
      <?php endif; ?>

      <button class="btn btn--linea" type="button" data-cta-cerrar>Ahora no</button>
    </div>
  </div>
</dialog>

<script src="<?= $esc($sitio->asset('assets/js/comunicado.js', (string) ($raiz ?? ''))) ?>" defer></script>
