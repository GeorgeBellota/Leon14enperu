<?php
/**
 * ============================================================================
 *  CONTROLADOR FRONTAL
 *  Único archivo PHP alcanzable desde el navegador. Todo lo demás vive fuera
 *  de esta carpeta y sólo se llega a ello a través de aquí.
 * ============================================================================
 */

declare(strict_types=1);

// ── Arranque ─────────────────────────────────────────────────────────────
$raiz = dirname(__DIR__);

require $raiz . '/app/Core/Autoloader.php';
\Intranet\Core\Autoloader::registrar($raiz . '/app');

use Intranet\Core\Contenedor;
use Intranet\Core\Request;
use Intranet\Core\Response;
use Intranet\Core\Router;

$config = require $raiz . '/config/config.php';

date_default_timezone_set($config['app']['zona'] ?? 'America/Lima');
mb_internal_encoding('UTF-8');

// En desarrollo los errores se ven; en producción se registran y no se
// enseñan: una traza de PHP en pantalla revela rutas y a veces credenciales.
$desarrollo = ($config['app']['entorno'] ?? 'produccion') === 'desarrollo';
ini_set('display_errors', $desarrollo ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $raiz . '/storage/logs/php.log');
error_reporting(E_ALL);

if (!is_dir($raiz . '/storage/logs')) {
    @mkdir($raiz . '/storage/logs', 0775, true);
}

// ── ¿Está configurado? ───────────────────────────────────────────────────
//
// Se comprueba que la configuración EFECTIVA esté completa, no que exista un
// archivo concreto. Da igual si los valores vienen de config.php o de
// config.local.php: lo que importa es que estén.
//
// La versión anterior exigía la existencia de config.local.php y mandaba a
// crearlo aunque los datos ya estuvieran puestos en config.php. Eso es
// confundir el sitio recomendado para guardar algo con el requisito de
// tenerlo.
$falta = [];

$claveApp = base64_decode((string) ($config['app']['clave'] ?? ''), true);

if ($claveApp === false || strlen($claveApp) < 32) {
    $falta[] = ['app.clave', 'La clave con la que se cifran los DNI. 32 bytes en base64.'];
}

foreach (['base' => 'Nombre de la base de datos', 'usuario' => 'Usuario de MySQL'] as $campo => $queEs) {
    if (trim((string) ($config['bd'][$campo] ?? '')) === '') {
        $falta[] = ['bd.' . $campo, $queEs];
    }
}

if ($falta !== []) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');

    $dondeEsta = is_file($raiz . '/config/config.local.php')
        ? 'Tienes <code>config.local.php</code>: rellénalo ahí.'
        : 'Puedes ponerlo en <code>intranet/config/config.php</code>, o mejor en un '
          . '<code>config.local.php</code> junto a él (ver abajo por qué).';

    $lista = '';
    foreach ($falta as [$clave, $queEs]) {
        $lista .= '<li><code>' . htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') . '</code> — '
                . htmlspecialchars($queEs, ENT_QUOTES, 'UTF-8') . '</li>';
    }

    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Falta configurar</title>'
       . '<body style="font:16px/1.65 system-ui,-apple-system,Segoe UI,sans-serif;'
       . 'max-width:46rem;margin:10vh auto;padding:0 1.5rem;color:#1C1416">'
       . '<h1 style="color:#97101A;font-size:1.6rem">Falta configurar el panel</h1>'
       . '<p>No se puede arrancar hasta que estos valores tengan contenido:</p>'
       . '<ul>' . $lista . '</ul>'
       . '<p>' . $dondeEsta . '</p>'
       . '<p style="margin-top:1.5rem">Para generar la clave de cifrado:</p>'
       . '<pre style="background:#F3E7E5;padding:1rem;border-radius:4px;overflow:auto">'
       . 'php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"</pre>'
       . '<hr style="margin:2rem 0;border:0;border-top:1px solid #E7DEDC">'
       . '<h2 style="font-size:1.05rem">¿Por qué se recomienda config.local.php?</h2>'
       . '<p>Porque <strong>sobrevive a las actualizaciones</strong>. Cuando se suba una '
       . 'versión nueva del sitio, <code>config.php</code> se sobrescribe con la del paquete '
       . 'y se llevaría por delante lo que hayas escrito ahí: las credenciales de MySQL y, '
       . 'sobre todo, <strong>la clave de cifrado</strong>. Perder esa clave significa que '
       . 'los DNI y teléfonos ya guardados dejan de poder leerse.</p>'
       . '<p><code>config.local.php</code> no viaja en los paquetes, así que nadie lo pisa. '
       . '<code>config.php</code> lo lee al final y lo fusiona encima.</p>'
       . '</body></html>';
    exit;
}

// ── Petición → contenedor → rutas → despacho ─────────────────────────────
$peticion   = Request::capturar();
$contenedor = new Contenedor($config, $peticion);

Response::cabecerasSeguridad();

$router = new Router($contenedor);
(require $raiz . '/app/Http/rutas.php')($router);

$router->despachar($peticion);
