<?php
/**
 * ============================================================================
 *  Crea el primer usuario administrador.
 *
 *  Uso:  php database/crear-admin.php "Nombre Apellido" correo@dominio.pe
 *        php database/crear-admin.php "Nombre Apellido" correo@dominio.pe --generar
 *
 *  La contraseña NO se pasa por argumento: quedaría en el historial del
 *  terminal. Se pide por pantalla, oculta mientras se teclea.
 *
 *  Con --generar, el script inventa una contraseña de un solo uso, la imprime
 *  y marca la cuenta como «debe cambiar»: el panel obligará a sustituirla en
 *  el primer acceso. Es la opción para instalaciones desatendidas, donde no
 *  hay una persona delante del teclado.
 *
 *  El script se niega a trabajar si ya existe un superadmin. Para crear más
 *  usuarios, el sitio es el propio panel.
 * ============================================================================
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script sólo se ejecuta por línea de comandos.\n");
}

require __DIR__ . '/../app/Core/Autoloader.php';
\Intranet\Core\Autoloader::registrar(__DIR__ . '/../app');

$config = require __DIR__ . '/../config/config.php';

$argumentos = array_values(array_filter(
    array_slice($argv, 1),
    static fn (string $a): bool => !str_starts_with($a, '--')
));

$generar = in_array('--generar', $argv, true);
$nombre  = $argumentos[0] ?? '';
$correo  = mb_strtolower(trim($argumentos[1] ?? ''));

if ($nombre === '' || $correo === '') {
    exit("Uso: php database/crear-admin.php \"Nombre Apellido\" correo@dominio.pe [--generar]\n");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    exit("El correo «{$correo}» no es válido.\n");
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['bd']['host'],
    $config['bd']['puerto'],
    $config['bd']['base'],
    $config['bd']['charset']
);

try {
    $pdo = new PDO($dsn, $config['bd']['usuario'], $config['bd']['clave'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    exit("No se pudo conectar. ¿Ejecutaste antes `php database/migrate.php`?\n  {$e->getMessage()}\n");
}

// ── Nadie duplica al administrador ───────────────────────────────────────
$yaHay = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.clave = 'superadmin'"
)->fetchColumn();

if ($yaHay > 0) {
    exit("Ya existe un administrador. Los demás usuarios se crean desde el panel.\n");
}

$rolId = $pdo->query("SELECT id FROM roles WHERE clave = 'superadmin' LIMIT 1")->fetchColumn();

if ($rolId === false) {
    exit("No existe el rol «superadmin». Ejecuta antes: php database/migrate.php\n");
}

// ── Contraseña ───────────────────────────────────────────────────────────
$minimo = (int) ($config['seguridad']['clave_min'] ?? 10);

if ($generar) {
    $clave = generarClave(16);
} else {
    fwrite(STDOUT, "Contraseña para {$correo} (mínimo {$minimo} caracteres): ");
    $clave = leerOculto();
    fwrite(STDOUT, PHP_EOL . 'Repítela: ');
    $repite = leerOculto();
    fwrite(STDOUT, PHP_EOL);

    if (mb_strlen($clave) < $minimo) {
        exit("Demasiado corta. Mínimo {$minimo} caracteres.\n");
    }

    if (!hash_equals($clave, $repite)) {
        exit("Las dos contraseñas no coinciden.\n");
    }
}

$sentencia = $pdo->prepare(
    'INSERT INTO usuarios (rol_id, nombre, correo, clave_hash, activo, debe_cambiar)
     VALUES (:rol, :nombre, :correo, :hash, 1, :cambiar)'
);

$sentencia->execute([
    'rol'     => (int) $rolId,
    'nombre'  => $nombre,
    'correo'  => $correo,
    'hash'    => password_hash($clave, PASSWORD_DEFAULT),
    // Una contraseña generada por el script la ha visto la consola y puede
    // quedar en un log: se marca para cambio obligatorio. Una elegida por la
    // persona no la ha visto nadie más.
    'cambiar' => $generar ? 1 : 0,
]);

$panel = rtrim((string) ($config['url']['panel'] ?? ''), '/');

fwrite(STDOUT, "\nAdministrador creado.\n");
fwrite(STDOUT, "  Correo : {$correo}\n");

if ($generar) {
    fwrite(STDOUT, "  Clave  : {$clave}\n");
    fwrite(STDOUT, "\n  Esta contraseña es de un solo uso: el panel pedirá cambiarla\n");
    fwrite(STDOUT, "  al entrar. No se volverá a mostrar.\n");
}

fwrite(STDOUT, "  Entrar : {$panel}/login\n\n");


/** Contraseña legible de teclear: sin caracteres que se confundan entre sí. */
function generarClave(int $largo): string
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $clave = '';

    for ($i = 0; $i < $largo; $i++) {
        $clave .= $abc[random_int(0, strlen($abc) - 1)];
    }

    return $clave;
}


/**
 * Lee sin mostrar lo que se teclea. En Windows no existe `stty`, así que se
 * recurre a un pequeño script de PowerShell. Si tampoco estuviera disponible,
 * se lee en claro y se avisa: es preferible a no poder crear la cuenta.
 */
function leerOculto(): string
{
    if (DIRECTORY_SEPARATOR === '\\') {
        $comando = 'powershell -NoProfile -Command '
                 . '"$c = [Console]::ReadKey($true); '
                 . '$s = \'\'; '
                 . 'while ($c.Key -ne \'Enter\') { '
                 . '  if ($c.Key -eq \'Backspace\') { if ($s.Length -gt 0) { $s = $s.Substring(0, $s.Length - 1) } } '
                 . '  else { $s += $c.KeyChar } '
                 . '  $c = [Console]::ReadKey($true) '
                 . '} '
                 . 'Write-Output $s"';

        $valor = @shell_exec($comando);

        if (is_string($valor)) {
            return rtrim($valor, "\r\n");
        }
    } else {
        @shell_exec('stty -echo');
        $valor = fgets(STDIN);
        @shell_exec('stty echo');

        if (is_string($valor)) {
            return rtrim($valor, "\r\n");
        }
    }

    fwrite(STDOUT, "\n[aviso] No se pudo ocultar la escritura; la contraseña será visible.\n");

    return rtrim((string) fgets(STDIN), "\r\n");
}
