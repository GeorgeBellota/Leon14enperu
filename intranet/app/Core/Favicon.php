<?php
/**
 * ============================================================================
 *  Favicon — el icono de la pestaña, subido desde el panel.
 * ============================================================================
 *
 *  Es hermano de Logotipo y comparte con él toda la cautela de una subida de
 *  archivos, pero son dos cosas distintas y por eso son dos clases:
 *
 *    · El logotipo se ve DENTRO de la página, en la cabecera, y puede ser
 *      ancho y llevar letra.
 *    · El favicon se ve FUERA, en la pestaña del navegador y en el marcador,
 *      a 16 píxeles. Ahí no cabe una palabra: cabe una forma.
 *
 *  Cambiar uno no tiene por qué cambiar el otro, así que cada uno tiene su
 *  archivo y su control en Configuración.
 *
 *  ── Mientras no haya ninguno subido ─────────────────────────────────────
 *
 *  Se sirve el favicon.svg de la raíz, que dibuja una cruz latina sobre campo
 *  rojo. Ese archivo lleva dentro su propia explicación: antes había un lirio
 *  del escudo pontificio y se retiró porque el escudo y sus elementos son
 *  símbolos heráldicos del Santo Padre y su uso no está aprobado. Es la misma
 *  regla que esta web le pide a los medios en la página de Prensa, así que
 *  conviene no saltársela aquí.
 *
 *  ── Por qué esto no es «guardar un archivo» y ya ────────────────────────
 *
 *  Una subida en un panel es la puerta más usada para colar código en un
 *  servidor. Las defensas son las mismas que en Logotipo:
 *
 *    · La extensión NO decide nada: manda el contenido, leído con
 *      getimagesize().
 *    · El nombre lo pone el servidor —«favicon»—, no quien sube.
 *    · El SVG se comprueba aparte y se rechaza si trae <script>, atributos
 *      on… o enlaces javascript:, que es como un dibujo se vuelve un ataque.
 *      Importa más aquí que en el logotipo: el navegador pide el favicon en
 *      TODAS las páginas, incluido el propio panel.
 *    · Se guarda en assets/img/marca/, una carpeta de imágenes donde el
 *      servidor no ejecuta PHP.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Favicon
{
    /** Medio mega. Un icono de pestaña razonable pesa unos pocos kilos. */
    private const TOPE = 512 * 1024;

    /** El orden manda cuando hay varios: gana el vector, que no pixela. */
    private const ORDEN = ['svg', 'png', 'ico', 'webp'];

    private const TIPOS = [
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_ICO  => 'ico',
        IMAGETYPE_WEBP => 'webp',
    ];

    /** Lo que se pone en el «type» del <link>. */
    private const MIMES = [
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'ico'  => 'image/x-icon',
        'webp' => 'image/webp',
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
            throw new ErrorDeNegocio('El icono pesa demasiado. El máximo son 512 KB.', 'favicon');
        }

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($archivo['tmp_name'] ?? ''))) {
            throw new ErrorDeNegocio('No se pudo recibir el icono. Inténtalo de nuevo.', 'favicon');
        }

        $temporal = (string) $archivo['tmp_name'];

        if (filesize($temporal) > self::TOPE) {
            throw new ErrorDeNegocio('El icono pesa demasiado. El máximo son 512 KB.', 'favicon');
        }

        $extension = $this->extensionSegura($temporal, (string) ($archivo['name'] ?? ''));

        if (!is_dir($this->carpeta) && !@mkdir($this->carpeta, 0775, true) && !is_dir($this->carpeta)) {
            throw new ErrorDeNegocio('No se pudo guardar el icono en el servidor.', 'favicon');
        }

        /* Se borra el anterior antes de escribir. Si el icono pasa de PNG a
           SVG, dos archivos con el mismo nombre y distinta extensión quedarían
           conviviendo y ganaría el primero del orden, no el que se acaba de
           subir. */
        $this->borrar();

        $destino = $this->carpeta . '/favicon.' . $extension;

        if (!@move_uploaded_file($temporal, $destino)) {
            throw new ErrorDeNegocio('No se pudo guardar el icono en el servidor.', 'favicon');
        }

        @chmod($destino, 0644);

        return 'assets/img/marca/favicon.' . $extension;
    }

    /** Quita el icono subido y devuelve al de la raíz. */
    public function borrar(): void
    {
        foreach (self::ORDEN as $ext) {
            @unlink($this->carpeta . '/favicon.' . $ext);
        }
    }

    /**
     * La ruta pública del icono subido si existe, o null.
     *
     * Se comprueba el ARCHIVO, no un ajuste guardado: si alguien lo borra por
     * FTP, la web vuelve sola al de la raíz en vez de enseñar un icono roto, y
     * el panel dice la verdad en lugar de un recuerdo.
     */
    public function actual(): ?string
    {
        foreach (self::ORDEN as $ext) {
            if (is_file($this->carpeta . '/favicon.' . $ext)) {
                return 'assets/img/marca/favicon.' . $ext;
            }
        }

        return null;
    }

    /** El «type» que le corresponde a una ruta de icono. */
    public static function mime(string $ruta): string
    {
        $ext = strtolower((string) pathinfo($ruta, PATHINFO_EXTENSION));

        return self::MIMES[$ext] ?? 'image/png';
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

        /* Los .ico no los reconoce getimagesize en todas las versiones de PHP,
           así que se comprueban por su firma: los cuatro primeros bytes de un
           icono son 00 00 01 00. Es el encabezado del formato. */
        $firma = (string) @file_get_contents($temporal, false, null, 0, 4);

        if ($firma === "\x00\x00\x01\x00") {
            return 'ico';
        }

        // getimagesize no entiende de SVG: es texto, no una imagen de píxeles.
        // Se acepta sólo si el nombre lo anuncia Y el contenido lo confirma Y
        // no trae nada ejecutable dentro.
        if (strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION)) === 'svg') {
            $contenido = (string) file_get_contents($temporal);

            if (!str_contains($contenido, '<svg')) {
                throw new ErrorDeNegocio('Ese archivo no parece una imagen.', 'favicon');
            }

            if (preg_match('/<script|\bon[a-z]+\s*=|javascript:|<foreignObject/i', $contenido)) {
                throw new ErrorDeNegocio(
                    'Ese SVG lleva código dentro y no se puede usar como icono. '
                    . 'Guárdalo como PNG y vuelve a subirlo.',
                    'favicon'
                );
            }

            return 'svg';
        }

        throw new ErrorDeNegocio('Sube un icono SVG, PNG, ICO o WEBP.', 'favicon');
    }
}
