<?php
/**
 * ============================================================================
 *  Imagen — de un archivo subido a la familia que sirve el sitio.
 * ============================================================================
 *
 *  El sitio no sirve una imagen: sirve una familia. Cada foto existe en dos
 *  formatos y varios anchos, y el navegador elige el que le conviene:
 *
 *      <picture>
 *        <source type="image/webp" srcset="…-640.webp 640w, …-1024.webp 1024w">
 *        <img src="…-1024.jpg" srcset="…-640.jpg 640w, …-1024.jpg 1024w">
 *      </picture>
 *
 *  Quien sube una foto desde el panel sube una sola, la grande. Esta clase se
 *  encarga del resto: reduce, convierte y deja los archivos con el mismo
 *  nombre y la misma convención que los que ya hay en assets/img, para que
 *  las imágenes nuevas y las antiguas se sirvan igual.
 *
 *  ── Lo que NO hace ───────────────────────────────────────────────────────
 *  · No agranda. Si suben una imagen de 800 px, no se inventa una de 1600:
 *    se generan sólo los anchos que caben dentro del original.
 *  · No toca los SVG. Un vector ya escala solo; reescalarlo sería empeorarlo.
 */

declare(strict_types=1);

namespace Intranet\Core;

use GdImage;

final class Imagen
{
    /**
     * Los anchos que sirve el sitio.
     *
     * 640 cubre el móvil, que es de donde llega la mayoría del tráfico; 1024
     * el escritorio corriente; 1600 las pantallas densas y las fotos que se
     * ven a todo el ancho. Coinciden con los que ya existen en assets/img.
     */
    public const ANCHOS = [640, 1024, 1600];

    /** Calidad de compresión. 82 es el punto donde deja de notarse la pérdida. */
    private const CALIDAD = 82;

    /**
     * Tope del lado mayor de la imagen de origen.
     *
     * No es un capricho: descomprimir una imagen ocupa ancho × alto × 4 bytes
     * en memoria, así que una de 20 000 px tumbaría el proceso de PHP antes de
     * llegar a redimensionar nada. A 8000 px el peor caso son unos 256 MB.
     */
    private const LADO_MAXIMO = 8000;

    public function __construct(private string $carpeta)
    {
    }

    /**
     * Deja en disco la familia completa a partir de un archivo ya validado.
     *
     * @param  string $temporal Ruta del archivo de origen.
     * @param  string $base     Nombre base, sin extensión ni ancho.
     * @return array{ruta:string, base:string, ancho:int, alto:int, peso:int, mime:string, variantes:array<string,mixed>}
     */
    public function derivar(string $temporal, string $base): array
    {
        $info = @getimagesize($temporal);

        if (!is_array($info)) {
            throw new ErrorDeNegocio('Ese archivo no es una imagen que se pueda procesar.', 'archivo');
        }

        [$anchoOriginal, $altoOriginal] = $info;

        if ($anchoOriginal > self::LADO_MAXIMO || $altoOriginal > self::LADO_MAXIMO) {
            throw new ErrorDeNegocio(
                'La imagen es demasiado grande: ' . $anchoOriginal . '×' . $altoOriginal . ' px. '
                . 'El máximo son ' . self::LADO_MAXIMO . ' px de lado. Redúcela antes de subirla.',
                'archivo'
            );
        }

        $origen = @imagecreatefromstring((string) file_get_contents($temporal));

        if (!$origen instanceof GdImage) {
            throw new ErrorDeNegocio('No se pudo leer la imagen. Vuelve a exportarla y súbela otra vez.', 'archivo');
        }

        // Con transparencia el respaldo no puede ser JPG: perdería el fondo y
        // saldría negro. En ese caso el respaldo es PNG.
        $conAlfa    = $this->tieneTransparencia($origen, (int) $info[2]);
        $respaldo   = $conAlfa ? 'png' : 'jpg';
        $formatos   = ['webp', $respaldo];

        $this->prepararCarpeta();

        $anchos = $this->anchosPara($anchoOriginal);
        $peso   = 0;

        foreach ($anchos as $ancho) {
            $lienzo = $this->escalar($origen, $ancho, $anchoOriginal, $altoOriginal, $conAlfa);

            foreach ($formatos as $formato) {
                $destino = $this->carpeta . '/' . $base . '-' . $ancho . '.' . $formato;

                $guardada = match ($formato) {
                    'webp' => @imagewebp($lienzo, $destino, self::CALIDAD),
                    'png'  => @imagepng($lienzo, $destino, 6),
                    default => @imagejpeg($lienzo, $destino, self::CALIDAD),
                };

                if (!$guardada) {
                    // Lo ya escrito se retira: media familia en disco es peor
                    // que ninguna, porque el <picture> pediría archivos que no
                    // están y el navegador mostraría el hueco.
                    $this->limpiar($base);

                    throw new ErrorDeNegocio('No se pudo guardar la imagen en el servidor.', 'archivo');
                }

                @chmod($destino, 0644);
                $peso += (int) @filesize($destino);
            }
        }

        // La ruta de respaldo es la del ancho intermedio: es la que va en el
        // <img src> y la que se ve si el navegador no entiende srcset.
        $intermedio = $anchos[(int) floor((count($anchos) - 1) / 2)];
        $relativa   = $this->relativa();

        $mayor = (int) max($anchos);
        $alto  = (int) round($altoOriginal * ($mayor / $anchoOriginal));

        return [
            'ruta'      => $relativa . '/' . $base . '-' . $intermedio . '.' . $respaldo,
            'base'      => $relativa . '/' . $base,
            'ancho'     => $mayor,
            'alto'      => $alto,
            'peso'      => $peso,
            'mime'      => $respaldo === 'png' ? 'image/png' : 'image/jpeg',
            'variantes' => [
                'base'     => $relativa . '/' . $base,
                'anchos'   => $anchos,
                'formatos' => $formatos,
            ],
        ];
    }

