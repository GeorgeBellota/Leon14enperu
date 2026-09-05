<?php
/**
 * Catalogo — jurisdicciones y servicios.
 *
 * Es la fuente de los dos <select> del formulario público y, a la vez, de las
 * seis tarjetas de la sección «servicios». Una sola tabla para las dos cosas:
 * si estuvieran duplicadas, un día la tarjeta diría «Servicio de acogida» y la
 * opción del desplegable otra cosa distinta.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Model;

final class Catalogo extends Model
{
    /** @return array<int, array<string, mixed>> */
    public function jurisdicciones(bool $soloActivas = true): array
    {
        $filtro = $soloActivas ? 'WHERE activo = 1' : '';

        return $this->bd()->filas("SELECT * FROM jurisdicciones {$filtro} ORDER BY orden, nombre");
    }

    /** @return array<int, array<string, mixed>> */
    public function servicios(bool $soloActivos = true): array
    {
        $filtro = $soloActivos ? 'WHERE activo = 1' : '';

        return $this->bd()->filas("SELECT * FROM servicios {$filtro} ORDER BY orden, nombre");
    }

    public function jurisdiccionValida(int $id): bool
    {
        return $this->bd()->valor(
            'SELECT 1 FROM jurisdicciones WHERE id = :id AND activo = 1',
            ['id' => $id]
        ) !== null;
    }

    public function servicioValido(int $id): bool
    {
        return $this->bd()->valor(
            'SELECT 1 FROM servicios WHERE id = :id AND activo = 1',
            ['id' => $id]
        ) !== null;
    }

    /** Un ajuste suelto, con valor por defecto si no está definido. */
    public function ajuste(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = $this->bd()->valor('SELECT valor FROM ajustes WHERE clave = :c', ['c' => $clave]);

        return $valor === null ? $porDefecto : (string) $valor;
    }

    public function ajusteBool(string $clave, bool $porDefecto = false): bool
    {
        $valor = $this->ajuste($clave);

        return $valor === null ? $porDefecto : in_array($valor, ['1', 'true', 'si'], true);
    }
}
