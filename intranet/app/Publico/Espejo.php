<?php
/**
 * ============================================================================
 *  Espejo — la última copia buena de las listas que la página necesita.
 * ============================================================================
 *
 *  La página de voluntariado pinta cuatro listas que vienen de la base: los
 *  servicios, las jurisdicciones, los departamentos y si la convocatoria está
 *  abierta. Si la base no responde, esas consultas lanzan y la página entera
 *  se cae con un error 500: nadie llega a ver el formulario siquiera.
 *
 *  Eso ya pasó. Se descubrió probando: con la base desconectada, el envío
 *  aguantaba —hay una red de seguridad para eso— pero la página no llegaba a
 *  mostrarse. Una red de seguridad detrás de una puerta cerrada no sirve de
 *  nada.
 *
 *  Este espejo guarda la última respuesta buena de cada lista en un archivo.
 *  Mientras la base va bien, se refresca sola y nadie la nota. Cuando la base
 *  falla, la página se pinta con la copia y sigue funcionando.
 *
 *  ── Qué NO guarda ───────────────────────────────────────────────────────
 *  Sólo listas públicas: nombres de servicios, de diócesis, de departamentos.
 *  Lo mismo que cualquiera ve en el formulario. Aquí no entra ningún dato
 *  personal, y por eso —a diferencia del almacén de contingencia— no va
 *  cifrado. Si algún día se guardara algo de alguien, tendría que cifrarse.
 *
 *  ── El riesgo, dicho claramente ─────────────────────────────────────────
 *  Una copia puede quedarse vieja: un servicio retirado del catálogo seguiría
 *  ofreciéndose mientras la base esté caída. Es asumible porque el servidor
 *  valida de nuevo al guardar y rechazaría un servicio que ya no existe. Una
 *  lista un poco desfasada durante unos minutos es mucho menos grave que un
 *  formulario que no se puede abrir.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use Throwable;

final class Espejo
{
    private string $carpeta;

    public function __construct(?string $carpeta = null)
    {
        $this->carpeta = $carpeta ?? dirname(__DIR__, 2) . '/almacen/espejo';
    }

    /**
     * Devuelve el valor consultándolo a la base, y guarda una copia.
     * Si la consulta falla, devuelve la última copia buena.
     *
     *     $servicios = $espejo->recordar('servicios', fn () => $catalogo->servicios());
     *
     * @param callable():mixed $consulta
     * @param mixed $siNoHayNada  qué devolver si falla y tampoco hay copia
     */
    public function recordar(string $clave, callable $consulta, mixed $siNoHayNada = []): mixed
    {
        try {
            $valor = $consulta();

            // Sólo se guarda una respuesta con contenido. Una lista vacía suele
            // significar que algo va mal, y machacar con ella una copia buena
            // dejaría a la página sin nada justo cuando más falta hace.
            if ($valor !== null && $valor !== [] && $valor !== '') {
                $this->guardar($clave, $valor);
            }

            return $valor;
        } catch (Throwable $e) {
            error_log('[espejo] «' . $clave . '» no se pudo consultar: ' . $e->getMessage());

            $copia = $this->leer($clave);

            if ($copia !== null) {
                error_log('[espejo] se usa la copia local de «' . $clave . '»');

                return $copia;
            }

            return $siNoHayNada;
        }
    }

    private function guardar(string $clave, mixed $valor): void
    {
        try {
            if (!is_dir($this->carpeta) && !@mkdir($this->carpeta, 0700, true) && !is_dir($this->carpeta)) {
                return;
            }

            $json = json_encode($valor, JSON_UNESCAPED_UNICODE);

            if ($json === false) {
                return;
            }

            $archivo = $this->carpeta . '/' . $this->nombre($clave) . '.json';

            // Sólo se reescribe si cambió algo: estas listas se consultan en
            // cada visita y no tiene sentido escribir en disco cada vez.
            if (@file_get_contents($archivo) === $json) {
                return;
            }

            @file_put_contents($archivo, $json, LOCK_EX);
        } catch (Throwable $e) {
            // Que no se pueda guardar la copia no puede tumbar la página: es
            // una comodidad, no un requisito.
            error_log('[espejo] no se pudo guardar «' . $clave . '»: ' . $e->getMessage());
        }
    }

    private function leer(string $clave): mixed
    {
        $archivo = $this->carpeta . '/' . $this->nombre($clave) . '.json';

        if (!is_file($archivo)) {
            return null;
        }

        $crudo = @file_get_contents($archivo);

        if ($crudo === false || $crudo === '') {
            return null;
        }

        return json_decode($crudo, true);
    }

    /** La clave nunca toca el sistema de archivos tal cual llega. */
    private function nombre(string $clave): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '_', $clave) ?? 'x';
    }
}
