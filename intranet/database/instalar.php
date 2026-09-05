<?php
/**
 * ============================================================================
 *  INSTALADOR · deja la base lista para trabajar, en un comando
 * ============================================================================
 *
 *  Uso:  php database/instalar.php
 *        php database/instalar.php --forzar    borra lo que haya y reinstala
 *
 *  Carga, en orden, los cinco archivos de database/instalacion/:
 *
 *      01-estructura.sql          las 23 tablas, tal como están en producción
 *      02-catalogos.sql           roles, permisos, jurisdicciones, servicios,
 *                                 y el ubigeo completo del Perú
 *      03-contenido.sql           las 24 páginas del sitio con su contenido
 *      04-voluntarios-ejemplo.sql diez fichas inventadas
 *      05-migraciones.sql         marca las quince migraciones como aplicadas
 *
 *  Después hace falta un usuario para entrar al panel:
 *
 *      php database/crear-admin.php "Tu Nombre" tu@correo.pe
 *
 *  ── Esto NO se ejecuta contra producción ───────────────────────────────────
 *
 *  El archivo 01 lleva DROP TABLE delante de cada tabla. En producción hay
 *  35 000 inscripciones. Por eso el script se niega a arrancar si encuentra
 *  datos y no se le pasa --forzar, y vuelve a negarse si el entorno no es
 *  «desarrollo».
 *
 *  ── Versiones ──────────────────────────────────────────────────────────────
 *
 *      Producción    MariaDB 11.8.6  ·  PHP 8.3  ·  Nginx
 *      Desarrollo    MariaDB 10.4    ·  PHP 8.1  ·  Apache
 *      Mínimo        MariaDB 10.4    ·  PHP 8.0
 *
 *  MariaDB, no MySQL: el proyecto usa «ADD COLUMN IF NOT EXISTS».
 * ============================================================================
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script sólo se ejecuta por línea de comandos.\n");
}

$raiz = dirname(__DIR__);

require $raiz . '/app/Core/Autoloader.php';
\Intranet\Core\Autoloader::registrar($raiz . '/app');

$forzar = in_array('--forzar', $argv, true);

/** Un renglón de la salida. */
$decir = static function (string $texto, string $marca = ' '): void {
    printf("  %s %s\n", $marca, $texto);
};

echo "\n  INSTALACIÓN DE LA BASE · leon14enperu.com\n";
echo "  " . str_repeat('─', 62) . "\n\n";

/* ── 1 · Lo que hace falta antes de empezar ─────────────────────────────── */
$faltan = [];

if (PHP_VERSION_ID < 80000) {
    $faltan[] = 'PHP 8.0 o superior (tienes ' . PHP_VERSION . ')';
}

foreach (['pdo_mysql', 'mbstring', 'gd', 'openssl', 'dom', 'json', 'iconv'] as $ext) {
    if (!extension_loaded($ext)) {
        $faltan[] = "la extensión de PHP «{$ext}»";
    }
}

$configLocal = $raiz . '/config/config.local.php';

if (!is_file($configLocal)) {
    $faltan[] = "config/config.local.php — cópialo de config.local.example.php";
}

if ($faltan !== []) {
    echo "  No se puede instalar. Falta:\n\n";
    foreach ($faltan as $f) { $decir($f, '·'); }
    echo "\n";
    exit(1);
}

$config = require $raiz . '/config/config.php';
$bd     = $config['bd'];

$decir('PHP ' . PHP_VERSION . ' y las siete extensiones', 'ok');

