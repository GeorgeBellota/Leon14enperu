<?php
/**
 * ============================================================================
 *  ImagenSocial — la imagen que sale al pegar un enlace en WhatsApp o redes.
 * ============================================================================
 *
 *  De una imagen de la biblioteca saca un JPG pensado sólo para eso —como
 *  mucho 1200 px de lado y por debajo de 300 KB— y devuelve su ruta. Lo usan la web, para la etiqueta og:image, y el
 *  panel, que la prepara en cuanto alguien la elige.
 *
 *  ── Por qué no vale la imagen tal cual ────────────────────────────────────
 *
 *  Pasó: el panel ya dejaba elegir una «Imagen al compartir» y aun así las
 *  tarjetas no cambiaban. Tres razones, y esta clase resuelve las tres:
 *
 *   1. La dirección no cambiaba. WhatsApp y Facebook guardan la vista previa
 *      asociada a la URL de la imagen; si la URL es la misma, no la vuelven a
 *      pedir. Aquí el nombre del archivo lleva un resumen del origen: otra
 *      imagen, u otra versión de la misma, es otra dirección.
 *   2. Se servía la variante de 640 px —la `ruta` de la biblioteca es la de
 *      respaldo, no la grande— y Facebook pide al menos 1200 para la tarjeta
 *      grande. Aquí se parte del original, o de la variante mayor.
 *   3. La plantilla declaraba siempre 1200 × 630 aunque la foto midiera otra
 *      cosa, y algunas redes descartan la imagen cuando no cuadra. Ahora se
 *      declaran las medidas del archivo que se sirve (ver medidas()).
 *
 *  Y pesa poco: WhatsApp deja sin imagen las vistas previas de archivos de
 *  más de unos 300 KB. Se baja la calidad hasta quedar por debajo.
 *
 *  ── Ni recorte ni franjas ─────────────────────────────────────────────────
 *
 *  La imagen se sirve ENTERA y con su proporción: sólo se reduce y se
 *  comprime. La primera versión la encajaba en 1200 × 630 —recortando las
 *  apaisadas y poniendo franjas granates a las demás— y el cliente no quería
 *  ni lo uno ni lo otro.
 *
 *  La contrapartida: Facebook, X y LinkedIn enseñan la tarjeta en 1.91:1 y
 *  recortan ellos lo que sobre. Por eso al cliente se le pide 1200 × 630, que
 *  es justo esa proporción y se ve completa en todas partes.
 *
 *  ── Lo que NO hace ────────────────────────────────────────────────────────
 *
 *  · No toca los SVG: GD no los lee. Con uno elegido se cae al siguiente
 *    candidato (la imagen general o la de por defecto).
 *  · No lanza. Si algo falla devuelve null y la página sigue con la imagen
 *    siguiente: una tarjeta sin la foto elegida es mejor que una página caída.
 */

declare(strict_types=1);

namespace Intranet\Core;

use GdImage;
use Throwable;

final class ImagenSocial
{
    /** El lado mayor de lo que se sirve. Es el ancho que pide Facebook. */
    public const LADO = 1200;

    /** La medida que se recomienda subir: 1.91:1, la de las tarjetas. */
    public const ANCHO = 1200;
    public const ALTO  = 630;

    /** Donde se dejan, relativo a la raíz del sitio. */
    public const CARPETA = 'assets/subidos/compartir';

    /** Por debajo de esto WhatsApp sí enseña la imagen. */
    private const PESO_MAXIMO = 280 * 1024;

    /* Sube este número si cambia la forma de componer la tarjeta: todas las
       direcciones cambian y las redes vuelven a pedirlas. */
    private const RECETA = '2';

