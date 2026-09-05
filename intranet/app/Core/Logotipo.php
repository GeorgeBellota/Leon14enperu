<?php
/**
 * ============================================================================
 *  Logotipo — la imagen de la marca, subida desde el panel.
 * ============================================================================
 *
 *  Mientras no haya ninguna subida, la cabecera del sitio muestra sólo el
 *  texto: «León XIV / En el Perú». Antes mostraba también el lirio del escudo
 *  pontificio, y ese uso no está aprobado; hasta que lo esté, no se enseña
 *  ningún símbolo que no sea propio.
 *
 *  ── Por qué esto no es «guardar un archivo» y ya ────────────────────────
 *  Una subida de archivos en un panel es la puerta de entrada más usada para
 *  colar código en un servidor. Basta con llamar `logo.php` a algo que no es
 *  una imagen y pedirlo por su dirección. Aquí no puede pasar:
 *
 *    · La extensión NO decide nada: se mira el contenido con getimagesize(),
 *      que sólo reconoce imágenes de verdad.
 *    · El nombre lo pone el servidor, no quien sube: `marca.png`. Nada de lo
 *      que escriba nadie llega al sistema de archivos.
 *    · Sólo se aceptan PNG, JPEG, WEBP y SVG.
 *    · El SVG se comprueba aparte —getimagesize no lo reconoce— y se rechaza
 *      si trae <script>, atributos on… o enlaces javascript:, que es como se
 *      convierte un dibujo en un ataque.
 *    · Se guarda en assets/img/marca/, una carpeta de imágenes; el servidor
 *      no ejecuta PHP ahí, y aunque lo intentara, el archivo se llama .png.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Logotipo
{
    /** Tres megas: un logotipo razonable pesa cien veces menos. */
    private const TOPE = 3 * 1024 * 1024;

    private const TIPOS = [
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_WEBP => 'webp',
    ];

    private string $carpeta;

    public function __construct(?string $carpeta = null)
    {
        $this->carpeta = $carpeta ?? dirname(__DIR__, 3) . '/assets/img/marca';
    }

    /**
     * Guarda el archivo recibido. Devuelve la ruta pública, relativa a la raíz
     * del sitio, o lanza ErrorDeNegocio con un motivo que se le puede enseñar
     * a quien lo subió.
     *
     * @param array{name?:string,tmp_name?:string,size?:int,error?:int} $archivo
     */
    public function guardar(array $archivo): string
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new ErrorDeNegocio('La imagen pesa demasiado. El máximo son 3 MB.', 'logotipo');
        }

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($archivo['tmp_name'] ?? ''))) {
            throw new ErrorDeNegocio('No se pudo recibir la imagen. Inténtalo de nuevo.', 'logotipo');
        }

        $temporal = (string) $archivo['tmp_name'];

        if (filesize($temporal) > self::TOPE) {
            throw new ErrorDeNegocio('La imagen pesa demasiado. El máximo son 3 MB.', 'logotipo');
        }

        $extension = $this->extensionSegura($temporal, (string) ($archivo['name'] ?? ''));

        if (!is_dir($this->carpeta) && !@mkdir($this->carpeta, 0775, true) && !is_dir($this->carpeta)) {
            throw new ErrorDeNegocio('No se pudo guardar la imagen en el servidor.', 'logotipo');
        }

        // Se borra el anterior antes de escribir: si el logotipo pasa de PNG a
        // SVG, dos archivos con el mismo nombre y distinta extensión quedarían
        // conviviendo y no se sabría cuál manda.
        foreach (glob($this->carpeta . '/marca.*') ?: [] as $viejo) {
            @unlink($viejo);
        }

        $destino = $this->carpeta . '/marca.' . $extension;

        if (!@move_uploaded_file($temporal, $destino)) {
            throw new ErrorDeNegocio('No se pudo guardar la imagen en el servidor.', 'logotipo');
        }

        @chmod($destino, 0644);

        return 'assets/img/marca/marca.' . $extension;
    }

    /** Quita el logotipo y devuelve a la marca de sólo texto. */
    public function borrar(): void
    {
        foreach (glob($this->carpeta . '/marca.*') ?: [] as $archivo) {
            @unlink($archivo);
        }
    }

    /**
     * La ruta pública del logotipo si existe, o null.
     *
     * Se comprueba el archivo, no el ajuste: si alguien lo borra por FTP, la
     * cabecera debe volver al texto sola en lugar de enseñar una imagen rota.
     */
    public function actual(): ?string
    {
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (is_file($this->carpeta . '/marca.' . $ext)) {
                return 'assets/img/marca/marca.' . $ext;
            }
        }

        return null;
    }

    /**
     * La extensión que le corresponde al archivo POR SU CONTENIDO.
     *
     * Nunca por el nombre: quien sube elige el nombre, y confiar en él es
     * exactamente el agujero que permite subir código disfrazado de imagen.
     */
    private function extensionSegura(string $temporal, string $nombre): string
    {
        $info = @getimagesize($temporal);

        if (is_array($info) && isset(self::TIPOS[$info[2]])) {
            return self::TIPOS[$info[2]];
        }

        // getimagesize no entiende de SVG: es texto, no una imagen de píxeles.
        // Se acepta sólo si el nombre lo anuncia Y el contenido lo confirma Y
        // no trae nada ejecutable dentro.
        if (strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION)) === 'svg') {
            $contenido = (string) file_get_contents($temporal);

            if (!str_contains($contenido, '<svg')) {
                throw new ErrorDeNegocio('Ese archivo no parece una imagen.', 'logotipo');
            }

            if (preg_match('/<script|\bon[a-z]+\s*=|javascript:|<foreignObject/i', $contenido)) {
                throw new ErrorDeNegocio(
                    'Ese SVG lleva código dentro y no se puede usar como logotipo. '
                    . 'Guárdalo como PNG y vuelve a subirlo.',
                    'logotipo'
                );
            }

            return 'svg';
        }

        throw new ErrorDeNegocio('Sube una imagen PNG, JPG, WEBP o SVG.', 'logotipo');
    }
}