/* ── 2 · La conexión ────────────────────────────────────────────────────── */
try {
    $pdo = new PDO(
        "mysql:host={$bd['host']};port={$bd['puerto']};dbname={$bd['base']};charset={$bd['charset']}",
        $bd['usuario'],
        $bd['clave'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    echo "\n  No se pudo conectar a la base «{$bd['base']}».\n\n";
    $decir($e->getMessage(), '·');
    echo "\n  Créala antes:\n\n";
    echo "      CREATE DATABASE {$bd['base']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";
    exit(1);
}

/* MariaDB y no MySQL. No es un capricho: tres migraciones del proyecto usan
   «ADD COLUMN IF NOT EXISTS», que MySQL rechaza con error de sintaxis. */
$version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

if (!str_contains(strtolower($version), 'mariadb')) {
    echo "\n  Estás en MySQL ({$version}), y el proyecto necesita MariaDB.\n\n";
    $decir('Tres migraciones usan «ADD COLUMN IF NOT EXISTS», que MySQL no acepta.', '·');
    $decir('En Laragon: Menú → MySQL → Version → elige una MariaDB.', '·');
    echo "\n";
    exit(1);
}

$decir("Conectado a {$bd['base']} · {$version}", 'ok');

/* ── 3 · ¿Hay algo dentro? ──────────────────────────────────────────────── */
$entorno = (string) ($config['app']['entorno'] ?? 'produccion');

if ($entorno !== 'desarrollo' && !$forzar) {
    echo "\n  El entorno de config.local.php dice «{$entorno}», no «desarrollo».\n";
    echo "  Este script BORRA las tablas. No se ejecuta aquí.\n\n";
    exit(1);
}

$hay = 0;

try {
    $hay = (int) $pdo->query('SELECT COUNT(*) FROM voluntarios')->fetchColumn();
} catch (PDOException $e) {
    // La tabla no existe: la base está vacía, que es lo esperado.
}

if ($hay > 0 && !$forzar) {
    echo "\n  La base ya tiene {$hay} inscripciones.\n\n";
    $decir('Si de verdad quieres empezar de cero:  php database/instalar.php --forzar', '·');
    $decir('Eso las BORRA todas. No hay vuelta atrás.', '·');
    echo "\n";
    exit(1);
}

if ($hay > 0) {
    $decir("Se van a borrar {$hay} inscripciones (--forzar)", '⚠');
}

/* ── 4 · Los cinco archivos ─────────────────────────────────────────────── */
$archivos = [
    '01-estructura.sql'          => 'las 23 tablas',
    '02-catalogos.sql'           => 'roles, permisos, jurisdicciones, servicios y el ubigeo',
    '03-contenido.sql'           => 'las 24 páginas con su contenido',
    '04-voluntarios-ejemplo.sql' => 'diez fichas de ejemplo',
    '05-migraciones.sql'         => 'el registro de migraciones',
];

echo "\n";

/* Las claves ajenas se apagan mientras dura la carga: los archivos van en
   orden lógico, no en orden de dependencias, y una tabla puede referirse a
   otra que todavía no existe. */
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

foreach ($archivos as $archivo => $queEs) {
    $ruta = __DIR__ . '/instalacion/' . $archivo;

    if (!is_file($ruta)) {
        echo "\n  Falta {$ruta}\n\n";
        exit(1);
    }

    $sql = file_get_contents($ruta);

    if ($sql === false) {
        echo "\n  No se pudo leer {$ruta}\n\n";
        exit(1);
    }

    $inicio = microtime(true);

    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "\n  Falló {$archivo}:\n\n";
        $decir($e->getMessage(), '·');
        echo "\n";
        exit(1);
    }

    printf("   ok  %-28s %s · %.1fs\n", $archivo, $queEs, microtime(true) - $inicio);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

/* ── 5 · Recuento ───────────────────────────────────────────────────────── */
$cuenta = static fn (string $t): int => (int) $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();

echo "\n  " . str_repeat('─', 62) . "\n\n";

foreach ([
    'páginas'        => 'paginas',
    'secciones'      => 'secciones',
    'fichas'         => 'bloques',
    'ajustes'        => 'ajustes',
    'distritos'      => 'ubigeo_distrito',
    'servicios'      => 'servicios',
    'voluntarios'    => 'voluntarios',
] as $rotulo => $tabla) {
    printf("   %-14s %6s\n", $rotulo, number_format($cuenta($tabla)));
}

$usuarios = $cuenta('usuarios');

echo "\n";

if ($usuarios === 0) {
    echo "  Falta un usuario para entrar al panel:\n\n";
    echo "      php database/crear-admin.php \"Tu Nombre\" tu@correo.pe\n\n";
} else {
    $decir("Ya hay {$usuarios} usuario(s) del panel.", 'ok');
    echo "\n";
}

$sitio = rtrim((string) ($config['url']['sitio'] ?? ''), '/');
$panel = rtrim((string) ($config['url']['panel'] ?? ''), '/');

echo "  La web:    {$sitio}/\n";
echo "  El panel:  {$panel}/\n\n";
