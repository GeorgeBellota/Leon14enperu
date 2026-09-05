<?php
/**
 * Auditoria — el registro de quién hizo qué.
 *
 * Existe por dos motivos, uno práctico y otro legal:
 *  · práctico: cuando una inscripción aparece cambiada, hay que poder decir
 *    quién la cambió y cuándo;
 *  · legal: el acceso a datos personales sensibles debe quedar trazado. Ver el
 *    DNI completo de un voluntario es una acción registrable, no una consulta
 *    cualquiera.
 *
 * Nunca guarda el valor de un dato sensible en `detalle`: eso duplicaría en
 * claro justo lo que la tabla de voluntarios cifra.
 */

declare(strict_types=1);

namespace Intranet\Core;

use Throwable;

final class Auditoria
{
    /** @param array<string, mixed> $detalle */
    public static function registrar(
        Contenedor $c,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        array $detalle = []
    ): void {
        try {
            $c->bd()->insertar('auditoria', [
                'usuario_id' => $c->auth()->id(),
                'accion'     => mb_substr($accion, 0, 64),
                'entidad'    => mb_substr($entidad, 0, 64),
                'entidad_id' => $entidadId,
                'detalle'    => $detalle === [] ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE),
                'ip'         => $c->peticion()->ipBinaria(),
            ]);
        } catch (Throwable $e) {
            // Que falle la auditoría no puede tumbar la operación que se estaba
            // haciendo. Se registra en el log del servidor y se sigue.
            error_log('[auditoria] ' . $e->getMessage());
        }
    }

    /**
     * Acceso a un dato sensible. Método aparte para que sea imposible
     * confundirlo con un registro normal al leer el código.
     */
    public static function accesoSensible(Contenedor $c, string $entidad, int $entidadId, string $campo): void
    {
        self::registrar($c, 'ver_sensible', $entidad, $entidadId, ['campo' => $campo]);
    }
}
