<?php
/**
 * Arranque del sitio público. Devuelve un objeto Sitio ya montado.
 *
 *     $sitio = require __DIR__ . '/../intranet/app/Publico/arranque.php';
 *
 * Deliberadamente silencioso: una página informativa no puede mostrar una
 * traza de PHP a un visitante. Los errores van al log y la página se pinta con
 * sus textos de reserva.
 */

declare(strict_types=1);

use Intranet\Core\Autoloader;
use Intranet\Core\Request;
use Intranet\Publico\Mantenimiento;
use Intranet\Publico\Sitio;

$raizIntranet = dirname(__DIR__, 2);

require_once $raizIntranet . '/app/Core/Autoloader.php';
Autoloader::registrar($raizIntranet . '/app');

$config = require $raizIntranet . '/config/config.php';

date_default_timezone_set($config['app']['zona'] ?? 'America/Lima');
mb_internal_encoding('UTF-8');

// En el sitio público NUNCA se muestran errores, ni en desarrollo: el visitante
// no es quien tiene que verlos. Se registran y punto.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $raizIntranet . '/storage/logs/php.log');

if (!is_dir($raizIntranet . '/storage/logs')) {
    @mkdir($raizIntranet . '/storage/logs', 0775, true);
}

$sitio = new Sitio($config, Request::capturar());

/* ── Cabeceras de seguridad del sitio público ─────────────────────────────
 *
 *  Se emiten desde PHP y no desde el servidor web a propósito. Vivían en un
 *  .htaccess, y al pasar de Apache a Nginx dejaron de aplicarse sin que nada
 *  avisara: Nginx no lee .htaccess y las páginas quedaron sin ellas. Desde
 *  aquí acompañan a la página se sirva donde se sirva.
 *
 *  Lo que hace cada una:
 *
 *   · nosniff        — impide que el navegador adivine el tipo de un archivo
 *                      e interprete como script algo que se sirvió como texto.
 *   · X-Frame-Options — nadie puede meter el formulario en un <iframe> y
 *                      superponerle una capa para robar las pulsaciones.
 *   · Referrer-Policy — el código de inscripción va en la URL tras enviar el
 *                      formulario; sin esto viajaría a cualquier sitio
 *                      enlazado desde esa página.
 *   · HSTS           — obliga al navegador a usar HTTPS durante un año. Es la
 *                      defensa real contra que alguien en la misma red fuerce
 *                      una conexión en claro para leer lo que se envía. Sólo
 *                      se manda si la petición ya llegó cifrada: anunciarlo
 *                      desde HTTP dejaría el dominio inaccesible si algún día
 *                      el certificado falla.
 */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');

    $cifrada = (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    if ($cifrada) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    /* La política de contenido: qué puede cargar y ejecutar la página.
     *
     *  Es la red por debajo del filtro de HtmlSeguro. Aunque algún día se
     *  colara HTML con un <script> dentro, el navegador se negaría a
     *  ejecutarlo: sólo corre el JavaScript de este mismo dominio y el
     *  incrustado que lleve el nonce de esta petición, que cambia cada vez y
     *  no se puede adivinar.
     *
     *  Las librerías se sirven desde aquí, así que 'self' basta para script.
     *  En estilos se admite 'unsafe-inline' porque las páginas llevan
     *  atributos style= sueltos; un estilo incrustado no ejecuta código, así
     *  que el riesgo no es comparable.
     *
     *  form-action 'self' es lo que impide que un formulario manipulado envíe
     *  a otro servidor los datos que se acaban de teclear.
     */
    $nonce = $sitio->nonce();

    header(
        "Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'nonce-{$nonce}'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data:; "
        . "connect-src 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'"
    );

    // La versión de PHP no le sirve a ningún visitante y sí a quien busca un
    // fallo conocido de esa versión concreta.
    header_remove('X-Powered-By');
}

// Modo mantenimiento. Va aquí, en el arranque, y no en cada página: así basta
// con que una página use este arranque para quedar cubierta, y no depende de
// que alguien se acuerde de añadir la comprobación al crear una página nueva.
//
// Si está activo y la IP no está autorizada, esta llamada imprime el aviso y
// termina la petición: lo que venga después de este require no se ejecuta.
// La intranet no pasa por aquí y por tanto nunca se corta.
(new Mantenimiento($sitio->contenedor()))->aplicar();

return $sitio;
