<?php
/**
 * Autoloader PSR-4 mínimo. Doce líneas útiles y ninguna dependencia: es todo
 * lo que hace falta para no escribir un `require` por clase.
 *
 * Mapea el espacio de nombres `Intranet\` a la carpeta app/:
 *     Intranet\Core\Database              → app/Core/Database.php
 *     Intranet\Controllers\AuthController → app/Controllers/AuthController.php
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Autoloader
{
    private const PREFIJO = 'Intranet\\';

    public static function registrar(string $rutaApp): void
    {
        // realpath() y no un simple rtrim: en Windows, `dirname(__DIR__) . '/app'`
        // produce «C:\...\intranet/app», con las barras mezcladas, mientras que
        // el realpath() de cada archivo devuelve todo con contrabarra. La
        // comprobación de prefijo de más abajo fallaba siempre y no se cargaba
        // ni una clase.
        $normalizada = realpath($rutaApp);

        if ($normalizada === false) {
            throw new \RuntimeException("No existe la carpeta de clases: {$rutaApp}");
        }

        $rutaApp = rtrim($normalizada, DIRECTORY_SEPARATOR);

        spl_autoload_register(static function (string $clase) use ($rutaApp): void {
            if (!str_starts_with($clase, self::PREFIJO)) {
                return;
            }

            $relativa = substr($clase, strlen(self::PREFIJO));
            $archivo  = $rutaApp . DIRECTORY_SEPARATOR
                      . str_replace('\\', DIRECTORY_SEPARATOR, $relativa) . '.php';

            // realpath + comprobación de prefijo: sin esto, un nombre de clase
            // con «..» construido desde una petición podría leer archivos de
            // fuera de app/.
            $real = realpath($archivo);
            if ($real !== false && str_starts_with($real, $rutaApp) && is_file($real)) {
                require $real;
            }
        });
    }
}
