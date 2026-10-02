<?php

declare(strict_types=1);

namespace Intranet\Core;

/**
 * Qué costó atender cada visita.
 *
 * Una línea por petición: cuándo, qué ruta, con qué respuesta, cuántos
 * milisegundos, cuántos de ellos dentro de la base, cuántas consultas y
 * cuánta memoria. El tamaño de la respuesta no: eso lo apunta nginx, y PHP
 * no lo sabe de forma fiable cuando se llega al apagado. De ahí sale todo lo demás.
 *
 * ── Por qué a un fichero y no a la base ───────────────────────────────────
 *
 * Porque esto se va a usar justo cuando la base esté ahogada. Doscientas mil
 * visitas serían doscientos mil INSERT en la misma base que la web intenta
 * leer: la medición se convertiría en el cuello de botella y acabaríamos
 * midiendo nuestro propio instrumento. Un fichero se abre, se escribe una
 * línea y se cierra; medido, cuesta 0,28 ms sobre una petición de 500. El
 * resumen por días se calcula después, en frío.
 *
 * ── Por qué sin bloquear el fichero ───────────────────────────────────────
 *
 * Un fichero abierto en modo «añadir» escribe de forma atómica mientras la
 * línea sea corta; las de aquí rondan los noventa bytes. Pedir un cerrojo por
 * cada visita pondría a doscientos mil procesos a hacer cola por él, que es
 * exactamente lo que no queremos tener bajo carga.
 *
 * ── Qué NO guarda ─────────────────────────────────────────────────────────
 *
 * Ni la IP, ni el navegador completo, ni nada que permita reconocer a una
 * persona entre dos visitas. Sólo si parecía un robot, que es lo que hace
 * falta para separar una prueba de carga del tráfico de verdad. Esto es
 * registro de funcionamiento, no analítica.
 *
 * ── Qué pasa si falla ─────────────────────────────────────────────────────
 *
 * Nada. Todo va dentro de un try/catch que se traga cualquier cosa: una web
 * no puede caerse porque el disco esté lleno y no se pueda apuntar una línea.
 */
final class Medidor
{
    /** Instante de arranque, en nanosegundos. */
    private static int $arranque = 0;

    private static string $carpeta = '';

    private static string $origen = 'web';

    private static bool $enMarcha = false;

    /** Días que se guardan los registros antes de borrarse solos. */
    private static int $dias = 30;

    /**
     * Empieza a medir. Se llama lo antes posible en el punto de entrada.
     *
     * @param string $carpeta dónde dejar los registros
     * @param string $origen  «web», «panel» o «baliza»
     */
    public static function arrancar(string $carpeta, string $origen = 'web', int $dias = 30): void
    {
        if (self::$enMarcha) {
            return;
        }

        self::$arranque = hrtime(true);
        self::$carpeta  = rtrim($carpeta, '/\\');
        self::$origen   = $origen;
        self::$dias     = max(1, $dias);
        self::$enMarcha = true;

        /* Al apagar, pase lo que pase: así se apunta también la petición que
           termina en un error o en un exit() a media página, que son
           justamente las que interesan cuando algo va mal. */
        register_shutdown_function([self::class, 'cerrar']);
    }

    /** Escribe la línea. La llama el apagado; llamarla a mano no hace daño. */
    public static function cerrar(): void
    {
        if (!self::$enMarcha) {
            return;
        }

        self::$enMarcha = false;

        try {
            $ms    = (hrtime(true) - self::$arranque) / 1000000;
            $gasto = Database::gasto();

            $linea = implode("\t", [
                gmdate('Y-m-d\TH:i:s'),
                self::ruta(),
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                self::$origen,
                http_response_code() ?: 200,
                self::esRobot() ? 'bot' : 'pers',
                number_format($ms, 1, '.', ''),
                number_format($gasto['ms'], 1, '.', ''),
                (int) $gasto['consultas'],
                (int) round(memory_get_peak_usage(true) / 1024),
            ]) . "\n";

            $fichero = self::$carpeta . '/' . gmdate('Y-m-d') . '.log';

            if (!is_dir(self::$carpeta)) {
                @mkdir(self::$carpeta, 0775, true);
            }

            @file_put_contents($fichero, $linea, FILE_APPEND);

            /* La poda va DESPUÉS de escribir, nunca antes: si falla —permisos,
               un archivo bloqueado— la medición ya está a salvo.

               Una vez de cada mil. Con 20 000 visitas al día son veinte
               revisiones diarias, de sobra para que no se acumule nada, y en
               las otras novecientas noventa y nueve no cuesta ni una llamada
               al disco. Es lo mismo que hace PHP con sus propias sesiones. */
            if (random_int(1, 1000) === 1) {
                self::podar();
            }
        } catch (\Throwable $e) {
            // Una web no se cae porque no se pueda apuntar una línea.
        }
    }

