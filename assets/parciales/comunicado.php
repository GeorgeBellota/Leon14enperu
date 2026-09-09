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

/* ── El texto ya no se pinta debajo de la imagen ───────────────────────────
   Lo pidió el cliente: el cartel ocupa el área principal y abajo quedan sólo
   los botones. La pieza que se sube al panel ya trae el mensaje escrito
   dentro, así que repetirlo en un párrafo era decirlo dos veces y robarle
   alto a la imagen.

   PERO NO SE BORRA. Un cartel es una imagen y una imagen no la lee nadie con
   un lector de pantalla: si el texto desapareciera del documento, el aviso
   dejaría de existir para quien navega a ciegas. Sigue ahí, fuera de la
   vista, y es el NOMBRE ACCESIBLE del diálogo —que es como se anunciaba
   antes—. El campo «descripción» del panel conserva su sentido y su sitio.

   Cuando no hay descripción, el nombre del comunicado hace de respaldo. Sin
   esto el diálogo se quedaba con un `aria-labelledby` apuntando a un elemento
   que no existía, y un diálogo sin nombre se anuncia como «diálogo».

   ── Y SÓLO CUANDO HAY CARTEL ─────────────────────────────────────────────

   El texto se retira porque la imagen ya lo dice. Un aviso SIN imagen no dice
   nada: se quedaría en dos botones flotando y nadie sabría a qué está diciendo
   que sí. Así que la regla es condicional —sin pieza gráfica, el párrafo se
   pinta como toda la vida— y no hace falta acordarse de nada al publicar. */
$descripcion = trim((string) $comunicado['descripcion']);

/* ── El cartel de reserva ──────────────────────────────────────────────────
   Mismo trato que el resto del sitio: la pieza vive escrita aquí y, en cuanto
   alguien suba una desde el panel, la suya manda. Así el aviso no depende de
   que nadie se acuerde de subir nada —sin imagen se quedaba en dos botones
   flotando, sin decir a qué estabas diciendo que sí— y sigue siendo el panel
   quien lo cambia el día que cambie la campaña.

   Se comprueba el ARCHIVO y no un ajuste, como hace la cabecera con el
   logotipo: si alguien lo borra por FTP, el aviso vuelve a enseñar su texto en
   lugar de dejar una imagen rota.

   ⚠ ES UNA SOLA PIEZA PARA TODOS LOS AVISOS, y hoy es la de la Colecta. Un
   comunicado FUTURO que se publique sin imagen propia saldría con este cartel,
   que no le corresponde. Mientras la Colecta sea la campaña viva no hay
   problema; cuando deje de serlo, lo correcto es subir la pieza de cada aviso
   desde el panel —que es lo que sustituye a esto— o cambiar este archivo. */
$imagenPropia = trim((string) ($comunicado['imagen'] ?? ''));
$reservaBase  = 'assets/img/banners/modal';
$hayReserva   = is_file(dirname(__DIR__, 2) . '/' . $reservaBase . '-1120.jpg');
$conCartel    = $imagenPropia !== '' || $hayReserva;
?>
<dialog class="cta-modal<?= $conCartel ? ' cta-modal--cartel' : '' ?>" data-comunicado
        data-id="<?= $idComunicado ?>"
        data-aviso="<?= $esc($sitio->url('comunicado.php')) ?>"
        data-veces="<?= (int) $comunicado['veces_max'] ?>"
        data-retraso="<?= (int) $comunicado['retraso_ms'] ?>"
        data-autocierre="<?= (int) $comunicado['autocierre_ms'] ?>"
        <?= $descripcion !== ''
              ? 'aria-labelledby="comunicado-texto"'
              : 'aria-label="' . $esc($comunicado['nombre']) . '"' ?>>

  <button class="cta-modal__aspa" type="button" data-cta-cerrar aria-label="Cerrar el aviso">
    <svg aria-hidden="true"><use href="#i-cerrar"/></svg>
  </button>

  <?php if ($conCartel): ?>
    <span class="cta-modal__media">
      <?php if ($imagenPropia !== ''): ?>
        <?php /* La que subieron al panel, tal cual: llega con la proporción y
                 el peso que tenga y no hay familia de anchos que servir. */ ?>
        <img src="<?= $esc($sitio->url($imagenPropia)) ?>"
             alt="" loading="lazy" decoding="async">
      <?php else: ?>
        <?php /* La de reserva sí va con sus tres anchos: el cartel no se pinta
                 nunca a más de 560 px, así que 1120 cubre las pantallas 2x y
                 un móvil no se descarga la pieza de escritorio. */ ?>
        <picture>
          <source type="image/webp"
                  sizes="(min-width: 616px) 560px, calc(100vw - 56px)"
                  srcset="<?= $esc($sitio->asset($reservaBase . '-560.webp'))  ?> 560w,
                          <?= $esc($sitio->asset($reservaBase . '-840.webp'))  ?> 840w,
                          <?= $esc($sitio->asset($reservaBase . '-1120.webp')) ?> 1120w">
          <img src="<?= $esc($sitio->asset($reservaBase . '-560.jpg')) ?>"
               sizes="(min-width: 616px) 560px, calc(100vw - 56px)"
               srcset="<?= $esc($sitio->asset($reservaBase . '-560.jpg'))  ?> 560w,
                       <?= $esc($sitio->asset($reservaBase . '-840.jpg'))  ?> 840w,
                       <?= $esc($sitio->asset($reservaBase . '-1120.jpg')) ?> 1120w"
               width="1428" height="1588"
               alt="" loading="lazy" decoding="async">
        </picture>
      <?php endif; ?>
    </span>
  <?php endif; ?>

  <div class="cta-modal__cuerpo">
    <?php if ($descripcion !== ''): ?>
      <p class="<?= $conCartel ? 'solo-lectores' : 'cta-modal__texto' ?>" id="comunicado-texto"><?= nl2br($esc($descripcion)) ?></p>
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
