<?php
/**
 * ============================================================================
 *  Database — acceso a MySQL por PDO.
 * ============================================================================
 *
 *  Decisiones que no son negociables aquí dentro, y por qué:
 *
 *  · ATTR_EMULATE_PREPARES = false. Con la emulación activada (el valor por
 *    defecto de PDO con MySQL) es el propio PDO quien pega los parámetros en
 *    la consulta antes de enviarla. Funciona, pero deja de ser una sentencia
 *    preparada de verdad y aparecen agujeros conocidos con ciertos charsets.
 *    Desactivada, los parámetros viajan aparte y el motor nunca los interpreta
 *    como SQL. Es la línea que hace real la promesa de «sin inyección».
 *
 *  · ERRMODE_EXCEPTION. Un error de SQL debe romper, no devolver false y
 *    seguir como si nada.
 *
 *  · ATTR_STRINGIFY_FETCHES = false + emulación desactivada: los INT vuelven
 *    como int y los comparadores estrictos (===) funcionan.
 *
 *  · Conexión perezosa: no se abre hasta la primera consulta. Una petición que
 *    sólo sirve una página estática no toca la base.
 *
 *  Los métodos de conveniencia (fila, filas, valor, insertar…) existen para que
 *  ningún modelo tenga que escribir prepare/execute/fetch a mano y se olvide
 *  de los parámetros preparados justo el día que hay prisa.
 */

declare(strict_types=1);

