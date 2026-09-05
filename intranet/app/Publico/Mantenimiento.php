<?php
/**
 * ============================================================================
 *  Mantenimiento — cerrar el sitio público sin cerrarse la puerta uno mismo.
 * ============================================================================
 *
 *  Cuando está activo, cualquier página pública responde 503 con un aviso, y
 *  el visitante no ve nada más. Las direcciones IP registradas en el panel
 *  siguen viendo el sitio normal.
 *
 *  Tres reglas que no cambian:
 *
 *  1. LA INTRANET NUNCA SE CORTA. Si el mantenimiento alcanzara al panel,
 *     quien lo activó desde fuera de la lista blanca se quedaría sin forma de
 *     desactivarlo. Por eso esta comprobación sólo se llama desde las páginas
 *     públicas, jamás desde intranet/public/index.php.
 *
 *  2. SI FALLA LA BASE, EL SITIO SIGUE ABIERTO. Un error de MySQL no puede
 *     tumbar la web entera; el contenido ya tiene sus textos de reserva.
 *
 *  3. Se responde 503 y no 200. Un buscador que encuentre un 200 con el aviso
 *     lo indexa como si fuera la página; con 503 entiende que es temporal y
 *     vuelve más tarde.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use Intranet\Core\Contenedor;
use Intranet\Models\Catalogo;
use Throwable;

final class Mantenimiento
{
    /**
     * Recibe el Contenedor y no el Sitio: el panel también necesita la
     * comprobación de IP —para avisar a quien va a cerrar el sitio de que se
     * quedará fuera— y allí no hay ningún Sitio que pasarle.
     */
    public function __construct(private Contenedor $c)
    {
    }

    /**
     * Corta la petición si procede. Si no, devuelve y la página sigue.
     * No devuelve nada: o deja pasar, o imprime el aviso y termina.
     */
    public function aplicar(): void
    {
        try {
            $catalogo = new Catalogo($this->c);

            if (!$catalogo->ajusteBool('mantenimiento.activo', false)) {
                return;
            }

            $ip = $this->c->peticion()->ip();

            if ($this->permitida($ip)) {
                $this->anotarPaso($ip);

                return;
            }

            $this->responder(
                (string) $catalogo->ajuste('mantenimiento.titulo', 'Volvemos enseguida'),
                (string) $catalogo->ajuste('mantenimiento.mensaje', 'Estamos trabajando en esta web.'),
                (string) $catalogo->ajuste('mantenimiento.vuelve', '')
            );
        } catch (Throwable $e) {
            // Regla 2: ante la duda, el sitio se queda abierto.
            error_log('[mantenimiento] no se pudo comprobar: ' . $e->getMessage());
        }
    }

    /** ¿Está esta IP en la lista blanca? Admite direcciones sueltas y rangos. */
    public function permitida(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        $filas = $this->c->bd()->columna(
            'SELECT ip FROM ips_permitidas WHERE activa = 1'
        );

        foreach ($filas as $patron) {
            if (self::coincide($ip, (string) $patron)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compara una IP con una entrada de la lista, que puede ser:
     *   · una dirección exacta          203.0.113.7
     *   · un rango CIDR                 203.0.113.0/24
     *   · localhost en sus dos formas   127.0.0.1  ::1
     */
    public static function coincide(string $ip, string $patron): bool
    {
        $patron = trim($patron);

        if ($patron === '') {
            return false;
        }

        if (strcasecmp($ip, $patron) === 0) {
            return true;
        }

        if (!str_contains($patron, '/')) {
            return false;
        }

        [$red, $bits] = explode('/', $patron, 2);

        $binIp  = @inet_pton($ip);
        $binRed = @inet_pton(trim($red));

        // Una IPv4 y una IPv6 no se comparan entre sí: tienen distinta longitud.
        if ($binIp === false || $binRed === false || strlen($binIp) !== strlen($binRed)) {
            return false;
        }

        $bits = (int) $bits;
        $max  = strlen($binIp) * 8;

        if ($bits < 0 || $bits > $max) {
            return false;
        }

        // Se comparan los primeros $bits bits: los bytes enteros de golpe y el
        // byte a medias con una máscara.
        $bytesEnteros = intdiv($bits, 8);
        $bitsSueltos  = $bits % 8;

        if ($bytesEnteros > 0 && strncmp($binIp, $binRed, $bytesEnteros) !== 0) {
            return false;
        }

        if ($bitsSueltos === 0) {
            return true;
        }

        $mascara = ~((1 << (8 - $bitsSueltos)) - 1) & 0xFF;

        return (ord($binIp[$bytesEnteros]) & $mascara) === (ord($binRed[$bytesEnteros]) & $mascara);
    }

    /** Deja constancia de que esa IP entró durante un mantenimiento. */
    private function anotarPaso(string $ip): void
    {
        try {
            $this->c->bd()->consultar(
                'UPDATE ips_permitidas SET ultimo_uso = NOW() WHERE ip = :ip',
                ['ip' => $ip]
            );
        } catch (Throwable $e) {
            // Anotar es un extra; que falle no puede impedir el paso.
        }
    }

    /** Imprime el aviso y termina la petición. */
    private function responder(string $titulo, string $mensaje, string $vuelve): void
    {
        $e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        // Que ninguna caché guarde esta página: cuando se abra el sitio, debe
        // desaparecer al instante.
        header('Cache-Control: no-store, must-revalidate');
        header('Retry-After: 3600');

        $sitio = rtrim((string) $this->c->config('url.sitio', ''), '/');

        echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<meta name="robots" content="noindex">'
           . '<title>' . $e($titulo) . ' · León XIV en el Perú</title>'
           . '<link rel="icon" href="' . $e($sitio) . '/favicon.svg" type="image/svg+xml">'
           . '<link rel="preconnect" href="https://fonts.googleapis.com">'
           . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
           . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Instrument+Sans:wght@400&display=swap">'
           . '<link rel="stylesheet" href="' . $e($sitio) . '/assets/css/tokens.css">'
           . '<style>'
           . 'body{margin:0;min-height:100vh;display:grid;place-items:center;padding:var(--space-l);'
           . 'background:var(--surface-invert);color:var(--on-primary);'
           . 'font-family:var(--font-ui);font-size:var(--step-0);line-height:1.6;text-align:center}'
           . '.caja{max-width:34rem}'
           . 'h1{font-family:var(--font-display);font-weight:600;font-size:var(--step-3);'
           . 'line-height:1.1;margin:0 0 var(--space-m)}'
           . 'p{margin:0 0 var(--space-s)}'
           . '.rotulo{font-size:var(--step--2);letter-spacing:var(--track-rotulo);'
           . 'text-transform:uppercase;opacity:.8;margin:0 0 var(--space-s)}'
           . '.vuelve{margin-top:var(--space-l);padding-top:var(--space-m);'
           . 'border-top:1px solid rgba(255,255,255,.28);font-size:var(--step--1);opacity:.85}'
           . 'svg{width:44px;height:44px;margin:0 auto var(--space-m);display:block;color:var(--gold-invert)}'
           . '</style></head><body><main class="caja">'
           . '<svg viewBox="0 0 100 145" aria-hidden="true" fill="currentColor">'
           . '<path d="M50 2 C41 25 35 41 35 56 C35 71 42 84 50 96 C58 84 65 71 65 56 C65 41 59 25 50 2 Z"/>'
           . '<path d="M48 56 C40 45 20 43 8 53 C-1 61 0 75 11 83 C7 72 9 61 17 55 C27 49 41 50 48 56 Z"/>'
           . '<path d="M52 56 C60 45 80 43 92 53 C101 61 100 75 89 83 C93 72 91 61 83 55 C73 49 59 50 52 56 Z"/>'
           . '<path d="M20 88 H80 C83.5 88 85 90.5 85 94.5 C85 98.5 83.5 101 80 101 H20 C16.5 101 15 98.5 15 94.5 C15 90.5 16.5 88 20 88 Z"/>'
           . '<path d="M40 101 C39 115 35 128 26 139 C37 136 45 129 50 121 C55 129 63 136 74 139 C65 128 61 115 60 101 Z"/>'
           . '</svg>'
           . '<p class="rotulo">León XIV en el Perú</p>'
           . '<h1>' . $e($titulo) . '</h1>'
           . '<p>' . $e($mensaje) . '</p>'
           . ($vuelve !== '' ? '<p class="vuelve">' . $e($vuelve) . '</p>' : '')
           . '</main></body></html>';

        exit;
    }
}
