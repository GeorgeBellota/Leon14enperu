<?php
/**
 * PanelController — el escritorio.
 *
 * En Fase 1 muestra el estado real de la instalación y las cifras de
 * voluntariado. Las consultas están protegidas contra la primera ejecución:
 * si todavía no hay inscripciones, la pantalla no puede reventar.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Controller;
use Intranet\Core\Request;
use Throwable;

final class PanelController extends Controller
{
    public function inicio(Request $peticion): void
    {
        /* ── El día que se está mirando ──────────────────────────────────
           Por defecto, hoy. Con ?dia=AAAA-MM-DD se mira otro.

           La fecha se comprueba componiéndola de nuevo y viendo si sale la
           misma: así entra sólo un día real, y de paso nunca llega a la
           consulta nada que no sean diez caracteres con forma de fecha. Un
           «2026-02-31» se cae aquí, no en MySQL. */
        $dia = $peticion->get('dia', '');
        $dia = is_string($dia) ? trim($dia) : '';

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $dia);

        if ($fecha === false || $fecha->format('Y-m-d') !== $dia) {
            $fecha = new \DateTimeImmutable('today');
        }

        $dia = $fecha->format('Y-m-d');
        $esHoy = $dia === (new \DateTimeImmutable('today'))->format('Y-m-d');

        $this->ver('panel/inicio', [
            'titulo'    => 'Escritorio',
            'resumen'   => $this->resumen(),
            'porEstado' => $this->porEstado(),
            'ultimos'   => $this->ultimos(),

            // ── Lo que se puede filtrar por día ──────────────────────────
            'dia'            => $dia,
            'esHoy'          => $esHoy,
            'diaAnterior'    => $fecha->modify('-1 day')->format('Y-m-d'),
            'diaSiguiente'   => $esHoy ? null : $fecha->modify('+1 day')->format('Y-m-d'),
            'delDia'         => $this->totalDelDia($dia),
            'porHora'        => $this->porHora($dia),
            'porDepartamento' => $this->porDepartamento($dia),
            'porJurisdiccion' => $this->porJurisdiccion($dia),
            'ultimos7'       => $this->ultimos7Dias(),
            'comunicados'    => $this->comunicados(),
        ]);
    }

    /**
     * Los comunicados que han tenido movimiento, para el escritorio.
     *
     * Incluye los retirados: ahí está la gracia del historial. Comparar el
     * aviso de la peregrinación con el de las inscripciones sólo se puede si
     * los dos siguen ahí con sus cifras.
     *
     * @return array<int, array<string, mixed>>
     */
    private function comunicados(): array
    {
        if (!$this->c->auth()->puede('comunicados.ver')) {
            return [];
        }

        try {
            return (new \Intranet\Models\Comunicado($this->c))->conCifras();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Cuántas inscripciones entraron en un día.
     *
     * `DATE(creado_en) = :d` y no un BETWEEN con horas: es más legible y en
     * una tabla de este tamaño la diferencia no se nota. Si algún día llega a
     * notarse, el arreglo es un índice sobre creado_en, no reescribir esto.
     */
    private function totalDelDia(string $dia): int
    {
        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return 0;
        }

        try {
            return (int) $this->c->bd()->valor(
                'SELECT COUNT(*) FROM voluntarios
                  WHERE borrado_en IS NULL AND DATE(creado_en) = :d',
                ['d' => $dia]
            );
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * El reparto por horas de un día: las 24, incluidas las que no tuvieron
     * ninguna.
     *
     * Las horas vacías importan tanto como las llenas. Si sólo se devolvieran
     * las que tienen inscripciones, un día con actividad a las 8 y a las 20
     * se dibujaría como dos barras juntas y parecería actividad continua. Con
     * las 24 se ve la forma real de la jornada, que es lo que sirve para
     * decidir cuándo publicar.
     *
     * @return array<int, int> hora (0-23) => cuántas
     */
    private function porHora(string $dia): array
    {
        $horas = array_fill(0, 24, 0);

        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return $horas;
        }

        try {
            $filas = $this->c->bd()->filas(
                'SELECT HOUR(creado_en) AS h, COUNT(*) AS n
                   FROM voluntarios
                  WHERE borrado_en IS NULL AND DATE(creado_en) = :d
                  GROUP BY HOUR(creado_en)',
                ['d' => $dia]
            );

            foreach ($filas as $f) {
                $horas[(int) $f['h']] = (int) $f['n'];
            }
        } catch (Throwable $e) {
            // Se devuelven las 24 horas a cero: el gráfico sale vacío, no roto.
        }

        return $horas;
    }

    /**
     * Inscripciones por departamento: las del día y el total acumulado.
     *
     * Se ordena por el ACUMULADO y no por las del día, para que la tabla no
     * cambie de orden cada vez que entra alguien. Una lista que se reordena
     * sola obliga a buscar de nuevo dónde estaba cada zona.
     *
     * El LEFT JOIN es necesario: hay inscripciones sin código de ubigeo —las
     * que se escribieron a mano cuando la lista no cargaba— y con un JOIN a
     * secas desaparecerían de la cuenta sin que nadie lo notara.
     *
     * @return array<int, array<string, mixed>>
     */
    private function porDepartamento(string $dia): array
    {
        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return [];
        }

        try {
            return $this->c->bd()->filas(
                "SELECT COALESCE(d.name, 'Sin especificar') AS nombre,
                        COUNT(*) AS total,
                        SUM(DATE(v.creado_en) = :d) AS del_dia
                   FROM voluntarios v
                   LEFT JOIN ubigeo_departamento d ON d.id = v.ubigeo_departamento_id
                  WHERE v.borrado_en IS NULL
                  GROUP BY nombre
                  ORDER BY total DESC, nombre",
                ['d' => $dia]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Lo mismo por jurisdicción eclesiástica.
     *
     * @return array<int, array<string, mixed>>
     */
    private function porJurisdiccion(string $dia): array
    {
        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return [];
        }

        try {
            return $this->c->bd()->filas(
                "SELECT COALESCE(j.nombre, 'Sin especificar') AS nombre,
                        COUNT(*) AS total,
                        SUM(DATE(v.creado_en) = :d) AS del_dia
                   FROM voluntarios v
                   LEFT JOIN jurisdicciones j ON j.id = v.jurisdiccion_id
                  WHERE v.borrado_en IS NULL
                  GROUP BY nombre
                  ORDER BY total DESC, nombre",
                ['d' => $dia]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Los últimos siete días, uno a uno y en orden.
     *
     * Se construyen los siete en PHP y luego se rellenan con lo que diga la
     * base, por lo mismo que las horas: un día sin inscripciones tiene que
     * aparecer con su cero. Si se dibujara sólo lo que devuelve la consulta,
     * un fin de semana flojo se volvería invisible en lugar de verse como lo
     * que es.
     *
     * @return array<int, array{dia: string, etiqueta: string, total: int, esHoy: bool}>
     */
    private function ultimos7Dias(): array
    {
        $dias = [];
        $hoy = new \DateTimeImmutable('today');
        $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        for ($i = 6; $i >= 0; $i--) {
            $f = $hoy->modify("-{$i} day");
            $clave = $f->format('Y-m-d');

            $dias[$clave] = [
                'dia'      => $clave,
                'etiqueta' => $f->format('j') . ' ' . $meses[(int) $f->format('n')],
                'total'    => 0,
                'esHoy'    => $i === 0,
            ];
        }

        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return array_values($dias);
        }

        try {
            $filas = $this->c->bd()->filas(
                'SELECT DATE(creado_en) AS d, COUNT(*) AS n
                   FROM voluntarios
                  WHERE borrado_en IS NULL
                    AND creado_en >= (CURDATE() - INTERVAL 6 DAY)
                  GROUP BY DATE(creado_en)'
            );

            foreach ($filas as $f) {
                $clave = (string) $f['d'];
                if (isset($dias[$clave])) {
                    $dias[$clave]['total'] = (int) $f['n'];
                }
            }
        } catch (Throwable $e) {
            // Siete días a cero antes que una pantalla rota.
        }

        return array_values($dias);
    }

    /** @return array<string, int> */
    private function resumen(): array
    {
        $bd = $this->c->bd();

        try {
            return [
                'total'   => (int) $bd->valor('SELECT COUNT(*) FROM voluntarios WHERE borrado_en IS NULL'),
                'hoy'     => (int) $bd->valor('SELECT COUNT(*) FROM voluntarios WHERE borrado_en IS NULL AND DATE(creado_en) = CURDATE()'),
                'semana'  => (int) $bd->valor('SELECT COUNT(*) FROM voluntarios WHERE borrado_en IS NULL AND creado_en > (NOW() - INTERVAL 7 DAY)'),
                'nuevos'  => (int) $bd->valor("SELECT COUNT(*) FROM voluntarios WHERE borrado_en IS NULL AND estado = 'nuevo'"),
            ];
        } catch (Throwable $e) {
            return ['total' => 0, 'hoy' => 0, 'semana' => 0, 'nuevos' => 0];
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function porEstado(): array
    {
        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return [];
        }

        try {
            return $this->c->bd()->filas(
                'SELECT estado, COUNT(*) AS total
                   FROM voluntarios
                  WHERE borrado_en IS NULL
                  GROUP BY estado
                  ORDER BY total DESC'
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Últimas inscripciones. Aquí NO se selecciona ni un campo cifrado: el
     * escritorio no es sitio para datos sensibles, ni siquiera enmascarados.
     *
     * @return array<int, array<string, mixed>>
     */
    private function ultimos(): array
    {
        if (!$this->c->auth()->puede('voluntarios.ver')) {
            return [];
        }

        try {
            return $this->c->bd()->filas(
                'SELECT v.id, v.codigo, v.nombres, v.estado, v.creado_en,
                        j.nombre AS jurisdiccion, s.nombre AS servicio
                   FROM voluntarios v
                   JOIN jurisdicciones j ON j.id = v.jurisdiccion_id
                   JOIN servicios s      ON s.id = v.servicio_id
                  WHERE v.borrado_en IS NULL
                  ORDER BY v.creado_en DESC
                  LIMIT 8'
            );
        } catch (Throwable $e) {
            return [];
        }
    }
}
