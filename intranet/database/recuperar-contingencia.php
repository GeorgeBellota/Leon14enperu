<?php
/**
 * ============================================================================
 *  Recupera las inscripciones que se guardaron fuera de la base.
 * ============================================================================
 *
 *  Cuando la base no puede aceptar una inscripción, el formulario la escribe
 *  cifrada en `intranet/almacen/contingencia/`. Este programa las lee y las
 *  mete en la base, una a una.
 *
 *  Se ejecuta desde la consola:
 *
 *      php intranet/database/recuperar-contingencia.php            (ver qué hay)
 *      php intranet/database/recuperar-contingencia.php --aplicar  (meterlas)
 *
 *  Sin --aplicar no toca nada: enseña lo que haría. Esa es la forma correcta
 *  de usarlo la primera vez.
 *
 *  Los sobres recuperados NO se borran: se mueven a `recuperados/`. Si algo
 *  sale mal y nadie lo nota hasta la semana siguiente, el original sigue ahí.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/Core/Autoloader.php';

use Intranet\Core\Autoloader;
use Intranet\Core\Contenedor;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Request;
use Intranet\Models\Voluntario;
use Intranet\Publico\Contingencia;

Autoloader::registrar(__DIR__ . '/../app');

$config = require __DIR__ . '/../config/config.php';
$c      = new Contenedor($config, Request::capturar());

$contingencia = new Contingencia($c->cripto());
$voluntarios  = new Voluntario($c);

$aplicar = in_array('--aplicar', $argv, true);

$pendientes = $contingencia->pendientes();

printf("Almacén: %s\n", $contingencia->donde());
printf("Sobres pendientes: %d\n\n", count($pendientes));

if ($pendientes === []) {
    echo "No hay nada que recuperar.\n";
    exit(0);
}

if (!$aplicar) {
    echo "MODO PRUEBA · no se va a escribir nada. Añade --aplicar para hacerlo de verdad.\n\n";
}

$metidas = 0;
$repetidas = 0;
$fallidas = 0;

foreach ($pendientes as $item) {
    $sobre = $item['sobre'];
    $datos = $sobre['datos'] ?? [];
    $meta  = $sobre['meta'] ?? [];

    printf(
        "%-34s %s%s\n",
        (string) ($sobre['codigo'] ?? '?'),
        (string) ($sobre['recibido'] ?? '?'),
        !empty($meta['sin_validar']) ? '   ⚠ NO VALIDADA' : ''
    );
    printf("    %s · %s\n",
        (string) ($datos['nombres'] ?? '(sin nombre)'),
        (string) ($datos['correo'] ?? '(sin correo)'));

    if (!$aplicar) {
        continue;
    }

    try {
        // Se reutiliza el mismo camino que el formulario. Si el sobre venía
        // sin validar —porque la base estaba caída y no se pudo comprobar
        // nada—, aquí sí se comprueba: el modelo aplica sus reglas y lanza si
        // algo no cuadra. Es el momento correcto para descubrirlo.
        $resultado = $voluntarios->registrar(
            $datos,
            null,
            'recuperado de contingencia (' . (string) ($sobre['codigo'] ?? '') . ')'
        );

        printf("    → guardada como %s\n", $resultado['codigo']);
        $metidas++;
        $contingencia->archivar($item['archivo']);
    } catch (ErrorDeNegocio $e) {
        // Lo más común: el DNI ya está inscrito. Suele significar que la
        // persona lo intentó otra vez y esa segunda vez sí entró. No es un
        // fallo, y el sobre se aparta igual.
        printf("    → ya estaba: %s\n", $e->getMessage());
        $repetidas++;
        $contingencia->archivar($item['archivo']);
    } catch (Throwable $e) {
        // El sobre NO se aparta: se queda para el siguiente intento.
        printf("    → FALLÓ: %s\n", $e->getMessage());
        $fallidas++;
    }
}

echo "\n";

if ($aplicar) {
    printf("Guardadas: %d · ya estaban: %d · fallidas: %d\n", $metidas, $repetidas, $fallidas);

    if ($fallidas > 0) {
        echo "\nLas fallidas siguen en el almacén. Revisa el motivo y vuelve a ejecutarlo.\n";
        exit(1);
    }
} else {
    printf("Se intentarían %d. Ejecuta con --aplicar cuando lo veas bien.\n", count($pendientes));
}

exit(0);