    /**
     * Un SVG se guarda tal cual: es un vector y no tiene familia.
     *
     * @return array{ruta:string, base:string, ancho:int, alto:int, peso:int, mime:string, variantes:null}
     */
    public function vector(string $temporal, string $base): array
    {
        $this->prepararCarpeta();

        $destino = $this->carpeta . '/' . $base . '.svg';

        if (!@copy($temporal, $destino)) {
            throw new ErrorDeNegocio('No se pudo guardar la imagen en el servidor.', 'archivo');
        }

        @chmod($destino, 0644);

        $medida = @getimagesize($destino);

        return [
            'ruta'      => $this->relativa() . '/' . $base . '.svg',
            'base'      => $this->relativa() . '/' . $base,
            'ancho'     => is_array($medida) ? (int) $medida[0] : 0,
            'alto'      => is_array($medida) ? (int) $medida[1] : 0,
            'peso'      => (int) @filesize($destino),
            'mime'      => 'image/svg+xml',
            'variantes' => null,
        ];
    }

    /**
     * Borra la familia entera de una imagen, por su nombre base público.
     *
     * Se hace por patrón porque los archivos son varios y sus nombres se
     * derivan del base: no hace falta guardar la lista para poder borrarlos.
     */
    public function borrarFamilia(?string $basePublica): void
    {
        if ($basePublica === null || $basePublica === '') {
            return;
        }

        $raizSitio = dirname(__DIR__, 3);
        $carpeta   = realpath($this->carpeta);

        if ($carpeta === false) {
            return;
        }

        // El nombre base viene de la base de datos, pero se comprueba igual que
        // el archivo resultante cae dentro de la carpeta de medios: un valor
        // manipulado con «..» no puede llevar el borrado a otra parte.
        foreach (glob($raizSitio . '/' . ltrim($basePublica, '/') . '*') ?: [] as $archivo) {
            $real = realpath($archivo);

            if ($real !== false && str_starts_with($real, $carpeta) && is_file($real)) {
                @unlink($real);
            }
        }
    }

    /**
     * Los anchos que caben dentro del original, sin agrandar nunca.
     *
     * @return array<int, int>
     */
    private function anchosPara(int $anchoOriginal): array
    {
        $anchos = array_values(array_filter(
            self::ANCHOS,
            static fn (int $a): bool => $a <= $anchoOriginal
        ));

        // Una imagen más pequeña que el menor de los anchos se queda con su
        // tamaño: es preferible a no generar ninguna variante.
        return $anchos === [] ? [$anchoOriginal] : $anchos;
    }

    private function escalar(GdImage $origen, int $ancho, int $anchoOriginal, int $altoOriginal, bool $conAlfa): GdImage
    {
        $alto = max(1, (int) round($altoOriginal * ($ancho / $anchoOriginal)));

        $lienzo = imagecreatetruecolor($ancho, $alto);

        if ($conAlfa) {
            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
            imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
        } else {
            // Sin esto, una imagen con transparencia que se guarda en JPG sale
            // con el fondo negro en vez de blanco.
            imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        }

        imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);

        return $lienzo;
    }

    /**
     * ¿La imagen usa de verdad la transparencia?
     *
     * La pregunta importa porque decide el formato de respaldo, y con él el
     * peso. No basta con mirar si el PNG *admite* canal alfa: casi todos lo
     * admiten y casi ninguno lo usa. Un banner de 1920×1080 sin un solo píxel
     * transparente pesa 2,8 MB guardado como PNG y unos 300 KB como JPG; dar
     * por transparente todo lo que podría serlo multiplicaría por nueve lo que
     * descarga cada visitante.
     *
     * Así que se miran los píxeles. Se muestrea en rejilla en vez de recorrer
     * la imagen entera: con transparencia real, el primer píxel transparente
     * aparece enseguida, y una zona translúcida sobrevive al muestreo porque
     * ocupa muchas casillas.
     */
    private function tieneTransparencia(GdImage $imagen, int $tipo): bool
    {
        if ($tipo !== IMAGETYPE_PNG && $tipo !== IMAGETYPE_WEBP) {
            return false;
        }

        // Paleta con color transparente declarado: no hace falta mirar más.
        if (!imageistruecolor($imagen) && imagecolortransparent($imagen) >= 0) {
            return true;
        }

        $ancho = imagesx($imagen);
        $alto  = imagesy($imagen);

        // Un paso que deje del orden de 20 000 muestras en imágenes grandes y
        // recorra entera las pequeñas.
        $paso = max(1, (int) floor(sqrt(($ancho * $alto) / 20000)));

        for ($y = 0; $y < $alto; $y += $paso) {
            for ($x = 0; $x < $ancho; $x += $paso) {
                if (((imagecolorat($imagen, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function prepararCarpeta(): void
    {
        if (!is_dir($this->carpeta) && !@mkdir($this->carpeta, 0775, true) && !is_dir($this->carpeta)) {
            throw new ErrorDeNegocio('No se pudo crear la carpeta de imágenes en el servidor.', 'archivo');
        }
    }

    /** Lo que ya se hubiera escrito, si algo falla a mitad. */
    private function limpiar(string $base): void
    {
        foreach (glob($this->carpeta . '/' . $base . '-*') ?: [] as $archivo) {
            @unlink($archivo);
        }
    }

    /** La carpeta, relativa a la raíz del sitio. */
    private function relativa(): string
    {
        return str_replace('\\', '/', substr($this->carpeta, strlen(dirname(__DIR__, 3)) + 1));
    }
}