namespace Intranet\Core;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?self $instancia = null;

    private ?PDO $pdo = null;

    private function __construct(private array $config)
    {
    }

    /** Singleton: una conexión por petición, no una por modelo. */
    public static function instancia(?array $config = null): self
    {
        if (self::$instancia === null) {
            if ($config === null) {
                throw new RuntimeException('Database::instancia() necesita la configuración la primera vez.');
            }
            self::$instancia = new self($config);
        }

        return self::$instancia;
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['puerto'],
            $this->config['base'],
            $this->config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $this->config['usuario'], $this->config['clave'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                // Sin esto, MySQL acepta en silencio un texto de 300 caracteres
                // en un VARCHAR(160) y lo recorta. En una tabla de personas,
                // truncar un apellido sin avisar es un error de datos.
                // La zona horaria va en el mismo comando de arranque, y no es
                // un detalle: es lo que hace que PHP y MySQL hablen del mismo
                // día.
                //
                // `creado_en` se rellena con CURRENT_TIMESTAMP, o sea con la
                // hora de MySQL, que por omisión es la del servidor. Los
                // contadores del panel, en cambio, calculan «hoy» con la zona
                // de la aplicación (America/Lima). Si el servidor no está en
                // Lima —y el hosting no lo está—, las dos cosas no coinciden:
                // las inscripciones de última hora de la tarde se contarían al
                // día siguiente, y el resumen diario saldría desplazado sin que
                // nada pareciera roto.
                //
                // Se manda el desfase en horas (-05:00) y no el nombre de la
                // zona porque los nombres exigen que el servidor tenga cargadas
                // las tablas de zonas horarias de MySQL, y en un hosting
                // compartido no suelen estarlo. El desfase lo entiende siempre.
                PDO::MYSQL_ATTR_INIT_COMMAND =>
                    "SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'"
                    . ", time_zone='" . self::desfaseHorario() . "'",
            ]);
        } catch (PDOException $e) {
            // El mensaje de PDO incluye usuario y host: no puede llegar al
            // navegador. Se registra completo y se lanza uno neutro.
            error_log('[BD] ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar con la base de datos.', 0, $e);
        }

        return $this->pdo;
    }

    /**
     * El desfase de la zona horaria de PHP, en la forma que entiende MySQL:
     * «-05:00».
     *
     * Se calcula en el momento y no se escribe a mano porque el desfase de una
     * zona puede cambiar —el horario de verano existe— y un «-05:00» fijo
     * estaría equivocado media parte del año en cuanto el sitio se usara desde
     * otra zona. Perú no cambia la hora, pero el código no tiene por qué dar
     * eso por sentado.
     */
    private static function desfaseHorario(): string
    {
        $segundos = (new DateTimeZone(date_default_timezone_get()))
            ->getOffset(new DateTimeImmutable('now'));

        return sprintf(
            '%s%02d:%02d',
            $segundos < 0 ? '-' : '+',
            intdiv(abs($segundos), 3600),
            intdiv(abs($segundos) % 3600, 60)
        );
    }

    /**
     * Ejecuta una consulta con parámetros. Es el único punto por el que pasa
     * todo el SQL de la aplicación.
     *
     * @param array<string|int, mixed> $params
     */
    public function consultar(string $sql, array $params = []): PDOStatement
    {
        $sentencia = $this->pdo()->prepare($sql);

        foreach ($params as $clave => $valor) {
            $nombre = is_int($clave) ? $clave + 1 : $clave;

            $tipo = match (true) {
                is_int($valor)  => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                is_null($valor) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };

            $sentencia->bindValue($nombre, $valor, $tipo);
        }

        $sentencia->execute();

        return $sentencia;
    }

    /** @return array<string, mixed>|null */
    public function fila(string $sql, array $params = []): ?array
    {
        $fila = $this->consultar($sql, $params)->fetch();

        return $fila === false ? null : $fila;
    }

    /** @return array<int, array<string, mixed>> */
    public function filas(string $sql, array $params = []): array
    {
        return $this->consultar($sql, $params)->fetchAll();
    }

    /** Primera columna de la primera fila. Para COUNT(*), EXISTS y similares. */
    public function valor(string $sql, array $params = []): mixed
    {
        $valor = $this->consultar($sql, $params)->fetchColumn();

        return $valor === false ? null : $valor;
    }

    /** @return array<int, mixed> */
    public function columna(string $sql, array $params = []): array
    {
        return $this->consultar($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * INSERT construido desde un array asociativo. Los nombres de columna se
     * escapan con acentos graves y nunca vienen de la petición: los fija el
     * modelo. Los valores van siempre como parámetros.
     *
     * @param array<string, mixed> $datos
     */
    public function insertar(string $tabla, array $datos): int
    {
        $columnas = array_keys($datos);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $tabla,
            implode(', ', array_map(static fn ($c) => "`{$c}`", $columnas)),
            implode(', ', array_map(static fn ($c) => ":{$c}", $columnas))
        );

        $this->consultar($sql, $datos);

        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * UPDATE por una condición WHERE con parámetros propios.
     *
     * @param array<string, mixed> $datos
     * @param array<string, mixed> $paramsWhere
     */
    public function actualizar(string $tabla, array $datos, string $where, array $paramsWhere = []): int
    {
        if ($datos === []) {
            return 0;
        }

        // Los campos del SET llevan prefijo para no chocar con un parámetro
        // del WHERE que se llame igual (`:id` en ambos sitios, por ejemplo).
        $asignaciones = [];
        $params       = $paramsWhere;

        foreach ($datos as $columna => $valor) {
            $asignaciones[]        = "`{$columna}` = :set_{$columna}";
            $params["set_{$columna}"] = $valor;
        }

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $tabla, implode(', ', $asignaciones), $where);

        return $this->consultar($sql, $params)->rowCount();
    }

    public function eliminar(string $tabla, string $where, array $params = []): int
    {
        return $this->consultar("DELETE FROM `{$tabla}` WHERE {$where}", $params)->rowCount();
    }

    /**
     * Transacción con cierre. Si el callback lanza, se deshace todo.
     * Recordatorio: en MySQL el DDL hace commit implícito, así que esto sólo
     * protege INSERT/UPDATE/DELETE.
     */
    public function transaccion(callable $trabajo): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $resultado = $trabajo($this);
            $pdo->commit();

            return $resultado;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Ni clonar ni deserializar un singleton de conexión. */
    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('No se puede deserializar la conexión.');
    }
}
