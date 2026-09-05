<?php
/**
 * ============================================================================
 *  Adjunto — recibir un archivo del panel sin abrir un agujero.
 * ============================================================================
 *
 *  Lo usan la imagen y el documento de los comunicados. Existe como pieza
 *  aparte porque una subida de archivos es la puerta de entrada más usada para
 *  colar código en un servidor, y esas comprobaciones no deben estar escritas
 *  dos veces con la esperanza de que las dos copias se mantengan iguales.
 *
 *  ── Lo que impide ────────────────────────────────────────────────────────
 *   · Que la extensión decida. Se mira el CONTENIDO: las imágenes con
 *     getimagesize(), los documentos por sus primeros bytes.
 *   · Que el nombre lo ponga quien sube. Lo pone el servidor, con caracteres
 *     que no significan nada en una ruta.
 *   · Que un SVG traiga <script> dentro. Se rechaza.
 *   · Que un .php se cuele con nombre de imagen. No pasa la comprobación de
 *     contenido, y aunque pasara, se guarda con extensión de imagen en una
 *     carpeta donde el servidor no ejecuta PHP.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Adjunto
{
    /** Imágenes: lo que se puede enseñar en un modal. */
    private const IMAGENES = [
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Documentos, por su firma —los primeros bytes—, no por la extensión.
     *
     * Un PDF empieza siempre por «%PDF». Un MP3 por «ID3» si trae etiquetas, o
     * por 0xFF 0xFB / 0xFF 0xF3 / 0xFF 0xF2 si es una trama suelta. Los cuatro
     * casos aparecen en archivos reales, así que están los cuatro.
     */
    private const DOCUMENTOS = [
        'pdf'  => ['%PDF'],
        'mp3'  => ['ID3', "\xFF\xFB", "\xFF\xF3", "\xFF\xF2"],
        'mp4'  => ['ftyp'],       // aparece en el byte 4, se busca dentro
        'docx' => ["PK\x03\x04"],
        'xlsx' => ["PK\x03\x04"],
    ];

    public function __construct(private string $carpeta, private int $topeMB = 20)
    {
    }

    /**
     * Guarda una imagen. Devuelve la ruta pública relativa a la raíz del sitio.
     *
     * @param array{name?:string,tmp_name?:string,size?:int,error?:int} $archivo
     */
    public function imagen(array $archivo, string $base): string
    {
        $temporal = $this->recibir($archivo);
        $info = @getimagesize($temporal);

        if (is_array($info) && isset(self::IMAGENES[$info[2]])) {
            return $this->mover($temporal, $base, self::IMAGENES[$info[2]]);
        }

        if ($this->esSvgLimpio($temporal, (string) ($archivo['name'] ?? ''))) {
            return $this->mover($temporal, $base, 'svg');
        }

        throw new ErrorDeNegocio('Sube una imagen PNG, JPG, WEBP o SVG.', 'imagen');
    }

    /**
     * Guarda un documento. Devuelve la ruta pública.
     *
     * @param array{name?:string,tmp_name?:string,size?:int,error?:int} $archivo
     */
    public function documento(array $archivo, string $base): string
    {
        $temporal = $this->recibir($archivo);
        $nombre = (string) ($archivo['name'] ?? '');
        $extension = strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));

        if (!isset(self::DOCUMENTOS[$extension])) {
            throw new ErrorDeNegocio(
                'Sólo se pueden subir archivos PDF, MP3, MP4, DOCX o XLSX.',
                'archivo'
            );
        }

        $cabecera = (string) file_get_contents($temporal, false, null, 0, 16);
        $coincide = false;

        foreach (self::DOCUMENTOS[$extension] as $firma) {
            if (str_starts_with($cabecera, $firma) || str_contains($cabecera, $firma)) {
                $coincide = true;
                break;
            }
        }

        if (!$coincide) {
            throw new ErrorDeNegocio(
                'Ese archivo dice ser ' . strtoupper($extension) . ' pero su contenido no lo es. '
                . 'Vuelve a exportarlo y súbelo otra vez.',
                'archivo'
            );
        }

        return $this->mover($temporal, $base, $extension);
    }

    /** El nombre original, limpio, para que la descarga no se llame «a7f3.pdf». */
    public static function nombreLegible(string $original): string
    {
        $nombre = pathinfo($original, PATHINFO_FILENAME);
        $nombre = preg_replace('/[^\p{L}\p{N} ._-]+/u', '', $nombre) ?? '';
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre) ?? '');

        return mb_substr($nombre === '' ? 'documento' : $nombre, 0, 120)
             . '.' . strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
    }

    /** Borra un archivo subido, por su ruta pública. */
    public function borrar(?string $rutaPublica): void
    {
        if ($rutaPublica === null || $rutaPublica === '') {
            return;
        }

        // Sólo se borra dentro de la carpeta de esta instancia: una ruta con
        // «..» no puede llevar el borrado a otra parte del disco.
        $fisica = realpath(dirname(__DIR__, 3) . '/' . ltrim($rutaPublica, '/'));
        $raiz   = realpath($this->carpeta);

        if ($fisica !== false && $raiz !== false && str_starts_with($fisica, $raiz) && is_file($fisica)) {
            @unlink($fisica);
        }
    }

    /**
     * Comprobaciones comunes a todo lo que llega. Devuelve la ruta temporal.
     *
     * @param array{name?:string,tmp_name?:string,size?:int,error?:int} $archivo
     */
    private function recibir(array $archivo): string
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new ErrorDeNegocio('El archivo pesa demasiado. El máximo son ' . $this->topeMB . ' MB.', 'archivo');
        }

        $temporal = (string) ($archivo['tmp_name'] ?? '');

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($temporal)) {
            throw new ErrorDeNegocio('No se pudo recibir el archivo. Inténtalo de nuevo.', 'archivo');
        }

        if (filesize($temporal) > $this->topeMB * 1024 * 1024) {
            throw new ErrorDeNegocio('El archivo pesa demasiado. El máximo son ' . $this->topeMB . ' MB.', 'archivo');
        }

        return $temporal;
    }

    private function esSvgLimpio(string $temporal, string $nombre): bool
    {
        if (strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION)) !== 'svg') {
            return false;
        }

        $contenido = (string) file_get_contents($temporal);

        if (!str_contains($contenido, '<svg')) {
            return false;
        }

        if (preg_match('/<script|\bon[a-z]+\s*=|javascript:|<foreignObject/i', $contenido)) {
            throw new ErrorDeNegocio(
                'Ese SVG lleva código dentro y no se puede usar. Guárdalo como PNG.',
                'imagen'
            );
        }

        return true;
    }

    /**
     * Mueve el archivo a su sitio con un nombre que pone el servidor.
     *
     * El nombre lleva un trozo aleatorio para que subir un archivo distinto con
     * el mismo nombre no se quede escondido detrás de la copia guardada del
     * navegador, y para que nadie pueda adivinar la dirección de un documento
     * que todavía no se ha publicado.
     */
    private function mover(string $temporal, string $base, string $extension): string
    {
        if (!is_dir($this->carpeta) && !@mkdir($this->carpeta, 0775, true) && !is_dir($this->carpeta)) {
            throw new ErrorDeNegocio('No se pudo guardar el archivo en el servidor.', 'archivo');
        }

        $nombre = $base . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = $this->carpeta . '/' . $nombre;

        if (!@move_uploaded_file($temporal, $destino)) {
            throw new ErrorDeNegocio('No se pudo guardar el archivo en el servidor.', 'archivo');
        }

        @chmod($destino, 0644);

        $relativa = str_replace('\\', '/', substr($this->carpeta, strlen(dirname(__DIR__, 3)) + 1));

        return $relativa . '/' . $nombre;
    }
}
