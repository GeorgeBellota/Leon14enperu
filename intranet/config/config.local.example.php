<?php
/**
 * Copiar este archivo a config.local.php y rellenarlo.
 * config.local.php NO se sube al repositorio ni al servidor por FTP a ciegas:
 * contiene la clave con la que se cifran los datos personales.
 *
 * Para generar la clave de aplicación:
 *     php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 *
 * ⚠ Si se pierde esa clave, los DNI, direcciones y teléfonos ya guardados NO
 *   se pueden recuperar. Guárdala en el gestor de contraseñas de la
 *   organización y en la copia de seguridad, no sólo en el servidor.
 */

declare(strict_types=1);

return [
    'app' => [
        'entorno' => 'desarrollo',
        'clave'   => 'PEGA-AQUI-LA-CLAVE-BASE64-DE-32-BYTES',
    ],

    'bd' => [
        'host'    => '127.0.0.1',
        'base'    => 'leon14peru',
        'usuario' => 'root',
        'clave'   => '',
    ],

    'url' => [
        'sitio' => 'http://localhost/leon14peru',
        'panel' => 'http://localhost/leon14peru/intranet/public',
    ],
];
