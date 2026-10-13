<?php
/**
 * ============================================================================
 *  Compartir — qué imagen sale al pegar el enlace de una página.
 * ============================================================================
 *
 *  Una sola regla, y la usan la web y el panel: así la pantalla de SEO
 *  enseña exactamente la imagen que verá quien pegue el enlace.
 *
 *  Gana la primera que haya, en este orden:
 *
 *    1. La propia de lo que se comparte: la «Imagen al compartir» de una
 *       noticia —o su portada—, o la foto de una ficha interna (/sedes/lima/).
 *       Es lo más concreto, y no puede pisarla la de la página que la contiene.
 *    2. La elegida para la página en SEO → la página.
 *    3. La imagen general, elegida en SEO arriba del todo.
 *    4. La de por defecto, que va con el código: el pantallazo del banner de la portada.
 *
 *  Cada una se sirve entera y con su proporción, reducida y comprimida por
 *  Core\ImagenSocial, con una dirección que cambia cuando cambia la imagen.
 *  Ver esa clase.
 *
 *  ── «Forzar la actualización» ─────────────────────────────────────────────
 *
 *  El ajuste `seo.version` va pegado a la dirección de TODAS las imágenes.
 *  Subirlo cambia todas a la vez, y cualquier red que vuelva a leer una
 *  página tiene que descargar la imagen de nuevo aunque no haya cambiado.
 *  Sirve para el día que la caché de una red se queda con algo viejo.
 */

declare(strict_types=1);

namespace Intranet\Cms;

use Intranet\Core\Contenedor;
use Intranet\Core\ImagenSocial;
use Intranet\Models\Ajuste;

final class Compartir
{
    /** La de por defecto: el pantallazo del banner de la portada (la lámina 1), entero. */
    public const POR_DEFECTO = 'assets/img/og/og-portada.jpg';

    public const AJUSTE_GENERAL = 'seo.og_imagen_id';
    public const AJUSTE_VERSION = 'seo.version';

    private ?Ajuste $ajustes = null;

    /** @var array<int, array<string, mixed>|null> */
    private array $medios = [];

    public function __construct(private Contenedor $c)
    {
    }

    /**
     * La imagen que se comparte, ya resuelta.
     *
     * @return array{ruta:string, url:string, origen:string, ancho:int, alto:int}
     *         `origen` es propia, pagina, general o defecto
     */
    public function resolver(int $propia = 0, int $pagina = 0): array
    {
        foreach (['propia' => $propia, 'pagina' => $pagina, 'general' => $this->generalId()] as $origen => $id) {
            if ($id <= 0) {
                continue;
            }

            $medio = $this->medio($id);
            $ruta  = $medio === null ? null : ImagenSocial::de($medio);

            if ($ruta !== null) {
                return $this->salida($ruta, $origen);
            }
        }

        return $this->salida(self::POR_DEFECTO, 'defecto');
    }

    /** La imagen general elegida en el panel, o 0 si no hay. */
    public function generalId(): int
    {
        return (int) $this->ajustes()->leer(self::AJUSTE_GENERAL, '0');
    }

    public function version(): int
    {
        return max(1, (int) $this->ajustes()->leer(self::AJUSTE_VERSION, '1'));
    }

    /**
     * El enlace de una página con una marca que cambia cuando cambia su
     * imagen o cuando se fuerza la actualización.
     *
     * Es el truco para WhatsApp: guarda la vista previa por enlace exacto y no
     * deja pedir que la renueve. Con la marca, para WhatsApp es un enlace que
     * nunca ha visto y lo lee de cero. La página es la misma —la marca no
     * cambia nada de lo que se pinta— y la dirección canónica sigue limpia.
     */
    public function enlaceFresco(string $urlPagina, string $rutaImagen): string
    {
        $marca = substr(md5($rutaImagen . '|' . $this->version()), 0, 6);

        return $urlPagina . (str_contains($urlPagina, '?') ? '&' : '?') . 'c=' . $marca;
    }

    /** @return array{ruta:string, url:string, origen:string, ancho:int, alto:int} */
    private function salida(string $ruta, string $origen): array
    {
        /* La de por defecto es un archivo del código: su versión es el resumen
           de su contenido, así que reemplazarlo también cambia la dirección.
           Las generadas ya llevan el resumen en el nombre. */
        $huella = $origen === 'defecto'
            ? \Intranet\Publico\Sitio::version(dirname(__DIR__, 3) . '/' . $ruta) . '-'
            : '';

        // Las medidas del archivo de verdad: cada imagen conserva su proporción.
        [$ancho, $alto] = ImagenSocial::medidas($ruta);

        return [
            'ruta'   => $ruta,
            'url'    => rtrim((string) $this->c->config('url.sitio', ''), '/') . '/' . $ruta
                        . '?v=' . $huella . $this->version(),
            'origen' => $origen,
            'ancho'  => $ancho,
            'alto'   => $alto,
        ];
    }

    /** @return array<string, mixed>|null */
    private function medio(int $id): ?array
    {
        if (!array_key_exists($id, $this->medios)) {
            $this->medios[$id] = $this->c->bd()->fila(
                'SELECT `id`, `ruta`, `original`, `variantes` FROM `medios` WHERE `id` = :id',
                ['id' => $id]
            );
        }

        return $this->medios[$id];
    }

    private function ajustes(): Ajuste
    {
        return $this->ajustes ??= new Ajuste($this->c);
    }
}
