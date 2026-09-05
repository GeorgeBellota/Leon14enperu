<?php
/**
 * ============================================================================
 *  Slug — la parte legible de la dirección de una pieza.
 * ============================================================================
 *
 *      «León XIV regresa al Perú»  →  /noticias/leon-xiv-regresa-al-peru/
 *
 *  ── La regla que importa ─────────────────────────────────────────────────
 *
 *  El slug se calcula UNA VEZ, al crear la pieza, y no se vuelve a tocar
 *  aunque el titular cambie.
 *
 *  No es un detalle de implementación: si alguien corrige una errata del
 *  titular y el slug se regenera, la dirección que ya se compartió por
 *  WhatsApp, la que indexó Google y la que está enlazada desde la CEP dejan
 *  de existir a la vez. Se puede cambiar a mano desde el panel —a veces hace
 *  falta—, pero con aviso y a propósito, nunca como efecto secundario de
 *  arreglar una tilde.
 *
 *  ── Por qué no se usa iconv ──────────────────────────────────────────────
 *
 *  iconv con ASCII//TRANSLIT convierte «qué» en «qu?» en Windows, y el slug
 *  sale roto. Se usa un mapa explícito, igual que el normalizador de nombres
 *  de ubigeo que ya tiene el proyecto.
 */

declare(strict_types=1);

namespace Intranet\Cms;

final class Slug
{
    /** Lo que el visitante nunca debería ver en una dirección. */
    private const EQUIVALENCIAS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'ª' => 'a', 'º' => 'o',
        '·' => '-', '—' => '-', '–' => '-', '«' => '', '»' => '',
        '“' => '', '”' => '', '‘' => '', '’' => '',
    ];

    /**
     * Palabras que no aportan nada a una dirección.
     *
     * Se quitan sólo si sobra longitud: «El Papa» no puede quedarse en
     * «papa» si eso es todo lo que hay.
     */
    private const VACIAS = [
        'de', 'del', 'la', 'el', 'los', 'las', 'y', 'o', 'a', 'en', 'un',
        'una', 'al', 'por', 'con', 'para', 'su', 'sus', 'lo', 'se', 'que',
    ];

    /** Longitud máxima. Cabe en la columna y no rompe la línea al compartir. */
    private const TOPE = 90;

    /**
     * El slug de un titular.
     *
     * @param callable(string):bool|null $ocupado Devuelve true si ese slug ya
     *        está usado. Si se pasa, se busca variante con sufijo numérico.
     */
    public static function desde(string $titular, ?callable $ocupado = null): string
    {
        $base = self::normalizar($titular);

        if ($ocupado === null || !$ocupado($base)) {
            return $base;
        }

        // Dos noticias pueden llamarse igual. Se numeran, y se empieza en 2
        // porque la primera es la que no lleva sufijo.
        for ($n = 2; $n <= 200; $n++) {
            $intento = mb_substr($base, 0, self::TOPE - 4) . '-' . $n;

            if (!$ocupado($intento)) {
                return $intento;
            }
        }

        // Doscientas colisiones no es un caso real; aun así, algo hay que
        // devolver antes que entrar en un bucle sin salida.
        return mb_substr($base, 0, self::TOPE - 7) . '-' . bin2hex(random_bytes(3));
    }

    /** Limpia un slug escrito a mano en el panel. */
    public static function normalizar(string $texto): string
    {
        $t = strtr(mb_strtolower(trim($texto), 'UTF-8'), self::EQUIVALENCIAS);
        $t = preg_replace('~[^a-z0-9]+~u', '-', $t) ?? '';
        $t = trim($t, '-');

        if ($t === '') {
            return 'pieza';
        }

        if (mb_strlen($t) <= self::TOPE) {
            return $t;
        }

        // Si no cabe, se recorta por palabras y se descartan las vacías antes
        // que cortar a mitad de una.
        $partes = array_values(array_filter(
            explode('-', $t),
            static fn (string $p): bool => !in_array($p, self::VACIAS, true)
        ));

        $corto = '';
        foreach ($partes as $p) {
            $siguiente = $corto === '' ? $p : $corto . '-' . $p;

            if (mb_strlen($siguiente) > self::TOPE) {
                break;
            }

            $corto = $siguiente;
        }

        return $corto !== '' ? $corto : mb_substr($t, 0, self::TOPE);
    }

    /** ¿Puede una dirección llevar este slug? */
    public static function valido(string $slug): bool
    {
        return $slug !== ''
            && mb_strlen($slug) <= self::TOPE
            && preg_match('~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $slug) === 1;
    }
}
