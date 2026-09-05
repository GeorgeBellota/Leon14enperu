<?php
/**
 * ============================================================================
 *  Rutas del sitio público.
 * ============================================================================
 *
 *  Un mapa de dirección → vista. Sustituye a la carpeta por página: antes,
 *  publicar /prensa/ exigía crear la carpeta prensa/ con su index.php dentro,
 *  con las cuarenta y cinco líneas de <head> repetidas una vez más.
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
 *   1. Una entrada aquí.
 *   2. Un archivo en views/ con el mismo nombre que la vista.
 *
 *  Ni carpeta, ni index.php, ni <head> repetido.
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
            'voluntariado'         => ['clave' => 'voluntariado',         'vista' => 'voluntariado'],

            // Colecciones con página de detalle. La ruta de las dos últimas
            // lleva barra dentro a propósito: /cep/obispos/ cuelga de la
            // sección institucional, y el mapa de rutas es texto, así que
            // la jerarquía de tres niveles no cuesta nada.
            'tierra-de-santos'     => ['clave' => 'tierra-de-santos',     'vista' => 'coleccion'],
            'cep/obispos'          => ['clave' => 'obispos',              'vista' => 'coleccion'],
            'cep/comisiones'       => ['clave' => 'comisiones',           'vista' => 'coleccion'],
            'participa'            => ['clave' => 'participa',            'vista' => 'coleccion'],
            'multimedia'           => ['clave' => 'multimedia',           'vista' => 'coleccion'],
            'el-papa'              => ['clave' => 'el-papa',              'vista' => 'el-papa'],
            'sedes'                => ['clave' => 'sedes',                'vista' => 'sedes'],
            'agenda'               => ['clave' => 'agenda',               'vista' => 'agenda'],
            'cep'                  => ['clave' => 'cep',                  'vista' => 'cep'],
            'noticias'             => ['clave' => 'noticias',             'vista' => 'noticias'],
            'preguntas-frecuentes' => ['clave' => 'preguntas-frecuentes', 'vista' => 'preguntas-frecuentes'],
            'guia-del-peregrino'   => ['clave' => 'guia-del-peregrino',   'vista' => 'guia-del-peregrino'],
            'materiales'           => ['clave' => 'materiales',           'vista' => 'materiales'],
            'en-directo'           => ['clave' => 'en-directo',           'vista' => 'en-directo'],
            'donativo'             => ['clave' => 'donativo',             'vista' => 'donativo'],
            'patrocinios'          => ['clave' => 'patrocinios',          'vista' => 'patrocinios'],
            'prensa'               => ['clave' => 'prensa',               'vista' => 'prensa'],
            'contacto'             => ['clave' => 'contacto',             'vista' => 'contacto'],
            'transparencia'        => ['clave' => 'transparencia',        'vista' => 'transparencia'],
            'aviso-legal'          => ['clave' => 'aviso-legal',          'vista' => 'aviso-legal'],
            'privacidad'           => ['clave' => 'privacidad',           'vista' => 'privacidad'],
            'cookies'              => ['clave' => 'cookies',              'vista' => 'cookies'],
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

    /** ¿Existe esta clave de página? Lo usa el despachador de la portada. */
    public static function existe(string $clave): bool
    {
        return isset(self::todas()[$clave]);
    }
}
