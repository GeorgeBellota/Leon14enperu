<?php
/**
 * Menu — qué páginas pueden salir en la navegación, y cómo se llaman.
 *
 * ── Por qué existe este archivo ────────────────────────────────────────────
 *
 * Porque esta lista estaba escrita DOS veces: una en assets/parciales/cabecera.php,
 * que dibuja el menú, y otra en Controllers\ConfiguracionController, que dibuja
 * las casillas del panel. Las dos llevaban un comentario diciendo que tenían
 * que decir lo mismo. No bastó.
 *
 * El rediseño de 2026 renombró tres páginas —«el-papa» pasó a «papa-leon-xiv»,
 * «tierra-de-santos» a «santos» y «materiales» a «subsidios»— y actualizó la
 * lista de la cabecera. La del panel se quedó con los nombres viejos.
 *
 * El resultado fue una avería silenciosa y desconcertante: la casilla «Papa
 * León XIV» aparecía MARCADA en el panel y la entrada no salía en la web. El
 * rótulo era el mismo, pero por debajo el panel guardaba «el-papa» y la web
 * buscaba «papa-leon-xiv». Y como esa pantalla reescribe el ajuste entero al
 * guardar, cualquier cambio —la fecha del viaje, el píxel de Meta— tiraba las
 * entradas que el panel ya no sabía nombrar.
 *
 * Con una sola lista, eso no puede repetirse.
 *
 * ── Por qué no sale de la tabla `paginas` ──────────────────────────────────
 *
 * El menú de una web pública no es el índice de todo lo que existe. Hay
 * páginas —privacidad, cookies, aviso legal— que viven en el pie y no deben
 * poder subir a la navegación principal por el hecho de estar publicadas.
 */

declare(strict_types=1);

namespace Intranet\Publico;

final class Menu
{
    /**
     * Todo lo que puede salir en el menú: clave de página → rótulo.
     *
     * El rótulo es el del menú, no el título de la página: en la barra manda
     * lo corto. «Subsidios Pastorales» es como lo nombra el cliente y como lo
     * busca una parroquia.
     *
     * Añadir una entrada aquí es una línea, y la navegación de escritorio, la
     * de móvil y las casillas del panel salen de ella a la vez.
     *
     * @return array<string, string>
     */
    public static function catalogo(): array
    {
        return [
            // Las del diseño, en el orden en que se dibujaron.
            'papa-leon-xiv'        => 'Papa León XIV',
            'sedes'                => 'Sedes',
            'agenda'               => 'Agenda',
            'cep'                  => 'CEP',
            'subsidios'            => 'Subsidios Pastorales',
            'voluntariado'         => 'Voluntariado',
            'noticias'             => 'Noticias',
            'prensa'               => 'Prensa',
            'contacto'             => 'Contacto',

            // Publicadas, pero fuera del menú mientras nadie las añada.
            'santos'               => 'Santos del Perú',
            'logo-y-lema'          => 'Logo y lema',
            'preguntas-frecuentes' => 'Preguntas frecuentes',
            'guia-del-peregrino'   => 'Guía del peregrino',
            'participa'            => 'Participa',
            'multimedia'           => 'Multimedia',
            'en-directo'           => 'En directo',
            'donativo'             => 'Donaciones',
            'patrocinios'          => 'Patrocinios',
            'transparencia'        => 'Transparencia',
        ];
    }

    /**
     * Las nueve del diseño: lo que se enseña mientras nadie haya elegido nada,
     * y también si la base no responde. Es la navegación que se dibujó y la
     * que cabe sin apretar.
     *
     * @return list<string>
     */
    public static function porDefecto(): array
    {
        return [
            'papa-leon-xiv', 'sedes', 'agenda', 'cep', 'subsidios',
            'voluntariado', 'noticias', 'prensa', 'contacto',
        ];
    }

    /**
     * Claves guardadas → claves de hoy.
     *
     * Hace tres cosas, y las tres hacen falta:
     *
     *  · Traduce las páginas que se renombraron, con el MISMO mapa que usa
     *    Rutas para los 301. Así un ajuste guardado antes del rediseño sigue
     *    valiendo y el menú se arregla solo, sin tocar la base a mano.
     *  · Descarta lo que ya no existe en el catálogo, para que una clave
     *    muerta no llegue a pintarse como un enlace roto.
     *  · Quita repetidas, que es lo que pasaría si alguien tuviera guardadas
     *    la vieja y la nueva de la misma página.
     *
     * @param  iterable<mixed> $claves
     * @return list<string>
     */
    public static function normalizar(iterable $claves): array
    {
        $catalogo = self::catalogo();
        $limpias  = [];

        foreach ($claves as $clave) {
            $clave = trim((string) $clave);

            if ($clave === '') {
                continue;
            }

            $clave = Rutas::mudanza($clave) ?? $clave;

            if (isset($catalogo[$clave]) && !in_array($clave, $limpias, true)) {
                $limpias[] = $clave;
            }
        }

        return $limpias;
    }
}
