<?php
/**
 * ============================================================================
 *  Rutas del sitio público.
 * ============================================================================
 *
 *  Un mapa de dirección → vista. Publicar una página son dos cosas: una línea
 *  aquí y un archivo en views/. Ni carpeta, ni bootstrap repetido.
 *
 *  ── Por qué en código y no en la base ────────────────────────────────────
 *
 *  La tabla `paginas` tiene una columna `ruta` y podría alimentar esto. No se
 *  hace, y es deliberado: si el enrutado dependiera de la base, una caída de
 *  MySQL no dejaría el sitio sin contenido —eso ya está resuelto con los
 *  textos de reserva— sino sin NINGUNA página, ni siquiera un 404 decente.
 *
 *  El contenido viene de la base; el mapa de direcciones, no.
 *
 *  ── Añadir una página ────────────────────────────────────────────────────
 *
 *   1. Una entrada en todas().
 *   2. Un archivo en views/ con el mismo nombre que la vista.
 *   3. Si se quiere en el menú, añadir la clave al ajuste `menu.visibles`.
 */

declare(strict_types=1);

namespace Intranet\Publico;

final class Rutas
{
    /**
     * clave  → la que usa el CMS en la tabla `paginas` y el menú
     * vista  → el archivo de views/, sin extensión
     *
     * @return array<string, array{clave: string, vista: string}>
     */
    public static function todas(): array
    {
        return [
            // ── Las catorce del rediseño ─────────────────────────────────
            'papa-leon-xiv'        => ['clave' => 'papa-leon-xiv',        'vista' => 'papa-leon-xiv'],
            'sedes'                => ['clave' => 'sedes',                'vista' => 'sedes'],
            'agenda'               => ['clave' => 'agenda',               'vista' => 'agenda'],
            'cep'                  => ['clave' => 'cep',                  'vista' => 'cep'],
            'subsidios'            => ['clave' => 'subsidios',            'vista' => 'subsidios'],
            'voluntariado'         => ['clave' => 'voluntariado',         'vista' => 'voluntariado'],
            'noticias'             => ['clave' => 'noticias',             'vista' => 'noticias'],
            'prensa'               => ['clave' => 'prensa',               'vista' => 'prensa'],
            'contacto'             => ['clave' => 'contacto',             'vista' => 'contacto'],
            'santos'               => ['clave' => 'santos',               'vista' => 'santos'],
            'logo-y-lema'          => ['clave' => 'logo-y-lema',          'vista' => 'logo-y-lema'],
            'preguntas-frecuentes' => ['clave' => 'preguntas-frecuentes', 'vista' => 'preguntas-frecuentes'],

            // ── Colecciones con página de detalle ────────────────────────
            // La ruta de las dos últimas lleva barra dentro a propósito:
            // /cep/obispos/ cuelga de la sección institucional, y el mapa es
            // texto, así que la jerarquía de tres niveles no cuesta nada.
            'participa'            => ['clave' => 'participa',            'vista' => 'coleccion'],
            'multimedia'           => ['clave' => 'multimedia',           'vista' => 'coleccion'],
            'cep/obispos'          => ['clave' => 'obispos',              'vista' => 'coleccion'],
            'cep/comisiones'       => ['clave' => 'comisiones',           'vista' => 'coleccion'],

            // ── El resto de páginas del sitio ────────────────────────────
            'guia-del-peregrino'   => ['clave' => 'guia-del-peregrino',   'vista' => 'guia-del-peregrino'],
            'en-directo'           => ['clave' => 'en-directo',           'vista' => 'en-directo'],
            'donativo'             => ['clave' => 'donativo',             'vista' => 'donativo'],
            'patrocinios'          => ['clave' => 'patrocinios',          'vista' => 'patrocinios'],
            'transparencia'        => ['clave' => 'transparencia',        'vista' => 'transparencia'],
            'aviso-legal'          => ['clave' => 'aviso-legal',          'vista' => 'aviso-legal'],
            'privacidad'           => ['clave' => 'privacidad',           'vista' => 'privacidad'],
            'cookies'              => ['clave' => 'cookies',              'vista' => 'cookies'],
        ];
    }

    /**
     * ── Direcciones que cambiaron de nombre ──────────────────────────────
     *
     * El rediseño renombró tres páginas. Las direcciones antiguas llevan
     * meses publicadas, compartidas e indexadas, así que no se apagan: se
     * responden con un 301 a la nueva. El buscador traspasa el
     * posicionamiento y nadie se encuentra un 404.
     *
     * Se resuelve aquí y no en el .htaccess a propósito: así el mapa de
     * direcciones está entero en un archivo y sigue funcionando en un
     * servidor que no lea los .htaccess.
     *
     * @return array<string, string>  antigua → nueva
     */
    public static function mudanzas(): array
    {
        return [
            'el-papa'          => 'papa-leon-xiv',
            'tierra-de-santos' => 'santos',
            'materiales'       => 'subsidios',
        ];
    }

    /**
     * La ruta pedida, o null si no existe.
     *
     * @return array{clave: string, vista: string}|null
     */
    public static function resolver(string $ruta): ?array
    {
        return self::todas()[trim($ruta, '/')] ?? null;
    }

    /** La dirección nueva de una que se mudó, o null si no se mudó. */
    public static function mudanza(string $ruta): ?string
    {
        return self::mudanzas()[trim($ruta, '/')] ?? null;
    }

    /** ¿Existe esta clave de página? Lo usa el despachador de la portada. */
    public static function existe(string $clave): bool
    {
        return isset(self::todas()[$clave]);
    }
}
