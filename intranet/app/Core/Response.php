<?php
/**
 * Response — salida HTTP.
 *
 * Cabeceras de seguridad incluidas por defecto en toda respuesta HTML del
 * panel. La política de contenido es estricta a propósito: el panel no carga
 * scripts de terceros, así que no hay motivo para permitirlos, y eso convierte
 * cualquier XSS que se escape en algo mucho menos aprovechable.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Response
{
    public static function cabecerasSeguridad(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('X-Frame-Options: DENY');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // 'unsafe-inline' en estilos porque el panel usa atributos style
        // puntuales para las barras de progreso. Los scripts NO lo llevan:
        // todo el JavaScript del panel vive en archivos propios.
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data:; "
            . "form-action 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'"
        );

        // El panel muestra datos personales: que ninguna caché intermedia ni
        // el botón «atrás» los deje en pantalla tras cerrar sesión.
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }

    public static function html(string $contenido, int $codigo = 200): void
    {
        http_response_code($codigo);
        self::cabecerasSeguridad();
        header('Content-Type: text/html; charset=utf-8');
        echo $contenido;
    }

    public static function json(mixed $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        self::cabecerasSeguridad();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirigir(string $url, int $codigo = 302): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, $codigo);
        }
        exit;
    }

    /**
     * Descarga de CSV. Con BOM UTF-8 porque, sin él, Excel en Windows abre las
     * tildes como caracteres rotos y el listado de voluntarios llega ilegible.
     *
     * @param array<int, array<string, mixed>> $filas
     * @param array<string, string>            $columnas  clave => encabezado
     */
    /**
     * @param iterable<array<string, mixed>> $filas
     *
     * `iterable` y no `array`: así se le puede pasar un generador que va
     * sacando filas de la base de una en una. Con `array` había que tenerlas
     * todas en memoria antes de escribir la primera, y eso es lo que obligaba
     * a ponerle un tope a la exportación.
     */
    public static function csv(string $nombre, iterable $filas, array $columnas): void
    {
        // Sin límite de tiempo: una descarga de cien mil filas tarda, y morir
        // a los treinta segundos dejaría un archivo a medias que parece entero.
        @set_time_limit(0);

        self::cabecerasSeguridad();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');

        $salida = fopen('php://output', 'wb');
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, array_values($columnas), ';');

        $escritas = 0;

        foreach ($filas as $fila) {
            $linea = [];
            foreach (array_keys($columnas) as $clave) {
                $valor = $fila[$clave] ?? '';
                // Un valor que empiece por = + - @ lo interpreta Excel como
                // fórmula. Se antepone un apóstrofo para neutralizarlo.
                if (is_string($valor) && $valor !== '' && str_contains('=+-@', $valor[0])) {
                    $valor = "'" . $valor;
                }
                $linea[] = $valor;
            }
            fputcsv($salida, $linea, ';');

            /* Cada fila sale hacia el navegador en cuanto se escribe, en lugar
               de acumularse en el búfer de PHP. Es lo que hace que la memoria
               usada sea la de una fila y no la de la tabla entera. */
            if ($escritas++ % 200 === 0) {
                flush();
            }
        }

        fclose($salida);
        exit;
    }
}
