<?php
/**
 * ============================================================================
 *  Contingencia — la red de seguridad de las inscripciones.
 * ============================================================================
 *
 *  Cuando la base de datos no puede guardar una inscripción, esta clase la
 *  escribe en un archivo. La persona ve una confirmación —distinta, pero
 *  confirmación— y su inscripción no se pierde. Más tarde se recuperan todas
 *  con `intranet/database/recuperar-contingencia.php`.
 *
 *  ── Por qué existe ──────────────────────────────────────────────────────
 *  El hosting tarda entre uno y diez segundos en servir un archivo estático.
 *  Una base de datos sobre esa misma máquina falla de vez en cuando, y cada
 *  fallo era una persona viendo «no hemos podido registrar tu inscripción»
 *  después de rellenar cuatro pasos. Casi ninguna vuelve a intentarlo.
 *
 *  La convocatoria dura semanas y la base estará bien la mayor parte del
 *  tiempo. Esto es para los minutos en que no lo esté.
 *
 *  ── Por qué va cifrado ──────────────────────────────────────────────────
 *  Aquí dentro hay DNI, teléfono, dirección y fecha de nacimiento: datos
 *  personales de gente real. Un archivo de texto plano en el servidor sería
 *  una fuga esperando a ocurrir —basta un listado de directorio mal
 *  configurado— y sería peor que el problema que esto resuelve.
 *
 *  Se usa la misma caja fuerte que la base (AES-256-GCM con la clave de la
 *  aplicación), así que el archivo no dice nada a quien lo encuentre. Y como
 *  segunda barrera, la carpeta lleva su propio .htaccess que prohíbe servirla.
 *
 *  Si la clave se pierde, estos archivos son irrecuperables. Es el mismo
 *  riesgo que ya tienen los datos de la base, y está anotado en la deuda
 *  técnica.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use Intranet\Core\Cripto;
use Throwable;

final class Contingencia
{
    /** Carpeta del almacén, fuera de todo lo que sirve el servidor web. */
    private string $carpeta;

    private Cripto $cripto;

    public function __construct(Cripto $cripto, ?string $carpeta = null)
    {
        $this->cripto  = $cripto;
        $this->carpeta = $carpeta ?? dirname(__DIR__, 2) . '/almacen/contingencia';
    }

    /**
     * Guarda una inscripción que la base no pudo aceptar.
     *
     * Devuelve el código provisional que se le enseña a la persona, o null si
     * ni siquiera esto se pudo hacer —disco lleno, permisos—. En ese caso el
     * envío está realmente perdido y quien llama debe saberlo.
     *
     * @param array<string, mixed> $datos  la inscripción ya validada y limpia
     * @param array<string, mixed> $meta   ip, navegador, motivo del fallo
     */
    /**
     * Tope de sobres pendientes.
     *
     * Mientras la base esté caída no hay freno por IP —esa comprobación es una
     * consulta—, así que un robot podría llenar el disco. Con este tope, lo
     * peor que consigue es agotar el almacén, no tumbar el servidor. Diez mil
     * es muchísimo más de lo que esta convocatoria va a recibir en el rato que
     * dure una caída, y aun así el disco aguanta.
     */
    private const TOPE = 10000;

    public function guardar(array $datos, array $meta = []): ?string
    {
        try {
            if (!$this->prepararCarpeta()) {
                return null;
            }

            if ($this->cuantosPendientes() >= self::TOPE) {
                error_log('[contingencia] almacén lleno (' . self::TOPE . '), se descarta el envío');

                return null;
            }

            $codigo = $this->codigo();

            $sobre = [
                'codigo'   => $codigo,
                'recibido' => date('c'),
                'datos'    => $datos,
                'meta'     => $meta,
            ];

            $json = json_encode($sobre, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                return null;
            }

            // El nombre del archivo no dice nada de la persona: sólo la hora y
            // un identificador. Un directorio que se pueda listar no debe
            // revelar quién se inscribió.
            $archivo = $this->carpeta . '/' . $codigo . '.txt';

            // LOCK_EX porque dos inscripciones pueden llegar a la vez. Sin él,
            // dos escrituras simultáneas se entrelazan y ambas se corrompen.
            $bytes = @file_put_contents($archivo, $this->cripto->cifrar($json), LOCK_EX);

            if ($bytes === false) {
                error_log('[contingencia] no se pudo escribir ' . $archivo);

                return null;
            }

            @chmod($archivo, 0600);

            error_log(sprintf(
                '[contingencia] inscripción guardada fuera de la base · %s · motivo: %s',
                $codigo,
                (string) ($meta['motivo'] ?? 'sin especificar')
            ));

            return $codigo;
        } catch (Throwable $e) {
            // Este método es la última red. Si falla, falla en silencio hacia
            // fuera y deja rastro dentro: lanzar una excepción aquí volvería a
            // enseñarle un error a quien se está inscribiendo, que es
            // justamente lo que se quiere evitar.
            error_log('[contingencia] fallo al guardar: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Los sobres pendientes, ya descifrados y ordenados por hora de llegada.
     *
     * @return array<int, array{archivo: string, sobre: array<string, mixed>}>
     */
    public function pendientes(): array
    {
        $lista = [];

        foreach (glob($this->carpeta . '/*.txt') ?: [] as $archivo) {
            $crudo = @file_get_contents($archivo);
            if ($crudo === false || $crudo === '') {
                continue;
            }

            try {
                $sobre = json_decode($this->cripto->descifrar($crudo), true);
            } catch (Throwable $e) {
                error_log('[contingencia] no se pudo descifrar ' . basename($archivo) . ': ' . $e->getMessage());
                continue;
            }

            if (is_array($sobre)) {
                $lista[] = ['archivo' => $archivo, 'sobre' => $sobre];
            }
        }

        usort($lista, static fn (array $a, array $b): int
            => strcmp((string) ($a['sobre']['recibido'] ?? ''), (string) ($b['sobre']['recibido'] ?? '')));

        return $lista;
    }

    /**
     * Aparta un sobre ya recuperado. No se borra: se mueve a `recuperados/`.
     *
     * Borrar sería más limpio y también irreversible. Si la importación tuvo
     * un fallo que nadie vio, el original es lo único que queda.
     */
    public function archivar(string $archivo): bool
    {
        $destino = $this->carpeta . '/recuperados';

        if (!is_dir($destino) && !@mkdir($destino, 0700, true) && !is_dir($destino)) {
            return false;
        }

        return @rename($archivo, $destino . '/' . basename($archivo));
    }

    public function cuantosPendientes(): int
    {
        return count(glob($this->carpeta . '/*.txt') ?: []);
    }

    public function donde(): string
    {
        return $this->carpeta;
    }

    /**
     * Crea la carpeta y la cierra a cal y canto.
     *
     * El .htaccess es una segunda barrera, no la primera: la carpeta está
     * fuera de lo que el servidor publica. Pero las instalaciones se mueven,
     * alguien puede copiar `intranet/` dentro de public_html un día con prisa,
     * y entonces esta línea es lo único que separa los DNI de la gente de una
     * dirección web. Cuesta nada.
     */
    private function prepararCarpeta(): bool
    {
        if (!is_dir($this->carpeta) && !@mkdir($this->carpeta, 0700, true) && !is_dir($this->carpeta)) {
            error_log('[contingencia] no se pudo crear ' . $this->carpeta);

            return false;
        }

        $guardia = $this->carpeta . '/.htaccess';

        if (!is_file($guardia)) {
            @file_put_contents(
                $guardia,
                "# Datos personales cifrados. Nada de aquí se sirve por web.\n"
                . "Require all denied\n"
                . "<IfModule !mod_authz_core.c>\n"
                . "  Deny from all\n"
                . "</IfModule>\n"
            );
        }

        // index.html vacío por si el .htaccess se ignora y el listado de
        // directorio está activo: se vería una página en blanco, no la lista.
        $tapa = $this->carpeta . '/index.html';
        if (!is_file($tapa)) {
            @file_put_contents($tapa, '');
        }

        return true;
    }

    /**
     * Un código legible y distinto de los de la base.
     *
     * Empieza por CONT- a propósito: si alguien escribe ese código en el panel
     * buscando su inscripción, que quede claro de inmediato que aún no está en
     * la base y hay que recuperarla.
     */
    private function codigo(): string
    {
        return sprintf('CONT-%s-%s', date('Ymd-His'), bin2hex(random_bytes(3)));
    }
}