    /**
     * La imagen para compartir de una fila de `medios`, generándola si falta.
     *
     * @param  array<string, mixed> $medio  con `id`, `ruta`, `original` y `variantes`
     * @return string|null ruta relativa a la raíz del sitio
     */
    public static function de(array $medio): ?string
    {
        try {
            $id     = (int) ($medio['id'] ?? 0);
            $origen = self::origen($medio);

            if ($id <= 0 || $origen === null) {
                return null;
            }

            $huella  = substr(md5(implode('|', [
                $origen, (int) @filesize($origen), (int) @filemtime($origen), self::RECETA,
            ])), 0, 10);
            $relativa = self::CARPETA . '/og-' . $id . '-' . $huella . '.jpg';
            $destino  = self::raiz() . '/' . $relativa;

            if (is_file($destino)) {
                return $relativa;
            }

            return self::componer($origen, $destino) ? $relativa : null;
        } catch (Throwable $e) {
            error_log('[imagen-social] ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Borra las tarjetas de una imagen. Se llama al borrarla de la biblioteca.
     */
    public static function olvidar(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        foreach (glob(self::raiz() . '/' . self::CARPETA . '/og-' . $id . '-*.jpg') ?: [] as $archivo) {
            @unlink($archivo);
        }
    }

    /**
     * El archivo del que partir: el original si está y GD lo lee, si no la
     * variante más ancha, y si no la ruta de respaldo.
     *
     * Las rutas salen de la base, pero se comprueba igual que el archivo caiga
     * dentro del sitio: un valor con «..» no puede leer nada de fuera.
     */
    private static function origen(array $medio): ?string
    {
        $candidatas = [];

        if (!empty($medio['original'])) {
            $candidatas[] = (string) $medio['original'];
        }

        $variantes = $medio['variantes'] ?? null;
        $variantes = is_string($variantes) ? json_decode($variantes, true) : $variantes;

        if (is_array($variantes) && !empty($variantes['base']) && !empty($variantes['anchos'])) {
            $anchos = array_map('intval', (array) $variantes['anchos']);
            rsort($anchos);

            // El formato de respaldo (jpg o png) va primero: GD siempre lo lee.
            $formatos = array_reverse(array_values((array) ($variantes['formatos'] ?? ['jpg'])));

            foreach ($anchos as $a) {
                foreach ($formatos as $f) {
                    $candidatas[] = $variantes['base'] . '-' . $a . '.' . $f;
                }
            }
        }

        if (!empty($medio['ruta'])) {
            $candidatas[] = (string) $medio['ruta'];
        }

        $raiz = realpath(self::raiz());

        foreach ($candidatas as $c) {
            if (preg_match('~\.(jpe?g|png|webp|gif)$~i', $c) !== 1) {
                continue;
            }

            $real = realpath(self::raiz() . '/' . ltrim($c, '/'));

            if ($real !== false && $raiz !== false && str_starts_with($real, $raiz) && is_file($real)) {
                return $real;
            }
        }

        return null;
    }

    private static function componer(string $origen, string $destino): bool
    {
        $info = @getimagesize($origen);

        if (!is_array($info) || $info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000) {
            return false;
        }

        $foto = @imagecreatefromstring((string) file_get_contents($origen));

        if (!$foto instanceof GdImage) {
            return false;
        }

        [$w, $h] = [imagesx($foto), imagesy($foto)];

        // Entera y con su proporción: sólo se reduce, y nunca se agranda.
        $escala = min(1, self::LADO / max($w, $h));
        $dw     = max(1, (int) round($w * $escala));
        $dh     = max(1, (int) round($h * $escala));

        $lienzo = imagecreatetruecolor($dw, $dh);
        // Un PNG con transparencia saldría con el fondo negro en JPG.
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopyresampled($lienzo, $foto, 0, 0, 0, 0, $dw, $dh, $w, $h);

        $carpeta = dirname($destino);

        if (!is_dir($carpeta) && !@mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            return false;
        }

        imageinterlace($lienzo, true);

        // Se escribe en un temporal y se mueve: dos visitas a la vez no pueden
        // dejar a medias el archivo que la otra está sirviendo.
        $temporal = $destino . '.' . bin2hex(random_bytes(4)) . '.tmp';

        foreach ([86, 80, 74, 68, 62] as $calidad) {
            if (!@imagejpeg($lienzo, $temporal, $calidad)) {
                @unlink($temporal);

                return false;
            }

            clearstatcache(true, $temporal);

            if ((int) @filesize($temporal) <= self::PESO_MAXIMO) {
                break;
            }
        }

        @chmod($temporal, 0644);

        if (!@rename($temporal, $destino)) {
            @unlink($temporal);

            return is_file($destino);
        }

        return true;
    }

    /**
     * Ancho y alto de una imagen del sitio, para declararlos en og:image.
     *
     * @return array{0:int, 1:int}
     */
    public static function medidas(string $relativa): array
    {
        $info = @getimagesize(self::raiz() . '/' . ltrim($relativa, '/'));

        return is_array($info) ? [(int) $info[0], (int) $info[1]] : [self::ANCHO, self::ALTO];
    }

    private static function raiz(): string
    {
        return dirname(__DIR__, 3);
    }
}
