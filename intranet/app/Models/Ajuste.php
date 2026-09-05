<?php
/**
 * Ajuste — los pares clave/valor de alcance global.
 *
 * Lectura y escritura de la tabla `ajustes`: la fecha del viaje, si el
 * voluntariado está abierto, el modo mantenimiento, la versión del texto de
 * consentimiento.
 *
 * Los valores se cachean dentro de la petición: el modo mantenimiento se
 * consulta en cada página pública, y no tiene sentido ir a la base tres veces
 * por la misma clave.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Ajuste extends Model
{
    protected string $tabla = 'ajustes';
    protected string $llave = 'clave';

    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function leer(string $clave, ?string $porDefecto = null): ?string
    {
        $this->cargar();

        return $this->cache[$clave] ?? $porDefecto;
    }

    public function esBool(string $clave, bool $porDefecto = false): bool
    {
        $valor = $this->leer($clave);

        return $valor === null ? $porDefecto : in_array($valor, ['1', 'true', 'si'], true);
    }

    public function escribir(string $clave, ?string $valor): void
    {
        // ON DUPLICATE KEY y no un UPDATE a secas: así también sirve para una
        // clave que todavía no existe, sin comprobar antes si está.
        $this->bd()->consultar(
            'INSERT INTO ajustes (clave, valor) VALUES (:c, :v)
             ON DUPLICATE KEY UPDATE valor = :v2',
            ['c' => $clave, 'v' => $valor, 'v2' => $valor]
        );

        $this->cache = null;
    }

    /** @return array<int, array<string, mixed>> */
    public function todos(): array
    {
        return $this->bd()->filas('SELECT * FROM ajustes ORDER BY clave');
    }

    private function cargar(): void
    {
        if ($this->cache !== null) {
            return;
        }

        $this->cache = [];

        foreach ($this->bd()->filas('SELECT clave, valor FROM ajustes') as $fila) {
            $this->cache[(string) $fila['clave']] = $fila['valor'] === null ? null : (string) $fila['valor'];
        }
    }
}
