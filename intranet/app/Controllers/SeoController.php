<?php
/**
 * SeoController — lo que se ve al compartir un enlace, en un solo sitio.
 *
 * Antes la «Imagen al compartir» de cada página vivía escondida dentro de
 * Páginas → la página → Datos para buscadores, y nadie la encontraba: en
 * octubre de 2026 ninguna página la tenía puesta y todos los enlaces salían
 * en WhatsApp con la misma tarjeta vieja.
 *
 * Esta pantalla las enseña todas a la vez, con la imagen que sale DE VERDAD
 * en cada una —la propia, la general o la de por defecto, según Cms\Compartir—
 * y da arriba la imagen general del sitio y el botón de forzar la
 * actualización en las redes.
 *
 * Editar una página lleva a la pantalla de siempre, /paginas/{clave}/seo:
 * no hay dos formularios para lo mismo.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Cms\Compartir;
use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\ImagenSocial;
use Intranet\Core\Request;
use Intranet\Models\Ajuste;
use Intranet\Models\Medio;
use Intranet\Models\Noticia;
use Intranet\Models\Pagina;

final class SeoController extends Controller
{
    public function listar(Request $peticion): void
    {
        $compartir = new Compartir($this->c);
        $paginas   = (new Pagina($this->c))->todas();

        // El SEO de la página Noticias: es lo que hereda una noticia sin
        // imagen propia.
        $ogNoticias = 0;

        foreach ($paginas as $i => $p) {
            $tarjeta = $compartir->resolver(0, (int) ($p['og_imagen_id'] ?? 0));
            $url     = $this->c->urlSitio((string) $p['ruta']);

            $paginas[$i]['tarjeta'] = $tarjeta;
            $paginas[$i]['url']     = $url;
            $paginas[$i]['fresco']  = $compartir->enlaceFresco($url, $tarjeta['ruta']);

            if ($p['clave'] === 'noticias') {
                $ogNoticias = (int) ($p['og_imagen_id'] ?? 0);
            }
        }

        $noticias = (new Noticia($this->c))->listar('publicada', 1, 100)['filas'];

        foreach ($noticias as $i => $n) {
            $propia  = (int) ($n['og_imagen_id'] ?? 0) ?: (int) ($n['imagen_id'] ?? 0);
            $tarjeta = $compartir->resolver($propia, $ogNoticias);
            $url     = $this->c->urlSitio('noticias/' . $n['slug'] . '/');

            $noticias[$i]['tarjeta'] = $tarjeta;
            $noticias[$i]['url']     = $url;
            $noticias[$i]['fresco']  = $compartir->enlaceFresco($url, $tarjeta['ruta']);
        }

        $this->ver('seo/listar', [
            'titulo'    => 'SEO y redes',
            'paginas'   => $paginas,
            'noticias'  => $noticias,
            'generalId' => $compartir->generalId(),
            'general'   => $compartir->resolver(),
            'version'   => $compartir->version(),
            'medios'    => (new Medio($this->c))->paraElegir(),
        ]);
    }

    /** La imagen general: la que sale donde no se haya elegido otra. */
    public function guardarGeneral(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $id    = (int) $peticion->entero('og_imagen_id', 0);
        $medio = $id > 0 ? (new Medio($this->c))->buscar($id) : null;

        if ($id > 0 && $medio === null) {
            $this->conError('Esa imagen ya no está en la biblioteca.', '/seo');
        }

        /* Se prepara ya la versión para compartir, para que el primero que
           pegue un enlace no tenga que esperar a que se haga. Y se comprueba
           que se pueda hacer: un SVG, o un archivo que falta en el servidor,
           dejarían el ajuste puesto y la web enseñando otra cosa. */
        if ($medio !== null && ImagenSocial::de($medio) === null) {
            $this->conError(
                'Esa imagen no sirve para compartir: no es una foto (un SVG, por ejemplo) '
                . 'o su archivo no está en el servidor. Elige otra.',
                '/seo'
            );
        }

        (new Ajuste($this->c))->escribir(Compartir::AJUSTE_GENERAL, $id > 0 ? (string) $id : null);

        Auditoria::registrar($this->c, 'editar', 'ajustes', null, [
            'clave' => Compartir::AJUSTE_GENERAL,
            'valor' => $id > 0 ? $id : 'por defecto',
        ]);

        $this->conExito(
            $id > 0
                ? 'Imagen general guardada. Ya sale en todas las páginas que no tienen una propia.'
                : 'Vuelve a salir el pantallazo del banner de la portada en las páginas sin imagen propia.',
            '/seo'
        );
    }

    /**
     * Cambia la dirección de TODAS las imágenes para compartir a la vez.
     *
     * No borra ni rehace nada: sube un número que va pegado a cada dirección.
     * Así cualquier red que vuelva a leer una página tiene que descargar la
     * imagen otra vez, y también cambian los «enlaces para compartir» de la lista.
     */
    public function refrescar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $compartir = new Compartir($this->c);
        $nueva     = $compartir->version() + 1;

        (new Ajuste($this->c))->escribir(Compartir::AJUSTE_VERSION, (string) $nueva);

        Auditoria::registrar($this->c, 'editar', 'ajustes', null, [
            'clave' => Compartir::AJUSTE_VERSION,
            'valor' => $nueva,
        ]);

        $this->conExito(
            'Listo: todas las imágenes tienen una dirección nueva. Comparte los «enlaces para compartir» '
            . 'de abajo, o pide a Facebook que vuelva a leer la página.',
            '/seo'
        );
    }
}
