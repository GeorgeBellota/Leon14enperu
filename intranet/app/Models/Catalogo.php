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

    /**
     * Las jurisdicciones con su cupo: cuántas plazas hay, cuántas van y si
     * queda sitio.
     *
     * ── Qué cuenta como plaza ocupada ────────────────────────────────────
     *
     * TODAS las inscripciones vivas, sea cual sea su estado —«nuevo»,
     * «en_validacion», «validado», «acreditado», «rechazado» y «baja»—.
     * Decisión de la Conferencia Episcopal: la plaza se ocupa al inscribirse
     * y un rechazo posterior no la devuelve.
     *
     * Lo único fuera de la cuenta es lo borrado, que es otra cosa: ese
     * registro ya no existe a efectos del sistema.
     *
     * ── Sobre el coste ───────────────────────────────────────────────────
     *
     * Una subconsulta por jurisdicción, cinco en total, resueltas por el
     * índice `ix_voluntarios_cupo` sin tocar la tabla. Con 36 000 filas
     * sigue siendo una lectura de índice.
     *
     * @return array<int, array<string, mixed>> cada una con `limite`,
     *         `inscritos`, `completa` y `quedan`
     */
    public function jurisdiccionesConCupo(bool $soloActivas = true): array
    {
        $filtro = $soloActivas ? 'WHERE j.activo = 1' : '';

        $filas = $this->bd()->filas(
            "SELECT j.*,
                    (SELECT COUNT(*) FROM voluntarios v
                      WHERE v.jurisdiccion_id = j.id AND v.borrado_en IS NULL) AS inscritos
               FROM jurisdicciones j
               {$filtro}
              ORDER BY j.orden, j.nombre"
        );

        foreach ($filas as &$j) {
            /* `?? null` y no `$j['limite']` a secas: si los archivos suben antes
               que la migración, la columna todavía no existe y esto se llama en
               cada visita al formulario. Sin el `??`, cinco avisos por visita en
               el registro. Sin columna no hay tope, que es el comportamiento
               correcto mientras tanto. */
            $limite    = ($j['limite'] ?? null) === null ? null : (int) $j['limite'];
            $inscritos = (int) $j['inscritos'];

            $j['limite']    = $limite;
            $j['inscritos'] = $inscritos;
            /* Sin tope nunca se llena. Con tope, se compara con «>=» y no con
               «==»: si alguien baja el cupo por debajo de lo ya inscrito, la
               jurisdicción queda cerrada en vez de seguir abierta por no dar
               exactamente en el número. */
            $j['completa']  = $limite !== null && $inscritos >= $limite;
            $j['quedan']    = $limite === null ? null : max(0, $limite - $inscritos);
        }

        return $filas;
    }

    /**
     * ¿Admite esta jurisdicción una inscripción más?
     *
     * Se llama al GUARDAR, no sólo al pintar el formulario. El «disabled» del
     * desplegable es una pista visual: no impide que alguien envíe el id a
     * mano, y sobre todo no cubre el caso que pasa solo —abrir el formulario
     * con tres plazas libres, tardar seis minutos en rellenarlo y enviarlo
     * cuando ya no queda ninguna—.
     */
    public function jurisdiccionAdmite(int $id): bool
    {
        /* `j.*` y no `j.limite`: si los archivos suben antes que la migración,
           nombrar una columna que aún no existe es un ERROR de SQL, no un
           aviso, y esto se llama al guardar una inscripción. Con `j.*` la
           consulta sigue siendo válida y el `?? null` de abajo la deja sin
           tope, que es lo correcto mientras la columna no esté. */
        $fila = $this->bd()->fila(
            'SELECT j.*,
                    (SELECT COUNT(*) FROM voluntarios v
                      WHERE v.jurisdiccion_id = j.id AND v.borrado_en IS NULL) AS inscritos
               FROM jurisdicciones j
              WHERE j.id = :id AND j.activo = 1
              LIMIT 1',
            ['id' => $id]
        );

        if ($fila === null) {
            return false;
        }

        $limite = $fila['limite'] ?? null;

        return $limite === null || (int) $fila['inscritos'] < (int) $limite;
    }

    /** Guarda el tope de una jurisdicción. Null = sin tope. */
    public function guardarLimite(int $id, ?int $limite): int
    {
        return $this->bd()->actualizar(
            'jurisdicciones',
            ['limite' => $limite === null || $limite < 0 ? null : $limite],
            'id = :id',
            ['id' => $id]
        );
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