    /**
     * Apunta cuánto estuvo alguien mirando una página.
     *
     * Lo manda el navegador al salir, y llega con la URL y los segundos, nada
     * más: sin identificador, sin cookie y sin forma de unir dos visitas de
     * la misma persona.
     *
     * La marca de «continuación» es la única excepción, y no identifica nada:
     * dice «esto sigue a una lectura que ya se contó», para que quien cambia
     * de pestaña y vuelve sume su tiempo sin aparecer como dos visitas. Ni
     * siquiera dice a cuál sigue. Se guarda aparte porque no es una petición que el
     * servidor haya atendido, es una medida que viene de fuera.
     */
    public static function apuntarPermanencia(
        string $carpeta,
        string $ruta,
        int $segundos,
        bool $continuacion = false
    ): void {
        try {
            $carpeta = rtrim($carpeta, '/\\');

            if (!is_dir($carpeta)) {
                @mkdir($carpeta, 0775, true);
            }

            @file_put_contents(
                $carpeta . '/permanencia-' . gmdate('Y-m-d') . '.log',
                implode("\t", [
                    gmdate('Y-m-d\TH:i:s'),
                    self::limpiar($ruta),
                    $segundos,
                    // 1 = sigue una lectura ya contada: suma el tiempo pero
                    // no cuenta otra visita.
                    $continuacion ? 1 : 0,
                ]) . "\n",
                FILE_APPEND
            );
        } catch (\Throwable $e) {
            // Igual que arriba.
        }
    }

    /**
     * Tira los registros pasados de fecha.
     *
     * Se decide por el NOMBRE del archivo, que es la fecha, no por su fecha de
     * modificación: una copia de seguridad o un FTP pueden cambiarle la fecha
     * a un archivo sin que su contenido envejezca ni un día.
     */
    private static function podar(): void
    {
        $limite = gmdate('Y-m-d', time() - self::$dias * 86400);

        foreach (glob(self::$carpeta . '/*.log') ?: [] as $archivo) {
            $nombre = basename($archivo, '.log');

            // «2026-10-02» o «permanencia-2026-10-02»: en los dos casos la
            // fecha son los diez últimos caracteres.
            $fecha = substr($nombre, -10);

            if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $fecha) !== 1) {
                continue; // No es un registro nuestro: no se toca.
            }

            if ($fecha < $limite) {
                @unlink($archivo);
            }
        }
    }

    /** La ruta pedida, sin la consulta y acotada. */
    private static function ruta(): string
    {
        $url = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        return self::limpiar(explode('?', $url, 2)[0]);
    }

    /**
     * Una ruta que se pueda apuntar sin miedo.
     *
     * Se quitan los tabuladores y los saltos —partirían la línea y
     * desordenarían el fichero entero— y se acota: una URL de diez mil
     * caracteres no aporta nada y sí llena el disco.
     */
    private static function limpiar(string $ruta): string
    {
        $ruta = str_replace(["\t", "\r", "\n"], ' ', $ruta);

        return mb_substr($ruta, 0, 120);
    }

    /**
     * ¿Parece un robot?
     *
     * Basta con separar una prueba de carga del tráfico de verdad; no se
     * guarda el navegador completo, que sí serviría para reconocer a alguien.
     */
    private static function esRobot(): bool
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($ua === '') {
            return true;
        }

        return (bool) preg_match(
            '~bot|crawler|spider|curl|wget|python|java/|go-http|ab/|siege|k6|vegeta|locust|headless~i',
            $ua
        );
    }
}
