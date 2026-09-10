<?php
/**
 * DocumentoController — la biblioteca de documentos.
 *
 * El hermano pequeño de la de imágenes: subir un PDF, ver los que hay y
 * copiar su ruta para pegarla en la pieza que lo enlaza.
 *
 * ── Por qué no tiene tabla ─────────────────────────────────────────────────
 *
 * Porque no le hace falta. Una imagen se registra en `medios` porque arrastra
 * cosas que hay que guardar: su texto alternativo, sus medidas y la familia de
 * anchos que el servidor deriva de ella. Un PDF no tiene nada de eso: es un
 * archivo y una ruta.
 *
 * Así que la biblioteca ES la carpeta. Se lee del disco en cada visita, lo que
 * significa que no puede desincronizarse: si alguien sube un archivo por FTP
 * aparece aquí, y si borra uno deja de aparecer. Sin migración, sin esquema
 * nuevo y sin una tabla que mantener al día.
 *
 * ── Por qué no sube desde la pantalla de secciones ─────────────────────────
 *
 * Porque el formulario de una sección no es «multipart» y sus piezas se borran
 * y se recrean al guardar. Convertirlo para admitir archivos tiene un riesgo
 * que no compensa: si la subida supera `post_max_size`, PHP entrega un $_POST
 * VACÍO, y ese formulario reconstruye la sección con lo que recibe. Un PDF de
 * diez megas contra un límite de ocho vaciaría la sección entera sin un solo
 * mensaje de error.
 *
 * Subiendo aquí, esa pantalla no cambia: en la pieza se pega la ruta.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Adjunto;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Request;

final class DocumentoController extends Controller
{
    /** Ruta pública de la carpeta, tal como se escribe en una pieza. */
    private const PUBLICA = 'assets/subidos/documentos';

    private function carpeta(): string
    {
        return dirname(__DIR__, 3) . '/' . self::PUBLICA;
    }

    public function listar(Request $peticion): void
    {
        $this->ver('documentos/listar', [
            'titulo'     => 'Documentos',
            'documentos' => $this->documentos(),
            'carpeta'    => self::PUBLICA,
        ]);
    }

    /**
     * Lo que hay en la carpeta, del más reciente al más antiguo.
     *
     * @return array<int, array{nombre:string, ruta:string, peso:string, fecha:string}>
     */
    private function documentos(): array
    {
        $carpeta = $this->carpeta();

        if (!is_dir($carpeta)) {
            return [];
        }

        $lista = [];

        foreach ((array) scandir($carpeta) as $nombre) {
            if (!is_string($nombre) || $nombre === '.' || $nombre === '..') {
                continue;
            }

            $fisica = $carpeta . '/' . $nombre;

            // Ni carpetas ni los guardianes del directorio: .htaccess e
            // index.html están ahí para que nadie liste la carpeta por web, no
            // son documentos.
            if (!is_file($fisica) || str_starts_with($nombre, '.') || $nombre === 'index.html') {
                continue;
            }

            $bytes = (int) filesize($fisica);

            $lista[] = [
                'nombre' => $nombre,
                'ruta'   => self::PUBLICA . '/' . $nombre,
                'peso'   => $bytes >= 1048576
                    ? number_format($bytes / 1048576, 1, ',', '.') . ' MB'
                    : max(1, (int) round($bytes / 1024)) . ' KB',
                'fecha'  => date('d/m/Y H:i', (int) filemtime($fisica)),
                'orden'  => (int) filemtime($fisica),
            ];
        }

        usort($lista, static fn (array $a, array $b): int => $b['orden'] <=> $a['orden']);

        return $lista;
    }

    public function subir(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $archivo = $_FILES['documento'] ?? null;

        /* Un $_FILES vacío no siempre significa «no eligió archivo». Cuando la
           subida pasa de `post_max_size`, PHP descarta la petición entera y
           llega aquí sin $_POST y sin $_FILES. Se distingue por el
           CONTENT_LENGTH: si venía cuerpo y no ha llegado nada, es que se pasó
           del límite, y decirlo es la diferencia entre arreglarlo y volver a
           intentarlo diez veces con el mismo archivo. */
        if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $tam = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

            if ($tam > 0 && $_POST === []) {
                $this->conError(
                    'El archivo es más grande de lo que admite el servidor. Pide que suban '
                    . '«upload_max_filesize» y «post_max_size» en el PHP del alojamiento.',
                    '/documentos'
                );
            }

            $this->conError('Elige el documento que quieres subir.', '/documentos');
        }

        try {
            $almacen = new Adjunto($this->carpeta(), 20);
            // El nombre lo pone el servidor. Ver Core\Adjunto: que lo ponga
            // quien sube es de donde salen la mitad de los agujeros.
            $ruta = $almacen->documento($archivo, 'doc');
        } catch (ErrorDeNegocio $e) {
            $this->conError($e->getMessage(), '/documentos');
        }

        // Auditoria::registrar ya se traga sus propios errores: una anotación
        // que falla no puede tirar una subida que ya está en disco.
        Auditoria::registrar($this->c, 'crear', 'documentos', null, [
            'ruta'     => $ruta,
            'original' => Adjunto::nombreLegible((string) ($archivo['name'] ?? '')),
        ]);

        $this->conExito('Documento subido. Copia su ruta y pégala en la pieza que lo enlaza.', '/documentos');
    }

    public function borrar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $nombre = trim($peticion->texto('nombre', ''));

        /* El nombre llega del formulario, así que no se toca el disco con él
           tal cual: sólo se acepta si coincide EXACTAMENTE con uno de los
           archivos que la carpeta tiene ahora mismo. Una ruta con «..» o con
           barras no coincide con ninguno y no llega a ninguna parte. */
        $existe = false;

        foreach ($this->documentos() as $doc) {
            if ($doc['nombre'] === $nombre) {
                $existe = true;
                break;
            }
        }

        if (!$existe) {
            $this->conError('Ese documento ya no está en la biblioteca.', '/documentos');
        }

        (new Adjunto($this->carpeta(), 20))->borrar(self::PUBLICA . '/' . $nombre);

        Auditoria::registrar($this->c, 'borrar', 'documentos', null, ['nombre' => $nombre]);

        /* Aviso deliberado: esta biblioteca no sabe quién enlaza qué. Las
           piezas guardan la RUTA del archivo, no una referencia con la que se
           pueda preguntar. Así que antes de borrar hay que mirar, y lo mínimo
           es decirlo al terminar. */
        $this->conExito(
            'Documento borrado. Si alguna pieza lo enlazaba, su botón de descarga ha dejado de aparecer.',
            '/documentos'
        );
    }
}
