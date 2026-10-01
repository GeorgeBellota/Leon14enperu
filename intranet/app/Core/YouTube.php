<?php
/**
 * ============================================================================
 *  YouTube — sacar de un enlace su identificador, su título y su portada.
 * ============================================================================
 *
 *  Lo pidió el cliente: «configurar la sección para que cada video muestre
 *  automáticamente su título y su miniatura de YouTube, sin necesidad de
 *  subir imágenes manualmente».
 *
 *  ── Por qué se DESCARGA la miniatura en vez de enlazarla ────────────────
 *
 *  Enlazar a `i.ytimg.com` sería una línea. Pero la CSP de este sitio es
 *  `img-src 'self'`, y abrirla significaría que el navegador de cada
 *  visitante pide esa imagen a Google **en cuanto abre /noticias/**, antes de
 *  aceptar nada. Este sitio no carga un solo recurso de YouTube hasta que la
 *  persona pulsa el vídeo; hacerlo por la puerta de atrás con las portadas
 *  rompería esa promesa en una web con política de privacidad publicada.
 *
 *  Descargándola una vez: la CSP no se toca, el visitante no habla con Google
 *  hasta que decide, y si el vídeo se hace privado la portada sigue ahí.
 *
 *  ── Por qué oEmbed y no la API de datos ─────────────────────────────────
 *
 *  Porque oEmbed no pide clave. Una clave de API es un secreto más que
 *  guardar, rotar y explicar, para traer un título.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class YouTube
{
    /** Las portadas, de la mejor a la peor. No todos los vídeos tienen las
     *  dos primeras: las sube el autor sólo si el original era grande. */
    private const PORTADAS = ['maxresdefault', 'sddefault', 'hqdefault'];

    private const ESPERA = 12;

    /**
     * El identificador de un enlace de YouTube, o null.
     *
     * Admite las formas que de verdad se pegan: el enlace normal, el corto,
     * el de incrustar, el de directo y el de Shorts. Lo que no reconoce
     * devuelve null, para avisar en lugar de guardar un vídeo que no existe.
     */
    public static function identificador(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        // Alguien pega sólo el identificador. Es válido y es cómodo.
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url) === 1) {
            return $url;
        }

        $patrones = [
            '~youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~i',
            '~youtu\.be/([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/embed/([A-Za-z0-9_-]{11})~i',
            '~youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/live/([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/shorts/([A-Za-z0-9_-]{11})~i',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $url, $coincide) === 1) {
                return $coincide[1];
            }
        }

        return null;
    }

    /**
     * El título y el autor, por oEmbed.
     *
     * Devuelve un arreglo vacío si YouTube no contesta o el vídeo no existe.
     * No lanza: que no se pueda traer el título no es motivo para impedir
     * guardar el vídeo —se escribe a mano y listo—, y un panel que revienta
     * porque una red va lenta es peor que uno que avisa.
     *
     * @return array{titulo?: string, autor?: string}
     */
    public static function ficha(string $youtubeId): array
    {
        $json = self::traer(
            'https://www.youtube.com/oembed?format=json&url='
            . rawurlencode('https://www.youtube.com/watch?v=' . $youtubeId)
        );

        if ($json === null) {
            return [];
        }

        $datos = json_decode($json, true);

        if (!is_array($datos)) {
            return [];
        }

        $salida = [];

        if (trim((string) ($datos['title'] ?? '')) !== '') {
            $salida['titulo'] = mb_substr(trim((string) $datos['title']), 0, 255);
        }

        if (trim((string) ($datos['author_name'] ?? '')) !== '') {
            $salida['autor'] = mb_substr(trim((string) $datos['author_name']), 0, 160);
        }

        return $salida;
    }

    /**
     * Descarga la portada a un archivo temporal. Devuelve la ruta, o null.
     *
     * Prueba de la mejor a la peor: no todos los vídeos tienen `maxres`, y
     * cuando falta, YouTube no devuelve un 404 limpio sino una imagen gris de
     * 120x90 que diría «portada» sin serlo. Por eso se comprueba el TAMAÑO:
     * esa imagen de relleno pesa unos pocos kilobytes.
     */
    public static function portada(string $youtubeId): ?string
    {
        foreach (self::PORTADAS as $calidad) {
            $datos = self::traer("https://i.ytimg.com/vi/{$youtubeId}/{$calidad}.jpg", true);

            if ($datos === null || strlen($datos) < 4096) {
                continue;
            }

            // Que sea una imagen de verdad, no una página de error con
            // extensión .jpg: se mira el contenido, no el nombre.
            $temporal = tempnam(sys_get_temp_dir(), 'yt');

            if ($temporal === false) {
                return null;
            }

            file_put_contents($temporal, $datos);

            $info = @getimagesize($temporal);

            if (is_array($info) && ($info[2] ?? 0) === IMAGETYPE_JPEG && (int) $info[0] >= 480) {
                return $temporal;
            }

            @unlink($temporal);
        }

        return null;
    }

    /** La dirección para incrustar, sin cookies hasta que se pulse. */
    public static function incrustar(string $youtubeId): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . $youtubeId . '?autoplay=1&rel=0';
    }

    /**
     * Una petición de salida, con tiempos cortos.
     *
     * Esto corre DENTRO de guardar un vídeo en el panel. Sin un tope, una
     * tarde en que YouTube vaya lento dejaría el formulario colgado y quien
     * lo usa pensaría que el panel se rompió.
     */
    private static function traer(string $url, bool $binario = false): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);

        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => self::ESPERA,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'leon14enperu.com',
            // Una portada grande ronda los 300 KB. El tope evita que una
            // respuesta inesperada se trague la memoria del proceso.
            CURLOPT_BUFFERSIZE     => 65536,
        ]);

        $respuesta = curl_exec($ch);
        $codigo    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        if (!is_string($respuesta) || $codigo !== 200) {
            return null;
        }

        if (!$binario && strlen($respuesta) > 64 * 1024) {
            return null;
        }

        return $respuesta;
    }
}
