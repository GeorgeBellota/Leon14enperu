<?php
/**
 * ============================================================================
 *  MIGRADOR. Lo más simple que funciona.
 * ============================================================================
 *
 *  Uso:  php database/migrate.php            aplica lo pendiente
 *        php database/migrate.php --estado   sólo informa, no toca nada
 *
 *  Una migración es un archivo en database/migrations/ con nombre
 *  `NNNN_descripcion.sql` o `NNNN_descripcion.php`. Se aplican en orden
 *  alfabético, que con el prefijo numérico es el orden cronológico.
 *
 *  Reglas de la casa:
 *   · Una migración aplicada NO se edita nunca. Se corrige con otra nueva.
 *   · Cada archivo debe poder ejecutarse dos veces sin romper nada
 *     (IF NOT EXISTS, ON DUPLICATE KEY, IF EXISTS al borrar).
 *   · Las .php reciben $pdo y deben devolver o lanzar; nada de echo.
 *
 *  No hay rollback a propósito. En una base con datos reales de personas, un
 *  `down()` automático es más peligroso que útil: se restaura de copia.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script sólo se ejecuta por línea de comandos.\n");
}

require __DIR__ . '/../app/Core/Autoloader.php';
\Intranet\Core\Autoloader::registrar(__DIR__ . '/../app');

$config    = require __DIR__ . '/../config/config.php';
$soloVer   = in_array('--estado', $argv, true);
$rutaMigra = __DIR__ . '/migrations';

// ── Conexión sin nombre de base: la primera vez todavía no existe ────────
$dsnSinBase = sprintf(
    'mysql:host=%s;port=%d;charset=%s',
    $config['bd']['host'],
    $config['bd']['puerto'],
    $config['bd']['charset']
);

try {
    $pdo = new PDO($dsnSinBase, $config['bd']['usuario'], $config['bd']['clave'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    salir("No se pudo conectar a MySQL: " . $e->getMessage(), 1);
}

$base = $config['bd']['base'];
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$base}`
            DEFAULT CHARACTER SET {$config['bd']['charset']}
            DEFAULT COLLATE {$config['bd']['cotejo']}");
$pdo->exec("USE `{$base}`");

$pdo->exec("CREATE TABLE IF NOT EXISTS `migraciones` (
              `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `archivo`     VARCHAR(190) NOT NULL,
              `aplicada_en` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_migraciones_archivo` (`archivo`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$config['bd']['charset']}");

// ── Qué hay y qué falta ─────────────────────────────────────────────────
$aplicadas = $pdo->query('SELECT `archivo` FROM `migraciones`')->fetchAll(PDO::FETCH_COLUMN);

$archivos = array_merge(
    glob($rutaMigra . '/*.sql') ?: [],
    glob($rutaMigra . '/*.php') ?: []
);
$archivos = array_map('basename', $archivos);
sort($archivos, SORT_STRING);

$pendientes = array_values(array_diff($archivos, $aplicadas));

linea("Base de datos : {$base}");
linea("Migraciones   : " . count($archivos) . " en total, " . count($aplicadas) . " aplicadas");

if ($soloVer) {
    foreach ($archivos as $a) {
        linea((in_array($a, $aplicadas, true) ? '  [x] ' : '  [ ] ') . $a);
    }
    exit(0);
}

if (!$pendientes) {
    linea('Nada pendiente. Todo al día.');
    exit(0);
}

// ── Aplicar ─────────────────────────────────────────────────────────────
// INSERT IGNORE y no INSERT a secas: schema.sql termina marcándose a sí mismo
// como aplicado —hace falta para quien lo importa a mano desde phpMyAdmin—, así
// que cuando lo ejecuta la migración 0001 el registro ya existe y un INSERT
// normal aborta la instalación entera después de haberla hecho bien.
$registrar = $pdo->prepare('INSERT IGNORE INTO `migraciones` (`archivo`) VALUES (:archivo)');

foreach ($pendientes as $archivo) {
    $ruta = $rutaMigra . '/' . $archivo;
    linea("→ {$archivo}");

    try {
        // DDL en MySQL no participa en transacciones: un CREATE TABLE hace
        // commit implícito. Envolver esto en beginTransaction daría una
        // sensación falsa de atomicidad, así que no se hace. Si una migración
        // falla a medias, el mensaje dice dónde y se corrige a mano.
        if (str_ends_with($archivo, '.php')) {
            $ejecutar = require $ruta;
            if (!is_callable($ejecutar)) {
                throw new RuntimeException('La migración .php debe devolver una función que reciba PDO.');
            }
            $ejecutar($pdo, $config);
        } else {
            $sql = file_get_contents($ruta);
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Archivo vacío o ilegible.');
            }
            $pdo->exec($sql);
        }

        $registrar->execute([':archivo' => $archivo]);
        linea("  ok");
    } catch (Throwable $e) {
        salir("  FALLÓ en {$archivo}\n  {$e->getMessage()}", 1);
    }
}

linea('Listo.');
exit(0);


function linea(string $texto): void
{
    fwrite(STDOUT, $texto . PHP_EOL);
}

function salir(string $texto, int $codigo): void
{
    fwrite(STDERR, $texto . PHP_EOL);
    exit($codigo);
}
