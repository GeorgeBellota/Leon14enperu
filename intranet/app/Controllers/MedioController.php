<?php
/**
 * MedioController — la biblioteca de imágenes.
 *
 * Subir una foto, ponerle su texto alternativo y borrarla. Lo que se sube aquí
 * es lo que después se elige en las secciones y las piezas de cada página.
 *
 * Quien sube manda UNA imagen, la grande. El servidor genera la familia —dos
 * formatos y hasta tres anchos— para que el sitio siga sirviendo a cada
 * visitante el archivo que le conviene. Ver Core\Imagen.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Adjunto;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Imagen;
use Intranet\Core\Request;
use Intranet\Models\Medio;
use Throwable;

final class MedioController extends Controller
{
    /** Dónde viven las imágenes que se suben desde el panel. */
    private function carpeta(): string
    {
        return dirname(__DIR__, 3) . '/assets/subidos/paginas';
    }

    public function listar(Request $peticion): void
    {
        $modelo = new Medio($this->c);

        $filtros = ['buscar' => $peticion->texto('buscar', '')];
        $listado = $modelo->listar($filtros, max(1, (int) $peticion->get('pagina', 1)));

        $this->ver('medios/listar', [
            'titulo'  => 'Imágenes',
            'listado' => $listado,
            'filtros' => $filtros,
        ]);
    }

    public function subir(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $alt        = trim($peticion->texto('alt', ''));
        $decorativa = $peticion->casilla('decorativa');
        $archivo    = $_FILES['imagen'] ?? null;

        if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->conError('Elige la imagen que quieres subir.', '/medios');
        }

        // El texto alternativo es obligatorio salvo que la imagen se marque
        // como decorativa. No es burocracia: una foto de contenido sin alt deja
        // fuera a quien navega con lector de pantalla, y añadirlo después
        // significa repasar el sitio entero buscando cuáles faltan.
        if ($alt === '' && !$decorativa) {
            $this->conError(
                'Escribe qué se ve en la imagen, o márcala como decorativa si no aporta información.',
                '/medios'
            );
        }

        try {
            // Se reutiliza la validación de Adjunto —contenido real, no
            // extensión; SVG sin código dentro— y después se deriva la familia.
            $almacen  = new Adjunto($this->carpeta(), 20);
            $subida   = $almacen->imagen($archivo, 'img');
            $completa = dirname(__DIR__, 3) . '/' . $subida;

            $base    = pathinfo($subida, PATHINFO_FILENAME);
            $motor   = new Imagen($this->carpeta());
            $esVector = str_ends_with(strtolower($subida), '.svg');

            $procesada = $esVector
                ? $motor->vector($completa, $base)
                : $motor->derivar($completa, $base);

            // El archivo que dejó Adjunto ya no hace falta: las variantes son
            // archivos nuevos. En los SVG sí es el definitivo, así que se
            // conserva salvo que el vector lo haya copiado a otro nombre.
            if (!$esVector || $procesada['ruta'] !== $subida) {
                @unlink($completa);
            }
        } catch (ErrorDeNegocio $e) {
            // conError redirige y termina la petición: no se sigue por aquí.
            $this->conError($e->getMessage(), '/medios');
        }

        $nombre = Adjunto::nombreLegible((string) ($archivo['name'] ?? 'imagen'));

        try {
            $id = (new Medio($this->c))->registrar(
                $procesada,
                $nombre,
                $decorativa ? '' : $alt,
                $decorativa,
                $this->c->auth()->id()
            );
        } catch (Throwable $e) {
            // Los archivos ya están en disco. Si la fila no llega a escribirse,
            // sin esto quedarían huérfanos: ocupando espacio, sin aparecer en
            // ninguna pantalla y sin forma de borrarlos desde el panel.
            (new Imagen($this->carpeta()))->borrarFamilia($procesada['variantes']['base'] ?? $procesada['ruta']);

            error_log('[medios] no se pudo registrar la imagen: ' . $e->getMessage());

            $this->conError(
                'No se pudo guardar la imagen. Inténtalo de nuevo; si vuelve a fallar, avisa al equipo técnico.',
                '/medios'
            );
        }

        Auditoria::registrar($this->c, 'crear', 'medios', $id, [
            'nombre'  => $nombre,
            'anchos'  => $procesada['variantes']['anchos'] ?? [],
        ]);

        $variantes = $procesada['variantes']['anchos'] ?? [];

        $this->conExito(
            $variantes === []
                ? 'Imagen subida.'
                : 'Imagen subida. Se generaron ' . count($variantes) . ' tamaños en WebP y respaldo.',
            '/medios'
        );
    }

    /** Cambiar el texto alternativo sin volver a subir el archivo. */
    public function actualizar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $modelo = new Medio($this->c);
        $id     = (int) ($params['id'] ?? 0);
        $medio  = $modelo->buscar($id);

        if ($medio === null) {
            $this->conError('Esa imagen no existe.', '/medios');
        }

        $alt        = trim($peticion->texto('alt', ''));
        $decorativa = $peticion->casilla('decorativa');

        if ($alt === '' && !$decorativa) {
            $this->conError(
                'Escribe qué se ve en la imagen, o márcala como decorativa.',
                '/medios'
            );
        }

        $modelo->actualizar($id, [
            'alt'        => mb_substr($decorativa ? '' : $alt, 0, 255),
            'decorativa' => $decorativa ? 1 : 0,
        ]);

        Auditoria::registrar($this->c, 'editar', 'medios', $id, ['alt' => $alt]);

        $this->conExito('Descripción guardada.', '/medios');
    }

    public function borrar(Request $peticion, array $params = []): void
    {
        $this->exigirCsrf($peticion);

        $modelo = new Medio($this->c);
        $id     = (int) ($params['id'] ?? 0);
        $medio  = $modelo->conVariantes($id);

        if ($medio === null) {
            $this->conError('Esa imagen no existe.', '/medios');
        }

        // Se comprueba el uso antes de borrar. La clave foránea es
        // ON DELETE SET NULL: sin esta comprobación, borrar dejaría la página
        // pública sin foto y nadie se enteraría hasta verlo publicado.
        $usos = $modelo->usos($id);

        if ($usos['total'] > 0) {
            $this->conError(
                'No se puede borrar: la imagen está en uso en ' . $usos['total'] . ' sitio'
                . ($usos['total'] === 1 ? '' : 's') . ' — ' . implode('; ', array_slice($usos['donde'], 0, 3))
                . ($usos['total'] > 3 ? '…' : '') . '. Cámbiala allí primero.',
                '/medios'
            );
        }

        $base = $medio['variantes']['base'] ?? null;

        $modelo->eliminar($id);
        (new Imagen($this->carpeta()))->borrarFamilia(is_string($base) ? $base : $medio['ruta']);

        Auditoria::registrar($this->c, 'borrar', 'medios', $id, [
            'nombre' => $medio['nombre_archivo'] ?? '',
        ]);

        $this->conExito('Imagen borrada.', '/medios');
    }
}
