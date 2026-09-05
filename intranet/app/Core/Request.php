<?php
/**
 * Request — la petición HTTP, envuelta.
 *
 * Existe para que ningún controlador toque $_GET, $_POST o $_SERVER
 * directamente. Todo lo que entra se lee desde aquí, y aquí es donde se
 * resuelve el problema real de XAMPP: el panel no cuelga del dominio raíz sino
 * de /leon14peru/intranet/public, así que hay que descontar ese prefijo antes
 * de comparar la ruta con las del enrutador.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Request
{
    private string $metodo;
    private string $ruta;
    private string $base;

    /** @var array<string, mixed> */
    private array $get;

    /** @var array<string, mixed> */
    private array $post;

    private function __construct()
    {
        $this->get  = $_GET;
        $this->post = $_POST;

        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // HEAD se trata como GET. Es lo que manda HTTP: todo recurso que
        // responde a GET debe responder a HEAD con las mismas cabeceras y sin
        // cuerpo, y de eso último ya se encarga PHP solo. Sin esto, el
        // enrutador no encontraba ninguna ruta para HEAD y devolvía 405 a los
        // monitores de disponibilidad y a los buscadores.
        if ($metodo === 'HEAD') {
            $metodo = 'GET';
        }

        // Los formularios HTML sólo saben enviar GET y POST. Para PUT/DELETE se
        // admite el campo oculto _metodo, que es la convención habitual.
        if ($metodo === 'POST' && isset($this->post['_metodo'])) {
            $declarado = strtoupper((string) $this->post['_metodo']);
            if (in_array($declarado, ['PUT', 'PATCH', 'DELETE'], true)) {
                $metodo = $declarado;
            }
        }
        $this->metodo = $metodo;

        // Prefijo bajo el que vive el panel. dirname(SCRIPT_NAME) da
        // «/leon14peru/intranet/public» sin necesidad de configurarlo a mano.
        //
        // La normalización va DESPUÉS de dirname(), no antes: en Windows,
        // dirname('/index.php') devuelve '\' —el separador del sistema— y ese
        // valor se colaba como prefijo en todos los enlaces y en el path de la
        // cookie de sesión. Es justo el caso de producción, con el
        // DocumentRoot apuntando ya a public/.
        $script     = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $this->base = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($this->base === '.' || $this->base === '/') {
            $this->base = '';
        }

        $uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if ($this->base !== '' && str_starts_with($uri, $this->base)) {
            $uri = substr($uri, strlen($this->base));
        }

        $this->ruta = '/' . trim(rawurldecode($uri), '/');
    }

    public static function capturar(): self
    {
        return new self();
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    /** Ruta ya limpia de prefijo: «/voluntarios/12». */
    public function ruta(): string
    {
        return $this->ruta;
    }

    /** Prefijo del panel, para construir enlaces y la cookie de sesión. */
    public function base(): string
    {
        return $this->base;
    }

    public function esPost(): bool
    {
        return $this->metodo === 'POST';
    }

    public function get(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->get[$clave] ?? $porDefecto;
    }

    public function post(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->post[$clave] ?? $porDefecto;
    }

    /** Campo de formulario como texto limpio: sin espacios sobrantes ni nulos. */
    public function texto(string $clave, string $porDefecto = ''): string
    {
        $valor = $this->post[$clave] ?? $this->get[$clave] ?? $porDefecto;

        return is_scalar($valor) ? trim((string) $valor) : $porDefecto;
    }

    public function entero(string $clave, int $porDefecto = 0): int
    {
        $valor = $this->post[$clave] ?? $this->get[$clave] ?? null;

        return is_numeric($valor) ? (int) $valor : $porDefecto;
    }

    public function casilla(string $clave): bool
    {
        $valor = $this->post[$clave] ?? null;

        return in_array($valor, ['1', 'on', 'true', 'si', 1, true], true);
    }

    /** @return array<string, mixed> */
    public function todo(): array
    {
        return $this->metodo === 'GET' ? $this->get : $this->post;
    }

    public function esAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /**
     * IP del cliente en binario, lista para una columna VARBINARY(16).
     * No se leen cabeceras tipo X-Forwarded-For: son falsificables salvo que
     * haya un proxy de confianza delante, y aquí no lo hay.
     */
    public function ipBinaria(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $bin = @inet_pton($ip);

        return $bin === false ? null : $bin;
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    public function agente(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }
}
