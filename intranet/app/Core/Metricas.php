<?php

declare(strict_types=1);

namespace Intranet\Core;

/**
 * Lee los registros que deja el Medidor y los convierte en cifras.
 *
 * ── Por qué lee los archivos y no una tabla ───────────────────────────────
 *
 * Porque el registro se escribe cuando el servidor está peor: doscientas mil
 * visitas serían doscientos mil INSERT en la misma base que la web intenta
 * leer. Escribir a un archivo cuesta 0,28 ms y no toca la base. Lo caro
 * —sumar— se hace aquí, cuando alguien abre el tablero, que es una vez cada
 * mucho y nunca en mitad de la tormenta.
 *
 * ── Los percentiles van por cajones ───────────────────────────────────────
 *
 * Para dar el p95 exacto habría que guardar en memoria los tiempos de todas
 * las visitas: un día de doscientas mil son unos 16 MB, y una semana no cabe.
 * En su lugar se cuenta cuántas caen en cada tramo —hasta 10 ms, hasta 25,
 * hasta 50…— y el percentil se deduce del tramo. Es aproximado, y se dice: lo
 * que importa de un p95 es si vale 200 ms o 4 segundos, no si son 212 o 219.
 *
 * ── Por qué el p95 y no la media ──────────────────────────────────────────
 *
 * Porque la media miente cuando algo va mal. Si de cada veinte visitas
 * diecinueve tardan 50 ms y una tarda ocho segundos, la media dice 450 ms y
 * suena bien; el p95 dice ocho segundos, que es lo que se está llevando a los
 * visitantes.
 */
final class Metricas
{
    /** Los tramos en milisegundos. El último recoge todo lo que se pase. */
    private const TRAMOS = [10, 25, 50, 100, 200, 400, 800, 1600, 3200, 6400, 12800];

    public function __construct(private string $carpeta)
    {
        $this->carpeta = rtrim($carpeta, '/\\');
    }

    /**
     * Los días de los que hay registro, del más reciente al más antiguo.
     *
     * @return array<int, string>
     */
    public function dias(): array
    {
        $dias = [];

        foreach (glob($this->carpeta . '/????-??-??.log') ?: [] as $f) {
            $dias[] = basename($f, '.log');
        }

        rsort($dias);

        return $dias;
    }

    /**
     * Qué pasó entre dos fechas.
     *
     * @return array<string, mixed>
     */
    public function resumen(string $desde, string $hasta): array
    {
        $total = [
            'visitas' => 0, 'bots' => 0, 'personas' => 0,
            'ms' => 0.0, 'msBase' => 0.0, 'consultas' => 0,
            'errores' => 0, 'tramos' => array_fill(0, count(self::TRAMOS) + 1, 0),
        ];

        $rutas  = [];
        $horas  = [];
        $codigos = [];

        foreach ($this->archivos($desde, $hasta) as $archivo) {
            $gestor = @fopen($archivo, 'rb');

            if ($gestor === false) {
                continue;
            }

            while (($linea = fgets($gestor)) !== false) {
                $c = explode("\t", rtrim($linea, "\r\n"));

                // Una línea a medio escribir —el servidor se cayó justo ahí—
                // se salta en vez de envenenar la suma.
                if (count($c) < 10) {
                    continue;
                }

                [$cuando, $ruta, , $origen, $codigo, $quien, $ms, $msBase, $consultas] = $c;

                $ms        = (float) $ms;
                $msBase    = (float) $msBase;
                $consultas = (int) $consultas;
                $codigo    = (int) $codigo;

                $total['visitas']++;
                $total[$quien === 'bot' ? 'bots' : 'personas']++;
                $total['ms']        += $ms;
                $total['msBase']    += $msBase;
                $total['consultas'] += $consultas;

                if ($codigo >= 500) {
                    $total['errores']++;
                }

                $total['tramos'][$this->tramo($ms)]++;

                $clave = $origen . ' ' . $ruta;

                if (!isset($rutas[$clave])) {
                    $rutas[$clave] = [
                        'ruta' => $ruta, 'origen' => $origen, 'visitas' => 0,
                        'ms' => 0.0, 'msBase' => 0.0, 'consultas' => 0,
                        'errores' => 0, 'tramos' => array_fill(0, count(self::TRAMOS) + 1, 0),
                    ];
                }

                $rutas[$clave]['visitas']++;
                $rutas[$clave]['ms']        += $ms;
                $rutas[$clave]['msBase']    += $msBase;
                $rutas[$clave]['consultas'] += $consultas;
                $rutas[$clave]['tramos'][$this->tramo($ms)]++;

                if ($codigo >= 500) {
                    $rutas[$clave]['errores']++;
                }

                $hora = substr($cuando, 0, 13); // hasta la hora
                $horas[$hora] = ($horas[$hora] ?? 0) + 1;

                $codigos[$codigo] = ($codigos[$codigo] ?? 0) + 1;

                unset($c);
            }

            fclose($gestor);
        }

        // De más visitado a menos: es el orden en que se mira.
        uasort($rutas, static fn (array $a, array $b): int => $b['visitas'] <=> $a['visitas']);

        foreach ($rutas as $k => $r) {
            $rutas[$k]['msMedia'] = $r['visitas'] > 0 ? $r['ms'] / $r['visitas'] : 0.0;
            $rutas[$k]['p95']     = $this->percentil($r['tramos'], 95);
        }

        ksort($horas);
        krsort($codigos);

        return [
            'total'   => $total + [
                'msMedia' => $total['visitas'] > 0 ? $total['ms'] / $total['visitas'] : 0.0,
                'p50'     => $this->percentil($total['tramos'], 50),
                'p95'     => $this->percentil($total['tramos'], 95),
                'p99'     => $this->percentil($total['tramos'], 99),
            ],
            'rutas'   => $rutas,
            'horas'   => $horas,
            'codigos' => $codigos,
        ];
    }

