<?php
/**
 * ============================================================================
 *  Endpoint del comunicado: contar y servir.
 * ============================================================================
 *
 *  Dos cosas, según lo que se pida:
 *
 *    ?id=N&a=vista      suma una vista. Lo llama el JavaScript al abrirse el
 *                       aviso. Responde 204, sin cuerpo.
 *
 *    ?id=N&a=clic       suma un clic. Lo llama el JavaScript al pulsar el
 *                       botón de un comunicado de tipo enlace.
 *
 *    ?id=N&a=descargar  suma un clic Y sirve el archivo como adjunto.
 *
 *  ── Por qué la descarga pasa por aquí ────────────────────────────────────
 *  Podría enlazarse el archivo directamente y contar el clic con JavaScript,
 *  pero entonces no se contaría a quien abre el enlace en otra pestaña, lo
 *  copia, o lo guarda con el botón derecho —que es justo como se descarga un
 *  PDF—. Sirviéndolo desde aquí, la cuenta la lleva el servidor y no depende
 *  de que el navegador colabore.
 */

declare(strict_types=1);

/** @var \Intranet\Publico\Sitio $sitio */
$sitio = require __DIR__ . '/intranet/app/Publico/arranque.php';

use Intranet\Models\Comunicado;

$id = (int) ($_GET['id'] ?? 0);
$accion = (string) ($_GET['a'] ?? 'vista');

if ($id <= 0 || !in_array($accion, ['vista', 'clic', 'descargar'], true)) {
    http_response_code(400);
    exit;
}

// Nada de esto se guarda en caché: son cuentas.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');

try {
    $modelo = new Comunicado($sitio->contenedor());
    $fila = $modelo->buscar($id);

    if ($fila === null) {
        http_response_code(404);
        exit;
    }

    $modelo->sumar($id, $accion === 'vista' ? 'vistas' : 'clics');
} catch (Throwable $e) {
    // Que falle la cuenta no puede impedir la descarga: el documento importa
    // más que la estadística.
    error_log('[comunicado] no se pudo contar: ' . $e->getMessage());
    $fila = $fila ?? null;
}

// ── Sólo contar ───────────────────────────────────────────────────────────
if ($accion !== 'descargar') {
    http_response_code(204);
    exit;
}

// ── Servir el archivo ─────────────────────────────────────────────────────
if ($fila === null || ($fila['boton_tipo'] ?? '') !== 'descarga') {
    http_response_code(404);
    exit;
}

$relativa = ltrim((string) ($fila['boton_destino'] ?? ''), '/');

/* La ruta se comprueba con realpath contra la carpeta de subidas.
   Sin esto, un valor manipulado en la base —o un error al guardarlo— podría
   hacer que este archivo sirviera cualquier cosa del servidor, empezando por
   config.local.php. Aquí sólo se puede servir lo que está dentro de la carpeta
   de subidas, y nada más. */
$fisica = realpath(__DIR__ . '/' . $relativa);
$permitida = realpath(__DIR__ . '/assets/subidos/comunicados');

if ($fisica === false || $permitida === false
    || !str_starts_with($fisica, $permitida) || !is_file($fisica)) {
    error_log('[comunicado] archivo fuera de sitio o inexistente: ' . $relativa);
    http_response_code(404);
    exit;
}

$nombre = (string) ($fila['archivo_nombre'] ?? '') ?: basename($fisica);

// El nombre va dos veces: `filename` para los navegadores antiguos y
// `filename*` para los que entienden acentos y eñes. Sin el segundo, un
// «Programación.pdf» llega como «Programacin.pdf».
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nombre) . '"'
     . "; filename*=UTF-8''" . rawurlencode($nombre));
header('Content-Length: ' . filesize($fisica));
header('X-Content-Type-Options: nosniff');

// Se limpia cualquier búfer antes de escribir: un espacio en blanco escapado
// de otro archivo se metería dentro del PDF y lo dejaría corrupto.
while (ob_get_level() > 0) {
    ob_end_clean();
}

readfile($fisica);
exit;