    /**
     * Cuánto se estuvo mirando cada página.
     *
     * @return array<string, array<string, mixed>>
     */
    public function permanencia(string $desde, string $hasta): array
    {
        $rutas = [];

        foreach ($this->archivos($desde, $hasta, 'permanencia-') as $archivo) {
            $gestor = @fopen($archivo, 'rb');

            if ($gestor === false) {
                continue;
            }

            while (($linea = fgets($gestor)) !== false) {
                $c = explode("\t", rtrim($linea, "\r\n"));

                if (count($c) < 3) {
                    continue;
                }

                $ruta     = $c[1];
                $segundos = (int) $c[2];
                $sigue    = ($c[3] ?? '0') === '1';

                if (!isset($rutas[$ruta])) {
                    $rutas[$ruta] = ['ruta' => $ruta, 'lecturas' => 0, 'segundos' => 0];
                }

                $rutas[$ruta]['segundos'] += $segundos;

                /* La continuación suma tiempo pero no cuenta otra lectura:
                   quien cambió de pestaña y volvió es una persona, no dos. */
                if (!$sigue) {
                    $rutas[$ruta]['lecturas']++;
                }
            }

            fclose($gestor);
        }

        foreach ($rutas as $k => $r) {
            $rutas[$k]['media'] = $r['lecturas'] > 0 ? $r['segundos'] / $r['lecturas'] : 0;
        }

        uasort($rutas, static fn (array $a, array $b): int => $b['lecturas'] <=> $a['lecturas']);

        return $rutas;
    }

    /**
     * Los archivos de un rango. Se listan por nombre porque el nombre ES la
     * fecha: así no hay que abrir ninguno para saber si entra.
     *
     * @return array<int, string>
     */
    private function archivos(string $desde, string $hasta, string $prefijo = ''): array
    {
        $salen = [];

        foreach (glob($this->carpeta . '/' . $prefijo . '????-??-??.log') ?: [] as $f) {
            $dia = basename($f, '.log');

            if ($prefijo !== '') {
                $dia = substr($dia, strlen($prefijo));
            }

            if ($dia >= $desde && $dia <= $hasta) {
                $salen[] = $f;
            }
        }

        sort($salen);

        return $salen;
    }

    /** En qué tramo cae un tiempo. */
    private function tramo(float $ms): int
    {
        foreach (self::TRAMOS as $i => $tope) {
            if ($ms <= $tope) {
                return $i;
            }
        }

        return count(self::TRAMOS);
    }

    /**
     * El percentil, deducido del tramo donde cae.
     *
     * Devuelve el techo del tramo, así que es un «no más de»: si dice 200 ms,
     * el 95 % de las visitas tardó 200 ms o menos. Se queda del lado seguro.
     *
     * @param array<int, int> $tramos
     */
    private function percentil(array $tramos, int $cual): float
    {
        $total = array_sum($tramos);

        if ($total === 0) {
            return 0.0;
        }

        $objetivo = $total * $cual / 100;
        $van = 0;

        foreach ($tramos as $i => $cuantas) {
            $van += $cuantas;

            if ($van >= $objetivo) {
                return (float) (self::TRAMOS[$i] ?? self::TRAMOS[count(self::TRAMOS) - 1] * 2);
            }
        }

        return (float) (self::TRAMOS[count(self::TRAMOS) - 1] * 2);
    }
}
